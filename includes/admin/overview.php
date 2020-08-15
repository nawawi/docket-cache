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
$is_saasconnect = false; //$this->plugin->constans->is_true('DOCKET_CACHE_CONNECTSAAS');

$has_proxy = false;
$proxy_title = '';
if ($this->plugin->is_behind_proxy()) {
    $has_proxy = true;
    $cf = $this->plugin->is_cloudflare();
    if (false !== $cf) {
        $proxy_title = 'Cloudflare';
        $proxy_text = esc_html($cf);
    } else {
        $proxy_title = esc_html__('Web Proxy', 'docket-cache');
        $proxy_text = $this->plugin->get_proxy_ip();
    }
}
?>
<div class="section overview">
    <?php if ($is_saasconnect) : ?>
    <div class="flex-container">
        <div class="row-left">
            <?php endif; ?>
            <?php $this->tab_title(esc_html__('Overview', 'docket-cache')); ?>
            <table class="form-table">
                <tr>
                    <th><?php esc_html_e('Web Server', 'docket-cache'); ?></th>
                    <td><?php echo $this->plugin->get_server_software(); ?></td>
                </tr>

                <tr>
                    <th<?php echo !$has_proxy ? ' class="border-b"' : ''; ?>><?php esc_html_e('PHP SAPI', 'docket-cache'); ?></th>
                        <td><?php echo PHP_VERSION.' / '.\PHP_SAPI; ?></td>
                </tr>

                <?php if ($has_proxy) : ?>
                <tr>
                    <th class="border-b"><?php echo $proxy_title; ?></th>
                    <td><?php echo $proxy_text; ?></td>
                </tr>
                <?php endif; ?>

                <tr>
                    <th><?php esc_html_e('Object Cache', 'docket-cache'); ?></th>
                    <td><?php echo 1 === $this->info->status_code && !empty($this->info->status_text_stats) ? $this->info->status_text_stats : $this->info->status_text; ?></td>
                </tr>

                <tr>
                    <th class="border-b"><?php esc_html_e('Zend OPcache', 'docket-cache'); ?></th>
                    <td>
                        <?php
                        echo 1 === $this->info->opcache_code ? '<a href="'.$this->plugin->action_query('flush-opcache').'" class="btxo">'.$this->info->opcache_text_stats.'</a>' : $this->info->opcache_text;
                    ?>
                    </td>
                </tr>

                <tr>
                    <th><?php esc_html_e('PHP Memory Limit', 'docket-cache'); ?></th>
                    <td><?php echo $this->info->php_memory_limit; ?></td>
                </tr>

                <tr>
                    <th><?php esc_html_e('WP Frontend Memory Limit', 'docket-cache'); ?></th>
                    <td><?php echo $this->info->wp_memory_limit; ?></td>
                </tr>

                <tr>
                    <th class="border-b"><?php esc_html_e('WP Backend Memory Limit', 'docket-cache'); ?></th>
                    <td><?php echo $this->info->wp_max_memory_limit; ?></td>
                </tr>

                <tr>
                    <th><?php esc_html_e('Drop-in Installable', 'docket-cache'); ?></th>
                    <td><?php echo $this->info->write_dropin; ?></td>
                </tr>

                <tr>
                    <th class="border-b"><?php esc_html_e('Drop-in Path', 'docket-cache'); ?></th>
                    <td><?php echo $this->info->dropin_path; ?></td>
                </tr>

                <tr>
                    <th><?php esc_html_e('Cache Writable', 'docket-cache'); ?></th>
                    <td><?php echo $this->info->write_cache; ?></td>
                </tr>

                <tr>
                    <th><?php esc_html_e('Cache Path', 'docket-cache'); ?></th>
                    <td><?php echo $this->info->cache_path; ?></td>
                </tr>

            </table>

            <p class="submit">
                <?php if (!$this->plugin->dropino->exists()) : ?>

                <?php if ($this->info->cache_size > 0) : ?>
                <a href="<?php echo $this->plugin->action_query('flush-occache'); ?>" class="button button-secondary button-large"><?php esc_html_e('Flush Cache', 'docket-cache'); ?></a>
                <?php endif; ?>

                <?php if (2 !== $this->info->status_code) : ?>
                <a href="<?php echo $this->plugin->action_query('enable-occache'); ?>" class="button button-primary button-large"><?php esc_html_e('Enable Object Cache', 'docket-cache'); ?></a>
                <?php endif; ?>

                <?php elseif ($this->plugin->dropino->validate()) : ?>

                <?php if ($this->info->cache_size > 0) : ?>
                <a href="<?php echo $this->plugin->action_query('flush-occache'); ?>" class="button button-primary button-large"><?php esc_html_e('Flush Cache', 'docket-cache'); ?></a>
                <?php else : ?>
                <a href="<?php echo $this->tab_query('overview'); ?>" class="button button-secondary button-refresh button-large" id="refresh"><?php esc_html_e('Refresh', 'docket-cache'); ?></a>
                <?php endif; ?>

                <a href="<?php echo $this->plugin->action_query('disable-occache'); ?>" class="button button-secondary button-large"><?php esc_html_e('Disable Object Cache', 'docket-cache'); ?></a>
                <?php endif; ?>
            </p>
        </div>
        <?php if ($is_saasconnect) : ?>
        <div class="row-right">
            <?php $this->page('connect'); ?>
        </div>
    </div>
    <?php endif; ?>
</div>