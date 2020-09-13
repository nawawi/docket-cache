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

class CronAgent
{
    private $backend = 'https://cronbot.docketcache.com';
    private $plugin;

    public function __construct(Plugin $plugin)
    {
        $this->plugin = $plugin;
        if ($this->plugin->constans()->is_true('DOCKET_CACHE_DEV')) {
            $this->backend = 'http://cronbot.docketcache.local';
        }
    }

    public function register()
    {
        add_action(
            'plugin_loaded',
            function () {
                $this->receive_ping();
            },
            -PHP_INT_MAX
        );

        add_action(
            'shutdown',
            function () {
                $this->check_connection();
            },
            PHP_INT_MAX
        );

        add_filter(
            'docket-cache/cronbot-active',
            function ($status) {
                $status = $status ? 'on' : 'off';

                return $this->send_action($status);
            },
            PHP_INT_MAX
        );

        add_filter(
            'docket-cache/cronbot-runevent',
            function ($status) {
                $results = $this->run_wpcron();

                if (is_wp_error($results)) {
                    return false;
                }

                if (200 !== wp_remote_retrieve_response_code($results)) {
                    return false;
                }

                return true;
            },
            PHP_INT_MAX
        );
    }

    private function is_ping_request()
    {
        return !empty($_POST['ping']) && !empty($_GET['docketcache_ping']) && !empty($_SERVER['REQUEST_URI']) && false !== strpos($_SERVER['REQUEST_URI'], '/?docketcache_ping=');
    }

    private function site_url() {
        return rtrim(network_site_url(), '\\/');
    }

    private function send_action($action, $is_hello = false)
    {
        $uip = $this->plugin->get_user_ip();

        static $cache = [];
        if (isset($cache[$uip])) {
            return $cache[$uip];
        }

        $site_url = $this->site_url();
        $site_key = substr(md5($site_url), 0, 22);
        $site_body = $this->plugin->nw_encrypt($site_url, $site_key);
        $site_id = $this->plugin->nw_encrypt($site_key, $site_body);

        $args = [
            'blocking' => $is_hello ? false : true,
            'body' => [
                'timestamp' => date('Y-m-d H:i:s T'),
                'timezone' => wp_timezone_string(),
                'site' => $this->plugin->base64_encode_url($site_body),
                'sitem' => is_multisite() ? 1 : 0,
                'status' => $action,
            ],
            'headers' => [
                'REFERER' => $site_url,
                'DOCKETID' => $site_id,
            ],
        ];

        $results = Crawler::post($this->backend, $args);

        if ($is_hello) {
            return true;
        }

        $output = [
            'created' => date('Y-m-d H:i:s T'),
            'connected' => false,
            'last_status' => $action,
            'request' => [
                'headers' => $args['headers'],
                'content' => $args['body'],
            ],
        ];

        if (is_wp_error($results)) {
            $output['error'] = $results->get_error_message();
            $this->plugin->canopt()->save_part($output, 'cronbot');
            set_transient('docketcache/cronboterror', $output['error'], 10);

            $cache[$uip] = false;

            return false;
        }

        $output['response'] = wp_remote_retrieve_body($results);
        if (!empty($output['response'])) {
            $output['response'] = json_decode($output['response'], true);
            if (JSON_ERROR_NONE === json_last_error()) {
                if (!empty($output['response']['error'])) {
                    $output['error'] = $output['response']['error'];
                    $this->plugin->canopt()->save_part($output, 'cronbot');
                    set_transient('docketcache/cronboterror', $output['error'], 10);

                    $cache[$uip] = false;

                    return false;
                }
            }
        }

        $code = (int) wp_remote_retrieve_response_code($results);
        if ($code > 400) {
            $output['error'] = $code;
            $this->plugin->canopt()->save_part($output, 'cronbot');
            set_transient('docketcache/cronboterror', $output['error'], 10);

            $cache[$uip] = false;

            return false;
        }

        $output['connected'] = 'off' === $action ? false : true;
        $this->plugin->canopt()->save_part($output, 'cronbot');

        $cache[$uip] = true;

        return true;
    }

    private function close_ping($response)
    {
        $output = $response;
        $output['request'] = array_filter(
            $_SERVER,
            function ($arr) {
                if ('HTTP_' === substr($arr, 0, 5)) {
                    return true;
                }
            },
            ARRAY_FILTER_USE_KEY
        );

        $this->plugin->canopt()->save_part($output, 'pings');

        @header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0');
        @header('Content-Type: application/json; charset=UTF-8');
        $this->plugin->close_exit(json_encode($response, JSON_UNESCAPED_SLASHES));
    }

