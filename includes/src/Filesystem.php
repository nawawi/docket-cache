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

final class Filesystem
{
    public function is_docketcachedir($dir)
    {
        return 'docket-cache' === basename($dir);
    }

    public function filesize($file)
    {
        return file_exists($file) ? @sprintf('%u', @filesize($file)) : 0;
    }

    public function chmod($file)
    {
        static $cache = [];

        if (isset($cache[$file])) {
            return $cache[$file];
        }

        if (is_file($file) && \defined('FS_CHMOD_FILE')) {
            $perms = FS_CHMOD_FILE;
        } elseif (is_dir($file) && \defined('FS_CHMOD_DIR')) {
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
            }
        );

        // alias
        $data = str_replace('\Nawawi\Symfony\Component\VarExporter\Internal\\', '\Nawawi\DocketCache\Exporter\\', $data);

        $ok = $this->put($tmpfile, $data);
        if (true === $ok) {
            if (@rename($tmpfile, $file)) {
                $this->chmod($file);

                // compile
                $this->opcache_compile($file);

                return true;
            }
        }

        // maybe -1, true, false
        return $ok;
    }

    public function cachedir_flush($dir)
    {
        clearstatcache();
        $cnt = 0;
        $dir = realpath($dir);
        if (false !== $dir && is_dir($dir) && is_writable($dir) && $this->is_docketcachedir($dir)) {
            foreach ($this->scanfiles($dir) as $object) {
                $this->unlink($object->getPathName(), false);
                ++$cnt;
            }
            $this->unlink($dir.'/index.php', true);
        }

        if ($cnt > 0) {
            if (DOCKET_CACHE_WPCLI) {
                do_action('docket_preload');
            }
        }

        return $cnt;
    }

    public function opcache_flush($file)
    {
        static $done = [];

        if (isset($done[$file])) {
            return true;
        }

        if (\function_exists('opcache_invalidate') && 'php' === substr($file, -3) && file_exists($file)) {
            $done[$file] = $file;

            return @opcache_invalidate($file, true);
        }

        return false;
    }

    public function opcache_compile($file)
    {
        static $done = [];

        if (isset($done[$file])) {
            return true;
        }

        if (\function_exists('opcache_compile_file') && 'php' === substr($file, -3) && file_exists($file)) {
            $done[$file] = $file;

            return @opcache_compile_file($file);
        }

        return false;
    }

    public function cache_get($file)
    {
        if (!file_exists($file) || empty($this->filesize($file))) {
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
        $file = DOCKET_CACHE_LOG_FILE;
        if (file_exists($file)) {
            if (DOCKET_CACHE_LOG_FLUSH && 'flush' === strtolower($id) || $this->filesize($file) >= (int) DOCKET_CACHE_LOG_SIZE) {
                $this->put($file, '', 'cb');
            }
        }

        if (false !== strpos($caller, '?page=docket-cache&index=log&order=')) {
            $caller = @preg_replace('@index=log\&order=.*@', 'index=log', $caller);
        }

        $meta = [];
        $meta['timestamp'] = date('Y-m-d H:i:s e');

        if (!empty($caller)) {
            $meta['caller'] = $caller;
        }

        if (\is_array($data)) {
            $log = $this->export_var(array_merge($meta, $data));
        } else {
            $log = '['.$meta['timestamp'].'] '.$tag.': "'.$id.'" "'.trim($data).'" "'.$caller.'"';
        }

        return @$this->put($file, $log.PHP_EOL, 'ab');
    }
}
