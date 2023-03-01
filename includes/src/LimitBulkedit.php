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

final class LimitBulkedit
{
    private $limit = 100;
    private $notice_id = 'docket-cache-notice-bulkedit';

    public function __construct()
    {
        $limit = (int) nwdcx_constval('LIMITBULKEDIT_LIMIT');
        if ($limit > 20) {
            $this->limit = $limit;
        }
    }

    public function register()
    {
        // min 20.
        if ($this->limit > 20) {
            add_action('wp_loaded', [$this, 'limit_bulk_edit_for_registered_post_types']);
        }
    }

    public function limit_bulk_edit_for_registered_post_types()
    {
        $types = get_post_types([
            'show_ui' => true,
        ]);

        foreach ($types as $type) {
            add_filter('bulk_actions-edit-'.$type, [$this, 'limit_bulk_edit']);
            add_action('admin_notices', [$this, 'bulk_edit_admin_notice']);
        }
    }

    private function cleanup_dismissed_pointers()
    {
        $id = $this->notice_id;
        $user_id = get_current_user_id();
        $pointers = array_filter(explode(',', (string) get_user_meta($user_id, 'dismissed_wp_pointers', true)));
        if (\in_array($id, $pointers, true)) {
            $index = array_search($id, $pointers);
            unset($pointers[$index]);
            $pointers = implode(',', $pointers);
            update_user_meta($user_id, 'dismissed_wp_pointers', $pointers);
        }
    }

    private function bulk_editing_is_limited()
    {
        if (isset($GLOBALS['wp_query']) && ($GLOBALS['wp_query'] instanceof WP_Query)) {
            $total_posts = $GLOBALS['wp_query']->found_posts;

            if (isset($total_posts) && $this->limit > $total_posts) {
                $this->cleanup_dismissed_pointers();

                return false;
            }
        }

        // Get default per page.
        if (!empty($_GET['post_type'])) {
            $post_type = filter_var($_GET['post_type'], \FILTER_SANITIZE_STRING);
            // See wp-admin/includes/post.php -> wp_edit_posts_query().
            $option = 'edit_'.$post_type.'_per_page';
            $per_page = (int) get_user_option($option);
            if (empty($per_page) || $per_page < 1) {
                $per_page = 20;
            }
        } else {
            $per_page = get_query_var('posts_per_page');
        }

        // Get per page when use wp_list_table.
        if (isset($GLOBALS['wp_list_table']) && ($GLOBALS['wp_list_table'] instanceof WP_Posts_List_Table)) {
            $per_page = isset($GLOBALS['wp_list_table']->_pagination_args['per_page']) ? $GLOBALS['wp_list_table']->_pagination_args['per_page'] : $per_page;
        }

        if (-1 === $per_page || $per_page > $this->limit) {
            return true;
        }

        $this->cleanup_dismissed_pointers();

        return false;
    }

    public function limit_bulk_edit($bulk_actions)
    {
        if ($this->bulk_editing_is_limited()) {
            $bulk_actions = [];
        }

        return $bulk_actions;
    }

    public function bulk_edit_admin_notice()
    {
        if (!$this->bulk_editing_is_limited()) {
            return;
        }

        $id = $this->notice_id;

        $dismissed_pointers = array_filter(explode(',', (string) get_user_meta(get_current_user_id(), 'dismissed_wp_pointers', true)));
        if (\in_array($id, $dismissed_pointers, true)) {
            return;
        }

        $msg = sprintf(
            /* translators: %d = number of items */
            __('<strong>Docket Cache:</strong> Bulk actions are disabled because more than %d items have been listed. To re-enable bulk editing, please adjust the "Number of items" setting under "Screen Options".', 'docket-cache'),
            (int) $this->limit
        );
        $js_id = esc_js($id);
        $code = '<script data-cfasync="false" data-noptimize="1" data-no-minify="1">';
        $code .= 'jQuery(document).ready(function($){$( "#'.$js_id.'" ).on( "remove",function(){$.ajax({url: ajaxurl,type:"POST",xhrFields:{withCredentials:true},data:{action:"dismiss-wp-pointer",pointer:"'.$js_id.'"}});})});';
        $code .= '</script>';

        echo Resc::boxmsg(['id' => $id, 'text' => $msg, 'extra_after' => $code], 'error', true, false, false);
    }
}
