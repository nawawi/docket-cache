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
    private static function canopt()
    {
        static $inst;
        if (!\is_object($inst)) {
            $inst = new Canopt();
        }

        return $inst;
    }

    private static function normalize_eol($content)
    {
        return str_replace(["\r\n", "\n\r", "\r", "\n"], PHP_EOL, $content);
    }

    private static function keys()
    {
        return [
             'rtpostautosave' => 'AUTOSAVE_INTERVAL',
             'rtpostrevision' => 'WP_POST_REVISIONS',
             'rtpostemptytrash' => 'EMPTY_TRASH_DAYS',
             'rtpluginthemeeditor' => 'DISALLOW_FILE_EDIT',
             'rtpluginthemeinstall' => 'DISALLOW_FILE_MODS',
             'rtimageoverwrite' => 'IMAGE_EDIT_OVERWRITE',
         ];
    }

    public static function is_bedrock()
    {
        return class_exists('\\Roots\\Bedrock\\Autoloader');
    }

    public static function get_file()
    {
        $file = false;
        if (@is_file(ABSPATH.'wp-config.php')) {
            $file = ABSPATH.'wp-config.php';
        } elseif (@is_file(\dirname(ABSPATH).'/wp-config.php') && !@is_file(\dirname(ABSPATH).'/wp-settings.php')) {
            $file = \dirname(ABSPATH).'/wp-config.php';
        }

        if (!$file) {
            return false;
        }

        return str_replace('\\', '/', $file);
    }

    public static function is_writable()
    {
        $file = self::get_file();

        return @is_file($file) && is_writable($file);
    }

    public static function strip_marker($content)
    {
        if (!empty($content) && false !== strpos($content, '/*@DOCKETCACHE-RUNTIME-BEGIN*/') && false !== strpos($content, '/*@DOCKETCACHE-RUNTIME-END*/')) {
            try {
                $content_r = @preg_replace('#/\*@DOCKETCACHE-RUNTIME-BEGIN\*/.*/\*@DOCKETCACHE-RUNTIME-END\*/'.PHP_EOL.'#sm', '', $content, -1, $cnt);
                if (!empty($content_r) && $cnt > 0) {
                    $content = $content_r;
                }
            } catch (\Throwable $e) {
                nwdcx_throwable(__METHOD__, $e);
            }
        }

        return $content;
    }

    public static function get_file_loader()
    {
        $contentpath = \dirname(self::canopt()->path);

        return $contentpath.'/docketcache-loader.php';
    }

    public static function runtime_remove()
    {
        $file = self::get_file_loader();
        if (@is_file($file) && is_writable($file)) {
            @unlink($file);
        }

        $wpconfig = self::get_contents();
        if (!$wpconfig) {
            return false;
        }

        $wpconfig = self::strip_marker($wpconfig);
        if (false !== strpos($wpconfig, '/*@DOCKETCACHE-RUNTIME-BEGIN*/')) {
            return false;
        }

        return self::put_contents($wpconfig);
    }

    public static function runtime_code()
    {
        $data_path = self::canopt()->path;
        $code = '/*@DOCKETCACHE-RUNTIME-BEGIN*/'.PHP_EOL;
        $code .= "if(!\\function_exists('docketcache_runtime')){".PHP_EOL;
        $code .= ' function docketcache_runtime(){'.PHP_EOL;
        $code .= '  try{'.PHP_EOL;
        $code .= '   $path="'.$data_path.'";'.PHP_EOL;
        $code .= '   $runtime=$path."/runtime.php";'.PHP_EOL;
        $code .= '   if(is_file($runtime)){include_once $runtime;}'.PHP_EOL;
        $code .= '  }catch(\\Throwable $e){}'.PHP_EOL;
        $code .= ' }'.PHP_EOL;
        $code .= ' docketcache_runtime();'.PHP_EOL;
        $code .= '}'.PHP_EOL;
        $code .= '/*@DOCKETCACHE-RUNTIME-END*/'.PHP_EOL;

        return $code;
    }

    public static function runtime_install()
    {
        $wpconfig = self::get_contents();
        if (!$wpconfig) {
            return false;
        }

        $wpconfig = self::strip_marker($wpconfig);
        if (false !== strpos($wpconfig, '/*@DOCKETCACHE-RUNTIME-BEGIN*/')) {
            return false;
        }

        $code = self::runtime_code();

        $wpconfig_r = false;
        $cnt = 0;

        // find placeholder
        if (@preg_match("@^(require_once\s+ABSPATH\s+?\.\s+?'wp-settings\.php';)@m", $wpconfig, $mm)) {
            $placeholder = $mm[1];
            $wpconfig_r = str_replace($placeholder, $code.$placeholder, $wpconfig, $cnt);
        } elseif (@preg_match('@^(/\*\* Sets up WordPress vars and included files\. \*/)@m', $wpconfig, $mm)) {
            $placeholder = $mm[1];
            $wpconfig_r = str_replace($placeholder, $placeholder.$code, $wpconfig, $cnt);
        }

        if (!empty($wpconfig_r) && $cnt > 0) {
            self::canopt()->opcache_flush(self::get_file());

            if (self::put_contents($wpconfig_r)) {
                $results = Crawler::fetch_home_nocache(['blocking' => true]);
                if (!is_wp_error($results) && (isset($results['response']['code']) && '50' !== substr($results['response']['code'], 0, 2))) {
                    self::write_runtime();

                    return true;
                }

                self::put_contents($wpconfig);
            }
        }

        return false;
    }

    public static function get_contents()
    {
        $file = self::get_file();
        if (!$file || !@is_readable($file)) {
            return false;
        }

        $content = @file_get_contents($file);
        if (!empty($content) && \is_string($content)) {
            return self::normalize_eol($content);
        }

        return false;
    }

    public static function put_contents($content)
    {
        if (empty($content)) {
            return $content;
        }

        $file = self::get_file();
        if (!$file || !@is_writable($file)) {
            return false;
        }

        return @file_put_contents($file, $content, LOCK_EX);
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

            $keys = self::keys();
            foreach ($tokens as $token) {
                if (!empty($token) && \is_array($token)) {
                    $token_name = token_name($token[0]);
                    $token_value = trim($token[1], '"\'');
                    $token_value = strtoupper($token_value);
                    if ('T_CONSTANT_ENCAPSED_STRING' === $token_name && ('DOCKET_CACHE_' === substr($token_value, 0, 13) || \in_array($token_value, array_values($keys)))) {
                        $found[$token_value] = 1;
                    }
                }
            }
        }

        return \array_key_exists(nwdcx_constfx($name), $found) || (isset($keys[$name]) && \array_key_exists($keys[$name], $found));
    }

    public static function is_runtimeconst($name)
    {
        return \array_key_exists($name, self::keys());
    }

    public static function is_runtimenotice()
    {
        return !\function_exists('docketcache_runtime');
    }

    public static function write_runtime()
    {
        $file = self::canopt()->path.'/runtime.php';
        $config = self::canopt()->read_config();
        $runtime = [
            'contentpath' => nwdcx_constval('CONTENT_PATH'),
            'pluginpath' => realpath(plugin_dir_path(nwdcx_constval('FILE'))),
            'configpath' => self::canopt()->path,
        ];

        $cons = '';
        $keys = self::keys();
        foreach ($keys as $k => $v) {
            $ka = nwdcx_constfx($k);
            if (!empty($config[$ka])) {
                $val = $config[$ka];
                if ('default' === $val) {
                    continue;
                }

                if ('on' === $val) {
                    $val = 'true';
                } elseif ('off' === $val) {
                    $val = 'false';
                } elseif (@preg_match('@^\d+$@', $val)) {
                    $val = (int) $val;

                    if ('rtpostautosave' === $k) {
                        $val = 60 * $val;
                    }
                }

                $cons .= "!defined('".$v."') && define('".$v."', ".$val.');'.PHP_EOL;
            }
        }

        $code = '<?php '.PHP_EOL;
        $code .= "if ( !defined('ABSPATH') ) { return; }".PHP_EOL;
        $code .= '$runtime = (object)'.var_export($runtime, 1).';'.PHP_EOL;
        $code .= 'if ( !@is_dir($runtime->configpath) ) { return; }'.PHP_EOL;
        $code .= 'if ( !@is_file($runtime->pluginpath.\'/docket-cache.php\') ) { return; }'.PHP_EOL;
        if (!empty($cons)) {
            $code .= $cons;
        }

        $data = apply_filters('docketcache/filter/wpconfig/runtime', $code, $runtime);

        self::canopt()->opcache_flush($file);

        return @file_put_contents($file, $data, LOCK_EX);
    }

    public static function unlink_runtime()
    {
        $file = self::canopt()->path.'/runtime.php';
        if (@is_file($file)) {
            @unlink($file);
        }
    }

    public static function notice_filter($name, $value, $key)
    {
        if ('default' === $value) {
            /* translators: %s = option name */
            return sprintf(esc_html__('%s resets to WordPress default.', 'docket-cache'), $name);
        }

        $notice = '';
        switch ($key) {
            case 'rtpostautosave':
                if ('off' === $value) {
                    /* translators: %s = option name */
                    $notice = sprintf(esc_html__('%s set to disable.', 'docket-cache'), $name);
                } elseif ($value > 1) {
                    /* translators: %1$s = option name, %2$s = option_value */
                    $notice = sprintf(esc_html__('%1$s set to every %2$s minutes.', 'docket-cache'), $name, $value);
                } else {
                    /* translators: %s = option name */
                    $notice = sprintf(esc_html__('%s set to every minute.', 'docket-cache'), $name);
                }
                break;
            case 'rtpostrevision':
                if ('off' === $value) {
                    /* translators: %s = option name */
                    $notice = sprintf(esc_html__('%s set to disable.', 'docket-cache'), $name);
                } elseif ('on' === $value) {
                    /* translators: %s = option name */
                    $notice = sprintf(esc_html__('%s set to no limit.', 'docket-cache'), $name);
                } else {
                    /* translators: %1$s = option name, %2$s = option_value */
                    $notice = sprintf(esc_html__('%1$s set limit to %2$s revisions.', 'docket-cache'), $name, $value);
                }
                break;
            case 'rtpostemptytrash':
                if ('off' === $value) {
                    /* translators: %s = option name */
                    $notice = sprintf(esc_html__('%s set to disable.', 'docket-cache'), $name);
                } else {
                    /* translators: %1$s = option name, %2$s = option_value */
                    $notice = sprintf(esc_html__('%1$s set to empty in %2$s days.', 'docket-cache'), $name, $value);
                }
                break;
            case 'rtimageoverwrite':
            case 'rtpluginthemeeditor':
            case 'rtpluginthemeinstall':
                if ('off' === $value) {
                    /* translators: %s = option name */
                    $notice = sprintf(esc_html__('%s set to disable.', 'docket-cache'), $name);
                } elseif ('on' === $value) {
                    /* translators: %s = option name */
                    $notice = sprintf(esc_html__('%s set to enable.', 'docket-cache'), $name);
                }
                break;
        }

        return $notice;
    }

    public static function backup($dest)
    {
        if (@is_dir($dest)) {
            return false;
        }

        $dir = \dirname($dest);
        $dest_file = trailingslashit($dir).'backup-wp-config.php';
        if (@copy($file, $dest_file)) {
            return true;
        }

        return false;
    }
}
