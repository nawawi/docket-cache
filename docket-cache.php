<?php
/**
 * @wordpress-plugin
 * Plugin Name:         Docket Cache
 * Version:             1.0.0
 * Description:         A persistent WP Object Cache stored on local disk.
 * GitHub Plugin URI:   https://github.com/nawawi/docket-cache
 * Author:              Nawawi Jamili
 * Author URI:          https://rutweb.com
 * Requires at least:   5.4
 * Requires PHP:        7.2
 * License:             MIT
 * License URI:         https://github.com/nawawi/docket-cache/blob/master/LICENSE.txt
 * Text Domain:         docket-cache
 * Domain Path:         /languages
 */
if (!\defined('ABSPATH') || \defined('DOCKET_CACHE_VERSION')) {
    exit;
}

\define('DOCKET_CACHE_VERSION', '1.0.0');

class Docket_Object_Cache
{
    public $slug;
    public $path;

    private $screen = 'settings_page_docket-cache';
    private $actions = [
        'docket-enable-cache',
        'docket-disable-cache',
        'docket-flush-cache',
        'docket-update-dropin',
    ];

    public $status_code;
    private $hook;
    private $page;
    private $token;

    public function __construct()
    {
        $this->slug = 'docket-cache';
        $this->file = __FILE__;
        $this->hook = plugin_basename($this->file);
        $this->path = realpath(plugin_dir_path($this->file));
        $this->page = (is_multisite() ? 'settings.php?page=' : 'options-general.php?page=').$this->slug;

        $this->register_plugin_hooks();

        $this->status_code = [
            0 => __('Disabled', $this->slug),
            1 => __('Enable', $this->slug),
            2 => __('Not Available', $this->slug),
            -1 => __('Unknown', $this->slug),
        ];
        $this->register_admin_hooks();
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

        $cache['dropin'] = get_plugin_data(WP_CONTENT_DIR.'/object-cache.php');
        $cache['plugin'] = get_plugin_data($this->path.'/includes/object-cache.php');

        return $cache[$key];
    }

    public function validate_dropin()
    {
        if (!$this->has_dropin()) {
            return false;
        }

        if (0 !== strcmp($this->plugin_data('dropin')['Name'], $this->plugin_data('plugin')['Name'])) {
            return false;
        }

        return true;
    }

    public function get_status()
    {
        if (!$this->has_dropin() || (\defined('DOCKET_CACHE_DISABLED') && DOCKET_CACHE_DISABLED)) {
            return 0;
        }

        if ($this->validate_dropin()) {
            return 1;
        }

        return -1;
    }

