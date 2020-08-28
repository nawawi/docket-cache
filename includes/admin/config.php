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
<div class="section config">
    <div class="flex-container">
        <div class="row-left">
            <?php $this->tab_title(esc_html__('Configuration', 'docket-cache')); ?>
            <p class="desc"><?php esc_html_e('The following option allows to quickly change basic configuration, overwrites by constants if defined.', 'docket-cache'); ?></p>
            <table class="form-table form-table-selection">
                <tr>
                    <th class="border-b"><?php esc_html_e('Cronbot Service', 'docket-cache'); ?></th>
                    <td>
                        <?php echo $this->config_select_bool('cronbot', DOCKET_CACHE_CRONBOT); ?>
                    </td>
                </tr>
                <tr>
                    <th<?php echo !DOCKET_CACHE_LOG ? ' class="border-b"' : ''; ?>><?php esc_html_e('Cache Log', 'docket-cache'); ?></th>
                        <td>
                            <?php echo $this->config_select_bool('log', DOCKET_CACHE_LOG); ?>
                        </td>
                </tr>
                <?php if (DOCKET_CACHE_LOG) : ?>
                <tr>
                    <th class="border-b"><?php esc_html_e('Cache Log Time Format', 'docket-cache'); ?></th>
                    <td>
                        <?php
                        echo $this->config_select_set(
                            'log_time',
                            [
                                'default' => __('Default', 'docket-cache'),
                                'utc' => __('UTC', 'docket-cache'),
                                'local' => __('Local time', 'docket-cache'),
                                'wp' => __('Site Format', 'docket-cache'),
                            ],
                            DOCKET_CACHE_LOG_TIME
                        );
                        ?>
                    </td>
                </tr>
                <?php endif; ?>
                <tr>
                    <th><?php esc_html_e('Advanced Post Caching', 'docket-cache'); ?></th>
                    <td>
                        <?php echo $this->config_select_bool('advcpost', DOCKET_CACHE_ADVCPOST); ?>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Object Cache Precaching', 'docket-cache'); ?></th>
                    <td>
                        <?php echo $this->config_select_bool('precache', DOCKET_CACHE_PRECACHE); ?>
                    </td>
                </tr>
                <tr>
                    <th class="border-b"><?php esc_html_e('WordPress Translation Caching', 'docket-cache'); ?></th>
                    <td>
                        <?php echo $this->config_select_bool('mocache', DOCKET_CACHE_MOCACHE); ?>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Optimize Term Count Queries', 'docket-cache'); ?></th>
                    <td>
                        <?php echo $this->config_select_bool('optermcount', DOCKET_CACHE_OPTERMCOUNT); ?>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Optimize Database Tables', 'docket-cache'); ?></th>
                    <td>
                        <?php
                            echo $this->config_select_set(
                                'cronoptmzdb',
                                [
                                    'default' => __('Default', 'docket-cache'),
                                    'daily' => __('Daily', 'docket-cache'),
                                    'weekly' => __('Weekly', 'docket-cache'),
                                    'monthly' => __('Monthly', 'docket-cache'),
                                    'never' => __('Never', 'docket-cache'),
                                ],
                                DOCKET_CACHE_CRONOPTMZDB
                            );
                            ?>
                    </td>
                </tr>
                <tr>
                    <th class="border-b"><?php esc_html_e('Suspend WP Options Autoload', 'docket-cache'); ?></th>
                    <td>
                        <?php echo $this->config_select_bool('wpoptaload', DOCKET_CACHE_WPOPTALOAD); ?>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Post Missed Schedule Tweaks', 'docket-cache'); ?></th>
                    <td>
                        <?php echo $this->config_select_bool('postmissedschedule', DOCKET_CACHE_POSTMISSEDSCHEDULE); ?>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Misc Performance Tweaks', 'docket-cache'); ?></th>
                    <td>
                        <?php echo $this->config_select_bool('misc_tweaks', DOCKET_CACHE_MISC_TWEAKS); ?>
                    </td>
                </tr>
                <tr>
                    <th class="border-b"><?php esc_html_e('Misc WooCommerce Tweaks', 'docket-cache'); ?></th>
                    <td>
                        <?php echo $this->config_select_bool('wootweaks', DOCKET_CACHE_WOOTWEAKS); ?>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Admin Page Cache Preloading', 'docket-cache'); ?></th>
                    <td>
                        <?php echo $this->config_select_bool('preload', DOCKET_CACHE_PRELOAD); ?>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Admin Page Loader', 'docket-cache'); ?></th>
                    <td>
                        <?php echo $this->config_select_bool('pageloader', DOCKET_CACHE_PAGELOADER); ?>
                    </td>
                </tr>
            </table>
        </div>
        <div class="row-right">
            <?php $this->page('resource'); ?>
        </div>
    </div>
</div>