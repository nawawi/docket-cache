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
                        <?php $this->opt_title('cronbot'); ?>
                    </th>
                    <td>
                        <?php $this->config_select_bool_e('cronbot'); ?>
                    </td>
                </tr>
                <tr id="opcviewer">
                    <th>
                        <?php $this->opt_title('opcviewer'); ?>
                    </th>
                    <td>
                        <?php $this->config_select_bool_e('opcviewer'); ?>
                    </td>
                </tr>

                <?php $this->render('@inc:features'); ?>

                <tr id="log">
                    <th class="border-b">
                        <?php $this->opt_title('log'); ?>
                    </th>
                    <td>
                        <?php $this->config_select_bool_e('log'); ?>
                    </td>
                </tr>
                <tr>
                    <td colspan="2" class="stitle">
                        <?php esc_html_e('Cache Options', 'docket-cache'); ?>
                    </td>
                </tr>
                <?php if (version_compare($GLOBALS['wp_version'], '6.1', '<')) : ?>
                <tr id="advpost">
                    <th><?php $this->opt_title('advcpost'); ?></th>
                    <td>
                        <?php $this->config_select_bool_e('advcpost'); ?>
                    </td>
                </tr>
                <?php if ($this->vcf()->is_dctrue('advcpost')) : ?>
                <tr id="advpost_posttype_all">
                    <th><?php $this->opt_title('advpost_posttype_all'); ?></th>
                    <td>
                        <?php $this->config_select_bool_e('advpost_posttype_all'); ?>
                    </td>
                </tr>
                <?php endif; ?>
                <?php endif; // wp_version?>
                <tr id="precache">
                    <th><?php $this->opt_title('precache'); ?></th>
                    <td>
                        <?php $this->config_select_bool_e('precache'); ?>
                    </td>
                </tr>
                <tr id="menucache">
                    <th><?php $this->opt_title('menucache'); ?></th>
                    <td>
                        <?php $this->config_select_bool_e('menucache'); ?>
                    </td>
                </tr>
                <tr id="mocache">
                    <th><?php $this->opt_title('mocache'); ?></th>
                    <td>
                        <?php $this->config_select_bool_e('mocache'); ?>
                    </td>
                </tr>
                <tr id="preload">
                    <th><?php $this->opt_title('preload'); ?></th>
                    <td>
                        <?php $this->config_select_bool_e('preload'); ?>
                    </td>
                </tr>
                <tr id="transientdb">
                    <th class="border-b"><?php $this->opt_title('transientdb'); ?></th>
                    <td>
                        <?php $this->config_select_bool_e('transientdb'); ?>
                    </td>
                </tr>
                <tr>
                    <td colspan="2" class="stitle">
                        <?php esc_html_e('Optimisations', 'docket-cache'); ?>
                    </td>
                </tr>
                <tr id="optwpquery">
                    <th><?php $this->opt_title('optwpquery'); ?></th>
                    <td>
                        <?php $this->config_select_bool_e('optwpquery'); ?>
                    </td>
                </tr>
                <tr id="optermcount">
                    <th><?php $this->opt_title('optermcount'); ?></th>
                    <td>
                        <?php $this->config_select_bool_e('optermcount'); ?>
                    </td>
                </tr>
                <tr id="cronoptmzdb">
                    <th><?php $this->opt_title('cronoptmzdb'); ?></th>
                    <td>
                        <?php
                        $this->config_select_set_e(
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
                    <th><?php $this->opt_title('wpoptaload'); ?></th>
                    <td>
                        <?php $this->config_select_bool_e('wpoptaload'); ?>
                    </td>
                </tr>
                <tr id="postmissedschedule">
                    <th><?php $this->opt_title('postmissedschedule'); ?></th>
                    <td>
                        <?php $this->config_select_bool_e('postmissedschedule'); ?>
                    </td>
                </tr>
                <tr id="limitbulkedit">
                    <th><?php $this->opt_title('limitbulkedit'); ?></th>
                    <td>
                        <?php $this->config_select_bool_e('limitbulkedit'); ?>
                    </td>
                </tr>
                <tr id="misc_tweaks">
                    <th class="border-b"><?php $this->opt_title('misc_tweaks'); ?></th>
                    <td>
                        <?php $this->config_select_bool_e('misc_tweaks'); ?>
                    </td>
                </tr>
                <tr>
                    <td colspan="2" class="stitle">
                        <?php esc_html_e('Woo Tweaks', 'docket-cache'); ?>
                    </td>
                </tr>
                <tr id="wootweaks">
                    <th><?php $this->opt_title('wootweaks'); ?></th>
                    <td>
                        <?php $this->config_select_bool_e('wootweaks'); ?>
                    </td>
                </tr>
                <tr id="wooadminoff">
                    <th><?php $this->opt_title('wooadminoff'); ?></th>
                    <td>
                        <?php $this->config_select_bool_e('wooadminoff'); ?>
                    </td>
                </tr>
                <tr id="woowidgetoff">
                    <th><?php $this->opt_title('woowidgetoff'); ?></th>
                    <td>
                        <?php $this->config_select_bool_e('woowidgetoff'); ?>
                    </td>
                </tr>
                <tr id="woowpdashboardoff">
                    <th><?php $this->opt_title('woowpdashboardoff'); ?></th>
                    <td>
                        <?php $this->config_select_bool_e('woowpdashboardoff'); ?>
                    </td>
                </tr>
                <tr id="wooextensionpageoff">
                    <th><?php $this->opt_title('wooextensionpageoff'); ?></th>
                    <td>
                        <?php $this->config_select_bool_e('wooextensionpageoff'); ?>
                    </td>
                </tr>
                <tr id="woocartfragsoff">
                    <th><?php $this->opt_title('woocartfragsoff'); ?></th>
                    <td>
                        <?php $this->config_select_bool_e('woocartfragsoff'); ?>
                    </td>
                </tr>
                <tr id="wooaddtochartcrawling">
                    <th class="border-b"><?php $this->opt_title('wooaddtochartcrawling'); ?></th>
                    <td>
                        <?php $this->config_select_bool_e('wooaddtochartcrawling'); ?>
                    </td>
                </tr>
                <tr>
                    <td colspan="2" class="stitle">
                        <?php esc_html_e('WP Tweaks', 'docket-cache'); ?>
                    </td>
                </tr>
                <tr id="headerjunk">
                    <th><?php $this->opt_title('headerjunk'); ?></th>
                    <td>
                        <?php $this->config_select_bool_e('headerjunk'); ?>
                    </td>
                </tr>
                <tr id="pingback">
                    <th><?php $this->opt_title('pingback'); ?></th>
                    <td>
                        <?php $this->config_select_bool_e('pingback'); ?>
                    </td>
                </tr>
                <tr id="wpemoji">
                    <th><?php $this->opt_title('wpemoji'); ?></th>
                    <td>
                        <?php $this->config_select_bool_e('wpemoji'); ?>
                    </td>
                </tr>
                <tr id="wpfeed">
                    <th><?php $this->opt_title('wpfeed'); ?></th>
                    <td>
                        <?php $this->config_select_bool_e('wpfeed'); ?>
                    </td>
                </tr>
                <tr id="wpembed">
                    <th><?php $this->opt_title('wpembed'); ?></th>
                    <td>
                        <?php $this->config_select_bool_e('wpembed'); ?>
                    </td>
                </tr>
                <tr id="wplazyload">
                    <th><?php $this->opt_title('wplazyload'); ?></th>
                    <td>
                        <?php $this->config_select_bool_e('wplazyload'); ?>
                    </td>
                </tr>
                <tr id="wpsitemap">
                    <th><?php $this->opt_title('wpsitemap'); ?></th>
                    <td>
                        <?php $this->config_select_bool_e('wpsitemap'); ?>
                    </td>
                </tr>
                <tr id="wpapppassword">
                    <th><?php $this->opt_title('wpapppassword'); ?></th>
                    <td>
                        <?php $this->config_select_bool_e('wpapppassword'); ?>
                    </td>
                </tr>
                <tr id="wpdashboardnews">
                    <th><?php $this->opt_title('wpdashboardnews'); ?></th>
                    <td>
                        <?php $this->config_select_bool_e('wpdashboardnews'); ?>
                    </td>
                </tr>
                <tr id="postviaemail">
                    <th><?php $this->opt_title('postviaemail'); ?></th>
                    <td>
                        <?php $this->config_select_bool_e('postviaemail'); ?>
                    </td>
                </tr>
                <tr id="wpbrowsehappy">
                    <th><?php $this->opt_title('wpbrowsehappy'); ?></th>
                    <td>
                        <?php $this->config_select_bool_e('wpbrowsehappy'); ?>
                    </td>
                </tr>
                <tr id="wpservehappy">
                    <th><?php $this->opt_title('wpservehappy'); ?></th>
                    <td>
                        <?php $this->config_select_bool_e('wpservehappy'); ?>
                    </td>
                </tr>
                <tr id="limithttprequest">
                    <th><?php $this->opt_title('limithttprequest'); ?></th>
                    <td>
                        <?php $this->config_select_bool_e('limithttprequest'); ?>
                    </td>
                </tr>
                <?php if (version_compare($GLOBALS['wp_version'], '5.8', '<')) : ?>
                <tr id="httpheadersexpect">
                    <th class="border-b"><?php $this->opt_title('httpheadersexpect'); ?></th>
                    <td>
                        <?php $this->config_select_bool_e('httpheadersexpect'); ?>
                    </td>
                </tr>
                <?php endif; ?>
                <?php if (is_main_network()) : ?>
                <tr>
                    <td colspan="2" class="stitle">
                        <?php esc_html_e('Runtime Options', 'docket-cache'); ?>
                    </td>
                </tr>
                <tr id="rtpostautosave">
                    <th><?php $this->opt_title('rtpostautosave'); ?></th>
                    <td>
                        <?php
$this->config_select_set_e(
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
                    <th><?php $this->opt_title('rtpostrevision'); ?></th>
                    <td>
                        <?php
                    $this->config_select_set_e(
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
                    <th><?php $this->opt_title('rtpostemptytrash'); ?></th>
                    <td>
                        <?php
                    $this->config_select_set_e(
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
                    <th><?php $this->opt_title('rtimageoverwrite'); ?></th>
                    <td>
                        <?php
                    $this->config_select_set_e(
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
                    <th><?php $this->opt_title('rtwpcoreupdate'); ?></th>
                    <td>
                        <?php
                    $this->config_select_set_e(
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
                    <th><?php $this->opt_title('rtpluginthemeeditor'); ?></th>
                    <td>
                        <?php
                    $this->config_select_set_e(
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
                    <th><?php $this->opt_title('rtpluginthemeinstall'); ?></th>
                    <td>
                        <?php
                    $this->config_select_set_e(
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
                <tr id="rtconcatenatescripts">
                    <th><?php $this->opt_title('rtconcatenatescripts'); ?></th>
                    <td>
                        <?php
                    $this->config_select_set_e(
                        'rtconcatenatescripts',
                        [
                            'default' => __('Default', 'docket-cache'),
                            'on' => __('Enable', 'docket-cache'),
                            'off' => __('Disable', 'docket-cache'),
                        ],
                        !empty($GLOBALS[$this->vcf()->px('rtconcatenatescripts_false')]) && !(bool) CONCATENATE_SCRIPTS ? 'on' : $this->vcf()->dcvalue('rtconcatenatescripts')
                    );
                    ?>
                    </td>
                </tr>
                <tr id="rtdisablewpcron">
                    <th class="border-b"><?php $this->opt_title('rtdisablewpcron'); ?></th>
                    <td>
                        <?php
                    $this->config_select_set_e(
                        'rtdisablewpcron',
                        [
                            'default' => __('Default', 'docket-cache'),
                            'on' => __('Enable', 'docket-cache'),
                            'off' => __('Disable', 'docket-cache'),
                        ],
                        !empty($GLOBALS[$this->vcf()->px('rtdisablewpcron_false')]) && DISABLE_WP_CRON ? 'on' : $this->vcf()->dcvalue('rtdisablewpcron')
                    );
                    ?>
                    </td>
                </tr>
                <?php
                    $rtwpdebug_default = !empty($GLOBALS[$this->vcf()->px('rtwpdebug_false')]) && WP_DEBUG ? 'on' : $this->vcf()->dcvalue('rtwpdebug');
                    ?>
                <tr id="rtwpdebug">
                    <th<?php echo  'off' === $rtwpdebug_default ? ' class="border-b"' : ''; ?>><?php $this->opt_title('rtwpdebug'); ?></th>
                        <td>
                            <?php
                                $this->config_select_set_e(
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
                    <th><?php $this->opt_title('rtwpdebugdisplay'); ?></th>
                    <td>
                        <?php
                        $this->config_select_set_e(
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
					    $error_log = \ini_get('error_log');
					    ?>
                <tr id="rtwpdebuglog">
                    <th class="border-b"><?php $this->opt_title('rtwpdebuglog'); ?></th>
                    <td>
                        <?php
					        $this->config_select_set_e(
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
                <?php endif; // is_main_network?>

                <tr>
                    <td colspan="2" class="stitle">
                        <?php esc_html_e('Storage Options', 'docket-cache'); ?>
                    </td>
                </tr>
                <tr id="maxfile">
                    <th><?php $this->opt_title('maxfile'); ?></th>
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
$this->config_select_set_e(
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
                    <th><?php $this->opt_title('maxsize_disk'); ?></th>
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
$this->config_select_set_e(
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
                    <th><?php $this->opt_title('chunkcachedir'); ?></th>
                    <td>
                        <?php $this->config_select_bool_e('chunkcachedir'); ?>
                    </td>
                </tr>
                <tr id="maxfile_livecheck">
                    <th><?php $this->opt_title('maxfile_livecheck'); ?></th>
                    <td>
                        <?php $this->config_select_bool_e('maxfile_livecheck'); ?>
                    </td>
                </tr>
                <tr id="flush_stalecache">
                    <th><?php $this->opt_title('flush_stalecache'); ?></th>
                    <td>
                        <?php $this->config_select_bool_e('flush_stalecache'); ?>
                    </td>
                </tr>
                <?php
                /*
                <tr id="stalecache_ignore">
                    <th><?php $this->opt_title('stalecache_ignore'); ?></th>
                <td>
                    <?php $this->config_select_bool_e('stalecache_ignore'); ?>
                </td>
                </tr>*/
                ?>
                <tr id="emptycache_ignore">
                    <th class="border-b"><?php $this->opt_title('emptycache_ignore'); ?></th>
                    <td>
                        <?php $this->config_select_bool_e('emptycache_ignore'); ?>
                    </td>
                </tr>
                <tr>
                    <td colspan="2" class="stitle">
                        <?php esc_html_e('Admin Interface', 'docket-cache'); ?>
                    </td>
                </tr>
                <tr id="pageloader">
                    <th><?php $this->opt_title('pageloader'); ?></th>
                    <td>
                        <?php $this->config_select_bool_e('pageloader'); ?>
                    </td>
                </tr>
                <tr id="stats">
                    <th><?php $this->opt_title('stats'); ?></th>
                    <td>
                        <?php $this->config_select_bool_e('stats'); ?>
                    </td>
                </tr>
                <tr id="gcaction">
                    <th><?php $this->opt_title('gcaction'); ?></th>
                    <td>
                        <?php $this->config_select_bool_e('gcaction'); ?>
                    </td>
                </tr>
                <tr id="flushaction">
                    <th class="border-b"><?php $this->opt_title('flushaction'); ?></th>
                    <td>
                        <?php $this->config_select_bool_e('flushaction'); ?>
                    </td>
                </tr>
                <tr>
                    <td colspan="2" class="stitle">
                        <?php esc_html_e('Plugin Options', 'docket-cache'); ?>
                    </td>
                </tr>
                <tr id="autoupdate_toggle">
                    <th><?php $this->opt_title('autoupdate_toggle'); ?></th>
                    <td>
                        <?php $this->config_select_bool_e('autoupdate_toggle'); ?>
                    </td>
                </tr>
                <tr id="checkversion">
                    <th><?php $this->opt_title('checkversion'); ?></th>
                    <td>
                        <?php $this->config_select_bool_e('checkversion'); ?>
                    </td>
                </tr>
                <tr id="flush_shutdown">
                    <th><?php $this->opt_title('flush_shutdown'); ?></th>
                    <td>
                        <?php $this->config_select_bool_e('flush_shutdown'); ?>
                    </td>
                </tr>
                <tr id="opcshutdown">
                    <th><?php $this->opt_title('opcshutdown'); ?></th>
                    <td>
                        <?php $this->config_select_bool_e('opcshutdown'); ?>
                    </td>
                </tr>
            </table>
        </div>
        <div class="row-right">
            <?php $this->render('resource'); ?>
        </div>
    </div>
</div>
