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

class Bepart extends Filesystem
{
    /**
     * fastcgi_close.
     */
    public function fastcgi_close()
    {
        if ((\PHP_SAPI === 'fpm-fcgi')
            && \function_exists('fastcgi_finish_request')) {
            @session_write_close();
            @fastcgi_finish_request();
        }
    }

    /**
     * close_exit.
     */
    public function close_exit($msg = '')
    {
        if (!empty($msg)) {
            echo $msg;
        }
        $this->fastcgi_close();
        exit;
    }

    /**
     * send_json_continue.
     */
    public function send_json_continue($msg, $success = true)
    {
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=UTF-8');
        }

        $response = ['success' => $success];
        $response['data'] = $msg;
        echo wp_json_encode($response);
        $this->fastcgi_close();
    }

    /**
     * plugin_meta.
     */
    public function plugin_meta($file)
    {
        if (!@is_file($file)) {
            return;
        }

        static $cache = [];

        if (isset($cache[$file])) {
            return $cache[$file];
        }

        $default_headers = [
            'Name' => 'Plugin Name',
            'PluginURI' => 'Plugin URI',
            'Version' => 'Version',
            'Description' => 'Description',
            'Author' => 'Author',
            'AuthorURI' => 'Author URI',
            'TextDomain' => 'Text Domain',
            'DomainPath' => 'Domain Path',
            'Network' => 'Network',
            'RequiresWP' => 'Requires at least',
            'RequiresPHP' => 'Requires PHP',
        ];

        $cache[$file] = get_file_data($file, $default_headers);

        return $cache[$file];
    }

    /**
     * code_worker.
     */
    public function code_worker($types = '')
    {
        $types = (array) $types;
        if (empty($types)) {
            return;
        }

        $code = '<script>'.PHP_EOL;
        $code .= 'if ( "undefined" !== typeof(jQuery) && "undefined" !== typeof(docket_cache_config) && "function" === typeof(docket_cache_worker) ) {'.PHP_EOL;
        $code .= '    jQuery( document ).ready( function() {'.PHP_EOL;
        $code .= '        var config = docket_cache_config;'.PHP_EOL;
        foreach ($types as $type) {
            $code .= '        docket_cache_worker( "'.$type.'", config );'.PHP_EOL;
        }
        $code .= '    });'.PHP_EOL;
        $code .= '}'.PHP_EOL;
        $code .= '</script>';

        return $code;
    }

    public function safe_wpdb()
    {
        if (!isset($GLOBALS['wpdb']) || !$GLOBALS['wpdb']->ready) {
            return false;
        }

        return $GLOBALS['wpdb'];
    }

    /**
     * get_user_ip.
     */
    public function get_user_ip()
    {
        foreach ([
            'HTTP_CF_CONNECTING_IP',
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_X_REAL_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR',
        ] as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = explode(',', $_SERVER[$key]);
                $ip = end($ip);

                if (false !== filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return '0.0.0.0';
    }

    /**
     * get_proxy_ip.
     */
    public function get_proxy_ip()
    {
        $ip = wp_cache_get('proxy-ip', 'docketcache-data');
        if (false !== $ip) {
            return $ip;
        }

        if (!empty($_SERVER['HTTP_HOST'])) {
            $ip = gethostbyname($_SERVER['HTTP_HOST']);
        } elseif (!empty($_SERVER['SERVER_ADDR'])) {
            $ip = $_SERVER['SERVER_ADDR'];
        }

        if (!empty($ip)) {
            wp_cache_set('proxy-ip', $ip, 'docketcache-data', 3600);
        }

        return $ip;
    }

    /**
     * is_cloudflare.
     */
    public function is_cloudflare()
    {
        if (!empty($_SERVER['HTTP_CF_RAY'])) {
            $rip = $this->get_proxy_ip();

            return $rip.' [Ray ID: '.$_SERVER['HTTP_CF_RAY'].']';
        }

        return false;
    }

    /**
     * is_behind_proxy.
     */
    public function is_behind_proxy()
    {
        if (!empty($_SERVER['HTTP_CF_RAY'])) {
            return true;
        }

        $sipr = (!empty($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : null);
        $sipl = (!empty($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : null);

        if (null !== $sipr && null !== $sipl) {
            $ipr = explode(',', $sipr);
            $ipr = end($ipr);

            $ipl = $sipl;

            return  $ipr == $ipl ? true : false;
        }

        return false;
    }

    /**
     * get_server_software.
     */
    public function get_server_software()
    {
        if (!empty($_SERVER['SERVER_SOFTWARE'])) {
            $data = explode(' ', $_SERVER['SERVER_SOFTWARE']);

            return str_replace('/', ' / ', $data[0]);
        }

        return 'Unknown';
    }

    /**
     * get_user_agent.
     */
    public function get_user_agent()
    {
        return !empty($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'Unknown';
    }

    /**
     * base64_encode_url.
     */
    public function base64_encode_url($string)
    {
        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($string));
    }

    /**
     * base64_decode_url.
     */
    public function base64_decode_url($string)
    {
        return base64_decode(str_replace(['-', '_'], ['+', '/'], $string));
    }

    /**
     * nw_encrypt.
     */
    public function nw_encrypt($string, $epad = '!!$$surya16!!')
    {
        $mykey = '!!$'.$epad.'!!';
        $pad = base64_decode($mykey);
        $encrypted = '';
        for ($i = 0; $i < \strlen($string); ++$i) {
            $encrypted .= @\chr(@\ord($string[$i]) ^ @\ord($pad[$i]));
        }

        return $this->base64_encode_url($encrypted);
    }

    /**
     * nw_decrypt.
     */
    public function nw_decrypt($string, $epad = '!!$$surya16!!')
    {
        $mykey = '!!$'.$epad.'!!';
        $pad = base64_decode($mykey);
        $encrypted = $this->base64_decode_url($string);
        $decrypted = '';
        for ($i = 0; $i < \strlen($encrypted); ++$i) {
            $decrypted .= @\chr(@\ord($encrypted[$i]) ^ @\ord($pad[$i]));
        }

        return $decrypted;
    }

    /**
     * is_ssl.
     */
    public function is_ssl()
    {
        // cloudflare
        if (!empty($_SERVER['HTTP_CF_VISITOR'])) {
            $cfo = json_decode($_SERVER['HTTP_CF_VISITOR']);
            if (isset($cfo->scheme) && 'https' === $cfo->scheme) {
                return true;
            }
        }

        // other proxy
        if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && 'https' === $_SERVER['HTTP_X_FORWARDED_PROTO']) {
            return true;
        }

        return \function_exists('is_ssl') ? is_ssl() : false;
    }
}
