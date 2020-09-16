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

final class Plugin extends Bepart
{
    /**
     * Plugin file.
     *
     * @var string
     */
    public $file;

    /**
     * Plugin slug.
     *
     * @var string
     */
    public $slug;

    /**
     * Plugin hook.
     *
     * @var string
     */
    public $hook;

    /**
     * Plugin path.
     *
     * @var string
     */
    public $path;

    /**
     * Plugin valid page uri.
     *
     * @var string
     */
    public $page;

    /**
     * Plugin action token.
     *
     * @var string
     */
    public $token;

    /**
     * Plugin screen name.
     *
     * @var string
     */
    public $screen;

    /**
     * The cache path.
     *
     * @var string
     */
    public $cache_path;

    /**
     * constructor.
     */
    public function __construct()
    {
        $this->slug = 'docket-cache';
        $this->file = DOCKET_CACHE_FILE;
        $this->hook = plugin_basename($this->file);
        $this->path = realpath(plugin_dir_path($this->file));
        $this->register_init();
    }

    public function dropino()
    {
        static $inst;
        if (!\is_object($inst)) {
            $inst = new Dropino($this->path);
        }

        return $inst;
    }

    public function canopt()
    {
        static $inst;
        if (!\is_object($inst)) {
            $inst = new Canopt();
        }

        return $inst;
    }

    public function constans()
    {
        static $inst;
        if (!\is_object($inst)) {
            $inst = new Constans();
        }

        return $inst;
    }

    /**
     * get_info.
     */
    public function get_info()
    {
        $status_code = [
             0 => __('Disabled', 'docket-cache'),
             1 => __('Enabled', 'docket-cache'),
             2 => __('Not Available', 'docket-cache'),
             3 => __('Unknown', 'docket-cache'),
         ];

        $yesno = [
             0 => esc_html__('No', 'docket-cache'),
             1 => esc_html__('Yes', 'docket-cache'),
         ];

        $cache_stats = (object) [
             'timeout' => 0,
             'size' => 0,
             'filesize' => 0,
             'files' => 0,
         ];

        $force_stats = false;
        if ($this->constans()->is_true('DOCKET_CACHE_WPCLI')) {
            $force_stats = true;
        }

        if ($this->constans()->is_true('DOCKET_CACHE_STATS')) {
            $cache_stats = $this->cache_size($this->cache_path, true, $force_stats);
        }

        $status = $this->get_status();
        $status_text_stats = '';
        $status_text = '';

        switch ($status) {
             case 1:
                 if ($cache_stats->files > 1) {
                     /* translators: %1$s = size, %2$s number of file */
                     $status_text_stats = sprintf(esc_html__(_n('%1$s of %2$s file', '%1$s of %2$s files', $cache_stats->files, 'docket-cache')), $this->normalize_size($cache_stats->size), $cache_stats->files);
                 }
                 $status_text = $status_code[1];
                 break;
             case 2:
                 $status_text = esc_html__('Disabled at runtime.', 'docket-cache');
                 break;
             default:
                 $status_text = $status_code[$status];
         }

        $opcache = $this->get_opcache_status();
        $opcache_text_stats = '';
        $opcache_status = 2;
        if (1 === $opcache->status) {
            /* translators: %1$s = size, %2$s number of file */
            $opcache_text_stats = sprintf(esc_html__(_n('%1$s of %2$s file', '%1$s of %2$s files', $opcache->files, 'docket-cache')), $this->normalize_size($opcache->size), $opcache->files);
            $opcache_status = 1;
        }

        return [
             'status_code' => $status,
             'status_text' => $status_text,
             'status_text_stats' => $status_text_stats,
             'opcache_code' => $opcache->status,
             'opcache_text' => $status_code[$opcache_status],
             'opcache_text_stats' => $opcache_text_stats,
             'php_memory_limit' => $this->normalize_size(@ini_get('memory_limit')),
             'wp_memory_limit' => $this->normalize_size(WP_MEMORY_LIMIT),
             'wp_max_memory_limit' => $this->normalize_size(WP_MAX_MEMORY_LIMIT),
             'write_dropin' => $yesno[is_writable(WP_CONTENT_DIR.'/object-cache.php')],
             'dropin_path' => $this->sanitize_rootpath(WP_CONTENT_DIR.'/object-cache.php'),
             'write_cache' => $yesno[is_writable($this->cache_path)],
             'cache_size' => $this->normalize_size($cache_stats->size),
             'cache_path_real' => $this->cache_path,
             'cache_path' => $this->sanitize_rootpath($this->cache_path),
             'log_file_real' => $this->constans()->value('DOCKET_CACHE_LOG_FILE'),
             'log_file' => $this->sanitize_rootpath($this->constans()->value('DOCKET_CACHE_LOG_FILE')),
             'log_enable' => $this->constans()->is_true('DOCKET_CACHE_LOG') ? 1 : 0,
             'log_enable_text' => $status_code[$this->constans()->is_true('DOCKET_CACHE_LOG') ? 1 : 0],
             'config_path' => $this->sanitize_rootpath($this->canopt()->path),
             'write_config' => $yesno[is_writable($this->canopt()->path)],
         ];
    }

