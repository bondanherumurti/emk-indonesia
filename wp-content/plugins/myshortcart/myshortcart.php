<?php

/*
Plugin Name: MyShortCart Payment Gateway
Plugin URI: http://myshortcart.com
Description: MyShortCart Payment Gateway plugin extentions for woocommerce and Wordpress version 3.5.1
Version: 1.1
Author: DOKU MyShortCart
Author URI: http://www.myshortcart.com
 
	License: GNU General Public License v3.0
	License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/
 
//database
function install() 
{
	global $wpdb;
	global $db_version;
	$db_version = "1.0";
 	$table_name = $wpdb->prefix . "myshortcart";
	$sql = "
		CREATE TABLE $table_name (
			trx_id int( 11 ) NOT NULL AUTO_INCREMENT,
			ip_address VARCHAR( 16 ) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
			process_type VARCHAR( 15 ) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
			process_datetime DATETIME NULL,
			payment_datetime DATETIME NULL,
			transidmerchant VARCHAR( 30 ) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
			msc_transidmerchant VARCHAR( 30 ) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
			amount DECIMAL( 20,2 ) NOT NULL DEFAULT '0',
			status_code VARCHAR( 4 ) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
			result_msg VARCHAR( 20 ) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
			check_flag INT( 1 ) COLLATE utf8_unicode_ci NOT NULL DEFAULT 0,
			reversal INT( 1 ) COLLATE utf8_unicode_ci NOT NULL DEFAULT 0,
			payment_channel VARCHAR( 15 ) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
			payment_code VARCHAR( 20 ) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
			words VARCHAR( 200 ) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
			extra_info TEXT COLLATE utf8_unicode_ci,
			message TEXT COLLATE utf8_unicode_ci,  
			PRIMARY KEY (trx_id)
		) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1
	";

	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	dbDelta($sql);

	add_option('myshortcart_db_version', $db_version);
}

function uninstall() 
{
	delete_option('myshortcart_db_version');
	global $wpdb;
	$table_name = $wpdb->prefix . "myshortcart";
	$wpdb->query("DROP TABLE IF EXISTS $table_name");
}

register_activation_hook( __FILE__, 'install');
register_uninstall_hook(  __FILE__, 'uninstall');

add_action('plugins_loaded', 'woocommerce_gateway_myshortcart_init', 0);

function woocommerce_gateway_myshortcart_init() 
{
	
		if ( !class_exists( 'WC_Payment_Gateway' ) ) return;
	 
		/**
		 * Localisation
		 */
		load_plugin_textdomain('wc-gateway-name', false, dirname( plugin_basename( __FILE__ ) ) . '/languages');
		
		/**
		 * Gateway class
		 */
		class WC_MyShortCart_Gateway extends WC_Payment_Gateway 
		{
				public function __construct() 
				{				
						$this->id = 'myshortcart';
						$this->ip_range = "103.10.128.";
						$this->method_title = 'MyShortCart';
						$this->has_fields = true;     // false

						$this->init_form_fields();
						$this->init_settings();
						
						$this->title       = $this->settings['name'];
						$this->description = 'Kartu Kredit <strong>Visa</strong> atau <strong>Mastercard</strong></br></br><img style="width:90px;" src="/wp-content/uploads/2015/12/emk-125x50-visalogo.png"/><img style="width:90px;" src="/wp-content/uploads/2015/12/emk-125x50-mastercardlogo.png"/></br></br>Pembayaran ini diproses oleh DOKU MyShortCart. DOKU adalah perusahaan penyedia pembayaran online terbesar di Indonesia yang membantu EMK Indonesia memproses pembayaran online secara aman dan tersertifikasi.';
						
						$this->store_id    = $this->settings['store_id'];
						$this->shared_key  = $this->settings['shared_key'];
						$this->prefixid    = $this->settings['prefix'];

						$pattern = "/([^a-zA-Z0-9]+)/";
						$result  = preg_match($pattern, $this->prefixid, $matches, PREG_OFFSET_CAPTURE);
						
						/*add_action('init', array(&$this, 'check_myshortcart_response'));*/
						add_action('valid_myshortcart_request', array(&$this, 'sucessfull_request'));	
						add_action('woocommerce_receipt_myshortcart', array(&$this, 'receipt_page'));
						
						if ( version_compare( WOOCOMMERCE_VERSION, '2.0.0', '>=' ) ) 
						{
								add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( &$this, 'process_admin_options' ) );
						} 
						else 
						{
								add_action( 'woocommerce_update_options_payment_gateways', array( &$this, 'process_admin_options' ) );
						}
						
						add_action( 'woocommerce_api_wc_myshortcart_gateway', array( &$this, 'myshortcart_callback' ) );
				}
				
			/**
			 * Initialisation form for Gateway Settings
			 */
				function init_form_fields() 
				{
					
					$this->form_fields = array(
							'enabled' => array(
									'title' => __( 'Enable/Disable', 'woocommerce' ),
									'type' => 'checkbox',
									'label' => __( 'Enable MyShortCart Payment Gateway', 'woocommerce' ),
									'default' => 'yes'
							),
							'store_id' => array(
									'title' => __( 'Store ID', 'woocommerce' ),
									'type' => 'text',
									'description' => __( 'Input Store ID get from MyShortCart Back Office.', 'woocommerce' ),
									'default' => '',
									'desc_tip' => true,
							),
							'shared_key' => array(
									'title' => __( 'Shared Key', 'woocommerce' ),
									'type' => 'text',
									'description' => __( 'Input Shared Key get from MyShortCart Back Office.', 'woocommerce' ),
									'default' => '',
									'desc_tip' => true,
							),
							'prefix' => array(
									'title' => __('Prefix : ', 'woocommerce'),
									'type' => 'text',
									'description' => __('Input only 4 characters or numbers, no symbols.', 'woocommerce'),
									'default' => '',
									'desc_tip' => true,
							),
							'name' => array(
									'title' => __('Payment Name : ', 'woocommerce'),
									'type' => 'text',
									'description' => __('Payment name to be displayed when checkout.', 'woocommerce'),
									'default' => 'Credit Card, ATM Transfer and DOKU Wallet via MyShortCart',
									'desc_tip' => true,
							),						
					);
					
				}
			
				public function admin_options() 
				{
						echo '<h2>'.__('MyShortCart Payment Gateway', 'woocommerce').'</h2>';
						echo '<p>' .__('MyShortCart is an online payment that can process many kind of payment method, include Credit Card, ATM Transfer and DOKU Wallet.<br>
														Check us at <a href="http://www.myshortcart.com">http://www.myshortcart.com</a>', 'woocommerce').'</p>';
						
						echo "<h3>MyShortCart Parameter</h3><br>\r\n";
						
						echo '<table class="form-table">';
						$this->generate_settings_html();
						echo '</table>';
						
						// URL                             
						$myserverpath = explode ( "/", $_SERVER['PHP_SELF'] );
						if ( $myserverpath[1] <> 'admin' && $myserverpath[1] <> 'wp-admin' ) 
						{
								$serverpath = '/' . $myserverpath[1];    
						}
						else
						{
								$serverpath = '';
						}
						
						if((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443)
						{
								$myserverprotocol = "https";
						}
						else
						{
								$myserverprotocol = "http";    
						}
						
						$myservername = $_SERVER['SERVER_NAME'] . $serverpath;			
										
						$mainurl =  $myserverprotocol.'://'.$myservername;
						
						echo "<h3>URL to put at MyShortCart Server</h3><br>\r\n";
						echo "<table>\r\n";
						echo "<tr><td width=\"100\">Verify URL</td><td width=\"3\">:</td><td>$mainurl/?wc-api=wc_myshortcart_gateway&task=verify</td></tr>\r\n";
						echo "<tr><td>Notify URL</td><td>:</td><td>$mainurl/?wc-api=wc_myshortcart_gateway&task=notify</td></tr>\r\n";
						echo "<tr><td>Redirect URL</td><td>:</td><td>$mainurl/?wc-api=wc_myshortcart_gateway&task=redirect</td></tr>\r\n";
						echo "</table>";
						
				}
			
				/**
				* Generate form
				*
				* @param mixed $order_id
				* @return string
				*/
			
				public function generate_myshortcart_form($order_id) 
				{
					
						global $woocommerce;
						global $wpdb;
						static $basket;
		
						$order = new WC_Order($order_id);
						$counter = 0;
		
						foreach($order->get_items() as $item) 
						{
								$BASKET = $basket.$item['name'].','.$order->get_item_subtotal($item).','.$item['qty'].','.$order->get_line_subtotal($item).';';
						}
						
						$BASKET = "";
						
						// Order Items
						if( sizeof( $order->get_items() ) > 0 )
						{
								foreach( $order->get_items() as $item )
								{							
										$BASKET .= $item['name'] . "," . number_format($order->get_item_subtotal($item), 2, '.', '') . "," . $item['qty'] . "," . number_format($order->get_item_subtotal($item)*$item['qty'], 2, '.', '') . ";";
								}
						}
						
						// Shipping Fee
						if( $order->order_shipping > 0 )
						{
								$BASKET .= "Shipping Fee," . number_format($order->order_shipping, 2, '.', '') . ",1," . number_format($order->order_shipping, 2, '.', '') . ";";
						}					
						
						// Tax
						if( $order->get_total_tax() > 0 )
						{
								$BASKET .= "Tax," . number_format($order->get_total_tax(), 2, '.', '') . ",1," . number_format($order->get_total_tax(), 2, '.', '') . ";";
						}
						
						// Coupon
						$coupun_items = WC()->cart->coupon_discount_amounts;
						if ( !empty($coupun_items) )
						{
								$coupon_total = 0;
								foreach ($coupun_items as $coupon)
								{
									$coupon_total -= $coupon;
								}
								$BASKET .= "Coupon," . number_format($coupon_total, 2, '.', '') . ",1," . number_format($coupon_total, 2, '.', '') . ";";
						}
			
						// Fees
						if ( sizeof( $order->get_fees() ) > 0 )
						{
								$fee_counter = 0;
								foreach ( $order->get_fees() as $item )
								{
										$fee_counter++;
										$BASKET .= "Fee Item," . number_format($item['line_total'], 2, '.', '') . ",1," . number_format($item['line_total'], 2, '.', '') . ";";																		
								}
						}
				
						$BASKET = preg_replace("/([^a-zA-Z0-9.\-,=:;&% ]+)/", " ", $BASKET);
												
						// URL                             
						$myserverpath = explode ( "/", $_SERVER['PHP_SELF'] );
						if ( $myserverpath[1] <> 'admin' ) 
						{
								$serverpath = '/' . $myserverpath[1];    
						}
						else
						{
								$serverpath = '';
						}
						
						if((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443)
						{
								$myserverprotocol = "https";
						}
						else
						{
								$myserverprotocol = "http";    
						}
						
						$myservername = $_SERVER['SERVER_NAME'] . $serverpath;			
										
						$mainurl =  $myserverprotocol.'://'.$myservername;
						
						$MALL_ID             = trim($this->store_id);
						$SHARED_KEY          = trim($this->shared_key);
						$PREFIX              = trim($this->prefixid);
						$URL                 = "https://apps.myshortcart.com/payment/request-payment/";					
						$CURRENCY            = 360;
						$TRANSIDMERCHANT     = $order_id;
						$MSC_TRANSIDMERCHANT = $PREFIX . "_" . $TRANSIDMERCHANT;
						$NAME                = trim($order->billing_first_name . " " . $order->billing_last_name);
						$EMAIL               = trim($order->billing_email);
						$ADDRESS             = trim($order->billing_address_1 . " " . $order->billing_address_2);
						//$CITY                = trim($order->billing_city);
						$ZIPCODE             = trim($order->billing_postcode);
						//$STATE               = trim($order->billing_state);
						$REQUEST_DATETIME    = date("YmdHis");
						$IP_ADDRESS          = $this->getMyipaddress();
						$PROCESS_DATETIME    = date("Y-m-d H:i:s");
						$PROCESS_TYPE        = "REQUEST";
						$AMOUNT              = number_format($order->order_total, 2, '.', '');
						$PHONE               = trim($order->billing_phone);
						$WORDS               = sha1(trim($AMOUNT).
																				trim($SHARED_KEY).
																				trim($MSC_TRANSIDMERCHANT));

						$myshortcart_args = array(
							'BASKET'          => $BASKET,
							'STOREID'         => $MALL_ID,
							'TRANSIDMERCHANT' => $MSC_TRANSIDMERCHANT,
							'AMOUNT'          => $AMOUNT,
							'URL'             => $mainurl,
							'WORDS'           => $WORDS,
							'CNAME'           => $NAME,
							'CEMAIL'          => $EMAIL,
							'CWPHONE'         => $PHONE,
							'CHPHONE'         => $PHONE,
							'CMPHONE'         => $PHONE,
							'CCAPHONE'        => $PHONE,        
							'CADDRESS'        => $ADDRESS,
							'CCITY'           => $CITY,
							'CSTATE'          => $STATE,
							'CZIPCODE'        => $ZIPCODE,
							'CCOUNTRY'        => 360,
							'SADDRESS'        => $ADDRESS,
							'SCITY'           => $CITY,
							'SSTATE'          => $STATE,
							'SZIPCODE'        => $ZIPCODE,
							'SCOUNTRY'        => 360,
							'BIRTHDATE'       => ""
						);

						$trx['ip_address']          = $IP_ADDRESS;
						$trx['process_type']        = $PROCESS_TYPE;
						$trx['process_datetime']    = $PROCESS_DATETIME;
						$trx['transidmerchant']     = $TRANSIDMERCHANT;
						$trx['msc_transidmerchant'] = $MSC_TRANSIDMERCHANT;
						$trx['amount']              = $AMOUNT;
						$trx['words']               = $WORDS;
						$trx['message']             = "Transaction request start";

						# Insert transaction request to table myshortcart
						$this->add_myshortcart($trx);					
						
						// Form
						$myshortcart_args_array = array();
						foreach($myshortcart_args as $key => $value)
						{
								$myshortcart_args_array[] = "<input type='hidden' name='$key' value='$value' />";
						}
						
						return '<form action="'.$URL.'" method="post" id="myshortcart_payment_form">'.
										implode(" \r\n", $myshortcart_args_array).
										'<input type="submit" class="button-alt" id="submit_myshortcart_payment_form" value="'.__('Pay via MyShortCart', 'woocommerce').'" />
										<!--
										<a class="button cancel" href="'.$order->get_cancel_order_url().'">'.__('Cancel order &amp; restore cart', 'woocommerce').'</a>
										-->
										
										<script type="text/javascript">
										jQuery(function(){
										jQuery("body").block(
										{
												message: "<img src=\"'.$woocommerce->plugin_url().'/assets/images/ajax-loader.gif\" alt=\"Redirecting...\" style=\"float:left; margin-right: 10px;\" />'.__('Thank you for your order. We are now redirecting you to MyShortcart to make payment.', 'woocommerce').'",
												overlayCSS:
										{
										background: "#fff",
										opacity: 0.6
										},
										css: {
													padding:        20,
													textAlign:      "center",
													color:          "#555",
													border:         "3px solid #aaa",
													backgroundColor:"#fff",
													cursor:         "wait",
													lineHeight:     "32px"
												}
										});
										jQuery("#submit_myshortcart_payment_form").click();});
										</script>
										</form>';
			 
				}
			
				public function process_payment($order_id)
				{
						global $woocommerce;
						$order = new WC_Order($order_id);
						return array(
								'result' => 'success',
								'redirect' => add_query_arg('order', $order->id, add_query_arg('key', $order->order_key, get_permalink(woocommerce_get_page_id('pay'))))
						);	
				}
			
				public function receipt_page($order)
				{
						echo $this->generate_myshortcart_form($order);
				}

				function getMyServerConfig()
				{
						$STORE_ID   = $this->settings['store_id'];
						$SHARED_KEY = $this->settings['shared_key'];
						$PREFIX     = $this->settings['prefix'];
						
						$config  = array( "MALL_ID"      => $STORE_ID, 
															"SHARED_KEY"   => $SHARED_KEY,
															"PREFIX"       => $PREFIX);  
									
						return $config;
				}
				
				private function getMyipaddress()    
				{
					if (!empty($_SERVER['HTTP_CLIENT_IP']))
					{
						$ip=$_SERVER['HTTP_CLIENT_IP'];
					}
					elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))
					{
						$ip=$_SERVER['HTTP_X_FORWARDED_FOR'];
					}
					else
					{
						$ip=$_SERVER['REMOTE_ADDR'];
					}
				
					return $ip;
				} 

				function checkMyTrx($trx)
				{
						global $wpdb;
					
						$wpdb->get_results("SELECT * FROM ".$wpdb->prefix."myshortcart" .
															 " WHERE process_type = 'REQUEST'" .
															 " AND transidmerchant = '" . $trx['transidmerchant'] . "'" .
															 " AND amount = '". $trx['amount'] . "'" );        
															
						return $wpdb->num_rows;
				}
								
				private function add_myshortcart($datainsert) 
				{
						global $wpdb;
						
						$SQL = "";
						
						foreach ( $datainsert as $field_name=>$field_data )
						{
								$SQL .= " $field_name = '$field_data',";
						}
						$SQL = substr( $SQL, 0, -1 );
				
						$wpdb->query("INSERT INTO ".$wpdb->prefix."myshortcart SET $SQL");
				}

				function myshortcart_callback()
				{
						require_once(dirname(__FILE__) . "/myshortcart.pages.inc");
						die;
				}
				
		}
		
		/**
		* Add the Gateway to WooCommerce
		**/
		function woocommerce_add_gateway_MyShortCart_gateway($methods)
		{
				$methods[] = 'WC_MyShortCart_Gateway';
				return $methods;
		}
		
		add_filter('woocommerce_payment_gateways', 'woocommerce_add_gateway_MyShortCart_gateway' );
		
}

?>
