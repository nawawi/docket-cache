<?php
/**
 * Docket Cache.
 *
 * @author  Nawawi Jamili
 * @license MIT
 *
 * @see    https://github.com/nawawi/docket-cache
 */
\defined('ABSPATH') || exit;

/*
 * Reference:
 *  wp-includes/class-wp-object-cache.php
 *  wp-includes/cache.php
 */

/**
 * Core class that implements an object cache.
 */
class WP_Object_Cache
{
    /**
     * Holds the cached objects.
     *
     * @var array
     */
    private $cache = [];

    /**
     * The amount of times the cache data was already stored in the cache.
     *
     * @var int
     */
    public $cache_hits = 0;

    /**
     * Amount of times the cache did not have the request in cache.
     *
     * @var int
     */
    public $cache_misses = 0;

    /**
     * List of global cache groups.
     *
     * @var array
     */
    protected $global_groups = [];

    /**
     * List of non persistent groups.
     *
     * @var array
     */
    protected $non_persistent_groups = [];

    /**
     * List of non persistent keys.
     *
     * @var array
     */
    protected $non_persistent_keys = [];

    /**
     * List of non persistent group:key.
     *
     * @var array
     */
    protected $non_persistent_groupkey = [];

    /**
     * List of group:key exclude from pecaching.
     *
     * @var array
     */
    protected $bypass_precache = [];

    /**
     * The blog prefix to prepend to keys in non-global groups.
     *
     * @var string
     */
    private $blog_prefix;

    /**
     * Holds the value of is_multisite().
     *
     * @var bool
     */
    private $multisite;

    /**
     * The cache path.
     *
     * @var string
     */
    private $cache_path;

    /**
     * The cache maximum size of cache file.
     *
     * @var int
     */
    private $cache_maxsize = 3145728;

    /**
     * The cache file lifespan.
     *
     * @var int
     */
    private $cache_maxttl = 345600;

    /**
     * List of filtered groups.
     *
     * @var array
     */
    private $filtered_groups = false;

    /**
     * Show signature.
     *
     * @var bool
     */
    private $add_signature;

    /**
     * List of caches to preload.
     *
     * @var array
     */
    public $precache = [];

    /**
     * List of loaded keys.
     *
     * @var array
     */
    public $precache_loaded = [];

    /**
     * Precache status.
     *
     * @var bool
     */
    private $is_precache = false;

    /**
     * Precache key.
     *
     * @var string
     */
    private $precache_hashkey = '';

    /**
     * Precache max entries.
     *
     * @var int
     */
    private $precache_maxlist = 500;

    /**
     * The maximum time in seconds a script is allowed to run.
     *
     * @var int
     */
    private $max_execution_time = 0;

    /**
     * Start of run timestamp.
     *
     * @var int
     */
    private $wp_start_timestamp = 0;

    /**
     * Stalecache status.
     *
     * @var bool
     */
    private $is_stalecache = false;

    /**
     * List of stale cache to remove.
     *
     * @var array
     */
    private $stalecache_list = [];

    /**
     * Dev mode.
     *
     * @var bool
     */
    private $is_dev = false;

    /**
     * Sets up object properties.
     */
    public function __construct()
    {
        $this->multisite = \function_exists('is_multisite') && is_multisite();
        $this->blog_prefix = $this->switch_to_blog(get_current_blog_id());
        $this->dc_init();
    }

    /**
     * Serves as a utility function to determine whether a key exists in the cache.
     *
     * @param int|string $key   cache key to check for existence
     * @param string     $group cache group for the key existence check
     *
     * @return bool whether the key exists in the cache for the given group
     */
    protected function _exists($key, $group)
    {
        // check key
        if (!$this->is_valid_key($key)) {
            return false;
        }

        // check group
        if (!\is_string($group)) {
            // unset junk
            unset($this->cache[$group]);
            unset($this->precache[$group]);

            return false;
        }

        $is_exists = !empty($this->cache) && isset($this->cache[$group]) && (isset($this->cache[$group][$key]) || \array_key_exists($key, $this->cache[$group]));
        if (!$is_exists && !$this->is_non_persistent_groups($group) && !$this->is_non_persistent_keys($key) && !$this->is_non_persistent_groupkey($group, $key)) {
            $data = $this->dc_get($key, $group, false);
            if (false !== $data) {
                $is_exists = true;
                $this->cache[$group][$key] = $data;

                if ($this->is_precache && !$this->is_bypass_precache($group, $key)) {
                    $this->precache[$group][$key] = 1;
                }
            }
        }

        return $is_exists;
    }

    /**
     * Adds data to the cache if it doesn't already exist.
     *
     * @uses WP_Object_Cache::_exists() Checks to see if the cache already has data.
     * @uses WP_Object_Cache::set()     Sets the data after the checking the cache
     *                                  contents existence.
     *
     * @param int|string $key    what to call the contents in the cache
     * @param mixed      $data   the contents to store in the cache
     * @param string     $group  Optional. Where to group the cache contents. Default 'default'.
     * @param int        $expire Optional. When to expire the cache contents. Default 0 (no expiration).
     *
     * @return bool true on success, false if cache key and group already exist
     */
    public function add($key, $data, $group = 'default', $expire = 0)
    {
        if (wp_suspend_cache_addition()) {
            return false;
        }

        if (empty($group)) {
            $group = 'default';
        }

        if (!$this->is_valid_key($key)) {
            return false;
        }

        $cache_key = $this->dc_key($key, $group);
        if ($this->_exists($cache_key, $group)) {
            return false;
        }

        return $this->set($key, $data, $group, (int) $expire);
    }

    /**
     * Adds multiple values to the cache in one call.
     *
     * @param array  $data   array of keys and values to be added
     * @param string $group  Optional. Where the cache contents are grouped. Default empty.
     * @param int    $expire Optional. When to expire the cache contents, in seconds.
     *                       Default 0 (no expiration).
     *
     * @return bool[] Array of return values, grouped by key. Each value is either
     *                true on success, or false if cache key and group already exist.
     */
    public function add_multiple(array $data, $group = '', $expire = 0)
    {
        $values = [];

        foreach ($data as $key => $value) {
            $values[$key] = $this->add($key, $value, $group, $expire);
        }

        return $values;
    }

    /**
     * Replaces the contents in the cache, if contents already exist.
     *
     * @see WP_Object_Cache::set()
     *
     * @param int|string $key    what to call the contents in the cache
     * @param mixed      $data   the contents to store in the cache
     * @param string     $group  Optional. Where to group the cache contents. Default 'default'.
     * @param int        $expire Optional. When to expire the cache contents. Default 0 (no expiration).
     *
     * @return bool false if not exists, true if contents were replaced
     */
    public function replace($key, $data, $group = 'default', $expire = 0)
    {
        if (empty($group)) {
            $group = 'default';
        }

        if (!$this->is_valid_key($key)) {
            return false;
        }

        $cache_key = $this->dc_key($key, $group);

        if (!$this->_exists($cache_key, $group)) {
            return false;
        }

        return $this->set($key, $data, $group, (int) $expire);
    }

