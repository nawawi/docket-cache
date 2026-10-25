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

                $selector.find( 'div.notice' )
                    .show( {
                        duration: 100
                    } );

                $selector.find( 'div.is-dismissible' )
                    .find( 'button.notice-dismiss' )
                    .on(
                        'click.dc-dismiss-notice',
                        function( e ) {
                            e.preventDefault();
                            $selector.find( '.notice-focus' )
                                .removeClass( 'notice-focus' );

                            $selector.find( 'p#innotice' )
                                .remove();
                        }
                    );

                $selector.find( 'a#refresh' )
                    .on(
                        'click.dc-refresh',
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
                        'change.dc-refresh',
                        function() {
                            $selector.find( 'a#refresh' )
                                .trigger( 'click' );
                        }
                    );

                $selector.find( 'select.config-filter' )
                    .on(
                        'change.dc-select-config-filter',
                        function() {
                            $selector.find( 'form#config-filter' )
                                .submit();
                            return false;
                        }
                    );

                var close_notice = function() {
                    $selector.find( 'div.is-dismissible' )
                        .find( 'button.notice-dismiss' )
                        .trigger( 'click' );
                };

                window.dospinner = false;
                var spinner = function() {
                    if ( !window.dospinner ) {
                        return;
                    }

                    $( window )
                        .on(
                            'beforeunload.dc-loader',
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
                        'click.dc-btspinner',
                        function() {
                            if ( $( this )
                                .attr( 'disabled' ) ) {
                                return;
                            }
                            window.dospinner = true;
                            spinner();
                        }
                    );

                $selector.find( 'a.btx-cleanuppost' )
                    .on(
                        'click.cleanuppost',
                        function( e ) {
                            e.preventDefault();
                            var url = $( this )
                                .attr( 'href' );
                            url = url.replace( /\&siteid=.*/, '' );
                            var siteid = $selector.find( 'select#siteid' )
                                .children( 'option:selected' )
                                .val() || false;
                            if ( siteid ) {
                                url += '&siteid=' + siteid;
                            }
                            window.dospinner = true;
                            spinner();
                            window.location.replace( url );
                            return false;
                        }
                    );

                var $psubmit = $selector.find( 'p.submit' );
                $psubmit.find( 'a.button' )
                    .on(
                        'click.dc-submit',
                        function() {
                            window.dospinner = true;
                            spinner();
                        }
                    );

                $psubmit.find( 'select' )
                    .on(
                        'change.dc-select',
                        function() {
                            window.dospinner = true;
                            spinner();
                        }
                    );

                $selector.find( '.form-table-selection' )
                    .find( 'select.config-select' )
                    .on(
                        'change.dc-select-config',
                        function() {
                            close_notice();
                            window.dospinner = true;
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
                        'change.dc-select-nav',
                        function() {
                            var $self = $( this );
                            var link = $self.children( 'option:selected' )
                                .attr( 'data-action-link' );
                            window.location.replace( link );
                            return false;
                        }
                    );

                // Row under the click, from the caret.
                var $vcache_bt = $selector.find( 'a.button-vcache' );
                var $vcache_info = $selector.find( 'span.vcache' );
                var $vcache_close = $selector.find( 'a.button-vcache-c' );

                var view_row = function( row ) {
                    var mm = row.match( /\:\s+\"([^"]+)\"\s+/ );
                    if ( mm && mm[ 1 ] ) {
                        var idx = mm[ 1 ];
                        var url = $vcache_bt.attr( 'href' );

                        url = url.replace( /\&vcache=.*/, '' );
                        url = url + '&vcache=' + idx;
                        $vcache_bt.attr( 'href', url )
                            .removeClass( 'hide' );
                        $vcache_info.removeClass( 'hide' )
                            .html( '<strong>Cache Index:</strong> ' + idx );
                        $vcache_close.removeClass( 'hide' );
                    }
                };

                var select_row = function() {
                    $selector.find( '.log' )
                        .find( 'textarea#log' )
                        .on(
                            "click.dc-log",
                            function() {
                                var val = this.value;
                                var pos = this.selectionStart;
                                var start = pos > 0 ? val.lastIndexOf( "\n", pos - 1 ) + 1 : 0;
                                var end = val.indexOf( "\n", pos );
                                if ( end === -1 ) {
                                    end = val.length;
                                }

                                view_row( val.substring( start, end ) );
                                this.setSelectionRange( start, end );
                            }
                        );
                };

                if ( uh.match( /idx=log&vcache=/ ) === null ) {
                    select_row();
                }
            }
        );
} )( jQuery );