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

$info = (object) $this->plugin->get_info();

$do_preload = false;
if (1 === $info->status_code && isset($this->plugin->token)) {
    switch ($this->plugin->token) {
        case 'docket-cache-flushed':
            $this->plugin->flush_cache();
            $do_preload = true;
            break;
        case 'docket-cache-enabled':
            $do_preload = true;
            break;
        case 'docket-log-flushed':
            $this->plugin->flush_log();
            break;
    }
    if ((!\defined('DOCKET_CACHE_PRELOAD') || !DOCKET_CACHE_PRELOAD) || 2 === $info->status_code) {
        $do_preload = false;
    }
}

if (is_multisite() && is_network_admin()) {
    settings_errors('general');
}
?>
<div class="wrap" id="docket-cache">
    <h1><?php _e('Docket Object Cache', 'docket-cache'); ?><span id="docket-cache-spinner" class="spinner is-active"></span></h1>
    <?php $this->tab_nav(); ?>

    <div class="tab-content">
        <?php if ($this->tab_current('default')) : ?>
        <div class="section overview">
            <h2 class="title"><?php _e('Overview', 'docket-cache'); ?></h2>

            <table class="form-table">
                <tr>
                    <th><?php _e('Status', 'docket-cache'); ?></th>
                    <td><?php echo $info->status_text; ?></td>
                </tr>

                <tr>
                    <th><?php _e('OPCache', 'docket-cache'); ?></th>
                    <td><?php echo $info->opcache_text; ?></td>
                </tr>

                <tr>
                    <th><?php _e('PHP Memory Limit', 'docket-cache'); ?></th>
                    <td><?php echo $info->php_memory_limit; ?></td>
                </tr>

                <tr>
                    <th><?php _e('WP Memory Limit', 'docket-cache'); ?></th>
                    <td><?php echo $info->wp_memory_limit; ?></td>
                </tr>

                <tr>
                    <th><?php _e('Drop-in Installable', 'docket-cache'); ?></th>
                    <td><?php echo $info->write_dropin; ?></td>
                </tr>

                <tr>
                    <th><?php _e('Cache Writable', 'docket-cache'); ?></th>
                    <td><?php echo $info->write_cache; ?></td>
                </tr>

                <tr>
                    <th><?php _e('Cache Path', 'docket-cache'); ?></th>
                    <td><?php echo $info->cache_path; ?></td>
                </tr>

                <tr>
                    <th><?php _e('Total Cache Size', 'docket-cache'); ?></th>
                    <td><?php echo $info->cache_size; ?></td>
                </tr>

            </table>

            <p class="submit">
                <?php if (!$this->plugin->has_dropin()) : ?>
                <?php if ($info->cache_size > 0) : ?>
                <a href="<?php echo $this->plugin->action_query('flush-cache'); ?>" class="button button-secondary button-large"><?php _e('Flush Cache', 'docket-cache'); ?></a>&nbsp;&nbsp;
                <?php endif; ?>
                <?php if (2 !== $info->status_code) : ?>
                <a href="<?php echo $this->plugin->action_query('enable-cache'); ?>" class="button button-primary button-large"><?php _e('Enable Object Cache', 'docket-cache'); ?></a>
                <?php endif; ?>
                <?php elseif ($this->plugin->validate_dropin()) : ?>
                <?php if ($info->cache_size > 0) : ?>
                <a href="<?php echo $this->plugin->action_query('flush-cache'); ?>" class="button button-primary button-large"><?php _e('Flush Cache', 'docket-cache'); ?></a>&nbsp;&nbsp;
                <?php endif; ?>
                <a href="<?php echo $this->plugin->action_query('disable-cache'); ?>" class="button button-secondary button-large"><?php _e('Disable Object Cache', 'docket-cache'); ?></a>
                <?php endif; ?>
            </p>
        </div>

        <?php endif; ?>

        <?php
        if ($this->tab_current('config')) :

            ?>
        <div class="section config">
            <h2 class="title"><?php _e('Configuration', 'docket-cache'); ?></h2>
            <p>
                <?php _e('The following PHP constants can be defined in your <a href="https://wordpress.org/support/article/editing-wp-config-php/" rel="noopener" target="_blank">wp-config.php</a> file, in order to change the behavior of Docket Cache.', 'docket-cache'); ?>
            </p>

            <table class="form-table">
                <?php
                foreach ($this->config_desc() as $key => $texts) :
                    ?>
                <tr>
                    <th><?php echo $key; ?></th>
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
        <?php endif; ?>

        <?php
        if ($this->tab_current('log')) :
            $default_order = !empty($_GET['order']) ? $_GET['order'] : 'last';
            $default_sort = !empty($_GET['sort']) ? $_GET['sort'] : 'desc';
            $default_line = !empty($_GET['line']) ? $_GET['line'] : 100;
            $default_line = (int) $default_line;
            $output = $this->plugin->read_log($default_line, 'last' === $default_order ? true : false);
            $output_empty = empty($output);
            $output_size = !$output_empty ? \count($output) : 0;
            ?>

        <div class="section<?php echo !$output_empty ? ' log' : ''; ?>">
            <h2 class="title"><?php _e('Cache Log ', 'docket-cache'); ?></h2>

            <table class="form-table">
                <tr>
                    <th><?php _e('Status', 'docket-cache'); ?></th>
                    <td><?php echo $info->log_enable_text; ?></td>
                </tr>
                <tr>
                    <th><?php _e('File', 'docket-cache'); ?></th>
                    <td><?php echo $info->log_file; ?></td>
                </tr>
                <?php if ($output_empty) : ?>
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
                        <textarea id="log" class="code" readonly="readonly" rows="<?php echo $output_size < 20 ? $output_size : 20; ?>" wrap="off"><?php echo implode("\n", 'desc' === $default_sort ? array_reverse($output, true) : $output); ?></textarea>
                    </td>
                </tr>
                <?php endif; ?>
            </table>

            <p class="submit">
                <?php if (!$output_empty) : ?>
                <select id="order">
                    <?php
                    foreach (['first', 'last'] as $order) {
                        $selected = ($order === $default_order ? ' selected' : '');
                        echo '<option value="'.$order.'"'.$selected.'>'.strtoupper($order).'</option>';
                    }
                    ?>
                </select>
                <select id="line">
                    <?php
                    foreach (['10', '50', '100', '300', '500'] as $line) {
                        $selected = ((int) $line === $default_line ? ' selected' : '');
                        echo '<option value="'.$line.'"'.$selected.'>'.$line.'</option>';
                    }
                    ?>
                </select>
                <select id="sort">
                    <?php
                    foreach (['asc', 'desc'] as $sort) {
                        $selected = ($sort === $default_sort ? ' selected' : '');
                        echo '<option value="'.$sort.'"'.$selected.'>'.strtoupper($sort).'</option>';
                    }
                    ?>
                </select>
                <br>
                <a href="<?php echo $this->plugin->action_query('flush-log'); ?>" class="button button-primary button-large"><?php _e('Flush Log', 'docket-cache'); ?></a>&nbsp;
                <?php endif; ?>
                <a href="<?php echo $this->tab_query('log'); ?>" class="button button-<?php echo !$output_empty ? 'secondary' : 'primary'; ?> button-large" id="refresh"><?php _e('Refresh', 'docket-cache'); ?></a>
            </p>
        </div>

        <?php endif; ?>
    </div>
</div>
<div id="docket-cache-overlay"></div>

<?php if ($do_preload) : ?>
<script>
    jQuery( document ).ready( function() {
        docket_cache_preload( docket_cache_config );
    } );
</script>
<?php endif; ?>