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
    private static $version = '26.04.09';
    public static $send_cookie = false;

    private static function is_local_url($url)
    {
        $host = wp_parse_url($url, \PHP_URL_HOST);
        if (empty($host)) {
            return false;
        }

        foreach ([home_url(), site_url(), network_home_url()] as $local) {
            if (0 === strcasecmp($host, (string) wp_parse_url($local, \PHP_URL_HOST))) {
                return true;
            }
        }

        return false;
    }

    private static function default_args($url, $param = [])
    {
        $args = [
            'blocking' => false,
            'timeout' => 15,
            'httpversion' => '1.1',
            'user-agent' => 'Mozilla/5.0 (compatible; docket-cache/'.self::$version.'; +https://docketcache.com)',
            'body' => null,
            'compress' => false,
            'decompress' => false,
            // only loopback requests may skip certificate verification.
            'sslverify' => self::is_local_url($url) ? apply_filters('https_local_ssl_verify', false) : apply_filters('https_ssl_verify', true),
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
        $args = self::default_args($url, $param);

        return wp_remote_get($url, $args);
    }

    public static function post($url, $param = [])
    {
        $args = self::default_args($url, $param);

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
