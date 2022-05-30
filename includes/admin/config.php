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
                    <td colspan="2" class="stitle">
                        <?php esc_html_e('Feature Options', 'docket-cache'); ?>
                    </td>
                </tr>
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
                <tr id="opcviewer">
                    <th>
                        <?php
                        echo esc_html__('OPcache Viewer', 'docket-cache').$this->tooltip('opcviewer');
                        ?>
                    </th>
                    <td>
                        <?php echo $this->config_select_bool('opcviewer'); ?>
                    </td>
                </tr>

                <?php $this->render('@inc:features'); ?>

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
                <tr>
                    <td colspan="2" class="stitle">
                        <?php esc_html_e('Cache Options', 'docket-cache'); ?>
                    </td>
                </tr>
                <tr id="advpost">
                    <th><?php echo esc_html__('Advanced Post Caching', 'docket-cache').$this->tooltip('advcpost'); ?></th>
                    <td>
                        <?php echo $this->config_select_bool('advcpost'); ?>
                    </td>
                </tr>
                <?php if ($this->vcf()->is_dctrue('advcpost')) : ?>
                <tr id="advpost_posttype_all">
                    <th><?php echo esc_html__('Post Caching Any Post Type', 'docket-cache').$this->tooltip('advpost_posttype_all'); ?></th>
                    <td>
                        <?php echo $this->config_select_bool('advpost_posttype_all'); ?>
                    </td>
                </tr>
                <?php endif; ?>

                <tr id="precache">
                    <th><?php echo esc_html__('Object Cache Precaching', 'docket-cache').$this->tooltip('precache'); ?></th>
                    <td>
                        <?php echo $this->config_select_bool('precache'); ?>
                    </td>
                </tr>
                <tr id="menucache">
                    <th><?php echo esc_html__('WordPress Menu Caching', 'docket-cache').$this->tooltip('menucache'); ?></th>
                    <td>
                        <?php echo $this->config_select_bool('menucache'); ?>
                    </td>
                </tr>
                <tr id="mocache">
                    <th><?php echo esc_html__('WordPress Translation Caching', 'docket-cache').$this->tooltip('mocache'); ?></th>
                    <td>
                        <?php echo $this->config_select_bool('mocache'); ?>
                    </td>
                </tr>
                <tr id="preload">
                    <th class="border-b"><?php echo esc_html__('Admin Object Cache Preloading', 'docket-cache').$this->tooltip('preload'); ?></th>
                    <td>
                        <?php echo $this->config_select_bool('preload'); ?>
                    </td>
                </tr>
                <tr>
                    <td colspan="2" class="stitle">
                        <?php esc_html_e('Optimisations', 'docket-cache'); ?>
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
                    <th><?php echo esc_html__('Suspend WP Options Autoload', 'docket-cache').$this->tooltip('wpoptaload'); ?></th>
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
                    <th class="border-b"><?php echo esc_html__('Misc Performance Tweaks', 'docket-cache').$this->tooltip('misc_tweaks'); ?></th>
                    <td>
                        <?php echo $this->config_select_bool('misc_tweaks'); ?>
                    </td>
                </tr>
                <tr>
                    <td colspan="2" class="stitle">
                        <?php esc_html_e('Woo Tweaks', 'docket-cache'); ?>
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
                    <th><?php echo esc_html__('Deactivate WooCommerce WP Dashboard', 'docket-cache').$this->tooltip('woowpdashboardoff'); ?></th>
                    <td>
                        <?php echo $this->config_select_bool('woowpdashboardoff'); ?>
                    </td>
                </tr>
                <tr id="wooextensionpageoff">
                    <th><?php echo esc_html__('Deactivate WooCommerce Extensions Page', 'docket-cache').$this->tooltip('wooextensionpageoff'); ?></th>
                    <td>
                        <?php echo $this->config_select_bool('wooextensionpageoff'); ?>
                    </td>
                </tr>
                <tr id="woocartfragsoff">
                    <th><?php echo esc_html__('Deactivate WooCommerce Cart Fragments', 'docket-cache').$this->tooltip('woocartfragsoff'); ?></th>
                    <td>
                        <?php echo $this->config_select_bool('woocartfragsoff'); ?>
                    </td>
                </tr>
                <tr id="wooaddtochartcrawling">
                    <th class="border-b"><?php echo esc_html__('Prevent robots crawling add-to-cart links', 'docket-cache').$this->tooltip('wooaddtochartcrawling'); ?></th>
                    <td>
                        <?php echo $this->config_select_bool('wooaddtochartcrawling'); ?>
                    </td>
                </tr>
                <tr>
                    <td colspan="2" class="stitle">
                        <?php esc_html_e('WP Tweaks', 'docket-cache'); ?>
                    </td>
                </tr>
                <tr id="headerjunk">
                    <th><?php echo esc_html__('Remove WP Header Junk', 'docket-cache').$this->tooltip('headerjunk'); ?></th>
                    <td>
                        <?php echo $this->config_select_bool('headerjunk'); ?>
                    </td>
                </tr>
                <tr id="pingback">
                    <th><?php echo esc_html__('Deactivate XML-RPC / Pingbacks', 'docket-cache').$this->tooltip('pingback'); ?></th>
                    <td>
                        <?php echo $this->config_select_bool('pingback'); ?>
                    </td>
                </tr>
                <tr id="wpemoji">
                    <th><?php echo esc_html__('Deactivate WP Emoji', 'docket-cache').$this->tooltip('wpemoji'); ?></th>
                    <td>
                        <?php echo $this->config_select_bool('wpemoji'); ?>
                    </td>
                </tr>
                <tr id="wpfeed">
                    <th><?php echo esc_html__('Deactivate WP Feed', 'docket-cache').$this->tooltip('wpfeed'); ?></th>
                    <td>
                        <?php echo $this->config_select_bool('wpfeed'); ?>
                    </td>
                </tr>
                <tr id="wpembed">
                    <th><?php echo esc_html__('Deactivate WP Embed', 'docket-cache').$this->tooltip('wpembed'); ?></th>
                    <td>
                        <?php echo $this->config_select_bool('wpembed'); ?>
                    </td>
                </tr>
                <tr id="wplazyload">
                    <th><?php echo esc_html__('Deactivate WP Lazy Load', 'docket-cache').$this->tooltip('wplazyload'); ?></th>
                    <td>
                        <?php echo $this->config_select_bool('wplazyload'); ?>
                    </td>
                </tr>
                <tr id="wpsitemap">
                    <th><?php echo esc_html__('Deactivate WP Sitemap', 'docket-cache').$this->tooltip('wpsitemap'); ?></th>
                    <td>
                        <?php echo $this->config_select_bool('wpsitemap'); ?>
                    </td>
                </tr>
                <tr id="wpapppassword">
                    <th><?php echo esc_html__('Deactivate WP Application Passwords', 'docket-cache').$this->tooltip('wpapppassword'); ?></th>
                    <td>
                        <?php echo $this->config_select_bool('wpapppassword'); ?>
                    </td>
                </tr>
                <tr id="wpdashboardnews">
                    <th><?php echo esc_html__('Deactivate WP Events & News Feed Dashboard', 'docket-cache').$this->tooltip('wpdashboardnews'); ?></th>
                    <td>
                        <?php echo $this->config_select_bool('wpdashboardnews'); ?>
                    </td>
                </tr>
                <tr id="wpbrowsehappy">
                    <th><?php echo esc_html__('Deactivate Browse Happy Checking', 'docket-cache').$this->tooltip('wpbrowsehappy'); ?></th>
                    <td>
                        <?php echo $this->config_select_bool('wpbrowsehappy'); ?>
                    </td>
                </tr>
                <tr id="wpservehappy">
                    <th><?php echo esc_html__('Deactivate Serve Happy Checking', 'docket-cache').$this->tooltip('wpservehappy'); ?></th>
                    <td>
                        <?php echo $this->config_select_bool('wpservehappy'); ?>
                    </td>
                </tr>
                <tr id="limithttprequest">
                    <th><?php echo esc_html__('Limit WP-Admin HTTP Requests', 'docket-cache').$this->tooltip('limithttprequest'); ?></th>
                    <td>
                        <?php echo $this->config_select_bool('limithttprequest'); ?>
                    </td>
                </tr>
                <?php if (version_compare($GLOBALS['wp_version'], '5.8', '<')) : ?>
                <tr id="httpheadersexpect">
                    <th class="border-b"><?php echo esc_html__('HTTP Request Expect header tweaks', 'docket-cache').$this->tooltip('httpheadersexpect'); ?></th>
                    <td>
                        <?php echo $this->config_select_bool('httpheadersexpect'); ?>
                    </td>
                </tr>
                <?php endif; ?>
                <tr>
                    <td colspan="2" class="stitle">
                        <?php esc_html_e('Runtime Options', 'docket-cache'); ?>
                    </td>
                </tr>
                <tr id="rtpostautosave">
                    <th><?php echo esc_html__('Auto Save Interval', 'docket-cache').$this->tooltip('rtpostautosave'); ?></th>
                    <td>
                        <?php
                            echo $this->config_select_set(
                                'rtpostautosave',
                                [
                                    'default' => __('Default', 'docket-cache'),
                                    '1' => __('Every Minute', 'docket-cache'),
                                    '5' => __('Every 5 Minutes', 'docket-cache'),
                                    '15' => __('Every 15 Minutes', 'docket-cache'),
                                    'off' => __('Disable', 'docket-cache'),
                                ]
                            );
                            ?>
                    </td>
                </tr>
                <tr id="rtpostrevision">
                    <th><?php echo esc_html__('Post Revisions', 'docket-cache').$this->tooltip('rtpostrevision'); ?></th>
                    <td>
                        <?php
                            echo $this->config_select_set(
                                'rtpostrevision',
                                [
                                    'default' => __('Default', 'docket-cache'),
                                    '3' => __('Limit to 3 Revisions', 'docket-cache'),
                                    '5' => __('Limit to 5 Revisions', 'docket-cache'),
                                    'on' => __('No Limit', 'docket-cache'),
                                    'off' => __('Disable', 'docket-cache'),
                                ]
                            );
                            ?>
                    </td>
                </tr>
                <tr id="rtpostemptytrash">
                    <th><?php echo esc_html__('Trash Bin', 'docket-cache').$this->tooltip('rtpostemptytrash'); ?></th>
                    <td>
                        <?php
                            echo $this->config_select_set(
                                'rtpostemptytrash',
                                [
                                    'default' => __('Default', 'docket-cache'),
                                    '7' => __('Empty In 7 Days', 'docket-cache'),
                                    '14' => __('Empty In 14 Days', 'docket-cache'),
                                    '30' => __('Empty In 30 Days', 'docket-cache'),
                                    'off' => __('Disable', 'docket-cache'),
                                ]
                            );
                            ?>
                    </td>
                </tr>
                <tr id="rtimageoverwrite">
                    <th><?php echo esc_html__('Cleanup Image Edits', 'docket-cache').$this->tooltip('rtimageoverwrite'); ?></th>
                    <td>
                        <?php
                            echo $this->config_select_set(
                                'rtimageoverwrite',
                                [
                                    'default' => __('Default', 'docket-cache'),
                                    'on' => __('Enable', 'docket-cache'),
                                    'off' => __('Disable', 'docket-cache'),
                                ],
                                !empty($GLOBALS[$this->vcf()->px('rtimageoverwrite_false')]) && IMAGE_EDIT_OVERWRITE ? 'on' : $this->vcf()->dcvalue('rtimageoverwrite')
                            );
                            ?>
                    </td>
                </tr>
                <tr id="rtwpcoreupdate">
                    <th><?php echo esc_html__('Disallows WP Auto Update Core', 'docket-cache').$this->tooltip('rtwpcoreupdate'); ?></th>
                    <td>
                        <?php
                            echo $this->config_select_set(
                                'rtwpcoreupdate',
                                [
                                    'default' => __('Default', 'docket-cache'),
                                    'off' => __('Enable', 'docket-cache'),
                                    'on' => __('Disable', 'docket-cache'),
                                ],
                                !empty($GLOBALS[$this->vcf()->px('rtwpcoreupdate_false')]) && !(bool) WP_AUTO_UPDATE_CORE ? 'on' : $this->vcf()->dcvalue('rtwpcoreupdate')
                            );
                            ?>
                    </td>
                </tr>
                <tr id="rtpluginthemeeditor">
                    <th><?php echo esc_html__('Disallows Plugin / Theme Editor', 'docket-cache').$this->tooltip('rtpluginthemeeditor'); ?></th>
                    <td>
                        <?php
                            echo $this->config_select_set(
                                'rtpluginthemeeditor',
                                [
                                    'default' => __('Default', 'docket-cache'),
                                    'on' => __('Enable', 'docket-cache'),
                                    'off' => __('Disable', 'docket-cache'),
                                ],
                                !empty($GLOBALS[$this->vcf()->px('rtpluginthemeeditor_false')]) && DISALLOW_FILE_EDIT ? 'on' : $this->vcf()->dcvalue('rtpluginthemeeditor')
                            );
                            ?>
                    </td>
                </tr>
                <tr id="rtpluginthemeinstall">
                    <th><?php echo esc_html__('Disallows Plugin / Theme Update and Installation', 'docket-cache').$this->tooltip('rtpluginthemeinstall'); ?></th>
                    <td>
                        <?php
                            echo $this->config_select_set(
                                'rtpluginthemeinstall',
                                [
                                    'default' => __('Default', 'docket-cache'),
                                    'on' => __('Enable', 'docket-cache'),
                                    'off' => __('Disable', 'docket-cache'),
                                ],
                                !empty($GLOBALS[$this->vcf()->px('rtpluginthemeinstall_false')]) && DISALLOW_FILE_MODS ? 'on' : $this->vcf()->dcvalue('rtpluginthemeinstall')
                            );
                            ?>
                    </td>
                </tr>
                <?php
                    $rtwpdebug_default = !empty($GLOBALS[$this->vcf()->px('rtwpdebug_false')]) && WP_DEBUG ? 'on' : $this->vcf()->dcvalue('rtwpdebug');
                ?>
                <tr id="rtwpdebug">
                    <th<?php echo  'off' === $rtwpdebug_default ? ' class="border-b"' : ''; ?>><?php echo esc_html__('WP Debug', 'docket-cache').$this->tooltip('rtwpdebug'); ?></th>
                        <td>
                            <?php
                            echo $this->config_select_set(
                                'rtwpdebug',
                                [
                                    'default' => __('Default', 'docket-cache'),
                                    'on' => __('Enable', 'docket-cache'),
                                    'off' => __('Disable', 'docket-cache'),
                                ],
                                $rtwpdebug_default
                            );
                            ?>
                        </td>
                </tr>
                <?php if ('on' === $rtwpdebug_default) : ?>
                <tr id="rtwpdebugdisplay">
                    <th><?php echo esc_html__('WP Debug Display', 'docket-cache').$this->tooltip('rtwpdebugdisplay'); ?></th>
                    <td>
                        <?php
                            echo $this->config_select_set(
                                'rtwpdebugdisplay',
                                [
                                    'default' => __('Default', 'docket-cache'),
                                    'on' => __('Enable', 'docket-cache'),
                                    'off' => __('Disable', 'docket-cache'),
                                ],
                                !empty($GLOBALS[$this->vcf()->px('rtwpdebugdisplay_false')]) && WP_DEBUG_DISPLAY ? 'on' : $this->vcf()->dcvalue('rtwpdebugdisplay')
                            );
                        ?>
                    </td>
                </tr>
                <?php
                    $rtwpdebuglog_default = !empty($GLOBALS[$this->vcf()->px('rtwpdebuglog_false')]) && WP_DEBUG_LOG ? 'on' : $this->vcf()->dcvalue('rtwpdebuglog');
                    $error_log = ini_get('error_log');
                    ?>
                <tr id="rtwpdebuglog">
                    <th class="border-b"><?php echo esc_html__('WP Debug Log', 'docket-cache').$this->tooltip('rtwpdebuglog'); ?></th>
                    <td>
                        <?php
                            echo $this->config_select_set(
                                'rtwpdebuglog',
                                [
                                    'default' => __('Default', 'docket-cache'),
                                    'on' => __('Enable', 'docket-cache'),
                                    'off' => __('Disable', 'docket-cache'),
                                ],
                                $rtwpdebuglog_default
                            );

                        if (\defined('WP_DEBUG') && WP_DEBUG && \defined('WP_DEBUG_LOG') && WP_DEBUG_LOG && @is_file($error_log) && is_readable($error_log)) {
                            $error_log = basename($error_log);
                            echo '<span class="wpdebuglog"><a class="btxo" title="'.$error_log.'" href="'.$this->tab_query('config', ['wplog' => '0'.time()]).'" rel="noopener" target="new"><span class="dashicons dashicons-external"></span>View Log</a></span>';
                        }
                        ?>
                    </td>
                </tr>
                <?php endif; ?>
                <tr>
                    <td colspan="2" class="stitle">
                        <?php esc_html_e('Storage Options', 'docket-cache'); ?>
                    </td>
                </tr>
                <tr id="maxfile">
                    <th><?php echo esc_html__('Cache Files Limit', 'docket-cache').$this->tooltip('maxfile'); ?></th>
                    <td>
                        <?php
                            $maxfile_default = '50K';
                        switch ($this->vcf()->dcvalue('maxfile')) {
                            case '50000':
                                $maxfile_default = '50K';
                                break;
                            case '100000':
                                $maxfile_default = '100K';
                                break;
                            case '200000':
                                $maxfile_default = '200K';
                                break;
                        }
                            echo $this->config_select_set(
                                'maxfile',
                                [
                                    'default' => __('Default', 'docket-cache'),
                                    '50K' => '50000',
                                    '100K' => '100000',
                                    '200K' => '200000',
                                ],
                                $maxfile_default
                            );
                            ?>
                    </td>
                </tr>
                <tr id="maxsize_disk">
                    <th><?php echo esc_html__('Cache Disk Limit', 'docket-cache').$this->tooltip('maxsize_disk'); ?></th>
                    <td>
                        <?php
                            $maxsize_disk_default = '500M';
                        switch ($this->vcf()->dcvalue('maxsize_disk')) {
                            case '524288000':
                                $maxsize_disk_default = '500M';
                                break;
                            case '1073741824':
                                $maxsize_disk_default = '1G';
                                break;
                            case '2147483648':
                                $maxsize_disk_default = '2G';
                                break;
                        }
                            echo $this->config_select_set(
                                'maxsize_disk',
                                [
                                    'default' => __('Default', 'docket-cache'),
                                    '500M' => '500M',
                                    '1G' => '1G',
                                    '2G' => '2G',
                                ],
                                $maxsize_disk_default
                            );
                            ?>
                    </td>
                </tr>
                <tr id="chunkcachedir">
                    <th><?php echo esc_html__('Chunk Cache Directory', 'docket-cache').$this->tooltip('chunkcachedir'); ?></th>
                    <td>
                        <?php echo $this->config_select_bool('chunkcachedir'); ?>
                    </td>
                </tr>
                <tr id="flush_stalecache">
                    <th class="border-b"><?php echo esc_html__('Auto Remove Stale Cache', 'docket-cache').$this->tooltip('flush_stalecache'); ?></th>
                    <td>
                        <?php echo $this->config_select_bool('flush_stalecache'); ?>
                    </td>
                </tr>
                <tr>
                    <td colspan="2" class="stitle">
                        <?php esc_html_e('Admin Interface', 'docket-cache'); ?>
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
                    <th><?php echo esc_html__('Garbage Collector Action Button', 'docket-cache').$this->tooltip('gcaction'); ?></th>
                    <td>
                        <?php echo $this->config_select_bool('gcaction'); ?>
                    </td>
                </tr>
                <tr id="flushaction">
                    <th class="border-b"><?php echo esc_html__('Additional Flush Cache Action Button', 'docket-cache').$this->tooltip('flushaction'); ?></th>
                    <td>
                        <?php echo $this->config_select_bool('flushaction'); ?>
                    </td>
                </tr>
                <tr>
                    <td colspan="2" class="stitle">
                        <?php esc_html_e('Plugin Options', 'docket-cache'); ?>
                    </td>
                </tr>
                <tr id="autoupdate">
                    <th><?php echo esc_html__('Docket Cache Auto-Updates', 'docket-cache').$this->tooltip('autoupdate'); ?></th>
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
                <tr id="flush_shutdown">
                    <th><?php echo esc_html__('Flush Object Cache During Deactivation', 'docket-cache').$this->tooltip('flush_shutdown'); ?></th>
                    <td>
                        <?php echo $this->config_select_bool('flush_shutdown'); ?>
                    </td>
                </tr>
                <tr id="opcshutdown">
                    <th><?php echo esc_html__('Flush OPcache During Deactivation', 'docket-cache').$this->tooltip('opcshutdown'); ?></th>
                    <td>
                        <?php echo $this->config_select_bool('opcshutdown'); ?>
                    </td>
                </tr>
            </table>
        </div>
        <div class="row-right">
            <?php $this->render('resource'); ?>
        </div>
    </div>
</div>
