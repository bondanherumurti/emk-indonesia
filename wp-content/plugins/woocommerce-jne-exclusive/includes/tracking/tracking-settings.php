<?php
/**
 * JNE Tracking Settings
 *
 * Handles admin settings for JNE Tracking module
 *
 * @author AgenWebsite
 * @package WooCommerce JNE Shipping
 * @since 8.0.0
 */

if ( !defined( 'WOOCOMMERCE_JNE' ) ) { exit; } // Exit if accessed directly

if ( !class_exists( 'WC_JNE_Tracking_Settings' ) ) :
	
/**
 * Class WooCommerce JNE Tracking
 *
 * @since 8.0.0
 **/
class WC_JNE_Tracking_Settings{
	
	/**
	 * Immportant string
	 * option group, option page, id
	 *
	 * @access private
	 * @var string
	 * @since 8.0.0
	 **/
	private $option_group = 'woocommerce_jne_tracking_option_group';
	private $option_page = 'woocommerce_jne_tracking_settings_admin';
	private $id = 'woocommerce_jne_tracking_settings';
	
	/**
	 * Options
	 *
	 * @access private
	 * @var array
	 * @since 8.0.0
	 **/
	private $options;
	
	public function __construct(){
		// Load settings admin
		add_action( 'admin_menu', array( &$this, 'add_plugin_page' ) );
		add_action( 'admin_init', array( &$this, 'page_init' ) );			
	}

	/**
	 * Add option
	 * option submenu display on settings menu
	 *
	 * @access public
	 * @return void
	 * @since 8.0.0
	 **/
	public function add_plugin_page(){
		add_options_page( 'JNE Tracking', 'JNE Tracking', 'manage_options', $this->id, array( &$this, 'settings_page' ));	
	}
	
	/**
	 * Settings page
	 * display the settings page html
	 *
	 * @access public
	 * @return HTML
	 * @since 8.0.0
	 **/
	public function settings_page(){
		$this->options = get_option( 'woocommerce_jne_tracking_settings' );
		
		ob_start();
		$output = '<div class="wrap">';
			$output .= screen_icon();
			$output .= '<h2>' . __( 'WooCommerce JNE Tracking Settings', 'agenwebsite' ) . '</h2>';
			$output .= '<form method="post" action="options.php"';
				$output .= settings_fields( $this->option_group ); 
				$output .= do_settings_sections( $this->option_page );
				$output .= submit_button();
			$output .= '</form>';
		$output .= '</div>';
		$output .= ob_get_clean();
		
		echo $output;
	}

	/**
	 * Register a new settings option group
	 *
	 * @access public
	 * @return void
	 * @since 8.0.0
	 **/
	public function page_init(){
		register_setting( $this->option_group, $this->id, array( &$this, 'sanitize' ) );

		add_settings_section( 'general_settings', '', '', $this->option_page );

		add_settings_field( 'judul', __( 'Judul', 'agenwebsite' ), array( &$this, 'judul_callback' ), $this->option_page, 'general_settings' );

		add_settings_field( 'pesan', __( 'Pesan', 'agenwebsite' ),  array( &$this, 'pesan_callback' ), $this->option_page, 'general_settings' );
	}
	
	/**
	 * Sanitize the option's value
	 *
	 * @param mixed $input
	 * @return array
	 * @since 8.0.0
	*/
	public function sanitize( $input ){
			
	$new_input = array();
			
	if( isset( $input['judul'] ) )
		$new_input['judul'] = sanitize_text_field( $input['judul'] );
	
		if( isset( $input['pesan'] ) )
			$new_input['pesan'] =  $input['pesan'];
				
		return $new_input;
		
	}
	
	/**
	 * Judul Callback
	 *
	 * @return HTML
	 * @since 8.0.0
	 */
	public function judul_callback() {
		printf(
			'<input type="text" id="judul" name="' . $this->id . '[judul]" value="%s" class="regular-text" />',
			isset( $this->options['judul'] ) ? esc_attr( $this->options['judul']) : ''
		);
		echo '<p><i>Judul ditampilkan ketika customer ingin melihat nomor tracking</i></p>';
	}
	
	/**
	 * Pesan Callback
	 *
	 * @return HTML
	 * @since 8.0.0
	*/
	public function pesan_callback() {
		echo'<textarea id="description" name="' . $this->id . '[pesan]" value="%s" class="input-text wide-input" style="width:100%; height: 120px;">';
		echo (isset( $this->options['pesan'] ) ? esc_attr( $this->options['pesan']) : '');
		echo '</textarea>';
		echo '<p><i>Deskripsi ditampilkan di bagian my-account, view order, dan email ketika order complete</i></p>';
		echo '<p><strong>[jne_tracking_title]</strong> = Judul JNE Tracking</p>';
		echo '<p><strong>[jne_tracking_number]</strong> = Menampilkan kode jne tracking</p>';
	}
	
}
	 	
endif;