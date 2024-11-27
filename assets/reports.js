jQuery(document).ready(function ($) {
    $.post(bsm_report_data.ajaxurl, {
        action: "bsm_get_stock_report",
        nonce: bsm_report_data.nonce,
    }, function (response) {
        if (response.success) {
            // Update Summary.
            $("#bsm-total-products").text(response.data.total_products);
            $("#bsm-in-stock").text(response.data.in_stock);
            $("#bsm-out-of-stock").text(response.data.out_of_stock);

            // Populate Charts.
            new Chart($("#bsm-stock-status-chart"), {
                type: "pie",
                data: {
                    labels: ["In Stock", "Out of Stock", "Backorders"],
                    datasets: [{
                        data: [
                            response.data.in_stock,
                            response.data.out_of_stock,
                            response.data.backorders
                        ],
                        backgroundColor: ["#4CAF50", "#F44336", "#FFC107"]
                    }]
                }
            });

            // Populate Out-of-Stock Table (Limit to 10 items).
            let outOfStockCount = 0;
            response.data.products.forEach(function (product) {
                if (product.stock_status.toLowerCase() === "outofstock" && outOfStockCount < 10) {
                    const editLink = bsm_report_data.ajaxurl.replace(
                        "admin-ajax.php",
                        `post.php?post=${product.id}&action=edit`
                    );
                    $("#bsm-out-of-stock-table tbody").append(`
                        <tr>
                            <td><a href="${editLink}" target="_blank">${product.name}</a></td>
                            <td>${product.sku}</td>
                        </tr>
                    `);
                    outOfStockCount++;
                }
            });

            // Show a note if there are more than 10 out-of-stock products.
            if (response.data.out_of_stock > 10) {
                $("#bsm-out-of-stock-table tbody").append(`
                    <tr>
                        <td colspan="2" style="text-align: center; font-style: italic;">
                            ${response.data.out_of_stock - 10} more out-of-stock products...
                        </td>
                    </tr>
                `);
            }
        } else {
            alert("Failed to load report data.");
        }
    }).fail(function (xhr, status, error) {
        alert("Failed to fetch report data.");
        console.error("AJAX Error:", status, error);
    });
});
