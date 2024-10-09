<?php
/*
 * Plugin Name: WooCommerce LunaPay
 * Plugin URI: http://www.skillstechinc.com/
 * Description: Plugin used for the integration for custom payment gateway with wooCommerce allowing users to purchase products that can be easily posted to your custom payment gateway.
 * Version: 1.1
 * Author: Skills Tech Inc
 * Author URI: http://www.skillstechinc.com/
 * License: GPLv2 or later
 */

//Do not load plugin directly
if( ! defined( 'ABSPATH' ) ) {
    die( '-1' ); 
}

//Defining constants
if( ! defined( 'WC_LPAY_PATH' ) ) {
    define( 'WC_LPAY_PATH',  plugin_dir_path( __FILE__ ) );
}

if( ! defined( 'WC_LPAY_URL' ) ) {
    define( 'WC_LPAY_URL', plugin_dir_url( __FILE__ ) );
}

if( ! defined( 'WC_LPAY_BASE' ) ) {
    define( 'WC_LPAY_BASE', plugin_basename( __FILE__ ) );
}

function wc_smm_gateway_missing_wc_notice() {
    echo '<div class="error"><p><strong>' . sprintf(esc_html__('WC LunaPay requires WooCommerce to be installed and active. You can download %s here.', 'cryptapi'), '<a href="https://woocommerce.com/" target="_blank">WooCommerce</a>') . '</strong></p></div>';
}

function wc_smm_gateway_include_gateway($methods) {
    $methods[] = 'WC_LunaPay_Gateway';
    return $methods;
}

function wc_smm_loader() {
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', 'wc_lunapay_gateway_missing_wc_notice');
        return;
    }

    require_once WC_SMM_PATH . 'inc/controller/WC_LunaPay_Gateway.php';

    $WC_LunaPay_Gateway = new WC_LunaPay_Gateway();
}

add_action('plugins_loaded', 'wc_lunapay_loader');
add_filter('woocommerce_payment_gateways', 'wc_lunapay_gateway_include_gateway');

require_once  WC_LPAY_PATH . 'inc/Activate.php';

/*
 * Run code during plugin activation
 */
 function WC_activatePlugin() {
    ActivateWCLunaPay::activate();
 }
 
 register_activation_hook( __FILE__, 'activatePlugin' );

/*
 * Run code during plugin deactivation
 */
function WC_deactivatePlugin() {
    DeactivateWCLunaPay::deactivate();
}
register_deactivation_hook(__FILE__, 'WC_deactivatePlugin');
