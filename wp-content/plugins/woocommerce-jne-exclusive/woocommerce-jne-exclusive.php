<?php
/**
 * Plugin Name: WooCommerce JNE Shipping ( Exclusive Version )
 * Plugin URI: http://www.agenwebsite.com/products/woocommerce-jne-shipping
 * Description: Plugin untuk WooCommerce dengan penambahan metode shipping JNE.
 * Version: 8.1.10
 * Author: AgenWebsite
 * Author URI: http://agenwebsite.com
 *
 *
 * Copyright 2015 AgenWebsite. All Rights Reserved.
 * This Software should not be used or changed without the permission
 * of AgenWebsite.
 * 
 */

if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly

if ( ! class_exists( 'WooCommerce_JNE' ) ) :

/**
 * Initiliase Class
 *
 * @since 8.0.0
 **/
class WooCommerce_JNE{

	/**
	 * @var string
	 */
	public $version = '8.1.10';

	/**
	 * @var string
	 */
	public $db_version = '8.0.1';

	/**
	 * @var string
	 */
	public $product_version = 'exclusive';

	/**
	 * @var woocommerce jne main class
	 * @since 8.0.0
	 */
	protected static $_instance = null;

	/**
	 * @var WC_JNE_Shipping $shipping
	 * @since 8.0.0
	 */
	public $shipping = null;

	/**
	 * @var WC_JNE_Asuransi $asuransi
	 * @since 8.0.0
	 */
	public $asuransi = null;

	/**
	 * @var WC_JNE_Tracking $tracking
	 * @since 8.0.0
	 */
	public $tracking = null;	

	/**
	 * @var WC_JNE_Tracking $tracking
	 * @since 8.1.10
	 */
	public $api = null;	

	/**
	 * @var string
	 * @since 8.0.0
	 */
	private $nonce = '_woocommerce_jne__nonce';
	
	/**
	 * Various Links
	 * @var string
	 * @since 8.0.0
	 */
	public $url_dokumen = 'http://docs.agenwebsite.com/?url=WooCommerce%20JNE%20Shipping';
	public $url_support = 'http://www.agenwebsite.com/support';
		
	/**
	 * WooCommerce JNE Instance
	 *
	 * @access public
	 * @return Main Instance
	 * @since 8.0.0
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Constructor
	 *
	 * @return self
	 * @since 8.0.0
	 */	
	public function __construct(){
		$this->define_constants();
		$this->includes();
		$this->init_hooks();
	}
	
	/**
	 * Define JNE Constant
	 *
	 * @access private
	 * @return void
	 * @since 8.0.0
	 */	
	private function define_constants(){
		register_activation_hook( __FILE__, array( 'WooCommerce_JNE', 'install' ) );
		define( 'WOOCOMMERCE_JNE', TRUE );
		define( 'WOOCOMMERCE_JNE_VERSION', $this->version );
	}
	
	/**
	 * Install
	 *
	 * @access public
	 * @return void
	 * @since 8.0.0
	 */	
	public static function install(){

		// Check is under version 8
		if( get_option( 'woocommerce_jne_version' ) ){
			delete_option( 'woocommerce_jne_tracking' );
			delete_option( 'woocommerce_jne_shipping_data_save' );
			delete_option( 'woocommerce_jne_shipping_settings' );
		}

		delete_option( 'woocommerce_jne_version' );
		add_option( 'woocommerce_jne_version', WC_JNE()->version );
		
		delete_option( 'woocommerce_jne_db_version' );
		add_option( 'woocommerce_jne_db_version', WC_JNE()->db_version );
	}
	
