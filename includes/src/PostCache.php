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

#[AllowDynamicProperties]
final class PostCache
{
    public $prefix = 'docketcache-post';
    public $group_prefix;
    public $cache_incr = 0;
    public $cache_group = '';
    public $cache_key = '';
    public $all_post_ids = false;
    public $cached_post_ids = [];
    public $cached_posts = [];
    public $found_posts = false;
    public $cache_func = 'wp_cache_add';
    public $cache_func_expiry = 86400; // 1d
    public $allow_posttype = [];
    public $blacklist_posttype = [];
    public $allow_posttype_all = false;

    public function __construct()
    {
        $this->group_prefix = $this->prefix.'-';
        $this->allow_posttype = [
            'post',
            'page',
            'attachment',
            'revision',
            'nav_menu_item',
            'custom_css',
            'customize_changeset',
            'oembed_cache',
            'user_request',
            'wp_block',
            'wp_template',
            'wp_template_part',
            'wp_global_styles',
            'wp_navigation',
        ];

        $this->blacklist_posttype = ['scheduled-action'];
    }

    public function register()
    {
        // already in core.
        // https://github.com/WordPress/wordpress-develop/commit/7f7d616d822b79c952cbd6a3046a6f6a8aa5a35e
        if (isset($GLOBALS['wp_version']) && version_compare($GLOBALS['wp_version'], '6.1', '<')) {
            $this->allow_posttype_all = nwdcx_construe('ADVCPOST_POSTTYPE_ALL');
            if (!$this->allow_posttype_all) {
                $types = nwdcx_constval('ADVCPOST_POSTTYPE');

                if (\is_string($types) && 'all' === $types || \in_array($types, $this->allow_posttype)) {
                    $this->allow_posttype = $types;
                } elseif (!empty($types) && \is_array($types)) {
                    $this->allow_posttype = array_merge($this->allow_posttype, $types);
                }
            }

            $this->setup_for_blog();

            add_action('switch_blog', [$this, 'setup_for_blog'], 10, 2);
            add_filter('posts_request', [&$this, 'posts_request'], 10, 2);
            add_filter('posts_results', [&$this, 'posts_results'], 10, 2);
            add_filter('post_limits_request', [&$this, 'post_limits_request'], 999, 2);
            add_filter('found_posts_query', [&$this, 'found_posts_query'], 10, 2);
            add_filter('found_posts', [&$this, 'found_posts'], 10, 2);

            add_action('clean_term_cache', [$this, 'invalidate_cache']);
            add_action('clean_post_cache', [$this, 'invalidate_cache']);

            add_action('added_post_meta', [$this, 'invalidate_cache']);
            add_action('updated_post_meta', [$this, 'invalidate_cache']);
            add_action('deleted_post_meta', [$this, 'invalidate_cache']);
        }
    }

    public function setup_for_blog($new_blog_id = false, $previous_blog_id = false)
    {
        if ($new_blog_id && $new_blog_id === $previous_blog_id) {
            return;
        }

        $this->cache_incr = wp_cache_get('cache_incr', $this->prefix);
        if (!is_numeric($this->cache_incr)) {
            $now = time();
            wp_cache_set('cache_incr', $now, $this->prefix);
            $this->cache_incr = $now;
        }
        $this->cache_group = $this->group_prefix.$this->cache_incr;
    }