    /**
     * Sets the data contents into the cache.
     *
     * The cache contents are grouped by the $group parameter followed by the
     * $key. This allows for duplicate ids in unique groups. Therefore, naming of
     * the group should be used with care and should follow normal function
     * naming guidelines outside of core WordPress usage.
     *
     * @param int|string $key    what to call the contents in the cache
     * @param mixed      $data   the contents to store in the cache
     * @param string     $group  Optional. Where to group the cache contents. Default 'default'.
     * @param int        $expire the expiration time, defaults to 0
     *
     * @return true always returns true
     */
    public function set($key, $data, $group = 'default', $expire = 0)
    {
        if (empty($group)) {
            $group = 'default';
        }

        if (!$this->is_valid_key($key)) {
            return false;
        }

        // from invalidate cache
        if ($this->is_stalecache) {
            $this->dc_stalecache_filter($key, $group);
        }

        $cache_key = $this->dc_key($key, $group);

        if (\is_object($data)) {
            $data = clone $data;
        }

        $this->cache[$group][$cache_key] = $data;

        if ((!$this->is_non_persistent_groups($group) && !$this->is_non_persistent_keys($key) && !$this->is_non_persistent_groupkey($group, $key)) || $this->is_filtered_groups($group, $key)) {
            $expire = $this->maybe_expire($group, $expire, $key);
            $this->dc_save($cache_key, $this->cache[$group][$cache_key], $group, $expire, $key);
        }

        return true;
    }

    /**
     * Sets multiple values to the cache in one call.
     *
     * @param array  $data   array of key and value to be set
     * @param string $group  Optional. Where the cache contents are grouped. Default empty.
     * @param int    $expire Optional. When to expire the cache contents, in seconds.
     *                       Default 0 (no expiration).
     *
     * @return bool[] Array of return values, grouped by key. Each value is always true.
     */
    public function set_multiple(array $data, $group = '', $expire = 0)
    {
        $values = [];

        foreach ($data as $key => $value) {
            $values[$key] = $this->set($key, $value, $group, $expire);
        }

        return $values;
    }

    /**
     * Retrieves the cache contents, if it exists.
     *
     * The contents will be first attempted to be retrieved by searching by the
     * key in the cache group. If the cache is hit (success) then the contents
     * are returned.
     *
     * On failure, the number of cache misses will be incremented.
     *
     * @param int|string $key   what the contents in the cache are called
     * @param string     $group Optional. Where the cache contents are grouped. Default 'default'.
     * @param bool       $force Optional. Unused. Whether to force a refetch rather than relying on the local
     *                          cache. Default false.
     * @param bool       $found Optional. Whether the key was found in the cache (passed by reference).
     *                          Disambiguates a return of false, a storable value. Default null.
     *
     * @return mixed|false the cache contents on success, false on failure to retrieve contents
     */
    public function get($key, $group = 'default', $force = false, &$found = null)
    {
        if (empty($group)) {
            $group = 'default';
        }

        if (!$this->is_valid_key($key)) {
            return false;
        }

        $cache_key = $this->dc_key($key, $group);

        if ($this->_exists($cache_key, $group)) {
            $found = true;

            if (\is_object($this->cache[$group][$cache_key])) {
                return clone $this->cache[$group][$cache_key];
            }

            return $this->cache[$group][$cache_key];
        }

        $found = false;

        return false;
    }

    /**
     * Retrieves multiple values from the cache in one call.
     *
     * @param array  $keys  array of keys under which the cache contents are stored
     * @param string $group Optional. Where the cache contents are grouped. Default 'default'.
     * @param bool   $force Optional. Whether to force an update of the local cache
     *                      from the persistent cache. Default false.
     *
     * @return array array of values organized into groups
     */
    public function get_multiple($keys, $group = 'default', $force = false)
    {
        $values = [];
        if (!empty($keys) && \is_array($keys)) {
            foreach ($keys as $key) {
                $values[$key] = $this->get($key, $group, $force);
            }
        }

        return $values;
    }

    /**
     * Removes the contents of the cache key in the group.
     *
     * If the cache key does not exist in the group, then nothing will happen.
     *
     * @param int|string $key        what the contents in the cache are called
     * @param string     $group      Optional. Where the cache contents are grouped. Default 'default'.
     * @param bool       $deprecated Optional. Unused. Default false.
     *
     * @return bool false if the contents weren't deleted and true on success
     */
    public function delete($key, $group = 'default', $deprecated = false)
    {
        if (empty($group)) {
            $group = 'default';
        }

        if (!$this->is_valid_key($key)) {
            return false;
        }

        $key = $this->dc_key($key, $group);

        unset($this->cache[$group][$key]);
        unset($this->precache[$group][$key]);

        $this->dc_remove($key, $group);

        // always true
        return true;
    }

    /**
     * Deletes multiple values from the cache in one call.
     *
     * @param array  $keys  array of keys to be deleted
     * @param string $group Optional. Where the cache contents are grouped. Default empty.
     *
     * @return bool[] Array of return values, grouped by key. Each value is either
     *                true on success, or false if the contents were not deleted.
     */
    public function delete_multiple(array $keys, $group = '')
    {
        $values = [];

        foreach ($keys as $key) {
            $values[$key] = $this->delete($key, $group);
        }

        return $values;
    }

    /**
     * Increments numeric cache item's value.
     *
     * @param int|string $key    The cache key to increment
     * @param int        $offset Optional. The amount by which to increment the item's value. Default 1.
     * @param string     $group  Optional. The group the key is in. Default 'default'.
     *
     * @return int|false the item's new value on success, false on failure
     */
    public function incr($key, $offset = 1, $group = 'default')
    {
        if (empty($group)) {
            $group = 'default';
        }

        if (!$this->is_valid_key($key)) {
            return false;
        }

        $cache_key = $this->dc_key($key, $group);

        if (!$this->_exists($cache_key, $group)) {
            return false;
        }

        if (!is_numeric($this->cache[$group][$cache_key])) {
            $this->cache[$group][$cache_key] = 0;
        }

        $offset = (int) $offset;

        $this->cache[$group][$cache_key] += $offset;

        if ($this->cache[$group][$cache_key] < 0) {
            $this->cache[$group][$cache_key] = 0;
        }

        $this->dc_update($cache_key, $this->cache[$group][$cache_key], $group);

        return $this->cache[$group][$cache_key];
    }

    /**
     * Decrements numeric cache item's value.
     *
     * @param int|string $key    the cache key to decrement
     * @param int        $offset Optional. The amount by which to decrement the item's value. Default 1.
     * @param string     $group  Optional. The group the key is in. Default 'default'.
     *
     * @return int|false the item's new value on success, false on failure
     */
    public function decr($key, $offset = 1, $group = 'default')
    {
        if (empty($group)) {
            $group = 'default';
        }

        if (!$this->is_valid_key($key)) {
            return false;
        }

        $cache_key = $this->dc_key($key, $group);

        if (!$this->_exists($cache_key, $group)) {
            return false;
        }

        if (!is_numeric($this->cache[$group][$cache_key])) {
            $this->cache[$group][$cache_key] = 0;
        }

        $offset = (int) $offset;

        $this->cache[$group][$cache_key] -= $offset;

        if ($this->cache[$group][$cache_key] < 0) {
            $this->cache[$group][$cache_key] = 0;
        }

        $this->dc_update($cache_key, $this->cache[$group][$cache_key], $group);

        return $this->cache[$group][$cache_key];
    }

