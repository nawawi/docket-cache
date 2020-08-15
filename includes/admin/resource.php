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
<?php $this->tab_title('<span class="dashicons dashicons-share"></span>&nbsp;'.esc_html__('Resources', 'docket-cache'), false, 'text-capitalize'); ?>
<div class="postbox">
    <div class="inside">
        <div>
            <p>
                <?php esc_html_e('The Docket Cache keeps the admin interface clean and simple as possible, predefined configuration and works out-of-the-box.', 'docket-cache'); ?>
            </p>
            <hr>
            <p>
                <strong><?php esc_html_e('CONSTANTS', 'docket-cache'); ?></strong><br class="break">
                <?php
                    /* translators: %s: <a href="https://github.com/nawawi/docket-cache/wiki/Constants" rel="noopener" target="new">Configuration Wiki</a> */
                    printf(esc_html__('This plugin uses constants variable as main configuration methods. To adjust the plugin behavior, please refer to %s page for details.', 'docket-cache'), '<a href="https://github.com/nawawi/docket-cache/wiki/Constants" rel="noopener" target="new">Configuration Wiki</a>');
                ?>
            </p>
            <p>
                <strong>WP-CLI</strong><br class="break">
                <?php
                    /* translators: %s: <a href="https://github.com/nawawi/docket-cache/wiki/WP-CLI" rel="noopener" target="new">WP-CLI Wiki</a> */
                    printf(esc_html__('You can manage this plugin through command line, please refer to %s page for available commands.', 'docket-cache'), '<a href="https://github.com/nawawi/docket-cache/wiki/WP-CLI" rel="noopener" target="new">WP-CLI Wiki</a>');
                ?>
            </p>
            <hr>
            <p>
                <?php esc_html_e('If Docket Cache beneficial to your website performance, it’s more than thank you if you can leave a review about your experience.', 'docket-cache'); ?><br>
                <a href="https://wordpress.org/support/plugin/docket-cache/reviews/" rel="noopener" target="new"><?php esc_html_e('Write your review.', 'docket-cache'); ?></a>
            </p>
        </div>


    </div>

</div>