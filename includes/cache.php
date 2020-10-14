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
 *  wp-includes/class-wp-object-cache.php.
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
    private $cache_maxsize = 5000000;

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
     * Precache status.
     *
     * @var bool
     */
    private $is_precache = false;

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
     * Clears the object cache of all data.
     *
     * @return true always returns true
     */
    public function flush()
    {
        $this->cache = [];
        $this->precache = [];

        return $this->dc_flush();
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
        echo '<p>total: '.number_format($total / KB_IN_BYTES).'</p>';
        echo $ret;
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
    private function is_non_persistent_keys($key)
    {
        return !empty($this->non_persistent_keys) && \in_array($key, $this->non_persistent_keys);
    }

    /**
     * Check if key in non persistent index.
     *
     * @param bool $group cache group
     * @param bool $key   cache key
     */
    private function is_non_persistent_groupkey($group, $key)
    {
        return !empty($this->non_persistent_groupkey) && \in_array($group.':'.$key, $this->non_persistent_groupkey);
    }

    /**
     * Check if key in non persistent index.
     *
     * @param bool $group cache group
     * @param bool $key   cache key
     */
    private function is_bypass_precache($group, $key)
    {
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
    private function is_filtered_groups($group, $key)
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
                $this->dc_log('flush', 'internalproc-'.$this->item_hash(__FUNCTION__), $group.':'.$key);
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

        $expire = $this->fs()->sanitize_second($expire);

        if (0 === $expire) {
            if (\in_array($group, ['site-transient', 'transient'])) {
                if ('site-transient' === $group && \in_array($key, ['update_plugins', 'update_themes', 'update_core'])) {
                    $expire = 2419200; // 28d
                } else {
                    $expire = 345600; // 4d
                }
            } elseif (\in_array($group, ['post_meta', 'options'])) {
                $expire = 172800; // 2d
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
        $index = $this->item_hash($group).'-'.$this->item_hash($key);

        return $this->cache_path.$index.'.php';
    }

    /**
     * skip_stats.
     */
    private function skip_stats($group, $key = '')
    {
        if ($this->is_non_persistent_groups($group)) {
            return true;
        }

        return $this->cf()->is_dcfalse('LOG_ALL') && $this->fs()->internal_group($group);
    }

    /**
     * is_data_uptodate.
     */
    private function is_data_uptodate($key, $group, $data)
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

        if (!$doserialize && $data_p === $data) {
            return true;
        }

        if ($doserialize && @md5(@serialize($data_p)) === @md5(@serialize($data))) {
            return true;
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
        $cnt = $this->fs()->cachedir_flush($dir);
        if (false === $cnt) {
            $this->dc_log('err', 'internalproc-'.$this->item_hash(__FUNCTION__), 'Cache could not be flushed');
        }

        if ($cnt > 0) {
            $this->dc_log('flush', 'internalproc-'.$this->item_hash(__FUNCTION__), 'files:'.$cnt);
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
     * dc_get.
     */
    private function dc_get($key, $group, $is_raw = false)
    {
        $file = $this->get_file_path($key, $group);
        $index = $this->get_item_hash($file);

        $data = $this->fs()->cache_get($file);
        if (false === $data) {
            if (!$this->skip_stats($group)) {
                ++$this->cache_misses;

                $this->dc_log('miss', $index, $group.':'.$key);
            }

            return false;
        }

        if (!empty($data['timeout']) && $this->fs()->valid_timestamp($data['timeout']) && time() >= $data['timeout']) {
            $this->dc_log('exp', $this->get_item_hash($file), $group.':'.$key);
            $this->fs()->unlink($file, false);
        }

        if (!$this->skip_stats($group)) {
            ++$this->cache_hits;
            $this->dc_log('hit', $index, $group.':'.$key);
        }

        clearstatcache();

        return $is_raw ? $data : $data['data'];
    }

    /**
     * dc_code.
     */
    private function dc_code($file, $arr)
    {
        $fname = $this->get_item_hash($file);

        $data = $this->fs()->export_var($arr, $error);
        if (false === $data) {
            $this->dc_log('err', $fname, 'Failed to export var: '.$error);

            return false;
        }

        $len = \strlen($data);
        if ($len >= $this->cache_maxsize) {
            $this->dc_log('err', $fname, 'Data too large: '.$len.'/'.$this->cache_maxsize);

            return false;
        }

        $code = $this->fs()->code_stub($data);
        $stat = $this->fs()->dump($file, $code);
        if (-1 === $stat) {
            $this->dc_log('err', $fname, 'Failed to write');

            return false;
        }

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

        if (!@wp_mkdir_p($this->cache_path)) {
            return false;
        }

        if (!@is_file($this->cache_path.'index.php')) {
            @$this->fs()->put($this->cache_path.'index.php', $this->fs()->code_stub(time()));
        }

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

        if (0 === $expire && !empty($key) && @is_file($file) && $this->is_data_uptodate($key, $group, $data)) {
            $this->dc_log('info', $group.':'.$cache_key, __FUNCTION__.'()->nochanges');

            return false;
        }

        $meta = [];
        $meta['timestamp'] = time();

        if ($this->multisite) {
            // try to avoid error-prone
            try {
                $meta['network_id'] = get_current_network_id();
            } catch (\Exception $e) {
                $meta['network_id'] = 0;
            }
        }

        $meta['site_id'] = get_current_blog_id();
        $meta['group'] = $group;
        $meta['key'] = $cache_key;
        $meta['type'] = $type;
        $meta['timeout'] = $timeout;
        $meta['data'] = $data;

        if (true === $this->dc_code($file, $meta)) {
            if ($timeout > 0) {
                @touch($file, $timeout);
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
     * dc_precache_get.
     */
    private function dc_precache_get($hash)
    {
        static $cached = [];
        $group = 'docketcache-precache';
        $keys = $this->get($hash, $group);

        if (!empty($keys) && \is_array($keys)) {
            foreach ($keys as $cache_group => $arr) {
                foreach ($arr as $cache_key) {
                    // reduce our load
                    if (isset($cached[$cache_key.$cache_group])) {
                        continue;
                    }

                    if (false !== $this->get($cache_key, $cache_group)) {
                        $cached[$cache_key.$cache_group] = 1;
                    }
                }
            }
        }
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

        // limit precache list to 10000
        $cache_hash = $this->get('index', $group);
        if (!empty($cache_hash) && \is_array($cache_hash)) {
            if (\count($cache_hash) >= 10000) {
                // flush first 500
                $x = 0;
                foreach ($cache_hash as $h) {
                    if ($x > 500) {
                        break;
                    }

                    $this->delete($h, $group);
                    unset($cache_hash[$h]);
                    ++$x;
                }

                if ($x > 0) {
                    $this->set('index', $cache_hash, $group, 86400);
                }
            }
        }

        foreach ($this->precache as $cache_group => $cache_keys) {
            if ($cache_group === $group || 'docketcache-post' === substr($cache_group, 0, 16)) {
                continue;
            }

            if ($this->is_non_persistent_groups($cache_group)) {
                continue;
            }

            $cache_keys = array_keys($cache_keys);
            $data[$cache_group] = $cache_keys;
        }

        if (!empty($data)) {
            if ($this->is_data_uptodate($hash, $group, $data)) {
                $this->dc_log('info', $group.':'.$hash, __FUNCTION__.'()->nochanges');

                return;
            }

            $this->set($hash, $data, $group, 86400); // 1d
            $cache_hash[$hash] = 1;
            $this->set('index', $cache_hash, $group, 86400);
        }
    }

    /**
     * dc_precache.
     */
    private function dc_precache()
    {
        if (empty($_SERVER['REQUEST_URI']) || $this->cf()->is_dctrue('WPCLI')) {
            return;
        }

        $req_host = !empty($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
        if ('localhost' !== $req_host) {
            $req_host = nwdcx_fixhost($req_host);
        }

        $req_uri = $_SERVER['REQUEST_URI'];
        $dostrip = !empty($_SERVER['QUERY_STRING']);
        if ($dostrip && $this->is_user_logged_in() && false !== strpos($req_uri, '.php?') && false !== strpos($req_uri, '/wp-admin/') && @preg_match('@/wp-admin/(network/)?.*?\.php\?.*?@', $req_uri)) {
            $dostrip = false;
        }

        // without pretty permalink
        if ($dostrip && !empty($_GET) && empty($_GET['s']) && empty($_GET['q']) && (false !== strpos($req_uri, '/?p=') || false !== strpos($req_uri, '/?cat=') || false !== strpos($req_uri, '/?m=') || false !== strpos($req_uri, '/?page_id=') || false !== strpos($req_uri, '/index.php/')) && !@nwdcx_optget('permalink_structure')) {
            $dostrip = false;
        }

        if ($dostrip) {
            $req_uri = @preg_replace('@\?.*@', '', $req_uri);
        }

        if (empty($req_host) || empty($req_uri)) {
            return;
        }

        $req_key = $this->item_hash($req_host.$req_uri);

        $this->dc_precache_get($req_key);

        add_action(
            'shutdown',
            function () use ($req_key) {
                $this->dc_precache_set($req_key);
            },
            PHP_INT_MAX
        );
    }

    /**
     * dc_init.
     */
    private function dc_init()
    {
        if ($this->cf()->is_dcint('MAXSIZE', $dcvalue)) {
            if ($dcvalue >= 1000000) {
                $this->cache_maxsize = $dcvalue;
                if ($this->cache_maxsize > 10485760) {
                    $this->cache_maxsize = 10485760;
                }
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
            $this->cache_path = nnwdcx_network_dirpath($this->cache_path);
        }

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
                                PHP_INT_MAX - 1
                            );
                        }
                    }
                },
                PHP_INT_MAX
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
                                $this->delete(get_current_network_id().':active_sitewide_plugins', 'site-options');
                            },
                            PHP_INT_MAX - 1
                        );
                    }
                    add_action(
                        'shutdown',
                        function () {
                            $this->delete('uninstall_plugins', 'options');
                        },
                        PHP_INT_MAX - 1
                    );
                },
                PHP_INT_MAX,
                2
            );
        }

        // cron
        add_filter(
            'pre_clear_scheduled_hook',
            function ($a, $hook, $args) {
                add_action(
                    'shutdown',
                    function () {
                        $this->delete('alloptions', 'options');
                    },
                    PHP_INT_MAX - 1
                );
            },
            PHP_INT_MAX,
            3
        );

        // filtered groups hooks
        if (\is_array($this->filtered_groups)) {
            add_action(
                'save_post',
                function ($post_id, $post, $update) {
                    $this->flush_filtered_groups('save_post', [$post_id, $post, $update]);
                },
                -PHP_INT_MAX,
                3
            );

            add_action(
                'edit_post',
                function ($post_id, $post) {
                    $this->flush_filtered_groups('edit_post', [$post_id, $post]);
                },
                -PHP_INT_MAX,
                2
            );

            add_action(
                'delete_post',
                function ($post_id) {
                    $this->flush_filtered_groups('delete_post', [$post_id]);
                },
                -PHP_INT_MAX
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
                -PHP_INT_MAX
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
                -PHP_INT_MAX
            );

            add_action(
                'pre_get_users',
                function ($wpq) {
                    if (nwdcx_wpdb($wpdb) && !empty($wpq->query_vars['count_total'])) {
                        $wpq->query_vars['count_total'] = false;
                        $wpq->query_vars['nwdcx_count_total'] = true;
                    }
                },
                -PHP_INT_MAX
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
                -PHP_INT_MAX
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
                -PHP_INT_MAX
            );

            add_action(
                'shutdown',
                function () {
                    if ($this->add_signature && !$this->is_user_logged_in()) {
                        echo "\n<!-- Performance optimized by Docket Cache: https://wordpress.org/plugins/docket-cache -->\n";
                    }
                },
                PHP_INT_MAX
            );
        }

        $this->is_precache = $this->cf()->is_dctrue('PRECACHE');

        if ($this->is_precache) {
            $this->dc_precache();
        }
    }
}

