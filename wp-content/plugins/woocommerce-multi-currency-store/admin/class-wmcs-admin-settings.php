<?php
/**
 * Plugin settings
 *
 * @package   Woocommerce Multi Currency Store
 * @author    Code Ninjas 
 * @link      http://codeninjas.co
 * @copyright 2014 Code Ninjas
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
 
class WMCS_Settings {

	var $tab = 'multi_currency';
	var $settings;

	public function __construct(){
		include_once( WMCS_DIR . 'includes/class-wmcs-exchange-api.php' );
		
		$this->settings = $this->init_settings();	
		
		add_filter( 'woocommerce_settings_tabs_array', array( $this, 'add_settings_tab' ), 30 );
		add_action( 'woocommerce_update_options_'.$this->tab, array( $this, 'save_settings' ) );
		add_action( 'woocommerce_sections_'.$this->tab, array( $this, 'output_tabs_sections' ) );
		add_action( 'woocommerce_settings_'.$this->tab, array( $this, 'output_tabs_settings' ) );
		
		//add_filter( 'woocommerce_settings_api_form_fields_flat_rate', array( $this, 'add_flat_rate_shipping_settings' ) );
	
		//add_filter( 'woocommerce_general_settings', array( $this, 'add_settings' ) );
		//Additonal Currencies Section
		add_action( 'woocommerce_admin_field_wmcs_store_currencies', array( $this, 'setting_wmcs_store_currencies_output' ) );
		if( version_compare( WOOCOMMERCE_VERSION, '2.4', '<' ) ){ 
			add_action( 'woocommerce_update_option_wmcs_store_currencies', array( $this, 'setting_wmcs_store_currencies_save' ) ); //deprecated in Woo 2.4
		} else {
			add_action( 'woocommerce_admin_settings_sanitize_option__wmcs_store_currencies', array( $this, 'setting_wmcs_store_currencies_save_2_4' ), 10, 3 );
		}		
		
	}
	
	function setting_wmcs_store_currencies_save_2_4( $value, $option, $raw_value ){
		$this->setting_wmcs_store_currencies_save( $option );
	}
	
	/**
	 * Return the defaults for each setting in id=>default pairs
	 *
	 * @since 	1.2.0
	 */
	public static function get_settings_defaults(){
		
		$defaults = array();
		foreach( self::init_settings() as $settings ){
			foreach( $settings as $setting ){
				if( isset( $setting['id'] ) && isset( $setting['default'] ) ){
					$defaults[$setting['id']] = $setting['default'];
				}
			}
		}
		
		return $defaults;
		
	}
	
	public function add_flat_rate_shipping_settings( $form_fields ){
	
		$flat_rate_cost_pos = 0;
		$count = 1;
		foreach($form_fields as $key => $setting){
			if($key == 'cost_per_order') $flat_rate_cost_pos = $count;
			$count++;
		}

		$form_fields = 	array_slice($form_fields, 0, $flat_rate_cost_pos, true) +
						array( 'wmcs_cost_per_order_currencies' => array( 'type' => 'wmcs_cost_per_order_currencies' ) ) + 
						array_slice($form_fields, $flat_rate_cost_pos, NULL, true);
		
		
		//pr($form_fields);
		
		return $form_fields;
	}
	
	public function add_settings_tab( $tabs ){
		$tabs[$this->tab] = 'Multi Currency';
		return $tabs;
	}
	
	public function save_settings(){
		if( !function_exists( 'woocommerce_update_options' ) ) include trailingslashit( WP_PLUGIN_DIR ) . 'woocommerce/admin/settings/settings-save.php';
		
		$current_section = ( empty( $_REQUEST['section'] ) ) ? key( $this->settings ) : sanitize_text_field( urldecode( $_REQUEST['section'] ) );
		woocommerce_update_options( $this->settings[ $current_section ] );
		
		//reschedule the cron
		if( isset( $_POST['wmcs_exchange_rate_schedule'] ) ){
			$timestamp = wp_next_scheduled( 'wmcs_cron_check_exchange_rates' );
			wp_unschedule_event( $timestamp, 'wmcs_cron_check_exchange_rates' );
			wp_schedule_event( time(), $_POST['wmcs_exchange_rate_schedule'], 'wmcs_cron_check_exchange_rates' ); 
		}		
		
		if( version_compare( WC_VERSION, '2.4', '>=' ) ){
			//delete variation product prices transients
			global $wpdb;
			$wpdb->query( $wpdb->prepare( "DELETE FROM $wpdb->options WHERE option_name LIKE '%s'", '_transient_wc_var_prices%' ) );
			$wpdb->query( $wpdb->prepare( "DELETE FROM $wpdb->options WHERE option_name LIKE '%s'", '_transient_timeout_wc_var_prices%' ) );
		}
		
		//if exchange rate source is set to an api, get the rates
		$api = new WMCS_Exchange_Api();
		$api->get_exchange_rates();
		
	}
	
	public function output_tabs_sections()
	{ 	
		global $woocommerce;
	
		reset($this->settings);
		$current_section = ( empty( $_REQUEST['section'] ) ) ? key( $this->settings ) : sanitize_text_field( urldecode( $_REQUEST['section'] ) );
		
		//output section links
		$admin_url = ( version_compare( $woocommerce->version, 2.1, '<' ) ) ? admin_url('admin.php?page=woocommerce_settings&tab='.$this->tab) : admin_url('admin.php?page=wc-settings&tab='.$this->tab);
		$section_links = array();
		foreach( $this->settings as $section => $settings ){
			$title = ucwords( str_replace( '_', ' ', $section ) );
			$current = ( $section == $current_section ) ? 'class="current"' : '';
			$section_links[] = '<a href="' . add_query_arg( 'section', $section, $admin_url ) . '"' . $current . '>' . esc_html( $title ) . '</a>';
		}
		echo '<ul class="subsubsub"><li>' . implode( ' | </li><li>', $section_links ) . '</li></ul><br class="clear" /><hr />';
	}
	
	public function output_tabs_settings(){
		reset($this->settings);
		$current_section = ( empty( $_REQUEST['section'] ) ) ? key( $this->settings ) : sanitize_text_field( urldecode( $_REQUEST['section'] ) );
		
		woocommerce_admin_fields( $this->settings[$current_section] );
	}
	
	public function init_settings(){
	
		$live_exchange_rates = get_option( 'wmcs_live_exchange_rates', array( ) );
		$last_checked = ( isset( $live_exchange_rates['last_checked'] ) && $live_exchange_rates['last_checked'] ) ? date( 'M jS, Y \a\t H:i', $live_exchange_rates['last_checked'] ) : 'Never';
		
		$settings = array();
		
		//General
		$settings['multi_currency_options'] = array(
			array( 'title' => __( 'General', 'woocommerce' ), 'type' => 'title','desc' => '', 'id' => 'wmcs_general_settings' ),
				
				array(
					'title'   => __( 'Enabled', 'woocommerce' ),
					'desc'    => __( 'Enable multiple currencies in your store', 'woocommerce' ),
					'id'      => 'wmcs_enabled',
					'default' => 'no',
					'type'    => 'checkbox'
				),
				
				array(
					'title'   => __( 'Enable GeoIp', 'woocommerce' ),
					'desc'    => __( 'Attempt to automatically determine customers country/currency', 'woocommerce' ),
					'id'      => 'wmcs_enable_geoip',
					'default' => 'yes',
					'type'    => 'checkbox'
				),
			
			array( 'type' => 'sectionend', 'id' => 'wmcs_general_settings' ),
			
			array( 'title' => __( 'Exchange Rates', 'woocommerce' ), 'type' => 'title','desc' => '', 'id' => 'wmcs_exchange_rate_settings' ),
			
			array(
				'title'    	=> 	__( 'Check rates using', 'woocommerce' ),
				'desc'     	=> 	__( '<strong>Last checked:</strong> '.$last_checked, 'woocommerce' ),
				'id'       	=> 	'wmcs_exchange_rate_source',
				'class'		=>	'',
				'css'      	=> 	'min-width:250px;',
				'default'  	=> 	'yahoo',
				'type'     	=> 	'select',
				'options'  	=> 	array(
					'yahoo'		=> __( 'Yahoo Finance API', 'woocommerce' ),
					'custom'	=> __( 'Use custom defined rates', 'woocommerce' )
				),
				'desc_tip' =>  'Set which exchange rates should be used when converting prices',
			),
			
			array(
				'title'    	=> 	__( 'Check rates every', 'woocommerce' ),
				'desc'     	=> 	__( 'If the Exchange rate source is set to check an API, define how often the rates should be checked.<br /><br />Checking the API very often may have an effect on your stores performance.', 'woocommerce' ),
				'id'       	=> 	'wmcs_exchange_rate_schedule',
				'class'		=>	'',
				'css'      	=> 	'min-width:200px;',
				'default'  	=> 	'daily',
				'type'     	=> 	'select',
				'options'  	=> 	array(
					'wmcs_daily'	=> __( 'Day', 'woocommerce' ),
					'wmcs_12hour'	=> __( '12 hours', 'woocommerce' ),
					'wmcs_6hour'	=> __( '6 hours', 'woocommerce' ),
					'wmcs_3hour'	=> __( '3 hours', 'woocommerce' ),
					'wmcs_hour'		=> __( 'Hour', 'woocommerce' ),
					'wmcs_30min'	=> __( '30 minutes', 'woocommerce' ),
					'wmcs_15min'	=> __( '15 minutes', 'woocommerce' ),
					'wmcs_10min'	=> __( '10 minutes', 'woocommerce' ),
					'wmcs_5min'		=> __( '5 minutes', 'woocommerce' ),
				),
				'desc_tip' =>  true,
			),
			
			array( 'type' => 'sectionend', 'id' => 'wmcs_exchange_rate_settings' ),
		);
		
		
		//Additional Currencies
		$settings['additional_currencies'] = array(
			array( 'title' => __( 'Additional Currencies', 'woocommerce' ), 'type' => 'title','desc' => '', 'id' => 'additional_currencies' ),
				
			array( 
				'id' 	=> '_wmcs_store_currencies', //ID needs to be different to form field name otherwise woo will just overwrite our custom save with the values posted.
				'type' 	=> 'wmcs_store_currencies',
				'title'	=> '',
				'desc'	=> __( 'Additional currencies that you want in your store. Customers whose local currency is defined below will be shown prices in their local currency instead of the base currency.<br />
								If a customers currency is not defined below, then prices will be shown in the base currency.', 'woocommerce' ),
				'tip'	=> ''
			),
			
			array( 'type' => 'sectionend', 'id' => 'additional_currencies' )
		);
		
		
		//Currency Mappings
		/*$mappings = wmcs_country_currency_mappings();
		$default_mappings = wmcs_default_country_currency_mappings();
		
		$currencies = get_woocommerce_currencies();
		foreach( $currencies as $code => $name ){
			$currencies[$code] = "$name (" . get_woocommerce_currency_symbol( $code ) . ")";
		}
		$currencies = array_merge( array('' => 'Store Base Currency (' . get_woocommerce_currency() . ')' ), $currencies );
		
		if( !class_exists( 'WC_Countries' ) ) include_once(  WC()->plugin_path() . '/includes/class-wc-countries.php' );
		$wc_countries = new WC_Countries();
		$countries = $wc_countries->get_countries();
		
		$settings['currency_mappings'] = array();
		$settings['currency_mappings'][] = array( 'title' => __( 'Country/Currency Mapping', 'woocommerce' ), 'type' => 'title','desc' => '', 'id' => 'currency_mappings' );
		
		foreach( $mappings as $country_code => $currency_code){
			
			$flag = '<img src="https://hotelimages.distributenetwork.net/TCF/H4U|11047/" />';
			
			$default_currency = $default_mappings[$country_code] != '' ? $default_mappings[$country_code] : 'Store base currency';
			
			$settings['currency_mappings'][] = array(
				'title'    	=> 	__( "{$countries[$country_code]} ({$country_code})", 'woocommerce' ),
				'id'       	=> 	"wmcs_country_currency_mappings[{$country_code}]",
				'class'		=>	'wmcs_chosen_select',
				'css'      	=> 	'',
				'type'     	=> 	'select',
				'default'  	=> 	$mappings[$country_code],
				'options'  	=> 	$currencies,
				'desc_tip'  =>  __( "Default Currency", 'woocommerce' ) . ": {$default_currency}",
			);
			
		}
		
		$settings['currency_mappings'][] = array( 'type' => 'sectionend', 'id' => 'currency_mappings' );
		*/		
				
		return $settings;
	}
	
	
	public function setting_wmcs_store_currencies_output( $values ){
		
		extract($values);
		
		$store_currencies = get_option( 'wmcs_store_currencies', array() );
		
		$currency_table_rows = '';
		foreach( $store_currencies as $currency ){
			$currency_table_rows .= $this->store_currencies_table_row( $currency );
		}
		
		include 'views/settings-store-currencies.phtml';
		
		?>
		<script type="text/javascript">
		
			jQuery('#wmcs_settings_add_currency').click(function(){
				jQuery('#wmcs_currencies').append('<?php echo trim( preg_replace( '/\r|\n/', '', $this->store_currencies_table_row() ) ); ?>'); //need to remove new lines otherwise get unterminated string literal error
				wmcs_init_chosen_select();
			});
			
			
		</script>
		<?php
	}
	
	private function store_currencies_table_row( $currency = array() ){
		
		$defaults = array(
			'currency_code' => '',
			'price_format' => '[currency_symbol][price]',
			'thousand_separator' => ',',
			'decimal_separator' => '.',
			'decimal_places' => '2',
			'rounding_type' => 'none',
			'rounding_to' => '2',
			'exchange_rate_type' => 'live',
			'exchange_rate_value' => '',
		);
		$currency = wp_parse_args( $currency, $defaults );
		
		$base_currency_code = get_option( 'woocommerce_currency' );
		$live_exchange_rates = get_option( 'wmcs_live_exchange_rates', array( ) );
		$currencies_live_rate = ( isset( $live_exchange_rates['rates'] ) && array_key_exists( $currency['currency_code'], $live_exchange_rates['rates'] ) ) ? $live_exchange_rates['rates'][$currency['currency_code']] : FALSE;
		
		//Currency select
		$currency_select_options = '';
		foreach ( get_woocommerce_currencies() as $code => $name ) {
			if( $code != $base_currency_code ){ //don't want base currency to be selectable
				$selected = ( $code == $currency['currency_code'] ) ? 'selected="selected"' : '';  
				$currency_select_options .= '<option value="'.$code.'" '.$selected.'>' . $name . ' (' . get_woocommerce_currency_symbol( $code ) . ')</option>';
			}
		}
		
		//round type
		$rounding_types = array( 'none', 'up', 'down' );
		$rounding_types_options = '';
		foreach( $rounding_types as $type ){
			$selected = ( $type == $currency['rounding_type'] ) ? 'selected="selected"' : '';  
			$rounding_types_options .= '<option value="'.$type.'" '.$selected.'>'.ucwords($type).'</option>';
		}
		$rounding_type_show_adds = ( $currency['rounding_type'] == $defaults['rounding_type'] ) ? 'display: none;' : '';
		
		//exchange rate type
		$exchange_type_options = '';
		foreach( array( 'live', 'custom' ) as $type ){
			$selected = ( $type == $currency['exchange_rate_type'] ) ? 'selected="selected"' : '';  
			$exchange_type_options .= '<option value="'.$type.'" '.$selected.'>Use '.ucwords($type).'</option>';
		}
		$exchange_type_show_adds = ( $currency['exchange_rate_type'] == $defaults['exchange_rate_type'] ) ? 'display: none;' : '';
		
		$return = '
		<tr>
			<td class="currency" width="20%">
				<select style="margin-bottom: 10px;" class="wmcs_chosen_select" name="wmcs_store_currencies[currency_code][]">'.$currency_select_options.'</select>';
				
		if( $currencies_live_rate !== FALSE ){
		$return .= '
				<div class="exchange-rate" style="margin-top: 10px;">
					<span class="rate">Live rate: 1 '.$base_currency_code.' = '.$currencies_live_rate.'</span>
				</div>';
		}
		//<select style="width: 100%;" name="wmcs_store_currencies[position][]">'.$currency_position_options.'</select>
		$return .= '
				<div class="submitbox" style="margin-top: 10px;">
					 <a class="submitdelete" onclick="jQuery(this).parent().parent().parent().remove();" style="vertical-align: bottom;">Remove Currency</a>
				</div>
			</td>
			
			<td class="currency_details">
				<table width="100%">					
					<tr>
						<td>Price Format <a class="tips" data-tip="How the price will be displayed.  Use the tags and any additonal text to create the price output. Defaults to [currency_symbol][price]">[?]</a></td>
						<td colspan="3">
							<input style="width:100%;" type="text" name="wmcs_store_currencies[price_format][]" value="'.$currency['price_format'].'" />
							<p class="description"><strong>Tags: </strong>[price], [currency_symbol], [currency_code]</p>
						</td>
						
					</tr>
					<tr>
						<td>Thousands Separator <a class="tips" data-tip="The thousands separator for the currency">[?]</a></td>
						<td><input style="width: 50px;" type="text" name="wmcs_store_currencies[thousand_separator][]" value="'.$currency['thousand_separator'].'" /></td>
						<td>Decimal Separator <a class="tips" data-tip="The decimal separator for the currency">[?]</a></td>
						<td><input style="width: 50px;" type="text" name="wmcs_store_currencies[decimal_separator][]" value="'.$currency['decimal_separator'].'" /></td>
					</tr>
					<tr>
						<td>Decimal Places <a class="tips" data-tip="The number of digits after the decimal separator">[?]</a></td>
						<td><input style="width: 50px;" type="number" min="0" name="wmcs_store_currencies[decimal_places][]" value="'.$currency['decimal_places'].'" /></td>
						<td>Rounding <a class="tips" data-tip="When using dynamic conversion of the currency, choose whether to round the converted value up or down to the nearest while number">[?]</a></td>
						<td>
							<select class="wmcs_rounding_type" name="wmcs_store_currencies[rounding_type][]" onchange="toggleAddOpts(this, jQuery(this).val());">'.$rounding_types_options.'</select>
							<span class="rounding_options" style="'.$rounding_type_show_adds.'">
								to <input type="number" min="0" style="width: 50px;" name="wmcs_store_currencies[rounding_to][]" value="'.$currency['rounding_to'].'" /> decimal places
							</dspan>
						</td>
					</tr>
					<tr>
						<td>Exchange Rate <a class="tips" data-tip="Custom exchange rate to use when converting prices instead of live exchange rates">[?]</a></td>
						<td>
							<select class="wmcs_exchange_rate_type" name="wmcs_store_currencies[exchange_rate_type][]" onchange="toggleAddOpts(this, jQuery(this).val());">'.$exchange_type_options.'</select>
							<div class="custom_exchange_rate" style="'.$exchange_type_show_adds.'">
								1 '.get_option('woocommerce_currency').' = <input type="text" style="width: 60px;" name="wmcs_store_currencies[exchange_rate_value][]" value="'.$currency['exchange_rate_value'].'" />
							</div>
						</td>
						<td></td>
						<td></td>
					</tr>
				</table>
			</td>
			
		</tr>';
		/*
		<select class="wmcs_exchange_rate_type" name="wmcs_store_currencies[exchange_rate_type][]" onchange="toggleAddOpts(this, jQuery(this).val());">'.$exchange_type_options.'</select>
		*/
		return $return;
		
	}
	
	public function setting_wmcs_store_currencies_save( $values ){
		
		if ( empty( $_POST ) ) return false;
		
		
		extract( $values );
		
		//sort currencies into their own array
		$sorted_currencies = array();
		if( isset( $_POST[ 'wmcs_store_currencies' ] ) ){
			foreach( $_POST[ 'wmcs_store_currencies' ] as $k => $v ){
				foreach( $v as $key => $value ){
					$sorted_currencies[$key][$k] = $value; //$sorted_currencies[0]['currency_code'] = 'GBP';
				}
			}
		}
		
		//put currency code as the key for easy searching and do some checks
		$insert = array();
		foreach( $sorted_currencies as $currency ){
			
			//Some validation
			$currency['decimal_places'] = (int)$currency['decimal_places'];
			$currency['rounding_to'] = (int)$currency['rounding_to'];
			$currency['exchange_rate_value'] = (float)$currency['exchange_rate_value'];
			$currency['price_format'] = !empty($currency['price_format']) ? $currency['price_format'] : '[currency_symbol][price]';
		
			$insert[ $currency['currency_code'] ] = $currency;
		}
		
		update_option( 'wmcs_store_currencies', $insert );
	
	}

}
return new WMCS_Settings();