    /**
     * sanitize_rootpath.
     */
    public function sanitize_rootpath($path)
    {
        return rtrim(str_replace([WP_CONTENT_DIR, ABSPATH], ['/'.basename(WP_CONTENT_DIR), '/'], $path), '/');
    }

    /**
     * get_status.
     */
    public function get_status()
    {
        if ($this->constans()->is_true('DOCKET_CACHE_DISABLED') || $this->constans()->is_true('DOCKET_CACHE_HALT')) {
            return 2;
        }

        if (!$this->dropino()->exists()) {
            return 0;
        }

        if ($this->dropino()->validate()) {
            return 1;
        }

        return 3;
    }

    /**
     * get_logsize.
     */
    public function get_logsize()
    {
        return $this->has_log() ? $this->normalize_size($this->filesize($this->constans()->value('DOCKET_CACHE_LOG_FILE'))) : 0;
    }

    /**
     * get_opcache_status.
     */
    public function get_opcache_status($is_raw = false)
    {
        $total_bytes = 0;
        $total_files = 0;
        $status = 0;
        $raw = [];
        if ($this->is_opcache_enable() && \function_exists('opcache_get_status')) {
            $data = @opcache_get_status();
            if (!empty($data) && \is_array($data) && (!empty($data['opcache_enabled']) || !empty($data['file_cache_only']))) {
                $status = 1;

                if (!empty($data['memory_usage']['used_memory'])) {
                    $total_bytes = $data['memory_usage']['used_memory'];
                }
                if (!empty($data['opcache_statistics']['num_cached_scripts'])) {
                    $total_files = $data['opcache_statistics']['num_cached_scripts'];
                }

                $raw = $data;
            }
        }

        $arr = [
            'status' => (int) $status,
            'size' => $total_bytes,
            'files' => (int) $total_files,
        ];

        if ($is_raw) {
            $arr['data'] = $raw;
        }

        return (object) $arr;
    }

    /**
     * normalize_size.
     */
    public function normalize_size($size)
    {
        $size = wp_convert_hr_to_bytes($size);
        $size = str_replace([',', ' ', 'B'], '', size_format($size));
        if (is_numeric($size)) {
            $size = $size.' '.__('Byte', 'docket-cache');
        }

        return $size;
    }

    /**
     * flush_cache.
     */
    public function flush_cache($cleanup = false)
    {
        $this->constans()->maybe_define('DOCKET_CACHE_DOING_FLUSH', true);
        $this->dropino()->delay();
        $ret = $this->cachedir_flush($this->cache_path, $cleanup);
        if (false === $ret) {
            $this->dropino()->undelay();

            return false;
        }

        return true;
    }

    /**
     * flush_fcache.
     */
    public function flush_fcache()
    {
        if (!empty($_GET['idxv'])) {
            $fx = sanitize_text_field($_GET['idxv']);
            $file = $this->cache_path.$fx.'.php';
            if (@is_file($file)) {
                return $this->unlink($file, true);
            }
        }

        return true;
    }

    /**
     * flush_opcache.
     */
    public function flush_opcache()
    {
        return $this->opcache_reset($this->cache_path);
    }

    /**
     * has_log.
     */
    public function has_log()
    {
        return @is_file($this->constans()->value('DOCKET_CACHE_LOG_FILE')) && is_readable($this->constans()->value('DOCKET_CACHE_LOG_FILE'));
    }

    /**
     * flush_log.
     */
    public function flush_log()
    {
        if ($this->has_log()) {
            return @unlink($this->constans()->value('DOCKET_CACHE_LOG_FILE'));
        }

        return false;
    }

    /**
     * suspend_wp_options_autoload.
     */
    public function suspend_wp_options_autoload($status = null)
    {
        $wpdb = $this->safe_wpdb();
        if (!$wpdb) {
            return false;
        }

        if (null === $status) {
            $status = $this->constans()->is_true('DOCKET_CACHE_WPOPTALOAD') ? true : false;
        }

        $suspend_value = 'docketcache-no';
        $options_tbl = $wpdb->options;

        // check
        $query = $wpdb->prepare("SELECT autoload FROM `{$options_tbl}` WHERE autoload='yes' OR autoload=%s ORDER BY option_id ASC LIMIT 1", $suspend_value);
        $check = $wpdb->query($query);

        if ($check < 1) {
            return false;
        }

        if ($status) {
            $query = "SELECT autoload FROM `{$options_tbl}` WHERE autoload='yes' ORDER BY option_id ASC LIMIT 1";
            $check = $wpdb->query($query);
            if ($check < 1) {
                return false;
            }
        } else {
            $query = $wpdb->prepare("SELECT autoload FROM `{$options_tbl}` WHERE autoload=%s ORDER BY option_id ASC LIMIT 1", $suspend_value);
            $check = $wpdb->query($query);
            if ($check < 1) {
                return false;
            }
        }

        if ($status) {
            $query = $wpdb->prepare("UPDATE `{$options_tbl}` SET autoload=%s WHERE autoload='yes' ORDER BY option_id ASC", $suspend_value);
        } else {
            $query = $wpdb->prepare("UPDATE `{$options_tbl}` SET autoload='yes' WHERE autoload=%s ORDER BY option_id ASC", $suspend_value);
        }

        $suppress = $wpdb->suppress_errors(true);
        $result = $wpdb->query($query);
        $wpdb->suppress_errors($suppress);

        wp_cache_delete('alloptions', 'options');

        return $result;
    }

