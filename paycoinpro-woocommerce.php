<?php
/**
 * Plugin Name: PayCoinPro for WooCommerce
 * Plugin URI: https://paycoinpro.com
 * Description: Accept cryptocurrency payments via PayCoinPro payment gateway
 * Version: 1.0.0
 * Author: PayCoinPro
 * Author URI: https://paycoinpro.com
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: paycoinpro-woocommerce
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * WC requires at least: 5.0
 * WC tested up to: 8.5
 */

if (!defined('ABSPATH')) {
    exit;
}

define('PAYCOINPRO_VERSION', '1.0.0');
define('PAYCOINPRO_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('PAYCOINPRO_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Check if WooCommerce is active
 */
function paycoinpro_check_woocommerce() {
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', 'paycoinpro_woocommerce_missing_notice');
        return false;
    }
    return true;
}

/**
 * WooCommerce missing notice
 */
function paycoinpro_woocommerce_missing_notice() {
    echo '<div class="error"><p><strong>PayCoinPro for WooCommerce</strong> requires WooCommerce to be installed and active.</p></div>';
}

/**
 * Initialize the gateway
 */
function paycoinpro_init_gateway() {
    if (!paycoinpro_check_woocommerce()) {
        return;
    }

    require_once PAYCOINPRO_PLUGIN_DIR . 'includes/class-paycoinpro-gateway.php';

    add_filter('woocommerce_payment_gateways', 'paycoinpro_add_gateway');
}
add_action('plugins_loaded', 'paycoinpro_init_gateway');

/**
 * Add the gateway to WooCommerce
 */
function paycoinpro_add_gateway($gateways) {
    $gateways[] = 'WC_Gateway_PayCoinPro';
    return $gateways;
}

/**
 * Add settings link to plugins page
 */
function paycoinpro_plugin_links($links) {
    $plugin_links = array(
        '<a href="' . admin_url('admin.php?page=wc-settings&tab=checkout&section=paycoinpro') . '">Settings</a>',
    );
    return array_merge($plugin_links, $links);
}
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'paycoinpro_plugin_links');

/**
 * Declare HPOS compatibility
 */
add_action('before_woocommerce_init', function() {
    if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
    }
});

/**
 * Register webhook endpoint
 */
function paycoinpro_register_webhook_endpoint() {
    register_rest_route('paycoinpro/v1', '/webhook', array(
        'methods' => 'POST',
        'callback' => 'paycoinpro_handle_webhook',
        'permission_callback' => '__return_true',
    ));
}
add_action('rest_api_init', 'paycoinpro_register_webhook_endpoint');

/**
 * Handle webhook from PayCoinPro
 */
function paycoinpro_handle_webhook(WP_REST_Request $request) {
    $gateway = new WC_Gateway_PayCoinPro();
    return $gateway->handle_webhook($request);
}
