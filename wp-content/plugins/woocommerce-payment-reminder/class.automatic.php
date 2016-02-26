<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * WooCommerce_Payment_Reminder_Plugin_Automatic
 * Class for automatisation.
 * @since 2.0
 */

if ( ! class_exists( 'WooCommerce_Payment_Reminder_Plugin_Automatic' ) ) {

class WooCommerce_Payment_Reminder_Plugin_Automatic {
		
		private $parameters;
		private $enabled = false;
		
		function __construct()
		{	
			$this->parameters = get_option('woocommerce_payment_reminder_settings');
			
			if(!isset($this->parameters['enabled_automatisation']))
				$this->parameters['enabled_automatisation'] == 'no';
				
			if(isset($this->parameters['enabled']) and $this->parameters['enabled']=='yes' )
				$this->enabled = true;
			
			$this->hooks();
			
				
		} //__construct
		
		
		protected function hooks()
		{
			//include the new email class
			add_filter( 'woocommerce_email_classes', array($this, 'add_email_classes'));
			add_action( 'woopar_do_cron_hook',array($this,'trigger_cron') );
			
			add_action('init', array($this, 'init'));
	
		} // hooks
		
		public function init()
		{
			if($this->enabled)
			{
				if($this->parameters['enabled_automatisation'] == 'yes')
				{
					//for debug purpose
					if(isset($_GET['trigger_cron']))
					{
						$this->trigger_cron();
						die;
					}
						
					if ( ! wp_next_scheduled( 'woopar_do_cron_hook' ) ) {
						wp_schedule_event( time(), apply_filters('woopar_do_cron_timing', 'hourly'), 'woopar_do_cron_hook' );
					}	
				}
			}
		}
		
		
		public function add_email_classes($email_classes)
		{
			if(!isset($email_classes['WC_Email_Payment_Reminder']))
			{
				require( 'class-wc-email-payment-reminder.php' );
				$email_classes['WC_Email_Payment_Reminder'] = new WC_Email_Payment_Reminder();
			}
			return $email_classes;
		}
		
		public function trigger_cron()
		{
			//get the statuses
			$statuses = WooCommerce_Payment_Reminder_Plugin_Helpers::getActivatedStatuses();
			
			//get all the orders with thoses statuses
			$orders = $this->getOrders($statuses);
			
			foreach($orders as $o)
			{
				$this->handleOrder($o);
			}
			
		}
		
		private function handleOrder($post)
		{
			if($this->parameters['enabled_automatisation'] == 'yes')
			{
				$relances_passees = get_post_meta($post->ID, 'woopar_historique', true);
				if(!$relances_passees)
					$relances_passees = array();
					
				$nombre_relances = count($relances_passees);
				
				$date_commande = strtotime($post->post_date);
				$date_derniere_relance = false;
				$now = current_time('timestamp');
				$laps_premiere_relance = $this->parameters['automatisation_start'] * DAY_IN_SECONDS;
				$laps_relance = $this->parameters['automatisation_frequence'] * DAY_IN_SECONDS;
				$max_relances = $this->parameters['automatisation_number'];
				
				if($nombre_relances > 0)
					$date_derniere_relance = $relances_passees[count($relances_passees) - 1];
				
				if( //jamais relancé
					( ($nombre_relances == 0) and ($now - $laps_premiere_relance > $date_commande) )
					or
					//deja relancé
					( ($nombre_relances > 0) and ($date_derniere_relance < $now - $laps_relance) )
				)
				{
					//si le nombre de relances n'a pas été atteint
					if($nombre_relances < $max_relances)
					{
				
						//relancer
						$this->sendAutomaticReminder($post->ID);
						
						//enregistrer 
						$relances_passees[] = $now;
						update_post_meta($post->ID, 'woopar_historique', $relances_passees);
						//pour la liste
						update_post_meta($post->ID, '_wopar_reminder_sent', date('Y-m-d H:i:s', $now));
			
					}
					else
					{
						//on relancerait mais le nombre de relances a été atteint.
						//peut-être on change le statut.
						
						if($this->parameters['automatisation_change_status'] != 'no')
						{
							$new_status = $this->parameters['automatisation_change_status'];
							
							$message = sprintf(__('After %s unsuccessful automatic reminders, order status was automatically changed.', 'woopar'), $nombre_relances);
							
							$order = new WC_Order($post->ID);
							$order->update_status($new_status, $message);
						}
					
					}
				}
				
				
			}
		}
		
		private function sendAutomaticReminder($order_id)
		{
			$order = new WC_Order( $order_id );
			
			global $woocommerce;
			
			//calling it just to construct the classes... for hooking the woocommerce_email_before_order_table hook...
			$paiment_gateways = $woocommerce->payment_gateways->payment_gateways();
			
			$mailers = $woocommerce->mailer()->get_emails();
			$mailer = $mailers['WC_Email_Payment_Reminder'];
			
			$mailer->trigger($order_id, 'automatic');
			do_action( 'woopar_on_automatic_trigger_mail_action', $order );
		}
		
		private function getOrders($statuses)
		{
			if(function_exists('wc_get_order_statuses')) //WC 2.2+
			{
				foreach($statuses as $i=>$st)
					$statuses[$i] = 'wc-'.$st;
					
				$args = array(
					'posts_per_page' => -1,
					'post_type'   => 'shop_order',
					'post_status' => $statuses,
					'orderby' => 'post_date',
					'order' => 'ASC'
				);
			}
			else
			{
		
				$args = array(
					'posts_per_page' => -1,
					'post_type'   => 'shop_order',
					'post_status' => 'publish',
					'orderby' => 'post_date',
					'order' => 'ASC',
					'tax_query'=>array(
						array(

							 'taxonomy' =>'shop_order_status',
							 'field' => 'name',
							 'terms' => $statuses
						)
					)
				);
			}

			$orders_query = new WP_Query($args);
			$orders = $orders_query->get_posts();
			
			return $orders;
		}
		
	} // WooCommerce_Payment_Reminder_Plugin_Automatic
}

