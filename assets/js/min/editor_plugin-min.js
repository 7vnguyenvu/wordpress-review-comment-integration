!(function () {
    tinymce.PluginManager.add("crfp", function (n, t) {
        n.addButton("crfp", { title: "Review Comment Integration - Display Rating", icon: "icon dashicons-star-filled", cmd: "crfp" }),
            n.addCommand("crfp", function () {
                n.windowManager.open({
                    id: "comment-rating-field-modal",
                    title: "Insert Average Rating",
                    width: 500,
                    height: 710,
                    inline: 1,
                    buttons: [],
                }),
                    jQuery.post(ajaxurl, { action: "comment_rating_field_pro_output_tinymce_modal" }, function (n) {
                        jQuery("#comment-rating-field-modal-body").html(n);
                    });
            });
    });
})();
