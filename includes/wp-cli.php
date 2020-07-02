<?php
/**
 * Enables, disabled, updates, and checks the status of the Docket object cache.
 */
class Docket_Object_Cache_CLI_Commands extends WP_CLI_Command
{
    private function docket()
    {
        return $GLOBALS['Docket_Object_Cache'];
    }

    private function halt_error($error)
    {
        $docket = $this->docket();
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
     *     wp docket-cache status
     */
    public function status()
    {
        $docket = $this->docket();
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
     *     wp docket-cache enable
     */
    public function enable()
    {
        $docket = $this->docket();

        if ($docket->has_dropin()) {
            if ($docket->validate_dropin()) {
                WP_CLI::line(__('Docket object cache already enabled.', $docket->slug));
                WP_CLI::halt(0);
            }

            $this->halt_error(__('An unknown object cache drop-in was found. To use Docket object cache, run: wp docket-cache update-dropin.', $docket->slug));
        }

        if ($docket->dropin_install()) {
            $this->halt_success(__('Object cache enabled.', $docket->slug));
        }

        $this->halt_error(__('Object cache could not be enabled.', $docket->slug));
    }

    /**
     * Disables the Docket object cache.
     *
     * Default behavior is to delete the object cache drop-in,
     * unless an unknown object cache drop-in is present.
     *
     * ## EXAMPLES
     *
     *     wp docket-cache disable
     */
    public function disable()
    {
        $docket = $this->docket();

        if (!$docket->has_dropin()) {
            $this->halt_error(__('No object cache drop-in found.', $docket->slug));
        }

        if (!$docket->validate_dropin()) {
            $this->halt_error(__('An unknown object cache drop-in was found. To use Docket run: wp docket-cache update-dropin.', $docket->slug));
        }

        if ($docket->dropin_uninstall()) {
            $this->halt_success(__('Object cache disabled.', $docket->slug));
        }

        $this->halt_error(__('Object cache could not be disabled.', $docket->slug));
    }

    /**
     * Updates the Docket object cache drop-in.
     *
     * Default behavior is to overwrite any existing object cache drop-in.
     *
     * ## EXAMPLES
     *
     *     wp docket-cache update-dropin
     *
     * @subcommand update-dropin
     */
    public function update_dropin()
    {
        $docket = $this->docket();

        if ($docket->dropin_install()) {
            $this->halt_success(__('Updated object cache drop-in and enabled Docket object cache.', $docket->slug));
        }
        $this->halt_error(__('Object cache drop-in could not be updated.', $docket->slug));
    }

    /**
     * Flushes the object cache.
     *
     * Directly execute 'wp cache flush' if drop-in file exists.
     *
     * ## EXAMPLES
     *
     *     wp docket-cache flush
     */
    public function flush()
    {
        $docket = $this->docket();

        if (!$docket->has_dropin()) {
            $this->halt_error(__('No object cache drop-in found.', $docket->slug));
        }

        WP_CLI::runcommand('cache flush');
    }
}

WP_CLI::add_command('docket-cache', 'Docket_Object_Cache_CLI_Commands');
