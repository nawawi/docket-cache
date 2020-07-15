<?php
/**
 * Docket Cache.
 *
 * @author  Nawawi Jamili
 * @license MIT
 *
 * @see    https://github.com/nawawi/docket-cache
 */

/**
 * Based on:
 *  https://github.com/Automattic/vip-go-mu-plugins-built/blob/master/advanced-post-cache/advanced-post-cache.php.
 */

namespace Nawawi\Docket_Cache;

class Advanced_Post
{
    public $prefix;
    public $group_prefix;
    public $do_flush_cache = true;

    public $need_to_flush_cache = true;

    /* Per cache-clear data */
    public $cache_incr = 0;
    public $cache_group = '';

    /* Per query data */
    public $cache_key = '';
    public $all_post_ids = false;
    public $cached_post_ids = [];
    public $cached_posts = [];
    public $found_posts = false;
    public $cache_func = 'wp_cache_add';

    public static $inst;

    public function __construct()
    {
        $this->prefix = 'docketcache-post';
        $this->group_prefix = $this->prefix.'-';

        $this->setup_for_blog();
        $this->setup_hooks();
    }

    public static function inst()
    {
        if (!isset(self::$inst)) {
            self::$inst = new self();
        }

        return self::$inst;
    }

    private function setup_hooks()
    {
        add_action('switch_blog', [$this, 'setup_for_blog'], 10, 2);
        add_filter('posts_request', [&$this, 'posts_request'], 10, 2);
        add_filter('posts_results', [&$this, 'posts_results'], 10, 2);
        add_filter('post_limits_request', [&$this, 'post_limits_request'], 999, 2);
        add_filter('found_posts_query', [&$this, 'found_posts_query'], 10, 2);
        add_filter('found_posts', [&$this, 'found_posts'], 10, 2);

        add_action('clean_term_cache', [$this, 'flush_cache']);
        add_action('clean_post_cache', [$this, 'flush_cache']);

        add_action(
            'wp_updating_comment_count',
            function () {
                $this->do_flush_cache = true;
            }
        );

        add_action(
            'wp_update_comment_count',
            function () {
                $this->do_flush_cache = false;
            }
        );

        add_filter('instagram_cache_oembed_api_response_body', '__return_true');

        add_filter(
            'dashboard_recent_posts_query_args',
            function ($query_args) {
                $query_args['cache_results'] = true;
                $query_args['suppress_filters'] = false;

                return $query_args;
            },
            10,
            1
        );

        add_filter(
            'dashboard_recent_drafts_query_args',
            function ($query_args) {
                $query_args['suppress_filters'] = false;

                return $query_args;
            },
            10,
            1
        );

        add_filter(
            'wp_count_comments',
            function ($counts = false, $post_id = 0) {
                if (0 !== $post_id) {
                    return $counts;
                }

                $cache_key = "comments-{$post_id}";
                $stats_object = wp_cache_get($cache_key);

                if (false === $stats_object) {
                    $stats = get_comment_count($post_id);
                    $stats['moderated'] = $stats['awaiting_moderation'];
                    unset($stats['awaiting_moderation']);
                    $stats_object = (object) $stats;

                    wp_cache_set($cache_key, $stats_object, $this->prefix, 30 * MINUTE_IN_SECONDS);
                }

                return $stats_object;
            },
            10,
            2
        );

        add_filter(
            'media_library_months_with_files',
            function () {
                $cache_group = $this->prefix.'_media';

                $months = wp_cache_get('media_library_months_with_files', $cache_group);

                if (false === $months) {
                    global $wpdb;
                    $months = $wpdb->get_results(
                        $wpdb->prepare(
                            'SELECT DISTINCT YEAR( post_date ) AS year, MONTH( post_date ) AS month FROM '.$wpdb->posts.' WHERE post_type = %s ORDER BY post_date DESC',
                            'attachment'
                        )
                    );
                    wp_cache_set('media_library_months_with_files', $months, $cache_group);
                }

                return $months;
            }
        );

        add_action(
            'add_attachment',
            function ($post_id) {
                if (\defined('WP_IMPORTING') && WP_IMPORTING) {
                    return;
                }

                global $wpdb;
                $months = $wpdb->get_results(
                    $wpdb->prepare(
                        'SELECT DISTINCT YEAR( post_date ) AS year, MONTH( post_date ) AS month FROM '.$wpdb->posts.' WHERE post_type = %s ORDER BY post_date DESC LIMIT 1',
                        'attachment'
                    )
                );

                $cache_group = $this->prefix.'_media';
                $months = array_shift(array_values($months));

                if (!$months->year == get_the_time('Y', $post_id) && !$months->month == get_the_time('m', $post_id)) {
                    wp_cache_delete('media_library_months_with_files', $cache_group);
                }
            }
        );
    }