    /**
     * pushup.
     */
    public function wearechampion()
    {
        add_action(
            'shutdown',
            function () {
                $active_plugins = (array) get_option('active_plugins', []);
                if (!empty($active_plugins) && \is_array($active_plugins) && isset($active_plugins[0]) && \in_array($this->hook, $active_plugins) && $this->hook !== $active_plugins[0]) {
                    unset($active_plugins[array_search($this->hook, $active_plugins)]);
                    array_unshift($active_plugins, $this->hook);
                    update_option('active_plugins', $active_plugins);
                }
            },
            PHP_INT_MAX
        );
    }

    /**
     * cleanup.
     */
    private function cleanup()
    {
        if ($this->dropino()->validate()) {
            $this->dropino()->uninstall();
        }

        $this->dropino()->undelay();
        $this->cachedir_flush($this->cache_path, true);
        $this->flush_log();
        $this->suspend_wp_options_autoload(false);
    }

    /**
     * uninstall.
     */
    public static function uninstall()
    {
        ( new self() )->cleanup();
    }

    /**
     * deactivate.
     */
    public function deactivate()
    {
        $this->cleanup();
        $this->unregister_cronjob();
    }

    /**
     * activate.
     */
    public function activate()
    {
        $this->flush_cache();
        $this->suspend_wp_options_autoload(null);

        $this->dropino()->install(true);
        $this->unregister_cronjob();
        $this->wearechampion();
    }

    private function register_init()
    {
        $this->page = (is_multisite() ? 'settings.php' : 'options-general.php').'?page='.$this->slug;
        $this->screen = 'settings_page_docket-cache';
        $this->cache_path = $this->define_cache_path($this->constans()->value('DOCKET_CACHE_PATH'));
    }

    /**
     * register_plugin_hooks.
     */
    private function register_plugin_hooks()
    {
        add_action(
            'plugins_loaded',
            function () {
                load_plugin_textdomain(
                    'docket-cache',
                    false,
                    $this->path.'/languages/'
                );
            },
            0
        );

        add_action(
            'upgrader_process_complete',
            function ($wp_upgrader, $options) {
                if ('update' !== $options['action']) {
                    return;
                }

                if ('plugin' === $options['type'] && !empty($options['plugins'])) {
                    if (!\is_array($options['plugins'])) {
                        return;
                    }
                    foreach ($options['plugins'] as $plugin) {
                        if ($plugin === $this->hook) {
                            add_action(
                                'shutdown',
                                function () {
                                    $this->dropino()->install(true);
                                },
                                PHP_INT_MAX
                            );
                            break;
                        }
                    }
                }
            },
            PHP_INT_MAX,
            2
        );

        add_action(
            'admin_footer',
            function () {
                $output = $this->dropino()->after_delay();
                if (!empty($output)) {
                    echo $output;
                }
            },
            PHP_INT_MAX
        );

        if ($this->constans()->is_true('DOCKET_CACHE_SIGNATURE')) {
            add_action(
                'send_headers',
                function () {
                    $status = $this->dropino()->validate() ? 'on' : 'off';
                    header($this->slug.': '.$status);
                },
                PHP_INT_MAX
            );
        }

        add_action(
            'plugins_loaded',
            function () {
                if (!headers_sent() && $this->constans()->is_true('DOCKET_CACHE_LOG')
                    && !empty($_SERVER['REQUEST_URI']) && false !== strpos($_SERVER['REQUEST_URI'], '?page=docket-cache&idx=log&dl=0')
                    && preg_match('@log\&dl=\d+$@', $_SERVER['REQUEST_URI'])) {
                    $file = $this->constans()->value('DOCKET_CACHE_LOG_FILE');
                    @header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0');
                    @header('Content-Type: text/plain; charset=UTF-8');
                    if (@is_file($file)) {
                        @readfile($file);
                        exit;
                    }
                    echo 'No data available';
                    exit;
                }
            },
            0
        );

        if (class_exists('Nawawi\\DocketCache\\CronAgent')) {
            ( new CronAgent($this) )->register();
        }

        if (\defined('DOCKET_CACHE_PRIVATEREPO') && class_exists('Nawawi\\DocketCache\\PrivateRepo')) {
            $repo = $this->constans()->value('DOCKET_CACHE_PRIVATEREPO');
            if (!empty($repo)) {
                new PrivateRepo($this->slug, $this->hook, $this->plugin_meta($this->file)['Version'], $this->constans()->value('DOCKET_CACHE_PRIVATEREPO'));
            }
        }

        register_activation_hook($this->hook, [$this, 'activate']);
        register_deactivation_hook($this->hook, [$this, 'deactivate']);
        register_uninstall_hook($this->hook, [__CLASS__, 'uninstall']);
    }

