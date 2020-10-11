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
            <table class="form-table form-table-selection">
                <tr>
                    <th><?php esc_html_e('Cronbot Service', 'docket-cache'); ?></th>
                    <td>
                        <?php echo $this->config_select_bool('cronbot'); ?>
                    </td>
                </tr>
                <tr>
                    <th class="border-b"><?php esc_html_e('Cache Log', 'docket-cache'); ?></th>
                    <td>
                        <?php echo $this->config_select_bool('log'); ?>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Advanced Post Caching', 'docket-cache'); ?></th>
                    <td>
                        <?php echo $this->config_select_bool('advcpost'); ?>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Object Cache Precaching', 'docket-cache'); ?></th>
                    <td>
                        <?php echo $this->config_select_bool('precache'); ?>
                    </td>
                </tr>
                <tr>
                    <th class="border-b"><?php esc_html_e('WordPress Translation Caching', 'docket-cache'); ?></th>
                    <td>
                        <?php echo $this->config_select_bool('mocache'); ?>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Optimize Term Count Queries', 'docket-cache'); ?></th>
                    <td>
                        <?php echo $this->config_select_bool('optermcount'); ?>
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
                                ]
                            );
                            ?>
                    </td>
                </tr>
                <tr>
                    <th class="border-b"><?php esc_html_e('Suspend WP Options Autoload', 'docket-cache'); ?></th>
                    <td>
                        <?php echo $this->config_select_bool('wpoptaload'); ?>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Post Missed Schedule Tweaks', 'docket-cache'); ?></th>
                    <td>
                        <?php echo $this->config_select_bool('postmissedschedule'); ?>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Misc Performance Tweaks', 'docket-cache'); ?></th>
                    <td>
                        <?php echo $this->config_select_bool('misc_tweaks'); ?>
                    </td>
                </tr>
                <tr>
                    <th class="border-b"><?php esc_html_e('Misc WooCommerce Tweaks', 'docket-cache'); ?></th>
                    <td>
                        <?php echo $this->config_select_bool('wootweaks'); ?>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Admin Page Cache Preloading', 'docket-cache'); ?></th>
                    <td>
                        <?php echo $this->config_select_bool('preload'); ?>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Admin Page Loader', 'docket-cache'); ?></th>
                    <td>
                        <?php echo $this->config_select_bool('pageloader'); ?>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Auto Update', 'docket-cache'); ?></th>
                    <td>
                        <?php echo $this->config_select_bool('autoupdate'); ?>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Check Critical Version', 'docket-cache'); ?></th>
                    <td>
                        <?php echo $this->config_select_bool('checkversion'); ?>
                    </td>
                </tr>

                <tr>
                    <th><?php esc_html_e('Object Cache Data Stats', 'docket-cache'); ?></th>
                    <td>
                        <?php echo $this->config_select_bool('stats'); ?>
                    </td>
                </tr>
            </table>
        </div>
        <div class="row-right">
            <?php $this->page('resource'); ?>
        </div>
    </div>
</div>