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

// for early version, cache use symfony directly, only if symfony class not exists
if (!class_exists('Symfony\\Component\\VarExporter\\Internal\\Hydrator', false) && class_exists('Nawawi\\Symfony\\Component\\VarExporter\\Internal\\Hydrator')) {
    class_alias('Nawawi\Symfony\Component\VarExporter\Internal\Hydrator', 'Symfony\Component\VarExporter\Internal\Hydrator', false);
}
if (!class_exists('Symfony\\Component\\VarExporter\\Internal\\Registry', false) && class_exists('Nawawi\\Symfony\\Component\\VarExporter\\Internal\\Registry')) {
    class_alias('Nawawi\Symfony\Component\VarExporter\Internal\Registry', 'Symfony\Component\VarExporter\Internal\Registry', false);
}

// backward < 20.07.17
if (class_exists('Nawawi\\DocketCache\\Constans')) {
    class_alias('Nawawi\DocketCache\Constans', 'Nawawi\Docket_Cache\Constans', false);
}
if (class_exists('Nawawi\\DocketCache\\Filesystem')) {
    class_alias('Nawawi\DocketCache\Filesystem', 'Nawawi\Docket_Cache\Files', false);
}

// backward < 20.08.04
if (class_exists('Nawawi\\DocketCache\\CachePost')) {
    class_alias('Nawawi\DocketCache\CachePost', 'Nawawi\DocketCache\Advanced_Post', false);
    class_alias('Nawawi\DocketCache\CachePost', 'Nawawi\DocketCache\AdvancedPost', false);
}

// internal classmap
if (class_exists('Nawawi\\Symfony\\Component\\VarExporter\\VarExporter')) {
    class_alias('Nawawi\Symfony\Component\VarExporter\VarExporter', 'Nawawi\DocketCache\Exporter\VarExporter', false);
}
if (class_exists('Nawawi\\Symfony\\Component\\VarExporter\\Internal\\Hydrator')) {
    class_alias('Nawawi\Symfony\Component\VarExporter\Internal\Hydrator', 'Nawawi\DocketCache\Exporter\Hydrator', false);
}
if (class_exists('Nawawi\\Symfony\\Component\\VarExporter\\Internal\\Registry')) {
    class_alias('Nawawi\Symfony\Component\VarExporter\Internal\Registry', 'Nawawi\DocketCache\Exporter\Registry', false);
}
