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

final class TermCount
{
    public $counted_status;
    public $counted_terms;

    public function __construct()
    {
        $this->counted_status = ['publish'];
        $this->counted_terms = [];
    }

    public function register()
    {
        if (nwdcx_construe('WP_CLI') || nwdcx_construe('WP_IMPORTING') || wp_doing_cron()) {
            return;
        }

        if (!nwdcx_wpdb()) {
            return;
        }

        add_action(
            'init',
            function () {
                wp_defer_term_counting(true);
                remove_action('transition_post_status', '_update_term_count_on_transition_post_status');

                add_action('transition_post_status', [$this, 'transition_post_status'], 10, 3);
                add_action('added_term_relationship', [$this, 'added_term_relationship'], 10, 3);
                add_action('deleted_term_relationships', [$this, 'deleted_term_relationships'], 10, 3);
                add_action('edit_term', [$this, 'maybe_recount_posts_for_term'], 10, 3);
            }
        );
    }

    public function added_term_relationship($object_id, $tt_id, $taxonomy)
    {
        $this->handle_term_relationship_change($object_id, (array) $tt_id, $taxonomy, 'increment');
    }

    public function deleted_term_relationships($object_id, $tt_ids, $taxonomy)
    {
        $this->handle_term_relationship_change($object_id, $tt_ids, $taxonomy, 'decrement');
    }

    private function handle_term_relationship_change($object_id, $tt_ids, $taxonomy, $transition_type)
    {
        $post = get_post($object_id);

        if (!$post || !is_object_in_taxonomy($post->post_type, $taxonomy)) {
            $this->quick_update_terms_count($object_id, $tt_ids, $taxonomy, $transition_type);
        } elseif (\in_array(get_post_status($post), $this->counted_status, true)) {
            $this->quick_update_terms_count($object_id, $tt_ids, $taxonomy, $transition_type);
        } else {
            clean_term_cache($tt_ids, $taxonomy, false);
        }
    }

    public function transition_post_status($new_status, $old_status, $post)
    {
        $object_taxonomies = (array) get_object_taxonomies($post->post_type);
        foreach ($object_taxonomies as $taxonomy) {
            $tt_ids = wp_get_object_terms($post->ID, $taxonomy, ['fields' => 'tt_ids']);

            if (!empty($tt_ids) && !is_wp_error($tt_ids)) {
                $this->quick_update_terms_count(
                    $post->ID,
                    $tt_ids,
                    $taxonomy,
                    $this->transition_type($new_status, $old_status)
                );
            }
        }

        if ('attachment' !== $post->post_type) {
            $attachments = new \WP_Query(
                [
                    'post_type' => 'attachment',
                    'post_parent' => $post->ID,
                    'post_status' => 'inherit',
                    'ignore_sticky_posts' => true,
                    'no_found_rows' => true,
                    'posts_per_page' => -1,
                    'fields' => 'ids',
                    'orderby' => 'ID',
                    'order' => 'ASC',
                ]
            );

            if ($attachments->have_posts()) {
                foreach ($attachments->posts as $attachment_id) {
                    $this->transition_post_status(
                        $new_status,
                        $old_status,
                        (object) [
                            'ID' => $attachment_id,
                            'post_type' => 'attachment',
                        ]
                    );
                }
            }
        }
    }

    public function quick_update_terms_count($object_id, $tt_ids, $taxonomy, $transition_type)
    {
        if (!nwdcx_wpdb($wpdb)) {
            return;
        }

        if (!$transition_type) {
            return;
        }

        $taxonomy_get = get_taxonomy($taxonomy);
        if ($taxonomy_get) {
            $tt_ids = array_filter(array_map('intval', (array) $tt_ids));

            if (!empty($taxonomy_get->update_count_callback)) {
                \call_user_func($taxonomy_get->update_count_callback, $tt_ids, $taxonomy_get);
            } elseif (!empty($tt_ids)) {
                if (!isset($this->counted_terms[$object_id][$taxonomy][$transition_type])) {
                    $this->counted_terms[$object_id][$taxonomy][$transition_type] = [];
                }

                $tt_ids = array_diff($tt_ids, $this->counted_terms[$object_id][$taxonomy][$transition_type]);

                if (empty($tt_ids)) {
                    return;
                }

                $this->counted_terms[$object_id][$taxonomy][$transition_type] = array_merge(
                    $this->counted_terms[$object_id][$taxonomy][$transition_type],
                    $tt_ids
                );

                $tt_ids = array_map('absint', $tt_ids);
                $tt_ids_string = '('.implode(',', $tt_ids).')';

                $taxonomy_table = $wpdb->term_taxonomy;
                if ('increment' === $transition_type) {
                    $update_query = sprintf('UPDATE `%s` AS tt SET tt.count = tt.count + 1 WHERE tt.term_taxonomy_id IN %s', $taxonomy_table, $tt_ids_string);
                } else {
                    $update_query = sprintf('UPDATE `%s` AS tt SET tt.count = tt.count - 1 WHERE tt.term_taxonomy_id IN %s AND tt.count > 0', $taxonomy_table, $tt_ids_string);
                }

                foreach ($tt_ids as $tt_id) {
                    do_action('edit_term_taxonomy', $tt_id, $taxonomy);
                }

                $wpdb->query($update_query);
                foreach ($tt_ids as $tt_id) {
                    do_action('edited_term_taxonomy', $tt_id, $taxonomy);
                }
            }

            clean_term_cache($tt_ids, $taxonomy, false);
        }
    }

    public function transition_type($new, $old)
    {
        if (!\is_array($this->counted_status) || !$this->counted_status) {
            return false;
        }

        $new_is_counted = \in_array($new, $this->counted_status, true);
        $old_is_counted = \in_array($old, $this->counted_status, true);

        if ($new_is_counted && !$old_is_counted) {
            return 'increment';
        } elseif ($old_is_counted && !$new_is_counted) {
            return 'decrement';
        }

        return false;
    }

    public function maybe_recount_posts_for_term($term_id, $tt_id, $taxonomy)
    {
        $screen = \function_exists('get_current_screen') ? get_current_screen() : '';
        if (!($screen instanceof \WP_Screen)) {
            return false;
        }
        if ('edit-'.$taxonomy === $screen->id) {
            wp_update_term_count_now([$tt_id], $taxonomy);
        }

        return true;
    }
}
