
# ![Docket Cache](https://static.monodb.io/logo-150x150.svg) Docket Cache

A file-based persistent WordPress Object Cache stored as a plain PHP code.
  
## Description

The Docket cache is a file-based persistent WordPress Object Cache that stored as a plain PHP code. Rather than using `serialize` and `unserialize` a PHP object to store into flat files, Docket Cache stores the data by converting the object into plain PHP code, resulting faster data retrieving and better performance if PHP OPcache enabled.

## Installation

To use Docket Cache require minimum PHP 7.2, WordPress 5.4 and PHP OPcache for better performance.

### Manual
 1. Download the plugin as a [ZIP file](https://github.com/nawawi/docket-cache/archive/master.zip) from GitHub.
 2. In your WordPress admin click *Plugins -> Add New -> Upload Plugin*.
 3. Upload the ZIP file.
 4. Activate the plugin.
 5. Enable the object cache under _Settings -> Docket Cache_, or in Multisite setups under _Network Admin -> Settings -> Docket Cache_.

### Via WP-CLI

[`WP-CLI`](http://wp-cli.org/) is the official command-line interface for WordPress. You can install `docket-cache` using the `wp` command like this:

```
wp plugin install --activate https://github.com/nawawi/docket-cache/archive/master.zip
```

### Via Composer
The plugin is available as [Composer package](https://packagist.org/packages/nawawi/docket-cache) and can be installed via Composer from the root of your WordPress installation.
```
composer create-project -s dev --prefer-dist nawawi/docket-cache wp-content/plugins/docket-cache
```

### Via Git
Go to your WordPress plugins folder `cd wp-content/plugins`
```
git clone https://github.com/nawawi/docket-cache
```

### Automatic Udates
The plugin supports the [GitHub Updater plugin](https://github.com/afragen/github-updater) for WordPress. The plugin enables automatic updates from this GitHub Repository. You will find all information about the how and why at the [plugin wiki page](https://github.com/afragen/github-updater/wiki).


## Configuration Options

To adjust the configuration, define any of the following constants in your `wp-config.php` file.

**`DOCKET_CACHE_MAXTTL`**

Maximum cache time-to-live in seconds, if expiry key `0`.   
Default:
```php
define('DOCKET_CACHE_MAXTTL', 8600); 
```

**`DOCKET_CACHE_IGNORED_GROUPS`**

List of cache groups that should not be cached.  
Default:
```php
define('DOCKET_CACHE_IGNORED_GROUPS',
  [
    'counts',
    'plugins',
    'themes',
    'comment',
    'wc_session_id',
    'bp_notifications',
    'bp_messages',
    'bp_pages'
  ]
);
```

**`DOCKET_CACHE_IGNORED_KEYS`**

List of cache keys that should not be cached.  
Default:
```php
define('DOCKET_CACHE_IGNORED_KEYS', []);
```

**`DOCKET_CACHE_DISABLED`**

Set to `true` to disable the object cache at runtime.  
Default:
```php
define('DOCKET_CACHE_DISABLED', false);
```

**`DOCKET_CACHE_PATH`** 

Set the cache directory.
Default:
```php
define('DOCKET_CACHE_PATH`', WP_CONTENT_DIR.'/cache/docket-cache');
```

**Debug Options**

**`DOCKET_CACHE_DEBUG`**

Set to `true` to enable debug log.  
Default:
```php
define('DOCKET_CACHE_DEBUG', false);
```

**`DOCKET_CACHE_DEBUG_FLUSH`**

Set to `true` to empty the log file when object cache flushed.  
Default:
```php
define('DOCKET_CACHE_DEBUG_FLUSH', true);
```

**`DOCKET_CACHE_DEBUG_SIZE`**

Set the maximum size of a log file in byte. Default set to 10MB.  
Default:
```php
define('DOCKET_CACHE_DEBUG_SIZE', 10000000);
```

**`DOCKET_CACHE_PRELOAD`**

Set to `true` to enable cache preloading, triggered after the cache has been flushed and when installation of drop-in file.  
Default:
```php
define('DOCKET_CACHE_PRELOAD', false);
```

**`DOCKET_CACHE_PRELOAD_ADMIN`**

Set the list of admin path _(/wp-admin/<path>)_ to preload.  
Default:
```php
define('DOCKET_CACHE_PRELOAD_ADMIN',
  [
    'options-general.php',
    'options-writing.php',
    'options-reading.php',
    'options-discussion.php',
    'options-media.php',
    'options-permalink.php',
    'edit-comments.php',
    'profile.php',
    'users.php',
    'upload.php',
    'plugins.php',
    'edit.php',
    'themes.php',
    'tools.php',
    'widgets.php',
    'update-core.php'
  ]
);
```

**Multisite Options**

**`DOCKET_CACHE_GLOBAL_GROUPS`**

Set the list of network-wide cache groups that should not be prefixed with the blog-id.
Default:
```php
define('DOCKET_CACHE_GLOBAL_GROUPS',
  [
    'blog-details',
    'blog-id-cache',
    'blog-lookup',
    'global-posts',
    'networks',
    'rss',
    'sites',
    'site-details',
    'site-lookup',
    'site-options',
    'site-transient',
    'users',
    'useremail',
    'userlogins',
    'usermeta',
    'user_meta',
    'userslugs'
  ]
);
```

**`DOCKET_CACHE_PRELOAD_NETWORK`**

Set the list of network admin path _(/wp-admin/network/<path>)_ to preload.
Default:
```php
define('DOCKET_CACHE_PRELOAD_ADMIN',
  [
    'update-core.php',
    'sites.php',
    'users.php',
    'themes.php',
    'plugins.php',
    'settings.php'
  ]
);
```

## WP-CLI Commands

To use the WP-CLI commands, make sure the plugin is activated:
```
wp plugin activate docket-cache
```

The following commands are supported:

  * `wp cache status`

    Show the Docket object cache status.

  * `wp cache enable`

    Enables the Docket object cache. Default behavior is to create the object cache drop-in, unless an unknown object cache drop-in is present.

  * `wp cache disable`

    Disables the Docket object cache. Default behavior is to delete the object cache drop-in, unless an unknown object cache drop-in is present.

  * `wp cache update-dropin`

    Updates the Docket object cache drop-in. Default behavior is to overwrite any existing object cache drop-in.

## Screenshots
![Overview](./.wordpress.org/screenshot-1.png)

![Debug Log](./.wordpress.org/screenshot-2.png)

## How Versions Work

Versions are as follows: Major.Minor.Patch

* Major: Rewrites with completely new code-base.
* Minor: New Features/Changes that breaks compatibility.
* Patch: New Features/Fixes that does not break compatibility.


## Contributions

Anyone can contribute to Docket Cache. Please do so by posting issues when you've found something that is unexpected or sending a pull request for improvements.


## License

Docket cache is open-sourced software licensed under the [MIT license](https://github.com/nawawi/docket-cache/blob/master/LICENSE.txt).
