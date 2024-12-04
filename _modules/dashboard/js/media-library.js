/**
 * Choose Image from Media Library
 */
( function( $ ){

	// Open Media Library
	$( '#wpbody' ).on( 'click', '.insert-media-plugin', function( e ) {

		// Prevent default action
		e.preventDefault();

		// Get some attributes from the button we clicked
		// This tells us where to store some values later on
		var input_id = $( this ).data( 'input' ), 			// Should be an input field with this ID
			output_id = $( this ).data( 'output' ), 		// Should be an output field with this ID
			output_size = $( this ).data( 'output-size' ) 	// The size of the image to output

		if ( typeof output_size == undefined ) {
			output_size = 'thumbnail';
		}

		// If plugin_media_manager has already been defined, open it now
		if ( plugin_media_manager ) {
			plugin_media_manager.open();
			return;
		}

		// Setup new wp.media instance, if it's not already set by one of our plugins
		var plugin_media_manager = wp.media( {
			title: 'Choose Image',
			button: {
				text: 'Select',
			},
			multiple: false
		} );
		
		/**
		* Insert Media
		*/
		plugin_media_manager.on( 'select', function() {
			// Get selected attachment and some of its attributes
			var attachment = plugin_media_manager.state().get( 'selection' ).first(),
				attachment_id = attachment.get( 'id' ),
				attachment_url = attachment.get( 'url' );

			// If the image size we're requesting exists, use that instead
			if ( typeof attachment.attributes.sizes[ output_size ] !== undefined ) {
				attachment_url = attachment.attributes.sizes[ output_size ].url;
			}

			// Insert the attachment URL
			$( 'input#' + input_id ).val( attachment_id );
			$( 'img#' + output_id ).attr( 'src', attachment_url );
		} );

		// Open the Media View
		plugin_media_manager.open();

	} );

	// Remove a chosen image
	$( '#wpbody' ).on( 'click', '.delete-media-plugin', function( e ) {
		// Prevent default action
		e.preventDefault();

		// Get some attributes from the button we clicked
		// This tells us where to store some values later on
		var input_id = $( this ).data( 'input' ), // Should be an input field with this ID
			output_id = $( this ).data( 'output' ); // Should be an output field with this ID

		// Remove image
		$( 'input#' + input_id ).val( '' );
		$( 'img#' + output_id ).attr( 'src', '' );
	} );

} ( jQuery ) );