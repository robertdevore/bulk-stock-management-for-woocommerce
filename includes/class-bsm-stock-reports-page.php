<?php
/**
 * Class BSM_Stock_Reports_Page
 *
 * Handles the Stock Reports page for the Bulk Stock Management plugin.
 *
 * @package BSM_WooCommerce
 */

class BSM_Stock_Reports_Page {

    /**
     * Constructor.
     *
     * Registers actions and hooks for the Stock Reports page.
     *
     * @since 1.0.0
     */
    public function __construct() {
        add_action( 'admin_menu', [ $this, 'register_reports_page' ] );
        add_action( 'admin_init', [ $this, 'handle_csv_download' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
    }

    /**
     * Registers the Stock Reports page under WooCommerce menu.
     *
     * @since 1.0.0
     * @return void
     */
    public function register_reports_page() {
        add_submenu_page(
            'woocommerce',
            esc_html__( 'Stock Reports', 'bsm-woocommerce' ),
            esc_html__( 'Stock Reports', 'bsm-woocommerce' ),
            'manage_woocommerce',
            'bsm-stock-reports',
            [ $this, 'render_reports_page' ]
        );
    }

    /**
     * Enqueues scripts and styles for the Bulk Stock Management reports page.
     *
     * This method checks if the current admin screen is the stock reports page
     * and, if so, enqueues the necessary CSS and JavaScript files for the page.
     * It also localizes a JavaScript object for AJAX requests with the `bsm_reports_nonce`.
     *
     * @since  1.0.0
     * @return void
     */
    public function enqueue_assets() {
        $screen = get_current_screen();

        if ( $screen && $screen->id === 'woocommerce_page_bsm-stock-reports' ) {
            wp_enqueue_style( 'bsm-admin', BSM_PLUGIN_URL . 'assets/admin.css', [], BSM_PLUGIN_VERSION );
            wp_enqueue_script( 'chart-js', BSM_PLUGIN_URL . 'assets/charts.js', [], BSM_PLUGIN_VERSION, true );
            wp_enqueue_script( 'bsm-reports', BSM_PLUGIN_URL . 'assets/reports.js', [ 'jquery', 'chart-js' ], BSM_PLUGIN_VERSION, true );

            wp_localize_script( 'bsm-reports', 'bsm_report_data', [
                'ajaxurl' => admin_url( 'admin-ajax.php' ),
                'nonce'   => wp_create_nonce( 'bsm_reports_nonce' ),
            ] );
        }
    }

    /**
     * Renders the Stock Reports page HTML.
     *
     * @since 1.0.0
     * @return void
     */
    public function render_reports_page() {
        ?>
        <div class="wrap">
            <h1>
                <?php esc_html_e( 'Stock Reports', 'bsm-woocommerce' ); ?>
                <a id="bsm-woocommerce-support-btn" href="https://robertdevore.com/contact/" target="_blank" class="button button-alt" style="margin-left: 10px;">
                    <span class="dashicons dashicons-format-chat" style="vertical-align: middle;"></span>
                    <?php esc_html_e( 'Support', 'bsm-woocommerce' ); ?>
                </a>
                <a id="bsm-woocommerce-docs-btn" href="https://robertdevore.com/articles/bulk-stock-management-for-woocommerce/" target="_blank" class="button button-alt" style="margin-left: 5px;">
                    <span class="dashicons dashicons-media-document" style="vertical-align: middle;"></span>
                    <?php esc_html_e( 'Documentation', 'bsm-woocommerce' ); ?>
                </a>
            </h1>

            <hr />

            <div class="bsm-flex-container">
                <!-- Left Column -->
                <div class="bsm-left-column">
                    <div class="bsm-summary">
                        <div class="bsm-summary-item">
                            <h2><?php esc_html_e( 'Total Products', 'bsm-woocommerce' ); ?></h2>
                            <p id="bsm-total-products">0</p>
                        </div>
                        <div class="bsm-summary-item">
                            <h2><?php esc_html_e( 'In Stock', 'bsm-woocommerce' ); ?></h2>
                            <p id="bsm-in-stock">0</p>
                        </div>
                        <div class="bsm-summary-item">
                            <h2><?php esc_html_e( 'Out of Stock', 'bsm-woocommerce' ); ?></h2>
                            <p id="bsm-out-of-stock">0</p>
                        </div>
                        <div class="bsm-download">
                            <a href="<?php echo esc_url( admin_url( 'admin.php?action=bsm_download_stock_report' ) ); ?>" class="button button-primary">
                                <?php esc_html_e( 'Download CSV', 'bsm-woocommerce' ); ?>
                            </a>
                        </div>
                    </div>

                    <div class="bsm-out-of-stock-box">
                        <h2><?php esc_html_e( 'Out of Stock Products', 'bsm-woocommerce' ); ?></h2>
                        <table class="widefat fixed" id="bsm-out-of-stock-table">
                            <thead>
                                <tr>
                                    <th><?php esc_html_e( 'Product Name', 'bsm-woocommerce' ); ?></th>
                                    <th><?php esc_html_e( 'SKU', 'bsm-woocommerce' ); ?></th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>

                <!-- Right Column -->
                <div class="bsm-right-column">
                    <div class="bsm-charts">
                        <canvas id="bsm-stock-status-chart" width="400" height="200"></canvas>
                    </div>
                </div>
            </div>

        </div>
        <?php
    }

    /**
     * Handles the CSV download for stock reports.
     *
     * @since  1.0.0
     * @return void Outputs the CSV content and exits.
     */
    public function handle_csv_download() {
        if ( isset( $_GET['action'] ) && 'bsm_download_stock_report' === $_GET['action'] ) {
            // Verify permissions.
            if ( ! current_user_can( 'manage_woocommerce' ) ) {
                wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'bsm-woocommerce' ) );
            }

            // Get domain and datetime for the file name.
            $domain   = parse_url( get_site_url(), PHP_URL_HOST );
            $datetime = date( 'Y-m-d_H-i-s' );

            // Set CSV headers with dynamic file name.
            header( 'Content-Type: text/csv; charset=utf-8' );
            header( 'Content-Disposition: attachment; filename=' . $domain . '-stock-inventory-report-' . $datetime . '.csv' );

            // Output the CSV.
            $output = fopen( 'php://output', 'w' );

            // Get user-selected columns.
            $options = get_option( 'bsm_report_columns', [
                'product_id'   => 'yes',
                'product_name' => 'yes',
                'sku'          => 'yes',
                'stock_qty'    => 'yes',
                'stock_status' => 'yes',
                'backorders'   => 'yes',
            ] );

            // Prepare the CSV headers based on selected columns.
            $headers = [];
            if ( 'yes' === $options['product_id'] ) {
                $headers[] = esc_html__( 'Product ID', 'bsm-woocommerce' );
            }
            if ( 'yes' === $options['product_name'] ) {
                $headers[] = esc_html__( 'Product Name', 'bsm-woocommerce' );
            }
            if ( 'yes' === $options['sku'] ) {
                $headers[] = esc_html__( 'SKU', 'bsm-woocommerce' );
            }
            if ( 'yes' === $options['stock_qty'] ) {
                $headers[] = esc_html__( 'Stock Quantity', 'bsm-woocommerce' );
            }
            if ( 'yes' === $options['stock_status'] ) {
                $headers[] = esc_html__( 'Stock Status', 'bsm-woocommerce' );
            }
            if ( 'yes' === $options['backorders'] ) {
                $headers[] = esc_html__( 'Backorders', 'bsm-woocommerce' );
            }

            // Write headers to the CSV file.
            fputcsv( $output, $headers );

            // Fetch product data.
            $args     = [
                'post_type'      => 'product',
                'post_status'    => 'publish',
                'posts_per_page' => -1,
            ];
            $products = get_posts( $args );

            foreach ( $products as $product_post ) {
                $product = wc_get_product( $product_post->ID );

                // Prepare the CSV row based on selected columns.
                $row = [];
                if ( 'yes' === $options['product_id'] ) {
                    $row[] = $product->get_id();
                }
                if ( 'yes' === $options['product_name'] ) {
                    $row[] = $product->get_name();
                }
                if ( 'yes' === $options['sku'] ) {
                    $row[] = $product->get_sku();
                }
                if ( 'yes' === $options['stock_qty'] ) {
                    $row[] = $product->get_stock_quantity();
                }
                if ( 'yes' === $options['stock_status'] ) {
                    $row[] = $product->get_stock_status();
                }
                if ( 'yes' === $options['backorders'] ) {
                    $row[] = $product->get_backorders();
                }

                fputcsv( $output, $row );
            }

            fclose( $output );
            exit;
        }
    }
}

