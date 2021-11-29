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
$event_list = $this->cronbot_eventlist();
if (\is_object($event_list)) :
    $utc_offset = '('.$this->pt->get_utc_offset().')';
    $total_page = $event_list->get_pagination_arg('total_pages');

    $is_connected = $this->is_cronbot_connected();
    $ping_last = esc_html__('Not Available', 'docket-cache');
    $ping_next = $ping_last;
    $ping_data = $this->ping_next();
    if (!empty($ping_data)) :
        $ping_next = $ping_data['next'].' '.$utc_offset;
        $ping_last = $ping_data['last'].' '.$utc_offset;
    endif;
endif;
?>
<div class="section cronbot">
    <div class="flex-container">
        <div class="row">
            <?php $this->tab_title(esc_html__('Cronbot', 'docket-cache')); ?>
            <?php
            if (!\is_object($event_list)) :
                echo Resc::boxmsg(__('Failed to load EventList()', 'docket-cache'), 'error', false, true, false);
            else :
                ?>
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
                <a href="<?php echo $this->pt->action_query('disconnect-cronbot', ['idx' => 'cronbot']); ?>" class="button button-secondary button-large btx-spinner"><?php esc_html_e('Disconnect', 'docket-cache'); ?></a>
                <a href="<?php echo $this->pt->action_query('pong-cronbot', ['idx' => 'cronbot']); ?>" class="button button-secondary button-large btx-spinner"><?php esc_html_e('Test Ping', 'docket-cache'); ?></a>
                <?php else : ?>
                <a href="<?php echo $this->pt->action_query('connect-cronbot', ['idx' => 'cronbot']); ?>" class="button button-primary button-large btx-spinner"><?php esc_html_e('Connect', 'docket-cache'); ?></a>
                <?php endif; ?>
            </p>

            <?php $this->tab_title(esc_html__('Cron Events', 'docket-cache'), 'title-below'); ?>
            <table class="form-table">
                <?php
                $title = esc_html__('Cron Events For Site', 'docket-cache');
                $sites = $this->pt->get_network_sites();
                if (is_multisite() && !empty($sites) && \is_array($sites) && \count($sites) > 1) :
                    ?>
                <tr class="form-table-selection">
                    <th><?php echo $title; ?></th>
                    <td><select id="siteid" class="config-select">
                            <?php
                            $cronbot_siteid = (int) $this->pt->get_cron_siteid();

                            foreach ($sites as $site) {
                                $site_id = (int) $site['id'];
                                $site_url = $site['url'];
                                $v = '['.$site_id.'] '.nwdcx_noscheme($site_url);
                                $url = $this->pt->action_query(
                                    'selectsite-cronbot',
                                    [
                                        'idx' => 'cronbot',
                                        'nv' => $site_id,
                                    ]
                                );
                                $selected = $site_id === $cronbot_siteid ? ' selected' : '';
                                echo '<option value="'.$site_id.'" data-action-link="'.$url.'"'.$selected.'>'.$v.'</option>';
                            }
                            ?>
                        </select>
                        <small>[SiteId] Hostname</small>
                    </td>
                </tr>
                <?php else : ?>
                <tr>
                    <th><?php echo $title; ?></th>
                    <td><?php echo nwdcx_noscheme(home_url()); ?></td>
                </tr>
                <?php endif; ?>
            </table>
            <div class="gridlist">

                <div class="box-left">
                    <a href="
                    <?php
                    echo $this->pt->action_query(
                        'runevent-cronbot',
                        [
                            'idx' => 'cronbot',
                        ]
                    );
                    ?>
                    " class="button button-secondary button-large btx-spinner"><?php esc_html_e('Run Scheduled Event', 'docket-cache'); ?></a>
                    <a href="
                    <?php
                    echo $this->pt->action_query(
                        'runeventnow-cronbot',
                        [
                            'idx' => 'cronbot',
                        ]
                    );
                    ?>
                    " class="button button-secondary  button-large btx-spinner"><?php esc_html_e('Run All Now', 'docket-cache'); ?></a>
                </div>

                <?php if ($total_page > 1 || !empty($_GET['s'])) : ?>
                <div class="box-right">
                    <form id="events-filter" method="get" action="<?php echo esc_url($this->pt->get_page()); ?>">
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
            <?php endif; // is_object?>
        </div>
    </div>
</div>
