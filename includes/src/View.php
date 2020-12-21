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

    private function parse_subpage()
    {
        $index = $this->pt->get_subpage();
        if (!empty($index) && empty($_GET['idx'])) {
            $_GET['idx'] = $index;
        }
    }

    private function render($index)
    {
        $this->info = (object) $this->pt->get_info();
        $file = $this->pt->path.'/includes/admin/'.$index.'.php';

        $file = apply_filters('docketcache/filter/view/render', $file, $index);

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

        $this->parse_subpage();

        $this->do_preload = false;
        $this->do_flush = false;
        $this->do_fetch = false;
        $this->render('wrap');
        $this->pt->cx()->delay_expire();
    }

    private function tab_title($title)
    {
        echo '<h2 class="title">'.$title.'</h2>';
        $this->action_notice();
    }

    private function tab_content()
    {
        $this->render($this->is_index());
    }

    private function tab_query($index, $args_extra = [])
    {
        $args = array_merge(
            [
                'idx' => $index,
            ],
            $args_extra
        );

        $page = $this->pt->page;
        if (!empty($args['idx']) && $this->pt->is_subpage($args['idx'])) {
            $page = $page.'-'.$args['idx'];
        }

        return network_admin_url(add_query_arg($args, $page));
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
            case 'cronbot':
                if (!$this->cronbot_enable) {
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
        $lists = [];
        $lists['overview'] = esc_html__('Overview', 'docket-cache');

        if ($this->log_enable) {
            $lists['log'] = esc_html__('Cache Log', 'docket-cache');
        }

        if ($this->cronbot_enable) {
            $lists['cronbot'] = esc_html__('Cronbot', 'docket-cache');
        }

        $lists['config'] = esc_html__('Configuration', 'docket-cache');

        $lists = apply_filters('docketcache/filter/view/tabnav', $lists);

        $option = '';
        $html = '<nav class="nav-tab-wrapper">';
        $html .= '<div id="dclogo" style="background: url('.Resc::iconnav().') no-repeat left;"></div>';
        foreach ($lists as $id => $text) {
            $link = $this->tab_query($id);
            $active = $this->tab_active($id);
            $html .= '<a href="'.$link.'" class="nav-tab'.$active.'" title="'.$text.'">'.$text.'</a>';

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

    private function config_select_bool($name, $default = 'dcdefault', $idx = 'config', $quiet = false)
    {
        if ('dcdefault' === $default) {
            $default = $this->vcf()->dcvalue(strtoupper($name), true);
        }

        $args = [];
        $args['idx'] = $idx;

        if ($quiet) {
            $args['quiet'] = 1;
        }

        $default = $default ? 'enable' : 'disable';
        $html = '<select id="'.$name.'" class="config-select">';
        foreach ([
            'default' => __('Default', 'docket-cache'),
            'enable' => __('Enable', 'docket-cache'),
            'disable' => __('Disable', 'docket-cache'),
        ] as $n => $v) {
            $action = $n.'-'.$name;
            $url = $this->pt->action_query($action, $args);
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

    private function code_focus()
    {
        if (empty($_GET['nx'])) {
            return;
        }

        $nx = sanitize_text_field($_GET['nx']);
        $code = '<script id="docket-cache-focus">'.PHP_EOL;
        $code .= '(function($) {';
        $code .= '$(document).ready(function() {';
        $code .= 'var fx = $(document).find("tr#'.$nx.'");';
        $code .= 'if ( fx && fx[0]) {';
        $code .= 'fx[0].scrollIntoView({block:"center", behavior:"smooth"});';
        $code .= 'if ( $(document).find("div").hasClass("notice") ) {';
        $code .= 'fx.addClass("notice-focus");';
        $code .= 'setTimeout(function() { fx.removeClass("notice-focus"); }, 3000);';
        $code .= '}';
        $code .= '}';
        $code .= '});';
        $code .= '})(jQuery);'.PHP_EOL;
        $code .= '</script>';

        return $code;
    }

    private function tooltip($id)
    {
        $info = [
            'cronbot' => esc_html__('The Cronbot is an external service that pings your website every hour to keep WordPress Cron running actively.', 'docket-cache'),
            'log' => esc_html__('The cache log intends to provide information on how the cache works. For performance and security concerns, disable it if no longer needed.', 'docket-cache'),
            'advcpost' => esc_html__('Cache WordPress Post Queries which results in faster data retrieval and reduced database workload.', 'docket-cache'),
            'precache' => esc_html__('Increase cache performance by early loading cached objects based on the current URL.', 'docket-cache'),
            'mocache' => esc_html__('Improve the performance of the WordPress Translation function.', 'docket-cache'),
            'optwpquery' => esc_html__('Docket Cache will attempt to optimize WordPress core query when this option enabled.', 'docket-cache'),
            'optermcount' => esc_html__('Improve the performance of Word Term Count Update.', 'docket-cache'),
            'cronoptmzdb' => esc_html__('Docket Cache will optimize WordPress database tables using SQL optimizing syntax at scheduled times.', 'docket-cache'),
            'wpoptaload' => esc_html__('Reduce the size of Options Autoload by excluding non-WordPress Option from autoloading.', 'docket-cache'),
            'postmissedschedule' => esc_html__('Fix the WordPress Missed Schedule Error after scheduling a future blog post.', 'docket-cache'),
            'misc_tweaks' => esc_html__('Miscellaneous WordPress Tweaks. Including performance, security dan user experience.', 'docket-cache'),
            'wootweaks' => esc_html__('Miscellaneous WooCommerce Tweaks. Including performance, security dan user experience.', 'docket-cache'),
            'wooadminoff' => esc_html__('Deactivate WooCommerce Admin feature.', 'docket-cache'),
            'woowidgetoff' => esc_html__('Deactivate WooCommerce Widget feature.', 'docket-cache'),
            'woowpdashboardoff' => esc_html__('Remove the WooCommerce meta box in the WordPress Dashboard.', 'docket-cache'),
            'woocartfragsoff' => esc_html__('Remove the WooCommerce Cart Fragments.', 'docket-cache'),
            'pingback' => esc_html__('Remove the WordPress XML-RPC and Pingbacks related features.', 'docket-cache'),
            'headerjunk' => esc_html__('Remove WordPress features related to HTML header such as meta generators and feed links to reduce the page size.', 'docket-cache'),
            'wpemoji' => esc_html__('Remove the WordPress Emoji feature.', 'docket-cache'),
            'wpfeed' => esc_html__('Remove the WordPress Feed feature.', 'docket-cache'),
            'wpembed' => esc_html__('Remove the WordPress Embed feature.', 'docket-cache'),
            'wplazyload' => esc_html__('Remove the WordPress Lazy Load feature.', 'docket-cache'),
            'wpsitemap' => esc_html__('Remove the WordPress Auto-generate Sitemap feature.', 'docket-cache'),
            'wpapppassword' => esc_html__('Remove the WordPress Application Passwords feature.', 'docket-cache'),
            'preload' => esc_html__('Preload Object Cache by fetching administrator-related pages.', 'docket-cache'),
            'pageloader' => esc_html__('Display page loader when loading administrator pages.', 'docket-cache'),
            'stats' => esc_html__('Display Object Cache stats at Overview page.', 'docket-cache'),
            'gcaction' => esc_html__('Enable the Garbage Collector action button on the Overview page.', 'docket-cache'),
            'autoupdate' => esc_html__('Enable automatic plugin updates for Docket Cache.', 'docket-cache'),
            'checkversion' => esc_html__('Allows Docket Cache to check any critical future version that requires removing cache files before doing the updates, purposely to avoid error-prone.', 'docket-cache'),
        ];

        $info = apply_filters('docketcache/filter/view/tooltips', $info);

        $text = isset($info[$id]) ? $info[$id] : esc_html__('No info available', 'docket-cache');

        return '<span tabindex="0" data-tooltip="'.$text.'"><span class="dashicons dashicons-editor-help"></span></span>';
    }

    private function action_notice()
    {
        static $done = false;
        if (!$done && !empty($this->pt->notice) && !empty($this->pt->token)) {
            $type = 'failed' === substr($this->pt->token, -6) ? 'error' : 'success';
            echo Resc::boxmsg($this->pt->notice, $type);
            $done = true;
        }
    }
}