/**
 * Adds data to the cache, if the cache key doesn't already exist.
 *
 * @see WP_Object_Cache::add()
 *
 * @global WP_Object_Cache $wp_object_cache Object cache global instance.
 *
 * @param int|string $key    the cache key to use for retrieval later
 * @param mixed      $data   the data to add to the cache
 * @param string     $group  Optional. The group to add the cache to. Enables the same key
 *                           to be used across groups. Default empty.
 * @param int        $expire Optional. When the cache data should expire, in seconds.
 *                           Default 0 (no expiration).
 *
 * @return bool true on success, false if cache key and group already exist
 */
function wp_cache_add($key, $data, $group = '', $expire = 0)
{
    global $wp_object_cache;

    return $wp_object_cache->add($key, $data, $group, (int) $expire);
}

/**
 * Closes the cache.
 *
 * This function has ceased to do anything since WordPress 2.5. The
 * functionality was removed along with the rest of the persistent cache. This
 * does not mean that plugins can't implement this function when they need to
 * make sure that the cache is cleaned up after WordPress no longer needs it.
 *
 * @return bool Always returns True
 */
function wp_cache_close()
{
    return true;
}

/**
 * Decrements numeric cache item's value.
 *
 * @see WP_Object_Cache::decr()
 *
 * @global WP_Object_Cache $wp_object_cache Object cache global instance.
 *
 * @param int|string $key    the cache key to decrement
 * @param int        $offset Optional. The amount by which to decrement the item's value. Default 1.
 * @param string     $group  Optional. The group the key is in. Default empty.
 *
 * @return int|false the item's new value on success, false on failure
 */
