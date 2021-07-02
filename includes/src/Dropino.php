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

final class Dropino extends Bepart
{
    private $path;
    public $wpcondir;
    public $condir;

    public function __construct($path)
    {
        $this->path = wp_normalize_path($path);

        $this->wpcondir = \defined('WP_CONTENT_DIR') ? WP_CONTENT_DIR : ABSPATH.'wp-content';
        $this->wpcondir = wp_normalize_path($this->wpcondir);

        $this->condir = \defined('DOCKET_CACHE_CONTENT_PATH') ? DOCKET_CACHE_CONTENT_PATH : $this->wpcondir;
        $this->condir = wp_normalize_path($this->condir);
    }

    /**
     * file.
     */
    public function resc()
    {
        $dt = [];
        $dt['src'] = $this->path.'/includes/object-cache.php';
        $dt['dst'] = $this->condir.'/object-cache.php';
        $dt['wpdst'] = $this->wpcondir.'/object-cache.php';

        // sync with includes/object-cache.php
        $dt['halt'] = $this->condir.'/.object-cache-delay.txt';
        $dt['after'] = $this->condir.'/.object-cache-after-delay.txt';

        return (object) $dt;
    }

    /**
     * exists.
     */
    public function exists()
    {
        clearstatcache();

        return @is_file($this->resc()->dst);
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

        $cache['dropino'] = $this->plugin_meta($this->condir.'/object-cache.php');
        $cache['plugin'] = $this->plugin_meta($this->path.'/includes/object-cache.php');

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

        if (0 !== strcmp(nwdcx_noscheme($this->meta('dropino')['PluginURI']), nwdcx_noscheme($this->meta('plugin')['PluginURI']))) {
            return false;
        }

        return true;
    }

    /**
     * is_valid.
     */
    public function is_outdated()
    {
        return version_compare($this->meta('dropino')['Version'], $this->meta('plugin')['Version'], '<') || false === strpos($this->meta('dropino')['Version'], '.');
    }

