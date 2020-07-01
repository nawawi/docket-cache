
<?php defined('ABSPATH') || exit; ?>

<div class="wrap" id="docket-cache">

    <h1><?php _e('Docket Object Cache', $this->slug); ?></h1>

    <div class="section-overview">

        <h2 class="title"><?php _e('Overview', $this->slug); ?></h2>

        <table class="form-table">
<?php
    $status = $this->get_status();
        $halt = 2;

        switch ($status) {
            case __('Disabled', $this->slug):
                $halt = 1;
                break;
            case __('Enabled', $this->slug):
                $halt = 0;
                break;
        }
?>
            <tr>
                <th><?php _e('Status', $this->slug); ?></th>
                <td><code><?php echo $status; ?></code></td>
            </tr>

            <tr>
                <th><?php _e('OPCache', $this->slug); ?></th>
                <td><code><?php echo $this->get_opcache_status(); ?></code></td>
            </tr>

            <tr>
                <th><?php _e('Memory Limit', $this->slug); ?></th>
                <td><code><?php echo $this->get_mem_size(); ?></code></td>
            </tr>
<?php if (0 === $halt): ?>
            <tr>
                <th><?php _e('Cache Size', $this->slug); ?></th>
                <td><code><?php echo $this->get_dirsize(); ?></code></td>
            </tr>
<?php endif; ?>
        </table>

        <p class="submit">

            <?php if (!$this->has_dropin()) : ?>
                <a href="<?php echo wp_nonce_url(network_admin_url(add_query_arg('action', 'docket-enable-cache', $this->page)), 'docket-enable-cache'); ?>" class="button button-primary button-large"><?php _e('Enable Object Cache', $this->slug); ?></a>
            <?php elseif ($this->validate_dropin()) : ?>
                <a href="<?php echo wp_nonce_url(network_admin_url(add_query_arg('action', 'docket-flush-cache', $this->page)), 'docket-flush-cache'); ?>" class="button button-primary button-large"><?php _e('Flush Cache', $this->slug); ?></a> &nbsp;
                <a href="<?php echo wp_nonce_url(network_admin_url(add_query_arg('action', 'docket-disable-cache', $this->page)), 'docket-disable-cache'); ?>" class="button button-secondary button-large"><?php _e('Disable Object Cache', $this->slug); ?></a>
           <?php endif; ?>

        </p>
<!--
        <h2 class="title">
            <?php _e('Configuration', $this->slug); ?>
        </h2>

        <table class="form-table">
            <tr>
                <th>DOCKET_CACHE_IGNORED_GROUPS</th>
                <td><code><?php echo defined('DOCKET_CACHE_MAXTTL') ? constant('DOCKET_CACHE_MAXTTL') : 0; ?></code></td>
            </tr>
            <tr>
                <th>DOCKET_CACHE_MAXTTL</th>
                <td><code><?php echo defined('DOCKET_CACHE_MAXTTL') ? constant('DOCKET_CACHE_MAXTTL') : 0; ?></code></td>
            </tr>
            <tr>
                <th>DOCKET_CACHE_PATH</th>
                <td><code><?php echo str_replace(WP_CONTENT_DIR, 'WP_CONTENT_DIR', defined('DOCKET_CACHE_PATH') ? constant('DOCKET_CACHE_PATH') : WP_CONTENT_DIR.'/cache/docket-cache'); ?></code></td>
            </tr>
            <tr>
                <th>DOCKET_CACHE_DEBUG</th>
                <td><code><?php echo defined('DOCKET_CACHE_DEBUG') ? constant('DOCKET_CACHE_DEBUG') : __('Not set', $this->slug); ?></code></td>
            </tr>
        </table>
-->
    </div>
</div>
