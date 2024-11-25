<?php
class BSM_Stock_Management_Page {

    public function __construct() {
        add_action( 'admin_menu', [ $this, 'register_menu_page' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
    }

    public function register_menu_page() {
        add_submenu_page(
            'woocommerce',
            __( 'Stock Management', 'bsm-woocommerce' ),
            __( 'Stock Management', 'bsm-woocommerce' ),
            'manage_woocommerce',
            'bsm-stock-management',
            [ $this, 'render_stock_management_page' ]
        );
    }

    public function enqueue_assets() {
        wp_enqueue_style( 'bsm-admin', BSM_PLUGIN_URL . 'assets/admin.css', [], '1.0.0' );

        wp_enqueue_script( 'bsm-admin', BSM_PLUGIN_URL . 'assets/admin.js', [ 'jquery' ], '1.0.0', true );
        wp_localize_script( 'bsm-admin', 'bsm_admin', [
            'nonce'   => wp_create_nonce( 'bsm_admin_nonce' ),
            'ajaxurl' => admin_url( 'admin-ajax.php' ),
        ] );
    }

    public function render_stock_management_page() {
        echo '<div class="wrap"><h1>' . esc_html__( 'Bulk Stock Management', 'bsm-woocommerce' );
        echo '<a id="bsm-woocommerce-support-btn" href="https://robertdevore.com/contact/" target="_blank" class="button button-alt" style="margin-left: 10px;">
                <span class="dashicons dashicons-format-chat" style="vertical-align: middle;"></span> ' . esc_html__( 'Support', 'bluesky-feed' ) . '
            </a>
            <a id="bsm-woocommerce-docs-btn" href="https://robertdevore.com/articles/bulk-stock-management-for-woocommerce/" target="_blank" class="button button-alt" style="margin-left: 5px;">
                <span class="dashicons dashicons-media-document" style="vertical-align: middle;"></span> ' . esc_html__( 'Documentation', 'bluesky-feed' ) . '
            </a>';
        echo '</h1>';
        echo '<hr />';
    
        $stock_table = new BSM_Stock_List_Table();
    
        // Process bulk actions.
        if ( 'POST' === $_SERVER['REQUEST_METHOD'] ) {
            $stock_table->process_bulk_action();
        }
    
        $stock_table->prepare_items();
    
        echo '<form method="post">';
        $stock_table->search_box( __( 'Search Products', 'bsm-woocommerce' ), 'search-id' );
        $stock_table->display();
        echo '</form></div>';
    }
}

add_action( 'wp_ajax_bsm_update_stock', function () {
    // Verify the AJAX request nonce.
    check_ajax_referer( 'bsm_admin_nonce', 'nonce' );

    // Sanitize input.
    $product_id = isset( $_POST['product_id'] ) ? intval( $_POST['product_id'] ) : 0;
    $stock_qty  = isset( $_POST['stock_qty'] ) ? intval( $_POST['stock_qty'] ) : null;

    // Validate the input.
    if ( ! $product_id || $stock_qty === null ) {
        wp_send_json_error( __( 'Invalid product ID or stock quantity.', 'bsm-woocommerce' ) );
    }

    // Load the product.
    $product = wc_get_product( $product_id );
    if ( ! $product ) {
        wp_send_json_error( __( 'Product not found.', 'bsm-woocommerce' ) );
    }

    // Debug product type.
    error_log( 'Product ID: ' . $product_id );
    error_log( 'Product Type: ' . $product->get_type() );
    error_log( 'Current Stock: ' . $product->get_stock_quantity() );

    try {
        // Update the stock quantity.
        $product->set_stock_quantity( $stock_qty );

        // If the stock is set to zero, mark as "outofstock" automatically.
        if ( $stock_qty <= 0 ) {
            $product->set_stock_status( 'outofstock' );
        } else {
            $product->set_stock_status( 'instock' );
        }

        // Save the product.
        $product->save();

        // Ensure stock is saved explicitly in the database (as a fallback).
        update_post_meta( $product_id, '_stock', $stock_qty );
        update_post_meta( $product_id, '_stock_status', $stock_qty > 0 ? 'instock' : 'outofstock' );

        // Debug updated values.
        error_log( 'Updated Stock: ' . get_post_meta( $product_id, '_stock', true ) );

        // Trigger WooCommerce hooks for stock changes.
        do_action( 'woocommerce_product_set_stock', $product );
        do_action( 'woocommerce_product_stock_changed', $product_id );

        wp_send_json_success( __( 'Stock updated successfully.', 'bsm-woocommerce' ) );
    } catch ( Exception $e ) {
        wp_send_json_error( sprintf( __( 'Failed to update stock: %s', 'bsm-woocommerce' ), $e->getMessage() ) );
    }
} );
