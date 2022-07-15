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

final class Plugin extends Bepart
{
    /**
     * Plugin file.
     *
     * @var string
     */
    public $file;

    /**
     * Plugin slug.
     *
     * @var string
     */
    public $slug;

    /**
     * Plugin hook.
     *
     * @var string
     */
    public $hook;

    /**
     * Plugin path.
     *
     * @var string
     */
    public $path;

    /**
     * Plugin valid page uri.
     *
     * @var string
     */
    public $page;

    /**
     * Plugin action token.
     *
     * @var string
     */
    public $token;

    /**
     * Plugin action notice.
     *
     * @var string
     */
    public $notice;

    /**
     * Plugin screen name.
     *
     * @var string
     */
    public $screen;

    /**
     * The cache path.
     *
     * @var string
     */
    public $cache_path;

    /**
     * API Endpoint.
     *
     * @var string
     */
    public $api_endpoint;

    /**
     * Cronbot Endpoint.
     *
     * @var string
     */
    public $cronbot_endpoint;

    /**
     * WpConfig runtime notice.
     *
     * @var bool
     */
    public $inruntime = false;

    /**
     * constructor.
     */
    public function __construct()
    {
        $this->slug = 'docket-cache';
        $this->file = nwdcx_constval('FILE');
        $this->hook = plugin_basename($this->file);
        $this->path = realpath(plugin_dir_path($this->file));
        $this->register_init();
    }

    /**
     * Dropino().
     */
    public function cx()
    {
        static $inst;
        if (!\is_object($inst)) {
            $inst = new Dropino($this->path);
        }

        return $inst;
    }

    /**
     * Canopt().
     */
    public function co()
    {
        static $inst;
        if (!\is_object($inst)) {
            $inst = new Canopt();
        }

        return $inst;
    }

    /**
     * Constans().
     */
    public function cf()
    {
        static $inst;
        if (!\is_object($inst)) {
            $inst = new Constans();
        }

        return $inst;
    }

    /**
     * get_version.
     */
    public function version()
    {
        static $version = false;

        if (!empty($version)) {
            return $version;
        }

        $version = $this->plugin_meta($this->file)['Version'];

        return $version;
    }

    /**
     * get_info.
     */
    public function get_info()
    {
        $status_code = [
             0 => esc_html__('Disabled', 'docket-cache'),
             1 => esc_html__('Enabled', 'docket-cache'),
             2 => esc_html__('Not Available', 'docket-cache'),
             3 => esc_html__('Unknown', 'docket-cache'),
         ];

        $yesno = [
             0 => esc_html__('No', 'docket-cache'),
             1 => esc_html__('Yes', 'docket-cache'),
         ];

        $force_stats = $this->cf()->is_dctrue('WPCLI');
        $cache_stats = $this->get_cache_stats($force_stats);

        $status = $this->get_status();
        $status_text_stats = '';
        $status_text = '';

        switch ($status) {
             case 1:
                 if ($this->cf()->is_dctrue('STATS')) {
                     /* translators: %1$s = size, %2$s number of file */
                     $status_text_stats = sprintf(esc_html__(_n('%1$s object of %2$s file', '%1$s object of %2$s files', $cache_stats->files < 1 ? 1 : $cache_stats->files, 'docket-cache')), $this->normalize_size($cache_stats->size), $cache_stats->files);
                 }
                 $status_text = $status_code[1];
                 break;
             case 2:
                 $status_text = esc_html__('Disabled at runtime.', 'docket-cache');
                 break;
             default:
                 $status_text = $status_code[$status];
         }

        $opcache = $this->get_opcache_status();
        $opcache_text_stats = '';
        $opcache_text = '';

        $opcache_dc_stats = '';
        $opcache_wp_stats = '';
        switch ($opcache->status) {
             case 1:
                 /* translators: %1$s = size, %2$s number of file */
                 $opcache_text_stats = sprintf(esc_html__(_n('%1$s memory of %2$s file', '%1$s memory of %2$s files', $opcache->files, 'docket-cache')), $this->normalize_size($opcache->size), $opcache->files);

                 if ($opcache->dcfiles > 1) {
                     /* translators: %1$s = size, %2$s number of file */
                     $opcache_dc_stats = sprintf(esc_html__(_n('%1$s memory of %2$s file', '%1$s memory of %2$s files', $opcache->dcfiles, 'docket-cache')), $this->normalize_size($opcache->dcsize), $opcache->dcfiles);

                     /* translators: %1$s = size, %2$s number of file */
                     $opcache_wp_stats = sprintf(esc_html__(_n('%1$s memory of %2$s file', '%1$s memory of %2$s files', $opcache->wpfiles, 'docket-cache')), $this->normalize_size($opcache->wpsize), $opcache->wpfiles);
                 }
                 break;
             case 2:
                 $opcache_text = $status_code[1];
                 break;
             case 3:
                 $opcache_text = $status_code[1].' (<a href="https://www.php.net/manual/en/opcache.configuration.php#ini.opcache.file-cache-only" rel="noopener" target="new">'.esc_html__('File cache only', 'docket-cache').'</a>)';
                 break;
             case 4:
                 if ($opcache->files > 0) {
                     /* translators: %1$s = size, %2$s number of file */
                     $opcache_text_stats = sprintf(esc_html__(_n('%1$s size of %2$s file', '%1$s size of %2$s files', $opcache->files, 'docket-cache')), $this->normalize_size($opcache->size), $opcache->files);

                     if ($opcache->dcfiles > 1) {
                         /* translators: %1$s = size, %2$s number of file */
                         $opcache_dc_stats = sprintf(esc_html__(_n('%1$s size of %2$s file', '%1$s size of %2$s files', $opcache->dcfiles, 'docket-cache')), $this->normalize_size($opcache->dcsize), $opcache->dcfiles);

                         /* translators: %1$s = size, %2$s number of file */
                         $opcache_wp_stats = sprintf(esc_html__(_n('%1$s size of %2$s file', '%1$s size of %2$s files', $opcache->wpfiles, 'docket-cache')), $this->normalize_size($opcache->wpsize), $opcache->wpfiles);
                     }
                 } else {
                     $opcache_text = $status_code[1].' (<a href="https://www.php.net/manual/en/opcache.configuration.php#ini.opcache.file-cache-only" rel="noopener" target="new">'.esc_html__('File cache only', 'docket-cache').'</a>)';
                 }

                 break;
             default:
                 $opcache_text = $status_code[2];
         }

        $log_enable = $this->cf()->is_dctrue('LOG') ? 1 : 0;
        $log_file = $this->cf()->dcvalue('LOG_FILE');

        if (is_multisite()) {
            $log_file = nwdcx_network_filepath($log_file);
        }

        $multisite_text = $status_code[3];
        $multinets_lock = '';

        if (is_multisite()) {
            $netcount = get_networks(['count' => 1]);
            $this->get_network_sites($sitecount, true);

            /* translators: %s = sites */
            $multisite_text = sprintf(esc_html__(_n('%s Site', '%s Sites', $sitecount, 'docket-cache')), $sitecount);

            if ($netcount > 1) {
                /* translators: %s = networks */
                $multisite_text = $multisite_text.' '.sprintf(esc_html__(_n('of %s Network', 'of %s Networks', $netcount, 'docket-cache')), $netcount);

                $multinets_lock = $this->sanitize_rootpath($this->cx()->multinet_tag());
            }
        }

        $file_dropin = $this->cx()->resc()->dst;
        if (@is_file($file_dropin)) {
            $write_dropin = @is_writable($file_dropin);
        } else {
            $write_dropin = @is_writable($this->cx()->condir.'/');
        }

        $file_dropin_wp = $this->cx()->resc()->wpdst;
        $dropin_wp_exist = false;

        $is_dropin_alternative = $this->cx()->is_alternative();
        if ($is_dropin_alternative) {
            $dropin_wp_exist = @is_file($file_dropin_wp) || @is_link($file_dropin_wp);
        }

        $file_max = $this->get_cache_maxfile();
        $disk_max = $this->normalize_size($this->get_cache_maxsize_disk());

        $file_stats = $file_max;
        $disk_stats = $disk_max;

        if (isset($cache_stats->files) && !empty($cache_stats->files)) {
            $file_stats = $cache_stats->files.' / '.$file_max;
        }

        if (isset($cache_stats->filesize) && !empty($cache_stats->filesize)) {
            $disk_stats = $this->normalize_size($cache_stats->filesize).' / '.$disk_max;
        }

        return [
             'status_code' => $status,
             'status_text' => $status_text,
             'status_text_stats' => $status_text_stats,
             'opcache_code' => $opcache->status,
             'opcache_text' => $opcache_text,
             'opcache_text_stats' => $opcache_text_stats,
             'opcache_dc_stats' => $opcache_dc_stats,
             'opcache_wp_stats' => $opcache_wp_stats,
             'php_memory_limit' => $this->normalize_size(@ini_get('memory_limit')),
             'wp_memory_limit' => $this->normalize_size(WP_MEMORY_LIMIT),
             'wp_max_memory_limit' => $this->normalize_size(WP_MAX_MEMORY_LIMIT),
             'write_dropin' => $yesno[$write_dropin],
             'dropin_path' => $this->sanitize_rootpath($file_dropin),
             'dropin_isalt' => $is_dropin_alternative,
             'dropin_alt' => $yesno[$is_dropin_alternative],
             'dropin_wp' => $this->sanitize_rootpath($file_dropin_wp),
             'dropin_wp_isexist' => $dropin_wp_exist,
             'dropin_wp_exist' => $yesno[$dropin_wp_exist],
             'write_cache' => $yesno[is_writable($this->cache_path)],
             'cache_chunkdir' => $yesno[$this->cf()->dcvalue('CHUNKCACHEDIR')],
             'cache_size' => $this->normalize_size($cache_stats->size),
             'cache_path_real' => $this->cache_path,
             'cache_path' => $this->sanitize_rootpath($this->cache_path),
             'cache_maxfile' => $file_max,
             'cache_file_stats' => $file_stats,
             'cache_maxsize_disk' => $this->normalize_size($this->get_cache_maxsize_disk()),
             'cache_disk_stats' => $disk_stats,
             'log_file_real' => $log_file,
             'log_file' => $this->sanitize_rootpath($log_file),
             'log_enable' => $log_enable,
             'log_enable_text' => $status_code[$log_enable],
             'config_path' => $this->sanitize_rootpath($this->co()->path),
             'write_config' => $yesno[$this->co()->is_options_writable()],
             'wp_multisite' => $multisite_text,
             'wp_multinetlock' => $multinets_lock,
             'wp_multinetmain' => $yesno[is_main_network()],
         ];
    }

