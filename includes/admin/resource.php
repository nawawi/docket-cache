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
<?php $this->tab_title(esc_html__('Actions', 'docket-cache')); ?>
<div class="qact">
    <div class="cmd">
        <h4><?php esc_html_e('Config Reset', 'docket-cache'); ?></h4>
        <p>
            <?php esc_html_e('Reset all configuration to default.', 'docket-cache'); ?>
        </p>
        <a href="<?php echo $this->pt->action_query('configreset', ['idx' => 'config']); ?>" class="button button-primary button-large btx-spinner"><?php esc_html_e('Reset to default', 'docket-cache'); ?></a>
        <hr>
        <h4><?php esc_html_e('Cleanup Post', 'docket-cache'); ?></h4>
        <p>
            <?php esc_html_e('Cleanup Revisions, Auto Draft, Trash Bin.', 'docket-cache'); ?>
        </p>
        <a href="<?php echo $this->pt->action_query('cleanuppost', ['idx' => 'config']); ?>" class="button button-primary button-large btx-spinner"><?php esc_html_e('Cleanup Post', 'docket-cache'); ?></a>
    </div>
</div>
<?php $this->tab_title(esc_html__('Resources', 'docket-cache')); ?>
<div class="postbox">
    <div class="inside">
        <div>
            <p>
                <strong><?php esc_html_e('DOCUMENTATION', 'docket-cache'); ?></strong><br class="break">
                <?php esc_html_e('To adjust the plugin behaviour and manage through a command line, please refer to the documentation page for details.', 'docket-cache'); ?>
                <a href="https://docs.docketcache.com/" class="button button-secondary button-small bt-cx" rel="noopener" target="new"><?php esc_html_e('Dcoumenation', 'docket-cache'); ?></a>
            </p>
            <?php if (!apply_filters('docketcache/filter/view/nosponsor', false)) : ?>
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
                <a href="https://www.patreon.com/bePatron?u=41796862" class="button button-secondary button-small bt-cx" rel="noopener" target="new"><?php esc_html_e('Become Sponsor', 'docket-cache'); ?></a>
            </p>
            <?php endif; ?>
            <?php do_action('docketcache/action/view/resources'); ?>
        </div>
    </div>
</div>
