<?php
/**
 * JNE Tracking
 *
 * This is a module, handles for the tracking number
 *
 * @author AgenWebsite
 * @package WooCommerce JNE Shipping
 * @since 8.0.0
 */

if ( !defined( 'WOOCOMMERCE_JNE' ) ) { exit; } // Exit if accessed directly

if ( !class_exists( 'WC_JNE_Tracking' ) ) :
	
/**
 * Class WooCommerce JNE Tracking
 *
 * @since 8.0.0
 **/
class WC_JNE_Tracking{

	/**
	 * @var woocommerce jne tracking class
	 * @since 8.0.0
	 */
	private static $class = null;
	
	/**
	 * @var string
	 * @since 8.0.0
	 */
	private static $id = 'woocommerce_jne_tracking';

	/**
	 * @var string
	 * @since 8.0.0
	 */
	private $tracking_option = 'woocommerce_jne_tracking_settings';

	/**
	 * @var string
	 * @since 8.0.0
	 */
	private $tracking_meta = '_woocommerce_jne_tracking_number';

	/**
	 * @var string
	 * @since 8.0.0
	 */
	private $tracking_nonce = 'woocommerce_jne_tracking__nonce';

	/**
	 * @var string
	 * @since 8.0.0
	 */
	private $tracking_action = 'woocommerce_jne_tracking_field';

	/**
	 * @var array
	 * @since 8.0.0
	 */
	private $tracking_field = null;

	/**
     * Base URL of JNE official website for cekongkir
     * @var string
     * @since 8.1.02
     */
	public $base_url = 'http://jne.co.id/';

	/**
     * Captcha URL
     * @var string
     * @since 8.1.02
     */
	public $captcha_url = 'captcha2.php';

	/**
     * Tracking Endpoint URL
     * @var string
     * @since 8.1.02
     */
    public $endpoint_url = 'index.php?mib=tracking&lang=IN';	
    
	/**
	 * Constructor
	 *
	 * @access public
	 * @return void
	 * @since 8.0.0
	 **/
	public function __construct(){
		
		$this->tracking_field = apply_filters( 'woocommerce_jne_tracking_field', array(
			"name" => "nomor_resi",
			"default" => "",
			"label" => __( "Nomor Resi JNE", 'agenwebsite' ),
			"type" => "text",
			"placeholder" => __( "Type your tracking code here", 'agenwebsite' ),
		));
		
		// load default option
		$this->load_default_option();
		
		add_action( 'init', array( &$this, 'admin_settings' ) );
					
		// Load meta post
		add_action( 'load-post.php', array( &$this, 'init' ) );
		add_action( 'load-post-new.php', array( &$this, 'init' ) );
		
		// Save meta post
		add_action( 'add_meta_boxes', array( &$this, 'add_meta_box' ) );
		add_action( 'save_post', array( $this, 'save' ) );					
		
		// Diplay tracking info at view order, my account
		add_action( 'woocommerce_view_order', array( &$this, 'view_order' ) );
        
        // Add shortcode for tracking
        add_shortcode( 'jne_tracking', array( &$this, 'shortcode' ) );
        
        add_filter( 'woocommerce_jne_shortcodes', array( &$this, 'add_shortcode_to_list' ) );
	}
    
	/**
	 * Initialise
	 * The initialise to self class
	 *
	 * @access public
	 * @return object
	 * @since 8.0.0
	 **/
	public static function init(){
		if( null == self::$class )
			self::$class = new self;
			
		return self::$class;
	}
	
	/**
	 * Run the settings page
	 *
	 * @access public
	 * @return object
	 * @since 8.0.0
	 **/
	public function admin_settings(){
		return new WC_JNE_Tracking_Settings();
	}
	