    /**
     * sanitize_rootpath.
     */
    public function sanitize_rootpath($path)
    {
        $wp_content_dir = wp_normalize_path(WP_CONTENT_DIR);
        $abspath = wp_normalize_path(ABSPATH);

        return rtrim(str_replace([$wp_content_dir, $abspath], ['/'.basename($wp_content_dir), '/'], $path), '/');
    }

    /**
     * get_status.
     */
    public function get_status()
    {
        if ($this->cf()->is_dctrue('DISABLED')) {
            return 2;
        }

        if (!$this->cx()->exists()) {
            return 0;
        }

        if ($this->cx()->validate() && $this->cx()->multinet_me()) {
            if ($this->cf()->is_dctrue('OBJECTCACHEOFF', true)) {
                $this->co()->save('objectcacheoff', 'default');
            }

            return 1;
        }

        if (!$this->cx()->multinet_me()) {
            return 0;
        }

        return 3;
    }

    /**
     * get_logsize.
     */
    public function get_logsize()
    {
        if ($this->has_log($logfile)) {
            return $this->normalize_size($this->filesize($logfile));
        }

        return 0;
    }

    /**
     * get_opcache_status.
     */
    public function get_opcache_status($is_raw = false)
    {
        $total_bytes = 0;
        $total_files = 0;
        $status = 0;

        $dcfiles = 0;
        $dcbytes = 0;

        $wpfiles = 0;
        $wpbytes = 0;

        $stale = 0;

        $data = [];
        if ($this->is_opcache_enable()) {
            if (!$this->opcache_function_exists('opcache_get_status')) {
                $status = 2;
            } else {
                $data = @opcache_get_status();
                if (!empty($data) && \is_array($data)) {
                    if (!empty($data['opcache_enabled'])) {
                        if ($is_raw) {
                            return $data;
                        }

                        $status = 1;

                        if (!empty($data['memory_usage']['used_memory'])) {
                            $total_bytes = $data['memory_usage']['used_memory'];
                        }
                        if (!empty($data['opcache_statistics']['num_cached_scripts'])) {
                            $total_files = $data['opcache_statistics']['num_cached_scripts'];
                        }

                        if (!empty($data['scripts']) && \is_array($data['scripts'])) {
                            foreach ($data['scripts'] as $script => $arr) {
                                $cpath = $arr['full_path'];
                                if (!@is_file($cpath)) {
                                    ++$stale;
                                }
                                $cfile = basename($cpath);
                                $cdir = \dirname($cpath);
                                if (false !== strpos($script, $this->cache_path) && $this->is_docketcachedir($cdir) && @preg_match('@^([a-z0-9]{12})\-([a-z0-9]{12})\.php$@', $cfile)) {
                                    ++$dcfiles;
                                    if (isset($arr['memory_consumption'])) {
                                        $dcbytes += $arr['memory_consumption'];
                                    }
                                } else {
                                    ++$wpfiles;
                                    if (isset($arr['memory_consumption'])) {
                                        $wpbytes += $arr['memory_consumption'];
                                    }
                                }
                            }
                        }
                    } elseif (!empty($data['file_cache_only'])) {
                        $status = 3;

                        if (!empty($data['file_cache']) && is_dir($data['file_cache']) && is_readable($data['file_cache'])) {
                            $dir = nwdcx_normalizepath(realpath($data['file_cache']));
                            $cnt = 0;

                            // dummy
                            $cdata = [
                                'opcache_statistics' => [
                                    'num_cached_scripts' => 0,
                                ],
                                'scripts' => [],
                            ];

                            foreach ($this->opcache_filecache_scanfiles($dir) as $object) {
                                try {
                                    if (!$object->isFile()) {
                                        continue;
                                    }

                                    $cpath = nwdcx_normalizepath($object->getPathName());
                                    if (false === strpos($cpath, nwdcx_normalizepath(ABSPATH))) {
                                        continue;
                                    }

                                    $cfile = basename($cpath);
                                    $cdir = \dirname($cpath);
                                    $fs = $object->getSize();

                                    if (false !== strpos($cpath, $this->cache_path) && $this->is_docketcachedir($cdir) && @preg_match('@^([a-z0-9]{12})\-([a-z0-9]{12})\.php\.bin$@', $cfile)) {
                                        ++$dcfiles;
                                        $dcbytes += $fs;
                                    } else {
                                        ++$wpfiles;
                                        $wpbytes += $fs;
                                    }

                                    ++$cnt;

                                    $total_bytes += $fs;

                                    $cdata['scripts'][$cpath] = [
                                        'full_path' => $cpath,
                                        'memory_consumption' => $fs,
                                        'last_used_timestamp' => $object->getATime(),
                                        'hits' => 1,
                                    ];
                                } catch (\Throwable $e) {
                                    nwdcx_throwable(__METHOD__, $e);
                                    continue;
                                }
                            }

                            if ($cnt > 0) {
                                $status = 4;
                                $total_files = $cnt;
                            }
                        }

                        if ($is_raw) {
                            $cdata['opcache_statistics']['num_cached_scripts'] = $cnt;
                            $data = array_merge($data, $cdata);
                            $data['_numfile'] = $cnt;
                            $data['_sizefile'] = $total_bytes;

                            return $data;
                        }
                    }
                }
            }
        }

        $arr = [
            'status' => (int) $status,
            'size' => $total_bytes,
            'files' => (int) $total_files,
            'wpfiles' => (int) $wpfiles,
            'wpsize' => (int) $wpbytes,
            'dcfiles' => (int) $dcfiles,
            'dcsize' => (int) $dcbytes,
            'stale' => (int) $stale,
        ];

        return (object) $arr;
    }

