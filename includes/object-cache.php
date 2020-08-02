<?php
/**
 * @wordpress-plugin
 * Plugin Name:         Docket Cache Drop-in
 * Plugin URI:          http://wordpress.org/plugins/docket-cache/
 * Version:             20.08.03
 * Description:         A file-based persistent WordPress Object Cache stored as a plain PHP code.
 * Author:              Nawawi Jamili
 * Author URI:          https://profiles.wordpress.org/nawawijamili
 * Requires at least:   5.4
 * Requires PHP:        7.2.5
 * License:             MIT
 * License URI:         https://github.com/nawawi/docket-cache/blob/master/LICENSE.txt
 */
\defined('ABSPATH') || exit;

/*
 * Check if caching is not disabled.
 * If true, prevent functions and classes from being defined.
 * See wp_start_object_cache() -> wp-includes/load.php.
 */
if (\defined('DOCKET_CACHE_DISABLED') && DOCKET_CACHE_DISABLED) {
    return;
}

/*
 * Check for minimum php version.
 * If not match, prevent functions and classes from being defined.
 * See wp_start_object_cache() -> wp-includes/load.php.
 */
if (version_compare(PHP_VERSION, '7.2.5', '<')) {
    return;
}

/*
 * Determine if WP_CONTENT_DIR is exists.
 * Rarely condition, when opcache set to file only and no timestamp checking.
 */
if (!\defined('WP_CONTENT_DIR')) {
    \define('WP_CONTENT_DIR', ABSPATH.'wp-content');
}

/*
 * Determine if WP_PLUGIN_DIR is exists.
 * Rarely condition, when opcache set to file only and no timestamp checking.
 */
if (!\defined('WP_PLUGIN_DIR')) {
    \define('WP_PLUGIN_DIR', WP_CONTENT_DIR.'/plugins');
}

/*
 * Determine if docket object cache class and functions exists.
 * If failed, prevent WP Object Cache functions and classes from being defined.
 * See wp_start_object_cache() -> wp-includes/load.php.
 */
if (!@is_file(WP_PLUGIN_DIR.'/docket-cache/includes/cache.php')) {
    return;
}

/*
 * Determine if docket cache autoload exists.
 * If failed, prevent WP Object Cache functions and classes from being defined.
 * See wp_start_object_cache() -> wp-includes/load.php.
 */
if (!@is_file(WP_PLUGIN_DIR.'/docket-cache/includes/load.php')) {
    return;
}

/*
 * Determine if we can load docket cache library.
 * If failed, prevent WP Object Cache functions and classes from being defined.
 * See wp_start_object_cache() -> wp-includes/load.php.
 */
@include_once WP_PLUGIN_DIR.'/docket-cache/includes/load.php';
if (!class_exists('Nawawi\\DocketCache\\Plugin') || !class_exists('Nawawi\\DocketCache\\Constans')) {
    return;
}

/*
 * Check for object-cache-delay.txt file.
 * If exists, prevent WP Object Cache functions and classes from being defined.
 * See wp_start_object_cache() -> wp-includes/load.php.
 */
if (@is_file(WP_CONTENT_DIR.'/.object-cache-delay.txt')) {
    // rarely condition, use function_exists to confirm function exists to avoid fatal error on certain hosting mostly using apache mod_fcgid
    if (\function_exists('add_action')) {
        if (time() > @filemtime(WP_CONTENT_DIR.'/.object-cache-delay.txt')) {
            if (!\function_exists('__docket_cache_halt_transient')) {
                // prevent from transient save to db before replace with our dropin
                function __docket_cache_halt_transient($value, $option, $old_value = '')
                {
                    if (false !== strpos($option, '_transient_')) {
                        return false;
                    }

                    return $value;
                }

                add_filter('pre_update_option', '__docket_cache_halt_transient', -PHP_INT_MAX, 3);
                add_filter('pre_get_option', '__docket_cache_halt_transient', -PHP_INT_MAX, 3);
                add_filter(
                    'added_option',
                    function ($option, $value) {
                        return __docket_cache_halt_transient($value, $option);
                    },
                    -PHP_INT_MAX,
                    2
                );
            }

            add_action(
                'shutdown',
                function () {
                    // only remove expired transient, dont remove all that will lead to heavy load once replace with our dropin
                    if (\function_exists('delete_expired_transients')) {
                        delete_expired_transients(true);
                    }
                    // previous file format
                    foreach (['object-cache-delay.txt', 'object-cache-after-delay.txt', 'object-cache.log'] as $f) {
                        $fx = WP_CONTENT_DIR.'/'.$f;
                        if (@is_file($fx)) {
                            @unlink($fx);
                        }
                    }

                    // execute action after dropin installed
                    @rename(WP_CONTENT_DIR.'/.object-cache-delay.txt', WP_CONTENT_DIR.'/.object-cache-after-delay.txt');
                },
                PHP_INT_MAX
            );
        }
    }

    return;
}

/*
 * Define WP Object Cache functions and classes.
 * Dont separate functions and classes, issue with opcache, will lead to unknown deactivation on upgrade.
 * See wp_start_object_cache() -> wp-includes/load.php.
 */
@include_once WP_PLUGIN_DIR.'/docket-cache/includes/cache.php';
