<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * WooCommerce_Payment_Reminder_Plugin_Back
 * Class for backend.
 * @since 1.1.1
 */

if ( ! class_exists( 'WooCommerce_Payment_Reminder_Plugin_Back' ) ) {

class WooCommerce_Payment_Reminder_Plugin_Back {
		
		private $enabled = false;
		
		function __construct()
		{	
			$parameters = get_option('woocommerce_payment_reminder_settings');
			
			if(isset($parameters['enabled']) and $parameters['enabled']=='yes' )
				$this->enabled = true;
			
			$this->hooks();
			
			
		} //__construct
		
		
		protected function hooks()
		{
			//include the new email class
			add_filter( 'woocommerce_email_classes', array($this, 'add_email_classes'));
				
			if($this->enabled)
			{
			
				//add an icon button to the orders list
				add_filter('woocommerce_admin_order_actions', array($this, 'order_actions'), 10, 2);
			
				//add the action of sending email while clicking the action button
				add_action('wp_ajax_woocommerce-payment-reminder', array($this, 'payment_reminder_send_email'));
			
				//add the email in the list of email you can send again in the order detail
				add_filter( 'woocommerce_resend_order_emails_available', array($this, 'add_email_to_resend_order_emails_list') );
			
				//add reminder to bulk actions
				add_action( 'admin_footer', array( $this, 'bulk_admin_footer' ), 10 );
				add_action( 'load-edit.php', array( $this, 'bulk_action' ) );
				add_action( 'admin_notices', array( $this, 'bulk_admin_notices' ) );
			}
			
			//css for button
			add_action( 'admin_init', array($this, 'css') );
			
			//link to configure in plugin list
			add_filter( 'plugin_action_links_'.dirname(plugin_basename(__FILE__)).'/woocommerce-payment-reminder.php', array($this,'action_links'), 10, 2 );
					
		} // hooks
		
		
		public function action_links( $links, $file )
		{
			global $woocommerce;
			$woocommerce_version = $woocommerce->version;
		
			if(substr($woocommerce_version, 0, 3) == '2.0')
			{
				array_unshift( $links, '<a href="' . admin_url( 'admin.php?page=woocommerce_settings&tab=email&section=WC_Email_Payment_Reminder' ) . '">' . __( 'Configure', 'woopar' ) . '</a>' );
			}
			else
			{
				array_unshift( $links, '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=email&section=wc_email_payment_reminder' ) . '">' . __( 'Configure', 'woopar' ) . '</a>' );
			}
			return $links;

		}
		
		public function bulk_admin_footer()
		{
			global $post_type;

			if ( 'shop_order' == $post_type ) {
				?>
				<script type="text/javascript">
				jQuery(function() {
					jQuery('<option>').val('send_reminder').text('<?php _e( 'Send reminder', 'woopar' )?>').appendTo("select[name='action']");
					jQuery('<option>').val('send_reminder').text('<?php _e( 'Send reminder', 'woopar' )?>').appendTo("select[name='action2']");

				});
				</script>
				<?php
			}
		}
		
		public function bulk_action()
		{
		
			global $pagenow;
			
			if ( 'edit.php' == $pagenow && isset($_REQUEST['post_type']) and 'shop_order' == $_REQUEST['post_type']  and isset($_REQUEST['post']) and isset($_REQUEST['action']) and isset($_REQUEST['action2']) and ($_REQUEST['action'] == 'send_reminder' or $_REQUEST['action2'] == 'send_reminder'))
			{
			
				$wp_list_table = _get_list_table( 'WP_Posts_List_Table' );
				$action = $wp_list_table->current_action();

				$changed = 0;

				$post_ids = array_map( 'absint', (array) $_REQUEST['post'] );

				foreach ( $post_ids as $post_id ) {
					$this->payment_reminder_send_email($post_id, false);
					$changed++;
				}

				$sendback = add_query_arg( array( 'send_reminder'=> 1, 'changed' => $changed ), wp_get_referer() );
				
				wp_safe_redirect( $sendback );
				exit();
			}
		}

		/**
		 * Show confirmation message that order status changed for number of orders
		 *
		 * @access public
		 * @return void
		 */
		public function bulk_admin_notices() {
			global $post_type, $pagenow;

			if ( isset( $_REQUEST['send_reminder'] ) ) {
				$number = isset( $_REQUEST['changed'] ) ? absint( $_REQUEST['changed'] ) : 0;

				if ( 'edit.php' == $pagenow && 'shop_order' == $post_type ) {
					$number = number_format_i18n( $number );
					
					if($number == 1)
						$message = __('Reminder sent.', 'woopar');
					else
						$message = sprintf(__('%s reminders sent.', 'woopar'), $number);
					echo '<div class="updated"><p>' . $message . '</p></div>';
				}
			}
		}
		
		/**
		 * Enqueue CSS and JS
		 */
		public function css()
		{
			global $pagenow;
			
			if($pagenow == 'edit.php' and isset($_GET['post_type']) and $_GET['post_type']=='shop_order')
			{
				wp_register_style('woopar', plugins_url('css/wopar.css', __FILE__));
				wp_enqueue_style('woopar');
			}

			if($pagenow == 'admin.php' and isset($_GET['page']) and ($_GET['page']=='wc-settings' or $_GET['page']=='woocommerce_settings')
				and isset($_GET['tab']) and $_GET['tab']=='email'
				and isset($_GET['section']) and ($_GET['section']=='wc_email_payment_reminder' or $_GET['section']=='WC_Email_Payment_Reminder') )
			{
				wp_register_style('wopar_settings', plugins_url('css/settings.css', __FILE__));
				wp_enqueue_style('wopar_settings');
				
				wp_enqueue_script( 'wopar_settings_js', plugins_url('js/woopar.js', __FILE__), array(), '1.0.0', true );
			}
		}
		
		/**
		 * Include the new email class
		 * @param array $email_classes Array of email's classes loaded on WooCommerce.
		 * @return array The array with the WC_Email_Payment_Reminder class added
		 */
		public function add_email_classes($email_classes)
		{
			if(!isset($email_classes['WC_Email_Payment_Reminder']))
			{
				require( 'class-wc-email-payment-reminder.php' );
				$email_classes['WC_Email_Payment_Reminder'] = new WC_Email_Payment_Reminder();
			}
			return $email_classes;
		}
		
		/**
		 * Add the email in the list of email you can send again in the order detail
		 * @param array $email Array of availables emails to re-send.
		 * @return array The array with our action added
		 */
		public function add_email_to_resend_order_emails_list($emails)
		{
			$emails[] = 'payment_reminder';
			return $emails;
		}
		
		/**
		 * Add an icon button to the orders list
		 * @param array $actions Array of availables actions on each order in the order list panel.
		 * @param object $the_order The order to add the action button
		 * @return array The $actions array with our action added
		 */
		public function order_actions($actions, $the_order)
		{
		
			$statuses = WooCommerce_Payment_Reminder_Plugin_Helpers::getActivatedStatuses();
			
			if(!$statuses)
			{	
				//si settings non enregistrés : on-hold
				$statuses = apply_filters('wopar_button_status_filter', array( 'on-hold' ));
			}
		
			if ( in_array( $the_order->status, $statuses) )
			{
				global $woocommerce;
				
				$label = __('Send reminder', 'woopar');
				
				$date = get_post_meta($the_order->id, '_wopar_reminder_sent', true);
				if(!$date) //1.0 compatibility
				{
					$date = get_post_meta($the_order->id, 'wopar_reminder_sent', true);
					if($date)
					{
						delete_post_meta($the_order->id, 'wopar_reminder_sent');
						update_post_meta($the_order->id, '_wopar_reminder_sent', $date);
					}
				}
				
				if($date)
					$label = apply_filters('wopar_date_tooltip_text_filter', __('Reminder sent on', 'woopar').' '.date(__('Y-m-d', 'woopar'), strtotime($date)).' '.__('at', 'woopar').' '.date(__('H:i:s', 'woopar'), strtotime($date)) );
		
				$actions[] = array('image_url' => $woocommerce->plugin_url().'/assets/images/icons/reload.png',
					'name' => $label,
					'action' => 'woopar',
					'url' 		=> wp_nonce_url( admin_url( 'admin-ajax.php?action=woocommerce-payment-reminder&order_id=' . $the_order->id ), 'woocommerce-payment-reminder' ) 
				);
			}
			return $actions;
		}
		
		
		
		
		/**
		 * Action to send the email
		 */
		public function payment_reminder_send_email($order_id = null, $redirect = true) {

			if ( !is_admin() ) die;
			if ( !current_user_can(apply_filters('wopar_admin_capabilities_filter', 'edit_shop_orders')) ) wp_die( __( 'You do not have sufficient permissions to access this page.', 'woocommerce' ) );
			
			if(!isset($order_id))
			{
				if ( !check_admin_referer('woocommerce-payment-reminder')) wp_die( __( 'Wrong referer.', 'woocommerce' ) );
			}
			
			if(!isset($order_id) or !$order_id )
			{
				$order_id = isset($_GET['order_id']) && (int) $_GET['order_id'] ? (int) $_GET['order_id'] : '';
			}
			if (!$order_id) die;
			
			$order = new WC_Order( $order_id );
			
			global $woocommerce;
			
			//calling it just to construct the classes... for hooking the woocommerce_email_before_order_table hook...
			$paiment_gateways = $woocommerce->payment_gateways->payment_gateways();
			
			$mailers = $woocommerce->mailer()->get_emails();
			$mailer = $mailers['WC_Email_Payment_Reminder'];
			
			do_action( 'woocommerce_before_resend_order_emails', $order );
			$mailer->trigger($order_id);
			do_action( 'woocommerce_after_resend_order_email', $order, 'payment_reminder' );
			
			if($redirect)
			{
				$sendback = add_query_arg( array( 'send_reminder'=> 1, 'changed' => 1 ), wp_get_referer()  );
				wp_safe_redirect( $sendback );
				exit();
			}
		}
		
	} // WooCommerce_Payment_Reminder_Plugin_Back
}

