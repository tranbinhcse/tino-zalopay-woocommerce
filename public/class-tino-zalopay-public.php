<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://tinohost.com
 * @since      1.0.0
 *
 * @package    Tino_Zalopay
 * @subpackage Tino_Zalopay/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Tino_Zalopay
 * @subpackage Tino_Zalopay/public
 * @author     Trần Bình <binh@tino.org>
 */
class Tino_Zalopay_Public {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;
		add_action( 'plugins_loaded', array($this,'init_gateway_class') );
		add_filter( 'woocommerce_payment_gateways', array($this,'add_gateway_class') );
	}

	public function init_gateway_class() {
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/gateways/zalopay-gateway.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/gateways/zalopay-atm-method.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/gateways/zalopay-credits-method.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/gateways/zalopay-qr-method.php';
	}

	public function add_gateway_class($methods ){
		$methods[] = 'ZaloPayQr';
		$methods[] = 'ZaloPayAtm';
		$methods[] = 'ZaloPayCredits';
		// $methods[] = 'MocaQRScanGetWay';
		// $methods[] = 'AirPayQRScanGetWay';
	    return $methods;
	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Tino_Zalopay_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Tino_Zalopay_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/tino-zalopay-public.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		wp_enqueue_script( 'TinoZaloPayQrCode', plugin_dir_url( __FILE__ ) . 'js/qrcode.min.js', array( 'jquery' ), $this->version, false );
 		wp_enqueue_script( 'TinoZaloPayiCheck', plugin_dir_url( __FILE__ ) . 'js/icheck.min.js', array( 'jquery' ), $this->version, false );
		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/tino-zalopay-public.js', array( 'jquery' ), 123, false );


	}

}
