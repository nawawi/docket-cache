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

final class MoCache
{
    private $domain = null;
    private $cache = [];
    private $dosave = false;
    private $override = null;
    private $upstream = null;
    private $mofile = null;

    // Avoid warning on get_translations_for_domain.
    // Reference: wp-includes/pomo/translations.php.
    public $entries = [];
    public $headers = [];

    public function __construct($mofile, $domain, $override)
    {
        $this->mofile = apply_filters('load_textdomain_mofile', $mofile, $domain);
        $this->domain = $domain;
        $this->override = $override;

        $cache_key = $domain.'-'.basename($this->mofile, '.mo');
        $cache_group = 'docketcache-mo';

        $mtime = @filemtime($this->mofile);
        $cache = wp_cache_get($cache_key, $cache_group);
        if (false !== $cache && !empty($cache['data'])) {
            if (!empty($cache['time']) && $mtime > $cache['time']) {
                $this->cache = [];
                wp_cache_delete($cache_key, $cache_group);
            } else {
                $this->cache = $cache['data'];
            }
        }

        add_action(
            'shutdown',
            function () use ($mtime, $cache_key, $cache_group) {
                if ($this->dosave) {
                    $cache_data = [
                        'time' => $mtime,
                        'data' => $this->cache,
                    ];

                    wp_cache_set($cache_key, $cache_data, $cache_group);
                }
            },
            \PHP_INT_MAX
        );
    }

    public function translate($text, $context = null)
    {
        $args = \func_get_args();

        return $this->get_translation($this->text_key($args), $text, $args);
    }

    public function translate_plural($singular, $plural, $count, $context = null)
    {
        $text = (1 == abs($count)) ? $singular : $plural;
        $args = \func_get_args();

        return $this->get_translation($this->text_key([$text, $count, $context]), $text, $args);
    }

    private function text_key($args)
    {
        return substr(md5(serialize([$args, $this->domain])), 0, 12);
    }

    private function get_translation($text_key, $text, $args)
    {
        if (isset($this->cache[$text_key])) {
            return $this->cache[$text_key];
        }

        $translate_function = \count($args) > 2 ? 'translate_plural' : 'translate';

        if ($this->override) {
            if (($translation = \call_user_func_array([$this->override, $translate_function], $args)) !== $text) {
                $this->dosave = true;

                return $this->cache[$text_key] = $translation;
            }
        }

        if (!$this->upstream) {
            $this->upstream = new \Mo();
            do_action('load_textdomain', $this->domain, $this->mofile);
            $this->upstream->import_from_file($this->mofile);
        }

        if (($translation = \call_user_func_array([$this->upstream, $translate_function], $args)) !== $text) {
            $this->dosave = true;

            return $this->cache[$text_key] = $translation;
        }

        $translation = \call_user_func_array([$this->upstream, $translate_function], $args);

        $this->dosave = true;

        return $this->cache[$text_key] = $translation;
    }
}
