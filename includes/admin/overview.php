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

$has_proxy = false;
$proxy_title = '';
if ($this->pt->is_behind_proxy()) :
    $has_proxy = true;
    $cf = $this->pt->is_cloudflare();
    if (false !== $cf) :
        $proxy_title = 'Cloudflare';
        $proxy_text = esc_html($cf);
    else :
        $proxy_title = esc_html__('Web Proxy', 'docket-cache');
        $proxy_text = $this->pt->get_proxy_ip();
    endif;
endif;

$has_stats = $this->vcf()->is_dctrue('STATS');

?>
<div class="section overview">
    <div class="flex-container">
        <div class="row-left">
            <?php $this->tab_title(esc_html__('Overview', 'docket-cache')); ?>
            <table class="form-table">
                <tr>
                    <th><?php esc_html_e('Web Server', 'docket-cache'); ?></th>
                    <td><?php echo $this->pt->get_server_software(); ?></td>
                </tr>

                <tr>
                    <th<?php echo !$has_proxy ? ' class="border-b"' : ''; ?>><?php esc_html_e('PHP SAPI', 'docket-cache'); ?></th>
                        <td><?php echo \PHP_VERSION.' / '.\PHP_SAPI.' ('.\PHP_OS_FAMILY.')'; ?></td>
                </tr>

                <?php if ($has_proxy) : ?>
                <tr>
                    <th class="border-b"><?php echo $proxy_title; ?></th>
                    <td><?php echo $proxy_text; ?></td>
                </tr>
                <?php endif; ?>

                <?php if (!empty($this->info->status_text_stats) && !empty($this->info->opcache_dc_stats)) : ?>
                <tr>
                    <th><?php esc_html_e('Object Cache Stats', 'docket-cache'); ?></th>
                    <td id="objectcache-stats">
                        <?php
                        echo $this->info->status_text_stats;
                        ?>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Object OPcache Stats', 'docket-cache'); ?></th>
                    <td id="dcopcache-stats">
                        <?php echo $this->info->opcache_dc_stats; ?>
                    </td>
                </tr>
                <tr>
                    <th class="border-b"><?php esc_html_e('WP OPcache Stats', 'docket-cache'); ?></th>
                    <td id="wpopcache-stats">
                        <?php echo $this->info->opcache_wp_stats; ?>
                    </td>
                </tr>
                <?php else : ?>
                <tr>
                    <th><?php esc_html_e('Object Cache', 'docket-cache'); ?></th>
                    <td id="objectcache-stats">
                        <?php
                        echo 1 === $this->info->status_code && !empty($this->info->status_text_stats) ? $this->info->status_text_stats : $this->info->status_text;
                        ?>
                    </td>
                </tr>
                <tr>
                    <th class="border-b"><?php esc_html_e('Zend OPcache', 'docket-cache'); ?></th>
                    <td id="opcache-stats0">
                        <?php
                        echo 1 === $this->info->opcache_code && !empty($this->info->opcache_text_stats) ? $this->info->opcache_text_stats : $this->info->opcache_text;
                        ?>
                    </td>
                </tr>
                <?php endif; ?>
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
                <?php
                if (is_multisite()) :
                    $wp_multinetlock = $this->info->wp_multinetlock;
                    ?>

                <?php if (empty($wp_multinetlock)) : ?>
                <tr>
                    <th class="border-b"><?php esc_html_e('WP Multi Site', 'docket-cache'); ?></th>
                    <td><?php echo $this->info->wp_multisite; ?></td>
                </tr>
                <?php else : ?>
                <tr>
                    <th><?php esc_html_e('WP Multi Network', 'docket-cache'); ?></th>
                    <td><?php echo $this->info->wp_multisite; ?></td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Primary Network', 'docket-cache'); ?></th>
                    <td><?php echo $this->info->wp_multinetmain; ?></td>
                </tr>
                <tr>
                    <th class="border-b"><?php esc_html_e('Network Locking File', 'docket-cache'); ?></th>
                    <td><?php echo $this->info->wp_multinetlock; ?></td>
                </tr>
                <?php endif; ?>
                <?php endif; ?>

                <tr>
                    <th><?php esc_html_e('Drop-in Writable', 'docket-cache'); ?></th>
                    <td><?php echo $this->info->write_dropin; ?></td>
                </tr>

                <tr>
                    <th<?php echo !$this->info->dropin_isalt ? ' class="border-b"' : ''; ?>><?php esc_html_e('Drop-in File', 'docket-cache'); ?></th>
                        <td><?php echo $this->info->dropin_path; ?></td>
                </tr>

                <?php if ($this->info->dropin_isalt) : ?>
                <tr>
                    <th><?php esc_html_e('Drop-in use Wrapper', 'docket-cache'); ?></th>
                    <td><?php echo $this->info->dropin_alt; ?></td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Drop-in Wrapper Available', 'docket-cache'); ?></th>
                    <td><?php echo $this->info->dropin_wp_exist; ?></td>
                </tr>
                <tr>
                    <th class="border-b"><?php esc_html_e('Drop-in Wrapper File', 'docket-cache'); ?></th>
                    <td><?php echo $this->info->dropin_wp; ?></td>
                </tr>
                <?php endif; ?>

                <tr>
                    <th><?php esc_html_e('Cache Writable', 'docket-cache'); ?></th>
                    <td><?php echo $this->info->write_cache; ?></td>
                </tr>

                <tr>
                    <th><?php esc_html_e('Cache Files Limit', 'docket-cache'); ?></th>
                    <td id="file-stats"><?php echo $this->info->cache_file_stats; ?></td>
                </tr>

                <tr>
                    <th><?php esc_html_e('Cache Disk Limit', 'docket-cache'); ?></th>
                    <td id="disk-stats"><?php echo $this->info->cache_disk_stats; ?></td>
                </tr>

                <tr>
                    <th><?php esc_html_e('Cache Path', 'docket-cache'); ?></th>
                    <td><?php echo $this->info->cache_path; ?></td>
                </tr>

                <tr>
                    <th class="border-b"><?php esc_html_e('Chunk Cache Directory', 'docket-cache'); ?></th>
                    <td><?php echo $this->info->cache_chunkdir; ?></td>
                </tr>

                <tr>
                    <th><?php esc_html_e('Config Writable', 'docket-cache'); ?></th>
                    <td><?php echo $this->info->write_config; ?></td>
                </tr>

                <tr>
                    <th><?php esc_html_e('Config Path', 'docket-cache'); ?></th>
                    <td><?php echo $this->info->config_path; ?></td>
                </tr>

            </table>
        </div>
        <div class="row-right">
            <?php $this->render('actions'); ?>
        </div>
    </div>
</div>
<?php
if ($has_stats) :
    echo $this->pt->code_worker('repeat_countcachesize');
endif;
