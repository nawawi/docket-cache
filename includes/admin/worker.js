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
        }
    );
};