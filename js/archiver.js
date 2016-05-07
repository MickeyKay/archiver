/**
 * Archiver JS.
 */

(function ( $ ) {
	'use strict';

	$( document ).ready( function() {

		$( '#wp-admin-bar-archiver-trigger a' ).on( 'click', function( e ) {

			e.preventDefault();

			var ajaxurl = ajaxurl || archiver.ajax_url;

			var $link = $( this );
			var $topLevelLink = $link.closest( '#wp-admin-bar-archiver' ).addClass( 'archiver-active' );

			var data = {
				'action':               'archiver_trigger_archive',
				'archiver_ajax_nonce' : archiver.archiver_ajax_nonce,
				'url':                  archiver.url
			};

			$.post( ajaxurl, data, function( response ) {

				$topLevelLink.removeClass( 'archiver-active' );

				if ( response.success ) {
					$topLevelLink.addClass( 'archiver-success' );
				} else {
					$topLevelLink.addClass( 'archiver-failure' );
					console.warn(response.data);
				}

				setTimeout( function() {
					$topLevelLink.removeClass( 'archiver-success' ).removeClass( 'archiver-failure' )
				}, 2000 );

			});
		});
	});

})( jQuery );