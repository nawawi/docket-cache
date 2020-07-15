=== Docket Cache ===
Contributors: nawawijamili,rutflare
Tags: object cache, cache, performance, flat-file
Donate link: https://www.paypal.me/ghostbirdme/5usd
Requires at least: 5.4
Tested up to: 5.4.2
Requires PHP: 7.2.5
Stable tag: trunk
License: MIT
License URI: ./license.txt

A file-based persistent WordPress Object Cache stored as a plain PHP code.

== Description ==
The Docket cache is a file-based persistent WordPress Object Cache that stored as a plain PHP code. Intends to provide as alternative options for who can't use Redis or Memcache server.

Rather than using serialize and unserialize a PHP object to store into flat files, Docket Cache stores the data by converting the object into plain PHP code, resulting faster data retrieving and better performance with PHP OPCache enabled.

== Support & Contributions ==
- [Report issues](https://github.com/nawawi/docket-cache/issues)
- [Send Pull requests](https://github.com/nawawi/docket-cache/pulls)

== Configuration Options ==

To adjust the configuration, please see the [configuration options](https://github.com/nawawi/docket-cache#configuration-options) for a full list.

== WP-CLI Commands ==

To use the WP-CLI commands, please see the [WP-CLI commands](https://github.com/nawawi/docket-cache#wp-cli-commands) for a full list.

== Installation ==
To use Docket Cache require minimum PHP 7.2.5, WordPress 5.4 and PHP OPcache for better performance.

1. In your WordPress admin click *Plugins -> Add New*
2. Search plugins "Docket Cache" and click Install Now.
3. Click *Activate* or *Network Activate* in Multisite setups.
4. Enable the object cache under Settings -> Docket Cache, or in Multisite setups under Network Admin -> Settings -> Docket Cache.

== Screenshots ==
1. Plugin Overview.
2. Cache Log.
3. Options Info.

== Upgrade Notice ==
= 20.07.14 =
- DOCKET_CACHE_DEBUG has been renamed to  DOCKET_CACHE_LOG.
- Please do manually remove wp-content/object-cache.php and wp-content/cache/docker-cache if an error occurs during updates.

= 20.07.09 ==
- Versions are as follows: Year.Month.Day of new release.
- Please do manually remove wp-content/object-cache.php and wp-content/cache/docker-cache if an error occurs during updates.

== Changelog ==
= 20.07.14 =
- Fixed: wrong conditional -> sanitize_second
- Fixed: using class method instead of closure function
- Fixed: cache file, write to temp file first to avoid data half write
- Fixed: dont cache if size more than 1MB.
- Fixed: null type data, set data to empty -> object-cache.php
- Fixed: chmod wrong file -> object-cache.php
- Fixed: file locking when read write cache file
- Fixed: only truncate when flush the cache files
- Fixed: DOCKET_CACHE_DISABLED doesnt work
- Added: tweaks for woocommerce
- Added: filtered_group to cache group with conditional
- Added: class Files to handle most of filesystem functions
- Added: garbage collector
- Added: DOCKET_CACHE_GC constant to disable garbage collector
- Added: DOCKET_CACHE_FLUSH_DELETE to enable remove rather than truncate when flush
- Updated: replace DOCKET_CACHE_DEBUG* to DOCKET_CACHE_LOG*
- Updated: using trigger_error instead of throw Exception when library not found -> object-cache.php

= 20.07.09 =
- Versions are as follows: Year.Month.Day of new release.
- Fixed: Invalid data -> docket_update, docket_get.
- Added: opcache_invalidate after updating cache -> opcache_flush_file.
- Added: performance tweaks -> register_tweaks.
- Added: Advanced Post Cache from vip-go.

= 1.0.0 =
- Initial release of the plugin.
