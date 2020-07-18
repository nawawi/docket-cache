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

final class View
{
    private $plugin;

    public function __construct($plugin)
    {
        $this->plugin = $plugin;
    }

    private function config_desc()
    {
        return [
             'DOCKET_CACHE_MAXTTL' => [
                 __('Maximum cache time-to-live in seconds, if expiry key 0', 'docket-cache'),
                 __('Default: 0', 'docket-cache'),
             ],
             'DOCKET_CACHE_SIZE' => [
                 __('Set the maximum size of cache in bytes.', 'docket-cache'),
                 __('Default: 3000000 (3MB).', 'docket-cache'),
             ],
             'DOCKET_CACHE_PATH' => [
                 __('Set the cache directory.', 'docket-cache'),
                 /* translators: %s: value of WP_CONTENT_DIR */
                 sprintf(__('Default: %s/cache/docket-cache', 'docket-cache'), $this->plugin->sanitize_rootpath(WP_CONTENT_DIR)),
             ],
             'DOCKET_CACHE_FLUSH_DELETE' => [
                 __('By default Docket Cache only empty the cache file. Set to true to delete the cache file.', 'docket-cache'),
                 __('Default: false', 'docket-cache'),
             ],
             'DOCKET_CACHE_GLOBAL_GROUPS' => [
                 __('Lists of cache groups that share cache with other sites in a Multisite setup.', 'docket-cache'),
                 /* translators: %s: url */
                 sprintf(__('Default: <a href="%s" rel="noopener" target="blank">See details</a>', 'docket-cache'), 'https://github.com/nawawi/docket-cache#configuration-options'),
             ],
             'DOCKET_CACHE_IGNORED_GROUPS' => [
                 __('List of cache groups that should not be cached.', 'docket-cache'),
                 /* translators: %s: url */
                 sprintf(__('Default: <a href="%s" rel="noopener" target="blank">See details</a>', 'docket-cache'), 'https://github.com/nawawi/docket-cache#configuration-options'),
             ],
             'DOCKET_CACHE_LOG' => [
                 __('Set to true to enable the cache log.', 'docket-cache'),
                 __('Default: false', 'docket-cache'),
             ],
             'DOCKET_CACHE_LOG_SIZE' => [
                 __('Set the maximum size of a log file in bytes.', 'docket-cache'),
                 __('Default: 10000000 (10MB)', 'docket-cache'),
             ],
             'DOCKET_CACHE_LOG_FILE' => [
                 __('Set the log file.', 'docket-cache'),
                 /* translators: %s: value of WP_CONTENT_DIR */
                 sprintf(__('Default: %s/object-cache.log', 'docket-cache'), $this->plugin->sanitize_rootpath(WP_CONTENT_DIR)),
             ],
             /*'DOCKET_CACHE_LOG_FLUSH' => [
                 __('Set to true to empty the log file when the object cache is flushed.', 'docket-cache'),
                 __('Default: true', 'docket-cache'),
             ],
             'DOCKET_CACHE_GC' => [
                 __('The Docket Cache Garbage collector is scheduled to run every 30 minutes to clean empty files that are more than 2 minutes old. Set to false to disable the garbage collector.', 'docket-cache'),
                 __('Default: true', 'docket-cache'),
             ],*/
             'DOCKET_CACHE_ADVCPOST' => [
                 __('Set to true to enable Advanced Post Cache.', 'docket-cache'),
                 __('Default: true', 'docket-cache'),
             ],
             'DOCKET_CACHE_MISC_TWEAKS' => [
                 __('Set to true to enable miscellaneous WordPress performance tweaks.', 'docket-cache'),
                 __('Default: true', 'docket-cache'),
             ],
             /* 'DOCKET_CACHE_PRELOAD' => [
                 __('Set to true to enable cache preloading.', 'docket-cache'),
                 __('Default: false', 'docket-cache'),
             ],
             'DOCKET_CACHE_COMMENT' => [
                 __('Set to true to enable the HTML footer comment that promote this plugin.', 'docket-cache'),
                 __('Default: true', 'docket-cache'),
             ],*/
             'DOCKET_CACHE_PAGELOADER' => [
                 __('Set to true to enable a loading bar when the page is loading.', 'docket-cache'),
                 __('Default: true', 'docket-cache'),
             ],
             'DOCKET_CACHE_DISABLED' => [
                 __('Set to true to bypass caching object at runtime. No object cache at this time.', 'docket-cache'),
                 __('Default: false', 'docket-cache'),
             ],
         ];
    }

    public function index()
    {
        include_once $this->plugin->path.'/includes/admin/page.php';
        $this->plugin->dropin_undelay();
    }

    private function tab_query($index)
    {
        return network_admin_url(add_query_arg('index', $index, $this->plugin->page));
    }

    private function tab_index()
    {
        return !empty($_GET['index']) ? sanitize_text_field($_GET['index']) : 'default';
    }

    private function tab_current($index)
    {
        return $index === $this->tab_index();
    }

    private function tab_active($index)
    {
        if ($this->tab_current($index)) {
            return ' nav-tab-active';
        }

        return '';
    }

    private function tab_nav()
    {
        $html = '<nav class="nav-tab-wrapper">';
        $html .= '<a href="'.$this->tab_query('default').'" class="nav-tab'.$this->tab_active('default').'">'.__('Overview', 'docket-cache').'</a>';
        $html .= '<a href="'.$this->tab_query('log').'" class="nav-tab'.$this->tab_active('log').'">'.__('Cache Log', 'docket-cache').'</a>';
        $html .= '<a href="'.$this->tab_query('config').'" class="nav-tab'.$this->tab_active('config').'">'.__('Config', 'docket-cache').'</a>';
        $html .= '</nav>';
        echo $html;
    }
}
