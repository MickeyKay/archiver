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
				'action': 'trigger_archive',
				'url': archiver.url
			};

			$.post( ajaxurl, data, function( response ) {

				$topLevelLink.removeClass( 'archiver-active' );

				if ( response.success ) {
					$topLevelLink.addClass( 'archiver-success' );
				} else {
					$topLevelLink.addClass( 'archiver-failure' );
					console.log(response.data);
				}

				window.setTimeout( function() {
					$topLevelLink.removeClass( 'archiver-success' ).removeClass( 'archiver-failure' )
				}, 2000 );

			});
		});
	});

})( jQuery );