    /**
     * actions.
     */
    public function action_token()
    {
        $keys = [
             'docket-enable-occache',
             'docket-disable-occache',
             'docket-flush-occache',
             'docket-update-dropino',
             'docket-flush-oclog',
             'docket-flush-ocfile',
             'docket-flush-opcache',
             'docket-connect-cronbot',
             'docket-disconnect-cronbot',
             'docket-runevent-cronbot',
         ];

        foreach ($this->canopt()->keys() as $key) {
            $keys[] = 'docket-default-'.$key;
            $keys[] = 'docket-enable-'.$key;
            $keys[] = 'docket-disable-'.$key;
            $keys[] = 'docket-save-'.$key;
        }

        return $keys;
    }

    /**
     * action_query.
     */
    public function action_query($key, $args_extra = [])
    {
        $key = str_replace('docket-', '', $key);
        $key = 'docket-'.$key;

        $args = array_merge(
            [
                'action' => $key,
            ],
            $args_extra
        );

        $query = add_query_arg($args, $this->page);

        return wp_nonce_url(network_admin_url($query), $key);
    }

    public function our_screen()
    {
        return substr(get_current_screen()->id, 0, \strlen($this->screen)) === $this->screen;
    }

    /**
     * register_admin_hooks.
     */
    private function register_admin_hooks()
    {
        $action_name = is_multisite() ? 'network_admin_menu' : 'admin_menu';
        add_action(
            $action_name,
            function () {
                $page_link = is_multisite() ? 'settings.php' : 'options-general.php';
                $page_cap = is_multisite() ? 'manage_network_options' : 'manage_options';

                add_submenu_page(
                    $page_link,
                    __('Docket Cache', 'docket-cache'),
                    __('Docket Cache', 'docket-cache'),
                    $page_cap,
                    $this->slug,
                    function () {
                        ( new View($this) )->index();
                    }
                );
            }
        );

        add_action(
            'all_admin_notices',
            function () {
                if (!current_user_can(is_multisite() ? 'manage_network_options' : 'manage_options')) {
                    return;
                }

                if ($this->dropino()->exists()) {
                    $url = $this->action_query('update-dropino');

                    if ($this->dropino()->validate()) {
                        if ($this->dropino()->is_outdated() && !$this->dropino()->install(true)) {
                            /* translators: %s: url */
                            $message = sprintf(__('<strong>Docket Cache:</strong> The object-cache.php drop-in is outdated. Please click "Re-Install" to update it now.<p style="padding:0;"><a href="%s" class="button button-primary">Re-Install</a>', 'docket-cache'), $url);
                        }
                    } else {
                        /* translators: %s: url */
                        $message = sprintf(__('<strong>Docket Cache:</strong> An unknown object-cache.php drop-in was found. Please click "Install" to use Docket Cache.<p style="margin-bottom:0;"><a href="%s" class="button button-primary">Install</a></p>', 'docket-cache'), $url);
                    }
                }

                if (2 === $this->get_status() && $this->our_screen()) {
                    $message = esc_html__('The object-cache.php drop-in has been disabled at runtime.', 'docket-cache');
                }

                if (isset($message)) {
                    echo '<div id="docket-notice" class="notice notice-warning">';
                    echo '<p>'.$message.'</p>';
                    echo '</div>';
                }
            }
        );

        add_action(
            'admin_enqueue_scripts',
            function ($hook) {
                $is_debug = $this->constans()->is_true('WP_DEBUG');
                $plugin_url = plugin_dir_url($this->file);
                $version = str_replace('.', '', $this->plugin_meta($this->file)['Version']).'x'.($is_debug ? date('his') : date('d'));
                wp_enqueue_script($this->slug.'-worker', $plugin_url.'includes/admin/worker.js', ['jquery'], $version, false);
                wp_localize_script(
                    $this->slug.'-worker',
                    'docket_cache_config',
                    [
                        'ajaxurl' => admin_url('admin-ajax.php'),
                        'token' => wp_create_nonce('docketcache-token-nonce'),
                        'slug' => $this->slug,
                        'debug' => $is_debug ? 'true' : 'false',
                    ]
                );
                if ($hook === $this->screen) {
                    wp_enqueue_style($this->slug.'-core', $plugin_url.'includes/admin/docket.css', null, $version);
                    wp_enqueue_script($this->slug.'-core', $plugin_url.'includes/admin/docket.js', ['jquery'], $version, true);
                }

                if ($this->constans()->is_true('DOCKET_CACHE_PAGELOADER')) {
                    wp_enqueue_style($this->slug.'-loader', $plugin_url.'includes/admin/pageloader.css', null, $version);
                    wp_enqueue_script($this->slug.'-loader', $plugin_url.'includes/admin/pageloader.js', ['jquery'], $version, true);
                }
            }
        );

        add_action(
            'wp_ajax_docket_worker',
            function () {
                if (!check_ajax_referer('docketcache-token-nonce', 'token', false) && !isset($_POST['type'])) {
                    wp_send_json_error('Invalid security token sent.');
                    exit;
                }

                $type = sanitize_text_field($_POST['type']);

                if ($this->dropino()->validate()) {
                    if ('preload' === $type) {
                        $this->send_json_continue($this->slug.':worker: pong '.$type);
                        $this->dropino()->undelay();
                        do_action('docket-cache/preload');
                        exit;
                    }

                    if ('fetch' === $type) {
                        $this->send_json_continue($this->slug.':worker: pong '.$type);
                        @Crawler::fetch_home();
                        exit;
                    }

                    if ('countcachesize' === $type) {
                        $this->send_json_continue($this->slug.':worker: pong '.$type);
                        do_action('docket-cache/countcachesize');
                        exit;
                    }
                }

                if ('flush' === $type) {
                    $this->send_json_continue($this->slug.':worker: pong '.$type);
                    if (\function_exists('delete_expired_transients')) {
                        delete_expired_transients(true);
                    }
                    exit;
                }

                wp_send_json_error($this->slug.':worker: "'.$type.'" not available');
                exit;
            }
        );

        add_filter(
            'admin_footer_text',
            function ($text) {
                if ($this->our_screen()) {
                    $meta = $this->plugin_meta($this->file);
                    /* translators: %s: version */
                    $text = $meta['Name'].' '.sprintf(__('Version %s', 'docket-cache'), $meta['Version']);
                }

                return $text;
            },
            PHP_INT_MAX
        );

        foreach (['update_footer', 'core_update_footer'] as $fn) {
            add_filter(
                $fn,
                function ($text) {
                    if ($this->our_screen()) {
                        /* translators: %s: version */
                        $text = 'WordPress '.' '.sprintf(__('Version %s', 'docket-cache'), $GLOBALS['wp_version']);
                    }

                    return $text;
                },
                PHP_INT_MAX
            );
        }

        add_action(
            'load-'.$this->screen,
            function () {
                if (isset($_GET['_wpnonce'], $_GET['action'])) {
                    $action = sanitize_text_field($_GET['action']);

                    if (\in_array($action, $this->action_token())) {
                        if (!wp_verify_nonce($_GET['_wpnonce'], $action)) {
                            return;
                        }
                        if ('docket-flush-occache' === $action) {
                            $message = $this->flush_cache() ? 'docket-occache-flushed' : 'docket-occache-flushed-failed';
                        }

                        switch ($action) {
                            case 'docket-enable-occache':
                                $result = $this->dropino()->install(true);
                                $message = $result ? 'docket-occache-enabled' : 'docket-occache-enabled-failed';
                                do_action('docket-cache/object-cache-enable', $result);
                                break;

                            case 'docket-disable-occache':
                                $result = $this->dropino()->uninstall();
                                $message = $result ? 'docket-occache-disabled' : 'docket-occache-disabled-failed';
                                do_action('docket-cache/object-cache-disable', $result);
                                break;

                            case 'docket-update-dropino':
                                $result = $this->dropino()->install(true);
                                $message = $result ? 'docket-dropino-updated' : 'docket-dropino-updated-failed';
                                do_action('docket-cache/object-cache-install', $result);
                                break;

                            case 'docket-flush-oclog':
                                $result = $this->flush_log();
                                $message = $result ? 'docket-log-flushed' : 'docket-log-flushed-failed';
                                do_action('docket-cache/flush-log', $result);
                                break;

                            case 'docket-flush-ocfile':
                                $result = $this->flush_fcache();
                                $message = $result ? 'docket-file-flushed' : 'docket-file-flushed-failed';
                                do_action('docket-cache/flush-fcache', $result);
                                break;
                            case 'docket-flush-opcache':
                                $result = $this->flush_opcache();
                                $message = $result ? 'docket-opcache-flushed' : 'docket-opcache-flushed-failed';
                                do_action('docket-cache/flush-opcache', $result);
                                break;
                            case 'docket-connect-cronbot':
                                $result = apply_filters('docket-cache/cronbot-active', true);
                                $message = $result ? 'docket-cronbot-connect' : 'docket-cronbot-connect-failed';
                                do_action('docket-cache/connect-cronbot', $result);
                                break;
                            case 'docket-disconnect-cronbot':
                                $result = apply_filters('docket-cache/cronbot-active', false);
                                $message = $result ? 'docket-cronbot-disconnect' : 'docket-cronbot-disconnect-failed';
                                do_action('docket-cache/disconnect-cronbot', $result);
                                break;
                            case 'docket-runevent-cronbot':
                                $result = apply_filters('docket-cache/cronbot-runevent', false);
                                $message = $result ? 'docket-cronbot-runevent' : 'docket-cronbot-runevent-failed';
                                do_action('docket-cache/runevent-cronbot', $result);
                                @Crawler::fetch_admin(admin_url('/'));
                                break;
                        }

                        if (empty($message) && preg_match('@^docket-(default|enable|disable|save)-([a-z_]+)$@', $action, $mm)) {
                            $nk = $mm[1];
                            $nx = $mm[2];
                            if (\in_array($nx, $this->canopt()->keys())) {
                                if ('save' === $nk && isset($_GET['nv'])) {
                                    $nv = sanitize_text_field($_GET['nv']);
                                    $message = $this->canopt()->save($nx, $nv) ? 'docket-option-save' : 'docket-option-failed';
                                } else {
                                    $okmsg = 'default' === $nk ? 'docket-option-default' : 'docket-option-'.$nk;
                                    $message = $this->canopt()->save($nx, $nk) ? $okmsg : 'docket-option-failed';
                                }
                            }
                        }

                        if (!empty($message)) {
                            $args = [
                                'message' => $message,
                            ];

                            if (!empty($_GET['idx'])) {
                                $args['idx'] = sanitize_text_field($_GET['idx']);
                            }

                            $query = add_query_arg($args, $this->page);
                            wp_safe_redirect(network_admin_url($query));
                            exit;
                        }
                    }
                }
            }
        );

        add_action(
            'load-'.$this->screen,
            function () {
                if (version_compare(PHP_VERSION, $this->plugin_meta($this->file)['RequiresPHP'], '<')) {
                    /* translators: %s: php version */
                    add_settings_error(is_multisite() ? 'general' : '', $this->slug, sprintf(__('This plugin requires PHP %s or greater.', 'docket-cache'), $this->plugin_meta($this->file)['RequiresPHP']));
                }

                if (isset($_GET['message'])) {
                    $token = sanitize_text_field($_GET['message']);
                    $this->token = $token;
                    switch ($token) {
                        case 'docket-occache-enabled':
                            $message = esc_html__('Object cache enabled.', 'docket-cache');
                            break;
                        case 'docket-occache-enabled-failed':
                            $error = esc_html__('Object cache could not be enabled.', 'docket-cache');
                            break;
                        case 'docket-occache-disabled':
                            $message = esc_html__('Object cache disabled.', 'docket-cache');
                            break;
                        case 'docket-occache-disabled-failed':
                            $error = esc_html__('Object cache could not be disabled.', 'docket-cache');
                            break;
                        case 'docket-occache-flushed':
                            $message = esc_html__('Object cache was flushed.', 'docket-cache');
                            break;
                        case 'docket-occache-flushed-failed':
                            $error = esc_html__('Object cache could not be flushed.', 'docket-cache');
                            break;
                        case 'docket-dropino-updated':
                            $message = esc_html__('Updated object cache drop-in and enabled Docket object cache.', 'docket-cache');
                            break;
                        case 'docket-dropino-updated-failed':
                            $error = esc_html__('Docket object cache drop-in could not be updated.', 'docket-cache');
                            break;
                        case 'docket-log-flushed':
                            $message = esc_html__('Cache log was flushed.', 'docket-cache');
                            break;
                        case 'docket-log-flushed-failed':
                            $error = esc_html__('Cache log could not be flushed.', 'docket-cache');
                            break;
                        case 'docket-file-flushed':
                            $message = esc_html__('Cache file was flushed.', 'docket-cache');
                            break;
                        case 'docket-file-flushed-failed':
                            $error = esc_html__('Cache file could not be flushed.', 'docket-cache');
                            break;
                        case 'docket-opcache-flushed':
                            $message = esc_html__('OPcache was flushed.', 'docket-cache');
                            $this->flush_opcache();
                            break;
                        case 'docket-opcache-flushed-failed':
                            $error = esc_html__('OPcache could not be flushed.', 'docket-cache');
                            break;
                        case 'docket-cronbot-connect':
                            $message = esc_html__('Cronbot connected.', 'docket-cache');
                            break;
                        case 'docket-cronbot-connect-failed':
                            $msg = get_transient('docketcache/cronboterror');
                            delete_transient('docketcache/cronboterror');
                            $errmsg = !empty($msg) ? ': '.$msg : '.';
                            /* translators: %s = error message */
                            $error = sprintf(esc_html__('Cronbot failed to connect%s', 'docket-cache'), $errmsg);
                            unset($msg, $errmsg);
                            break;
                        case 'docket-cronbot-disconnect':
                            $message = esc_html__('Cronbot disconnected.', 'docket-cache');
                            break;
                        case 'docket-cronbot-disconnect-failed':
                            $error = esc_html__('Cronbot failed to disconnect.', 'docket-cache');
                            break;
                        case 'docket-cronbot-runevent':
                            $message = esc_html__('Cron run successful.', 'docket-cache');
                            break;
                        case 'docket-cronbot-runevent-failed':
                            $error = esc_html__('Failed to run cron.', 'docket-cache');
                            break;
                        case 'docket-option-enable':
                            $message = esc_html__('Option enabled.', 'docket-cache');
                            break;
                        case 'docket-option-disable':
                            $message = esc_html__('Option disabled.', 'docket-cache');
                            break;
                        case 'docket-option-save':
                            $message = esc_html__('Option saved.', 'docket-cache');
                            break;
                        case 'docket-option-default':
                            $message = esc_html__('Option resets to default.', 'docket-cache');
                            break;
                        case 'docket-option-failed':
                            $error = esc_html__('Failed to change options.', 'docket-cache');
                            break;
                    }

                    if (isset($message) || isset($error)) {
                        $msg = isset($message) ? $message : $error;
                        $type = isset($message) ? 'updated' : 'error';
                        add_settings_error(is_multisite() ? 'general' : '', $this->slug, $msg, $type);
                    }
                }
            }
        );

        $filter_name = sprintf('%splugin_action_links_%s', is_multisite() ? 'network_admin_' : '', $this->hook);
        add_filter(
            $filter_name,
            function ($links) {
                array_unshift(
                    $links,
                    sprintf(
                        '<a href="%s">%s</a>',
                        network_admin_url($this->page),
                        __('Settings', 'docket-cache')
                    )
                );

                switch ($this->get_status()) {
                    case 0:
                        $text = esc_html__('Enable Object Cache', 'docket-cache');
                        $action = 'enable-occache';
                        break;
                    case 1:
                        $text = esc_html__('Disable Object Cache', 'docket-cache');
                        $action = 'disable-occache';
                        break;
                    default:
                        $text = esc_html__('Install Drop-in', 'docket-cache');
                        $action = 'update-dropino';
                }

                $links[] = sprintf('<a href="%s">%s</a>', $this->action_query($action), $text);

                return $links;
            }
        );

        add_action(
            'docket-cache/save-option',
            function ($name, $value, $status = true) {
                switch ($name) {
                    case 'log':
                        if (true === $status) {
                            $this->flush_log();
                        }
                        if ('enable' === $value) {
                            @Crawler::fetch_home();
                        }
                        break;
                    case 'wpoptaload':
                        $opt = 'enable' === $value ? true : false;
                        $this->suspend_wp_options_autoload($opt);
                        break;
                    case 'cronoptmzdb':
                        $this->unregister_cronjob();
                        break;
                    case 'cronbot':
                        $action = 'enable' === $value ? true : false;
                        add_action(
                            'shutdown',
                            function () use ($action) {
                                if (!$action) {
                                    apply_filters('docket-cache/cronbot-active', $action);
                                }
                            },
                            PHP_INT_MAX
                        );
                        break;
                }
            },
            -1,
            3
        );

        add_action(
            'docket-cache/preload',
            function () {
                if ($this->constans()->is_false('DOCKET_CACHE_PRELOAD') || $this->constans()->is_true('DOCKET_CACHE_WPCLI')) {
                    return;
                }

                // preload
                add_action(
                    'shutdown',
                    function () {
                        wp_load_alloptions();
                        wp_count_comments(0);

                        @Crawler::fetch_home();

                        $preload_admin = [
                            'index.php',
                            'options-general.php',
                            'options-writing.php',
                            'options-reading.php',
                            'options-discussion.php',
                            'options-media.php',
                            'options-permalink.php',
                            'edit-comments.php',
                            'profile.php',
                            'users.php',
                            'upload.php',
                            'plugins.php',
                            'edit.php',
                            'edit-tags.php?taxonomy=category',
                            'edit-tags.php?taxonomy=post_tag',
                            'edit.php?post_type=page',
                            'post-new.php?post_type=page',
                            'themes.php',
                            'widgets.php',
                            'nav-menus.php',
                            'tools.php',
                            'import.php',
                            'export.php',
                            'site-health.php',
                            'update-core.php',
                        ];

                        $preload_network = [
                            'index.php',
                            'update-core.php',
                            'sites.php',
                            'users.php',
                            'themes.php',
                            'plugins.php',
                            'settings.php',
                        ];

                        if ($this->constans()->is_array('DOCKET_CACHE_PRELOAD_ADMIN')) {
                            $preload_admin = DOCKET_CACHE_PRELOAD_ADMIN;
                        }

                        if ($this->constans()->is_array('DOCKET_CACHE_PRELOAD_NETWORK')) {
                            $preload_network = DOCKET_CACHE_PRELOAD_NETWORK;
                        }

                        foreach ($preload_admin as $path) {
                            $url = admin_url('/'.$path);
                            @Crawler::fetch_admin($url);
                            usleep(500000);
                        }

                        if (is_multisite()) {
                            foreach ($preload_network as $path) {
                                $url = network_admin_url('/'.$path);
                                @Crawler::fetch_admin($url);
                                usleep(500000);
                            }
                        }
                    },
                    PHP_INT_MAX
                );
            }
        );

        add_action(
            'docket-cache/countcachesize',
            function () {
                $locked = wp_cache_get('doing_countcachesize', 'docketcache', true);
                if (empty($locked)) {
                    wp_cache_set('doing_countcachesize', 1, 'docketcache', 120);
                    $this->cache_size($this->cache_path, 0, true);
                }
                wp_cache_set('doing_countcachesize', 0, 'docketcache', 0);
            },
            PHP_INT_MAX
        );

        add_action(
            'docket-cache/suspend_wp_options_autoload',
            function () {
                $locked = wp_cache_get('doing_suspend_wp_options_autoload', 'docketcache', true);
                if (empty($locked)) {
                    wp_cache_set('doing_suspend_wp_options_autoload', 1, 'docketcache', 120);
                    $this->suspend_wp_options_autoload(null);
                }
                wp_cache_set('doing_suspend_wp_options_autoload', 0, 'docketcache', 0);
            },
            PHP_INT_MAX
        );
    }

