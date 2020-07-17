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
The Docket cache is a file-based persistent WordPress Object Cache that stored as a plain PHP code. Intends to provide as alternative options for who can't use Redis or Memcached server.

Rather than using [serialize](https://www.php.net/manual/en/function.serialize.php) and [unserialize](https://www.php.net/manual/en/function.unserialize.php) a PHP object to store into flat files. This plugin stores the data by converting the object into plain PHP code, resulting faster data retrieving and better performance with PHP OPCache enabled.

Kindly, please refer to the WordPress documentation about [Object Cache](https://make.wordpress.org/hosting/handbook/handbook/performance/#object-cache).

== Why this plugin? ==
When come to persistent Object Cache in WordPress, the most reliable solution is used with [Redis](https://redis.io/) or [Memcached](https://memcached.org/) server. However, that solution are not available to low cost or shared hosting server.

The only solutions are to store the object cache into a file-based. With WordPress, exporting the PHP object are not easy, most plugin that implements file-based solution will serialize and unserialize the object to store and retrieve the data.

The Docket Cache makes it better by converting the object cache into plain PHP code. This solution more faster, since WodPress only retrieve the stored cache same as it load it's own library.

== Development ==
- [Report issues](https://github.com/nawawi/docket-cache/issues)
- [Send Pull requests](https://github.com/nawawi/docket-cache/pulls)
- [Changelog](https://github.com/nawawi/docket-cache/releases)

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
3. Setting Instruction.

== Upgrade Notice ==
Kindly, please do manually remove wp-content/object-cache.php and wp-content/cache/docket-cache if an error occurs during updates. Thanks.

== Changelog ==
- Please refer to [Github Repo](https://github.com/nawawi/docket-cache/releases) to view changelog.
- Kindly, please do manually remove wp-content/object-cache.php and wp-content/cache/docket-cache if an error occurs during updates. Thanks.


