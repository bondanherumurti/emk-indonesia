<?php

function wmcs_log( $message, $log_type = 'error' ){
	
	$path = WMCS_DIR.'logs/'.$log_type.'.log';
	
	$fp = fopen( $path, 'a+' );
	fwrite( $fp, date('Y-m-d H:i:s') . ' | ' . $message . PHP_EOL );
	fclose( $fp );

}

/**
 * get_option wrapper
 * if get_option doesn't find a value in the database, the default value will be returned instead
 *
 * @since     1.2.0
 */
function wmcs_get_option( $option_name = '' ){
	
	if( !class_exists( 'WMCS_Settings' ) ){
		include_once( WMCS_DIR. 'admin/class-wmcs-admin-settings.php' );
	}
	
	$defaults = WMCS_Settings::get_settings_defaults();
	
	$value = get_option( $option_name, NULL );
	if( is_null( $value ) ){
		if( array_key_exists( $option_name, $defaults ) ){
			return $defaults[$option_name];
		} else {
			return FALSE;
		}
	}
	
	return $value;
	
}

/**
 * Get the customers currency based on their country (from their IP address)
 *
 * @since     1.0.0
 */
function wmcs_get_customers_currency(){
	
	$currency = isset($_SESSION['wmcs_currency']) ? $_SESSION['wmcs_currency'] : FALSE;
	
	
	if( !$currency ){

		$base_currency = get_option( 'woocommerce_currency' );

		/**
		 * @since 1.2
		 * Removed custom geoip stuff and using built in Woocommerce Geoip functionality (added in 2.3)
		 */
		if( wmcs_get_option('wmcs_enable_geoip') == 'yes' ){

			$geoip = WC_Geolocation::geolocate_ip();
			if( isset( $geoip['country'] ) && !empty( $geoip['country'] ) ){
				$country_code = $geoip['country'];
				$country_currency_mapping = wmcs_default_country_currency_mappings(); //@TODO change thsi to use new country mappings stuff

				if( array_key_exists( $country_code, $country_currency_mapping ) ){

					$currency = $country_currency_mapping[$country_code];

					$store_currencies = get_option( 'wmcs_store_currencies', array() );
					if( array_key_exists( $currency, $store_currencies ) ){ //currency has been added to the store
						$_SESSION['wmcs_currency'] = $currency;
					} else {
						$_SESSION['wmcs_currency'] = $base_currency;
					}

				} else { //Can't match the country code with a currency, default to base currency

					$_SESSION['wmcs_currency'] = $base_currency;

				}
			}

		}

	}
	
	return apply_filters( 'wmcs_filter_customers_currency', $currency );
	
}

function wmcs_convert_price( $price, $currency = '' ){
	
	if( $price == '' ) return $price;
	
	if( !$currency )
		$currency = wmcs_get_customers_currency();
		
	$exchange_rate = WMCS_Exchange_Api::get_rate( $currency );
	
	if( !$exchange_rate || $currency == get_option('woocommerce_currency') ) return $price;
	
	return $price * $exchange_rate;
	
}


function wmcs_country_currency_mappings(){
	
	$current_mappings = get_option( 'wmcs_country_currency_mappings', array() );
	if( $current_mappings ) return $current_mappings;
	
	return wmcs_default_country_currency_mappings();
}


