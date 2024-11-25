<?php

class BSM_Settings_Page {

    public function __construct() {
        add_action( 'admin_menu', [ $this, 'register_settings_page' ] );
        add_action( 'admin_init', [ $this, 'register_settings' ] );
        add_action( 'admin_init', [ $this, 'enqueue_assets' ] );
    }

    public function register_settings_page() {
        add_submenu_page(
            'woocommerce',
            __( 'Stock Settings', 'bsm-woocommerce' ),
            __( 'Stock Settings', 'bsm-woocommerce' ),
            'manage_woocommerce',
            'bsm-settings',
            [ $this, 'render_settings_page' ]
        );
    }

    public function register_settings() {
        register_setting( 'bsm_settings_group', 'bsm_enable_reporting' );
        register_setting( 'bsm_settings_group', 'bsm_default_filters' );

        add_settings_section(
            'bsm_general_settings',
            __( 'General Settings', 'bsm-woocommerce' ),
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
            'bsm_default_filters',
            __( 'Default Product Filters', 'bsm-woocommerce' ),
            function () {
                $value = get_option( 'bsm_default_filters', '' );
                echo '<textarea name="bsm_default_filters" rows="5" style="width: 100%;">' . esc_textarea( $value ) . '</textarea>';
                echo '<p class="description">' . esc_html__( 'Enter default filters as JSON (e.g., {"stock_status":"instock"}).', 'bsm-woocommerce' ) . '</p>';
            },
            'bsm-settings',
            'bsm_general_settings'
        );
    }

    public function render_settings_page() {
        echo '<div class="wrap">';
        echo '<h1>' . esc_html__( 'Bulk Stock Management Settings', 'bsm-woocommerce' );
        echo '<a id="bsm-woocommerce-support-btn" href="https://robertdevore.com/contact/" target="_blank" class="button button-alt" style="margin-left: 10px;">
                <span class="dashicons dashicons-format-chat" style="vertical-align: middle;"></span> ' . esc_html__( 'Support', 'bluesky-feed' ) . '
            </a>
            <a id="bsm-woocommerce-docs-btn" href="https://robertdevore.com/articles/bulk-stock-management-for-woocommerce/" target="_blank" class="button button-alt" style="margin-left: 5px;">
                <span class="dashicons dashicons-media-document" style="vertical-align: middle;"></span> ' . esc_html__( 'Documentation', 'bluesky-feed' ) . '
            </a>';
        echo '</h1>';
        echo '<hr />';
        echo '<form method="post" action="options.php">';
        settings_fields( 'bsm_settings_group' );
        do_settings_sections( 'bsm-settings' );
        submit_button();
        echo '</form>';
        echo '</div>';
    }

    public function enqueue_assets() {
        wp_enqueue_style( 'bsm-admin', BSM_PLUGIN_URL . 'assets/admin.css', [], '1.0.0' );
        wp_enqueue_script( 'bsm-admin', BSM_PLUGIN_URL . 'assets/admin.js', [ 'jquery' ], '1.0.0', true );
    }
    
}
