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
                var uh = window.location.href;

                if ( uh && uh.match( /admin\.php\?page=docket-cache/ ) === null ) {
                    return;
                }

                $selector = $( document )
                    .find( 'div#docket-cache' );

                $selector.find( 'div.is-dismissible' )
                    .find( 'button.notice-dismiss' )
                    .on(
                        'click',
                        function() {
                            $( this )
                                .parent()
                                .remove();

                            $selector.find( '.notice-focus' )
                                .removeClass( 'notice-focus' );
                        }
                    );

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

                $selector.find( 'select[data-id=logopt]' )
                    .on(
                        'change',
                        function() {
                            $selector.find( 'a#refresh' )
                                .trigger( 'click' );
                        }
                    );

                window.dospinner = false;
                var spinner = function() {
                    if ( !window.dospinner ) {
                        return;
                    }

                    $( window )
                        .on(
                            'beforeunload',
                            function() {
                                window.dospinner = false;
                                var $overlay = $( document )
                                    .find( '#docket-cache-overlay' );
                                $overlay.css( 'display', 'block' );

                                setTimeout(
                                    function() {
                                        $overlay.css( 'background-color', 'rgba(0,0,0,0.5)' );
                                        $overlay.find( '#wait-spinner' )
                                            .css( 'display', 'block' );
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
                            if ( $( this )
                                .attr( 'disabled' ) ) {
                                return;
                            }
                            window.dospinner = true;
                            spinner();
                        }
                    );

                var $psubmit = $selector.find( 'p.submit' );
                $psubmit.find( 'a.button' )
                    .on(
                        'click',
                        function() {
                            window.dospinner = true;
                            spinner();
                        }
                    );

                $psubmit.find( 'select' )
                    .on(
                        'change',
                        function() {
                            window.dospinner = true;
                            spinner();
                        }
                    );

                $selector.find( '.form-table-selection' )
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
                            .html( '<strong>Cache Index:</strong> ' + idx );

                        $selector.find( 'a.button-vcache-c' )
                            .removeClass( 'hide' );
                    }
                };

                var select_row = function() {
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
                };

                if ( uh.match( /idx=log&vcache=/ ) === null ) {
                    select_row();
                }
            }
        );
} )( jQuery );