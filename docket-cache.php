<?php
/**
 * Docket Cache.
 *
 * @author  Nawawi Jamili
 * @license MIT
 *
 * @see    https://github.com/nawawi/docket-cache
 */

/**
 * @wordpress-plugin
 * Plugin Name:         Docket Cache
 * Plugin URI:          https://wordpress.org/plugins/docket-cache/
 * Version:             20.10.03
 * VerPrev:             20.10.02
 * Description:         A persistent object cache stored as a plain PHP code, accelerates caching with OPcache backend.
 * GitHub Plugin URI:   https://github.com/nawawi/docket-cache
 * Author:              Nawawi Jamili
 * Author URI:          https://docketcache.com
 * Requires at least:   5.4
 * Requires PHP:        7.2.5
 * Network:             true
 * License:             MIT
 * License URI:         https://github.com/nawawi/docket-cache/blob/master/LICENSE.txt
 * Text Domain:         docket-cache
 * Domain Path:         /languages
 */

namespace Nawawi\DocketCache;

\defined('ABSPATH') && !\defined('DOCKET_CACHE_FILE') || exit;

\define('DOCKET_CACHE_FILE', __FILE__);
require __DIR__.'/includes/load.php';
( new Plugin() )->register();
