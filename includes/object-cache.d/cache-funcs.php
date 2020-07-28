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
 * Based on:
 *  wp-includes/cache.php
 */

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
