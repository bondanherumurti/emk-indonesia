<?php

/**
 * Plugin Name: Reminder for WooCommerce Unpaid Orders
 * Description: Send manually or automatically a reminder email to clients who finished their order (by cheque or bacs...) but you never received the payment.
 * Version: 2.6
 * Author: MB Création
 * Author URI: http://www.mbcreation.com
 * License: http://codecanyon.net/licenses/regular_extended
 *
 */

// Required Classes
require_once('class.helpers.php');
require_once('class.admin.php');
require_once('class.automatic.php');

// Loader
function WooCommerce_Payment_Reminder_Plugin_Loader(){

	if(class_exists('Woocommerce')) {
		
		load_plugin_textdomain('woopar', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/');

		if( is_admin() and current_user_can(apply_filters('wopar_admin_capabilities_filter', 'edit_shop_orders')) )
			$GLOBALS['WooCommerce_Payment_Reminder_Plugin_Back'] = new WooCommerce_Payment_Reminder_Plugin_Back();
			
		$GLOBALS['WooCommerce_Payment_Reminder_Plugin_Automatic'] = new WooCommerce_Payment_Reminder_Plugin_Automatic();
	
	}
	
}

add_action( 'plugins_loaded' , 'WooCommerce_Payment_Reminder_Plugin_Loader');


//Autoupdater
add_action( 'admin_init', 'mbc_woopar_autoupdate' ); 

function mbc_woopar_autoupdate()
{
	if(!class_exists('WPMBC_AutoUpdate'))
		require_once ( dirname(__FILE__).'/wp_autoupdate.php' );
	$plugin_data = get_plugin_data( __FILE__ );
	$plugin_current_version = $plugin_data['Version'];
	$plugin_remote_path = 'http://www.mbcreation.com/plugin/woocommerce-payment-reminder/';	
	$plugin_slug = plugin_basename( __FILE__ );
	new WPMBC_AutoUpdate ( $plugin_current_version, $plugin_remote_path, $plugin_slug );	
}
