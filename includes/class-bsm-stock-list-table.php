<?php
if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class BSM_Stock_List_Table extends WP_List_Table {

    public function __construct() {
        parent::__construct( [
            'singular' => __( 'Product', 'bsm-woocommerce' ),
            'plural'   => __( 'Products', 'bsm-woocommerce' ),
            'ajax'     => false, // Disable AJAX for simplicity.
        ] );
    }

    /**
     * Define table columns.
     */
    public function get_columns() {
        return [
            'cb'            => '<input type="checkbox" />', // Checkbox for bulk actions.
            'name'          => __( 'Name', 'bsm-woocommerce' ),
            'sku'           => __( 'SKU', 'bsm-woocommerce' ),
            'stock_qty'     => __( 'Stock Quantity', 'bsm-woocommerce' ),
            'stock_status'  => __( 'Stock Status', 'bsm-woocommerce' ),
            'actions'       => __( 'Actions', 'bsm-woocommerce' ),
        ];
    }

    /**
     * Set sortable columns.
     */
    public function get_sortable_columns() {
        return [
            'name'       => [ 'name', true ],
            'sku'        => [ 'sku', false ],
            'stock_qty'  => [ 'stock_qty', false ],
        ];
    }

    /**
     * Render the table headers with sortable options.
     */
    public function print_column_headers( $with_id = true ) {
        parent::print_column_headers( $with_id );
    }

    /**
     * Define bulk actions.
     */
    public function get_bulk_actions() {
        return [
            'mark_in_stock'        => __( 'Mark “In Stock”', 'bsm-woocommerce' ),
            'mark_out_of_stock'    => __( 'Mark “Out of Stock”', 'bsm-woocommerce' ),
            'allow_backorders'     => __( 'Allow Backorders', 'bsm-woocommerce' ),
            'notify_backorders'    => __( 'Allow Backorders, Notify Customer', 'bsm-woocommerce' ),
            'disallow_backorders'  => __( 'Do Not Allow Backorders', 'bsm-woocommerce' ),
        ];
    }

    /**
     * Process bulk actions.
     */
    public function process_bulk_action() {
        $action     = $this->current_action();
        $product_ids = array_map( 'intval', $_POST['product'] ?? [] );

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
                case 'allow_backorders':
                    $product->set_backorders( 'yes' );
                    break;
                case 'notify_backorders':
                    $product->set_backorders( 'notify' );
                    break;
                case 'disallow_backorders':
                    $product->set_backorders( 'no' );
                    break;
            }
            $product->save();
        }
    }

    /**
     * Prepare items for display.
     */
    public function prepare_items() {
        $args = [
            'limit'   => -1, // Fetch all products.
            'orderby' => ! empty( $_GET['orderby'] ) ? sanitize_text_field( $_GET['orderby'] ) : 'name',
            'order'   => ! empty( $_GET['order'] ) ? sanitize_text_field( $_GET['order'] ) : 'asc',
        ];

        // Filter by stock status if selected.
        if ( isset( $_GET['stock_status'] ) && in_array( $_GET['stock_status'], [ 'instock', 'outofstock' ] ) ) {
            $args['stock_status'] = sanitize_text_field( $_GET['stock_status'] );
        }

        $products = wc_get_products( $args );

        $data = [];
        foreach ( $products as $product ) {
            $data[] = [
                'ID'          => $product->get_id(),
                'name'        => $product->get_name(),
                'sku'         => $product->get_sku() ?: __( 'N/A', 'bsm-woocommerce' ),
                'stock_qty'   => $product->get_stock_quantity() ?? __( 'N/A', 'bsm-woocommerce' ),
                'stock_status'=> $product->get_stock_status() === 'instock' ? __( 'In Stock', 'bsm-woocommerce' ) : __( 'Out of Stock', 'bsm-woocommerce' ),
            ];
        }

        // Debug: Output the prepared data.
        error_log( 'Prepared items: ' . print_r( $data, true ) );

        $per_page     = 10;
        $current_page = $this->get_pagenum();
        $total_items  = count( $data );

        // Slice data for pagination.
        $this->items = array_slice( $data, ( $current_page - 1 ) * $per_page, $per_page );

        // Debug: Output the paginated items.
        error_log( 'Paginated items: ' . print_r( $this->items, true ) );

        $this->set_pagination_args( [
            'total_items' => $total_items,
            'per_page'    => $per_page,
            'total_pages' => ceil( $total_items / $per_page ),
        ] );
    }

    /**
     * Render default columns.
     */
    public function column_default( $item, $column_name ) {
        switch ( $column_name ) {
            case 'name':
            case 'sku':
            case 'stock_status':
                return esc_html( $item[ $column_name ] );
            case 'stock_qty':
                return sprintf(
                    '<input type="number" class="bsm-stock-input" data-product-id="%d" value="%d" min="0" />',
                    esc_attr( $item['ID'] ),
                    esc_attr( $item['stock_qty'] )
                );
            default:
                return ''; // Return empty string for undefined columns.
        }
    }

    /**
     * Render the checkbox column for bulk actions.
     */
    public function column_cb( $item ) {
        return sprintf( '<input type="checkbox" name="product[]" value="%d" />', esc_attr( $item['ID'] ) );
    }

    /**
     * Render the actions column.
     */
    public function column_actions( $item ) {
        return sprintf(
            '<a href="%s" class="button">%s</a>',
            esc_url( admin_url( 'post.php?post=' . $item['ID'] . '&action=edit' ) ),
            __( 'Edit', 'bsm-woocommerce' )
        );
    }

    /**
     * Display a message if no items are found.
     */
    public function no_items() {
        echo __( 'No products found.', 'bsm-woocommerce' );
    }

    /**
     * Render rows.
     */
    public function display_rows() {
        foreach ( $this->items as $item ) {
            echo '<tr>';
            echo '<td>' . $this->column_cb( $item ) . '</td>';
            echo '<td>' . esc_html( $item['name'] ) . '</td>';
            echo '<td>' . esc_html( $item['sku'] ) . '</td>';
            echo '<td>' . $this->column_default( $item, 'stock_qty' ) . '</td>';
            echo '<td>' . esc_html( $item['stock_status'] ) . '</td>';
            echo '<td>' . $this->column_actions( $item ) . '</td>';
            echo '</tr>';
        }
    }

    /**
     * Render filters above the table.
     */
    public function extra_tablenav( $which ) {
        if ( 'top' === $which ) {
            echo '<div class="alignleft actions">';
            echo '<select name="stock_status">';
            echo '<option value="">' . __( 'All Stock Statuses', 'bsm-woocommerce' ) . '</option>';
            echo '<option value="instock"' . selected( $_GET['stock_status'] ?? '', 'instock', false ) . '>' . __( 'In Stock', 'bsm-woocommerce' ) . '</option>';
            echo '<option value="outofstock"' . selected( $_GET['stock_status'] ?? '', 'outofstock', false ) . '>' . __( 'Out of Stock', 'bsm-woocommerce' ) . '</option>';
            echo '</select>';
            submit_button( __( 'Filter', 'bsm-woocommerce' ), 'button', '', false );
            echo '</div>';
        }
    }
}
