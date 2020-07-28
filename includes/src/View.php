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
    private $info;
    private $do_preload;
    private $do_flush;

    public function __construct(Plugin $plugin)
    {
        $this->plugin = $plugin;
    }

    private function constants_desc()
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
                 sprintf(__('Default: %s/.object-cache.log', 'docket-cache'), $this->plugin->sanitize_rootpath(WP_CONTENT_DIR)),
             ],
             'DOCKET_CACHE_LOG_FLUSH' => [
                 __('Set to true to empty the log file when the object cache is flushed.', 'docket-cache'),
                 __('Default: true', 'docket-cache'),
             ],
             'DOCKET_CACHE_GC' => [
                 __('The Docket Cache Garbage collector is scheduled to run every 30 minutes to clean empty files that are more than 2 minutes old. Set to false to disable the garbage collector.', 'docket-cache'),
                 __('Default: true', 'docket-cache'),
             ],
             'DOCKET_CACHE_ADVCPOST' => [
                 __('Set to true to enable Advanced Post Cache.', 'docket-cache'),
                 __('Default: true', 'docket-cache'),
             ],
             'DOCKET_CACHE_OPTERMCOUNT' => [
                 __('Set to true to enable miscellaneous WordPress performance tweaks.', 'docket-cache'),
                 __('Default: true', 'docket-cache'),
             ],
             'DOCKET_CACHE_MOCACHE' => [
                 __('Set to true to enable WordPress Translation Cache.', 'docket-cache'),
                 __('Default: true', 'docket-cache'),
             ],
             'DOCKET_CACHE_MISC_TWEAKS' => [
                 __('Set to true to enable optimization of WordPress Term count queries.', 'docket-cache'),
                 __('Default: true', 'docket-cache'),
             ],
             'DOCKET_CACHE_PRELOAD' => [
                 __('Set to true to enable cache preloading.', 'docket-cache'),
                 __('Default: false', 'docket-cache'),
             ],
             'DOCKET_CACHE_COMMENT' => [
                 __('Set to true to enable the HTML footer comment that promote this plugin.', 'docket-cache'),
                 __('Default: true', 'docket-cache'),
             ],
             'DOCKET_CACHE_PAGELOADER' => [
                 __('Set to true to enable a loading bar when the admin page is loading.', 'docket-cache'),
                 __('Default: false', 'docket-cache'),
             ],
             'DOCKET_CACHE_DISABLED' => [
                 __('Set to true to bypass caching object at runtime. No object cache at this time.', 'docket-cache'),
                 __('Default: false', 'docket-cache'),
             ],
         ];
    }

    private function parse_query()
    {
        $ret = (object) [];
        $ret->default_order = 'last';
        $ret->default_sort = 'desc';
        $ret->default_line = 100;

        if (!empty($_GET['srt'])) {
            $srt = explode('-', $_GET['srt']);
            if (3 >= \count($srt)) {
                $ret->default_order = $srt[0];
                $ret->default_sort = $srt[1];
                $ret->default_line = (int) $srt[2];
            }
        }

        $ret->output = $this->read_log($ret->default_line, 'last' === $ret->default_order ? true : false);
        $ret->output_empty = empty($ret->output);
        $ret->output_size = !$ret->output_empty ? \count($ret->output) : 0;

        return $ret;
    }

    private function page($index)
    {
        $this->info = (object) $this->plugin->get_info();
        $file = $this->plugin->path.'/includes/admin/'.$index.'.php';
        if (@is_file($file)) {
            include_once $file;
        }
    }

    public function index()
    {
        $this->do_preload = false;
        $this->do_flush = false;
        $this->page('wrap');
        $this->plugin->dropino->delay_expire();
    }

    private function tab_content()
    {
        $this->page($this->is_index());
    }

    private function tab_query($index)
    {
        return network_admin_url(add_query_arg('idx', $index, $this->plugin->page));
    }

    private function is_index()
    {
        return !empty($_GET['idx']) ? sanitize_text_field($_GET['idx']) : 'overview';
    }

    private function tab_current($index)
    {
        return $index === $this->is_index();
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
        $html .= '<a href="'.$this->tab_query('overview').'" class="nav-tab'.$this->tab_active('overview').'">'.__('Overview', 'docket-cache').'</a>';
        $html .= '<a href="'.$this->tab_query('log').'" class="nav-tab'.$this->tab_active('log').'">'.__('Cache Log', 'docket-cache').'</a>';
        $html .= '<a href="'.$this->tab_query('config').'" class="nav-tab'.$this->tab_active('config').'">'.__('Configure', 'docket-cache').'</a>';
        $html .= '</nav>';
        echo $html;
    }

    /**
     * read_log.
     */
    private function read_log($limit = 100, $do_last = true)
    {
        $limit = (int) $limit;
        $output = [];
        if ($this->plugin->has_log()) {
            foreach ($this->plugin->tail(DOCKET_CACHE_LOG_FILE, $limit, $do_last) as $line) {
                $line = trim($line);
                if (empty($line)) {
                    continue;
                }
                $output[] = $line;
            }
        }

        return $output;
    }

    private function config_select_bool($name, $default)
    {
        $default = $default ? 'enable' : 'disable';
        $html = '<select id="'.$name.'" class="config-select">';
        foreach ([
            'default' => __('Default', 'docket-cache'),
            'enable' => __('Enable', 'docket-cache'),
            'disable' => __('Disable', 'docket-cache'),
        ] as $n => $v) {
            $action = $n.'-'.$name;
            $url = $this->plugin->action_query($action, ['idx' => 'config']);
            $selected = $n === $default ? ' selected' : '';
            $html .= '<option value="'.$n.'" data-action-link="'.$url.'"'.$selected.'>'.$v.'</option>';
        }
        $html .= '</select>';

        return $html;
    }

    private function config_select_set($name, $options, $default)
    {
        $action = 'save-'.$name;
        $html = '<select id="'.$name.'" class="config-select">';
        foreach ((array) $options as $n => $v) {
            $url = $this->plugin->action_query(
                $action,
                [
                    'idx' => 'config',
                    'nv' => $n,
                ]
            );
            $selected = $n === $default ? ' selected' : '';
            $html .= '<option value="'.$n.'" data-action-link="'.$url.'"'.$selected.'>'.$v.'</option>';
        }
        $html .= '</select>';

        return $html;
    }
}
