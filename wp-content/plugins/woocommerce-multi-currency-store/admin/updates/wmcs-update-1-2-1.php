<?php
/**
 * Version 1.2.1 Update
 *   - Replace currency symbol position setting for each currency with tag based formatting
 */

//Replace currency symbol position setting for each currency with tag based formatting
$store_currencies = get_option( 'wmcs_store_currencies', array() );

foreach( $store_currencies as $currency => $data ){
	
	switch( $data['position'] ){
		case 'left':
			$store_currencies[$currency]['price_format'] = '[currency_symbol][price]';
			break;
		case 'right':
			$store_currencies[$currency]['price_format'] = '[price][currency_symbol]';
			break;
		case 'left_space':
			$store_currencies[$currency]['price_format'] = '[currency_symbol] [price]';
			break;
		case 'right_space':
			$store_currencies[$currency]['price_format'] = '[price] [currency_symbol]';
			break;
	}
	
}

update_option( 'wmcs_store_currencies', $store_currencies );
