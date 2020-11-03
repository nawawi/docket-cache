=== Docket Cache - Object Cache Accelerator ===
Contributors: nawawijamili
Tags: object cache, OPcache, fastcgi, cache, database, Optimisation, performance, redis, memcached, speed, multisite, server load, docket
Donate link: https://www.paypal.me/ghostbirdme/5usd
Requires at least: 5.4
Tested up to: 5.5
Requires PHP: 7.2.5
Stable tag: 20.10.03
License: MIT
License URI: ./license.txt

Supercharge your website using a persistent object cache, accelerates caching with OPcache backend.

== Description ==
The Docket cache is a persistent WordPress Object Cache that is stored as a plain PHP code. Intends to provide an alternative option for those who can't use Redis or Memcached server.

Rather than using [serialize](https://www.php.net/manual/en/function.serialize.php) and [unserialize](https://www.php.net/manual/en/function.unserialize.php) a PHP object to store into flat files, this plugin stores data by converting the object into plain PHP code which results in faster data retrieval and better performance with Zend OPcache enabled.

Kindly refer to the documentation on [Caching In WordPress](https://docs.docketcache.com/resources/caching-in-wordpress).

== Why use this plugin? ==
When it comes to reliable persistent Object Cache in WordPress, [Redis](https://redis.io/) or [Memcached](https://memcached.org/) comes on top. However, those solutions require knowledge of server and rarely available at low cost or shared hosting servers.

The only solution is to store the object caches into files. With WordPress, exporting the PHP objects are not easy, most plugin that implements file-based solution will serialize and unserialize the object to store and retrieve the data.

The Docket Cache is better because it converts the object cache into plain PHP code. This solution is faster since WordPress can use the cache directly without running other operation.

== Features ==

- Object caching + OPcache
- Advanced Post Caching
- Object Cache Precaching
- WordPress Translation Caching
- WordPress Core Query Optimisation
- Term Count Queries Optimisation
- Post, Page, Comment Count Optimisation
- Database Tables Optimisation
- WooCommerce Optimisation
- WP Options Autoload suspension
- Post Missed Schedule Tweaks
- Object Cache + OPcache Stats
- Cache Log
- Cronbot Service
- Multisite support
- Multi-Network support

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
- [Changelog](https://raw.githubusercontent.com/nawawi/docket-cache/master/changelog.txt)
- [Documentation](https://docs.docketcache.com)
- [Sponsorship](https://docketcache.com/sponsorship)

== Installation ==
To use Docket Cache require minimum PHP 7.2.5, WordPress 5.4 and Zend OPcache for best performance.

1. In your WordPress admin click **Plugins -> Add New**
2. Search plugins "Docket Cache" and click Install Now.
3. Click **Activate** or **Network Activate** in Multisite setups.
4. Click **Docket Cache** in the left menu to access the admin page.

Please wait around 5 seconds for Docket Cache ready to cache the objects.

== Screenshots ==
1. Overview.
2. Cache Log.
3. Cache view.
4. Cache content.
5. Cronbot.
6. Configuration.
7. Multisite / Multi-Network Overview.
8. Multisite / Multi-Network Cronbot.


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

= What is the minimum RAM required to use with shared hosting? =
By default, WordPress allocates the memory limit to 256 MB. Combined with MySQL and Web Server, you need more than 256 MB. If you're using a cheap hosting plan that allocates only 256 MB for totals usage. It is not enough, and Docket Cache can't improve your website performance.

= What’s the difference with the other object cache plugins? =
Docket Cache is an Object Cache Accelerator. It does some Optimisation of caching like cache post queries, comments counting, WordPress translation and more before storing the object caches.

= Can I pair using it with other cache plugin? =
Yes and No. You can pair using it with page caching plugin, but not with the object cache plugin.

= I'm using a VPS server. Can I use Docket Cache to replace Redis? =
Yes, you can. It can boost more your WordPress performance since there is no network connection need to makes and no worry about memory burst, cache-key conflict and error-prone caused by the improper settings.

== Upgrade Notice ==
Kindly do manually remove wp-content/object-cache.php and wp-content/cache/docket-cache if an error occurs during updates. Thanks.

== Changelog ==
= 20.10.03 =

- Fixed nwdcx_optget() -> missing sql syntax.

Thanks to Mark Barnes (@mark8barnes) for reporting this issue.

= 20.10.02 =

- Fixed output buffering issue with page caching.

= 20.10.01 =

This is Major Release based on previous releases.

- Improved precaching.
- Improved cache stats.
- Improved garbage collector.
- Improved disk I/O and CPU usage.
- Added new constant DOCKET_CACHE_CONTENT_PATH.

Thank you for using docket cache.

= 20.09.07 =

Fix release.

- Fixed Deactivate WooCommerce Widget, prevent error notice _doing_it_wrong for the_widget.
- Fixed Precaching, always strip query string doing_wp_cron.
- Fixed nwdcx_network_multi function, replace with simple query to detect multinetwork condition.

= 20.09.06 =

Fix release.

- Fixed Misc WooCommerce Tweaks, remove checking for woo class exist since we at the first sequence.
- Fixed Precaching, strip query string, replace preg_replace with strtok.
- Added Deactivate WooCommerce Admin, Widget, Dashboard Metabox to configuration page instead of bundling with misc tweaks.

= 20.09.05 =

Enhance and Fix release.

- Fixed Suspend WP Options Autoload. Use hook instead of change autoload value in the database. WordPress will treat all key as autoload if none has set to yes.
- Fixed Drop-in after delay. Remove transient from database if expiry not set and already expired.
- Added Remove XML-RPC / Pingbacks, WP Header Junk into the configuration instead of bundling with Misc Performance Tweaks.
- Added Remove WP Emoji, WP Feed, WP Embed options.
- Added ReqAction class to handle action.
- Added wp_cache_flush_group.
- Added try to set SQL_BIG_SELECTS=1 for shared hosting.

= 20.09.04 =

Enhance and Fix release.

- Fixed OPcache Stats, invalid calculation for cache files.
- Fixed Cronbot, run scheduled event in multisite.
- Added Optimize WP Query option at configuration page.
- Added the Check Critical Version description to comply with WordPress policy.

= 20.09.03 =

New features and fix release.

- Added Multi-Network for Multisite.
- Added Object OPcache, WP OPcache stats.
- Added lookup_* methods to handle our temp internal data.
- Added locking file to suspend cache addition when doing flush.
- Fixed replace update_user_meta with lookup function to makes query-monitor happy.
- Fixed Admin interface, loading spinner should not display when no action.
- Fixed CronAgent::run_wpcron(), reset doing_cron if locked.
- Fixed CronAgent::run_wpcron(), halt if run reach maximum CRONBOT_MAX for site in multisite.

= 20.09.02 =

Enhance and Fix release.

- Cron event, docketcache_optimizedb and docketcache_checkversion only run on main site if multisite.
- Cron event, checkversion change to every 3 days to avoid excessive process.
- Cronbot, change Test Ping to use it own action, to avoid conflict with connect/disconnect action.
- Cronbot, max to 5 sites if multisite, define DOCKET_CACHE_CRONBOT_MAX to change it.
- CronAgent::send_action, allow capture error if second argument set to pong.
- Canopt::keys, added description for each key.
- Cleanup admin interface.

Thanks.

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


Kindly refer to [changelog.txt](https://raw.githubusercontent.com/nawawi/docket-cache/master/changelog.txt) for previous changes.

Please do manually remove wp-content/object-cache.php and wp-content/cache/docket-cache if an error occurs during updates. Thanks.