function wp_cache_decr($key, $offset = 1, $group = '')
{
    global $wp_object_cache;

    return $wp_object_cache->decr($key, $offset, $group);
}

/**
 * Removes the cache contents matching key and group.
 *
 * @see WP_Object_Cache::delete()
 *
 * @global WP_Object_Cache $wp_object_cache Object cache global instance.
 *
 * @param int|string $key   what the contents in the cache are called
 * @param string     $group Optional. Where the cache contents are grouped. Default empty.
 *
 * @return bool true on successful removal, false on failure
 */
function wp_cache_delete($key, $group = '')
{
    global $wp_object_cache;

    return $wp_object_cache->delete($key, $group);
}

/**
 * Removes all cache items.
 *
 * @see WP_Object_Cache::flush()
 *
 * @global WP_Object_Cache $wp_object_cache Object cache global instance.
 *
 * @return bool true on success, false on failure
 */
function wp_cache_flush()
{
    global $wp_object_cache;

    return $wp_object_cache->flush();
}

/**
 * Retrieves the cache contents from the cache by key and group.
 *
 * @see WP_Object_Cache::get()
 *
 * @global WP_Object_Cache $wp_object_cache Object cache global instance.
 *
 * @param int|string $key   the key under which the cache contents are stored
 * @param string     $group Optional. Where the cache contents are grouped. Default empty.
 * @param bool       $force Optional. Whether to force an update of the local cache from the persistent
 *                          cache. Default false.
 * @param bool       $found Optional. Whether the key was found in the cache (passed by reference).
 *                          Disambiguates a return of false, a storable value. Default null.
 *
 * @return bool|mixed False on failure to retrieve contents or the cache
 *                    contents on success
 */
