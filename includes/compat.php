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

if (class_exists('Nawawi\\Symfony\\Component\\VarExporter\\VarExporter')) {
    class_alias('Nawawi\Symfony\Component\VarExporter\VarExporter', 'Nawawi\DocketCache\Exporter\VarExporter', false);
}
if (class_exists('Nawawi\\Symfony\\Component\\VarExporter\\Internal\\Hydrator')) {
    class_alias('Nawawi\Symfony\Component\VarExporter\Internal\Hydrator', 'Nawawi\DocketCache\Exporter\Hydrator', false);
}
if (class_exists('Nawawi\\Symfony\\Component\\VarExporter\\Internal\\Registry')) {
    class_alias('Nawawi\Symfony\Component\VarExporter\Internal\Registry', 'Nawawi\DocketCache\Exporter\Registry', false);
}

if (!\function_exists('nawawi_arraymap')) {
    function nawawi_arraymap($func, $arr)
    {
        $new = [];
        foreach ($arr as $key => $value) {
            $new[$key] = (\is_array($value) ? nawawi_arraymap($func, $value) : (\is_array($func) ? \call_user_func_array($func, $value) : $func($value)));
        }

        return $new;
    }
}

if (!\function_exists('nawawi_unserialize')) {
    function nawawi_unserialize($data)
    {
        if (!\function_exists('is_serialized')) {
            @include_once ABSPATH.WPINC.'/functions.php';
        }

        if (!\function_exists('is_serialized') || !is_serialized($data)) {
            return $data;
        }

        $ok = true;
        if (false !== strpos($data, 'O:') && @preg_match_all('@O:\d+:"([^"]+)"@', $data, $mm)) {
            if (!empty($mm) && !empty($mm[1])) {
                foreach ($mm[1] as $v) {
                    if ('stdClass' !== $v) {
                        $ok = false;
                        break;
                    }
                }
                unset($mm);
            }
        }

        return !$ok ? $data : @unserialize(trim($data));
    }
}

if (!\function_exists('nawawi_delete_transient_db')) {
    function nawawi_delete_transient_db()
    {
        if (!wp_using_ext_object_cache()) {
            return false;
        }

        if (!isset($GLOBALS['wpdb']) || !$GLOBALS['wpdb']->ready) {
            return false;
        }

        $wpdb = $GLOBALS['wpdb'];

        $suppress = $wpdb->suppress_errors(true);

        // normal setup
        $wpdb->query(
            $wpdb->prepare('DELETE FROM `'.$wpdb->options.'` WHERE `option_name` LIKE %s', $wpdb->esc_like('_transient_').'%')
        );

        // single site
        $wpdb->query(
            $wpdb->prepare('DELETE FROM `'.$wpdb->options.'` WHERE `option_name` LIKE %s', $wpdb->esc_like('_site_transient_').'%')
        );

        // multisite
        if (is_multisite() && isset($wpdb->sitemeta)) {
            $wpdb->query(
                $wpdb->prepare('DELETE FROM `'.$wpdb->sitemeta.'` WHERE `meta_key` LIKE %s', $wpdb->esc_like('_site_transient_').'%')
            );
        }

        $wpdb->suppress_errors($suppress);

        return true;
    }
}
