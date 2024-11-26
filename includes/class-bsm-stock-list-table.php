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
    
        // Base query.
        $query = "SELECT ID FROM {$wpdb->posts} p";
        $where = " WHERE p.post_type = 'product' AND p.post_status = 'publish'";

        // Add search term conditions.
        if ( $search_term ) {
            $query .= " LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id";
            $where .= $wpdb->prepare(
                " AND (p.post_title LIKE %s OR (pm.meta_key = '_sku' AND pm.meta_value LIKE %s))",
                '%' . $wpdb->esc_like( $search_term ) . '%',
                '%' . $wpdb->esc_like( $search_term ) . '%'
            );
        }

        // Add stock status condition.
        if ( $stock_status ) {
            $query .= " LEFT JOIN {$wpdb->postmeta} pm_stock_status ON p.ID = pm_stock_status.post_id";
            $where .= $wpdb->prepare(
                " AND (pm_stock_status.meta_key = '_stock_status' AND pm_stock_status.meta_value = %s)",
                $stock_status
            );
        }

        // Combine query and where clause.
        $query .= $where . " GROUP BY p.ID";

        // Pagination.
        $per_page     = 40;
        $current_page = $this->get_pagenum();
        $offset       = ( $current_page - 1 ) * $per_page;

        // Get total items
        $total_items = $wpdb->query( $query );

        // Add limit for pagination
        $query .= $wpdb->prepare( " LIMIT %d OFFSET %d", $per_page, $offset );

        // Fetch product IDs
        $product_ids = $wpdb->get_col( $query );

        $data = [];
        foreach ( $product_ids as $product_id ) {
            $product = wc_get_product( $product_id );
            if ( ! $product ) {
                continue;
            }

            $data[] = [
                'ID'           => $product->get_id(),
                'name'         => $product->get_name(),
                'sku'          => $product->get_sku() ?: __( 'N/A', 'bsm-woocommerce' ),
                'stock_qty'    => $product->get_stock_quantity() ?? __( 'N/A', 'bsm-woocommerce' ),
                'stock_status' => $product->get_stock_status() === 'instock' ? __( 'In Stock', 'bsm-woocommerce' ) : __( 'Out of Stock', 'bsm-woocommerce' ),
            ];
        }

        $this->items = $data;

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
     * @param array $item Row data.
     * 
     * @since  1.0.0
     * @return string Action buttons HTML.
     */
    public function column_actions( $item ) {
        return sprintf(
            '<a href="%s" class="button">%s</a>',
            esc_url( admin_url( 'post.php?post=' . $item['ID'] . '&action=edit' ) ),
            esc_html__( 'Edit', 'bsm-woocommerce' )
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
