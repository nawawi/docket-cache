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

if (1 === $this->info->status_code && isset($this->plugin->token)) {
    switch ($this->plugin->token) {
        case 'docket-occache-flushed':
            $this->plugin->flush_cache();
            $this->do_preload = true;
            $this->do_flush = true;
            break;
        case 'docket-occache-enabled':
            $this->do_preload = true;
            $this->do_flush = true;
            break;
        case 'docket-log-flushed':
            $this->plugin->flush_log();
            $this->do_fetch = true;
            break;
    }
    if ($this->plugin->constans()->is_false('DOCKET_CACHE_PRELOAD') || 2 === $this->info->status_code) {
        $this->do_preload = false;
    }
}

if (is_multisite() && is_network_admin()) {
    settings_errors('general');
}
?>
<div class="wrap" id="docket-cache">
    <h1 class="screen-reader-text"><?php _e('Docket Cache', 'docket-cache'); ?></h1>
    <?php $this->tab_nav(); ?>

    <div class="tab-content">
        <?php
            $this->tab_content();
        ?>
    </div>
</div>
<div id="docket-cache-overlay"></div>
<?php
if ($this->do_preload) :
    echo $this->plugin->code_worker(['flush', 'preload']);
elseif ($this->do_flush) :
    echo $this->plugin->code_worker('flush');
elseif ($this->do_fetch) :
    echo $this->plugin->code_worker('fetch');
endif;