<?php
/**
 * Plugin Name: BWK Woo Accounting
 * Description: Accounting suite for WooCommerce.
 * Version: 0.1.0
 * Author: BinawebKu
 * Text Domain: bwk-woo-accounting
 * Requires PHP: 7.4
 * Requires at least: 6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'BWK_ACC_VERSION', '0.1.0' );
define( 'BWK_ACC_PATH', plugin_dir_path( __FILE__ ) );
define( 'BWK_ACC_URL', plugin_dir_url( __FILE__ ) );
define( 'BWK_ACC_BASENAME', plugin_basename( __FILE__ ) );

require_once BWK_ACC_PATH . 'includes/Autoloader.php';
BWK\Accounting\Autoloader::register();

function bwk_acc_init_plugin() {
    new BWK\Accounting\Plugin();
}
add_action( 'plugins_loaded', 'bwk_acc_init_plugin' );

register_activation_hook( __FILE__, ['BWK\\Accounting\\Activator', 'activate'] );
register_deactivation_hook( __FILE__, ['BWK\\Accounting\\Activator', 'deactivate'] );
