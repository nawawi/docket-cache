<?php
/**
 * Docket Cache.
 *
 * @author  Nawawi Jamili
 * @license MIT
 *
 * @see    https://github.com/nawawi/docket-cache
 */
\defined('ABSPATH') || exit;

/*
 * Check for object-cache-delay.txt file.
 * If exists, prevent WP Object Cache functions and classes from being defined.
 * See wp_start_object_cache() -> wp-includes/load.php.
 */
$object_cache_delay = (object) [
    'before' => WP_CONTENT_DIR.'/.object-cache-delay.txt',
    'after' => WP_CONTENT_DIR.'/.object-cache-after-delay.txt',
];

if (@is_file($object_cache_delay->before)) {
    if (time() < @filemtime($object_cache_delay->before)) {
        return;
    }

    // check function if exists to avoid fatal error on certain hosting
    if (\function_exists('add_action')) {
        if (!\function_exists('__docket_cache_halt_transient')) {
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
            function () use ($object_cache_delay) {
                // double check
                if (\function_exists('delete_expired_transients') && (isset($GLOBALS['wpdb']) && $GLOBALS['wpdb']->ready)) {
                    delete_expired_transients(true);
                }
                @rename($object_cache_delay->before, $object_cache_delay->after);
            },
            PHP_INT_MAX
        );
    }

    return;
}
unset($object_cache_delay);

include_once __DIR__.'/cache-funcs.php';
include_once __DIR__.'/cache-class.php';
