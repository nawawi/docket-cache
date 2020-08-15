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
<?php $this->tab_title('<span class="dashicons dashicons-rest-api"></span>&nbsp;'.esc_html__('Docket Connect', 'docket-cache'), false, 'text-capitalize'); ?>
<div class="postbox">
    <div class="inside">
        <form name="connector" method="post">
            <input type="hidden" name="action" value="connector-signin">
            <?php wp_nonce_field('connectorsignin'); ?>
            <table class="connector-table">
                <tr>
                    <th>Status</th>
                    <td>Not Connected</td>
                </tr>
                <tr>
                    <th>Cron Service</th>
                    <td>Not Available</td>
                </tr>
                <tr>
                    <th>Crawler Service</th>
                    <td>Not Available</td>
                </tr>

                <tr>
                    <td colspan="2">
                        <hr>
                    </td>
                </tr>

                <tr>
                    <th>API Email</th>
                    <td><input type="text" name="cemail" class="api"></td>
                </tr>

                <tr>
                    <th>API Secret</th>
                    <td><input type="text" name="cemail" class="api"></td>
                </tr>

                <tr>
                    <th></th>
                    <td><input type="submit" name="submit" value="Connect" class="button button-primary button-small btapi"></td>
                </tr>

                <tr>
                    <th></th>
                    <td><a href="https://docketcache.com/docketregister" rel="noopener" target="new">Register Account</a></td>
                </tr>

            </table>

        </form>
    </div>
</div>