    /**
     * get_cache_maxfile.
     */
    public function get_cache_maxfile()
    {
        $maxfile = $this->cf()->dcvalue('MAXFILE');

        return $this->sanitize_maxfile($maxfile);
    }

    /**
     * get_cache_maxttl.
     */
    public function get_cache_maxttl()
    {
        $maxttl = $this->cf()->dcvalue('MAXTTL');

        return $this->sanitize_maxttl($maxttl);
    }

    /**
     * get_cache_maxsize_disk.
     */
    public function get_cache_maxsize_disk()
    {
        $maxsizedisk = $this->cf()->dcvalue('MAXSIZE_DISK');

        return $this->sanitize_maxsizedisk($maxsizedisk);
    }

    /**
     * get_precache_maxfile.
     */
    public function get_precache_maxfile()
    {
        if ($this->cf()->is_dcfalse('PRECACHE')) {
            return 0;
        }

        $maxfile = $this->cf()->dcvalue('PRECACHE_MAXFILE');

        return $this->sanitize_precache_maxfile($maxfile);
    }

    /**
     * get_cache_stats.
     */
    public function get_cache_stats($force = false)
    {
        $cache_stats = false;

        if ($this->cf()->is_dctrue('STATS')) {
            if ($force) {
                $cache_stats = $this->cache_size($this->cache_path);
                $this->co()->save_part($cache_stats, 'cachestats');
            } else {
                $cache_stats = $this->co()->get_part('cachestats');
            }
        }

        if (empty($cache_stats) || !\is_array($cache_stats)) {
            $cache_stats = [
                'size' => 0,
                'filesize' => 0,
                'files' => 0,
            ];
        }

        return (object) $cache_stats;
    }

    /**
     * normalize_size.
     */
    public function normalize_size($size, $showb = true)
    {
        $size = wp_convert_hr_to_bytes($size);
        $size = str_replace([',', ' ', 'B'], '', size_format($size));
        if ($showb && is_numeric($size)) {
            $size = $size.'B';
        }

        return $size;
    }

    public function site_url_scheme($site_url)
    {
        $site_url = trim($site_url);
        if ('https://' !== substr($site_url, 0, 8) && $this->is_ssl()) {
            $site_url = nwdcx_fixscheme($site_url, 'https://');
        } elseif (!@preg_match('@^(https?:)?//@', $site_url)) {
            $site_url = nwdcx_fixscheme($site_url, 'http://');
        }

        return rtrim($site_url, '/\\');
    }

    public function site_url($current = false, $is_home = false)
    {
        $option = $is_home ? 'home' : 'siteurl';
        $site_url = get_option($option);
        if (!$current && is_multisite()) {
            $blog_id = get_main_site_id();
            switch_to_blog($blog_id);
            $site_url = get_option($option);
            restore_current_blog();
        }

        return $this->site_url_scheme($site_url);
    }

    public function site_meta($short = false)
    {
        $m = '0,0';
        if (is_multisite()) {
            $n = get_networks(['count' => 1]);
            $s = get_sites(['count' => 1]);
            $m = $n.','.$s;
        }

        if ($short) {
            $meta = $m.','.$this->version();
            $meta = str_replace('0,0,', '', $meta);
            $meta = str_replace('0', '', $meta);
        } else {
            $meta = $m.','.$this->version().','.$GLOBALS['wp_version'];
        }

        return str_replace('.', '', $meta);
    }

    /**
     * flush_cache.
     */
    public function flush_cache($cleanup = false, &$is_timeout = false)
    {
        $this->co()->clear_part('cachestats');
        $this->cx()->delay();

        delete_expired_transients(true);

        $cnt = $this->cachedir_flush($this->cache_path, $cleanup, $is_timeout);
        if (false === $cnt) {
            $this->cx()->undelay();

            return $cnt;
        }

        return $cnt;
    }

    /**
     * flush_fcache.
     */
    public function flush_fcache(&$file = '')
    {
        if (!empty($_GET['idxv'])) {
            $fx = sanitize_text_field($_GET['idxv']);
            $file = $this->cache_path.$fx.'.php';
            if (@is_file($file)) {
                $this->co()->clear_part('cachestats');

                return $this->unlink($file, true);
            }
        }

        return true;
    }

    /**
     * has_log.
     */
    public function has_log(&$logfile = '')
    {
        $logfile = $this->cf()->dcvalue('LOG_FILE');

        if (is_multisite()) {
            $logfile = nwdcx_network_filepath($logfile);
        }

        return @is_file($logfile) && is_readable($logfile);
    }

    /**
     * flush_log.
     */
    public function flush_log()
    {
        if ($this->has_log($logfile)) {
            return @unlink($logfile);
        }

        return false;
    }

    public function switch_cron_site()
    {
        if (is_multisite()) {
            $cronbot_siteid = $this->get_cron_siteid();
            if (!empty($cronbot_siteid) && (int) $cronbot_siteid > 0) {
                switch_to_blog($cronbot_siteid);

                return true;
            }
        }

        return false;
    }

    public function delete_cron_siteid($userid = false)
    {
        if (empty($userid)) {
            $userid = get_current_user_id();
        }

        $key = 'cronbot-siteid-'.get_current_user_id();

        return $this->co()->lookup_delete($key);
    }

    public function set_cron_siteid($id)
    {
        $key = 'cronbot-siteid-'.get_current_user_id();

        return $this->co()->lookup_set($key, $id);
    }

    public function get_cron_siteid()
    {
        $key = 'cronbot-siteid-'.get_current_user_id();
        $siteid = $this->co()->lookup_get($key);
        if (empty($siteid)) {
            $siteid = is_multisite() ? get_main_site_id() : get_current_blog_id();
        }

        return $siteid;
    }

