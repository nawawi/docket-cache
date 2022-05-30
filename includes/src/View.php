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
    private $po;
    private $info;
    private $do_preload;
    private $do_flush;
    private $do_fetch;
    private $log_enable;
    private $log_max_size;
    private $cache_max_size;
    private $cronbot_enable;
    private $opcviewer_enable;

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
        $this->opcviewer_enable = $this->vcf()->is_dctrue('OPCVIEWER');
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

        $fileo->seek(\PHP_INT_MAX);
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
            if ($this->pt->cf()->is_dctrue('CHUNKCACHEDIR')) {
                $part = explode('-', $vache);
                if (isset($part[1])) {
                    $vache = $this->pt->get_chunk_path($part[0], $part[1]).$vache;
                }
            }
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

    public function index($po = false)
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

        // for addon
        $this->po = $po;

        $this->render('wrap');
        $this->pt->cx()->delay_expire();
    }

    private function tab_title($title, $cls = '')
    {
        $class = !empty($cls) ? ' '.$cls : '';
        echo '<h2 class="title'.$class.'">'.$title.'</h2>';
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
            case 'opcviewer':
                if (!$this->opcviewer_enable) {
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

        if ($this->cronbot_enable) {
            $lists['cronbot'] = esc_html__('Cronbot', 'docket-cache');
        }

        if ($this->opcviewer_enable) {
            $lists['opcviewer'] = esc_html__('OPcache', 'docket-cache');
        }

        $lists = apply_filters('docketcache/filter/view/tabnavbefore', $lists);

        if ($this->log_enable) {
            $lists['log'] = esc_html__('Cache Log', 'docket-cache');
        }

        $lists['config'] = esc_html__('Configuration', 'docket-cache');

        $lists = apply_filters('docketcache/filter/view/tabnavafter', $lists);

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
                    $timestamp = wp_date('Y-m-d H:i:s', $time);
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
        if (empty($default) || 'dcdefault' === $default) {
            $default = $this->vcf()->dcvalue(strtoupper($name), true);
        }

        $args = [];
        $args['idx'] = $idx;

        if (\is_array($idx) && !empty($idx)) {
            $args = array_merge($args, $idx);
        }

        if ($quiet) {
            $args['quiet'] = 1;
        }

        if (empty($args['nodefault'])) {
            $options['default'] = __('Default', 'docket-cache');
        }

        $options['enable'] = __('Enable', 'docket-cache');
        $options['disable'] = __('Disable', 'docket-cache');

        $cls = !empty($args['cls']) ? $args['cls'] : 'config-select';
        $default = $default ? 'enable' : 'disable';
        $html = '<select id="'.$name.'" class="'.$cls.'">';
        foreach ($options as $n => $v) {
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
        if (empty($default) || 'dcdefault' === $default) {
            $default = $this->vcf()->dcvalue(strtoupper($name), true);
        }

        $args = [];
        $args['idx'] = $idx;

        if (\is_array($idx) && !empty($idx)) {
            $args = array_merge($args, $idx);
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

        try {
            if (!\is_object($inst)) {
                $inst = new EventList($this->pt);
            }

            $inst->prepare_items();

            return $inst;
        } catch (\Throwable $e) {
            nwdcx_throwable(__METHOD__, $e);
        }

        return false;
    }

    private function opcache_view()
    {
        static $inst;

        try {
            if (!\is_object($inst)) {
                $inst = new OPcacheView($this->pt);
            }

            $inst->prepare_items();

            return $inst;
        } catch (\Throwable $e) {
            nwdcx_throwable(__METHOD__, $e);
        }

        return false;
    }

    private function code_focus()
    {
        if (empty($_GET['nx'])) {
            return;
        }

        $nx = sanitize_text_field($_GET['nx']);

        if (WpConfig::is_runtimeconst($nx) && WpConfig::is_runtimefalse()) {
            return;
        }

        $code = '<script id="docket-cache-focus" data-noptimize="1">'.\PHP_EOL;
        $code .= '(function($) {';
        $code .= '$(document).ready(function() {';
        $code .= 'var fx = $(document).find("tr#'.$nx.'");';
        $code .= 'if ( fx && fx[0]) {';
        $code .= 'fx[0].scrollIntoView({block:"center"});';
        $code .= 'if ( $(document).find("div").hasClass("notice") ) {';
        $code .= 'fx.addClass("notice-focus");';
        $code .= 'var mx = $(document).find("div.notice");';
        $code .= 'var mg = mx.text();';
        $code .= 'var tc = "";';
        $code .= 'if ( mx.hasClass("notice-success") ) { tc = "text-green"; } else if ( mx.hasClass("notice-alert") ) { tc = "text-red"; } else if ( mx.hasClass("notice-warning") ) { tc = "text-maroon"; }';
        $code .= 'fx.children("td").append("<p id=\"innotice\" class=\""+tc+"\">"+mg+"</p>");';
        $code .= 'setTimeout(function() { fx.removeClass("notice-focus"); }, 3000);';
        $code .= '}';
        $code .= '}';
        $code .= '});';
        $code .= '})(jQuery);'.\PHP_EOL;
        $code .= '</script>';

        return $code;
    }

    private function tooltip($id)
    {
        $info = [
            'cronbot' => esc_html__('The Cronbot is an external service that pings your website every hour to keep WordPress Cron running actively.', 'docket-cache'),
            'log' => esc_html__('The cache log intends to provide information on how the cache works. For performance and security concerns, disable it if no longer needed.', 'docket-cache'),
            'opcviewer' => esc_html__('OPcache Viewer allows you to view OPcache status and usage.', 'docket-cache'),
            'advcpost' => esc_html__('Cache WP Queries for a post which results in faster data retrieval and reduced database workload. By default only for Post Type post, page and attachment.', 'docket-cache'),
            'advpost_posttype_all' => esc_html__('Allow Advanced Post Caching to cache any Post Type.', 'docket-cache'),
            'menucache' => esc_html__('Cache the WordPress dynamic navigation menu.', 'docket-cache'),
            'precache' => esc_html__('Increase cache performance by early loading cached objects based on the current URL.', 'docket-cache'),
            'mocache' => esc_html__('Improve the performance of the WordPress Translation function.', 'docket-cache'),
            'optwpquery' => esc_html__('Docket Cache will attempt to optimize WordPress core query when this option enabled.', 'docket-cache'),
            'optermcount' => esc_html__('Improve the performance of Word Term Count Update.', 'docket-cache'),
            'cronoptmzdb' => esc_html__('Docket Cache will optimize WordPress database tables using SQL optimizing syntax at scheduled times.', 'docket-cache'),
            'wpoptaload' => esc_html__('Reduce the size of Options Autoload by excluding non-WordPress Option from autoloading.', 'docket-cache'),
            'postmissedschedule' => esc_html__('Fix the WordPress Missed Schedule Error after scheduling a future blog post.', 'docket-cache'),
            'misc_tweaks' => esc_html__('Miscellaneous WordPress Tweaks. Including performance, security dan user experience.', 'docket-cache'),
            'wootweaks' => esc_html__('Miscellaneous WooCommerce Tweaks. Including performance, security dan user experience.', 'docket-cache'),
            'wooadminoff' => esc_html__('WooCommerce Admin or Analytics page is a new JavaScript-driven interface for managing stores. Enable this option to turn off any feature-related.', 'docket-cache'),
            'woowidgetoff' => esc_html__('Deactivate WooCommerce Widget feature.', 'docket-cache'),
            'woowpdashboardoff' => esc_html__('Remove the WooCommerce meta box in the WordPress Dashboard.', 'docket-cache'),
            'woocartfragsoff' => esc_html__('Remove the WooCommerce Cart Fragments.', 'docket-cache'),
            'wooextensionpageoff' => esc_html__('Remove WooCommerce Extensions page that includes My Subscriptions page.', 'docket-cache'),
            'wooaddtochartcrawling' => esc_html__('This option will add rules to robots.txt to prevent robots from crawling add-to-cart links.', 'docket-cache'),
            'pingback' => esc_html__('Deactivate the WordPress XML-RPC and Pingbacks related features.', 'docket-cache'),
            'headerjunk' => esc_html__('Remove WordPress features related to HTML header such as meta generators and feed links to reduce the page size.', 'docket-cache'),
            'wpemoji' => esc_html__('Deactivate the WordPress Emoji feature.', 'docket-cache'),
            'wpfeed' => esc_html__('Deactivate the WordPress Feed feature.', 'docket-cache'),
            'wpembed' => esc_html__('Deactivate the WordPress Embed feature.', 'docket-cache'),
            'wplazyload' => esc_html__('Deactivate the WordPress Lazy Load feature.', 'docket-cache'),
            'wpsitemap' => esc_html__('Deactivate the WordPress Auto-generate Sitemap feature.', 'docket-cache'),
            'wpapppassword' => esc_html__('Deactivate the WordPress Application Passwords feature.', 'docket-cache'),
            'wpdashboardnews' => esc_html__('Deactivate the WordPress Events & News feed in Dashboard.', 'docket-cache'),
            'wpbrowsehappy' => esc_html__('Enable this option to turn off Browse Happy HTTP API requests, which checks whether the user needs a browser update.', 'docket-cache'),
            'wpservehappy' => esc_html__('Enable this option to turn off the Serve Happy HTTP API request, which checks whether the user needs to update PHP.', 'docket-cache'),
            'preload' => esc_html__('Preload Object Cache by fetching administrator-related pages.', 'docket-cache'),
            'pageloader' => esc_html__('Display page loader when loading administrator pages.', 'docket-cache'),
            'stats' => esc_html__('Display Object Cache stats at Overview page.', 'docket-cache'),
            'gcaction' => esc_html__('Enable the Garbage Collector action button on the Overview page.', 'docket-cache'),
            'flushaction' => esc_html__('Enable the additional Flush Cache action button on the Configuration page.', 'docket-cache'),
            'autoupdate' => esc_html__('Enable automatic plugin updates for Docket Cache.', 'docket-cache'),
            'checkversion' => esc_html__('Allows Docket Cache to check any critical future version that requires removing cache files after doing the updates, purposely to avoid error-prone.', 'docket-cache'),
            'flush_shutdown' => esc_html__('Flush Object Cache when deactivate / uninstall.', 'docket-cache'),
            'opcshutdown' => esc_html__('Flush OPcache when deactivate / uninstall.', 'docket-cache'),
            'maxsize_disk' => esc_html__('Maximum size of the cache storage on disk. The garbage collector will remove the cache file to free up storage space.', 'docket-cache'),
            'maxfile' => esc_html__('The maximum cache file can be stored on a disk. The cache file will free up by the garbage collector when triggered by WP Cron.', 'docket-cache'),
            'chunkcachedir' => esc_html__('Enable this option to chunk cache files into a smaller directory to avoid an excessive number of cache files in a single directory. Only enable this option if you have difficulty when manually clearing the cache or experience a slowdown when the cache becomes too large.', 'docket-cache'),
            'flush_stalecache' => esc_html__('Enable this option to immediately remove the stale cache abandoned by WordPress, WooCommerce and others after doing cache invalidation. By default, it will be removed by GC within 4 days. This option may cause exessive usage of I/O and CPU. Only enable this option if you require to keep storage space in check.', 'docket-cache'),
            'limithttprequest' => esc_html__('Limit HTTP requests in WP Admin.', 'docket-cache'),
            'httpheadersexpect' => esc_html__('By default, cURL sends the "Expect" header all the time which severely impacts performance. Enable this option, only send it if the body is larger than 1 MB.', 'docket-cache'),
            'rtpostautosave' => esc_html__('WordPress by default automatically saves a draft every 1 minute when editing or create a new post. Changing this behaviour can reduce the usage of server resource.', 'docket-cache'),
            'rtpostrevision' => esc_html__('Post revision is a copy of each edit made to a post or page, allowing the possibility of reverting to a previous version. However, have a revision too much can create a bad impact on database performance. Changing this behaviour can reduce the usage of server resource.', 'docket-cache'),
            'rtpostemptytrash' => esc_html__('This option allows you to change the number of days before WordPress permanently deletes posts, pages, attachments, and comments, from the trash bin. The default is 30 days. There is no confirmation alert when someone clicks on "Delete Permanently" if this option is set to "Disable Trash Bin".', 'docket-cache'),
            'rtpluginthemeeditor' => esc_html__('This option will completely disable the use of plugin and theme editor. If this option enabled, no plugins or theme file can be edited.', 'docket-cache'),
            'rtpluginthemeinstall' => esc_html__('This option will block users being able to use the plugin and theme installation/update functionality from the WordPress admin area.', 'docket-cache'),
            'rtimageoverwrite' => esc_html__('By default, WordPress creates a new set of images every time you edit image and restore the original. It leaves all the edits on the server. Enable this option to change this behaviour.', 'docket-cache'),
            'rtwpdebug' => esc_html__('Enable this option to turn on WordPress debugging.', 'docket-cache'),
            'rtwpdebugdisplay' => esc_html__('Enable this option to print debug info.', 'docket-cache'),
            'rtwpdebuglog' => esc_html__('Enable this option to log debug info.', 'docket-cache'),
            'rtwpcoreupdate' => esc_html__('This option will disable all core updates.', 'docket-cache'),
        ];

        $info = apply_filters('docketcache/filter/view/tooltips', $info);

        $text = isset($info[$id]) ? $info[$id] : esc_html__('No info available', 'docket-cache');

        return '<span tabindex="0" data-tooltip="'.$text.'"><span class="dashicons dashicons-editor-help"></span></span>';
    }

    private function action_notice()
    {
        static $done = false;
        if (!$done) {
            if (!empty($this->pt->notice) && !empty($this->pt->token)) {
                $type = 'success';
                if (@preg_match('@\-(failed|warn|error|info|success)$@', $this->pt->token, $mm)) {
                    switch ($mm[1]) {
                        case 'failed':
                        case 'error':
                            $type = 'error';
                            break;
                        case 'info':
                            $type = 'info';
                            break;
                        case 'warn':
                        case 'warning':
                            $type = 'warning';
                            break;
                    }
                }

                echo Resc::boxmsg($this->pt->notice, $type);

                if (WpConfig::is_runtimefalse() && !empty($this->pt->inruntime) && true === $this->pt->inruntime) {
                    $args['idx'] = 'config';
                    if (!empty($_GET['idx'])) {
                        $args['idx'] = sanitize_text_field($_GET['idx']);

                        if (!empty($_GET['adx'])) {
                            $args['adx'] = sanitize_text_field($_GET['adx']);
                        }
                    }
                    $action = $this->pt->action_query('runtime', $args);
                    echo Resc::runtimenotice($action);
                }
            } else {
                if (!empty($_GET['idx']) && !empty($_GET['adx'])) {
                    $args['idx'] = sanitize_text_field($_GET['idx']);
                    $adx = sanitize_text_field($_GET['adx']);

                    if ('config' === $args['idx'] && 'rtcnf' === $adx) {
                        $args['st'] = time();
                        $action = $this->pt->action_query('runtime', $args);

                        $args['adr'] = 1;
                        $action_rm = $this->pt->action_query('runtime', $args);
                        echo Resc::runtimenotice($action, $action_rm);
                    }
                }
            }
            $done = true;
        }
    }
}
