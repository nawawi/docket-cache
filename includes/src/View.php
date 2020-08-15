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
    private $plugin;
    private $info;
    private $do_preload;
    private $do_flush;
    private $log_enable;
    private $log_max_size;
    private $cache_max_size;

    public function __construct(Plugin $plugin)
    {
        $this->plugin = $plugin;
        $this->log_enable = DOCKET_CACHE_LOG;
        $this->log_max_size = $this->plugin->normalize_size(DOCKET_CACHE_LOG_SIZE);
        $this->cache_max_size = $this->plugin->normalize_size(DOCKET_CACHE_MAXSIZE);
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
            $cache_path = $this->plugin->cache_path;
            $vache = $this->idx_vcache();
            $file = $cache_path.$vache.'.php';
            if ($this->plugin->filesize($file) > 0) {
                $data = $this->plugin->cache_get($file);
                if (false !== $data) {
                    $ret->output = $this->plugin->export_var($data);
                    $ret->output_empty = empty($ret->output);
                    $ret->log_size = $this->plugin->normalize_size(\strlen(serialize($data)));
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
            $ret->log_size = $this->plugin->get_logsize();
            if ($ret->output_size < 15) {
                $ret->row_size = $ret->output_size;
            }
            $ret->output = implode("\n", 'desc' === $ret->default_sort ? array_reverse($ret->output, true) : $ret->output);
        }

        return $ret;
    }

    private function page($index)
    {
        $this->info = (object) $this->plugin->get_info();
        $file = $this->plugin->path.'/includes/admin/'.$index.'.php';
        if (@is_file($file)) {
            include_once $file;
        }
    }

    public function index()
    {
        $this->do_preload = false;
        $this->do_flush = false;
        $this->page('wrap');
        $this->plugin->dropino->delay_expire();
    }

    private function tab_title($title, $add_loader = true, $css = '')
    {
        echo '<h2 class="title'.(!empty($css) ? ' '.$css : '').'">'.$title.($add_loader ? '<span id="docket-cache-spinner" class="spinner is-active"></span>' : '').'</h2>';
    }

    private function tab_content()
    {
        $this->page($this->is_index());
    }

    private function tab_query($index)
    {
        return network_admin_url(add_query_arg('idx', $index, $this->plugin->page));
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
        $html = '<nav class="nav-tab-wrapper">';
        $html .= '<a href="'.$this->tab_query('overview').'" class="nav-tab'.$this->tab_active('overview').'">'.__('Overview', 'docket-cache').'</a>';
        if ($this->log_enable) {
            $html .= '<a href="'.$this->tab_query('log').'" class="nav-tab'.$this->tab_active('log').'">'.__('Cache Log', 'docket-cache').'</a>';
        }
        $html .= '<a href="'.$this->tab_query('config').'" class="nav-tab'.$this->tab_active('config').'">'.__('Configure', 'docket-cache').'</a>';
        $html .= '</nav>';
        echo $html;
    }

    private function change_timestamp($time)
    {
        $timestamp = '';
        if ('utc' !== DOCKET_CACHE_LOG_TIME) {
            switch (DOCKET_CACHE_LOG_TIME) {
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
        if ($this->plugin->has_log()) {
            foreach ($this->plugin->tail(DOCKET_CACHE_LOG_FILE, $limit, $do_last) as $line) {
                $line = trim($line);
                if (empty($line)) {
                    continue;
                }
                $output[] = $this->maybe_change_timestamp($line);
            }
        }

        return $output;
    }

    private function config_select_bool($name, $default)
    {
        $default = $default ? 'enable' : 'disable';
        $html = '<select id="'.$name.'" class="config-select">';
        foreach ([
            'default' => __('Default', 'docket-cache'),
            'enable' => __('Enable', 'docket-cache'),
            'disable' => __('Disable', 'docket-cache'),
        ] as $n => $v) {
            $action = $n.'-'.$name;
            $url = $this->plugin->action_query($action, ['idx' => 'config']);
            $selected = $n === $default ? ' selected' : '';
            $html .= '<option value="'.$n.'" data-action-link="'.$url.'"'.$selected.'>'.$v.'</option>';
        }
        $html .= '</select>';

        return $html;
    }

    private function config_select_set($name, $options, $default)
    {
        $action = 'save-'.$name;
        $html = '<select id="'.$name.'" class="config-select">';
        foreach ((array) $options as $n => $v) {
            $url = $this->plugin->action_query(
                $action,
                [
                    'idx' => 'config',
                    'nv' => $n,
                ]
            );
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
}
