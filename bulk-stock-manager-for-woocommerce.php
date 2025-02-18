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
  * Version:     1.0.1
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
require 'vendor/plugin-update-checker/plugin-update-checker.php';
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

$myUpdateChecker = PucFactory::buildUpdateChecker(
    'https://github.com/robertdevore/bulk-stock-management-for-woocommerce/',
    __FILE__,
    'bulk-stock-management-for-woocommerce'
);

// Set the branch that contains the stable release.
$myUpdateChecker->setBranch( 'main' );

// Check if Composer's autoloader is already registered globally.
if ( ! class_exists( 'RobertDevore\WPComCheck\WPComPluginHandler' ) ) {
    require_once __DIR__ . '/vendor/autoload.php';
}

use RobertDevore\WPComCheck\WPComPluginHandler;

new WPComPluginHandler( plugin_basename( __FILE__ ), 'https://robertdevore.com/why-this-plugin-doesnt-support-wordpress-com-hosting/' );

/**
 * Load plugin text domain for translations
 * 
 * @since 1.0.1
 * @return void
 */
function bsm_load_textdomain() {
    load_plugin_textdomain( 
        'bsm-woocommerce', 
        false, 
        dirname( plugin_basename( __FILE__ ) ) . '/languages/'
    );
}
add_action( 'plugins_loaded', 'bsm_load_textdomain' );

// Define plugin constants.
define( 'BSM_PLUGIN_VERSION', '1.0.1' );
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
