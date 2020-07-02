
<?php
defined('ABSPATH') || exit;
$status = $this->get_status();
$status_text = $this->status_code[$status];

if (1 === $status && isset($this->token) && 'docket-cache-flushed' === $this->token) {
    wp_cache_flush();
}
?>

<div class="wrap" id="docket-cache">
    <h1><?php _e('Docket Object Cache', $this->slug); ?></h1>

    <div class="section">
        <h2 class="title"><?php _e('Overview', $this->slug); ?></h2>

        <table class="form-table">
            <tr>
                <th><?php _e('Status', $this->slug); ?></th>
                <td><code><?php echo $status_text; ?></code></td>
            </tr>

            <tr>
                <th><?php _e('OPCache', $this->slug); ?></th>
                <td><code><?php echo $this->get_opcache_status(); ?></code></td>
            </tr>

            <tr>
                <th><?php _e('Memory', $this->slug); ?></th>
                <td><code><?php echo $this->get_mem_size(); ?></code></td>
            </tr>

            <?php if (1 === $status): ?>
            <tr>
                <th><?php _e('Cache Size', $this->slug); ?></th>
                <td><code><?php echo $this->get_dirsize(); ?></code></td>
            </tr>
            <?php endif; ?>
        </table>

        <p class="submit">

            <?php if (!$this->has_dropin()) : ?>
                <a href="<?php echo $this->action_query('enable-cache'); ?>" class="button button-primary button-large"><?php _e('Enable Object Cache', $this->slug); ?></a>
            <?php elseif ($this->validate_dropin()) : ?>
                <a href="<?php echo $this->action_query('flush-cache'); ?>" class="button button-primary button-large"><?php _e('Flush Cache', $this->slug); ?></a> &nbsp;
                <a href="<?php echo $this->action_query('disable-cache'); ?>" class="button button-secondary button-large"><?php _e('Disable Object Cache', $this->slug); ?></a>
           <?php endif; ?>

        </p>
    </div>
</div>