    public function invalidate_cache()
    {
        if (is_admin() && isset($_POST['wp-preview']) && 'dopreview' === $_POST['wp-preview']) {
            return;
        }

        if (\defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        $this->cache_incr = wp_cache_incr('cache_incr', 1, $this->prefix);

        if (10 < \strlen($this->cache_incr)) {
            wp_cache_set('cache_incr', 0, $this->prefix);
            $this->cache_incr = 0;
        }
        $this->cache_group = $this->group_prefix.$this->cache_incr;
    }

    private function allow_post_type($post_type)
    {
        if ($this->allow_posttype_all && !\in_array($post_type, $this->blacklist_posttype)) {
            return true;
        }

        $this->allow_posttype = apply_filters('docketcache/filter/postcache/posttype', $this->allow_posttype);
        if (\is_string($this->allow_posttype) && ('all' === $this->allow_posttype || $post_type === $this->allow_posttype)) {
            return true;
        }

        if (\in_array($post_type, $this->allow_posttype) || \in_array('all', $this->allow_posttype)) {
            return true;
        }

        return false;
    }

    public function posts_request($sql, $query)
    {
        if (!nwdcx_wpdb($wpdb)) {
            return $sql;
        }

        $post_type = $query->get('post_type');
        if (!$this->allow_post_type($post_type)) {
            return $sql;
        }

        $this->cache_key = 'query-'.$post_type.'-'.md5($sql);
        $this->all_post_ids = wp_cache_get($this->cache_key, $this->cache_group);

        if ('NA' !== $this->found_posts) {
            $cache_key = $this->cache_key.'-found';
            $this->found_posts = wp_cache_get($cache_key, $this->cache_group);
        }

        if ($this->all_post_ids xor $this->found_posts) {
            $this->cache_func = 'wp_cache_set';
        } else {
            $this->cache_func = 'wp_cache_add';
        }

        $this->cached_post_ids = [];
        $this->cached_posts = [];

        if ($this->found_posts && \is_array($this->all_post_ids)) {
            if (\function_exists('wp_cache_get_multi')) {
                $this->cached_posts = wp_cache_get_multi(['posts' => $this->all_post_ids]);
            } else {
                $this->cached_posts = [];
                foreach ($this->all_post_ids as $key) {
                    $this->cached_posts[$key] = wp_cache_get($key, 'posts');
                }
            }

            // array_filter = remove "empty" array
            $this->cached_posts = array_filter($this->cached_posts);

            foreach ($this->cached_posts as $post) {
                if (!empty($post)) {
                    $this->cached_post_ids[] = $post->ID;
                }
            }
            $uncached_post_ids = array_diff($this->all_post_ids, $this->cached_post_ids);

            if ($uncached_post_ids) {
                return "SELECT * FROM `{$wpdb->posts}` WHERE ID IN(".implode(',', array_map('absint', $uncached_post_ids)).')';
            }

            return '';
        }

        return $sql;
    }

    public function posts_results($posts, $query)
    {
        if (!$this->allow_post_type($query->get('post_type'))) {
            return $posts;
        }

        if ($this->found_posts && \is_array($this->all_post_ids)) {
            $collated_posts = [];
            foreach ($this->cached_posts as $post) {
                $posts[] = $post;
            }

            foreach ($posts as $post) {
                $loc = array_search($post->ID, $this->all_post_ids);
                if (is_numeric($loc) && -1 < $loc) {
                    $collated_posts[$loc] = $post;
                }
            }
            ksort($collated_posts);

            return array_map('get_post', array_values($collated_posts));
        }

        $post_ids = [];
        foreach ((array) $posts as $post) {
            $post_ids[] = $post->ID;
        }

        if (!$post_ids) {
            return [];
        }

        \call_user_func($this->cache_func, $this->cache_key, $post_ids, $this->cache_group, $this->cache_func_expiry);

        return array_map('get_post', $posts);
    }

    public function post_limits_request($limits, $query)
    {
        if (!$this->allow_post_type($query->get('post_type'))) {
            return $limits;
        }

        if (empty($limits) || (isset($query->query_vars['no_found_rows']) && $query->query_vars['no_found_rows'])) {
            $this->found_posts = 'NA';
        } else {
            $this->found_posts = false;
        }

        return $limits;
    }

    public function found_posts_query($sql, $query)
    {
        if (!$this->allow_post_type($query->get('post_type'))) {
            return $sql;
        }

        if ($this->found_posts && \is_array($this->all_post_ids)) {
            return '';
        }

        return $sql;
    }

    public function found_posts($found_posts, $query)
    {
        if (!$this->allow_post_type($query->get('post_type'))) {
            return $found_posts;
        }

        if ($this->found_posts && \is_array($this->all_post_ids)) {
            return (int) $this->found_posts;
        }

        $cache_key = $this->cache_key.'-found';
        \call_user_func($this->cache_func, $cache_key, (int) $found_posts, $this->cache_group, $this->cache_func_expiry);

        return $found_posts;
    }
}
