<?php
/**
 * Docket Cache.
 *
 * @author  Nawawi Jamili
 * @license MIT
 *
 * @see    https://github.com/nawawi/docket-cache
 */

namespace Nawawi\DocketCache;

\defined('ABSPATH') || exit;

final class View
{
    private $pt;
    private $info;
    private $do_preload;
    private $do_flush;
    private $do_fetch;
    private $log_enable;
    private $log_max_size;
    private $cache_max_size;
    private $cronbot_enable;

    public function __construct(Plugin $pt)
    {
        $this->pt = $pt;
        $this->register();
    }

    public function vcf()
    {
        return $this->pt->cf();
    }

    public function register()
    {
        $this->log_enable = $this->vcf()->is_dctrue('LOG');
        $this->log_max_size = $this->pt->normalize_size($this->vcf()->dcvalue('LOG_SIZE'));
        $this->cache_max_size = $this->pt->normalize_size($this->vcf()->dcvalue('MAXSIZE'));
        $this->cronbot_enable = $this->vcf()->is_dctrue('CRONBOT');
    }

    /**
     * tail.
     */
    public function tail($filename, $limit = 100, $do_last = true, &$error = '')
    {
        $limit = (int) $limit;
        $object = [];

        try {
            $fileo = new \SplFileObject($filename, 'rb');
        } catch (\Throwable $e) {
            $error = $e->getMessage();

            return $object;
        }

        $fileo->seek(PHP_INT_MAX);
        $total_lines = $fileo->key();

        if ($limit > $total_lines) {
            $limit = $total_lines;
        }

        if ($do_last) {
            $total_lines = $total_lines > $limit ? $total_lines - $limit : $total_lines;
        } else {
            $total_lines = $limit;
        }

        if ($total_lines > 0) {
            if ($do_last) {
                $object = new \LimitIterator($fileo, $total_lines);
            } else {
                $object = new \LimitIterator($fileo, 0, $total_lines);
            }
        }

        return $object;
    }

    private function parse_log_query()
    {
        $ret = (object) [];
        $ret->default_order = 'last';
        $ret->default_sort = 'desc';
        $ret->default_line = 100;
        $ret->output = '';
        $ret->output_empty = true;
        $ret->log_size = 0;
        $ret->row_size = 15;

        if ($this->has_vcache()) {
            $cache_path = $this->pt->cache_path;
            $vache = $this->idx_vcache();
            $file = $cache_path.$vache.'.php';
            if ($this->pt->filesize($file) > 0) {
                $data = $this->pt->cache_get($file);
                if (false !== $data) {
                    $ret->output = $this->pt->export_var($data);
                    $ret->output_empty = empty($ret->output);
                    $ret->log_size = $this->pt->normalize_size(\strlen(serialize($data)));
                    $ret->output_size = $ret->log_size;
                    if ($ret->output_size > 15) {
                        $ret->row_size = 25;
                    }
                }
                unset($data);
            }
        } else {
            if (!empty($_GET['srt'])) {
                $srt = explode('-', sanitize_text_field($_GET['srt']));
                if (3 >= \count($srt)) {
                    $ret->default_order = $srt[0];
                    $ret->default_sort = $srt[1];
                    $ret->default_line = (int) $srt[2];
                }
            }

            $ret->output = $this->read_log($ret->default_line, 'last' === $ret->default_order ? true : false);
            $ret->output_empty = empty($ret->output);
            $ret->output_size = !$ret->output_empty ? \count($ret->output) : 0;
            $ret->log_size = $this->pt->get_logsize();
            if ($ret->output_size < 15) {
                $ret->row_size = $ret->output_size;
            }
            $ret->output = implode("\n", 'desc' === $ret->default_sort ? array_reverse($ret->output, true) : $ret->output);
        }

        return $ret;
    }

    private function cronbot_status()
    {
        $data = $this->pt->co()->get_part('cronbot');
        if (!empty($data) && \is_array($data)) {
            return $data;
        }

        return false;
    }

    private function cronbot_pings()
    {
        $data = $this->pt->co()->get_part('pings');
        if (!empty($data) && \is_array($data)) {
            return $data;
        }

        return false;
    }

    private function is_cronbot_connected()
    {
        $data = $this->cronbot_status();

        return !empty($data) && !empty($data['connected']);
    }

