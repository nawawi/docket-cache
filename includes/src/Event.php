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
    private $plugin;
    private $is_optimizedb;

    public function __construct(Plugin $plugin)
    {
        $this->plugin = $plugin;
        $this->is_optimizedb = false;
    }

    /**
     * register.
     */
    public function register()
    {
        // global
        add_filter('docketcache/garbage-collector', [$this, 'garbage_collector']);

        add_filter(
            'cron_schedules',
            function ($schedules) {
                $schedules = [
                    'halfhour' => [
                        'interval' => 30 * MINUTE_IN_SECONDS,
                        'display' => esc_html__('Every 30 Minutes', 'docket-cache'),
                    ],
                    'monthly' => [
                        'interval' => MONTH_IN_SECONDS,
                        'display' => esc_html__('Once Monthly', 'docket-cache'),
                    ],
                    'docketcache_gc_schedule' => [
                        'interval' => 5 * MINUTE_IN_SECONDS,
                        'display' => esc_html__('Every 5 Minutes', 'docket-cache'),
                    ],
                    'docketcache_checkversion_schedule' => [
                        'interval' => 3 * DAY_IN_SECONDS,
                        'display' => esc_html__('Every 3 Days', 'docket-cache'),
                    ],
                ];

                return $schedules;
            }
        );

        add_action(
            'plugin_loaded',
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
                $cronoptmzdb = $this->plugin->constans()->value('DOCKET_CACHE_CRONOPTMZDB');
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
                        wp_clear_scheduled_hook('docketcache_checkversion');
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
                if ($this->plugin->constans()->is_true('DOCKET_CACHE_CHECKVERSION') && is_main_site()) {
                    // 06102020: reset to 2 days
                    $check = wp_get_scheduled_event('docketcache_checkversion');
                    if (\is_object($check) && 'docketcache_checkversion_schedule' !== $check->schedule) {
                        wp_clear_scheduled_hook('docketcache_checkversion');
                    }

                    add_action('docketcache_checkversion', [$this, 'checkversion']);
                    if (!wp_next_scheduled('docketcache_checkversion')) {
                        wp_schedule_event(time(), 'docketcache_checkversion_schedule', 'docketcache_checkversion');
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
     * monitor.
     */
    public function watchproc()
    {
        if ($this->plugin->canopt()->lockexp('watchproc')) {
            return false;
        }

        $this->plugin->canopt()->setlock('watchproc', time() + 3600);

        if (!$this->is_optimizedb) {
            $this->delete_expired_transients_db();
        }

        $this->clear_unknown_cron();
        if (has_action('docketcache/suspend_wp_options_autoload')) {
            do_action('docketcache/suspend_wp_options_autoload');
        }

        $this->plugin->get_cache_stats();
        $this->plugin->canopt()->setlock('watchproc', 0);

        return true;
    }

    /**
     * garbage_collector.
     */
    public function garbage_collector($is_filter = false)
    {
        $maxfile = $this->plugin->get_cache_maxfile();
        $maxfile = $maxfile - 100;

        $maxttl = $this->plugin->get_cache_maxttl();
        if (!empty($maxttl)) {
            $maxttl = time() - $maxttl;
        }

        $maxsizedisk = $this->plugin->get_cache_maxsize_disk();
        if (!empty($maxsizedisk)) {
            $maxsizedisk = $maxsizedisk - 1048576;
        }

        $collect = (object) [
            'maxttl' => $maxttl,
            'maxttl_h' => date('Y-m-d H:i:s T', $maxttl),
            'maxttl_c' => 0,
            'maxfile' => $maxfile,
            'maxfile_c' => 0,
            'total' => 0,
            'clean' => 0,
            'expired' => 0,
            'ignore' => 0,
        ];

        if ($this->plugin->canopt()->lockexp('garbage_collector')) {
            if ($is_filter) {
                return $collect;
            }

            return false;
        }

        $this->plugin->canopt()->setlock('garbage_collector', time() + 3600);

        if ($this->plugin->is_docketcachedir($this->plugin->cache_path)) {
            clearstatcache();
            $bytestotal = 0;
            $cnt = 0;
            foreach ($this->plugin->scanfiles($this->plugin->cache_path) as $object) {
                $fx = $object->getPathName();

                if (!$object->isFile() || 'file' !== $object->getType() || !$this->plugin->is_php($fx)) {
                    ++$collect->ignore;
                    continue;
                }

                $fn = $object->getFileName();
                $fs = $object->getSize();
                $fm = time() + 300;
                $ft = filemtime($fx);

                if ($fm >= $ft && (0 === $fs || 'dump_' === substr($fn, 0, 5))) {
                    $this->plugin->unlink($fx, true);
                    --$cnt;
                    ++$collect->clean;
                    continue;
                }

                $domaxttl = false;

                $data = $this->plugin->cache_get($fx);
                if (false !== $data) {
                    if (!empty($data['timeout']) && $this->plugin->valid_timestamp($data['timeout']) && $fm >= (int) $data['timeout']) {
                        $this->plugin->unlink($fx, false);
                        unset($data);
                        --$cnt;
                        ++$collect->clean;
                        ++$collect->expired;
                        continue;
                    }

                    if (empty($data['timeout']) && !empty($data['timestamp']) && $this->plugin->valid_timestamp($data['timestamp']) && $maxttl > 0) {
                        if ((int) $data['timestamp'] < $maxttl) {
                            $this->plugin->unlink($fx, false);
                            unset($data);
                            --$cnt;
                            ++$collect->clean;
                            ++$collect->maxttl_c;

                            $domaxttl = true;
                            continue;
                        }
                    }

                    $bytestotal += \strlen(serialize($data));
                    if ((int) $maxsizedisk > 1048576 && $bytestotal > $maxsizedisk) {
                        $this->plugin->unlink($fx, false);
                        unset($data);
                        --$cnt;
                        ++$collect->clean;
                    }
                }
                unset($data);

                if (!$domaxttl && $maxttl > 0 && $ft < $maxttl) {
                    $this->plugin->unlink($fx, true);
                    --$cnt;
                    ++$collect->clean;
                    ++$collect->maxttl_c;
                    continue;
                }

                if ($cnt >= $maxfile) {
                    $this->plugin->unlink($fx, true);
                    --$cnt;
                    ++$collect->clean;
                    ++$collect->maxfile_c;
                    continue;
                }

                ++$cnt;
                ++$collect->total;
            }
        }

        $this->plugin->canopt()->setlock('garbage_collector', 0);
        $this->plugin->dropino()->delay_expire();

        if ($is_filter) {
            return $collect;
        }

        return true;
    }

    /**
     * optimizedb.
     */
    public function optimizedb()
    {
        $wpdb = $this->plugin->safe_wpdb();
        if (!$wpdb) {
            return false;
        }

        if ($this->plugin->canopt()->lockexp('optimizedb')) {
            return false;
        }

        $this->plugin->canopt()->setlock('optimizedb', time() + 3600);

        $suppress = $wpdb->suppress_errors(true);

        @set_time_limit(300);
        $this->delete_expired_transients_db();

        if (is_main_site()) {
            $dbname = $wpdb->dbname;
            $tables = $wpdb->get_results('SHOW TABLES FROM '.$dbname, ARRAY_A);
            if (!empty($tables) && \is_array($tables)) {
                foreach ($tables as $table) {
                    $tbl = $table['Tables_in_'.$dbname];
                    $wpdb->query('OPTIMIZE TABLE `'.$tbl.'`');
                }
            }
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

        $wpdb = $this->plugin->safe_wpdb();
        if (!$wpdb) {
            return false;
        }

        delete_expired_transients(true);

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

        if ($this->plugin->canopt()->lockexp($part)) {
            return false;
        }

        $this->plugin->canopt()->setlock($part, time() + 3600);

        $main_site_url = $this->plugin->site_url();
        $site_url = $this->plugin->site_url(true);
        $stmp = time() + 120;
        $api_endpoint = $this->plugin->api_endpoint.'/'.$part.'?v='.$stmp;

        $args = [
            'blocking' => true,
            'body' => [
                'timestamp' => date('Y-m-d H:i:s T'),
                'timezone' => wp_timezone_string(),
                'site' => $site_url,
                'token' => $this->plugin->nw_encrypt($main_site_url, md5($site_url)),
                'meta' => $this->plugin->site_meta(),
            ],
            'headers' => [
                'REFERER' => $site_url,
            ],
        ];

        $results = Crawler::post($api_endpoint, $args);

        $output = [
            'created' => date('Y-m-d H:i:s T'),
            'endpoint' => $api_endpoint,
            'request' => [
                'headers' => $args['headers'],
                'content' => $args['body'],
            ],
        ];

        if (is_wp_error($results)) {
            $output['error'] = $results->get_error_message();
            $this->plugin->canopt()->save_part($output, $part);

            return false;
        }

        $output['response'] = wp_remote_retrieve_body($results);
        if (!empty($output['response'])) {
            $output['response'] = json_decode($output['response'], true);
            if (JSON_ERROR_NONE === json_last_error()) {
                if (!empty($output['response']['error'])) {
                    $output['error'] = $output['response']['error'];
                    $this->plugin->canopt()->save_part($output, $part);

                    return false;
                }
            }
        }

        $code = (int) wp_remote_retrieve_response_code($results);
        if ($code > 400) {
            $output['error'] = $code;
            $this->plugin->canopt()->save_part($output, $part);

            return false;
        }

        $this->plugin->canopt()->save_part($output, $part);

        $this->plugin->canopt()->setlock($part, 0);

        return true;
    }
}
