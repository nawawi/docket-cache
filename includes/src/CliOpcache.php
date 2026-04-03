<?php
/**
 * Docket Cache.
 *
 * @author  Nawawi Jamili
 * @license MIT
 *
 * @see    https://github.com/nawawi/docket-cache
 */

namespace Nawawi\DocketCache;

\defined('ABSPATH') || exit;

/**
 * CliOpcache.
 *
 * Bridges WP-CLI cache operations to the web-server OPcache.
 *
 * Problem: when opcache.validate_timestamps=0 (common on production for
 * performance), truncating or deleting a Docket Cache PHP file from the CLI
 * has no effect on the web server's in-memory OPcache because:
 *   1. opcache.enable_cli=0 by default, so opcache_invalidate() from CLI
 *      operates on a separate (disabled) OPcache segment.
 *   2. With validate_timestamps=0 the web server never re-checks file mtimes.
 *
 * Solution: when running under WP-CLI, after flushing cache files, fire a
 * non-blocking HTTP POST to a REST endpoint registered by this plugin. That
 * request runs inside the web-server process where opcache_invalidate() /
 * opcache_reset() actually work.
 *
 * Security: a shared secret (HMAC-SHA256) is auto-generated on first use and
 * stored in the Docket Cache data directory (web-server-owned). The CLI reads
 * the same file. No user configuration required.
 *
 * Opt-out: define('DOCKET_CACHE_WPCLI_OPCACHE', false) in wp-config.php.
 */
final class CliOpcache
{
    /**
     * REST namespace.
     *
     * @var string
     */
    const REST_NAMESPACE = 'docket-cache/v1';

    /**
     * REST route.
     *
     * @var string
     */
    const REST_ROUTE = '/opcache-invalidate';

    /**
     * Secret lookup key used with Canopt::lookup_set/get.
     *
     * @var string
     */
    const SECRET_KEY = 'cli_opcache_secret';

    /**
     * Whether a bulk (full) flush is in progress.
     * Used to suppress per-file notifications during cachedir_flush().
     *
     * @var bool
     */
    private static $bulk_flush = false;

    /**
     * Plugin instance.
     *
     * @var Plugin
     */
    private $pt;

    /**
     * Constructor.
     */
    public function __construct(Plugin $pt)
    {
        $this->pt = $pt;
    }

    /**
     * Register the REST endpoint.
     *
     * @return void
     */
    public function register_rest_route()
    {
        register_rest_route(
            self::REST_NAMESPACE,
            self::REST_ROUTE,
            [
                'methods'             => 'POST',
                'callback'            => [$this, 'handle_request'],
                'permission_callback' => [$this, 'verify_request'],
            ]
        );
    }

    /**
     * Verify the HMAC signature on incoming requests.
     *
     * @param \WP_REST_Request $request
     * @return bool|\WP_Error
     */
    public function verify_request(\WP_REST_Request $request)
    {
        $signature = $request->get_header('X-Docket-Signature');
        $timestamp  = $request->get_header('X-Docket-Timestamp');

        if (empty($signature) || empty($timestamp)) {
            return new \WP_Error('missing_headers', 'Missing authentication headers.', ['status' => 401]);
        }

        // Reject requests older than 30 seconds to prevent replay attacks.
        if (\abs(time() - (int) $timestamp) > 30) {
            return new \WP_Error('expired_request', 'Request timestamp expired.', ['status' => 401]);
        }

        $secret = $this->get_or_create_secret();
        if (empty($secret)) {
            return new \WP_Error('no_secret', 'Shared secret not available.', ['status' => 500]);
        }

        $body     = $request->get_body();
        $expected = \hash_hmac('sha256', $timestamp.$body, $secret);

        if (!\hash_equals($expected, $signature)) {
            return new \WP_Error('invalid_signature', 'Invalid signature.', ['status' => 403]);
        }

        return true;
    }

    /**
     * Handle the invalidation request.
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function handle_request(\WP_REST_Request $request)
    {
        $params    = $request->get_json_params();
        $flush_all = !empty($params['all']);
        $files     = !empty($params['files']) && \is_array($params['files']) ? $params['files'] : [];

        $invalidated = 0;

        if ($flush_all) {
            if (\function_exists('opcache_reset') && @opcache_reset()) {
                $invalidated = -1; // signals "all"
            }
        } elseif (!empty($files)) {
            foreach ($files as $file) {
                $file = (string) $file;
                // Only invalidate files inside the cache directory.
                if (0 !== \strpos($file, nwdcx_normalizepath(DOCKET_CACHE_PATH))) {
                    continue;
                }
                if (\function_exists('opcache_invalidate') && @opcache_invalidate($file, true)) {
                    ++$invalidated;
                }
            }
        }

        return new \WP_REST_Response(
            [
                'success'     => true,
                'invalidated' => $invalidated,
            ],
            200
        );
    }

    /**
     * Signal that a bulk flush is starting or ending.
     * While true, per-file notifications in unlink() are suppressed.
     *
     * @param bool $state
     * @return void
     */
    public static function set_bulk_flush(bool $state)
    {
        self::$bulk_flush = $state;
    }

    /**
     * Whether a bulk flush is currently in progress.
     *
     * @return bool
     */
    public static function is_bulk_flush(): bool
    {
        return self::$bulk_flush;
    }

    /**
     * Called from WP-CLI after cache files are flushed.
     *
     * Fires a non-blocking HTTP POST to the REST endpoint so that
     * opcache_invalidate() / opcache_reset() runs in the web-server process.
     *
     * @param array $files  Absolute paths of cache files that were flushed.
     *                      Pass an empty array to request a full opcache_reset().
     * @return void
     */
    public static function notify(array $files = [])
    {
        if (!nwdcx_construe('WPCLI_OPCACHE')) {
            return;
        }

        $secret = self::read_secret();
        if (empty($secret)) {
            return;
        }

        $flush_all = empty($files);
        $body      = \wp_json_encode(
            $flush_all
                ? ['all' => true]
                : ['files' => \array_values($files)]
        );

        if (false === $body) {
            return;
        }

        $timestamp = (string) time();
        $signature = \hash_hmac('sha256', $timestamp.$body, $secret);

        $url = \get_rest_url(null, self::REST_NAMESPACE.self::REST_ROUTE);

        \wp_remote_post(
            $url,
            [
                'body'      => $body,
                'headers'   => [
                    'Content-Type'       => 'application/json',
                    'X-Docket-Signature' => $signature,
                    'X-Docket-Timestamp' => $timestamp,
                ],
                'timeout'   => 5,
                'blocking'  => false,
                'sslverify' => false,
            ]
        );
    }

    /**
     * Get the shared secret, creating it if it does not exist.
     *
     * @return string
     */
    public function get_or_create_secret()
    {
        $secret = $this->pt->co()->lookup_get(self::SECRET_KEY);
        if (!empty($secret) && \is_string($secret) && 64 === \strlen($secret)) {
            return $secret;
        }

        // Generate a new 256-bit secret.
        $secret = \bin2hex(\random_bytes(32));
        $this->pt->co()->lookup_set(self::SECRET_KEY, $secret);

        return $secret;
    }

    /**
     * Read the shared secret from disk (CLI-safe, no WP object cache).
     *
     * Canopt stores lookup values as lock files in the data directory.
     * We replicate the path logic here so CLI can read it without
     * going through the object cache (which is the thing we're fixing).
     *
     * @return string
     */
    private static function read_secret()
    {
        // Canopt::lookup_get is available in CLI — use it directly.
        $co = new Canopt();

        return (string) $co->lookup_get(self::SECRET_KEY);
    }
}
