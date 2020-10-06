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
        $this->path = DOCKET_CACHE_DATA_PATH;
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

    public function keys($key = false)
    {
        $data = [
            'log' => esc_html__('Cache Log', 'docket-cache'),
            'log_time' => esc_html__('Log Timestamp', 'docket-cache'),
            'preload' => esc_html__('Admin Page Cache Preloading', 'docket-cache'),
            'advcpost' => esc_html__('Advanced Post Caching', 'docket-cache'),
            'optermcount' => esc_html__('Optimize Term Count Queries', 'docket-cache'),
            'precache' => esc_html__('Object Cache Precaching', 'docket-cache'),
            'mocache' => esc_html__('WordPress Translation Caching', 'docket-cache'),
            'misc_tweaks' => esc_html__('Misc Performance Tweaks', 'docket-cache'),
            'postmissedschedule' => esc_html__('Post Missed Schedule Tweaks', 'docket-cache'),
            'wootweaks' => esc_html__('Misc WooCommerce Tweaks', 'docket-cache'),
            'pageloader' => esc_html__('Admin Page Loader', 'docket-cache'),
            'wpoptaload' => esc_html__('Suspend WP Options Autoload', 'docket-cache'),
            'cronoptmzdb' => esc_html__('Optimize Database Tables', 'docket-cache'),
            'cronbot' => esc_html__('Cronbot Service', 'docket-cache'),
            'stats' => esc_html__('Object Cache File Stats', 'docket-cache'),
            'autoupdate' => esc_html__('Auto Update', 'docket-cache'),
            'checkversion' => esc_html__('Critical Notice Checking', 'docket-cache'),
        ];

        if (false !== $key) {
            if (!empty($data[$key])) {
                return $data[$key];
            }

            return false;
        }

        return array_keys($data);
    }

    private function read_config($file = '', $force = false)
    {
        $file = empty($file) ? $this->file : $file;
        $config = [];
        if (@is_file($file) && is_readable($file)) {
            // fresh
            if ($force) {
                $this->opcache_flush($file);
            }

            $config = @include $file;
        }

        return $config;
    }

    private function put_config($config, $file = '')
    {
        $file = empty($file) ? $this->file : $file;
        if (empty($config) || !\is_array($config)) {
            @unlink($file);

            return false;
        }

        $data = $this->export_var($config);
        $code = $this->code_stub($data);

        return $this->dump($file, $code);
    }

    private function cleanup($config)
    {
        if (!empty($config) && \is_array($config)) {
            $keys = $this->keys();
            foreach ($config as $name => $value) {
                $nx = str_replace('DOCKET_CACHE_', '', $name);
                $nx = strtolower($nx);

                if (!\in_array($nx, $keys)) {
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
        if (!@wp_mkdir_p($this->path)) {
            return false;
        }

        $this->placeholder($this->path);

        $config = $this->read_config();
        $config = $this->cleanup($config);

        if (\in_array($name, $this->keys())) {
            $nx = 'DOCKET_CACHE_'.strtoupper($name);

            if ('default' === $value) {
                unset($config[$nx]);
            } else {
                $config[$nx] = $value;
            }
        }

        $ret = $this->put_config($config);
        do_action('docketcache/save-option', $name, $value, $ret);

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

        return @unlink($file);
    }

    public function clear_lock()
    {
        $path = $this->path_lock;
        if (!@is_dir($path)) {
            return false;
        }

        $files = glob($path.'/lock-*.txt', GLOB_MARK | GLOB_NOSORT);
        if (!empty($files) && \is_array($files)) {
            foreach ($files as $file) {
                if (@is_file($file) && @is_writable($file)) {
                    if (\defined('DocketCache_CLI') && DocketCache_CLI) {
                        fwrite(STDOUT, basename($file).PHP_EOL);
                    }
                    @unlink($file);
                }
            }
        }

        return true;
    }

    private function lock_file($key)
    {
        $key = substr(md5($key), 0, 12);
        $path = $this->path_lock;
        if (!@wp_mkdir_p($path.'/')) {
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

        $do_chmod = !@is_file($file);
        if (@file_put_contents($file, $value, LOCK_EX)) {
            if ($do_chmod) {
                $this->chmod($file);
            }

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

        return @unlink($file);
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

    public function lockexp($key)
    {
        if ($this->locked($key, $locked)) {
            if (!empty($locked) && (int) $locked > time()) {
                return true;
            }
        }

        return false;
    }
}