    /**
     * Clears the object cache of all data.
     *
     * @param bool $is_runtime Optional. Only removes cache items from the in-memory runtime cache.
     *
     * @return bool true on success, false on failure
     */
    public function flush($is_runtime = false)
    {
        $this->cache = [];
        $this->precache = [];
        $this->precache_loaded = [];

        return $is_runtime ? true : $this->dc_flush();
    }

    /**
     * Sets the list of global cache groups.
     *
     * @param array $groups list of groups that are global
     */
    public function add_global_groups($groups)
    {
        $groups = (array) $groups;

        $groups = array_fill_keys($groups, true);
        $this->global_groups = array_merge($this->global_groups, $groups);
    }

    /**
     * Switches the internal blog ID.
     *
     * This changes the blog ID used to create keys in blog specific groups.
     *
     * @param int $blog_id blog ID
     */
    public function switch_to_blog($blog_id)
    {
        $blog_id = (int) $blog_id;
        $this->blog_prefix = $this->multisite ? $blog_id.':' : '';
    }

    /**
     * Echoes the stats of the caching.
     *
     * Gives the cache hits, and cache misses. Also prints every cached group,
     * key and the data.
     */
    public function stats()
    {
        $ret = '';
        $ret .= '<p>';
        $ret .= "<strong>Cache Hits:</strong> {$this->cache_hits}<br />";
        $ret .= "<strong>Cache Misses:</strong> {$this->cache_misses}<br />";
        $ret .= '</p>';
        $ret .= '<ul>';
        $total = 0;

        foreach ($this->cache as $group => $cache) {
            $ret .= '<li><strong>Group:</strong> '.esc_html($group).' - ( '.number_format(\strlen(serialize($cache)) / KB_IN_BYTES, 2).'K )</li>';
            $total += \strlen(serialize($cache));
        }

        $ret .= '</ul>';
        $ret .= '<p>total: '.number_format($total / KB_IN_BYTES).'</p>';
        echo $ret;
    }

    /**
     * Sets the list of non persistent groups.
     *
     * @param array $groups list of groups that are to be ignored
     */
    public function add_non_persistent_groups($groups)
    {
        $groups = (array) $groups;
        $this->non_persistent_groups = array_unique(array_merge($this->non_persistent_groups, $groups));
    }

    /**
     * Check if group in non persistent groups.
     *
     * @param bool $group cache group
     */
    protected function is_non_persistent_groups($group)
    {
        return !empty($this->non_persistent_groups) && \in_array($group, $this->non_persistent_groups);
    }

    /**
     * Check if key in non persistent keys.
     *
     * @param bool $key cache key
     */
    protected function is_non_persistent_keys($key)
    {
        return !empty($this->non_persistent_keys) && \in_array($key, $this->non_persistent_keys);
    }

    /**
     * Check if key in non persistent index.
     *
     * @param bool $group cache group
     * @param bool $key   cache key
     */
    protected function is_non_persistent_groupkey($group, $key)
    {
        return !empty($this->non_persistent_groupkey) && \in_array($group.':'.$key, $this->non_persistent_groupkey);
    }

    /**
     * Check if key in non persistent index.
     *
     * @param bool $group cache group
     * @param bool $key   cache key
     */
    protected function is_bypass_precache($group, $key)
    {
        if (!empty($_POST) || ($this->fs()->is_docketcachegroup($group) || $this->fs()->is_transient($group) || $this->is_non_persistent_groups($group))
            // wc: woocommerce/includes/class-wc-cache-helper.php
            || ('wc_cache_' === substr($key, 0, 9) || 'wc_session_id' === $group || @preg_match('@^wc_.*_cache_prefix@', $key))
            // stale cache *last_changed
            || (false !== strpos($key, ':') && @preg_match('@(.*):([a-z0-9]{32}):([0-9\. ]+)$@', $key))) {
            return true;
        }

        return !empty($this->bypass_precache) && \in_array($group.':'.$key, $this->bypass_precache);
    }

    /**
     * is_valid_key.
     */
    private function is_valid_key($key)
    {
        return \is_string($key) || \is_int($key);
    }

    /**
     * is_user_logged_in.
     */
    private function is_user_logged_in()
    {
        return \function_exists('is_user_logged_in') && is_user_logged_in();
    }

    /**
     * is_filtered_groups.
     */
    protected function is_filtered_groups($group, $key)
    {
        if (!\is_array($this->filtered_groups) || !isset($this->filtered_groups[$group])) {
            return false;
        }

        if (false === $this->filtered_groups[$group]) {
            $this->filtered_groups[$group][] = $key;
            $this->filtered_groups[$group] = array_unique($this->filtered_groups[$group]);

            return true;
        }

        if (\in_array($key, $this->filtered_groups[$group])) {
            return true;
        }

        return false;
    }

    /**
     * flush_filtered_groups.
     */
    private function flush_filtered_groups($hook, $args)
    {
        if (!\is_array($this->filtered_groups)) {
            return false;
        }

        foreach ($this->filtered_groups as $group => $keys) {
            if (empty($keys) || !\is_array($keys)) {
                continue;
            }

            $keys = array_unique($keys);
            foreach ($keys as $key) {
                $this->delete($key, $group);
                $this->dc_log('flush', '000000000000-'.$this->item_hash(__FUNCTION__), $group.':'.$key);
            }
        }

        return true;
    }

