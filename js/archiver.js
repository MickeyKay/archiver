/**
 * Archiver JS.
 */

( function ( $ ) {

	$( document ).ready( function() {

		var ajaxurl = ajaxurl || archiver.ajax_url;
		var $topMenuItem = $( '#wp-admin-bar-archiver' );

		// Trigger snapshot functionality.
		$( '#wp-admin-bar-archiver-trigger a' ).on( 'click', function( e ) {

			e.preventDefault();

			var $menuItem = $( this ).closest( 'li' ).addClass( 'archiver-active' );

			var data = {
				'action':               'archiver_trigger_archive',
				'archiver_ajax_nonce' : archiver.archiver_ajax_nonce,
				'url':                  archiver.url
			};

			$.post( ajaxurl, data, function( response ) {

				$menuItem.removeClass( 'archiver-active' );

				if ( response.success ) {
					$menuItem.addClass( 'archiver-success' );
					$topMenuItem.addClass( 'archiver-success' );
				} else {
					$menuItem.addClass( 'archiver-failure' );
					$topMenuItem.addClass( 'archiver-failure' );
					console.warn(response.data);
				}

				setTimeout( function() {
					$menuItem.removeClass( 'archiver-success' ).removeClass( 'archiver-failure' );
					$topMenuItem.removeClass( 'archiver-success' ).removeClass( 'archiver-failure' )
				}, 2000 );

			});
		});

		// Dismiss notice functionality.
		$( '.archiver-notice' ).on( 'click', '.notice-dismiss', function() {

			var data = {
				'action':               'archiver_dismiss_notice',
				'archiver_ajax_nonce' : archiver.archiver_ajax_nonce,
				'notice_id':            $( this ).closest( '.archiver-notice' ).attr( 'id' )
			};

			$.post( ajaxurl, data, function( response ) {

				if ( ! response.success ) {
					console.warn(response.data);
				}
			});
		});
	});

})( jQuery );