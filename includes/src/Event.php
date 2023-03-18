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

final class Event
{
    private $pt;
    private $is_optimizedb;
    private $max_execution_time = 0;
    private $wp_start_timestamp = 0;

    public function __construct(Plugin $pt)
    {
        $this->pt = $pt;
        $this->is_optimizedb = false;
        $this->wp_start_timestamp = \defined('WP_START_TIMESTAMP') ? WP_START_TIMESTAMP : microtime(true);
        $this->max_execution_time = $this->pt->get_max_execution_time();
    }

    /**
     * register.
     */
    public function register()
    {
        // global
        add_filter('docketcache/filter/garbagecollector', [$this, 'garbage_collector']);

        add_filter(
            'cron_schedules',
            function ($schedules) {
                $schedules['halfhour'] = [
                    'interval' => 30 * MINUTE_IN_SECONDS,
                    'display' => esc_html__('Every 30 Minutes', 'docket-cache'),
                ];

                if (empty($schedules['hourly'])) {
                    $schedules['hourly'] = [
                        'interval' => HOUR_IN_SECONDS,
                        'display' => esc_html__('Once Hourly', 'docket-cache'),
                    ];
                }

                if (empty($schedules['monthly'])) {
                    $schedules['monthly'] = [
                        'interval' => MONTH_IN_SECONDS,
                        'display' => esc_html__('Once Monthly', 'docket-cache'),
                    ];
                }

                $schedules['docketcache_gc_schedule'] = [
                    'interval' => 5 * MINUTE_IN_SECONDS,
                    'display' => esc_html__('Every 5 Minutes', 'docket-cache'),
                ];

                $schedules['docketcache_checkversion_schedule'] = [
                    'interval' => 15 * DAY_IN_SECONDS,
                    'display' => esc_html__('Every 15 Days', 'docket-cache'),
                ];

                return $schedules;
            },
            \PHP_INT_MAX
        );

        add_action(
            'plugins_loaded',
            function () {
                // 19092020: standardize. rename hooks.
                // 19012023: remove docketcache_watchproc.
                foreach (['docket_cache_gc', 'docket_cache_optimizedb', 'docket_cache_monitor', 'docketcache_watchproc'] as $hx) {
                    if (false !== wp_get_scheduled_event($hx)) {
                        wp_clear_scheduled_hook($hx);
                    }
                }

                // garbage collector
                // 27012023: added disable constant
                if ($this->pt->cf()->is_dcfalse('GCRON_DISABLED')) {
                    add_action('docketcache_gc', [$this, 'garbage_collector']);
                    if (!wp_next_scheduled('docketcache_gc')) {
                        wp_schedule_event(time(), 'docketcache_gc_schedule', 'docketcache_gc');
                    }
                } else {
                    if (wp_get_schedule('docketcache_gc')) {
                        wp_clear_scheduled_hook('docketcache_gc');
                    }
                }

                // optimize db
                $cronoptmzdb = $this->pt->cf()->dcvalue('CRONOPTMZDB');
                if (!empty($cronoptmzdb) && 'never' !== $cronoptmzdb && is_main_site()) {
                    $recurrence = '';
                    switch ($cronoptmzdb) {
                        case 'daily':
                            $recurrence = 'daily';
                            break;
                        case 'weekly':
                            $recurrence = 'weekly';
                            break;
                        case 'monthly':
                            $recurrence = 'monthly';
                            break;
                    }

                    if (empty($recurrence)) {
                        wp_clear_scheduled_hook('docketcache_optimizedb');
                    } else {
                        $this->is_optimizedb = true;
                        add_action('docketcache_optimizedb', [$this, 'optimizedb']);

                        if (!wp_next_scheduled('docketcache_optimizedb')) {
                            wp_schedule_event(time(), $recurrence, 'docketcache_optimizedb');
                        }
                    }
                } else {
                    if (wp_get_schedule('docketcache_optimizedb')) {
                        wp_clear_scheduled_hook('docketcache_optimizedb');
                    }
                }

                // check version
                if ($this->pt->cf()->is_dctrue('CHECKVERSION', true)) {
                    // 06102020: reset old schedule
                    $check = wp_get_scheduled_event('docketcache_checkversion');
                    if (\is_object($check) && 'docketcache_checkversion_schedule' !== $check->schedule) {
                        wp_clear_scheduled_hook('docketcache_checkversion');
                    }

                    if (is_main_site() && is_main_network()) {
                        add_action('docketcache_checkversion', [$this, 'checkversion']);
                        if (!wp_next_scheduled('docketcache_checkversion')) {
                            wp_schedule_event(time(), 'docketcache_checkversion_schedule', 'docketcache_checkversion');
                        }
                    }
                } else {
                    if (wp_get_schedule('docketcache_checkversion')) {
                        wp_clear_scheduled_hook('docketcache_checkversion');
                    }
                }

                // expired transient in DB
                if (has_action('delete_expired_transients') && wp_using_ext_object_cache()) {
                    add_action('delete_expired_transients', [$this, 'delete_expired_transients_db']);
                }
            }
        );
    }

