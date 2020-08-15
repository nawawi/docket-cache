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
    private $file;
    private $path;

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
             'connectsaas',
         ];
    }

    public function get($name)
    {
        $config = [];
        if (@is_file($this->file) && is_readable($this->file)) {
            $config = @include $this->file;
        }

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

        $config = [];
        if (@is_file($this->file)) {
            $config = @include $this->file;
        }

        if (\in_array($name, $this->keys())) {
            $nx = 'DOCKET_CACHE_'.strtoupper($name);

            if ('default' === $value) {
                unset($config[$nx]);
            } else {
                $config[$nx] = $value;
            }
        }

        $code = '<?php ';
        $code .= "defined('ABSPATH') || exit;".PHP_EOL;
        $code .= 'return '.$this->export_var($config).';';

        $ret = $this->dump($this->file, $code);
        do_action('docket-cache/save-option', $name, $value, $ret);

        return $ret;
    }
}
