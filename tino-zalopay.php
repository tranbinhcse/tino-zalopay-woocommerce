<?php

/**
 *
 * Plugin Name:       Cổng thanh toán ZaloPay Business
 * Plugin URI:        https://tinohost.com
 * Description:       Cổng thanh toán ZaloPay Business
 * Version:           1.0.0
 * Author:            TinoHost
 * Author URI:        https://tinohost.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       tino-zalopay
 * Domain Path:       /languages
 * @link              http://tinohost.com
 * @since             1.0.0
 * @package           Tino_Zalopay
 *
 * 
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

define( 'TINO_ZALOPAY_VERSION', '1.0.2' );
define( 'TINO_ZALOPAY_PLUGIN_URL', esc_url( plugins_url( '', __FILE__ ) ) );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-tino-zalopay-activator.php
 */
function activate_tino_zalopay() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-tino-zalopay-activator.php';
	Tino_Zalopay_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-tino-zalopay-deactivator.php
 */
function deactivate_tino_zalopay() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-tino-zalopay-deactivator.php';
	Tino_Zalopay_Deactivator::deactivate();
}



/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_tino_zalopay() {

	$plugin = new Tino_Zalopay();
	$plugin->run();

}


if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {

	register_activation_hook( __FILE__, 'activate_tino_zalopay' );
	register_deactivation_hook( __FILE__, 'deactivate_tino_zalopay' );
	require plugin_dir_path( __FILE__ ) . 'includes/class-tino-zalopay.php';

	run_tino_zalopay();
}

function tino_zalopay_installed_notice() {
	if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ){
	    $class = 'notice notice-error';
		$message = __( 'Plugin Thanh Toán Quét Mã QR cần Woocommerce kích hoạt trước khi sử dụng. Vui lòng kiểm tra Woocommerce', 'qr_auto' );
		printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
    }
}
add_action( 'admin_notices', 'tino_zalopay_installed_notice' );
