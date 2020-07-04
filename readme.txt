=== Docket Cache ===
Contributors: Nawawi Jamili
Tags: caching, cache, object cache
Requires at least: 5.4
Tested up to: 5.4
Requires PHP: 7.2
License: MIT
License URI: https://github.com/nawawi/docket-cache/blob/master/LICENSE.txt

A persistent WordPress Object Cache stored on local disk.
  
== Description ==

The Docket cache is a persistent WordPress Object Cache that stored on local disk. Rather than using `serialize` and `unserialize` a PHP object to store into flat files, Docket Cache stores the data by converting the object into plain PHP code, resulting faster data retrieving and better performance if combined with PHP OPcache.

== Installation ==

To use Docket Cache require minimum PHP 7.2, WordPress 5.4 and PHP OPcache for better performance.

1. Install and activate plugin.
2. Enable the object cache under _Settings -> Docket Cache_, or in Multisite setups under _Network Admin -> Settings -> Docket Cache_.

== Configuration Options ==

To adjust the configuration, define any of the following constants in your `wp-config.php` file.

  * `DOCKET_CACHE_MAXTTL` (default: `86400`)

    Set maximum time-to-live (in seconds) for cache keys with an expiration time of `0`.

  * `DOCKET_CACHE_IGNORED_GROUPS` (default: `['counts', 'plugins', 'themes', 'comment', 'wc_session_id', 'bp_notifications', 'bp_messages','bp_pages']`)

    Set the cache groups that should not be cached.

  * `DOCKET_CACHE_IGNORED_KEYS` (default: _not set_)

    Set the cache keys that should not be cached.

  * `DOCKET_CACHE_DISABLED` (default: _not set_)

    Set to `true` to disable the object cache at runtime.

  * `DOCKET_CACHE_PATH` (default: `WP_CONTENT_DIR/cache/docket-cache`)

    Set the cache directory.

  * `DOCKET_CACHE_DEBUG` (default: _not set_)

    Set to `true` to enable debug log.

  * `DOCKET_CACHE_DEBUG_FLUSH` (default: `true`)

    Set to `true` to empty the log file when object cache flushed.

  * `DOCKET_CACHE_DEBUG_SIZE` (default: `10000000`)

    Set the maximum size of log file in byte. Default set to 10MB.

  * `DOCKET_CACHE_PRELOAD` (default: `false`)

    Set to `true` to enable cache preloading after the cache has been flushed and after installation of drop-in file.

  * `DOCKET_CACHE_PRELOAD_ADMIN` (default: `['options-general.php', 'options-writing.php', 'options-reading.php', 'options-discussion.php', 'options-media.php', 'options-permalink.php', 'edit-comments.php', 'profile.php', 'users.php', 'upload.php', 'plugins.php', 'edit.php', 'themes.php', 'tools.php', 'widgets.php', 'update-core.php']`)

    Set the list of admin path _(/wp-admin/<path>)_ to preload.

**Multisite Options**

  * `DOCKET_CACHE_GLOBAL_GROUPS` (default: `['blog-details', 'blog-id-cache', 'blog-lookup', 'global-posts', 'networks', 'rss', 'sites', 'site-details', 'site-lookup', 'site-options', 'site-transient', 'users', 'useremail', 'userlogins', 'usermeta', 'user_meta', 'userslugs']`)

    Set the list of network-wide cache groups that should not be prefixed with the blog-id.

  * `DOCKET_CACHE_PRELOAD_NETWORK` (default: `['update-core.php', 'sites.php', 'users.php', 'themes.php', 'plugins.php', 'settings.php']`)

    Set the list of network admin path _(/wp-admin/network/<path>)_ to preload.


== WP-CLI Commands ==

To use the WP-CLI commands, make sure the plugin is activated:

    wp plugin activate docket-cache

The following commands are supported:

  * `wp cache status`

    Show the Docket object cache status.

  * `wp cache enable`

    Enables the Docket object cache. Default behavior is to create the object cache drop-in, unless an unknown object cache drop-in is present.

  * `wp cache disable`

    Disables the Docket object cache. Default behavior is to delete the object cache drop-in, unless an unknown object cache drop-in is present.

  * `wp cache update-dropin`

    Updates the Docket object cache drop-in. Default behavior is to overwrite any existing object cache drop-in.


== Changelog ==

= 1.0.0 =

- Public release.
