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
$event_list = new EventList();
$event_list->prepare_items();
$utc_offset = '('.$event_list->get_utc_offset().')';
$total_page = $event_list->get_pagination_arg('total_pages');

$is_connected = $this->is_cronbot_connected();
$ping_last = esc_html__('Not Available', 'docket-cache');
$ping_next = $ping_last;
$ping_data = $this->ping_next();
if (!empty($ping_data)) :
    $ping_next = $ping_data['next'].' '.$utc_offset;
    $ping_last = $ping_data['last'].' '.$utc_offset;
endif;
?>
<div class="section cronbot">
    <?php $this->tab_title(esc_html__('Cronbot', 'docket-cache')); ?>
    <table class="form-table">
        <tr>
            <th><?php esc_html_e('Service Status', 'docket-cache'); ?></th>
            <td class="<?php echo $is_connected ? 'text-green' : 'text-red'; ?>"><?php echo $is_connected ? esc_html__('Connected', 'docket-cache') : esc_html__('Not Connected', 'docket-cache'); ?></td>
        </tr>

        <tr>
            <th><?php esc_html_e('Last Received Ping', 'docket-cache'); ?></th>
            <td><?php echo $ping_last; ?></td>
        </tr>
        <?php if ($is_connected) : ?>
        <tr>
            <th><?php esc_html_e('Next Expecting Ping', 'docket-cache'); ?></th>
            <td><?php echo $ping_next; ?></td>
        </tr>
        <?php endif; ?>
    </table>

    <p class="submit">
        <?php if ($is_connected) : ?>
        <a href="<?php echo $this->plugin->action_query('disconnect-cronbot', ['idx' => 'cronbot']); ?>" class="button button-secondary button-large"><?php esc_html_e('Disconnect', 'docket-cache'); ?></a>
        <a href="<?php echo $this->plugin->action_query('connect-cronbot', ['idx' => 'cronbot']); ?>" class="button button-secondary button-large"><?php esc_html_e('Test ping', 'docket-cache'); ?></a>
        <?php else : ?>
        <a href="<?php echo $this->plugin->action_query('connect-cronbot', ['idx' => 'cronbot']); ?>" class="button button-primary button-large"><?php esc_html_e('Connect', 'docket-cache'); ?></a>
        <?php endif; ?>
    </p>

    <?php $this->tab_title(esc_html__('Cron Events', 'docket-cache'), false); ?>
    <div class="eventlist">

        <div class="box-left">
            <a href="<?php echo $this->plugin->action_query('runevent-cronbot', ['idx' => 'cronbot']); ?>" class="button button-secondary"><?php esc_html_e('Run WP-CRON', 'docket-cache'); ?></a>
        </div>

        <?php if ($total_page > 1 || !empty($_GET['s'])) : ?>
        <div class="box-right">
            <form id="events-filter" method="get" action="<?php echo $this->plugin->page; ?>">
                <input type="hidden" name="page" value="docket-cache">
                <input type="hidden" name="idx" value="cronbot">
                <?php $event_list->search_box(__('Filter Hook Names', 'docket-cache'), 'eventlist-event'); ?>
            </form>
        </div>
        <?php endif; ?>
        <div class="table-responsive">
            <?php $event_list->display(); ?>
        </div>
    </div>

</div>