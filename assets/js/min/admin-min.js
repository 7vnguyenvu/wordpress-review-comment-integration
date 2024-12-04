jQuery(document).ready(function ($) {
    $("a.crfp-delete-ratings").on("click", function (e) {
        e.preventDefault();
        var t = confirm(crfp.delete_ratings);
        t &&
            $.ajax({
                type: "POST",
                url: crfp.ajax_url,
                data: { action: "comment_rating_field_pro_delete_all_ratings", post_id: crfp.post_id, nonce: crfp.nonce },
                success: function (e) {
                    "1" == e &&
                        ($("*[class*='crfp-group']").hide(),
                        $("#review-comment-integration-pro-plugin-ratings div.inside div.option:first-child p").text(crfp.deleted_ratings),
                        $("#review-comment-integration-pro-plugin-ratings div.inside div.option:last-child").hide());
                },
                error: function (e, t, i) {
                    alert(t);
                },
            });
    }),
        $(".color-picker-control").each(function () {
            $(this).wpColorPicker();
        }),
        $("button.add-rating-field").on("click", function (e) {
            e.preventDefault(),
                $("#sortable").append($("div.field.hidden").html()),
                $("#sortable div.option").each(function (e) {
                    $("span.hierarchy", $(this)).text(e + 1);
                });
        }),
        $("#sortable").length > 0 && $("#sortable").sortable(),
        $(document).on("click", "a.delete-rating-field", function (e) {
            e.preventDefault();
            var t = confirm(crfp.delete_rating_field);
            t && $(this).parent().parent().remove();
        });
});