    private function ping_next()
    {
        $data = $this->cronbot_pings();
        if (!empty($data) && \is_array($data) && !empty($data['timestamp'])) {
            $timestamp = strtotime($data['timestamp']);

            return [
                'last' => wp_date('Y-m-d H:i:s', $timestamp),
                'next' => wp_date('Y-m-d H:i:s', strtotime('+1 hour', $timestamp)),
            ];
        }

        return false;
    }

    private function page($index)
    {
        $this->info = (object) $this->pt->get_info();
        $file = $this->pt->path.'/includes/admin/'.$index.'.php';
        if (@is_file($file)) {
            include_once $file;
        }
    }

    public function index()
    {
        if (!empty($_SERVER['REQUEST_URI']) && false === strpos($_SERVER['REQUEST_URI'], '/'.$this->pt->page)) {
            $url = network_admin_url('/'.$this->pt->page);
            echo '<meta http-equiv="refresh" content="3;url='.$url.'">';
            echo '<script>window.setTimeout(function() { location.assign("'.$url.'"); }, 2000);</script>';
            exit('<div class="wrap"><p>[ '.date('Y-m-d H:i:s').' ] Redirect to /'.$this->pt->page.'</p></div>');
        }

        $this->do_preload = false;
        $this->do_flush = false;
        $this->do_fetch = false;
        $this->page('wrap');
        $this->pt->cx()->delay_expire();
    }

    private function tab_title($title, $add_loader = true, $css = '')
    {
        echo '<h2 class="title'.(!empty($css) ? ' '.$css : '').'">'.$title.($add_loader ? '<span id="docket-cache-spinner" class="spinner is-active"></span>' : '').'</h2>';
    }

    private function tab_content()
    {
        $this->page($this->is_index());
    }

    private function tab_query($index, $args_extra = [])
    {
        $args = array_merge(
            [
                'idx' => $index,
            ],
            $args_extra
        );

        return network_admin_url(add_query_arg($args, $this->pt->page));
    }

    private function is_index()
    {
        $idx = !empty($_GET['idx']) ? sanitize_text_field($_GET['idx']) : 'overview';
        switch ($idx) {
            case 'log':
                if (!$this->log_enable) {
                    $idx = 'config';
                }
                break;
        }

        return $idx;
    }

    private function tab_current($index)
    {
        return $index === $this->is_index();
    }

    private function tab_active($index)
    {
        if ($this->tab_current($index)) {
            return ' nav-tab-active';
        }

        return '';
    }

