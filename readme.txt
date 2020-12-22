=== Docket Cache - Object Cache Accelerator ===
Contributors: nawawijamili
Tags: object cache, OPcache, fastcgi, cache, database, Optimisation, performance, redis, memcached, speed, multisite, server load, docket
Donate link: https://www.patreon.com/bePatron?u=41796862
Requires at least: 5.4
Tested up to: 5.6
Requires PHP: 7.2.5
Stable tag: 20.11.05
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
- WP-CLI support
- Multisite support
- Multi-Network support

== Requirements ==

To use Docket Cache requires minimum:

- PHP 7.2.5
- WordPress 5.4
- Zend OPcache

== Documentation ==

To adjust the plugin behaviour, installation or manage through a command line, please refer to the [Documentation](https://docs.docketcache.com) for details.

== Development ==
- [Report issues](https://github.com/nawawi/docket-cache/issues)
- [Changelog](https://raw.githubusercontent.com/nawawi/docket-cache/master/changelog.txt)

== Sponsor this project ==

There is a lot of room for improvement and features to add, require plenty of person-hours dedicated to testing and development.

[Become our sponsor](https://www.patreon.com/bePatron?u=41796862). All funds will be dedicated to maintenance, development, and marketing of this project.

Thank you for sponsoring Docket Cache.

Sponsored by:

- [DNSVault](https://dnsvault.net/?utm_source=docketcachewporg)
- [Cun Host](https://cunhost.com/?utm_source=docketcachewporg)
- [Exnano Creative](https://exnano.io/?utm_source=docketcachewporg)
- [Jimat Hosting](https://jimathosting.com/?utm_source=docketcachewporg)

Affiliates with:

- [Dreamhost](https://mbsy.co/3cGLwM)
- [LiteSpeed](https://store.litespeedtech.com/store/aff.php?aff=1260&promo=wpaccel)
- [Bluehost](https://www.bluehost.com/track/docketcache/)
- [Onlinenic](https://onlinenic.com/en/Home/cloudReferral.html?usercode=87783819348ea6021e9df91d9bfd4981)
- [Digitalocean](https://m.do.co/c/6c93db5b1ef6)
- [KiahStore](https://docketcache.com/wp-content/spx/kiahstore/?utm_source=docketcachewporg)
- [Pikoseeds](https://docketcache.com/wp-content/spx/pikoseed/?utm_source=docketcachewporg)

The Docket Cache has been reported seemly works with these hosting provider:

- [GB Network](https://www.gbnetwork.my/?utm_source=docketcachewporg)
- [Zenpipe](https://www.zenpipe.com/?utm_source=docketcachewporg)
- [KelateBiz](https://kelate.biz/?utm_source=docketcachewporg)
- [ServerFreak](https://secure.web-hosting.net.my/clients/aff.php?aff=4725)
- [Exabytes](https://billing.exabytes.my/mypanel/aff.php?aff=8102792)

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
The Cronbot is an external service that pings your website every hour to keep WordPress Cron running actively. This service offered as an alternative option and is not compulsory to use. By default, this service not connected to the [end-point server](https://cronbot.docketcache.com/). You can completely disable it at the configuration page.

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
Docket Cache is an Object Cache Accelerator. It does some Optimisation of caching like cache post queries, comments counting, WordPress translation and more before storing the object caches.

= Can I pair using it with other cache plugin? =
Yes and No. You can pair using it with page caching plugin, but not with the object cache plugin.

= I'm using a VPS server. Can I use Docket Cache to replace Redis? =
Yes, you can. It can boost more your WordPress performance since there is no network connection need to makes and no worry about memory burst, cache-key conflict and error-prone caused by the improper settings.

== Upgrade Notice ==
Please do manually remove wp-content/object-cache.php and wp-content/cache/docket-cache if an error occurs during updates. Thanks.

== Changelog ==
= 20.11.05 =

- Fixed CronAgent::check_connection() -> close_ping() -> Invalid selfcheck delay. Set to 90 minutes instead of now.
- Fixed Auto-updates -> wp >= 5.5 can't enable/disable auto-updates for docket cache at plugins page. Now, it works vise-versa.
- Fixed ReqAction -> Error notice undefined variable nv.

= 20.11.04 =

- Fixed Admin Interface -> filter others admin notice using hook.
- Fixed Event -> rare condition checkversion cronagent process lock timestamp no effect.
- Fixed Event -> invalid remove event.
- Fixed Actions -> when disable object cache, it will stay disabled until enable it back.
- Fixed Notice -> only show compability notice at plugins, updates page and our overview page.
- Added Constans() -> option to reload config at dc* methods.
- Added Configuration -> deactivate WooCommerce Cart Fragments.
- Added nwdcx_cleanuptransient -> makes it reuseable for Event::delete_expired_transients_db().

= 20.11.03 =

- Fixed Admin Interface -> action notice can't dismiss.
- Fixed Admin Notice -> exclude Dismissible Notices Handler dnh_dismissed_notices option from cache.
- Added Configuration -> remove the WordPress Application Passwords feature.

= 20.11.02 =

- Fixed PostCache::setup_hooks() -> removed deprecated jetpack hook instagram_cache_oembed_api_response_body.

= 20.11.01 =

- Added WP-CLI command -> run:gc, run:stats, run:cron, reset:lock, reset:cron, dropin:enable, dropin:disable, dropin:update, flush:precache
- Fixed Admin Interface -> only show our own notice.


Kindly refer to [changelog.txt](https://raw.githubusercontent.com/nawawi/docket-cache/master/changelog.txt) for previous changes.

Please do manually remove wp-content/object-cache.php and wp-content/cache/docket-cache if an error occurs during updates. Thanks.
