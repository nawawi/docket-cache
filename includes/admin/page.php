<?php
\defined('ABSPATH') || exit;

$info = (object) $this->get_info();

$do_preload = false;
if (1 === $info->status_code && isset($this->token)) {
    switch ($this->token) {
        case 'docket-cache-flushed':
            $this->flush_cache();
            $do_preload = true;
            break;
        case 'docket-cache-enabled':
            $do_preload = true;
            break;
        case 'docket-log-flushed':
            $this->flush_log();
            break;
    }
    if (!DOCKET_CACHE_PRELOAD || 2 === $info->status_code) {
        $do_preload = false;
    }
}

if (is_multisite() && is_network_admin()) {
    settings_errors('general');
}
?>
<div class="wrap" id="docket-cache">
    <h1><?php _e('Docket Object Cache', 'docket-cache'); ?></h1>
    <?php $this->page_nav_tabs(); ?>

    <div class="tab-content">
        <?php if ($this->page_is_tab('default')) : ?>
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
                    <th><?php _e('Cache Size', 'docket-cache'); ?></th>
                    <td><?php echo $info->cache_size; ?></td>
                </tr>

            </table>

            <p class="submit">
                <?php if (!$this->has_dropin()) : ?>
                <?php if ($info->cache_size > 0) : ?>
                <a href="<?php echo $this->action_query('flush-cache'); ?>" class="button button-secondary button-large"><?php _e('Flush Cache', 'docket-cache'); ?></a>&nbsp;&nbsp;
                <?php endif; ?>
                <?php if (2 !== $info->status_code) : ?>
                <a href="<?php echo $this->action_query('enable-cache'); ?>" class="button button-primary button-large"><?php _e('Enable Object Cache', 'docket-cache'); ?></a>
                <?php endif; ?>
                <?php elseif ($this->validate_dropin()) : ?>
                <?php if ($info->cache_size > 0) : ?>
                <a href="<?php echo $this->action_query('flush-cache'); ?>" class="button button-primary button-large"><?php _e('Flush Cache', 'docket-cache'); ?></a>&nbsp;&nbsp;
                <?php endif; ?>
                <a href="<?php echo $this->action_query('disable-cache'); ?>" class="button button-secondary button-large"><?php _e('Disable Object Cache', 'docket-cache'); ?></a>
                <?php endif; ?>
            </p>
        </div>

        <?php endif; ?>

        <?php if ($this->page_is_tab('config')) : ?>
        <div class="section config">
            <h2 class="title"><?php _e('Configuration', 'docket-cache'); ?></h2>
            <p>
                In order to change the setting of Docket Cache, the following PHP constants can be defined in your wp-config.php file.
            </p>

            <table class="form-table">
                <tr>
                    <th>DOCKET_CACHE_DISABLED</th>
                    <td>
                        <p>
                            Set to <code>true</code> to disable the object cache at runtime.
                        </p>
                        <small>Default: <code>false</code></small>
                    </td>
                </tr>

                <tr>
                    <th>DOCKET_CACHE_MAXTTL</th>
                    <td>
                        <p>
                            Maximum cache time-to-live in seconds
                        </p>
                        <small>Default: <code>0</code></small>
                    </td>
                </tr>

                <tr>
                    <th>DOCKET_CACHE_SIZE</th>
                    <td>
                        <p>
                            Set the size of a cache file in byte.
                        </p>
                        <small>Default: <code>3000000</code> (3MB)</small>
                    </td>
                </tr>

                <tr>
                    <th>DOCKET_CACHE_PATH</th>
                    <td>
                        <p>
                            Set the cache directory.
                        </p>
                        <small>Default: <code>WP_CONTENT_DIR/cache/docket-cache</code></small>
                    </td>
                </tr>

                <tr>
                    <th>DOCKET_CACHE_FLUSH_DELETE</th>
                    <td>
                        <p>
                            Set to <code>true</code> to delete cache files instead of only truncated.
                        </p>
                        <small>Default: <code>false</code></small>
                    </td>
                </tr>

                <tr>
                    <th>DOCKET_CACHE_GLOBAL_GROUPS</th>
                    <td>
                        <p>
                            List of cache groups that shared cache with others site in Multisite setups.
                        </p>
                        <small>Default: <code>['blog-details','blog-id-cache','blog-lookup','global-posts','networks','rss','sites','site-details','site-lookup','site-options','site-transient','users','useremail','userlogins','usermeta','user_meta','userslugs']</code></small>
                    </td>
                </tr>

                <tr>
                    <th>DOCKET_CACHE_IGNORED_GROUPS</th>
                    <td>
                        <p>
                            List of cache groups that should not be cached.
                        </p>
                        <small>Default: <code>['counts', 'plugins', 'themes', 'comment', 'wc_session_id', 'bp_notifications', 'bp_messages', 'bp_pages']</code></small>
                    </td>
                </tr>

                <tr>
                    <th>DOCKET_CACHE_IGNORED_KEYS</th>
                    <td>
                        <p>
                            List of cache keys that should not be cached.
                        </p>
                        <small>Default: <em>not set</em></small>
                    </td>
                </tr>

                <tr>
                    <th>DOCKET_CACHE_LOG</th>
                    <td>
                        <p>
                            Set to <code>true</code> to enable cache log.
                        </p>
                        <small>Default: <code>false</code></small>
                    </td>
                </tr>

                <tr>
                    <th>DOCKET_CACHE_LOG_SIZE</th>
                    <td>
                        <p>
                            Set the maximum size of a log file in byte.
                        </p>
                        <small>Default: <code>10000000</code> (10MB)</small>
                    </td>
                </tr>


                <tr>
                    <th>DOCKET_CACHE_LOG_FILE</th>
                    <td>
                        <p>
                            Set the file of log.
                        </p>
                        <small>Default: <code>WP_CONTENT_DIR/object-cache.log</code></small>
                    </td>
                </tr>

                <tr>
                    <th>DOCKET_CACHE_LOG_FLUSH</th>
                    <td>
                        <p>
                            Set to <code>true</code> to empty the log file when object cache flushed.
                        </p>
                        <small>Default: <code>true</code></small>
                    </td>
                </tr>

                <tr>
                    <th>DOCKET_CACHE_GC</th>
                    <td>
                        <p>
                            Set to <code>true</code> to enable the garbage collector runs every 30 minutes to remove any leftover cache.
                        </p>
                        <small>Default: <code>true</code></small>
                    </td>
                </tr>

                <tr>
                    <th>DOCKET_CACHE_ADVCPOST</th>
                    <td>
                        <p>
                            Set to <code>true</code> to enable Advanced Post Cache.
                        </p>
                        <small>Default: <code>true</code></small>
                    </td>
                </tr>

                <tr>
                    <th>DOCKET_CACHE_MISC_TWEAKS</th>
                    <td>
                        <p>
                            Set to <code>true</code> to enable miscellaneous WordPress performance tweaks.
                        </p>
                        <small>Default: <code>true</code></small>
                    </td>
                </tr>

            </table>
        </div>
        <?php endif; ?>

        <?php
        if ($this->page_is_tab('log')) :
            $output = $this->tail_log(100);
            ?>

        <div class="section<?php echo !empty($output) ? ' log' : ''; ?>">
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
                <?php if (empty($output)) : ?>
                <tr>
                    <th><?php _e('Data', 'docket-cache'); ?></th>
                    <td><?php _e('Not available', 'docket-cache'); ?></td>
                </tr>
                <?php else : ?>
                <tr>
                    <th><?php _e('Size', 'docket-cache'); ?></th>
                    <td><?php echo $this->get_logsize(); ?></td>
                </tr>
                <tr>
                    <td colspan="2" class="noborder">
                        <textarea id="log" class="code" readonly="readonly" rows="20" wrap="off"><?php echo implode("\n", array_reverse($output, true)); ?></textarea>
                    </td>
                </tr>
                <?php endif; ?>
            </table>

            <p class="submit">
                <?php if (!empty($output)) : ?>
                <a href="<?php echo $this->action_query('flush-log'); ?>" class="button button-primary button-large"><?php _e('Flush Log', 'docket-cache'); ?></a>&nbsp;
                <?php endif; ?>
                <a href="<?php echo $this->page_tab_query('log'); ?>" class="button button-<?php echo !empty($output) ? 'secondary' : 'primary'; ?> button-large"><?php _e('Refresh', 'docket-cache'); ?></a>
            </p>
        </div>

        <?php endif; ?>

    </div>
</div>

<?php if ($do_preload) : ?>
<script>
    jQuery( document ).ready( function() {
        jQuery.post( ajaxurl, {
            "action": "docket_preload"
        }, function( response ) {
            console.log( response.data + ' ' + response.success );
        } );
    } );
</script>
<?php endif; ?>