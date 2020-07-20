<?php
/**
 * @wordpress-plugin
 * Plugin Name:         Docket Cache Drop-in
 * Plugin URI:          http://wordpress.org/plugins/docket-cache/
 * Version:             20.07.20
 * Description:         A file-based persistent WordPress Object Cache stored as a plain PHP code.
 * Author:              Nawawi Jamili
 * Author URI:          https://rutweb.com
 * Requires at least:   5.4
 * Requires PHP:        7.2.5
 * License:             MIT
 * License URI:         https://opensource.org/licenses/MIT
 */
\defined('ABSPATH') || exit;

/*
 * Check if caching is not disabled.
 * If false, prevent functions and classes from being defined.
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
if (@is_file(WP_CONTENT_DIR.'/object-cache-delay.txt')) {
    if (time() > @filemtime(WP_CONTENT_DIR.'/object-cache-delay.txt')) {
        @unlink(WP_CONTENT_DIR.'/object-cache-delay.txt');
    }

    return;
}

/*
 * Define WP Object Cache functions and classes.
 * See wp_start_object_cache() -> wp-includes/load.php.
 */
@include_once WP_PLUGIN_DIR.'/docket-cache/includes/cache.php';
