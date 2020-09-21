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

    public function __construct(Plugin $plugin)
    {
        $this->plugin = $plugin;
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
                    'docketcache_gc_schedule' => [
                        'interval' => 5 * MINUTE_IN_SECONDS,
                        'display' => esc_html__('Every 5 Minutes', 'docket-cache'),
                    ],
                    'monthly' => [
                        'interval' => MONTH_IN_SECONDS,
                        'display' => esc_html__('Once Monthly', 'docket-cache'),
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

                // optimize db
                $cronoptmzdb = $this->plugin->constans()->value('DOCKET_CACHE_CRONOPTMZDB');
                if (!empty($cronoptmzdb) && 'never' !== $cronoptmzdb) {
                    add_action('docketcache_optimizedb', [$this, 'optimizedb']);

                    $recurrence = 'weekly';
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

                    if (!wp_next_scheduled('docketcache_optimizedb')) {
                        wp_schedule_event(time(), $recurrence, 'docketcache_optimizedb');
                    }
                } else {
                    if (wp_get_schedule('docketcache_optimizedb')) {
                        wp_clear_scheduled_hook('docketcache_optimizedb');
                    }
                }

                // monitor
                add_action('docketcache_watchproc', [$this, 'watchproc']);
                if (!wp_next_scheduled('docketcache_watchproc')) {
                    wp_schedule_event(time(), 'hourly', 'docketcache_watchproc');
                }
            }
        );
    }

    /**
     * unregister.
     */
    public function unregister()
    {
        foreach (['docketcache_gc', 'docketcache_optimizedb', 'docketcache_monitor'] as $hx) {
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

        $this->clear_unknown_cron();
        $this->delete_expired_transients_db();
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
                $fm = time() + 120;
                $ft = filemtime($fx);

                if ($maxttl > 0 && $ft < $maxttl) {
                    $this->plugin->unlink($fx, true);
                    --$cnt;
                    ++$collect->clean;
                    ++$collect->maxttl_c;
                    continue;
                }

                if ($fm >= $ft && (0 === $fs || 'dump_' === substr($fn, 0, 5))) {
                    $this->plugin->unlink($fx, true);
                    --$cnt;
                    ++$collect->clean;
                    continue;
                }

                $data = $this->plugin->cache_get($fx);
                if (false !== $data) {
                    if (!empty($data['timeout']) && $fm >= (int) $data['timeout']) {
                        $this->plugin->unlink($fx, false);
                        unset($data);
                        --$cnt;
                        ++$collect->clean;
                        ++$collect->expired;
                        continue;
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
        $this->delete_expired_transients_db();

        $dbname = $wpdb->dbname;
        $tables = $wpdb->get_results('SHOW TABLES FROM '.$dbname, ARRAY_A);
        if (!empty($tables) && \is_array($tables)) {
            foreach ($tables as $table) {
                $tbl = $table['Tables_in_'.$dbname];
                $wpdb->query('OPTIMIZE TABLE `'.$tbl.'`');
            }
        }

        $wpdb->suppress_errors($suppress);

        $this->plugin->canopt()->setlock('optimizedb', 0);

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

        // delete expired transient in db
        // https://developer.wordpress.org/reference/functions/delete_expired_transients/
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
}
