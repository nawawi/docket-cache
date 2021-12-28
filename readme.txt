=== Docket Cache - Object Cache Accelerator ===
Contributors: nawawijamili
Tags: object cache, OPcache, fastcgi, cache, database, Optimisation, performance, redis, memcached, speed, multisite, server load, docket
Requires at least: 5.4
Tested up to: 5.8
Requires PHP: 7.2.5
Stable tag: 21.08.03
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

All funds will be dedicated to the maintenance, development, and marketing of this project.

**Noteworthy Sponsors:**

A heartful thanks and appreciation.

- [Exnano Creative](https://exnano.io/?utm_source=docketcache&utm_campaign=plugin-uri&utm_medium=wporg)
- [Jimat Hosting](https://jimathosting.com/?utm_source=docketcache&utm_campaign=plugin-uri&utm_medium=wporg)
- [Themecloud](https://www.themecloud.io/?utm_source=docketcache&utm_campaign=plugin-uri&utm_medium=wporg)
- [DNSVault](https://dnsvault.net/?utm_source=docketcache&utm_campaign=plugin-uri&utm_medium=wporg)
- [SecurePay](https://www.securepay.my/?utm_source=docketcache&utm_campaign=plugin-uri&utm_medium=wporg)
- [Cun Host](https://cunhost.com/?utm_source=docketcache&utm_campaign=plugin-uri&utm_medium=wporg)


Other sponsors are mentioned in the [honourable list](https://github.com/nawawi/docket-cache/issues/5)

== Additional Tool ==

[Docket CronWP](https://github.com/nawawi/docket-cronwp) - A command-line tool for executing WordPress cron events in parallel.

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

= What is the Cronbot Service in Docket Cache? =
The Cronbot is an external service that pings your website every hour to keep WordPress Cron running actively. 

This service offered as an alternative option and is not compulsory to use. By default, this service not connected to the [end-point server](https://cronbot.docketcache.com/). You can completely disable it at the configuration page.

= What is Garbage Collector in Docket Cache? =
Garbage Collector is a Cron Events than run every 5 minutes to monitoring cache file purposely for cleanup and collecting stats.

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

= What’s the difference with the other object cache plugins? =
Docket Cache is an Object Cache Accelerator. It does some optimization of caching like cache post queries, comments counting, WordPress translation and more before storing the object caches.

= Can I pair using it with other cache plugin? =
Yes and No. You can pair using it with page caching plugin, but not with the object cache plugin.

= I'm using a VPS server. Can I use Docket Cache to replace Redis? =
Yes, you can. It can boost more your WordPress performance since there is no network connection need to makes and no worry about memory burst, cache-key conflict and error-prone caused by the improper settings.

== Upgrade Notice ==
Please do manually remove wp-content/object-cache.php and wp-content/cache/docket-cache if an error occurs during updates. Thanks.

== Changelog ==
= 21.08.03 =
- Fixed: Filesystem::is_opcache_enable() -> do checking PHP INI disable_functions directive.
- Fixed: Overview -> if OPcache enable and opcache_get_status function disabled, only show "Enabled".
- Fixed: Flush OPcache -> show notice if opcache_reset function disabled.
- Fixed: OPcache Viewer -> show notice if opcache_get_status, opcache_get_configuration function disabled.
- Fixed: docketcache_runtime() -> only valid for PHP >= 7.2.5.

Thanks to @robderijk.

= 21.08.02 =
- Fixed: opcacheviewer -> some filter parameters do not escape.

Thanks to Erwan from WPScan.

= 21.08.01 =
- Changed: plugin options -> check critical version, disabled by default.
- Changed: Mark 21.08.1 as a stable release.

= 21.02.08 =

- Fixed: Tweaks::http_headers_expect() -> only visible to wp < 5.8 since already included in core.
- Fixed: Filesystem::is_request_from_theme_editor() -> checking if from plugin-editor.
- Fixed: nwdcx_unserialize() -> checking if ABSPATH and WPINC defined.
- Tested up to 5.8.

= 21.02.07 =

- Fixed: missing Becache.php in wp repo.

= 21.02.06 =

- Fixed: Plugin::site_url_scheme() -> strip whitespace.
- Fixed: Tweaks::post_missed_schedule() -> remove sort by date.
- Fixed: Tweaks::register_tweaks() -> run register_tweaks at shutdown, lock for 3 minutes.
- Fixed: ReqAction::exit_failed() -> missing args.
- Fixed: ReqAction::parse_action() -> replace $_GET, $_POST conditional with $_REQUEST.
- Fixed: Canopt::put_config() -> check file exists before unlink.
- Fixed: WP_Object_Cache::maybe_expire() -> exclude transient key health-check-site-status-result.
- Fixed: CronAgent::run_wpcron() -> capture hook output if any.
- Removed: Plugin::suspend_wp_options_autoload() -> already replace with Filesystem::optimize_alloptions().
- Added: Filesystem::keys_alloptions() -> list of core alloptions key.
- Added: Action Hook -> 'docketcache/action/flushcache/object' to flush cache files.
- Added: Becache::export() -> early cache for transient and alloptions.
- Improved: Configuration -> change wording at Option label.
- Improved: CronAgent::send_action() -> disconnect if object cache disabled.

= 21.02.05 =

- Fixed: Normalize a filesystem path on Windows.
- Fixed: Plugin::cleanuppost() -> Invalid counting for trash.
- Fixed: Tweaks::woocommerce_crawling_addtochart_links() -> Checking user-agent to avoid redundancy in robots.txt.
- Fixed: OPcache -> OPcache Config. Proper link directives name to php documentation.
- Added: Configuration -> Actions -> Runtime code. install/uninstall runtime code.
- Added: Configuration -> Runtime Options. Possible to handles wp debug and auto update core.
- Added: OPcache -> OPcache Files -> Items limit selection. Limit items to display.
- Improved: runtime code and how to handle wp constants.
- Removed: Our sequence order to the first index in the plugin list.

Thanks to @kotyarashop for reporting an issue with robots.txt.

= 21.02.04 =

- Fixed: View::code_focus() -> remove {behavior: "smooth"} to correct scroll position in firefox.
- Fixed: OPcacheView::get_files() -> normalize files path.
- Fixed: Filesystem::opcache_reset() -> remove additional invalidate files, issue with memory burst when run admin preloading.
- Fixed: ReqAction::run_action() -> prevent run opcache_reset after flush object cache.
- Fixed: Tweaks::limit_http_request() -> allows admin-ajax.php and .local hostname.
- Added: Tweaks::woocommerce_crawling_addtochart_links() -> simple tweaks to prevent robots from crawling add-to-cart links.
- Added: LIMITHTTPREQUEST_WHITELIST constant -> list of hostname to exclude from checking.
- Added: Tweaks::wpdashboardnews() -> remove Events & News Feed in WP dashboard.
- Added: Cronbot -> Run Now for single event.

= 21.02.03 =

- Fixed: WpConfig::has() -> missing argument for nwdcx_throwable.

Thanks to Stanislav Khromov for testing with php 8. https://github.com/nawawi/docket-cache/issues/10

= 21.02.02 =

- Fixed: Plugin::is_subpage() -> opcach viewer left menu link.
- Fixed: Filesystem::fastcgi_close() -> Theme editor failed to verify updated file.
- Added: Tweaks::http_headers_expect() -> HTTP Request Expect header tweaks.

Thanks to Oleg for reporting an issue with Theme Editor https://docketcache.com/feedback/#comment-2

= 21.02.01 =

- Fixed: Filesystem::chmod() -> invalid mode for file.
- Fixed: Filesystem::define_cache_path() -> avoid checking if the cache path exists and create the content path if define.
- Fixed: Overview -> Cache Path not same with DOCKET_CACHE_PATH, due to error at define_cache_path().
- Added: Filesystem::mkdir_p() -> fix directory permissions issues, when web server and php has different user/group.
- Added: Filesystem::touch() -> fix notice "Utime failed: Operation not permitted" when web server and php has different user/group.
- Added: Filesysten::getchmod() -> gets file/dir permissions in octal format.
- Added: sites selection for cleanup post on multisite.
- Added: OPcache viewer.
- Updated tested up to 5.7
- Improved action notice at the configuration page.

Thanks to @patrickwgs for reporting an issue on bedrock installation.

= 21.01.01 =

- Fixed: Tweaks::woocommerce_cart_fragments_remove() -> check if "wc-cart-fragments" script exists.
- Fixed: WP_Object_Cache::dc_precache_set -> ignore transient, site-transient.
- Added: Configuration Options -> Runtime Options.
- Added: Configuration Actions -> Config reset.
- Added: Configuration Actions -> Cleanup Post revisions, auto drafts, trash bin.
- Added: WP-CLI command -> runtime:install, runtime:reset.

= 20.12.04 =

- Fixed: Limit WP-Admin HTTP Requests -> invalid variable pagenow.
- Fixed: Cache Log -> use get_utc_offset() instead of date('T').
- Fixed: Overview Actions -> rename admin/actcmd.php to admin/actions.php.
- Fixed: Cronbot Events -> display notice if failed to load EventList() .
- Fixed: nwdcx_throwable() -> only save data if WP_DEBUG defined .
- Fixed: Tweak::compat_notice() -> move to Plugin::compat_notice(), do check at CLI.
- Fixed: Plugin::get_subpage() -> add checking for adx variable.
- Fixed: OPcache flush -> lock for 20 seconds before accept new request.
- Added: Filesystem() methods -> sanitize_precache_maxfile, sanitize_maxsize, sanitize_maxsizedisk.


Kindly refer to [changelog.txt](https://raw.githubusercontent.com/nawawi/docket-cache/master/changelog.txt) for previous changes.

Please do manually remove wp-content/object-cache.php and wp-content/cache/docket-cache if an error occurs during updates. Thanks.
