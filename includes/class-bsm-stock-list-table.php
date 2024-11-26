<?php
/**
 * Class BSM_Stock_List_Table
 *
 * Manages the Bulk Stock Management stock list table in WordPress admin.
 *
 * @package BSM_WooCommerce
 */

class BSM_Stock_List_Table extends WP_List_Table {

    /**
     * Constructor.
     *
     * Initializes the list table with default parameters.
     *
     * @since 1.0.0
     */
    public function __construct() {
        parent::__construct( [
            'singular' => esc_html__( 'Product', 'bsm-woocommerce' ),
            'plural'   => esc_html__( 'Products', 'bsm-woocommerce' ),
            'ajax'     => false,
        ] );
    }

    /**
     * Defines the columns for the table.
     *
     * @since 1.0.0
     * @return array Associative array of column headers.
     */
    public function get_columns() {
        return [
            'cb'           => '<input type="checkbox" />',
            'name'         => esc_html__( 'Name', 'bsm-woocommerce' ),
            'sku'          => esc_html__( 'SKU', 'bsm-woocommerce' ),
            'stock_qty'    => esc_html__( 'Stock Quantity', 'bsm-woocommerce' ),
            'stock_status' => esc_html__( 'Stock Status', 'bsm-woocommerce' ),
            'actions'      => esc_html__( 'Actions', 'bsm-woocommerce' ),
        ];
    }

    /**
     * Defines sortable columns.
     *
     * @since 1.0.0
     * @return array Associative array of sortable columns.
     */
    public function get_sortable_columns() {
        return [
            'name'      => [ 'name', true ],
            'sku'       => [ 'sku', false ],
            'stock_qty' => [ 'stock_qty', false ],
        ];
    }

    /**
     * Defines bulk actions available for the table.
     *
     * @since 1.0.0
     * @return array Associative array of bulk actions.
     */
    public function get_bulk_actions() {
        return [
            'mark_in_stock'     => esc_html__( 'Mark “In Stock”', 'bsm-woocommerce' ),
            'mark_out_of_stock' => esc_html__( 'Mark “Out of Stock”', 'bsm-woocommerce' ),
        ];
    }

    /**
     * Prepares the items for the list table.
     *
     * Retrieves and processes product data for display, including search, filters, and pagination.
     *
     * @since 1.0.0
     * @return void
     */
    public function prepare_items() {
        global $wpdb;
    
        $search_term  = isset( $_REQUEST['s'] ) ? sanitize_text_field( $_REQUEST['s'] ) : ''; // Search term
        $stock_status = isset( $_REQUEST['stock_status'] ) ? sanitize_text_field( $_REQUEST['stock_status'] ) : ''; // Stock status filter
        $orderby      = isset( $_REQUEST['orderby'] ) ? sanitize_sql_orderby( $_REQUEST['orderby'] ) : 'name'; // Sort column
        $order        = isset( $_REQUEST['order'] ) && in_array( strtolower( $_REQUEST['order'] ), [ 'asc', 'desc' ], true ) ? strtoupper( $_REQUEST['order'] ) : 'ASC'; // Sort direction
    
        // Map table columns to database columns
        $sortable_columns_map = [
            'name'      => 'p.post_title',
            'sku'       => "pm.meta_value",
            'stock_qty' => "CAST(pm_stock.meta_value AS UNSIGNED)",
        ];
        $orderby_column = $sortable_columns_map[$orderby] ?? 'p.post_title';
    
        // Base query
        $query = "SELECT p.ID, p.post_title, 
                         pm.meta_value AS sku, 
                         pm_stock.meta_value AS stock_qty, 
                         pm_stock_status.meta_value AS stock_status
                  FROM {$wpdb->posts} p 
                  LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_sku'
                  LEFT JOIN {$wpdb->postmeta} pm_stock ON p.ID = pm_stock.post_id AND pm_stock.meta_key = '_stock'
                  LEFT JOIN {$wpdb->postmeta} pm_stock_status ON p.ID = pm_stock_status.post_id AND pm_stock_status.meta_key = '_stock_status'
                  WHERE p.post_type = 'product' AND p.post_status = 'publish'";
    
        // Add search term
        if ( $search_term ) {
            $query .= $wpdb->prepare(
                " AND (p.post_title LIKE %s OR pm.meta_value LIKE %s)",
                '%' . $wpdb->esc_like( $search_term ) . '%',
                '%' . $wpdb->esc_like( $search_term ) . '%'
            );
        }
    
        // Add stock status filter
        if ( $stock_status ) {
            $query .= $wpdb->prepare(
                " AND pm_stock_status.meta_value = %s",
                $stock_status
            );
        }
    
        // Sorting
        $query .= " GROUP BY p.ID ORDER BY $orderby_column $order";
    
        // Pagination
        $per_page     = 40;
        $current_page = $this->get_pagenum();
        $offset       = ( $current_page - 1 ) * $per_page;
    
        $total_items_query = "SELECT COUNT(*) FROM ({$query}) AS total_items";
        $total_items       = $wpdb->get_var( $total_items_query );
    
        $query .= $wpdb->prepare( " LIMIT %d OFFSET %d", $per_page, $offset );
    
        $results = $wpdb->get_results( $query, ARRAY_A );
    
        // Prepare items
        $this->items = array_map( function ( $row ) {
            return [
                'ID'           => $row['ID'],
                'name'         => $row['post_title'],
                'sku'          => $row['sku'] ?: __( 'N/A', 'bsm-woocommerce' ),
                'stock_qty'    => intval( $row['stock_qty'] ),
                'stock_status' => $row['stock_status'] === 'instock' ? __( 'In Stock', 'bsm-woocommerce' ) : __( 'Out of Stock', 'bsm-woocommerce' ),
            ];
        }, $results );
    
        // Set up table headers and pagination
        $this->_column_headers = [
            $this->get_columns(),
            [],
            $this->get_sortable_columns(),
        ];
    
        $this->set_pagination_args( [
            'total_items' => $total_items,
            'per_page'    => $per_page,
            'total_pages' => ceil( $total_items / $per_page ),
        ] );
    }

