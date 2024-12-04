/**
 * Initialises the 500px Map modal popup by registering a button
 * in the TinyMCE instance.
 *
 * @since 	3.5.1
 */
(function () {
    tinymce.PluginManager.add("crfp", function (editor, url) {
        // Add Button to Visual Editor Toolbar
        editor.addButton("crfp", {
            title: "Review Comment Integration - Display Rating",
            icon: "icon dashicons-star-filled",
            cmd: "crfp",
        });

        // Load View when button clicked
        editor.addCommand("crfp", function () {
            // Open the TinyMCE Modal
            editor.windowManager.open({
                id: "comment-rating-field-modal",
                title: "Insert Average Rating",
                width: 500,
                height: 710,
                inline: 1,
                buttons: [],
            });

            // Perform an AJAX call to load the modal's view
            jQuery.post(
                ajaxurl,
                {
                    action: "comment_rating_field_pro_output_tinymce_modal",
                },
                function (response) {
                    // Inject HTML into modal
                    jQuery("#comment-rating-field-modal-body").html(response);
                }
            );
        });
    });
})();
