<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Customer Payment Reminder Email
 *
 * An email to remind a client to send its payment
 *
 * @class 		WC_Email_Payment_Reminder
 * @version		1.1.1
 * Version: 1.1
 * Author: MB Création
 * Author URI: http://www.mbcreation.net
 * @extends 	WC_Email
 */
class WC_Email_Payment_Reminder extends WC_Email {

	/**
	 * Constructor
	 */

	private $order_statuses;
	 
	function __construct() {

		$this->id 				= 'payment_reminder';
		$this->title 			= __( 'Payment reminder', 'woopar' );
		$this->description		= __( 'This is an email sent to the customer who finished their order but didnt pay for it.', 'woopar' );

		$this->heading 			= __( 'Your order is still pending', 'woopar' );
		$this->subject      	= __( 'Your order is still pending', 'woopar' );
		
		$this->content      	= $this->get_option( 'content', __( 'You put an order on {order_date}, you indicate youll pay it by {order_payment_method}, but we are still waiting for your payment !', 'woopar' ) );

		
		//take the template payment-reminder.php if exists in the theme, else use the note template
		// final template can be overrided by filters wopar_template_html_filter / wopar_template_plain_filter
		
		global $woocommerce;

		if(defined('WC_TEMPLATE_PATH'))
			$template_path = WC_TEMPLATE_PATH;
		else
		{
			global $woocommerce;
			$template_path = $woocommerce->template_url;
		}

		if(locate_template( $template_path.apply_filters('wopar_template_html_filter', 'emails/payment-reminder.php' )))
			$this->template_html 	= apply_filters('wopar_template_html_filter', 'emails/payment-reminder.php');
		else
			$this->template_html 	= 'emails/customer-note.php';
		
		if(locate_template( $template_path.apply_filters('wopar_template_plain_filter', 'emails/plain/payment-reminder.php') ))
			$this->template_plain 	= apply_filters('wopar_template_plain_filter', 'emails/plain/payment-reminder.php');
		else
			$this->template_plain 	= 'emails/plain/customer-note.php';


		$this->order_statuses = $this->getOrderStatuses();
		
		// Call parent constructor
		parent::__construct();
	}


	public function getOrderStatuses()
	{
		if(function_exists('wc_get_order_statuses'))
		{
			$statuses2 = wc_get_order_statuses();
			
			$statuses = array();
			foreach($statuses2 as $i=>$val)
			{
				$statuses[str_replace('wc-', '', $i)] = $val;
			}
	   	}
	   	else
	   	{
	   		$statuses = array();
	   		$stats = get_terms( 'shop_order_status', 'orderby=id&hide_empty=0' );
	   		foreach($stats as $status)
	   		{
	   			$statuses[$status->slug] = $status->name;
	   		}
	   	}

	   	return $statuses;
	}
	
	/**
	 * get_content_html function.
	 *
	 * @access public
	 * @attribute $order_id
	 * @return null
	 */
	function trigger( $order_id, $method = 'manual' ) {
		global $woocommerce;


		if ( $order_id ) {
			$this->object 		= new WC_Order( $order_id );
			$this->recipient	= $this->object->billing_email;
		
			if ( ! $this->is_enabled() || ! $this->get_recipient() )
				return;
				
			//use filter wopar_keys_to_find_and_replace_filter to add new $key => $value replacements
			
			$keys_to_find_and_replace = array(
				'{order_date}' => date_i18n( woocommerce_date_format(), strtotime( $this->object->order_date ) ), 
				'{order_number}' => $this->object->get_order_number(), 
				'{order_payment_method}' => apply_filters('wopar_payment_method_title_filter', $this->object->payment_method_title),
				'{first_name}' => $this->object->billing_first_name,
				'{last_name}' => $this->object->billing_last_name,
				'{payment_url}' => $this->object->get_checkout_payment_url()
			);
			
			$keys_to_find_and_replace = apply_filters('wopar_keys_to_find_and_replace_filter', $keys_to_find_and_replace, $this);
			
			$this->find = array();
			$this->replace = array();
			foreach($keys_to_find_and_replace as $key=>$replacement)
			{
				$this->find[] =  $key;
				$this->replace[] = $replacement;
			}
			
			$order = new WC_Order( $order_id );
			$date = date('Y-m-d H:i:s', current_time('timestamp'));
			update_post_meta( $order_id, '_wopar_reminder_sent', $date );
			
			if($method=='manual')
			{
				$message = apply_filters('wopar_admin_order_note_text_filter', __('Sending reminder email (manual)', 'woopar').' ('.date(__('Y-m-d', 'woopar'), strtotime($date)).' '.__('at', 'woopar').' '.date(__('H:i:s', 'woopar'), strtotime($date)).')');
			}
			else
			{
				$message = apply_filters('wopar_admin_order_note_text_filter_automatic', __('Sending reminder email (automatic)', 'woopar').' ('.date(__('Y-m-d', 'woopar'), strtotime($date)).' '.__('at', 'woopar').' '.date(__('H:i:s', 'woopar'), strtotime($date)).')');
			}
			
			$order->add_order_note($message);
			
			do_action('wopar_on_reminder_send_action', $order);
			do_action('wopar_on_reminder_send_'.$method.'_action', $order);
			
		}

		$this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
		
	}
	
