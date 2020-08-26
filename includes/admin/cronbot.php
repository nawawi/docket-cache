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

$is_connected = $this->is_cronbot_connected();
$ping_last = esc_html__('Not Available', 'docket-cache');
$ping_next = $ping_last;
$ping_data = $this->ping_next();
if (!empty($ping_data)) {
    $ping_next = $ping_data['next'];
    $ping_last = $ping_data['last'];
}
?>
<div class="section cronbot">
    <?php $this->tab_title(esc_html__('Cronbot', 'docket-cache')); ?>
    <table class="form-table">
        <tr>
            <th><?php esc_html_e('Service Status', 'docket-cache'); ?></th>
            <td><?php echo $is_connected ? esc_html__('Connected', 'docket-cache') : esc_html__('Not Connected', 'docket-cache'); ?></td>
        </tr>

        <tr>
            <th><?php esc_html_e('Last Received Ping', 'docket-cache'); ?></th>
            <td><?php echo $ping_last; ?></td>
        </tr>

        <tr>
            <th><?php esc_html_e('Next Expecting Ping', 'docket-cache'); ?></th>
            <td><?php echo $ping_next; ?></td>
        </tr>

    </table>

    <p class="submit">
        <?php if ($is_connected) : ?>
        <a href="<?php echo $this->plugin->action_query('disconnect-cronbot', ['idx' => 'cronbot']); ?>" class="button button-secondary button-large"><?php esc_html_e('Disconnect', 'docket-cache'); ?></a>
        <?php else : ?>
        <a href="<?php echo $this->plugin->action_query('connect-cronbot', ['idx' => 'cronbot']); ?>" class="button button-primary button-large"><?php esc_html_e('Connect', 'docket-cache'); ?></a>
        <?php endif; ?>
    </p>
</div>