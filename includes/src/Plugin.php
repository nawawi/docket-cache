<?php
/**
 * Docket Cache.
 *
 * @author  Nawawi Jamili
 * @license MIT
 *
 * @see    https://github.com/nawawi/docket-cache
 */

namespace Nawawi\Docket_Cache;

final class Plugin
{
    public $file;
    public $slug;
    public $path;

    private $screen;
    private $actions;
    private $hook;
    private $page;
    private $token;
    private $fs;
    private $cache_path;

    public function __construct()
    {
        $this->slug = 'docket-cache';
        $this->file = DOCKET_CACHE_FILE;
        $this->hook = plugin_basename($this->file);
        $this->path = realpath(plugin_dir_path($this->file));
        $this->page = (is_multisite() ? 'settings.php?page=' : 'options-general.php?page=').$this->slug;

        $this->screen = 'settings_page_docket-cache';
        $this->actions = [
            'docket-enable-cache',
            'docket-disable-cache',
            'docket-flush-cache',
            'docket-update-dropin',
            'docket-flush-log',
        ];
        Constans::init();
        $this->fs = new Files();
        $this->cache_path = is_dir(DOCKET_CACHE_PATH) && '/' !== DOCKET_CACHE_PATH ? rtrim(DOCKET_CACHE_PATH, '/\\').'/' : WP_CONTENT_DIR.'/cache/docket-cache/';
        if ('docket-cache' !== basename($this->cache_path)) {
            $this->cache_path = rtim($this->cache_path, '/').'docket-cache/';
        }
    }

    public function get_plugin_data($file, $as_object = true)
    {
        $default_headers = [
            'Name' => 'Plugin Name',
            'PluginURI' => 'Plugin URI',
            'Version' => 'Version',
            'Description' => 'Description',
            'Author' => 'Author',
            'AuthorURI' => 'Author URI',
            'TextDomain' => 'Text Domain',
            'DomainPath' => 'Domain Path',
            'Network' => 'Network',
            'RequiresWP' => 'Requires at least',
            'RequiresPHP' => 'Requires PHP',
        ];

        $plugin_data = get_file_data($file, $default_headers);
        if ($as_object) {
            $plugin_data = (object) $plugin_data;
        }

        return $plugin_data;
    }

    public function get_info()
    {
        $status_code = [
             0 => __('Disabled', 'docket-cache'),
             1 => __('Enable', 'docket-cache'),
             2 => __('Not Available', 'docket-cache'),
             3 => __('Unknown', 'docket-cache'),
         ];
        $status = $this->get_status();
        $opcache = $this->get_opcache_status();

        return [
             'status_code' => $status,
             'status_text' => $status_code[$status],
             'opcache_code' => $opcache,
             'opcache_text' => $status_code[$opcache],
             'php_memory_limit' => $this->normalize_size(@ini_get('memory_limit')),
             'wp_memory_limit' => $this->normalize_size(WP_MEMORY_LIMIT),
             'write_dropin' => is_writable(WP_CONTENT_DIR) ? __('Yes', 'docket-cache') : __('No', 'docket-cache'),
             'write_cache' => is_writable($this->cache_path) ? __('Yes', 'docket-cache') : __('No', 'docket-cache'),
             'cache_size' => $this->get_dirsize(),
             'cache_path_real' => $this->cache_path,
             'cache_path' => $this->sanitize_rootpath($this->cache_path),
             'log_file_real' => DOCKET_CACHE_LOG_FILE,
             'log_file' => $this->sanitize_rootpath(DOCKET_CACHE_LOG_FILE),
             'log_enable' => DOCKET_CACHE_LOG ? 1 : 0,
             'log_enable_text' => $status_code[DOCKET_CACHE_LOG ? 1 : 0],
         ];
    }

    public function has_dropin()
    {
        return file_exists(WP_CONTENT_DIR.'/object-cache.php');
    }

    private function plugin_data($key)
    {
        static $cache = [];

        if (isset($cache[$key])) {
            return $cache[$key];
        }

        $cache['dropin'] = $this->get_plugin_data(WP_CONTENT_DIR.'/object-cache.php', false);
        $cache['plugin'] = $this->get_plugin_data($this->path.'/includes/object-cache.php', false);

        return $cache[$key];
    }

