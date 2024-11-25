jQuery(document).ready(function ($) {
    $(".bsm-stock-input").on("change", function () {
        let productId = $(this).data("product-id");
        let newStock = parseInt($(this).val(), 10); // Ensure stock is an integer.
        let inputField = $(this);

        // Validate stock value.
        if (isNaN(newStock) || newStock < 0) {
            alert("Invalid stock value.");
            return;
        }

        console.log("Sending AJAX request with:");
        console.log("Product ID:", productId);
        console.log("New Stock:", newStock);

        // Send AJAX request.
        $.post(bsm_admin.ajaxurl, {
            action: "bsm_update_stock",
            product_id: productId,
            stock_qty: newStock,
            nonce: bsm_admin.nonce,
        })
        .done(function (response) {
            console.log("AJAX Response:", response);
            if (response.success) {
                $("body").append('<div class="bsm-overlay-success">Stock Updated Successfully!</div>');
                $(".bsm-overlay-success").fadeOut(2000, function () {
                    $(this).remove();
                });
            } else {
                alert("Error: " + response.data);
            }
        })
        .fail(function () {
            alert("AJAX request failed. Please try again.");
        });
    });
});
