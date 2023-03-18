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
 * This class is a minimal version of cache.php, only used for object-cache.php.
 *
 * Reference:
 *  wp-content/plugins/docket-cache/includes/cache.php
 *  wp-content/plugins/docket-cache/includes/object-cache.php
 */

namespace Nawawi\DocketCache;

\defined('ABSPATH') || exit;

final class Becache
{
    private $cache_path;
    private $cache_maxsize = 3145728;
    private $cache_maxttl = 345600;
    private $multisite = false;
    private $blog_prefix;
    private $qlimit = 1000;
    private static $inst;
    private $network_id = 1;
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

        $this->network_id = (int) nwdcx_network_id();
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

        if ($this->cf()->is_dcfalse('CHUNKCACHEDIR', true)) {
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

    private function store_cache($key, $data, $group, $expire = 0)
    {
        if (!$this->fs()->mkdir_p($this->cache_path)) {
            return false;
        }

        $cache_key = $this->cache_key($key, $group);
        $file = $this->get_file_path($cache_key, $group);

        // chunk dir
        if ($this->cf()->is_dctrue('CHUNKCACHEDIR', true) && !$this->fs()->mkdir_p(\dirname($file))) {
            return false;
        }

        if ($this->fs()->is_transient($group)) {
            // transient timeout already as timestamp in DB.
            $timeout = $expire > 0 ? $expire : time() + 3600;
        } elseif ('options' === $group) {
            // initial set to 1 hour.
            $timeout = time() + 3600;
        }

        $timeout = $timeout > $this->cache_maxttl ? time() + $this->cache_maxttl : $timeout;
        $type = \gettype($data);
        if ('NULL' === $type && null === $data) {
            $data = '';
        }

        if (!empty($data)) {
            $len = 0;
            $nwdcx_suppresserrors = nwdcx_suppresserrors(true);
            if (\function_exists('maybe_serialize')) {
                $len = \strlen(@maybe_serialize($data));
            } else {
                $len = \strlen(@serialize($data));
            }
            nwdcx_suppresserrors($nwdcx_suppresserrors);

            if ($len >= $this->cache_maxsize) {
                $this->fs()->unlink($file, false);

                return false;
            }

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

        $meta = [];
        $meta['timestamp'] = time();

        if ($this->multisite) {
            $meta['network_id'] = $this->network_id;
        }

        $final_type = \gettype($data);
        if ('string' === $final_type && nwdcx_serialized($data)) {
            $final_type = 'string_serialize';
        } elseif ('array' === $final_type) {
            $nwdcx_suppresserrors = nwdcx_suppresserrors(true);
            $export_data = @var_export($data, 1);
            if (!empty($export_data)) {
                if (false !== strpos($export_data, 'Requests_Utility_CaseInsensitiveDictionary::__set_state')) {
                    $data = @serialize($data);
                    if (nwdcx_serialized($data)) {
                        $final_type = 'array_serialize';
                    }
                }

                if ('array' === $final_type && $this->fs()->is_transient($group) && false !== strpos($export_data, '::__set_state')) {
                    $data = @serialize($data);
                    if (nwdcx_serialized($data)) {
                        $final_type = 'array_serialize';
                    }
                }
            }
            unset($export_data);
            nwdcx_suppresserrors($nwdcx_suppresserrors);
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
        if ($this->cf()->is_dctrue('TRANSIENTDB')) {
            return false;
        }

        if (!nwdcx_wpdb($wpdb)) {
            return false;
        }

        $suppress = $wpdb->suppress_errors(true);
        $collect = [];

        // $results = $wpdb->get_results('SELECT `option_id`,`option_name`,`option_value` FROM `'.$wpdb->options.'` WHERE `option_name` LIKE "_transient_%" OR `option_name` LIKE "_site_transient_%" ORDER BY `option_id` ASC LIMIT '.$this->qlimit, ARRAY_A);
        $results = $wpdb->get_results('SELECT `option_id`,`option_name`,`option_value` FROM `'.$wpdb->options.'` WHERE `option_name` RLIKE "^(_site)?(_transient)(_timeout)?_.*?" ORDER BY `option_id` ASC LIMIT '.$this->qlimit, ARRAY_A);
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

                    if (time() > $collect[$key]['timeout']) {
                        unset($collect[$key]);
                    }
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
            // $results = $wpdb->get_results('SELECT `meta_id`,`meta_key`,`meta_value` FROM `'.$wpdb->sitemeta.'` WHERE `meta_key` LIKE "_site_transient_%" ORDER BY `meta_id` ASC LIMIT '.$this->qlimit, ARRAY_A);
            $results = $wpdb->get_results('SELECT `meta_id`,`meta_key`,`meta_value` FROM `'.$wpdb->sitemeta.'` WHERE `meta_key` RLIKE "^(_site_transient)(_timeout)?_.*?" ORDER BY `meta_id` ASC LIMIT '.$this->qlimit, ARRAY_A);
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

                    $key = @preg_replace('@^(_site_transient)(_timeout)?_@', '', $row['meta_key']);
                    if (!isset($collect[$key])) {
                        $collect[$key] = ['value' => '', 'group' => 'site-transient', 'timeout' => 0];
                    }

                    $collect[$key]['value'] = $row['meta_value'];

                    if (false !== strpos($row['meta_key'], '_site_transient_timeout_')) {
                        $collect[$key]['timeout'] = (int) $row['meta_value'];

                        if (time() > $collect[$key]['timeout']) {
                            unset($collect[$key]);
                        }
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

    public static function cleanup_transient()
    {
        if (nwdcx_construe('TRANSIENTDB')) {
            return false;
        }

        if (\function_exists('nwdcx_cleanuptransient')) {
            return nwdcx_cleanuptransient();
        }

        return false;
    }

    public function export_alloptions()
    {
        if (!nwdcx_wpdb($wpdb) || $this->multisite) {
            return false;
        }

        // Only export if autoload=yes and not transient.
        $suppress = $wpdb->suppress_errors(true);
        $alloptions_db = $wpdb->get_results('SELECT `option_name`,`option_value` FROM `'.$wpdb->options.'` WHERE autoload = \'yes\' AND `option_name` NOT LIKE "_transient_%" AND `option_name` NOT LIKE "_site_transient_%"', ARRAY_A);
        if (empty($alloptions_db)) {
            return false;
        }
        $wpdb->suppress_errors($suppress);

        $alloptions = [];

        if (\is_array($alloptions_db)) {
            $is_filter = $this->cf()->is_dctrue('WPOPTALOAD', true);
            $wp_options = [];
            if ($is_filter) {
                $wp_options = $this->fs()->keys_alloptions();
            }
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
