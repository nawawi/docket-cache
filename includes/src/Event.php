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

    public function __construct(Plugin $pt)
    {
        $this->pt = $pt;
        $this->is_optimizedb = false;
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
            PHP_INT_MAX
        );

        add_action(
            'plugins_loaded',
            function () {
                // 19092020: standardize. rename hooks
                foreach (['docket_cache_gc', 'docket_cache_optimizedb', 'docket_cache_monitor'] as $hx) {
                    if (false !== wp_get_scheduled_event($hx)) {
                        wp_clear_scheduled_hook($hx);
                    }
                }

                // gc: always enable
                add_action('docketcache_gc', [$this, 'garbage_collector']);
                if (!wp_next_scheduled('docketcache_gc')) {
                    wp_schedule_event(time(), 'docketcache_gc_schedule', 'docketcache_gc');
                }

                // monitor: always enable
                add_action('docketcache_watchproc', [$this, 'watchproc']);
                if (!wp_next_scheduled('docketcache_watchproc')) {
                    wp_schedule_event(time(), 'hourly', 'docketcache_watchproc');
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
                if ($this->pt->cf()->is_dctrue('CHECKVERSION')) {
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
     * reset,.
     */
    public function reset()
    {
        $this->unregister();
        $this->register();
    }

    /**
     * monitor.
     */
    public function watchproc()
    {
        if ($this->pt->co()->lockproc('watchproc', time() + 3600)) {
            return false;
        }

        if (!$this->is_optimizedb) {
            $this->delete_expired_transients_db();
        }

        $this->clear_unknown_cron();

        $this->pt->get_cache_stats(true);
        $this->pt->co()->lockreset('watchproc');

        return true;
    }

    /**
     * garbage_collector.
     */
    public function garbage_collector($force = false)
    {
        $maxfileo = (int) $this->pt->get_cache_maxfile();
        $maxfile = $maxfileo;

        if ($maxfileo > 10000) {
            $maxfile = $maxfileo - 1000;
        }

        $maxfileo_pre = (int) $this->pt->get_precache_maxfile();
        $maxfile_pre = $maxfileo_pre;

        if ($maxfileo_pre > 10000) {
            $maxfile_pre = $maxfileo_pre - 1000;
        }

        $maxttl0 = (int) $this->pt->get_cache_maxttl();
        $maxttl = $maxttl0;
        if (!empty($maxttl)) {
            $maxttl = time() - $maxttl;
        }

        $chkmaxdisk = false;
        $maxsizedisk0 = (int) $this->pt->get_cache_maxsize_disk();
        $maxsizedisk = $maxsizedisk0;
        if (!empty($maxsizedisk)) {
            $maxsizedisk = $maxsizedisk - 1048576;

            if ($maxsizedisk > 1048576) {
                $chkmaxdisk = true;
            }
        }

        $collect = (object) [
            'cache_maxttl' => $maxttl0,
            'cache_maxfile' => $maxfileo,
            'cache_maxdisk' => $maxsizedisk0,
            'cleanup_maxfile' => 0,
            'cleanup_precache_maxfile' => 0,
            'cleanup_maxttl' => 0,
            'cleanup_maxdisk' => 0,
            'cache_file' => 0,
            'cache_cleanup' => 0,
            'cache_ignore' => 0,
            'cleanup_failed' => 0,
        ];

        if ($this->pt->co()->lockproc('garbage_collector', time() + 3600)) {
            return $collect;
        }

        wp_suspend_cache_addition(true);

        $delay = $force ? 650 : 1000;
        if ($this->pt->is_docketcachedir($this->pt->cache_path)) {
            clearstatcache();
            $fsizetotal = 0;
            $cnt = 0;
            $pcnt = 0;
            $slowdown = 0;
            foreach ($this->pt->scanfiles($this->pt->cache_path) as $object) {
                try {
                    $fx = $object->getPathName();

                    if (!$object->isFile() || 'file' !== $object->getType() || !$this->pt->is_php($fx)) {
                        ++$collect->cache_ignore;
                        continue;
                    }

                    $fn = $object->getFileName();
                    $fs = $object->getSize();
                    $fm = time() + 300;
                    $ft = filemtime($fx);
                } catch (\Throwable $e) {
                    nwdcx_throwable(__METHOD__, $e);
                    continue;
                }

                if ($fm >= $ft && (0 === $fs || 'dump_' === substr($fn, 0, 5))) {
                    $this->pt->unlink($fx, true);

                    if ($force && @is_file($fx)) {
                        ++$collect->cleanup_failed;
                    }

                    usleep(100);
                    continue;
                }

                // 032e9f2c5b60- = docketcache-precache-
                if ($maxfile_pre > 0 && '032e9f2c5b60-' === substr($fn, 0, 13)) {
                    ++$pcnt;

                    if ($pcnt > $maxfile_pre) {
                        $this->pt->unlink($fx, true);

                        if ($force && @is_file($fx)) {
                            ++$collect->cleanup_failed;
                        }

                        ++$collect->cleanup_precache_maxfile;

                        usleep(100);
                        continue;
                    }
                }

                if ($cnt >= $maxfile) {
                    $this->pt->unlink($fx, true);

                    if ($force && @is_file($fx)) {
                        ++$collect->cleanup_failed;
                    }

                    ++$collect->cleanup_maxfile;

                    usleep(100);
                    continue;
                }

                $fsizetotal += $fs;
                if ($chkmaxdisk && $fsizetotal > $maxsizedisk) {
                    $this->pt->unlink($fx, true);

                    if ($force && @is_file($fx)) {
                        ++$collect->cleanup_failed;
                    }

                    ++$collect->cleanup_maxdisk;

                    usleep(100);
                    continue;
                }

                $data = $this->pt->cache_get($fx);
                $is_timeout = false;
                if (false !== $data) {
                    $is_timeout = !empty($data['timeout']) && $this->pt->valid_timestamp($data['timeout']) ? true : false;

                    if ($is_timeout) {
                        if ($fm >= (int) $data['timeout']) {
                            $this->pt->unlink($fx, true);

                            if ($force && @is_file($fx)) {
                                ++$collect->cleanup_failed;
                            }

                            unset($data);

                            ++$collect->cleanup_maxttl;

                            usleep(100);
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

                            usleep(100);
                            continue;
                        }
                    }
                }
                unset($data);

                // no timeout data or 0
                if (false === $is_timeout && $maxttl > 0 && $maxttl > $ft) {
                    $this->pt->unlink($fx, true);

                    if ($force && @is_file($fx)) {
                        ++$collect->cleanup_failed;
                    }

                    ++$collect->cleanup_maxttl;

                    usleep(100);
                    continue;
                }

                ++$cnt;
                ++$collect->cache_file;

                if ($slowdown > 10) {
                    $slowdown = 0;
                    usleep($delay);
                }

                ++$slowdown;
            }

            $collect->cache_cleanup = $collect->cleanup_maxttl + $collect->cleanup_maxfile + $collect->cleanup_maxdisk + $collect->cleanup_precache_maxfile;
        }

        wp_suspend_cache_addition(false);

        $this->pt->co()->lockreset('garbage_collector');
        $this->pt->cx()->delay_expire();

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
        $this->delete_expired_transients_db();

        if (is_main_site() && is_main_network()) {
            $dbname = $wpdb->dbname;
            $tables = $wpdb->get_results('SHOW TABLES FROM '.$dbname, ARRAY_A);
            if (!empty($tables) && \is_array($tables)) {
                foreach ($tables as $table) {
                    $tbl = $table['Tables_in_'.$dbname];
                    $wpdb->query('OPTIMIZE TABLE `'.$tbl.'`');
                }
            }
            unset($tables);
        }

        $wpdb->suppress_errors($suppress);

        return true;
    }

    /**
     * delete_expired_transients_db.
     */
    public function delete_expired_transients_db()
    {
        if (!wp_using_ext_object_cache()) {
            return false;
        }

        if (!nwdcx_wpdb($wpdb)) {
            return false;
        }

        if (\function_exists('nwdcx_cleanuptransient')) {
            nwdcx_cleanuptransient();
        } elseif (\function_exists('delete_expired_transients')) {
            delete_expired_transients(true);
        }

        return true;
    }

    /**
     * clear_unknown_cron.
     */
    public function clear_unknown_cron()
    {
        if (!wp_using_ext_object_cache()) {
            return;
        }

        if (!\function_exists('_get_cron_array')) {
            return;
        }
        $crons = _get_cron_array();
        if (!empty($crons) && \is_array($crons)) {
            foreach ($crons as $time => $cron) {
                foreach ($cron as $hook => $dings) {
                    if (!has_action($hook)) {
                        wp_clear_scheduled_hook($hook);
                    }
                }
            }
        }
        unset($crons);
    }

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
            if (JSON_ERROR_NONE === json_last_error()) {
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

        return true;
    }
}