    /**
     * delay.
     */
    public function delay($seconds = 5)
    {
        $time = time() + (int) $seconds;
        $file_delay = $this->resc()->halt;
        if ($this->put($file_delay, $time)) {
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
        }
        if (@is_file($after_delay)) {
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

    /**
     * after_delay.
     */
    public function after_delay()
    {
        $after_delay = $this->resc()->after;
        if (@is_file($after_delay)) {
            if (@unlink($after_delay)) {
                return $this->code_worker(['flush', 'preload']);
            }
        }

        return false;
    }

    /**
     * install.
     */
    public function install($delay = false)
    {
        if (nwdcx_construe('DISABLED')) {
            return false;
        }

        $src = $this->resc()->src;
        $dst = $this->resc()->dst;

        if (is_writable(\dirname($dst))) {
            if ($delay) {
                $this->delay();
            }

            if ($this->copy($src, $dst)) {
                // refresh
                $this->opcache_flush($dst);

                $this->multinet_active(true);

                return true;
            }
        }

        return false;
    }

    /**
     * uninstall.
     */
    public function uninstall($delay = false)
    {
        if (nwdcx_construe('DISABLED')) {
            return false;
        }

        $file = $this->resc()->dst;

        $this->undelay();

        if (!@is_file($file)) {
            return true;
        }

        // remove flag file to trigger disable at admin interface
        $this->multinet_active(false);

        // dont remove drop-in if active on sub network.
        if ((!\defined('WP_CLI') || !WP_CLI) && $this->multinet_available()) {
            return true;
        }

        $this->opcache_flush($file);

        if (is_writable($file) && @unlink($file)) {
            return true;
        }

        return false;
    }

    public function multinet_install($hook)
    {
        if (!is_multisite() || !nwdcx_network_multi()) {
            return false;
        }

        if (!nwdcx_wpdb($wpdb)) {
            return false;
        }

        $table = $wpdb->base_prefix.'sitemeta';

        $network_id = null;

        $suppress = $wpdb->suppress_errors(true);

        $query = "SELECT `site_id`,`meta_key`,`meta_value` FROM `{$table}` WHERE `meta_key`='active_sitewide_plugins' ORDER BY site_id ASC LIMIT 100";
        $results = $wpdb->get_results($query, ARRAY_A);
        if (!empty($results) && \is_array($results)) {
            while ($row = @array_shift($results)) {
                $site_id = $row['site_id'];
                $meta_key = $row['meta_key'];
                $meta_value = maybe_unserialize($row['meta_value']);

                if (null === $network_id) {
                    $network_id = $site_id;
                }

                if (!empty($meta_value) && \is_array($meta_value) && !empty($meta_value[$hook])) {
                    $this->multinet_active(true, $site_id);
                }
            }
        }

        if (null !== $network_id) {
            @file_put_contents($this->condir.'/.object-cache-network-main.txt', $network_id, \LOCK_EX);
        }

        $wpdb->suppress_errors($suppress);
    }

    private function multinet_clear_main($cleanup = false)
    {
        $file = $this->condir.'/.object-cache-network-main.txt';
        if (@is_file($file)) {
            @unlink($file);
        }

        if ($cleanup) {
            $file = $this->condir.'/.object-cache-network-multi.txt';
            if (@is_file($file)) {
                @unlink($file);
            }
        }
    }

    private function multinet_list()
    {
        $list = [];
        $files = @glob($this->condir.'/.object-cache-network-*.txt', \GLOB_MARK | \GLOB_NOSORT);
        if (!empty($files) && \is_array($files)) {
            foreach ($files as $file) {
                $fx = basename($file);
                if (@preg_match('@^\.object-cache-network-(\d+)\.txt$@', $fx, $mm)) {
                    $id = $mm[1];
                    $list[$id] = $file;
                }
            }
        }

        return !empty($list) ? $list : false;
    }

    public function multinet_available()
    {
        $network_id = get_current_network_id();
        $files = $this->multinet_list();
        if (!empty($files) && \is_array($files)) {
            foreach ($files as $id => $file) {
                if ($id !== $network_id) {
                    return true;
                }
            }
        }

        return false;
    }

    public function multinet_clear($cache_path, $logfile)
    {
        clearstatcache();
        $this->multinet_clear_main(true);

        $files = $this->multinet_list();
        if (!empty($files) && \is_array($files)) {
            foreach ($files as $network_id => $file) {
                $cachepath = $cache_path;
                if (@is_file($file)) {
                    if (false === strpos($cache_path, '/network-')) {
                        $cachepath = $cache_path.'/network-'.$network_id.'/';
                    }

                    if (@is_dir($cachepath)) {
                        $this->cachedir_flush($cachepath);
                    }
                    @unlink($file);
                }

                $ext = substr($logfile, -4);
                $fname = substr($logfile, 0, -4);
                $logfile = $fname.'-'.$network_id.$ext;
                if (@is_file($logfile)) {
                    @unlink($logfile);
                }
            }
        }
    }

    public function multinet_tag($network_id = false)
    {
        $network_id = empty($network_id) ? get_current_network_id() : $network_id;

        return sprintf('%s/.object-cache-network-%s.txt', $this->condir, $network_id);
    }

    public function multinet_active($status = false, $network_id = false)
    {
        if (!is_multisite() || !nwdcx_network_multi()) {
            return false;
        }

        $lock_file = $this->condir.'/.object-cache-network-multi.txt';
        if (!@is_file($lock_file)) {
            @file_put_contents($lock_file, 1, \LOCK_EX);
        }

        if (empty($network_id)) {
            $this->multinet_clear_main();
            $network_id = get_current_network_id();
        }

        $file = $this->multinet_tag($network_id);

        clearstatcache();

        $is_file = @is_file($file);
        if ($status) {
            if ($is_file) {
                return true;
            }

            return $this->put($file, $network_id);
        }

        if (!$is_file) {
            return true;
        }

        return @unlink($file);
    }

    public function multinet_me()
    {
        if (!is_multisite()) {
            return true;
        }

        if (!nwdcx_network_multi()) {
            return true;
        }

        $file = $this->multinet_tag();

        return @is_file($file);
    }

    public function is_alternative()
    {
        if (0 === strcmp($this->wpcondir, $this->condir)) {
            return false;
        }

        return true;
    }
}
