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
        <h4><?php esc_html_e('Cleanup Post', 'docket-cache'); ?></h4>
        <p>
            <?php esc_html_e('Cleanup Revisions, Auto Draft, Trash Bin.', 'docket-cache'); ?>
        </p>
        <?php
            $sites = $this->pt->get_network_sites();
        if (is_multisite() && !empty($sites) && \is_array($sites) && \count($sites) > 1) :
            $current_siteid = (int) $this->pt->get_current_select_siteid();
            ?>
        <label for="siteid"><?php esc_html_e('For Site:', 'docket-cache'); ?></label>
        <select id="siteid">
            <option value='0' <?php echo 0 === $current_siteid ? ' selected' : ''; ?>><?php esc_html_e('all', 'docket-cache'); ?></option>
            <?php
            foreach ($sites as $site) {
                $site_id = (int) $site['id'];
                $site_url = $site['url'];
                $v = nwdcx_noscheme($site_url);
                $selected = $site_id > 0 && $site_id === $current_siteid ? ' selected' : '';
                echo '<option value="'.$site_id.'"'.$selected.'>'.$v.'</option>';
            }
            ?>
        </select>
        <?php endif; ?>
        <a href="<?php echo $this->pt->action_query('cleanuppost', ['idx' => 'config']); ?>" class="button button-primary button-large btx-spinner btx-cleanuppost"><?php esc_html_e('Cleanup Post', 'docket-cache'); ?></a>

        <?php if ($this->vcf()->is_dctrue('FLUSHACTION')) : ?>
        <?php if ($this->vcf()->is_dctrue('ADVCPOST')) : ?>
        <hr>
        <h4><?php esc_html_e('Advanced Post Cache Files', 'docket-cache'); ?></h4>
        <p>
            <?php esc_html_e('Remove Advanced Post Cache files.', 'docket-cache'); ?>
        </p>
        <a href="<?php echo $this->pt->action_query('flush-advcpost', ['idx' => 'config']); ?>" class="button button-primary button-large btx-spinner"><?php esc_html_e('Flush Advanced Post Cache', 'docket-cache'); ?></a>
        <?php endif; ?>

        <?php if ($this->vcf()->is_dctrue('PRECACHE')) : ?>
        <hr>
        <h4><?php esc_html_e('Object Precache Files', 'docket-cache'); ?></h4>
        <p>
            <?php esc_html_e('Remove Object Cache Precaching files.', 'docket-cache'); ?>
        </p>
        <a href="<?php echo $this->pt->action_query('flush-ocprecache', ['idx' => 'config']); ?>" class="button button-primary button-large btx-spinner"><?php esc_html_e('Flush Object Precache', 'docket-cache'); ?></a>
        <?php endif; ?>

        <hr>
        <h4><?php esc_html_e('Transient Cache Files', 'docket-cache'); ?></h4>
        <p>
            <?php esc_html_e('Remove transient cache files.', 'docket-cache'); ?>
        </p>
        <a href="<?php echo $this->pt->action_query('flush-transient', ['idx' => 'config']); ?>" class="button button-primary button-large btx-spinner"><?php esc_html_e('Flush Transient Cache', 'docket-cache'); ?></a>

        <?php if ($this->vcf()->is_dctrue('MENUCACHE')) : ?>
        <hr>
        <h4><?php esc_html_e('Menu Cache Files', 'docket-cache'); ?></h4>
        <p>
            <?php esc_html_e('Remove menu cache files.', 'docket-cache'); ?>
        </p>
        <a href="<?php echo $this->pt->action_query('flush-menucache', ['idx' => 'config']); ?>" class="button button-primary button-large btx-spinner"><?php esc_html_e('Flush Menu Cache', 'docket-cache'); ?></a>
        <?php endif; ?>

        <?php if ($this->vcf()->is_dctrue('MOCACHE')) : ?>
        <hr>
        <h4><?php esc_html_e('Translation Cache Files', 'docket-cache'); ?></h4>
        <p>
            <?php esc_html_e('Remove translation cache files.', 'docket-cache'); ?>
        </p>
        <a href="<?php echo $this->pt->action_query('flush-mocache', ['idx' => 'config']); ?>" class="button button-primary button-large btx-spinner"><?php esc_html_e('Flush Translation Cache', 'docket-cache'); ?></a>
        <?php endif; ?>

        <?php endif; // flushaction?>
        <hr>
        <h4><?php esc_html_e('Config Reset', 'docket-cache'); ?></h4>
        <p>
            <?php esc_html_e('Reset all configuration to default.', 'docket-cache'); ?>
        </p>
        <a href="<?php echo $this->pt->action_query('configreset', ['idx' => 'config']); ?>" class="button button-primary button-large btx-spinner"><?php esc_html_e('Reset to default', 'docket-cache'); ?></a>

        <hr>
        <h4><?php esc_html_e('Runtime Code', 'docket-cache'); ?></h4>
        <p>
            <?php esc_html_e('Code to handles WordPress constants.', 'docket-cache'); ?>
        </p>
        <a href="
        <?php
        $is_install = WpConfig::is_runtimefalse();
        $act = $is_install ? esc_html__('Install Runtime Code', 'docket-cache') : esc_html__('Update Runtime Code', 'docket-cache');
        $actc = $is_install ? 'button-primary' : 'button-secondary';
        echo $this->pt->get_page(
            [
                'idx' => 'config',
                'adx' => 'rtcnf',
                'st' => time(),
            ]
        );
        ?>
        " class="button <?php echo $actc; ?> button-large btx-spinner"><?php echo $act; ?></a>
    </div>
</div>
<?php $this->tab_title(esc_html__('Resources', 'docket-cache')); ?>
<div class="postbox">
    <div class="inside">
        <div>
            <p>
                <strong><?php esc_html_e('DOCUMENTATION', 'docket-cache'); ?></strong><br class="break">
                <?php esc_html_e('To adjust the plugin behaviour and manage through a command line.', 'docket-cache'); ?>
                <a href="https://docs.docketcache.com/?utm_source=wp-plugins&utm_campaign=docs-uri&utm_medium=dc-config" class="button button-secondary button-small bt-cx" rel="noopener" target="new"><?php esc_html_e('Documentation', 'docket-cache'); ?></a>
            </p>
            <hr>
            <p>
                <strong><?php esc_html_e('FEEDBACK', 'docket-cache'); ?></strong><br class="break">
                <?php esc_html_e('Write a review of your experience using this plugin.', 'docket-cache'); ?>
                <a href="https://docketcache.com/feedback/?utm_source=wp-plugins&utm_campaign=reviews-uri&utm_medium=dc-config" class="button button-secondary button-small bt-cx" rel="noopener" target="new"><?php esc_html_e('Submit Review', 'docket-cache'); ?></a>
            </p>
            <?php if (!apply_filters('docketcache/filter/view/nosponsor', false)) : ?>
            <hr>
            <p>
                <strong><?php esc_html_e('SPONSOR', 'docket-cache'); ?></strong><br class="break">
                <?php esc_html_e('Fund Docket Cache one-off or recurring payment to support our open-source development efforts.', 'docket-cache'); ?>
                <a href="https://docketcache.com/sponsorship/?utm_source=wp-plugins&utm_campaign=sponsor-uri&utm_medium=dc-config" class="button button-secondary button-small bt-cx" rel="noopener" target="new"><?php esc_html_e('Become Sponsor', 'docket-cache'); ?></a>
            </p>
            <?php endif; ?>
            <?php do_action('docketcache/action/view/resources'); ?>
        </div>
    </div>
</div>
