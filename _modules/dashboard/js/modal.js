/**
 * Handles the Insert and Cancel events on TinyMCE Modals
 *
 * @since   1.0.0
 */
jQuery( document ).ready( function( $ ) {
    
    // Cancel
    $( 'body' ).on( 'click', 'form.wpzinc-tinymce-popup button.close', function( e ) {

        if ( $( '.mce-container-body' ).length > 0 ) {
            // Visual Editor
            tinymce.activeEditor.windowManager.close();
        } else {
            // Text Editor
            parent.tb_remove();
        }

    } );

    // Insert
    $( 'body' ).on( 'click', 'form.wpzinc-tinymce-popup input[type=submit]', function( e ) {

        // Prevent default action
        e.preventDefault();

        // Get containing form
        var form = $( this ).closest( 'form.wpzinc-tinymce-popup' );

        // Build Shortcode
        var shortcode = '[' + $( 'input[name=shortcode]', $( form ) ).val();

        $( 'input, select', $( form ) ).each( function( i ) {
            // Skip if no data-shortcode attribute
            if ( typeof $( this ).data( 'shortcode' ) === 'undefined' ) {
                return true; // Skip this entry
            }

            shortcode += ' ' + $( this ).data( 'shortcode' ) + '="' + $( this ).val() + '"';
        } );

        // Close Shortcode
        shortcode += ']';

        /**
         * Finish building the link, and insert it, depending on whether we were initialized from
         * the Visual Editor or not.
         */
        if ( $( '.mce-container-body' ).length > 0 ) {
            // TinyMCE
            // tinyMCE.activeEditor will give you the TinyMCE editor instance that's being used.
            
            // Insert into editor
            tinyMCE.activeEditor.execCommand( 'mceReplaceContent', false, shortcode );

            // Close modal
            tinyMCE.activeEditor.windowManager.close();

            // Done
            return;
        } else {
            // Insert into text editor
            editor.value += shortcode;

            // Trigger a change event for scripts that watch for changes to the Text editor content
            $( editor ).trigger( 'change' ); 

            // Close popup
            parent.tb_remove();
        }

    } );

} );