    /**
     * maybe_expire.
     */
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
                } elseif ('transient' === $group && 'health-check-site-status-result' === $key) {
                    $expire = 0; // to check with is_data_uptodate
                } else {
                    $expire = $maxttl < 604800 ? 604800 : $maxttl; // 7d
                }
            } elseif (\in_array($group, ['options', 'site-options'])) {
                $expire = $maxttl < 1209600 ? 1209600 : $maxttl; // 14d
            } elseif (\in_array($group, ['terms', 'posts', 'post_meta', 'comments'])) {
                $expire = $maxttl < 1209600 ? 1209600 : $maxttl; // 14d

                // wp stale cache
                // prefix:md5hash:microtime
                if (false !== strpos($key, ':') && @preg_match('@(.*):([a-z0-9]{32}):([0-9\. ]+)$@', $key)) {
                    $expire = $maxttl < 345600 ? $maxttl : 345600; // 4d
                }

                // advcpost
                // docketcache-post-timestamp
            } elseif (false !== strpos($group, 'docketcache-post-')) {
                $expire = $maxttl < 345600 ? $maxttl : 345600; // 4d

                // woocommerce stale cache
                // wc_cache_0.72953700 1651592702
            } elseif (false !== strpos($key, 'wc_cache_') && @preg_match('@^wc_cache_([0-9\. ]+)_@', $key)) {
                $expire = $maxttl < 345600 ? $maxttl : 345600; // 4d
            }
        }

        // if 0 let's gc handle it by comparing file mtime.
        return $expire;
    }

    /**
     * get_item_hash.
     */
    private function get_item_hash($file)
    {
        return basename($file, '.php');
    }

    /**
     * item_hash.
     */
    private function item_hash($str, $length = 12)
    {
        if (!$this->is_valid_key($str)) {
            $str = serialize($str);
        }

        if (empty($length)) {
            return md5($str);
        }

        return substr(md5($str), 0, $length);
    }

    /**
     * get_file_path.
     */
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

    /**
     * skip_stats.
     */
    private function skip_stats($group, $key = '')
    {
        if ($this->is_non_persistent_groups($group)) {
            return true;
        }

        return $this->cf()->is_dcfalse('LOG_ALL') && $this->fs()->is_docketcachegroup($group);
    }

    /**
     * is_data_uptodate.
     */
    private function is_data_uptodate($key, $group, $data, $data_serialized = null)
    {
        $file = $this->get_file_path($key, $group);
        $data_p = $this->fs()->cache_get($file);
        if (false === $data_p || !isset($data_p['data'])) {
            return false;
        }

        $data_p = $data_p['data'];
        $data_p_type = \gettype($data_p);
        $data_type = \gettype($data);
        $doserialize = 'array' === $data_type || 'object' === $data_type;

        if ($data_p_type !== $data_type) {
            return false;
        }

        if (!$doserialize && ((false !== strpos($data_type, 'string') && 0 === strcmp($data_p, $data)) || $data_p === $data)) {
            return true;
        }

        // @note 2122: use md5, serialize can be large.
        if ($doserialize) {
            $data_ps = !empty($data_serialized) ? $data_serialized : @serialize($data_p);
            if (@md5($data_serialized) === @md5(@serialize($data))) {
                return true;
            }
        }

        return false;
    }

    /**
     * fs.
     */
    private function fs()
    {
        static $inst;
        if (!\is_object($inst)) {
            $inst = new Nawawi\DocketCache\Filesystem();
        }

        return $inst;
    }

    /**
     * cf.
     */
    private function cf()
    {
        static $inst;
        if (!\is_object($inst)) {
            $inst = new Nawawi\DocketCache\Constans();
        }

        return $inst;
    }

    /**
     * dc_key.
     */
    private function dc_key($key, $group)
    {
        if ($this->multisite && !\array_key_exists($group, $this->global_groups)) {
            $key = $this->blog_prefix.$key;
        }

        return $key;
    }

    /**
     * dc_log.
     */
    private function dc_log($tag, $id, $data)
    {
        if ($this->cf()->is_dcfalse('LOG')) {
            return false;
        }

        if ($this->skip_stats($data)) {
            return false;
        }

        if ($this->cf()->is_dcfalse('LOG_ALL')) {
            if (!\in_array($tag, ['hit', 'miss'])) {
                return false;
            }

            if (false !== strpos($data, 'user') && @preg_match('@^user(s|email|logins|_meta)\:.*@', $data)) {
                return false;
            }
        }

        $caller = '';
        if (!empty($_SERVER['REQUEST_URI'])) {
            $caller = $_SERVER['REQUEST_URI'];
        } elseif ($this->cf()->is_dctrue('WPCLI')) {
            $caller = 'wp-cli';
        }

        if (false !== strpos($caller, '?page=docket-cache')) {
            return false;
        }

        static $duplicate = [];

        $buff = $this->item_hash($tag.$id.$data.$caller);
        if (isset($duplicate[$buff])) {
            return false;
        }

        $duplicate[$buff] = 1;

        return $this->fs()->log($tag, $id, $data, $caller);
    }

    /**
     * dc_flush.
     */
    private function dc_flush()
    {
        $dir = $this->cache_path;
        $is_timeout = false;
        $cnt = $this->fs()->cachedir_flush($dir, false, $is_timeout);
        $logkey = '000000000000-'.$this->item_hash(__FUNCTION__);

        if ($is_timeout) {
            $this->dc_log('err', $logkey, 'Process aborted. Reached maximum execution time. Total cache flushed: '.$cnt);

            return false;
        }

        if (false === $cnt) {
            $this->dc_log('err', $logkey, 'Cache could not be flushed');

            return false;
        }

        if ($cnt > 0) {
            $this->dc_log('flush', $logkey, 'Total cache flushed: '.$cnt);
        }

        return true;
    }

    /**
     * dc_remove.
     */
    private function dc_remove($key, $group)
    {
        $file = $this->get_file_path($key, $group);
        $this->fs()->unlink($file, false);
        $this->dc_log('del', $this->get_item_hash($file), $group.':'.$key);
    }

    /**
     * dc_remove_group.
     */
    public function dc_remove_group($group)
    {
        $total = 0;
        if (!$this->fs()->is_docketcachedir($this->cache_path)) {
            return $total;
        }

        $pattern = '@^'.$this->item_hash($group).'\-([a-z0-9]{12})\.php$@';

        if (\is_array($group) && !empty($group)) {
            $groups = array_map(function ($name) {
                return $this->item_hash($name);
            }, $group);

            $pattern = '@^('.implode('|', $groups).")\-([a-z0-9]{12})\.php$@";
            $group = implode(',', $group);
        }

        $slowdown = 0;
        foreach ($this->fs()->scanfiles($this->cache_path, null, $pattern) as $object) {
            if ($object->isFile()) {
                $fx = $object->getPathName();
                $fn = $object->getFileName();
                $this->fs()->unlink($fx, true);
                $this->dc_log('flush', $this->get_item_hash($fx), $group.':*');
                ++$total;
                unset($this->cache[$group]);
            }

            if ($slowdown > 10) {
                $slowdown = 0;
                usleep(5000);
            }

            ++$slowdown;

            if ($this->max_execution_time > 0 && (microtime(true) - $this->wp_start_timestamp) > $this->max_execution_time) {
                break;
            }
        }

        return $total;
    }

    /**
     * dc_remove_group_match.
     */
    public function dc_remove_group_match($group)
    {
        $total = 0;
        if (!$this->fs()->is_docketcachedir($this->cache_path)) {
            return $total;
        }

        $slowdown = 0;
        $pattern = '@^([a-z0-9]{12})\-([a-z0-9]{12})\.php$@';
        foreach ($this->fs()->scanfiles($this->cache_path, null, $pattern) as $object) {
            if ($object->isFile()) {
                $fx = $object->getPathName();
                $data = $this->fs()->cache_get($fx);
                if (!empty($data) && !empty($data['group'])) {
                    $match = $data['group'];

                    if (\is_array($group) && !empty($group)) {
                        foreach ($group as $grp) {
                            if ($grp === substr($match, 0, \strlen($grp))) {
                                $this->fs()->unlink($fx, true);
                                $this->dc_log('flush', $this->get_item_hash($fx), $match.':*');
                                unset($this->cache[$match]);

                                ++$total;
                            }
                        }
                    } else {
                        if ($group === substr($match, 0, \strlen($group))) {
                            $this->fs()->unlink($fx, true);
                            $this->dc_log('flush', $this->get_item_hash($fx), $match.':*');
                            unset($this->cache[$match]);

                            ++$total;
                        }
                    }
                }
                unset($data);
            }

            if ($slowdown > 10) {
                $slowdown = 0;
                usleep(5000);
            }

            ++$slowdown;

            if ($this->max_execution_time > 0 && (microtime(true) - $this->wp_start_timestamp) > $this->max_execution_time) {
                break;
            }
        }

        return $total;
    }

    /**
     * dc_stalecache_filter.
     */
    private function dc_stalecache_filter($key, $group)
    {
        if ('wc_' === substr($key, 0, 3) && '_cache_prefix' === substr($key, -13)) {
            // get previous usec
            $usec = $this->get('wc_'.$group.'_cache_prefix', $group);
            if ($usec) {
                $val = 'wc_cache:'.$group.':'.$usec;
                $this->stalecache_list[md5($val)] = $val;
            }
        } elseif ('last_changed' === $key) {
            // get previous usec
            $usec = $this->get('last_changed', $group);
            if ($usec) {
                $val = 'last_changed:'.$group.':'.$usec;
                $this->stalecache_list[md5($val)] = $val;
            }

            // can't capture by last_changed.
            // we compare key prefix and timestamp.
        } elseif (false !== strpos($key, ':') && @preg_match('@(.*):([a-z0-9]{32}):([0-9\. ]+)$@', $key, $mm)) {
            $val = 'after:'.$group.':'.$mm[3].':'.$mm[1];
            $this->stalecache_list[md5($val)] = $val;
        }
    }

    /**
     * advcpost_stalecache_se.
     */
    public function add_stalecache($lists)
    {
        if ($this->is_stalecache && !empty($lists) && \is_array($lists)) {
            $this->stalecache_list = array_merge($this->stalecache_list, $lists);
        }
    }

    /**
     * dc_get.
     */
    private function dc_get($key, $group, $is_raw = false)
    {
        $file = $this->get_file_path($key, $group);
        $logkey = $this->get_item_hash($file);

        $data = $this->fs()->cache_get($file);
        if (false === $data) {
            if (!$this->skip_stats($group)) {
                ++$this->cache_misses;

                $this->dc_log('miss', $logkey, $group.':'.$key);
            }

            return false;
        }

        $is_timeout = false;
        if (!empty($data['timeout']) && $this->fs()->valid_timestamp($data['timeout']) && time() >= $data['timeout']) {
            $this->dc_log('exp', $logkey, $group.':'.$key);
            $this->fs()->unlink($file, false);
            $is_timeout = true;
        }

        // incase gc not run
        if (!$is_timeout && !empty($this->cache_maxttl) && !empty($data['timestamp']) && $this->fs()->valid_timestamp($data['timestamp'])) {
            $maxttl = time() - $this->cache_maxttl;
            if ($data['timestamp'] < $maxttl) {
                $this->dc_log('exp', $logkey, $group.':'.$key);
                $this->fs()->unlink($file, true); // true = delete it instead of truncate
            }
        }

        if (!$this->skip_stats($group)) {
            ++$this->cache_hits;
            $this->dc_log('hit', $logkey, $group.':'.$key);
        }

        // If the transient does not exist, does not have a value, or has expired, then the return value will be false.
        if (!empty($data['group']) && $this->fs()->is_transient($data['group']) && ('' === $data['data'] || $is_timeout)) {
            $data['data'] = false;
        }

        // nwdcx_unserialize failed to convert serialize object.
        // we unserialize it here to get the object.
        if (!empty($data['data'])) {
            // *_serialize set at dc_save, to load it faster
            if (false !== strpos($data['type'], '_serialize')) {
                $data['data'] = unserialize($data['data']);
            } elseif ('string' === $data['type'] && \function_exists('maybe_unserialize')) {
                // old cache data
                $data['data'] = maybe_unserialize($data['data']);
            }
        }
        clearstatcache();

        return $is_raw ? $data : $data['data'];
    }

    /**
     * dc_code.
     */
    private function dc_code($file, $arr)
    {
        $logkey = $this->get_item_hash($file);
        $logpref = __FUNCTION__.'():';

        $data = $this->fs()->export_var($arr, $error);
        if (false === $data) {
            $this->dc_log('err', $logkey, $logpref.' Failed to export var -> '.$error);

            return false;
        }

        $code = $this->fs()->code_stub($data);
        $stat = $this->fs()->dump($file, $code, false); // 3rd param = validate

        if (false === $stat) {
            return false;
        }

        if (-1 === $stat) {
            $this->dc_log('err', $logkey, $logpref.' Failed to write');

            return false;
        }

        // remove lock
        $this->fs()->validate_fatal_error_file($file);

        return $stat;
    }

    /**
     * dc_save.
     */
    private function dc_save($cache_key, $data, $group = 'default', $expire = 0, $key = '')
    {
        if (wp_suspend_cache_addition()) {
            return false;
        }

        $logkey = $this->item_hash($group).'-'.$this->item_hash($cache_key);
        $logpref = __FUNCTION__.'():';

        // skip save to disk, return true;
        if ('' === $data && $this->fs()->is_transient($group)) {
            if ($this->is_dev) {
                $this->dc_log('debug', $logkey, $group.':'.$cache_key.' '.$logpref.' Data empty');
            }

            return true;
        }

        if (!$this->fs()->mkdir_p($this->cache_path)) {
            return false;
        }

        @$this->fs()->placeholder($this->cache_path);

        $file = $this->get_file_path($cache_key, $group);

        // chunk dir
        if ($this->cf()->is_dctrue('CHUNKCACHEDIR') && !$this->fs()->mkdir_p(\dirname($file))) {
            return false;
        }

        // if $expire is larger than 0, convert it to timestamp
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

        // abort if object too large
        $data_serialized = serialize($data);
        $len = \strlen(serialize($data_serialized));
        if ($len >= $this->cache_maxsize) {
            $this->dc_log('err', $logkey, $group.':'.$cache_key.' '.$logpref.' Object too large -> '.$len.'/'.$this->cache_maxsize);

            return false;
        }

        // since timeout set to timestamp.
        if (0 === $expire && !empty($key) && @is_file($file) && $this->is_data_uptodate($key, $group, $data, $data_serialized)) {
            if ($this->is_dev) {
                $this->dc_log('debug', $logkey, $group.':'.$cache_key.' '.$logpref.' No changes');
            }

            return false;
        }

        $meta = [];
        $meta['timestamp'] = time();

        if ($this->multisite) {
            // try to avoid error-prone
            // in rare condition, get_current_network_id dependencies not load properly.
            try {
                $meta['network_id'] = get_current_network_id();
            } catch (\Throwable $e) {
                $meta['network_id'] = 0;
            }
        }

        $final_type = \gettype($data);
        if ('string' === $final_type && nwdcx_serialized($data)) {
            $final_type = 'string_serialize';
        } elseif ('array' === $final_type) {
            // may lead to __PHP_Incomplete_Class
            // headers => Requests_Utility_CaseInsensitiveDictionary Object
            if (!empty($data['headers']) && \is_object($data['headers']) && false !== strpos(var_export($data['headers'], 1), 'Requests_Utility_CaseInsensitiveDictionary::__set_state')) {
                $data = @serialize($data);
                if (nwdcx_serialized($data)) {
                    $final_type = 'array_serialize';
                }
            }
        }

        $meta['site_id'] = get_current_blog_id();
        $meta['group'] = $group;
        $meta['key'] = $cache_key;
        $meta['type'] = $final_type;

        // if 0 let gc handle it by comparing file mtime
        // and maxttl constants.
        $meta['timeout'] = $timeout;

        $meta['data'] = $data;

        if (true === $this->dc_code($file, $meta)) {
            if ($this->is_dev) {
                $this->dc_log('debug', $logkey, $group.':'.$cache_key.' '.$logpref.' Storing to disk');
            }

            return true;
        }

        return false;
    }

    /**
     * dc_update.
     */
    private function dc_update($cache_key, $data, $group)
    {
        $meta = $this->dc_get($cache_key, $group, true);
        if (false === $meta || !\is_array($meta) || !isset($meta['data'])) {
            return false;
        }

        $file = $this->get_file_path($cache_key, $group);
        $meta['data'] = $data;

        if (true === $this->dc_code($file, $meta)) {
            return true;
        }

        return false;
    }

    /**
     * dc_precache_load.
     */
    private function dc_precache_load($hash)
    {
        static $is_done = false;
        $logkey = $this->item_hash('docketcache-precache').'-'.$this->item_hash(__FUNCTION__);
        $logpref = __FUNCTION__.'():';

        if ($is_done) {
            if ($this->is_dev) {
                $this->dc_log('debug', $logkey, $logpref.' Precache Ignored: Already loaded');
            }

            return;
        }

        $cached = [];
        $group = 'docketcache-precache';
        $keys = $this->get($hash, $group);

        if (empty($keys) || !\is_array($keys)) {
            return;
        }

        if ($this->is_dev) {
            $this->dc_log('debug', $logkey, $logpref.' Precache Load: Start');
        }

        $this->precache_loaded[$hash] = $keys;

        $slowdown = 0;
        $cnt_max = 0;

        foreach ($keys as $cache_group => $arr) {
            foreach ($arr as $cache_key) {
                if ($cnt_max >= $this->precache_maxlist) {
                    break 2;
                }

                if (!isset($cached[$cache_key.$cache_group]) && false !== $this->get($cache_key, $cache_group)) {
                    $cached[$cache_key.$cache_group] = 1;
                }

                ++$cnt_max;

                if ($slowdown > 10) {
                    $slowdown = 0;
                    usleep(1000);
                }

                ++$slowdown;

                if ($this->max_execution_time > 0 && (microtime(true) - $this->wp_start_timestamp) > $this->max_execution_time) {
                    break 2;
                }
            }
        }

        if ($this->is_dev) {
            $this->dc_log('debug', $logkey, $logpref.' Precache Load: End -> '.\count($cached));
        }

        unset($keys, $cached);
        $is_done = true;
    }

    /**
     * dc_precache_set.
     */
    private function dc_precache_set($hash)
    {
        if (empty($this->precache) || !\is_array($this->precache)) {
            return;
        }

        $group = 'docketcache-precache';
        $data = [];
        $slowdown = 0;
        $cnt_max = 0;

        $logkey = $this->item_hash('docketcache-precache').'-'.$this->item_hash(__FUNCTION__);
        $logpref = __FUNCTION__.'():';

        if ($this->is_dev) {
            $this->dc_log('debug', $logkey, $logpref.' Precache Set: Start');
        }

        foreach ($this->precache as $cache_group => $cache_keys) {
            if ($cnt_max >= $this->precache_maxlist) {
                break;
            }

            if ($cache_group !== $group) {
                $cache_keys = array_keys($cache_keys);
                $data[$cache_group] = $cache_keys;
            }

            ++$cnt_max;

            if ($slowdown > 10) {
                $slowdown = 0;
                usleep(100);
            }

            ++$slowdown;

            if ($this->max_execution_time > 0 && (microtime(true) - $this->wp_start_timestamp) > $this->max_execution_time) {
                // bypass, maybe data too big
                $data = [];
                $this->delete($hash);
                break;
            }
        }

        if ($this->is_dev) {
            $this->dc_log('debug', $logkey, $logpref.' Precache Set: End -> '.\count($data));
        }

        if (!empty($data)) {
            if (!empty($this->precache_loaded) && md5(serialize($this->precache_loaded[$hash])) === md5(serialize($data))) {
                if ($this->is_dev) {
                    $this->dc_log('debug', $logkey, $logpref.' '.$hash.' No changes');
                }

                return;
            }

            $this->set($hash, $data, $group, 86400); // 1d
        }

        unset($data, $hash);
    }

    /**
     * dc_precache.
     */
    private function dc_precache()
    {
        if (!empty($_POST) || empty($_SERVER['REQUEST_URI']) || $this->cf()->is_dctrue('WPCLI')) {
            return;
        }

        $logkey = $this->item_hash('docketcache-precache').'-'.$this->item_hash(__FUNCTION__);
        $logpref = __FUNCTION__.'():';

        $req_uri = $_SERVER['REQUEST_URI'];
        $dostrip = !empty($_SERVER['QUERY_STRING']);

        $intersect_key = [
            'docketcache_ping' => 1,
            'doing_wp_cron' => 1,
            'wc-ajax' => 1,
            '_fs_blog_admin' => 1,
            'action' => 1,
            'message' => 1,
        ];

        if ($dostrip && !empty($_GET) && array_intersect_key($intersect_key, $_GET)) {
            $this->dc_log('info', $logkey, $logpref.' Bypass GET key');

            return;
        }

        if (false !== strpos($req_uri, '/wp-json/') || false !== strpos($req_uri, '/wp-admin/admin-ajax.php') || false !== strpos($req_uri, '/xmlrpc.php') || false !== strpos($req_uri, '/wp-cron.php') || false !== strpos($req_uri, '/robots.txt') || false !== strpos($req_uri, '/favicon.ico')) {
            $this->dc_log('info', $logkey, $logpref.' Bypass Request');

            return;
        }

        $req_host = !empty($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
        if ('localhost' !== $req_host) {
            $req_host = nwdcx_fixhost($req_host);
        }

        if ($dostrip && $this->is_user_logged_in() && false !== strpos($req_uri, '.php?') && false !== strpos($req_uri, '/wp-admin/') && @preg_match('@/wp-admin/(network/)?.*?\.php\?.*?@', $req_uri)) {
            $dostrip = false;
        }

        // without pretty permalink
        if ($dostrip && !empty($_GET) && empty($_GET['s']) && empty($_GET['q']) && (false !== strpos($req_uri, '/?p=') || false !== strpos($req_uri, '/?cat=') || false !== strpos($req_uri, '/?m=') || false !== strpos($req_uri, '/?page_id=') || false !== strpos($req_uri, '/index.php/')) && !@nwdcx_optget('permalink_structure')) {
            $dostrip = false;
        }

        if ($dostrip) {
            $req_uri = strtok($req_uri, '?#');
        }

        if (empty($req_host) || empty($req_uri)) {
            return;
        }

        $this->precache_hashkey = $this->item_hash($req_host.$req_uri);

        $this->dc_precache_load($this->precache_hashkey);
    }

    /**
     * dc_close.
     * reference:
     *  wp_cache_close()
     *  wp-includes/load.php -> shutdown_action_hook().
     */
    public function dc_close()
    {
        $this->fs()->close_buffer();
        static $is_done = false;

        if (!$is_done) {
            if ($this->is_precache && !empty($this->precache_hashkey) && $this->fs()->close_buffer()) {
                $this->dc_precache_set($this->precache_hashkey);
            }

            if ($this->is_stalecache && !empty($this->stalecache_list)) {
                $this->add('items', $this->stalecache_list, 'docketcache-stalecache', 3600);
            }

            $is_done = true;
        }
    }

    /**
     * dc_init.
     */
    private function dc_init()
    {
        $this->wp_start_timestamp = \defined('WP_START_TIMESTAMP') ? WP_START_TIMESTAMP : microtime(true);
        $this->max_execution_time = $this->fs()->get_max_execution_time();
        $this->is_dev = $this->cf()->is_dctrue('DEV');

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

        if ($this->cf()->is_dcarray('GLOBAL_GROUPS', $dcvalue)) {
            $this->add_global_groups($dcvalue);
        }

        if ($this->cf()->is_dcarray('IGNORED_GROUPS', $dcvalue)) {
            $this->non_persistent_groups = $dcvalue;
        }

        if ($this->cf()->is_dcarray('IGNORED_KEYS', $dcvalue)) {
            $this->non_persistent_keys = $dcvalue;
        }

        if ($this->cf()->is_dcarray('FILTERED_GROUPS', $dcvalue)) {
            $this->filtered_groups = $dcvalue;
        }

        if ($this->cf()->is_dcarray('IGNORED_GROUPKEY', $dcvalue)) {
            $this->non_persistent_groupkey = $dcvalue;
        }

        if ($this->cf()->is_dcarray('IGNORED_PRECACHE', $dcvalue)) {
            $this->bypass_precache = $dcvalue;
        }

        $this->cache_path = $this->fs()->define_cache_path($this->cf()->dcvalue('PATH'));
        if ($this->multisite) {
            $this->cache_path = nwdcx_network_dirpath($this->cache_path);
        }

        if ($this->cf()->is_dctrue('WPOPTALOAD')) {
            $this->fs()->optimize_alloptions();
        }

        add_filter(
            'pre_cache_alloptions',
            function ($alloptions) {
                if (isset($alloptions['cron'])) {
                    unset($alloptions['cron']);
                }

                if (isset($alloptions['litespeed_messages'])) {
                    unset($alloptions['litespeed_messages']);
                }

                if (isset($alloptions['litespeed.admin_display.messages'])) {
                    unset($alloptions['litespeed.admin_display.messages']);
                }

                return $alloptions;
            },
            \PHP_INT_MAX
        );

        // litespeed admin notice
        add_action(
            'litespeed_purged_all',
            function () {
                $this->delete('alloptions', 'options');
                $this->delete('litespeed_messages', 'options');
                $this->delete('litespeed.admin_display.messages', 'options');
            },
            \PHP_INT_MAX
        );

        add_action(
            'all_admin_notices',
            function () {
                if (\function_exists('run_litespeed_cache')) {
                    $this->delete('litespeed_messages', 'options');
                    $this->delete('litespeed.admin_display.messages', 'options');
                }
            },
            \PHP_INT_MAX
        );

        foreach (['added', 'updated', 'deleted'] as $prefix) {
            add_action(
                $prefix.'_option',
                function ($option) {
                    if (!wp_installing()) {
                        $alloptions = wp_load_alloptions();
                        if (isset($alloptions[$option])) {
                            add_action(
                                'shutdown',
                                function () {
                                    $this->fs()->close_buffer();
                                    $this->delete('alloptions', 'options');
                                },
                                \PHP_INT_MAX - 1
                            );
                        }
                        unset($alloptions);
                    }
                },
                \PHP_INT_MAX
            );
        }

        foreach (['activate', 'deactivate'] as $prefix) {
            add_action(
                $prefix.'_plugin',
                function ($plugin, $network) {
                    if ($this->multisite) {
                        add_action(
                            'shutdown',
                            function () {
                                $this->fs()->close_buffer();
                                $this->delete(get_current_network_id().':active_sitewide_plugins', 'site-options');
                            },
                            \PHP_INT_MAX - 1
                        );
                    }
                    add_action(
                        'shutdown',
                        function () {
                            $this->fs()->close_buffer();
                            $this->delete('uninstall_plugins', 'options');
                        },
                        \PHP_INT_MAX - 1
                    );
                },
                \PHP_INT_MAX,
                2
            );
        }

        // filtered groups hooks
        if (\is_array($this->filtered_groups)) {
            add_action(
                'save_post',
                function ($post_id, $post, $update) {
                    $this->flush_filtered_groups('save_post', [$post_id, $post, $update]);
                },
                \PHP_INT_MIN,
                3
            );

            add_action(
                'edit_post',
                function ($post_id, $post) {
                    $this->flush_filtered_groups('edit_post', [$post_id, $post]);
                },
                \PHP_INT_MIN,
                2
            );

            add_action(
                'delete_post',
                function ($post_id) {
                    $this->flush_filtered_groups('delete_post', [$post_id]);
                },
                \PHP_INT_MIN
            );
        }

        if ($this->cf()->is_dctrue('OPTWPQUERY')) {
            add_action(
                'pre_get_posts',
                function (&$args) {
                    if (\is_object($args)) {
                        $args->no_found_rows = true;
                        $args->order = 'ASC';
                    } elseif (\is_array($args)) {
                        $args['no_found_rows'] = true;
                        $args['order'] = 'ASC';
                    }
                },
                \PHP_INT_MIN
            );

            add_action(
                'parse_query',
                function (&$args) {
                    if (\is_object($args)) {
                        $args->no_found_rows = true;
                        $args->order = 'ASC';
                    } elseif (\is_array($args)) {
                        $args['no_found_rows'] = true;
                        $args['order'] = 'ASC';
                    }
                },
                \PHP_INT_MIN
            );

            add_action(
                'pre_get_users',
                function ($wpq) {
                    if (nwdcx_wpdb($wpdb) && !empty($wpq->query_vars['count_total'])) {
                        $wpq->query_vars['count_total'] = false;
                        $wpq->query_vars['nwdcx_count_total'] = true;
                    }
                },
                \PHP_INT_MIN
            );

            add_action(
                'pre_user_query',
                function ($wpq) {
                    if (nwdcx_wpdb($wpdb) && !empty($wpq->query_vars['nwdcx_count_total'])) {
                        unset($wpq->query_vars['nwdcx_count_total']);
                        $sql = "SELECT COUNT(*) {$wpq->query_from} {$wpq->query_where}";
                        $wpq->total_users = $wpdb->get_var($sql);
                    }
                },
                \PHP_INT_MIN
            );
        }

        // html comment
        $this->add_signature = false;
        if ($this->cf()->is_dctrue('SIGNATURE')) {
            add_action(
                'wp_head',
                function () {
                    if (!$this->is_user_logged_in()) {
                        $this->add_signature = true;
                    }
                },
                \PHP_INT_MIN
            );

            add_action(
                'shutdown',
                function () {
                    if ($this->add_signature && !$this->is_user_logged_in()) {
                        echo apply_filters('docketcache/filter/signature/htmlfooter', "\n<!-- Performance optimized by Docket Cache: https://wordpress.org/plugins/docket-cache -->\n");
                        $this->fs()->close_buffer();
                    }
                },
                \PHP_INT_MAX
            );
        }

        // stalecache
        $this->is_stalecache = $this->cf()->is_dctrue('FLUSH_STALECACHE');

        // load precache
        $this->is_precache = $this->cf()->is_dctrue('PRECACHE');
        if ($this->is_precache) {
            $this->precache_maxlist = (int) $this->cf()->dcvalue('PRECACHE_MAXLIST');
            $this->dc_precache();
        }

        // maxfile
        $maxfile = (int) $this->fs()->sanitize_maxfile($this->cf()->dcvalue('MAXFILE'));
        $numfile = (int) $this->get('numfile', 'docketcache-gc');
        $numfile = $numfile > 0 ? $numfile : 0;
        if ($numfile > $maxfile) {
            wp_suspend_cache_addition(true);
        }
    }
}

