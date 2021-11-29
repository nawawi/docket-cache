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
$ocdisabled = 2 === $this->info->status_code ? ' onclick="return false;" disabled' : '';
$opdisabled = 0 === $this->info->opcache_code ? ' onclick="return false;" disabled' : '';
?>
<?php $this->tab_title(esc_html__('Actions', 'docket-cache')); ?>
<div class="qact">
    <div class="cmd">
        <h4><?php esc_html_e('Object Cache Files', 'docket-cache'); ?></h4>
        <p>
            <?php esc_html_e('Remove all cache files.', 'docket-cache'); ?>
        </p>
        <a href="<?php echo $this->pt->action_query('flush-occache'); ?>" class="button button-primary button-large btx-spinner"><?php esc_html_e('Flush Object Cache', 'docket-cache'); ?></a>
        <hr>

        <h4><?php esc_html_e('Zend OPcache', 'docket-cache'); ?></h4>
        <p>
            <?php esc_html_e('Reset OPcache usage.', 'docket-cache'); ?>
        </p>
        <a href="<?php echo $this->pt->action_query('flush-opcache'); ?>" class="button button-primary button-large btx-spinner" <?php echo $opdisabled; ?>><?php esc_html_e('Flush OPcache', 'docket-cache'); ?></a>
        <hr>
        <h4><?php esc_html_e('Object Cache Drop-In', 'docket-cache'); ?></h4>
        <p>
            <?php esc_html_e('Enable / Disable Drop-In usage.', 'docket-cache'); ?>
        </p>
        <?php if ($this->info->dropin_isalt && !$this->info->dropin_wp_isexist) : ?>
        <p class="text-red">
            <?php esc_html_e('Drop-In Wrapper not available.', 'docket-cache'); ?>
        </p>
        <?php endif; ?>

        <?php if ($this->is_dropin_validate() && $this->is_dropin_multinet()) : ?>
        <a href="<?php echo $this->pt->action_query('disable-occache'); ?>" class="button button-primary button-large btx-spinner" <?php echo $ocdisabled; ?>><?php esc_html_e('Disable Object Cache', 'docket-cache'); ?></a>
        <?php else : ?>
        <a href="<?php echo $this->pt->action_query('enable-occache'); ?>" class="button button-secondary button-large btx-spinner" <?php echo $ocdisabled; ?>><?php esc_html_e('Enable Object Cache', 'docket-cache'); ?></a>
        <?php endif; ?>

        <?php if ($this->vcf()->is_dctrue('GCACTION')) : ?>
        <hr>

        <h4><?php esc_html_e('Garbage Collector', 'docket-cache'); ?></h4>
        <p>
            <?php esc_html_e('Execute the Garbage Collector Task.', 'docket-cache'); ?>
        </p>
        <a href="<?php echo $this->pt->action_query('rungc'); ?>" class="button button-primary button-large btx-spinner"><?php esc_html_e('Run Garbage Collector', 'docket-cache'); ?></a>
        <?php endif; ?>

    </div>
</div>
