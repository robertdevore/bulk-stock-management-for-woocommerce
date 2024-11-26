<?php
/**
 * Class BSM_Settings_Page
 *
 * Handles the settings page for the Bulk Stock Management plugin.
 *
 * @package BSM_WooCommerce
 */

class BSM_Settings_Page {

    /**
     * BSM_Settings_Page constructor.
     * 
     * @since  1.0.0
     */
    public function __construct() {
        add_action( 'admin_menu', [ $this, 'register_settings_page' ] );
        add_action( 'admin_init', [ $this, 'register_settings' ] );
        add_action( 'admin_init', [ $this, 'enqueue_assets' ] );
    }

    /**
     * Registers the settings page under WooCommerce menu.
     *
     * @since  1.0.0
     * @return void
     */
    public function register_settings_page() {
        add_submenu_page(
            'woocommerce',
            esc_html__( 'Stock Settings', 'bsm-woocommerce' ),
            esc_html__( 'Stock Settings', 'bsm-woocommerce' ),
            'manage_woocommerce',
            'bsm-settings',
            [ $this, 'render_settings_page' ]
        );
    }

    /**
     * Registers plugin settings and fields.
     *
     * @since  1.0.0
     * @return void
     */
    public function register_settings() {
        register_setting( 'bsm_settings_group', 'bsm_enable_reporting', [
            'type'              => 'string',
            'sanitize_callback' => [ $this, 'sanitize_checkbox' ],
            'default'           => 'yes',
        ] );

        register_setting( 'bsm_settings_group', 'bsm_default_filters', [
            'type'              => 'string',
            'default'           => '',
            'sanitize_callback' => function( $value ) {
                if ( json_decode( $value ) === null && json_last_error() !== JSON_ERROR_NONE ) {
                    add_settings_error(
                        'bsm_default_filters',
                        'invalid_json',
                        esc_html__( 'The default filters must be valid JSON.', 'bsm-woocommerce' )
                    );
                    return '';
                }
                return wp_kses_post( $value );
            },
        ] );

        add_settings_section(
            'bsm_general_settings',
            esc_html__( 'General Settings', 'bsm-woocommerce' ),
            function () {
                echo '<p>' . esc_html__( 'Configure the default behavior of the Bulk Stock Management plugin.', 'bsm-woocommerce' ) . '</p>';
            },
            'bsm-settings'
        );

        add_settings_field(
            'bsm_enable_reporting',
            __( 'Enable Stock Reporting', 'bsm-woocommerce' ),
            function () {
                $value = get_option( 'bsm_enable_reporting', 'yes' );
                echo '<input type="checkbox" name="bsm_enable_reporting" value="yes" ' . checked( $value, 'yes', false ) . ' />';
            },
            'bsm-settings',
            'bsm_general_settings'
        );

        add_settings_field(
            'bsm_report_columns',
            esc_html__( 'Report Columns', 'bsm-woocommerce' ),
            function () {
                $options = get_option( 'bsm_report_columns', [
                    'product_name' => 'yes',
                    'sku'          => 'yes',
                    'stock_qty'    => 'yes',
                    'stock_status' => 'yes',
                    'backorders'   => 'yes',
                ] );
        
                $columns = [
                    'product_name' => esc_html__( 'Product Name', 'bsm-woocommerce' ),
                    'sku'          => esc_html__( 'SKU', 'bsm-woocommerce' ),
                    'stock_qty'    => esc_html__( 'Stock Quantity', 'bsm-woocommerce' ),
                    'stock_status' => esc_html__( 'Stock Status', 'bsm-woocommerce' ),
                    'backorders'   => esc_html__( 'Backorders', 'bsm-woocommerce' ),
                ];
        
                foreach ( $columns as $key => $label ) {
                    echo '<label style="display:block; margin-bottom: 8px;">';
                    echo '<input type="checkbox" name="bsm_report_columns[' . esc_attr( $key ) . ']" value="yes" ' . checked( $options[ $key ] ?? '', 'yes', false ) . ' />';
                    echo ' ' . esc_html( $label );
                    echo '</label>';
                }
            },
            'bsm-settings',
            'bsm_general_settings'
        );        

        add_settings_field(
            'bsm_default_filters',
            esc_html__( 'Default Product Filters', 'bsm-woocommerce' ),
            function () {
                $value = get_option( 'bsm_default_filters', '' );
                echo '<textarea name="bsm_default_filters" rows="5" style="width: 100%;">' . esc_textarea( $value ) . '</textarea>';
                echo '<p class="description">' . esc_html__( 'Enter default filters as JSON (e.g., {"stock_status":"instock"}).', 'bsm-woocommerce' ) . '</p>';
            },
            'bsm-settings',
            'bsm_general_settings'
        );
    }

    /**
     * Renders the settings page HTML.
     *
     * @since  1.0.0
     * @return void
     */
    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1>
                <?php echo esc_html__( 'Bulk Stock Management Settings', 'bsm-woocommerce' ); ?>
                <a id="bsm-woocommerce-support-btn" href="https://robertdevore.com/contact/" target="_blank" class="button button-alt" style="margin-left: 10px;">
                    <span class="dashicons dashicons-format-chat" style="vertical-align: middle;"></span>
                    <?php echo esc_html__( 'Support', 'bluesky-feed' ); ?>
                </a>
                <a id="bsm-woocommerce-docs-btn" href="https://robertdevore.com/articles/bulk-stock-management-for-woocommerce/" target="_blank" class="button button-alt" style="margin-left: 5px;">
                    <span class="dashicons dashicons-media-document" style="vertical-align: middle;"></span>
                    <?php echo esc_html__( 'Documentation', 'bluesky-feed' ); ?>
                </a>
            </h1>
            <hr />
            <form method="post" action="options.php">
                <?php
                settings_fields( 'bsm_settings_group' );
                do_settings_sections( 'bsm-settings' );
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Enqueues admin assets for the settings page.
     *
     * @since  1.0.0
     * @return void
     */
    public function enqueue_assets() {
        $screen = get_current_screen();

        if ( $screen && $screen->id === 'woocommerce_page_bsm-settings' ) {
            wp_enqueue_style( 'bsm-admin', BSM_PLUGIN_URL . 'assets/admin.css', [], '1.0.0' );
            wp_enqueue_script( 'bsm-admin', BSM_PLUGIN_URL . 'assets/admin.js', [ 'jquery' ], '1.0.0', true );
        }
    }

    /**
     * Sanitizes checkbox input.
     *
     * @param string $value Checkbox value.
     * @return string Sanitized value.
     */
    public function sanitize_checkbox( $value ) {
        return $value === 'yes' ? 'yes' : '';
    }
}