    private function run_wpcron()
    {
        $do_fetch = false;

        if ($this->plugin->constans()->is_true('DISABLE_WP_CRON')
              || $this->plugin->constans()->is_true('ALTERNATE_WP_CRON')
              || class_exists('\\HM\\Cavalcade\\Plugin\\Job', false)
              || class_exists('\\Automattic\\WP\\Cron_Control', false)) {
            $do_fetch = true;
        }

        $wp_cron_url = network_site_url('wp-cron.php');

        $results = [];

        if ($do_fetch) {
            $results = Crawler::fetch(
                $wp_cron_url,
                [
                    'blocking' => true,
                    'timeout' => 10,
                ]
            );
        } else {
            $doing_wp_cron = sprintf('%.22F', microtime(true));
            $url = add_query_arg('doing_wp_cron', $doing_wp_cron, $wp_cron_url);
            $cron_request = apply_filters(
                'cron_request',
                [
                    'url' => $url,
                    'key' => $doing_wp_cron,
                    'args' => [
                        'timeout' => 10,
                        'blocking' => true,
                        'sslverify' => apply_filters('https_local_ssl_verify', false),
                    ],
                ]
            );

            $cron_request['args']['blocking'] = true;
            $results = Crawler::post($cron_request['url'], $cron_request['args']);
        }

        return $results;
    }

    private function receive_ping()
    {
        if (headers_sent() || !$this->is_ping_request()) {
            return;
        }

        if ($_POST['ping'] !== md5($_GET['docketcache_ping'])) {
            return;
        }

        if (!@preg_match('@compatible;\s+cronbot/[0-9\.]+;\s+docket\-cache/[0-9\.]+;\s+@', $this->plugin->get_user_agent())) {
            return;
        }

        $uip = $this->plugin->get_user_ip();

        static $cache = [];
        if (!empty($cache[$uip])) {
            return $cache[$uip];
        }

        $response = [
            'timestamp' => date('Y-m-d H:i:s T'),
            'timezone' => wp_timezone_string(),
            'site' => $this->site_url(),
            'status' => 1,
        ];

        if ($this->plugin->constans()->is_false('DOCKET_CACHE_CRONBOT')) {
            $response['status'] = 0;
            $cache[$uip] = $response;
            $this->close_ping($response);
        }

        $locked = get_transient('docketcache/recping');
        if (false === $locked) {
            set_transient('docketcache/recping', 0);
        }

        if (!empty($locked) && (int) $locked > time()) {
            $response['msg'] = 'already received. try again in few minutes';
            $this->close_ping($response);

            return;
        }

        $results = $this->run_wpcron();

        if (is_wp_error($results)) {
            $response['wpcron_return'] = 0;
            $response['wpcron_error'] = $results->get_error_message();
        } else {
            $code = wp_remote_retrieve_response_code($results);
            $response['wpcron_return'] = 200 === $code ? 1 : 0;
            $response['wpcron_code'] = $code;
        }

        set_transient('docketcache/recping', time() + 30);

        $cache[$uip] = $response;
        $this->close_ping($cache[$uip]);
    }

    private function check_connection()
    {
        if (wp_is_maintenance_mode() || $this->is_ping_request()) {
            return;
        }

        if ($this->plugin->constans()->is_true('DOCKET_CACHE_DOING_FLUSH') || $this->plugin->constans()->is_true('WP_IMPORTING')) {
            return;
        }

        $locked = get_transient('docketcache/checkconn');
        if (false === $locked) {
            set_transient('docketcache/checkconn', 0);

            return;
        }

        if (!empty($locked) && (int) $locked > time()) {
            return;
        }

        $crondata = $this->plugin->canopt()->get_part('cronbot', true);
        if (empty($crondata)) {
            return;
        }

        if (\is_array($crondata) && !empty($crondata['connected'])) {
            $pingdata = $this->plugin->canopt()->get_part('pings', true);
            if (empty($pingdata)) {
                return;
            }
            if (\is_array($pingdata) && !empty($pingdata['timestamp'])) {
                $timestamp = strtotime('+90 minutes', strtotime($pingdata['timestamp']));
                if ($timestamp > 0 && time() > $timestamp) {
                    set_transient('docketcache/checkconn', time() + 30);
                    $this->send_action('on', true);
                }
            }
        }
    }
}
