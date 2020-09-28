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
    private $plugin;

    public function __construct(Plugin $plugin)
    {
        $this->plugin = $plugin;
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

    /**
     * Display the Docket object cache status enable or disable.
     *
     * ## EXAMPLES
     *
     *  wp cache status
     */
    public function status()
    {
        $info = (object) $this->plugin->get_info();
        $halt = $info->status_code ? 0 : 1;

        WP_CLI::line($this->title('Cache Status').$this->status_color($info->status_code, $info->status_text));
        WP_CLI::line($this->title('Cache Path').$info->cache_path);
        if ($this->plugin->constans()->is_true('DOCKET_CACHE_STATS')) {
            WP_CLI::line($this->title('Cache Size').$info->cache_size);
        }
        WP_CLI::halt($halt);
    }

    /**
     * Enables the Docket object cache.
     *
     * Default behavior is to create the object cache drop-in,
     * unless an unknown object cache drop-in is present.
     *
     * ## EXAMPLES
     *
     *  wp cache enable
     */
    public function enable()
    {
        if ($this->plugin->dropino()->exists()) {
            if ($this->plugin->dropino()->validate()) {
                WP_CLI::line(__('Docket object cache already enabled.', 'docket-cache'));
                WP_CLI::halt(0);
            }

            $this->halt_error(__('An unknown object cache drop-in was found. To use Docket object cache, run: wp cache update.', 'docket-cache'));
        }

        if ($this->plugin->dropino()->install()) {
            $this->halt_success(__('Object cache enabled.', 'docket-cache'));
        }

        $this->halt_error(__('Object cache could not be enabled.', 'docket-cache'));
    }

    /**
     * Disables the Docket object cache.
     *
     * Default behavior is to delete the object cache drop-in,
     * unless an unknown object cache drop-in is present.
     *
     * ## EXAMPLES
     *
     *  wp cache disable
     */
    public function disable()
    {
        if (!$this->plugin->dropino()->exists()) {
            $this->halt_error(__('No object cache drop-in found.', 'docket-cache'));
        }

        if (!$this->plugin->dropino()->validate()) {
            $this->halt_error(__('An unknown object cache drop-in was found. To use Docket run: wp cache update.', 'docket-cache'));
        }

        if ($this->plugin->dropino()->uninstall()) {
            $this->halt_success(__('Object cache disabled.', 'docket-cache'));
        }

        $this->halt_error(__('Object cache could not be disabled.', 'docket-cache'));
    }

    /**
     * Updates the Docket object cache drop-in.
     *
     * Default behavior is to overwrite any existing object cache drop-in.
     *
     * ## EXAMPLES
     *
     *  wp cache update
     *
     * @subcommand update
     */
    public function update_dropino()
    {
        if ($this->plugin->dropino()->install()) {
            $this->halt_success(__('Updated object cache drop-in and enabled Docket object cache.', 'docket-cache'));
        }
        $this->halt_error(__('Object cache drop-in could not be updated.', 'docket-cache'));
    }

    /**
     * Flushes the object cache.
     *
     * For WordPress.
     *
     * ## EXAMPLES
     *
     *  wp cache flush
     *
     * @subcommand flush
     */
    public function flush_cache()
    {
        if (false === $this->plugin->flush_cache(true)) {
            $this->halt_error(__('Object cache could not be flushed.', 'docket-cache'));
        }

        $this->plugin->dropino()->undelay();
        $this->halt_success(__('The cache was flushed.', 'docket-cache'));
    }

    /**
     * Clear lock files.
     *
     * Remove lock file.
     *
     * ## EXAMPLES
     *
     *  wp cache clearlock
     *
     * @subcommand flush
     */
    public function clearlock()
    {
        $this->plugin->canopt()->clear_lock();
        $this->halt_success(__('The lock file flushed.', 'docket-cache'));
    }

    /**
     * Run garbage collector.
     *
     * Remove empty and older files.
     *
     * ## EXAMPLES
     *
     *  wp cache gc
     *
     * @subcommand gc
     */
    public function rungc()
    {
        if (!has_filter('docketcache/garbage-collector')) {
            $this->halt_error(__('Garbage collector not available.', 'docket-cache'));
        }

        $is_debug = \defined('WP_DEBUG') && WP_DEBUG;
        $pad = $is_debug ? 25 : 10;
        $collect = apply_filters('docketcache/garbage-collector', true);
        $header = [
            'maxttl' => 'MaxTTL Seconds',
            'maxttl_h' => 'MaxTTL Human',
            'maxttl_c' => 'MaxTTL Cleaned',
            'maxfile' => 'MaxFiles',
            'maxfile_c' => 'MaxFiles Cleaned',
            'total' => 'Totals',
            'clean' => 'Cleaned',
            'expired' => 'Expired',
            'ignore' => 'Ignored',
        ];
        foreach ($collect as $n => $v) {
            if (!$is_debug && 'max' === substr($n, 0, 3)) {
                continue;
            }
            $n = $this->title($header[$n], $pad);
            WP_CLI::line($n.$v);
        }
        $this->halt_success(__('Executing garbage collector.', 'docket-cache'));
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
        $this->halt_status($this->plugin->slug);
    }
}
