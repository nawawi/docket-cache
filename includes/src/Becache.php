<?php
/**
 * Docket Cache.
 *
 * @author  Nawawi Jamili
 * @license MIT
 *
 * @see    https://github.com/nawawi/docket-cache
 */

/*
 * Reference:
 *  wp-content/plugins/docket-cache/includes/cache.php
 *  wp-content/plugins/docket-cache/includes/object-cache.php
 */

namespace Nawawi\DocketCache;

\defined('ABSPATH') || exit;

class Becache
{
    private $cache_path;
    private $cache_maxsize = 3145728;
    private $cache_maxttl = 345600;
    private $multisite = false;
    private $blog_prefix;
    private $qlimit = 5000;
    private static $inst;

    public function __construct()
    {
        $this->multisite = \function_exists('is_multisite') && is_multisite();
        $this->blog_prefix = $this->switch_to_blog(get_current_blog_id());

        $this->cache_path = $this->fs()->define_cache_path($this->cf()->dcvalue('PATH'));
        if (\function_exists('is_multisite') && is_multisite()) {
            $this->cache_path = nwdcx_network_dirpath($this->cache_path);
        }

        if ($this->cf()->is_dcint('MAXSIZE', $dcvalue)) {
            if (!empty($dcvalue)) {
                $this->cache_maxsize = $this->fs()->sanitize_maxsize($dcvalue);
            }
        }

        if ($this->cf()->is_dcint('MAXTTL', $dcvalue)) {
            if (!empty($dcvalue)) {
                $this->cache_maxttl = $this->fs()->sanitize_maxttl($dcvalue);
            }
        }
    }

    public static function export()
    {
        if (!isset(self::$inst)) {
            self::$inst = new self();
        }

        self::$inst->export_transient();
        self::$inst->export_alloptions();
    }

    private function fs()
    {
        static $inst;
        if (!\is_object($inst)) {
            $inst = new Filesystem();
        }

        return $inst;
    }

    private function cf()
    {
        static $inst;
        if (!\is_object($inst)) {
            $inst = new Constans();
        }

        return $inst;
    }

    private function cache_key($key, $group)
    {
        if ($this->multisite && 'site-transient' === $group) {
            $key = $this->blog_prefix.$key;
        }

        return $key;
    }

    private function item_hash($str, $length = 12)
    {
        if (empty($length)) {
            return md5($str);
        }

        return substr(md5($str), 0, $length);
    }

    private function get_file_path($key, $group)
    {
        $index = $this->item_hash($group).'-'.$this->item_hash($key);

        return $this->cache_path.$index.'.php';
    }

    private function dump_code($file, $arr)
    {
        $data = $this->fs()->export_var($arr, $error);
        if (false === $data) {
            return false;
        }

        $code = $this->fs()->code_stub($data);
        $stat = $this->fs()->dump($file, $code, false);

        if (false === $stat) {
            return false;
        }

        if (-1 === $stat) {
            return false;
        }

        $this->fs()->validate_fatal_error_file($file);

        return $stat;
    }

    private function maybe_expire($group, $expire = 0, $key = '')
    {
        if (empty($expire)) {
            $expire = 0;
        }

        $expire = $this->fs()->sanitize_timestamp($expire);
        $maxttl = $this->cache_maxttl;

        if (0 === $expire && $maxttl < 2419200) {
            if (\in_array($group, ['site-transient', 'transient'])) {
                if ('site-transient' === $group && \in_array($key, ['update_plugins', 'update_themes', 'update_core', '_woocommerce_helper_updates'])) {
                    $expire = $maxttl < 2419200 ? 2419200 : $maxttl; // 28d
                } else {
                    $expire = $maxttl < 604800 ? 604800 : $maxttl; // 7d
                }
            } elseif (\in_array($group, ['terms', 'posts', 'post_meta', 'options', 'site-options', 'comments'])) {
                $expire = $maxttl < 1209600 ? 1209600 : $maxttl; // 14d
            }
        }

        return $expire;
    }

    private function store_cache($key, $data, $group, $expire = 0)
    {
        if (!$this->fs()->mkdir_p($this->cache_path)) {
            return false;
        }

        $expire = $this->maybe_expire($group, $expire, $key);

        $cache_key = $this->cache_key($key, $group);
        $file = $this->get_file_path($cache_key, $group);
        $timeout = ($expire > 0 ? time() + $expire : 0);

        $type = \gettype($data);
        if ('NULL' === $type && null === $data) {
            $data = '';
        }

        if (!empty($data)) {
            if ('string' === $type) {
                $data = nwdcx_unserialize($data);
            } elseif ('array' === $type) {
                $data_r = nwdcx_arraymap('nwdcx_unserialize', $data);

                if (!empty($data_r)) {
                    $data = $data_r;
                }
                unset($data_r);
            }
        }

        $len = \strlen(serialize($data));
        if ($len >= $this->cache_maxsize) {
            return false;
        }

        $meta = [];
        $meta['timestamp'] = time();

        if ($this->multisite) {
            try {
                $meta['network_id'] = get_current_network_id();
            } catch (\Throwable $e) {
                $meta['network_id'] = 0;
            }
        }

        $final_type = \gettype($data);

        $meta['site_id'] = get_current_blog_id();
        $meta['group'] = $group;
        $meta['key'] = $cache_key;
        $meta['type'] = $final_type;
        $meta['timeout'] = $timeout;
        $meta['data'] = $data;

        if (true === $this->dump_code($file, $meta)) {
            return true;
        }

        return false;
    }

