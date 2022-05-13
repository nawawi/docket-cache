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

final class MenuCache
{
    public function __construct()
    {
    }

    public function register()
    {
        if ($this->is_front_end()) {
            add_action('init', function () {
                add_filter('pre_wp_nav_menu', [$this, 'pre_wp_nav_menu'], \PHP_INT_MAX, 2);
                add_filter('wp_nav_menu', [$this, 'wp_nav_menu'], \PHP_INT_MAX, 2);
            }, \PHP_INT_MAX);
        }

        add_action('admin_init', function () {
            add_action('wp_update_nav_menu', [$this, 'wp_update_nav_menu'], \PHP_INT_MAX);
            add_action('wp_delete_nav_menu', [$this, 'wp_update_nav_menu'], \PHP_INT_MAX);
            add_filter('pre_set_theme_mod_nav_menu_locations', [$this, 'pre_set_theme_mod_nav_menu_locations'], \PHP_INT_MAX, 2);
        }, \PHP_INT_MAX);
    }

    private function is_front_end()
    {
        if (!is_admin()
            && (!\defined('WP_CLI') || !WP_CLI)
            && (!\defined('DOING_AJAX') || !DOING_AJAX)) {
            return true;
        }

        if (!empty($_SERVER['REQUEST_URI']) && \function_exists('rest_get_url_prefix')) {
            $rest_prefix = rest_get_url_prefix();

            return substr($_SERVER['REQUEST_URI'], 1, \strlen($rest_prefix)) === $rest_prefix;
        }

        return false;
    }

    private function normalize_menu_object(&$args)
    {
        $menu = wp_get_nav_menu_object($args->menu);

        $locations = get_nav_menu_locations();
        if (!$menu && $args->theme_location && $locations && isset($locations[$args->theme_location])) {
            $menu = wp_get_nav_menu_object($locations[$args->theme_location]);
        }

        if (!$menu && !$args->theme_location) {
            $menus = wp_get_nav_menus();
            foreach ($menus as $menu_maybe) {
                $menu_items = wp_get_nav_menu_items($menu_maybe->term_id, ['update_post_term_cache' => false]);
                if ($menu_items) {
                    $menu = $menu_maybe;
                    break;
                }
            }
        }

        if (empty($args->menu) || is_numeric($args->menu)) {
            $args->menu = $menu;
        }
    }

    private function set_key($args)
    {
        return md5(wp_json_encode($args));
    }

    private function get_key($menu_key)
    {
        return 'docketcache-menu-'.$menu_key;
    }

    private function get_cache($menu_key)
    {
        return wp_cache_get($this->get_key($menu_key), 'docketcache-menu');
    }

    private function navmenu_flush_cache()
    {
        if (\function_exists('wp_cache_flush_group')) {
            wp_cache_flush_group('docketcache-menu');
        }
    }

    public function pre_wp_nav_menu($output, $args)
    {
        $this->normalize_menu_object($args);
        if (isset($args->menu->term_id)) {
            $menu_key = $this->set_key($args);
            if ($cached_output = $this->get_cache($menu_key)) {
                if (nwdcx_construe('SIGNATURE')) {
                    $cached_output .= '<!-- This menu is cached by Docket Cache -->'.\PHP_EOL;
                }

                return $cached_output;
            }
        }

        return $output;
    }

    public function wp_nav_menu($nav_menu, $args)
    {
        if (false !== strpos($nav_menu, 'no-cache')) {
            return $nav_menu;
        }

        if (isset($args->menu->term_id)) {
            $menu_key = $this->set_key($args);
            $cache_ttl = nwdcx_constval('MENUCACHE_TTL');
            if (empty($cache_ttl) || !\is_int($cache_ttl)) {
                $cache_ttl = 1209600; // 14 days
            }

            wp_cache_set($this->get_key($menu_key), $nav_menu, 'docketcache-menu', $cache_ttl);
        }

        return $nav_menu;
    }

    public function wp_update_nav_menu($menu_id)
    {
        $this->navmenu_flush_cache();
    }

    public function pre_set_theme_mod_nav_menu_locations($new, $old)
    {
        $this->navmenu_flush_cache();

        return $new;
    }
}
