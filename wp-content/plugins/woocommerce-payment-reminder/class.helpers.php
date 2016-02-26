<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * WooCommerce_Payment_Reminder_Plugin_Helpers
 * Helpers.
 * @since 2.0
 */

if ( ! class_exists( 'WooCommerce_Payment_Reminder_Plugin_Helpers' ) ) {

class WooCommerce_Payment_Reminder_Plugin_Helpers {
		
		
		static public function getActivatedStatuses()
		{
			$options = get_option('woocommerce_payment_reminder_settings');
			
			$statuses = array();
			if($options)
			{
				$options_status = array();
			
				foreach($options as $key=>$option)
				{
					if(substr($key, 0, 13) == 'status_wopar_' and $option == 'yes')
					{
						$statuses[] = substr($key, 13);
					}
				}
			}
			
			if(!empty($statuses))
				return $statuses;
			else
				return false;
		}
		
		
		
	} // WooCommerce_Payment_Reminder_Plugin_Helpers
}