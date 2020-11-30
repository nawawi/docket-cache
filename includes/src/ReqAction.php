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

final class ReqAction
{
    private $pt;

    public function __construct(Plugin $pt)
    {
        $this->pt = $pt;
    }

    public function register()
    {
        add_action(
            'load-'.$this->pt->screen,
            function () {
                $this->parse_action();
                $this->screen_notice();
            }
        );
    }

    private function compat_notice()
    {
        static $phpver = null;
        if (null === $phpver) {
            $phpver = $this->pt->plugin_meta($this->pt->file)['RequiresPHP'];
        }

        if (!empty($phpver) && version_compare(PHP_VERSION, $phpver, '<')) {
            /* translators: %s: php version */
            add_settings_error(is_multisite() ? 'general' : '', $this->pt->slug, sprintf(__('This plugin requires PHP %s or greater.', 'docket-cache'), $phpver));
        }
    }

    private function exit_failed()
    {
        $args = [
             'message' => 'docket-action-failed',
         ];

        if (!empty($_GET['idx'])) {
            $args['idx'] = sanitize_text_field($_GET['idx']);
        }

        $query = add_query_arg($args, $this->pt->page);
        wp_safe_redirect(network_admin_url($query));
    }

    /**
     * action_token.
     */
    private function action_token()
    {
        $keys = [
             'docket-enable-occache',
             'docket-disable-occache',
             'docket-flush-occache',
             'docket-update-dropino',
             'docket-flush-oclog',
             'docket-flush-ocfile',
             'docket-flush-opcache',
             'docket-connect-cronbot',
             'docket-disconnect-cronbot',
             'docket-pong-cronbot',
             'docket-runevent-cronbot',
             'docket-runeventnow-cronbot',
             'docket-selectsite-cronbot',
             'docket-rungc',
         ];

        foreach ($this->pt->co()->keys() as $key) {
            $keys[] = 'docket-default-'.$key;
            $keys[] = 'docket-enable-'.$key;
            $keys[] = 'docket-disable-'.$key;
            $keys[] = 'docket-save-'.$key;
        }

        return $keys;
    }

