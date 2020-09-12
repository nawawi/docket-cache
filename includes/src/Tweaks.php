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

final class Tweaks
{
    private $plugin;

    public function __construct(Plugin $plugin)
    {
        $this->plugin = $plugin;
    }

    public function wpquery()
    {
        // vipcom: prevent core from doing filename lookups for media search.
        // https://core.trac.wordpress.org/ticket/39358
        add_action(
            'pre_get_posts',
            function () {
                remove_filter('posts_clauses', '_filter_query_attachment_filenames');
            },
            PHP_INT_MAX
        );

        // vipcom: improve perfomance of the _WP_Editors::wp_link_query method
        add_filter(
            'wp_link_query_args',
            function ($query) {
                $query['no_found_rows'] = true;

                return $query;
            },
            PHP_INT_MAX
        );

        // vipcom: disable custom fields meta box dropdown (very slow)
        add_filter('postmeta_form_keys', '__return_false');
    }

    public function misc()
    {
        // wp: if only one post is found by the search results, redirect user to that post
        add_action(
            'template_redirect',
            function () {
                if (is_search()) {
                    global $wp_query;
                    if (1 === (int) $wp_query->post_count && 1 === (int) $wp_query->max_num_pages) {
                        wp_redirect(get_permalink($wp_query->posts['0']->ID));
                        exit;
                    }
                }
            },
            PHP_INT_MAX
        );

        // wp: hide update notifications to non-admin users
        add_action(
            'admin_head',
            function () {
                if (!current_user_can('update_core')) {
                    remove_action('admin_notices', 'update_nag', 3);
                }
            },
            PHP_INT_MAX
        );

        // wp: header junk
        add_action(
            'after_setup_theme',
            function () {
                remove_action('wp_head', 'rsd_link');
                remove_action('wp_head', 'wp_generator');
                remove_action('wp_head', 'feed_links', 2);
                remove_action('wp_head', 'feed_links_extra', 3);
                remove_action('wp_head', 'index_rel_link');
                remove_action('wp_head', 'wlwmanifest_link');
                remove_action('wp_head', 'start_post_rel_link', 10, 0);
                remove_action('wp_head', 'parent_post_rel_link', 10, 0);
                remove_action('wp_head', 'adjacent_posts_rel_link', 10, 0);
                remove_action('wp_head', 'adjacent_posts_rel_link_wp_head', 10, 0);
                remove_action('wp_head', 'wp_shortlink_wp_head', 10, 0);
            },
            PHP_INT_MAX
        );

        // wp: disable pingback
        add_action(
            'pre_ping',
            function (&$links) {
                foreach ($links as $l => $link) {
                    if (0 === strpos($link, get_option('home'))) {
                        unset($links[$l]);
                    }
                }
            },
            PHP_INT_MAX
        );

        // wp: disable and remove do_pings
        // https://wp-mix.com/wordpress-clean-up-do_pings/
        add_action(
            'plugins_loaded',
            function () {
                if (isset($_GET['doing_wp_cron'])) {
                    remove_action('do_pings', 'do_all_pings');
                    wp_clear_scheduled_hook('do_pings');
                }
            },
            PHP_INT_MAX
        );

        // jetpack: enables object caching for the response sent by instagram when querying for instagram image html
        // https://developer.jetpack.com/hooks/instagram_cache_oembed_api_response_body/
        add_filter('instagram_cache_oembed_api_response_body', '__return_true');

        // wp: disable xmlrpc
        // https://www.wpbeginner.com/plugins/how-to-disable-xml-rpc-in-wordpress/
        // https://kinsta.com/blog/xmlrpc-php/
        if ($this->plugin->constans()->is_false('DOCKET_CACHE_TWEAKS_XMLRPC_DISABLED')) {
            add_filter('xmlrpc_enabled', '__return_false');
            add_filter('pre_update_option_enable_xmlrpc', '__return_false');
            add_filter('pre_option_enable_xmlrpc', '__return_zero');
            add_action(
                'plugins_loaded',
                function () {
                    if (isset($_SERVER['REQUEST_URI']) && '/xmlrpc.php' === $_SERVER['REQUEST_URI']) {
                        http_response_code(403);
                        exit('xmlrpc.php not available.');
                    }
                },
                PHP_INT_MAX
            );
        }

        if ($this->plugin->constans()->is_false('DOCKET_CACHE_TWEAKS_WPCOOKIE_DISABLED')) {
            // wp: comment cookie lifetime, default to 30000000 second = 12 months
            add_filter(
                'comment_cookie_lifetime',
                function () {
                    return 12 * HOUR_IN_SECONDS;
                },
                -PHP_INT_MAX
            );

            // wp: protected post, expire when browser close
            add_filter(
                'post_password_expires',
                function () {
                    return 0;
                },
                -PHP_INT_MAX
            );
        }
    }

