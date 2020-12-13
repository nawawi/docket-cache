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
                <tr id="cronbot">
                    <th>
                        <?php
                        echo esc_html__('Cronbot Service', 'docket-cache').$this->tooltip('cronbot');
                        ?>
                    </th>
                    <td>
                        <?php echo $this->config_select_bool('cronbot'); ?>
                    </td>
                </tr>
                <tr id="log">
                    <th class="border-b">
                        <?php
                        echo esc_html__('Cache Log', 'docket-cache').$this->tooltip('log');
                        ?>
                    </th>
                    <td>
                        <?php echo $this->config_select_bool('log'); ?>
                    </td>
                </tr>
                <tr id="advpost">
                    <th><?php echo esc_html__('Advanced Post Caching', 'docket-cache').$this->tooltip('advcpost'); ?></th>
                    <td>
                        <?php echo $this->config_select_bool('advcpost'); ?>
                    </td>
                </tr>
                <tr id="precache">
                    <th><?php echo esc_html__('Object Cache Precaching', 'docket-cache').$this->tooltip('precache'); ?></th>
                    <td>
                        <?php echo $this->config_select_bool('precache'); ?>
                    </td>
                </tr>
                <tr id="mocache">
                    <th class="border-b"><?php echo esc_html__('WordPress Translation Caching', 'docket-cache').$this->tooltip('mocache'); ?></th>
                    <td>
                        <?php echo $this->config_select_bool('mocache'); ?>
                    </td>
                </tr>
                <tr id="optwpquery">
                    <th><?php echo esc_html__('Optimize WP Query', 'docket-cache').$this->tooltip('optwpquery'); ?></th>
                    <td>
                        <?php echo $this->config_select_bool('optwpquery'); ?>
                    </td>
                </tr>
                <tr id="optermcount">
                    <th><?php echo esc_html__('Optimize Term Count Queries', 'docket-cache').$this->tooltip('optermcount'); ?></th>
                    <td>
                        <?php echo $this->config_select_bool('optermcount'); ?>
                    </td>
                </tr>
                <tr id="cronoptmzdb">
                    <th><?php echo esc_html__('Optimize Database Tables', 'docket-cache').$this->tooltip('cronoptmzdb'); ?></th>
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
                <tr id="wpoptaload">
                    <th class="border-b"><?php echo esc_html__('Suspend WP Options Autoload', 'docket-cache').$this->tooltip('wpoptaload'); ?></th>
                    <td>
                        <?php echo $this->config_select_bool('wpoptaload'); ?>
                    </td>
                </tr>
                <tr id="postmissedschedule">
                    <th><?php echo esc_html__('Post Missed Schedule Tweaks', 'docket-cache').$this->tooltip('postmissedschedule'); ?></th>
                    <td>
                        <?php echo $this->config_select_bool('postmissedschedule'); ?>
                    </td>
                </tr>
                <tr id="misc_tweaks">
                    <th><?php echo esc_html__('Misc Performance Tweaks', 'docket-cache').$this->tooltip('misc_tweaks'); ?></th>
                    <td>
                        <?php echo $this->config_select_bool('misc_tweaks'); ?>
                    </td>
                </tr>
                <tr id="wootweaks">
                    <th><?php echo esc_html__('Misc WooCommerce Tweaks', 'docket-cache').$this->tooltip('wootweaks'); ?></th>
                    <td>
                        <?php echo $this->config_select_bool('wootweaks'); ?>
                    </td>
                </tr>
                <tr id="wooadminoff">
                    <th><?php echo esc_html__('Deactivate WooCommerce Admin', 'docket-cache').$this->tooltip('wooadminoff'); ?></th>
                    <td>
                        <?php echo $this->config_select_bool('wooadminoff'); ?>
                    </td>
                </tr>
                <tr id="woowidgetoff">
                    <th><?php echo esc_html__('Deactivate WooCommerce Widget', 'docket-cache').$this->tooltip('woowidgetoff'); ?></th>
                    <td>
                        <?php echo $this->config_select_bool('woowidgetoff'); ?>
                    </td>
                </tr>
                <tr id="woowpdashboardoff">
                    <th class="border-b"><?php echo esc_html__('Deactivate WooCommerce WP Dashboard', 'docket-cache').$this->tooltip('woowpdashboardoff'); ?></th>
                    <td>
                        <?php echo $this->config_select_bool('woowpdashboardoff'); ?>
                    </td>
                </tr>
                <tr id="pingback">
                    <th><?php echo esc_html__('Remove XML-RPC / Pingbacks', 'docket-cache').$this->tooltip('pingback'); ?></th>
                    <td>
                        <?php echo $this->config_select_bool('pingback'); ?>
                    </td>
                </tr>
                <tr id="headerjunk">
                    <th><?php echo esc_html__('Remove WP Header Junk', 'docket-cache').$this->tooltip('headerjunk'); ?></th>
                    <td>
                        <?php echo $this->config_select_bool('headerjunk'); ?>
                    </td>
                </tr>
                <tr id="wpemoji">
                    <th><?php echo esc_html__('Remove WP Emoji', 'docket-cache').$this->tooltip('wpemoji'); ?></th>
                    <td>
                        <?php echo $this->config_select_bool('wpemoji'); ?>
                    </td>
                </tr>
                <tr id="wpfeed">
                    <th><?php echo esc_html__('Remove WP Feed', 'docket-cache').$this->tooltip('wpfeed'); ?></th>
                    <td>
                        <?php echo $this->config_select_bool('wpfeed'); ?>
                    </td>
                </tr>
                <tr id="wpembed">
                    <th><?php echo esc_html__('Remove WP Embed', 'docket-cache').$this->tooltip('wpembed'); ?></th>
                    <td>
                        <?php echo $this->config_select_bool('wpembed'); ?>
                    </td>
                </tr>
                <tr id="wplazyload">
                    <th><?php echo esc_html__('Remove WP Lazy Load', 'docket-cache').$this->tooltip('wplazyload'); ?></th>
                    <td>
                        <?php echo $this->config_select_bool('wplazyload'); ?>
                    </td>
                </tr>
                <tr id="wpsitemap">
                    <th><?php echo esc_html__('Remove WP Sitemap', 'docket-cache').$this->tooltip('wpsitemap'); ?></th>
                    <td>
                        <?php echo $this->config_select_bool('wpsitemap'); ?>
                    </td>
                </tr>
                <tr id="wpapppassword">
                    <th class="border-b"><?php echo esc_html__('Remove WP Application Passwords', 'docket-cache').$this->tooltip('wpapppassword'); ?></th>
                    <td>
                        <?php echo $this->config_select_bool('wpapppassword'); ?>
                    </td>
                </tr>
                <tr id="preload">
                    <th><?php echo esc_html__('Admin Page Cache Preloading', 'docket-cache').$this->tooltip('preload'); ?></th>
                    <td>
                        <?php echo $this->config_select_bool('preload'); ?>
                    </td>
                </tr>
                <tr id="pageloader">
                    <th><?php echo esc_html__('Admin Page Loader', 'docket-cache').$this->tooltip('pageloader'); ?></th>
                    <td>
                        <?php echo $this->config_select_bool('pageloader'); ?>
                    </td>
                </tr>
                <tr id="stats">
                    <th><?php echo esc_html__('Object Cache Data Stats', 'docket-cache').$this->tooltip('stats'); ?></th>
                    <td>
                        <?php echo $this->config_select_bool('stats'); ?>
                    </td>
                </tr>
                <tr id="gcaction">
                    <th class="border-b"><?php echo esc_html__('Garbage Collector Action Button', 'docket-cache').$this->tooltip('gcaction'); ?></th>
                    <td>
                        <?php echo $this->config_select_bool('gcaction'); ?>
                    </td>
                </tr>
                <tr id="autoupdate">
                    <th><?php echo esc_html__('Docket Cache Auto Update', 'docket-cache').$this->tooltip('autoupdate'); ?></th>
                    <td>
                        <?php echo $this->config_select_bool('autoupdate'); ?>
                    </td>
                </tr>
                <tr id="checkversion">
                    <th><?php echo esc_html__('Check Critical Version', 'docket-cache').$this->tooltip('checkversion'); ?></th>
                    <td>
                        <?php echo $this->config_select_bool('checkversion'); ?>
                    </td>
                </tr>
            </table>
        </div>
        <div class="row-right">
            <?php $this->render('resource'); ?>
        </div>
    </div>
</div>
