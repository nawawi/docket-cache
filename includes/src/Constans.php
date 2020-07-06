<?php

namespace Nawawi\Docket_Cache;

class Constans
{
    public static function init()
    {
        if (!\defined('DOCKET_CACHE_PATH')) {
            \define('DOCKET_CACHE_PATH', WP_CONTENT_DIR.'/cache/docket-cache/');
        }

        if (!\defined('DOCKET_CACHE_MAXTTL') || !\is_int(DOCKET_CACHE_MAXTTL)) {
            \define('DOCKET_CACHE_MAXTTL', 86400);
        }

        if (!\defined('DOCKET_CACHE_DEBUG')) {
            \define('DOCKET_CACHE_DEBUG', false);
        }

        if (!\defined('DOCKET_CACHE_DEBUG_FILE')) {
            \define('DOCKET_CACHE_DEBUG_FILE', WP_CONTENT_DIR.'/object-cache.log');
        }

        if (!\defined('DOCKET_CACHE_DEBUG_FLUSH')) {
            \define('DOCKET_CACHE_DEBUG_FLUSH', true);
        }

        if (!\defined('DOCKET_CACHE_DEBUG_SIZE')) {
            \define('DOCKET_CACHE_DEBUG_SIZE', 10000000);
        }
    }
}