    /**
     * Renders default column content.
     *
     * @param array  $item        Row data.
     * @param string $column_name Column name.
     * 
     * @since  1.0.0
     * @return string Column content.
     */
    public function column_default( $item, $column_name ) {
        return $item[ $column_name ] ?? '';
    }

    /**
     * Renders the checkbox column for bulk actions.
     *
     * @param array $item Row data.
     * 
     * @since  1.0.0
     * @return string Checkbox HTML.
     */
    public function column_cb( $item ) {
        return sprintf( '<input type="checkbox" name="product[]" value="%d" />', esc_attr( $item['ID'] ) );
    }

    /**
     * Renders the stock quantity column.
     *
     * Outputs an editable input field for stock quantity.
     *
     * @param array $item Row data.
     * 
     * @since  1.0.0
     * @return string HTML input field for stock quantity.
     */
    public function column_stock_qty( $item ) {
        return sprintf(
            '<input type="number" class="bsm-stock-input" value="%d" data-product-id="%d" min="0" />',
            esc_attr( $item['stock_qty'] ),
            esc_attr( $item['ID'] )
        );
    }

    /**
     * Renders the actions column.
     *
     * Includes styled edit and delete buttons with icons.
     *
     * @param array $item Row data.
     * 
     * @since  1.0.0
     * @return string HTML for action buttons.
     */
    public function column_actions( $item ) {
        return sprintf(
            '<button type="button" class="button bsm-edit-button" data-product-id="%d">
                <span class="dashicons dashicons-edit"></span> %s
            </button>
            <button type="button" class="button button-secondary bsm-delete-button" data-product-id="%d">
                <span class="dashicons dashicons-trash"></span> %s
            </button>',
            esc_attr( $item['ID'] ),
            esc_html__( 'Edit', 'bsm-woocommerce' ),
            esc_attr( $item['ID'] ),
            esc_html__( 'Delete', 'bsm-woocommerce' )
        );
    }

    /**
     * Displays the table rows.
     *
     * Iterates over the items and renders each row.
     *
     * @since 1.0.0
     * @return void
     */
    public function display_rows() {
        foreach ( $this->items as $item ) {
            $this->single_row( $item );
        }
    }

    /**
     * Displays a message when no items are found.
     *
     * @since 1.0.0
     * @return void
     */
    public function no_items() {
        esc_html_e( 'No products found.', 'bsm-woocommerce' );
    }

    /**
     * Renders additional controls above the table.
     *
     * Outputs a dropdown to filter products by stock status.
     *
     * @param string $which Top or bottom position.
     * 
     * @since 1.0.0
     * @return void
     */
    public function extra_tablenav( $which ) {
        if ( 'top' === $which ) {
            ?>
            <div class="alignleft actions">
                <select name="stock_status">
                    <option value=""><?php esc_html_e( 'All Stock Statuses', 'bsm-woocommerce' ); ?></option>
                    <option value="instock" <?php selected( $_REQUEST['stock_status'] ?? '', 'instock' ); ?>>
                        <?php esc_html_e( 'In Stock', 'bsm-woocommerce' ); ?>
                    </option>
                    <option value="outofstock" <?php selected( $_REQUEST['stock_status'] ?? '', 'outofstock' ); ?>>
                        <?php esc_html_e( 'Out of Stock', 'bsm-woocommerce' ); ?>
                    </option>
                </select>
                <?php submit_button( esc_html__( 'Filter', 'bsm-woocommerce' ), 'button', '', false ); ?>
            </div>
            <?php
        }
    }

