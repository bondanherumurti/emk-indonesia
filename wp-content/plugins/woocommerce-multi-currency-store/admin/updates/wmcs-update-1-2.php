<?php
/**
 * Version 1.2 Update
 *   - Move saved currency prices for each product from single meta (wmcs_currency_prices) to individual meta for each currency
 */

global $wpdb;
 
//Move saved currency prices for each product from single meta (wmcs_currency_prices) to individual meta for each currency
$currency_prices_meta = $wpdb->get_results("SELECT * FROM $wpdb->postmeta WHERE meta_key = 'wmcs_currency_prices'");
foreach( $currency_prices_meta as $meta ){
	
	$product_id = $meta->post_id;
	$currency_prices = unserialize($meta->meta_value);
	foreach( $currency_prices  as $currency => $prices ){
		$regular_price = $prices['regular'] != 0 ? $prices['regular'] : '';
		$sale_price = $prices['sale'] != 0 ? $prices['sale'] : '';
		$price = $sale_price ? $sale_price : $regular_price;
		
		add_post_meta( $product_id, '_'.$currency.'_regular_price', $regular_price );
		add_post_meta( $product_id, '_'.$currency.'_sale_price', $sale_price );
		add_post_meta( $product_id, '_'.$currency.'_price', $price );
		
	}
	
	delete_post_meta( $product_id, 'wmcs_currency_prices' );
	
}
 