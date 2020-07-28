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
    <h2 class="title"><?php _e('Configuration', 'docket-cache'); ?></h2>

    <h3 class="title"><?php _e('Options', 'docket-cache'); ?></h3>
    <p class="desc"><?php _e('This option allows you to quickly change basic configuration, overwrites by constants if defined.', 'docket-cache'); ?></p>
    <table class="form-table noborder-b form-table-selection">
        <tr>
            <th><?php _e('Cache Log', 'docket-cache'); ?></th>
            <td>
                <?php echo $this->config_select_bool('log', DOCKET_CACHE_LOG); ?>
            </td>
        </tr>
        <tr>
            <th><?php _e('Cache Log Time Format', 'docket-cache'); ?></th>
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
        <tr>
            <th><?php _e('Cache Preloading', 'docket-cache'); ?></th>
            <td>
                <?php echo $this->config_select_bool('preload', DOCKET_CACHE_PRELOAD); ?>
            </td>
        </tr>
        <tr>
            <th><?php _e('Advanced Post Caching', 'docket-cache'); ?></th>
            <td>
                <?php echo $this->config_select_bool('advcpost', DOCKET_CACHE_ADVCPOST); ?>
            </td>
        </tr>
        <tr>
            <th><?php _e('Optimize Term Count Queries', 'docket-cache'); ?></th>
            <td>
                <?php echo $this->config_select_bool('optermcount', DOCKET_CACHE_OPTERMCOUNT); ?>
            </td>
        </tr>
        <tr>
            <th><?php _e('WordPress Translation Caching', 'docket-cache'); ?></th>
            <td>
                <?php echo $this->config_select_bool('mocache', DOCKET_CACHE_MOCACHE); ?>
            </td>
        </tr>
        <tr>
            <th><?php _e('Misc Performance Tweaks', 'docket-cache'); ?></th>
            <td>
                <?php echo $this->config_select_bool('misc_tweaks', DOCKET_CACHE_MISC_TWEAKS); ?>
            </td>
        </tr>
        <tr>
            <th><?php _e('Admin Page Loader', 'docket-cache'); ?></th>
            <td>
                <?php echo $this->config_select_bool('pageloader', DOCKET_CACHE_PAGELOADER); ?>
            </td>
        </tr>
    </table>

    <br class="break">
    <h3 class="title"><?php _e('Constants', 'docket-cache'); ?></h3>
    <p class="desc">
        <?php
            /* translators: %s: link to wp-config.php  */
            printf(__('The following PHP constants can be defined in your %s file, in order to change the default behavior.', 'docket-cache'), '<a href="https://wordpress.org/support/article/editing-wp-config-php/" rel="noopener" target="_blank">wp-config.php</a>');
        ?>
    </p>

    <table class="form-table table-scroll noborder-b">
        <?php
        foreach ($this->constants_desc() as $key => $texts) :
            ?>
        <tr>
            <th class="const"><?php echo $key; ?></th>
            <td>
                <p>
                    <?php echo $texts[0]; ?>
                </p>
                <small><?php echo $texts[1]; ?></small>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>