    public function woocommerce()
    {
        if (!class_exists('woocommerce')) {
            return;
        }
        // wc: remove counts slowing down the dashboard
        remove_filter('wp_count_comments', ['WC_Comments', 'wp_count_comments'], 10);

        // wc: remove order count from admin menu
        add_filter('woocommerce_include_processing_order_count_in_menu', '__return_false');

        // wc: remove Processing Order Count in wp-admin
        //add_filter('woocommerce_menu_order_count', '__return_false');

        // wc: action_scheduler_migration_dependencies_met
        add_filter('action_scheduler_migration_dependencies_met', '__return_false');

        // wc: disable background image regeneration
        add_filter('woocommerce_background_image_regeneration', '__return_false');

        // wc: remove marketplace suggestions
        // https://rudrastyh.com/woocommerce/remove-marketplace-suggestions.html
        add_filter('woocommerce_allow_marketplace_suggestions', '__return_false');

        // wc: remove connect your store to WooCommerce.com admin notice
        add_filter('woocommerce_helper_suppress_admin_notices', '__return_true');

        // wc: disable the WooCommerce Admin
        //add_filter('woocommerce_admin_disabled', '__return_true');

        // wc: disable the WooCommere Marketing Hub
        add_filter(
            'woocommerce_admin_features',
            function ($features) {
                $marketing = array_search('marketing', $features);
                unset($features[$marketing]);

                return $features;
            }
        );
    }

    public function post_missed_schedule()
    {
        $wpdb = $this->plugin->safe_wpdb();
        if (!$wpdb) {
            return false;
        }

        // check
        $query = "SELECT ID FROM `{$wpdb->posts}` WHERE post_status='future' ORDER BY ID ASC LIMIT 1";
        $check = $wpdb->query($query);

        if ($check < 1) {
            return false;
        }

        $limit = 1000;

        $now = gmdate('Y-m-d H:i:59');
        $args = [
            'public' => true,
            'exclude_from_search' => false,
            '_builtin' => false,
        ];

        $post_types = get_post_types($args, 'names', 'and');
        if (!empty($post_types) && \is_array($post_types)) {
            $types = implode("','", $post_types);
            $query = $wpdb->prepare("SELECT ID FROM `{$wpdb->posts}` WHERE post_type in ('post','page','%s') AND post_status='future' AND post_date_gmt < %s ORDER BY ID ASC LIMIT %d", $types, $now, $limit);
        } else {
            $query = $wpdb->prepare("SELECT ID FROM `{$wpdb->posts}` WHERE post_type in ('post','page') AND post_status='future' AND post_date_gmt < %s ORDER BY ID ASC LIMIT %d", $now, $limit);
        }

        $suppress = $wpdb->suppress_errors(true);
        $results = $wpdb->get_results($query, ARRAY_A);
        $wpdb->suppress_errors($suppress);

        if (!empty($results)) {
            while ($row = @array_shift($results)) {
                $id = $row['ID'];
                wp_publish_post($id);
            }
        }

        return true;
    }

    /*public function wpoptions_size() {
        $wpdb = $this->plugni->safe_wpdb();
        if ( !$wpdb ) {
            return false;
        }

        $query = $wpdb->prepare("SELECT LENGTH(option_value), option_name FROM `{$wpdb->options}` WHERE autoload='yes' ORDER BY length(option_value) DESC");

    }*/
}
