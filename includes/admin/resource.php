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
<?php $this->tab_title('<span class="dashicons dashicons-share"></span>&nbsp;'.esc_html__('Resources', 'docket-cache'), false, 'text-capitalize'); ?>
<div class="postbox">
    <div class="inside">
        <div>
            <p>
                <?php esc_html_e('The Docket Cache keeps the admin interface clean and simple as possible, predefined configuration and works out-of-the-box.', 'docket-cache'); ?>
            </p>
            <hr>
            <?php if ($this->tab_current('overview')) : ?>
            <p>
                <strong><?php esc_html_e('DROPIN', 'docket-cache'); ?></strong><br class="break">
                <?php esc_html_e('The Docket Cache relied on an object cache operation. Disabling the Object Cache will affect availability of other functions.', 'docket-cache'); ?><br>
            </p>
            <hr>
            <p>
                <strong><?php esc_html_e('CONFIGURATION', 'docket-cache'); ?></strong><br class="break">
                <?php esc_html_e('The configuration panel allows to quickly change basic configuration without using constants.', 'docket-cache'); ?><br>
            </p>
            <hr>
            <p>
                <strong><?php esc_html_e('CACHE LOG', 'docket-cache'); ?></strong><br class="break">
                <?php esc_html_e('The cache log panel provides information about the cache activities, disabled by default. Activate at the configuration panel.', 'docket-cache'); ?><br>
            </p>
            <?php elseif ($this->tab_current('config')) : ?>
            <p>
                <strong><?php esc_html_e('CONSTANTS', 'docket-cache'); ?></strong><br class="break">
                <?php
                    /* translators: %s: <a href="https://github.com/nawawi/docket-cache/wiki/Constants" rel="noopener" target="new">Configuration Wiki</a> */
                    printf(esc_html__('This plugin uses constants variable as main configuration methods. To adjust the plugin behavior, please refer to %s page for details.', 'docket-cache'), '<a href="https://github.com/nawawi/docket-cache/wiki/Constants" rel="noopener" target="new">Configuration Wiki</a>');
                ?>
            </p>
            <p>
                <strong>WP-CLI</strong><br class="break">
                <?php
                    /* translators: %s: <a href="https://github.com/nawawi/docket-cache/wiki/WP-CLI" rel="noopener" target="new">WP-CLI Wiki</a> */
                    printf(esc_html__('You can manage this plugin through command line, please refer to %s page for available commands.', 'docket-cache'), '<a href="https://github.com/nawawi/docket-cache/wiki/WP-CLI" rel="noopener" target="new">WP-CLI Wiki</a>');
                ?>
            </p>
            <hr>
            <p>
                <strong><?php esc_html_e('CRONBOT', 'docket-cache'); ?></strong><br class="break">
                <?php esc_html_e('The Cronbot is an external scheduler that pings your website every hour in order to keep WordPress Cron running active.', 'docket-cache'); ?><br>
            </p>
            <hr>
            <p>
                <?php esc_html_e('If Docket Cache beneficial to your website performance, it’s more than thank you if you can leave a review about your experience.', 'docket-cache'); ?><br>
                <a href="https://wordpress.org/support/plugin/docket-cache/reviews/" rel="noopener" target="new"><?php esc_html_e('Write your review.', 'docket-cache'); ?></a>
            </p>
            <?php endif; ?>
        </div>


    </div>

</div>