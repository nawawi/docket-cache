<?php
/**
 * @wordpress-plugin
 * Plugin Name:         Docket Cache Drop-in
 * Plugin URI:          http://wordpress.org/plugins/docket-cache/
 * Version:             20.09.03
 * Description:         A persistent object cache stored as a plain PHP code, accelerates caching with OPcache backend.
 * Author:              Nawawi Jamili
 * Author URI:          https://docketcache.com
 * Requires at least:   5.4
 * Requires PHP:        7.2.5
 * License:             MIT
 * License URI:         https://github.com/nawawi/docket-cache/blob/master/LICENSE.txt
 */
if (!\defined('ABSPATH')) {
    return;
}

/*
 * Check if doing action.
 */
if (!empty($_GET['_wpnonce']) && !empty($_GET['action']) && !empty($_GET['page']) && 'docket-cache' === $_GET['page']) {
    return;
}

/*
 * Check if caching is not disabled.
 */
if (\defined('DOCKET_CACHE_DISABLED') && DOCKET_CACHE_DISABLED) {
    return;
}

/*
 * Check for minimum php version.
 */
if (version_compare(PHP_VERSION, '7.2.5', '<')) {
    return;
}

/*
 * Determine if WP_CONTENT_DIR is exists.
 */
if (!\defined('WP_CONTENT_DIR')) {
    \define('WP_CONTENT_DIR', ABSPATH.'wp-content');
}

/*
 * Determine if WP_PLUGIN_DIR is exists.
 */
if (!\defined('WP_PLUGIN_DIR')) {
    \define('WP_PLUGIN_DIR', WP_CONTENT_DIR.'/plugins');
}

/*
 * Determine if docket object cache class and functions exists.
 */
if (!@is_file(WP_PLUGIN_DIR.'/docket-cache/includes/cache.php')) {
    return;
}

/*
 * Determine if docket cache autoload exists.
 */
if (!@is_file(WP_PLUGIN_DIR.'/docket-cache/includes/load.php')) {
    return;
}

/*
 * Determine if we can load docket cache library.
 */
@include_once WP_PLUGIN_DIR.'/docket-cache/includes/load.php';
if (!class_exists('Nawawi\\DocketCache\\Plugin') || !class_exists('Nawawi\\DocketCache\\Constans') || !class_exists('Nawawi\\DocketCache\\Filesystem') || !\function_exists('nwdcx_constfx')) {
    return;
}

/*
 * Check if doing flush.
 */
if (@is_file(WP_CONTENT_DIR.'/.object-cache-flush.txt')) {
    if (time() > @filemtime(WP_CONTENT_DIR.'/.object-cache-flush.txt')) {
        @unlink(WP_CONTENT_DIR.'/.object-cache-flush.txt');
    }

    return;
}

/*
 * Determine if we're on multinetwork and has object cache locking.
 */
if (\function_exists('nwdcx_network_ignore') && nwdcx_network_ignore()) {
    return;
}

/*
 * Check for object-cache-delay.txt file.
 */
if (@is_file(WP_CONTENT_DIR.'/.object-cache-delay.txt')) {
    if (!\function_exists('add_action')) {
        return;
    }

    if (time() > @filemtime(WP_CONTENT_DIR.'/.object-cache-delay.txt')) {
        if (!\function_exists('__dc_halt_transient')) {
            // prevent from transient save to db before replace with our dropin
            function __dc_halt_transient($value, $option, $old_value = '')
            {
                if (false !== strpos($option, '_transient_')) {
                    return false;
                }

                return $value;
            }

            add_filter('pre_update_option', '__dc_halt_transient', -PHP_INT_MAX, 3);
            add_filter('pre_get_option', '__dc_halt_transient', -PHP_INT_MAX, 3);
            add_filter(
                'added_option',
                function ($option, $value) {
                    return __dc_halt_transient($value, $option);
                },
                -PHP_INT_MAX,
                2
            );
        }

        add_action(
            'shutdown',
            function () {
                if (\function_exists('nwdcx_deltransdb')) {
                    nwdcx_deltransdb();
                }

                // previous file format
                foreach (['object-cache-delay.txt', 'object-cache-after-delay.txt', 'object-cache.log'] as $f) {
                    $fx = WP_CONTENT_DIR.'/'.$f;
                    if (@is_file($fx)) {
                        @unlink($fx);
                    }
                }

                @rename(WP_CONTENT_DIR.'/.object-cache-delay.txt', WP_CONTENT_DIR.'/.object-cache-after-delay.txt');
            },
            PHP_INT_MAX
        );
    }

    return;
}

/*
 * Define WP Object Cache functions and classes.
 */
@include_once WP_PLUGIN_DIR.'/docket-cache/includes/cache.php';
