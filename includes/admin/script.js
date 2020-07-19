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
    $( document )
        .ready(
            function() {
                $selector = $( document )
                    .find( 'div#docket-cache' );
                $selector.find( 'a#refresh' )
                    .on(
                        'click',
                        function( e ) {
                            e.preventDefault();
                            var $self = $( this );
                            var url = $self.attr( 'href' );
                            var sort = $selector.find( 'select#sort' )
                                .children( 'option:selected' )
                                .val();
                            var order = $selector.find( 'select#order' )
                                .children( 'option:selected' )
                                .val();
                            var line = $selector.find( 'select#line' )
                                .children( 'option:selected' )
                                .val();

                            url = url.replace( /\&order=.*/, '' );
                            url = url + '&order=' + order + '&sort=' + sort + '&line=' + line;
                            window.location.assign( url );
                            return false;
                        }
                    );

                $selector.find( 'select#sort' )
                    .on(
                        'change',
                        function() {
                            $selector.find( 'a#refresh' )
                                .trigger( 'click' );
                        }
                    );

                $selector.find( 'select#order' )
                    .on(
                        'change',
                        function() {
                            $selector.find( 'a#refresh' )
                                .trigger( 'click' );
                        }
                    );

                $selector.find( 'select#line' )
                    .on(
                        'change',
                        function() {
                            $selector.find( 'a#refresh' )
                                .trigger( 'click' );
                        }
                    );

                var spinner = function() {
                    $( window )
                        .on(
                            'beforeunload',
                            function() {
                                $( document )
                                    .find( '#docket-cache-overlay' )
                                    .css( 'display', 'block' );
                                setTimeout(
                                    function() {
                                        $( document )
                                            .find( '#docket-cache-spinner' )
                                            .css( 'display', 'inline-block' );
                                    },
                                    750
                                );
                            }
                        );
                };

                $selector.find( 'p.submit' )
                    .find( 'a.button' )
                    .on(
                        'click',
                        function() {
                            spinner();
                        }
                    );

                $selector.find( 'p.submit' )
                    .find( 'select' )
                    .on(
                        'change',
                        function() {
                            spinner();
                        }
                    );

            }
        );
} )( jQuery );