    public function export_transient()
    {
        if (!nwdcx_wpdb($wpdb)) {
            return false;
        }

        $suppress = $wpdb->suppress_errors(true);

        $collect = [];

        $results = $wpdb->get_results('SELECT `option_id`,`option_name`,`option_value` FROM `'.$wpdb->options.'` WHERE `option_name` LIKE "_transient_%" OR `option_name` LIKE "_site_transient_%" ORDER BY `option_id` ASC LIMIT '.$this->qlimit, ARRAY_A);
        if (!empty($results) && \is_array($results)) {
            while ($row = @array_shift($results)) {
                $key = @preg_replace('@^(_site)?(_transient)(_timeout)?_@', '', $row['option_name']);
                if (!isset($collect[$key])) {
                    $collect[$key] = ['value' => '', 'group' => 'transient', 'timeout' => 0];
                }

                $collect[$key]['value'] = $row['option_value'];
                if ('_site_' === substr($row['option_name'], 0, 6)) {
                    $collect[$key]['group'] = 'site-transient';
                }

                if (false !== strpos($row['option_name'], '_transient_timeout_')) {
                    $collect[$key]['timeout'] = (int) $row['option_value'];
                }
            }

            if (!empty($collect)) {
                foreach ($collect as $key => $arr) {
                    $this->store_cache($key, $arr['value'], $arr['group'], $arr['timeout']);
                }
            }
        }

        $collect = [];

        if ($this->multisite && isset($wpdb->sitemeta)) {
            $results = $wpdb->get_results('SELECT `meta_id`,`meta_key`,`meta_value` FROM `'.$wpdb->sitemeta.'` WHERE `meta_key` LIKE "_site_transient_%" ORDER BY `meta_id` ASC LIMIT '.$this->qlimit, ARRAY_A);
            if (!empty($results) && \is_array($results)) {
                while ($row = @array_shift($results)) {
                    $key = @preg_replace('@^(_site)?(_transient)(_timeout)?_@', '', $row['meta_key']);
                    if (!isset($collect[$key])) {
                        $collect[$key] = ['value' => '', 'group' => 'site-transient', 'expire' => 0];
                    }

                    $collect[$key]['value'] = $row['meta_value'];

                    if (false !== strpos($row['meta_key'], '_site_transient_timeout_')) {
                        $collect[$key]['timeout'] = (int) $row['meta_value'];
                    }
                }

                if (!empty($collect)) {
                    foreach ($collect as $key => $arr) {
                        $this->store_cache($key, $arr['value'], $arr['group'], $arr['timeout']);
                    }
                }
            }
        }

        unset($collect, $results);
        $wpdb->suppress_errors($suppress);

        return true;
    }

    public function export_alloptions()
    {
        if (!nwdcx_wpdb($wpdb) || $this->multisite) {
            return false;
        }

        $suppress = $wpdb->suppress_errors(true);
        $alloptions_db = $wpdb->get_results('SELECT `option_name`,`option_value` FROM `'.$wpdb->options.'` WHERE autoload = \'yes\'', ARRAY_A);
        if (empty($alloptions_db)) {
            $alloptions_db = $wpdb->get_results('SELECT `option_name`,`option_value` FROM `'.$wpdb->options.'`', ARRAY_A);
        }
        $wpdb->suppress_errors($suppress);

        $alloptions = [];

        if (!empty($alloptions_db) && \is_array($alloptions_db)) {
            $wp_options = $this->fs()->keys_alloptions();
            $is_filter = $this->cf()->is_dctrue('WPOPTALOAD');
            foreach ($alloptions_db as $num => $options) {
                $key = $options['option_name'];
                if ($is_filter && \array_key_exists($key, $wp_options)) {
                    continue;
                }
                $alloptions[$key] = $options['option_value'];
            }

            if (!empty($alloptions)) {
                $this->store_cache('alloptions', $alloptions, 'options');
            }
        }

        unset($alloptions, $alloptions_db);

        // always true
        return true;
    }

    private function switch_to_blog($blog_id)
    {
        $blog_id = (int) $blog_id;
        $this->blog_prefix = $this->multisite ? $blog_id.':' : '';
    }
}
