/**
 * Docket Cache.
 *
 * @author  Nawawi Jamili
 * @license MIT
 *
 * @see    https://github.com/nawawi/docket-cache
 */
function docket_cache_preload( config ) {
    console.log( config.slug + ': ping preload' );
    jQuery.post(
        config.ajaxurl, {
            "action": "docket_preload",
            "token": config.token,
        },
        function( response ) {
            console.log( response.data + ' -> ' + response.success );
        }
    );
};

( function( $ ) {
    $( document ).ready(
        function() {
            $selector = $( document );
            $selector.find( 'a#refresh' ).on(
                'click',
                function( e ) {
                    e.preventDefault();
                    var $self = $( this );
                    var url = $self.attr( 'href' );
                    var order = $selector.find( 'select#order' ).children( 'option:selected' ).val();
                    var line = $selector.find( 'select#line' ).children( 'option:selected' ).val();
                    url = url + '&order=' + order + '&line=' + line;
                    window.location.assign( url );
                    return false;
                }
            );

            $selector.find( 'select#order' ).on(
                'change',
                function() {
                    $selector.find( 'a#refresh' ).trigger( 'click' );
                }
            );

            $selector.find( 'select#line' ).on(
                'change',
                function() {
                    $selector.find( 'a#refresh' ).trigger( 'click' );
                }
            );
        }
    );
} )( jQuery );

/* doesn't work with jquery */
( function() {
    window.addEventListener(
        'beforeunload',
        function( e ) {
            document.getElementById( 'docket-cache-overlay' ).style.display = 'block';
            setTimeout(
                function() {
                    document.getElementById( 'docket-cache-spinner' ).style.display = 'inline-block';
                },
                750
            );
            return '';
        }
    );
} )();