function wp_cache_get($key, $group = '', $force = false, &$found = null)
{
    global $wp_object_cache;

    return $wp_object_cache->get($key, $group, $force, $found);
}

/**
 * Retrieves multiple values from the cache in one call.
 *
 * @see WP_Object_Cache::get_multiple()
 *
 * @global WP_Object_Cache $wp_object_cache Object cache global instance.
 *
 * @param array  $keys  array of keys under which the cache contents are stored
 * @param string $group Optional. Where the cache contents are grouped. Default empty.
 * @param bool   $force Optional. Whether to force an update of the local cache
 *                      from the persistent cache. Default false.
 *
 * @return array array of values organized into groups
 */
function wp_cache_get_multiple($keys, $group = '', $force = false)
{
    global $wp_object_cache;

    return $wp_object_cache->get_multiple($keys, $group, $force);
}

/**
 * Increment numeric cache item's value.
 *
 * @see WP_Object_Cache::incr()
 *
 * @global WP_Object_Cache $wp_object_cache Object cache global instance.
 *
 * @param int|string $key    the key for the cache contents that should be incremented
 * @param int        $offset Optional. The amount by which to increment the item's value. Default 1.
 * @param string     $group  Optional. The group the key is in. Default empty.
 *
 * @return int|false the item's new value on success, false on failure
 */