    public function setup_for_blog($new_blog_id = false, $previous_blog_id = false)
    {
        if ($new_blog_id && $new_blog_id === $previous_blog_id) {
            return;
        }

        $this->cache_incr = wp_cache_get('cache_incrementors', $this->prefix);
        if (!is_numeric($this->cache_incr)) {
            $now = time();
            wp_cache_set('cache_incrementors', $now, $this->prefix);
            $this->cache_incr = $now;
        }
        $this->cache_group = $this->group_prefix.$this->cache_incr;
    }

    public function flush_cache()
    {
        if (!$this->do_flush_cache) {
            return;
        }

        if (is_admin() && isset($_POST['wp-preview']) && 'dopreview' === $_POST['wp-preview']) {
            return;
        }

        if (\defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        $this->cache_incr = wp_cache_incr('cache_incrementors', 1, $this->prefix);
        if (10 < \strlen($this->cache_incr)) {
            wp_cache_set('cache_incrementors', 0, $this->prefix);
            $this->cache_incr = 0;
        }
        $this->cache_group = $this->group_prefix.$this->cache_incr;
        $this->need_to_flush_cache = false;
    }

    public function posts_request($sql, $query)
    {
        global $wpdb;

        if (apply_filters('docketcache_post_skip_type', false, $query->get('post_type'))) {
            return $sql;
        }

        $this->cache_key = 'query-'.substr(md5($sql), 0, 12);
        $this->all_post_ids = wp_cache_get($this->cache_key, $this->cache_group);
        if ('NA' !== $this->found_posts) {
            $this->found_posts = wp_cache_get("{$this->cache_key}_found", $this->cache_group);
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
                foreach ($this->all_post_ids as $pid) {
                    $this->cached_posts[] = wp_cache_get($pid, 'posts');
                }
            }

            $this->cached_posts = array_filter($this->cached_posts);

            foreach ($this->cached_posts as $post) {
                if (!empty($post)) {
                    $this->cached_post_ids[] = $post->ID;
                }
            }
            $uncached_post_ids = array_diff($this->all_post_ids, $this->cached_post_ids);

            if ($uncached_post_ids) {
                return "SELECT * FROM $wpdb->posts WHERE ID IN(".implode(',', array_map('absint', $uncached_post_ids)).')';
            }

            return '';
        }

        return $sql;
    }

    public function posts_results($posts, $query)
    {
        if (apply_filters('docketcache_post_skip_type', false, $query->get('post_type'))) {
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

        \call_user_func($this->cache_func, $this->cache_key, $post_ids, $this->cache_group);
        $this->need_to_flush_cache = true;

        return array_map('get_post', $posts);
    }

    public function post_limits_request($limits, $query)
    {
        if (apply_filters('docketcache_post_skip_type', false, $query->get('post_type'))) {
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
        if (apply_filters('docketcache_post_skip_type', false, $query->get('post_type'))) {
            return $sql;
        }

        if ($this->found_posts && \is_array($this->all_post_ids)) {
            return '';
        }

        return $sql;
    }

    public function found_posts($found_posts, $query)
    {
        if (apply_filters('docketcache_post_skip_type', false, $query->get('post_type'))) {
            return $found_posts;
        }

        if ($this->found_posts && \is_array($this->all_post_ids)) {
            return (int) $this->found_posts;
        }

        \call_user_func($this->cache_func, "{$this->cache_key}_found", (int) $found_posts, $this->cache_group);
        $this->need_to_flush_cache = true;

        return $found_posts;
    }
}
