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

use WP_CLI;
use WP_CLI_Command;

/**
 * Enables, disabled, updates, and checks the status of the Docket object cache.
 */
class Command extends WP_CLI_Command
{
    private $pt;

    public function __construct(Plugin $pt)
    {
        if (empty($_SERVER['HTTP_HOST'])) {
            $_SERVER['HTTP_HOST'] = wp_parse_url(site_url(), \PHP_URL_HOST);
        }

        $this->pt = $pt;
    }

    private function print_stdout($text, $nl = true)
    {
        if (!empty($_SERVER['argv']) && \in_array('--quiet', $_SERVER['argv'])) {
            return;
        }
        fwrite(\STDOUT, $text.($nl ? "\n" : ''));
    }

    private function clear_line()
    {
        $this->print_stdout("\r".str_repeat(' ', 100)."\r", false);
    }

    private function halt_warning($warning)
    {
        WP_CLI::warning($warning);
        WP_CLI::halt(2);
    }

    private function halt_error($error)
    {
        WP_CLI::error($error, false);
        WP_CLI::halt(1);
    }

    private function halt_success($success)
    {
        WP_CLI::success($success, false);
        WP_CLI::halt(0);
    }

    private function halt_status($text, $status = 0)
    {
        $this->print_stdout($text);
        WP_CLI::halt($status);
    }

    private function title($text, $pad = 15)
    {
        return str_pad($text, $pad).': ';
    }

    private function status_color($status, $text)
    {
        switch ($status) {
            case 1:
                $text = WP_CLI::colorize("%g{$text}%n");
                break;
            default:
                $text = WP_CLI::colorize("%r{$text}%n");
                break;
        }

        return $text;
    }

    private function dropino_runtime_status()
    {
        $info = (object) $this->pt->get_info();
        if (2 === $info->status_code) {
            $this->print_stdout($this->title('Cache Status').$this->status_color($info->status_code, $info->status_text));
            unset($info);
            WP_CLI::halt(1);
        }
        unset($info);
    }

    /**
     * Display the Docket Cache status.
     *
     * ## EXAMPLES
     *
     *  wp cache status
     */
    public function status()
    {
        $info = (object) $this->pt->get_info();
        $halt = $info->status_code ? 0 : 1;

        $line = str_repeat('-', 15).':'.str_repeat('-', \strlen($info->cache_path) + 2);
        $this->print_stdout($line);
        $this->print_stdout($this->title('Cache Status').$this->status_color($info->status_code, $info->status_text));
        $this->print_stdout($this->title('Cache Path').$info->cache_path);
        if ($this->pt->cf()->is_dctrue('STATS')) {
            $this->print_stdout($this->title('Cache Size').$info->cache_size);
        }
        $this->print_stdout($line);
        unset($info);

        WP_CLI::halt($halt);
    }

    /**
     * Enables the Docket Cache Drop-In file.
     *
     * Default behavior is to create the object cache Drop-In,
     * unless an unknown object cache Drop-In is present.
     *
     * ## EXAMPLES
     *
     *  wp cache dropin:enable
     */
    public function dropino_enable()
    {
        $this->dropino_runtime_status();

        if ($this->pt->cx()->exists()) {
            if ($this->pt->cx()->validate()) {
                $this->halt_success(__('Docket object cache already enabled.', 'docket-cache'));
            }

            $this->halt_error(__('An unknown object cache Drop-In was found. To use Docket object cache, run: wp cache dropin:update.', 'docket-cache'));
        }

        if ($this->pt->cx()->install()) {
            $this->halt_success(__('Object cache enabled.', 'docket-cache'));
        }

        $this->halt_error(__('Object cache could not be enabled.', 'docket-cache'));
    }

    /**
     * Disables the Docket Cache Drop-In file.
     *
     * Default behavior is to delete the object cache Drop-In,
     * unless an unknown object cache Drop-In is present.
     *
     * ## EXAMPLES
     *
     *  wp cache dropin:disable
     */
    public function dropino_disable()
    {
        $this->dropino_runtime_status();

        if (!$this->pt->cx()->exists()) {
            $this->halt_error(__('No object cache Drop-In found.', 'docket-cache'));
        }

        if (!$this->pt->cx()->validate()) {
            $this->halt_error(__('An unknown object cache Drop-In was found. To use Docket run: wp cache dropin:update.', 'docket-cache'));
        }

        if ($this->pt->cx()->uninstall()) {
            $this->halt_success(__('Object cache disabled.', 'docket-cache'));
        }

        $this->halt_error(__('Object cache could not be disabled.', 'docket-cache'));
    }