/**
 * Sets up Object Cache Global and assigns it.
 *
 * @global WP_Object_Cache $wp_object_cache
 */
function wp_cache_init()
{
    global $wp_object_cache;
    if (!($wp_object_cache instanceof WP_Object_Cache)) {
        $wp_object_cache = new WP_Object_Cache();
    }
}

/**
 * @see WP_Object_Cache::add()
 */
function wp_cache_add($key, $data, $group = '', $expire = 0)
{
    global $wp_object_cache;

    return $wp_object_cache->add($key, $data, $group, (int) $expire);
}

/**
 * @see WP_Object_Cache::add_multiple()
 */
function wp_cache_add_multiple(array $data, $group = '', $expire = 0)
{
    global $wp_object_cache;

    return $wp_object_cache->add_multiple($data, $group, $expire);
}

/**
 * @see WP_Object_Cache::replace()
 */
function wp_cache_replace($key, $data, $group = '', $expire = 0)
{
    global $wp_object_cache;

    return $wp_object_cache->replace($key, $data, $group, (int) $expire);
}

/**
 * @see WP_Object_Cache::set()
 */
function wp_cache_set($key, $data, $group = '', $expire = 0)
{
    global $wp_object_cache;

    return $wp_object_cache->set($key, $data, $group, (int) $expire);
}

