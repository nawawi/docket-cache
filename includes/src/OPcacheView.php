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

if (!class_exists('\\WP_List_Table', false)) {
    require_once ABSPATH.'wp-admin/includes/class-wp-list-table.php';
}

class OPcacheView extends \WP_List_Table
{
    private $pt;

    public function __construct(Plugin $pt)
    {
        parent::__construct(
            [
                'singular' => 'opclist-info',
                'plural' => 'opclist-infos',
                'ajax' => false,
                'screen' => 'opclist-infos',
            ]
        );

        $this->pt = $pt;
    }

    public function get_doc($name)
    {
        $name = str_replace('_', '-', $name);

        return 'https://www.php.net/manual/en/opcache.configuration.php#ini.'.$name;
    }

    public function get_config()
    {
        static $data = false;

        try {
            if (\function_exists('opcache_get_configuration') && (empty($data) || !\is_array($data))) {
                $data = @opcache_get_configuration();
            }
        } catch (\Throwable $e) {
            nwdcx_throwable(__METHOD__, $e);
        }

        return $data;
    }

    private function get_status()
    {
        $data = $this->pt->get_opcache_status(true);
        if (!empty($data) && \is_array($data)) {
            return $data;
        }

        return false;
    }

    public function get_usage()
    {
        $stats = [
             'num_cached_scripts' => 0,
             'num_cached_keys' => 0,
             'max_cached_keys' => 0,
             'hits' => 0,
             'start_time' => 0,
             'last_restart_time' => 0,
             'oom_restarts' => 0,
             'hash_restarts' => 0,
             'manual_restarts' => 0,
             'misses' => 0,
             'blacklist_misses' => 0,
             'blacklist_miss_ratio' => 0,
             'opcache_hit_rate' => 0,
             'used_memory' => 0,
             'free_memory' => 0,
             'wasted_memory' => 0,
             'current_wasted_percentage' => 0,
         ];

        $data = $this->get_status();
        if (!empty($data) && \is_array($data)) {
            $stats = array_merge($data['opcache_statistics'], $data['memory_usage']);
        }

        return (object) $stats;
    }

    private function get_files()
    {
        $stats = [];
        $data = $this->get_status();
        if (!empty($data) && \is_array($data)) {
            if (!empty($data['scripts']) && \is_array($data['scripts'])) {
                $sstr = !empty($_GET['s']) ? sanitize_text_field(wp_unslash($_GET['s'])) : '';
                $sftr = !empty($_GET['sf']) ? sanitize_text_field($_GET['sf']) : 'obc';
                $smtr = !empty($_GET['sm']) ? sanitize_text_field($_GET['sm']) : '1k';

                $cnt = 0;
                $limit = 0;

                if ('all' !== $smtr) {
                    $smtr = strtok($smtr, 'k').'000';
                    $limit = (int) $smtr;
                }

                foreach ($data['scripts'] as $script => $arr) {
                    $cpath = wp_normalize_path($arr['full_path']);
                    $script = wp_normalize_path($script);

                    if (!empty($sstr) && false === strpos($cpath, $sstr)) {
                        continue;
                    }

                    if (!empty($sftr)) {
                        $cfile = basename($cpath);
                        $cdir = \dirname($cpath);

                        if ('obc' === $sftr && (false === strpos($script, $this->pt->cache_path) || !$this->pt->is_docketcachedir($cdir) || !@preg_match('@^([a-z0-9_]+)\-([a-z0-9]+).*\.php$@', $cfile))) {
                            continue;
                        }

                        if ('wpc' === $sftr && (false !== strpos($script, $this->pt->cache_path) && $this->pt->is_docketcachedir($cdir) && @preg_match('@^([a-z0-9_]+)\-([a-z0-9]+).*\.php$@', $cfile))) {
                            continue;
                        }

                        if ('dfc' === $sftr && @is_file($cpath)) {
                            continue;
                        }
                    }

                    $sort = $arr['hits'];
                    if (!empty($_GET['orderby'])) {
                        switch ($_GET['orderby']) {
                            case 'file':
                                $sort = $cpath;
                                break;
                            case 'mem':
                                $sort = $arr['memory_consumption'];
                                break;
                            case 'stmp':
                                $sort = $arr['last_used_timestamp'];
                                break;
                        }
                    }

                    $stats[$sort.$script] = (object) [
                        'is_exists' => @is_file($cpath),
                        'file' => $this->pt->sanitize_rootpath($cpath),
                        'hits' => $arr['hits'],
                        'mem' => $this->pt->normalize_size($arr['memory_consumption']),
                        'stmp' => wp_date('Y-m-d H:i:s', $arr['last_used_timestamp']),
                    ];

                    ++$cnt;
                    if ($limit > 0 && $cnt >= $limit) {
                        break;
                    }
                }

                if (!empty($_GET['orderby']) && !empty($_GET['order'])) {
                    $order = sanitize_text_field($_GET['order']);
                    $orderby = sanitize_text_field($_GET['orderby']);

                    if ('desc' === $order) {
                        if ('file' !== $orderby) {
                            krsort($stats, SORT_NUMERIC);
                        } else {
                            krsort($stats, SORT_STRING);
                        }
                    } else {
                        if ('file' !== $orderby) {
                            ksort($stats, SORT_NUMERIC);
                        } else {
                            ksort($stats, SORT_STRING);
                        }
                    }
                } else {
                    krsort($stats, SORT_NUMERIC);
                }
            }
        }

        unset($data);

        return $stats;
    }

    public function prepare_items()
    {
        $stats = $this->get_files();

        $count = \count($stats);
        $per_page = 50;
        $offset = ($this->get_pagenum() - 1) * $per_page;

        $this->items = \array_slice($stats, $offset, $per_page);

        $this->set_pagination_args(
            [
                'total_items' => $count,
                'per_page' => $per_page,
                'total_pages' => ceil($count / $per_page),
            ]
        );
    }

    public function get_columns()
    {
        /* translators: %s = utc offset */
        $lastused = sprintf(esc_html__('Last Used %s', 'docket-cache'), '('.$this->pt->get_utc_offset().')');

        return [
            'opclist_file' => esc_html__('Cached Files', 'docket-cache'),
            'opclist_hits' => esc_html__('Cache Hits', 'docket-cache'),
            'opclist_mem' => esc_html__('Memory Usage', 'docket-cache'),
            'opclist_timestamp' => $lastused,
        ];
    }

    public function get_sortable_columns()
    {
        return [
             'opclist_file' => ['file', false],
             'opclist_hits' => ['hits', false],
             'opclist_mem' => ['mem', false],
             'opclist_timestamp' => ['stmp', false],
         ];
    }

    public function get_table_classes()
    {
        return ['widefat', 'striped', $this->_args['plural']];
    }

    public function column_opclist_hits($stats)
    {
        return '<code>'.$stats->hits.'</code>';
    }

    public function column_opclist_mem($stats)
    {
        return '<code>'.$stats->mem.'</code>';
    }

    public function column_opclist_file($stats)
    {
        if ($stats->is_exists) {
            return esc_html($stats->file);
        }

        return '<span class="text-red">'.esc_html($stats->file).'</span>';
    }

    public function column_opclist_timestamp($stats)
    {
        return esc_html($stats->stmp);
    }

    public function no_items()
    {
        if (empty($_GET['s'])) {
            esc_html_e('OPcache items not available.', 'docket-cache');
        } else {
            esc_html_e('No matching OPcache Cached Files.', 'docket-cache');
        }
    }
}
