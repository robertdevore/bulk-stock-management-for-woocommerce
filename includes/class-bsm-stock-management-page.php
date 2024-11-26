<?php
/**
 * Class BSM_Stock_Management_Page
 *
 * Handles the Bulk Stock Management admin page functionality.
 *
 * @package BSM_WooCommerce
 */
class BSM_Stock_Management_Page {

    /**
     * BSM_Stock_Management_Page constructor.
     * 
     * @since 1.0.0
     */
    public function __construct() {
        add_action( 'admin_menu', [ $this, 'register_menu_page' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
    }

    /**
     * Registers the stock management page in the WooCommerce submenu.
     *
     * @since  1.0.0
     * @return void
     */
    public function register_menu_page() {
        add_submenu_page(
            'woocommerce',
            esc_html__( 'Stock Management', 'bsm-woocommerce' ),
            esc_html__( 'Stock Management', 'bsm-woocommerce' ),
            'manage_woocommerce',
            'bsm-stock-management',
            [ $this, 'render_stock_management_page' ]
        );
    }

    /**
     * Enqueues assets for the stock management page.
     *
     * @since  1.0.0
     * @return void
     */
    public function enqueue_assets() {
        wp_enqueue_style( 'bsm-admin', BSM_PLUGIN_URL . 'assets/admin.css', [], BSM_PLUGIN_VERSION );

        wp_enqueue_script( 'bsm-admin', BSM_PLUGIN_URL . 'assets/admin.js', [ 'jquery' ], BSM_PLUGIN_VERSION, true );
        wp_localize_script( 'bsm-admin', 'bsm_admin', [
            'nonce'   => wp_create_nonce( 'bsm_admin_nonce' ),
            'ajaxurl' => admin_url( 'admin-ajax.php' ),
        ] );
    }

    /**
     * Renders the stock management page.
     *
     * @return void
     */
    public function render_stock_management_page() {
        ?>
        <div class="wrap">
            <h1>
                <?php esc_html_e( 'Bulk Stock Management', 'bsm-woocommerce' ); ?>
                <a id="bsm-woocommerce-support-btn" href="https://robertdevore.com/contact/" target="_blank" class="button button-alt" style="margin-left: 10px;">
                    <span class="dashicons dashicons-format-chat" style="vertical-align: middle;"></span>
                    <?php esc_html_e( 'Support', 'bluesky-feed' ); ?>
                </a>
                <a id="bsm-woocommerce-docs-btn" href="https://robertdevore.com/articles/bulk-stock-management-for-woocommerce/" target="_blank" class="button button-alt" style="margin-left: 5px;">
                    <span class="dashicons dashicons-media-document" style="vertical-align: middle;"></span>
                    <?php esc_html_e( 'Documentation', 'bluesky-feed' ); ?>
                </a>
            </h1>
            <hr />
            <?php
            // Instantiate the table class once.
            $stock_table = new BSM_Stock_List_Table();

            // Handle bulk actions only once per page load.
            if ( 'POST' === $_SERVER['REQUEST_METHOD'] ) {
                $stock_table->process_bulk_action();
            }

            // Prepare table items once per page load.
            $stock_table->prepare_items();

            // Display the table and its search box.
            ?>
            <form method="post">
                <?php
                $stock_table->search_box( __( 'Search Products', 'bsm-woocommerce' ), 'search-id' );
                $stock_table->display();
                ?>
            </form>    
            <div class="bsm-modal-overlay"></div>
            <div id="bsm-edit-modal" class="bsm-modal">
                <div class="bsm-modal-header">
                    <h2><?php esc_html_e( 'Edit Product Stock', 'bsm-woocommerce' ); ?></h2>
                    <button type="button" class="bsm-modal-close dashicons dashicons-no-alt"></button>
                </div>
                <div class="bsm-modal-body">
                    <label for="bsm-stock-qty"><?php esc_html_e( 'Stock Quantity:', 'bsm-woocommerce' ); ?></label>
                    <input type="number" id="bsm-stock-qty" value="" min="0" />

                    <label for="bsm-stock-status"><?php esc_html_e( 'Stock Status:', 'bsm-woocommerce' ); ?></label>
                    <select id="bsm-stock-status">
                        <option value="instock"><?php esc_html_e( 'In Stock', 'bsm-woocommerce' ); ?></option>
                        <option value="outofstock"><?php esc_html_e( 'Out of Stock', 'bsm-woocommerce' ); ?></option>
                    </select>

                    <label for="bsm-backorders"><?php esc_html_e( 'Backorders:', 'bsm-woocommerce' ); ?></label>
                    <select id="bsm-backorders">
                        <option value="no"><?php esc_html_e( 'Do Not Allow', 'bsm-woocommerce' ); ?></option>
                        <option value="notify"><?php esc_html_e( 'Allow, Notify Customer', 'bsm-woocommerce' ); ?></option>
                        <option value="yes"><?php esc_html_e( 'Allow', 'bsm-woocommerce' ); ?></option>
                    </select>
                </div>
                <div class="bsm-modal-footer">
                    <button type="button" id="bsm-edit-save" class="button button-primary">
                        <?php esc_html_e( 'Save Changes', 'bsm-woocommerce' ); ?>
                    </button>
                    <button type="button" class="button bsm-modal-close"><?php esc_html_e( 'Cancel', 'bsm-woocommerce' ); ?></button>
                </div>
            </div>

        </div>
        <?php
    }
}

/**
 * Handles the AJAX request for updating stock quantities.
 *
 * @since  1.0.0
 * @return void Outputs JSON response.
 */
add_action( 'wp_ajax_bsm_update_stock', function () {
    // Verify the AJAX request nonce.
    check_ajax_referer( 'bsm_admin_nonce', 'nonce' );

    // Sanitize input.
    $product_id = isset( $_POST['product_id'] ) ? intval( wp_unslash( $_POST['product_id'] ) ) : 0;
    $stock_qty  = isset( $_POST['stock_qty'] ) ? intval( wp_unslash( $_POST['stock_qty'] ) ) : null;

    // Validate the input.
    if ( ! $product_id || $stock_qty === null ) {
        wp_send_json_error( esc_html__( 'Invalid product ID or stock quantity.', 'bsm-woocommerce' ) );
    }

    // Load the product.
    $product = wc_get_product( $product_id );
    if ( ! $product ) {
        wp_send_json_error( esc_html__( 'Product not found.', 'bsm-woocommerce' ) );
    }

    try {
        // Update the stock quantity.
        $product->set_stock_quantity( $stock_qty );

        // Automatically set stock status based on quantity.
        if ( $stock_qty <= 0 ) {
            $product->set_stock_status( 'outofstock' );
        } else {
            $product->set_stock_status( 'instock' );
        }

        // Save the product.
        $product->save();

        // Explicitly save stock in the database as a fallback.
        update_post_meta( $product_id, '_stock', $stock_qty );
        update_post_meta( $product_id, '_stock_status', $stock_qty > 0 ? 'instock' : 'outofstock' );

        // Trigger WooCommerce hooks for stock changes.
        do_action( 'woocommerce_product_set_stock', $product );
        do_action( 'woocommerce_product_stock_changed', $product_id );

        wp_send_json_success( esc_html__( 'Stock updated successfully.', 'bsm-woocommerce' ) );
    } catch ( Exception $e ) {
        wp_send_json_error( sprintf( esc_html__( 'Failed to update stock: %s', 'bsm-woocommerce' ), esc_html( $e->getMessage() ) ) );
    }
} );
