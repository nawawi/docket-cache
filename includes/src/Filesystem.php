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
    /**
     * is_docketcachedir.
     */
    public function is_docketcachedir($dir)
    {
        $name = 'docket-cache';
        $ok = false;

        if (false === strpos($dir.'/', '/'.$name.'/')) {
            return $ok;
        }

        $dir = array_reverse(explode('/', trim($dir, '/')));

        // depth = 2
        foreach ($dir as $n => $c) {
            if ($n <= 2 && 0 === strcmp($name, $c)) {
                $ok = true;
                break;
            }
        }

        return $ok;
    }

    /**
     * is_dirempty.
     */
    public function is_dirempty($dir)
    {
        foreach (new \DirectoryIterator($dir) as $object) {
            if ($object->isDot()) {
                continue;
            }

            return false;
        }

        return true;
    }

    /**
     * filesize.
     */
    public function filesize($file)
    {
        if (!@is_file($file)) {
            return 0;
        }

        return sprintf('%u', @filesize($file));
    }

    /**
     * chmod.
     */
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

    /**
     * copy.
     */
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

    /**
     * scanfiles.
     */
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

    /**
     * export_var.
     */
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
                $this->log('err', 'internalproc-internalfunc', 'export_var: '.$error);

                return false;
            }
        }

        // alias: shorter name
        // map it in includes/compat.php
        $data = str_replace(
            '\Nawawi\Symfony\Component\VarExporter\Internal\\',
            '\Nawawi\DocketCache\Exporter\\',
            $data
        );

        return $data;
    }

    /**
     * unlink.
     */
    public function unlink($file, $is_delete = false, $is_block = false)
    {
        // skip if not exist
        if (!@is_file($file)) {
            return true;
        }

        $ok = false;

        $handle = @fopen($file, 'cb');
        if ($handle) {
            $lock = $is_block ? LOCK_EX : LOCK_EX | LOCK_NB;
            if (@flock($handle, $lock)) {
                $ok = @ftruncate($handle, 0);
                @flock($handle, LOCK_UN);
            }
            @fclose($handle);
        }

        // bcoz we empty the file
        $this->opcache_flush($file);

        $do_delete = (nwdcx_construe('FLUSH_DELETE') && $this->is_php($file)) || $is_delete;

        if ($do_delete && @unlink($file)) {
            $ok = true;
        }

        clearstatcache();

        if (false === $ok) {
            // if failed, try to remove on shutdown instead of truncate
            add_action(
                'shutdown',
                function () use ($file) {
                    if (@is_file($file)) {
                        @unlink($file);
                    }
                }
            );
        }

        // always true
        return true;
    }

    /**
     * put.
     */
    public function put($file, $data, $flag = 'cb', $is_block = false)
    {
        if (!$handle = @fopen($file, $flag)) {
            return false;
        }

        $lock = $is_block ? LOCK_EX : LOCK_EX | LOCK_NB;
        $ok = false;
        if (@flock($handle, $lock)) {
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
            $this->unlink($file, false);

            return -1;
        }

        $this->opcache_flush($file);
        $this->chmod($file);

        return $ok;
    }

    /**
     * dump.
     */
    public function dump($file, $data)
    {
        $dir = \dirname($file);
        $tmpfile = $dir.'/'.'dump_'.uniqid().'_'.basename($file);
        add_action(
            'shutdown',
            function () use ($tmpfile) {
                if (@is_file($tmpfile)) {
                    @unlink($tmpfile);
                }
            },
            PHP_INT_MAX
        );

        $this->opcache_flush($file);

        $ok = $this->put($tmpfile, $data, 'cb', true);
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

    /**
     * placeholder.
     */
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

    /**
     * is_php.
     */
    public function is_php($file)
    {
        $file = basename($file);

        return '.php' === substr($file, -4);
    }

    /**
     * is_opcache_enable.
     */
    public function is_opcache_enable()
    {
        return @ini_get('opcache.enable') && \function_exists('opcache_reset');
    }

    /**
     * opcache_is_cached.
     */
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

    /**
     * opcache_flush.
     */
    public function opcache_flush($file)
    {
        if (!$this->is_opcache_enable()) {
            return false;
        }

        // wp 5.5
        if (\function_exists('wp_opcache_invalidate')) {
            return @wp_opcache_invalidate($file, true);
        }

        if (\function_exists('opcache_invalidate') && $this->is_php($file) && @is_file($file)) {
            return @opcache_invalidate($file, true);
        }

        return false;
    }

    /**
     * opcache_compile.
     */
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

    /**
     * opcache_reset.
     */
    public function opcache_reset($dir)
    {
        if (!$this->is_opcache_enable()) {
            return false;
        }

        if (!@opcache_reset()) {
            return false;
        }

        $dir = realpath($dir);
        if (false !== $dir && @is_dir($dir) && @is_writable($dir) && $this->is_docketcachedir($dir)) {
            foreach ($this->scanfiles($dir) as $object) {
                $fx = $object->getPathName();
                if (!$object->isFile() || 'file' !== $object->getType() || !$this->is_php($fx)) {
                    continue;
                }

                $this->opcache_flush($fx);
            }
        }

        $opcache_status = opcache_get_status();
        if (!empty($opcache_status) && \is_array($opcache_status) && !empty($opcache_status['scripts'])) {
            foreach ($opcache_status['scripts'] as $key => $data) {
                $fx = $data['full_path'];
                $this->opcache_flush($fx);
            }
        }

        // always true
        return true;
    }

    /**
     * define_cache_path.
     */
    public function define_cache_path($cache_path)
    {
        $cache_path = !empty($cache_path) && @is_dir($cache_path) && '/' !== $cache_path ? rtrim($cache_path, '/\\').'/' : WP_CONTENT_DIR.'/cache/docket-cache/';
        if (!$this->is_docketcachedir($cache_path)) {
            $cache_path = rtim($cache_path, '/').'docket-cache/';
        }

        return $cache_path;
    }

    /**
     * cachedir_flush.
     */
    public function cachedir_flush($dir, $cleanup = false)
    {
        wp_suspend_cache_addition(true);

        clearstatcache();
        $cnt = 0;
        $dir = realpath($dir);
        if (false === $dir || !@is_dir($dir) || !@is_writable($dir) || !$this->is_docketcachedir($dir)) {
            return false;
        }

        if ($this->is_dirempty($dir)) {
            return true;
        }

        $flush_lock = WP_CONTENT_DIR.'/.object-cache-flush.txt';
        if ($this->put($flush_lock, time())) {
            @touch($flush_lock, time() + 120);
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

        wp_suspend_cache_addition(false);

        if (@is_file($flush_lock)) {
            @unlink($flush_lock);
        }

        return $cnt;
    }

    /**
     * cache_size.
     */
    public function cache_size($dir)
    {
        $bytestotal = 0;
        $fsizetotal = 0;
        $filestotal = 0;

        if ($this->is_docketcachedir($dir)) {
            // hardmax
            $maxfile = 300000;
            $cnt = 0;

            foreach ($this->scanfiles($dir) as $object) {
                $fx = $object->getPathName();

                if (!$object->isFile() || 'file' !== $object->getType() || !$this->is_php($fx)) {
                    continue;
                }

                if ($cnt >= $maxfile) {
                    $this->unlink($fx, true);
                    continue;
                }

                $fs = $object->getSize();

                if (0 === $fs) {
                    $this->unlink($fx, true);
                    continue;
                }

                $data = $this->cache_get($object->getPathName());
                if (false !== $data) {
                    $bytestotal += \strlen(serialize($data));
                    ++$filestotal;
                }
                unset($data);

                $fsizetotal += $fs;

                ++$cnt;
            }
        }

        clearstatcache();

        return [
            'time' => time(),
            'size' => $bytestotal,
            'filesize' => $fsizetotal,
            'files' => $filestotal,
        ];
    }

    /**
     * cache_get.
     */
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
            try {
                $data = @include $file;
            } catch (\Exception $e) {
                $error = $e->getMessage();
                if (false !== strpos($error, 'not found') && @preg_match('@Class.*not found@', $error)) {
                    $this->unlink($file, false);
                }

                $this->log('err', 'internalproc-internalfunc', 'cache_get: '.$error);
                $data = false;
            }

            @flock($handle, LOCK_UN);
        }
        @fclose($handle);

        if (empty($data) || !isset($data['data'])) {
            return false;
        }

        return $data;
    }

    /**
     * code_stub.
     */
    public function code_stub($data = '')
    {
        $is_debug = \defined('WP_DEBUG') && WP_DEBUG;
        $ucode = '';
        if (!empty($data) && false !== strpos($data, 'Registry::p(')) {
            if (@preg_match_all('@Registry::p\(\'([a-zA-Z_]+)\'\)@', $data, $mm)) {
                if (!empty($mm) && isset($mm[1]) && \is_array($mm[1])) {
                    $cls = $mm[1];
                    foreach ($cls as $clsname) {
                        if ('stdClass' !== $clsname) {
                            if ($is_debug) {
                                $reflector = new \ReflectionClass($clsname);
                                $clsfname = $reflector->getFileName();
                                if (false !== $clsfname) {
                                    $ucode .= '/* f: '.str_replace(ABSPATH, '', $clsfname).' */'.PHP_EOL;
                                }
                            }
                            $ucode .= "if ( !@class_exists('".$clsname."', false) ) { return false; }".PHP_EOL;
                        }
                    }
                    unset($cls, $clsname);
                }
                unset($mm);
            }
        }

        $code = '<?php ';
        $code .= "if ( !\defined('ABSPATH') ) { return false; }".PHP_EOL;
        if (!empty($data)) {
            if (!empty($ucode)) {
                $code .= $ucode;
            }
            $code .= 'return '.$data.';'.PHP_EOL;
        }

        return $code;
    }

    /**
     * log.
     */
    public function log($tag, $id, $data, $caller = '')
    {
        $do_flush = false;
        $file = nwdcx_constval('LOG_FILE');
        if (empty($file)) {
            return false;
        }

        $logsize = nwdcx_constval('LOG_SIZE');
        if (empty($logsize) || !\is_int($logsize)) {
            $logsize = 0;
        }

        if (is_multisite()) {
            $file = nwdcx_network_filepath($file);
        }

        if (@is_file($file)) {
            if (nwdcx_construe('LOG_FLUSH') && 'flush' === $tag || ($logsize > 0 && $this->filesize($file) >= $logsize)) {
                $do_flush = true;
            }
        }

        $timestamp = date('Y-m-d H:i:s T');

        $rtag = trim($tag);
        if (\in_array($rtag, ['hit', 'miss', 'err', 'exp', 'del', 'info'])) {
            $tag = str_pad($rtag, 5);
        }
        $log = '['.$timestamp.'] '.$tag.': "'.$id.'" "'.trim($data).'" "'.$caller.'"';

        $flags = !$do_flush ? LOCK_EX | FILE_APPEND : LOCK_EX;
        $do_chmod = !@is_file($file);
        if (@file_put_contents($file, $log.PHP_EOL, $flags)) {
            if ($do_chmod) {
                $this->chmod($file);
            }

            return true;
        }

        return false;
    }

    /**
     * internal_group.
     */
    public function internal_group($group)
    {
        return 'docketcache' === substr($group, 0, 11);
    }

    /**
     * sanitize_second.
     */
    public function sanitize_second($time)
    {
        $time = (int) $time;
        if ($time < 0) {
            $time = 0;
        } else {
            $max = ceil(log10($time));
            if ($max > 10 || 'NaN' === $max) {
                $time = 0;
            }
        }

        return $time;
    }

    /**
     * valid_timestamp.
     */
    public function valid_timestamp($timestamp)
    {
        $timestamp = $this->sanitize_second($timestamp);

        return $timestamp > 0;
    }
}
