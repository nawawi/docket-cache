<?php
/**
 * Docket Cache.
 *
 * @author  Nawawi Jamili
 * @license MIT
 *
 * @see    https://github.com/nawawi/docket-cache
 */

/*
 * Store Transients in DB.
 */

namespace Nawawi\DocketCache;

\defined('ABSPATH') || exit;

final class TransientDb
{
    public function set($transient, $value, $group, $timeout)
    {
        $result = false;

        // we set autoload with 'no' to prevent it store in alloptions.
        // timeout in timestamp, always set expiration.
        if ('transient' === $group) {
            $transient_timeout = '_transient_timeout_'.$transient;
            $transient_option = '_transient_'.$transient;

            if (false === get_option($transient_option)) {
                add_option($transient_timeout, $timeout, '', 'no');
                $result = add_option($transient_option, $value, '', 'no');
            } else {
                update_option($transient_timeout, $timeout, 'no');
                $result = update_option($transient_option, $value, 'no');
            }
        } elseif ('site-transient' === $group) {
            $transient_timeout = '_site_transient_timeout_'.$transient;
            $option = '_site_transient_'.$transient;

            if (false === get_site_option($option)) {
                add_site_option($transient_timeout, $timeout);
                $result = add_site_option($option, $value);
            } else {
                update_site_option($transient_timeout, $timeout);
                $result = update_site_option($option, $value);
            }
        }

        return $result;
    }

    public function get($transient, $group)
    {
        if ('transient' === $group) {
            $transient_option = '_transient_'.$transient;
            if (!wp_installing()) {
                $alloptions = wp_load_alloptions();
                if (!isset($alloptions[$transient_option])) {
                    $transient_timeout = '_transient_timeout_'.$transient;
                    $timeout = get_option($transient_timeout);
                    if (false !== $timeout && $timeout < time()) {
                        delete_option($transient_option);
                        delete_option($transient_timeout);

                        return false;
                    }
                }
            }

            return get_option($transient_option);
        }

        if ('site-transient' === $group) {
            $transient_option = '_site_transient_'.$transient;
            $transient_timeout = '_site_transient_timeout_'.$transient;
            $timeout = get_site_option($transient_timeout);
            if (false !== $timeout && $timeout < time()) {
                delete_site_option($transient_option);
                delete_site_option($transient_timeout);

                return false;
            }

            return get_site_option($transient_option);
        }

        return false;
    }

    public function delete($transient, $group)
    {
        $result = false;

        if ('transient' === $group) {
            $option_timeout = '_transient_timeout_'.$transient;
            $option = '_transient_'.$transient;
            $result = delete_option($option);

            if ($result) {
                delete_option($option_timeout);
            }
        } elseif ('site-transient' === $group) {
            $option_timeout = '_site_transient_timeout_'.$transient;
            $option = '_site_transient_'.$transient;
            $result = delete_site_option($option);

            if ($result) {
                delete_site_option($option_timeout);
            }
        }

        return $result;
    }

    public function match_key($key)
    {
        return preg_match('@^(_site)?(_transient)(_timeout)?_@', $key);
    }
}