    private function tab_nav()
    {
        $lists = [
             'overview' => esc_html__('Overview', 'docket-cache'),
             'log' => esc_html__('Cache Log', 'docket-cache'),
             'cronbot' => esc_html__('Cronbot', 'docket-cache'),
             'config' => esc_html__('Configuration', 'docket-cache'),
         ];

        if (!$this->log_enable) {
            unset($lists['log']);
        }

        if (!$this->cronbot_enable) {
            unset($lists['cronbot']);
        }

        $icon = 'iVBORw0KGgoAAAANSUhEUgAAAG8AAAAfCAYAAADp55OhAAAABHNCSVQICAgIfAhkiAAAAAlwSFlz';
        $icon .= 'AAAOywAADssB4+WgAQAAABl0RVh0U29mdHdhcmUAd3d3Lmlua3NjYXBlLm9yZ5vuPBoAAAlJSURB';
        $icon .= 'VGiB7ZtpkFTVFcd/53X3655BxSWJSkxMADfUiD0NowSVqVKrYgVcoHsmQTHlgoZFqUQSg1kgJsYY';
        $icon .= 'tXQKsdypQmRmWiDikrhEUQkVnO4eUcAVk5SKQjA6jg7dr3veyYf72nkMPUvPQL7Y/6qu6XfPueec';
        $icon .= 'e89y77t9RxggWuPhY0SYDHqiCKKII6oRwFEIiCC4vCGWPhZtzm8cqNwKBg/pjyGTCJ6uYs1CdZuo';
        $icon .= 'PvI2hXWJJF09+RQk3RAab6mcp8pRqvJALJl7fN+YXQH04bzWqZGRVsBdJLApq07jhCS7Bio0NZMQ';
        $icon .= 'H4cuE5FJqvwulnRe3TvmVuBHSeelEpE6EfcKtZw5sRXsHKzwzXH2y4rdiPBYTbOzavBmVlAKezgv';
        $icon .= 'Ux+aocrYT3fkf1a3loKCCOhgFShIW8K+zkWysZbczUMzt4JekW4InpZJhHab4EzCnpqaah87VNmp';
        $icon .= 'uH1tJmH/YKhyKuiGVfySqg+PwrVmte/IX+tn6Mw6T0mQNa3x8FlDUVSTdP6oSF06HqoZipwKuvGF';
        $icon .= '80R1USHvzKlbS8HPMHENHaLcZ4km04nQ5YNVJKA5zV0NskAXduutYPCwADLx4BmKvFy7mo9KMQXC';
        $icon .= 'zmLgNUSOTNeHbhvs5E9IsgthdXqz3TAEmyvwYJwg1uyO6tzi3phOWsbnqKwC3SrKK5kt9orUZKoH';
        $icon .= 'ozA6xnlIhGmV7Bs6rEzcHuOKvlu3lGyqIVTbFg+NLTWxwUhuCSoz3QPzywTrHqmyV62Pc3C5CmUh';
        $icon .= 'riLrM5sjk/bKCL7EsBAmi+ifAYa35192LflJZou9M52wH07Vhy7beEHVEWCyT1SStIcujrZkn1HR';
        $icon .= 'BRFCK1MXVB9etlZhNdI1eS+P5UsHS+GErV2F9QBH/YVcTbMzQ5WbBJ0oyphC0F2RSdgbUnH7Whd9';
        $icon .= 'WlQuSc0kFGvOZ7osmSuBwsMvNVR9oxylsebcVkUO3TdD2gP7AfOBuv+TvqFgFnDGQJktgJ5nlbGk';
        $icon .= 'c6OIXAFyFgGd7RaC5wnymSB3AGPlE/uGVJzh45qdTepyacB1HxpEBua19+O524EW7/MH4Gtlyt5N';
        $icon .= 'D3AYcP4g+i6GftfmscALwOvAg8CMQegp4qvAeQNlthTcUoSTm51HCDCdLpYHQoXxNcnc4ppk7nSx';
        $icon .= 'gqNF5QMR+9lMfXiJZWGh7jwJFlZtOJ9DBm6ntm+Yzv69EDOYSV8C5IC1QMRHDwC9BcvBwEGYSRev';
        $icon .= '/+p+jNkf4+CeqAWq+ug3AhNg84EaTJYf56NHgJG9yBjm01kMkLXe32qgVGWygSPxgt6y+jj6qlnh';
        $icon .= 'vKIBp05VZ2cS9s8Bok2d26LJ3K3RFieGyEqFm1XkKhW9Lxiyl7fECfQxWB+kM7irelgvxA+A7d5g';
        $icon .= 'FgJPAsXTmWnAP4FHgNeAqG9gTcA7wL+BTR6vH18HHOBfHj/AnzDB8ijwNMaREWApMApYhnHQ9BJ2';
        $icon .= 'zgHuBDYAnwO3Ae0erQZ4z7PpTeA7vn4LvPFtAV71bCjiFGAjJpMv8rVf6MlZ5o3tmH6367EV7Gzf';
        $icon .= 'kT9H4SuZ+vCSonMENNqU/VtN0jlHVe8QpR4kOkrsG/qTaaDVharOzwfGywuYwR8K3Ap8FxgPXOEN';
        $icon .= 'RoAfA0FMiR0BZDHR7cdc4EZMNjjA94CTMdkyDmgDrvFoS4H/APcCdwN/L2HXyUCr73mtJx/gbc/m';
        $icon .= '8Z7MC732U4EfAt/GlMlUDzurvX4xYLbXNgL4jdd2OvAroDGo9J8p5tTFmZ+ptxtGir0sNdm5LPYo';
        $icon .= 'nUX6uGT+JYWz04nwXIGbWhtCTeOa8m2ZeHgKoqdEW5wFPWWKyIG1y+noT7eHLBD2Bv4U8K7X/oJH';
        $icon .= 'OwI4C7geM/EOJuscn4wG4CPgeLqXijpP7h3e8+GYEudiHPEp8KynoxSqe+jwYyTQiCnhQWC9134m';
        $icon .= 'cA8mMACuAkb7+j0H7AK20r1UnOLZ9HvvOQBMsAAGWuqizU4TXfxWqkM3/GM6B/hpAhpryTUierWl';
        $icon .= '1pWZhuoRWXLrXJXtpQ62VQmW8WvF8cAbGP6e1cLy2pXdN0DvYMpWEeuANYD/7FYxJS/pfRoxGexH';
        $icon .= 'qA+73gRO6oV2J2YtPAG4pIdOv50dmIwvwn88WQwyBd7y2dkETLKAV0cTnNiHgbshttJ5XYfn59v5';
        $icon .= 'yPhSu0Udnr8f5dhoU+e2CUn+2zEsdxcBfuTnScfDowX9cIAqTwUuB5ZjovdMzFoEJtsCwPvAX4Gf';
        $icon .= 'YqK8CrNj9R+mvwdc57VN8tqeB04EXgSeAT7GlOQi2oGjPZnTMEHkxz2YYDjSe57i2QAm497BZM+l';
        $icon .= 'vj5Pes+HYOZvlq9Pb9jg2bHRs/MtoI50vX1cOhG6tZ/OA8ZL8WGHpRJ2Esyv8ZlE6PZMwl7aOjUy';
        $icon .= 'ssiTSoSvSSUifb13PYMpcTswa8rZPtoUzIZjC/AK3RuBEHAfsNP73IUpiQdh1p9fe3x3Ax9iAgLg';
        $icon .= 'Foxj38RkgF/XuZjA2Ak8AXyzhK1XAp9hNiDFSQa4GlN2P8AEzft0v0bM88a3HbMMFHed64FtmGxe';
        $icon .= 'hCmt8zzaDI+2BbNhmyUAmYTdklXn4nKuOpSC98NrsxW07nTdrg51rcOD4dyz+ax9riUaibbk79eF';
        $icon .= 'WJkt9sPRMc40WVj6NWWAOAiTKT0xDFN6cmXICmMypL0EzcIERl/yhmFeE7b3aD8Aswvd484PJpur';
        $icon .= 'MQ4eKALAgRjHEzRt7uKw2HPBuakMQbsh01A9It1VWCJK60lHZ5/f3THOQ8Vvba+FLhShZYiOg9KO';
        $icon .= 'AzNZ5SJH785x+6D5dZbS25djCv3QS6ELun/5+WLNSifsBzXgzCvnzkoqznCscC2qtSJ6CC7LapL5';
        $icon .= 'dG/86+NUhbEfjCadaUO5WlFBD7ROjYxM19tNz00qZuPehYKk6sP3pupD0f65Kygb6Ybgaan60C37';
        $icon .= 'QnYqYf+icodl72LP22Px0EUqRD/dkZ/f80rEYKAg6YT9S5DOWEtunwTGlxUlT/Uz8eAZKtYsUWdO';
        $icon .= 'NPnFSUDZWDeF/auq7EZc1tQknf4OhysoE73emG5riHyry3UXAa876txW7o1p+SQ8E9HTAgWuH7vS';
        $icon .= '2bxXrK1gN/T7vwqpeHCiiDUb9EMlsKZjR/bFUuVUF2K1bQ7WumKdC4xS5IFxLbkn9onVFQADcF4R';
        $icon .= '6Xh4NBbfF9WxKlgoeREiqmQRc/4nyuYu4fFxzc6mfWdyBUX8DxR1MjzQEuhgAAAAAElFTkSuQmCC';

        //$icon = plugin_dir_url($this->pt->file).'/includes/admin/header.svg?'.time();
        $option = '';
        $html = '<nav class="nav-tab-wrapper">';
        $html .= '<div id="dclogo" style="background: url(data:image/png;base64,'.$icon.') no-repeat left;"></div>';
        //$html .= '<div id="dclogo" style="background: url('.$icon.') no-repeat left;background-size: 130px 31px;"></div>';
        foreach ($lists as $id => $text) {
            $link = $this->tab_query($id);
            $active = $this->tab_active($id);
            $html .= '<a href="'.$link.'" class="nav-tab'.$active.'">'.$text.'</a>';

            $selected = $this->tab_current($id) ? ' selected' : '';
            $option .= '<option value="'.$id.'" data-action-link="'.$link.'"'.$selected.'>'.$text.'</option>';
        }

        $html .= '<select class="nav-select" style="display:none;">';
        $html .= $option;
        $html .= '</select>';

        $html .= '</nav>';

        echo $html;
    }

