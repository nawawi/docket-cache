<?php
/**
 * @wordpress-plugin
 * Plugin Name:         Docket Cache
 * Version:             1.0.0
 * Description:         A persistent WP Object Cache stored on local disk.
 * Author:              Nawawi Jamili
 * Author URI:          https://rutweb.com
 * Requires at least:   5.4
 * Requires PHP:        7.2
 * License:             MIT
 * License URI:         https://opensource.org/licenses/MIT
 * Text Domain:         docket-cache
 * Domain Path:         /languages
 */
if (!defined('ABSPATH') || defined('DOCKET_CACHE_VERSION')) {
    exit;
}

define('DOCKET_CACHE_VERSION', '1.0.0');

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

    private $hook;
    private $page;

    public function __construct()
    {
        $this->slug = 'docket-cache';
        $this->file = __FILE__;
        $this->hook = plugin_basename($this->file);
        $this->path = realpath(plugin_dir_path($this->file));
        $this->page = (is_multisite() ? 'settings.php?page=' : 'options-general.php?page=').$this->slug;

        $this->register_plugin_hooks();
        $this->register_admin_hooks();
    }

    public function enqueue_admin_styles($hook_suffix)
    {
        if ($hook_suffix === $this->screen) {
            wp_enqueue_style($this->slug, plugin_dir_url(__FILE__).'includes/admin.css', null, DOCKET_CACHE_VERSION);
        }
    }

    public function enqueue_admin_scripts()
    {
        $screen = get_current_screen();

        if (!isset($screen->id)) {
            return;
        }
    }

    public function has_dropin()
    {
        return file_exists(WP_CONTENT_DIR.'/object-cache.php');
    }

    public function validate_dropin()
    {
        if (!$this->has_dropin()) {
            return false;
        }

        $dropin = get_plugin_data(WP_CONTENT_DIR.'/object-cache.php');
        $plugin = get_plugin_data($this->path.'/includes/object-cache.php');

        if (0 !== strcmp($dropin['Name'], $plugin['Name'])) {
            return false;
        }

        return true;
    }

    public function get_status()
    {
        if (!$this->has_dropin() || (defined('DOCKET_CACHE_DISABLED') && DOCKET_CACHE_DISABLED)) {
            return __('Disabled', $this->slug);
        }

        if ($this->validate_dropin()) {
            return __('Enabled', $this->slug);
        }

        return __('Unknown', $this->slug);
    }

    public function get_dirsize()
    {
        $dir = defined('DOCKET_CACHE_PATH') && is_dir(DOCKET_CACHE_PATH) && '/' !== DOCKET_CACHE_PATH ? DOCKET_CACHE_PATH : WP_CONTENT_DIR.'/cache/docket-cache/';
        $size = $this->dirsize($dir);

        return size_format($size);
    }

    public function get_opcache_status()
    {
        if (function_exists('opcache_get_status')) {
            $status = @opcache_get_status();
            if (is_array($status) && $status['opcache_enabled']) {
                return __('Enabled', $this->slug);
            }

            return __('Disabled', $this->slug);
        }

        return __('Not Available', $this->slug);
    }

    private function dirsize($dir)
    {
        $size = 0;

        foreach (glob(rtrim($dir, '/').'/*-*.php', GLOB_NOSORT) as $each) {
            clearstatcache();
            $size += filesize($each);
        }

        return $size;
    }

    public function get_mem_size()
    {
        $mem = @ini_get('memory_limit');
        $mem = $this->normalize_int($mem);

        return size_format($mem);
    }

    private function normalize_int($size)
    {
        if (is_string($size)) {
            switch (substr($size, -1)) {
            case 'M': case 'm': return  (int) $size * 1048576;
            case 'K': case 'k': return  (int) $size * 1024;
            case 'G': case 'g': return  (int) $size * 1073741824;
        }
        }

        return (int) $size;
    }

    public function initialize_filesystem($url, $silent = false)
    {
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

    private function dropin_remove()
    {
        global $wp_filesystem;
        WP_Filesystem();

        wp_cache_flush();

        if ($this->validate_dropin() && $this->initialize_filesystem('', true)) {
            $wp_filesystem->delete(WP_CONTENT_DIR.'/object-cache.php');
        }
    }

    private function register_plugin_hooks()
    {
        if (defined('WP_CLI') && WP_CLI) {
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
                                if (false === $this->initialize_filesystem($url)) {
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
                    $dropin = get_plugin_data(WP_CONTENT_DIR.'/object-cache.php');
                    $plugin = get_plugin_data($this->path.'/includes/object-cache.php');

                    if (version_compare($dropin['Version'], $plugin['Version'], '<')) {
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

        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_styles']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);

        add_action('load-'.$this->screen, function () {
            global $wp_filesystem;
            WP_Filesystem();

            if (isset($_GET['_wpnonce'], $_GET['action'])) {
                $action = sanitize_text_field($_GET['action']);

                foreach ($this->actions as $name) {
                    if ($action === $name && !wp_verify_nonce($_GET['_wpnonce'], $action)) {
                        return;
                    }
                }

                if (in_array($action, $this->actions)) {
                    $url = wp_nonce_url(network_admin_url(add_query_arg('action', $action, $this->page)), $action);

                    if ('docket-flush-cache' === $action) {
                        $message = wp_cache_flush() ? 'docket-cache-flushed' : 'docket-cache-flushed-failed';
                    }

                    if ($this->initialize_filesystem($url, true)) {
                        $src = $this->path.'/includes/object-cache.php';
                        $dst = WP_CONTENT_DIR.'/object-cache.php';

                        switch ($action) {
                        case 'docket-enable-cache':
                            $result = $wp_filesystem->copy($src, $dst, true);
                            $message = $result ? 'docket-cache-enabled' : 'docket-cache-enabled-failed';
                            do_action('docket_object_cache_enable', $result);
                            break;

                        case 'docket-disable-cache':
                            $result = $wp_filesystem->delete($dst);
                            $message = $result ? 'docket-cache-disabled' : 'docket-cache-disabled-failed';
                            do_action('docket_object_cache_disable', $result);
                            break;

                        case 'docket-update-dropin':
                            $result = $wp_filesystem->copy($src, $dst, true);
                            $message = $result ? 'docket-dropin-updated' : 'docket-dropin-updated-failed';
                            do_action('docket_object_cache_update_dropin', $result);
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
                add_settings_error('', $this->slug, __('This plugin requires PHP 7.2 or greater.', $this->slug));
            }

            if (isset($_GET['message'])) {
                $token = sanitize_text_field($_GET['message']);

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
                        $message = __('Object cache flushed.', $this->slug);
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

                add_settings_error('', $this->slug, isset($message) ? $message : $error, isset($message) ? 'updated' : 'error');
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
    }
}

$GLOBALS['Docket_Object_Cache'] = new Docket_Object_Cache();
