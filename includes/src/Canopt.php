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

    private static $inst;

    public function __construct()
    {
        $this->path = DOCKET_CACHE_DATA_PATH;
        $this->file = $this->path.'/options.php';
    }

    public static function init()
    {
        if (!isset(self::$inst)) {
            self::$inst = new self();
        }

        return self::$inst;
    }

    public function keys()
    {
        return [
             'log',
             'log_time',
             'preload',
             'advcpost',
             'optermcount',
             'precache',
             'mocache',
             'misc_tweaks',
             'postmissedschedule',
             'wootweaks',
             'pageloader',
             'wpoptaload',
             'cronoptmzdb',
             'cronbot',
         ];
    }

    private function read_config($file = '')
    {
        $file = empty($file) ? $this->file : $file;
        $config = [];
        if (@is_file($file) && is_readable($file)) {
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
        do_action('docket-cache/save-option', $name, $value, $ret);

        return $ret;
    }

    public function save_part($data, $file = 'part')
    {
        $file = $this->path.'/'.$file.'.php';

        return $this->put_config($data, $file);
    }

    public function get_part($file = 'part')
    {
        $file = $this->path.'/'.$file.'.php';

        return $this->read_config($file);
    }
}
