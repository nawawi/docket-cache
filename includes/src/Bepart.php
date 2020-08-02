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
}