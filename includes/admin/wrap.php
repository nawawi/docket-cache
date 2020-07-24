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

$this->do_preload = false;
if (1 === $this->info->status_code && isset($this->plugin->token)) {
    switch ($this->plugin->token) {
        case 'docket-cache-flushed':
            $this->plugin->flush_cache();
            $this->do_preload = true;
            break;
        case 'docket-cache-enabled':
            $this->do_preload = true;
            break;
        case 'docket-log-flushed':
            $this->plugin->flush_log();
            break;
    }
    if ((!\defined('DOCKET_CACHE_PRELOAD') || !DOCKET_CACHE_PRELOAD) || 2 === $this->info->status_code) {
        $this->do_preload = false;
    }
}

if (is_multisite() && is_network_admin()) {
    settings_errors('general');
}

if ($this->do_preload) {
    echo $this->plugin->code_preload();
}
?>
<div class="wrap" id="docket-cache">
    <h1 class="title"><?php _e('Docket Cache', 'docket-cache'); ?><span id="docket-cache-spinner" class="spinner is-active"></span></h1>
    <?php $this->tab_nav(); ?>

    <div class="tab-content">
        <?php
            $this->tab_content();
        ?>
    </div>
</div>
<div id="docket-cache-overlay"></div>