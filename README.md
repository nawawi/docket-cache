![Docket Cache](./.wordpress.org/icon-128x128.png)
# Docket Cache
[![WP compatibility](https://plugintests.com/plugins/wporg/docket-cache/wp-badge.svg)](https://plugintests.com/plugins/wporg/docket-cache/latest) [![PHP compatibility](https://plugintests.com/plugins/wporg/docket-cache/php-badge.svg)](https://plugintests.com/plugins/wporg/docket-cache/latest)

Speed up your WordPress site with a persistent object cache, powered by OPcache. An efficient alternative to Redis and Memcached.

## Description

Docket Cache is a persistent WordPress Object Cache that stores cached data as plain PHP code. It is designed as an alternative for sites that do not have access to Redis or Memcached.

Most file-based caching plugins use [serialize](https://www.php.net/manual/en/function.serialize.php) and [unserialize](https://www.php.net/manual/en/function.unserialize.php) to save PHP objects to flat files. Docket Cache takes a different approach by converting objects into plain PHP code. This makes data retrieval faster and improves overall performance, especially when Zend OPcache is enabled.

For a full description, please visit https://wordpress.org/plugins/docket-cache.

## Documentation

For configuration options, installation guides, and command-line usage, please refer to the documentation at https://docs.docketcache.com.

## Installation

Docket Cache requires PHP 7.2.5 or higher, WordPress 5.4 or higher, and Zend OPcache for best performance.

1. In your WordPress admin, go to **Plugins -> Add New**.
2. Search for "Docket Cache" and click **Install Now**.
3. Click **Activate** or **Network Activate** for Multisite setups.
4. Click **Docket Cache** in the left menu to access the settings page.

Please allow a few seconds for Docket Cache to begin caching objects.

You may also download it directly from the [WordPress Plugin Directory](https://wordpress.org/plugins/docket-cache).

## Sponsor This Project

Support the ongoing development of Docket Cache with a one-off or recurring contribution.

[Become a sponsor](https://docketcache.com/sponsorship/) — all funds go towards the maintenance, development, and promotion of this project.

<br>

**Noteworthy Sponsors**

A heartfelt thanks and appreciation.

<a href="https://jimathosting.com/?utm_source=docketcache&utm_campaign=sponsor-uri&utm_medium=noteworthy"><img src="https://docketcache.com/wp-content/uploads/2021/03/jimathosting.jpg" width="250" height="125" style="margin:10px;"></a>
<a href="https://www.themecloud.io/?utm_source=docketcache&utm_campaign=sponsor-uri&utm_medium=noteworthy"><img src="https://docketcache.com/wp-content/uploads/2021/12/themecloud.jpg" width="250" height="125" style="margin:10px;"></a>
<a href="https://websavers.ca/?utm_source=docketcache&utm_campaign=sponsor-uri&utm_medium=noteworthy"><img src="https://docketcache.com/wp-content/uploads/2022/04/websavers-logo.jpg" width="250" height="125" style="margin:10px;"></a>
<a href="https://avu.nu/?utm_source=docketcache&utm_campaign=sponsor-uri&utm_medium=noteworthy"><img src="https://docketcache.com/wp-content/uploads/2023/01/avunu-logo0.jpg" width="250" height="125" style="margin:10px;"></a>
<a href="https://linqru.jp/?utm_source=docketcache&utm_campaign=sponsor-uri&utm_medium=noteworthy"><img src="https://docketcache.com/wp-content/uploads/2022/04/linqru-logo.jpg" width="250" height="125" style="margin:10px;"></a>
<a href="https://www.gentlemansguru.com/?utm_source=docketcache&utm_campaign=sponsor-uri&utm_medium=noteworthy"><img src="https://docketcache.com/wp-content/uploads/2023/06/gentlemansguru0.jpg" width="250" height="125" style="margin:10px;"></a>
<a href="https://www.securepay.my/?utm_source=docketcache&utm_campaign=sponsor-uri&utm_medium=noteworthy"><img src="https://docketcache.com/wp-content/uploads/2021/03/securepay0.jpg" width="250" height="125" style="margin:10px;"></a>
<a href="https://dnsvault.net/?utm_source=docketcache&utm_campaign=sponsor-uri&utm_medium=noteworthy"><img src="https://docketcache.com/wp-content/uploads/2021/03/dnsvault.jpg" width="250" height="125" style="margin:10px;"></a>
<a href="https://exnano.io/?utm_source=docketcache&utm_campaign=sponsor-uri&utm_medium=noteworthy"><img src="https://docketcache.com/wp-content/uploads/2021/03/exnano2-1.jpg" width="250" height="125" style="margin:10px;"></a>

Other sponsors are mentioned in the [honourable list](https://github.com/nawawi/docket-cache/issues/5).

<br>

## How Versions Work

Versions are as follows: Year.Month.Day

* Year: Two digits representation of a year.
* Month: Two digits representation of a month.
* Day: Two digits representation of a day.


## Developer Tools

[Gapo Tunnel](https://github.com/ghostbirdme/gapo) — Expose local services to the internet through secure tunnels. Share a website, SSH access, or database with a single command — no port forwarding or firewall changes needed. Useful for testing Docket Cache on a local development environment.

## Contributing

Anyone is welcome to contribute to Docket Cache. You can help by reporting issues or submitting pull requests for improvements.

- [Report an issue](https://github.com/nawawi/docket-cache/issues)
- [Submit a pull request](https://github.com/nawawi/docket-cache/pulls)

## Thanks

- [GitHub](https://github.com) for hosting the code and providing excellent infrastructure.
- [Symfony Components](https://github.com/symfony) for developing and maintaining reusable code.
- [WordPress.org](https://wordpress.org) for beautiful designs, powerful features, and the freedom to build anything.
- Everyone who has contributed to this plugin.

## Licence

Docket Cache is open-source software released under the [MIT licence](https://github.com/nawawi/docket-cache/blob/master/LICENSE.txt).
