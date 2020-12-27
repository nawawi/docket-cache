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

                        if ( response.cachestats.obc ) {
                            $selector.find( 'td#objectcache-stats0' )
                                .html( response.cachestats.obc );
                        }

                        if ( response.cachestats.opc ) {
                            $selector.find( 'td#opcache-stats0' )
                                .html( response.cachestats.opc );
                        }

                        if ( response.cachestats.obcs ) {
                            $selector.find( 'td#objectcache-stats' )
                                .html( response.cachestats.obcs );
                        }

                        if ( response.cachestats.opcs ) {
                            $selector.find( 'td#opcache-stats' )
                                .html( response.cachestats.opcs );
                        }

                        if ( response.cachestats.opcdc ) {
                            $selector.find( 'td#dcopcache-stats' )
                                .html( response.cachestats.opcdc );
                        }

                        if ( response.cachestats.opcwp ) {
                            $selector.find( 'td#wpopcache-stats' )
                                .html( response.cachestats.opcwp );
                        }

                        if ( response.cachestats.ofile ) {
                            $selector.find( 'td#file-stats' )
                                .html( response.cachestats.ofile );
                        }

                        if ( response.cachestats.odisk ) {
                            $selector.find( 'td#disk-stats' )
                                .html( response.cachestats.odisk );
                        }
                    },
                    3000
                );
            }
        }
    );
};