    private function run_action($action, $param, &$option_name = '', &$option_value = '')
    {
        $response = '';
        switch ($action) {
            case 'docket-flush-occache':
                $result = $this->pt->flush_cache();
                $response = $result ? 'docket-occache-flushed' : 'docket-occache-flushed-failed';
                do_action('docketcache/flush-cache', $result);
                break;
            case 'docket-enable-occache':
                $result = $this->pt->cx()->install(true);
                $response = $result ? 'docket-occache-enabled' : 'docket-occache-enabled-failed';
                do_action('docketcache/object-cache-enable', $result);
                break;

            case 'docket-disable-occache':
                $result = $this->pt->cx()->uninstall();
                $response = $result ? 'docket-occache-disabled' : 'docket-occache-disabled-failed';
                do_action('docketcache/object-cache-disable', $result);
                break;

            case 'docket-update-dropino':
                $result = $this->pt->cx()->install(true);
                $response = $result ? 'docket-dropino-updated' : 'docket-dropino-updated-failed';
                do_action('docketcache/object-cache-install', $result);
                break;

            case 'docket-flush-oclog':
                $result = $this->pt->flush_log();
                $response = $result ? 'docket-log-flushed' : 'docket-log-flushed-failed';
                do_action('docketcache/flush-log', $result);
                break;

            case 'docket-flush-ocfile':
                $result = $this->pt->flush_fcache();
                $response = $result ? 'docket-file-flushed' : 'docket-file-flushed-failed';
                do_action('docketcache/flush-fcache', $result);
                break;
            case 'docket-flush-opcache':
                $result = $this->pt->flush_opcache();
                $response = $result ? 'docket-opcache-flushed' : 'docket-opcache-flushed-failed';
                do_action('docketcache/flush-opcache', $result);
                break;
            case 'docket-connect-cronbot':
                $result = apply_filters('docketcache/cronbot-active', true);
                $response = $result ? 'docket-cronbot-connect' : 'docket-cronbot-connect-failed';
                do_action('docketcache/connect-cronbot', $result);
                break;
            case 'docket-disconnect-cronbot':
                $result = apply_filters('docketcache/cronbot-active', false);
                $response = $result ? 'docket-cronbot-disconnect' : 'docket-cronbot-disconnect-failed';
                do_action('docketcache/disconnect-cronbot', $result);
                break;
            case 'docket-pong-cronbot':
                $result = apply_filters('docketcache/cronbot-pong', false);
                $response = 'docket-cronbot-pong';
                do_action('docketcache/pong-cronbot', $result);
                break;
            case 'docket-runevent-cronbot':
                $response = 'docket-cronbot-runevent-failed';
                $result = apply_filters('docketcache/cronbot-runevent', false);
                if (false !== $result) {
                    $this->pt->co()->lookup_set('cronbotrun', $result);
                    $response = 'docket-cronbot-runevent';
                }

                do_action('docketcache/runevent-cronbot', $result);
                break;
            case 'docket-runeventnow-cronbot':
                $response = 'docket-cronbot-runevent-failed';
                $result = apply_filters('docketcache/cronbot-runevent', true);
                if (false !== $result) {
                    $this->pt->co()->lookup_set('cronbotrun', $result);
                    $response = 'docket-cronbot-runevent';
                }

                do_action('docketcache/runeventnow-cronbot', $result);
                break;
            case 'docket-selectsite-cronbot':
                if (isset($param['nv'])) {
                    $nv = sanitize_text_field($param['nv']);
                    if (!is_numeric($nv)) {
                        break;
                    }

                    $this->pt->set_cron_siteid($nv);

                    $response = 'docket-cronbot-selectsite';

                    $is_switch = $this->pt->switch_cron_site();

                    @Crawler::fetch_admin(admin_url('/'));

                    if ($is_switch) {
                        restore_current_blog();
                    }
                }
                break;
            case 'docket-rungc':
                $response = 'docket-gcrun-failed';
                $result = apply_filters('docketcache/garbage-collector', true);
                if (!empty($result) && \is_object($result)) {
                    $this->pt->co()->lookup_set('gcrun', (array) $result);
                    $response = 'docket-gcrun';
                }
                break;
        }

        if (empty($response) && preg_match('@^docket-(default|enable|disable|save)-([a-z_]+)$@', $action, $mm)) {
            $nk = $mm[1];
            $nx = $mm[2];
            if (\in_array($nx, $this->pt->co()->keys())) {
                $option_name = $nx;
                if ('save' === $nk && isset($param['nv'])) {
                    $nv = sanitize_text_field($param['nv']);
                    $response = $this->pt->co()->save($nx, $nv) ? 'docket-option-save' : 'docket-option-failed';
                    $option_value = $nv;
                } else {
                    $okmsg = 'default' === $nk ? 'docket-option-default' : 'docket-option-'.$nk;
                    $response = $this->pt->co()->save($nx, $nk) ? $okmsg : 'docket-option-failed';
                }
            }
        }

        return $response;
    }

    private function parse_action()
    {
        if (isset($_GET['_wpnonce'], $_GET['action'])) {
            $action = sanitize_text_field($_GET['action']);

            if (\in_array($action, $this->action_token())) {
                if (!wp_verify_nonce($_GET['_wpnonce'], $action)) {
                    $this->exit_failed();
                    exit;
                }

                $param = $_GET;
                $option_name = '';
                $option_value = '';
                $message = $this->run_action($action, $param, $option_name, $option_value);

                if (!empty($message)) {
                    $args = [
                        'message' => $message,
                    ];

                    if (!empty($_GET['idx'])) {
                        $args['idx'] = sanitize_text_field($_GET['idx']);

                        if (!empty($_GET['quiet']) && 1 === (int) $_GET['quiet']) {
                            $args['message'] = '';
                        }

                        if (!empty($option_name)) {
                            $args['nx'] = $option_name;
                        }

                        if (!empty($option_value)) {
                            $args['nv'] = $option_value;
                        }
                    }

                    $query = add_query_arg($args, $this->pt->page);
                    wp_safe_redirect(network_admin_url($query));
                    exit;
                }
            }
        }
    }

