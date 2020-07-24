/**
 * Docket Cache.
 *
 * @author  Nawawi Jamili
 * @license MIT
 *
 * @see    https://github.com/nawawi/docket-cache
 */
function docket_cache_preload( config ) {
    if ( config.log ) {
        console.log( config.slug + ': ping preload' );
    }
    jQuery.post(
        config.ajaxurl, {
            "action": "docket_preload",
            "token": config.token,
        },
        function( response ) {
            if ( config.log ) {
                console.log( response.data + ' -> ' + response.success );
            }
        }
    );
};