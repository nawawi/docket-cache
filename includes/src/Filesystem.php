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

use Nawawi\DocketCache\Exporter\VarExporter;

class Filesystem
{
    public function is_docketcachedir($dir)
    {
        return 'docket-cache' === basename($dir);
    }

    public function filesize($file)
    {
        if (!@is_file($file)) {
            return 0;
        }

        return sprintf('%u', @filesize($file));
    }

    public function chmod($file)
    {
        static $cache = [];

        if (isset($cache[$file])) {
            return $cache[$file];
        }

        if (@is_file($file) && \defined('FS_CHMOD_FILE')) {
            $perms = FS_CHMOD_FILE;
        } elseif (@is_dir($file) && \defined('FS_CHMOD_DIR')) {
            $perms = FS_CHMOD_DIR;
        } else {
            $stat = @stat(\dirname($file));
            $perms = $stat['mode'] & 0007777;
            $perms = $perms & 0000666;
        }

        if (@chmod($file, $perms)) {
            $cache[$file] = $perms;
        }

        clearstatcache();
    }

    public function copy($src, $dst)
    {
        $this->opcache_flush($src);
        $this->opcache_flush($dst);

        if (@copy($src, $dst)) {
            $this->chmod($dst);

            return true;
        }

        return false;
    }

    public function scanfiles($dir, $maxdepth = 0)
    {
        $dir = realpath($dir);
        if (false !== $dir && is_dir($dir) && is_readable($dir)) {
            $diriterator = new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS | \RecursiveDirectoryIterator::KEY_AS_FILENAME | \RecursiveDirectoryIterator::CURRENT_AS_FILEINFO);
            $object = new \RegexIterator(new \RecursiveIteratorIterator($diriterator), '@^(dump_)?([a-z0-9]+)\-([a-z0-9]+).*\.php$@', \RegexIterator::MATCH, \RegexIterator::USE_KEY);
            $object->setMaxDepth($maxdepth);

            return $object;
        }

