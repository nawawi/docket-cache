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

$log = $this->parse_query();
?>

<div class="section<?php echo !$log->output_empty ? ' log' : ''; ?>">
    <h2 class="title"><?php _e('Cache Log ', 'docket-cache'); ?></h2>

    <table class="form-table">
        <tr>
            <th><?php _e('Status', 'docket-cache'); ?></th>
            <td><?php echo $this->info->log_enable_text; ?></td>
        </tr>
        <tr>
            <th><?php _e('File', 'docket-cache'); ?></th>
            <td><?php echo $this->info->log_file; ?></td>
        </tr>
        <?php if ($log->output_empty) : ?>
        <tr>
            <th><?php _e('Data', 'docket-cache'); ?></th>
            <td><?php _e('Not available', 'docket-cache'); ?></td>
        </tr>
        <?php else : ?>
        <tr>
            <th class="border"><?php _e('Size', 'docket-cache'); ?></th>
            <td><?php echo $this->plugin->get_logsize(); ?></td>
        </tr>
        <tr>
            <td colspan="2">
                <textarea id="log" class="code" readonly="readonly" rows="<?php echo $log->output_size < 15 ? $log->output_size : 15; ?>" wrap="off"><?php echo implode("\n", 'desc' === $log->default_sort ? array_reverse($log->output, true) : $log->output); ?></textarea>
            </td>
        </tr>
        <?php endif; ?>
    </table>

    <p class="submit">
        <?php if (!$log->output_empty) : ?>
        <select id="order">
            <?php
            foreach ([
                'first' => __('FIRST', 'docket-cache'),
                'last' => __('LAST', 'docket-cache'),
            ] as $order => $text) {
                $selected = ($order === $log->default_order ? ' selected' : '');
                echo '<option value="'.$order.'"'.$selected.'>'.esc_html($text).'</option>';
            }
            ?>
        </select>
        <select id="line">
            <?php
            foreach (['10', '50', '100', '300', '500'] as $line) {
                $selected = ((int) $line === $log->default_line ? ' selected' : '');
                echo '<option value="'.$line.'"'.$selected.'>'.$line.'</option>';
            }
            ?>
        </select>
        <select id="sort">
            <?php
            foreach ([
                'asc' => __('ASCENDING', 'docket-cache'),
                'desc' => __('DESCENDING', 'docket-cache'),
            ] as $sort => $text) {
                $selected = ($sort === $log->default_sort ? ' selected' : '');
                $text = esc_html($text);
                if (\in_array($text, ['ASCENDING', 'DESCENDING'])) {
                    $text = 'desc' === $sort ? substr($text, 0, 4) : substr($text, 0, 3);
                }
                echo '<option value="'.$sort.'"'.$selected.'>'.$text.'</option>';
            }
            ?>
        </select>
        <br>
        <a href="<?php echo $this->plugin->action_query('flush-oclog', ['idx' => 'log']); ?>" class="button button-primary button-large"><?php _e('Flush Log', 'docket-cache'); ?></a>&nbsp;
        <?php endif; ?>

        <?php if ($this->info->log_enable || !$log->output_empty) : ?>
        <a href="<?php echo $this->tab_query('log'); ?>" class="button button-secondary button-refresh  button-large" id="refresh"><?php _e('Refresh', 'docket-cache'); ?></a>
        <?php endif; ?>
    </p>
</div>