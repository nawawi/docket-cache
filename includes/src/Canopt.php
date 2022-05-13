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

final class Canopt extends Bepart
{
    public $file;
    public $path;
    public $path_lock;

    private static $inst;

    public function __construct()
    {
        $this->define_path();
    }

    private function define_path()
    {
        $path = '';
        $constfx = nwdcx_constfx('DATA_PATH');
        if (\defined($constfx)) {
            $path = \constant($constfx);
        }

        if (!empty($path)) {
            if ('docket-cache-data' !== basename($path)) {
                $path = rtrim($path, '/\\').'/docket-cache-data';
            }
        } else {
            $path = rtrim(DOCKET_CACHE_CONTENT_PATH, '/\\').'/docket-cache-data';
        }

        $path = wp_normalize_path($path);

        if (is_multisite()) {
            $path = nwdcx_network_dirpath($path);
        }

        $this->path = rtrim($path, '/\\');

        $this->file = $this->path.'/options.php';
        $this->path_lock = $this->path.'/lock';
    }

    public static function init()
    {
        if (!isset(self::$inst)) {
            self::$inst = new self();
        }

        return self::$inst;
    }

    public function is_options_writable()
    {
        if (@is_file($this->file)) {
            return @is_writable($this->file);
        }

        if (@is_dir($this->path)) {
            return @is_writable($this->path);
        }

        return @is_writable(\dirname($this->path));
    }

    public function keys($key = false)
    {
        // key = title
        $data = [
            'log' => esc_html__('Cache Log', 'docket-cache'),
            'log_time' => esc_html__('Log Timestamp', 'docket-cache'),
            'log_all' => esc_html__('Log All', 'docket-cache'),
            'preload' => esc_html__('Admin Page Cache Preloading', 'docket-cache'),
            'advcpost' => esc_html__('Advanced Post Caching', 'docket-cache'),
            'advpost_posttype_all' => esc_html__('Post Caching Any Post Type', 'docket-cache'),
            'menucache' => esc_html__('WordPress Menu Caching', 'docket-cache'),
            'optermcount' => esc_html__('Optimize Term Count Queries', 'docket-cache'),
            'precache' => esc_html__('Object Cache Precaching', 'docket-cache'),
            'mocache' => esc_html__('WordPress Translation Caching', 'docket-cache'),
            'misc_tweaks' => esc_html__('Misc Performance Tweaks', 'docket-cache'),
            'postmissedschedule' => esc_html__('Post Missed Schedule Tweaks', 'docket-cache'),
            'wootweaks' => esc_html__('Misc WooCommerce Tweaks', 'docket-cache'),
            'wooadminoff' => esc_html__('Deactivate WooCommerce Admin', 'docket-cache'),
            'woowidgetoff' => esc_html__('Deactivate WooCommerce Widget', 'docket-cache'),
            'woowpdashboardoff' => esc_html__('Deactivate WooCommerce WP Dashboard', 'docket-cache'),
            'wooextensionpageoff' => esc_html__('Deactivate WooCommerce Extensions Page', 'docket-cache'),
            'woocartfragsoff' => esc_html__('Deactivate WooCommerce Cart Fragments', 'docket-cache'),
            'wooaddtochartcrawling' => esc_html__('Prevent robots crawling add-to-cart links', 'docket-cache'),
            'pageloader' => esc_html__('Admin Page Loader', 'docket-cache'),
            'wpoptaload' => esc_html__('Suspend WP Options Autoload', 'docket-cache'),
            'cronoptmzdb' => esc_html__('Optimize Database Tables', 'docket-cache'),
            'cronbot' => esc_html__('Cronbot Service', 'docket-cache'),
            'opcviewer' => esc_html__('OPcache Viewer', 'docket-cache'),
            'stats' => esc_html__('Object Cache Data Stats', 'docket-cache'),
            'gcaction' => esc_html__('Garbage Collector Action Button', 'docket-cache'),
            'flushaction' => esc_html__('Additional Flush Cache Action Button', 'docket-cache'),
            'autoupdate' => esc_html__('Docket Cache Auto Update', 'docket-cache'),
            'checkversion' => esc_html__('Critical Version Checking', 'docket-cache'),
            'optwpquery' => esc_html__('Optimize WP Query', 'docket-cache'),
            'pingback' => esc_html__('Deactivate XML-RPC / Pingbacks', 'docket-cache'),
            'headerjunk' => esc_html__('Deactivate WP Header Junk', 'docket-cache'),
            'wpemoji' => esc_html__('Deactivate WP Emoji', 'docket-cache'),
            'wpembed' => esc_html__('Deactivate WP Embed', 'docket-cache'),
            'wpfeed' => esc_html__('Deactivate WP Feed', 'docket-cache'),
            'wplazyload' => esc_html__('Deactivate WP Lazy Load', 'docket-cache'),
            'wpsitemap' => esc_html__('Deactivate WP Sitemap', 'docket-cache'),
            'wpapppassword' => esc_html__('Deactivate WP Application Passwords', 'docket-cache'),
            'wpdashboardnews' => esc_html__('Deactivate WP Events & News Feed Dashboard', 'docket-cache'),
            'wpbrowsehappy' => esc_html__('Deactivate Browse Happy Checking', 'docket-cache'),
            'wpservehappy' => esc_html__('Deactivate Serve Happy Checking', 'docket-cache'),
            'objectcacheoff' => esc_html__('Suspend Object Cache', 'docket-cache'),
            'flush_shutdown' => esc_html__('Flush Object Cache During Deactivation', 'docket-cache'),
            'opcshutdown' => esc_html__('Flush OPcache During Deactivation', 'docket-cache'),
            'maxsize_disk' => esc_html__('Cache Disk Limit', 'docket-cache'),
            'maxfile' => esc_html__('Cache Files Limit', 'docket-cache'),
            'chunkcachedir' => esc_html__('Chunk Cache Directory', 'docket-cache'),
            'flush_stalecache' => esc_html__('Auto Remove Stale Cache', 'docket-cache'),
            'limithttprequest' => esc_html__('Limit WP-Admin HTTP requests', 'docket-cache'),
            'httpheadersexpect' => esc_html__('HTTP Request Expect header tweaks', 'docket-cache'),
            'rtpostautosave' => esc_html__('Auto Save Interval', 'docket-cache'),
            'rtpostrevision' => esc_html__('Post Revisions', 'docket-cache'),
            'rtpostemptytrash' => esc_html__('Trash Bin', 'docket-cache'),
            'rtpluginthemeeditor' => esc_html__('Disallows Plugin / Theme Editor', 'docket-cache'),
            'rtpluginthemeinstall' => esc_html__('Disallows Plugin / Theme Update and Installation', 'docket-cache'),
            'rtimageoverwrite' => esc_html__('Cleanup Image Edits', 'docket-cache'),
            'rtwpdebug' => esc_html__('WP Debug', 'docket-cache'),
            'rtwpdebugdisplay' => esc_html__('WP Debug Display', 'docket-cache'),
            'rtwpdebuglog' => esc_html__('WP Debug Log', 'docket-cache'),
            'rtwpcoreupdate' => esc_html__('Disallows WP Auto Update Core', 'docket-cache'),
        ];

        $data = apply_filters('docketcache/filter/optionkeys', $data);

        if (false !== $key) {
            if (!empty($data[$key])) {
                return $data[$key];
            }

            return false;
        }

        return array_keys($data);
    }