/**
 * @see WP_Object_Cache::set_multiple()
 */
function wp_cache_set_multiple(array $data, $group = '', $expire = 0)
{
    global $wp_object_cache;

    return $wp_object_cache->set_multiple($data, $group, $expire);
}

/**
 * @see WP_Object_Cache::get()
 */
function wp_cache_get($key, $group = '', $force = false, &$found = null)
{
    global $wp_object_cache;

    return $wp_object_cache->get($key, $group, $force, $found);
}

/**
 * @see WP_Object_Cache::get_multiple()
 */
function wp_cache_get_multiple(array $keys, $group = '', $force = false)
{
    global $wp_object_cache;

    return $wp_object_cache->get_multiple($keys, $group, $force);
}

/**
 * @see WP_Object_Cache::delete()
 */
function wp_cache_delete($key, $group = '')
{
    global $wp_object_cache;

    return $wp_object_cache->delete($key, $group);
}

/**
 * @see WP_Object_Cache::delete_multiple()
 */
function wp_cache_delete_multiple(array $keys, $group = '')
{
    global $wp_object_cache;

    return $wp_object_cache->delete_multiple($keys, $group);
}

/**
 * @see WP_Object_Cache::incr()
 */
function wp_cache_incr($key, $offset = 1, $group = '')
{
    global $wp_object_cache;

    return $wp_object_cache->incr($key, $offset, $group);
}

