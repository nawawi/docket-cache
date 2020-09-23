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
        if (@preg_match_all('@O:\d+:"([^"]+)"@', $data, $mm)) {
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