    /**
     * Processes bulk actions.
     *
     * Updates stock status for selected products based on the selected bulk action.
     *
     * @since 1.0.0
     * @return void
     */
    public function process_bulk_action() {
        $action      = $this->current_action();
        $product_ids = array_map( 'intval', wp_unslash( $_REQUEST['product'] ?? [] ) );

        if ( empty( $product_ids ) ) {
            return;
        }

        foreach ( $product_ids as $product_id ) {
            $product = wc_get_product( $product_id );
            if ( ! $product ) {
                continue;
            }

            switch ( $action ) {
                case 'mark_in_stock':
                    $product->set_stock_status( 'instock' );
                    break;
                case 'mark_out_of_stock':
                    $product->set_stock_status( 'outofstock' );
                    break;
            }

            $product->save();
        }
    }
}

add_action( 'wp_ajax_bsm_delete_product', function () {
    // Verify nonce
    check_ajax_referer( 'bsm_admin_nonce', 'nonce' );

    // Sanitize and validate product ID
    $product_id = isset( $_POST['product_id'] ) ? intval( $_POST['product_id'] ) : 0;
    if ( ! $product_id ) {
        wp_send_json_error( esc_html__( 'Invalid product ID.', 'bsm-woocommerce' ) );
    }

    // Attempt to trash the product
    $result = wp_trash_post( $product_id );
    if ( ! $result ) {
        wp_send_json_error( esc_html__( 'Failed to delete the product.', 'bsm-woocommerce' ) );
    }

    wp_send_json_success( esc_html__( 'Product moved to trash.', 'bsm-woocommerce' ) );
} );

add_action( 'wp_ajax_bsm_update_stock_fields', function () {
    // Verify nonce
    check_ajax_referer( 'bsm_admin_nonce', 'nonce' );

    $product_id    = intval( $_POST['product_id'] ?? 0 );
    $stock_qty     = intval( $_POST['stock_qty'] ?? 0 );
    $stock_status  = sanitize_text_field( $_POST['stock_status'] ?? '' );
    $backorders    = sanitize_text_field( $_POST['backorders'] ?? '' );

    if ( ! $product_id ) {
        wp_send_json_error( __( 'Invalid product ID.', 'bsm-woocommerce' ) );
    }

    $product = wc_get_product( $product_id );
    if ( ! $product ) {
        wp_send_json_error( __( 'Product not found.', 'bsm-woocommerce' ) );
    }

    try {
        // Update stock quantity
        $product->set_stock_quantity( $stock_qty );

        // Update stock status
        if ( $stock_status ) {
            $product->set_stock_status( $stock_status );
        }

        // Update backorders
        if ( $backorders ) {
            $product->set_backorders( $backorders );
        }

        // Save product changes
        $product->save();

        // Manually update stock meta for consistency
        update_post_meta( $product_id, '_stock', $stock_qty );
        update_post_meta( $product_id, '_stock_status', $stock_status );
        update_post_meta( $product_id, '_backorders', $backorders );

        // Trigger WooCommerce stock change hooks
        do_action( 'woocommerce_product_set_stock', $product );
        do_action( 'woocommerce_product_stock_changed', $product_id );

        wp_send_json_success( __( 'Product updated successfully.', 'bsm-woocommerce' ) );
    } catch ( Exception $e ) {
        wp_send_json_error( sprintf( __( 'Failed to update product: %s', 'bsm-woocommerce' ), $e->getMessage() ) );
    }
} );


add_action( 'wp_ajax_bsm_get_product_data', function () {
    // Verify nonce
    check_ajax_referer( 'bsm_admin_nonce', 'nonce' );

    $product_id = intval( $_POST['product_id'] ?? 0 );
    if ( ! $product_id ) {
        wp_send_json_error( __( 'Invalid product ID.', 'bsm-woocommerce' ) );
    }

    $product = wc_get_product( $product_id );
    if ( ! $product ) {
        wp_send_json_error( __( 'Product not found.', 'bsm-woocommerce' ) );
    }

    // Send product data
    wp_send_json_success( [
        'stock_qty'    => $product->get_stock_quantity(),
        'stock_status' => $product->get_stock_status(),
        'backorders'   => $product->get_backorders(),
    ] );
} );
