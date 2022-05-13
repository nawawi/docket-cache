<?php
/**
 * Docket Cache.
 *
 * @author  Nawawi Jamili
 * @license MIT
 *
 * @see    https://github.com/nawawi/docket-cache
 */
\defined('ABSPATH') || exit;

if (class_exists('Nawawi\\Symfony\\Component\\VarExporter\\VarExporter')) {
    class_alias('Nawawi\Symfony\Component\VarExporter\VarExporter', 'Nawawi\DocketCache\Exporter\VarExporter', false);
}
if (class_exists('Nawawi\\Symfony\\Component\\VarExporter\\Internal\\Hydrator')) {
    class_alias('Nawawi\Symfony\Component\VarExporter\Internal\Hydrator', 'Nawawi\DocketCache\Exporter\Hydrator', false);
}
if (class_exists('Nawawi\\Symfony\\Component\\VarExporter\\Internal\\Registry')) {
    class_alias('Nawawi\Symfony\Component\VarExporter\Internal\Registry', 'Nawawi\DocketCache\Exporter\Registry', false);
}

if (!\function_exists('nwdcx_arraymap')) {
    function nwdcx_arraymap($func, $arr)
    {
        $new = [];
        foreach ($arr as $key => $value) {
            $new[$key] = (\is_array($value) ? nwdcx_arraymap($func, $value) : (\is_array($func) ? \call_user_func_array($func, $value) : $func($value)));
        }

        return $new;
    }
}

if (!\function_exists('nwdcx_serialized')) {
    function nwdcx_serialized($data)
    {
        if (!\function_exists('is_serialized')) {
            // 16072021: rare opcache issue with some hosting ABSPATH not defined.
            if (\defined('ABSPATH') && \defined('WPINC')) {
                @include_once ABSPATH.WPINC.'/functions.php';
            }
        }

        if (!\function_exists('is_serialized')) {
            return false;
        }

        return is_serialized($data);
    }
}

if (!\function_exists('nwdcx_unserialize')) {
    function nwdcx_unserialize($data)
    {
        if (!nwdcx_serialized($data)) {
            return $data;
        }

        $ok = true;

        // if the string has object format, check it if has stdClass,
        // other than that set it as false and return the original data
        if (false !== strpos($data, 'O:') && @preg_match_all('@O:\d+:"([^"]+)"@', $data, $mm)) {
            if (!empty($mm) && !empty($mm[1])) {
                foreach ($mm[1] as $v) {
                    if ('stdClass' !== $v) {
                        $ok = false;
                        break;
                    }
                }
                unset($mm);
            }
        }

        return !$ok ? $data : @unserialize(trim($data));
    }
}

if (!\function_exists('nwdcx_fixhost')) {
    function nwdcx_fixhost($hostname)
    {
        if (false !== strpos($hostname, ':')) {
            return @preg_replace('@:\d+$@', '', $hostname);
        }

        return $hostname;
    }
}

if (!\function_exists('nwdcx_fixscheme')) {
    // replace http scheme
    function nwdcx_fixscheme($url, $scheme = 'http://')
    {
        return @preg_replace('@^((([a-zA-Z]+)?:)?(//))?@', $scheme, trim($url));
    }
}

if (!\function_exists('nwdcx_noscheme')) {
    // replace http scheme
    function nwdcx_noscheme($url)
    {
        return nwdcx_fixscheme($url, '');
    }
}

if (!\function_exists('nwdcx_wpdb')) {
    function nwdcx_wpdb(&$wpdb = '')
    {
        if (!isset($GLOBALS['wpdb']) || !\is_object($GLOBALS['wpdb']) || (isset($GLOBALS['wpdb']->ready) && !$GLOBALS['wpdb']->ready)) {
            $wpdb = false;

            return false;
        }

        $wpdb = $GLOBALS['wpdb'];

        return $wpdb;
    }
}

