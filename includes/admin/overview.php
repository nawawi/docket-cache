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
<div class="section overview">
    <h2 class="title"><?php _e('Overview', 'docket-cache'); ?></h2>

    <table class="form-table">
        <tr>
            <th><?php _e('Status', 'docket-cache'); ?></th>
            <td><?php echo $this->info->status_text; ?></td>
        </tr>

        <tr>
            <th><?php _e('OPCache', 'docket-cache'); ?></th>
            <td><?php echo $this->info->opcache_text; ?></td>
        </tr>

        <tr>
            <th><?php _e('PHP Memory Limit', 'docket-cache'); ?></th>
            <td><?php echo $this->info->php_memory_limit; ?></td>
        </tr>

        <tr>
            <th><?php _e('WP Memory Limit', 'docket-cache'); ?></th>
            <td><?php echo $this->info->wp_memory_limit; ?></td>
        </tr>

        <tr>
            <th><?php _e('Drop-in Installable', 'docket-cache'); ?></th>
            <td><?php echo $this->info->write_dropin; ?></td>
        </tr>

        <tr>
            <th><?php _e('Cache Writable', 'docket-cache'); ?></th>
            <td><?php echo $this->info->write_cache; ?></td>
        </tr>

        <tr>
            <th><?php _e('Cache Path', 'docket-cache'); ?></th>
            <td><?php echo $this->info->cache_path; ?></td>
        </tr>

        <tr>
            <th><?php _e('Total Cache Size', 'docket-cache'); ?></th>
            <td><?php echo $this->info->cache_size; ?></td>
        </tr>

    </table>

    <p class="submit">
        <?php if (!$this->plugin->dropin->exists()) : ?>

        <?php if ($this->info->cache_size > 0) : ?>
        <a href="<?php echo $this->plugin->action_query('flush-cache'); ?>" class="button button-secondary button-large"><?php _e('Flush Cache', 'docket-cache'); ?></a>&nbsp;&nbsp;
        <?php endif; ?>

        <?php if (2 !== $this->info->status_code) : ?>
        <a href="<?php echo $this->plugin->action_query('enable-cache'); ?>" class="button button-primary button-large"><?php _e('Enable Object Cache', 'docket-cache'); ?></a>
        <?php endif; ?>

        <?php elseif ($this->plugin->dropin->validate()) : ?>

        <?php if ($this->info->cache_size > 0) : ?>
        <a href="<?php echo $this->plugin->action_query('flush-cache'); ?>" class="button button-primary button-large"><?php _e('Flush Cache', 'docket-cache'); ?></a>&nbsp;&nbsp;
        <?php endif; ?>

        <a href="<?php echo $this->plugin->action_query('disable-cache'); ?>" class="button button-secondary button-large"><?php _e('Disable Object Cache', 'docket-cache'); ?></a>
        <?php endif; ?>
    </p>
</div>