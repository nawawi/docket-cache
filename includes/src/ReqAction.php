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
            'load-'.$this->pt->get_screen(),
            function () {
                $this->parse_action();
                $this->screen_notice();
            }
        );
    }

    private function exit_failed()
    {
        $args = [
             'message' => 'docket-action-failed',
         ];

        if (!empty($_GET['idx'])) {
            $args['idx'] = sanitize_text_field($_GET['idx']);
        }

        if (!empty($param['adx'])) {
            $args['adx'] = sanitize_text_field($param['adx']);
        }

        $query = add_query_arg($args, $this->pt->get_page());
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
             'docket-dismiss-dropino',
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
             'docket-runtime',
             'docket-configreset',
             'docket-cleanuppost',
         ];

        $keys = apply_filters('docketcache/filter/reqaction/token', $keys);

        foreach ($this->pt->co()->keys() as $key) {
            $keys[] = 'docket-default-'.$key;
            $keys[] = 'docket-enable-'.$key;
            $keys[] = 'docket-disable-'.$key;
            $keys[] = 'docket-save-'.$key;
        }

        return array_unique($keys);
    }

    private function run_action($action, $param, &$option_name = '', &$option_value = '')
    {
        $response = '';

        $action_filter = apply_filters('docketcache/filter/reqaction/runaction', $action, $param, $option_name, $option_value);
        if (!empty($action_filter) && \is_object($action_filter)) {
            $response = $action_filter->response;
            $option_name = $action_filter->option_name;
            $option_value = $action_filter->option_value;
        }

        switch ($action) {
            case 'docket-flush-occache':
                $result = $this->pt->flush_cache();
                $response = $result ? 'docket-occache-flushed' : 'docket-occache-flushed-failed';
                do_action('docketcache/action/flush/objectcache', $result);
                break;
            case 'docket-enable-occache':
                $result = $this->pt->cx()->install(true);
                $response = $result ? 'docket-occache-enabled' : 'docket-occache-enabled-failed';
                do_action('docketcache/action/enable/objectcache', $result);
                break;

            case 'docket-disable-occache':
                $result = $this->pt->cx()->uninstall();
                $response = $result ? 'docket-occache-disabled' : 'docket-occache-disabled-failed';
                do_action('docketcache/action/disable/objectcache', $result);
                break;

            case 'docket-update-dropino':
                $result = $this->pt->cx()->install(true);
                $response = $result ? 'docket-dropino-updated' : 'docket-dropino-updated-failed';
                do_action('docketcache/action/update/objectcache', $result);
                break;

            case 'docket-dismiss-dropino':
                $result = $this->pt->co()->save('objectcacheoff', 'enable');
                $response = $result ? 'docket-occache-disabled' : 'docket-occache-disabled-failed';
                do_action('docketcache/action/dismiss/objectcache', $result);
                break;

            case 'docket-flush-oclog':
                $result = $this->pt->flush_log();
                $response = $result ? 'docket-log-flushed' : 'docket-log-flushed-failed';
                do_action('docketcache/action/flush/log/objectcache', $result);
                break;

            case 'docket-flush-ocfile':
                $result = $this->pt->flush_fcache($filename);
                $response = $result ? 'docket-file-flushed' : 'docket-file-flushed-failed';
                do_action('docketcache/action/flush/file/objectcache', $result, $filename);
                break;
            case 'docket-flush-opcache':
                if ($this->pt->co()->lockproc('opcache_reset', time() + 20)) {
                    $result = false;
                    $response = 'docket-opcache-flushed-warn';
                } else {
                    $result = $this->pt->opcache_reset();
                    $response = $result ? 'docket-opcache-flushed' : 'docket-opcache-flushed-failed';
                }
                do_action('docketcache/action/flush/opcache', $result);
                break;
            case 'docket-connect-cronbot':
                $result = apply_filters('docketcache/filter/active/cronbot', true);
                $response = $result ? 'docket-cronbot-connect' : 'docket-cronbot-connect-failed';
                do_action('docketcache/action/connect/cronbot', $result);
                break;
            case 'docket-disconnect-cronbot':
                $result = apply_filters('docketcache/filter/active/cronbot', false);
                $response = $result ? 'docket-cronbot-disconnect' : 'docket-cronbot-disconnect-failed';
                do_action('docketcache/action/disconnect/cronbot', $result);
                break;
            case 'docket-pong-cronbot':
                $result = apply_filters('docketcache/filter/check/cronbot', false);
                $response = 'docket-cronbot-pong';
                do_action('docketcache/action/pong/cronbot', $result);
                break;
            case 'docket-runevent-cronbot':
                $response = 'docket-cronbot-runevent-failed';
                $result = apply_filters('docketcache/filter/runevent/cronbot', false);
                if (false !== $result) {
                    $this->pt->co()->lookup_set('cronbotrun', $result);
                    $response = 'docket-cronbot-runevent';
                }

                do_action('docketcache/action/runevent/cronbot', $result);
                break;
            case 'docket-runeventnow-cronbot':
                $response = 'docket-cronbot-runevent-failed';
                $result = apply_filters('docketcache/filter/runevent/cronbot', true);
                if (false !== $result) {
                    $this->pt->co()->lookup_set('cronbotrun', $result);
                    $response = 'docket-cronbot-runevent';
                }

                do_action('docketcache/action/runeventnow/cronbot', $result);
                break;
            case 'docket-selectsite-cronbot':
                if (isset($param['nv'])) {
                    $nv = sanitize_text_field($param['nv']);
                    if (!is_numeric($nv)) {
                        do_action('docketcache/action/switchsite', false, $nv);
                        break;
                    }

                    $this->pt->set_cron_siteid($nv);

                    $response = 'docket-cronbot-selectsite';

                    $is_switch = $this->pt->switch_cron_site();

                    @Crawler::fetch_admin(admin_url('/'));

                    if ($is_switch) {
                        restore_current_blog();
                    }

                    do_action('docketcache/action/switchsite', true, $nv);
                }
                break;
            case 'docket-rungc':
                $ok = false;
                $response = 'docket-gcrun-failed';
                $result = apply_filters('docketcache/filter/garbagecollector', true);
                if (!empty($result) && \is_object($result)) {
                    $this->pt->co()->lookup_set('gcrun', (array) $result);
                    $response = 'docket-gcrun';
                    $ok = true;
                }

                do_action('docketcache/action/garbagecollector', $ok, $result);
                break;
            case 'docket-runtime':
                $result = WpConfig::runtime_install();
                $response = $result ? 'docket-runtimeok' : 'docket-runtimeok-failed';
                do_action('docketcache/action/updatewpconfig', $result);
                break;
            case 'docket-configreset':
                $result = $this->pt->co()->reset();
                $response = $result ? 'docket-configresetok' : 'docket-configresetok-failed';
                do_action('docketcache/action/configreset', $result);
                break;
            case 'docket-cleanuppost':
                if ($this->pt->co()->lockproc('cleanuppost_check', time() + 20)) {
                    $result = false;
                    $response = 'docket-cleanuppostok-warn';
                } else {
                    $ok = false;
                    $response = 'docket-cleanuppostok-failed';
                    $result = $this->pt->cleanuppost();
                    if (!empty($result) && \is_object($result)) {
                        $this->pt->co()->lookup_set('cleanuppost', (array) $result);
                        $response = 'docket-cleanuppostok';
                        $ok = true;
                    }

                    do_action('docketcache/action/cleanuppost', $ok, $result);
                }
                break;
        }

        if (empty($response) && preg_match('@^docket-(default|enable|disable|save)-([a-z_]+)$@', $action, $mm)) {
            $nk = $mm[1];
            $nx = $mm[2];
            if (\in_array($nx, $this->pt->co()->keys())) {
                $option_name = $nx;
                $ok = false;
                $nv = '';
                if ('save' === $nk && isset($param['nv'])) {
                    $nv = sanitize_text_field($param['nv']);
                    if (is_numeric($nv)) {
                        $nv = (int) $nv;
                    }
                    $ok = $this->pt->co()->save($nx, $nv);
                    $response = $ok ? 'docket-option-save' : 'docket-option-failed';
                    $option_value = $nv;
                } else {
                    $okmsg = 'default' === $nk ? 'docket-option-default' : 'docket-option-'.$nk;
                    $ok = $this->pt->co()->save($nx, $nk);
                    $response = $ok ? $okmsg : 'docket-option-failed';
                }

                if ($ok && WpConfig::has($nx)) {
                    $response = 'docket-option-wpf-warn';
                }

                do_action(
                    'docketcache/action/option',
                    $ok,
                    [
                        'key' => $nx,
                        'value' => $nv,
                        'name' => $nk,
                        'action' => $action,
                    ]
                );
            }
        }

        return $response;
    }

    private function parse_action()
    {
        $param = [];
        if (isset($_GET['_wpnonce'], $_GET['action'])) {
            $param = $_GET;
        } elseif (isset($_POST['_wpnonce'], $_POST['action'])) {
            $param = $_POST;
        }

        if (!empty($param) && \is_array($param)) {
            $action = sanitize_text_field($param['action']);

            if (\in_array($action, $this->action_token())) {
                if (!wp_verify_nonce($param['_wpnonce'], $action)) {
                    $this->exit_failed();
                    exit;
                }

                $option_name = '';
                $option_value = '';
                $response = $this->run_action($action, $param, $option_name, $option_value);

                if (!empty($response)) {
                    $args = [
                        'message' => $response,
                    ];

                    if (!empty($param['idx'])) {
                        $args['idx'] = sanitize_text_field($param['idx']);

                        if (!empty($param['adx'])) {
                            $args['adx'] = sanitize_text_field($param['adx']);
                        }

                        if (!empty($param['quiet']) && 1 === (int) $param['quiet']) {
                            $args['message'] = '';
                        }

                        if (!empty($option_name)) {
                            $args['nx'] = $option_name;
                        }

                        if (!empty($option_value)) {
                            $args['nv'] = $option_value;
                        }
                    }

                    $query = add_query_arg($args, $this->pt->get_page());
                    wp_safe_redirect(network_admin_url($query));
                    exit;
                }
            }
        }
    }

    private function screen_notice()
    {
        if (isset($_GET['message'])) {
            $token = sanitize_text_field($_GET['message']);
            $this->pt->token = $token;

            $this->pt->notice = '';
            $this->pt->inruntime = false;

            $kx = '';

            $option_name = esc_html__('Option', 'docket-cache');
            if (!empty($_GET['nx'])) {
                $kx = sanitize_text_field($_GET['nx']);
                $nx = $this->pt->co()->keys($kx);
                if (!empty($nx)) {
                    $option_name = $nx;

                    if (WpConfig::is_runtimeconst($kx)) {
                        $this->pt->inruntime = true;
                    }
                }
            }

            $option_value = '';
            if (!empty($_GET['nv'])) {
                $nv = sanitize_text_field($_GET['nv']);
                if (!empty($nv)) {
                    $option_value = $nv;
                }
            }

            $notice_filter = apply_filters('docketcache/filter/reqaction/screennotice', $token, $option_name, $option_value, $kx);
            if (!empty($notice_filter) && \is_string($notice_filter) && $notice_filter !== $token) {
                $this->pt->notice = $notice_filter;

                return;
            }

            switch ($token) {
                case 'docket-occache-enabled':
                    $this->pt->notice = esc_html__('Object cache enabled.', 'docket-cache');
                    $this->pt->co()->save('objectcacheoff', 'default');
                    break;
                case 'docket-occache-enabled-failed':
                    $this->pt->notice = esc_html__('Object cache could not be enabled.', 'docket-cache');
                    break;
                case 'docket-occache-disabled':
                    $this->pt->notice = esc_html__('Object cache disabled.', 'docket-cache');
                    $this->pt->co()->save('objectcacheoff', 'enable');
                    break;
                case 'docket-occache-disabled-failed':
                    $this->pt->notice = esc_html__('Object cache could not be disabled.', 'docket-cache');
                    break;
                case 'docket-occache-flushed':
                    $this->pt->notice = esc_html__('Object cache was flushed.', 'docket-cache');
                    break;
                case 'docket-occache-flushed-failed':
                    $this->pt->notice = esc_html__('Object cache could not be flushed.', 'docket-cache');
                    break;
                case 'docket-dropino-updated':
                    $this->pt->notice = esc_html__('Updated object cache Drop-In and enabled Docket object cache.', 'docket-cache');
                    break;
                case 'docket-dropino-updated-failed':
                    $this->pt->notice = esc_html__('Object cache Drop-In could not be updated.', 'docket-cache');
                    break;
                case 'docket-log-flushed':
                    $this->pt->notice = esc_html__('Cache log was flushed.', 'docket-cache');
                    break;
                case 'docket-log-flushed-failed':
                    $this->pt->notice = esc_html__('Cache log could not be flushed.', 'docket-cache');
                    break;
                case 'docket-file-flushed':
                    $this->pt->notice = esc_html__('Cache file was flushed.', 'docket-cache');
                    break;
                case 'docket-file-flushed-failed':
                    $this->pt->notice = esc_html__('Cache file could not be flushed.', 'docket-cache');
                    break;
                case 'docket-opcache-flushed':
                    $this->pt->notice = esc_html__('OPcache was flushed.', 'docket-cache');
                    $this->pt->opcache_reset();
                    break;
                case 'docket-opcache-flushed-failed':
                    $this->pt->notice = esc_html__('OPcache could not be flushed.', 'docket-cache');
                    break;
                case 'docket-opcache-flushed-warn':
                    $this->pt->notice = esc_html__('OPcache already flushed. Try again in a few seconds.', 'docket-cache');
                    break;
                case 'docket-cronbot-connect':
                    $this->pt->notice = esc_html__('Cronbot connected.', 'docket-cache');
                    break;
                case 'docket-cronbot-connect-failed':
                    $msg = $this->pt->co()->lookup_get('cronboterror', true);
                    $errmsg = !empty($msg) ? ': '.$msg : '.';
                    /* translators: %s = error message */
                    $this->pt->notice = sprintf(esc_html__('Cronbot failed to connect%s', 'docket-cache'), $errmsg);
                    unset($msg, $errmsg);
                    break;
                case 'docket-cronbot-disconnect':
                    $this->pt->notice = esc_html__('Cronbot disconnected.', 'docket-cache');
                    break;
                case 'docket-cronbot-disconnect-failed':
                    $this->pt->notice = esc_html__('Cronbot failed to disconnect.', 'docket-cache');
                    break;
                case 'docket-cronbot-pong':
                    $endpoint = parse_url($this->pt->cronbot_endpoint, PHP_URL_HOST);
                    $errmsg = $this->pt->co()->lookup_get('cronboterror', true);
                    if (!empty($errmsg)) {
                        /* translators: %1$s: cronbot endpoint, %2$s = error message */
                        $this->pt->notice = sprintf(esc_html__('Pong from %1$s: %2$s.', 'docket-cache'), $endpoint, $errmsg);
                    } else {
                        /* translators: %s: cronbot endpoint */
                        $this->pt->notice = sprintf(esc_html__('Pong from %s : connected.', 'docket-cache'), $endpoint);
                    }
                    break;
                case 'docket-cronbot-runevent':
                    $this->pt->notice = esc_html__('Running cron successful.', 'docket-cache');
                    $msg = $this->pt->co()->lookup_get('cronbotrun', true);

                    if (!empty($msg) && \is_array($msg)) {
                        $wmsg = '';
                        if (!empty($msg['wpcron_msg'])) {
                            $wmsg = $msg['wpcron_msg'];
                        }

                        if ($msg['wpcron_return'] > 1 && !empty($wmsg)) {
                            $this->pt->notice = $wmsg;
                            $this->pt->token = $this->pt->token.'-failed';
                        } else {
                            if (!empty($wmsg) && empty($msg['wpcron_event'])) {
                                $this->pt->notice = $wmsg;
                            } else {
                                /* translators: %d = cron event */
                                $this->pt->notice = sprintf(esc_html__('Executed a total of %d cron events', 'docket-cache'), $msg['wpcron_event']);
                            }
                        }
                    }
                    unset($msg, $wmsg);

                    break;
                case 'docket-cronbot-runevent-failed':
                    $this->pt->notice = esc_html__('Failed to run cron.', 'docket-cache');
                    break;
                case 'docket-cronbot-selectsite':
                    $this->pt->notice = '';
                    break;
                case 'docket-option-enable':
                    /* translators: %s = option name */
                    $this->pt->notice = sprintf(esc_html__('%s. Enabled.', 'docket-cache'), $option_name);
                    break;
                case 'docket-option-disable':
                    /* translators: %s = option name */
                    $this->pt->notice = sprintf(esc_html__('%s. Disabled.', 'docket-cache'), $option_name);
                    break;
                case 'docket-option-save':
                    if (!empty($option_value)) {
                        $noticewpconfig = '';
                        if ($this->pt->inruntime) {
                            $noticewpconfig = WpConfig::notice_filter($option_name, $option_value, $kx);
                        }

                        if (!empty($noticewpconfig)) {
                            $this->pt->notice = $noticewpconfig;
                        } else {
                            if ('default' === $option_value) {
                                /* translators: %s = option name */
                                $this->pt->notice = sprintf(esc_html__('%s resets to default.', 'docket-cache'), $option_name);
                            } else {
                                /* translators: %1$s = option name, %2$s = option_value */
                                $this->pt->notice = sprintf(esc_html__('%1$s set to %2$s.', 'docket-cache'), $option_name, $option_value);
                            }
                        }
                    } else {
                        /* translators: %s = option name */
                        $this->pt->notice = sprintf(esc_html__('%s updated.', 'docket-cache'), $option_name);
                    }
                    break;
                case 'docket-option-default':
                    /* translators: %s = option name */
                    $this->pt->notice = sprintf(esc_html__('%s resets to default.', 'docket-cache'), $option_name);
                    break;
                case 'docket-option-failed':
                    /* translators: %s = option name */
                    $this->pt->notice = sprintf(esc_html__('Failed to update option %s.', 'docket-cache'), $option_name);
                    break;
                case 'docket-option-wpf-warn':
                    /* translators: %s = option name */
                    $this->pt->notice = sprintf(esc_html__('%s configuration already defined or exists in wp-config.php file. This update has no effect.', 'docket-cache'), $option_name);
                    break;
                case 'docket-action-failed':
                    /* translators: %s = option name */
                    $this->pt->notice = esc_html__('Failed to execute the action request. Please try again.', 'docket-cache');
                    break;
                case 'docket-gcrun':
                    $this->pt->notice = esc_html__('Executing the garbage collector successful', 'docket-cache');
                    $msg = $this->pt->co()->lookup_get('gcrun', true);
                    if (!empty($msg) && \is_array($msg)) {
                        $collect = (object) $msg;

                        $gcmsg = '<div class="gc"><ul>';
                        $gcmsg .= '<li><span>'.esc_html__('Cache MaxTTL', 'docket-cache').'</span>'.$collect->cache_maxttl.'</li>';
                        $gcmsg .= '<li><span>'.esc_html__('Cache File Limit', 'docket-cache').'</span>'.$collect->cache_maxfile.'</li>';
                        $gcmsg .= '<li><span>'.esc_html__('Cache Disk Limit', 'docket-cache').'</span>'.$this->pt->normalize_size($collect->cache_maxdisk).'</li>';
                        $gcmsg .= '<li><span>'.esc_html__('Cleanup Cache MaxTTL', 'docket-cache').'</span>'.$collect->cleanup_maxttl.'</li>';
                        $gcmsg .= '<li><span>'.esc_html__('Cleanup Cache File Limit', 'docket-cache').'</span>'.$collect->cleanup_maxfile.'</li>';
                        $gcmsg .= '<li><span>'.esc_html__('Cleanup Cache Disk Limit', 'docket-cache').'</span>'.$collect->cleanup_maxdisk.'</li>';

                        if ($this->pt->get_precache_maxfile() > 0) {
                            $gcmsg .= '<li><span>'.esc_html__('Cleanup Precache Limit', 'docket-cache').'</span>'.$collect->cleanup_precache_maxfile.'</li>';
                        }

                        $gcmsg .= '<li><span>'.esc_html__('Total Cache Cleanup', 'docket-cache').'</span>'.$collect->cache_cleanup.'</li>';
                        $gcmsg .= '<li><span>'.esc_html__('Total Cache Ignored', 'docket-cache').'</span>'.$collect->cache_ignore.'</li>';
                        $gcmsg .= '<li><span>'.esc_html__('Total Cache File', 'docket-cache').'</span>'.$collect->cache_file.'</li>';
                        $gcmsg .= '</ul></div>';

                        $this->pt->notice .= $gcmsg;
                    }
                    unset($msg, $gcmsg);
                    break;
                case 'docket-gcrun-failed':
                    $this->pt->notice = esc_html__('Failed to run the garbage collector.', 'docket-cache');
                    break;
                case 'docket-runtimeok':
                    $this->pt->notice = esc_html__('Updating wp-config.php file successful.', 'docket-cache');
                    break;
                case 'docket-runtimeok-failed':
                    $this->pt->notice = esc_html__('Failed to update wp-config.php file.', 'docket-cache');
                    break;
                case 'docket-configresetok':
                    $this->pt->notice = esc_html__('Reset all configuration successful.', 'docket-cache');
                    break;
                case 'docket-configresetok-failed':
                    $this->pt->notice = esc_html__('Failed to reset configuration.', 'docket-cache');
                    break;
                case 'docket-cleanuppostok':
                    $this->pt->notice = esc_html__('Cleanup Post successful', 'docket-cache');
                    $msg = $this->pt->co()->lookup_get('cleanuppost', true);
                    if (!empty($msg) && \is_array($msg)) {
                        $collect = (object) $msg;

                        $clmsg = '<div class="gc"><ul>';
                        $clmsg .= '<li><span>'.esc_html__('Revisions', 'docket-cache').'</span>'.$collect->revision.'</li>';
                        $clmsg .= '<li><span>'.esc_html__('Auto Drafts', 'docket-cache').'</span>'.$collect->autodraft.'</li>';
                        $clmsg .= '<li><span>'.esc_html__('Trash Bin', 'docket-cache').'</span>'.$collect->trashbin.'</li>';
                        $clmsg .= '</ul>';

                        if (isset($collect->site) && is_multisite() && $this->pt->get_current_select_siteid() <= 0) {
                            /* translators: %d = sites */
                            $clmsg .= '<p>'.sprintf(esc_html__('For %d sites', 'docket-cache'), $collect->site).'</p>';
                        }

                        $clmsg .= '</div>';

                        $this->pt->notice .= $clmsg;
                    }
                    unset($msg, $clmsg);
                    break;
                case 'docket-cleanuppostok-failed':
                    $this->pt->notice = esc_html__('Failed to cleanup Post.', 'docket-cache');
                    break;
                case 'docket-cleanuppostok-warn':
                    $this->pt->notice = esc_html__('Post already cleanup. Try again in a few seconds.', 'docket-cache');
                    break;
            }
        }
    }
}
