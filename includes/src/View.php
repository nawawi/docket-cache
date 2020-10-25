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
    public function tail($filepath, $limit = 100, $do_last = true)
    {
        $limit = (int) $limit;
        $file = new \SplFileObject($filepath);
        $file->seek(PHP_INT_MAX);
        $total_lines = $file->key();

        if ($limit > $total_lines) {
            $limit = $total_lines;
        }

        if ($do_last) {
            $total_lines = $total_lines > $limit ? $total_lines - $limit : $total_lines;
        } else {
            $total_lines = $limit;
        }

        $object = [];
        if ($total_lines > 0) {
            if ($do_last) {
                $object = new \LimitIterator($file, $total_lines);
            } else {
                $object = new \LimitIterator($file, 0, $total_lines);
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

        $icon = 'iVBORw0KGgoAAAANSUhEUgAAAHUAAAAfCAYAAADZa4KAAAAABmJLR0QA/wD/AP+gvaeTAAAACXBI';
        $icon .= 'WXMAAA7EAAAOxAGVKw4bAAAAB3RJTUUH5AoBDh8H6q06xwAAB7pJREFUaN7tmnuwlVUZxn/nHFgH';
        $icon .= 'kZvE5aiIl0AmBAOWkxQYiiJOmubUrDKzvGSUxSSIKThURhEihJNTk+YFggrWkGMjKYbp5ESStLQC';
        $icon .= 'Cs6h2+QhSbnVgWApnP7Yzzez3O19Lptz8Y/vndmz97e/9a3L+6z3eS/rq6IdEpzpBdQCpwLn6vsE';
        $icon .= '4CiwG9gONACHgEPWx2Zy6XKpaiOYg4H3AaOBdwL/An4H/ANoEtBDgLHACOA1YAvwe+vjH3M1v81A';
        $icon .= 'Dc7cKKv8A7DZ+rilDc+cqk0wFngDWGR9fCNXdzeDGpypBR4FNgKrrY972tt5cKY38H5gLnCt9fGV';
        $icon .= 'XOXdJMGZgcGZh4Mzl+m6qgP62xicsbl2O1+qywRDdwJPWR/X6+9hwZnRlQ5ifdwLXArcGpwZl6u9';
        $icon .= 'i0EFvg7UWx/XJv8dBuZlllshsAfV94zgzEm56rsI1ODMNcAR6+NDRYC8BvwKWBOc+dxxALsDeA6Y';
        $icon .= 'kau+C0BVYHSD9fGuMm2fAJ4Grg7O3HccwHrgguDM8Fz9nW+pM4D7WwCjEdgJPALsDc48GJzpX+G4';
        $icon .= 's4Alufo7EVSlHqcBLwZnBgRnppbxewsVRN0DbAKWBGfqKrDWesAEZ4blEHSepY4CXgEOABE4G3g+';
        $icon .= 'OOODMzODM2cIjCbg+8Ay6+MjwE+BVRUGPt8GPplD0HmgDgX+bX08bH08JOAeBK4AJgDrgzM7gjOz';
        $icon .= 'VJCYEJwZbn1cB8wDNgdnBrVz7F8D57VwfwgwEbBAnw5Y63DgDqCmE3V5LTCbQiWt0ty+L3CzmLOy';
        $icon .= 'iaiw0FtWmtHjUevj/cANwIWyqA8DI4F64ESlOP2tjy8CHwXWtTP4eRM4EJypLnP/GuAFYDnQCCw+';
        $icon .= 'TkCGqM/qCp7doc1VToYCf1Nccg6w8jjmeoKMaXClC+2hwftQKM4X+741wZmdCqBWWx9vAW4JzkwT';
        $icon .= 'yA8HZ7YqKv4KcE9w5jbr4642jN0MvK7J7y4D+stiigHANgqHBCt1/z3AuykcKPyCwiFDJmcDk7S+';
        $icon .= 'LfL/zeozk3OB/wiMZuB8YJw29zr1eyZwOlCnzX2y5tRYNNcVwAbgJl1Xqc+ewHs1H4CX9Emtcjow';
        $icon .= 'CHhV7gzgCIXDk/HAXuAp1QoAhgEXq+9fUjgV+z/KqBKwR8oENQFwwPjgzPf03wbr42eBOcCfgeu1';
        $icon .= 'oCbgm+3YVPsB08qmy9qtSPLb6QrWagX6t4DMr4+Wddfp+Q8k/VVROGCYrkKIkfIvB76h++cDP1T7';
        $icon .= 'QQK6msLp1HhtsFT6CbilRRsWGcvFQHZkuQyYnMxlLXCJ5jRN4zRTONKckmQKWW3gZOAnwClAf7nC';
        $icon .= 'oZQoC/YIzlwfnLmwJe0HZ0xwZkFw5rniWnBwpk9wZmJw5tngTHNw5t7W0AzO1ARnFgdnTinT5PMU';
        $icon .= 'TobSlCs7EFgF3Kjfg4EngYt0vRJYUERniD43CsCXgbOSNltkiZnUiwkyeVWWX6oKN5LC+XEpv19V';
        $icon .= '1H5OkjbeROH4MpNeyXqeAabq+ioVbAAeABYlz3wXuL2UpR4DDooKWkpDovVxvgKozcGZgcm9Juvj';
        $icon .= 'JuvjVFnNnODMBIF3ZXBmdpkF9xMFt0Vqgf8m/nG7fjeJMgcm4L2QPJc9c0zPrQL2AH9J2oxR/r1T';
        $icon .= 'dDZAbYvdQdZPKlH/9Sox595irtfVbmESQE0UfWZyONHLAbETwD5RLWKKT2mODcDHRMdvBdX6eEyK';
        $icon .= 'OakN1lVlffyxOl4UnBlVbLXWx9vkXxfKCv8EnFcin60GBlsfYyt+N5NJipgzBfdKlNBDFIb8ZP8y';
        $icon .= 'm6hKUfA+KTiTQwpORgngOll/KsfKzPHv6m9yiXsXiH5PE9XfkVj0/hIbpzWJwN1ihzGy6lnlUpq9';
        $icon .= 'QN/gTM9WrLVZ39uA+RTecuhVot3XgBHWx13WxwZR6dyiZmNEc+WkRj5tihYyKfHXL4m+Bsinnp5Y';
        $icon .= '3gpgpvxSP+CypL8msdJs0W3mb38kauytz1WK8DPZpQh/XMIIqSwF7pM/PgP4uDZQrWKVGkXFX0gs';
        $icon .= 'foXGnyLApyV49Ewsujqx1O8AtyauY7IwKAlqvZxwm4sI1sfdiviOlLDo3hmdBGcuF2WfFZx5V9Ls';
        $icon .= 'M8DqFob4qyLem7Wo6QnlLhGFPiP/uRjYqnvLgccVUGxIfOF+CocSVYr0F8iSalUl2wc8Kwu9IrF8';
        $icon .= 'gE9IN/NkzaUKKYv0/YT8e7XodYsY5i4Bv03PbFUAOl/R+UzNLQKbkxRzT8JQq7XW5XIxX0oAfwsl';
        $icon .= 'ZUB8FVhvfdx0vFl4cOYxpTkNGuN5UXZf6+Oy4Ewf4Gnr46S8/tPx0iP5/Shwd3DmN5W8BSigzhS1';
        $icon .= '/dP6+EBwpsb6eFRNHiqqIc/N1d85Upya3A68aX1c1k4wRyjBHglssz4+3kL7i4APWR+/mKu/c2u/';
        $icon .= 'mZ+8FxgbnLm0nRFZo3KpRa0AWgd8REl4Ll1hqUmQswqYZ33c3lEDKbL+MhBaAj6XDrZUWeshRYN3';
        $icon .= 'BmfGdBCgAxT9HcwB7QZLTYAYC3xapbpV1scjFQJ6CXAl8Fvr4w9ylXcjqALkHcAHlag/qfeL2grm';
        $icon .= 'OSo6NAJr9dJZLt0NqsCpVhXlOgrHbT8HHrM+bi3RdqgS9+uUoy4FGpK0Jpe3A6glgJsGXE2h7FVD';
        $icon .= 'odZ6IoU67W7gZ8Aa6+PhXL3dI/8DPjBuXO0kT+cAAAAASUVORK5CYII=';

        //$icon = plugin_dir_url($this->pt->file).'/includes/admin/header.png?'.time();
        $option = '';
        $html = '<nav class="nav-tab-wrapper">';
        $html .= '<div id="dclogo" style="background: url(data:image/png;base64,'.$icon.') no-repeat left;"></div>';
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
