<?php
/**
 * Docket Cache.
 *
 * @author  Nawawi Jamili
 * @license MIT
 *
 * @see    https://github.com/nawawi/docket-cache
 */

namespace Nawawi\DocketCache;

\defined('ABSPATH') || exit;

final class Constans
{
    private static $inst;

    public function __construct()
    {
        $this->register_default();
    }

    public static function init()
    {
        if (!isset(self::$inst)) {
            self::$inst = new self();
        }

        return self::$inst;
    }

    public function is_false($name)
    {
        return !\defined($name) || !\constant($name);
    }

    public function is_true($name)
    {
        return \defined($name) && \constant($name);
    }

    public function value($name)
    {
        $value = '';
        if (\defined($name)) {
            $value = \constant($name);
        }

        return $value;
    }

    public function is_array($name)
    {
        $value = $this->value($name);

        return !empty($value) && \is_array($value);
    }

    public function is_int($name)
    {
        $value = $this->value($name);

        return !empty($value) && \is_int($value);
    }

    public function is($name, $value)
    {
        return \defined($name) && $value === \constant($name);
    }

    public function maybe_define($name, $value, $user_config = true)
    {
        if (!\defined($name)) {
            if ($user_config && class_exists('Nawawi\\DocketCache\\Canopt')) {
                $nv = Canopt::init()->get($name);
                if (!empty($nv) && 'default' !== $nv) {
                    switch ($nv) {
                        case 'enable':
                            $nv = true;
                            break;
                        case 'disable':
                            $nv = false;
                            break;
                    }

                    $value = $nv;
                }
            }

            return @\define($name, $value);
        }

        return false;
    }

    public function register_default()
    {
        // compat
        $this->maybe_define('WP_CONTENT_DIR', ABSPATH.'wp-content', false);
        $this->maybe_define('WP_PLUGIN_DIR', WP_CONTENT_DIR.'/plugins', false);

        // data dir
        $this->maybe_define('DOCKET_CACHE_DATA_PATH', WP_CONTENT_DIR.'/docket-cache-data/', false);

        // cache dir
        $this->maybe_define('DOCKET_CACHE_PATH', WP_CONTENT_DIR.'/cache/docket-cache/', false);

        // cache file max size: 3MB, 1MB = 1048576 bytes (binary) = 1000000 bytes (decimal)
        $this->maybe_define('DOCKET_CACHE_MAXSIZE', 3145728);

        // cache maxttl: 0
        $this->maybe_define('DOCKET_CACHE_MAXTTL', 0);

        // log on/off
        $this->maybe_define('DOCKET_CACHE_LOG', false);

        // private: log on/off
        $this->maybe_define('DOCKET_CACHE_LOG_ALL', (\defined('WP_DEBUG') ? WP_DEBUG : false));

        // log file
        $this->maybe_define('DOCKET_CACHE_LOG_FILE', WP_CONTENT_DIR.'/.object-cache.log');

        // empty file when cache flushed
        $this->maybe_define('DOCKET_CACHE_LOG_FLUSH', true);

        // log time format: utc, local, wp
        $this->maybe_define('DOCKET_CACHE_LOG_TIME', 'utc');

        // log file max size: 10MB, 1MB = 1048576 bytes (binary) = 1000000 bytes (decimal)
        $this->maybe_define('DOCKET_CACHE_LOG_SIZE', 10485760);

        // truncate or delete cache file
        $this->maybe_define('DOCKET_CACHE_FLUSH_DELETE', false);

        // garbage collector
        $this->maybe_define('DOCKET_CACHE_GC', true);

        // optimize db
        $this->maybe_define('DOCKET_CACHE_CRONOPTMZDB', 'never');

        // option autoload
        $this->maybe_define('DOCKET_CACHE_WPOPTALOAD', false);

        // global cache group
        $this->maybe_define(
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
        $this->maybe_define(
            'DOCKET_CACHE_IGNORED_GROUPS',
            [
                'themes',
                'counts',
                'plugins',
            ]
        );

        // @private
        // cache ignored keys
        $this->maybe_define('DOCKET_CACHE_IGNORED_KEYS', []);

        // @private
        // this option private for right now
        $this->maybe_define(
            'DOCKET_CACHE_FILTERED_GROUPS',
            [
                'counts' => [
                    'posts-page',
                    'posts-post',
                ],
            ]
        );

        // misc tweaks
        $this->maybe_define('DOCKET_CACHE_MISC_TWEAKS', true);

        // woocommerce tweaks
        $this->maybe_define('DOCKET_CACHE_WOOTWEAKS', true);

        // post missed schedule
        $this->maybe_define('DOCKET_CACHE_POSTMISSEDSCHEDULE', false);

        // advanced post cache
        $this->maybe_define('DOCKET_CACHE_ADVCPOST', true);

        // optimize term count
        $this->maybe_define('DOCKET_CACHE_OPTERMCOUNT', true);

        // translation mo file cache
        $this->maybe_define('DOCKET_CACHE_MOCACHE', false);

        // @private
        // wp-cli
        $this->maybe_define('DOCKET_CACHE_WPCLI', (\defined('WP_CLI') && WP_CLI));

        // banner
        $this->maybe_define('DOCKET_CACHE_SIGNATURE', true);

        // preload
        $this->maybe_define('DOCKET_CACHE_PRELOAD', false);

        // precache
        $this->maybe_define('DOCKET_CACHE_PRECACHE', true);

        // page loader
        $this->maybe_define('DOCKET_CACHE_PAGELOADER', true);

        // docket cronbot
        $this->maybe_define('DOCKET_CACHE_CRONBOT', false);

        // config page
        $this->maybe_define('DOCKET_CACHE_PAGECONFIG', true);

        // cache stats
        $this->maybe_define('DOCKET_CACHE_STATS', true);

        // backwards-compatible
        $this->maybe_define('DOCKET_CACHE_COMMENT', DOCKET_CACHE_SIGNATURE);
    }
}
