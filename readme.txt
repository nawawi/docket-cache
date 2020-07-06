=== Docket cache ===
Contributors: nawawijamili
Tags: object cache, cache, performance
Donate link: https://www.paypal.me/ghostbirdme/5usd
Requires at least: 5.4
Tested up to: 5.4.2
Requires PHP: 7.2
Stable tag: trunk
License: MIT
License URI: ./license.txt

A file-based persistent WordPress Object Cache stored as a plain PHP code.

== Description ==
The Docket cache is a file-based persistent WordPress Object Cache that stored as a plain PHP code. Rather than using `serialize` and `unserialize` a PHP object to store into flat files, Docket Cache stores the data by converting the object into plain PHP code, resulting faster data retrieving and better performance if PHP OPcache enabled.

== Configuration Options ==

To adjust the configuration, please see the [configuration options](https://github.com/nawawi/docket-cache#configuration-options) for a full list.

== WP-CLI Commands ==

To use the WP-CLI commands, please see the [WP-CLI commands](https://github.com/nawawi/docket-cache#wp-cli-commands) for a full list.

== Installation ==
To use Docket Cache require minimum PHP 7.2, WordPress 5.4 and PHP OPcache for better performance.

1. Install and activate plugin.
2. Enable the object cache under _Settings -> Docket Cache_, or in Multisite setups under _Network Admin -> Settings -> Docket Cache_.

== Screenshots ==
1. Plugin Overview.
2. Debug Log.
3. Options Info.

== Upgrade Notice ==

== Changelog ==
= 1.0.0 =
- Initial release of the plugin.
