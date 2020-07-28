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
     * Dropino() instance.
     *
     * @var object
     */
    public $dropino;

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

        if (!$this->dropino->exists()) {
            return 0;
        }

        if ($this->dropino->validate()) {
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
            if (\is_array($status) && (!empty($status['opcache_enabled']) || !empty($status['file_cache_only']))) {
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
        $this->dropino->delay();
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
        if ($this->dropino->validate()) {
            $this->dropino->uninstall();
        }

        $this->dropino->undelay();
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
        $this->flush_cache();
        $this->dropino->install(true);
        $this->unregister_garbage_collector();
        $this->wearechampion();
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

        $this->dropino = new Dropino($this->path);
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
                                    $this->dropino->install(true);

                                    // previous file format
                                    foreach (['object-cache-delay.txt', 'object-cache-after-delay.txt', 'object-cache.log'] as $f) {
                                        $fx = WP_CONTENT_DIR.'/'.$f;
                                        if (@is_file($fx)) {
                                            @unlink($fx);
                                        }
                                    }
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
                $output = $this->dropino->after_delay();
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
             'docket-enable-occache',
             'docket-disable-occache',
             'docket-flush-occache',
             'docket-update-dropino',
             'docket-flush-oclog',
         ];

        foreach ($this->canopt->keys() as $key) {
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
        return  substr(get_current_screen()->id, 0, \strlen($this->screen)) === $this->screen;
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

                if ($this->dropino->exists()) {
                    $url = $this->action_query('update-dropino');

                    if ($this->dropino->validate()) {
                        if ($this->dropino->is_outdated() && !$this->dropino->install(true)) {
                            /* translators: %s: url */
                            $message = sprintf(__('<strong>Docket Cache:</strong> The object-cache.php drop-in is outdated. Please click "Re-Install" to update it now.<p style="padding:0;"><a href="%s" class="button button-primary">Re-Install</a>', 'docket-cache'), $url);
                        }
                    } else {
                        if (!$this->dropino->install(true)) {
                            /* translators: %s: url */
                            $message = sprintf(__('<strong>Docket Cache:</strong> An unknown object-cache.php drop-in was found. Please click "Install" to use Docket Cache.<p style="margin-bottom:0;"><a href="%s" class="button button-primary">Install</a></p>', 'docket-cache'), $url);
                        }
                    }
                }

                if (2 === $this->get_status() && $this->our_screen()) {
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
                $version = str_replace('.', '', $this->plugin_meta($this->file)['Version']).'x'.(\defined('WP_DEBUG') && WP_DEBUG ? date('his') : date('d'));
                wp_enqueue_script($this->slug.'-worker', $plugin_url.'includes/admin/worker.js', ['jquery'], $version, false);
                wp_localize_script(
                    $this->slug.'-worker',
                    'docket_cache_config',
                    [
                        'ajaxurl' => admin_url('admin-ajax.php'),
                        'token' => wp_create_nonce('docketcache-token-nonce'),
                        'slug' => $this->slug,
                        'debug' => \defined('WP_DEBUG') && WP_DEBUG ? 'true' : 'false',
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
            'wp_ajax_docket_worker',
            function () {
                if (!check_ajax_referer('docketcache-token-nonce', 'token', false) && !isset($_POST['type'])) {
                    wp_send_json_error('Invalid security token sent.');
                    exit;
                }

                $type = sanitize_text_field($_POST['type']);

                if ($this->dropino->validate()) {
                    if ('preload' === $type) {
                        $this->send_json_continue($this->slug.':worker: pong '.$type);
                        do_action('docket_preload');
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
                    /* translators: %s: version */
                    $text = $this->plugin_meta($this->file)['Name'].' '.sprintf(__('Version %s', 'docket-cache'), $this->plugin_meta($this->file)['Version']);
                }

                return $text;
            },
            PHP_INT_MAX
        );

        add_filter(
            'update_footer',
            function ($text) {
                if ($this->our_screen()) {
                    /* translators: %s: version */
                    $text = 'WordPress '.' '.sprintf(__('Version %s', 'docket-cache'), $GLOBALS['wp_version']);
                }

                return $text;
            },
            PHP_INT_MAX
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
                        if ('docket-flush-occache' === $action) {
                            $message = $this->flush_cache() ? 'docket-occache-flushed' : 'docket-occache-flushed-failed';
                        }

                        if (is_writable(WP_CONTENT_DIR)) {
                            switch ($action) {
                                case 'docket-enable-occache':
                                    $result = $this->dropino->install(true);
                                    $message = $result ? 'docket-occache-enabled' : 'docket-occache-enabled-failed';
                                    do_action('docket_cache_enable', $result);
                                    break;

                                case 'docket-disable-occache':
                                    $result = $this->dropino->uninstall();
                                    $message = $result ? 'docket-occache-disabled' : 'docket-occache-disabled-failed';
                                    do_action('docket_cache_disable', $result);
                                    break;

                                case 'docket-update-dropino':
                                    $result = $this->dropino->install(true);
                                    $message = $result ? 'docket-dropino-updated' : 'docket-dropino-updated-failed';
                                    do_action('docket_cache_update_dropino', $result);
                                    break;

                                case 'docket-flush-oclog':
                                    $result = $this->flush_log();
                                    $message = $result ? 'docket-log-flushed' : 'docket-log-flushed-failed';
                                    do_action('docket_cache_flush_log', $result);
                                    break;
                            }

                            if (empty($message) && preg_match('@^docket-(default|enable|disable|save)-([a-z_]+)$@', $action, $mm)) {
                                $nk = $mm[1];
                                $nx = $mm[2];
                                if (\in_array($nx, $this->canopt->keys())) {
                                    if ('save' === $nk && isset($_GET['nv'])) {
                                        $nv = sanitize_text_field($_GET['nv']);
                                        $message = $this->canopt->save($nx, $nv) ? 'docket-option-save' : 'docket-option-failed';
                                    } else {
                                        $okmsg = 'default' === $nk ? 'docket-option-default' : 'docket-option-'.$nk;
                                        $message = $this->canopt->save($nx, $nk) ? $okmsg : 'docket-option-failed';
                                    }
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
                            $message = __('Object cache enabled.', 'docket-cache');
                            break;
                        case 'docket-occache-enabled-failed':
                            $error = __('Object cache could not be enabled.', 'docket-cache');
                            break;
                        case 'docket-occache-disabled':
                            $message = __('Object cache disabled.', 'docket-cache');
                            break;
                        case 'docket-occache-disabled-failed':
                            $error = __('Object cache could not be disabled.', 'docket-cache');
                            break;
                        case 'docket-occache-flushed':
                            $message = __('Object cache was flushed.', 'docket-cache');
                            break;
                        case 'docket-occache-flushed-failed':
                            $error = __('Object cache could not be flushed.', 'docket-cache');
                            break;
                        case 'docket-dropino-updated':
                            $message = __('Updated object cache drop-in and enabled Docket object cache.', 'docket-cache');
                            break;
                        case 'docket-dropino-updated-failed':
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
                        case 'docket-option-save':
                            $message = __('Option saved.', 'docket-cache');
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
                        $action = 'enable-occache';
                        break;
                    case 1:
                        $text = __('Disable Object Cache', 'docket-cache');
                        $action = 'disable-occache';
                        break;
                    default:
                        $text = __('Install Drop-in', 'docket-cache');
                        $action = 'update-dropino';
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

                    // make plugin tester happy
                    @Crawler::fetch(get_home_url());
                    $preload_min = [
                        'index.php',
                        'edit.php',
                        'edit-comments.php',
                        'edit-tags.php?taxonomy=category',
                        'edit.php?post_type=page',
                        'post-new.php?post_type=page',
                    ];
                    foreach ($preload_min as $path) {
                        $url = admin_url('/'.$path);
                        @Crawler::fetch_admin(admin_url($url));
                    }

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

                        if (Constans::is_defined_array('DOCKET_CACHE_PRELOAD_ADMIN')) {
                            $preload_admin = DOCKET_CACHE_PRELOAD_ADMIN;
                        }

                        if (Constans::is_defined_array('DOCKET_CACHE_PRELOAD_NETWORK')) {
                            $preload_network = DOCKET_CACHE_PRELOAD_NETWORK;
                        }

                        if (!DOCKET_CACHE_WPCLI) {
                            foreach ($preload_admin as $path) {
                                $url = admin_url('/'.$path);
                                if (!DOCKET_CACHE_WPCLI) {
                                    @Crawler::fetch_admin($url);
                                }

                                usleep(7500);
                            }
                        }

                        if (is_multisite()) {
                            if (!DOCKET_CACHE_WPCLI) {
                                foreach ($preload_network as $path) {
                                    $url = network_admin_url('/'.$path);
                                    @Crawler::fetch_admin($url);
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

    /**
     * register_tweaks.
     */
    private function register_tweaks()
    {
        $this->wearechampion();

        if (class_exists('Nawawi\\DocketCache\\Tweaks')) {
            $tweaks = new Tweaks($this);
            $tweaks->vipcom();

            if (DOCKET_CACHE_MISC_TWEAKS) {
                $tweaks->misc();
                $tweaks->woocommerce();
            }
        }

        // wp_cache: advanced cache post
        if ($this->dropino->exists()) {
            if (DOCKET_CACHE_ADVCPOST && class_exists('Nawawi\\DocketCache\\AdvancedPost')) {
                AdvancedPost::init();
            }

            if (DOCKET_CACHE_MOCACHE && class_exists('Nawawi\\DocketCache\\MoCache')) {
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
        if (DOCKET_CACHE_OPTERMCOUNT && class_exists('Nawawi\\DocketCache\\TermCount')) {
            TermCount::init();
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
        $this->dropino->delay_expire();
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
            \WP_CLI::add_command('cache update', [$cli, 'update_dropino']);
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
