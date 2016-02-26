<?php
/**
 * Common init class for both frontend and admin
 *
 * @package   Woocommerce Multi Currency Store
 * @author    Code Ninjas 
 * @link      http://codeninjas.co
 * @copyright 2014 Code Ninjas
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
 
class WMCS_Common_Init {
	
	protected $currency_data = NULL;

	public function __construct()
	{	
		include_once 'wmcs-helper.php';
		include_once 'class-wmcs-exchange-api.php';
		include_once 'widgets/class-wmcs-widget-currency-switcher.php';
	
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	
		add_action( 'init', array( $this, 'start_session' ), 1);
		add_action('wp_logout', array( $this, 'end_session' ) );
		add_action('wp_login', array( $this, 'end_session' ) );
	
		add_action( 'init', array( $this, 'init' ) );
		
		add_action( 'widgets_init', array( $this, 'register_widgets' ) );
		//add_action( 'init', array( $this, 'capture_currency_change' ) );
		add_action( 'wp_loaded', array( $this, 'capture_currency_change' ) );
		
	}

	/**
	 * Initialisation for Frontend
	 * Some stuff only needs to be done from the frontend only, so check first and initialise if this is the frontend
	 *
	 * @since	1.0
	 */
	public function init(){
	
		if( defined('DOING_AJAX') && DOING_AJAX || !is_admin() ){ //only do this on the frontend
			
			include_once 'class-wmcs-product.php';
			//include_once 'class-wmcs-cart.php';
			
			$this->get_customer_currency();
			add_filter( 'woocommerce_currency', array( $this, 'filter_woocommerce_currency' ), 99 ); //filter currency
			add_filter( 'pre_option_woocommerce_price_decimal_sep', array( $this, 'filter_woocommerce_price_decimal_sep' ) ); //filter decimal separator
			add_filter( 'pre_option_woocommerce_price_thousand_sep', array( $this, 'filter_woocommerce_price_thousand_sep' ) ); //filter thousands separator
			add_filter( 'pre_option_woocommerce_price_num_decimals', array( $this, 'filter_woocommerce_price_num_decimals' ) ); //filter decimal places
			//add_filter( 'pre_option_woocommerce_currency_pos', array( $this, 'filter_woocommerce_currency_pos' ) ); //filter currency position
			add_filter( 'woocommerce_price_format', array( $this, 'filter_woocommerce_price_format' ) );
			add_filter( 'raw_woocommerce_price', array( $this, 'price_rounding' ) );
			
			add_filter( 'woocommerce_package_rates', array( $this, 'convert_shipping_rates' ), 10, 2 );
			
		}
		
	}
	
	
	/**
	 * Init PHP session
	 *
	 * @since	1.0
	 */
	public function start_session(){
		if( !session_id() ) session_start();
	}
	
	/**
	 * End and destroy PHP session
	 *
	 * @since	1.0
	 */
	public function end_session(){
		if( session_id() ) session_destroy();
	}
	
	/**
	 * Register widgets
	 *
	 * @since	1.0
	 */
	public function register_widgets(){
		register_widget( 'WMCS_Widget_Currency_Switcher' );
	}
	
	/**
	 * Load scripts needed
	 *
	 * @since	1.0
	 */
	public function enqueue_scripts(){
		wp_enqueue_script( 'jquery' );	
	}
	
	/**
	 * Change the currency of the store to whatever has been passed. 
	 * Used in the currency switcher widget
	 *
	 * @since	1.0
	 */
	public function capture_currency_change(){
	
		if( !empty( $_GET ) && isset( $_GET['wmcs_set_currency'] ) ){
			
			$new_currency = $_GET['wmcs_set_currency'];
			
			$store_currencies = get_option( 'wmcs_store_currencies', array() );
			if( array_key_exists( $new_currency, $store_currencies ) ){ //only change if its a store currency
				$_SESSION['wmcs_currency'] = $new_currency;
			} else {
				$_SESSION['wmcs_currency'] = get_option('woocommerce_currency'); //otherwise revert to store base currency
			}
			
			//recalculate/recreate cart totals/widget
			WC()->session->cart = WC()->cart->get_cart_for_session();
			WC()->cart->calculate_totals();
			
			wp_redirect( remove_query_arg( 'wmcs_set_currency' ) );
			exit;
			
		}
	
	}
	
	/**
	 * Get the customers currency and save the data for use later when filtering 
	 *
	 * @since	1.1
	 */
	public function get_customer_currency(){
		
		$customers_currency = wmcs_get_customers_currency();
		$store_currencies = get_option( 'wmcs_store_currencies', array() );
		if($customers_currency){
			if( array_key_exists( $customers_currency, $store_currencies ) ){
				$this->currency_data = $store_currencies[$customers_currency];
			}
		}
				
	}
	
	/**
	 * Filter Woocommerces currency with custom currency
	 *
	 * @var		string	$separator	Stores currency
	 * @return	string	Custom currency or stores if no currency set
	 * @since	1.0
	 */
	public function filter_woocommerce_currency( $currency ){
		
		if(!is_null($this->currency_data))
			return $this->currency_data['currency_code'];

		return $currency;
	}
	
	/**
	 * Filter Woocommerces decimal separator with custom currency's
	 *
	 * @var		string	$separator	Stores decimal separator
	 * @return	string	Custom currencys decimal separator or stores if no currency set
	 * @since	1.1
	 */
	public function filter_woocommerce_price_decimal_sep( $separator ){
		
		if(!is_null($this->currency_data))
			return $this->currency_data['decimal_separator'];
		
		return $separator;
	}
	
	/**
	 * Filter Woocommerces thousands separator with custom currency's
	 *
	 * @var		string	$separator	Stores thousands separator
	 * @return	string	Custom currencys thousands separator or stores if no currency set
	 * @since	1.1
	 */
	public function filter_woocommerce_price_thousand_sep( $separator ){
		
		if(!is_null($this->currency_data))
			return $this->currency_data['thousand_separator'];
		
		return $separator;
	}
	
	/**
	 * Filter Woocommerces number of decimal places with custom currency's
	 *
	 * @var		string	$position	Store numbers of decimal places
	 * @return	string	Custom currencys decimal places or stores if no currency set
	 * @since	1.1
	 */
	public function filter_woocommerce_price_num_decimals( $decimal_places ){
		
		if(!is_null($this->currency_data))
			return $this->currency_data['decimal_places'];
		
		return $decimal_places;
	}
	
	/**
	 * Filter Woocommerces currency symbol  position with custom currency's
	 *
	 * @var		string	$position	Stores currency position
	 * @return	string	Customer currency's symbol position or stores if no currency set
	 * @since	1.1
	 */
	public function filter_woocommerce_currency_pos( $position ){
		
		if(!is_null($this->currency_data))
			return $this->currency_data['position'];
		
		return $position;
	}
	
	
	/**
	 * Filter Woocommerces price format 
	 *
	 * @var		string	$format		Current price format
	 * @return	string				Price format for this currency
	 * @since	1.1
	 */
	public function filter_woocommerce_price_format( $format ){
		
		if(!is_null($this->currency_data)){
			$format = $this->currency_data['price_format'];
			
			//price - %2$s in wc_price
			$format = str_replace( '[price]', '%2$s', $format );
			//currency_symbol - %1$s in wc_price
			$format = str_replace( '[currency_symbol]', '%1$s', $format );
			//currency_code
			$format = str_replace( '[currency_code]', $this->currency_data['currency_code'], $format );
			
			return $format;
			
		}
		
		return $format;
	}
	
	
	/**
	 * Apply rounding to price
	 *
	 * @var		float	$price	Raw price
	 * @return	float	$price	Rounded price/raw price
	 * @since	1.2.3
	 */
	function price_rounding( $price ){
		
		if( !is_null( $this->currency_data ) ){
			$rounding_type = $this->currency_data['rounding_type'];
			$rounding_to = $this->currency_data['rounding_to'];
			if($rounding_type != 'none'){
				if($rounding_type == 'up') $rounded_price = round( $price, $rounding_to, PHP_ROUND_HALF_UP );
				else $rounded_price = round( $price, $rounding_to, PHP_ROUND_HALF_DOWN );
				
				return $rounded_price;
			}
		}
		
		return $price;
	}
	
	
	/**
	 * Convert the shipping rates costs to the selected currency
	 *
	 * @var		array	$rates		The shipping rates
	 * @var 	object	$pacakge	Package/Order
	 * @return	array	$rates		Modify rates costs
	 * @since	1.0
	 */
	public function convert_shipping_rates( $rates, $package ){
		
		foreach( $rates as $id => $rate ){
			$rates[$id]->cost = wmcs_convert_price($rate->cost);
		}
		
		return $rates;
				
	}
	
}
return new WMCS_Common_Init();