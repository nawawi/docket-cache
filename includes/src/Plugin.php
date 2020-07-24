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
     * Dropin() instance.
     *
     * @var object
     */
    public $dropin;

    /**
     * View() instance.
     *
     * @var object
     */
    private $view;

    /**
     * Canopt() instance.
     *
     * @var object
     */
    public $canopt;

    /**
     * The cache path.
     *
     * @var string
     */
    private $cache_path;

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
             'cache_size' => $this->normalize_size($this->cache_size($this->cache_path)),
             'cache_path_real' => $this->cache_path,
             'cache_path' => $this->sanitize_rootpath($this->cache_path),
             'log_file_real' => DOCKET_CACHE_LOG_FILE,
             'log_file' => $this->sanitize_rootpath(DOCKET_CACHE_LOG_FILE),
             'log_enable' => DOCKET_CACHE_LOG ? 1 : 0,
             'log_enable_text' => $status_code[DOCKET_CACHE_LOG ? 1 : 0],
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
        if (\defined('DOCKET_CACHE_DISABLED') && DOCKET_CACHE_DISABLED || \defined('DOCKET_CACHE_HALT') && DOCKET_CACHE_HALT) {
            return 2;
        }

        if (!$this->dropin->exists()) {
            return 0;
        }

        if ($this->dropin->validate()) {
            return 1;
        }

        return 3;
    }

    /**
     * get_logsize.
     */
    public function get_logsize()
    {
        return $this->has_log() ? $this->normalize_size($this->filesize(DOCKET_CACHE_LOG_FILE)) : 0;
    }

    /**
     * get_opcache_status.
     */
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

    /**
     * normalize_size.
     */
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

    /**
     * flush_cache.
     */
    public function flush_cache()
    {
        $this->dropin->delay();
        $this->cachedir_flush($this->cache_path, false);

        return true;
    }

    /**
     * has_log.
     */
    public function has_log()
    {
        return @is_file(DOCKET_CACHE_LOG_FILE) && is_readable(DOCKET_CACHE_LOG_FILE);
    }

    /**
     * flush_log.
     */
    public function flush_log()
    {
        if ($this->has_log()) {
            return @unlink(DOCKET_CACHE_LOG_FILE);
        }

        return false;
    }

    /**
     * cleanup.
     */
    private function cleanup()
    {
        if ($this->dropin->validate()) {
            $this->dropin->uninstall();
        }

        $this->dropin->undelay();
        $this->cachedir_flush($this->cache_path, true);
        $this->flush_log();
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
        $this->unregister_garbage_collector();
    }

    /**
     * activate.
     */
    public function activate()
    {
        if ($this->dropin->validate()) {
            // our cache
            $this->flush_cache();
        } else {
            // others dropin
            wp_cache_flush();
        }

        // replace with our dropin
        $this->dropin->install(true);

        $this->unregister_garbage_collector();
    }

    private function register_init()
    {
        $this->page = (is_multisite() ? 'settings.php?page=' : 'options-general.php?page=').$this->slug;
        $this->screen = 'settings_page_docket-cache';

        Constans::init();

        $this->cache_path = is_dir(DOCKET_CACHE_PATH) && '/' !== DOCKET_CACHE_PATH ? rtrim(DOCKET_CACHE_PATH, '/\\').'/' : WP_CONTENT_DIR.'/cache/docket-cache/';
        if (!$this->is_docketcachedir($this->cache_path)) {
            $this->cache_path = rtim($this->cache_path, '/').'docket-cache/';
        }

        $this->dropin = new Dropin($this->path);
        $this->view = new View($this);
        $this->canopt = new Canopt();
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
                                    $this->dropin->install(true);
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
            'admin_head',
            function () {
                $output = $this->dropin->after_delay();
                if (!empty($output)) {
                    echo $output;
                }
            },
            PHP_INT_MAX
        );

        if (\defined('DOCKET_CACHE_PRIVATEREPO') && class_exists('Nawawi\\DocketCache\\PrivateRepo')) {
            new PrivateRepo($this->slug, $this->hook, $this->plugin_meta($this->file)['Version'], DOCKET_CACHE_PRIVATEREPO);
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
             'docket-enable-cache',
             'docket-disable-cache',
             'docket-flush-cache',
             'docket-update-dropin',
             'docket-flush-log',
         ];

        foreach ($this->canopt->keys() as $key) {
            $keys[] = 'docket-default-'.$key;
            $keys[] = 'docket-enable-'.$key;
            $keys[] = 'docket-disable-'.$key;
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

    /**
     * fastcgi_close.
     */
    public function fastcgi_close()
    {
        if ((\PHP_SAPI === 'fpm-fcgi')
            && \function_exists('fastcgi_finish_request')) {
            @session_write_close();
            @fastcgi_finish_request();
        }
    }

    /**
     * ajax_response_continue.
     */
    public function ajax_response_continue($msg, $success = true)
    {
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=UTF-8');
        }

        $response = ['success' => $success];
        $response['data'] = $msg;
        echo wp_json_encode($response);
        $this->fastcgi_close();
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
                        $this->view->index();
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

                if ($this->dropin->exists()) {
                    $url = $this->action_query('update-dropin');

                    if ($this->dropin->validate()) {
                        if ($this->dropin->is_outdated() && !$this->dropin->install(false)) {
                            /* translators: %s: url */
                            $message = sprintf(__('<strong>Docket Cache:</strong> The object-cache.php drop-in is outdated. Please click "Re-Install" to update it now.<p style="padding:0;"><a href="%s" class="button button-primary">Re-Install</a>', 'docket-cache'), $url);
                        }
                    } else {
                        if (!$this->dropin->install(false) ) {
                            /* translators: %s: url */
                            $message = sprintf(__('<strong>Docket Cache:</strong> An unknown object-cache.php drop-in was found. Please click "Install" to use Docket Cache.<p style="margin-bottom:0;"><a href="%s" class="button button-primary">Install</a></p>', 'docket-cache'), $url);
                        }
                    }
                }

                if (2 === $this->get_status() && get_current_screen()->id === $this->screen) {
                    $message = __('The object-cache.php drop-in has been disabled at runtime.', 'docket-cache');
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
                $plugin_url = plugin_dir_url($this->file);
                $version = str_replace('.', '', $this->plugin_meta($this->file)['Version']).'x'.date('d');
                wp_enqueue_script($this->slug.'-worker', $plugin_url.'includes/admin/worker.js', ['jquery'], $version, false);
                wp_localize_script(
                    $this->slug.'-worker',
                    'docket_cache_config',
                    [
                        'ajaxurl' => admin_url('admin-ajax.php'),
                        'token' => wp_create_nonce('docketcache-token-nonce'),
                        'slug' => $this->slug,
                        'log' => DOCKET_CACHE_LOG ? 'true' : 'false',
                    ]
                );
                if ($hook === $this->screen) {
                    wp_enqueue_style($this->slug.'-core', $plugin_url.'includes/admin/docket.css', null, $version);
                    wp_enqueue_script($this->slug.'-core', $plugin_url.'includes/admin/docket.js', ['jquery'], $version, true);
                }

                if (DOCKET_CACHE_PAGELOADER) {
                    wp_enqueue_style($this->slug.'-loader', $plugin_url.'includes/admin/pageloader.css', null, $version);
                    wp_enqueue_script($this->slug.'-loader', $plugin_url.'includes/admin/pageloader.js', ['jquery'], $version, true);
                }
            }
        );

        add_action(
            'wp_ajax_docket_preload',
            function () {
                if (!check_ajax_referer('docketcache-token-nonce', 'token', false)) {
                    wp_send_json_error('Invalid security token sent.');
                    exit;
                }

                if ( $this->dropin->validate() ) {
                    $this->ajax_response_continue($this->slug.': pong preload');
                    do_action('docket_preload');
                    exit;
                }

                wp_send_json_success($this->slug.': pong preload not available');
                exit;
            }
        );

        add_action(
            'load-'.$this->screen,
            function () {
                if (isset($_GET['_wpnonce'], $_GET['action'])) {
                    $action = sanitize_text_field($_GET['action']);

                    if (\in_array($action, $this->action_token())) {
                        if (!wp_verify_nonce($_GET['_wpnonce'], $action)) {
                            return;
                        }
                        if ('docket-flush-cache' === $action) {
                            $message = $this->flush_cache() ? 'docket-cache-flushed' : 'docket-cache-flushed-failed';
                        }

                        if (is_writable(WP_CONTENT_DIR)) {
                            switch ($action) {
                                case 'docket-enable-cache':
                                    $result = $this->dropin->install(true);
                                    $message = $result ? 'docket-cache-enabled' : 'docket-cache-enabled-failed';
                                    do_action('docket_cache_enable', $result);
                                    break;

                                case 'docket-disable-cache':
                                    $result = $this->dropin->uninstall();
                                    $message = $result ? 'docket-cache-disabled' : 'docket-cache-disabled-failed';
                                    do_action('docket_cache_disable', $result);
                                    break;

                                case 'docket-update-dropin':
                                    $result = $this->dropin->install(true);
                                    $message = $result ? 'docket-dropin-updated' : 'docket-dropin-updated-failed';
                                    do_action('docket_cache_update_dropin', $result);
                                    break;

                                case 'docket-flush-log':
                                    $result = $this->flush_log();
                                    $message = $result ? 'docket-log-flushed' : 'docket-log-flushed-failed';
                                    do_action('docket_cache_flush_log', $result);
                                    break;
                            }

                            if (empty($message) && preg_match('@^docket-(default|enable|disable)-([a-z_]+)$@', $action, $mm)) {
                                $nk = $mm[1];
                                $nx = $mm[2];
                                if (\in_array($nx, $this->canopt->keys())) {
                                    $okmsg = 'default' === $nk ? 'docket-option-default' : 'docket-option-'.$nk;
                                    $message = $this->canopt->save($nx, $nk) ? $okmsg : 'docket-option-failed';
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
                        case 'docket-option-enable':
                            $message = __('Option enabled.', 'docket-cache');
                            break;
                        case 'docket-option-disable':
                            $message = __('Option disabled.', 'docket-cache');
                            break;
                        case 'docket-option-default':
                            $message = __('Option resets to default.', 'docket-cache');
                            break;
                        case 'docket-option-failed':
                            $error = __('Failed to change options.', 'docket-cache');
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
                        $text = __('Enable Object Cache', 'docket-cache');
                        $action = 'enable-cache';
                        break;
                    case 1:
                        $text = __('Disable Object Cache', 'docket-cache');
                        $action = 'disable-cache';
                        break;
                    default:
                        $text = __('Install Drop-in', 'docket-cache');
                        $action = 'update-cache';
                }

                $links[] = sprintf('<a href="%s">%s</a>', $this->action_query($action), $text);

                return $links;
            }
        );

        add_action(
            'docket_preload',
            function () {
                if (!DOCKET_CACHE_PRELOAD) {
                    // preload minima
                    wp_load_alloptions();
                    wp_count_comments(0);
                    @Crawler::fetch(get_home_url());
                    @Crawler::fetch(admin_url('/index.php'));
                    @Crawler::fetch(network_admin_url('/index.php'));

                    return;
                }

                // preload
                add_action(
                    'shutdown',
                    function () {
                        wp_load_alloptions();
                        wp_count_comments(0);

                        @Crawler::fetch(get_home_url());

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

                        @Crawler::fetch_admin(admin_url('/index.php'));
                        foreach ($preload_admin as $path) {
                            $url = admin_url('/'.$path);
                            if (DOCKET_CACHE_WPCLI) {
                                fwrite(STDOUT, 'Fetch: '.$url."\n");
                            }
                            @Crawler::fetch_admin($url);
                            usleep(7500);
                        }

                        if (is_multisite()) {
                            @Crawler::fetch_admin(network_admin_url('/index.php'));
                            foreach ($preload_network as $path) {
                                $url = network_admin_url('/'.$path);
                                if (DOCKET_CACHE_WPCLI) {
                                    fwrite(STDOUT, 'Fetch: '.$url."\n");
                                }
                                @Crawler::fetch_admin($url);
                                usleep(7500);
                            }
                        }
                    },
                    PHP_INT_MAX
                );
            }
        );
    }

    /**
     * register_tweaks.
     */
    private function register_tweaks()
    {
        // set first
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
        if ($this->dropin->exists()) {
            if (DOCKET_CACHE_ADVCPOST && class_exists('Nawawi\\DocketCache\\AdvancedPost')) {
                AdvancedPost::init();
            }
        }
    }

    /**
     * garbage_collector.
     */
    public function garbage_collector()
    {
        if ($this->is_docketcachedir($this->cache_path)) {
            clearstatcache();
            foreach ($this->scanfiles($this->cache_path) as $object) {
                $fn = $object->getFileName();
                $fx = $object->getPathName();
                $fs = $object->getSize();
                $fm = time() + 120;
                if ($fm >= filemtime($fx) && (0 === $fs || 'dump_' === substr($fn, 0, 5))) {
                    $this->unlink($fx, true);
                }
            }
        }
        $this->dropin->delay_expire();
    }

    /**
     * register_garbage_collector.
     */
    private function register_garbage_collector()
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

    /**
     * unregister_garbage_collector.
     */
    private function unregister_garbage_collector()
    {
        wp_clear_scheduled_hook('docket_cache_gc');
    }

    private function register_cli()
    {
        if (DOCKET_CACHE_WPCLI && !\defined('DocketCache_CLI')) {
            \define('DocketCache_CLI', true);
            $cli = new Command($this);
            \WP_CLI::add_command('cache update', [$cli, 'update_dropin']);
            \WP_CLI::add_command('cache enable', [$cli, 'enable']);
            \WP_CLI::add_command('cache disable', [$cli, 'disable']);
            \WP_CLI::add_command('cache status', [$cli, 'status']);
            \WP_CLI::add_command('cache type', [$cli, 'type']);
            \WP_CLI::add_command('cache preload', [$cli, 'run_preload']);
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
        $this->register_garbage_collector();
        $this->register_cli();
    }
}