    /**
     * unregister.
     */
    public function unregister()
    {
        foreach (['docketcache_gc', 'docketcache_optimizedb', 'docketcache_watchproc', 'docketcache_checkversion'] as $hx) {
            wp_clear_scheduled_hook($hx);
        }
    }

    /**
     * reset.
     */
    public function reset()
    {
        $this->unregister();
        $this->register();
    }

    /**
     * garbage_collector.
     */
    public function garbage_collector($force = false)
    {
        static $is_done = false;

        $maxfile_default = (int) $this->pt->get_cache_maxfile();
        $maxfile = $maxfile_default;

        if ($maxfile_default > 10000) {
            $maxfile = $maxfile_default - 1000;
        }

        $maxfile_precache_default = (int) $this->pt->get_precache_maxfile();
        $maxfile_precache = $maxfile_precache_default;

        if ($maxfile_precache_default > 10000) {
            $maxfile_precache = $maxfile_precache_default - 1000;
        }

        $maxttl_default = (int) $this->pt->get_cache_maxttl();
        $maxttl = $maxttl_default;
        if (!empty($maxttl)) {
            $maxttl = time() - $maxttl;
        }

        $chkmaxdisk = false;
        $maxsizedisk_default = (int) $this->pt->get_cache_maxsize_disk();
        $maxsizedisk = $maxsizedisk_default;
        if (!empty($maxsizedisk)) {
            $maxsizedisk = $maxsizedisk - 1048576;

            if ($maxsizedisk > 1048576) {
                $chkmaxdisk = true;
            }
        }

        $collect = (object) [
            'is_locked' => false,
            'cache_maxttl' => $maxttl_default,
            'cache_maxfile' => $maxfile_default,
            'cache_maxdisk' => $maxsizedisk_default,
            'cleanup_maxfile' => 0,
            'cleanup_precache_maxfile' => 0,
            'cleanup_maxttl' => 0,
            'cleanup_expire' => 0,
            'cleanup_maxdisk' => 0,
            'cache_file' => 0,
            'cache_cleanup' => 0,
            'cache_ignore' => 0,
            'cleanup_failed' => 0,
            'cleanup_stalecache' => 0,
        ];

        clearstatcache();
        if (!$this->pt->is_docketcachedir($this->pt->cache_path) || @is_file(DOCKET_CACHE_CONTENT_PATH.'/.object-cache-flush.txt')) {
            $collect->is_locked = true;

            return $collect;
        }

        // try to set max execution time to 3 minutes if not 0 or lower than 180 seconds.
        $max_execution_time = $this->pt->get_max_execution_time(180);

        // lock process.
        $lock_expiry = $max_execution_time > 0 ? $max_execution_time : 180;
        $lock_expiry = time() + $lock_expiry;
        if ($is_done || $this->pt->co()->lockproc('garbage_collector', $lock_expiry)) {
            $collect->is_locked = true;

            return $collect;
        }

        // Stalecache
        $is_flush_stalecache = $this->pt->cf()->is_dctrue('FLUSH_STALECACHE', true);
        $is_ignore_stalecache = $this->pt->cf()->is_dctrue('STALECACHE_IGNORE', true);

        $wp_cache_last_changed = [];
        $wp_cache_last_changed_match = [
            'posts' => 'wp_query',
            'terms' => 'get_terms',
            'comment' => 'get_comments',
            'sites' => 'get_sites',
            'networks' => 'get_network_ids',
        ];
        foreach ($wp_cache_last_changed_match as $grp => $kk) {
            $wp_cache_last_changed[$grp] = wp_cache_get_last_changed($grp);
            // wp >= 6.3
            // remove ending 's' -> posts = post-queries.
            $grpq = strtok($grp, 's').'-queries';
            $wp_cache_last_changed[$grpq] = $wp_cache_last_changed[$grp];
        }

        $wp_cache_last_changed['advpost'] = wp_cache_get('cache_incr', 'docketcache-post');
        $wc_has_cache_helper = method_exists('WC_Cache_Helper', 'get_cache_prefix');
        $wc_session_cache_group = \defined('WC_SESSION_CACHE_GROUP') ? WC_SESSION_CACHE_GROUP : 'wc_session_id';

        $delay = $force ? 650 : 5000;
        if ('cli' === \PHP_SAPI) {
            $delay = 100;
        }

        // hold cache write
        $this->pt->suspend_cache_write(true);

        $filesize_total = 0;
        $file_cache_count = 0;
        $file_precache_count = 0;
        $bytes_total = 0;
        $slowdown = 0;

        $gcisrun_lock = $this->pt->cache_path.'/.gc-is-run.txt';
        $this->pt->touch($gcisrun_lock);

        foreach ($this->pt->scanfiles($this->pt->cache_path) as $object) {
            if ($max_execution_time > 0 && (microtime(true) - $this->wp_start_timestamp) > $max_execution_time) {
                break;
            }

            if ($slowdown > 10) {
                $slowdown = 0;
                usleep($delay);
            }

            ++$slowdown;

            try {
                if (!$object->isFile()) {
                    ++$collect->cache_ignore;
                    continue;
                }

                $fx = $object->getPathName();
                $fn = $object->getFileName();
                $fs = $object->getSize();
                $fm = time() + 300;
                $ft = filemtime($fx);

                $this->pt->remove_non_chunk_cache($this->pt->cache_path, $fx);
            } catch (\Throwable $e) {
                nwdcx_throwable(__METHOD__, $e);
                continue;
            }

            nwdcx_cliverbose('run-gc: '.$fx."\n");

            if ($fm >= $ft && (0 === $fs || 'dump_' === substr($fn, 0, 5))) {
                $this->pt->unlink($fx, true);

                if ($force && @is_file($fx)) {
                    ++$collect->cleanup_failed;
                }
                continue;
            }

            // 03022023: timeout 0 was set to maxtll, see WP_Object_Cache::maybe_expire
            // cleanup first to reduce memory usage
            if ($maxttl > 0 && $maxttl > $ft) {
                $this->pt->unlink($fx, true);

                if ($force && @is_file($fx)) {
                    ++$collect->cleanup_failed;
                }

                ++$collect->cleanup_maxttl;
                continue;
            }

            // 032e9f2c5b60- = docketcache-precache-
            if ($maxfile_precache > 0 && '032e9f2c5b60-' === substr($fn, 0, 13)) {
                ++$file_precache_count;

                if ($file_precache_count > $maxfile_precache) {
                    $this->pt->unlink($fx, true);

                    if ($force && @is_file($fx)) {
                        ++$collect->cleanup_failed;
                    }

                    ++$collect->cleanup_precache_maxfile;
                    continue;
                }
            }

            if ($file_cache_count >= $maxfile) {
                $this->pt->unlink($fx, true);

                if ($force && @is_file($fx)) {
                    ++$collect->cleanup_failed;
                }

                ++$collect->cleanup_maxfile;
                continue;
            }

            if ($chkmaxdisk && $filesize_total > $maxsizedisk) {
                $this->pt->unlink($fx, true);

                if ($force && @is_file($fx)) {
                    ++$collect->cleanup_failed;
                }

                ++$collect->cleanup_maxdisk;
                continue;
            }

            $data = $this->pt->cache_get($fx);
            $is_timeout = false;
            if (false !== $data) {
                unset($data['data']);

                $is_timeout = !empty($data['timeout']) && $this->pt->valid_timestamp($data['timeout']) ? true : false;
                if ($is_timeout) {
                    if ($fm >= (int) $data['timeout']) {
                        $this->pt->unlink($fx, true);

                        if ($force && @is_file($fx)) {
                            ++$collect->cleanup_failed;
                        }

                        unset($data);
                        ++$collect->cleanup_expire;
                        continue;
                    }
                } else {
                    if (!empty($data['timestamp']) && $this->pt->valid_timestamp($data['timestamp']) && $maxttl > $data['timestamp']) {
                        $this->pt->unlink($fx, true);

                        if ($force && @is_file($fx)) {
                            ++$collect->cleanup_failed;
                        }

                        unset($data);
                        ++$collect->cleanup_maxttl;
                        continue;
                    }
                }

                if ($is_flush_stalecache) {
                    // wp stale cache
                    // wp >= 6.3
                    if ($is_ignore_stalecache && $this->is_wp_cache_group_queries($data['group'])) {
                        if (@unlink($fx)) {
                            clearstatcache(true, $fx);

                            nwdcx_cliverbose('run-gc:stale-cache: '.$fx."\n");

                            ++$collect->cleanup_stalecache;
                            continue;
                        }
                    }

                    // wp stale cache
                    // prefix:hash:timestamp timestamp
                    if (!empty($wp_cache_last_changed_match[$data['group']]) && preg_match('@^(wp_query|get_terms|get_comments|comment_feed|get_sites|get_network_ids|get_page_by_path|adjacent_post|wp_get_archives):([0-9a-f]{32}):([0-9\. ]{21})([0-9\. ]+)?$@', $data['key'], $mm)) {
                        // main type
                        switch ($mm[1]) {
                            case 'get_page_by_path':
                            case 'adjacent_post':
                                $mm[1] = 'wp_query';
                                break;
                            case 'comment_feed':
                                $mm[1] = 'get_comments';
                                break;
                        }

                        $km = $wp_cache_last_changed_match[$data['group']];

                        if (($km === $mm[1] && $wp_cache_last_changed[$data['group']] !== $mm[3]) || $is_ignore_stalecache) {
                            if (@unlink($fx)) {
                                clearstatcache(true, $fx);

                                nwdcx_cliverbose('run-gc:stale-cache: '.$fx."\n");

                                ++$collect->cleanup_stalecache;
                                continue;
                            }
                        }
                    }

                    // wp stale cache
                    // get_comment_child_ids:int:hash:timestamp timestamp
                    // get_comment_child_ids:1654:5247020d1a40e3b2e9a40de1139bc5c9:0.48761900 1678553206
                    if (!empty($wp_cache_last_changed_match[$data['group']]) && preg_match('@^(get_comment_child_ids):(\d+):([0-9a-f]{32}):([0-9\. ]{21})([0-9\. ]+)?$@', $data['key'], $mm)) {
                        $mm[1] = 'get_comments';
                        $km = $wp_cache_last_changed_match[$data['group']];

                        if (($km === $mm[1] && $wp_cache_last_changed[$data['group']] !== $mm[4]) || $is_ignore_stalecache) {
                            if (@unlink($fx)) {
                                clearstatcache(true, $fx);

                                nwdcx_cliverbose('run-gc:stale-cache: '.$fx."\n");

                                ++$collect->cleanup_stalecache;
                                continue;
                            }
                        }
                    }

                    // advpost stale cache
                    if (false !== strpos($data['group'], 'docketcache-post-') && preg_match('@^docketcache-post-(\d+)$@', $data['group'], $mm)) {
                        if ((int) $wp_cache_last_changed['advpost'] !== (int) $mm[1] || $is_ignore_stalecache) {
                            if (@unlink($fx)) {
                                clearstatcache(true, $fx);

                                nwdcx_cliverbose('run-gc:stale-cache: '.$fx."\n");

                                ++$collect->cleanup_stalecache;
                                continue;
                            }
                        }
                    }

                    // wc stale cache
                    if (false !== strpos($data['key'], 'wc_cache_') && preg_match('@^(wc_cache_[0-9\. ]+_)@', $data['key'], $mm)) {
                        if (!$wc_has_cache_helper || $is_ignore_stalecache) {
                            if (@unlink($fx)) {
                                clearstatcache(true, $fx);

                                nwdcx_cliverbose('run-gc:stale-cache: '.$fx."\n");

                                ++$collect->cleanup_stalecache;
                            }
                            continue;
                        }

                        $current_prefix = $mm[1];
                        static $cache_prefix_cached = [];

                        // wc product
                        if ('products' === $data['group'] && preg_match('@.*?_type_(\d+)$@', $data['key'], $nn)) {
                            $grp = 'product_'.$nn[1];

                            if (!empty($cache_prefix_cached[$grp])) {
                                $cache_prefix = $cache_prefix_cached;
                            } else {
                                $cache_prefix = \WC_Cache_Helper::get_cache_prefix($grp);
                                $cache_prefix_cached[$grp] = $cache_prefix;
                            }

                            if ($cache_prefix !== $current_prefix) {
                                if (@unlink($fx)) {
                                    clearstatcache(true, $fx);

                                    nwdcx_cliverbose('run-gc:stale-cache: '.$fx."\n");

                                    ++$collect->cleanup_stalecache;
                                    continue;
                                }
                            }
                        }

                        if (\in_array($data['group'],
                            [
                                'products', 'coupons', 'orders', 'webhooks', 'taxes', 'shipping_zones',
                                'woocommerce-attributes', 'store_api_rate_limit', $wc_session_cache_group,
                            ])) {
                            $grp = $data['group'];

                            if (!empty($cache_prefix_cached[$grp])) {
                                $cache_prefix = $cache_prefix_cached;
                            } else {
                                $cache_prefix = \WC_Cache_Helper::get_cache_prefix($grp);
                                $cache_prefix_cached[$grp] = $cache_prefix;
                            }

                            if ($cache_prefix !== $current_prefix) {
                                if (@unlink($fx)) {
                                    clearstatcache(true, $fx);

                                    nwdcx_cliverbose('run-gc:stale-cache: '.$fx."\n");

                                    ++$collect->cleanup_stalecache;
                                    continue;
                                }
                            }
                        }

                        if ('wc_rate_limit' === $data['group'] && 'rate_limit' === substr($data['key'], 0, 10)) {
                            $grp = $data['group'];

                            if (!empty($cache_prefix_cached[$grp])) {
                                $cache_prefix = $cache_prefix_cached;
                            } else {
                                $cache_prefix = \WC_Cache_Helper::get_cache_prefix($grp);
                                $cache_prefix_cached[$grp] = $cache_prefix;
                            }

                            if ($cache_prefix !== $current_prefix) {
                                if (@unlink($fx)) {
                                    clearstatcache(true, $fx);

                                    nwdcx_cliverbose('run-gc:stale-cache: '.$fx."\n");

                                    ++$collect->cleanup_stalecache;
                                    continue;
                                }
                            }
                        }
                    }
                }
                $bytes_total += \strlen(serialize($data));
            } // data

            // no timeout data or 0
            // 03022023: timeout 0 was set to maxtll, see WP_Object_Cache::maybe_expire
            /*if (false === $is_timeout && $maxttl > 0 && $maxttl > $ft) {
                $this->pt->unlink($fx, true);

                if ($force && @is_file($fx)) {
                    ++$collect->cleanup_failed;
                }

                ++$collect->cleanup_maxttl;
                continue;
            }*/

            unset($data);

            $filesize_total += $fs;
            ++$file_cache_count;
        } // foreach1

        @unlink($gcisrun_lock);

        $collect->cache_file = $file_cache_count;

        $collect->cache_cleanup = $collect->cleanup_maxttl + $collect->cleanup_expire + $collect->cleanup_maxfile + $collect->cleanup_maxdisk + $collect->cleanup_precache_maxfile + $collect->cleanup_stalecache;

        // release
        $this->pt->suspend_cache_write(false);

        if ($this->pt->cf()->is_dcfalse('TRANSIENTDB') && \function_exists('nwdcx_cleanuptransient')) {
            nwdcx_cliverbose("run-gc: cleanup expired transients in DB\n");
            nwdcx_cleanuptransient();
        }

        // reset gc
        $count_file = $collect->cache_file;
        $count_file = $count_file < 0 ? 0 : $count_file;
        wp_cache_set('count_file', $count_file, 'docketcache-gc', 86400);

        // reset precache
        $count_file = $file_precache_count - $collect->cleanup_precache_maxfile;
        $count_file = $count_file < 0 ? 0 : $count_file;
        wp_cache_set('count_file', $count_file, 'docketcache-precache-gc', 86400);

        // stats
        $this->pt->co()->save_part([
            'timestamp' => time(),
            'size' => $bytes_total,
            'filesize' => $filesize_total,
            'files' => $collect->cache_file,
        ], 'cachestats');

        // done
        $this->pt->co()->lockreset('garbage_collector');
        $this->pt->cx()->delay_expire();
        $is_done = true;

        return $collect;
    }