    private function screen_notice()
    {
        $this->compat_notice();

        if (isset($_GET['message'])) {
            $token = sanitize_text_field($_GET['message']);
            $this->pt->token = $token;

            $option_name = esc_html__('Option', 'docket-cache');
            if (!empty($_GET['nx'])) {
                $nx = $this->pt->co()->keys(sanitize_text_field($_GET['nx']));
                if (!empty($nx)) {
                    $option_name = $nx;
                }
            }

            $option_value = '';
            if (!empty($_GET['nv'])) {
                $nv = sanitize_text_field($_GET['nv']);
                if (!empty($nv)) {
                    $option_value = $nv;
                }
            }

            switch ($token) {
                case 'docket-occache-enabled':
                    $message = esc_html__('Object cache enabled.', 'docket-cache');
                    break;
                case 'docket-occache-enabled-failed':
                    $error = esc_html__('Object cache could not be enabled.', 'docket-cache');
                    break;
                case 'docket-occache-disabled':
                    $message = esc_html__('Object cache disabled.', 'docket-cache');
                    break;
                case 'docket-occache-disabled-failed':
                    $error = esc_html__('Object cache could not be disabled.', 'docket-cache');
                    break;
                case 'docket-occache-flushed':
                    $message = esc_html__('Object cache was flushed.', 'docket-cache');
                    break;
                case 'docket-occache-flushed-failed':
                    $error = esc_html__('Object cache could not be flushed.', 'docket-cache');
                    break;
                case 'docket-dropino-updated':
                    $message = esc_html__('Updated object cache Drop-In and enabled Docket object cache.', 'docket-cache');
                    break;
                case 'docket-dropino-updated-failed':
                    $error = esc_html__('Object cache Drop-In could not be updated.', 'docket-cache');
                    break;
                case 'docket-log-flushed':
                    $message = esc_html__('Cache log was flushed.', 'docket-cache');
                    break;
                case 'docket-log-flushed-failed':
                    $error = esc_html__('Cache log could not be flushed.', 'docket-cache');
                    break;
                case 'docket-file-flushed':
                    $message = esc_html__('Cache file was flushed.', 'docket-cache');
                    break;
                case 'docket-file-flushed-failed':
                    $error = esc_html__('Cache file could not be flushed.', 'docket-cache');
                    break;
                case 'docket-opcache-flushed':
                    $message = esc_html__('OPcache was flushed.', 'docket-cache');
                    $this->pt->flush_opcache();
                    break;
                case 'docket-opcache-flushed-failed':
                    $error = esc_html__('OPcache could not be flushed.', 'docket-cache');
                    break;
                case 'docket-cronbot-connect':
                    $message = esc_html__('Cronbot connected.', 'docket-cache');
                    break;
                case 'docket-cronbot-connect-failed':
                    $msg = $this->pt->co()->lookup_get('cronboterror', true);
                    $errmsg = !empty($msg) ? ': '.$msg : '.';
                    /* translators: %s = error message */
                    $error = sprintf(esc_html__('Cronbot failed to connect%s', 'docket-cache'), $errmsg);
                    unset($msg, $errmsg);
                    break;
                case 'docket-cronbot-disconnect':
                    $message = esc_html__('Cronbot disconnected.', 'docket-cache');
                    break;
                case 'docket-cronbot-disconnect-failed':
                    $error = esc_html__('Cronbot failed to disconnect.', 'docket-cache');
                    break;
                case 'docket-cronbot-pong':
                    $endpoint = parse_url($this->pt->cronbot_endpoint, PHP_URL_HOST);
                    $errmsg = $this->pt->co()->lookup_get('cronboterror', true);
                    if (!empty($errmsg)) {
                        /* translators: %1$s: cronbot endpoint, %2$s = error message */
                        $error = sprintf(esc_html__('Pong from %1$s: %2$s.', 'docket-cache'), $endpoint, $errmsg);
                    } else {
                        /* translators: %s: cronbot endpoint */
                        $message = sprintf(esc_html__('Pong from %s : connected.', 'docket-cache'), $endpoint);
                    }
                    break;
                case 'docket-cronbot-runevent':
                    $message = esc_html__('Running cron successful.', 'docket-cache');
                    $msg = $this->pt->co()->lookup_get('cronbotrun', true);
                    if (!empty($msg) && \is_array($msg)) {
                        $wmsg = '';
                        if (!empty($msg['wpcron_msg'])) {
                            $wmsg = $msg['wpcron_msg'];
                        }

                        if ($msg['wpcron_return'] > 1 && !empty($wmsg)) {
                            $error = $message;
                        }

                        if (empty($error)) {
                            if (!empty($wmsg) && empty($msg['wpcron_event'])) {
                                $message = $wmsg;
                            } else {
                                /* translators: %d = cron event */
                                $message = sprintf(esc_html__('Executed a total of %d cron events', 'docket-cache'), $msg['wpcron_event']);
                            }
                        }
                    }
                    unset($msg, $wmsg);

                    break;
                case 'docket-cronbot-runevent-failed':
                    $error = esc_html__('Failed to run cron.', 'docket-cache');
                    break;
                case 'docket-cronbot-selectsite':
                    $message = '';
                    break;
                case 'docket-option-enable':
                    /* translators: %s = option name */
                    $message = sprintf(esc_html__('%s enabled.', 'docket-cache'), $option_name);
                    break;
                case 'docket-option-disable':
                    /* translators: %s = option name */
                    $message = sprintf(esc_html__('%s disabled.', 'docket-cache'), $option_name);
                    break;
                case 'docket-option-save':
                    if (!empty($option_value)) {
                        if ('default' === $option_value) {
                            $message = sprintf(esc_html__('%s resets to default.', 'docket-cache'), $option_name);
                        } else {
                            /* translators: %1$s = option name, %2$s = option_value */
                            $message = sprintf(esc_html__('%1$s set to %2$s.', 'docket-cache'), $option_name, $option_value);
                        }
                    } else {
                        /* translators: %s = option name */
                        $message = sprintf(esc_html__('%s updated.', 'docket-cache'), $option_name);
                    }
                    break;
                case 'docket-option-default':
                    /* translators: %s = option name */
                    $message = sprintf(esc_html__('%s resets to default.', 'docket-cache'), $option_name);
                    break;
                case 'docket-option-failed':
                    /* translators: %s = option name */
                    $error = sprintf(esc_html__('Failed to update option %s.', 'docket-cache'), $option_name);
                    break;
                case 'docket-action-failed':
                    /* translators: %s = option name */
                    $error = esc_html__('Failed to execute the action request. Please try again.', 'docket-cache');
                    break;
                case 'docket-gcrun':
                    $message = esc_html__('Executing the garbage collector successful', 'docket-cache');
                    $msg = $this->pt->co()->lookup_get('gcrun', true);
                    if (!empty($msg) && \is_array($msg)) {
                        $collect = (object) $msg;

                        $gcmsg = '<div class="gc"><ul>';
                        $gcmsg .= '<li><span>'.esc_html__('Cache MaxTTL', 'docket-cache').'</span>'.$collect->cache_maxttl.'</li>';
                        $gcmsg .= '<li><span>'.esc_html__('Cache File Limit', 'docket-cache').'</span>'.$collect->cache_maxfile.'</li>';
                        $gcmsg .= '<li><span>'.esc_html__('Cache Disk Limit', 'docket-cache').'</span>'.$this->pt->normalize_size($collect->cache_maxdisk).'</li>';
                        $gcmsg .= '<li><span>'.esc_html__('Cleanup Cache MaxTTL', 'docket-cache').'</span>'.$collect->cleanup_maxttl.'</li>';
                        $gcmsg .= '<li><span>'.esc_html__('Cleanup Cache File Limit', 'docket-cache').'</span>'.$collect->cleanup_maxfile.'</li>';
                        $gcmsg .= '<li><span>'.esc_html__('Cleanup Cache Precache Limit', 'docket-cache').'</span>'.$collect->cleanup_precache_maxfile.'</li>';
                        $gcmsg .= '<li><span>'.esc_html__('Cleanup Cache Disk Limit', 'docket-cache').'</span>'.$collect->cleanup_maxdisk.'</li>';
                        $gcmsg .= '<li><span>'.esc_html__('Total Cache Cleanup', 'docket-cache').'</span>'.$collect->cache_cleanup.'</li>';
                        $gcmsg .= '<li><span>'.esc_html__('Total Cache Ignored', 'docket-cache').'</span>'.$collect->cache_ignore.'</li>';
                        $gcmsg .= '<li><span>'.esc_html__('Total Cache File', 'docket-cache').'</span>'.$collect->cache_file.'</li>';
                        $gcmsg .= '</ul></div>';

                        $message .= $gcmsg;
                    }
                    unset($msg, $wmsg, $gcmsg);
                    break;
                case 'docket-gcrun-failed':
                    $error = esc_html__('Failed to run the garbage collector.', 'docket-cache');
                    break;
            }

            if (!empty($message) || !empty($error)) {
                $msg = !empty($message) ? $message : (!empty($error) ? $error : '');
                $type = !empty($message) ? 'updated' : 'error';
                add_settings_error(is_multisite() ? 'general' : '', $this->pt->slug, $msg, $type);
            }
        }
    }
}
