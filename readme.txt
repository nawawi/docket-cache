=== Docket Cache - Object Cache Accelerator ===
Contributors: nawawijamili
Tags: object cache, OPcache, cache, database, performance
Requires at least: 5.4
Tested up to: 7.0
Requires PHP: 7.2.5
Stable tag: 26.04.10
License: MIT
License URI: https://github.com/nawawi/docket-cache/blob/master/LICENSE.txt
Donate link: https://docketcache.com/#sponsor

Speed up your WordPress site with a persistent object cache, powered by OPcache. An efficient alternative to Redis and Memcached.

== Description ==
Docket Cache is a persistent WordPress Object Cache that stores cached data as plain PHP code. It is designed as an alternative for sites that do not have access to Redis or Memcached.

Most file-based caching plugins use [serializing](https://www.php.net/manual/en/function.serialize.php) and [unserializing](https://www.php.net/manual/en/function.unserialize.php) to save PHP objects to flat files. Docket Cache takes a different approach by converting objects into plain PHP code. This makes data retrieval faster and improves overall performance, especially when Zend OPcache is enabled.

For more information, please refer to the documentation on [Caching In WordPress](https://docketcache.com/docs/resources/caching-in-wordpress/).

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

We also recommend:

- Zend OPcache

Docket Cache saves the cache as PHP files, so it works with or without OPcache. With OPcache, PHP keeps those files ready in memory and does not have to read them from disk on every page load, which makes the cache much faster.

== Documentation ==

For configuration options, installation guides, and command-line usage, please refer to the [Documentation](https://docketcache.com/docs/).

== Development ==
- [GitHub Repository](https://github.com/nawawi/docket-cache/) — Source code and development hub for Docket Cache.
- [Changelog](https://raw.githubusercontent.com/nawawi/docket-cache/master/changelog.txt) — Full history of changes, fixes, and improvements.
- [Gapo Tunnel](https://github.com/ghostbirdme/gapo) — Expose local services to the internet through secure tunnels. Useful for testing Docket Cache on a local development environment.
- [WP Staging Desktop](https://wp-staging.com/desktop/?utm_source=docket-cache&utm_medium=referral&utm_campaign=wp-staging-desktop&utm_content=readme-description) — Turn a live WordPress site into a local development environment in minutes.

== Sponsor This Project ==

Support the ongoing development of Docket Cache with a [one-off or recurring contribution](https://docketcache.com/?utm_source=wp-readme&utm_campaign=sponsor-uri&utm_medium=wporg#sponsor).

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

On Windows, create a RAM disk and set the [DOCKET_CACHE_PATH](https://docketcache.com/docs/constants/#docket_cache_path) to point to the RAM disk drive.

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
= 26.04.10 =
- Fixed: WP_Object_Cache::dc_get()/dc_save() -> Return cached strings exactly as stored to prevent PHP object injection from serialized-looking values.
- Fixed: WP_Object_Cache::dc_get() -> Verify the stored key and group before serving a cached value.
- Fixed: CliOpcache -> Store the WP-CLI OPcache shared secret in a PHP file instead of a web-readable lock file.
- Fixed: Plugin::flush_fcache() -> Validate the cache file index to prevent path traversal.
- Fixed: ReqAction -> Move the Suspend Object Cache state change behind the nonce-verified action.
- Fixed: View::code_focus() -> Build the admin notice via a text node and restrict the option key to prevent DOM-based XSS.
- Fixed: WpConfig::write_runtime() -> Only write expected on/off/integer values into runtime.php.
- Fixed: Crawler -> Only attach cookies to loopback requests, never to the remote Cronbot endpoint.
- Fixed: CronAgent::close_ping() -> Exclude captured cron hook output from the ping response.
- Fixed: EventList -> Escape cron event arguments and callback names in the Cronbot table.
- Fixed: Tweaks -> Cache HTTP responses only for GET requests without Authorization or Cookie, and never cache a response that sets cookies.
- Fixed: MenuCache -> Skip the shared menu cache for logged-in users.
- Improved: WP_Object_Cache -> On-disk cache format version migrates pre-update cache files on read, without a manual flush.

= 26.04.09 =
- Added: Cronbot -> Sponsor notice when the service is connected.
- Fixed: nwdcx_cleanuptransient() -> Use prepared statements when removing transients from the database.
- Fixed: Crawler -> Verify SSL certificate on requests to remote endpoints.
- Fixed: ReqAction -> Escape Cronbot error message in admin notice.
- Removed: Critical Version Checking -> No longer needed.

= 26.04.08 =
- Fixed: WP_Object_Cache::dc_log() -> Redact query string values in the logged request URI.
- Fixed: Constans -> Default Cache Log file name is no longer predictable.
- Fixed: Filesystem::log_remove_legacy() -> Remove Cache Log files left under the old name.
- Fixed: CronAgent::close_ping() -> Fatal error on rejected ping request.
- Fixed: CronAgent::close_ping() -> Exclude Cookie and Authorization headers from stored ping data.

= 26.04.07 =
- Fixed: Polyfill PhpToken stub -> Extend scoped Nawawi\Symfony\Polyfill\Php80\PhpToken.
- Improved: Updated Symfony vendor libraries for PHP 8.4 compatibility.

= 26.04.06 =
- Fixed: admin/log.php -> esc_textarea() on Cache Log output to prevent stored XSS.
- Fixed: Plugin::dc_log() -> Sanitise REQUEST_URI caller before logging.
- Fixed: Plugin -> Capability check on log/debug.log downloads reachable unauthenticated on 'init'.

= 26.04.05 =
- Fixed: Removed double echo in log.php.

= 26.04.04 =
- Added: Configuration UI toggle for WP-CLI OPcache Invalidation (mirrors DOCKET_CACHE_WPCLI_OPCACHE constant).
- Fixed: CliOpcache::notify() -> Buffered per-file notifications at shutdown to prevent HTTP request floods during GC and individual cache unlinks.
- Fixed: CliOpcache -> REST route registered before compat check to eliminate 404 noise when workers bail early under load.
- Fixed: CronAgent::receive_ping() -> Type-safety guard on ping inputs to prevent PHP 8 TypeError from array-form values.
- Fixed: CronAgent::receive_ping() -> Explicit return after close_ping() when CRONBOT is disabled.
- Fixed: CronAgent::receive_ping() -> Multisite loop isolates per-site errors via try/catch; wpcron_error reported so cronbot server detects persistent failures.
- Fixed: CronAgent::run_wpcron() -> Re-entry guard via instance flag and try/finally.
- Fixed: CronAgent::run_wpcron() -> ob_end_clean() on exception path; stop decrementing $run_event below zero.
- Fixed: CronAgent::run_wpcron() -> Pre-init $results and $cron_event to prevent PHP 8 undefined-variable warnings.
- Fixed: CronAgent::run_wpcron() -> REQUEST_URI check uses urldecode() and checks wp_doing_cron() first to avoid URL-encoded bypass.
- Fixed: CronAgent::send_action() -> Cache key includes action to prevent collision between on/off calls from the same IP.
- Improved: CronAgent::send_action() -> $stmp cache-buster refreshes every 5 minutes to avoid CDN staleness in long-running processes.
- Improved: CronAgent::check_connection() -> Skip on AJAX/REST/cron requests to reduce shutdown-hook overhead on high-traffic endpoints.

= 26.04.03 =
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


Kindly refer to [changelog.txt](https://raw.githubusercontent.com/nawawi/docket-cache/master/changelog.txt) for previous changes.

Please do manually remove wp-content/object-cache.php and wp-content/cache/docket-cache if an error occurs during updates. Thanks.