    /**
     * optimizedb.
     */
    public function optimizedb()
    {
        if (!nwdcx_wpdb($wpdb)) {
            return false;
        }

        if ($this->pt->co()->lockproc('optimizedb', time() + 3600)) {
            return false;
        }

        $suppress = $wpdb->suppress_errors(true);

        @set_time_limit(300);
        $max_execution_time = $this->pt->get_max_execution_time();
        $this->delete_expired_transients_db();

        if (is_main_site() && is_main_network()) {
            $dbname = $wpdb->dbname;
            $tables = $wpdb->get_results('SHOW TABLES FROM '.$dbname, ARRAY_A);
            if (!empty($tables) && \is_array($tables)) {
                foreach ($tables as $table) {
                    $tbl = $table['Tables_in_'.$dbname];
                    $sql = 'OPTIMIZE TABLE `'.$tbl.'`';
                    $ret = $wpdb->query($sql);

                    nwdcx_cliverbose(str_replace('`', '', $sql)."\n");
                    if ($max_execution_time > 0 && (microtime(true) - $this->wp_start_timestamp) > $max_execution_time) {
                        break;
                    }
                }
            }
            unset($tables);
        }

        $wpdb->suppress_errors($suppress);

        $this->pt->co()->lockreset('optimizedb');

        return true;
    }

