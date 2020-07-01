=== Docket Cache ===
Contributors: Nawawi Jamili
Tags: caching, cache, object cache
Requires at least: 5.4
Tested up to: 5.4
Requires PHP: 7.2
License: MIT
License URI: https://opensource.org/licenses/MIT

A persistent WordPress Object Cache stored on local disk.

== Description ==

A persistent WordPress Object Cache stored on local disk. Rather than using serialize and unserialize PHP object to store in flat files, Docket Cache stores the data by converting the object into plain PHP code, resulting faster data retrieving and better performance if combined with PHP Opcache.

== Installation ==

1. Install and activate plugin.
2. Enable the object cache under _Settings -> Docket Cache_, or in Multisite setups under _Network Admin -> Settings -> Docket Cache_.

== Configuration Options ==

To adjust the configuration, define any of the following constants in your `wp-config.php` file.

  * `DOCKET_CACHE_MAXTTL` (default: _not set_)

    Set maximum time-to-live (in seconds) for cache keys with an expiration time of `0`.

  * `DOCKET_CACHE_GLOBAL_GROUPS` (default: `['blog-details', 'blog-id-cache', 'blog-lookup', 'global-posts', 'networks', 'rss', 'sites', 'site-details', 'site-lookup', 'site-options', 'site-transient', 'users', 'useremail', 'userlogins', 'usermeta', 'user_meta', 'userslugs']`)

    Set the list of network-wide cache groups that should not be prefixed with the blog-id _(Multisite only)_.

  * `DOCKET_CACHE_IGNORED_GROUPS` (default: `['counts', 'plugins', 'themes', 'comment', 'wc_session_id', 'bp_notifications', 'bp_messages','bp_pages']`)

    Set the cache groups that should not be cached.

  * `DOCKET_CACHE_IGNORED_KEYS` (default: _not set_)

    Set the cache keys that should not be cached.

  * `DOCKET_CACHE_DISABLED` (default: _not set_)

    Set to `true` to disable the object cache at runtime.

  * `DOCKET_CACHE_PATH` (default: `WP_CONTENT_DIR/cache/docket-cache`)

    Set the cache directory.

  * `DOCKET_CACHE_DEBUG` (default: _not set_)

    Set to `true` to enable debug log in WP_CONTENT_DIR/object-cache.log

== WP-CLI Commands ==

To use the WP-CLI commands, make sure the plugin is activated:

    wp plugin activate docket-cache

The following commands are supported:

  * `wp docket-cache status`

    Show the Docket object cache status.

  * `wp docket-cache enable`

    Enables the Docket object cache. Default behavior is to create the object cache drop-in, unless an unknown object cache drop-in is present.

  * `wp docket-cache disable`

    Disables the Docket object cache. Default behavior is to delete the object cache drop-in, unless an unknown object cache drop-in is present.

  * `wp docket-cache update-dropin`

    Updates the Docket object cache drop-in. Default behavior is to overwrite any existing object cache drop-in.


== Screenshots ==


== Changelog ==

= 1.0.0 =

  * Initial release
