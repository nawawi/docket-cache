/**
 * Docket Cache.
 *
 * @author  Nawawi Jamili
 * @license MIT
 *
 * @see    https://github.com/nawawi/docket-cache
 */
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

                            var dt = Math.floor( Date.now() / 1000 );
                            url = url.replace( /\&sorting=.*/, '' );
                            if ( order ) {
                                url = url + '&srt=' + order + '-' + sort + '-' + line;
                            }
                            url = url + '&t=' + dt;
                            window.location.replace( url );
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

                $selector.find( 'a.btx-spinner' )
                    .on(
                        'click',
                        function() {
                            spinner();
                        }
                    );

                var $psubmit = $selector.find( 'p.submit' );
                $psubmit.find( 'a.button' )
                    .on(
                        'click',
                        function() {
                            spinner();
                        }
                    );

                $psubmit.find( 'select' )
                    .on(
                        'change',
                        function() {
                            spinner();
                        }
                    );

                $selector.find( '.config' )
                    .find( 'select.config-select' )
                    .on(
                        'change',
                        function() {
                            spinner();
                            var $self = $( this );
                            var link = $self.children( 'option:selected' )
                                .attr( 'data-action-link' );
                            window.location.replace( link );
                            return false;
                        }
                    );

                $selector.find( '.nav-tab-wrapper' )
                    .find( 'select.nav-select' )
                    .on(
                        'change',
                        function() {
                            var $self = $( this );
                            var link = $self.children( 'option:selected' )
                                .attr( 'data-action-link' );
                            window.location.replace( link );
                            return false;
                        }
                    );

                var highlight_row = function( selector, lineNum ) {
                    var val = selector.value;
                    var arr = val.split( "\n" );

                    var startPos = 0,
                        endPos = val.length;
                    for ( var x = 0; x < arr.length; x++ ) {
                        if ( x == lineNum ) {
                            break;
                        }
                        startPos += ( arr[ x ].length + 1 );

                    }

                    var endPos = arr[ lineNum ].length + startPos;

                    if ( typeof( selector.selectionStart ) != "undefined" ) {
                        selector.focus();
                        selector.selectionStart = startPos;
                        selector.selectionEnd = endPos;
                        return true;
                    }

                    if ( document.selection && document.selection.createRange ) {
                        selector.focus();
                        selector.select();
                        var range = document.selection.createRange();
                        range.collapse( true );
                        range.moveEnd( "character", endPos );
                        range.moveStart( "character", startPos );
                        range.select();
                        return true;
                    }

                    return false;
                };

                var view_row = function( row ) {
                    var mm = row.match( /\:\s+\"([^"]+)\"\s+/ );
                    if ( mm && mm[ 1 ] ) {
                        var idx = mm[ 1 ];
                        var $bt = $selector.find( 'a.button-vcache' );
                        var url = $bt.attr( 'href' );

                        $bt.removeClass( 'hide' );

                        url = url.replace( /\&vcache=.*/, '' );
                        url = url + '&vcache=' + idx;
                        $bt.attr( 'href', url );
                        $selector.find( 'span.vcache' )
                            .removeClass( 'hide' )
                            .html( idx );
                    }
                };
                $selector.find( '.log' )
                    .find( 'textarea#log' )
                    .on(
                        "click",
                        function( e ) {
                            var $self = $( this );
                            var sp = $self
                                .scrollTop()
                            var lh = $self
                                .css( "line-height" );
                            lh = parseInt( lh.substring( 0, lh.length - 2 ) );

                            var line = Math.floor( ( e.offsetY + sp ) / lh );
                            var arr = $self
                                .val()
                                .split( "\n" );
                            var row = arr[ line ];
                            view_row( row );
                            highlight_row( this, line );
                        }
                    );
            }
        );
} )( jQuery );