function wp_cache_incr($key, $offset = 1, $group = '')
{
    global $wp_object_cache;

    return $wp_object_cache->incr($key, $offset, $group);
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
 * Replaces the contents of the cache with new data.
 *
 * @see WP_Object_Cache::replace()
 *
 * @global WP_Object_Cache $wp_object_cache Object cache global instance.
 *
 * @param int|string $key    the key for the cache data that should be replaced
 * @param mixed      $data   the new data to store in the cache
 * @param string     $group  Optional. The group for the cache data that should be replaced.
 *                           Default empty.
 * @param int        $expire Optional. When to expire the cache contents, in seconds.
 *                           Default 0 (no expiration).
 *
 * @return bool False if original value does not exist, true if contents were replaced
 */
function wp_cache_replace($key, $data, $group = '', $expire = 0)
{
    global $wp_object_cache;

    return $wp_object_cache->replace($key, $data, $group, (int) $expire);
}

/**
 * Saves the data to the cache.
 *
 * Differs from wp_cache_add() and wp_cache_replace() in that it will always write data.
 *
 * @see WP_Object_Cache::set()
 *
 * @global WP_Object_Cache $wp_object_cache Object cache global instance.
 *
 * @param int|string $key    the cache key to use for retrieval later
 * @param mixed      $data   the contents to store in the cache
 * @param string     $group  Optional. Where to group the cache contents. Enables the same key
 *                           to be used across groups. Default empty.
 * @param int        $expire Optional. When to expire the cache contents, in seconds.
 *                           Default 0 (no expiration).
 *
 * @return bool true on success, false on failure
 */
function wp_cache_set($key, $data, $group = '', $expire = 0)
{
    global $wp_object_cache;

    return $wp_object_cache->set($key, $data, $group, (int) $expire);
}

/**
 * Switches the internal blog ID.
 *
 * This changes the blog id used to create keys in blog specific groups.
 *
 * @see WP_Object_Cache::switch_to_blog()
 *
 * @global WP_Object_Cache $wp_object_cache Object cache global instance.
 *
 * @param int $blog_id site ID
 */
function wp_cache_switch_to_blog($blog_id)
{
    global $wp_object_cache;

    $wp_object_cache->switch_to_blog($blog_id);
}

/**
 * Adds a group or set of groups to the list of global groups.
 *
 * @see WP_Object_Cache::add_global_groups()
 *
 * @global WP_Object_Cache $wp_object_cache Object cache global instance.
 *
 * @param string|array $groups a group or an array of groups to add
 */
function wp_cache_add_global_groups($groups)
{
    global $wp_object_cache;

    $wp_object_cache->add_global_groups($groups);
}

/**
 * Adds a group or set of groups to the list of non-persistent groups.
 *
 * @param string|array $groups a group or an array of groups to add
 */
function wp_cache_add_non_persistent_groups($groups)
{
    global $wp_object_cache;
    $wp_object_cache->add_non_persistent_groups($groups);
}

function wp_cache_stats()
{
    global $wp_object_cache;
    $wp_object_cache->stats();
}
