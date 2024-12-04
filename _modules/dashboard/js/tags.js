/**
* Tags
*/
jQuery( document ).ready( function( $ ) {

	var wpzinc_tags = function() {
		$( 'select.wpzinc-tags' ).each( function() {
			$( this ).unbind( 'change.wpzinc-tags' ).on( 'change.wpzinc-tags', function( e ) {

				// Insert tag into required input or textarea
				var tag 	= $( this ).val(),
					ele 	= $( this ).data( 'element' ),
					val 	= $( ele ).val();

				// If the target element is a TinyMCE instance, handle this differently.
				if ( $( ele ).hasClass( 'tmce-active' ) ) {
				} else {
					// Get position of cursor
					var pos = $( ele )[0].selectionStart

					// Pad tag if cursor not at start
					if ( pos > 0 ) {
						tag = ' ' + tag;
					}

					// Insert tag
					$( ele ).val( val.substring( 0, pos ) + tag + val.substring( pos ) );
				}

			} );
		} );
	}
	
	wpzinc_tags();

} );