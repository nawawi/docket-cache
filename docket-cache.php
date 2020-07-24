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
 * Version:             20.07.24
 * Description:         A file-based persistent WordPress Object Cache stored as a plain PHP code.
 * GitHub Plugin URI:   https://github.com/nawawi/docket-cache
 * Author:              Nawawi Jamili
 * Author URI:          https://rutweb.com
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
( new Plugin(__FILE__) )->attach();
