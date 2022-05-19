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
     * json_header.
     */
    public function json_header()
    {
        if (!headers_sent()) {
            @header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0');
            @header('Content-Type: application/json; charset=UTF-8');
        }
    }

    /**
     * send_json_continue.
     */
    public function send_json_continue($msg, $success = true)
    {
        $this->json_header();

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
            'VerPrev' => 'VerPrev',
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

        $repeat_funcs = [];

        $code = '<script id="docket-cache-worker" data-noptimize="1">'.\PHP_EOL;
        $code .= 'if ( "undefined" !== typeof(jQuery) && "undefined" !== typeof(docket_cache_config) && "function" === typeof(docket_cache_worker) ) {'.\PHP_EOL;
        $code .= '    jQuery( document ).ready( function() {'.\PHP_EOL;
        $code .= '        var config = docket_cache_config;'.\PHP_EOL;
        foreach ($types as $type) {
            if (false !== strpos($type, 'repeat_')) {
                $repeat_funcs[] = str_replace('repeat_', '', $type);
                continue;
            }
            $code .= '        docket_cache_worker( "'.$type.'", config );'.\PHP_EOL;
        }

        if (!empty($repeat_funcs)) {
            foreach ($repeat_funcs as $func) {
                $code .= '        if ( location.href.match(/admin\.php\?page=docket\-cache/) ) {'.\PHP_EOL;
                $code .= '            docket_cache_worker( "'.$func.'", config );'.\PHP_EOL;
                $code .= '            window.setInterval(function() { docket_cache_worker( "'.$func.'", config ); }, 60000);'.\PHP_EOL;
                $code .= '        }'.\PHP_EOL;
            }
        }

        $code .= '    });'.\PHP_EOL;
        $code .= '}'.\PHP_EOL;
        $code .= '</script>';

        return $code;
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

                if (false !== filter_var($ip, \FILTER_VALIDATE_IP)) {
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

            return $ipr == $ipl ? true : false;
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

    public function get_network_sites(&$counts = 0, $all = false)
    {
        $data = [];
        $cnt = 0;
        if (is_multisite()) {
            $args = [
                'no_found_rows' => true,
            ];

            if (!$all) {
                $args['network_id'] = get_current_network_id();
            }

            $sites = get_sites($args);
            if (!empty($sites) && \is_array($sites)) {
                $main_site_id = get_main_site_id();
                foreach ($sites as $num => $site) {
                    $data[$num]['id'] = $site->blog_id;
                    switch_to_blog($site->blog_id);
                    $data[$num]['url'] = get_option('siteurl');
                    restore_current_blog();
                    $data[$num]['is_main'] = 0;
                    if ((int) $site->blog_id === (int) $main_site_id) {
                        $data[$num]['is_main'] = 1;
                    }
                    ++$cnt;
                }
            }
        } else {
            $data[0] = [
                'id' => get_current_blog_id(),
                'url' => get_option('siteurl'),
                'is_main' => 1,
            ];
            $cnt = 1;
        }

        $counts = $cnt;

        return $data;
    }

    public function get_crons($all = false, &$count_all = 0, &$count_run = 0)
    {
        $cron_array = _get_cron_array();
        if (empty($cron_array)) {
            $count_all = 0;
            $count_run = 0;

            return false;
        }

        $count_all = \count($cron_array);

        $gmt_time = microtime(true);
        $crons = $cron_array;
        $cnt = 0;
        foreach ($cron_array as $timestamp => $cronhooks) {
            if (!$all && $timestamp > $gmt_time) {
                unset($crons[$timestamp]);
                continue;
            }

            foreach ($cronhooks as $hook => $keys) {
                if (!has_action($hook)) {
                    //wp_clear_scheduled_hook($hook);
                    unset($crons[$timestamp]);
                    continue;
                }
                ++$cnt;
            }
        }

        unset($cron_array);

        $count_run = $cnt;

        return $crons;
    }

    public function get_utc_offset()
    {
        $offset = get_option('gmt_offset', 0);

        if (empty($offset)) {
            return 'UTC';
        }

        if (0 <= $offset) {
            $formatted_offset = '+'.(string) $offset;
        } else {
            $formatted_offset = (string) $offset;
        }
        $formatted_offset = str_replace(
            ['.25', '.5', '.75'],
            [':15', ':30', ':45'],
            $formatted_offset
        );

        return 'UTC'.$formatted_offset;
    }

    public function get_timezone_name()
    {
        $timezone_string = get_option('timezone_string', '');
        $gmt_offset = get_option('gmt_offset', 0);

        if ('UTC' === $timezone_string || (empty($gmt_offset) && empty($timezone_string))) {
            return 'UTC';
        }

        if ('' === $timezone_string) {
            return $this->get_utc_offset();
        }

        return sprintf(
            '%s, %s',
            str_replace('_', ' ', $timezone_string),
            $this->get_utc_offset()
        );
    }

    public function normalize_content($content)
    {
        return str_replace(["\r\n", "\n\r", "\r", "\n"], "\n", $content);
    }
}