	public function get_headers() {
		$headers = parent::get_headers();
		
		$bcc = $this->get_option('bcc');
		
		$bcc = trim($bcc);
		
		if( $bcc != '')
		{
			$headers .= 'Bcc: ' . $bcc . "\r\n";
		}
		return $headers;
	}
	
	/**
	 * get_content_html function.
	 *
	 * @access public
	 * @return string
	 */
	function get_content_html() {
		ob_start();
		
		//in the mail template, use $customer_note to get the message defined in back-end
		
		woocommerce_get_template( $this->template_html, array(
			'order' 		=> $this->object,
			'email_heading' => $this->get_heading(),
			'customer_note' => $this->content,
			'sent_to_admin' => false,
			'plain_text' => true
		) );
		$email = ob_get_clean();
		
		$email = str_replace( $this->find, $this->replace, $email);
		
		return $email;
	}

	/**
	 * get_content_plain function.
	 *
	 * @access public
	 * @return string
	 */
	function get_content_plain() {
		ob_start();
		
		//in the mail template, use $customer_note to get the message defined in back-end
		
		woocommerce_get_template( $this->template_plain, array(
			'order' 		=> $this->object,
			'email_heading' => $this->get_heading(),
			'customer_note' => $this->content,
			'sent_to_admin' => false,
			'plain_text' => false
		) );
		
		$email = ob_get_clean();
		
		$email = str_replace( $this->find, $this->replace, strip_tags($email));
		
		return $email;
	}
	
	
	function init_form_fields() {
    	$this->form_fields = array(


			'title_general' => array(
				'title' 		=> __( 'General', 'woocommerce' ),
				'type' 			=> 'title',
			),

			'enabled' => array(
				'title' 		=> __( 'Enable/Disable', 'woocommerce' ),
				'type' 			=> 'checkbox',
				'label' 		=> __( 'Enable this email notification', 'woocommerce' ),
				'default' 		=> 'no'
			),
			
			'title_status' => array(
				'title' 		=> __( 'Order status', 'woopar' ),
				'type' 			=> 'title',
				'class' => 'petit',
				'description' 	=> __( 'Choose for which order statuses reminder applies.', 'woopar' ),
			),
		);
			
		$array = array();
		
		foreach($this->order_statuses as $key=>$status)
		{
			$default = 'no';
			if($key == 'on-hold')
				$default = 'yes';
			
			$array['status_wopar_'.$key] = array(
					'label' 		=> $status,
					'type' 			=> 'checkbox',
					'default' 		=> $default,
					'class'			=> 'like_checkbox_multiple',
			);
		}
		
		$this->form_fields = array_merge($this->form_fields, $array);
		
		$this->form_fields = array_merge($this->form_fields, 
		array(
			'title_automatisation' => array(
				'title' 		=> __( 'Automation', 'woopar' ),
				'type' 			=> 'title',
				'css'			=> 'border-top:1px solid #eee;',
				'description' 	=> __( 'Configure automatic emails.', 'woopar' )
				.'<br /><strong>'.__( 'Important note:', 'woopar').'</strong> '.__( 'Automatic emails are independent from manual emails you might send. For example, if you set number of reminders to 3, the plugin will send 3 reminders automatically, no matter if you already sent some reminders manually.', 'woopar' ),
			),


			'enabled_automatisation' => array(
				'title' 		=> __( 'Enable/Disable Automation', 'woopar' ),
				'type' 			=> 'checkbox',
				'label' 		=> __( 'Enable automatic email notification', 'woopar' ),
				'default' 		=> 'no'
			),



			'automatisation_start' => array(
				'title' 		=> __( 'Send first reminder after ... days', 'woopar' ),
				'type' 			=> 'decimal',
				'description' 	=> __( 'Number of days before sending reminder first time', 'woopar' ),
				'placeholder' 	=> '',
				'default' 		=> '5',
				'css' 			=> 'width: 50px;'
			),

			'automatisation_frequence' => array(
				'title' 		=> __( 'Then, send it every ... days', 'woopar' ),
				'type' 			=> 'decimal',
				'description' 	=> __( 'Number of days between each reminder', 'woopar' ),
				'placeholder' 	=> '',
				'default' 		=> '10',
				'css' 			=> 'width: 50px;'
			),

			'automatisation_number' => array(
				'title' 		=> __( 'Send maximum ... reminders', 'woopar' ),
				'type' 			=> 'decimal',
				'description' 	=> __( 'Total number of automatic reminders to send (if order still has one of the selected statuses)', 'woopar' ),
				'placeholder' 	=> '',
				'default' 		=> '3',
				'css' 			=> 'width: 50px;'
			),

			'automatisation_change_status' => array(
				'title' 		=> __( 'At the end, change order status ?', 'woopar' ),
				'type' 			=> 'select',
				'description' 	=> __( 'Once all the reminders have been unsuccessfully sent, automatically change the status of the order (after waiting the same time as between two reminders).', 'woopar' ),
				'placeholder' 	=> '',
				'default' 		=> '3',
				'css' 			=> 'width: 180px;',
				'options'		=>  array_merge( array('no'=> __( 'Dont change status', 'woopar' )), $this->order_statuses)
			),

			'title_email' => array(
				'title' 		=> __( 'Email settings', 'woopar' ),
				'type' 			=> 'title',
				'description' 	=> __( 'Configure email that will be send.', 'woopar' ),
			),

			'subject' => array(
				'title' 		=> __( 'Subject', 'woocommerce' ),
				'type' 			=> 'text',
				'description' 	=> sprintf( __( 'This controls the email subject line. Leave blank to use the default subject: <code>%s</code>.', 'woocommerce' ), $this->subject ),
				'placeholder' 	=> '',
				'default' 		=> ''
			),
			'heading' => array(
				'title' 		=> __( 'Email Heading', 'woocommerce' ),
				'type' 			=> 'text',
				'description' 	=> sprintf( __( 'This controls the main heading contained within the email notification. Leave blank to use the default heading: <code>%s</code>.', 'woocommerce' ), $this->heading ),
				'placeholder' 	=> '',
				'default' 		=> ''
			),
			'content' => array(
				'title' 		=> __( 'Email content', 'woopar' ),
				'type' 			=> 'textarea',
				'description' 	=> __( 'Content of the message sent to customer. You may put these variables : {order_date}, {order_number}, {order_payment_method}, {first_name}, {payment_url}. <a target="_blank" href="http://codecanyon.net/item/woocommerce-payment-reminder/6965773/faqs/24782">Note on payment url</a>', 'woopar' ),
				'placeholder' 	=> '',
				'default' 		=> $this->content
			),
			'bcc' => array(
				'title' 		=> __( 'Email bcc', 'woopar' ),
				'type' 			=> 'text',
				'description' 	=> __( 'Enter an email address to be in bcc of the reminder emails. (Optional)', 'woopar' ),
				'placeholder' 	=> '',
				'default' 		=> ''
			),
			'email_type' => array(
				'title' 		=> __( 'Email type', 'woocommerce' ),
				'type' 			=> 'select',
				'description' 	=> __( 'Choose which format of email to send.', 'woocommerce' ),
				'default' 		=> 'html',
				'class'			=> 'email_type',
				'options'		=> array(
					'plain'		 	=> __( 'Plain text', 'woocommerce' ),
					'html' 			=> __( 'HTML', 'woocommerce' ),
					'multipart' 	=> __( 'Multipart', 'woocommerce' ),
				)
			),
		)

		);
    }
}