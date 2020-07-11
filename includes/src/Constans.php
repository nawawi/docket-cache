<?php
/**
 * Docket Cache.
 *
 * @author  Nawawi Jamili
 * @license MIT
 *
 * @see    https://github.com/nawawi/docket-cache
 */

namespace Nawawi\Docket_Cache;

class Constans
{
    public static function init()
    {
        if (!\defined('DOCKET_CACHE_PATH')) {
            \define('DOCKET_CACHE_PATH', WP_CONTENT_DIR.'/cache/docket-cache/');
        }

        if (!\defined('DOCKET_CACHE_MAXTTL') || !\is_int(DOCKET_CACHE_MAXTTL)) {
            \define('DOCKET_CACHE_MAXTTL', 0);
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

        if (!\defined('DOCKET_CACHE_ADVCPOST')) {
            \define('DOCKET_CACHE_ADVCPOST', true);
        }

        if (!\defined('DOCKET_CACHE_FLUSH_DELETE')) {
            \define('DOCKET_CACHE_FLUSH_DELETE', false);
        }

        if (!\defined('DOCKET_CACHE_GLOBAL_GROUPS')) {
            \define(
                'DOCKET_CACHE_GLOBAL_GROUPS',
                [
                    'blog-details',
                    'blog-id-cache',
                    'blog-lookup',
                    'global-posts',
                    'networks',
                    'rss',
                    'sites',
                    'site-details',
                    'site-lookup',
                    'site-options',
                    'site-transient',
                    'users',
                    'useremail',
                    'userlogins',
                    'usermeta',
                    'user_meta',
                    'userslugs',
                ]
            );
        }

        if (!\defined('DOCKET_CACHE_IGNORED_GROUPS')) {
            \define(
                'DOCKET_CACHE_IGNORED_GROUPS',
                [
                    'themes',
                    'counts',
                    'plugins',
                    'user_meta',
                    'comment',
                    'wc_session_id',
                    'bp_notifications',
                    'bp_messages',
                    'bp_pages',
                ]
            );
        }
    }
}
