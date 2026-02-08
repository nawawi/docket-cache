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
require_once __DIR__.'/vendor/autoload.php';
if (@is_file(__DIR__.'/src/Crawler.php')) {
    require_once __DIR__.'/src/Crawler.php';
}
require_once __DIR__.'/compat.php';