    public function read_config($file = '', $force = false)
    {
        $file = empty($file) ? $this->file : $file;
        $config = [];
        if (@is_file($file) && is_readable($file)) {
            // fresh
            if ($force) {
                $this->opcache_flush($file);
            }

            try {
                $config = @include $file;
            } catch (\Throwable $e) {
                nwdcx_throwable(__METHOD__, $e);
            }
        }

        return $config;
    }

    public function put_config($config, $file = '')
    {
        $file = empty($file) ? $this->file : $file;
        if (empty($config) || !\is_array($config)) {
            if (@is_file($file) && @is_writable($file)) {
                @unlink($file);
            }

            clearstatcache();

            return false;
        }

        $data = $this->export_var($config);
        $code = $this->code_stub($data);

        return $this->dump($file, $code);
    }

    public function config_cleanup($config)
    {
        if (!empty($config) && \is_array($config)) {
            $keys = $this->keys();
            foreach ($config as $name => $value) {
                $nx = strtolower(nwdcx_constfx($name, true));
                if (!\in_array($nx, $keys) || (\is_string($value) && 'default' === $value)) {
                    unset($config[$name]);
                }
            }
        }

        return $config;
    }

    public function get($name)
    {
        $config = $this->read_config();

        if (!empty($config) && !empty($config[$name])) {
            return $config[$name];
        }

        return false;
    }

