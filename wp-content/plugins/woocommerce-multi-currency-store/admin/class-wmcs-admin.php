<?php
/**
 * Admin entry class
 *
 * @package   Woocommerce Multi Currency Store
 * @author    Code Ninjas 
 * @link      http://codeninjas.co
 * @copyright 2014 Code Ninjas
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
 
class WMCS_Admin {

	/**
	 * Instance of this class.
	 *
	 * @since    1.1.0
	 */
	protected static $instance = null;

	/**
	 * Initialize Admin
	 *
	 * @since     1.1.0
	 */
	public function __construct()
	{		
		// Includes
		include_once 'updates/class-wmcs-update.php';
		include_once 'class-wmcs-admin-settings.php';
		include_once 'class-wmcs-admin-product.php';
		
		// Actions/Filters
		//add_action( 'admin_print_styles', array( $this, 'print_admin_styles' ), 99 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
				
	}

	/**
	 * Return an instance of this class.
	 *
	 * @since     1.0.0
	 *
	 * @return    object    A single instance of this class.
	 */
	public static function get_instance()
	{

		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Register and enqueue admin-specific stylesheets.
	 *
	 * @since     1.0.0
	 */
	public function print_admin_styles()
	{		
		$screen = get_current_screen();
		
		switch( $screen->id ){
			
			case 'woocommerce_page_wc-settings';
				wp_enqueue_style( 'wmcs-settings-css', plugins_url( 'assets/css/settings.css', __FILE__ ) );
				break;
			case 'product';
				wp_enqueue_style( 'wmcs-product-css', plugins_url( 'assets/css/product.css', __FILE__ ) );
				break;
		}
	}

	/**
	 * Register and enqueue admin-specific JavaScript.
	 *
	 * @since     1.0.0
	 */
	public function enqueue_admin_scripts()
	{
		$screen = get_current_screen();
		switch( $screen->id ){
			case 'woocommerce_page_wc-settings';
				wp_enqueue_script( 'wmcs-settings-js', plugins_url( 'assets/js/settings.js', __FILE__ ), array( 'jquery' ) );
				wp_enqueue_style( 'wmcs-settings-css', plugins_url( 'assets/css/settings.css', __FILE__ ) );
				break;
			case 'product';
				wp_enqueue_style( 'wmcs-product-css', plugins_url( 'assets/css/product.css', __FILE__ ) );
				break;
		}

	}
	
}