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

final class WpConfig
{
    private static function get_file()
    {
        $file = trailingslashit(ABSPATH).'wp-config.php';
        if (!@is_file($file)) {
            return false;
        }

        return $file;
    }

    private static function get_contents($as_array = false)
    {
        $file = self::get_file();
        if (!$file || !@is_readable($file)) {
            return false;
        }

        return $as_array ? @file($file) : @file_get_contents($file);
    }

    public static function has($name)
    {
        static $found = [];

        if (empty($found) || !\is_array($found)) {
            $config = self::get_contents();
            if (empty($config)) {
                return false;
            }

            try {
                $tokens = token_get_all($config);
            } catch (\Throwable $e) {
                nwdcx_throwable($e);

                return false;
            }

            if (empty($tokens) || !\is_array($tokens)) {
                return false;
            }

            foreach ($tokens as $token) {
                if (!empty($token) && \is_array($token)) {
                    $token_name = token_name($token[0]);
                    $token_value = trim($token[1], '"\'');
                    if ('T_CONSTANT_ENCAPSED_STRING' === $token_name && 'DOCKET_CACHE_' === substr($token_value, 0, 13)) {
                        $found[$token_value] = 1;
                    }
                }
            }
        }

        return \array_key_exists(nwdcx_constfx($name), $found);
    }
}
