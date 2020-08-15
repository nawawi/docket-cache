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

class PageCache
{
    private $plugin;
    private $can_buffer;
    private $can_filter;
    private $cache_group;

    public function __construct(Plugin $plugin)
    {
        $this->plugin = $plugin;
        $this->can_buffer = false;
        $this->can_filter = false;
        $this->cache_group = 'docketcache-page';
    }

    private function can_process()
    {
        if (empty($_SERVER['REQUEST_URI'])) {
            return false;
        }

        // not from index.php
        if ($this->plugin->constans->is_false('WP_USE_THEMES')) {
            return false;
        }

        if (\function_exists('is_amp_endpoint') && is_amp_endpoint()) {
            return false;
        }

        if (\function_exists('is_user_logged_in') && is_user_logged_in()) {
            return false;
        }

        if (!empty($_COOKIE)) {
            $cookies_regex = '/^(wp-postpass|wordpress_logged_in|comment_author)_/';
            foreach ($_COOKIE as $k => $v) {
                if (preg_match($cookies_regex, $k)) {
                    return false;
                }
            }
        }

        return true;
    }

    private function get_key()
    {
        return substr(md5($_SERVER['REQUEST_URI']), 0, 12);
    }

    private function get_content()
    {
        $key = $this->get_key();
        $output = wp_cache_get($key, $this->cache_group);
        if (false !== $output) {
            echo $output;
            $timestart = $GLOBALS['timestart'];
            $timestop = microtime(true) - $timestart;

            if ($this->plugin->constans->is_true('DOCKET_CACHE_SIGNATURE')) {
                echo "\n<!-- Performance optimized by Docket PageCache: https://wordpress.org/plugins/docket-cache -->\n";
            }
            $this->plugin->fastcgi_close();
            exit;
        }
    }

    private function save_content($output)
    {
        $key = $this->get_key();

        return wp_cache_add($key, trim($output), $this->cache_group, HOUR_IN_SECONDS);
    }

    public function process()
    {
        add_action(
            'plugins_loaded',
            function () {
                if ($this->can_process()) {
                    $this->get_content();

                    ob_start(null, 700000);
                    $this->can_buffer = true;
                }
            },
            -PHP_INT_MAX
        );

        add_action(
            'wp_head',
            function () {
                if ($this->can_buffer && $this->can_process()) {
                    $this->can_filter = true;
                }
            },
            -PHP_INT_MAX
        );
        add_action(
            'shutdown',
            function () {
                if ($this->can_filter && $this->can_process()) {
                    $buffer = '';
                    $levels = ob_get_level();

                    for ($i = 0; $i < $levels; ++$i) {
                        $buffer .= ob_get_clean();
                    }

                    $buffer = trim($buffer);
                    $output = apply_filters('docket-cache/buffer', $buffer);

                    if (!empty($output)) {
                        $buffer = $output;
                    }

                    $this->save_content($buffer);

                    echo $buffer;
                }
            },
            -PHP_INT_MAX
        );

        add_filter(
            'docket-cache/buffer',
            function ($output) {
                $output = preg_replace('/<link[^>]+href=(?:\'|")https?:\/\/gmpg.org\/xfn\/11(?:\'|")(?:[^>]+)?>/', '', $output);

                return $output;
            }
        );
    }
}
