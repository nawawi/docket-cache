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
    public function wpquery()
    {
        // vipcom: prevent core from doing filename lookups for media search.
        // https://core.trac.wordpress.org/ticket/39358
        add_action(
            'pre_get_posts',
            function () {
                if (version_compare($GLOBALS['wp_version'], '6.0.3', '>')) {
                    add_filter('wp_allow_query_attachment_by_filename', '__return_false', \PHP_INT_MAX);
                } else {
                    remove_filter('posts_clauses', '_filter_query_attachment_filenames');
                }
            },
            \PHP_INT_MAX
        );

        // vipcom: improve perfomance of the _WP_Editors::wp_link_query method
        add_filter(
            'wp_link_query_args',
            function ($query) {
                $query['no_found_rows'] = true;

                return $query;
            },
            \PHP_INT_MAX
        );

        // vipcom: disable custom fields meta box dropdown (very slow)
        add_filter('postmeta_form_keys', '__return_false');

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

        add_action('load-edit.php', function () {
            if (isset($_REQUEST['bulk_edit'])) {
                wp_defer_term_counting(true);
                add_action('shutdown', function () {
                    wp_defer_term_counting(false);
                });
            }
        }, \PHP_INT_MIN);

        if (wp_using_ext_object_cache()) {
            if (nwdcx_consfalse('TWEAKS_WPQUERY_NOFOUNDROWS_DISABLED')) {
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

            if (nwdcx_consfalse('TWEAKS_COUNT_COMMENTS_DISABLED')) {
                add_filter(
                    'wp_count_comments',
                    function ($counts = false, $post_id = 0) {
                        if (0 !== $post_id) {
                            return $counts;
                        }

                        $cache_group = 'docketcache-wpquery';
                        $cache_key = 'comments-0';
                        $stats_object = wp_cache_get($cache_key, $cache_group);

                        if (false === $stats_object) {
                            $stats = get_comment_count(0);
                            $stats['moderated'] = $stats['awaiting_moderation'];
                            unset($stats['awaiting_moderation']);
                            $stats_object = $stats;

                            wp_cache_set($cache_key, $stats_object, $cache_group, 1800); // 1800 = 30min
                        }

                        return (object) $stats_object;
                    },
                    \PHP_INT_MAX,
                    2
                );

                // core
                foreach (['comment_post', 'wp_set_comment_status'] as $fx) {
                    add_action(
                        $fx,
                        function () {
                            wp_cache_delete('comments-0', 'docketcache-wpquery');
                        }
                    );
                }

                // jetpack
                foreach (['unapproved_to_approved', 'approved_to_unapproved', 'spam_to_approved', 'approved_to_spam'] as $fx) {
                    add_action(
                        'comment_'.$fx,
                        function () {
                            wp_cache_delete('comments-0', 'docketcache-wpquery');
                        }
                    );
                }
            }

            if (nwdcx_consfalse('TWEAKS_COUNT_MEDIA_LIBRARY_DISABLED')) {
                add_filter(
                    'media_library_months_with_files',
                    function () {
                        $cache_group = 'docketcache-wpquery';

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
                            wp_cache_set('media_library_months_with_files', $months, $cache_group, 2592000); // 2592000 = 1month
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

                        $cache_group = 'docketcache-wpquery';
                        $months = array_values($months);
                        $months = array_shift($months);

                        $months = (object) $months;

                        if (!$months->year == get_the_time('Y', $post_id) && !$months->month == get_the_time('m', $post_id)) {
                            wp_cache_delete('media_library_months_with_files', $cache_group);
                        }
                    }
                );
            }
        } // wp_using_ext_object_cache
    }

    public function misc()
    {
        // wp: if only one post is found by the search results, redirect user to that post
        if (nwdcx_consfalse('TWEAKS_SINGLESEARCHREDIRECT_DISABLED')) {
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
                \PHP_INT_MAX
            );
        }

        // wp: hide update notifications to non-admin users
        add_action(
            'admin_head',
            function () {
                if (!current_user_can('update_core')) {
                    remove_action('admin_notices', 'update_nag', 3);
                }
            },
            \PHP_INT_MAX
        );

        // jetpack: enables object caching for the response sent by instagram when querying for instagram image html
        // https://developer.jetpack.com/hooks/instagram_cache_oembed_api_response_body/
        // Removed in Jetpack 9.1.0
        // add_filter('instagram_cache_oembed_api_response_body', '__return_true');

        if (nwdcx_consfalse('TWEAKS_WPCOOKIE_DISABLED')) {
            // wp: comment cookie lifetime, default to 30000000 second = 12 months
            add_filter(
                'comment_cookie_lifetime',
                function () {
                    return 12 * HOUR_IN_SECONDS;
                },
                \PHP_INT_MIN
            );

            // wp: protected post, expire when browser close
            add_filter(
                'post_password_expires',
                function () {
                    return 0;
                },
                \PHP_INT_MIN
            );
        }

        if (nwdcx_consfalse('TWEAKS_WPLOGIN_TRANSLATIONAPI_DISABLED')) {
            add_action(
                'init',
                function () {
                    add_filter(
                        'translations_api',
                        function ($type, $args) {
                            if (false !== strpos($_SERVER['REQUEST_URI'], '/wp-login.php')) {
                                return true;
                            }

                            return false;
                        },
                        \PHP_INT_MAX,
                        2
                    );
                },
                \PHP_INT_MAX
            );
        }
    }

    public function headerjunk()
    {
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
            \PHP_INT_MAX
        );

        add_filter('the_generator', '__return_empty_string', \PHP_INT_MAX);
        add_filter('x_redirect_by', '__return_false', \PHP_INT_MAX);
    }

    public function pingback()
    {
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
            \PHP_INT_MAX
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
            \PHP_INT_MAX
        );

        // vipcom: performance/do-pings.php
        // Disable pings by default.
        add_action('schedule_event', function ($event) {
            if (!\is_object($event)) {
                return $event;
            }

            if ('do_pings' === $event->hook) {
                return false;
            }

            return $event;
        });

        // vipcom: performance/do-pings.php : pre_disable_pings.
        // Hooking at 0 to get in before cron control on pre_schedule_event.
        add_filter('pre_schedule_event', function ($scheduled, $event) {
            if (null !== $scheduled) {
                return $scheduled;
            }

            if ('do_pings' === $event->hook) {
                return false;
            }

            return $scheduled;
        }, 0, 2);

        // vipcom: performance/do-pings.php : avoid new _encloseme metas.
        // https://wordpress.stackexchange.com/questions/20904/the-encloseme-meta-key-conundrum
        add_filter('add_post_metadata', function ($should_update, $object_id, $meta_key) {
            if ('_encloseme' === $meta_key) {
                $should_update = false;
            }

            return $should_update;
        }, 10, 3);

        // wp: disable xmlrpc
        // https://www.wpbeginner.com/plugins/how-to-disable-xml-rpc-in-wordpress/
        // https://kinsta.com/blog/xmlrpc-php/
        add_filter('xmlrpc_enabled', '__return_false');
        add_filter('pre_update_option_enable_xmlrpc', '__return_false');
        add_filter('pre_option_enable_xmlrpc', '__return_zero');

        // additional
        add_filter('pings_open', '__return_false');
        add_filter('pre_option_default_ping_status', '__return_zero');
        add_filter('pre_option_default_pingback_flag', '__return_zero');
        add_filter(
            'xmlrpc_methods',
            function ($methods) {
                unset($methods['pingback.ping']);
                unset($methods['pingback.extensions.getPingbacks']);
                unset($methods['wp.getUsersBlogs']);
                unset($methods['system.multicall']);
                unset($methods['system.listMethods']);
                unset($methods['system.getCapabilities']);
                unset($methods['demo.sayHello']);

                return $methods;
            }
        );

        add_action(
            'xmlrpc_call',
            function ($method) {
                if ('pingback.ping' !== $method) {
                    return;
                }
                http_response_code(403);
                exit('This site does not have pingback.');
            }
        );

        add_filter(
            'template_redirect',
            function () {
                header_remove('X-Pingback');
            },
            \PHP_INT_MAX
        );

        add_filter(
            'wp_headers',
            function ($headers) {
                unset($headers['X-Pingback']);

                return $headers;
            },
            \PHP_INT_MAX
        );

        add_action(
            'plugins_loaded',
            function () {
                if (isset($_SERVER['REQUEST_URI']) && '/xmlrpc.php' === $_SERVER['REQUEST_URI']) {
                    http_response_code(403);
                    exit('xmlrpc.php not available.');
                }

                // additional
                if (isset($_SERVER['SCRIPT_FILENAME']) && 'xmlrpc.php' === basename($_SERVER['SCRIPT_FILENAME'])) {
                    http_response_code(403);
                    exit('xmlrpc.php not available.');
                }
            },
            \PHP_INT_MAX
        );
    }

    private function has_woocommerce()
    {
        return isset($GLOBALS['woocommerce']) && \is_object($GLOBALS['woocommerce']);
    }

    public function woocommerce_misc()
    {
        // wc: action_scheduler_migration_dependencies_met
        if ('complete' === get_option('action_scheduler_migration_status')) {
            add_filter('action_scheduler_migration_dependencies_met', '__return_false', \PHP_INT_MAX);
        }

        // wc: disable background image regeneration
        add_filter('woocommerce_background_image_regeneration', '__return_false', \PHP_INT_MAX);

        // wc: remove marketplace suggestions
        // https://rudrastyh.com/woocommerce/remove-marketplace-suggestions.html
        add_filter('woocommerce_allow_marketplace_suggestions', '__return_false', \PHP_INT_MAX);

        // wc: remove connect your store to WooCommerce.com admin notice
        add_filter('woocommerce_helper_suppress_admin_notices', '__return_true', \PHP_INT_MAX);

        // wc: disable the WooCommere Marketing Hub
        add_filter(
            'woocommerce_admin_features',
            function ($features) {
                $marketing = array_search('marketing', $features);
                unset($features[$marketing]);

                return $features;
            },
            \PHP_INT_MAX
        );
        add_filter('woocommerce_marketing_menu_items', '__return_empty_array', \PHP_INT_MAX);

        // wc: Enable WooCommerce no-cache headers
        // includes/class-wc-cache-helper.php
        add_filter('woocommerce_enable_nocache_headers', '__return_false');

        // wc: remove the WooCommerce usage tracker cron event
        wp_clear_scheduled_hook('woocommerce_tracker_send_event');

        // jetpack
        add_filter('jetpack_just_in_time_msgs', '__return_false', \PHP_INT_MAX);
        add_filter('jetpack_show_promotions', '__return_false', \PHP_INT_MAX);
    }

    public function woocommerce_admin_disabled()
    {
        // wc: disable the WooCommerce Admin
        add_filter('woocommerce_admin_disabled', '__return_true', \PHP_INT_MAX);

        // 09052022: line 1048 packages/woocommerce-admin/src/Loader.php -> Undefined index: id, value
        add_filter('woocommerce_admin_preload_settings', '__return_empty_array', \PHP_INT_MAX);
    }

    public function woocommerce_dashboard_status_remove()
    {
        add_action(
            'wp_dashboard_setup',
            function () {
                if (!$this->has_woocommerce()) {
                    return;
                }

                remove_meta_box('woocommerce_dashboard_status', 'dashboard', 'normal');
                remove_meta_box('woocommerce_dashboard_recent_reviews', 'dashboard', 'normal');
                remove_meta_box('woocommerce_network_orders', 'dashboard', 'normal');
                remove_meta_box('wc_admin_dashboard_setup', 'dashboard', 'normal');
            },
            \PHP_INT_MAX
        );
    }

    public function woocommerce_widget_remove()
    {
        add_action(
            'widgets_init',
            function () {
                if (!$this->has_woocommerce()) {
                    return;
                }

                // plugins/woocommerce/includes/wc-widget-functions.php
                $widgets = [
                    'WC_Widget_Cart',
                    'WC_Widget_Layered_Nav_Filters',
                    'WC_Widget_Layered_Nav',
                    'WC_Widget_Price_Filter',
                    'WC_Widget_Product_Categories',
                    'WC_Widget_Product_Search',
                    'WC_Widget_Product_Tag_Cloud',
                    'WC_Widget_Products',
                    'WC_Widget_Recently_Viewed',
                    'WC_Widget_Top_Rated_Products',
                    'WC_Widget_Recent_Reviews',
                    'WC_Widget_Rating_Filter',
                ];
                foreach ($widgets as $widget) {
                    // remove
                    unregister_widget($widget);

                    // prevent error notice _doing_it_wrong
                    // see wp-includes/widgets.php -> the_widget()
                    register_widget($widget, null);
                }
            },
            \PHP_INT_MAX
        );

        add_action('plugins_loaded', function () {
            if (!$this->has_woocommerce()) {
                return;
            }
            remove_action('widgets_init', 'wc_register_widgets');
        }, \PHP_INT_MAX);
    }

    public function woocommerce_cart_fragments_remove()
    {
        add_action(
            'wp_enqueue_scripts',
            function () {
                $id = 'wc-cart-fragments';
                $wp_scripts = $GLOBALS['wp_scripts'];
                if (!\is_object($wp_scripts) || !isset($wp_scripts->registered[$id])) {
                    return;
                }

                $src = $wp_scripts->registered[$id]->src;
                $wp_scripts->registered[$id]->src = null;

                $code = '(function() {';
                $code .= 'var checkhash = function() {';
                $code .= 'var n = "woocommerce_cart_hash";';
                $code .= 'var h = document.cookie.match("(^|;) ?" + n + "=([^;]*)(;|$)");';
                $code .= 'return h ? h[2] : null;';
                $code .= '};';
                $code .= 'var checkscript = function() {';
                $code .= 'var src = "'.$src.'";';
                $code .= 'var id = "docket-cache-wccartfragment";';
                $code .= 'if ( null !== document.getElementById(id) ) {';
                $code .= 'return false;';
                $code .= 'if ( checkhash() ) {';
                $code .= 'var script = document.createElement("script");';
                $code .= 'script.id = id;';
                $code .= 'script.src = src;';
                $code .= 'script.async = true;';
                $code .= 'document.head.appendChild(script);';
                $code .= '}';
                $code .= '}';
                $code .= '};';
                $code .= 'checkscript();';
                $code .= 'document.addEventListener("click", function(){setTimeout(checkscript,1000);});';
                $code .= '})();';
                wp_add_inline_script('jquery', $code);
            },
            \PHP_INT_MAX
        );
    }

    public function woocommerce_crawling_addtochart_links()
    {
        add_filter('robots_txt', function ($output, $public) {
            if (!$this->has_woocommerce()) {
                return $output;
            }

            $append = '';
            if (!@preg_match('@^Disallow:\s+/\*add\-to\-cart=\*@is', $output)) {
                $append .= 'Disallow: /*'."add-to-cart=*\n";
            }

            if (!@preg_match('@^Disallow:\s+/cart/@is', $output)) {
                $append .= "Disallow: /cart/\n";
            } else {
                $cart = basename(wc_get_cart_url());
                if (!@preg_match('@^Disallow:\s+/'.$cart.'/@is', $output)) {
                    $append .= 'Disallow: /'.$cart."/\n";
                }
            }

            if (!@preg_match('@^Disallow:\s+/checkout/@is', $output)) {
                $append .= "Disallow: /checkout/\n";
            } else {
                $checkout = basename(wc_get_checkout_url());
                if (!@preg_match('@^Disallow:\s+/'.$checkout.'/@is', $output)) {
                    $append .= 'Disallow: /'.$checkout."/\n";
                }
            }

            if (!@preg_match('@^Disallow:\s+/my\-account/@is', $output)) {
                $append .= "Disallow: /my-account/\n";
            } else {
                $myaccount = basename(wc_get_page_permalink('myaccount'));
                if (!@preg_match('@^Disallow:\s+/'.$myaccount.'/@is', $output)) {
                    $append .= 'Disallow: /'.$myaccount."/\n";
                }
            }

            if (!empty($append)) {
                $addua = true;
                if (@preg_match_all('@User-agent:\s+\S+@is', $output, $mm, \PREG_SET_ORDER)) {
                    $last = end($mm);
                    if (@preg_match('@User-agent:\s+\*@i', $last[0])) {
                        $addua = false;
                    }
                }

                $output .= "\n# Added by Docket Cache\n";

                if ($addua) {
                    $output .= "User-agent: *\n";
                }

                $output .= $append;
            }

            return $output;
        }, \PHP_INT_MAX, 2);
    }

    public function woocommerce_extensionpage_remove()
    {
        add_action('admin_menu', function () {
            remove_submenu_page('woocommerce', 'wc-addons');
            remove_submenu_page('woocommerce', 'wc-addons&section=helper');
        }, \PHP_INT_MAX);
    }

    public function post_missed_schedule()
    {
        if (!nwdcx_wpdb($wpdb)) {
            return false;
        }

        $suppress = $wpdb->suppress_errors(true);

        // check
        $query = "SELECT ID FROM `{$wpdb->posts}` WHERE post_status='future' ORDER BY ID ASC LIMIT 1";
        $check = $wpdb->query($query);

        if ($check < 1) {
            return false;
        }

        $limit = 1000;
        $args = [
            'public' => true,
            'exclude_from_search' => false,
            '_builtin' => false,
        ];

        $post_types = get_post_types($args, 'names', 'and');
        $current_datetime = date('Y-m-d H:i:s');
        if (!empty($post_types) && \is_array($post_types)) {
            $types = implode("','", $post_types);
            $query = $wpdb->prepare("SELECT ID FROM `{$wpdb->posts}` WHERE post_type in ('post','page','%s') AND post_status='future' AND %s >= post_date_gmt ORDER BY ID ASC LIMIT %d", $types, $current_datetime, $limit);
        } else {
            $query = $wpdb->prepare("SELECT ID FROM `{$wpdb->posts}` WHERE post_type in ('post','page') AND post_status='future' AND %s >= post_date_gmt ORDER BY ID ASC LIMIT %d", $current_datetime, $limit);
        }

        $results = $wpdb->get_results($query, ARRAY_A);

        if (!empty($results)) {
            while ($row = @array_shift($results)) {
                $id = $row['ID'];
                wp_publish_post($id);
            }
        }

        $wpdb->suppress_errors($suppress);

        return true;
    }

    public function wpemoji()
    {
        remove_action('wp_head', 'print_emoji_detection_script', 7);
        remove_action('admin_print_scripts', 'print_emoji_detection_script');
        remove_action('wp_print_styles', 'print_emoji_styles');
        remove_action('admin_print_styles', 'print_emoji_styles');
        remove_filter('the_content_feed', 'wp_staticize_emoji');
        remove_filter('comment_text_rss', 'wp_staticize_emoji');
        remove_filter('wp_mail', 'wp_staticize_emoji_for_email');

        add_filter('emoji_svg_url', '__return_false');

        add_filter(
            'tiny_mce_plugins',
            function ($plugins) {
                if (\is_array($plugins)) {
                    return array_diff($plugins, ['wpemoji']);
                }

                return [];
            }
        );

        add_filter(
            'wp_resource_hints',
            function ($urls, $relation_type) {
                if ('dns-prefetch' === (string) $relation_type) {
                    $emoji_url = 'https://s.w.org/images/core/emoji/';
                    foreach ($urls as $key => $url) {
                        if (false !== strpos($url, $emoji_url)) {
                            unset($urls[$key]);
                        }
                    }
                }

                return $urls;
            },
            10,
            2
        );
    }

    // ref: https://wordpress.org/support/topic/syntax-error-222/
    public function wpembed_bodyclass($classes, $class = [])
    {
        foreach ($classes as $num => $name) {
            if ('wp-embed-responsive' === $name) {
                unset($classes[$num]);
            }
        }

        return $classes;
    }

    public function wpembed()
    {
        if (isset($GLOBALS['wp']) && \is_object($GLOBALS['wp']) && isset($GLOBALS['wp']->public_query_vars)) {
            $GLOBALS['wp']->public_query_vars = array_diff($GLOBALS['wp']->public_query_vars, ['embed']);
        }

        if (isset($GLOBALS['wp_embed']) && \is_object($GLOBALS['wp_embed'])) {
            remove_filter('the_content', [$GLOBALS['wp_embed'], 'autoembed'], 8);
        }

        remove_filter('the_content_feed', '_oembed_filter_feed_content');
        remove_action('plugins_loaded', 'wp_maybe_load_embeds', 0);
        add_filter('pre_option_embed_autourls', '__return_false');
        add_filter('embed_oembed_discover', '__return_false');
        remove_action('rest_api_init', 'wp_oembed_register_route');
        remove_filter('rest_pre_serve_request', '_oembed_rest_pre_serve_request');
        remove_action('wp_head', 'wp_oembed_add_discovery_links');
        remove_action('wp_head', 'wp_oembed_add_host_js');
        remove_action('embed_head', 'enqueue_embed_scripts', 1);
        remove_action('embed_head', 'print_emoji_detection_script');
        remove_action('embed_head', 'print_embed_styles');
        remove_action('embed_head', 'wp_print_head_scripts', 20);
        remove_action('embed_head', 'wp_print_styles', 20);
        remove_action('embed_head', 'wp_no_robots');
        remove_action('embed_head', 'rel_canonical');
        remove_action('embed_head', 'locale_stylesheet', 30);
        remove_action('embed_content_meta', 'print_embed_comments_button');
        remove_action('embed_content_meta', 'print_embed_sharing_button');
        remove_action('embed_footer', 'print_embed_sharing_dialog');
        remove_action('embed_footer', 'print_embed_scripts');
        remove_action('embed_footer', 'wp_print_footer_scripts', 20);
        remove_filter('excerpt_more', 'wp_embed_excerpt_more', 20);
        remove_filter('the_excerpt_embed', 'wptexturize');
        remove_filter('the_excerpt_embed', 'convert_chars');
        remove_filter('the_excerpt_embed', 'wpautop');
        remove_filter('the_excerpt_embed', 'shortcode_unautop');
        remove_filter('the_excerpt_embed', 'wp_embed_excerpt_attachment');
        remove_filter('oembed_dataparse', 'wp_filter_oembed_result');
        remove_filter('oembed_response_data', 'get_oembed_response_data_rich');
        remove_filter('pre_oembed_result', 'wp_filter_pre_oembed_result');
        remove_filter('woocommerce_short_description', 'wc_do_oembeds');

        add_filter(
            'tiny_mce_plugins',
            function ($plugins) {
                return array_diff($plugins, ['wpembed', 'wpview']);
            }
        );

        add_filter(
            'rewrite_rules_array',
            function ($rules) {
                $results = [];
                foreach ($rules as $rule => $val) {
                    if (false !== ($pos = strpos($val, '?'))) {
                        $args = explode('&', substr($val, $pos + 1));
                        if (\in_array('embed=true', $args)) {
                            continue;
                        }
                    }
                    $results[$rule] = $val;
                }

                return $results;
            }
        );

        if (\defined('DOCKET_CACHE_WPEMBED_BODYCLASS_FILTER') && DOCKET_CACHE_WPEMBED_BODYCLASS_FILTER) {
            add_filter(
                'body_class', [$this, 'wpembed_bodyclass'],
                \PHP_INT_MAX,
                2
            );
        }

        add_action(
            'wp_footer',
            function () {
                wp_dequeue_script('wp-embed');
            },
            \PHP_INT_MAX
        );
    }

    public function wpfeed()
    {
        add_action(
            'wp_loaded',
            function () {
                remove_action('wp_head', 'feed_links', 2);
                remove_action('wp_head', 'feed_links_extra', 3);
            }
        );

        add_action(
            'init',
            function () {
                if (isset($GLOBALS['wp_rewrite']) && \is_object($GLOBALS['wp_rewrite']) && isset($GLOBALS['wp_rewrite']->feeds)) {
                    $GLOBALS['wp_rewrite']->feeds = [];
                }
            }
        );

        foreach (['rdf', 'rss', 'rss2', 'atom', 'rss2_comments', 'atom_comments'] as $feed) {
            add_action(
                'do_feed_'.$feed,
                function () {
                    wp_redirect(home_url(), 302);
                    exit;
                },
                1
            );
        }
    }

    public function wplazyload()
    {
        add_filter('wp_lazy_loading_enabled', '__return_false', \PHP_INT_MAX);
        add_filter('wp_get_attachment_image_attributes', function ($attr, $attachment, $size) {
            $attr['loading'] = 'eager';

            return $attr;
        }, \PHP_INT_MAX, 3);
    }

    public function wpsitemap()
    {
        add_action(
            'init',
            function () {
                add_filter('wp_sitemaps_enabled', '__return_false');
                remove_filter('robots_txt', ['WP_Sitemaps', 'add_robots']);
            },
            \PHP_INT_MIN
        );
    }

    public function wpapppassword()
    {
        add_filter('wp_is_application_passwords_available', '__return_false', \PHP_INT_MAX);
    }

    public function wpdashboardnews()
    {
        add_action(
            'wp_dashboard_setup',
            function () {
                remove_meta_box('dashboard_primary', 'dashboard', 'side');
            },
            \PHP_INT_MAX
        );

        add_action(
            'admin_init',
            function () {
                remove_meta_box('dashboard_primary', 'dashboard-network', 'side');
            },
            \PHP_INT_MAX
        );
    }

    public function postviaemail()
    {
        add_filter('enable_post_by_email_configuration', '__return_false', \PHP_INT_MAX);
    }

    // reference:
    // wp-admin/includes/dashboard.php -> wp_check_browser_version()
    public function wpbrowsehappy()
    {
        if (empty($_SERVER['HTTP_USER_AGENT'])) {
            return;
        }

        $key = md5($_SERVER['HTTP_USER_AGENT']);

        // reference: wp-includes/option.php -> get_site_transient( $transient )
        // return an array to implying it always exists and never expires.
        add_filter('pre_site_transient_browser_'.$key, function () {
            // return an array instead of true to avoid php error
            // "Trying to access array offset on value of type bool".
            return [];
        }, \PHP_INT_MAX);
    }

    // reference:
    // wp-admin/includes/misc.php -> wp_check_php_version()
    public function wpservehappy()
    {
        $key = md5(\PHP_VERSION);
        add_filter('pre_site_transient_php_check_'.$key, function () {
            /*
             * Response should be an array with:
             *  'recommended_version' - string - The PHP version recommended by WordPress.
             *  'is_supported' - boolean - Whether the PHP version is actively supported.
             *  'is_secure' - boolean - Whether the PHP version receives security updates.
             *  'is_acceptable' - boolean - Whether the PHP version is still acceptable or warnings
             *                              should be shown and an update recommended.
             */

            return [
                'recommended_version' => '',
                'is_supported' => '',
                'is_secure' => '',
                'is_lower_than_future_minimum' => '',
                'is_acceptable' => '',
            ];
        }, \PHP_INT_MAX);

        add_action('wp_dashboard_setup', function () {
            remove_meta_box('dashboard_php_nag', 'dashboard', 'normal');
        });
    }

    // wp < 5.8
    public function http_headers_expect()
    {
        // https://github.com/WordPress/Requests/pull/454
        if (version_compare($GLOBALS['wp_version'], '5.8', '>')) {
            return false;
        }

        add_filter('http_request_args', function ($args) {
            if (!isset($args['headers']['expect'])) {
                $args['headers']['expect'] = '';

                if (\is_array($args['body'])) {
                    $bytesize = 0;
                    $iterator = new \RecursiveIteratorIterator(new \RecursiveArrayIterator($args['body']));

                    foreach ($iterator as $datum) {
                        $bytesize += \strlen((string) $datum);

                        if ($bytesize >= 1048576) {
                            $args['headers']['expect'] = '100-Continue';
                            break;
                        }
                    }
                } elseif (!empty($args['body']) && \strlen((string) $args['body']) > 1048576) {
                    $args['headers']['expect'] = '100-Continue';
                }
            }

            return $args;
        }, \PHP_INT_MAX);
    }

    public function limit_http_request()
    {
        add_action(
            'admin_init',
            function () {
                add_filter(
                    'pre_http_request',
                    function ($preempt, $parsed_args, $url) {
                        if (!is_admin()) {
                            return false;
                        }

                        if (/* 'GET' !== $parsed_args['method'] || */ $this->http_filter_bypass_url($url)) {
                            return false;
                        }

                        if (empty($GLOBALS['pagenow'])) {
                            return false;
                        }

                        $pagenow = $GLOBALS['pagenow'];
                        $pageok = [
                            'index.php' => 1,
                            'plugins.php' => 1,
                            'plugin-install.php' => 1,
                            'update.php' => 1,
                            'themes.php' => 1,
                            'admin.php' => 1,
                            'update-core.php' => 1,
                            'admin-ajax.php' => 1,
                        ];

                        if (\array_key_exists($pagenow, $pageok)) {
                            return false;
                        }

                        /*$site_host = parse_url(site_url(), \PHP_URL_HOST);
                        if ('.local' === substr($site_host, -\strlen('.local')) || '.test' === substr($site_host, -\strlen('.test'))) {
                            return false;
                        }*/

                        $url_host = parse_url($url, \PHP_URL_HOST);

                        $is_block = true;
                        $wkey = nwdcx_constfx('LIMITHTTPREQUEST_WHITELIST');
                        if (\defined($wkey)) {
                            $whitelist = \constant($wkey);
                            if (!empty($whitelist) && \is_array($whitelist)) {
                                foreach ($whitelist as $host) {
                                    $host = nwdcx_noscheme($host);
                                    if ($url_host === $host) {
                                        $is_block = false;
                                        break;
                                    }

                                    if ('.' === $host[0] && $host === substr($url_host, -\strlen($host))) {
                                        $is_block = false;
                                        break;
                                    }
                                }
                            }
                        }

                        if ($is_block) {
                            nwdcx_debuglog('Tweaks::limit_http_request(): Blocked -> '.$url_host);
                        }

                        return $is_block;
                    },
                    \PHP_INT_MIN,
                    3
                );
            },
            \PHP_INT_MAX
        );
    }

    public function cache_http_response()
    {
        add_action('init', function () {
            add_filter('http_response', function ($response, $parsed_args, $url) {
                if (/* 'GET' !== $parsed_args['method'] || */ $this->http_filter_bypass_url($url)) {
                    return $response;
                }

                $cache_key = 'docketcache-httpresponse_'.md5($url);

                if (200 !== $response['response']['code']) {
                    delete_transient($cache_key);

                    return $response;
                }

                $cache_ttl = (int) nwdcx_constval('CACHEHTTPRESPONSE_TTL');
                if (empty($cache_ttl)) {
                    $cache_ttl = 300;
                }

                $include_list = nwdcx_constval('CACHEHTTPRESPONSE_INCLUDE');
                $exclude_list = nwdcx_constval('CACHEHTTPRESPONSE_EXCLUDE');

                if (empty($include_list) && empty($exclude_list)) {
                    set_transient($cache_key, $response, $cache_ttl);

                    return $response;
                }

                if (!empty($include_list) && \is_array($include_list) && \in_array($url, $include_list)) {
                    if (!empty($exclude_list) && \is_array($exclude_list) && !\in_array($url, $exclude_list)) {
                        set_transient($cache_key, $response, $cache_ttl);
                    }

                    return $response;
                }

                if (!empty($exclude_list) && \is_array($exclude_list) && !\in_array($url, $exclude_list)) {
                    set_transient($cache_key, $response, $cache_ttl);

                    return $response;
                }

                return $response;
            }, \PHP_INT_MIN, 3);

            add_filter('pre_http_request', function ($preempt, $parsed_args, $url) {
                if (/* 'GET' !== $parsed_args['method'] || */ $this->http_filter_bypass_url($url)) {
                    return $preempt;
                }

                $cache_key = 'docketcache-httpresponse_'.md5($url);
                $data = get_transient($cache_key);
                if (!empty($data) && \is_array($data)) {
                    nwdcx_debuglog('Tweaks::cache_http_response(): Cached -> '.$url);

                    return $data;
                }

                return $preempt;
            }, \PHP_INT_MIN, 3);
        }, \PHP_INT_MAX);
    }

    private function http_filter_bypass_url($url)
    {
        $hosts = [
            'wordpress.org',
            'docketcache.com',
            'paypal.com',
            'braintree-api.com',
            'stripe.com',
            'cloudflare.com',
            'woocommerce.com',
        ];

        $hosts = apply_filters('docketcache/filter/cache_http_response_bypass_url', $hosts);

        $site_host = wp_parse_url(site_url(), \PHP_URL_HOST);
        $url_host = wp_parse_url($url, \PHP_URL_HOST);

        if ('127.0.0.1' === $url_host || 'localhost' === $url_host || $site_host === $url_host) {
            return true;
        }

        foreach ($hosts as $host) {
            if ($host === $url_host || '.'.$host === substr($url_host, -\strlen('.'.$host))) {
                return true;
            }
        }

        return false;
    }
}
