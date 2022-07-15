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

final class Crawler
{
    private static $version = '22.07.01';
    public static $send_cookie = false;

    private static function default_args($param = [])
    {
        $args = [
            'blocking' => false,
            'timeout' => 15,
            'httpversion' => '1.1',
            'user-agent' => 'Mozilla/5.0 (compatible; docket-cache/'.self::$version.'; +https://docketcache.com)',
            'body' => null,
            'compress' => false,
            'decompress' => false,
            'sslverify' => apply_filters('https_local_ssl_verify', false),
            'stream' => false,
            'headers' => [
                'REFERER' => home_url(),
                'Cache-Control' => 'no-cache',
            ],
        ];

        if (self::$send_cookie && !empty($_COOKIE) && class_exists('\\WP_Http_Cookie')) {
            $cookies = [];
            foreach ($_COOKIE as $name => $value) {
                $cookies[] = new \WP_Http_Cookie(
                    [
                        'name' => $name,
                        'value' => $value,
                    ]
                );
            }

            if (!empty($cookies)) {
                $args['cookies'] = $cookies;
            }
        }

        if (!empty($param) && \is_array($param)) {
            $args = array_merge($args, $param);
        }

        return $args;
    }

    public static function fetch_admin($url, $param = [])
    {
        if (is_user_logged_in() && current_user_can(is_multisite() ? 'manage_network_options' : 'manage_options')) {
            $param['timeout'] = 3;

            self::$send_cookie = true;

            return self::fetch($url, $param);
        }

        return false;
    }

    public static function fetch_home($param = [])
    {
        self::$send_cookie = true;
        $param['timeout'] = 3;

        return self::fetch(home_url('/'), $param);
    }

    public static function fetch($url, $param = [])
    {
        $args = self::default_args($param);

        return wp_remote_get($url, $args);
    }

    public static function post($url, $param = [])
    {
        $args = self::default_args($param);

        return wp_remote_post($url, $args);
    }

    public static function fetch_home_nocache($param = [])
    {
        self::$send_cookie = true;
        $param['timeout'] = 3;
        $param['headers'] = [
            'REFERER' => home_url(),
            'Cache-Control' => 'no-cache',
        ];

        $path = '/?nocache='.time();

        return self::fetch(home_url($path), $param);
    }
}
