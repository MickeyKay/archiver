/**
 * Archiver JS.
 */

(function ( $ ) {
	'use strict';

	$( document ).ready( function() {

		$( '#wp-admin-bar-archiver-trigger a' ).on( 'click', function( e ) {

			e.preventDefault();

			var ajaxurl = ajaxurl || archiver.ajax_url;

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
				} else {
					$menuItem.addClass( 'archiver-failure' );
					console.warn(response.data);
				}

				setTimeout( function() {
					$menuItem.removeClass( 'archiver-success' ).removeClass( 'archiver-failure' )
				}, 2000 );

			});
		});
	});

})( jQuery );