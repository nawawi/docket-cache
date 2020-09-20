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
?>
<?php $this->tab_title('<span class="dashicons dashicons-admin-plugins"></span>Docket Cache', false, 'text-capitalize'); ?>
<div class="postbox">
    <div class="inside">
        <div>
            <?php if ($this->tab_current('overview')) : ?>
            <p>
                <strong><?php esc_html_e('ADMIN INTERFACE', 'docket-cache'); ?></strong><br class="break">
                <?php esc_html_e('The Docket Cache keeps the admin interface clean, responsive and simple as possible, predefined configuration and works out-of-the-box.', 'docket-cache'); ?>
            </p>
            <hr>
            <p>
                <strong><?php esc_html_e('DROPIN', 'docket-cache'); ?></strong><br class="break">
                <?php esc_html_e('The Docket Cache depends on an object cache operation. Disabling the Object Cache will affect availability of other functions.', 'docket-cache'); ?><br>
            </p>
            <hr>
            <p>
                <strong><?php esc_html_e('CONFIGURATION', 'docket-cache'); ?></strong><br class="break">
                <?php esc_html_e('The configuration page allows to quickly change basic configuration without using constants.', 'docket-cache'); ?><br>
            </p>
            <hr>
            <p>
                <strong><?php esc_html_e('CACHE LOG', 'docket-cache'); ?></strong><br class="break">
                <?php esc_html_e('The cache log page provides information about the cache activities, disabled by default. Activate at the configuration page.', 'docket-cache'); ?><br>
            </p>
            <?php elseif ($this->tab_current('config')) : ?>
            <p>
                <strong><?php esc_html_e('CONSTANTS', 'docket-cache'); ?></strong><br class="break">
                <?php
                    /* translators: %s: <a href="https://github.com/nawawi/docket-cache/wiki/Constants" rel="noopener" target="new">Configuration Wiki</a> */
                    printf(esc_html__('This plugin uses constants variable as main configuration methods. To adjust the plugin behavior, please refer to %s documentation for details.', 'docket-cache'), '<a href="https://docs.docketcache.com/configuration" rel="noopener" target="new">Configuration</a>');
                ?>
            </p>
            <hr>
            <p>
                <strong>WP-CLI</strong><br class="break">
                <?php
                    /* translators: %s: <a href="https://github.com/nawawi/docket-cache/wiki/WP-CLI" rel="noopener" target="new">WP-CLI Wiki</a> */
                    printf(esc_html__('You can manage this plugin through command line, please refer to %s documentation for available commands.', 'docket-cache'), '<a href="https://docs.docketcache.com/wp-cli" rel="noopener" target="new">WP-CLI</a>');
                ?>
            </p>
            <hr>
            <p>
                <strong><?php esc_html_e('CRONBOT', 'docket-cache'); ?></strong><br class="break">
                <?php
                    /* translators: %s: external scheduler */
                    printf(esc_html__('The Cronbot is an %s that pings your website every hour in order to keep WordPress Cron running active. Only site Timezone, URL and version are involved when enabling this service.', 'docket-cache'), '<a href="https://cronbot.docketcache.com" rel="noopener" target="new">external scheduler</a>');
                ?>
            </p>
            <hr>
            <p>
                <strong><?php esc_html_e('FEEDBACK', 'docket-cache'); ?></strong><br class="break">
                <?php esc_html_e('If Docket Cache beneficial to your website performance, it’s more than thank you if you can leave a review about your experience.', 'docket-cache'); ?>
                <a href="https://wordpress.org/support/plugin/docket-cache/reviews/" class="button button-secondary button-small bt-cx" rel="noopener" target="new"><?php esc_html_e('Write Your Review', 'docket-cache'); ?></a>
            </p>
            <hr>
            <p>
                <strong><?php esc_html_e('SPONSOR', 'docket-cache'); ?></strong><br class="break">
                <?php esc_html_e('Docket Cache is an open source project under MIT license. Free to use, free to modify and free to distribute as long copyright retained. To maintain its condition of being free, it requires your support.', 'docket-cache'); ?>
                <a href="https://www.paypal.com/paypalme/ghostbirdme/5usd" class="button button-secondary button-small bt-cx" rel="noopener" target="new"><?php esc_html_e('Sponsor This Project', 'docket-cache'); ?></a>
            </p>
            <?php endif; ?>
        </div>
    </div>
</div>