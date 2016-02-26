<?php
/**
 * Cart Class
 *
 * @package   Woocommerce Multi Currency Store
 * @author    Code Ninjas 
 * @link      http://codeninjas.co
 * @copyright 2014 Code Ninjas
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
 
class WMCS_Cart {

	public function __construct()
	{	
		//add_filter( 'woocommerce_calculated_total', array( $this, 'filter_woocommerce_calculated_total' ), 10, 2 );
		//add_action( 'init', array( $this, 'change_cart_dp' ), 99 );
	}
	
	public function change_cart_dp(){
	
		$customers_currency = wmcs_get_customers_currency();
		
		$store_currencies = get_option( 'wmcs_store_currencies', array() );
		
		if($customers_currency){
			if( array_key_exists( $customers_currency, $store_currencies ) ){
				$decimal_places = $store_currencies[$customers_currency]['decimal_places'];
				WC()->cart->dp = $decimal_places; 
			}
		}
	
	}
	
	public function filter_woocommerce_calculated_total( $total, $cart ){
	
		pr($total);
		return $total;
		
	}
}
return new WMCS_Cart();