<?php
/**
 * Docket Cache.
 *
 * @author  Nawawi Jamili
 * @license MIT
 *
 * @see    https://github.com/nawawi/docket-cache
 */

namespace Nawawi\Docket_Cache\CLI;

use WP_CLI;
use WP_CLI_Command;

/**
 * Enables, disabled, updates, and checks the status of the Docket object cache.
 */
class Command extends WP_CLI_Command
{
    private $docket;

    public function __construct($parent)
    {
        $this->docket = $parent;
    }

    private function halt_error($error)
    {
        $docket = $this->docket;
        WP_CLI::error($error, false);
        WP_CLI::halt(1);
    }

    private function halt_success($success)
    {
        WP_CLI::success($success, false);
        WP_CLI::halt(0);
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
        $docket = $this->docket;
        $status = $docket->get_status();
        $text = $docket->status_code[$status];
        $halt = 1;

        switch ($status) {
            case 1:
                $text = WP_CLI::colorize("%g{$text}%n");
                break;
            default:
                $text = WP_CLI::colorize("%r{$text}%n");
                break;
        }

        WP_CLI::line('Status: '.$text);
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
        $docket = $this->docket;

        if ($docket->has_dropin()) {
            if ($docket->validate_dropin()) {
                WP_CLI::line(__('Docket object cache already enabled.', 'docket-cache'));
                WP_CLI::halt(0);
            }

            $this->halt_error(__('An unknown object cache drop-in was found. To use Docket object cache, run: wp docket-cache update-dropin.', 'docket-cache'));
        }

        if ($docket->dropin_install()) {
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
        $docket = $this->docket;

        if (!$docket->has_dropin()) {
            $this->halt_error(__('No object cache drop-in found.', 'docket-cache'));
        }

        if (!$docket->validate_dropin()) {
            $this->halt_error(__('An unknown object cache drop-in was found. To use Docket run: wp docket-cache update-dropin.', 'docket-cache'));
        }

        if ($docket->dropin_uninstall()) {
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
     *  wp cache update-dropin
     *
     * @subcommand update-dropin
     */
    public function update_dropin()
    {
        $docket = $this->docket;

        if ($docket->dropin_install()) {
            $this->halt_success(__('Updated object cache drop-in and enabled Docket object cache.', 'docket-cache'));
        }
        $this->halt_error(__('Object cache drop-in could not be updated.', 'docket-cache'));
    }

    /**
     * Flushes the object cache.
     *
     * Directly execute 'wp cache flush' if drop-in file exists.
     *
     * ## EXAMPLES
     *
     *  wp docket-cache flush
     */
    public function flush()
    {
        $docket = $this->docket;

        if (!$docket->has_dropin()) {
            $this->halt_error(__('No object cache drop-in found.', 'docket-cache'));
        }

        WP_CLI::runcommand('cache flush');
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
     *     wp cache type
     */
    public function type()
    {
        WP_CLI::line($this->docket->slug);
    }
}
