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
            'init',
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
                if (!empty($results) && \is_array($results) && !empty($results['wpcron_return'])) {
                    return true;
                }

                return false;
            },
            PHP_INT_MAX
        );
    }

    private function is_ping_request()
    {
        return !empty($_POST['ping']) && !empty($_GET['docketcache_ping']) && !empty($_SERVER['REQUEST_URI']) && false !== strpos($_SERVER['REQUEST_URI'], '/?docketcache_ping=');
    }

    private function site_url()
    {
        $scheme = $this->plugin->is_ssl() ? 'https' : null;

        return rtrim(network_site_url('', $scheme), '\\/');
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
                'REFERER' => site_url(), // current site
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

    private function get_wpcron_lock()
    {
        $value = 0;
        if (wp_using_ext_object_cache()) {
            $value = wp_cache_get('doing_cron', 'transient', true);
        } else {
            $wpdb = $this->plugin->safe_wpdb();
            if ($wpdb) {
                $row = $wpdb->get_row($wpdb->prepare('SELECT option_value FROM `'.$wpdb->options.'` WHERE option_name = %s LIMIT 1', '_transient_doing_cron'));
                if (\is_object($row)) {
                    $value = $row->option_value;
                }
            }
        }

        return $value;
    }

    private function run_wpcron($run_now = false)
    {
        $results = [
            'wpcron_return' => 0,
            'wpcron_msg' => '',
        ];

        if (false !== strpos($_SERVER['REQUEST_URI'], '/wp-cron.php') || isset($_GET['doing_wp_cron']) || $this->plugin->constans()->is_true('DOING_CRON')) {
            $results['wpcron_return'] = 1;
            $results['wpcron_msg'] = 'another process currently run';

            return $results;
        }

        $crons = $run_now ? _get_cron_array() : wp_get_ready_cron_jobs();
        if (empty($crons)) {
            $results['wpcron_return'] = 1;
            $results['wpcron_msg'] = 'no scheduled event ready to run';

            return $results;
        }

        $gmt_time = microtime(true);
        $doing_cron_transient = get_transient('doing_cron');

        if ($doing_cron_transient && ($doing_cron_transient + 60 > $gmt_time)) {
            $results['wpcron_return'] = 1;
            $results['wpcron_msg'] = 'process locked';

            return $results;
        }

        $doing_wp_cron = sprintf('%.22F', microtime(true));
        $doing_cron_transient = $doing_wp_cron;
        set_transient('doing_cron', $doing_wp_cron);

        if ($doing_cron_transient !== $doing_wp_cron) {
            $results['wpcron_return'] = 1;
            $results['wpcron_msg'] = 'process locked';

            return $results;
        }

        $run_ok = 0;
        foreach ($crons as $timestamp => $cronhooks) {
            if (!$run_now && $timestamp > $gmt_time) {
                break;
            }

            foreach ($cronhooks as $hook => $keys) {
                foreach ($keys as $k => $v) {
                    $schedule = $v['schedule'];

                    if ($schedule) {
                        wp_reschedule_event($timestamp, $schedule, $hook, $v['args']);
                    }

                    wp_unschedule_event($timestamp, $hook, $v['args']);
                    do_action_ref_array($hook, $v['args']);

                    if ($this->get_wpcron_lock() !== $doing_wp_cron) {
                        $results['wpcron_return'] = 0;
                        $results['wpcron_msg'] = 'process timeout';

                        return $results;
                    }
                }
                ++$run_ok;
            }
        }

        if ($this->get_wpcron_lock() === $doing_wp_cron) {
            delete_transient('doing_cron');
        }

        $results['wpcron_return'] = 1;
        $results['wpcron_msg'] = 'number of events: '.$run_ok;

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

        $locked = wp_cache_get('receive_ping', 'docketcache-cron');
        if (!empty($locked) && (int) $locked > time()) {
            $response['msg'] = 'already received. try again in few minutes';
            $this->close_ping($response);

            return;
        }

        $results = $this->run_wpcron();
        $response = array_merge($response, $results);

        wp_cache_set('receive_ping', time() + 30, 'docketcache-cron');

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

        $locked = wp_cache_get('check_connection', 'docketcache-cron');
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
                    wp_cache_set('check_connection', time() + 30, 'docketcache-cron');
                    $this->send_action('on', true);
                }
            }
        }
    }
}
