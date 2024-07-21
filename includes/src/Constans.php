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
    public function __construct()
    {
        $this->register_default();
    }

    public function px($name)
    {
        return nwdcx_constfx($name);
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

    public function is_dctrue($name, $reload = false)
    {
        $key = $this->px($name);
        if ($reload) {
            $this->maybe_define($key, false, true);
        }

        return $this->is_true($key);
    }

    public function is_dcfalse($name, $reload = false)
    {
        $key = $this->px($name);
        if ($reload) {
            $this->maybe_define($key, false, true);
        }

        return $this->is_false($key);
    }

    public function is_dcarray($name, &$value = '', $reload = false)
    {
        $key = $this->px($name);
        if ($reload) {
            $this->maybe_define($key, false, true);
        }

        $value = '';
        if ($this->is_array($key)) {
            $value = $this->value($key);

            return true;
        }

        return false;
    }

    public function is_dcint($name, &$value = '', $reload = false)
    {
        $key = $this->px($name);
        if ($reload) {
            $this->maybe_define($key, false, true);
        }

        $value = '';
        if ($this->is_int($key)) {
            $value = $this->value($key);

            return true;
        }

        return false;
    }

    public function dcvalue($name, $reload = false)
    {
        $key = $this->px($name);
        if ($reload) {
            $this->maybe_define($key, '', true);
        }

        return $this->value($key);
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

        // mark defined constants
        if (empty($GLOBALS['DOCKET_CACHE_RUNTIME'])) {
            $GLOBALS['DOCKET_CACHE_RUNTIME'] = [];
        }
        $GLOBAL['DOCKET_CACHE_RUNTIME'][$this->px($name.'_FALSE')] = 1;

        return false;
    }

    public function register_default()
    {
        // compat
        $this->maybe_define('WP_CONTENT_DIR', nwdcx_normalizepath(ABSPATH).'wp-content', false);
        $this->maybe_define('WP_PLUGIN_DIR', nwdcx_normalizepath(WP_CONTENT_DIR).'/plugins', false);
        $this->maybe_define('DOCKET_CACHE_CONTENT_PATH', nwdcx_normalizepath(WP_CONTENT_DIR), false);

        // data dir
        $this->maybe_define($this->px('DATA_PATH'), DOCKET_CACHE_CONTENT_PATH.'/docket-cache-data/', false);

        // cache dir
        $this->maybe_define($this->px('PATH'), DOCKET_CACHE_CONTENT_PATH.'/cache/docket-cache/', false);

        // object max size: 3MB, 1MB = 1048576 bytes (binary) = 1000000 bytes (decimal)
        // Only numbers between 1000000 and 10485760 are accepted
        $this->maybe_define($this->px('MAXSIZE'), 3145728);

        // cache file max size total: 500MB, 1MB = 1048576 bytes (binary) = 1000000 bytes (decimal)
        // minimum 100MB
        $this->maybe_define($this->px('MAXSIZE_DISK'), 524288000);

        // cache file max accelerated files: Only numbers between 200 and 200000 are accepted
        $this->maybe_define($this->px('MAXFILE'), 50000);

        // check cache file limit in real-time
        $this->maybe_define($this->px('MAXFILE_LIVECHECK'), false);

        // cache maxttl: cache lifespan.  Only seconds between 86400 and 2419200 are accepted
        $this->maybe_define($this->px('MAXTTL'), 345600); // 4d

        // log on/off
        $this->maybe_define($this->px('LOG'), false);

        // log all on/off
        $this->maybe_define($this->px('LOG_ALL'), \defined('WP_DEBUG') ? WP_DEBUG : false);

        // log file
        $this->maybe_define($this->px('LOG_FILE'), DOCKET_CACHE_CONTENT_PATH.'/.object-cache.log');

        // empty file when cache flushed
        $this->maybe_define($this->px('LOG_FLUSH'), true);

        // log time format: utc, local
        $this->maybe_define($this->px('LOG_TIME'), 'utc');

        // log file max size: 10MB, 1MB = 1048576 bytes (binary) = 1000000 bytes (decimal)
        $this->maybe_define($this->px('LOG_SIZE'), 10485760);

        // truncate or delete cache file
        $this->maybe_define($this->px('FLUSH_DELETE'), false);

        // flush cache when deactivate/uninstall
        $this->maybe_define($this->px('FLUSH_SHUTDOWN'), true);

        // flush wc_cache / advanced post cache / wp stale cache
        $this->maybe_define($this->px('FLUSH_STALECACHE'), false);

        // split a cache file into smaller directory
        $this->maybe_define($this->px('CHUNKCACHEDIR'), false);

        // ignore stale cache
        $this->maybe_define($this->px('STALECACHE_IGNORE'), false);

        // ignore empty cache
        $this->maybe_define($this->px('EMPTYCACHE_IGNORE'), false);

        // optimize db
        $this->maybe_define($this->px('CRONOPTMZDB'), 'never');

        // option autoload
        $this->maybe_define($this->px('WPOPTALOAD'), false);

        // global cache group for multisite
        $this->maybe_define(
            $this->px('GLOBAL_GROUPS'),
            [
                'blog-details',
                'blog-id-cache',
                'blog-lookup',
                'global-posts',
                'networks',
                'network-queries',
                'rss',
                'sites',
                'site-details',
                'site-lookup',
                'site-options',
                'site-queries',
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
            $this->px('IGNORED_GROUPS'),
            [
                'themes',
                'counts',
                'plugins',
            ]
        );

        // @private
        // cache ignored keys
        // @note: dnh_dismissed_notices -> https://github.com/julien731/WP-Dismissible-Notices-Handler
        $this->maybe_define($this->px('IGNORED_KEYS'), ['dnh_dismissed_notices']);

        // @private
        // cache ignored group => key, group => [key1, key2]
        $this->maybe_define($this->px('IGNORED_GROUPKEY'), []);

        // @private
        // this option private for right now
        $this->maybe_define(
            $this->px('FILTERED_GROUPS'),
            [
                'counts' => [
                    'posts-page',
                    'posts-post',
                ],
            ]
        );

        // precache
        $this->maybe_define($this->px('PRECACHE'), false);

        // precache maxfile
        $this->maybe_define($this->px('PRECACHE_MAXFILE'), 100);

        // precache max key
        $this->maybe_define($this->px('PRECACHE_MAXKEY'), 20);

        // precache max group
        $this->maybe_define($this->px('PRECACHE_MAXGROUP'), 20);

        // @private
        // cache ignored precache
        $this->maybe_define(
            $this->px('IGNORED_PRECACHE'),
            [
                'freemius' => 'fs_accounts',
                'options' => [
                    'uninstall_plugins',
                    'auto_update_plugins',
                    'active_plugins',
                    'cron',
                    'litespeed_messages',
                    'litespeed.admin_display.messages',
                ],
                'site-options' => [
                    '1:auto_update_plugins',
                    '1:active_sitewide_plugins',
                ],
            ]
        );

        // transient in DB.
        $this->maybe_define($this->px('TRANSIENTDB'), false);

        // exclude transient in DB.
        $this->maybe_define($this->px('IGNORED_TRANSIENTDB'),
            [
                'doing_cron',
                'update_plugins',
                'update_themes',
                'update_core',
            ]
        );

        // misc tweaks
        $this->maybe_define($this->px('MISC_TWEAKS'), true);

        // woocommerce tweaks
        $this->maybe_define($this->px('WOOTWEAKS'), true);

        // woocommerce admin
        $this->maybe_define($this->px('WOOADMINOFF'), false);

        // woocommerce dashboard status meta box
        $this->maybe_define($this->px('WOOWPDASHBOARDOFF'), false);

        // woocommerce widget
        $this->maybe_define($this->px('WOOWIDGETOFF'), false);

        // woocommerce cart fragments
        $this->maybe_define($this->px('WOOCARTFRAGSOFF'), false);

        // woocommerce robots crawling add-to-cart links
        $this->maybe_define($this->px('WOOADDTOCHARTCRAWLING'), true);

        // woocommerce marketplace / my subscription menu page
        $this->maybe_define($this->px('WOOEXTENSIONPAGEOFF'), false);

        // post missed schedule
        $this->maybe_define($this->px('POSTMISSEDSCHEDULE'), false);

        // optimize term count
        $this->maybe_define($this->px('OPTERMCOUNT'), true);

        // translation mo file cache
        $this->maybe_define($this->px('MOCACHE'), false);

        // menu cache
        $this->maybe_define($this->px('MENUCACHE'), false);

        // menu cache ttl: 1209600 = 14 days
        $this->maybe_define($this->px('MENUCACHE_TTL'), 1209600);

        // @private
        // wp-cli
        $this->maybe_define($this->px('WPCLI'), \defined('WP_CLI') && WP_CLI);

        // banner
        $this->maybe_define($this->px('SIGNATURE'), true);

        // preload
        $this->maybe_define($this->px('PRELOAD'), false);

        // page loader
        $this->maybe_define($this->px('PAGELOADER'), true);

        // docket cronbot
        $this->maybe_define($this->px('CRONBOT'), true);

        // docket cronbot
        $this->maybe_define($this->px('CRONBOT_MAX'), 10);

        // opcviewer
        $this->maybe_define($this->px('OPCVIEWER'), false);

        // opcviewer show all
        $this->maybe_define($this->px('OPCVIEWER_SHOWALL'), false);

        // cache stats
        $this->maybe_define($this->px('STATS'), true);

        // gc action button
        $this->maybe_define($this->px('GCACTION'), false);

        // additional flush cache button
        $this->maybe_define($this->px('FLUSHACTION'), false);

        // check version
        $this->maybe_define($this->px('CHECKVERSION'), false);

        // / @private: auto update
        // 28012023: DOCKET_CACHE_AUTOUPDATE only to force WP auto_update_plugin filter.
        //           DOCKET_CACHE_AUTOUPDATE_TOGGLE will sync with WP auto_update_plugins option.
        // $this->maybe_define($this->px('AUTOUPDATE'), false);
        $this->maybe_define($this->px('AUTOUPDATE_TOGGLE'), false);

        // flush opcache when deactivate
        $this->maybe_define($this->px('OPCSHUTDOWN'), false);

        // optimize post query
        $this->maybe_define($this->px('OPTWPQUERY'), true);

        // limit bulk edit
        $this->maybe_define($this->px('LIMITBULKEDIT'), false);

        // limit bulk edit bulk limit
        $this->maybe_define($this->px('LIMITBULKEDIT_LIMIT'), 100);

        // xmlrpc pingbacks
        $this->maybe_define($this->px('PINGBACK'), true);

        // header junk
        $this->maybe_define($this->px('HEADERJUNK'), true);

        // wp emoji
        $this->maybe_define($this->px('WPEMOJI'), false);

        // wp embed
        $this->maybe_define($this->px('WPEMBED'), false);

        // wp feed
        $this->maybe_define($this->px('WPFEED'), false);

        // wp lazyload
        $this->maybe_define($this->px('WPLAZYLOAD'), false);

        // wp sitemap
        $this->maybe_define($this->px('WPSITEMAP'), false);

        // wp sitemap
        $this->maybe_define($this->px('WPDASHBOARDNEWS'), false);

        // wp application password: wp >= 5.6
        $this->maybe_define($this->px('WPAPPPASSWORD'), false);

        // limit http request from uncommon page.
        $this->maybe_define($this->px('LIMITHTTPREQUEST'), false);

        // whitelist host from limit http request.
        $this->maybe_define($this->px('LIMITHTTPREQUEST_WHITELIST'), []/* ['feeds.feedburner.com'] */);

        // wp browse-happy
        $this->maybe_define($this->px('WPBROWSEHAPPY'), false);

        // wp serve-happy
        $this->maybe_define($this->px('WPSERVEHAPPY'), false);

        // post vis email
        $this->maybe_define($this->px('POSTVIAEMAIL'), false);

        // cache http response from wp_remote_request.
        $this->maybe_define($this->px('CACHEHTTPRESPONSE'), false);

        // cache http response ttl: 300 = 5 minutes.
        $this->maybe_define($this->px('CACHEHTTPRESPONS_TTL'), 300);

        // cache http include list, if empty any url will include.
        $this->maybe_define($this->px('CACHEHTTPRESPONS_INCLUDE'), []);

        // cache http exclude list.
        $this->maybe_define($this->px('CACHEHTTPRESPONS_EXCLUDE'), []);

        // @compat: wp version < 6.1 || < 5.8
        if (isset($GLOBALS['wp_version'])) {
            if (version_compare($GLOBALS['wp_version'], '6.1', '<')) {
                // advanced post cache
                $this->maybe_define($this->px('ADVCPOST'), false);

                // advanced post cache allow post type
                $this->maybe_define(
                    $this->px('ADVCPOST_POSTTYPE'),
                    [
                        'post',
                        'page',
                        'attachment',
                        'revision',
                        'nav_menu_item',
                        'custom_css',
                        'customize_changeset',
                        'oembed_cache',
                        'user_request',
                        'wp_block',
                        'wp_template',
                        'wp_template_part',
                        'wp_global_styles',
                        'wp_navigation',
                    ]
                );

                // advanced post cache allow all post type
                $this->maybe_define($this->px('ADVCPOST_POSTTYPE_ALL'), false);
            }

            if (version_compare($GLOBALS['wp_version'], '5.8', '<')) {
                // curl "Expect" header performance tweak
                $this->maybe_define($this->px('HTTPHEADERSEXPECT'), false);
            }
        }

        // @private: auto save interval.
        $this->maybe_define($this->px('RTPOSTAUTOSAVE'), 1);

        // @private: post revision.
        $this->maybe_define($this->px('RTPOSTREVISION'), 'on');

        // @private: empty trash.
        $this->maybe_define($this->px('RTPOSTEMPTYTRASH'), 30);

        // @private: plugin / theme editor.
        $this->maybe_define($this->px('RTPLUGINTHEMEEDITOR'), \defined('DISALLOW_FILE_EDIT') && DISALLOW_FILE_EDIT ? 'on' : 'off');

        // @private: plugin / theme install.
        $this->maybe_define($this->px('RTPLUGINTHEMEINSTALL'), \defined('DISALLOW_FILE_MODS') && DISALLOW_FILE_MODS ? 'on' : 'off');

        // @private: overwrite image after edit.
        $this->maybe_define($this->px('RTIMAGEOVERWRITE'), \defined('IMAGE_EDIT_OVERWRITE') && IMAGE_EDIT_OVERWRITE ? 'on' : 'off');

        // @private: wp debug.
        $this->maybe_define($this->px('RTWPDEBUG'), \defined('WP_DEBUG') && WP_DEBUG ? 'on' : 'off');

        // @private: wp debug display.
        $this->maybe_define($this->px('RTWPDEBUGDISPLAY'), \defined('WP_DEBUG_DISPLAY') && WP_DEBUG_DISPLAY ? 'on' : 'off');

        // @private: wp debug log.
        $this->maybe_define($this->px('RTWPDEBUGLOG'), \defined('WP_DEBUG_LOG') && WP_DEBUG_LOG ? 'on' : 'off');

        // @private: deactivate wp auto update core.
        $this->maybe_define($this->px('RTWPCOREUPDATE'), \defined('WP_AUTO_UPDATE_CORE') && WP_AUTO_UPDATE_CORE ? 'off' : 'on');

        // @private: deactivate concatenate wp-admin scripts.
        $this->maybe_define($this->px('RTCONCATENATESCRIPTS'), \defined('CONCATENATE_SCRIPTS') && !(bool) CONCATENATE_SCRIPTS ? 'on' : 'off');

        // @private: deactivate wp cron.
        $this->maybe_define($this->px('RTDISABLEWPCRON'), \defined('DISABLE_WP_CRON') && DISABLE_WP_CRON ? 'on' : 'off');

        // @private
        // capture fatal error rarely incase non-throwable
        // set true for debugging only
        $this->maybe_define($this->px('CAPTURE_FATALERROR'), false);
    }
}