    /**
     * Updates the Docket Cache Drop-In file.
     *
     * Default behavior is to overwrite any existing object cache Drop-In.
     *
     * ## EXAMPLES
     *
     *  wp cache update
     *
     * @subcommand dropin:update
     */
    public function dropino_update()
    {
        $this->dropino_runtime_status();

        if ($this->pt->cx()->install()) {
            $this->halt_success(__('Updated object cache Drop-In and enabled Docket object cache.', 'docket-cache'));
        }
        $this->halt_error(__('Object cache Drop-In could not be updated.', 'docket-cache'));
    }

    /**
     * Flushes the object cache.
     *
     * Remove the object cache files.
     *
     * ## EXAMPLES
     *
     *  wp cache flush
     *
     * @subcommand flush
     */
    public function flush_cache()
    {
        $this->print_stdout(__('Flushing cache. Please wait..', 'docket-cache'), false);
        sleep(1);

        $is_timeout = false;

        $total = $this->pt->flush_cache(true, $is_timeout);

        $this->clear_line();

        $this->pt->cx()->undelay();

        if ($is_timeout) {
            /* translators: %d = seconds */
            $this->halt_error(sprintf(__('Process aborted. The object cache is not fully flushed. The maximum execution time of %d seconds was exceeded.', 'docket-cache'), $result));
        }

        if (empty($total)) {
            $this->halt_error(__('The cache is empty, no cache needs to be flushed.', 'docket-cache'));
        }

        /* translators: %d = count */
        $this->halt_success(sprintf(__('The cache was flushed. Total cache flushed: %d', 'docket-cache'), $total));
    }

    /**
     * Removes the Docket Cache lock files.
     *
     * Remove lock file.
     *
     * ## EXAMPLES
     *
     *  wp cache reset:lock
     *
     * @subcommand reset:lock
     */
    public function reset_lock()
    {
        $this->pt->co()->clear_lock();
        $this->halt_success(__('The lock has been removed.', 'docket-cache'));
    }

    /**
     * Reset the Docket Cache cron event.
     *
     * Reset cron event.
     *
     * ## EXAMPLES
     *
     *  wp cache reset:cron
     *
     * @subcommand reset:cron
     */
    public function reset_cron()
    {
        $this->print_stdout(__('Resetting cron event. Please wait..', 'docket-cache'), false);

        ( new Event($this->pt) )->reset();
        sleep(1);

        $this->clear_line();

        WP_CLI::runcommand('cron event list');
        $this->halt_success(__('Cron event has been reset.', 'docket-cache'));
    }

    /**
     * Removes the Docket Cache runtime code.
     *
     * Remove runtime code.
     *
     * ## EXAMPLES
     *
     *  wp cache runtime:remove
     *
     * @subcommand runtime:remove
     */
    public function runtime_remove()
    {
        if (WpConfig::is_bedrock()) {
            $this->print_stdout(__('This command does not support Bedrock. Please manually remove the runtime code.', 'docket-cache'));
            WP_CLI::halt(1);
        }

        if (WpConfig::runtime_remove()) {
            $this->halt_success(__('The runtime code has been removed.', 'docket-cache'));
        }
        $this->halt_error(__('Failed to remove runtime code.', 'docket-cache'));
    }

    /**
     * Install the Docket Cache runtime code.
     *
     * Install runtime code in wp-config file.
     *
     * ## EXAMPLES
     *
     *  wp cache runtime:install
     *
     * @subcommand runtime:install
     */
    public function runtime_install()
    {
        if (WpConfig::is_bedrock()) {
            $this->print_stdout(__('This command does not support Bedrock. Please manually install the runtime code.', 'docket-cache'));
            WP_CLI::halt(1);
        }

        if (WpConfig::runtime_install()) {
            $this->halt_success(__('Updating wp-config.php file successful', 'docket-cache'));
        }
        $this->halt_error(__('Failed to update wp-config.php file.', 'docket-cache'));
    }

    /**
     * Flushes the precaching files.
     *
     * Remove the precaching files.
     *
     * ## EXAMPLES
     *
     *  wp cache flush:precache
     *
     * @subcommand flush:precache
     */
    public function flush_precache()
    {
        if (!\function_exists('wp_cache_flush_group') || !method_exists('WP_Object_Cache', 'dc_remove_group')) {
            $this->halt_error(__('Object Precache could not be flushed. Docket Cache object-cache.php Drop-in is inactive.', 'docket-cache'));
        }

        $this->print_stdout(__('Flushing precache. Please wait..', 'docket-cache'), false);
        sleep(1);

        $total = wp_cache_flush_group(['docketcache-precache', 'docketcache-precache-gc']);
        $this->clear_line();

        /* translators: %d = count */
        $this->halt_success(sprintf(__('The precache was flushed. Total cache flushed: %d', 'docket-cache'), $total));
    }