	/**
	 * Load Default Option
	 * add the option if the option is doesn't exists
	 *
	 * @access private
	 * @return objec
	 * @since 8.0.0
	 **/
	private function load_default_option(){
		$option = get_option( $this->tracking_option );
		
		if( ! $option ){
			$default_option = apply_filters( 'woocommerce_jne_tracking_option', array(
				'judul'	=> 'JNE',
				'pesan'	=> 'Pesanan Anda dikirimkan melalui: <strong>[jne_tracking_title]</strong> <br/>Tracking Number untuk pesanan Anda: <strong>[jne_tracking_number]</strong>'
			));
			
			update_option( $this->tracking_option, $default_option );
		}
	}

	/**
	 * Add meta box
	 * add the meta box to the type post order
	 *
	 * @access public
	 * @param mixed $post_type
	 * @return void
	 * @since 8.0.0
	 **/
	
	public function add_meta_box( $post_type ){
		
		//limit meta box to order type only
		$post_order_type = array('shop_order');
		
		// if the post type is order then add meta box
		if( in_array( $post_type, $post_order_type ) ){
			add_meta_box(
				WC_JNE_Tracking::$id,
				__( 'WooCommerce JNE Tracking', 'agenwebsite' ),
				array( &$this, 'render_meta_box' ),
				$post_type,
				'side',
				'high'
			);
		}
		
	}
		