    public function validate_dropin()
    {
        if (!$this->has_dropin()) {
            return false;
        }

        if (0 !== strcmp($this->plugin_data('dropin')['PluginURI'], $this->plugin_data('plugin')['PluginURI'])) {
            return false;
        }

        return true;
    }

    public function get_status()
    {
        if (\defined('DOCKET_CACHE_DISABLED') && DOCKET_CACHE_DISABLED || \defined('DOCKET_CACHE_HALT') && DOCKET_CACHE_HALT) {
            return 2;
        }

        if (!$this->has_dropin()) {
            return 0;
        }

        if ($this->validate_dropin()) {
            return 1;
        }

        return 3;
    }

    public function get_dirsize()
    {
        $dir = realpath($this->cache_path);

        $bytestotal = 0;
        if (false !== $dir && is_dir($dir) && is_readable($dir) && 'docket-cache' === basename($dir)) {
            foreach ($this->fs->scanfiles($dir) as $object) {
                $bytestotal += $object->getSize();
            }
        }

        return $this->normalize_size($bytestotal);
    }

    public function get_logsize()
    {
        return $this->has_log() ? $this->normalize_size($this->fs->filesize(DOCKET_CACHE_LOG_FILE)) : 0;
    }

    public function sanitize_rootpath($path)
    {
        return str_replace([WP_CONTENT_DIR, ABSPATH], ['/wp-content', '/'], $path);
    }

    public function get_opcache_status()
    {
        if (\function_exists('opcache_get_status')) {
            $status = @opcache_get_status();
            if (\is_array($status) && ($status['opcache_enabled'] || $status['file_cache_only'])) {
                return 1;
            }

            return 0;
        }

        return 2;
    }

    public function normalize_size($size)
    {
        $size = wp_convert_hr_to_bytes($size);
        $size1 = size_format($size, 1);
        if (false !== strpos($size1, '.0')) {
            $size1 = size_format($size);
        }
        $size = str_replace([',', ' ', 'B'], '', $size1);

        return $size;
    }

    // always true
    private function flush_cache()
    {
        $this->fs->cachedir_flush($this->cache_path);

        return true;
    }

    private function has_log()
    {
        return  is_file(DOCKET_CACHE_LOG_FILE) && is_readable(DOCKET_CACHE_LOG_FILE);
    }

    private function flush_log()
    {
        if ($this->has_log()) {
            return @unlink(DOCKET_CACHE_LOG_FILE);
        }

        return false;
    }

    private function tail_log($limit = 100)
    {
        $limit = (int) $limit;
        $output = [];
        if ($this->has_log()) {
            foreach ($this->fs->tail(DOCKET_CACHE_LOG_FILE, $limit) as $line) {
                $line = trim($line);
                if (empty($line)) {
                    continue;
                }
                $output[] = $line;
            }
        }

        return $output;
    }

    private function page_tab_query($index)
    {
        return network_admin_url(add_query_arg('index', $index, $this->page));
    }

    private function page_tab_index()
    {
        return !empty($_GET['index']) ? sanitize_text_field($_GET['index']) : 'default';
    }

    private function page_is_tab($index)
    {
        return $index === $this->page_tab_index();
    }

    private function page_active_tab($index)
    {
        if ($this->page_is_tab($index)) {
            return ' nav-tab-active';
        }

        return '';
    }

    private function page_nav_tabs()
    {
        $html = '<nav class="nav-tab-wrapper">';
        $html .= '<a href="'.$this->page_tab_query('default').'" class="nav-tab'.$this->page_active_tab('default').'">'.__('Overview', 'docket-cache').'</a>';
        $html .= '<a href="'.$this->page_tab_query('log').'" class="nav-tab'.$this->page_active_tab('log').'">'.__('Cache Log', 'docket-cache').'</a>';
        $html .= '<a href="'.$this->page_tab_query('config').'" class="nav-tab'.$this->page_active_tab('config').'">'.__('Config', 'docket-cache').'</a>';
        $html .= '</nav>';
        echo $html;
    }

    private function dropin_file()
    {
        $dt = [];
        $dt['src'] = $this->path.'/includes/object-cache.php';
        $dt['dst'] = WP_CONTENT_DIR.'/object-cache.php';

        return (object) $dt;
    }