function wmcs_default_country_currency_mappings(){
	
	$default_mappings = array(
		'NZ' => 'NZD', 'CK' => 'NZD', 'NU' => 'NZD', 'PN' => 'NZD', 'TK' => 'NZD', 'AU' => 'AUD', 'CX' => 'AUD', 'CC' => 'AUD', 'HM' => 'AUD', 'KI' => 'AUD',
		'NR' => 'AUD', 'NF' => 'AUD', 'TV' => 'AUD', 'AS' => 'EUR', 'AD' => 'EUR', 'AT' => 'EUR', 'BE' => 'EUR','FI' => 'EUR',  'FR' => 'EUR', 'GF' => 'EUR',
		'TF' => 'EUR', 'DE' => 'EUR', 'GR' => 'EUR', 'GP' => 'EUR', 'IE' => 'EUR', 'IT' => 'EUR', 'LU' => 'EUR', 'MQ' => 'EUR', 'YT' => 'EUR', 'MC' => 'EUR',
		'NL' => 'EUR', 'PT' => 'EUR', 'RE' => 'EUR', 'WS' => 'EUR', 'SM' => 'EUR', 'SI' => 'EUR', 'ES' => 'EUR', 'VA' => 'EUR', 'GS' => 'GBP', 'GB' => 'GBP',
		'JE' => 'GBP', 'IO' => 'USD', 'GU' => 'USD', 'MH' => 'USD', 'FM' => 'USD', 'MP' => 'USD', 'PW' => 'USD', 'PR' => 'USD', 'TC' => 'USD', 'US' => 'USD',
		'UM' => 'USD', 'VG' => 'USD', 'VI' => 'USD', 'HK' => 'HKD', 'CA' => 'CAD', 'JP' => 'JPY', 'AF' => 'AFN', 'AL' => 'ALL', 'DZ' => 'DZD', 'AI' => 'XCD',
		'AG' => 'XCD', 'DM' => 'XCD', 'GD' => 'XCD', 'MS' => 'XCD', 'KN' => 'XCD', 'LC' => 'XCD', 'VC' => 'XCD', 'AR' => 'ARS', 'AM' => 'AMD', 'AW' => 'ANG',
		'AN' => 'ANG', 'AZ' => 'AZN', 'BS' => 'BSD', 'BH' => 'BHD', 'BD' => 'BDT', 'BB' => 'BBD', 'BY' => 'BYR', 'BZ' => 'BZD', 'BJ' => 'XOF', 'BF' => 'XOF',
		'GW' => 'XOF', 'CI' => 'XOF', 'ML' => 'XOF', 'NE' => 'XOF', 'SN' => 'XOF', 'TG' => 'XOF', 'BM' => 'BMD', 'BT' => 'INR', 'IN' => 'INR', 'BO' => 'BOB',
		'BW' => 'BWP', 'BV' => 'NOK', 'NO' => 'NOK', 'SJ' => 'NOK', 'BR' => 'BRL', 'BN' => 'BND', 'BG' => 'BGN', 'BI' => 'BIF', 'KH' => 'KHR', 'CM' => 'XAF',
		'CF' => 'XAF', 'TD' => 'XAF', 'CG' => 'XAF', 'GQ' => 'XAF', 'GA' => 'XAF', 'CV' => 'CVE', 'KY' => 'KYD', 'CL' => 'CLP', 'CN' => 'CNY', 'CO' => 'COP',
		'KM' => 'KMF', 'CD' => 'CDF', 'CR' => 'CRC', 'HR' => 'HRK', 'CU' => 'CUP', 'CY' => 'CYP', 'CZ' => 'CZK', 'DK' => 'DKK', 'FO' => 'DKK', 'GL' => 'DKK',
		'DJ' => 'DJF', 'DO' => 'DOP', 'TP' => 'IDR', 'ID' => 'IDR', 'EC' => 'ECS', 'EG' => 'EGP', 'SV' => 'SVC', 'ER' => 'ETB', 'ET' => 'ETB', 'EE' => 'EEK',
		'FK' => 'FKP', 'FJ' => 'FJD', 'PF' => 'XPF', 'NC' => 'XPF', 'WF' => 'XPF', 'GM' => 'GMD', 'GE' => 'GEL', 'GI' => 'GIP', 'GT' => 'GTQ', 'GN' => 'GNF',
		'GY' => 'GYD', 'HT' => 'HTG', 'HN' => 'HNL', 'HU' => 'HUF', 'IS' => 'ISK', 'IR' => 'IRR', 'IQ' => 'IQD', 'IL' => 'ILS', 'JM' => 'JMD', 'JO' => 'JOD',
		'KZ' => 'KZT', 'KE' => 'KES', 'KP' => 'KPW', 'KR' => 'KRW', 'KW' => 'KWD', 'KG' => 'KGS', 'LA' => 'LAK', 'LV' => 'LVL', 'LB' => 'LBP', 'LS' => 'LSL',
		'LR' => 'LRD', 'LY' => 'LYD', 'LI' => 'CHF', 'CH' => 'CHF', 'LT' => 'LTL', 'MO' => 'MOP', 'MK' => 'MKD', 'MG' => 'MGA', 'MW' => 'MWK', 'MY' => 'MYR',
		'MV' => 'MVR', 'MT' => 'MTL', 'MR' => 'MRO', 'MU' => 'MUR', 'MX' => 'MXN', 'MD' => 'MDL', 'MN' => 'MNT', 'MA' => 'MAD', 'EH' => 'MAD', 'MZ' => 'MZN',
		'MM' => 'MMK', 'NA' => 'NAD', 'NP' => 'NPR', 'NI' => 'NIO', 'NG' => 'NGN', 'OM' => 'OMR', 'PK' => 'PKR', 'PA' => 'PAB', 'PG' => 'PGK', 'PY' => 'PYG',
		'PE' => 'PEN', 'PH' => 'PHP', 'PL' => 'PLN', 'QA' => 'QAR', 'RO' => 'RON', 'RU' => 'RUB', 'RW' => 'RWF', 'ST' => 'STD', 'SA' => 'SAR', 'SC' => 'SCR',
		'SL' => 'SLL', 'SG' => 'SGD', 'SK' => 'EUR', 'SB' => 'SBD', 'SO' => 'SOS', 'ZA' => 'ZAR', 'LK' => 'LKR', 'SD' => 'SDG', 'SR' => 'SRD', 'SZ' => 'SZL',
		'SE' => 'SEK', 'SY' => 'SYP', 'TW' => 'TWD', 'TJ' => 'TJS', 'TZ' => 'TZS', 'TH' => 'THB', 'TO' => 'TOP', 'TT' => 'TTD', 'TN' => 'TND', 'TR' => 'TRY',
		'TM' => 'TMT', 'UG' => 'UGX', 'UA' => 'UAH', 'AE' => 'AED', 'UY' => 'UYU', 'UZ' => 'UZS', 'VU' => 'VUV', 'VE' => 'VEF', 'VN' => 'VND', 'YE' => 'YER',
		'ZM' => 'ZMK', 'ZW' => 'ZWD', 'AX' => 'EUR', 'AO' => 'AOA', 'AQ' => 'AQD', 'BA' => 'BAM', 'CD' => 'CDF', 'GH' => 'GHS', 'GG' => 'GGP', 'IM' => 'GBP',
		'LA' => 'LAK', 'MO' => 'MOP', 'ME' => 'EUR', 'PS' => 'JOD', 'BL' => 'EUR', 'SH' => 'GBP', 'MF' => 'ANG', 'PM' => 'EUR', 'RS' => 'RSD', 'USAF' => 'USD'
	);
	
	return $default_mappings;
	
	//Make sure each currency in the default mapping exists in Woocommerce
	/*if( !class_exists( 'WC_Countries' ) ) include_once(  WC()->plugin_path() . '/includes/class-wc-countries.php' );
	$wc_countries = new WC_Countries();
	$countries = $wc_countries->get_countries();
	$currencies = get_woocommerce_currencies();
	
	$mappings = array();
	foreach( $countries as $country_code => $country_name ){
		
		if( array_key_exists( $country_code, $default_mappings ) && array_key_exists( $default_mappings[$country_code], $currencies ) ){
			$mappings[$country_code] = $default_mappings[$country_code];
		} else {
			$mappings[$country_code] = '';
		}
		
	}
	
	return $mappings;*/
}