    public function save($name, $value)
    {
        if (!$this->mkdir_p($this->path)) {
            return false;
        }

        $this->placeholder($this->path);

        $config = $this->read_config();
        $config = $this->config_cleanup($config);

        if (\in_array($name, $this->keys())) {
            $nx = nwdcx_constfx($name);

            if ('default' === $value) {
                unset($config[$nx]);
            } else {
                $config[$nx] = $value;
            }
        }

        $ret = $this->put_config($config);
        do_action('docketcache/action/saveoption', $name, $value, $ret);

        return $ret;
    }

    public function save_part($data, $file = 'part')
    {
        $file = $this->path.'/'.$file.'.php';

        return $this->put_config($data, $file);
    }

    public function get_part($file = 'part', $force = false)
    {
        $file = $this->path.'/'.$file.'.php';

        return $this->read_config($file, $force);
    }

    public function clear_part($file)
    {
        $file = $this->path.'/'.$file.'.php';
        if (!@is_file($file)) {
            return true;
        }

        $ret = @unlink($file);
        clearstatcache();

        return $ret;
    }

    public function clear_lock()
    {
        $path = $this->path_lock;
        if (!@is_dir($path)) {
            return false;
        }

        $files = @glob($path.'/lock-*.txt', \GLOB_MARK | \GLOB_NOSORT);
        if (!empty($files) && \is_array($files)) {
            foreach ($files as $file) {
                if (@is_file($file) && @is_writable($file)) {
                    if (\defined('DocketCache_CLI') && DocketCache_CLI) {
                        @fwrite(\STDOUT, basename($file).\PHP_EOL);
                    }
                    @unlink($file);
                }
            }
        }

        $files = @glob($path.'/dump_*.txt', \GLOB_MARK | \GLOB_NOSORT);
        if (!empty($files) && \is_array($files)) {
            foreach ($files as $file) {
                if (@is_file($file) && @is_writable($file)) {
                    @unlink($file);
                }
            }
        }

        clearstatcache();

        return true;
    }

    private function lock_file($key)
    {
        $key = substr(md5($key), 0, 12);
        $path = $this->path_lock;
        if (!$this->mkdir_p($path.'/')) {
            return false;
        }
        $this->placeholder($path);

        return $path.'/lock-'.$key.'.txt';
    }

    public function setlock($key, $value)
    {
        $file = $this->lock_file($key);
        if (!$file) {
            return false;
        }

        if (true === $this->dump($file, $value)) {
            return true;
        }

        return false;
    }

    public function unlock($key)
    {
        $file = $this->lock_file($key);
        if (!$file || !@is_file($file)) {
            return true;
        }

        $ret = @unlink($file);
        clearstatcache();

        return $ret;
    }

    public function locked($key, &$value = '')
    {
        clearstatcache();

        $file = $this->lock_file($key);
        if (!$file || !@is_file($file)) {
            return false;
        }

        $value = @file_get_contents($file);

        return true;
    }

    // locked and not expire
    public function lockexp($key)
    {
        if ($this->locked($key, $locked)) {
            if (!empty($locked) && (int) $locked > time()) {
                return true;
            }
        }

        return false;
    }

    // lock process
    public function lockproc($key, $value)
    {
        // if locked and not expire
        if ($this->lockexp($key)) {
            return true;
        }

        // if expire set new lock
        $this->setlock($key, $value);

        return false;
    }

    public function lockreset($key)
    {
        return $this->setlock($key, 0);
    }

    // lookup
    public function lookup_set($key, $value)
    {
        $fkey = 'lockup-'.$key;

        return $this->setlock($fkey, maybe_serialize($value));
    }

    public function lookup_get($key, $forget = false)
    {
        $fkey = 'lockup-'.$key;
        if ($this->locked($fkey, $value)) {
            if (!empty($value)) {
                if ($forget) {
                    $this->lookup_delete($key);
                }

                return maybe_unserialize($value);
            }
        }

        if ($forget) {
            $this->lookup_delete($key);
        }

        return false;
    }

    public function lookup_delete($key)
    {
        $fkey = 'lockup-'.$key;

        return $this->unlock($fkey);
    }

    public function reset()
    {
        $this->clear_lock();

        $parts = [
            'options',
            'runtime',
            'pings',
            'cronbot',
            'checkversion',
            'cachestats',
            'gc',
        ];

        $ok = true;
        foreach ($parts as $part) {
            $file = $this->path.'/'.$part.'.php';
            if (@is_file($file) && is_writable($file)) {
                if (!@unlink($file)) {
                    $ok = false;
                }
            }
        }

        return $ok;
    }
}