    private function change_timestamp($time)
    {
        $timestamp = '';
        $val = $this->vcf()->dcvalue('LOG_TIME');
        if ('utc' !== $val) {
            switch ($val) {
                case 'local':
                    $timestamp = wp_date('Y-m-d H:i:s T', $time);
                    break;
                case 'wp':
                    $timestamp = wp_date(get_option('date_format'), $time).' '.wp_date(get_option('time_format'), $time);
                    break;
            }
        }

        return $timestamp;
    }

    private function maybe_change_timestamp($data)
    {
        if (preg_match('@^\[([0-9A-Z\.\:\-\+ ]+)\]\s+@', $data, $mm)) {
            $tm = strtotime($mm[1]);
            $timestamp = $this->change_timestamp($tm);

            if (!empty($timestamp)) {
                $data = str_replace('['.$mm[1].']', '['.$timestamp.']', $data);
            }
        }

        return $data;
    }

    private function parse_log($data)
    {
        if (preg_match('@^\[([^\]]+)\]\s+([a-zA-Z]+):\s+"([^"]+)"\s+"([^"]+)"\s+"([^"]+)"@', $data, $mm)) {
            $tm = strtotime($mm[1]);
            $timestamp = $this->change_timestamp($tm);

            if (!empty($timestamp)) {
                $mm[1] = $timestamp;
            }

            return $mm;
        }

        return $data;
    }

