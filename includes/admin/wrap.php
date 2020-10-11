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
settings_errors((is_multisite() && is_network_admin() ? 'general' : ''));

if (1 === $this->info->status_code && isset($this->pt->token)) {
    switch ($this->pt->token) {
        case 'docket-occache-flushed':
            $this->pt->flush_cache();
            $this->do_preload = true;
            $this->do_flush = true;
            break;
        case 'docket-occache-enabled':
            $this->do_preload = true;
            $this->do_flush = true;
            break;
        case 'docket-log-flushed':
            $this->pt->flush_log();
            $this->do_fetch = true;
            break;
    }
    if ($this->vcf()->is_dcfalse('PRELOAD') || 2 === $this->info->status_code) {
        $this->do_preload = false;
    }
}
?>
<div class="wrap" id="docket-cache">
    <h1 class="screen-reader-text">Docket Cache</h1>
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
    echo $this->pt->code_worker(['flush', 'preload']);
elseif ($this->do_flush) :
    echo $this->pt->code_worker('flush');
elseif ($this->do_fetch) :
    echo $this->pt->code_worker('fetch');
endif;