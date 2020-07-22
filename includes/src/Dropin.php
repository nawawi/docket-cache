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

final class Dropin
{
    private $plugin;

    public function __construct(Plugin $plugin)
    {
        $this->plugin = $plugin;
    }

    /**
     * file.
     */
    private function resc()
    {
        $dt = [];
        $dt['src'] = $this->plugin->path.'/includes/object-cache.php';
        $dt['dst'] = WP_CONTENT_DIR.'/object-cache.php';
        $dt['halt'] = WP_CONTENT_DIR.'/object-cache-delay.txt';
        $dt['after'] = WP_CONTENT_DIR.'/object-cache-after-delay.txt';

        return (object) $dt;
    }

    /**
     * exists.
     */
    public function exists()
    {
        return @is_file(WP_CONTENT_DIR.'/object-cache.php');
    }

    /**
     * meta.
     */
    private function meta($key)
    {
        static $cache = [];

        if (isset($cache[$key])) {
            return $cache[$key];
        }

        $cache['dropin'] = $this->plugin->plugin_meta(WP_CONTENT_DIR.'/object-cache.php');
        $cache['plugin'] = $this->plugin->plugin_meta($this->plugin->path.'/includes/object-cache.php');

        return $cache[$key];
    }

    /**
     * validate.
     */
    public function validate()
    {
        if (!$this->exists()) {
            return false;
        }

        if (0 !== strcmp($this->meta('dropin')['PluginURI'], $this->meta('plugin')['PluginURI'])) {
            return false;
        }

        return true;
    }

    /**
     * is_valid.
     */
    public function is_outdated()
    {
        return version_compare($this->meta('dropin')['Version'], $this->meta('plugin')['Version'], '<') || false === strpos($this->meta('dropin')['Version'], '.');
    }

    /**
     * delay.
     */
    public function delay()
    {
        $time = time() + 5;
        $file_delay = $this->resc()->halt;
        if ($this->plugin->put($file_delay, $time)) {
            @touch($file_delay, $time);
        }
    }

    /**
     * undelay.
     */
    public function undelay()
    {
        $file_delay = $this->resc()->halt;
        $after_delay = $this->resc()->after;
        if (@is_file($file_delay)) {
            @unlink($file_delay);
            @unlink($after_delay);
        }
    }

    /**
     * delay_expire.
     */
    public function delay_expire()
    {
        $file_delay = $this->resc()->halt;
        $after_delay = $this->resc()->after;
        if (@is_file($file_delay) && time() > @filemtime($file_delay)) {
            @rename($file_delay, $after_delay);
        }
    }

    public function after_delay()
    {
        $after_delay = $this->resc()->after;
        if (@is_file($after_delay)) {
            if (@unlink($after_delay)) {
                // pass to admin_footer
                return $this->plugin->code_preload();
            }
        }
    }

    /**
     * install.
     */
    public function install($delay = false)
    {
        $src = $this->resc()->src;
        $dst = $this->resc()->dst;

        if (is_writable(\dirname($this->resc()->dst))) {
            if ($delay) {
                $this->delay();
            }

            return $this->plugin->copy($src, $dst);
        }

        return false;
    }

    /**
     * uninstall.
     */
    public function uninstall()
    {
        $dst = $this->resc()->dst;

        $this->undelay();

        if (!@is_file($dst)) {
            return true;
        }

        if (is_writable($dst)) {
            return @unlink($dst);
        }

        return false;
    }

    /**
     * remove.
     */
    public function remove()
    {
        $this->plugin->flush_cache();
        if ($this->validate()) {
            $this->uninstall();
            $this->plugin->flush_log();
        }
    }
}
