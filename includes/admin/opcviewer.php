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
$opcache_view = $this->opcache_view();
$is_config = !empty($_GET['adx']) && 'cnf' === sanitize_text_field($_GET['adx']);
?>
<div class="section cronbot opcache">
    <div class="flex-container">
        <div class="row">
            <?php
            if ($is_config && $this->pt->is_opcache_enable()) :
                $this->tab_title(esc_html__('OPcache Config', 'docket-cache'));
                $config = $opcache_view->get_config();
                if (empty($config)) :
                    echo Resc::boxmsg(__('No data is available. The opcache_get_configuration function disabled in PHP configuration.', 'docket-cache'), 'warning', false, true, false);
                else :
                    ?>
            <table class="form-table opcconfig">
                <tr>
                    <td colspan="2" class="stitle">
                        <?php esc_html_e('Version', 'docket-cache'); ?>
                    </td>
                </tr>
                <?php
                    $last = \count($config['version']);
                    $lcnt = 1;
                    foreach ($config['version'] as $k => $v) :
                        ?>
                <tr>
                    <th<?php echo  $lcnt >= $last ? ' class="border-b"' : ''; ?>><?php echo $k; ?><span class="rsep">:</span></th>
                        <td><?php echo $v; ?></td>
                </tr>
                <?php
                        ++$lcnt;
                endforeach;
                    ?>

                <tr>
                    <td colspan="2" class="stitle">
                        <?php esc_html_e('Directives', 'docket-cache'); ?>
                    </td>
                </tr>
                <?php
                    foreach ($config['directives'] as $k => $v) :
                        $type = \gettype($v);
                        switch ($type) {
                            case 'boolean':
                                $v = $v ? 'true' : 'false';
                                break;
                            case 'string':
                                $v = '' === $v ? esc_html__('Not set', 'docket-cache') : $v;
                                break;
                            case 'integer':
                                switch ($k) {
                                    case 'opcache.memory_consumption':
                                        $v = $this->pt->normalize_size($v);
                                        break;
                                }
                                break;
                        }
                        $blacklist = [];
                        if ('opcache.blacklist_filename' === $k) {
                            $files = glob($v);
                            if (!empty($files) && \is_array($files)) {
                                $blacklist = $files;
                            }
                            unset($files);
                        }
                        ?>
                <tr>
                    <th><a href="<?php echo $opcache_view->get_doc($k); ?>" rel="noopener" target="new"><?php echo $k; ?></a><span class="rsep">:</span></th>
                    <td><?php echo $v; ?></td>
                </tr>
                <?php if (!empty($blacklist)) : ?>
                <tr>
                    <th></th>
                    <td><small><?php echo implode('<br>', $blacklist); ?></small></td>
                </tr>
                <?php endif; ?>

                <?php endforeach; ?>
            </table>
            <p class="submit">
                <a href="<?php echo $this->pt->get_page(['idx' => 'opcviewer']); ?>" class="button button-primary"><?php esc_html_e('Dismiss', 'docket-cache'); ?></a>
            </p>
            <?php
                endif; // empty config
                else :
                    ?>

            <?php $this->tab_title(esc_html__('OPcache Usage', 'docket-cache')); ?>
            <?php
                    if (!$this->pt->is_opcache_enable()) :
                        echo Resc::boxmsg(__('OPcache not available.', 'docket-cache'), 'warning', false, true, false);
            elseif (!\is_object($opcache_view)) :
                echo Resc::boxmsg(__('Failed to load OPcacheList()', 'docket-cache'), 'error', false, true, false);
            else :

                if (!$this->pt->opcache_function_exists('opcache_get_status')) {
                    echo Resc::boxmsg(__('No data is available. The opcache_get_status function disabled in PHP configuration.', 'docket-cache'), 'warning', false, true, false);
                } elseif ($this->pt->is_opcache_blacklisted()) {
                    echo Resc::boxmsg(__("This site's local path has been blacklisted by OPcache configuration, which prevents it from caching.", 'docket-cache'), 'warning', false, true, false);
                }

                $stats = $opcache_view->get_usage();

                if (empty($stats->file_cache_only)) :
                    ?>
            <table class="form-table">
                <tr>
                    <td colspan="4" class="stitle">
                        <?php esc_html_e('Statistics', 'docket-cache'); ?>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Cache Hits', 'docket-cache'); ?></th>
                    <td class="td-second"><?php echo $stats->hits; ?></td>
                    <th class="th-third"><?php esc_html_e('Cache Misses', 'docket-cache'); ?></th>
                    <td><?php echo $stats->misses; ?></td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Cached Files', 'docket-cache'); ?></th>
                    <td class="td-second"><?php echo $stats->num_cached_scripts; ?></td>
                    <th class="th-third"><?php esc_html_e('Cached Keys', 'docket-cache'); ?></th>
                    <td><?php echo $stats->num_cached_keys; ?></td>
                </tr>
                <?php if (0 !== (int) $stats->blacklist_misses) : ?>
                <tr>
                    <th class="border-b"><?php esc_html_e('Max Cached Keys', 'docket-cache'); ?></th>
                    <td class="td-second"><?php echo $stats->max_cached_keys; ?></td>
                    <th class="border-b th-third"><?php esc_html_e('Hit Rate', 'docket-cache'); ?></th>
                    <td><?php echo round($stats->opcache_hit_rate, 0); ?>%</td>
                </tr>
                <tr>
                    <th class="border-b"><?php esc_html_e('Blacklist Misses', 'docket-cache'); ?></th>
                    <td class="td-second"><?php echo $stats->blacklist_misses; ?></td>
                    <th class="border-b th-third"><?php esc_html_e('Blacklist Miss Ratio', 'docket-cache'); ?></th>
                    <td><?php echo round($stats->blacklist_miss_ratio, 0); ?>%</td>
                </tr>
                <?php else : ?>
                <tr>
                    <th class="border-b"><?php esc_html_e('Max Cached Keys', 'docket-cache'); ?></th>
                    <td class="td-second"><?php echo $stats->max_cached_keys; ?></td>
                    <th class="border-b th-third"><?php esc_html_e('Hit Rate', 'docket-cache'); ?></th>
                    <td><?php echo round($stats->opcache_hit_rate, 0); ?>%</td>
                </tr>
                <?php endif; ?>
                <tr>
                    <td colspan="4" class="stitle">
                        <?php esc_html_e('Memory Usage', 'docket-cache'); ?>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Used Memory', 'docket-cache'); ?></th>
                    <td class="td-second"><?php echo $this->pt->normalize_size($stats->used_memory); ?></td>
                    <th class="th-third"><?php esc_html_e('Free Memory', 'docket-cache'); ?></th>
                    <td><?php echo $this->pt->normalize_size($stats->free_memory); ?></td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Wasted Memory', 'docket-cache'); ?></th>
                    <td class="td-second"><?php echo $this->pt->normalize_size($stats->wasted_memory); ?></td>
                    <th class="th-third"><?php esc_html_e('Current Wasted', 'docket-cache'); ?></th>
                    <td><?php echo round($stats->current_wasted_percentage, 0); ?>%</td>
                </tr>
            </table>
            <?php else : ?>

            <table class="form-table">
                <tr>
                    <th><?php esc_html_e('Status', 'docket-cache'); ?></th>
                    <td><a href="https://www.php.net/manual/en/opcache.configuration.php#ini.opcache.file-cache-only" rel="noopener" target="new"><?php esc_html_e('File Cache Only', 'docket-cache'); ?></a></td>
                </tr>
                <tr>
                    <th><?php esc_html_e('File Cache Path', 'docket-cache'); ?></th>
                    <td><?php echo $stats->file_cache; ?></td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Stats', 'docket-cache'); ?></th>
                    <td><?php echo $stats->statsfile; ?></td>
                </tr>
            </table>

            <?php endif; ?>

            <p id="scrollmark" class="submit">
                <a href="<?php echo $this->pt->action_query('flush-opcache', ['idx' => 'opcviewer']); ?>" class="button button-primary button-large btx-spinner"><?php esc_html_e('Flush OPcache', 'docket-cache'); ?></a>
                <a href="
                <?php
                echo $this->pt->get_page(
                    [
                        'idx' => 'opcviewer',
                        'adx' => 'cnf',
                    ]
                );
                ?>
                " class="button button-secondary button-large btx-spinner"><?php esc_html_e('Display Config', 'docket-cache'); ?></a>
            </p>

            <?php $this->tab_title(esc_html__('OPcache Files', 'docket-cache'), 'title-below'); ?>
            <div class="gridlist grid-opclist border-t">
                <div class="box-left">
                    <form id="config-filter" method="get" action="<?php echo esc_url($this->pt->get_page()); ?>">
                        <input type="hidden" name="page" value="docket-cache-opcviewer">
                        <input type="hidden" name="idx" value="opcviewer">
                        <select id="statsop" name="sf" class="config-filter">
                            <?php
                            $sort_sf = !empty($_GET['sf']) ? sanitize_text_field($_GET['sf']) : 'obc';
                            $opts = [
                                'obc' => __('Object Cache Files', 'docket-cache'),
                                'wpc' => __('Other Files', 'docket-cache'),
                                'dfc' => __('Stale Files', 'docket-cache'),
                                'all' => __('All', 'docket-cache'),
                            ];

                            if (!empty($stats->file_cache_only)) {
                                unset($opts['dfc']);
                            }

                            foreach ($opts as $k => $n) {
                                echo '<option value="'.$k.'"'.($sort_sf === $k ? ' selected' : '').'>'.$n.'</option>';
                            }
                            ?>
                        </select>

                        <select id="statslm" name="sm" class="config-filter">
                            <?php
                            $sort_sm = !empty($_GET['sm']) ? sanitize_text_field($_GET['sm']) : '1k';
                            foreach ([
                                '1k' => __('< 1000 Items', 'docket-cache'),
                                '5k' => __('< 5000 Items', 'docket-cache'),
                                '10k' => __('< 10000 Items', 'docket-cache'),
                                'all' => __('> All Items', 'docket-cache'),
                            ] as $k => $n) {
                                echo '<option value="'.$k.'"'.($sort_sm === $k ? ' selected' : '').'>'.$n.'</option>';
                            }
                            ?>
                        </select>
                    </form>
                </div>

                <?php if ($opcache_view->get_pagination_arg('total_pages') > 1 || !empty($_GET['s'])) : ?>
                <div class="box-right">
                    <form id="search-filter" method="get" action="<?php echo esc_url($this->pt->get_page()); ?>">
                        <input type="hidden" name="page" value="docket-cache-opcviewer">
                        <input type="hidden" name="idx" value="opcviewer">
                        <input type="hidden" name="sf" value="<?php echo esc_attr($sort_sf); ?>">
                        <input type="hidden" name="sm" value="<?php echo esc_attr($sort_sm); ?>">
                        <?php $opcache_view->search_box(__('Filter Cached Files', 'docket-cache'), 'opclist-info'); ?>
                    </form>
                </div>
                <?php endif; ?>
                <div class="table-responsive">
                    <?php $opcache_view->display(); ?>
                </div>
            </div>
            <?php endif; // is_object?>
            <?php endif; // is_conf?>
        </div>
    </div>
</div>
<?php
if (!empty($_GET['orderby']) || !empty($_GET['paged']) || !empty($_GET['sf'])) :
    ?>
<script>
    var el = document.getElementById( "scrollmark" );
    el.scrollIntoView( {
        block: "start"
    } );

</script>
<?php
endif;
