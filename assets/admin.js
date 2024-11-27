jQuery(document).ready(function ($) {
    $(".bsm-stock-input").on("change", function () {
        let productId = $(this).data("product-id");
        let newStock = parseInt($(this).val(), 10);
        let inputField = $(this);

        // Validate stock value.
        if (isNaN(newStock) || newStock < 0) {
            alert("Invalid stock value.");
            return;
        }

        // Send AJAX request.
        $.post(bsm_admin.ajaxurl, {
            action: "bsm_update_stock",
            product_id: productId,
            stock_qty: newStock,
            nonce: bsm_admin.nonce,
        })
        .done(function (response) {
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

    // Open Edit Modal.
    $(".bsm-edit-button").on("click", function (e) {
        e.preventDefault();
        let productId = $(this).data("product-id");

        // Preload product data via AJAX.
        $.post(bsm_admin.ajaxurl, {
            action: "bsm_get_product_data",
            product_id: productId,
            nonce: bsm_admin.nonce,
        })
        .done(function (response) {
            if (response.success) {
                // Populate modal fields with product data.
                $("#bsm-stock-qty").val(response.data.stock_qty);
                $("#bsm-stock-status").val(response.data.stock_status);
                $("#bsm-backorders").val(response.data.backorders);

                // Open modal.
                $("#bsm-edit-modal").data("product-id", productId).fadeIn();
                $(".bsm-modal-overlay").fadeIn();
            } else {
                alert("Error: " + response.data);
            }
        })
        .fail(function () {
            alert("Failed to fetch product data.");
        });
    });

    // Save Changes from Edit Modal.
    $("#bsm-edit-save").on("click", function (e) {
        e.preventDefault();
        let modal = $("#bsm-edit-modal");
        let productId = modal.data("product-id");
        let stockQty = parseInt($("#bsm-stock-qty").val(), 10);
        let stockStatus = $("#bsm-stock-status").val();
        let backorders = $("#bsm-backorders").val();

        // AJAX to update fields.
        $.post(bsm_admin.ajaxurl, {
            action: "bsm_update_stock_fields",
            product_id: productId,
            stock_qty: stockQty,
            stock_status: stockStatus,
            backorders: backorders,
            nonce: bsm_admin.nonce,
        })
        .done(function (response) {
            if (response.success) {
                // Replace modal content with success message.
                modal.find(".bsm-modal-body").html('<p class="bsm-success-message">Changes saved successfully!</p>');

                // Hide buttons.
                $("#bsm-edit-save, .bsm-modal-close").hide();

                // Reload table after a short delay.
                setTimeout(function () {
                    location.reload();
                }, 1000);
            } else {
                alert("Error: " + response.data);
            }
        })
        .fail(function () {
            alert("AJAX request failed. Please try again.");
        });
    });

    // Open Delete Modal.
    $(".bsm-delete-button").on("click", function (e) {
        e.preventDefault();
        let productId = $(this).data("product-id");
        if (confirm("Are you sure you want to delete this product?")) {
            // AJAX to delete product.
            $.post(bsm_admin.ajaxurl, {
                action: "bsm_delete_product",
                product_id: productId,
                nonce: bsm_admin.nonce,
            })
            .done(function (response) {
                if (response.success) {
                    alert(response.data);
                    location.reload();
                } else {
                    alert("Error: " + response.data);
                }
            })
            .fail(function () {
                alert("AJAX request failed. Please try again.");
            });
        }
    });

    // Close Modals.
    $(".bsm-modal-close, .bsm-modal-overlay").on("click", function (e) {
        e.preventDefault();
        $(".bsm-modal, .bsm-modal-overlay").fadeOut();
    });
});
