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
    private static $version = '20.07.27';

    public static function fetch_admin($url, $param = [])
    {
        if (is_user_logged_in() && current_user_can(is_multisite() ? 'manage_network_options' : 'manage_options')) {
            return self::fetch($url, $param);
        }

        return false;
    }

    public static function fetch($url, $param = [])
    {
        $args = [
            'blocking' => false,
            'timeout' => 45,
            'httpversion' => '1.1',
            'user-agent' => 'docket-cache/'.self::$version,
            'body' => null,
            'compress' => false,
            'decompress' => false,
            'sslverify' => false,
            'stream' => false,
        ];

        if (!empty($_COOKIE) && class_exists('\\WP_Http_Cookie')) {
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

        return wp_remote_get($url, $args);
    }
}
