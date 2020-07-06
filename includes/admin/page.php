<?php
\defined('ABSPATH') || exit;

$status = $this->get_status();
$status_text = $this->status_code[$status];
$is_debug = (\defined('DOCKET_CACHE_DEBUG') && DOCKET_CACHE_DEBUG && \defined('DOCKET_CACHE_DEBUG_FILE'));
$tab = isset($_GET['tab']) ? $_GET['tab'] : '';
$do_preload = false;
if (1 === $status && isset($this->token)) {
    switch ($this->token) {
        case 'docket-cache-flushed':
            wp_cache_flush();
            $do_preload = true;
        break;
        case 'docket-cache-enabled':
            $do_preload = true;
        break;
    }
    if (!\defined('DOCKET_CACHE_PRELOAD') || !DOCKET_CACHE_PRELOAD) {
        $do_preload = false;
    }
}

if (is_multisite() && is_network_admin()) {
    settings_errors('general');
}

$output = '';
if ($is_debug) {
    $output = $this->tail_log(100);
}

?>
<div class="wrap" id="docket-cache">
    <h1><?php _e('Docket Object Cache', 'docket-cache'); ?></h1>

    <nav class="nav-tab-wrapper">
        <a href="<?php echo $this->page; ?>" class="nav-tab<?php echo  empty($tab) ? ' nav-tab-active' : ''; ?>"><?php _e('Overview', 'docket-cache'); ?></a>
        <a href="<?php echo $this->page; ?>&tab=debug" class="nav-tab<?php echo  'debug' === $tab ? ' nav-tab-active' : ''; ?>"><?php _e('Debug Log', 'docket-cache'); ?></a>
        <a href="<?php echo $this->page; ?>&tab=config" class="nav-tab<?php echo  'config' === $tab ? ' nav-tab-active' : ''; ?>"><?php _e('Options', 'docket-cache'); ?></a>
    </nav>

    <div class="tab-content">
    <?php if (empty($tab)): ?>
        <div class="section overview">
            <h2 class="title"><?php _e('Overview', 'docket-cache'); ?></h2>

            <table class="form-table">
                <tbody>
                    <tr>
                        <th><?php _e('Status', 'docket-cache'); ?></th>
                        <td><code><?php echo $status_text; ?></code></td>
                    </tr>

                    <tr>
                        <th><?php _e('OPCache', 'docket-cache'); ?></th>
                        <td><code><?php echo $this->status_code[$this->get_opcache_status()]; ?></code></td>
                    </tr>

                    <tr>
                        <th><?php _e('Memory', 'docket-cache'); ?></th>
                        <td><code><?php echo $this->get_mem_size(); ?></code></td>
                    </tr>

                    <?php if (1 === $status): ?>
                    <tr>
                        <th><?php _e('Cache Size', 'docket-cache'); ?></th>
                        <td><code><?php echo $this->get_dirsize(); ?></code></td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <p class="submit">

                <?php if (!$this->has_dropin()) : ?>
                    <a href="<?php echo $this->action_query('enable-cache'); ?>" class="button button-primary button-large"><?php _e('Enable Object Cache', 'docket-cache'); ?></a>
                <?php elseif ($this->validate_dropin()) : ?>
                    <a href="<?php echo $this->action_query('flush-cache'); ?>" class="button button-primary button-large"><?php _e('Flush Cache', 'docket-cache'); ?></a>&nbsp;&nbsp;
                    <a href="<?php echo $this->action_query('disable-cache'); ?>" class="button button-secondary button-large"><?php _e('Disable Object Cache', 'docket-cache'); ?></a>
            <?php endif; ?>

            </p>
        </div>

    <?php endif; ?>

    <?php if ('config' === $tab):?>
        <div class="section option">
            <h2 class="title"><?php _e('Options', 'docket-cache'); ?></h2>

    <p>
<h4><?php _e('Configuration Options', 'docket-cache'); ?></h4>

<?php _e('To adjust the configuration, please see the <a href="https://github.com/nawawi/docket-cache#configuration-options" rel="noopener" target="_blank">configuration options</a> for a full list.', 'docket-cache'); ?>

<h4><?php _e('WP-CLI Commands', 'docket-cache'); ?> </h4>

<?php _e('To use the WP-CLI commands, please see the <a href="https://github.com/nawawi/docket-cache#wp-cli-commands" rel="noopener" target="_blank">WP-CLI commands</a> for a full list.', 'docket-cache'); ?>
    </p>

        </div>
    <?php endif; ?>

    <?php if ('debug' === $tab):?>

        <div class="section<?php echo !empty($output) ? ' log' : ''; ?>">
            <h2 class="title"><?php _e('Overview', 'docket-cache'); ?></h2>

            <table class="form-table">
                <tbody>
                    <tr>
                        <th><?php _e('Status', 'docket-cache'); ?></th>
                        <td><code><?php echo $this->status_code[$is_debug ? 1 : 0]; ?></code></td>
                    </tr>
                    <tr>
                        <th><?php _e('Log File', 'docket-cache'); ?></th>
                        <td><code><?php echo DOCKET_CACHE_DEBUG_FILE; ?></code></td>
                    </tr>
                                        <tr>
                    <td colspan="2" class="output">

    <?php if ($is_debug) {
    if (empty($output)) {
        echo '<p><code>'.__('Not available', 'docket-cache').'</code></p>';
    } else {
        echo '<textarea id="log" class="code" readonly="readonly" rows="20">'.implode("\n", array_reverse($output, true)).'</textarea>';
    }
}
    ?>
</td></tr>
                </tbody>
            </table>

            <p class="submit">
                <a href="#" onclick="window.location.assign(window.location.href); return false;" class="button button-primary button-large"><?php _e('Refresh', 'docket-cache'); ?></a>&nbsp;
                <?php /*if (!empty($output)): ?>
                    <a href="<?php echo get_home_url(null, '/wp-content/object-cache.log').'?'.time(); ?>" target="_blank" rel="noopener" class="button button-primary button-large"><?php _e('Download','docket-cache');?></a>
                <?php endif;*/ ?>
            </p>
        </div>

    <?php endif; ?>

    </div>
</div>

<?php if ($do_preload): ?>
<script>
jQuery(document).ready(function() {
    wp.ajax.post( "docket_preload", {} ).done(function(response) {
    console.log(response);
  });
});
</script>
<?php endif; ?>