    /**
     * register_tweaks.
     */
    private function register_tweaks()
    {
        $this->wearechampion();

        if (class_exists('Nawawi\\DocketCache\\Tweaks')) {
            $tweaks = new Tweaks($this);
            $tweaks->wpquery();

            if ($this->constans()->is_true('DOCKET_CACHE_WOOTWEAKS')) {
                $tweaks->woocommerce();
            }

            if ($this->constans()->is_true('DOCKET_CACHE_POSTMISSEDSCHEDULE')) {
                foreach (['wp_footer', 'admin_footer'] as $hx) {
                    add_action(
                        $hx,
                        function () use ($tweaks) {
                            $tweaks->post_missed_schedule();
                        },
                        PHP_INT_MAX
                    );
                }
            }

            if ($this->constans()->is_true('DOCKET_CACHE_MISC_TWEAKS')) {
                $tweaks->misc();
            }
        }

        // only if our dropin
        if ($this->dropino()->validate()) {
            // wp_cache: advanced cache post
            if ($this->constans()->is_true('DOCKET_CACHE_ADVCPOST') && class_exists('Nawawi\\DocketCache\\PostCache')) {
                new PostCache($this);
            }

            // wp_cache: translation mo file cache
            if ($this->constans()->is_true('DOCKET_CACHE_MOCACHE') && class_exists('Nawawi\\DocketCache\\MoCache')) {
                add_filter(
                    'override_load_textdomain',
                    function ($plugin_override, $domain, $mofile) {
                        if (!@is_file($mofile) || !@is_readable($mofile) || !isset($GLOBALS['l10n'])) {
                            return false;
                        }

                        $l10n = $GLOBALS['l10n'];
                        $upstream = empty($l10n[$domain]) ? null : $l10n[$domain];
                        $mo = new MoCache($mofile, $domain, $upstream);
                        $l10n[$domain] = $mo;

                        $GLOBALS['l10n'] = $l10n;

                        return true;
                    },
                    PHP_INT_MAX,
                    3
                );
            }
        }

        // optimize term count
        if ($this->constans()->is_true('DOCKET_CACHE_OPTERMCOUNT') && class_exists('Nawawi\\DocketCache\\TermCount')) {
            TermCount::init();
        }
    }

    /**
     * register_cronjob.
     */
    private function register_cronjob()
    {
        ( new Event($this) )->register();
    }

    /**
     * unregister_cronjob.
     */
    private function unregister_cronjob()
    {
        ( new Event($this) )->unregister();
    }

    private function register_cli()
    {
        if ($this->constans()->is_true('DOCKET_CACHE_WPCLI') && $this->constans()->is_false('DocketCache_CLI')) {
            \define('DocketCache_CLI', true);
            $cli = new Command($this);
            \WP_CLI::add_command('cache update', [$cli, 'update_dropino']);
            \WP_CLI::add_command('cache enable', [$cli, 'enable']);
            \WP_CLI::add_command('cache disable', [$cli, 'disable']);
            \WP_CLI::add_command('cache status', [$cli, 'status']);
            \WP_CLI::add_command('cache type', [$cli, 'type']);
            \WP_CLI::add_command('cache flush', [$cli, 'flush_cache']);
        }
    }

    /**
     * attach.
     */
    public function attach()
    {
        $this->register_plugin_hooks();
        $this->register_admin_hooks();
        $this->register_tweaks();
        $this->register_cronjob();
        $this->register_cli();
    }
}
