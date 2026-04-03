=== Docket Cache - Object Cache Accelerator ===
Contributors: nawawijamili
Tags: object cache, OPcache, cache, database, performance
Requires at least: 5.4
Tested up to: 6.9
Requires PHP: 7.2.5
Stable tag: 26.04.03
License: MIT
License URI: https://github.com/nawawi/docket-cache/blob/master/LICENSE.txt
Donate link: https://docketcache.com/sponsorship/

Speed up your WordPress site with a persistent object cache, powered by OPcache. An efficient alternative to Redis and Memcached.

== Description ==
Docket Cache is a persistent WordPress Object Cache that stores cached data as plain PHP code. It is designed as an alternative for sites that do not have access to Redis or Memcached.

Most file-based caching plugins use [serializing](https://www.php.net/manual/en/function.serialize.php) and [unserializing](https://www.php.net/manual/en/function.unserialize.php) to save PHP objects to flat files. Docket Cache takes a different approach by converting objects into plain PHP code. This makes data retrieval faster and improves overall performance, especially when Zend OPcache is enabled.

For more information, please refer to the documentation on [Caching In WordPress](https://docs.docketcache.com/resources/caching-in-wordpress).

== Why use this plugin? ==
For reliable persistent Object Cache in WordPress, [Redis](https://redis.io/) or [Memcached](https://memcached.org/) are the top choices. However, they require server knowledge and are rarely available on low-cost or shared hosting plans.

The alternative is to store object caches in files. In WordPress, exporting PHP objects is not straightforward. Most file-based caching plugins rely on serialising and unserialising objects to store and retrieve data.

Docket Cache takes a better approach by turning the object cache into plain PHP code. This is faster because WordPress can use the cache directly without additional processing.

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

For configuration options, installation guides, and command-line usage, please refer to the [Documentation](https://docs.docketcache.com).

== Development ==
- [GitHub Repository](https://github.com/nawawi/docket-cache/) — Source code and development hub for Docket Cache.
- [Changelog](https://raw.githubusercontent.com/nawawi/docket-cache/master/changelog.txt) — Full history of changes, fixes, and improvements.
- [Gapo Tunnel](https://github.com/ghostbirdme/gapo) — Expose local services to the internet through secure tunnels. Useful for testing Docket Cache on a local development environment.

== Sponsor This Project ==

Support the ongoing development of Docket Cache with a [one-off or recurring contribution](https://docketcache.com/sponsorship/?utm_source=wp-readme&utm_campaign=sponsor-uri&utm_medium=wporg).

**Noteworthy Sponsors:**

A heartfelt thanks and appreciation.

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
Docket Cache requires PHP 7.2.5 or higher, WordPress 5.4 or higher, and Zend OPcache for best performance.

1. In your WordPress admin, go to **Plugins -> Add New**.
2. Search for "Docket Cache" and click **Install Now**.
3. Click **Activate** or **Network Activate** for Multisite setups.
4. Click **Docket Cache** in the left menu to access the settings page.

Please allow a few seconds for Docket Cache to begin caching objects.

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
Object caching stores database query results so they can be retrieved quickly the next time they are needed.

Cached data is served directly from the cache rather than making repeated database queries. This reduces the load on your server and makes your site faster.

In simple terms, object caching keeps frequently used data in a location where it can be accessed more quickly.

= What is Docket Cache in Object Caching? =
By default, the WordPress object cache is non-persistent. This means cached data is stored in memory only for the duration of a single page request. It is not kept between page loads. To make it persistent, the object cache must be stored on disk.

Docket Cache does more than just store the object cache — it converts cached data into plain PHP code. This is faster because WordPress can use the cache directly without additional processing.

= What is OPcache in Docket Cache? =
OPcache is a caching engine built into PHP. It improves performance by storing precompiled script bytecode in shared memory, so PHP does not need to load and parse scripts on each request.

Docket Cache converts the object cache into plain PHP code. When reading and writing cache data, it uses OPcache directly, which results in faster data retrieval and better performance.

= What is the Cronbot Service in Docket Cache? =
Cronbot is an external service that pings your website every hour to keep WordPress Cron running actively.

This service is optional and is not required. By default, it is not connected to the [end-point server](https://cronbot.docketcache.com/). You can disable it entirely on the configuration page.

= What is the Garbage Collector in Docket Cache? =
The Garbage Collector is a scheduled event that runs every 5 minutes to monitor cache files, clean up expired entries, and collect statistics.

= What is a RAM disk in Docket Cache? =
A RAM disk uses your server's memory (RAM) to act as a storage drive. It can be a hardware device or a virtual disk.

Reading and writing data from RAM is much faster than from an SSD. Storing Docket Cache files on a RAM disk can greatly improve performance.

Please note that creating a RAM disk requires server administrator access (root), so this option is not available on shared hosting.

Here is an example command to create and use a RAM disk with Docket Cache:

`$ cd wp-content/
$ sudo mount -t tmpfs -o size=500m tmpfs ./cache/docket-cache`

To mount the cache path automatically when the server starts, update your `/etc/fstab` file.

For more information about RAM disks:

1. [How to Easily Create a RAM Disk](https://www.linuxbabe.com/command-line/create-ramdisk-linux)
2. [What Is /dev/shm and Its Practical Usage](https://www.cyberciti.biz/tips/what-is-devshm-and-its-practical-usage.html)
3. [Creating a Filesystem in RAM](https://www.cyberciti.biz/faq/howto-create-linux-ram-disk-filesystem/)

On Windows, create a RAM disk and set the [DOCKET_CACHE_PATH](https://docs.docketcache.com/configuration#docket_cache_path) to point to the RAM disk drive.

= What is the minimum RAM required for shared hosting? =
By default, WordPress sets the memory limit to 256 MB. When combined with MySQL and the web server, you need more than 256 MB. If your hosting plan only provides 256 MB in total, this is not enough, and Docket Cache will not be able to improve your site's performance.

= How is Docket Cache different from other object cache plugins? =
Docket Cache is an Object Cache Accelerator. It optimises caching by handling post queries, comment counting, WordPress translations, and more before storing the object cache.

= Can I use it alongside other cache plugins? =
Yes and no. You can use it together with a page caching plugin, but not with another object cache plugin.

= Can I use it with LiteSpeed Cache? =
Yes. The LiteSpeed Cache plugin includes its own Object Cache feature. It may display a notice asking you to disable Docket Cache. Simply turn off the LiteSpeed Cache Object Cache to use Docket Cache instead.

= Can I use Docket Cache on a busy WooCommerce store? =
It is not recommended for high-traffic WooCommerce stores. Docket Cache is designed as a file-based alternative to in-memory caches like Redis and Memcached, and may not handle the demands of a busy store. For WooCommerce sites with heavy traffic, we recommend using Redis instead.

= I have a VPS. Can I use Docket Cache instead of Redis? =
You can, but if your VPS supports Redis, we recommend using Redis for better performance. Docket Cache is best suited for environments where Redis or Memcached is not available. That said, Docket Cache works well for small to medium sites on a VPS, with no network connection required and no risk of cache-key conflicts.

== Upgrade Notice ==
Please do manually remove wp-content/object-cache.php and wp-content/cache/docket-cache if an error occurs during updates. Thanks.

== Changelog ==
= v26.04.03 =
- Added: CliOpcache — Invalidate web-server OPcache from WP-CLI via REST endpoint.
- Added: DOCKET_CACHE_WPCLI_OPCACHE constant to enable/disable CLI OPcache invalidation.
- Added: DOCKET_CACHE_CONFIGACTION constant to enable/disable Export/Import settings feature.
- Added: Export/Import settings feature for configuration backup and restore.
- Fixed: CliOpcache -> Path traversal protection using realpath() validation.
- Fixed: CliOpcache -> Bulk flush suppression to avoid excessive HTTP requests during full cache flush.
- Fixed: CliOpcache -> Shared secret initialised during REST route registration.
- Fixed: ReqAction -> Flush Object Cache causing 502 nginx error by deferring flush after response.
- Fixed: Plugin::get_opcache_status() -> Filter scripts by ABSPATH to prevent counting other sites on shared hosting.
- Fixed: Plugin::get_opcache_status() -> Removed unnecessary is_file() stale check on every cached script.
- Fixed: OPcache Viewer -> Escaped output of configuration values to prevent XSS.
- Improved: Filesystem::unlink() -> Notify web-server OPcache on individual cache file deletion.
- Improved: Filesystem::cachedir_flush() -> Utilise Crawler class for internal HTTP requests.
- Improved: Plugin::get_opcache_status() -> Skip building scripts array for overview page, cap at 50000 for OPcacheView.
- Improved: Plugin::get_opcache_status() -> Timeout guard for file cache scan on shared hosting.
- Improved: OPcacheView -> Removed "All Items" option, replaced with 50000 items cap.
- Improved: OPcacheView::get_status() -> Cache result to prevent double scan per page load.

= v24.07.07 =
- Fixed: _load_textdomain_just_in_time was called incorrectly on WordPress 6.7+.

= v24.07.06 =
- Fixed: MoCache double-applying load_textdomain_mofile filter.

= 24.07.05 =
- Fixed: Authorization and nonce verification in AJAX worker handler.

= 24.07.04 =
- Fixed: View::parse_log_query() -> proper handling file path traversal.

= 24.07.03 =
- Fixed: View::render() -> proper handling subpage name.
- Fixed: Event::garbage_collector() -> missing is_wp_cache_group_queries() method.

= 24.07.02 =
- Fixed: _load_textdomain_just_in_time was called incorrectly.
- Fixed: blueprint.json -> deprecated pluginZipFile property.
- Updated: Tested up to 6.7.

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