	/**
	 * Hooks action and filter
	 *
	 * @access private
	 * @return void
	 * @since 8.0.0
	 */	
	private function init_hooks(){
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( &$this, 'add_settings_link' ) );
		add_action( 'wp_enqueue_scripts', array( &$this, 'load_scripts') );
		add_action( 'admin_enqueue_scripts', array( &$this, 'load_scripts_admin') );
        add_action( 'admin_notices', array( &$this, 'notice_set_license' ) );
	}
	
	/**
	 * Inititialise Includes
	 *
	 * @access private
	 * @return void
	 * @since 8.0.0
	 */	
	private function includes(){
		$this->shipping = WooCommerce_JNE::shipping();
		$this->asuransi = WooCommerce_JNE::asuransi();
		$this->tracking = WooCommerce_JNE::tracking();
		WooCommerce_JNE::includes_class();
	}
	
	/**
	 * Inititialise JNE Shipping module
	 *
	 * @access private
	 * @return WC_JNE_Shipping
	 * @since 8.0.0
	 */	
	private static function shipping(){
	 	// Load files yang untuk modul shipping
		WooCommerce_JNE::load_file( 'shipping' );		

		return new WC_JNE_Shipping();
	}
			
	/**
	 * Initialise JNE Asuransi module
	 *
	 * @access private
	 * @return WC_JNE_Asuransi
	 * @since 8.0.0
	 */	
	private static function asuransi(){
		WooCommerce_JNE::load_file( 'asuransi' );
		
		return new WC_JNE_Asuransi();
	}
	
	/**
	 * Initialise JNE Tracking module
	 *
	 * @access private
	 * @return WC_JNE_Tracking
	 * @since 8.0.0
	 */	
	private static function tracking(){
		WooCommerce_JNE::load_file( 'tracking' );
		
		return new WC_JNE_Tracking();
					
	}
	
	/**
	 * Include file
	 *
	 * @access private
	 * @return void
	 * @since 8.1.10
	 */	
	private function includes_class(){
		require_once( 'includes/wc-jne-ajax.php' );	
		require_once( 'includes/wc-jne-api.php' );	
        
        $this->api = new WC_JNE_API( sprintf( 'woocommerce-jne-%s', $this->product_version ), $this->get_license_code() );
	}
	
	/**
	 * Load Requires Files by modules
	 *
	 * @access private
	 * @return void
	 * @since 8.0.0
	 */	
	private static function load_file( $modules ){
		switch( $modules ){
			
			case 'asuransi':
				require_once( 'includes/asuransi/asuransi.php' );		
			break;
			
			case 'shipping':
				require_once( 'includes/shipping/shipping.php' );
				require_once( 'includes/shipping/shipping-frontend.php' );
				require_once( 'includes/shipping/shipping-cekongkir.php' );
				require_once( 'includes/shipping/shipping-cekongkir-tab.php' );
				require_once( 'includes/shipping/shipping-cekongkir-widget.php' );
				require_once( 'includes/shipping/shipping-volumetrik.php' );
				require_once( 'includes/shipping/shipping-packing-kayu.php' );
			break;
			
			case 'tracking':
				require_once( 'includes/tracking/tracking.php' );
				require_once( 'includes/tracking/tracking-settings.php' );
				require_once( 'includes/tracking/tracking-widget.php' );
			break;
			
		}
	}
	
	/**
	 * Load JS & CSS FrontEnd
	 *
	 * @access public
	 * @return void
	 * @since 8.0.0
	 */
	public function load_scripts(){
        $suffix			= defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
        $assets_path	= str_replace( array( 'http:', 'https:' ), '', WC()->plugin_url() ) . '/assets/';

        // select2
        $select2_js_path = $assets_path . 'js/select2/select2' . $suffix . '.js';
        $select2_css_path = $assets_path . 'css/select2.css';
        if( ! wp_script_is( 'select2', 'registered' ) ) wp_register_script( 'select2', $select2_js_path, array( 'jquery' ), '3.5.2' );
        if( ! wp_style_is( 'select2', 'registered' ) ) wp_register_style( 'select2', $select2_css_path );
        
        // chosen
        $chosen_js_path = $assets_path . 'js/chosen/chosen.jquery' . $suffix . '.js';
        $chosen_css_path = $assets_path . 'css/chosen.css';
        if( ! wp_script_is( 'chosen', 'registered' ) ) wp_register_script( 'chosen', $chosen_js_path, array( 'jquery' ), '1.0.0', true );
        if( ! wp_style_is( 'chosen', 'registered' ) ) wp_enqueue_style( 'woocommerce_chosen_styles', $chosen_css_path );

        wp_register_script( 'woocommerce-jne-shipping',		$this->plugin_url() . '/assets/js/shipping' . $suffix . '.js', 	array( 'jquery' ),	'1.0.12', true );
        wp_register_script( 'woocommerce-jne-cekongkir',	$this->plugin_url() . '/assets/js/cekongkir' . $suffix . '.js', array( 'jquery' ), 	'1.0.12', true );

        wp_register_style( 'woocommerce-jne-cekongkir', 		$this->plugin_url() . '/assets/css/cekongkir.css' );
        wp_register_style( 'woocommerce-jne-tracking-widget',	$this->plugin_url() . '/assets/css/tracking.widget.css' );

        // shipping
        if( $this->shipping->is_enable() ){
            if( is_checkout() || is_cart() || is_wc_endpoint_url( 'edit-address' ) ) {
                wp_enqueue_script( 'woocommerce-jne-shipping');
                wp_localize_script( 'woocommerce-jne-shipping', 'agenwebsite_woocommerce_jne_params', $this->localize_script( 'shipping' ) );
            }
        }

        // cek ongkir
        if( ( is_product() && $this->shipping->cek_ongkir->tab_is_enable() ) || is_active_widget( false, false, 'jne_cekongkir_widget', true ) || $this->is_active_shortcode( 'jne_cek_ongkir' ) ){
            wp_enqueue_style('woocommerce-jne-cekongkir');
            wp_enqueue_script('woocommerce-jne-cekongkir');
            wp_localize_script( 'woocommerce-jne-cekongkir', 'agenwebsite_woocommerce_jne_cekongkir_params', $this->localize_script( 'cekongkir' ) );
        }

        // load selec2 or chosen
        if( ( $this->shipping->is_enable() && is_cart() ) || ( is_product() && $this->shipping->cek_ongkir->tab_is_enable() ) || is_active_widget( false, false, 'jne_cekongkir_widget', true ) || $this->is_active_shortcode( 'jne_cek_ongkir' ) ){
            if( ! wp_script_is( 'select2' ) ) wp_enqueue_script( 'select2' );
            if( ! wp_style_is( 'select2' ) ) wp_enqueue_style( 'select2' );

            if( ! wp_script_is( 'chosen' ) ) wp_enqueue_script( 'chosen' );
            if( ! wp_style_is( 'chosen' ) ) wp_enqueue_style( 'chosen' );       
        }

    }
	
	/**
	 * Load JS dan CSS admin
	 *
	 * @access public
	 * @return void
	 * @since 8.0.0
	 */
	public function load_scripts_admin(){
        global $pagenow;

        $suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

        // Load for admin common JS & CSS
        wp_register_script( 'woocommerce-jne-js-admin', WC_JNE()->plugin_url() . '/assets/js/admin' . $suffix . '.js', array( 'jquery', 'zeroclipboard' ), '1.0.3', true );
        wp_register_style( 'woocommerce-jne-admin', WC_JNE()->plugin_url() . '/assets/css/admin.css' );
        
        wp_register_style( 'jquery-ui-datepicker-style', WC_JNE()->plugin_url() . '/assets/css/datepicker.css' );

        if( $pagenow == 'admin.php' && ( isset( $_GET['page'] ) && $_GET['page'] == 'wc-settings' ) && ( isset( $_GET['tab'] ) && $_GET['tab'] == 'shipping' ) && ( isset( $_GET['section'] ) && $_GET['section'] == 'wc_jne' ) ) {
            
            // datepicker
            wp_enqueue_script( 'jquery-ui-datepicker' );
            wp_enqueue_style( 'jquery-ui-datepicker-style' );
            
            wp_enqueue_script( 'woocommerce-jne-js-admin' );
            wp_enqueue_style( 'woocommerce-jne-admin' );

            // zeroclipboard
            wp_enqueue_script( 'zeroclipboard' );

            // Load localize admin params
            wp_localize_script( 'woocommerce-jne-js-admin', 'agenwebsite_jne_admin_params', $this->localize_script( 'admin' ) );
        }
        
        if( $this->is_page_to_notice() ){
            wp_enqueue_style( 'woocommerce-jne-admin' );                
        }            
        
    }
	
	/**
	 * Localize Scripts
	 *
	 * @access public
	 * @return void
	 * @since 8.0.0
	 */
	public function localize_script( $handle ){
		switch( $handle ){
			case 'admin':
				return array(
					'i18n_input_too_short_3'       => __( 'Ketikkan huruf minimal 3 atau lebih', 'agenwebsite' ),
					'i18n_searching'               => __( 'Pencairan data&hellip;', 'agenwebsite' ),
					'i18n_no_matches'              => __( 'Data tidak ditemukan', 'agenwebsite' ),
					'i18n_reset_default'           => __( 'Peringatan! Semua pengaturan anda akan dihapus. Anda yakin untuk kembalikan ke pengaturan awal ?', 'agenwebsite' ),
                    'i18n_delete_all'              => __( 'Anda yakin untuk hapus semua ?', 'agenwebsite' ),
					'i18n_is_available'            => __( 'sudah tersedia', 'agenwebsite' ),
					'jabodetabek'                  => WC_JNE()->shipping->get_jabodetabek(),
					'license'                      => ( $_POST && isset($_POST['woocommerce_jne_shipping_license_code'] ) ) ? $_POST['woocommerce_jne_shipping_license_code'] : '',
                    'tab'                          => ( $_GET && isset($_GET['tab_jne']) ) ? $_GET['tab_jne'] : 'general',                     
					'ajax_url'                     => self::ajax_url(),
					'jne_admin_wpnonce'            => wp_create_nonce( 'woocommerce_jne_admin' )
				);
			break;
			case 'shipping':
				return array(
                    'i18n_placeholder_kota'         => __( 'Pilih Kota / Kabupaten', 'agenwebsite' ),
                    'i18n_placeholder_kecamatan'    => __( 'Pilih Kecamatan', 'agenwebsite' ),
                    'i18n_label_kecamatan'          => __( 'Kecamatan', 'agenwebsite' ),
                    'i18n_no_matches'               => __( 'Data tidak ditemukan', 'agenwebsite' ),
                    'i18n_required_text'            => __( 'required', 'agenwebsite' ),
                    'i18n_loading_data'             => __( 'Meminta data...', 'agenwebsite' ),
                    'wc_version'                    => self::get_woocommerce_version(),
                    'ajax_url'                      => self::ajax_url(),
                    'page'                          => self::get_page(),
                    '_wpnonce'                      => wp_create_nonce( $this->nonce )
				);
			break;
			case 'cekongkir':
				return array(
                    'i18n_placeholder_kota'         => __( 'Pilih Kota / Kabupaten', 'agenwebsite' ),
                    'i18n_placeholder_kecamatan'    => __( 'Pilih Kecamatan', 'agenwebsite' ),
                    'i18n_no_matches'               => __( 'Data tidak ditemukan', 'agenwebsite' ),
                    'ajax_url'                      => self::ajax_url(),
                    '_wpnonce'                      => wp_create_nonce( $this->nonce )					
				);
			break;
		}
	}
	
	/**
	 * Add setting link to plugin list table
	 *
	 * @access public
	 * @param  array $links Existing links
	 * @return array		Modified links
	 * @since 8.0.0
	 */
	public function add_settings_link( $links ){
        $plugin_links = array(
            '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=shipping&section=wc_jne' ) . '">' . __( 'Settings', 'agenwebsite' ) . '</a>',
            '<a href="' . $this->url_dokumen . '" target="new">' . __( 'Docs', 'agenwebsite' ) . '</a>',
            '<a href="' . admin_url( 'options-general.php?page=woocommerce_jne_tracking_settings' ) . '">' . __( 'Trackings Settings', 'agenwebsite' ) . '</a>'
        );

        return array_merge( $plugin_links, $links );
    }
    
    
	/**
	 * Notice to set license
	 *
	 * @access public
	 * @return HTML
	 * @since 8.1.10
	 */	
    public function notice_set_license(){
        if( $this->is_page_to_notice() && ! $this->get_license_code() ){
            printf('<div class="updated notice_wc_jne woocommerce-jne"><p><b>%s</b> &#8211; %s</p><p class="submit">%s %s</p></div>',
                   __( 'Kode lisensi tidak ada. Masukkan kode lisensi untuk mengaktifkan WooCommerce JNE', 'agenwebsite' ),
                   __( 'anda bisa mendapatkan kode lisensi dari halaman akun AgenWebsite.', 'agenwebsite'  ),
                   '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=shipping&section=wc_jne' ) . '" class="button-primary">' . __( 'Masukkan kode lisensi', 'agenwebsite' ) . '</a>',
                   '<a href="' . esc_url( $this->url_dokumen ) . '" class="button-primary" target="new">' . __( 'Baca dokumentasi', 'agenwebsite' ) . '</a>' );
        }
    }
    
	/**
	 * Check page to notice
	 *
	 * @access public
	 * @return HTML
	 * @since 8.1.10
	 */	
    public function is_page_to_notice(){
        global $pagenow;
        $user = wp_get_current_user();
        $screen = get_current_screen();
        if( $pagenow == 'plugins.php' || $screen->id == "woocommerce_page_wc-settings" ){
            if( isset( $_GET['section'] ) && $_GET['section'] === 'wc_jne' ) return false;
            
            return true;
        }
        
        return false;
    }
    
	/**
	 * Check active shortcode
	 *
	 * @access public
	 * @return bool
	 * @since 8.1.10
	 */	
    public function is_active_shortcode( $shortcode ){
        global $post;
        
        if( is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, $shortcode ) ){
            return true;
        }
        
        return false;
    }
    
	/**
	 * Set transient
     * use function transient from wordpress and set expiration default to one day
	 *
	 * @access public
	 * @return HTML
	 * @since 8.1.10
	 */	
    public function set_transient( $transient, $value, $expiration = NULL ){
        $expiration = ( $expiration == NULL ) ? 4 * WEEK_IN_SECONDS : $expiration; // Cached for 4 week
        set_transient( $transient, $value, $expiration );
    }
	
	/**
	 * Get status weight
	 *
	 * @access public
	 * @return HTML
	 * @since 8.0.0
	 */	
	public function get_status_weight(){
		$weight_unit = $this->get_woocommerce_weight_unit();	
		$status = '';
		$status['unit']	= $weight_unit;
		if( $weight_unit == 'g' || $weight_unit == 'kg' ){
			$status['message'] = 'yes';
		}else{
			$status['message'] = 'error';
		}
		
		return $status;
	}
    
	/**
	 * Get license code
	 *
	 * @access public
	 * @return string
	 * @since 8.1.10
	 **/
	public function get_license_code(){
		return get_option( 'woocommerce_jne_shipping_license_code' );
	}
	
	/**
	 * WooCommerce weight unit
	 *
	 * @access public
	 * @return string
	 * @since 8.0.0
	 **/
	public function get_woocommerce_weight_unit(){
		return get_option( 'woocommerce_weight_unit' );	
	}
	
	/**
	 * Get nonce
	 *
	 * @access public
	 * @return string
	 * @since 8.0.0
	 */
	public function get_nonce(){
		return $this->nonce;
    }

    /**
     * Add shortcode to list in settings
     *
     * @access public
     * @param array $shortcodes
     * @param string $new_shortcode
     * @return array
     * @since 8.1.10
     */
    public function add_shortcode_list( $shortcodes, $new_shortcode, $desc ){
        
        $shortcode_copy = '<button type="button" class="copy-shortcode button-secondary" href="#" value="[' . $new_shortcode . ']" title="' . __( 'Copied!', 'woocommerce' ) . '">' . __( 'Copy Shortcode', 'agnwebsite'  ) . '</button>';

        $shortcode = array(
            $new_shortcode => array(
                'title'			=> sprintf( __( 'Shortcode : %s %s', 'agenwebsite' ), '['.$new_shortcode.']', $shortcode_copy ),
                'type'          => 'title',
                'description'	=> sprintf( __( 'Untuk menampilkan %s taruh <code>%s</code> di halaman atau post.', 'agenwebsite' ), $desc, '['.$new_shortcode.']' ),
                'default'		=> ''
            )
        );
        
        $output = array_merge( $shortcodes, $shortcode );

        return $output;
    }

    /**
     * Create a page
     *
     * @access public
     * @param string    $name       for url slug
     *                  $title      title page
     *                  $content    content of page
     *                  $status     status
     * @return void
     * @since 8.1.10
     */
    public function create_page( $title, $content ){
        $post = array(
            'post_content' => $content,
            'post_status' => 'publish',
            'post_title' => $title,
            'post_type' => 'page'
        );

        $post_id = wp_insert_post( $post );
        
        if( $post_id != 0 ){
            printf( '<div class="updated woocommerce-jne"><p><b>%s</b></p><p class="submit">%s %s</p></div>',
                    sprintf( __( 'Halaman %s berhasil dibuat.', 'agenwebsite' ), $title ),
                    '<a href="' . get_edit_post_link( $post_id ) . '" class="button-primary">' . __( 'Edit halaman', 'agenwebsite' ) . '</a>',
                    '<a href="' . get_page_link( $post_id ) . '" class="button-primary">' . __( 'Lihat halaman', 'agenwebsite' ) . '</a>'
                  );
        }else{
            printf( '<div class="updated"><p><b>%s</b></p></div>', sprintf( __( 'Halaman %s gagal dibuat.', 'agenwebsite' ), $title ) );
        }

        return $post_id;
    }

    /**
	 * Get current page
	 *
	 * @access private
	 * @return string
	 * @since 8.0.0
	 **/
	private static function get_page(){
		// get billing or shipping
		$permalink = $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"];
		$permalinks = explode( '/', $permalink );
		end($permalinks);
		$key = key( $permalinks );
		$currentPage = $permalinks[$key-1];

		if( is_cart() )
			$page = 'cart';
		elseif( is_checkout() )
			$page = 'checkout';
		elseif( $currentPage == 'billing' )
			$page = 'billing';
		elseif( $currentPage == 'shipping' )
			$page = 'shipping';
		else
			$page = '';
			
		return $page;
	}
    
	/**
	 * Convert date
	 *
	 * @access pubc
	 * @param string $date
	 * @param string $format
	 * @return string
	 * @since 8.1.10
	 **/
    public function convert_date( $date, $format ){
        return date( $format, strtotime( $date ) );
    }
	 
	/**
	 * AJAX URL
	 *
	 * @access private
	 * @return string URL
	 * @since 8.0.0
	 **/
	private static function ajax_url(){
		return admin_url( 'admin-ajax.php' );
	}
	
	/**
	 * WooCommerce version
	 *
	 * @access public
	 * @return string
	 * @since 8.0.0
	 **/
	private function get_woocommerce_version(){
		 require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		 $data = get_plugins( '/' . plugin_basename( 'woocommerce' ) );
		 $version = explode('.',$data['woocommerce.php']['Version']);
		 return $data['woocommerce.php']['Version'];
	}
	
	/**
	 * Get the plugin url.
	 *
	 * @access public
	 * @return string
	 * @since 8.0.0
	 */
	public function plugin_url(){
		return untrailingslashit( plugins_url( '/', __FILE__ ) );
	}
	
	/**
	 * Get the plugin path.
	 *
	 * @access public
	 * @return string
	 * @since 8.0.0
	 */
	public function plugin_path(){
		return untrailingslashit( plugin_dir_path( __FILE__ ) );
	}

	/**
	 * Render help tip
	 *
	 * @access public
	 * @return HTML for the help tip image
	 * @since 8.0.0
	 **/
	public function help_tip( $tip, $float = 'none' ){
		return '<img class="help_tip" data-tip="' . $tip . '" src="' . $this->plugin_url() . '/assets/images/help.png" height="16" width="16" style="float:' . $float . ';" />';
	}
			
	/**
	 * Render link tip
	 *
	 * @access public
	 * @return HTML for the help tip link
	 * @since 8.0.0
	 **/
	public function link_tip( $tip, $text, $href, $target = NULL, $style = NULL ){
		return '<a href="' . $href . '" data-tip="' . $tip . '" target="' . $target . '" class="help_tip">' . $text . '</a>';
	}
	
}
	
endif;

/**
 * Check if WooCommerce is active
 **/
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {	
	
	/**
	 * Returns the main instance
	 *
	 * @since  8.0.0
	 * @return WooCommerce_JNE
	 */
	function WC_JNE(){
		return WooCommerce_JNE::instance();
	}

	// Let's fucking rock n roll! Yeah!
	WooCommerce_JNE::instance();
	
};