if (!\function_exists('nwdcx_optget')) {
    function nwdcx_optget($key)
    {
        if (!nwdcx_wpdb($wpdb)) {
            return false;
        }

        static $cache = [];

        if (isset($cache[$key])) {
            return $cache[$key];
        }

        $suppress = $wpdb->suppress_errors(true);

        $query = $wpdb->prepare('SELECT option_value FROM `'.$wpdb->options.'` WHERE option_name=%s ORDER BY option_id ASC LIMIT 1', $key);
        $option = $wpdb->get_var($query);
        $cache[$key] = !empty($option) ? nwdcx_unserialize($option) : false;

        $wpdb->suppress_errors($suppress);

        return $cache[$key];
    }
}

if (!\function_exists('nwdcx_cleanuptransient')) {
    function nwdcx_cleanuptransient()
    {
        if (!nwdcx_wpdb($wpdb)) {
            return false;
        }

        $suppress = $wpdb->suppress_errors(true);

        $collect = [];

        $results = $wpdb->get_results('SELECT `option_id`,`option_name`,`option_value` FROM `'.$wpdb->options.'` WHERE `option_name` LIKE "_transient_%" OR `option_name` LIKE "_site_transient_%" ORDER BY `option_id` ASC LIMIT 1000', ARRAY_A);
        if (!empty($results) && \is_array($results)) {
            while ($row = @array_shift($results)) {
                $key = @preg_replace('@^(_site)?(_transient)(_timeout)?_@', '', $row['option_name']);
                $collect[$key] = $key;

                if (false !== strpos($row['option_name'], '_transient_timeout_') && (int) $row['option_value'] > time()) {
                    unset($collect[$key]);
                }
            }

            if (!empty($collect)) {
                $wpdb->query('START TRANSACTION');
                foreach ($collect as $key) {
                    $wpdb->query("DELETE FROM `{$wpdb->options}` WHERE `option_name`='_transient_{$key}'");
                    $wpdb->query("DELETE FROM `{$wpdb->options}` WHERE `option_name`='_transient_timeout_{$key}'");
                    $wpdb->query("DELETE FROM `{$wpdb->options}` WHERE `option_name`='_site_transient_{$key}'");
                    $wpdb->query("DELETE FROM `{$wpdb->options}` WHERE `option_name`='_site_transient_timeout_{$key}'");
                }
                $wpdb->query('COMMIT');
            }
        }

        $collect = [];

        if (is_multisite() && isset($wpdb->sitemeta)) {
            $results = $wpdb->get_results('SELECT `meta_id`,`meta_key`,`meta_value` FROM `'.$wpdb->sitemeta.'` WHERE `meta_key` LIKE "_site_transient_%" ORDER BY `meta_id` ASC LIMIT 1000', ARRAY_A);
            if (!empty($results) && \is_array($results)) {
                while ($row = @array_shift($results)) {
                    $key = @preg_replace('@^(_site)?(_transient)(_timeout)?_@', '', $row['meta_key']);
                    $collect[$key] = $key;

                    if (false !== strpos($row['meta_key'], '_site_transient_timeout_') && (int) $row['meta_value'] > time()) {
                        unset($collect[$key]);
                    }
                }

                if (!empty($collect)) {
                    $wpdb->query('START TRANSACTION');
                    foreach ($collect as $key) {
                        $wpdb->query("DELETE FROM `{$wpdb->sitemeta}` WHERE `meta_key`='_site_transient_{$key}'");
                        $wpdb->query("DELETE FROM `{$wpdb->sitemeta}` WHERE `meta_key`='_site_transient_timeout_{$key}'");
                    }
                    $wpdb->query('COMMIT');
                }
            }
        }

        unset($collect, $results);
        $wpdb->suppress_errors($suppress);

        return true;
    }
}

if (!\function_exists('nwdcx_runaction')) {
    function nwdcx_runaction(...$args)
    {
        add_action(
            'plugins_loaded',
            function () use ($args) {
                \call_user_func_array('do_action', $args);
            }
        );
    }
}

if (!\function_exists('nwdcx_throwable')) {
    function nwdcx_throwable($name, $error)
    {
        if (\defined('WP_DEBUG') && WP_DEBUG) {
            if (!isset($GLOBALS['docketcache_throwable'])) {
                $GLOBALS['docketcache_throwable'] = [];
            }
            $GLOBALS['docketcache_throwable'][$name] = $error;
        }
    }
}