        return [];
    }

    public function tail($filepath, $limit = 100, $do_last = true)
    {
        $limit = (int) $limit;
        $file = new \SplFileObject($filepath);
        $file->seek(PHP_INT_MAX);
        $total_lines = $file->key();

        if ($limit > $total_lines) {
            $limit = $total_lines;
        }

        if ($do_last) {
            $total_lines = $total_lines > $limit ? $total_lines - $limit : $total_lines;
        } else {
            $total_lines = $limit;
        }

        $object = [];
        if ($total_lines > 0) {
            if ($do_last) {
                $object = new \LimitIterator($file, $total_lines);
            } else {
                $object = new \LimitIterator($file, 0, $total_lines);
            }
        }

        return $object;
    }

    public function export_var($data, &$error = '')
    {
        try {
            $data = VarExporter::export($data);
        } catch (\Exception $e) {
            $error = $e->getMessage();
            if (false !== strpos($error, 'Cannot export value of type "stdClass"')) {
                $data = var_export($data, 1);
                $data = str_replace('stdClass::__set_state', '(object)', $data);
            } else {
                return false;
            }
        }

        return $data;
    }

    public function unlink($file, $del = false)
    {
        // skip if not exist
        if (!@is_file($file)) {
            return true;
        }

        $ok = false;

        $handle = @fopen($file, 'cb');
        if ($handle) {
            if (@flock($handle, LOCK_EX | LOCK_NB)) {
                $ok = @ftruncate($handle, 0);
                @flock($handle, LOCK_UN);
            }
            @fclose($handle);
        }

        // bcoz we empty the file
        $this->opcache_flush($file);

        if ((DOCKET_CACHE_FLUSH_DELETE || $del) && @unlink($file)) {
            $ok = true;
        }

        if (false === $ok) {
            // if failed, try to remove on shutdown instead of truncate
            add_action(
                'shutdown',
                function () use ($file) {
                    @unlink($file);
                }
            );
        }

        // always true
        return true;
    }

    public function put($file, $data, $flag = 'cb')
    {
        if (!$handle = @fopen($file, $flag)) {
            return false;
        }

        $ok = false;
        if (@flock($handle, LOCK_EX | LOCK_NB)) {
            $len = \strlen($data);
            $cnt = @fwrite($handle, $data);
            @fflush($handle);
            @flock($handle, LOCK_UN);
            if ($len === $cnt) {
                $ok = true;
            }
        }
        @fclose($handle);
        clearstatcache();

        if (false === $ok) {
            $this->unlink($file, true);

            return -1;
        }

        $this->opcache_flush($file);
        $this->chmod($file);

        return $ok;
    }

    public function dump($file, $data)
    {
        $dir = \dirname($file);
        $tmpfile = $dir.'/'.'dump_'.uniqid().'_'.basename($file);
        add_action(
            'shutdown',
            function () use ($tmpfile) {
                @unlink($tmpfile);
            },
            PHP_INT_MAX
        );

        // alias
        $data = str_replace(
            '\Nawawi\Symfony\Component\VarExporter\Internal\\',
            '\Nawawi\DocketCache\Exporter\\',
            $data
        );

        $this->opcache_flush($file);

        $ok = $this->put($tmpfile, $data);
        if (true === $ok) {
            if (@rename($tmpfile, $file)) {
                $this->chmod($file);

                // compile
                $this->opcache_compile($file);

                return true;
            }

            // failed to replace
            $ok = false;
        }

        // maybe -1, true, false
        return $ok;
    }

    public function placeholder($path)
    {
        if (!@is_dir($path)) {
            return false;
        }

        $file = $path.'/index.html';
        if (@is_file($file)) {
            return false;
        }

        $code = '<html><head><meta name="robots" content="noindex, nofollow"></head>';
        $code .= '<body>Generated by <a href="https://wordpress.org/plugins/docket-cache/" rel="nofollow">Docket Cache</a></body></html>';
        $this->put($file, $code);
    }

    public function is_php($file)
    {
        return '.php' === substr($file, -4);
    }

    public function is_opcache_enable()
    {
        return @ini_get('opcache.enable') && \function_exists('opcache_reset');
    }

    public function opcache_is_cached($file)
    {
        if (!$this->is_opcache_enable()) {
            return false;
        }

        static $done = [];

        if (isset($done[$file])) {
            return $done[$file];
        }

        if (\function_exists('opcache_is_script_cached')) {
            $done[$file] = @opcache_is_script_cached($file);

            return $done[$file];
        }

        return false;
    }

    public function opcache_flush($file)
    {
        if (!$this->is_opcache_enable()) {
            return false;
        }

        static $done = [];

        if (isset($done[$file])) {
            return $done[$file];
        }

        // wp 5.5
        if (\function_exists('wp_opcache_invalidate')) {
            $done[$file] = @wp_opcache_invalidate($file, true);

            return $done[$file];
        }

        if (\function_exists('opcache_invalidate') && $this->is_php($file) && @is_file($file)) {
            $done[$file] = @opcache_invalidate($file, true);

            return $done[$file];
        }

        return false;
    }

    public function opcache_compile($file)
    {
        if (!$this->is_opcache_enable()) {
            return false;
        }

        static $done = [];

        if (isset($done[$file])) {
            return $done[$file];
        }

        if (\function_exists('opcache_compile_file') && $this->is_php($file) && @is_file($file)) {
            $done[$file] = @opcache_compile_file($file);

            return $done[$file];
        }

        return false;
    }

    public function opcache_reset($dir)
    {
        if (!$this->is_opcache_enable()) {
            return false;
        }

        @opcache_reset();
        $dir = realpath($dir);
        if (false !== $dir && is_dir($dir) && is_writable($dir) && $this->is_docketcachedir($dir)) {
            foreach ($this->scanfiles($dir) as $object) {
                $fx = $object->getPathName();
                if (!$object->isFile() || 'file' !== $object->getType() || !$this->is_php($fx)) {
                    continue;
                }

                $this->opcache_flush($fx);
            }
        }

        // always true
        return true;
    }

    public function define_cache_path($cache_path)
    {
        $cache_path = !empty($cache_path) && is_dir($cache_path) && '/' !== $cache_path ? rtrim($cache_path, '/\\').'/' : WP_CONTENT_DIR.'/cache/docket-cache/';
        if (!$this->is_docketcachedir($cache_path)) {
            $cache_path = rtim($cache_path, '/').'docket-cache/';
        }

        return $cache_path;
    }

    public function cachedir_flush($dir, $cleanup = false)
    {
        clearstatcache();
        $cnt = 0;
        $dir = realpath($dir);
        if (false === $dir || !@is_dir($dir) || !@is_writable($dir) || !$this->is_docketcachedir($dir)) {
            return false;
        }

        foreach ($this->scanfiles($dir) as $object) {
            if (!$object->isFile() || 'file' !== $object->getType()) {
                continue;
            }
            $this->unlink($object->getPathName(), $cleanup ? true : false);
            ++$cnt;
        }

        if ($cleanup) {
            $this->unlink($dir.'/index.php', true);
        }

        if (!$cleanup && $cnt > 0) {
            if (DOCKET_CACHE_WPCLI) {
                do_action('docket-cache/preload');
            }
        }

        return $cnt;
    }

    /**
     * cache_size.
     */
    public function cache_size($dir, $is_stats = false, $force = false)
    {
        if (!$force) {
            $cache_stats = wp_cache_get('cache_stats', 'docketcache-data');
            if (!empty($cache_stats) && \is_array($cache_stats)) {
                return $is_stats ? (object) $cache_stats : $cache_stats->size;
            }
        }

        $bytestotal = 0;
        $fsizetotal = 0;
        $filestotal = 0;
        if ($this->is_docketcachedir($dir)) {
            foreach ($this->scanfiles($dir) as $object) {
                if (!$object->isFile() || 'file' !== $object->getType()) {
                    continue;
                }

                $fx = $object->getPathName();
                $fs = $object->getSize();

                if (0 === $fs) {
                    @unlink($fx);
                    continue;
                }

                $data = $this->cache_get($object->getPathName());
                if (false !== $data) {
                    $bytestotal += \strlen(serialize($data));
                    ++$filestotal;
                }
                unset($data);
                $fsizetotal += $fs;
            }
        }

        $cache_stats = [
            'size' => $bytestotal,
            'filesize' => $fsizetotal,
            'files' => $filestotal,
        ];

        wp_cache_set('cache_stats', $cache_stats, 'docketcache-data', 10);

        return $is_stats ? (object) $cache_stats : $bytestotal;
    }

    public function cache_get($file)
    {
        if (!@is_file($file) || empty($this->filesize($file))) {
            return false;
        }

        if (!$handle = @fopen($file, 'rb')) {
            return false;
        }

        $data = [];

        // include when we can read, try to avoid fatal error.
        if (flock($handle, LOCK_SH)) {
            $data = @include $file;
            @flock($handle, LOCK_UN);
        }
        @fclose($handle);

        if (empty($data) || !isset($data['data'])) {
            return false;
        }

        return $data;
    }

    public function log($tag, $id, $data, $caller = '')
    {
        $do_flush = false;
        $file = DOCKET_CACHE_LOG_FILE;
        if (@is_file($file)) {
            if (DOCKET_CACHE_LOG_FLUSH && 'flush' === $tag || $this->filesize($file) >= (int) DOCKET_CACHE_LOG_SIZE) {
                $do_flush = true;
            }
        }

        $meta = [];
        $date_format = 'Y-m-d H:i:s T';
        $timestamp = date($date_format);
        $meta['timestamp'] = $timestamp;

        if (!empty($caller)) {
            $meta['caller'] = $caller;
        }

        if (\is_array($data)) {
            $log = $this->export_var(array_merge($meta, $data));
        } else {
            $log = '['.$meta['timestamp'].'] '.$tag.': "'.$id.'" "'.trim($data).'" "'.$caller.'"';
        }

        return @$this->put($file, $log.PHP_EOL, $do_flush ? 'cb' : 'ab');
    }
}