	/**
	 * Save post
	 * save the meta box field to post meta
	 *
	 * @access public
	 * @param string $post_id
	 * @return void
	 * @since 8.0.0
	 **/
	public function save( $post_id ){
		// Check the nonce
		if( ! isset( $_POST[ $this->tracking_nonce ] ) )
			return $post_id;
		
		$tracking_nonce = $_POST[ $this->tracking_nonce ];
		
		// Validate the nonce
		if( ! wp_verify_nonce( $tracking_nonce, $this->tracking_action ) )
			return $post_id;
			
		// Check the autosave, dont save our form if autosave
		if( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
			return $post_id;
		
		// Check user permission. fuck off
		if( 'page' == $_POST['post_type'] ){
			if( ! current_user_can( 'edit_page', $post_id ) )
				return $post_id;
		} else {
			if( ! current_user_can( 'edit_post', $post_id ) )
				return $post_id;
		}
		
		// its safe. fuck user haven't permission :X
			
		$input = sanitize_text_field( $_POST[ $this->tracking_field['name'] ] );
		
		update_post_meta( $post_id, $this->tracking_meta, $input );
	}
	
	/**
	 * Render Meta Box
	 * display the meta box HTML
	 *
	 * @access public
	 * @param mixed $post
	 * @return HTML
	 * @since 8.0.0
	 **/
	public function render_meta_box( $post ){
		
		// nonce field for the jne tracking
		wp_nonce_field( $this->tracking_action, $this->tracking_nonce );

		$value_tracking = get_post_meta( $post->ID, $this->tracking_meta, TRUE );				
	
		// Start Wrapper
		echo '<div class="' . WC_JNE_Tracking::$id . '_wrapper">' . "\n";
		
		echo '<div class="form-wrap"><div class="form-field">' . "\n";
			// Create meta box content
			echo '<label for="' . $this->tracking_field['name'] . '">' . __( $this->tracking_field['label'], 'agenwebsite' ) . '</label>' . "\n";
			echo '<input type="' . $this->tracking_field['type'] . '" name="' . $this->tracking_field['name'] . '" id="' . $this->tracking_field['name'] . '" value="' . esc_attr( $value_tracking ) . '" placeholder="' . $this->tracking_field['placeholder'] . '" />' . "\n";
		
			echo '</div></div>' . "\n";
	
		// End Wrapper
		echo '</div>' . "\n";

	}
		
	/**
	 * Display Tracking Info
	 *
	 * @access public
	 * @param integer $order_id
	 * @return HTML
	 * @since 8.0.0
	 **/
	public function display_tracking_info( $orderid ){
		$tracking_number = get_post_meta( $orderid, $this->tracking_meta, TRUE );
		
		// check if tracking number is empty
		if( ! $tracking_number ) return;
		
		$tracking_option = get_option( $this->tracking_option );
		
		$message = $tracking_option['pesan'];
		$tracking_name = $tracking_option['judul'];
		
		$shortcode = array(
			'[jne_tracking_title]'	=> $tracking_name,
			'[jne_tracking_number]'	=> $tracking_number
		);
		
		echo wpautop( str_replace( array_keys( $shortcode ), array_values( $shortcode ), $message ) );
	}
    
    /**
     * Add jne tracking to shortcode list
     *
     * @access public
     * @param array $shortcodes
     * @return array
     * @since 8.1.10
     */
    public function add_shortcode_to_list( $shortcodes = array() ){
        return WC_JNE()->add_shortcode_list( $shortcodes, 'jne_tracking', 'jne cek resi' );
    }
		
	/**
	 * Shortcode for tracking
	 *
	 * @access public
	 * @param array $atts
	 * @return HTML
	 * @since 8.1.02
	 **/
	public function shortcode($atts) {
        extract( shortcode_atts( array('class' => ''), $atts ) );
        
        $placeholder_tracking_number = 'Masukkan nomor resi JNE';
        $placeholder_captcha = 'Masukkan teks captcha';
        $button = 'Cek Resi';
        
        wp_enqueue_style( 'woocommerce-jne-tracking-widget' );
        
        $output = '';
			$output .= '<form method="post" action="' . $this->base_url . $this->endpoint_url . '" target="_blank" class="jne_tracking ' . $class . '" id="jne_tracking">' . "\n";
				$output .= '<label for="awbnum">' . $placeholder_tracking_number . '</label>' . "\n";
				$output .= '<input type="text" id="awbnum" name="awbnum" placeholder="' . $placeholder_tracking_number . '" class="input_resi" style="margin-bottom:15px;" required />' . "\n";
				
				$output .= '<div class="captcha_img">' . "\n";
				$output .= '<img id="captcha" alt="" src="' . $this->base_url . $this->captcha_url . '" width="140" /><br />' . "\n";
				$output .= '<a id="change-image" href="javascript:void">' . __( 'Refresh', 'agenwebsite' ) . ' <span class="dashicons dashicons-update"></span></a><br />' . "\n";
				$output .= '</div>' . "\n";
				$output .= '<script type="text/javascript">' . "\n";
					$output .= '
							jQuery(document).ready(function($) {
								var captchaID = $(\'#captcha\');
								$( "#change-image" ).click(function(){
									$( "form#jne_tracking" ).addClass("updating-captcha");
									
									$( captchaID ).one("load", function() {
										$( "form#jne_tracking" ).removeClass("updating-captcha");
									}).attr( "src", \'' . $this->base_url . $this->captcha_url . '?\' + Math.random() );
								});
							});
					';
				$output .= '</script>' . "\n";
				
				$output .= '<label for="awbcaptcha">' . $placeholder_captcha . '</label>' . "\n";
				$output .= '<input id="awbcaptcha" name="captcha" type="text" placeholder="' . $placeholder_captcha . '" class="input_captcha" required />' . "\n";
				$output .= '<p><input type="submit" value="' . $button . '" class="button alt" name="submittracking" /></p>' . "\n";
			$output .= '</form>' . "\n";
	
		return $output;
	}    
	
	/**
	 * Tracking info on View Order
	 *
	 * @access public
	 * @param integer $order_id
	 * @return HTML
	 * @since 8.0.0
	 **/
	public function view_order( $order_id ){
		$this->display_tracking_info( $order_id );
	}

	
}

if( ! function_exists( 'aw_wc_jne_email_display' ) ){

	/**
	 * Display Tracking Info on Email
	 *
	 * @access public
	 * @param integer $order_id
	 * @return HTML
	 * @since 8.0.0
	 **/
	add_action('woocommerce_email_before_order_table', 'aw_wc_jne_email_display' );
	function aw_wc_jne_email_display( $order ){
		WC_JNE()->tracking->display_tracking_info( $order->id );
	}
}
 	
endif;