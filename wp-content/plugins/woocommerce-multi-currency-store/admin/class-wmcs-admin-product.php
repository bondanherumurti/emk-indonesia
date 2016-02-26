<?php
/**
 * Admin Product class
 *
 * @package   Woocommerce Multi Currency Store
 * @author    Code Ninjas 
 * @link      http://codeninjas.co
 * @copyright 2014 Code Ninjas
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
 
class WMCS_Admin_Product {

	public function __construct()
	{		
		if( get_option( 'wmcs_enabled', FALSE ) ){
			add_action( 'woocommerce_product_options_pricing', array( $this, 'output_currency_table' ) );
			add_action( 'save_post', array( $this, 'save_currency_rates' ) );
			
			//variations
			add_action( 'woocommerce_product_after_variable_attributes', array( $this, 'output_currency_table_variation'), 10, 3 );
			add_action( 'woocommerce_save_product_variation', array( $this, 'save_currency_rates_variation' ), 10, 2 );

		}
				
	}
	
	public function output_currency_table( ){
	
		global $thepostid, $post;
		
		$product = get_product( $thepostid );
		if( $product->is_type( 'variable' ) ) return;
		
		$products_currency_prices = get_post_meta( $thepostid, 'wmcs_currency_prices', TRUE );
		
		$regular_price = get_post_meta( $thepostid, '_regular_price', true );
		$sale_price = get_post_meta( $thepostid, '_sale_price', true );
		
		$store_currencies = get_option( 'wmcs_store_currencies', array() );
		
		if( !empty( $store_currencies ) ){
		
			echo '</div>';
			echo '<div class="options_group wmcs_currency_pricing show_if_simple show_if_external">';
			
			foreach( $store_currencies as $currency => $data ){
				
				//regular price
				$converted_regular_price = '';
				if($regular_price){
					$converted_regular_price = wmcs_convert_price( $regular_price, $currency );
					$converted_regular_price = 'Converted regular price - ' . wc_price( $converted_regular_price, array( 'currency' => $currency ) ) ;
				}
				woocommerce_wp_text_input( array( 'id' => '_'.$currency.'_regular_price', 'data_type' => 'price', 'label' => $currency . ' ' . __( 'Regular Price', 'woocommerce' ) . ' ('.get_woocommerce_currency_symbol($currency).')', 'description' => $converted_regular_price ) );
				
				//sale price
				$converted_sale_price = '';
				if($sale_price){
					$converted_sale_price = wmcs_convert_price( $sale_price, $currency );
					$converted_sale_price = 'Converted sale price - ' . wc_price( $converted_sale_price, array( 'currency' => $currency ) );
				}
				
				woocommerce_wp_text_input( array( 'id' => '_'.$currency.'_sale_price', 'data_type' => 'price', 'label' => $currency . ' ' . __( 'Sale Price', 'woocommerce' ) . ' ('.get_woocommerce_currency_symbol($currency).')', 'description' => $converted_sale_price ) );
				
			}
		
		}
	/*
		echo '<div class="form-field wmcs_currency_prices_form_field">
				<label>'._e( 'Currency Prices', 'woocommerce' ).'</label>';
		
				include 'views/product-currency-table.phtml';
				
		echo '<p class="description">Note: Remember to set both the regular and the sale price if needed.</p>
			  </div>';*/
	}
	
	
	public function output_currency_table_variation( $loop, $variation_data, $variation ){
		
		$store_currencies = get_option( 'wmcs_store_currencies', array() );
		
		if( !empty( $store_currencies ) ){
			
			echo '<div class="variable_pricing">';
			
			foreach( $store_currencies as $currency => $data ){
				
				//regular price
				$regular_price = get_post_meta( $variation->ID, '_regular_price', TRUE );
				$converted_regular_price = '';
				if($regular_price){
					$converted_regular_price = wmcs_convert_price( $regular_price, $currency );
					$converted_regular_price = 'Converted regular price - ' . wc_price( $converted_regular_price, array( 'currency' => $currency ) ) ;
				}
				echo '<p class="form-row form-row-first">';	
				echo '<label>'.$currency.' '.__( 'Regular Price:', 'woocommerce' ) . ' (' . get_woocommerce_currency_symbol($currency) . ') </label>';
				echo '<input type="text" size="5" name="currency_prices['.$loop.']['.$currency.'][regular]" value="' . get_post_meta( $variation->ID, "_{$currency}_regular_price", TRUE ) . '" class="wc_input_price" />';
				echo '<small>'.$converted_regular_price.'</small>';
				echo '</p>';
				
				
				//sale price
				$sale_price = get_post_meta( $variation->ID, '_sale_price', TRUE );
				$converted_sale_price = '';
				if($sale_price){
					$converted_sale_price = wmcs_convert_price( $sale_price, $currency );
					$converted_sale_price = 'Converted sale price - ' . wc_price( $converted_sale_price, array( 'currency' => $currency ) ) ;
				}
				echo '<p class="form-row form-row-last">';	
				echo '<label>'.$currency.' '.__( 'Sale Price:', 'woocommerce' ) . ' (' . get_woocommerce_currency_symbol($currency) . ') </label>';
				echo '<input type="text" size="5" name="currency_prices['.$loop.']['.$currency.'][sale]" value="' . get_post_meta( $variation->ID, "_{$currency}_sale_price", TRUE ) . '" class="wc_input_price" />';
				echo '<small>'.$converted_sale_price.'</small>';
				echo '</p>';
			}
			
			echo '</div>';
			
		}
		
		/*
		$products_currency_prices = get_post_meta( $variation->ID, 'wmcs_currency_prices', true );
		//$products_currency_prices = ( isset( $variation_data['wmcs_currency_prices'] ) ) ? unserialize( $variation_data['wmcs_currency_prices'][0] ) : array();
		
		$regular_price = is_array($variation_data['_regular_price']) ? $variation_data['_regular_price'][0] : $variation_data['_regular_price'];
		$sale_price = is_array($variation_data['_sale_price']) ? $variation_data['_sale_price'][0] : $variation_data['_sale_price'];

		
		
		echo '<tr>
				<td colspan="2">';
			
			echo '<label>Currency Pricing:</label>';
			include 'views/product-currency-table-variation.phtml';
		
		echo '	</td>
			</tr>';*/
			
	}
	
	
	public function save_currency_rates( $post_id ){
	
		if( !$post_id ) return;        
		
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
		
		$store_currencies = get_option( 'wmcs_store_currencies', array() );
		
		foreach( $store_currencies as $currency => $data ){
		
			$regular_price = isset( $_POST['_'.$currency.'_regular_price'] ) ? $_POST['_'.$currency.'_regular_price'] : '';
			$sale_price = isset( $_POST['_'.$currency.'_sale_price'] ) ? $_POST['_'.$currency.'_sale_price'] : '';
			$price = $sale_price ? $sale_price : $regular_price;
			
			update_post_meta( $post_id, '_'.$currency.'_regular_price', $regular_price );
			update_post_meta( $post_id, '_'.$currency.'_sale_price', $sale_price );
			update_post_meta( $post_id, '_'.$currency.'_price', $price );	
		
		}
	
	}
	
	
	public function save_currency_rates_variation( $variation_id, $loop ){    
		
		if( !$variation_id ) return;        
		
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
		
		if( isset( $_POST['currency_prices'] ) ){
			
			$currency_prices = $_POST['currency_prices'][$loop]; 
			foreach( $currency_prices as $currency => $prices ){
				
				$regular_price = $prices['regular'];
				$sale_price = $prices['sale'];
				$price = $sale_price ? $sale_price : $regular_price;
				
				update_post_meta( $variation_id, '_'.$currency.'_regular_price', $regular_price );
				update_post_meta( $variation_id, '_'.$currency.'_sale_price', $sale_price );
				update_post_meta( $variation_id, '_'.$currency.'_price', $price );
				
			}
			
		}
		
		/*
		
		if( isset( $_POST['currency_prices'] ) ){
		
			$currency_prices = array_shift( $_POST['currency_prices'] ); 
			
			$data = array();
			foreach( $currency_prices as $currency => $rate ){
				
				$rate_regular = (float)$rate['regular'];
				$rate_regular = ( $rate_regular < 0 ) ? 0.0 : $rate_regular;
				$rate_sale = (float)$rate['sale'];
				$rate_sale = ( $rate_sale < 0 ) ? 0.0 : $rate_sale;
				
				$data[$currency] = array('regular' => $rate_regular, 'sale' => $rate_sale);
			}
			
			update_post_meta( $variation_id, 'wmcs_currency_prices', $data );
		}*/
		
	}

}
return new WMCS_Admin_Product();

