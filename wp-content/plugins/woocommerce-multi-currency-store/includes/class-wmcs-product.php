<?php
/**
 * Product Class
 *
 * @package   Woocommerce Multi Currency Store Product Frontend
 * @author    Code Ninjas 
 * @link      http://codeninjas.co
 * @copyright 2014 Code Ninjas
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
 
class WMCS_Product {

	public function __construct()
	{	
		/*add_filter( 'woocommerce_get_price', array( $this, 'filter_price' ), 10, 2 );
		add_filter( 'woocommerce_get_regular_price', array( $this, 'filter_regular_price' ), 10, 2 );
		add_filter( 'woocommerce_get_sale_price', array( $this, 'filter_sale_price' ), 10, 2 );
		
		add_filter( 'woocommerce_get_variation_price', array( $this, 'filter_variation_price' ), 10, 4 );
		add_filter( 'woocommerce_get_variation_regular_price', array( $this, 'filter_variation_regular_price' ), 10, 4 );
		add_filter( 'woocommerce_get_variation_sale_price', array( $this, 'filter_variation_sale_price' ), 10, 4 ); // Not used anywhere in Woo!
		*/
		
		//add_filter( 'woocommerce_grouped_price_html', array( $this, 'filter_grouped_price_html' ), 10, 2 );
		
		add_filter( 'get_post_metadata', array( $this, 'filter_post_meta_prices' ), 10, 4 );
		add_filter( 'get_post_metadata', array( $this, 'filter_post_meta_min_max_variation_ids' ), 10, 4 );
		add_filter( 'get_post_metadata', array( $this, 'filter_post_meta_min_max_variation_prices' ), 10, 4 );
		
		if( version_compare( WC_VERSION, '2.4', '>=' ) ){
			add_filter( 'woocommerce_get_variation_prices_hash', array( $this, 'add_currency_to_variation_prices_hash' ), 10, 3 );
		}
		
	}
	
	/**
	 * Add the currently selected currency to the variation prices hash so the prices can be saved in a transient
	 * Transient expiry will be updated (using setted_transient action) to match next exchange rate update
	 */
	public function add_currency_to_variation_prices_hash( $hash, $product, $display ){
		
		$currency = wmcs_get_customers_currency();
		
		if( $currency == get_option( 'woocommerce_currency' ) )
			return $hash;
		
		$hash[] = $currency;
		
		/**
		 * Do we need to change the expiry of the new transient?
		 * The transient should expire when the exchange rates are refreshed (when using live rates)
		 * If using custom rates, the transients only need to expire when the products is save (built into Woo) or when the rates are changed.
		 */
		$store_currencies = get_option( 'wmcs_store_currencies', array() );
		if( array_key_exists( $currency, $store_currencies ) ){
			if( $store_currencies[$currency]['exchange_rate_type'] == 'live' ){
				add_action( 'setted_transient', array( $this, 'update_variation_prices_transient_expiry' ), 10, 3 );
			}
		}
		
		return $hash;
	}
	
	function update_variation_prices_transient_expiry( $transient, $value, $expiration ){
		
		if( strpos( $transient, 'wc_var_prices' ) !== FALSE ){
			$transient = str_replace( '_transient_', '', $transient );
			$next_rate_check = wp_next_scheduled( 'wmcs_cron_check_exchange_rates' );
			$new_transient_expiry = $next_rate_check - time();
			set_transient( $transient, $value, $new_transient_expiry );
		}
	}

	
	/**
	 * Return the price for the selected currency instead of the base price
	 * If no price has been set for the currency, the base price will be converted and returned
	 */
	public function filter_post_meta_prices( $metadata, $object_id, $meta_key, $single ){
		
		$post = get_post( $object_id );
		if( $post && ( $post->post_type == 'product' || $post->post_type == 'product_variation' ) ){
			
			if( in_array( $meta_key, array( '_price', '_regular_price', '_sale_price' ) ) ){
				
				$currency = wmcs_get_customers_currency();
				if($currency){
					global $wpdb; //using this as get_post_meta will cause an infinite loop
					
					//get price for this product in this currency
					$data = $wpdb->get_results( "SELECT * FROM $wpdb->postmeta WHERE post_id = $object_id AND meta_key = '_{$currency}{$meta_key}'" );
					if( $data && !empty( $data[0]->meta_value ) ){
						return (float)$data[0]->meta_value;
					}
						
					//no price set in this currency, get the original product price and convert it
					$data = $wpdb->get_results( "SELECT * FROM $wpdb->postmeta WHERE post_id = $object_id AND meta_key = '{$meta_key}'" );
					if( $data && !empty( $data[0]->meta_value ) ){
						return wmcs_convert_price( $data[0]->meta_value );
					}
					
				}
				
			}
			
		}
		
		return $metadata; //NULL
		
	}
	
	public function filter_post_meta_min_max_variation_ids( $metadata, $object_id, $meta_key, $single ){
		
		$post = get_post( $object_id );
		if( $post && ( $post->post_type == 'product' || $post->post_type == 'product_variation' ) ){
			
			$variation_ids_meta_keys = array( 
				'_min_price_variation_id',
				'_max_price_variation_id',
				'_min_regular_price_variation_id',
				'_max_regular_price_variation_id',
				'_min_sale_price_variation_id',
				'_max_sale_price_variation_id'
			);
			
			if( in_array( $meta_key, $variation_ids_meta_keys ) ){
				
				$currency = wmcs_get_customers_currency();
				if($currency){
					global $wpdb;
					
					//get products children
					$product = wc_get_product( $object_id );
					if( $product ){
						$children = $product->get_children();
						
						//which price are we getting?
						$min_or_max = 'min';
						$price_type = 'price';
						if( in_array( $meta_key, array( '_max_price_variation_id', '_max_regular_price_variation_id', '_max_sale_price_variation_id' ) ) ){ //max
							$min_or_max = 'max';
						}
						if( in_array( $meta_key, array( '_min_regular_price_variation_id', '_max_regular_price_variation_id' ) ) ){ //regular price
							$price_type = 'regular_price';
						}
						if( in_array( $meta_key, array( '_min_sale_price_variation_id', '_max_sale_price_variation_id' ) ) ){ //sale price
							$price_type = 'sale_price';
						}
						
						//get the prices for this price type
						$prices = array();
						foreach( $children as $child_id ){
							$data = $wpdb->get_results( "SELECT * FROM $wpdb->postmeta WHERE post_id = $child_id AND meta_key = '_{$currency}_{$price_type}'" );
							if( $data && !empty( $data[0]->meta_value ) ){
								$prices[$child_id] = $data[0]->meta_value;
							} else { //no price set for this currency, get the base price and convert
								$data = $wpdb->get_results( "SELECT * FROM $wpdb->postmeta WHERE post_id = $child_id AND meta_key = '_{$price_type}'" );
								if( $data && !empty( $data[0]->meta_value ) ){
									$prices[$child_id] = wmcs_convert_price( $data[0]->meta_value );
								}
							}
						}
						
						if( $prices ){ 
							//return the price
							if( $min_or_max == 'max' ) return array_search( max($prices), $prices);
							else return array_search( min($prices), $prices);
							
						}
						
					}
					
				}
				
			}
			
		}
		
		return $metadata; //NULL
		
	}
	
	
	public function filter_post_meta_min_max_variation_prices( $metadata, $object_id, $meta_key, $single ){
		
		$post = get_post( $object_id );
		if( $post && ( $post->post_type == 'product' || $post->post_type == 'product_variation' ) ){
			
			$variation_prices_meta_keys = array( 
				'_min_variation_price',
				'_max_variation_price',
				'_min_variation_regular_price',
				'_max_variation_regular_price',
				'_min_variation_sale_price',
				'_max_variation_sale_price' 
			);
			
			if( in_array( $meta_key, $variation_prices_meta_keys ) ){
				
				$currency = wmcs_get_customers_currency();
				if($currency){
					global $wpdb;
					
					//get products children
					$product = wc_get_product( $object_id );
					if( $product ){
						$children = $product->get_children();
						
						//which price are we getting?
						$min_or_max = 'min';
						$price_type = 'price';
						if( in_array( $meta_key, array( '_max_variation_price', '_max_variation_regular_price', '_max_variation_sale_price' ) ) ){ //max
							$min_or_max = 'max';
						}
						if( in_array( $meta_key, array( '_min_variation_regular_price', '_max_variation_regular_price' ) ) ){ //regular price
							$price_type = 'regular_price';
						}
						if( in_array( $meta_key, array( '_min_variation_sale_price', '_max_variation_sale_price' ) ) ){ //sale price
							$price_type = 'sale_price';
						}
						
						//get the prices for this price type
						$prices = array();
						foreach( $children as $child_id ){
							$data = $wpdb->get_results( "SELECT * FROM $wpdb->postmeta WHERE post_id = $child_id AND meta_key = '_{$currency}_{$price_type}'" );
							if( $data && !empty( $data[0]->meta_value ) ){
								$prices[$child_id] = $data[0]->meta_value;
							} else { //no price set for this currency, get the base price and convert
								$data = $wpdb->get_results( "SELECT * FROM $wpdb->postmeta WHERE post_id = $child_id AND meta_key = '_{$price_type}'" );
								if( $data && !empty( $data[0]->meta_value ) ){
									$prices[$child_id] = wmcs_convert_price( $data[0]->meta_value );
								}
							}
						}
						
						if( $prices ){ 
							//return the price
							if( $min_or_max == 'max' ) return max($prices);
							else return min($prices);
							
						}
						
					}
					
				}
				
			}
			
		}
		
		return $metadata; //NULL
		
	}
	
	
	/**
	 * Filter the products price and convert it to the selected currency
	 *
	 * @param	string	$price		Price of the product
	 * @param	object	$product	Product object
	 * @return	float	$price		Converted price
	 * @since	1.0 
	 */	
	public function filter_price( $price, $product ){
		
		if(!is_object($product)) return;
		
		if($product->is_type('variable')) return $price; //let variation filter handle the price
		
		$product_id = $product->is_type('variation') ? $product->variation_id : $product->id;
		
		$currency_price = $this->get_products_currency_price($product_id); //get this currencies price
		
		return $currency_price ? $currency_price : wmcs_convert_price( $price );
		
	}
	
	/**
	 * Filter the products regular price and convert it to the selected currency
	 *
	 * @param	string	$price		Price of the product
	 * @param	object	$product	Product object
	 * @return	float	$price		Converted price
	 * @since	1.0 
	 */	
	public function filter_regular_price( $price, $product ){
				
		if(!is_object($product)) return;
		
		if($product->is_type('variable')) return $price; //let variation filter handle the price
		
		$product_id = $product->is_type('variation') ? $product->variation_id : $product->id;
		$currency_price = $this->get_products_currency_price($product_id, 'regular'); //get this currencies price
		
		return $currency_price ? $currency_price : wmcs_convert_price( $price );
	}
	
	/**
	 * Filter the products sale price and convert it to the selected currency
	 *
	 * @param	string	$price		Price of the product
	 * @param	object	$product	Product object
	 * @return	float	$price		Converted price
	 * @since	1.0 
	 */	
	public function filter_sale_price( $price, $product ){
				
		if(!is_object($product)) return;
		
		if($product->is_type('variable')) return $price; //let variation filter handle the price
		
		$product_id = $product->is_type('variation') ? $product->variation_id : $product->id;
		$currency_price = $this->get_products_currency_price($product_id, 'sale'); //get this currencies price
		
		return $currency_price ? $currency_price : wmcs_convert_price( $price );
	}
	
	/**
	 * Get the prices for the current currency for the product and price type passed in
	 *
	 * @param	string	$product_id	Product ID
	 * @param	object	$price_type	Price tto get
	 * @return	float	$price		Price in current currency
	 * @since	1.1 
	 */	
	private function get_products_currency_price($product_id, $price_type = ''){
	
		$customers_currency = wmcs_get_customers_currency();
		
		$price_type = ( $price_type == 'regular' || $price_type == 'sale' ) ? "{$price_type}_price" : "price";
		$price_type = "_{$customers_currency}_{$price_type}";
		
		$price = get_post_meta( $product_id, $price_type, TRUE );
		
		return $price;
		
		/*
		$currency_prices = (array)get_post_meta( $product_id, 'wmcs_currency_prices', TRUE );
		
		$regular_price = '';
		$sale_price = '';
		
		if( array_key_exists( $customers_currency, $currency_prices ) ){ //prices set in this currency
		
			$regular_price = $currency_prices[$customers_currency]['regular'];
			$sale_price = $currency_prices[$customers_currency]['sale'];				
		
		}
		
		if($regular_price  != ''){ //a regular price is set, so price is valid (can't have a sale price without a regular price)
		
			if($price_type == 'regular'){
				return $regular_price;
			} elseif($price_type == 'sale'){
				return $sale_price;
			} else {
				return ($sale_price != '') ? $sale_price : $regular_price;
			}
			
		}
		
		return FALSE;
		*/
	}
	
	/**
	 * Filter a variations products price and convert it to the selected currency
	 *
	 * @param	string	$price		Price of the product
	 * @param	object	$product	Product object
	 * @param	string	$min_or_max	Which price to get
	 * @param	object	$display	Return with HTML or not
	 * @return	float	$price		Converted price
	 * @since	1.0 
	 */	
	public function filter_variation_price( $price, $product, $min_or_max, $display ){
		
		$return_price = $this->get_variable_products_price( $product, $min_or_max );
		
		if( $return_price ) return $return_price;
		
		return wmcs_convert_price( $price );
	
	}
	
	/**
	 * Filter a variations products regular price and convert it to the selected currency
	 *
	 * @param	string	$price		Price of the product
	 * @param	object	$product	Product object
	 * @param	string	$min_or_max	Which price to get
	 * @param	object	$display	Return with HTML or not
	 * @return	float	$price		Converted price
	 * @since	1.0 
	 */	
	public function filter_variation_regular_price( $price, $product, $min_or_max, $display ){
		
		$return_price = $this->get_variable_products_price( $product, $min_or_max, 'regular' );
		
		if( $return_price ) return $return_price;
		
		return wmcs_convert_price( $price );
	
	}
	
	private function get_variable_products_price( $product, $min_or_max = 'min', $price_type = '' ){
		
		$currency = wmcs_get_customers_currency();
		
		$product_children = $product->get_children();
		
		$price_type = ( $price_type == 'regular' || $price_type == 'sale' ) ? "{$price_type}_price" : "price";

		//get the prices for each child
		if( !empty( $product_children ) ){
			
			$prices = array();
			foreach( $product->children as $child_id ){
				
				$price = get_post_meta( $child_id, "_{$currency}_{$price_type}", TRUE );
				if( !$price ){ //no price for this currency
					
					//get base price
					$price = get_post_meta( $child_id, "_{$price_type}", TRUE );
					if( !$price ) continue; //no base price, skip child
					$price = wmcs_convert_price( $price );
				}
				$prices[] = $price;
				
			}
			
			if( $prices ){	
				if($min_or_max == 'max') return max( $prices );
				else return min( $prices );
			}
			
		}
		
		return FALSE;
		
		
				
		
		/*
		$min_price = '';
		$max_price = '';
	
		$customers_currency = wmcs_get_customers_currency();
		
		if( !isset( $product->children ) || empty( $product->children ) ){
			$product->children = $product->get_children();
		}
		
		foreach( $product->children as $child_id ){
		
			$currency_prices = (array)get_post_meta( $child_id, 'wmcs_currency_prices', TRUE );
			
			$regular_price = '';
			$sale_price = '';
			
			if( array_key_exists( $customers_currency, $currency_prices ) ){ //price set in this currency for this variation
			
				$regular_price = $currency_prices[$customers_currency]['regular'];
				$sale_price = $currency_prices[$customers_currency]['sale'];				
			
			}
			
			//no prices set for this currency for this variation
			if($regular_price == ''){
				//if not regular price has been set then get original prices
				//no need to check sale price as its an invalid price if no regular price has been set
				$regular_price = get_post_meta( $child_id, '_regular_price', TRUE );
				$sale_price = get_post_meta( $child_id, '_sale_price', TRUE );
				
				//original prices are in the store currency and need to be converted 
				$regular_price = wmcs_convert_price( $regular_price );
				$sale_price = wmcs_convert_price( $sale_price );
			}
			
			//pr($min_or_max.' regular - '.$regular_price);
			//pr($min_or_max.' sale - '.$sale_price);
			if($regular_price  != ''){ //a regular price is set, so price is valid (can't have a sale price without a regular price)
				
				if($price_type == 'regular'){ //only check regular prices
					
					if($min_price == '' || $regular_price < $min_price) //check min price
						$min_price = $regular_price;
						
					if($max_price == '' || $regular_price > $max_price) //check max price
						$max_price = $regular_price;
				
				} else { //check sale and regular prices
					
					if($sale_price != ''){ //if there a sale price
					
						if($min_price == '' || $sale_price < $min_price) //check min price
							$min_price = $sale_price;
							
						if($max_price == '' || $sale_price > $max_price) //check max price
							$max_price = $sale_price;
							
					} else { //no sale price, use regular price
					
						if($min_price == '' || $regular_price < $min_price) //check min price
							$min_price = $regular_price;
							
						if($max_price == '' || $regular_price > $max_price) //check max price
							$max_price = $regular_price;
					}
				
				}
			
			}
		
			//pr('min - '.$min_price);
			//pr('max - '.$max_price);
		
		}
		
		return ( $min_or_max == 'min' ) ? $min_price : $max_price;*/
	
	}
	
	
	
}
return new WMCS_Product();