    public function cleanuppost()
    {
        if (!nwdcx_wpdb($wpdb)) {
            return false;
        }

        $sites = $this->get_network_sites();
        $is_multisite = is_multisite();

        $collect = [
            'revision' => 0,
            'autodraft' => 0,
            'trashbin' => 0,
        ];

        $siteid = 0;
        if ($is_multisite) {
            $collect['site'] = \count($sites);

            if (isset($_GET['siteid'])) {
                $siteid = (int) sanitize_text_field($_GET['siteid']);
                $this->set_current_select_siteid($siteid);
            }
        }

        $max_execution_time = $this->get_max_execution_time();
        $suppress = $wpdb->suppress_errors(true);
        $doflush = false;
        foreach ($sites as $num => $site) {
            if ($is_multisite) {
                if (!empty($siteid) && $siteid !== (int) $site['id']) {
                    continue;
                }

                switch_to_blog($site['id']);
            }

            $query = $wpdb->get_col($wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE post_type = %s ORDER BY ID ASC LIMIT 1000", 'revision'));
            if ($query) {
                foreach ($query as $id) {
                    $id = (int) $id;
                    wp_delete_post_revision($id);
                    $doflush = true;
                }
                $collect['revision'] += \count($query);
            }

            $query = $wpdb->get_col($wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE post_status = %s ORDER BY ID ASC LIMIT 1000", 'auto-draft'));
            if ($query) {
                foreach ($query as $id) {
                    $id = (int) $id;
                    wp_delete_post($id, true);
                    $doflush = true;
                }
                $collect['autodraft'] += \count($query);
            }

            $query = $wpdb->get_col($wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE post_status = %s ORDER BY ID ASC LIMIT 1000", 'trash'));
            if ($query) {
                foreach ($query as $id) {
                    $id = (int) $id;
                    wp_delete_post($id, true);
                    $doflush = true;
                }
                $collect['trashbin'] += \count($query);
            }

            if ($is_multisite) {
                restore_current_blog();
            }

            if ($max_execution_time > 0 && \defined('WP_START_TIMESTAMP') && (microtime(true) - WP_START_TIMESTAMP) > $max_execution_time) {
                $is_timeout = true;
                break;
            }
        }

        $wpdb->suppress_errors($suppress);

        if ($doflush) {
            $this->flush_cache(false);
        }

        return (object) $collect;
    }

    public function delete_current_select_siteid($userid = false)
    {
        if (empty($userid)) {
            $userid = get_current_user_id();
        }

        $key = 'current-select-siteid-'.get_current_user_id();

        return $this->co()->lookup_delete($key);
    }

    public function get_current_select_siteid()
    {
        $key = 'current-select-siteid-'.get_current_user_id();

        return (int) $this->co()->lookup_get($key);
    }

    public function set_current_select_siteid($id)
    {
        $key = 'current-select-siteid-'.get_current_user_id();

        return $this->co()->lookup_set($key, $id);
    }

    /**
     * compat_notice.
     */
    private function compat_notice()
    {
        if (!(\PHP_VERSION_ID >= 70205)) {
            $text = __('Docket Cache plugin requires PHP 7.2.5 or greater.', 'docket-cache');

            add_action(
                'all_admin_notices',
                function () use ($text) {
                    echo '<div id="docket-cache-notice" class="notice notice-warning is-dismissible"><p>'.$text.'</p></div>';
                    deactivate_plugins($this->hook);
                    wp_cache_flush();
                },
                \PHP_INT_MIN
            );

            if (\defined('WP_CLI') && WP_CLI) {
                if (!\function_exists('deactivate_plugins')) {
                    include_once ABSPATH.'/wp-admin/includes/plugin.php';
                }

                if (\function_exists('deactivate_plugins')) {
                    deactivate_plugins($this->hook);
                    wp_cache_flush();

                    \WP_CLI::error($text, false);
                    \WP_CLI::halt(1);
                }
            }

            return false;
        }

        return true;
    }

    /**
     * cleanup.
     */
    private function deactivate_cleanup($is_uninstall = false)
    {
        WpConfig::unlink_runtime();

        if ($this->cx()->validate()) {
            $this->cx()->uninstall();
        }

        $this->cx()->undelay();

        if ($this->cf()->is_dctrue('FLUSH_SHUTDOWN', true)) {
            $this->cachedir_flush($this->cache_path, true);
        }

        $this->flush_log();

        if ($is_uninstall) {
            WpConfig::runtime_remove();

            // reset on/off button
            $this->co()->save('objectcacheoff', 'default');

            // stats
            $this->co()->clear_part('cachestats');

            // cronbot/etc
            $this->co()->save('cronbot', 'default');
            $this->co()->clear_part('cronbot');
            $this->co()->clear_part('pings');
            $this->co()->clear_part('checkversion');

            // clear all network if available
            if (is_multisite()) {
                $this->cx()->multinet_clear($this->cache_path, $this->cf()->dcvalue('LOG_FILE'));
            }
        }

        if ($this->cf()->is_dctrue('OPCSHUTDOWN', true)) {
            $this->opcache_cleanup();
        }
    }

    /**
     * uninstall.
     */
    public static function uninstall()
    {
        ( new self() )->deactivate_cleanup(true);
    }

    /**
     * deactivate.
     */
    public function deactivate()
    {
        $this->deactivate_cleanup();
        $this->unregister_cronjob();
    }

    /**
     * activate.
     */
    public function activate()
    {
        if (!$this->compat_notice()) {
            return;
        }

        $this->flush_cache(false);

        if ($this->cf()->is_dcfalse('OBJECTCACHEOFF', true)) {
            $this->cx()->install(true);
        }

        $this->unregister_cronjob();
    }

    /**
     * register_init.
     */
    private function register_init()
    {
        // unofficial constant: possible to disable nag notices
        !\defined('DISABLE_NAG_NOTICES') && \define('DISABLE_NAG_NOTICES', true);

        $this->page = 'admin.php?page='.$this->slug;
        $this->screen = 'toplevel_page_docket-cache';
        $this->cache_path = $this->define_cache_path($this->cf()->dcvalue('PATH'));

        if (is_multisite()) {
            $this->cache_path = nwdcx_network_dirpath($this->cache_path);
        }

        $this->api_endpoint = 'https://api.docketcache.com';
        $this->cronbot_endpoint = 'https://cronbot.docketcache.com';

        // use Constans() to trigger default
        if ($this->cf()->is_dctrue('DEV')) {
            $this->api_endpoint = 'http://api.docketcache.local';
            $this->cronbot_endpoint = 'http://cronbot.docketcache.local';
        }

        $this->token = '';
        $this->notice = '';
        $this->inruntime = false;

        add_filter(
            'perflab_oc_site_status_available_object_cache_services',
            function ($services) {
                $services[] = 'docket-cache';

                return $services;
            },
            \PHP_INT_MAX
        );
    }

    /**
     * critical_version.
     */
    private function critical_version()
    {
        $checkdata = $this->co()->get_part('checkversion', true);
        if (!empty($checkdata) && \is_array($checkdata) && !empty($checkdata['doversion'])) {
            $current_version = $this->plugin_meta($this->file)['Version'];
            if (0 === strcmp($checkdata['doversion'], $current_version)) {
                $this->flush_cache(true);
            }
        }
        unset($checkdata);

        return true;
    }

    /**
     * plugin_upgrade.
     */
    private function plugin_upgrade()
    {
        add_action(
            'shutdown',
            function () {
                $this->close_buffer();

                if ($this->cf()->is_dcfalse('OBJECTCACHEOFF', true)) {
                    $this->cx()->install(true);
                    if (is_multisite()) {
                        $this->cx()->multinet_install($this->hook);
                    }
                }

                // put last
                $this->critical_version();
            },
            \PHP_INT_MAX
        );
    }

    /**
     * register_plugin_hooks.
     */
    private function register_plugin_hooks()
    {
        add_action(
            'plugins_loaded',
            function () {
                load_plugin_textdomain(
                    'docket-cache',
                    false,
                    $this->path.'/languages/'
                );
            },
            0
        );

        add_action(
            'upgrader_process_complete',
            function ($wp_upgrader, $options) {
                if ('update' !== $options['action']) {
                    return;
                }

                if ('plugin' === $options['type'] && !empty($options['plugins'])) {
                    if (!\is_array($options['plugins'])) {
                        return;
                    }

                    foreach ($options['plugins'] as $plugin) {
                        if ($plugin === $this->hook) {
                            $this->plugin_upgrade();
                            break;
                        }
                    }
                }
            },
            \PHP_INT_MAX,
            2
        );

        // wp 5.5 >=
        if (\function_exists('wp_is_maintenance_mode')) {
            add_action(
                'upgrader_overwrote_package',
                function ($package, $package_data, $package_type = 'plugin') {
                    if (!empty($package_data) && \is_array($package_data) && !empty($package_data['TextDomain']) && $this->slug === $package_data['TextDomain']) {
                        $this->plugin_upgrade();
                    }
                },
                \PHP_INT_MAX,
                3
            );
        }

        add_action(
            'admin_footer',
            function () {
                $output = $this->cx()->after_delay();
                if (!empty($output)) {
                    echo $output;
                }
            },
            \PHP_INT_MAX
        );

        if (!is_admin() && $this->cf()->is_dctrue('SIGNATURE')) {
            add_action(
                'send_headers',
                function () {
                    $status = $this->cx()->validate() ? 'on' : 'off';
                    header('x-'.$this->slug.': '.$status.'; '.$this->site_meta(true));
                },
                \PHP_INT_MAX
            );
        }

        if (!empty($_SERVER['REQUEST_URI']) && false !== strpos($_SERVER['REQUEST_URI'], '/admin.php?page=docket-cache')) {
            add_action(
                'plugins_loaded',
                function () {
                    if (!headers_sent() && !empty($_SERVER['REQUEST_URI'])) {
                        if ($this->cf()->is_dctrue('LOG')) {
                            $req = $_SERVER['REQUEST_URI'];
                            if ((false !== strpos($req, '?page=docket-cache&idx=log&dl=0') || false !== strpos($req, '?page=docket-cache-log&idx=log&dl=0'))
                                && preg_match('@log\&dl=\d+$@', $req)) {
                                $file = $this->cf()->dcvalue('LOG_FILE');

                                if (is_multisite()) {
                                    $file = nwdcx_network_filepath($file);
                                }

                                @header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0');
                                @header('Content-Type: text/plain; charset=UTF-8');
                                if (@is_file($file) && @is_readable($file)) {
                                    @readfile($file);
                                    $this->close_exit();
                                }

                                $this->close_exit(__('No data available', 'docket-cache'));
                            }
                        }

                        if (\defined('WP_DEBUG') && WP_DEBUG && \defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
                            $req = $_SERVER['REQUEST_URI'];
                            if ((false !== strpos($req, '?page=docket-cache&idx=config&wplog=0') || false !== strpos($req, '?page=docket-cache-config&idx=config&wplog=0'))
                                && preg_match('@config\&wplog=\d+(\&dd=\d+)?$@', $req)) {
                                $file = ini_get('error_log');

                                @header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0');
                                @header('Content-Type: text/plain; charset=UTF-8');
                                if (@is_file($file) && @is_readable($file)) {
                                    if ($this->cf()->is_dctrue('TRYREADWPDEBUGLOG') || !empty($_GET['dd']) && 1 === (int) $_GET['dd']) {
                                        $log = file_get_contents($file);
                                        if (!empty($log)) {
                                            $log = str_replace(['undefined function', 'Stack trace'], ['undefined-function', 'Stack-trace'], $log);
                                        }
                                        $this->close_exit($log);
                                    }

                                    @readfile($file);
                                    $this->close_exit();
                                }

                                $this->close_exit(__('No data available', 'docket-cache'));
                            }
                        }
                    }
                },
                \PHP_INT_MIN
            );
        }

        add_action(
            'plugins_loaded',
            function () {
                if (nwdcx_wpdb($wpdb)) {
                    if ($this->co()->lockexp('sqlbigselect')) {
                        return;
                    }

                    $suppress = $wpdb->suppress_errors(true);
                    $ok = $wpdb->get_var('SELECT @@SESSION.SQL_BIG_SELECTS LIMIT 1');
                    if (empty($ok)) {
                        $wpdb->query('SET SESSION SQL_BIG_SELECTS=1');
                    } else {
                        // already big select, lock 24h
                        $locktime = time() + 86400;
                        $this->co()->setlock('sqlbigselect', $locktime);
                    }

                    $wpdb->suppress_errors($suppress);
                }
            },
            \PHP_INT_MIN
        );

        add_filter(
            'auto_update_plugin',
            function ($update, $item) {
                if ('docket-cache' === $item->slug) {
                    return $this->cf()->is_dctrue('AUTOUPDATE');
                }

                return $update;
            },
            \PHP_INT_MAX,
            2
        );

        if (class_exists('Nawawi\\DocketCache\\CronAgent')) {
            ( new CronAgent($this) )->register();
        }

        register_activation_hook($this->hook, [$this, 'activate']);
        register_deactivation_hook($this->hook, [$this, 'deactivate']);
        register_uninstall_hook($this->hook, [__CLASS__, 'uninstall']);
    }

    /**
     * action_query.
     */
    public function action_query($key, $args_extra = [])
    {
        $key = str_replace('docket-', '', $key);
        $key = 'docket-'.$key;

        $args = array_merge(
            [
                'action' => $key,
            ],
            $args_extra
        );

        // last
        $args['st'] = time();

        $page = $this->page;
        if (!empty($args['idx']) && $this->is_subpage($args['idx'])) {
            $page = $page.'-'.$args['idx'];
        }

        $query = add_query_arg($args, $page);

        return wp_nonce_url(network_admin_url($query), $key);
    }

    /**
     * action_field. Use at addons.
     */
    public function action_field($key, $args = [])
    {
        $key = str_replace('docket-', '', $key);
        $key = 'docket-'.$key;

        $field = wp_nonce_field($key, '_wpnonce', false, false);
        $field .= '<input type="hidden" name="action" value="'.$key.'">';
        if (!empty($args) && \is_array($args)) {
            foreach ($args as $n => $v) {
                if ('page' === $n) {
                    $v = 'docket-cache-'.$v;
                }
                $field .= '<input type="hidden" name="'.$n.'" value="'.$v.'">';
            }
        }

        return $field;
    }

    /**
     * is_subpage.
     */
    public function is_subpage($index)
    {
        if ('mods' === substr($index, 0, 4)) {
            return true;
        }

        $subpage = [
            'config' => 1,
            'log' => 1,
            'cronbot' => 1,
            'opcviewer' => 1,
        ];

        return \array_key_exists($index, $subpage);
    }

    /**
     * get_subpage.
     */
    public function get_subpage()
    {
        if (!empty($_GET['page'])) {
            if ('docket-cache-' === substr($_GET['page'], 0, 13)) {
                $index = substr($_GET['page'], 13);
                if (!empty($index) && $this->is_subpage($index)) {
                    return $index;
                }
            } elseif ('docket-cache' === $_GET['page'] && !empty($_GET['idx']) && 'mods' === substr($_GET['idx'], 0, 4)) {
                return $_GET['idx'];
            }
        }

        return false;
    }

    /**
     * get_screen.
     */
    public function get_screen()
    {
        $screen = $this->screen;
        $index = $this->get_subpage();
        if (!empty($index)) {
            $screen = $this->slug.'_page_'.$this->slug.'-'.$index;
        }

        return $screen;
    }

    /**
     * get_page.
     */
    public function get_page($args = [])
    {
        $page = $this->page;
        $index = $this->get_subpage();
        if (!empty($index)) {
            $page = $page.'-'.$index;
        }

        if (!empty($args) && \is_array($args)) {
            $query = http_build_query($args);
            if (false !== strpos($page, '?')) {
                $page = rtrim($page, '&').'&'.$query;
            } else {
                $page = $page.'?'.$query;
            }
        }

        return $page;
    }

    /**
     * our_screen.
     */
    public function our_screen()
    {
        $current_screen = get_current_screen()->id;
        if (substr($current_screen, 0, \strlen($this->screen)) === $this->screen) {
            return true;
        }

        $subsplug = $this->slug.'_page_'.$this->slug.'-';
        if (substr($current_screen, 0, \strlen($subsplug)) === $subsplug) {
            return true;
        }

        return false;
    }

    /**
     * register_admin_hooks.
     */
    private function register_admin_hooks()
    {
        $action_name = is_multisite() ? 'network_admin_menu' : 'admin_menu';
        add_action(
            $action_name,
            function () {
                $cap = is_multisite() ? 'manage_network_options' : 'manage_options';
                $order = is_multisite() ? '25.1' : '80.1';
                $view = new View($this);

                add_menu_page(
                    'Docket Cache',
                    'Docket Cache',
                    $cap,
                    $this->slug,
                    [$view, 'index'],
                    Resc::iconmenu(),
                    $order
                );

                $title = esc_html__('Overview', 'docket-cache');
                add_submenu_page(
                    $this->slug,
                    $title,
                    $title,
                    $cap,
                    $this->slug,
                    [$view, 'index']
                );

                if ($this->cf()->is_dctrue('CRONBOT')) {
                    $title = esc_html__('Cronbot', 'docket-cache');
                    add_submenu_page(
                        $this->slug,
                        $title,
                        $title,
                        $cap,
                        $this->slug.'-cronbot',
                        [$view, 'index']
                    );
                }

                if ($this->cf()->is_dctrue('OPCVIEWER')) {
                    $title = esc_html__('OPcache', 'docket-cache');
                    add_submenu_page(
                        $this->slug,
                        $title,
                        $title,
                        $cap,
                        $this->slug.'-opcviewer',
                        [$view, 'index']
                    );
                }

                do_action('docketcache/view/submenubefore', $this->slug, $cap, $view);

                if ($this->cf()->is_dctrue('LOG')) {
                    $title = esc_html__('Cache Log', 'docket-cache');
                    add_submenu_page(
                        $this->slug,
                        $title,
                        $title,
                        $cap,
                        $this->slug.'-log',
                        [$view, 'index']
                    );
                }

                $title = esc_html__('Configuration', 'docket-cache');
                add_submenu_page(
                    $this->slug,
                    $title,
                    $title,
                    $cap,
                    $this->slug.'-config',
                    [$view, 'index']
                );

                do_action('docketcache/view/submenuafter', $this->slug, $cap, $view);
            }
        );

        add_action(
            'admin_bar_menu',
            function ($admin_bar) {
                if (!is_multisite() || !current_user_can('manage_network_options')) {
                    return;
                }

                $admin_bar->add_menu(
                    [
                        'id' => 'network-admin-docketcache',
                        'parent' => 'network-admin',
                        'group' => null,
                        'title' => 'Docket Cache',
                        'href' => network_admin_url($this->page),
                        'meta' => [
                            'title' => 'Docket Cache',
                        ],
                    ]
                );

                if (nwdcx_network_multi()) {
                    $networks = get_networks();
                    if (!empty($networks) && \is_array($networks)) {
                        foreach ($networks as $network) {
                            $id = $network->id;
                            $url = $this->site_url_scheme('http://'.$network->domain.$network->path);
                            $admin_bar->add_menu(
                                [
                                    'id' => 'network-admin-docketcache-'.$id,
                                    'parent' => 'network-admin-'.$id,
                                    'group' => null,
                                    'title' => 'Docket Cache',
                                    'href' => $url.'/wp-admin/network/'.$this->page,
                                    'meta' => [
                                        'title' => 'Docket Cache',
                                    ],
                                ]
                            );
                        }
                    }
                }
            },
            \PHP_INT_MAX
        );

        add_action(
            'in_admin_header',
            function () {
                if ($this->our_screen()) {
                    remove_all_actions('admin_notices');
                    remove_all_actions('all_admin_notices');
                }

                add_action(
                    'all_admin_notices',
                    function () {
                        if (!current_user_can(is_multisite() ? 'manage_network_options' : 'manage_options')) {
                            return;
                        }

                        if (!$this->our_screen() && false === strpos($_SERVER['REQUEST_URI'], '/plugins.php') && false === strpos($_SERVER['REQUEST_URI'], '/update-core.php')) {
                            return;
                        }

                        if ($this->cx()->exists()) {
                            $url = $this->action_query('update-dropino');
                            $urld = $this->action_query('dismiss-dropino');

                            if ($this->cx()->validate()) {
                                if ($this->cx()->is_outdated() && !$this->cx()->install(true)) {
                                    /* translators: %s: url */
                                    $message = sprintf(__('The object-cache.php Drop-In is outdated. Please click <strong>Re-Install</strong> to update it now. <br><br><a href="%s" style="min-width:100px;text-align:center;font-wight:bold;" class="button button-primary">Re-Install</a>', 'docket-cache'), $url);
                                }
                            } else {
                                /* translators: %1$s: url install, %2$s = url dismiss */
                                $message = $this->cf()->is_dctrue('OBJECTCACHEOFF', true) ? '' : sprintf(__('An unknown object-cache.php Drop-In was found. Please click <strong>Install</strong> to use <strong>Docket Cache</strong>. <br><br><a href="%1$s" style="min-width:100px;text-align:center;font-weight:bold;" class="button button-primary button-small">Install</a>&nbsp;<a href="%2$s" style="min-width:100px;text-align:center;font-weight:bold;" class="button button-secondary button-small">Dismiss</a>', 'docket-cache'), $url, $urld);
                            }
                        }

                        if (2 === $this->get_status() && $this->our_screen()) {
                            $message = esc_html__('The Object Cache feature has been disabled at runtime.', 'docket-cache');
                        }

                        if (isset($message)) {
                            echo Resc::boxmsg($message, 'warning', false, false, false);
                        }
                    }
                );
            },
            \PHP_INT_MAX
        );

        add_action(
            'admin_enqueue_scripts',
            function ($hook) {
                $is_debug = $this->cf()->is_true('WP_DEBUG');
                $plugin_url = plugin_dir_url($this->file);
                $version = str_replace('.', '', $this->version()).'xe'.($is_debug ? date('his') : date('yd'));
                wp_enqueue_script($this->slug.'-worker', $plugin_url.'includes/admin/worker.js', ['jquery'], $version, false);
                wp_localize_script(
                    $this->slug.'-worker',
                    'docket_cache_config',
                    [
                        'ajaxurl' => admin_url('admin-ajax.php'),
                        'token' => wp_create_nonce('docketcache-token-nonce'),
                        'slug' => $this->slug,
                        'debug' => $is_debug ? 'true' : 'false',
                    ]
                );

                if ($hook === $this->screen || $this->our_screen()) {
                    wp_enqueue_style($this->slug.'-core', $plugin_url.'includes/admin/docket.css', null, $version);
                    wp_enqueue_script($this->slug.'-core', $plugin_url.'includes/admin/docket.js', ['jquery'], $version, true);
                }

                if ($this->cf()->is_dctrue('PAGELOADER')) {
                    wp_enqueue_style($this->slug.'-loader', $plugin_url.'includes/admin/pageloader.css', null, $version);
                    wp_enqueue_script($this->slug.'-loader', $plugin_url.'includes/admin/pageloader.js', ['jquery'], $version, true);
                }
            }
        );

        // refresh user_meta: after logout
        add_action(
            'wp_logout',
            function () {
                add_action(
                    'shutdown',
                    function () {
                        $this->close_buffer();
                        $user = wp_get_current_user();
                        if (\is_object($user) && isset($user->ID)) {
                            wp_cache_delete($user->ID, 'user_meta');
                            $this->delete_cron_siteid($user->ID);
                            $this->delete_current_select_siteid($user->ID);
                        }
                    },
                    \PHP_INT_MAX
                );
            },
            \PHP_INT_MAX
        );

        add_action(
            'wp_ajax_docket_worker',
            function () {
                if (!check_ajax_referer('docketcache-token-nonce', 'token', false) && !isset($_POST['type'])) {
                    wp_send_json_error('Invalid security token sent.');
                    exit;
                }

                $type = sanitize_text_field($_POST['type']);

                if ($this->cx()->validate()) {
                    if ('preload' === $type) {
                        $this->send_json_continue($this->slug.':worker: pong '.$type);
                        $this->cx()->undelay();
                        do_action('docketcache/action/preload/objectcache');
                        exit;
                    }

                    if ('fetch' === $type) {
                        $this->send_json_continue($this->slug.':worker: pong '.$type);
                        @Crawler::fetch_home();
                        exit;
                    }

                    if ('countcachesize' === $type) {
                        do_action('docketcache/action/countcachesize');

                        $info = (object) $this->get_info();

                        $cache_stats = 1 === $info->status_code && !empty($info->status_text_stats) ? $info->status_text_stats : $info->status_text;
                        $opcache_stats = 1 === $info->opcache_code && !empty($info->opcache_text_stats) ? $info->opcache_text_stats : $info->opcache_text;
                        $opcache_dc_stats = !empty($info->opcache_dc_stats) ? $info->opcache_dc_stats : esc_html__('Not Available', 'docket-cache');
                        $opcache_wp_stats = !empty($info->opcache_wp_stats) ? $info->opcache_wp_stats : esc_html__('Not Available', 'docket-cache');

                        $response = [];
                        $response = ['success' => true];
                        $response['data'] = $this->slug.':worker: pong '.$type;

                        $stats = [];
                        $stats['obc'] = '0B' !== substr($cache_stats, 0, 2) ? $cache_stats : esc_html__('Not Available', 'docket-cache');
                        $stats['opc'] = $opcache_stats;
                        $stats['obcs'] = '0B' !== substr($info->status_text_stats, 0, 2) ? $info->status_text_stats : esc_html__('Not Available', 'docket-cache');
                        $stats['opcs'] = $info->opcache_text_stats;
                        $stats['opcdc'] = $opcache_dc_stats;
                        $stats['opcwp'] = $opcache_wp_stats;

                        $stats['ofile'] = $info->cache_file_stats;
                        $stats['odisk'] = $info->cache_disk_stats;

                        $response['cachestats'] = $stats;
                        wp_send_json($response);
                        exit;
                    }
                }

                if ('flush' === $type) {
                    $this->send_json_continue($this->slug.':worker: pong '.$type);
                    delete_expired_transients(true);
                    exit;
                }

                wp_send_json_error($this->slug.':worker: "'.$type.'" not available');
                exit;
            }
        );

        add_filter(
            'admin_footer_text',
            function ($text) {
                if ($this->our_screen()) {
                    $meta = $this->plugin_meta($this->file);
                    /* translators: %s: version */
                    $text = $meta['Name'].' '.sprintf(__('Version %s', 'docket-cache'), $meta['Version']);
                    $text = apply_filters('docketcache/filter/footerversion', $text);
                }

                return $text;
            },
            \PHP_INT_MAX
        );

        foreach (['update_footer', 'core_update_footer'] as $fn) {
            add_filter(
                $fn,
                function ($text) {
                    if ($this->our_screen()) {
                        /* translators: %s: version */
                        $text = 'WordPress '.' '.sprintf(__('Version %s', 'docket-cache'), $GLOBALS['wp_version']);
                    }

                    return $text;
                },
                \PHP_INT_MAX
            );
        }

        $filter_name = sprintf('%splugin_action_links_%s', is_multisite() ? 'network_admin_' : '', $this->hook);
        add_filter(
            $filter_name,
            function ($links) {
                $new = [
                    'docket-cache-overview' => sprintf('<a href="%s">%s</a>', network_admin_url($this->page), __('Overview', 'docket-cache')),
                    'docket-cache-configuration' => sprintf('<a href="%s">%s</a>', network_admin_url($this->page.'-config'), __('Configure', 'docket-cache')),
                ];

                return array_merge($new, $links);
            }
        );

        add_filter(
            'plugin_row_meta',
            function ($plugin_meta, $plugin_file) {
                if ($plugin_file === $this->hook) {
                    $row_meta = [
                        'docs' => '<a href="https://docs.docketcache.com/?utm_source=wp-plugins&utm_campaign=doc-uri&utm_medium=wp-dash" target="new" rel="noopener">'.__('Docs', 'docket-cache').'</a>',
                        'sponsor' => '<a href="https://docketcache.com/sponsorship/?utm_source=wp-plugins&utm_campaign=sponsor-uri&utm_medium=wp-dash" target="new" rel="noopener"><span class="dashicons dashicons-star-filled" aria-hidden="true" style="font-size:14px;line-height:1.3"></span>'.__('Sponsor', 'docket-cache').'</a>',
                    ];
                    $plugin_meta = array_merge($plugin_meta, $row_meta);
                }

                return $plugin_meta;
            },
            10,
            2
        );

        // reference: Canopt::save()
        add_action(
            'docketcache/action/saveoption',
            function ($name, $value, $status = true) {
                switch ($name) {
                    case 'log':
                        if (true === $status) {
                            $this->flush_log();
                        }
                        if ('enable' === $value) {
                            @Crawler::fetch_home();
                        }
                        break;
                    case 'wpoptaload':
                        add_action(
                            'shutdown',
                            function () {
                                $this->close_buffer();

                                wp_cache_delete('alloptions', 'options');
                                if (\function_exists('wp_cache_flush_group')) {
                                    wp_cache_flush_group('options');
                                    wp_cache_flush_group('docketcache-precache');
                                }
                            },
                            \PHP_INT_MAX
                        );

                        break;
                    case 'cronoptmzdb':
                        $this->unregister_cronjob();
                        break;
                    case 'cronbot':
                        $action = 'enable' === $value ? true : false;
                        add_action(
                            'shutdown',
                            function () use ($action) {
                                if (!$action) {
                                    $this->close_buffer();

                                    apply_filters('docketcache/filter/active/cronbot', $action);
                                }
                            },
                            \PHP_INT_MAX
                        );
                        break;
                    case 'rtwpdebug':
                    case 'rtwpdebuglog':
                        $error_log = ini_get('error_log');
                        if ('off' === $value && @is_file($error_log) && @is_writable($error_log)) {
                            @unlink($error_log);
                        }
                        break;
                    case 'menucache':
                        wp_cache_flush_group('docketcache-menu');
                        break;
                }

                if (WpConfig::is_runtimeconst($name)) {
                    WpConfig::write_runtime();
                }
            },
            -1,
            3
        );

        add_action(
            'docketcache/action/preload/objectcache',
            function () {
                if ($this->cf()->is_dctrue('WPCLI')) {
                    return;
                }

                // lock opcache reset
                $this->co()->setlock('preload_lock_opcache_reset', time() + 20);

                // warmup: see after_delay
                if ($this->cf()->is_dcfalse('PRELOAD')) {
                    add_action(
                        'shutdown',
                        function () {
                            if ($this->co()->lockproc('preload', time() + 3600)) {
                                return false;
                            }
                            //wp_load_alloptions();
                            wp_count_comments(0);
                            wp_count_posts();
                            @Crawler::fetch_home(['blocking' => true]);

                            $this->co()->lockreset('preload');
                        },
                        \PHP_INT_MAX
                    );

                    return;
                }

                // preload
                add_action(
                    'shutdown',
                    function () {
                        if ($this->co()->lockproc('preload', time() + 3600)) {
                            return false;
                        }

                        wp_load_alloptions();
                        wp_count_comments(0);
                        wp_count_posts();

                        @Crawler::fetch_home(['blocking' => true]);

                        $preload_admin = [
                            'index.php',
                            'options-general.php',
                            'options-writing.php',
                            'options-reading.php',
                            'options-discussion.php',
                            'options-media.php',
                            'options-permalink.php',
                            'edit-comments.php',
                            'profile.php',
                            'users.php',
                            'upload.php',
                            'plugins.php',
                            'edit.php',
                            'edit-tags.php?taxonomy=category',
                            'edit-tags.php?taxonomy=post_tag',
                            'edit.php?post_type=page',
                            'post-new.php?post_type=page',
                            'themes.php',
                            'widgets.php',
                            'nav-menus.php',
                            'tools.php',
                            'import.php',
                            'export.php',
                            'site-health.php',
                            'update-core.php',
                        ];

                        $preload_network = [
                            'index.php',
                            'update-core.php',
                            'sites.php',
                            'users.php',
                            'themes.php',
                            'plugins.php',
                            'settings.php',
                        ];

                        if ($this->cf()->is_dcarray('PRELOAD_ADMIN')) {
                            $preload_admin = $this->cf()->dcvalue('PRELOAD_ADMIN');
                        }

                        if ($this->cf()->is_dcarray('PRELOAD_NETWORK')) {
                            $preload_network = $this->cf()->dcvalue('PRELOAD_NETWORK');
                        }

                        if (\is_array($preload_admin) && !empty($preload_admin)) {
                            foreach ($preload_admin as $path) {
                                $url = admin_url('/'.$path);
                                @Crawler::fetch_admin($url, ['blocking' => true]);
                                usleep(500000);
                            }
                        }

                        if (is_multisite() && \is_array($preload_network) && !empty($preload_network)) {
                            foreach ($preload_network as $path) {
                                $url = network_admin_url('/'.$path);
                                @Crawler::fetch_admin($url, ['blocking' => true]);
                                usleep(500000);
                            }
                        }

                        $this->co()->lockreset('preload');
                    },
                    \PHP_INT_MAX
                );
            }
        );

        add_action(
            'docketcache/action/countcachesize',
            function () {
                if ($this->co()->lockproc('doing_countcachesize', time() + $this->get_max_execution_time())) {
                    return;
                }

                $cache_stats = $this->cache_size($this->cache_path);
                $this->co()->save_part($cache_stats, 'cachestats');

                $this->co()->lockreset('doing_countcachesize');
            },
            \PHP_INT_MAX
        );

        add_action(
            'docketcache/action/flushcache/object',
            function () {
                $this->flush_cache(true);
            }
        );

        // page action
        if (class_exists('Nawawi\\DocketCache\\ReqAction')) {
            ( new ReqAction($this) )->register();
        }
    }

    /**
     * register_tweaks.
     */
    private function register_tweaks()
    {
        if (\defined('AUTOSAVE_INTERVAL') && false === AUTOSAVE_INTERVAL) {
            add_action(
                'init',
                function () {
                    wp_deregister_script('autosave');
                },
                \PHP_INT_MAX
            );
        }

        if (class_exists('Nawawi\\DocketCache\\Tweaks')) {
            $tweaks = new Tweaks();

            if ($this->cf()->is_dctrue('OPTWPQUERY')) {
                $tweaks->wpquery();
            }

            if ($this->cf()->is_dctrue('WOOTWEAKS')) {
                $tweaks->woocommerce_misc();
            }

            if ($this->cf()->is_dctrue('WOOADMINOFF')) {
                $tweaks->woocommerce_admin_disabled();
            }

            if ($this->cf()->is_dctrue('WOOWPDASHBOARDOFF')) {
                $tweaks->woocommerce_dashboard_status_remove();
            }

            if ($this->cf()->is_dctrue('WOOWIDGETOFF')) {
                $tweaks->woocommerce_widget_remove();
            }

            if ($this->cf()->is_dctrue('WOOCARTFRAGSOFF')) {
                $tweaks->woocommerce_cart_fragments_remove();
            }

            if ($this->cf()->is_dctrue('WOOADDTOCHARTCRAWLING')) {
                $tweaks->woocommerce_crawling_addtochart_links();
            }

            if ($this->cf()->is_dctrue('WOOEXTENSIONPAGEOFF')) {
                $tweaks->woocommerce_extensionpage_remove();
            }

            if ($this->cf()->is_dctrue('MISC_TWEAKS')) {
                $tweaks->misc();
            }

            if ($this->cf()->is_dctrue('HEADERJUNK')) {
                $tweaks->headerjunk();
            }

            if ($this->cf()->is_dctrue('PINGBACK')) {
                $tweaks->pingback();
            }

            if ($this->cf()->is_dctrue('WPEMOJI')) {
                $tweaks->wpemoji();
            }

            if ($this->cf()->is_dctrue('WPFEED')) {
                $tweaks->wpfeed();
            }

            if ($this->cf()->is_dctrue('WPEMBED')) {
                $tweaks->wpembed();
            }

            if ($this->cf()->is_dctrue('WPLAZYLOAD')) {
                $tweaks->wplazyload();
            }

            if ($this->cf()->is_dctrue('WPSITEMAP')) {
                $tweaks->wpsitemap();
            }

            if ($this->cf()->is_dctrue('WPAPPPASSWORD')) {
                $tweaks->wpapppassword();
            }

            if ($this->cf()->is_dctrue('WPDASHBOARDNEWS')) {
                $tweaks->wpdashboardnews();
            }

            if ($this->cf()->is_dctrue('WPBROWSEHAPPY')) {
                $tweaks->wpbrowsehappy();
            }

            if ($this->cf()->is_dctrue('WPSERVEHAPPY')) {
                $tweaks->wpservehappy();
            }

            if ($this->cf()->is_dctrue('LIMITHTTPREQUEST')) {
                $tweaks->limit_http_request();
            }

            if (version_compare($GLOBALS['wp_version'], '5.8', '<')) {
                if ($this->cf()->is_dctrue('HTTPHEADERSEXPECT')) {
                    $tweaks->http_headers_expect();
                }
            }

            if ($this->cf()->is_dctrue('POSTMISSEDSCHEDULE')) {
                add_action(
                    'shutdown',
                    function () use ($tweaks) {
                        if ($this->co()->lockproc('post_missed_schedule', time() + 180)) {
                            return false;
                        }
                        $this->close_buffer();
                        $tweaks->post_missed_schedule();
                        $this->co()->lockreset('post_missed_schedule');
                    },
                    \PHP_INT_MAX
                );
            }

            if ($this->cf()->is_dctrue('CACHEHTTPRESPONSE')) {
                $tweaks->cache_http_response();
            }
        }

        // only if dropin exists
        if (wp_using_ext_object_cache()) {
            // wp_cache: advanced cache post
            if ($this->cf()->is_dctrue('ADVCPOST') && class_exists('Nawawi\\DocketCache\\PostCache')) {
                ( new PostCache() )->register();
            }

            // wp_cache: translation mo file cache
            if ($this->cf()->is_dctrue('MOCACHE') && class_exists('Nawawi\\DocketCache\\MoCache')) {
                add_filter(
                    'override_load_textdomain',
                    function ($plugin_override, $domain, $mofile) {
                        if (!@is_file($mofile) || !@is_readable($mofile) || !isset($GLOBALS['l10n'])) {
                            return false;
                        }

                        $l10n = $GLOBALS['l10n'];
                        $upstream = empty($l10n[$domain]) ? null : $l10n[$domain];
                        $mo = new MoCache($mofile, $domain, $upstream);
                        $l10n[$domain] = $mo;

                        $GLOBALS['l10n'] = $l10n;

                        return true;
                    },
                    \PHP_INT_MAX,
                    3
                );
            }

            // wp_cache: menu cache
            if ($this->cf()->is_dctrue('MENUCACHE') && class_exists('Nawawi\\DocketCache\\MenuCache')) {
                ( new MenuCache() )->register();
            }
        }

        // optimize term count
        if ($this->cf()->is_dctrue('OPTERMCOUNT') && class_exists('Nawawi\\DocketCache\\TermCount')) {
            ( new TermCount() )->register();
        }
    }

    /**
     * register_cronjob.
     */
    private function register_cronjob()
    {
        ( new Event($this) )->register();
    }

    /**
     * unregister_cronjob.
     */
    private function unregister_cronjob()
    {
        ( new Event($this) )->unregister();
    }

    private function register_cli()
    {
        if ($this->cf()->is_dctrue('WPCLI') && $this->cf()->is_false('DocketCache_CLI')) {
            \define('DocketCache_CLI', true);
            $cli = new Command($this);

            \WP_CLI::add_command('cache dropin:update', [$cli, 'dropino_update']);
            \WP_CLI::add_command('cache dropin:enable', [$cli, 'dropino_enable']);
            \WP_CLI::add_command('cache dropin:disable', [$cli, 'dropino_disable']);
            \WP_CLI::add_command('cache run:gc', [$cli, 'run_gc']);
            \WP_CLI::add_command('cache run:cron', [$cli, 'run_cron']);
            \WP_CLI::add_command('cache run:stats', [$cli, 'run_stats']);
            \WP_CLI::add_command('cache reset:lock', [$cli, 'reset_lock']);
            \WP_CLI::add_command('cache reset:cron', [$cli, 'reset_cron']);

            if ($this->cf()->is_dctrue('ADVCPOST')) {
                \WP_CLI::add_command('cache flush:advcpost', [$cli, 'flush_advcpost']);
            }

            if ($this->cf()->is_dctrue('PRECACHE')) {
                \WP_CLI::add_command('cache flush:precache', [$cli, 'flush_precache']);
            }

            if ($this->cf()->is_dctrue('MENUCACHE')) {
                \WP_CLI::add_command('cache flush:menucache', [$cli, 'flush_menucache']);
            }

            if ($this->cf()->is_dctrue('MOCACHE')) {
                \WP_CLI::add_command('cache flush:mocache', [$cli, 'flush_mocache']);
            }

            \WP_CLI::add_command('cache flush:transient', [$cli, 'flush_transient']);
            \WP_CLI::add_command('cache flush', [$cli, 'flush_cache']);
            \WP_CLI::add_command('cache runtime:install', [$cli, 'runtime_install']);
            \WP_CLI::add_command('cache runtime:remove', [$cli, 'runtime_remove']);
            \WP_CLI::add_command('cache status', [$cli, 'status']);
            \WP_CLI::add_command('cache type', [$cli, 'type']);
            nwdcx_runaction('docketcache/init/cli', $this, $cli);
        }
    }

    /**
     * register.
     */
    public function register()
    {
        if (!$this->compat_notice()) {
            return;
        }
        $this->register_plugin_hooks();
        $this->register_admin_hooks();
        $this->register_tweaks();
        $this->register_cronjob();
        $this->register_cli();

        nwdcx_runaction('docketcache/init', $this);
    }
}