/**
 * @see WP_Object_Cache::decr()
 */
function wp_cache_decr($key, $offset = 1, $group = '')
{
    global $wp_object_cache;

    return $wp_object_cache->decr($key, $offset, $group);
}

/**
 * @see WP_Object_Cache::flush()
 */
function wp_cache_flush()
{
    global $wp_object_cache;

    return $wp_object_cache->flush();
}

/**
 * @see WP_Object_Cache::flush()
 */
function wp_cache_flush_runtime()
{
    global $wp_object_cache;

    return $wp_object_cache->flush(true);
}

/**
 * @see WP_Object_Cache::dc_close()
 */
function wp_cache_close()
{
    global $wp_object_cache;

    $wp_object_cache->dc_close();

    return true;
}

/**
 * @see WP_Object_Cache::add_non_persistent_groups()
 */
function wp_cache_add_non_persistent_groups($groups)
{
    global $wp_object_cache;
    $wp_object_cache->add_non_persistent_groups($groups);
}

/**
 * @see WP_Object_Cache::switch_to_blog()
 */
function wp_cache_switch_to_blog($blog_id)
{
    global $wp_object_cache;

    $wp_object_cache->switch_to_blog($blog_id);
}

/**
 * @see WP_Object_Cache::add_global_groups()
 */
function wp_cache_add_global_groups($groups)
{
    global $wp_object_cache;

    $wp_object_cache->add_global_groups($groups);
}

/**
 * @see WP_Object_Cache::stats()
 */
function wp_cache_stats()
{
    global $wp_object_cache;
    $wp_object_cache->stats();
}

/**
 * @see WP_Object_Cache::dc_remove_group()
 */
function wp_cache_flush_group($group = 'default')
{
    global $wp_object_cache;

    return $wp_object_cache->dc_remove_group($group);
}

/**
 * @see WP_Object_Cache::dc_remove_group_match()
 */
function wp_cache_flush_group_match($group = 'default')
{
    global $wp_object_cache;

    return $wp_object_cache->dc_remove_group_match($group);
}

/**
 * @see WP_Object_Cache::add_stalecache()
 */
function wp_cache_add_stalecache($lists)
{
    global $wp_object_cache;

    return $wp_object_cache->add_stalecache($lists);
}