add_action( 'wp_ajax_bsm_get_stock_report', function () {
    // Verify nonce.
    check_ajax_referer( 'bsm_reports_nonce', 'nonce' );

    $products = wc_get_products( [
        'status'  => [ 'publish', 'private' ],
        'limit'   => -1,
        'orderby' => 'name',
        'order'   => 'ASC',
    ] );

    $in_stock     = 0;
    $out_of_stock = 0;
    $backorders   = 0;
    $product_data = [];

    foreach ( $products as $product ) {
        $stock_status     = $product->get_stock_status();
        $backorder_status = $product->get_backorders();

        if ( 'instock' === $stock_status ) {
            $in_stock++;
        } elseif ( 'outofstock' === $stock_status ) {
            $out_of_stock++;
        }

        if ( 'notify' === $backorder_status || 'yes' === $backorder_status ) {
            $backorders++;
        }

        $product_data[] = [
            'name'           => $product->get_name(),
            'sku'            => $product->get_sku(),
            'stock_quantity' => $product->get_stock_quantity(),
            'stock_status'   => ucfirst( $stock_status ),
        ];
    }

    wp_send_json_success( [
        'total_products' => count( $products ),
        'in_stock'       => $in_stock,
        'out_of_stock'   => $out_of_stock,
        'backorders'     => $backorders,
        'products'       => $product_data,
    ] );
} );
