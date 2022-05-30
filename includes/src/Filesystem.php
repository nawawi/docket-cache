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
     * is_request_from_theme_editor.
     */
    public function is_request_from_theme_editor()
    {
        if (!empty($_POST)) {
            if (!empty($_POST['_wp_http_referer'])) {
                $wp_referer = $_POST['_wp_http_referer'];
                if ((false !== strpos($wp_referer, '/theme-editor.php?file=') || false !== strpos($wp_referer, '/plugin-editor.php?file=')) && (!empty($_POST['newcontent']) && false !== strpos($_POST['newcontent'], '<?php'))) {
                    return true;
                }
            }

            if (!empty($_POST['action']) && 'heartbeat' === $_POST['action'] && !empty($_POST['screen_id']) && ('theme-editor' === $_POST['screen_id'] || 'plugin-editor' === $_POST['screen_id'])) {
                return true;
            }
        }

        if (!empty($_GET) && !empty($_GET['wp_scrape_key']) && !empty($_GET['wp_scrape_nonce'])) {
            return true;
        }

        return false;
    }

    /**
     * fastcgi_close.
     */
    public function fastcgi_close()
    {
        if (\function_exists('fastcgi_finish_request') && !$this->is_request_from_theme_editor()) {
            return @fastcgi_finish_request();
        }

        return false;
    }

    /**
     * close_buffer.
     */
    public function close_buffer()
    {
        if (!@ob_get_level() && $this->fastcgi_close()) {
            return true;
        }

        return false;
    }

    /**
     * is_docketcachedir.
     */
    public function is_docketcachedir($path)
    {
        static $cached = [];

        if (!empty($cached[$path])) {
            return true;
        }

        $name = 'docket-cache';
        $ok = false;

        $dir = nwdcx_normalizepath($path);

        if (false === strpos($dir.'/', '/'.$name.'/')) {
            return $ok;
        }

        $dir = array_reverse(explode('/', trim($dir, '/')));

        // 28042022: new cache directory.
        // depth = 3: cache/docket-cache/93/b2/8a/
        // depth = 4: cache/docket-cache/network-1/93/b2/8a/
        $maxdepth = 4;
        foreach ($dir as $n => $c) {
            if ($n <= $maxdepth && 0 === strcmp($name, $c)) {
                $ok = true;
                $cached[$path] = 1;
                break;
            }
        }

        return $ok;
    }

    /**
     * is_docketcachefile.
     */
    public function is_docketcachefile($file)
    {
        static $cached = [];

        if (!empty($cached[$file])) {
            return true;
        }

        if (false !== strpos($file, '/docket-cache/') && @preg_match('@^([a-z0-9]{12})\-([a-z0-9]{12})\.php$@', basename($file))) {
            $cached[$file] = 1;

            return true;
        }

        return false;
    }

    /**
     * is_docketcachegroup.
     */
    public function is_docketcachegroup($group)
    {
        return 'docketcache' === substr($group, 0, 11);
    }

    /**
     * is_transient.
     */
    public function is_transient($group)
    {
        return 'transient' === $group || 'site-transient' === $group;
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
     * get_max_execution_time.
     */
    public function get_max_execution_time()
    {
        $max_execution_time = (int) ini_get('max_execution_time');
        if ($max_execution_time > 10) {
            --$max_execution_time;
        }

        return $max_execution_time;
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
     * touch.
     */
    public function touch($file, $time = 0, $atime = 0)
    {
        if (0 == $time) {
            $time = time();
        }

        if (0 == $atime) {
            $atime = time();
        }

        $nwdcx_suppresserrors = nwdcx_suppresserrors(true);

        $ok = @touch($file, $time, $atime);

        // user:group not same -> Utime failed: Operation not permitted
        if (!$ok) {
            $e = error_get_last();
            if (!\is_array($e)) {
                nwdcx_throwable(__METHOD__, $e);
            }
        }

        // restore error level
        nwdcx_suppresserrors($nwdcx_suppresserrors);

        return $ok;
    }

    /**
     * getchmod.
     */
    public function getchmod($file)
    {
        return substr(decoct(@fileperms($file)), -3);
    }

    /**
     * chmod.
     */
    public function chmod($file, $mode = false)
    {
        if (!$mode) {
            if (@is_file($file) && \defined('FS_CHMOD_FILE')) {
                $mode = FS_CHMOD_FILE;
            } elseif (@is_dir($file) && \defined('FS_CHMOD_DIR')) {
                $mode = FS_CHMOD_DIR;
            } else {
                clearstatcache();
                $stat = @stat(\dirname($file));
                $mode = $stat['mode'] & 0007777;

                if (@is_file($file)) {
                    $mode = $mode & 0000666;
                }
            }
        }

        clearstatcache();

        $nwdcx_suppresserrors = nwdcx_suppresserrors(true);

        $ok = @chmod($file, $mode);

        nwdcx_suppresserrors($nwdcx_suppresserrors);

        return $ok;
    }

    /**
     * mkdir.
     */
    public function mkdir_p($path)
    {
        $parent = \dirname($path);
        $okperms = [
            '777',
            '775',
            '755',
        ];

        if (@is_dir($path) && \in_array($this->getchmod($path), $okperms) && \in_array($this->getchmod($parent), $okperms)) {
            return true;
        }

        $nwdcx_suppresserrors = nwdcx_suppresserrors(true);

        if (\function_exists('wp_mkdir_p')) {
            $ok = @wp_mkdir_p($path);
        } else {
            $stat = @stat($parent);
            if ($stat) {
                $dir_perms = $stat['mode'] & 0007777;
            } else {
                $dir_perms = 0777;
            }

            $ok = @mkdir($path, $dir_perms, true);
        }

        nwdcx_suppresserrors($nwdcx_suppresserrors);

        if (!$ok) {
            return false;
        }

        if (!\in_array($this->getchmod($parent), $okperms)) {
            $this->chmod($parent, 0755);
        }

        if (!\in_array($this->getchmod($path), $okperms)) {
            $this->chmod($path, 0755);
        }

        return true;
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
    public function scanfiles($dir, $maxdepth = null, $pattern = false)
    {
        $dir = nwdcx_normalizepath(realpath($dir));
        if (false === $dir || !is_dir($dir) || !is_readable($dir)) {
            return [];
        }

        if (null === $maxdepth) {
            $maxdepth = 13;
        }

        if (empty($pattern)) {
            $pattern = '@^(dump_)?([a-z0-9_]+)\-([a-z0-9]+).*\.php$@';
        }

        $rec_dir_iterator_flags = \FilesystemIterator::SKIP_DOTS | \RecursiveDirectoryIterator::KEY_AS_FILENAME | \RecursiveDirectoryIterator::CURRENT_AS_FILEINFO;
        $rec_dir_iterator = new \RecursiveDirectoryIterator($dir, $rec_dir_iterator_flags);
        $reg_ite_iterator = new \RecursiveIteratorIterator($rec_dir_iterator);
        $reg_ite_pattern = $pattern;
        $reg_ite_mode = \RegexIterator::MATCH;
        $reg_ite_flags = \RegexIterator::USE_KEY;
        $reg_iterator = new \RegexIterator($reg_ite_iterator, $reg_ite_pattern, $reg_ite_mode, $reg_ite_flags);
        $reg_iterator->setMaxDepth($maxdepth);

        return $reg_iterator;
    }

    /**
     * validate_file.
     */
    public function validate_file($filename)
    {
        try {
            $fileo = new \SplFileObject($filename, 'rb');
        } catch (\Throwable $e) {
            nwdcx_throwable(__METHOD__, $e);

            return false;
        }

        if ($fileo->flock(\LOCK_EX)) {
            $fileo->seek(\PHP_INT_MAX);
            $lines = $fileo->key();
            $object = new \LimitIterator($fileo, $lines - 2);
            foreach ($object as $line) {
                if (false !== strpos($line, '/*@DOCKET_CACHE_EOF*/')) {
                    $fileo->flock(\LOCK_UN);

                    return true;
                }
            }
            $fileo->flock(\LOCK_UN);
        }

        $fileo = null;

        return false;
    }

    /**
     * export_var.
     */
    public function export_var($data, &$error = '')
    {
        try {
            $data = VarExporter::export($data);
        } catch (\Throwable $e) {
            nwdcx_throwable(__METHOD__, $e);
            $error = $e->getMessage();

            if (false !== strpos($error, 'Cannot export value of type "stdClass"')) {
                $data = var_export($data, 1);
                $data = str_replace('stdClass::__set_state', '(object)', $data);
            } else {
                $this->log('err', '000000000000-000000000000', 'export_var: '.$error);

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
     * shutdown_cleanup.
     */
    public function shutdown_cleanup($file, $seq = 10)
    {
        // dont use register_shutdown_function to avoid issue with page cache plugin
        add_action(
            'shutdown',
            function () use ($file) {
                if (@is_file($file)) {
                    @unlink($file);
                }
            },
            $seq
        );
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
            $lock = $is_block ? \LOCK_EX : \LOCK_EX | \LOCK_NB;
            if (@flock($handle, $lock)) {
                $ok = @ftruncate($handle, 0); // true, false
                @flock($handle, \LOCK_UN);
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

        // cleanup if ftruncate() failed
        if (false === $ok) {
            if (@is_file($file) && !@unlink($file)) {
                // try cleanup at shutdown
                $this->shutdown_cleanup($file);
            }
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

        $lock = $is_block ? \LOCK_EX : \LOCK_EX | \LOCK_NB;
        $ok = false;
        if (@flock($handle, $lock)) {
            $len = \strlen($data);
            $cnt = @fwrite($handle, $data);
            @fflush($handle);
            @flock($handle, \LOCK_UN);
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

    /**
     * dump.
     */
    public function dump($file, $data, $is_validate = false)
    {
        $dir = \dirname($file);
        $tmpfile = $dir.'/'.'dump_'.uniqid().'_'.basename($file);

        // cleanup at shutdown
        $this->shutdown_cleanup($tmpfile, \PHP_INT_MAX);

        // truncate reason
        $this->opcache_flush($file);

        $ok = $this->put($tmpfile, $data, 'cb', true);
        if (true === $ok) {
            try {
                clearstatcache();
                if (@is_file($tmpfile) && @rename($tmpfile, $file)) {
                    if (isset($nwdcx_suppresserrors)) {
                        nwdcx_suppresserrors($nwdcx_suppresserrors);
                    }

                    if ($is_validate && !$this->validate_file($file)) {
                        return false;
                    }

                    $this->chmod($file);

                    // compile
                    $this->opcache_compile($file);

                    return true;
                }
            } catch (\Throwable $e) {
                nwdcx_throwable(__METHOD__, $e);
            }

            // failed to replace
            $ok = false;
        }

        // cleanup if not bool true
        if (@is_file($tmpfile)) {
            @unlink($tmpfile);
        }

        // maybe -1, >= 1, false: return from put()
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

        $file = rtrim($path, '/\\').'/index.html';
        if (@is_file($file)) {
            return false;
        }

        $code = '<html><head><meta name="robots" content="noindex, nofollow"><title>Docket Cache</title></head>';
        $code .= '<body>Generated by <a href="https://wordpress.org/plugins/docket-cache/" rel="nofollow">Docket Cache</a></body></html>';

        return $this->put($file, $code);
    }

    /**
     * is_php.
     */
    public function is_php($file)
    {
        $file = basename($file);

        return '.php' === substr($file, -4);
    }

    public function is_function_disabled($name)
    {
        $disable_functions = @ini_get('disable_functions');
        if (!empty($disable_functions)) {
            $funcs = explode(',', $disable_functions);
            if (\in_array($name, $funcs)) {
                return true;
            }
        }

        return false;
    }

    public function get_chunk_path($group, $key)
    {
        $chunk = substr($group, 0, 2).substr($key, 0, 2).substr($key, -2);

        return chunk_split($chunk, 2, '/');
    }

    /**
     * remove cache file with previous structure.
     */
    public function remove_non_chunk_cache($cache_path, $file)
    {
        if (nwdcx_construe('CHUNKCACHEDIR')) {
            $filename = basename($file);
            $filepath = rtrim($cache_path, '\\/').'/'.$filename;
            if (is_file($filepath) && is_writable($filepath) && $this->is_docketcachefile($filepath)) {
                return @unlink($filepath);
            }
        }

        return false;
    }

    /**
     * opcache_function_exists.
     */
    public function opcache_function_exists($name = null)
    {
        $list = [
            'opcache_get_status',
            'opcache_reset',
            'opcache_compile_file',
            'opcache_invalidate',
            'opcache_is_script_cached',
            'opcache_get_configuration',
        ];

        if (!empty($name)) {
            return \in_array($name, $list) && !$this->is_function_disabled($name) && \function_exists($name);
        }

        foreach ($list as $func) {
            if (!$this->is_function_disabled($name) && \function_exists($func)) {
                return true;
            }
        }

        return false;
    }

    /**
     * is_opcache_enable.
     */
    public function is_opcache_enable()
    {
        if (@ini_get('opcache.enable')) {
            if (\function_exists('extension_loaded') && \extension_loaded('Zend OPcache')) {
                return true;
            }

            if (\function_exists('get_loaded_extensions')) {
                $arr = get_loaded_extensions(true);
                if (\is_array($arr) && \in_array('Zend OPcache', $arr)) {
                    return true;
                }
            }

            if ($this->opcache_function_exists()) {
                return true;
            }
        }

        return false;
    }

    /**
     * is_opcache_blacklisted.
     */
    public function is_opcache_blacklisted()
    {
        $conf = ini_get('opcache.blacklist_filename');
        if (empty($conf)) {
            return false;
        }

        $files = glob($conf);
        if (empty($files) || !\is_array($files)) {
            return false;
        }

        $abspath = rtrim(ABSPATH, '\\/');
        foreach ($files as $file) {
            if (!is_file($file) || !is_readable($file)) {
                continue;
            }
            $buff = file($file, \FILE_SKIP_EMPTY_LINES | \FILE_IGNORE_NEW_LINES);
            if (!empty($buff) && \is_array($buff)) {
                foreach ($buff as $line) {
                    if (empty($line) || ';' === substr($line, 0, 1)) {
                        continue;
                    }

                    if ($abspath === substr(rtrim($line, '\\/'), 0, \strlen($abspath))) {
                        return true;
                    }
                }
            }
            unset($buff);
        }

        return false;
    }

    /**
     * is_opcache_filecache_only.
     */
    public function is_opcache_filecache_only()
    {
        return @ini_get('opcache.file_cache_only');
    }

    /**
     * opcache_filecache_only.
     */
    public function opcache_filecache_only()
    {
        if ($this->is_opcache_filecache_only() && \function_exists('opcache_get_status')) {
            $data = @opcache_get_status();
            if (!empty($data) && \is_array($data) && !empty($data['file_cache_only']) && !empty($data['file_cache']) && is_dir($data['file_cache'])) {
                return $data;
            }
        }

        return false;
    }

    /**
     * opcache_filecache_scanfiles.
     */
    public function opcache_filecache_scanfiles($dir)
    {
        return $this->scanfiles($dir, -1, '@.*\.php\.bin$@');
    }

    /**
     * opcache_filecache_flush.
     */
    public function opcache_filecache_flush($file)
    {
        if (!($fcdata = $this->opcache_filecache_only()) || false === strpos(nwdcx_normalizepath($file), nwdcx_normalizepath(ABSPATH))) {
            return false;
        }

        $filem = nwdcx_normalizepath(rtrim($fcdata['file_cache'], '/\\').'/*/'.ltrim($file, '/\\').'.bin');
        $match = glob($filem);
        if (!empty($match) && \is_array($match)) {
            foreach ($match as $fm) {
                if (is_file($fm) && is_writable($fm)) {
                    @unlink($fm);
                }
            }
        }

        return true;
    }

    /**
     * opcache_filecache_reset.
     */
    public function opcache_filecache_reset()
    {
        static $is_done = false;

        $fcdata = $this->opcache_filecache_only();
        if (!$is_done && !empty($fcdata)) {
            $dir = $fcdata['file_cache'];
            $cnt = 0;
            $max_execution_time = $this->get_max_execution_time();
            $slowdown = 0;
            foreach ($this->opcache_filecache_scanfiles($dir) as $object) {
                try {
                    if ($object->isFile()) {
                        $file = nwdcx_normalizepath($object->getPathName());
                        if (false !== strpos($file, nwdcx_normalizepath(ABSPATH)) && is_writable($file)) {
                            @unlink($file);
                            ++$cnt;
                        }
                    }
                } catch (\Throwable $e) {
                    nwdcx_throwable(__METHOD__, $e);
                }

                if ($slowdown > 10) {
                    $slowdown = 0;
                    usleep(5000);
                }

                ++$slowdown;

                if ($max_execution_time > 0 && \defined('WP_START_TIMESTAMP') && (microtime(true) - WP_START_TIMESTAMP) > $max_execution_time) {
                    break;
                }
            }

            $is_done = true;

            return $cnt;
        }

        return false;
    }

    /**
     * opcache_is_cached.
     */
    public function opcache_is_cached($file)
    {
        if (!$this->is_opcache_enable()) {
            return -1;
        }

        $fcdata = $this->opcache_filecache_only();
        if (!empty($fcdata) && false !== strpos(nwdcx_normalizepath($file), nwdcx_normalizepath(ABSPATH))) {
            $filem = nwdcx_normalizepath(rtrim($fcdata['file_cache'], '/\\').'/*/'.ltrim($file, '/\\').'.bin');
            $match = glob($filem);
            if (!empty($match) && \is_array($match)) {
                return true;
            }
        }

        if (\function_exists('opcache_is_script_cached')) {
            return @opcache_is_script_cached($file);
        }

        return -1;
    }

    /**
     * opcache_flush.
     */
    public function opcache_flush($file)
    {
        if (!$this->is_php($file) || !@is_file($file) || !$this->is_opcache_enable()) {
            return false;
        }

        if ($this->opcache_filecache_flush($file)) {
            return true;
        }

        // wp 5.5
        if (\function_exists('wp_opcache_invalidate')) {
            return @wp_opcache_invalidate($file, true);
        }

        if (\function_exists('opcache_invalidate')) {
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

        if (!empty($_GET['_wpnonce']) && !empty($_GET['action']) && !empty($_GET['page']) && 'docket-cache' === $_GET['page'] && 'docket-flush-opcache' === $_GET['action']) {
            return -1;
        }

        if (\function_exists('opcache_compile_file') && $this->is_php($file) && @is_file($file) && false === $this->opcache_is_cached($file)) {
            $this->touch($file, time() - 60);

            try {
                return @opcache_compile_file($file);
            } catch (\Throwable $e) {
                nwdcx_throwable(__METHOD__, $e);
            }
        }

        return false;
    }

    /**
     * opcache_reset.
     */
    public function opcache_reset()
    {
        if (!$this->is_opcache_enable()) {
            return false;
        }

        try {
            if (!@opcache_reset()) {
                return $this->opcache_filecache_reset();
            }
        } catch (\Throwable $e) {
            nwdcx_throwable(__METHOD__, $e);

            return false;
        }

        // always true
        return true;
    }

    /**
     * opcache_cleanup.
     */
    public function opcache_cleanup()
    {
        add_action(
            'shutdown',
            function () {
                // anything involve disk, don't go into background
                //$this->close_buffer();
                $this->opcache_reset();
            },
            \PHP_INT_MAX
        );
    }

    /**
     * define_cache_path.
     */
    public function define_cache_path($cache_path)
    {
        $content_path = \defined('DOCKET_CACHE_CONTENT_PATH') ? DOCKET_CACHE_CONTENT_PATH : WP_CONTENT_DIR;
        $content_path = nwdcx_normalizepath($content_path);

        $cache_path = !empty($cache_path) && '/' !== $cache_path ? rtrim($cache_path, '/\\').'/' : $content_path.'/cache/docket-cache/';
        $cache_path = nwdcx_normalizepath($cache_path);

        if (!$this->is_docketcachedir($cache_path)) {
            $cache_path = rtrim($cache_path, '/\\').'docket-cache/';
        }

        // create if not normal installation
        if (false === strpos($content_path, '/wp-content/')) {
            if (!@is_dir($cache_path)) {
                $this->mkdir_p($cache_path);
            }

            if (!@is_dir($content_path)) {
                $this->mkdir_p($content_path);
            }
        }

        return $cache_path;
    }

    /**
     * cachedir_flush.
     */
    public function cachedir_flush($dir, $cleanup = false, &$is_timeout = false)
    {
        static $is_done = false;

        if ($is_done) {
            return 0;
        }

        clearstatcache();

        $dir = nwdcx_normalizepath(realpath($dir));

        if (false === $dir || !@is_dir($dir) || !@is_writable($dir) || !$this->is_docketcachedir($dir)) {
            return false;
        }

        if ($this->is_dirempty($dir)) {
            return true;
        }

        wp_suspend_cache_addition(true);

        $max_execution_time = $this->get_max_execution_time();
        $flush_lock = DOCKET_CACHE_CONTENT_PATH.'/.object-cache-flush.txt';
        if ($this->put($flush_lock, time())) {
            $this->touch($flush_lock, time() + $max_execution_time);
        }

        $slowdown = 0;
        $cnt = 0;

        foreach ($this->scanfiles($dir) as $object) {
            try {
                if ($object->isFile()) {
                    $this->unlink($object->getPathName(), $cleanup ? true : false);
                    ++$cnt;
                }
            } catch (\Throwable $e) {
                nwdcx_throwable(__METHOD__, $e);
            }

            if ($slowdown > 10) {
                $slowdown = 0;
                usleep(5000);
            }

            ++$slowdown;
            if ($max_execution_time > 0 && \defined('WP_START_TIMESTAMP') && (microtime(true) - WP_START_TIMESTAMP) > $max_execution_time) {
                $is_timeout = true;
                break;
            }
        }

        if ($cleanup) {
            // 24122020: deprecate
            $this->unlink($dir.'/index.php', true);

            // placeholder
            $this->unlink($dir.'/index.html', true);
        }

        if (@is_file($flush_lock)) {
            @unlink($flush_lock);
        }

        wp_suspend_cache_addition(false);
        $is_done = true;

        return $cnt;
    }

    /**
     * cache_size.
     */
    public function cache_size($dir)
    {
        static $is_done = false;

        $bytestotal = 0;
        $fsizetotal = 0;
        $filestotal = 0;

        clearstatcache();
        if (!$is_done && $this->is_docketcachedir($dir) && !@is_file(DOCKET_CACHE_CONTENT_PATH.'/.object-cache-flush.txt')) {
            // hardmax
            $maxfile = 999000; // 1000000 - 1000;
            $cnt = 0;
            $slowdown = 0;

            ignore_user_abort(true);
            $max_execution_time = $this->get_max_execution_time();

            $pattern = '@^([a-z0-9]{12})\-([a-z0-9]{12})\.php$@';
            foreach ($this->scanfiles($dir, null, $pattern) as $object) {
                try {
                    if ($object->isFile()) {
                        $fx = $object->getPathName();

                        if (!$this->remove_non_chunk_cache($dir, $fx)) {
                            $fs = $object->getSize();

                            if ($cnt >= $maxfile) {
                                $this->unlink($fx, true);
                            } elseif (0 === $fs) {
                                $this->unlink($fx, true);
                            } else {
                                $data = $this->cache_get($fx);
                                if (false === $data) {
                                    $this->unlink($fx, true);
                                } else {
                                    $bytestotal += \strlen(serialize($data));
                                    unset($data);

                                    $fsizetotal += $fs;

                                    ++$filestotal;
                                    ++$cnt;
                                }
                            }
                        }
                    }
                } catch (\Throwable $e) {
                    nwdcx_throwable(__METHOD__, $e);
                }

                if ($slowdown > 10) {
                    $slowdown = 0;
                    usleep(5000);
                }

                ++$slowdown;

                if ($max_execution_time > 0 && \defined('WP_START_TIMESTAMP') && (microtime(true) - WP_START_TIMESTAMP) > $max_execution_time) {
                    break;
                }
            }
        }

        $is_done = true;

        wp_cache_set('numfile', $filestotal, 'docketcache-gc', 60);

        return [
            'timestamp' => time(),
            'size' => $bytestotal,
            'filesize' => $fsizetotal,
            'files' => $filestotal,
        ];
    }

    public function get_fatal_error_filename($file)
    {
        // sesimple yang mungkin.
        return $file.'-error.txt';
    }

    public function has_fatal_error_before($file)
    {
        $file_fatal = $this->get_fatal_error_filename($file);

        return @is_file($file_fatal);
    }

    public function validate_fatal_error_file($file)
    {
        $file_fatal = $this->get_fatal_error_filename($file);
        if (!@is_file($file_fatal)) {
            return;
        }

        if (!@is_file($file)) {
            @unlink($file_fatal);

            return;
        }

        $fm = time() - 10; // 10s
        if ($fm > @filemtime($file_fatal)) {
            if ($this->validate_file($file)) {
                @unlink($file_fatal);

                return;
            }

            // update timestamp
            $this->touch($file_fatal);
        }
    }

    private function suspend_cache_file($file, $error, $seconds = 0)
    {
        $seconds = (int) $seconds;
        $file_fatal = $this->get_fatal_error_filename($file);

        $errmsg = date('Y-m-d H:i:s T').\PHP_EOL.$error;
        if ($this->dump($file_fatal, $errmsg)) {
            if ($seconds > 0) {
                $this->touch($file, time() + $seconds);
            }

            $this->dump(\dirname($file_fatal).'/last-error.txt', $errmsg);

            return true;
        }

        return false;
    }

    private function capture_fatal_error()
    {
        register_shutdown_function(
            function () {
                $error = error_get_last();
                if ($error && \in_array($error['type'], [\E_ERROR, \E_PARSE, \E_COMPILE_ERROR, \E_USER_ERROR, \E_RECOVERABLE_ERROR, \E_CORE_ERROR], true)) {
                    $file_error = $error['file'];

                    if ($this->is_docketcachefile($file_error)) {
                        $this->dump($file_error, '<?php return false;');

                        $error['file'] = basename($error['file']);
                        // 300s = 5m delay
                        if ($this->suspend_cache_file($file_error, $this->export_var($error), 300)) {
                            // refresh page if possible
                            if ('cli' !== \PHP_SAPI && !wp_doing_ajax()) {
                                echo '<script>document.body.innerHTML="";window.setTimeout(function() { window.location.assign(window.location.href); }, 750);</script>';
                            }
                        }
                    }
                }
            }
        );
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

        if ($this->has_fatal_error_before($file)) {
            return false;
        }

        // capture non-throwable
        if (nwdcx_construe('CAPTURE_FATALERROR')) {
            $this->capture_fatal_error();
        }

        // cache data
        $data = [];

        // include when we can read, try to avoid fatal error.
        // LOCK_SH = shared lock
        if (flock($handle, \LOCK_SH)) {
            try {
                $data = @include $file;
            } catch (\Throwable $e) {
                $error = $e->getMessage();

                $file_error = $e->getFile();
                if ($this->is_docketcachefile($file_error)) {
                    $errmsg = 'E: '.$error.\PHP_EOL;
                    $errmsg .= 'L: '.$e->getLine().\PHP_EOL;
                    $errmsg .= 'F: '.basename($file_error).\PHP_EOL;
                    $this->suspend_cache_file($file, $errmsg);
                }

                $this->log('err', '000000000000-000000000000', 'cache_get: '.$error);
                $data = false;
            }

            @flock($handle, \LOCK_UN);
        }
        @fclose($handle);

        if (empty($data) || !isset($data['data'])) {
            return false;
        }

        // 15052022
        /*if (false === $this->opcache_is_cached($file)) {
            $this->opcache_compile($file);
        }*/

        return $data;
    }

    /**
     * code_stub.
     */
    public function code_stub($data = '')
    {
        $is_debug = \defined('WP_DEBUG') && WP_DEBUG;
        $is_data = !empty($data);
        $ucode = '';
        if ($is_data && false !== strpos($data, 'Registry::p(')) {
            if (@preg_match_all('@Registry::p\(\'([a-zA-Z_]+)\'\)@', $data, $mm)) {
                if (!empty($mm) && isset($mm[1]) && \is_array($mm[1])) {
                    $cls = $mm[1];
                    foreach ($cls as $clsname) {
                        if ('stdClass' !== $clsname) {
                            if ($is_debug) {
                                $reflector = new \ReflectionClass($clsname);
                                $clsfname = $reflector->getFileName();
                                if (false !== $clsfname) {
                                    $ucode .= '/* f: '.str_replace(ABSPATH, '', $clsfname).' */'.\PHP_EOL;
                                }
                            }
                            $ucode .= "if ( !@class_exists('".$clsname."', false) ) { return false; }".\PHP_EOL;
                        }
                    }
                    unset($cls, $clsname);
                }
                unset($mm);
            }
        }

        $code = '<?php ';
        $code .= "if ( !\defined('ABSPATH') ) { return false; }";
        if ($is_data) {
            $code .= \PHP_EOL;
            if (!empty($ucode)) {
                $code .= $ucode;
            }
            $code .= 'return '.$data.';'.\PHP_EOL;
            $code .= '/*@DOCKET_CACHE_EOF*/';
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
        if (\in_array($rtag, ['hit', 'miss', 'err', 'exp', 'del', 'info', 'dev'])) {
            $tag = str_pad($rtag, 5);
        }
        $log = '['.$timestamp.'] '.$tag.': "'.$id.'" "'.trim($data).'" "'.$caller.'"';

        $flags = !$do_flush ? \LOCK_EX | \FILE_APPEND : \LOCK_EX;
        $do_chmod = !@is_file($file);
        if (@file_put_contents($file, $log.\PHP_EOL, $flags)) {
            if ($do_chmod) {
                $this->chmod($file);
            }

            return true;
        }

        return false;
    }

    /**
     * sanitize_timestamp.
     */
    public function sanitize_timestamp($time)
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
     * sanitize_maxttl.
     */
    public function sanitize_maxttl($seconds)
    {
        $seconds = $this->sanitize_timestamp($seconds);

        // 86400 = 1d
        // 345600 = 4d
        // 2419200 = 28d
        if ($seconds < 86400) {
            $seconds = 345600;
        } elseif ($seconds > 2419200) {
            $seconds = 2419200;
        }

        return $seconds;
    }

    /**
     * sanitize_maxfile.
     */
    public function sanitize_maxfile($maxfile, $default = 50000)
    {
        $maxfile = (int) $maxfile;
        $min = 200;
        $max = 1000000;
        if (empty($maxfile)) {
            $maxfile = $default;
        }

        if ($maxfile < $min) {
            $maxfile = $default;
        } elseif ($maxfile > $max) {
            $maxfile = $max;
        }

        return $maxfile;
    }

    /**
     * sanitize_precache_maxfile.
     */
    public function sanitize_precache_maxfile($maxfile)
    {
        if (empty($maxfile) || (int) $maxfile < 1) {
            return 0;
        }

        return $this->sanitize_maxfile($maxfile);
    }

    /**
     * sanitize_maxsize.
     */
    public function sanitize_maxsize($bytes)
    {
        $min = 1048576; // 1M
        $max = 10485760; // 10M
        $bytes = (int) $bytes;

        if ($bytes < $min) {
            return 3145728; // 3M
        }

        if ($bytes > $max) {
            $bytes = $max;
        }

        return $bytes;
    }

    /**
     * sanitize_maxsizedisk.
     */
    public function sanitize_maxsizedisk($bytes)
    {
        if (empty($bytes) || !\is_int($bytes)) {
            $maxsizedisk = 524288000; // 500MB
        }

        if ($bytes < 104857600) {
            $bytes = 104857600;
        }

        return $bytes;
    }

    /**
     * valid_timestamp.
     */
    public function valid_timestamp($timestamp)
    {
        $timestamp = $this->sanitize_timestamp($timestamp);

        return $timestamp > 0;
    }

    public function keys_alloptions()
    {
        // reference: wp-admin/includes/schema.php
        // exclude: rewrite_rules, active_plugins, uninstall_plugins, cron
        // exclude: widget_categories, widget_text, widget_rss
        return [
            'siteurl' => 1,
            'home' => 1,
            'blogname' => 1,
            'blogdescription' => 1,
            'users_can_register' => 1,
            'admin_email' => 1,
            'start_of_week' => 1,
            'use_balanceTags' => 1,
            'use_smilies' => 1,
            'require_name_email' => 1,
            'comments_notify' => 1,
            'posts_per_rss' => 1,
            'rss_use_excerpt' => 1,
            'mailserver_url' => 1,
            'mailserver_login' => 1,
            'mailserver_pass' => 1,
            'mailserver_port' => 1,
            'default_category' => 1,
            'default_comment_status' => 1,
            'default_ping_status' => 1,
            'default_pingback_flag' => 1,
            'posts_per_page' => 1,
            'date_format' => 1,
            'time_format' => 1,
            'links_updated_date_format' => 1,
            'comment_moderation' => 1,
            'moderation_notify' => 1,
            'permalink_structure' => 1,
            'hack_file' => 1,
            'blog_charset' => 1,
            'category_base' => 1,
            'ping_sites' => 1,
            'comment_max_links' => 1,
            'gmt_offset' => 1,
            'default_email_category' => 1,
            'template' => 1,
            'stylesheet' => 1,
            'comment_registration' => 1,
            'html_type' => 1,
            'use_trackback' => 1,
            'default_role' => 1,
            'db_version' => 1,
            'uploads_use_yearmonth_folders' => 1,
            'upload_path' => 1,
            'blog_public' => 1,
            'default_link_category' => 1,
            'show_on_front' => 1,
            'tag_base' => 1,
            'show_avatars' => 1,
            'avatar_rating' => 1,
            'upload_url_path' => 1,
            'thumbnail_size_w' => 1,
            'thumbnail_size_h' => 1,
            'thumbnail_crop' => 1,
            'medium_size_w' => 1,
            'medium_size_h' => 1,
            'avatar_default' => 1,
            'large_size_w' => 1,
            'large_size_h' => 1,
            'image_default_link_type' => 1,
            'image_default_size' => 1,
            'image_default_align' => 1,
            'close_comments_for_old_posts' => 1,
            'close_comments_days_old' => 1,
            'thread_comments' => 1,
            'thread_comments_depth' => 1,
            'page_comments' => 1,
            'comments_per_page' => 1,
            'default_comments_page' => 1,
            'comment_order' => 1,
            'timezone_string' => 1,
            'page_for_posts' => 1,
            'page_on_front' => 1,
            'default_post_format' => 1,
            'link_manager_enabled' => 1,
            'finished_splitting_shared_terms' => 1,
            'site_icon' => 1,
            'medium_large_size_w' => 1,
            'medium_large_size_h' => 1,
            'wp_page_for_privacy_policy' => 1,
            'show_comments_cookies_opt_in' => 1,
            'admin_email_lifespan' => 1,
            'initial_db_version' => 1,
            'fresh_site' => 1,
            'current_theme' => 1,
            'theme_switched' => 1,
            'generate_update_core_typography' => 1,
            'WPLANG' => 1,
            'new_admin_email' => 1,
            'recovery_mode_email_last_sent' => 1,
            'comment_previously_approved' => 1,
            'finished_updating_comment_type' => 1,
            'db_upgraded' => 1,
            /* wp >= 5.6.0 */
            'auto_update_core_dev' => 1,
            'auto_update_core_minor' => 1,
            'auto_update_core_major' => 1,
            /* wp >= 5.7 */
            'https_detection_errors' => 1,
            /* wp >= 5.8 */
            'wp_force_deactivated_plugins' => 1,
        ];
    }

    /**
     * optimize_alloptions.
     */
    public function optimize_alloptions()
    {
        add_filter(
            'pre_cache_alloptions',
            function ($alloptions) {
                $wp_options = $this->keys_alloptions();

                foreach ($alloptions as $key => $value) {
                    // skip
                    if (empty($value)) {
                        continue;
                    }

                    if (!\array_key_exists($key, $wp_options)) {
                        unset($alloptions[$key]);
                    }
                }

                return $alloptions;
            },
            \PHP_INT_MIN
        );
    }
}
