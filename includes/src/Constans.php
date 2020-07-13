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
    public static function maybe_define($name, $value)
    {
        if (!\defined($name)) {
            return @\define($name, $value);
        }

        return false;
    }

    public static function init()
    {
        // cache dir
        self::maybe_define('DOCKET_CACHE_PATH', WP_CONTENT_DIR.'/cache/docket-cache/');

        // cache maxttl
        self::maybe_define('DOCKET_CACHE_MAXTTL', 0);

        // log on/off
        self::maybe_define('DOCKET_CACHE_LOG', false);

        // log file
        self::maybe_define('DOCKET_CACHE_LOG_FILE', WP_CONTENT_DIR.'/object-cache.log');

        // empty file when cache flushed
        self::maybe_define('DOCKET_CACHE_LOG_FLUSH', true);

        // log file max size
        self::maybe_define('DOCKET_CACHE_LOG_SIZE', 10000000);

        // truncate or delete cache file
        self::maybe_define('DOCKET_CACHE_FLUSH_DELETE', false);

        // garbage collector
        self::maybe_define('DOCKET_CACHE_GC', true);

        // global cache group
        self::maybe_define(
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

        // cache ignored groups
        self::maybe_define(
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

        // cache ignored keys
        self::maybe_define('DOCKET_CACHE_IGNORED_KEYS', []);

        // this will handle conditionally
        self::maybe_define('DOCKET_CACHE_FILTERED_GROUPS', true);

        // preload
        self::maybe_define('DOCKET_CACHE_PRELOAD', false);

        // misc tweaks
        self::maybe_define('DOCKET_CACHE_MISC_TWEAKS', true);
        self::maybe_define('DOCKET_CACHE_ADVCPOST', true);

        // backwards-compatible
        self::maybe_define('DOCKET_CACHE_DEBUG', DOCKET_CACHE_LOG);
        self::maybe_define('DOCKET_CACHE_DEBUG_FILE', DOCKET_CACHE_LOG_FILE);
        self::maybe_define('DOCKET_CACHE_DEBUG_FLUSH', DOCKET_CACHE_LOG_FLUSH);
        self::maybe_define('DOCKET_CACHE_DEBUG_SIZE', DOCKET_CACHE_LOG_SIZE);
    }
}