    /**
     * Flushes the transient files.
     *
     * Remove the transient files.
     *
     * ## EXAMPLES
     *
     *  wp cache flush:transient
     *
     * @subcommand flush:transient
     */
    public function flush_transient()
    {
        if (!\function_exists('wp_cache_flush_group') || !method_exists('WP_Object_Cache', 'dc_remove_group')) {
            $this->halt_error(__('Transient could not be flushed. Docket Cache object-cache.php Drop-in is inactive.', 'docket-cache'));
        }

        $this->print_stdout(__('Flushing transient. Please wait..', 'docket-cache'), false);
        sleep(1);

        $total = wp_cache_flush_group(['transient', 'site-transient']);
        $this->clear_line();

        /* translators: %d = couint */
        $this->halt_success(sprintf(__('The transient was flushed. Total cache flushed: %d', 'docket-cache'), $total));
    }

    /**
     * Flushes the Advanced Post Cache files.
     *
     * Remove the Advanced Post Cache files.
     *
     * ## EXAMPLES
     *
     *  wp cache flush:advcpost
     *
     * @subcommand flush:advcpost
     */
    public function flush_advcpost()
    {
        if (!\function_exists('wp_cache_flush_group_match') || !method_exists('WP_Object_Cache', 'dc_remove_group')) {
            $this->halt_error(__('Advanced Post Cache could not be flushed. Docket Cache object-cache.php Drop-in is inactive.', 'docket-cache'));
        }

        $this->print_stdout(__('Flushing Advanced Post Cache. Please wait..', 'docket-cache'), false);
        sleep(1);
        $total = wp_cache_flush_group_match('docketcache-post');

        $this->clear_line();

        /* translators: %d = count */
        $this->halt_success(sprintf(__('The Advanced Post Cache was flushed. Total cache flushed: %d', 'docket-cache'), $total));
    }

    /**
     * Flushes the Menu Cache files.
     *
     * Remove the Menu Cache files.
     *
     * ## EXAMPLES
     *
     *  wp cache flush:menucache
     *
     * @subcommand flush:menucache
     */
    public function flush_menucache()
    {
        if (!\function_exists('wp_cache_flush_group') || !method_exists('WP_Object_Cache', 'dc_remove_group')) {
            $this->halt_error(__('Menu Cache could not be flushed. Docket Cache object-cache.php Drop-in is inactive.', 'docket-cache'));
        }

        $this->print_stdout(__('Flushing Menu Cache. Please wait..', 'docket-cache'), false);
        sleep(1);
        $total = wp_cache_flush_group('docketcache-menu');

        $this->clear_line();

        /* translators: %d = count */
        $this->halt_success(sprintf(__('The Menu Cache was flushed. Total cache flushed: %d', 'docket-cache'), $total));
    }

    /**
     * Flushes the Translation Cache files.
     *
     * Remove the Translation Cache files.
     *
     * ## EXAMPLES
     *
     *  wp cache flush:mocache
     *
     * @subcommand flush:mocache
     */
    public function flush_mocache()
    {
        if (!\function_exists('wp_cache_flush_group') || !method_exists('WP_Object_Cache', 'dc_remove_group')) {
            $this->halt_error(__('Translation Cache could not be flushed. Docket Cache object-cache.php Drop-in is inactive.', 'docket-cache'));
        }

        $this->print_stdout(__('Flushing Translation Cache. Please wait..', 'docket-cache'), false);
        sleep(1);
        $total = wp_cache_flush_group('docketcache-mo');

        $this->clear_line();

        /* translators: %d = count */
        $this->halt_success(sprintf(__('The Translation Cache was flushed. Total cache flushed: %d', 'docket-cache'), $total));
    }

    /**
     * Runs all cron event.
     *
     * Runs all cron event.
     *
     * ## EXAMPLES
     *
     *  wp cache run:cron
     *
     * @subcommand run:cron
     */
    public function run_cron()
    {
        $this->print_stdout(__('Executing the cron event. Please wait..', 'docket-cache'), false);
        sleep(1);

        $this->clear_line();

        WP_CLI::runcommand('cron event run --all');
        WP_CLI::runcommand('cron event list');
    }