    public function dropin_install()
    {
        $src = $this->dropin_file()->src;
        $dst = $this->dropin_file()->dst;

        if (is_writable(\dirname($this->dropin_file()->dst))) {
            return $this->fs->copy($src, $dst);
        }

        return false;
    }

    public function dropin_uninstall()
    {
        $dst = $this->dropin_file()->dst;
        if (is_writable($dst)) {
            return @unlink($dst);
        }

        return false;
    }

    private function dropin_remove()
    {
        wp_cache_flush();
        if ($this->validate_dropin()) {
            $this->dropin_uninstall();
        }
    }

    public static function uninstall()
    {
        ( new self() )->dropin_remove();
    }

    public function deactivate()
    {
        $this->dropin_remove();
        $this->unregister_scheduler();
    }

    public function activate()
    {
        wp_cache_flush();
        $this->unregister_scheduler();
    }

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

        register_activation_hook($this->hook, [$this, 'activate']);
        register_deactivation_hook($this->hook, [$this, 'deactivate']);
        register_uninstall_hook($this->hook, [__CLASS__, 'uninstall']);
    }

    private function action_query($key)
    {
        $key = str_replace('docket-cache', '', $key);
        $key = 'docket-'.$key;

        return wp_nonce_url(network_admin_url(add_query_arg('action', $key, $this->page)), $key);
    }

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
                    __('Docket Object Cache', 'docket-cache'),
                    __('Docket Cache', 'docket-cache'),
                    $page_cap,
                    $this->slug,
                    function () {
                        include_once $this->path.'/includes/admin/page.php';
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

                if ($this->has_dropin()) {
                    $url = $this->action_query('update-dropin');

                    if ($this->validate_dropin()) {
                        if (version_compare($this->plugin_data('dropin')['Version'], $this->plugin_data('plugin')['Version'], '<') || false === strpos($this->plugin_data('dropin')['Version'], '.')) {
                            /* translators: %s: url */
                            $message = sprintf(__('<strong>Docket Cache:</strong> The object-cache.php drop-in is outdated. Please click "Re-Install" to update it now.<p style="padding:0;"><a href="%s" class="button button-primary">Re-Install</a>', 'docket-cache'), $url);
                        }
                    } else {
                        /* translators: %s: url */
                        $message = sprintf(__('<strong>Docket Cache:</strong> An unknown object-cache.php drop-in was found. Please click "Install" to use Docket Cache.<p style="margin-bottom:0;"><a href="%s" class="button button-primary">Install</a></p>', 'docket-cache'), $url);
                    }
                }

                if (2 === $this->get_status() && get_current_screen()->id === $this->screen) {
                    $message = __('<strong>Docket Cache:</strong> The object-cache.php drop-in has been disabled via DOCKET_CACHE_DISABLED constant.', 'docket-cache');
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
                if ($hook === $this->screen) {
                    wp_enqueue_style($this->slug, plugin_dir_url($this->file).'includes/admin/style.css', null, str_replace('.', '', $this->get_plugin_data($this->file)->Version));
                }
            }
        );

        add_action(
            'wp_ajax_docket_preload',
            function () {
                do_action('docket_preload');
                wp_send_json_success($this->slug.': run preload');
            }
        );

        add_action(
            'load-'.$this->screen,
            function () {
                if (isset($_GET['_wpnonce'], $_GET['action'])) {
                    $action = sanitize_text_field($_GET['action']);

                    foreach ($this->actions as $name) {
                        if ($action === $name && !wp_verify_nonce($_GET['_wpnonce'], $action)) {
                            return;
                        }
                    }

                    if (\in_array($action, $this->actions)) {
                        $url = $this->action_query($action);

                        if ('docket-flush-cache' === $action) {
                            $message = $this->flush_cache() ? 'docket-cache-flushed' : 'docket-cache-flushed-failed';
                        }

                        if (is_writable(WP_CONTENT_DIR)) {
                            switch ($action) {
                                case 'docket-enable-cache':
                                    $result = $this->dropin_install();
                                    $message = $result ? 'docket-cache-enabled' : 'docket-cache-enabled-failed';
                                    do_action('docket_cache_enable', $result);
                                    break;

                                case 'docket-disable-cache':
                                    $result = $this->dropin_uninstall();
                                    $message = $result ? 'docket-cache-disabled' : 'docket-cache-disabled-failed';
                                    do_action('docket_cache_disable', $result);
                                    break;

                                case 'docket-update-dropin':
                                    $result = $this->dropin_install();
                                    $message = $result ? 'docket-dropin-updated' : 'docket-dropin-updated-failed';
                                    do_action('docket_cache_update_dropin', $result);
                                    break;

                                case 'docket-flush-log':
                                    $result = $this->flush_log();
                                    $message = $result ? 'docket-log-flushed' : 'docket-log-flushed-failed';
                                    do_action('docket_cache_flush_log', $result);
                                    break;
                            }
                        }

                        if (isset($message)) {
                            $query = add_query_arg('message', $message, $this->page);
                            if ('docket-flush-log' === $action) {
                                $query = add_query_arg(
                                    [
                                        'message' => $message,
                                        'index' => 'log',
                                    ],
                                    $this->page
                                );
                            }
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
                if (version_compare(PHP_VERSION, '7.2', '<')) {
                    add_settings_error(is_multisite() ? 'general' : '', $this->slug, __('This plugin requires PHP 7.2 or greater.', 'docket-cache'));
                }

                if (isset($_GET['message'])) {
                    $token = sanitize_text_field($_GET['message']);
                    $this->token = $token;
                    switch ($token) {
                        case 'docket-cache-enabled':
                            $message = __('Object cache enabled.', 'docket-cache');
                            break;
                        case 'docket-cache-enabled-failed':
                            $error = __('Object cache could not be enabled.', 'docket-cache');
                            break;
                        case 'docket-cache-disabled':
                            $message = __('Object cache disabled.', 'docket-cache');
                            break;
                        case 'docket-cache-disabled-failed':
                            $error = __('Object cache could not be disabled.', 'docket-cache');
                            break;
                        case 'docket-cache-flushed':
                            $message = __('Object cache was flushed.', 'docket-cache');
                            break;
                        case 'docket-cache-flushed-failed':
                            $error = __('Object cache could not be flushed.', 'docket-cache');
                            break;
                        case 'docket-dropin-updated':
                            $message = __('Updated object cache drop-in and enabled Docket object cache.', 'docket-cache');
                            break;
                        case 'docket-dropin-updated-failed':
                            $error = __('Docket object cache drop-in could not be updated.', 'docket-cache');
                            break;
                        case 'docket-log-flushed':
                            $message = __('Cache log was flushed.', 'docket-cache');
                            break;
                        case 'docket-log-flushed-failed':
                            $error = __('Cache log could not be flushed.', 'docket-cache');
                            break;
                    }

                    add_settings_error(is_multisite() ? 'general' : '', $this->slug, isset($message) ? $message : $error, isset($message) ? 'updated' : 'error');
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

                return $links;
            }
        );

        add_action(
            'docket_preload',
            function () {
                if (!DOCKET_CACHE_PRELOAD) {
                    return;
                }

                // preload
                add_action(
                    'shutdown',
                    function () {
                        wp_load_alloptions();

                        $args = [
                            'blocking' => false,
                            'timeout' => 45,
                            'httpversion' => '1.1',
                            'user-agent' => 'docket-cache',
                            'body' => null,
                            'compress' => false,
                            'decompress' => false,
                            'sslverify' => false,
                            'stream' => false,
                        ];

                        @wp_remote_get(get_home_url(), $args);

                        $preload_admin = [
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
                            'themes.php',
                            'tools.php',
                            'widgets.php',
                            'update-core.php',
                        ];

                        $preload_network = [
                            'update-core.php',
                            'sites.php',
                            'users.php',
                            'themes.php',
                            'plugins.php',
                            'settings.php',
                        ];

                        if (Constans::is_defined_array('DOCKET_CACHE_PRELOAD_ADMIN')) {
                            $preload_admin = DOCKET_CACHE_PRELOAD_ADMIN;
                        }

                        if (Constans::is_defined_array('DOCKET_CACHE_PRELOAD_NETWORK')) {
                            $preload_network = DOCKET_CACHE_PRELOAD_NETWORK;
                        }

                        if (is_user_logged_in() && current_user_can(is_multisite() ? 'manage_network_options' : 'manage_options') || DOCKET_CACHE_WPCLI) {
                            if (!empty($_COOKIE)) {
                                foreach ($_COOKIE as $name => $value) {
                                    $cookies[] = new \WP_Http_Cookie(
                                        [
                                            'name' => $name,
                                            'value' => $value,
                                        ]
                                    );
                                }
                                $args['cookies'] = $cookies;
                            }

                            @wp_remote_get(admin_url('/'), $args);
                            foreach ($preload_admin as $path) {
                                $url = admin_url('/'.$path);
                                if (DOCKET_CACHE_WPCLI) {
                                    fwrite(STDOUT, 'Fetch: '.$url."\n");
                                }
                                @wp_remote_get($url, $args);
                            }

                            if (is_multisite()) {
                                @wp_remote_get(network_admin_url('/'), $args);
                                foreach ($preload_network as $path) {
                                    $url = network_admin_url('/'.$path);
                                    if (DOCKET_CACHE_WPCLI) {
                                        fwrite(STDOUT, 'Fetch: '.$url."\n");
                                    }
                                    @wp_remote_get($url, $args);
                                    usleep(7500);
                                }
                            }
                        }
                    },
                    PHP_INT_MAX
                );
            }
        );
    }

    private function register_tweaks()
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

        add_action(
            'pre_get_posts',
            function () {
                remove_filter('posts_clauses', '_filter_query_attachment_filenames');
            },
            PHP_INT_MAX
        );

        add_filter(
            'wp_link_query_args',
            function ($query) {
                $query['no_found_rows'] = true;

                return $query;
            },
            PHP_INT_MAX
        );

        add_filter('postmeta_form_keys', '__return_false');

        if (DOCKET_CACHE_MISC_TWEAKS) {
            // wp: if only one post is found by the search results, redirect user to that post
            add_action(
                'template_redirect',
                function () {
                    if (is_search()) {
                        global $wp_query;
                        if (1 === (int) $wp_query->post_count && 1 === (int) $wp_query->max_num_pages) {
                            wp_redirect(get_permalink($wp_query->posts['0']->ID));
                            exit;
                        }
                    }
                },
                PHP_INT_MAX
            );

            // wp: hide update notifications to non-admin users
            add_action(
                'admin_head',
                function () {
                    if (!current_user_can('update_core')) {
                        remove_action('admin_notices', 'update_nag', 3);
                    }
                },
                PHP_INT_MAX
            );

            // wp: header junk
            add_action(
                'after_setup_theme',
                function () {
                    remove_action('wp_head', 'rsd_link');
                    remove_action('wp_head', 'wp_generator');
                    remove_action('wp_head', 'feed_links', 2);
                    remove_action('wp_head', 'feed_links_extra', 3);
                    remove_action('wp_head', 'index_rel_link');
                    remove_action('wp_head', 'wlwmanifest_link');
                    remove_action('wp_head', 'start_post_rel_link', 10, 0);
                    remove_action('wp_head', 'parent_post_rel_link', 10, 0);
                    remove_action('wp_head', 'adjacent_posts_rel_link', 10, 0);
                    remove_action('wp_head', 'adjacent_posts_rel_link_wp_head', 10, 0);
                    remove_action('wp_head', 'wp_shortlink_wp_head', 10, 0);
                },
                PHP_INT_MAX
            );

            // wp: disable pingback
            add_action(
                'pre_ping',
                function (&$links) {
                    foreach ($links as $l => $link) {
                        if (0 === strpos($link, get_option('home'))) {
                            unset($links[$l]);
                        }
                    }
                },
                PHP_INT_MAX
            );

            // wp: disable and remove do_pings
            // https://wp-mix.com/wordpress-clean-up-do_pings/
            add_action(
                'init',
                function () {
                    if (isset($_GET['doing_wp_cron'])) {
                        remove_action('do_pings', 'do_all_pings');
                        wp_clear_scheduled_hook('do_pings');
                    }
                },
                PHP_INT_MAX
            );

            // jetpack: enables object caching for the response sent by instagram when querying for instagram image html
            // https://developer.jetpack.com/hooks/instagram_cache_oembed_api_response_body/
            add_filter('instagram_cache_oembed_api_response_body', '__return_true');

            // wp: disable xmlrpc
            // https://www.wpbeginner.com/plugins/how-to-disable-xml-rpc-in-wordpress/
            // https://kinsta.com/blog/xmlrpc-php/
            add_filter('xmlrpc_enabled', '__return_false');
            add_filter('pre_update_option_enable_xmlrpc', '__return_false');
            add_filter('pre_option_enable_xmlrpc', '__return_zero');
            add_action(
                'init',
                function () {
                    if (isset($_SERVER['REQUEST_URI']) && '/xmlrpc.php' === $_SERVER['REQUEST_URI']) {
                        http_response_code(403);
                        exit('xmlrpc.php not available.');
                    }
                },
                PHP_INT_MAX
            );

            if (class_exists('woocommerce')) {
                // wc: remove counts slowing down the dashboard
                remove_filter('wp_count_comments', ['WC_Comments', 'wp_count_comments'], 10);

                // wc: remove order count from admin menu
                add_filter('woocommerce_include_processing_order_count_in_menu', '__return_false');

                // wc: remove Processing Order Count in wp-admin
                //add_filter('woocommerce_menu_order_count', '__return_false');

                // wc: action_scheduler_migration_dependencies_met
                add_filter('action_scheduler_migration_dependencies_met', '__return_false');

                // wc: disable background image regeneration
                add_filter('woocommerce_background_image_regeneration', '__return_false');

                // wc: remove marketplace suggestions
                // https://rudrastyh.com/woocommerce/remove-marketplace-suggestions.html
                add_filter('woocommerce_allow_marketplace_suggestions', '__return_false');

                // wc: remove connect your store to WooCommerce.com admin notice
                add_filter('woocommerce_helper_suppress_admin_notices', '__return_true');

                // wc: disable the WooCommerce Admin
                //add_filter('woocommerce_admin_disabled', '__return_true');

                // wc: disable the WooCommere Marketing Hub
                add_filter(
                    'woocommerce_admin_features',
                    function ($features) {
                        $marketing = array_search('marketing', $features);
                        unset($features[$marketing]);

                        return $features;
                    }
                );
            }
        }

        // wp_cache: advanced cache post
        if ($this->has_dropin()) {
            if (DOCKET_CACHE_ADVCPOST) {
                Advanced_Post::inst();
            }
        }
    }

    public function garbage_collector()
    {
        $dir = realpath($this->cache_path);
        if (false !== $dir && is_dir($dir) && is_readable($dir) && 'docket-cache' === basename($dir)) {
            clearstatcache();
            foreach ($this->fs->scanfiles($dir) as $object) {
                $fn = $object->getFileName();
                $fx = $object->getPathName();
                $fm = time() + 120;
                if ($fm >= filemtime($fx) && (0 === $object->getSize() || 'dump_' === substr($fn, 0, 5))) {
                    $this->fs->unlink($fx, true);
                }
            }
        }
    }

    private function register_scheduler()
    {
        if (!DOCKET_CACHE_GC) {
            return;
        }

        add_action(
            'init',
            function () {
                add_filter(
                    'cron_schedules',
                    function ($schedules) {
                        $schedules = [
                            'docket_cache_gc' => [
                                'interval' => 30 * MINUTE_IN_SECONDS,
                                'display' => __('Docket Cache Garbage Collector Run Every 30 minutes', 'docket-cache'),
                            ],
                        ];

                        return $schedules;
                    }
                );

                add_action('docket_cache_gc', [$this, 'garbage_collector']);

                if (!wp_next_scheduled('docket_cache_gc')) {
                    wp_schedule_event(time(), 'docket_cache_gc', 'docket_cache_gc');
                }
            }
        );
    }

    private function unregister_scheduler()
    {
        wp_clear_scheduled_hook('docket_cache_gc');
    }

    public function attach()
    {
        $this->register_plugin_hooks();
        $this->register_admin_hooks();
        $this->register_tweaks();
        $this->register_scheduler();

        if (DOCKET_CACHE_WPCLI && !\defined('Docket_Cache_CLI')) {
            \define('Docket_Cache_CLI', true);
            $cli = new Command($this);
            \WP_CLI::add_command('cache update', [$cli, 'update_dropin']);
            \WP_CLI::add_command('cache enable', [$cli, 'enable']);
            \WP_CLI::add_command('cache disable', [$cli, 'disable']);
            \WP_CLI::add_command('cache status', [$cli, 'status']);
            \WP_CLI::add_command('cache type', [$cli, 'type']);
            \WP_CLI::add_command('cache preload', [$cli, 'run_preload']);
        }
    }
}
