<?php

/**
  * The plugin bootstrap file
  *
  * @link              https://robertdevore.com
  * @since             1.0.0
  * @package           Bulk_Stock_Management_For_WooCommerce
  *
  * @wordpress-plugin
  *
  * Plugin Name: Bulk Stock Management for WooCommerce®
  * Description: Manage stock levels and generate stock reports for WooCommerce® products.
  * Plugin URI:  https://github.com/robertdevore/bulk-stock-management-for-woocommerce/
  * Version:     1.0.0
  * Author:      Robert DeVore
  * Author URI:  https://robertdevore.com/
  * License:     GPL-2.0+
  * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
  * Text Domain: bsm-woocommerce
  * Domain Path: /languages
  * Update URI:  https://github.com/robertdevore/bulk-stock-management-for-woocommerce/
  */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

// Add the Plugin Update Checker.
require 'includes/plugin-update-checker/plugin-update-checker.php';
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

$myUpdateChecker = PucFactory::buildUpdateChecker(
    'https://github.com/robertdevore/bulk-stock-management-for-woocommerce/',
    __FILE__,
    'bulk-stock-management-for-woocommerce'
);

// Set the branch that contains the stable release.
$myUpdateChecker->setBranch( 'main' );

// Define plugin constants.
define( 'BSM_PLUGIN_VERSION', '1.0.0' );
define( 'BSM_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'BSM_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Autoload necessary files.
require_once BSM_PLUGIN_PATH . 'includes/class-bsm-stock-management-page.php';
require_once BSM_PLUGIN_PATH . 'includes/class-bsm-stock-list-table.php';
require_once BSM_PLUGIN_PATH . 'includes/class-bsm-settings-page.php';
require_once BSM_PLUGIN_PATH . 'includes/class-bsm-stock-reports-page.php';

/**
 * Initializes the Bulk Stock Management plugin.
 *
 * This function checks if WooCommerce is active before initializing
 * the admin features of the Bulk Stock Management plugin. If WooCommerce
 * is not active, an admin notice is displayed to inform the user.
 *
 * @since  1.0.0
 * @return void
 */
function bsm_init_plugin() {
    // Check WooCommerce is active.
    if ( ! class_exists( 'WooCommerce' ) ) {
        add_action( 'admin_notices', function () {
            echo '<div class="notice notice-error"><p>' . esc_html__( 'Bulk Stock Management requires WooCommerce to be active.', 'bsm-woocommerce' ) . '</p></div>';
        } );
        return;
    }

    // Initialize admin features.
    if ( is_admin() ) {
        new BSM_Stock_Management_Page();
        new BSM_Settings_Page();

        if ( 'yes' === get_option( 'bsm_enable_reporting', 'yes' ) ) {
            new BSM_Stock_Reports_Page();
        }
    }
}
add_action( 'plugins_loaded', 'bsm_init_plugin' );

/**
 * Helper function to handle WordPress.com environment checks.
 *
 * @param string $plugin_slug     The plugin slug.
 * @param string $learn_more_link The link to more information.
 * 
 * @since  1.1.0
 * @return bool
 */
function wp_com_plugin_check( $plugin_slug, $learn_more_link ) {
    // Check if the site is hosted on WordPress.com.
    if ( defined( 'IS_WPCOM' ) && IS_WPCOM ) {
        // Ensure the deactivate_plugins function is available.
        if ( ! function_exists( 'deactivate_plugins' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        // Deactivate the plugin if in the admin area.
        if ( is_admin() ) {
            deactivate_plugins( $plugin_slug );

            // Add a deactivation notice for later display.
            add_option( 'wpcom_deactivation_notice', $learn_more_link );

            // Prevent further execution.
            return true;
        }
    }

    return false;
}

/**
 * Auto-deactivate the plugin if running in an unsupported environment.
 *
 * @since  1.1.0
 * @return void
 */
function wpcom_auto_deactivation() {
    if ( wp_com_plugin_check( plugin_basename( __FILE__ ), 'https://robertdevore.com/why-this-plugin-doesnt-support-wordpress-com-hosting/' ) ) {
        return; // Stop execution if deactivated.
    }
}
add_action( 'plugins_loaded', 'wpcom_auto_deactivation' );

/**
 * Display an admin notice if the plugin was deactivated due to hosting restrictions.
 *
 * @since  1.1.0
 * @return void
 */
function wpcom_admin_notice() {
    $notice_link = get_option( 'wpcom_deactivation_notice' );
    if ( $notice_link ) {
        ?>
        <div class="notice notice-error">
            <p>
                <?php
                echo wp_kses_post(
                    sprintf(
                        __( 'My Plugin has been deactivated because it cannot be used on WordPress.com-hosted websites. %s', 'bsm-woocommerce' ),
                        '<a href="' . esc_url( $notice_link ) . '" target="_blank" rel="noopener">' . __( 'Learn more', 'bsm-woocommerce' ) . '</a>'
                    )
                );
                ?>
            </p>
        </div>
        <?php
        delete_option( 'wpcom_deactivation_notice' );
    }
}
add_action( 'admin_notices', 'wpcom_admin_notice' );

/**
 * Prevent plugin activation on WordPress.com-hosted sites.
 *
 * @since  1.1.0
 * @return void
 */
function wpcom_activation_check() {
    if ( wp_com_plugin_check( plugin_basename( __FILE__ ), 'https://robertdevore.com/why-this-plugin-doesnt-support-wordpress-com-hosting/' ) ) {
        // Display an error message and stop activation.
        wp_die(
            wp_kses_post(
                sprintf(
                    '<h1>%s</h1><p>%s</p><p><a href="%s" target="_blank" rel="noopener">%s</a></p>',
                    __( 'Plugin Activation Blocked', 'bsm-woocommerce' ),
                    __( 'This plugin cannot be activated on WordPress.com-hosted websites. It is restricted due to concerns about WordPress.com policies impacting the community.', 'bsm-woocommerce' ),
                    esc_url( 'https://robertdevore.com/why-this-plugin-doesnt-support-wordpress-com-hosting/' ),
                    __( 'Learn more', 'bsm-woocommerce' )
                )
            ),
            esc_html__( 'Plugin Activation Blocked', 'bsm-woocommerce' ),
            [ 'back_link' => true ]
        );
    }
}
register_activation_hook( __FILE__, 'wpcom_activation_check' );

/**
 * Add a deactivation flag when the plugin is deactivated.
 *
 * @since  1.1.0
 * @return void
 */
function wpcom_deactivation_flag() {
    add_option( 'wpcom_deactivation_notice', 'https://robertdevore.com/why-this-plugin-doesnt-support-wordpress-com-hosting/' );
}
register_deactivation_hook( __FILE__, 'wpcom_deactivation_flag' );
