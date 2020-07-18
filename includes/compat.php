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

// 15072020
if (!class_exists('Symfony\\Component\\VarExporter\\Internal\\Hydrator')) {
    class_alias('Nawawi\Symfony\Component\VarExporter\Internal\Hydrator', 'Symfony\Component\VarExporter\Internal\Hydrator');
}

if (!class_exists('Symfony\\Component\\VarExporter\\Internal\\Registry')) {
    class_alias('Nawawi\Symfony\Component\VarExporter\Internal\Registry', 'Symfony\Component\VarExporter\Internal\Registry');
}

// backward < 20.07.17
if (!class_exists('Nawawi\\Docket_Cache\\Constans')) {
    class_alias('Nawawi\DocketCache\Constans', 'Nawawi\Docket_Cache\Constans');
}

// backward < 20.07.17
if (!class_exists('Nawawi\\Docket_Cache\\Files')) {
    class_alias('Nawawi\DocketCache\Filesystem', 'Nawawi\Docket_Cache\Files');
}

// backward < 20.07.19
if (!class_exists('Nawawi\\DocketCache\\Advanced_Post')) {
    class_alias('Nawawi\DocketCache\AdvancedPost', 'Nawawi\DocketCache\Advanced_Post');
}

// internal classmap
if (class_exists('Nawawi\\Symfony\\Component\\VarExporter\\VarExporter')) {
    class_alias('Nawawi\Symfony\Component\VarExporter\VarExporter', 'Nawawi\DocketCache\Exporter\VarExporter');
}
if (class_exists('Nawawi\\Symfony\\Component\\VarExporter\\Internal\\Hydrator')) {
    class_alias('Nawawi\Symfony\Component\VarExporter\Internal\Hydrator', 'Nawawi\DocketCache\Exporter\Hydrator');
}
if (class_exists('Nawawi\\Symfony\\Component\\VarExporter\\Internal\\Registry')) {
    class_alias('Nawawi\Symfony\Component\VarExporter\Internal\Registry', 'Nawawi\DocketCache\Exporter\Registry');
}
