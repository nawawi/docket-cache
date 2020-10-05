/**
 * Docket Cache.
 *
 * @author  Nawawi Jamili
 * @license MIT
 *
 * @see    https://github.com/nawawi/docket-cache
 */
( function( $ ) {
    $( window )
        .on(
            'beforeunload',
            function() {
                window.setTimeout(
                    function() {
                        var uh = window.location.href;
                        if ( uh.match( /\/update.*\.php/ ) === null && uh.match( /\/wp-admin\/post\.php\?post=\d+\&action=edit/ ) === null ) {
                            $( 'body' )
                                .removeClass( 'docket-cache-page-loading' )
                                .addClass( 'docket-cache-page-loading' );
                        }
                    },
                    750
                );
            }
        );
} )( jQuery );