    private function read_log($limit = 100, $do_last = true)
    {
        $limit = (int) $limit;
        $output = [];
        if ($this->pt->has_log($logfile)) {
            if (!@is_file($logfile)) {
                $output = [];
            }
            foreach ($this->tail($logfile, $limit, $do_last) as $line) {
                $line = trim($line);
                if (empty($line)) {
                    continue;
                }
                $output[] = $this->maybe_change_timestamp($line);
            }
        }

        return $output;
    }

    private function config_select_bool($name, $default = 'dcdefault')
    {
        if ('dcdefault' === $default) {
            $default = $this->vcf()->dcvalue(strtoupper($name));
        }

        $default = $default ? 'enable' : 'disable';
        $html = '<select id="'.$name.'" class="config-select">';
        foreach ([
            'default' => __('Default', 'docket-cache'),
            'enable' => __('Enable', 'docket-cache'),
            'disable' => __('Disable', 'docket-cache'),
        ] as $n => $v) {
            $action = $n.'-'.$name;
            $url = $this->pt->action_query($action, ['idx' => 'config']);
            $selected = $n === $default ? ' selected' : '';
            $html .= '<option value="'.$n.'" data-action-link="'.$url.'"'.$selected.'>'.$v.'</option>';
        }
        $html .= '</select>';

        return $html;
    }

    private function config_select_set($name, $options, $default = 'dcdefault', $idx = 'config')
    {
        if ('dcdefault' === $default) {
            $default = $this->vcf()->dcvalue(strtoupper($name));
        }

        if (\is_array($idx) && !empty($idx) && !empty($idx['idx'])) {
            $args = $idx;
        } else {
            $args = ['idx' => $idx];
        }

        $action = 'save-'.$name;
        $html = '<select id="'.$name.'" class="config-select">';
        foreach ((array) $options as $n => $v) {
            $args['nv'] = $n;
            $url = $this->pt->action_query($action, $args);
            $selected = $n === $default ? ' selected' : '';
            $html .= '<option value="'.$n.'" data-action-link="'.$url.'"'.$selected.'>'.$v.'</option>';
        }
        $html .= '</select>';

        return $html;
    }

    private function has_vcache()
    {
        return !empty($_GET['vcache']);
    }

    private function idx_vcache()
    {
        return $this->has_vcache() ? sanitize_text_field($_GET['vcache']) : '';
    }

    private function is_dropin_exists()
    {
        return $this->pt->cx()->exists();
    }

    private function is_dropin_validate()
    {
        return $this->pt->cx()->validate();
    }

    private function is_dropin_multinet()
    {
        return $this->pt->cx()->multinet_me();
    }

    private function cronbot_eventlist()
    {
        static $inst;

        if (!\is_object($inst)) {
            $inst = new EventList($this->pt);
        }

        $inst->prepare_items();

        return $inst;
    }
}
