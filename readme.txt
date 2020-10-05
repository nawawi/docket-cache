=== Docket Cache - Object Cache Accelerator ===
Contributors: nawawijamili
Tags: object cache, OPcache, fastcgi, cache, database, optimization, performance, redis, memcached, speed, multisite, server load, docket
Donate link: https://www.paypal.me/ghostbirdme/5usd
Requires at least: 5.4
Tested up to: 5.5
Requires PHP: 7.2.5
Stable tag: 20.09.01
License: MIT
License URI: ./license.txt

Supercharge your website using a persistent object cache, accelerates caching with OPcache backend.

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

== Requirements ==

To use Docket Cache requires minimum:

- PHP 7.2.5
- WordPress 5.4
- Zend OPcache

== Configuration Options ==

To adjust the configuration, please refer to [Configuration](https://docs.docketcache.com/configuration) documentation for details.

== WP-CLI Commands ==

To use the WP-CLI commands, please refer to [WP-CLI](https://docs.docketcache.com/wp-cli) documentation for available commands

== Development ==
- [Report issues](https://github.com/nawawi/docket-cache/issues)
- [Send Pull requests](https://github.com/nawawi/docket-cache/pulls)
- [Changelog](https://github.com/nawawi/docket-cache/releases)
- [Documentation](https://docs.docketcache.com)

== Installation ==
To use Docket Cache require minimum PHP 7.2.5, WordPress 5.4 and Zend OPcache for best performance.

1. In your WordPress admin click **Plugins -> Add New**
2. Search plugins "Docket Cache" and click Install Now.
3. Click **Activate** or **Network Activate** in Multisite setups.
4. Click **Docket Cache** in the left menu to access the admin page.

== Screenshots ==
1. Overview.
2. Cache Log.
3. Cache view.
4. Cache content.
5. Cronbot.
6. Configuration.
7. Cronbot on multisite.


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

= What is a RAM disk in Docket Cache? =
A RAM disk is a representation of a hard disk using RAM resources, and it can take the form of a hardware device or a virtual disk. 

Read and write speed on RAM is multiple times faster than SSD drives therefore storing Docket Cache files on a RAM disk greatly increases it's performance.

Do note that creating RAM disks requires server administrative permission (root access) so this solution is not suitable for shared hosting servers.

This is an example command to create and use a RAM disk with Docket Cache:

`$ cd wp-content/
$ sudo mount -t tmpfs -o size=500m tmpfs ./cache/docket-cache`

To mount the cache path automatically on boot, you need to update your /etc/fstab file.

Kindly refer to the articles below about RAM disk:

1. [How to Easily Create RAM Disk](https://www.linuxbabe.com/command-line/create-ramdisk-linux)
2. [What Is /dev/shm And Its Practical Usage](https://www.cyberciti.biz/tips/what-is-devshm-and-its-practical-usage.html)
4. [Creating A Filesystem In RAM](https://www.cyberciti.biz/faq/howto-create-linux-ram-disk-filesystem/)

To use it in Windows OS, create RAM Disk and change [DOCKET_CACHE_PATH](https://docs.docketcache.com/configuration#docket_cache_path) point to RAM Disk drive.

= What’s the difference with the other object cache plugin? =
Docket Cache is an Object Cache Accelerator. It does some optimization of caching like cache post queries, comments counting, WordPress translation and more before storing the object caches.

= Can I pair using it with other cache plugin? =
Yes and No. You can pair using it with page caching plugin, but not with the object cache plugin.

== Upgrade Notice ==
Kindly do manually remove wp-content/object-cache.php and wp-content/cache/docket-cache if an error occurs during updates. Thanks.

== Changelog ==
= 20.09.01 =

This is Major Release based on previous releases.

- Improved admin interface structure.
- Improved admin menu using top-level instead of submenu from settings.
- Improved Cronbot to support multisite.
- Added Actions pane at overview page to flush cache/OPcache and enable/disable object cache.
- Added Auto update options at configuration page.
- Added Cron event checkversion to check for critical update, define DOCKET_CACHE_CHECKVERSION constant to false to disable it.
- Fixed CronAgent issue with doing_cron locked on multisite.
- Fixed Cron event missing watchproc hook on unregister.

Please do "Flush Cache" after/before installing this update. Thanks.

= 20.08.18 =

This is an enhanced version based on previous fix releases.

- Fixed Cache Log -> change to native file_put_contents instead of put() to avoid early unlock result to truncate.
- Fixed Filesystem::put() -> add blocking option to avoid early unlock.
- Fixed dc_save() -> invalid conditional for is_data_updated().
- Fixed skip_stats() -> add checking for ignored groups.
- Fixed unlink -> add checking is_file to avoid php warning and make query-monitor happy.
- Fixed Canopt::setlock() -> set file permission if write true.
- Added DOCKET_CACHE_IGNORED_PRECACHE constant to exclude group:key from precaching.
- Added DOCKET_CACHE_IGNORED_GROUPKEY constant to exclude group:key from persistent cache.
- Added CLI command "unlock" to clear all lock files.

= 20.08.17 =

Fix release.

- Fixed CronAgent, woocommerce -> get_cart - not be called before the wp_loaded action.
- Fixed WP_Object_Cache::$cache_hits, WP_Object_Cache::$cache_misses -> hit rate.

= 20.08.16 =

Fix release.

- Fixed WP_Object_Cache::set() -> only write to disk if data change and expiry not 0.
- Fixed WP_Object_Cache::dc_precache_set -> only write to disk if data change.

= 20.08.15 =

Fix release.

- Fixed precaching, invalid conditional for query string.
- Fixed cache maxttl, missing timestamp in cache meta.
- Fixed cache, flush user_meta group before login and after logout.
- Fixed micro optimization, before using regex functions.
- Fixed transient, remove all from db before activate our dropin.

= 20.08.14 =

Fix release.

- Fixed unserialize data if serialized before coverting to php code.

= 20.08.13 =

Fix release.

- Fixed Advanced Post Cache, invalid comment counting.
- Fixed Precaching, exclude docketcache-post group.
- Set garbage collector always enable.

= 20.08.12 =

This is an improved version based on previous releases.

- Use our own locking functions instead of wp_cache_* functions.
- Standardize hook prefix, rename docket-cache to docketcache.
- Increase default maxfile to 50000.
- Cronbot, remove scheduled events if hooks has errors or not exist.
- Cronbot, added "Run All Now" at admin interface.
- Garbage collector, remove older files if maxttl defined. By default set to 2 days (172800 seconds).
- Cache group post_meta and options, set to expire in 24 hours if no expiration time.
- Precaching, data expire set to 4 hours and maximum 5000 lists at a time.
- Precaching, append site host as key to allow use it on multisite.
- Precaching allow query string if user_logged_in() true and uri match with "/wp-admin/(network/)?.\*?\.php\?.\*?".
- Preloading, add locking to prevent run multiple time in short period.
- Standardize data size in binary rather than decimal.
- DOCKET_CACHE_MAXTTL, only numbers between 86400 and 2419200 are accepted (1 day - 28 days).
- DOCKET_CACHE_MAXSIZE, only numbers between 1000000 and 10485760 are accepted (1 MB - 10 MB).
- DOCKET_CACHE_MAXSIZE_DISK, minimum 1048576 (1MB), default set to 500MB.
- CLI, new command to run garbage collector "wp cache gc".

Please do "Flush Cache" after/before installing this update. Thanks.

= 20.08.11 =

This is an enhanced version based on previous fix releases.

- Fixed Object cache stats, counting using ajax worker and only run on the overview page.
- Fixed Precaching, completely ignore query string and limit to 1000 urls.
- Fixed Caching, maxttl always set to 0 to avoid unexpected behavior from others plugin.
- Fixed Cronbot, bepart::is_ssl() check if site behind cloudflare/proxy.
- Added Transient, Set the expiry time to 12 hours if expiration not set.
- Added Garbage collector, scheduled to every 5 minutes instead of 30 minutes. Enable cronbot service if your site wp-cron not running active.
- Added Object cache stats, enable/disable at configuration page.
- Added DOCKET_CACHE_MAXFILE constant, attempting to reduce cache file if bigger than default 5000 files. Only numbers between 200 and 200000 are accepted.

Please do "Flush Cache" after/before installing this update. Thanks.

= 20.08.10 =

This is a hotfix release. Please do "Flush Cache" after/before installing this update. Thanks.

- Fixed cache file grow quickly if enable advanced post cache, maxttl should always set to 0.

= 20.08.09 =

This is a hotfix release. Please do "Flush Cache" after/before installing this update. Thanks.

- Fixed empty value return by constans->is_int, constans->is_array.

= 20.08.08 =

This is a hotfix release.

- Fixed cache stats, do collecting data in background to avoid lagging.
- Fixed cronbot, execute cron process directly without wp-cron.php, to avoid http connection error. 
- Added cache stats options, Enable/disable object cache stats at Overview page. 

= 20.08.07 =

Fix release.

- Fixed precaching, ignore query to avoid junk query string
- Fixed cronbot, add locking to avoid multiple send in short period
- Fixed cronbot, remove site url trailing slash
- Fixed cache stats overview

= 20.08.06 =

Fix release.

- Fixed precache "class not found"
- Fixed cronbot send site url instead of main site url if multisite
- Fixed cronbot recheck connection

= 20.08.05 =

New features and fix release.

- Added Cronbot Service
- Fixed precache overmissed
- Fixed for security reason, exclude *user* group from cache log if WP_DEBUG false
- Fixed cache_read() -> Registry::getClassReflector -> fatal error class not found
- Fixed PostCache::setup_hooks() -> array_shift error byreference
- Fixed get_proxy_ip() -> return bool instead of IP string
- Fixed ajax worker -> cache preload, cache flush, log flush

= 20.08.04 =

New features and fix release.

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


Kindly refer to [Github Repo](https://github.com/nawawi/docket-cache/releases) for previous Changelog.

Please do manually remove wp-content/object-cache.php and wp-content/cache/docket-cache if an error occurs during updates. Thanks.
