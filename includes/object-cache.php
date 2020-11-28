<?php
/**
 * @wordpress-plugin
 * Plugin Name:         Docket Cache Drop-in
 * Plugin URI:          https://wordpress.org/plugins/docket-cache/
 * Version:             20.10.09
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
if (!empty($_GET['_wpnonce']) && !empty($_GET['action']) && !empty($_GET['page']) && 'docket-cache' === $_GET['page'] && false === strpos($_GET['action'], 'cronbot') && false === strpos($_GET['action'], 'wpoptaload')) {
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
 * Determine if DOCKET_CACHE_CONTENT_PATH is exists.
 */
if (!\defined('DOCKET_CACHE_CONTENT_PATH')) {
    \define('DOCKET_CACHE_CONTENT_PATH', WP_CONTENT_DIR);
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
if (@is_file(DOCKET_CACHE_CONTENT_PATH.'/.object-cache-flush.txt')) {
    if (time() > @filemtime(DOCKET_CACHE_CONTENT_PATH.'/.object-cache-flush.txt')) {
        @unlink(DOCKET_CACHE_CONTENT_PATH.'/.object-cache-flush.txt');
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
if (@is_file(DOCKET_CACHE_CONTENT_PATH.'/.object-cache-delay.txt')) {
    if (!\function_exists('add_action')) {
        return;
    }

    if (time() > @filemtime(DOCKET_CACHE_CONTENT_PATH.'/.object-cache-delay.txt')) {
        if (!\function_exists('nwdcx_halttransient')) {
            // prevent from transient save to db before replace with our dropin
            function nwdcx_halttransient($value, $option, $old_value = '')
            {
                if (false !== strpos($option, '_transient_')) {
                    return false;
                }

                return $value;
            }

            add_filter('pre_update_option', 'nwdcx_halttransient', -PHP_INT_MAX, 3);
            add_filter('pre_get_option', 'nwdcx_halttransient', -PHP_INT_MAX, 3);
            add_filter(
                'added_option',
                function ($option, $value) {
                    return nwdcx_halttransient($value, $option);
                },
                -PHP_INT_MAX,
                2
            );
        }

        if (!\function_exists('nwdcx_cleanuptransient')) {
            function nwdcx_cleanuptransient()
            {
                if (!nwdcx_wpdb($wpdb)) {
                    return false;
                }

                $suppress = $wpdb->suppress_errors(true);

                $collect = [];

                $results = $wpdb->get_results('SELECT `option_id`,`option_name`,`option_value` FROM `'.$wpdb->options.'` WHERE `option_name` LIKE "_transient_%" OR `option_name` LIKE "_site_transient_%"', ARRAY_A);

                if (!empty($results) && \is_array($results)) {
                    while ($row = @array_shift($results)) {
                        $id = $row['option_id'];
                        $collect[$id] = $id;

                        if (false !== strpos($row['option_name'], '_transient_timeout_') && (int) $row['option_value'] > time()) {
                            unset($collect[$id]);
                        }
                    }

                    if (!empty($collect)) {
                        foreach ($collect as $id) {
                            if ((int) $id > 0) {
                                $wpdb->query("DELETE FROM `{$wpdb->options}` WHERE `option_id`='{$id}'");
                            }
                        }
                    }
                }

                $collect = [];

                if (is_multisite() && isset($wpdb->sitemeta)) {
                    $results = $wpdb->get_results('SELECT `meta_id`,`meta_key`,`meta_value` FROM `'.$wpdb->sitemeta.'` WHERE `meta_key` LIKE "_site_transient_%"', ARRAY_A);
                    if (!empty($results) && \is_array($results)) {
                        while ($row = @array_shift($results)) {
                            $id = $row['meta_id'];
                            $collect[$id] = $id;

                            if (false !== strpos($row['meta_key'], '_site_transient_timeout_') && (int) $row['meta_value'] > time()) {
                                unset($collect[$id]);
                            }
                        }

                        if (!empty($collect)) {
                            foreach ($collect as $id) {
                                if ((int) $id > 0) {
                                    $wpdb->query("DELETE FROM `{$wpdb->sitemeta}` WHERE `meta_id`='{$id}'");
                                }
                            }
                        }
                    }
                }

                unset($collect, $results);
                $wpdb->suppress_errors($suppress);

                return true;
            }

            add_action(
                'shutdown',
                function () {
                    nwdcx_cleanuptransient();
                },
                PHP_INT_MAX - 1
            );
        }

        add_action(
            'shutdown',
            function () {
                @rename(DOCKET_CACHE_CONTENT_PATH.'/.object-cache-delay.txt', DOCKET_CACHE_CONTENT_PATH.'/.object-cache-after-delay.txt');
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
