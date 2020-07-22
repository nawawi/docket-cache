=== Docket Cache ===
Contributors: nawawijamili,rutflare
Tags: cache, database, opcache, optimizing, object cache, performance, redis, memcached, keydb, speed
Donate link: https://www.paypal.me/ghostbirdme/5usd
Requires at least: 5.4
Tested up to: 5.4.2
Requires PHP: 7.2.5
Stable tag: trunk
License: MIT
License URI: ./license.txt

A file-based persistent WordPress Object Cache stored as a plain PHP code.

== Description ==
The Docket cache is a file-based persistent WordPress Object Cache that is stored as a plain PHP code. Intends to provide an alternative option for those who can't use Redis or Memcached server.

Rather than using [serialize](https://www.php.net/manual/en/function.serialize.php) and [unserialize](https://www.php.net/manual/en/function.unserialize.php) a PHP object to store into flat files, this plugin stores data by converting the object into plain PHP code which results in faster data retrieval and better performance with PHP OPCache enabled.

Kindly refer to the WordPress documentation on [Object Cache](https://make.wordpress.org/hosting/handbook/handbook/performance/#object-cache).

An inside look:

https://youtu.be/385zLPZLLb8

== Why use this plugin? ==
When it comes to reliable persistent Object Cache in WordPress, [Redis](https://redis.io/) or [Memcached](https://memcached.org/) comes on top. However, those solutions are rarely available at low cost or shared hosting servers.

The only solution is to store the object caches into files. With WordPress, exporting the PHP objects are not easy, most plugin that implements file-based solution will serialize and unserialize the object to store and retrieve the data.

The Docket Cache is better because it converts the object cache into plain PHP code. This solution is faster since WordPress can use the cache directly without running other operation.

== Development ==
- [Report issues](https://github.com/nawawi/docket-cache/issues)
- [Send Pull requests](https://github.com/nawawi/docket-cache/pulls)
- [Changelog](https://github.com/nawawi/docket-cache/releases)

== Configuration Options ==

To adjust the configuration, please see the [configuration wiki](https://github.com/nawawi/docket-cache/wiki/Constants) page for details.

== WP-CLI Commands ==

To use the WP-CLI commands, please see the [WP-CLI wiki](https://github.com/nawawi/docket-cache/wiki/WP-CLI) page for available commands.

== Installation ==
To use Docket Cache require minimum PHP 7.2.5, WordPress 5.4 and PHP OPCache for best performance.

1. In your WordPress admin click *Plugins -> Add New*
2. Search plugins "Docket Cache" and click Install Now.
3. Click *Activate* or *Network Activate* in Multisite setups.
4. Enable the object cache under Settings -> Docket Cache, or in Multisite setups under Network Admin -> Settings -> Docket Cache.

== Screenshots ==
1. Plugin Overview.
2. Cache Log.
3. Configuration Options.
4. Cache File.

== Upgrade Notice ==
Kindly do manually remove wp-content/object-cache.php and wp-content/cache/docket-cache if an error occurs during updates. Thanks.

== Changelog ==
= 20.07.20 =

This is an improved version based on previous releases.

- Cache performance, replace file_exists with is_file.

= 20.07.19 =

This is an improved version based on previous releases.

- Automatically enable object cache when plugin activate.
- Delay caching object when installing drop-in file.
- Sorting option by first and last line at cache log page.
- Prevent fatal error at drop-in file.


Kindly refer to [Github Repo](https://github.com/nawawi/docket-cache/releases) for details.

Please do manually remove wp-content/object-cache.php and wp-content/cache/docket-cache if an error occurs during updates. Thanks.


