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
<?php $this->tab_title('Resources', false); ?>
<div class="postbox">
    <div class="inside">
        <div>
            <p>
                <strong><?php esc_html_e('DOCUMENTATION', 'docket-cache'); ?></strong><br class="break">
                <?php esc_html_e('This plugin uses constants variable as main configuration methods. To adjust the plugin behaviour and manage through a command line, please refer to the documentation page for details.', 'docket-cache'); ?>
                <a href="https://docs.docketcache.com/" class="button button-secondary button-small bt-cx" rel="noopener" target="new"><?php esc_html_e('Dcoumenation', 'docket-cache'); ?></a>
            </p>
            <hr>
            <p>
                <strong><?php esc_html_e('CRONBOT', 'docket-cache'); ?></strong><br class="break">
                <?php
                    /* translators: %s: external service */
                    printf(esc_html__('The Cronbot is an %s that pings your website every hour to keep WordPress Cron running actively. Only site Timezone, URL and version are involved when enabling this service.', 'docket-cache'), '<a href="https://cronbot.docketcache.com" rel="noopener" target="new">external service</a>');
                ?>
            </p>
            <hr>
            <p>
                <strong><?php esc_html_e('CACHE LOG', 'docket-cache'); ?></strong><br class="break">
                <?php esc_html_e('The cache log intends to provide information on how the cache works. For performance and security concerns, deactivate if no longer needed.', 'docket-cache'); ?>
            </p>
            <hr>
            <p>
                <strong><?php esc_html_e('FEEDBACK', 'docket-cache'); ?></strong><br class="break">
                <?php esc_html_e('Kindly write a review of your experience if Docket Cache is beneficial to the performance of your website.', 'docket-cache'); ?>
                <a href="https://wordpress.org/support/plugin/docket-cache/reviews/" class="button button-secondary button-small bt-cx" rel="noopener" target="new"><?php esc_html_e('Write Your Review', 'docket-cache'); ?></a>
            </p>
            <hr>
            <p>
                <strong><?php esc_html_e('SPONSOR', 'docket-cache'); ?></strong><br class="break">
                <?php esc_html_e('Docket Cache is an Open Source project sponsored by you.', 'docket-cache'); ?>
                <a href="https://www.paypal.com/paypalme/ghostbirdme/10usd" class="button button-secondary button-small bt-cx" rel="noopener" target="new"><?php esc_html_e('Sponsor This Project', 'docket-cache'); ?></a>
            </p>
        </div>
    </div>
</div>