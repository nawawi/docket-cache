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
The Docket cache is a file-based persistent WordPress Object Cache that stored as a plain PHP code. Intends to provide as alternative options for who can't use Redis or Memcache server.

Rather than using serialize and unserialize a PHP object to store into flat files, Docket Cache stores the data by converting the object into plain PHP code, resulting faster data retrieving and better performance with PHP OPCache enabled.

Please refer to the WordPress documentation about [Object Cache](https://make.wordpress.org/hosting/handbook/handbook/performance/#object-cache).

== Support & Contributions ==
- [Report issues](https://github.com/nawawi/docket-cache/issues)
- [Send Pull requests](https://github.com/nawawi/docket-cache/pulls)

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
Please do manually remove wp-content/object-cache.php and wp-content/cache/docket-cache if an error occurs during updates. Thanks.

== Changelog ==
- Please refer to [Github Repo](https://github.com/nawawi/docket-cache/releases) to view changelog.
- Please do manually remove wp-content/object-cache.php and wp-content/cache/docket-cache if an error occurs during updates. Thanks.


