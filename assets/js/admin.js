jQuery( document ).ready( function( $ ) {

	/**
	* Color Pickers
	*/
	$( '.color-picker-control' ).each( function() {
		$( this ).wpColorPicker();
	} );

} );