
# Docket Cache

A persistent WordPress Object Cache stored on local disk.

## Description

The Docket cache is a persistent WordPress Object Cache that stored on local disk. Rather than using `serialize` and `unserialize` a PHP object to store into flat files, Docket Cache stores the data by converting the object into plain PHP code, resulting faster data retrieving and better performance if combined with PHP OPcache.

## Installation

To use Docket Cache require minimum PHP 7.2, WordPress 5.4 and PHP OPcache for better performance.

### Manual
 1. Download the plugin as a [ZIP file](https://github.com/nawawi/docket-cache/archive/master.zip) from GitHub.
 2. In your WordPress admin click *Plugins -> Add New -> Upload Plugin*.
 3. Upload the ZIP file.
 4. Activate the plugin.
 5. Enable the object cache under _Settings -> Docket Cache_, or in Multisite setups under _Network Admin -> Settings -> Docket Cache_.

### Via WP-CLI

[`WP-CLI`](http://wp-cli.org/) is the official command-line interface for WordPress. You can install `docket-cache` using `wp` command like this:

```
wp plugin install --activate https://github.com/nawawi/docket-cache/archive/master.zip
```

### Via Composer
The plugin is available as [Composer package](https://packagist.org/packages/nawawi/docket-cache) and can be installed via Composer from the root of your WordPress installation.
```
composer require nawawi/docket-cache
```

If you have changed the default directory structure or just want the plugin to a specific location, you can create a project from the Composer package.
```
composer create-project nawawi/docket-cache <optional-name>
```

### Via Git
Go to your WordPress plugins folder `cd wp-content/plugins`
```
git clone https://github.com/nawawi/docket-cache
```

### Automatic Udates
The plugin supports the [GitHub Updater plugin](https://github.com/afragen/github-updater) for WordPress. The plugin enable automatic updates from this GitHub Repository. You will find all information about the how and why at the [plugin wiki page](https://github.com/afragen/github-updater/wiki).

## Configuration Options

To adjust the configuration, define any of the following constants in your `wp-config.php` file.

  * `DOCKET_CACHE_MAXTTL` (default: `86400`)

    Set maximum time-to-live (in seconds) for cache keys with an expiration time of `0`.

  * `DOCKET_CACHE_GLOBAL_GROUPS` (default: `['blog-details', 'blog-id-cache', 'blog-lookup', 'global-posts', 'networks', 'rss', 'sites', 'site-details', 'site-lookup', 'site-options', 'site-transient', 'users', 'useremail', 'userlogins', 'usermeta', 'user_meta', 'userslugs']`)

    Set the list of network-wide cache groups that should not be prefixed with the blog-id _(Multisite only)_.

  * `DOCKET_CACHE_IGNORED_GROUPS` (default: `['counts', 'plugins', 'themes', 'comment', 'wc_session_id', 'bp_notifications', 'bp_messages','bp_pages']`)

    Set the cache groups that should not be cached.

  * `DOCKET_CACHE_IGNORED_KEYS` (default: _not set_)

    Set the cache keys that should not be cached.

  * `DOCKET_CACHE_DISABLED` (default: _not set_)

    Set to `true` to disable the object cache at runtime.

  * `DOCKET_CACHE_PATH` (default: `WP_CONTENT_DIR/cache/docket-cache`)

    Set the cache directory.

  * `DOCKET_CACHE_DEBUG` (default: _not set_)

    Set to `true` to enable debug log.

  * `DOCKET_CACHE_DEBUG_FLUSH` (default: `true`)

    Set to `true` to empty the log file when object cache flushed.

  * `DOCKET_CACHE_DEBUG_SIZE` (default: `10000000`)

    Set the maximum size of log file in byte. Default set to 10MB.

  * `DOCKET_CACHE_PRELOAD` (default: `false`)

    Set to `true` to enable cache preloading after cache has been flushed and installing drop-in.

  * `DOCKET_CACHE_PRELOAD_ADMIN` (default: `['options-general.php', 'options-writing.php', 'options-reading.php', 'options-discussion.php', 'options-media.php', 'options-permalink.php', 'edit-comments.php', 'profile.php', 'users.php', 'upload.php', 'plugins.php', 'edit.php', 'themes.php', 'tools.php', 'widgets.php', 'update-core.php']`)

    Set the admin path _(/wp-admin/<path>)_ to preload.

  * `DOCKET_CACHE_PRELOAD_NETWORK` (default: `['update-core.php', 'sites.php', 'users.php', 'themes.php', 'plugins.php', 'settings.php']`)

    Set the network admin path _(/wp-admin/network/<path>)_ to preload _(Multisite only)_.


## WP-CLI Commands

To use the WP-CLI commands, make sure the plugin is activated:

    wp plugin activate docket-cache

The following commands are supported:

  * `wp docket-cache status`

    Show the Docket object cache status.

  * `wp docket-cache enable`

    Enables the Docket object cache. Default behavior is to create the object cache drop-in, unless an unknown object cache drop-in is present.

  * `wp docket-cache disable`

    Disables the Docket object cache. Default behavior is to delete the object cache drop-in, unless an unknown object cache drop-in is present.

  * `wp docket-cache update-dropin`

    Updates the Docket object cache drop-in. Default behavior is to overwrite any existing object cache drop-in.

## How Versions Work

Versions are as follows: Major.Minor.Patch

* Major: Rewrites with completely new code-base.
* Minor: New Features/Changes that breaks compatibility.
* Patch: New Features/Fixes that does not break compatibility.


## Contributions

Anyone can contribute to Docket Cache. Please do so by posting issues when you've found something that is unexpected or sending a pull request for improvements.


## License

Docket cache is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## Changelog

### 1.0.0

  * Initial release
