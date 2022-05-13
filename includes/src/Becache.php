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
 * This class is a minimal version of cache.php, only use for object-cache.php.
 *
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
    private $max_execution_time = 0;

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

        $this->max_execution_time = $this->fs()->get_max_execution_time();
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
        $hash_group = $this->item_hash($group);
        $hash_key = $this->item_hash($key);

        $index = $hash_group.'-'.$hash_key;

        if ($this->cf()->is_dcfalse('CHUNKCACHEDIR')) {
            return $this->cache_path.$index.'.php';
        }

        $chunk_path = $this->fs()->get_chunk_path($hash_group, $hash_key);

        return $this->cache_path.$chunk_path.$index.'.php';
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
            if ($this->fs()->is_transient($group)) {
                if ('site-transient' === $group && \in_array($key, ['update_plugins', 'update_themes', 'update_core', '_woocommerce_helper_updates'])) {
                    $expire = $maxttl < 2419200 ? 2419200 : $maxttl; // 28d
                } elseif ('transient' === $group && 'health-check-site-status-result' === $key) {
                    $expire = 0; // to check with is_data_uptodate
                } else {
                    $expire = $maxttl < 604800 ? 604800 : $maxttl; // 7d
                }
            } elseif (\in_array($group, ['terms', 'posts', 'post_meta', 'options', 'site-options', 'comments'])) {
                $expire = $maxttl < 1209600 ? 1209600 : $maxttl; // 14d

                // woocommerce stale cache
                // wc_cache_0.72953700 1651592702
            } elseif ('wc_cache_' === substr($key, 0, 9) && preg_match('@^wc_cache_([0-9\. ]+)_@', $key)) {
                $expire = 86400; // 1d
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

        // chunk dir
        if ($this->cf()->is_dctrue('CHUNKCACHEDIR') && !$this->fs()->mkdir_p(\dirname($file))) {
            return false;
        }

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
        if ('array' === $final_type) {
            // http remote request
            // headers => Requests_Utility_CaseInsensitiveDictionary Object
            if (!empty($data['headers']) && \is_object($data['headers']) && false !== strpos(var_export($data['headers'], 1), 'Requests_Utility_CaseInsensitiveDictionary::__set_state')) {
                $data = @serialize($data);
                if (nwdcx_serialized($data)) {
                    $final_type = 'string';
                }
            }
        }

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
                if ($this->max_execution_time > 0 && \defined('WP_START_TIMESTAMP') && (microtime(true) - WP_START_TIMESTAMP) > $this->max_execution_time) {
                    $collect = [];
                    break;
                }

                // ignore empty value
                if ('' === $row['option_value']) {
                    continue;
                }

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
                    if ($this->max_execution_time > 0 && \defined('WP_START_TIMESTAMP') && (microtime(true) - WP_START_TIMESTAMP) > $this->max_execution_time) {
                        break;
                    }

                    $timeout = isset($arr['timeout']) ? $arr['timeout'] : 0;
                    $this->store_cache($key, $arr['value'], $arr['group'], $timeout);
                }
            }
        }

        $collect = [];

        if ($this->multisite && isset($wpdb->sitemeta)) {
            $results = $wpdb->get_results('SELECT `meta_id`,`meta_key`,`meta_value` FROM `'.$wpdb->sitemeta.'` WHERE `meta_key` LIKE "_site_transient_%" ORDER BY `meta_id` ASC LIMIT '.$this->qlimit, ARRAY_A);
            if (!empty($results) && \is_array($results)) {
                while ($row = @array_shift($results)) {
                    if ($this->max_execution_time > 0 && \defined('WP_START_TIMESTAMP') && (microtime(true) - WP_START_TIMESTAMP) > $this->max_execution_time) {
                        $collect = [];
                        break;
                    }

                    // ignore empty value
                    if ('' === $row['meta_value']) {
                        continue;
                    }

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
                        $timeout = isset($arr['timeout']) ? $arr['timeout'] : 0;
                        $this->store_cache($key, $arr['value'], $arr['group'], $timeout);
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
                if ($this->max_execution_time > 0 && \defined('WP_START_TIMESTAMP') && (microtime(true) - WP_START_TIMESTAMP) > $this->max_execution_time) {
                    $alloptions = [];
                    break;
                }

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
