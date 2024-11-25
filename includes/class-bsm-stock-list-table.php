<?php
/**
 * BSM_Stock_List_Table Class
 *
 * Manages the Bulk Stock Management stock list table in WordPress admin.
 *
 * @package BSM_WooCommerce
 */

// Ensure WP_List_Table is available.
if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Class BSM_Stock_List_Table
 *
 * Extends WP_List_Table to display product stock data.
 */
class BSM_Stock_List_Table extends WP_List_Table {

    /**
     * Constructor.
     *
     * Initializes the table class.
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
     * Define table columns.
     *
     * @since  1.0.0
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
     * Define sortable columns.
     *
     * @since  1.0.0
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
     * Define bulk actions.
     *
     * @since  1.0.0
     * @return array Associative array of bulk actions.
     */
    public function get_bulk_actions() {
        return [
            'mark_in_stock'     => esc_html__( 'Mark “In Stock”', 'bsm-woocommerce' ),
            'mark_out_of_stock' => esc_html__( 'Mark “Out of Stock”', 'bsm-woocommerce' ),
        ];
    }

    /**
     * Prepare items for display.
     *
     * Fetches and processes the product data for the table, including pagination.
     *
     * @since  1.0.0
     * @return void
     */
    public function prepare_items() {
        $args = [
            'limit'   => -1,
            'orderby' => sanitize_text_field( $_GET['orderby'] ?? 'name' ),
            'order'   => sanitize_text_field( $_GET['order'] ?? 'asc' ),
            'return'  => 'ids',
        ];

        if ( isset( $_GET['stock_status'] ) && in_array( $_GET['stock_status'], [ 'instock', 'outofstock' ], true ) ) {
            $args['stock_status'] = sanitize_text_field( $_GET['stock_status'] );
        }

        $product_ids = wc_get_products( $args );
        $data        = [];

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

        $per_page     = 10;
        $current_page = $this->get_pagenum();
        $total_items  = count( $data );

        $this->items = array_slice( $data, ( $current_page - 1 ) * $per_page, $per_page );

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
     * Default column renderer.
     *
     * Outputs the value for a given column and item.
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
     * Render the checkbox column for bulk actions.
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
     * Render the stock quantity column.
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
     * Render the actions column.
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
     * Display table rows.
     *
     * Iterates over the items and renders each row.
     *
     * @since  1.0.0
     * @return void
     */
    public function display_rows() {
        foreach ( $this->items as $item ) {
            $this->single_row( $item );
        }
    }

    /**
     * Display a message when no items are found.
     *
     * Outputs a user-friendly message.
     *
     * @since  1.0.0
     * @return void
     */
    public function no_items() {
        esc_html_e( 'No products found.', 'bsm-woocommerce' );
    }

    /**
     * Render additional controls above the table.
     *
     * Outputs a dropdown to filter products by stock status.
     *
     * @param string $which Top or bottom position.
     *
     * @since  1.0.0
     * @return void
     */
    public function extra_tablenav( $which ) {
        if ( 'top' === $which ) {
            ?>
            <div class="alignleft actions">
                <select name="stock_status">
                    <option value=""><?php esc_html_e( 'All Stock Statuses', 'bsm-woocommerce' ); ?></option>
                    <option value="instock" <?php selected( $_GET['stock_status'] ?? '', 'instock' ); ?>>
                        <?php esc_html_e( 'In Stock', 'bsm-woocommerce' ); ?>
                    </option>
                    <option value="outofstock" <?php selected( $_GET['stock_status'] ?? '', 'outofstock' ); ?>>
                        <?php esc_html_e( 'Out of Stock', 'bsm-woocommerce' ); ?>
                    </option>
                </select>
                <?php submit_button( esc_html__( 'Filter', 'bsm-woocommerce' ), 'button', '', false ); ?>
            </div>
            <?php
        }
    }
}
