<?php
/**
 * Exchange APIs
 *
 * @package   Woocommerce Multi Currency Pricing
 * @author    Code Ninjas 
 * @link      http://codeninjas.co
 * @copyright 2014 Code Ninjas
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
 
class WMCS_Exchange_Api {

	public function __construct()
	{
		
	}
	
	public function get_exchange_rates( $api = NULL ){
	
		if( !$api ) $api = get_option( 'wmcs_exchange_rate_source', 'custom' );
		
		$store_currencies = get_option( 'wmcs_store_currencies', array() );
	
		$this->$api( array_keys( $store_currencies ) );
	}
	
	private function yahoo( $currencies = array() ){

		if( $currencies ){
		
			$request_url = 'https://query.yahooapis.com/v1/public/yql?q=select%20*%20from%20yahoo.finance.xchange%20where%20pair%20in%20({{CURRENCY_PAIRS}})&format=json&env=store%3A%2F%2Fdatatables.org%2Falltableswithkeys&callback=';
		
			$base_currency_code = get_option( 'woocommerce_currency' );
			
			$currency_pairs = array();
			foreach( $currencies as $currency ){
				$currency_pairs[] = '"'.$base_currency_code.$currency.'"';
			}
			
			$request_url = str_replace( '{{CURRENCY_PAIRS}}', implode( ',', $currency_pairs ), $request_url );
			
			$args = array( 'timeout' => 10 );
			$retries = 3;
			while( $retries != 0 ){
				$data = wp_remote_get( $request_url, $args );
				if( is_wp_error( $data ) ){
					sleep(10);
					$retries--;
					if($retries == 0) $data = FALSE;
				} else {
					break;
				}
			}

			if( $data ){
				if( $data['response']['code'] == 200 ){
					
					$response = json_decode( $data['body'] );
					
					$rates = array();
					if( count( $response->query->results->rate ) == 1 ){
						$currency = $response->query->results->rate;
						$currency_code = str_ireplace( $base_currency_code, '', $currency->id );
						$rates[$currency_code] = $currency->Rate;
					} else {
						foreach( $response->query->results->rate as $currency ){
							$currency_code = str_ireplace( $base_currency_code, '', $currency->id );
							$rates[$currency_code] = $currency->Rate;
						}
					}
					
					update_option( 'wmcs_live_exchange_rates', array( 'last_checked' => time(), 'rates' => $rates ) );
					
				} else {
					
					$response = json_decode( $data['body'] );
					wmcs_log( 'Yahoo API | '. $response->error->description . ' | ' . $request_url, 'api' );
					
				}
			}
		}
	}
	
	private function custom(){
		
		update_option( 'wmcs_live_exchange_rates', array( 'rates' => array() ) );
		
	}
	
	public static function get_rate( $currency = '' ){
	
		if( !$currency )
			$currency = wmcs_get_customers_currency();
		
		if( !$currency ) return FALSE; //no currency selected
		
		$store_currencies = get_option( 'wmcs_store_currencies', array() );
			
		if( !array_key_exists( $currency, $store_currencies ) )   
			return FALSE;
				
		if( $store_currencies[$currency]['exchange_rate_type'] == 'custom' )
			return $store_currencies[$currency]['exchange_rate_value'];
		else{
			$live_exchange_rates = get_option( 'wmcs_live_exchange_rates', array( ) );
			if( array_key_exists( $currency, $live_exchange_rates['rates'] ) )
				return $live_exchange_rates['rates'][$currency];
		}
			
		return FALSE;
			
	
	}
	

}