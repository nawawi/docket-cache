/**
 * Docket Cache.
 *
 * @author  Nawawi Jamili
 * @license MIT
 *
 * @see    https://github.com/nawawi/docket-cache
 */
function docket_cache_worker( name, config ) {
    if ( config.debug ) {
        console.log( config.slug + ':worker: ping ' + name );
    }
    jQuery.post(
        config.ajaxurl, {
            "action": "docket_worker",
            "token": config.token,
            "type": name
        },
        function( response ) {
            if ( config.debug ) {
                console.log( response.data + ' -> ' + response.success );
            }

            if ( response.cachestats ) {
                if ( config.debug ) {
                    console.log( response.cachestats );
                }
                setTimeout(
                    function() {
                        var $selector = jQuery( document )
                            .find( 'div#docket-cache' );
                        $selector.find( 'td#objectcache-stats0' )
                            .html( response.cachestats.obc );
                        $selector.find( 'td#opcache-stats0' )
                            .html( response.cachestats.opc );
                        $selector.find( 'td#objectcache-stats' )
                            .html( response.cachestats.obcs );
                        $selector.find( 'td#opcache-stats' )
                            .html( response.cachestats.opcs );
                        $selector.find( 'td#dcopcache-stats' )
                            .html( response.cachestats.opcdc );
                        $selector.find( 'td#wpopcache-stats' )
                            .html( response.cachestats.opcwp );
                    },
                    3000
                );
            }
        }
    );
};