=== Docket Cache - Object Cache Accelerator ===
Contributors: nawawijamili
Tags: object cache, OPcache, fastcgi, cache, database, optimization, performance, redis, memcached, speed, multisite, server load, docket
Donate link: https://www.paypal.me/ghostbirdme/5usd
Requires at least: 5.4
Tested up to: 5.5
Requires PHP: 7.2.5
Stable tag: 20.08.04
License: MIT
License URI: ./license.txt

A persistent object cache stored as a plain PHP code, accelerates caching with OPcache backend.

== Description ==
The Docket cache is a persistent WordPress Object Cache that is stored as a plain PHP code. Intends to provide an alternative option for those who can't use Redis or Memcached server.

Rather than using [serialize](https://www.php.net/manual/en/function.serialize.php) and [unserialize](https://www.php.net/manual/en/function.unserialize.php) a PHP object to store into flat files, this plugin stores data by converting the object into plain PHP code which results in faster data retrieval and better performance with Zend OPcache enabled.

Kindly refer to the WordPress documentation on [Object Cache](https://make.wordpress.org/hosting/handbook/handbook/performance/#object-cache).

== Why use this plugin? ==
When it comes to reliable persistent Object Cache in WordPress, [Redis](https://redis.io/) or [Memcached](https://memcached.org/) comes on top. However, those solutions require knowledge of server and rarely available at low cost or shared hosting servers.

The only solution is to store the object caches into files. With WordPress, exporting the PHP objects are not easy, most plugin that implements file-based solution will serialize and unserialize the object to store and retrieve the data.

The Docket Cache is better because it converts the object cache into plain PHP code. This solution is faster since WordPress can use the cache directly without running other operation.

== Features ==

- Object caching + OPcache
- Advanced Post Caching
- Object Cache Precaching
- WordPress Translation Caching
- Term Count Queries Optimization
- Post, Page, Comment Count Optimization
- Database Tables Optimization
- WP Options Autoload suspension
- Post Missed Schedule Tweaks
- Cache Log
- Cronbot Service
- Multisite support

== Requirement ==

To use Docket Cache requires minimum:

- PHP 7.2.5
- WordPress 5.4
- Zend OPcache

== Configuration Options ==

To adjust the configuration, please see the [configuration wiki](https://github.com/nawawi/docket-cache/wiki/Constants) page for details.

== WP-CLI Commands ==

To use the WP-CLI commands, please see the [WP-CLI wiki](https://github.com/nawawi/docket-cache/wiki/WP-CLI) page for available commands.

== Development ==
- [Report issues](https://github.com/nawawi/docket-cache/issues)
- [Send Pull requests](https://github.com/nawawi/docket-cache/pulls)
- [Changelog](https://github.com/nawawi/docket-cache/releases)

== Installation ==
To use Docket Cache require minimum PHP 7.2.5, WordPress 5.4 and Zend OPcache for best performance.

1. In your WordPress admin click *Plugins -> Add New*
2. Search plugins "Docket Cache" and click Install Now.
3. Click *Activate* or *Network Activate* in Multisite setups.
4. Enable the object cache under Settings -> Docket Cache, or in Multisite setups under Network Admin -> Settings -> Docket Cache.

== Screenshots ==
1. Plugin Overview.
2. Cache Log.
3. Configuration.
4. Select log to view.
5. Cache view.

== Frequently Asked Questions ==
= What is Object Caching in WordPress? =
Object caching is a process that stores database query results in order to quickly bring them back up next time they are needed.

The cached object will be served promptly from the cache rather than sending multiple requests to a database. This is more efficient and reduces massive unnecessary loads on your server.

In simple terms, object caching allows objects that are used often to be copied and stored at a closer location for quicker use.

= What is Docket Cache in Object Caching? =
By default, the object cache in WordPress is non-persistent. This means that data stored in the cache reside in memory only and only for the duration of the request. Cached data will not be stored persistently across page loads. To make it persistent, the object cache must be stored on a local disk.

Docket Cache is not just stored the object cache, it converts the object cache into plain PHP code. This solution is faster since WordPress can use the cache directly without running other operation.

= What is OPcache in Docket Cache? =
OPcache is a caching engine built into PHP, improves performance by storing precompiled script bytecode in shared memory, thereby removing the need for PHP to load and parse scripts on each request.

Docket Cache converts the object cache into plain PHP code. When read and write cache, it will use OPcache directly which results in faster data retrieval and better performance.

= What’s the difference with the other object cache plugin? =
Docket Cache is an Object Cache Accelerator. It does some optimization of caching like cache post queries, comments counting, WordPress translation and more before storing the object caches.

= Can I pair using it with other cache plugin? =
Yes and No. You can pair using it with page caching plugin, but not with the object cache plugin.

== Upgrade Notice ==
Kindly do manually remove wp-content/object-cache.php and wp-content/cache/docket-cache if an error occurs during updates. Thanks.

== Changelog ==
= 20.08.04 =

New features and fix releases.

- Added Object Cache Precaching
- Added Optimize Database Tables
- Added Suspend WP Options Autoload
- Added Post Missed Schedule Tweaks
- Added OPcache reset
- Added Cache/OPcache Statistics
- Fixed Invalid variable at "maybe_recount_posts_for_term"
- Fixed Checking if file at cachedir_flush, cache_size
- Fixed Cache flush and Drop-in installation, return false if dir/file is not writable

= 20.08.03 =

This is an improved version based on previous releases.

- Added WordPres Translation Caching
- Added Optimization for Term Count Queries

= 20.07.27 =

This is an improved version based on previous releases.

- Added delete expired transients before replace dropin
- Fixed rarely condition, check wp function if exists before use at drop-in file to avoid fatal error, mostly if using apache mod_fcgid

= 20.07.24 =

This is an improved version based on previous releases.

- Added basic configuration interface
- improved cache read/write


Kindly refer to [Github Repo](https://github.com/nawawi/docket-cache/releases) for previous Changelog.

Please do manually remove wp-content/object-cache.php and wp-content/cache/docket-cache if an error occurs during updates. Thanks.


== Credits ==

* Isometric graphic by upklyak/freepik.com
