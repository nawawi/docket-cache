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
#[AllowDynamicProperties]
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
     * The amount of times the cached data was fetched from the cache file.
     *
     * @var int
     */
    public $persistent_cache_hits = 0;

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
    private $bypass_precache = [];

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
     * Holds the value of Network Id.
     *
     * @var bool
     */
    private $network_id = 1;

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
     * Precache max keys.
     *
     * @var int
     */
    private $precache_maxkey = 20;

    /**
     * Precache max groups.
     *
     * @var int
     */
    private $precache_maxgroup = 20;

    /**
     * Precache max cache file (url).
     *
     * @var int
     */
    private $precache_maxfile = 100;

    /**
     * Maximum cache file.
     *
     * @var int
     */
    private $maxfile = 50000;

    /**
     * Check cache file limit in real-time.
     *
     * @var bool
     */
    private $maxfile_livecheck = true;

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
     * Dev mode.
     *
     * @var bool
     */
    private $is_dev = false;

    /**
     * Ignore stale cache.
     *
     * @var bool
     */
    private $ignore_stalecache = false;

    /**
     * Ignore empty cache.
     *
     * @var bool
     */
    private $ignore_emptycache = false;

    /**
     * Store Transients in DB.
     *
     * @var bool
     */
    private $use_transientdb = false;

    /**
     * List of transient exclude from store in DB.
     *
     * @var array
     */
    private $bypass_transientdb = [];

    /**
     * Sets up object properties.
     */
    public function __construct()
    {
        $this->multisite = \function_exists('is_multisite') && is_multisite();
        $this->blog_prefix = $this->switch_to_blog(get_current_blog_id());
        $this->network_id = (int) nwdcx_network_id();
        $this->dc_init();
    }

    /**
     * Makes private properties readable for backward compatibility.
     *
     * @since 4.0.0
     *
     * @param string $name property to get
     *
     * @return mixed property
     */
    public function __get($name)
    {
        return $this->$name;
    }

    /**
     * Makes private properties settable for backward compatibility.
     *
     * @since 4.0.0
     *
     * @param string $name  property to set
     * @param mixed  $value property value
     *
     * @return mixed newly-set property
     */
    public function __set($name, $value)
    {
        return $this->$name = $value;
    }

    /**
     * Makes private properties checkable for backward compatibility.
     *
     * @since 4.0.0
     *
     * @param string $name property to check if set
     *
     * @return bool whether the property is set
     */
    public function __isset($name)
    {
        return isset($this->$name);
    }

    /**
     * Makes private properties un-settable for backward compatibility.
     *
     * @since 4.0.0
     *
     * @param string $name property to unset
     */
    public function __unset($name)
    {
        unset($this->$name);
    }

    /**
     * Serves as a utility function to determine whether a key exists in the cache.
     *
     * @param int|string $key   cache key to check for existence
     * @param string     $group cache group for the key existence check
     *
     * @return bool whether the key exists in the cache for the given group
     */
    protected function _exists($key, $group, $force = false)
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

        $is_exists = !$force && !empty($this->cache) && isset($this->cache[$group]) && (isset($this->cache[$group][$key]) || \array_key_exists($key, $this->cache[$group]));

        if (!$is_exists && !$this->is_non_persistent_groups($group) && !$this->is_non_persistent_keys($key) && !$this->is_non_persistent_groupkey($group, $key) && !$this->is_stalecache_ignored($key, $group)) {
            $data = $this->dc_get($key, $group, false, $codestub_false);
            if (false !== $data) {
                $is_exists = true;
                $this->cache[$group][$key] = $data;

                if ($this->is_precache && !$codestub_false && !$this->fs()->suspend_cache_write() && !$this->is_bypass_precache($group, $key)) {
                    if (empty($this->precache[$group])) {
                        $this->precache[$group][$key] = 1;
                    } elseif (\count($this->precache[$group]) < $this->precache_maxkey && \count($this->precache) < $this->precache_maxgroup) {
                        $this->precache[$group][$key] = 1;
                    }
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

        if (!$this->is_valid_key($key)) {
            return false;
        }

        if (empty($group)) {
            $group = 'default';
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
        if (!$this->is_valid_key($key)) {
            return false;
        }

        if (empty($group)) {
            $group = 'default';
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
        if (!$this->is_valid_key($key)) {
            return false;
        }

        if (empty($group)) {
            $group = 'default';
        }

        $cache_key = $this->dc_key($key, $group);

        if (\is_object($data)) {
            $data = clone $data;
        }

        $this->cache[$group][$cache_key] = $data;

        // suspend new cache
        if ($this->fs()->suspend_cache_write() && !is_file($this->get_file_path($cache_key, $group))) {
            return true;
        }

        if ((!$this->is_non_persistent_groups($group) && !$this->is_non_persistent_keys($key) && !$this->is_non_persistent_groupkey($group, $key) && !$this->is_stalecache_ignored($key, $group)) || $this->is_filtered_groups($group, $key)) {
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
    public function get($key, $group = 'default', $force = false, &$found = null, $doing_precache = false)
    {
        if (!$this->is_valid_key($key)) {
            return false;
        }

        if (empty($group)) {
            $group = 'default';
        }

        $cache_key = $this->dc_key($key, $group);

        if ($this->_exists($cache_key, $group, $force)) {
            if (!$doing_precache) {
                ++$this->cache_hits;
            }
            $found = true;
            if (\is_object($this->cache[$group][$cache_key])) {
                return clone $this->cache[$group][$cache_key];
            }

            return $this->cache[$group][$cache_key];
        }

        $found = false;
        if (!$doing_precache) {
            ++$this->cache_misses;
        }

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
        if (!$this->is_valid_key($key)) {
            return false;
        }

        if (empty($group)) {
            $group = 'default';
        }

        $key = $this->dc_key($key, $group);

        unset($this->cache[$group][$key]);
        unset($this->precache[$group][$key]);

        return $this->dc_remove($key, $group);
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
        if (!$this->is_valid_key($key)) {
            return false;
        }

        if (empty($group)) {
            $group = 'default';
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
        if (!$this->is_valid_key($key)) {
            return false;
        }

        if (empty($group)) {
            $group = 'default';
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
        $ret .= "<strong>Persistent Cache Hits:</strong> {$this->persistent_cache_hits}<br />";
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

        $groups = array_fill_keys($groups, true);
        $this->non_persistent_groups = array_merge($this->non_persistent_groups, $groups);
    }

    /**
     * Check if group in non persistent groups.
     *
     * @param bool $group cache group
     */
    protected function is_non_persistent_groups($group)
    {
        return !empty($this->non_persistent_groups) && \array_key_exists($group, $this->non_persistent_groups);
    }

    /**
     * Sets the list of non persistent keys.
     *
     * @param array $keys list of keys that are to be ignored
     */
    public function add_non_persistent_keys($keys)
    {
        $keys = (array) $keys;

        $keys = array_fill_keys($keys, true);
        $this->non_persistent_keys = array_merge($this->non_persistent_keys, $keys);
    }

    /**
     * Check if key in non persistent keys.
     *
     * @param bool $key cache key
     */
    protected function is_non_persistent_keys($key)
    {
        return !empty($this->non_persistent_keys) && \array_key_exists($key, $this->non_persistent_keys);
    }

    /**
     * Check if key in non persistent index.
     *
     * @param bool $group cache group
     * @param bool $key   cache key
     */
    protected function is_non_persistent_groupkey($group, $key)
    {
        if (!empty($this->non_persistent_groupkey) && !empty($this->non_persistent_groupkey[$group])) {
            $bypass_data = $this->non_persistent_groupkey[$group];

            if (\is_array($bypass_data)) {
                return false !== array_search($key, $bypass_data);
            }

            if ($key === $bypass_data) {
                return true;
            }
        }

        return false;
    }

    /**
     * Bypass preache.
     *
     * @param bool $group cache group
     * @param bool $key   cache key
     */
    private function is_bypass_precache($group, $key)
    {
        if ($this->fs()->is_docketcachegroup($group, $key) || $this->fs()->is_transient($group) || $this->has_stalecache($key, $group)) {
            return true;
        }

        if ('gbmedia-cpttables' === $group || 'doing_cron' === $key || preg_match('@^(\d+|[0-9a-f]{32})$@', $key)) {
            return true;
        }

        if (!empty($this->bypass_precache) && !empty($this->bypass_precache[$group])) {
            $bypass_data = $this->bypass_precache[$group];

            if (\is_array($bypass_data)) {
                return false !== array_search($key, $bypass_data);
            }

            if ($key === $bypass_data) {
                return true;
            }
        }

        return false;
    }

    /**
     * is_valid_key.
     */
    private function is_valid_key($key)
    {
        if (\is_int($key)) {
            return true;
        }

        if (\is_string($key) && '' !== trim($key)) {
            return true;
        }

        if (!\function_exists('__') && \function_exists('wp_load_translations_early')) {
            wp_load_translations_early();
        }

        return false;
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
            if ($this->fs()->is_transient($group)) {
                if ('site-transient' === $group && \in_array($key, ['update_plugins', 'update_themes', 'update_core', '_woocommerce_helper_updates'])) {
                    $expire = $maxttl < 2419200 ? 2419200 : $maxttl; // 28d
                } else {
                    // $expire = $maxttl < 604800 ? 604800 : $maxttl; // 7d
                    $expire = $maxttl < 86400 ? $maxttl : 86400; // 1d
                }
            } elseif ($this->fs()->is_wp_options($group)) {
                $expire = $maxttl < 1209600 ? 1209600 : $maxttl; // 14d
            } elseif (\in_array($group, ['terms', 'posts', 'post_meta', 'comments', 'comment_feed', 'sites', 'networks'])) {
                $expire = $maxttl < 1209600 ? 1209600 : $maxttl; // 14d
            }

            // wp stale cache
            // group-queries: wp >= 6.3
            elseif ($this->fs()->is_wp_cache_group_queries($group)) {
                $expire = $maxttl < 86400 ? $maxttl : 86400; // 1d
            }

            // wp stale cache
            // prefix:md5hash:microtime
            // wp_query|get_terms|get_comments|comment_feed|get_sites|get_network_ids|get_page_by_path|other?
            elseif (false !== strpos($key, ':') && @preg_match('@^([a-zA-Z0-9\._-]+):([0-9a-f]{32}):([0-9\. ]+)$@', $key)) {
                $expire = $maxttl < 86400 ? $maxttl : 86400; // 1d
            }

            // wp stale cache
            // cache timestamp
            elseif ('last_changed' === $key) {
                $expire = $maxttl < 2419200 ? 2419200 : $maxttl; // 28d
            }

            // advcpost
            // docketcache-post-(found|media|timestamp)
            elseif (false !== strpos($group, 'docketcache-post-')) {
                $expire = $maxttl < 86400 ? $maxttl : 86400; // 1d
            }

            // advcpost
            // docketcache-post-media
            elseif ('docketcache-post-media' === $group) {
                $expire = $maxttl < 2419200 ? 2419200 : $maxttl; // 28d
            }

            // advcpost
            // cache timestamp
            elseif ('docketcache-post' === $group && 'cache_incr' === $key) {
                $expire = $maxttl < 2419200 ? 2419200 : $maxttl; // 28d
            }

            // woocommerce stale cache
            // cache prefix
            elseif (false !== strpos($key, '_cache_prefix') && @preg_match('@^wc_(.*?)_cache_prefix$@', $key)) {
                $expire = $maxttl < 2419200 ? 2419200 : $maxttl; // 28d
            }

            // woocommerce stale cache
            // wc_cache_0.72953700 1651592702
            elseif (false !== strpos($key, 'wc_cache_') && @preg_match('@^wc_cache_([0-9\. ]+)_@', $key)) {
                $expire = $maxttl < 86400 ? $maxttl : 86400; // 1d
            } elseif (false !== strpos($group, 'wc_cache_') && @preg_match('@^wc_cache_([0-9\. ]+)_@', $group)) {
                $expire = $maxttl < 86400 ? $maxttl : 86400; // 1d
            }

            // common cache
            elseif (preg_match('@[0-9a-f]{32}@', $key)) {
                $expire = $maxttl < 86400 ? $maxttl : 86400; // 1d
            }

            // else
            else {
                $expire = $maxttl;
            }
        }

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

        if ($this->cf()->is_dcfalse('CHUNKCACHEDIR', true)) {
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
        if ($this->is_non_persistent_groups($group) || (!empty($keys) && $this->is_non_persistent_keys($key))) {
            return true;
        }

        return $this->cf()->is_dcfalse('LOG_ALL') && $this->fs()->is_docketcachegroup($group);
    }

    /**
     * has_stalecache.
     */
    private function has_stalecache($key, $group = '')
    {
        if ($this->fs()->is_wp_cache_group_queries($group)) {
            return true;
        }

        if ('wc_' === substr($key, 0, 3) && '_cache_prefix' === substr($key, -13)) {
            return true;
        }

        if ('wc_cache_' === substr($key, 0, 9) || 'wc_cache_' === substr($group, 0, 9)) {
            return true;
        }

        if (false !== strpos($key, ':') && @preg_match('@^([a-zA-Z0-9\._-]+):([0-9a-f]{32}):([0-9\. ]+)$@', $key)) {
            return true;
        }

        if (false !== strpos($group, 'docketcache-post-') && preg_match('@^docketcache-post-\d+$@', $group)) {
            return true;
        }

        return false;
    }

    /**
     * is_stalecache_ignored.
     */
    private function is_stalecache_ignored($key, $group = '')
    {
        if ($this->ignore_stalecache) {
            return $this->has_stalecache($key, $group);
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
     * transientdb.
     */
    private function transient_db()
    {
        static $inst;
        if (!\is_object($inst)) {
            $inst = new Nawawi\DocketCache\TransientDb();
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
        $result = true;
        if ($this->use_transientdb && $this->fs()->is_transient($group)) {
            $result = $this->transient_db()->delete($key, $group);
        }

        $file = $this->get_file_path($key, $group);
        $this->fs()->unlink($file, false);
        $this->dc_log('del', $this->get_item_hash($file), $group.':'.$key);

        return $result;
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

        $this->fs()->suspend_cache_write(true);
        $max_execution_time = $this->fs()->get_max_execution_time(180);

        $slowdown = 0;
        foreach ($this->fs()->scanfiles($this->cache_path, null, $pattern) as $object) {
            if ($object->isFile()) {
                $fx = $object->getPathName();
                $fn = $object->getFileName();
                $this->fs()->unlink($fx, true);
                ++$total;

                array_map(function ($grp) use ($fx) {
                    unset($this->cache[$grp]);
                    $this->dc_log('flush', $this->get_item_hash($fx), $grp.':*');
                }, explode(',', $group));
            }

            if ($slowdown > 10) {
                $slowdown = 0;
                usleep(5000);
            }

            ++$slowdown;

            if ($max_execution_time > 0 && (microtime(true) - $this->wp_start_timestamp) > $max_execution_time) {
                break;
            }
        }

        $this->fs()->suspend_cache_write(false);

        if ($this->use_transientdb && $this->fs()->is_transient(explode(',', $group)) && \function_exists('nwdcx_cleanuptransient')) {
            $total += nwdcx_cleanuptransient();
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

        $this->fs()->suspend_cache_write(true);
        $max_execution_time = $this->fs()->get_max_execution_time(180);

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

            if ($max_execution_time > 0 && (microtime(true) - $this->wp_start_timestamp) > $max_execution_time) {
                break;
            }
        }

        $this->fs()->suspend_cache_write(false);

        return $total;
    }

    /**
     * dc_get.
     */
    private function dc_get($key, $group, $is_raw = false, &$codestub_false = false)
    {
        $file = $this->get_file_path($key, $group);
        $logkey = $this->get_item_hash($file);

        if ($this->use_transientdb && !\in_array($key, $this->bypass_transientdb)) {
            if ($this->fs()->is_transient($group)) {
                return $this->transient_db()->get($key, $group);
            }

            if ($this->fs()->is_wp_options($group) && $this->transient_db()->match_key($key)) {
                return false;
            }
        }

        $data = $this->fs()->cache_get($file);
        if (false === $data) {
            if (!$this->skip_stats($group) && !$this->fs()->is_transient($group)) {
                $this->dc_log('miss', $logkey, $group.':'.$key);
            }

            /*if (!$this->skip_stats($group) && !$this->fs()->is_transient($group)) {
                ++$this->cache_misses;

                $this->dc_log('miss', $logkey, $group.':'.$key);
            }*/

            return false;
        }

        $is_timeout = false;
        if (!empty($data['timeout']) && $this->fs()->valid_timestamp($data['timeout']) && time() >= $data['timeout']) {
            $this->dc_log('exp', $logkey, $group.':'.$key);
            $this->fs()->unlink($file, false);
            $is_timeout = true;
        }

        // incase gc not run
        if (!$is_timeout && !empty($data['timestamp']) && $this->fs()->valid_timestamp($data['timestamp'])) {
            $maxttl = time() - $this->cache_maxttl;
            if ($maxttl > $data['timestamp']) {
                $this->dc_log('exp', $logkey, $group.':'.$key);
                $this->fs()->unlink($file, true); // true = delete it instead of truncate
            }
        }

        if (!$this->skip_stats($group)) {
            ++$this->persistent_cache_hits;
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

        // skip precache.
        if (isset($GLOBALS['DOCKET_CACHE_CODESTUB_FALSE']) && isset($GLOBALS['DOCKET_CACHE_CODESTUB_FALSE'][$file])) {
            $codestub_false = true;
        }

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

        if ($this->use_transientdb && !\in_array($cache_key, $this->bypass_transientdb)) {
            if ($this->fs()->is_transient($group)) {
                if (!$expire) {
                    $expire = 86400;
                }

                return $this->transient_db()->set($cache_key, $data, $group, time() + $expire);
            }

            if ($this->fs()->is_wp_options($group) && $this->transient_db()->match_key($cache_key)) {
                return false;
            }

            if (\in_array($cache_key, ['notoptions', 'alloptions']) && \is_array($data) && !empty($data)) {
                foreach ($data as $m => $n) {
                    if ($this->transient_db()->match_key($m)) {
                        unset($data[$m]);
                    }
                }
            }
        }

        if (!$this->fs()->mkdir_p($this->cache_path)) {
            return false;
        }

        @$this->fs()->placeholder($this->cache_path);

        $file = $this->get_file_path($cache_key, $group);

        // Skip save to disk, return true.
        if (('' === $data || (\is_array($data) && empty($data))) && ($this->fs()->is_transient($group) || $this->ignore_emptycache)) {
            nwdcx_debuglog(__FUNCTION__.': '.$logkey.': Process aborted. No data availale.');
            $this->fs()->unlink($file, false);
            unset($this->precache[$group][$cache_key]);

            return true;
        }

        // Chunk dir.
        if ($this->cf()->is_dctrue('CHUNKCACHEDIR', true) && !$this->fs()->mkdir_p(\dirname($file))) {
            return false;
        }

        // If $expire is larger than 0, convert it to timestamp.
        $timeout = ($expire > 0 ? time() + $expire : 0);

        $type = \gettype($data);
        if ('unknown type' === $type) {
            return false;
        }

        if ('NULL' === $type && null === $data) {
            $data = '';
        }

        if (!empty($data)) {
            if (!\in_array($type, ['boolean', 'integer', 'double', 'NULL'])) {
                // Abort if object too large.
                $len = 0;
                $nwdcx_suppresserrors = nwdcx_suppresserrors(true);
                if (\function_exists('maybe_serialize')) {
                    $len = \strlen(@maybe_serialize($data));
                } else {
                    $len = \strlen(@serialize($data));
                }
                nwdcx_suppresserrors($nwdcx_suppresserrors);

                if ($len >= $this->cache_maxsize) {
                    $this->dc_log('err', $logkey, $group.':'.$cache_key.' '.$logpref.' The size of object has breached '.$len.' of '.$this->cache_maxsize.' bytes.');

                    nwdcx_debuglog(__FUNCTION__.': '.$logkey.': Process aborted. The size of object has breached '.$len.' of '.$this->cache_maxsize.' bytes.');
                    $this->fs()->unlink($file, false);
                    unset($this->precache[$group][$cache_key]);

                    return false;
                }
            }

            // Unserialize content first.
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
            // The Data needs to be serialized.
            // The cache always returns false if the object has a class instance
            // other than stdClass since the class has not been loaded yet.
            $nwdcx_suppresserrors = nwdcx_suppresserrors(true);
            $export_data = @var_export($data, 1);
            if (!empty($export_data)) {
                // 1st priority. If has the "Request" instance.
                if (false !== strpos($export_data, 'Requests_Utility_CaseInsensitiveDictionary::__set_state')) {
                    $data = @serialize($data);
                    if (nwdcx_serialized($data)) {
                        $final_type = 'array_serialize';
                    }
                }

                // 2nd priority. If Transients and has class instance.
                if ('array' === $final_type && $this->fs()->is_transient($group) && false !== strpos($export_data, '::__set_state')) {
                    $data = @serialize($data);
                    if (nwdcx_serialized($data)) {
                        $final_type = 'array_serialize';
                    }
                }
            }
            unset($export_data);
            nwdcx_suppresserrors($nwdcx_suppresserrors);
            // Pass to code_stub.
        }

        $meta['site_id'] = get_current_blog_id();
        $meta['group'] = $group;
        $meta['key'] = $cache_key;
        $meta['type'] = $final_type;

        // If 0 let gc handle it by comparing file mtime
        // and maxttl constants.
        $meta['timeout'] = $timeout;

        // Before code_stub.
        $meta['data'] = $data;

        // Only count new file.
        clearstatcache(true, $file);
        $has_cache_file = is_file($file);
        if (true === $this->dc_code($file, $meta)) {
            nwdcx_debuglog(__FUNCTION__.': '.$this->get_item_hash($file).': Storing to disk.');

            if (!$has_cache_file && $this->maxfile_livecheck) {
                $count_file = (int) $this->get('count_file', 'docketcache-gc');
                if ($this->maxfile > $count_file) {
                    ++$count_file;
                    $this->set('count_file', $count_file, 'docketcache-gc', 86400); // 1d
                }
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

        if ($is_done) {
            return;
        }

        $cached = [];
        $group = 'docketcache-precache';
        $keys = $this->get($hash, $group);

        if (empty($keys) || !\is_array($keys)) {
            return;
        }

        nwdcx_debuglog(__FUNCTION__.': Process started.');

        $this->precache_loaded[$hash] = $keys;

        $slowdown = 0;
        $cnt_max = 0;

        foreach ($keys as $cache_group => $arr) {
            foreach ($arr as $cache_key) {
                if ($cnt_max >= $this->precache_maxkey) {
                    break 2;
                }

                $force = false;
                $found = false;
                $doing_precache = true;
                if (!isset($cached[$cache_key.$cache_group]) && false !== $this->get($cache_key, $cache_group, $force, $found, $doing_precache)) {
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

        nwdcx_debuglog(__FUNCTION__.': Process ended. Loaded '.\count($cached));

        unset($keys, $cached);
        $is_done = true;
    }

    /**
     * dc_precache_set.
     */
    private function dc_precache_set($hash)
    {
        $group = 'docketcache-precache';
        $file = $this->get_file_path($hash, $group);

        if (empty($this->precache) || !\is_array($this->precache)) {
            $this->fs()->unlink($file, true);

            return;
        }

        $file_hash = $this->get_item_hash($file);
        $data = [];
        $slowdown = 0;
        $cnt_max = 0;

        nwdcx_debuglog(__FUNCTION__.': '.$file_hash.': Process started.');

        // docketcache-precache-gc
        $count_file = (int) $this->get('count_file', $group.'-gc');
        if ($count_file >= $this->precache_maxfile) {
            nwdcx_debuglog(__FUNCTION__.': '.$file_hash.': Process aborted. Reached maximum file limit ('.$count_file.'/'.$this->precache_maxfile.')');

            return;
        }

        foreach ($this->precache as $cache_group => $cache_keys) {
            if ($cnt_max >= $this->precache_maxkey) {
                break;
            }

            if ($cache_group !== $group) {
                $data[$cache_group] = array_keys($cache_keys);
            }

            ++$cnt_max;

            if ($slowdown > 10) {
                $slowdown = 0;
                usleep(100);
            }

            ++$slowdown;

            if ($this->max_execution_time > 0 && (microtime(true) - $this->wp_start_timestamp) > $this->max_execution_time) {
                nwdcx_debuglog(__FUNCTION__.': '.$file_hash.': Process aborted. Reached maximum execution time.');
                break;
            }
        }

        if (!empty($data)) {
            nwdcx_debuglog(__FUNCTION__.': '.$file_hash.': Total items = '.\count($data, 1));

            if (!empty($this->precache_loaded) && \function_exists('nwdcx_arraysimilar') && nwdcx_arraysimilar($this->precache_loaded[$hash], $data)) {
                nwdcx_debuglog(__FUNCTION__.': '.$file_hash.': Process ended. No data changes.');

                return;
            }

            // docketcache-precache-gc
            if ($this->precache_maxfile > $count_file) {
                clearstatcache(true, $file);

                // only count new file.
                $has_precache_file = is_file($file);

                if ($this->set($hash, $data, $group, 86400)) { // 1d
                    nwdcx_debuglog(__FUNCTION__.': '.$file_hash.': Process ended. Storing cache to disk.');

                    if (!$has_precache_file) {
                        ++$count_file;
                        $this->set('count_file', $count_file, $group.'-gc', 86400); // 1d
                    }

                    return;
                }
            }
            nwdcx_debuglog(__FUNCTION__.': '.$file_hash.': Process aborted. Reached maximum file limit ('.$count_file.'/'.$this->precache_maxfile.')');

            return;
        }

        nwdcx_debuglog(__FUNCTION__.': '.$file_hash.': Process ended. No data available.');
        unset($data, $hash);
    }

    /**
     * dc_precache.
     */
    private function dc_precache_init()
    {
        if (!empty($_POST) || empty($_SERVER['REQUEST_URI']) || $this->cf()->is_dctrue('WPCLI')) {
            return;
        }

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
            return;
        }

        if (false !== strpos($req_uri, '/wp-json/') || false !== strpos($req_uri, '/wp-admin/admin-ajax.php') || false !== strpos($req_uri, '/xmlrpc.php') || false !== strpos($req_uri, '/wp-cron.php') || false !== strpos($req_uri, '/robots.txt') || false !== strpos($req_uri, '/favicon.ico')) {
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

        // $this->precache_hashkey = $this->item_hash($req_host.$req_uri);
        $this->precache_hashkey = md5($req_host.$req_uri);

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
        static $is_done = false;

        if (!$is_done) {
            if ($this->is_precache && !empty($this->precache_hashkey) && !$this->fs()->suspend_cache_write()) {
                $this->dc_precache_set($this->precache_hashkey);
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
            $this->add_non_persistent_groups($dcvalue);
        }

        if ($this->cf()->is_dcarray('IGNORED_KEYS', $dcvalue)) {
            $this->add_non_persistent_keys($dcvalue);
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

        if (class_exists('Nawawi\\DocketCache\\TransientDb')) {
            $this->use_transientdb = $this->cf()->is_dctrue('TRANSIENTDB');
            if ($this->cf()->is_dcarray('IGNORED_TRANSIENTDB', $dcvalue)) {
                $this->bypass_transientdb = $dcvalue;
            }
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
                    add_action(
                        'shutdown',
                        function () {
                            if ($this->multisite) {
                                $this->delete($this->network_id.':active_sitewide_plugins', 'site-options');
                                $this->delete($this->network_id.':auto_update_plugins', 'site-options');
                            }

                            $this->delete('uninstall_plugins', 'options');
                            $this->delete('auto_update_plugins', 'options');
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
                    }
                },
                \PHP_INT_MAX
            );
        }

        // bypass stalecache
        $this->ignore_stalecache = $this->cf()->is_dctrue('STALECACHE_IGNORE', true);

        // bypass emptyache
        $this->ignore_emptycache = $this->cf()->is_dctrue('EMPTYCACHE_IGNORE', true);

        // maxfile check
        // true = count file at dc_save, false = will handle by GC.
        $this->maxfile_livecheck = $this->cf()->is_dctrue('MAXFILE_LIVECHECK', true);

        // maxfile
        $this->maxfile = (int) $this->fs()->sanitize_maxfile($this->cf()->dcvalue('MAXFILE', true));
        $count_file = (int) $this->get('count_file', 'docketcache-gc');
        if ($count_file >= $this->maxfile) {
            $this->fs()->suspend_cache_write(true);
        }

        // load precache
        $this->is_precache = $this->cf()->is_dctrue('PRECACHE', true);
        if ($this->is_precache) {
            if ($this->cf()->is_dcint('PRECACHE_MAXGROUP', $dcvalue)) {
                if (!empty($dcvalue)) {
                    $this->precache_maxgroup = $dcvalue;
                }
            }

            if ($this->cf()->is_dcint('PRECACHE_MAXKEY', $dcvalue)) {
                if (!empty($dcvalue)) {
                    $this->precache_maxkey = $dcvalue;
                }
            }

            if ($this->cf()->is_dcint('PRECACHE_MAXFILE', $dcvalue)) {
                if (!empty($dcvalue)) {
                    $this->precache_maxfile = $this->fs()->sanitize_precache_maxfile($dcvalue);
                }
            }

            $this->dc_precache_init();
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
 * Determines whether the object cache implementation supports a particular feature.
 *
 * @since 6.1.0
 *
 * @param string $feature Name of the feature to check for. Possible values include:
 *                        'add_multiple', 'set_multiple', 'get_multiple', 'delete_multiple',
 *                        'flush_runtime', 'flush_group'.
 *
 * @return bool true if the feature is supported, false otherwise
 */
function wp_cache_supports($feature)
{
    switch ($feature) {
        case 'add_multiple':
        case 'set_multiple':
        case 'get_multiple':
        case 'delete_multiple':
        case 'flush_runtime':
        case 'flush_group':
            return true;

        default:
            return false;
    }
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
 * @see WP_Object_Cache::add_non_persistent_keys()
 */
function wp_cache_add_non_persistent_keys($keys)
{
    global $wp_object_cache;
    $wp_object_cache->add_non_persistent_keys($keys);
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
