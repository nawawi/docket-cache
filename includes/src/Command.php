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
        $this->pt = $pt;
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
        WP_CLI::line($text);
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
            WP_CLI::line($this->title('Cache Status').$this->status_color($info->status_code, $info->status_text));
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

        WP_CLI::line($this->title('Cache Status').$this->status_color($info->status_code, $info->status_text));
        WP_CLI::line($this->title('Cache Path').$info->cache_path);
        if ($this->pt->cf()->is_dctrue('STATS')) {
            WP_CLI::line($this->title('Cache Size').$info->cache_size);
        }

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
        WP_CLI::line(__('Flushing cache. Please wait..', 'docket-cache'));
        sleep(1);

        $is_timeout = false;

        $total = $this->pt->flush_cache(true, $is_timeout);

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
        WP_CLI::line(__('Resetting cron event. Please wait..', 'docket-cache'));
        ( new Event($this->pt) )->reset();
        sleep(1);
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
            WP_CLI::line(__('This command does not support Bedrock. Please manually remove the runtime code.', 'docket-cache'));
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
            WP_CLI::line(__('This command does not support Bedrock. Please manually install the runtime code.', 'docket-cache'));
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
        if (!\function_exists('wp_cache_flush_group')) {
            $this->halt_error(__('Precache could not be flushed.', 'docket-cache'));
        }

        WP_CLI::line(__('Flushing precache. Please wait..', 'docket-cache'));
        sleep(1);
        $total = wp_cache_flush_group('docketcache-precache');

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
        if (!\function_exists('wp_cache_flush_group')) {
            $this->halt_error(__('Transient could not be flushed.', 'docket-cache'));
        }

        WP_CLI::line(__('Flushing transient. Please wait..', 'docket-cache'));
        sleep(1);

        $total = wp_cache_flush_group(['transient', 'site-transient']);

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
        if (!\function_exists('wp_cache_flush_group_match')) {
            $this->halt_error(__('Advanced Post Cache could not be flushed.', 'docket-cache'));
        }

        WP_CLI::line(__('Flushing Advanced Post Cache. Please wait..', 'docket-cache'));
        sleep(1);
        $total = wp_cache_flush_group_match('docketcache-post');

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
        if (!\function_exists('wp_cache_flush_group')) {
            $this->halt_error(__('Menu Cache could not be flushed.', 'docket-cache'));
        }

        WP_CLI::line(__('Flushing Menu Cache. Please wait..', 'docket-cache'));
        sleep(1);
        $total = wp_cache_flush_group('docketcache-menu');

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
        if (!\function_exists('wp_cache_flush_group')) {
            $this->halt_error(__('Translation Cache could not be flushed.', 'docket-cache'));
        }

        WP_CLI::line(__('Flushing Translation Cache. Please wait..', 'docket-cache'));
        sleep(1);
        $total = wp_cache_flush_group('docketcache-mo');

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
        WP_CLI::line(__('Executing the cron event. Please wait..', 'docket-cache'));
        sleep(1);
        WP_CLI::runcommand('cron event run --all');
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
        WP_CLI::line(__('Executing the cache stats. Please wait..', 'docket-cache'));
        sleep(1);
        $pad = 15;
        $stats = $this->pt->get_cache_stats(true);
        WP_CLI::line($this->title(__('Object size', 'docket-cache'), $pad).$this->pt->normalize_size($stats->size));
        WP_CLI::line($this->title(__('File size', 'docket-cache'), $pad).$this->pt->normalize_size($stats->filesize));
        WP_CLI::line($this->title(__('Total file', 'docket-cache'), $pad).$stats->files);
        $this->halt_success(__('Executing the cache stats completed.', 'docket-cache'));
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
        if (!has_filter('docketcache/filter/garbagecollector')) {
            $this->halt_error(__('Garbage collector not available.', 'docket-cache'));
        }

        WP_CLI::line(__('Executing the garbage collector. Please wait..', 'docket-cache'));
        sleep(1);

        $pad = 35;
        $collect = apply_filters('docketcache/filter/garbagecollector', true);

        WP_CLI::line(str_repeat('-', $pad).':'.str_repeat('-', 10));
        WP_CLI::line($this->title(__('Cache MaxTTL', 'docket-cache'), $pad).$collect->cache_maxttl);
        WP_CLI::line($this->title(__('Cache File Limit', 'docket-cache'), $pad).$collect->cache_maxfile);
        WP_CLI::line($this->title(__('Cache Disk Limit', 'docket-cache'), $pad).$this->pt->normalize_size($collect->cache_maxdisk));
        WP_CLI::line(str_repeat('-', $pad).':'.str_repeat('-', 10));
        WP_CLI::line($this->title(__('Cleanup Cache MaxTTL', 'docket-cache'), $pad).$collect->cleanup_maxttl);
        WP_CLI::line($this->title(__('Cleanup Cache File Limit', 'docket-cache'), $pad).$collect->cleanup_maxfile);
        WP_CLI::line($this->title(__('Cleanup Cache Disk Limit', 'docket-cache'), $pad).$collect->cleanup_maxdisk);

        if ($collect->cleanup_expire > 0) {
            WP_CLI::line($this->title(__('Cleanup Cache Expire', 'docket-cache'), $pad).$collect->cleanup_expire);
        }

        if ($this->pt->get_precache_maxfile() > 0 && $collect->cleanup_precache_maxfile > 0) {
            WP_CLI::line($this->title(__('Cleanup Precache Limit', 'docket-cache'), $pad).$collect->cleanup_precache_maxfile);
        }

        if ($this->pt->cf()->is_dctrue('FLUSH_STALECACHE') && $collect->cleanup_stalecache > 0) {
            WP_CLI::line($this->title(__('Cleanup Stale Cache', 'docket-cache'), $pad).$collect->cleanup_stalecache);
        }

        WP_CLI::line(str_repeat('-', $pad).':'.str_repeat('-', 10));
        WP_CLI::line($this->title(__('Total Cache Cleanup', 'docket-cache'), $pad).$collect->cache_cleanup);
        WP_CLI::line($this->title(__('Total Cache Ignored', 'docket-cache'), $pad).$collect->cache_ignore);
        WP_CLI::line($this->title(__('Total Cache File', 'docket-cache'), $pad).$collect->cache_file);
        WP_CLI::line(str_repeat('-', $pad).':'.str_repeat('-', 10));
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
