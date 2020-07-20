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

final class PrivateRepo
{
    private $repo;
    private $transient_key;
    private $slug;
    private $hook;
    private $version;
    private $wp_version;

    public function __construct($slug, $hook, $version, $repo, $args = '')
    {
        $this->slug = $slug;
        $this->hook = $hook;
        $this->version = $version;
        $this->repo = $repo;
        $this->wp_version = get_bloginfo('version');

        // cache repo data
        $this->transient = $this->slug.'-private-repo';

        $this->register_hooks();
    }

    private function remote_data()
    {
        if (false == $remote = get_transient($this->transient)) {
            $remote = wp_remote_get(
                $this->repo,
                [
                    'timeout' => 10,
                    'headers' => [
                        'Accept' => 'application/json',
                    ],
                ]
            );

            if (!is_wp_error($remote) && isset($remote['response']['code']) && 200 === $remote['response']['code'] && !empty($remote['body'])) {
                set_transient($this->transient, $remote, 3600);
            }
        }

        return $remote;
    }

    private function check($res, $action, $args)
    {
        if ('plugin_information' !== $action || $this->slug !== $args->slug) {
            return false;
        }

        $remote = $this->remote_data();

        if (!is_wp_error($remote) && isset($remote['response']['code']) && 200 === $remote['response']['code'] && !empty($remote['body'])) {
            $remote = json_decode($remote['body']);
            if (null === $remote) {
                delete_transient($this->transient);

                return false;
            }

            $res = (object) [];

            $res->name = $remote->name;
            $res->slug = $this->slug;
            $res->version = $remote->version;
            $res->tested = $remote->tested;
            $res->requires = $remote->requires;
            $res->author = $remote->author;

            if (!empty($remote->author_profile)) {
                $res->author_profile = $remote->author_profile;
            }

            $res->download_link = $remote->download_link;
            $res->requires_php = $remote->requires_php;
            $res->last_updated = $remote->last_updated;

            $res->sections = [];
            $res->sections['description'] = $remote->sections->description;

            if (!empty($remote->sections->installation)) {
                $res->sections['installation'] = $remote->sections->installation;
            }

            if (!empty($remote->sections->changelog)) {
                $res->sections['changelog'] = $remote->sections->changelog;
            }

            if (!empty($remote->sections->screenshots)) {
                $res->sections['screenshots'] = $remote->sections->screenshots;
            }

            $res->banners = [];
            if (!empty($remote->banners->low)) {
                $res->banners['low'] = $remote->banners->low;
            }

            if (!empty($remote->banners->high)) {
                $res->banners['high'] = $remote->banners->high;
            }

            return $res;
        }
    }

    private function update($transient)
    {
        if (empty($transient->checked)) {
            return $transient;
        }

        $remote = $this->remote_data();

        if ($remote && !is_wp_error($remote) && isset($remote['response']['code']) && 200 === $remote['response']['code'] && !empty($remote['body'])) {
            $remote = json_decode($remote['body']);
            if (\is_object($remote) && version_compare($this->version, $remote->version, '<') && version_compare($remote->requires, $this->wp_version, '<')) {
                $res = (object) [];
                $res->id = $remote->id;
                $res->slug = $this->slug;
                $res->plugin = $this->hook;
                $res->new_version = $remote->version;
                $res->tested = $remote->tested;
                $res->package = $remote->download_link;
                $res->url = $remote->url;
                $res->icons['1x'] = $remote->icon1;
                if (!empty($remote->icon2)) {
                    $res->icons['svg'] = $remote->icon2;
                }
                $transient->response[$res->plugin] = $res;
            }
        }

        return $transient;
    }

    private function register_hooks()
    {
        add_filter(
            'http_request_host_is_external',
            function ($allow, $host, $url) {
                if ($host === parse_url($this->repo, PHP_URL_HOST)) {
                    return true;
                }
            },
            10,
            3
        );

        add_filter(
            'plugins_api',
            function ($res, $action, $args) {
                return $this->check($res, $action, $args);
            },
            20,
            3
        );

        add_filter(
            'site_transient_update_plugins',
            function ($transient) {
                return $this->update($transient);
            }
        );

        add_action(
            'upgrader_process_complete',
            function ($wp_upgrader, $options) {
                if ('update' !== $options['action']) {
                    return;
                }

                if ('plugin' === $options['type'] && !empty($options['plugins']) && \is_array($options['plugins']) && isset($options['plugins'][$this->hook])) {
                    do_action($this->slug.'_upgrader_process_complete');
                }
            },
            -PHP_INT_MAX,
            2
        );
    }
}