    public function get_dirsize()
    {
        $dir = realpath(DOCKET_CACHE_PATH);

        $bytestotal = 0;
        if (false !== $dir && is_dir($dir) && is_readable($dir)) {
            foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS)) as $object) {
                if ('index.php' !== $object->getFileName()) {
                    $bytestotal += $object->getSize();
                }
            }
        }

        return $this->normalize_size($bytestotal);
    }

    public function get_opcache_status()
    {
        if (\function_exists('opcache_get_status')) {
            $status = @opcache_get_status();
            if (\is_array($status) && $status['opcache_enabled']) {
                return 1;
            }

            return 0;
        }

        return 2;
    }

    public function get_mem_size()
    {
        $mem = @ini_get('memory_limit');

        return $this->normalize_size($mem);
    }

    private function normalize_size($size)
    {
        $size = $this->normalize_int($size);
        $size1 = size_format($size, 1);
        if (false !== strpos($size1, '.0')) {
            $size1 = size_format($size);
        }
        $size = str_replace([' ', 'B'], '', $size1);

        return $size;
    }

    private function normalize_int($size)
    {
        if (\is_string($size)) {
            $unit = strtolower(substr($size, -1));
            switch ($unit) {
                case 'm': return  (int) $size * 1048576;
                case 'k': return  (int) $size * 1024;
                case 'g': return  (int) $size * 1073741824;
            }
        }

        return (int) $size;
    }

    public function maybe_filesystem()
    {
        if (!isset($GLOBALS['wp_filesystem'])) {
            if (!\function_exists('WP_Filesystem')) {
                require_once ABSPATH.'/wp-admin/includes/file.php';
            }

            WP_Filesystem();
        }

        if (!\function_exists('WP_Filesystem')) {
            throw new Exception('WP_Filesystem not available');
        }

        return $GLOBALS['wp_filesystem'];
    }

    public function access_filesystem($url, $silent = false)
    {
        $this->maybe_filesystem();

        if ($silent) {
            ob_start();
        }

        if (false === ($credentials = request_filesystem_credentials($url))) {
            if ($silent) {
                ob_end_clean();
            }

            return false;
        }

        if (!WP_Filesystem($credentials)) {
            request_filesystem_credentials($url);

            if ($silent) {
                ob_end_clean();
            }

            return false;
        }

        return true;
    }

    private function dropin_file()
    {
        $dt = new stdClass();
        $dt->src = $this->path.'/includes/object-cache.php';
        $dt->dst = WP_CONTENT_DIR.'/object-cache.php';

        return $dt;
    }

    public function dropin_install()
    {
        $wp_filesystem = $this->maybe_filesystem();
        $src = $this->dropin_file()->src;
        $dst = $this->dropin_file()->dst;

        return @$wp_filesystem->copy($src, $dst, true);
    }

    public function dropin_uninstall()
    {
        $wp_filesystem = $this->maybe_filesystem();
        $dst = $this->dropin_file()->dst;

        return $wp_filesystem->delete($dst);
    }

    private function dropin_remove()
    {
        wp_cache_flush();
        if ($this->validate_dropin() && $this->access_filesystem('', true)) {
            $this->dropin_uninstall();
        }
    }

    public static function uninstall()
    {
        (new self())->dropin_remove();
    }

    public static function deactivate()
    {
        (new self())->dropin_remove();
    }

    public static function activate()
    {
        wp_cache_flush();
    }

    private function register_plugin_hooks()
    {
        if (!\defined('DOCKET_CACHE_PATH')) {
            \define('DOCKET_CACHE_PATH', WP_CONTENT_DIR.'/cache/docket-cache/');
        }

        if ('/' === DOCKET_CACHE_PATH) {
            throw new Exception('Invalid setting for DOCKET_CACHE_PATH'.DOCKET_CACHE_PATH);
        }

        if (!\defined('DOCKET_CACHE_MAXTTL') || !\is_int(DOCKET_CACHE_MAXTTL)) {
            \define('DOCKET_CACHE_MAXTTL', 86400);
        }

        if (\defined('WP_CLI') && WP_CLI) {
            require_once $this->path.'/includes/wp-cli.php';
        }

        add_action(
            'plugins_loaded',
            function () {
                load_plugin_textdomain(
                    $this->slug,
                    false,
                    $this->path.'/languages/'
                );
            },
            0
        );
        register_activation_hook($this->hook, [__CLASS__, 'activate']);
        register_deactivation_hook($this->hook, [__CLASS__, 'deactivate']);
        register_uninstall_hook($this->hook, [__CLASS__, 'uninstall']);
    }

    private function action_query($key)
    {
        $key = 'docket-'.$key;

        return wp_nonce_url(network_admin_url(add_query_arg('action', $key, $this->page)), $key);
    }

    private function register_admin_hooks()
    {
        $action_name = is_multisite() ? 'network_admin_menu' : 'admin_menu';
        add_action($action_name, function () {
            $page_link = is_multisite() ? 'settings.php' : 'options-general.php';
            $page_cap = is_multisite() ? 'manage_network_options' : 'manage_options';

            add_submenu_page(
                $page_link,
                __('Docket Object Cache', $this->slug),
                __('Docket Cache', $this->slug),
                $page_cap,
                $this->slug,
                function () {
                    if (isset($_GET['_wpnonce'], $_GET['action'])) {
                        $action = sanitize_text_field($_GET['action']);

                        foreach ($this->actions as $name) {
                            if ($action === $name && wp_verify_nonce($_GET['_wpnonce'], $action)) {
                                $url = wp_nonce_url(network_admin_url(add_query_arg('action', $action, $this->page)), $action);
                                if (false === $this->access_filesystem($url)) {
                                    return;
                                }
                            }
                        }
                    }
                    include_once $this->path.'/includes/admin.php';
                }
            );
        });

        add_action('all_admin_notices', function () {
            if (!current_user_can(is_multisite() ? 'manage_network_options' : 'manage_options')) {
                return;
            }

            if ($this->has_dropin()) {
                $url = wp_nonce_url(network_admin_url(add_query_arg('action', 'docket-update-dropin', $this->page)), 'docket-update-dropin');

                if ($this->validate_dropin()) {
                    if (version_compare($this->plugin_data('dropin')['Version'], $this->plugin_data('plugin')['Version'], '<')) {
                        $message = sprintf(__('The Docket Object Cache drop-in is outdated. Please <a href="%s">update it now</a>.', $this->slug), $url);
                    }
                } else {
                    $message = sprintf(__('An unknown object cache drop-in was found. To use Docket, <a href="%s">please replace it now</a>.', $this->slug), $url);
                }

                if (isset($message)) {
                    printf('<div class="update-nag">%s</div>', $message);
                }
            }
        });

        add_action('admin_enqueue_scripts', function ($hook) {
            if ($hook === $this->screen) {
                wp_enqueue_style($this->slug, plugin_dir_url($this->file).'includes/admin.css', null, DOCKET_CACHE_VERSION);
                wp_enqueue_script('wp-util');
            }
        });

        add_action('wp_ajax_docket_preload', function () {
            do_action('docket_preload');
            wp_send_json_success($this->slug.': run preload');
        });

        add_action('load-'.$this->screen, function () {
            if (isset($_GET['_wpnonce'], $_GET['action'])) {
                $action = sanitize_text_field($_GET['action']);

                foreach ($this->actions as $name) {
                    if ($action === $name && !wp_verify_nonce($_GET['_wpnonce'], $action)) {
                        return;
                    }
                }

                if (\in_array($action, $this->actions)) {
                    $url = wp_nonce_url(network_admin_url(add_query_arg('action', $action, $this->page)), $action);

                    if ('docket-flush-cache' === $action) {
                        $message = wp_cache_flush() ? 'docket-cache-flushed' : 'docket-cache-flushed-failed';
                    }

                    if ($this->access_filesystem($url, true)) {
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
                        }
                    }

                    if (isset($message)) {
                        wp_safe_redirect(network_admin_url(add_query_arg('message', $message, $this->page)));
                        exit;
                    }
                }
            }
        });

        add_action('load-'.$this->screen, function () {
            if (version_compare(PHP_VERSION, '7.2', '<')) {
                add_settings_error(is_multisite() ? 'general' : '', $this->slug, __('This plugin requires PHP 7.2 or greater.', $this->slug));
            }

            if (isset($_GET['message'])) {
                $token = sanitize_text_field($_GET['message']);
                $this->token = $token;
                switch ($token) {
                    case 'docket-cache-enabled':
                        $message = __('Object cache enabled.', $this->slug);
                        break;
                    case 'docket-cache-enabled-failed':
                        $error = __('Object cache could not be enabled.', $this->slug);
                        break;
                    case 'docket-cache-disabled':
                        $message = __('Object cache disabled.', $this->slug);
                        break;
                    case 'docket-cache-disabled-failed':
                        $error = __('Object cache could not be disabled.', $this->slug);
                        break;
                    case 'docket-cache-flushed':
                        $message = __('Object cache was flushed.', $this->slug);
                        break;
                    case 'docket-cache-flushed-failed':
                        $error = __('Object cache could not be flushed.', $this->slug);
                        break;
                    case 'docket-dropin-updated':
                        $message = __('Updated object cache drop-in and enabled Docket object cache.', $this->slug);
                        break;
                    case 'docket-dropin-updated-failed':
                        $error = __('Docket object cache drop-in could not be updated.', $this->slug);
                        break;
                }

                add_settings_error(is_multisite() ? 'general' : '', $this->slug, isset($message) ? $message : $error, isset($message) ? 'updated' : 'error');
            }
        });

        $filter_name = sprintf('%splugin_action_links_%s', is_multisite() ? 'network_admin_' : '', $this->hook);
        add_filter($filter_name, function ($links) {
            array_unshift(
                $links,
                sprintf(
                    '<a href="%s">%s</a>',
                    network_admin_url($this->page),
                    __('Settings', $this->slug)
                )
            );

            return $links;
        });

        add_action('docket_preload', function () {
            if (!\defined('DOCKET_CACHE_PRELOAD') || !DOCKET_CACHE_PRELOAD) {
                return;
            }

            // preload
            add_action('shutdown', function () {
                $args = [
                    'blocking' => false,
                    'timeout' => 45,
                    'httpversion' => '1.1',
                    'user-agent' => 'docket-cache',
                    'body' => null,
                    'compress' => false,
                    'decompress' => true,
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

                if (\defined('DOCKET_CACHE_PRELOAD_ADMIN') && \is_array(DOCKET_CACHE_PRELOAD_ADMIN) && !empty(OCKET_CACHE_PRELOAD_ADMIN)) {
                    $preload_admin = DOCKET_CACHE_PRELOAD_ADMIN;
                }

                if (\defined('DOCKET_CACHE_PRELOAD_NETWORK') && \is_array(DOCKET_CACHE_PRELOAD_NETWORK) && !empty(DOCKET_CACHE_PRELOAD_NETWORK)) {
                    $preload_network = DOCKET_CACHE_PRELOAD_NETWORK;
                }

                if (is_user_logged_in() && current_user_can(is_multisite() ? 'manage_network_options' : 'manage_options')) {
                    foreach ($_COOKIE as $name => $value) {
                        $cookies[] = new WP_Http_Cookie(['name' => $name, 'value' => $value]);
                    }

                    $args['cookies'] = $cookies;

                    @wp_remote_get(admin_url('/'), $args);
                    foreach ($preload_admin as $path) {
                        @wp_remote_get(admin_url('/'.$path), $args);
                    }

                    if (is_multisite()) {
                        @wp_remote_get(network_admin_url('/'), $args);
                        foreach ($preload_network as $path) {
                            @wp_remote_get(network_admin_url('/'.$path), $args);
                        }
                    }
                }
            }, PHP_INT_MAX, 2);
        });
    }

    private function debug_log($limit = 100)
    {
        $limit = (int) $limit;
        $output = [];
        if (\defined('DOCKET_CACHE_DEBUG_FILE') && is_file(DOCKET_CACHE_DEBUG_FILE) && is_readable(DOCKET_CACHE_DEBUG_FILE)) {
            $file = new SplFileObject(DOCKET_CACHE_DEBUG_FILE);
            $file->seek(PHP_INT_MAX);
            $total_lines = $file->key();
            $total_lines = $total_lines > $limit ? $total_lines - $limit : $total_lines;
            if ($total_lines > 0) {
                $reader = new LimitIterator($file, $total_lines);
                foreach ($reader as $line) {
                    $line = trim($line);
                    if (empty($line)) {
                        continue;
                    }
                    $output[] = $line;
                }
            }
        }

        return $output;
    }
}

$GLOBALS['Docket_Object_Cache'] = new Docket_Object_Cache();