    /**
     * Runs the Docket Cache cache stats.
     *
     * Collect cache stats data.
     *
     * ## EXAMPLES
     *
     *  wp cache run:stats
     *
     * @subcommand run:stats
     */
    public function run_stats()
    {
        $this->print_stdout(__('Executing the cache stats. Please wait..', 'docket-cache'), false);
        sleep(1);

        $pad = 15;
        $stats = $this->pt->get_cache_stats(true);

        $this->clear_line();

        $padr = 10;
        if (\strlen($stats->files) > 10) {
            $padr = \strlen($stats->files);
        }

        $line = str_repeat('-', $pad).':'.str_repeat('-', $padr);
        $this->print_stdout($line);
        $this->print_stdout($this->title(__('Object size', 'docket-cache'), $pad).$this->pt->normalize_size($stats->size));
        $this->print_stdout($this->title(__('File size', 'docket-cache'), $pad).$this->pt->normalize_size($stats->filesize));
        $this->print_stdout($this->title(__('Total file', 'docket-cache'), $pad).$stats->files);
        $this->print_stdout($line);
        $this->halt_success(__('Executing the cache stats completed.', 'docket-cache'));
    }

    /**
     * Runs the Docket Cache Optimizedb.
     *
     * Optimize DB.
     *
     * ## EXAMPLES
     *
     *  wp cache run:optimizedb
     *
     * @subcommand run:stats
     */
    public function run_optimizedb()
    {
        $this->print_stdout(__('Executing the optimizedb. Please wait..', 'docket-cache'), false);
        sleep(1);

        ( new Event($this->pt) )->optimizedb();

        $this->clear_line();

        $this->halt_success(__('Executing the optimizedb completed.', 'docket-cache'));
    }

    /**
     * Runs the Docket Cache garbage collector (GC).
     *
     * Remove empty and older files, and execute various actions.
     *
     * ## EXAMPLES
     *
     *  wp cache run:gc
     *
     * @subcommand run:gc
     */
    public function run_gc()
    {
        $this->print_stdout(__('Executing the garbage collector. Please wait..', 'docket-cache'), false);
        sleep(1);

        $pad = 35;
        $collect = ( new Event($this->pt) )->garbage_collector(true);

        $this->clear_line();

        if ($collect->is_locked) {
            $this->halt_warning(__('Process locked. The garbage collector is in process. Try again in a few seconds.', 'docket-cache'));
        }

        $line = str_repeat('-', $pad).':'.str_repeat('-', 10);
        $this->print_stdout($line);
        $this->print_stdout($this->title(__('Cache MaxTTL', 'docket-cache'), $pad).$collect->cache_maxttl);
        $this->print_stdout($this->title(__('Cache File Limit', 'docket-cache'), $pad).$collect->cache_maxfile);
        $this->print_stdout($this->title(__('Cache Disk Limit', 'docket-cache'), $pad).$this->pt->normalize_size($collect->cache_maxdisk));
        $this->print_stdout($line);
        $this->print_stdout($this->title(__('Cleanup Cache MaxTTL', 'docket-cache'), $pad).$collect->cleanup_maxttl);
        $this->print_stdout($this->title(__('Cleanup Cache File Limit', 'docket-cache'), $pad).$collect->cleanup_maxfile);
        $this->print_stdout($this->title(__('Cleanup Cache Disk Limit', 'docket-cache'), $pad).$collect->cleanup_maxdisk);

        if ($collect->cleanup_expire > 0) {
            $this->print_stdout($this->title(__('Cleanup Cache Expire', 'docket-cache'), $pad).$collect->cleanup_expire);
        }

        if ($this->pt->get_precache_maxfile() > 0 && $collect->cleanup_precache_maxfile > 0) {
            $this->print_stdout($this->title(__('Cleanup Precache Limit', 'docket-cache'), $pad).$collect->cleanup_precache_maxfile);
        }

        if ($this->pt->cf()->is_dctrue('FLUSH_STALECACHE') && $collect->cleanup_stalecache > 0) {
            $this->print_stdout($this->title(__('Cleanup Stale Cache', 'docket-cache'), $pad).$collect->cleanup_stalecache);
        }

        $this->print_stdout($line);
        $this->print_stdout($this->title(__('Total Cache Cleanup', 'docket-cache'), $pad).$collect->cache_cleanup);
        $this->print_stdout($this->title(__('Total Cache Ignored', 'docket-cache'), $pad).$collect->cache_ignore);
        $this->print_stdout($this->title(__('Total Cache File', 'docket-cache'), $pad).$collect->cache_file);
        $this->print_stdout($line);
        $this->halt_success(__('Executing the garbage collector completed.', 'docket-cache'));
    }

    /**
     * Attempts to determine which object cache is being used.
     *
     * Note that the guesses made by this function are based on the
     * WP_Object_Cache classes that define the 3rd party object cache extension.
     * Changes to those classes could render problems with this function's
     * ability to determine which object cache is being used.
     *
     * ## EXAMPLES
     *
     *  wp cache type
     */
    public function type()
    {
        $this->halt_status($this->pt->slug.' (v'.$this->pt->version().')');
    }
}