if (!\function_exists('nwdcx_microtimetofloat')) {
    function nwdcx_microtimetofloat($second)
    {
        list($usec, $sec) = explode(' ', $second);

        return (float) $usec + (float) $sec;
    }
}

if (!\function_exists('nwdcx_constfx')) {
    function nwdcx_constfx($name, $is_strip = false)
    {
        if (!$is_strip) {
            return strtoupper('docket_cache_'.$name);
        }

        return substr($name, 13);
    }
}

if (!\function_exists('nwdcx_construe')) {
    function nwdcx_construe($name)
    {
        $name = nwdcx_constfx($name);
        if (\defined($name)) {
            $value = (bool) \constant($name);
            if (true === $value || 1 === $value) {
                return true;
            }
        }

        return false;
    }
}

if (!\function_exists('nwdcx_consfalse')) {
    function nwdcx_consfalse($name)
    {
        $name = nwdcx_constfx($name);

        return !\defined($name) || !\constant($name);
    }
}

if (!\function_exists('nwdcx_constval')) {
    function nwdcx_constval($name)
    {
        $name = nwdcx_constfx($name);
        $value = '';
        if (\defined($name)) {
            $value = \constant($name);
        }

        return $value;
    }
}

if (!\function_exists('nwdcx_consdef')) {
    function nwdcx_consdef($name, $value)
    {
        $name = nwdcx_constfx($name);

        return !\defined($name) && \define($name, $value);
    }
}

if (!\function_exists('nwdcx_suppresserrors')) {
    function nwdcx_suppresserrors($level = true)
    {
        $errlevel = error_reporting();
        $erropt = true === $level ? 0 : $level;
        error_reporting($erropt);

        return $errlevel;
    }
}

if (!\function_exists('nwdcx_normalizepath')) {
    function nwdcx_normalizepath($path)
    {
        if (false === strpos($path, '\\')) {
            return $path;
        }

        if (\function_exists('wp_normalize_path')) {
            return wp_normalize_path($path);
        }

        return str_replace('\\', '/', $path);
    }
}

// network specific

if (!\function_exists('nwdcx_network_multi')) {
    function nwdcx_network_multi()
    {
        if (!is_multisite()) {
            return false;
        }

        if (\defined('MULTINETWORK')) {
            return MULTINETWORK;
        }

        if (\defined('DOCKET_CACHE_MULTINETWORK')) {
            return DOCKET_CACHE_MULTINETWORK;
        }

        if (!nwdcx_wpdb($wpdb)) {
            return false;
        }

        static $ok = null;

        if (\is_bool($ok)) {
            return $ok;
        }

        // this lock file should only exists if network more than 1
        // see Dropino::multinet_active
        $lock_file = nwdcx_normalizepath(DOCKET_CACHE_CONTENT_PATH).'/.object-cache-network-multi.txt';
        $timeout = time() + 86400;
        if (@is_file($lock_file) && @is_readable($lock_file) && $timeout > @filemtime($lock_file)) {
            $ok = !empty(@file_get_contents($lock_file)) ? true : false;

            return $ok;
        }

        $table = $wpdb->base_prefix.'site';

        $suppress = $wpdb->suppress_errors(true);
        $query = "SELECT id FROM `{$table}` WHERE id > 0 ORDER BY id ASC LIMIT 2";
        $check = $wpdb->query($query);
        $wpdb->suppress_errors($suppress);
        $ok = !empty($check) && $check > 1 ? true : false;

        return $ok;
    }
}

if (!\function_exists('nwdcx_network_ignore')) {
    function nwdcx_network_ignore()
    {
        if (nwdcx_network_multi()) {
            if (!@is_file(nwdcx_normalizepath(DOCKET_CACHE_CONTENT_PATH).'/.object-cache-network-'.nwdcx_network_id().'.txt')) {
                return true;
            }

            return false;
        }
    }
}

