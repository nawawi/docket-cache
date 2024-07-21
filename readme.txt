=== Docket Cache - Object Cache Accelerator ===
Contributors: nawawijamili
Tags: object cache, OPcache, cache, database, performance
Requires at least: 5.4
Tested up to: 6.6
Requires PHP: 7.2.5
Stable tag: 24.07.01
License: MIT
License URI: https://github.com/nawawi/docket-cache/blob/master/LICENSE.txt

Supercharge your website using a persistent object cache, accelerates caching with OPcache backend.

== Description ==
The Docket cache is a persistent WordPress Object Cache that is stored as a plain PHP code. Intends to provide an alternative option for those who can't use Redis or Memcached server.

Rather than using [serialize](https://www.php.net/manual/en/function.serialize.php) and [unserialize](https://www.php.net/manual/en/function.unserialize.php) a PHP object to store into flat files, this plugin stores data by converting the object into plain PHP code which results in faster data retrieval and better performance with Zend OPcache enabled.

Kindly refer to the documentation on [Caching In WordPress](https://docs.docketcache.com/resources/caching-in-wordpress).

== Why use this plugin? ==
When it comes to reliable persistent Object Cache in WordPress, [Redis](https://redis.io/) or [Memcached](https://memcached.org/) comes on top. However, those solutions require knowledge of server and rarely available at low cost or shared hosting servers.

The only solution is to store the object caches into files. With WordPress, exporting the PHP objects are not easy, most plugin that implements file-based solution will serialize and unserialize the object to store and retrieve the data.

Docket Cache takes a better approach by turning the object cache into plain PHP code. This solution is faster since WordPress can use the cache directly without running other operations.

== Features ==

- Object caching + OPcache
- Advanced Post Caching
- Object Cache Precaching
- WordPress Menu Caching
- WordPress Translation Caching
- WordPress Core Query Optimisation
- Term Count Queries Optimisation
- Post, Page, Comment Count Optimisation
- Database Tables Optimisation
- WooCommerce Optimisation
- WP Options Autoload suspension
- Post Missed Schedule Tweaks
- Object Cache + OPcache Stats + OPcache Viewer
- Cache Log
- Cronbot Service
- WP-CLI support
- Multisite / Multi-Network support

== Requirements ==

To use Docket Cache requires minimum:

- PHP 7.2.5
- WordPress 5.4
- Zend OPcache

== Documentation ==

To adjust the plugin behaviour, installation or manage through a command line, please refer to the [Documentation](https://docs.docketcache.com) for details.

== Development ==
- [Github Repo](https://github.com/nawawi/docket-cache/)
- [Changelog](https://raw.githubusercontent.com/nawawi/docket-cache/master/changelog.txt)

== Sponsor this project ==

[Fund Docket Cache](https://docketcache.com/sponsorship/?utm_source=wp-readme&utm_campaign=sponsor-uri&utm_medium=wporg) one-off or recurring payment to support our open-source development efforts.

**Noteworthy Sponsors:**

A heartful thanks and appreciation.

- [Jimat Hosting](https://jimathosting.com/?utm_source=docketcache&utm_campaign=plugin-uri&utm_medium=wporg)
- [Themecloud](https://www.themecloud.io/?utm_source=docketcache&utm_campaign=plugin-uri&utm_medium=wporg)
- [Websavers Inc](https://websavers.ca/?utm_source=docketcache&utm_campaign=plugin-uri&utm_medium=wporg)
- [Avunu LLC](https://avu.nu/?utm_source=docketcache&utm_campaign=plugin-uri&utm_medium=wporg)
- [Linqru](https://linqru.jp/?utm_source=docketcache&utm_campaign=plugin-uri&utm_medium=wporg)
- [Gentleman's Guru](https://www.gentlemansguru.com/?utm_source=docketcache&utm_campaign=plugin-uri&utm_medium=wporg)
- [SecurePay](https://www.securepay.my/?utm_source=docketcache&utm_campaign=plugin-uri&utm_medium=wporg)
- [DNSVault](https://dnsvault.net/?utm_source=docketcache&utm_campaign=plugin-uri&utm_medium=wporg)
- [Exnano Creative](https://exnano.io/?utm_source=docketcache&utm_campaign=plugin-uri&utm_medium=wporg)


Other sponsors are mentioned in the [honourable list](https://github.com/nawawi/docket-cache/issues/5)

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
OPcache is a caching engine built into PHP, that improves performance by storing precompiled script bytecode in shared memory, thereby removing the need for PHP to load and parse scripts on each request.

Docket Cache converts the object cache into plain PHP code. When reading and writing cache, it will use OPcache directly which results in faster data retrieval and better performance.

= What is the Cronbot Service in Docket Cache? =
The Cronbot is an external service that pings your website every hour to keep WordPress Cron running actively. 

This service offered as an alternative option and is not compulsory to use. By default, this service not connected to the [end-point server](https://cronbot.docketcache.com/). You can completely disable it at the configuration page.

= What is Garbage Collector in Docket Cache? =
Garbage Collector is a Cron Event that runs every 5 minutes to monitor cache files purposely for cleanup and collecting stats.

= What is a RAM disk in Docket Cache? =
A RAM disk is a representation of a hard disk using RAM resources, and it can take the form of a hardware device or a virtual disk. 

Read and write speed on RAM is multiple times faster than SSD drives therefore storing Docket Cache files on a RAM disk greatly increases it's performance.

Do note that creating RAM disks requires server administrative permission (root access) so this solution is not suitable for shared hosting servers.

This is an example command to create and use a RAM disk with Docket Cache:

`$ cd wp-content/
$ sudo mount -t tmpfs -o size=500m tmpfs ./cache/docket-cache`

To mount the cache path automatically on boot, you need to update your /etc/fstab file.

Please refer to the articles below about RAM disk:

1. [How to Easily Create RAM Disk](https://www.linuxbabe.com/command-line/create-ramdisk-linux)
2. [What Is /dev/shm And Its Practical Usage](https://www.cyberciti.biz/tips/what-is-devshm-and-its-practical-usage.html)
4. [Creating A Filesystem In RAM](https://www.cyberciti.biz/faq/howto-create-linux-ram-disk-filesystem/)

To use it in Windows OS, create RAM Disk and change [DOCKET_CACHE_PATH](https://docs.docketcache.com/configuration#docket_cache_path) point to RAM Disk drive.

= What is the minimum RAM required to use with shared hosting? =
By default, WordPress allocates the memory limit to 256 MB. Combined with MySQL and Web Server, you need more than 256 MB. If you're using a cheap hosting plan that allocates only 256 MB for totals usage. It is not enough, and Docket Cache can't improve your website performance.

= What's the difference with the other object cache plugins? =
Docket Cache is an Object Cache Accelerator. It does some optimization of caching like cache post queries, comments counting, WordPress translation and more before storing the object caches.

= Can I pair using it with other cache plugin? =
Yes and No. You can pair using it with page caching plugin, but not with the object cache plugin.

= Can I pair using it with LiteSpeed Cache? =
Yes, you can. The LiteSpeed Cache plugin has an Object Cache feature. Currently, by default, it will prompt a notice asking to disable Docket Cache. You only need to turn off LiteSpeed Cache Object Cache in order to use Docket Cache.

= Can I use Docket Cache on heavy WooCommerce stores? =
Yes and No. As suggested, Docket Cache is an alternative to in-memory caches like Redis and Memcached. It depends on how your store has been setups. It may require further tuning to the configuration and may involve other optimisations.

= I'm using a VPS server. Can I use Docket Cache to replace Redis? =
Yes, you can. It can boost more your WordPress performance since there is no network connection need to makes and no worry about memory burst, cache-key conflict and error-prone caused by the improper settings.

== Upgrade Notice ==
Please do manually remove wp-content/object-cache.php and wp-content/cache/docket-cache if an error occurs during updates. Thanks.

== Changelog ==
= 24.07.01 =
- Fixed: View::tooltip() -> Typos "dan".
- Fixed: Filesystem::sanitize_maxsizedisk() -> Returns default if empty.
- Added: DOCKET_CACHE_OPCVIEWER_SHOWALL constant to enable/disable listing all opcache file.
- Added: DOCKET_CACHE_PATH_NETWORK_(n) constant, to change default cache path for multinetwork. (n) for network Id.

= 23.08.02 =
- Fixed: Tweaks::post_missed_schedule() -> Prevent post publish immediately.
- Fixed: WP_Object_Cache() -> Add setters and getters for backwards compatibility.
- Added: TWEAKS_SINGLESEARCHREDIRECT_DISABLED constant to disable redirection of single search results to the page.
- Updated: Tested up to 6.4.

= 23.08.01 =
- Fixed: LimitBulkedit::bulk_editing_is_limited() -> Deprecated Constant FILTER_SANITIZE_STRING.
- Fixed: restrict_api error notice.
- Fixed: Outdated php warning is triggered when Disable Serve Happy Checking is enabled.
- Updated: Tested up to 6.3.

= 22.07.05 =
- Fixed: Plugin::register_plugin_hooks() -> Undefined property: stdClass::$slug.
- Fixed: Event::garbage_collector() -> Stale cache, invalid filter for comment_feed.
- Fixed: Event::garbage_collector() -> Stale cache, add filter for adjacent_post, wp_get_archives and get_comment_child_ids.
- Fixed: Tweaks::wplazyload() -> Add filter for wp_get_attachment_image_attributes.
- Fixed: WP_Object_Cache::dc_save() -> Returns false if data type is "unknown type".
- Added: Filesystem::is_wp_cache_group_queries() -> Match group for *-queries.
- Added: WP_Object_Cache::maybe_expire() ->  Match group for *-queries.

Thanks to Ronny from web55.se for bug report.

= 22.07.04 =
- Fixed: Advanced Post Cache -> Only visible to wp < 6.1.1 as it is already implemented in wp core (WP_Query caching).
- Fixed: Filesystem::shutdown_cleanup() -> Avoid cleanup on shutdown if a file is empty.
- Fixed: Plugin::register_plugin_hooks() -> Do not load the CronAgent class if cronbot is disabled.
- Fixed: TermCount() -> Invalid usage of clean_term_cache.
- Fixed: WP_Object_Cache::$cache_hits, WP_Object_Cache::$cache_misses -> Retain same as non persistent.
- Fixed: Prevent performance-lab from overwriting the object cache drop-in.
- Fixed: Becache::export_alloptions() -> Only export option with autoload 'yes' and not transient.
- Fixed: Becache::store_cache() -> Miss match array_serialize.
- Added:  WP_Object_Cache::$persistent_cache_hits -> Stats hits from cache file.
- Added: Tweaks::wpquery() -> wp_allow_query_attachment_by_filename for wp > 6.0.1.
- Added: Configuration -> Cache Options, Retain Transients in Db.
- Added: Configuration -> Optimisations, Limit Bulk Edit Actions.
- Added: Configuration -> Wp Tweaks, Deactivate Post Via Email.
- Added: DOCKET_CACHE_TRANSIENTDB constant to enable retaining Transients in the database.
- Added: DOCKET_CACHE_IGNORED_TRANSIENTDB constant. A list of cache keys that remain in the object cache instead of the db.
- Added: DOCKET_CACHE_POSTVIAEMAIL constant to enable deactivation Post Via Email.
- Added: DOCKET_CACHE_LIMITBULKEDIT constant to enable Limit Bulk Edit Actions.
- Added: DOCKET_CACHE_LIMITBULKEDIT_LIMIT constant to change bulk edit limit. By default it is set as 100..
- Changed: Advanced Post Cache, some part of the code has been moved to Tweaks::wpquery().
- Removed: WP_Object_Cache::is_data_uptodate() -> No longer needed.
- Updated: Symfony component -> symfony/var-exporter v5.4.21.

= 22.07.03 =
- Fixed: Tweaks::woocommerce_misc() -> Check if action_scheduler_migration_status is complete to prevent the list on the Scheduled Actions page from disappearing.
- Fixed: Tweaks::woocommerce_widget_remove() -> The classic widget is not disabled.
- Fixed: Plugin::get_precache_maxfile() -> Invalid constant, replace maxfile with precache_maxfile.
- Fixed: Filesystem::sanitize_precache_maxfile() -> Set the limit to 100 by default.
- Fixed: Becache::export() -> Invalid expiration time. Already in timestamp format not in seconds.
- Fixed: WP_Object_Cache::dc_save() -> Serialize twice when checking object size.
- Fixed: Configuration -> A notice is not shown when the constant is already defined.
- Added: Configuration -> Storage Options, Check file limits in real-time and Exclude Empty Object Data.
- Added: Configuration -> Runtime Options, Deactivate Concatenate WP-Admin Scripts and Deactivate WP Cron.
- Added: WP-CLI command -> run:optimizedb.
- Added: DOCKET_CACHE_MAXFILE_LIVECHECK constant to enable checking file limits in real-time.
- Added: DOCKET_CACHE_PRECACHE_MAXKEY, DOCKET_CACHE_PRECACHE_MAXGROUP constant to limit cache keys and groups.
- Added: DOCKET_CACHE_STALECACHE_IGNORE constant to enable excluding stale cache from being stored on disk.
- Added: DOCKET_CACHE_EMPTYCACHE constant to enable excluding empty caches from being stored on disk.
- Added: DOCKET_CACHE_AUTOUPDATE_TOGGLE constant, only to sync with WordPress auto_update_plugins option.
- Added: DOCKET_CACHE_GCRON_DISABLED constant to disable garbage collector cron event.
- Added: Filesystem::suspend_cache_write() -> Temporarily suspends new cache from being stored on disk.
- Changed: DOCKET_CACHE_AUTOUPDATE constant can only be defined manually to force an automatic update.
- Improved: Increase timeout limit if lower than 180 seconds.
- Improved: Constans::maybe_define() -> Keep track of constants that have been defined in the $GLOBAL['DOCKET_CACHE_RUNTIME'] list.
- Improved: WP_Object_Cache::maybe_expire() -> Set expiration to 1 day for key/group matches with the stale cache.
- Improved: Event::garbage_collector() -> Improve wc_cache filtering and other possible stale caches.
- Improved: WP_Object_Cache::dc_code() -> Use native var_export for data type objects and arrays if only have stdClass.
- Removed: Event::watchproc() -> No longer needed.
- Updated: DOCKET_CACHE_ADVCPOST_POSTTYPE -> Set the built-in Post Type as the default.
- Updated: Filesystem::get_max_execution_time() -> Accept value to set time limit.

Thanks to Kevin Shenk of Avunu LLC for providing access to the staging server for testing purposes.

= 22.07.02 =
- Fixed: Tweaks::cache_http_response() -> Default TTL.
- Fixed: Tweaks::wpservehappy() -> missing array key.
- Added: wp_cache_supports() function.
- Changed: Tweaks::cache_http_response() -> Use transient instead of wp_cache.
- Changed: Disable Auto update by default.
- Changed: Disable Advanced Post Cache by default.
- Tested up to 6.1.

= 22.07.01 =
- Fixed: Plugin:cleanuppost() -> Invalid index for trashbin.
- Fixed: MoCache() -> Add $entries, $headers properties to avoid warning on get_translations_for_domain.
- Updated: Symfony component -> symfony/var-exporter.



Kindly refer to [changelog.txt](https://raw.githubusercontent.com/nawawi/docket-cache/master/changelog.txt) for previous changes.

Please do manually remove wp-content/object-cache.php and wp-content/cache/docket-cache if an error occurs during updates. Thanks.