    /**
     * delete_expired_transients_db.
     */
    public function delete_expired_transients_db()
    {
        if (!nwdcx_wpdb($wpdb)) {
            return false;
        }

        if ($this->pt->cf()->is_dcfalse('TRANSIENTDB') && \function_exists('nwdcx_cleanuptransient')) {
            nwdcx_cleanuptransient();
        } elseif (\function_exists('delete_expired_transients')) {
            delete_expired_transients(true);
        }

        return true;
    }

    /**
     * checkversion.
     */
    public function checkversion()
    {
        if (!is_main_site()) {
            return false;
        }

        $part = 'checkversion';

        if ($this->pt->co()->lockproc($part, time() + 3600)) {
            return false;
        }

        $checkdata = $this->pt->co()->get_part($part, true);
        if (!empty($checkdata) && \is_array($checkdata) && !empty($checkdata['selfcheck'])) {
            $selfcheck = $checkdata['selfcheck'];
            if (0 === $this->pt->sanitize_timestamp($selfcheck)) {
                return false;
            }

            if ($selfcheck > 0 && $selfcheck > time()) {
                return false;
            }
        }

        $main_site_url = $this->pt->site_url();
        $site_url = $this->pt->site_url(true);
        $home_url = $this->pt->site_url(true, true);
        $stmp = time() + 120;
        $api_endpoint = $this->pt->api_endpoint.'/'.$part.'?v='.$stmp;

        $args = [
            'blocking' => true,
            'body' => [
                'timestamp' => date('Y-m-d H:i:s T'),
                'timezone' => wp_timezone_string(),
                'site' => $site_url,
                'token' => $this->pt->nw_encrypt($main_site_url, md5($site_url)),
                'meta' => $this->pt->site_meta(),
            ],
            'headers' => [
                'REFERER' => $home_url,
                'Cache-Control' => 'no-cache',
            ],
        ];

        $results = Crawler::post($api_endpoint, $args);

        $output = [
            'timestamp' => time(),
            'endpoint' => $api_endpoint,
            'request' => [
                'headers' => $args['headers'],
                'content' => $args['body'],
            ],
            'selfcheck' => time() + 86400,
        ];

        if (is_wp_error($results)) {
            $output['error'] = $results->get_error_message();
            $this->pt->co()->save_part($output, $part);

            return false;
        }

        $output['response'] = wp_remote_retrieve_body($results);
        if (!empty($output['response'])) {
            $output['response'] = json_decode($output['response'], true);
            if (\JSON_ERROR_NONE === json_last_error()) {
                if (!empty($output['response']['error'])) {
                    $output['error'] = $output['response']['error'];
                    $this->pt->co()->save_part($output, $part);

                    return false;
                }
            }
        }

        $code = (int) wp_remote_retrieve_response_code($results);
        if ($code > 400) {
            $output['error'] = $code;
            $this->pt->co()->save_part($output, $part);

            return false;
        }

        $this->pt->co()->save_part($output, $part);
        $this->pt->co()->lockreset($part);

        return true;
    }
}