if (!\function_exists('nwdcx_network_main')) {
    function nwdcx_network_main()
    {
        if (!is_multisite()) {
            return true;
        }

        if (\defined('PRIMARY_NETWORK_ID') && PRIMARY_NETWORK_ID === nwdcx_network_id()) {
            return true;
        }

        if (\defined('SITE_ID_CURRENT_SITE') && SITE_ID_CURRENT_SITE === nwdcx_network_id()) {
            return true;
        }

        if (empty($_SERVER['HTTP_HOST'])) {
            return true;
        }

        if (!nwdcx_wpdb($wpdb)) {
            return true;
        }

        $hostname = nwdcx_fixhost($_SERVER['HTTP_HOST']);

        if (\defined('DOMAIN_CURRENT_SITE') && DOMAIN_CURRENT_SITE === $hostname) {
            return true;
        }

        $lock_file = nwdcx_normalizepath(DOCKET_CACHE_CONTENT_PATH).'/.object-cache-network-main.txt';
        $timeout = time() + 86400;
        if (@is_file($lock_file) && @is_readable($lock_file) && $timeout > @filemtime($lock_file)) {
            $data = @file_get_contents($lock_file);
            if (!empty($data) && trim($data) === $hostname) {
                return true;
            }
        }

        static $cache = [];

        if (isset($cache[$hostname])) {
            return $cache[$hostname];
        }

        $table = $wpdb->base_prefix.'site';

        $suppress = $wpdb->suppress_errors(true);
        $query = "SELECT domain FROM `{$table}` WHERE id > 0 ORDER BY id ASC LIMIT 1";
        $domain = $wpdb->get_var($query);
        $wpdb->suppress_errors($suppress);

        if ($hostname === $domain) {
            $cache[$hostname] = true;
        } elseif (@preg_match('@(.*?\.)?'.preg_quote($domain, '@').'@', $hostname)) {
            $cache[$hostname] = true;
        } else {
            $cache[$hostname] = false;
        }

        if ($cache[$hostname] && !@is_file($lock_file)) {
            @file_put_contents($lock_file, $hostname, \LOCK_EX);
        }

        return $cache[$hostname];
    }
}

if (!\function_exists('nwdcx_network_id')) {
    function nwdcx_network_id()
    {
        $network_id = \defined('SITE_ID_CURRENT_SITE') ? SITE_ID_CURRENT_SITE : 1;

        if (!is_multisite()) {
            return $network_id;
        }

        if (!nwdcx_wpdb($wpdb)) {
            return $network_id;
        }

        if (empty($_SERVER['HTTP_HOST'])) {
            return $network_id;
        }

        static $cache = [];

        $hostname = nwdcx_fixhost($_SERVER['HTTP_HOST']);

        if (isset($cache[$hostname])) {
            return $cache[$hostname];
        }

        $table = $wpdb->base_prefix.'site';
        $suppress = $wpdb->suppress_errors(true);

        $query = "SELECT `id`,`domain`,`path` FROM `{$table}` ORDER BY id ASC LIMIT 100";
        $networks = $wpdb->get_results($query, ARRAY_A);

        if (!empty($networks) && \is_array($networks)) {
            while ($row = @array_shift($networks)) {
                $id = $row['id'];
                $domain = $row['domain'];
                $path = $row['path'];

                if ($hostname === $domain) {
                    $network_id = $id;
                    break;
                }

                if (false !== strpos($hostname, $domain) && @preg_match('@(.*?\.)?'.preg_quote($domain, '@').'$@', $hostname)) {
                    $network_id = $id;
                    break;
                }
            }
        }
        $wpdb->suppress_errors($suppress);

        $cache[$hostname] = $network_id;

        return $cache[$hostname];
    }
}

if (!\function_exists('nwdcx_network_dirpath')) {
    function nwdcx_network_dirpath($save_path)
    {
        if (nwdcx_network_multi() && !nwdcx_network_main()) {
            $save_path = rtrim($save_path, '/').'/network-'.nwdcx_network_id().'/';
        }

        return $save_path;
    }
}

if (!\function_exists('nwdcx_network_filepath')) {
    function nwdcx_network_filepath($file_path)
    {
        if (nwdcx_network_multi() && !nwdcx_network_main()) {
            $ext = substr($file_path, -4);
            $fname = substr($file_path, 0, -4);

            $file_path = $fname.'-'.nwdcx_network_id().$ext;
        }

        return $file_path;
    }
}
