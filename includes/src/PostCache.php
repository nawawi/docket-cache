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

final class PostCache
{
    public $prefix;
    public $group_prefix;
    public $cache_incr;
    public $cache_group;
    public $cache_key;
    public $all_post_ids;
    public $cached_post_ids;
    public $cached_posts;
    public $found_posts;
    public $cache_func;

    public function __construct()
    {
        $this->prefix = 'docketcache-post';
        $this->group_prefix = $this->prefix.'-';

        /* Per cache-clear data */
        $this->cache_incr = 0;
        $this->cache_group = '';

        /* Per query data */
        $this->cache_key = '';
        $this->all_post_ids = false;
        $this->cached_post_ids = [];
        $this->cached_posts = [];
        $this->found_posts = false;
        $this->cache_func = 'wp_cache_add';
    }

    public function register()
    {
        $this->setup_for_blog();
        $this->setup_hooks();
    }

    private function setup_hooks()
    {
        add_action('switch_blog', [$this, 'setup_for_blog'], 10, 2);
        add_filter('posts_request', [&$this, 'posts_request'], 10, 2);
        add_filter('posts_results', [&$this, 'posts_results'], 10, 2);
        add_filter('post_limits_request', [&$this, 'post_limits_request'], 999, 2);
        add_filter('found_posts_query', [&$this, 'found_posts_query'], 10, 2);
        add_filter('found_posts', [&$this, 'found_posts'], 10, 2);

        // https://developer.wordpress.org/reference/functions/clean_term_cache/
        add_action('clean_term_cache', [$this, 'flush_cache']);
        add_action('clean_post_cache', [$this, 'flush_cache']);

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

                $cache_key = 'comments-0';
                $stats_object = wp_cache_get($cache_key, $this->prefix);

                if (false === $stats_object) {
                    $stats = get_comment_count(0);
                    $stats['moderated'] = $stats['awaiting_moderation'];
                    unset($stats['awaiting_moderation']);
                    $stats_object = (object) $stats;

                    wp_cache_set($cache_key, $stats_object, $this->prefix, 1800); // 1800 = 30min
                }

                return $stats_object;
            },
            10,
            2
        );

        // core
        foreach (['comment_post', 'wp_set_comment_status'] as $fx) {
            add_action(
                $fx,
                function () {
                    wp_cache_delete('comments-0', $this->prefix);
                }
            );
        }

        // jetpack
        foreach (['unapproved_to_approved', 'approved_to_unapproved', 'spam_to_approved', 'approved_to_spam'] as $fx) {
            add_action(
                'comment_'.$fx,
                function () {
                    wp_cache_delete('comments-0', $this->prefix);
                }
            );
        }

        add_filter(
            'media_library_months_with_files',
            function () {
                $cache_group = $this->prefix.'-media';

                $months = wp_cache_get('media_library_months_with_files', $cache_group);

                if (false === $months) {
                    if (!nwdcx_wpdb($wpdb)) {
                        return $months;
                    }

                    $months = $wpdb->get_results(
                        $wpdb->prepare(
                            "SELECT DISTINCT YEAR( post_date ) AS year, MONTH( post_date ) AS month FROM `{$wpdb->posts}` WHERE post_type = %s ORDER BY post_date DESC",
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

                if (!nwdcx_wpdb($wpdb)) {
                    return;
                }

                $months = $wpdb->get_results(
                    $wpdb->prepare(
                        "SELECT DISTINCT YEAR( post_date ) AS year, MONTH( post_date ) AS month FROM `{$wpdb->posts}` WHERE post_type = %s ORDER BY post_date DESC LIMIT 1",
                        'attachment'
                    ),
                    ARRAY_A
                );

                if (empty($months) || !\is_array($months)) {
                    return;
                }

                $cache_group = $this->prefix.'-media';
                $months = array_values($months);
                $months = array_shift($months);

                $months = (object) $months;

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

        $this->cache_incr = wp_cache_get('cache_incr', $this->prefix);
        if (!is_numeric($this->cache_incr)) {
            $now = time();
            wp_cache_set('cache_incr', $now, $this->prefix);
            $this->cache_incr = $now;
        }
        $this->cache_group = $this->group_prefix.$this->cache_incr;
    }

    public function flush_cache()
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

    public function posts_request($sql, $query)
    {
        if (!nwdcx_wpdb($wpdb)) {
            return $sql;
        }

        if (apply_filters('docketcache/filter/skipposttype/postcache', false, $query->get('post_type'))) {
            return $sql;
        }

        $this->cache_key = 'query-'.substr(md5($sql), 0, 12);
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
                return "SELECT * FROM `{$wpdb->posts}` WHERE ID IN(".implode(',', array_map('absint', $uncached_post_ids)).')';
            }

            return '';
        }

        return $sql;
    }

    public function posts_results($posts, $query)
    {
        if (apply_filters('docketcache/filter/skipposttype/postcache', false, $query->get('post_type'))) {
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

        return array_map('get_post', $posts);
    }

    public function post_limits_request($limits, $query)
    {
        if (apply_filters('docketcache/filter/skipposttype/postcache', false, $query->get('post_type'))) {
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
        if (apply_filters('docketcache/filter/skipposttype/postcache', false, $query->get('post_type'))) {
            return $sql;
        }

        if ($this->found_posts && \is_array($this->all_post_ids)) {
            return '';
        }

        return $sql;
    }

    public function found_posts($found_posts, $query)
    {
        if (apply_filters('docketcache/filter/skipposttype/postcache', false, $query->get('post_type'))) {
            return $found_posts;
        }

        if ($this->found_posts && \is_array($this->all_post_ids)) {
            return (int) $this->found_posts;
        }

        $cache_key = $this->cache_key.'-found';
        \call_user_func($this->cache_func, $cache_key, (int) $found_posts, $this->cache_group);

        return $found_posts;
    }
}
