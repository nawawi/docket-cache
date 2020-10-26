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
                <?php esc_html_e('To adjust the plugin behaviour and manage through a command line, please refer to the documentation page for details.', 'docket-cache'); ?>
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
                <strong><?php esc_html_e('CHECK VERSION', 'docket-cache'); ?></strong><br class="break">
                <?php esc_html_e('The Check Critical Version allows Docket Cache to check any critical future version that requires removing cache files before doing the updates, purposely to avoid error-prone.', 'docket-cache'); ?>
            </p>
            <hr>
            <p>
                <strong><?php esc_html_e('FEEDBACK', 'docket-cache'); ?></strong><br class="break">
                <?php esc_html_e('Kindly write a review of your experience using this plugin.', 'docket-cache'); ?>
                <a href="https://docketcache.com/feedback" class="button button-secondary button-small bt-cx" rel="noopener" target="new"><?php esc_html_e('Submit Review', 'docket-cache'); ?></a>
            </p>
            <hr>
            <p>
                <strong><?php esc_html_e('SPONSOR', 'docket-cache'); ?></strong><br class="break">
                <?php esc_html_e('Become our sponsor to funding further development of this project.', 'docket-cache'); ?>
                <a href="https://docketcache.com/sponsorship" class="button button-secondary button-small bt-cx" rel="noopener" target="new"><?php esc_html_e('Become Sponsor', 'docket-cache'); ?></a>
            </p>
        </div>
    </div>
</div>