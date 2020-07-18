/**
 * Docket Cache.
 *
 * @author  Nawawi Jamili
 * @license MIT
 *
 * @see    https://github.com/nawawi/docket-cache
 */
(function($) {
    $( window ).on(
        'beforeunload',
        function() {
            $( 'body' ).addClass( 'docket-cache-page-loading' );
		}
    );
})( jQuery );
