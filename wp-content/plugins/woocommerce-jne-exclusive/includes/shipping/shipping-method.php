<?php
/**
 * WooCommerce JNE Shipping
 *
 * Main file for the calculation and settings shipping
 *
 * @author AgenWebsite
 * @package WooCommerce JNE Shipping
 * @since 8.0.0
 */

if ( !defined( 'WOOCOMMERCE_JNE' ) ) { exit; } // Exit if accessed directly

if ( !class_exists( 'WC_JNE' ) ) :
	
/**
 * Class WooCommerce JNE
 *
 * @since 8.0.0
 **/
class WC_JNE extends WC_Shipping_Method{
			
	/**
	 * Option name for save the settings
	 *
	 * @access private
	 * @var string
	 * @since 8.0.0
	 **/
	private $option_layanan;
	private $option_free_shipping;
	
	/**
	 * Notices
	 *
	 * @access private
	 * @var array
	 * @since 8.1.10
	 **/
	private $notice;
	
	/**
	 * Constructor
	 *
	 * @access public
	 * @return void
	 * @since 8.0.0
	 **/
	public function __construct(){
		$this->id                     = 'jne_shipping';
		$this->method_title           = __('JNE Shipping', 'agenwebsite');
		$this->method_description     = __( 'Plugin JNE Shipping mengintegrasikan ongkos kirim dengan total belanja pelanggan Anda.', 'agenwebsite' );
		
		$this->option_layanan         = $this->plugin_id . $this->id . '_layanan';
		$this->option_free_shipping   = $this->plugin_id . $this->id . '_free_shipping';
        $this->option_license_code    = $this->plugin_id . $this->id . '_license_code';
        
        add_filter( 'woocommerce_settings_api_sanitized_fields_' . $this->id, array( &$this, 'sanitize_fields' ) );
        add_filter( 'woocommerce_settings_api_form_fields_' . $this->id, array( &$this, 'set_form_fields' ) );
		
		$this->init();		
	}

	/**
	 * Init JNE settings
	 *
	 * @access public
	 * @return void
	 * @since 8.0.0
	 **/
	public function init(){
        //$this->load_default_options();
		// Load the settings API
		// Override the method to add JNE Shipping settings
		$this->form_fields = WC_JNE()->shipping->form_fields();
		// Loads settings you previously init.
		$this->init_settings();
		
		// Load default services options
		$this->load_default_services();
		
        if( get_option( 'woocommerce_jne_shipping_license_code' ) ){
            // Define user set variables
            $this->enabled                    = ( array_key_exists( 'enabled', $this->settings ) ) ? $this->settings['enabled'] : '';
            $this->title                      = ( array_key_exists( 'title', $this->settings ) ) ? $this->settings['title'] : '';
            $this->default_weight             = ( array_key_exists( 'default_weight', $this->settings ) ) ? $this->settings['default_weight'] : '';
            $this->free_shipping_enabled      = ( array_key_exists( 'free_shipping_enabled', $this->settings ) ) ? $this->settings['free_shipping_enabled'] : '';
            $this->free_shipping_service      = ( array_key_exists( 'free_shipping_service', $this->settings ) ) ? $this->settings['free_shipping_service'] : '';
            $this->free_shipping_label        = ( array_key_exists( 'free_shipping_label', $this->settings ) ) ? $this->settings['free_shipping_label'] : '';
            $this->free_shipping_hide_etd     = ( array_key_exists( 'free_shipping_hide_estimasi', $this->settings ) ) ? $this->settings['free_shipping_hide_estimasi'] : '';
            $this->free_shipping_min_price    = ( array_key_exists( 'free_shipping_min_price', $this->settings ) ) ? $this->settings['free_shipping_min_price'] : '';
        }
		// Save settings in admin
		add_action( 'woocommerce_update_options_shipping_' . $this->id, array( &$this, 'process_admin_options' ) );
		add_action( 'woocommerce_update_options_shipping_' . $this->id, array( &$this, 'process_admin_jne' ) );
		
	}
    
	/**
	 * Load default JNE services
	 *
	 * @access private
	 * @return void
	 * @since 8.0.0
	 **/
	private function load_default_services(){
		
		$servives_options = get_option( $this->option_layanan );
		if( ! $servives_options ) {
		
			$data_to_save = WC_JNE()->shipping->default_service();
			
			update_option( $this->option_layanan, $data_to_save );
		}
	}

  public function emk_debug($name)
  {
    global $wpdb;
    $wpdb->insert( 
      'wp_emk_debug', 
      array( 
        'debug_info' => $name
      ), 
      array( 
        '%s' 
      ) 
    );
  }

	/**
	 * calculate_shipping function.
	 *
	 * @access public
	 * @param mixed $package
	 * @return void
	 * @since 8.0.0
	 **/
	public function calculate_shipping( $package = array() ){

        if(get_option('wmcs_store_currencies', array())) {
          $store_currencies = get_option('wmcs_store_currencies', array());
          $currency = $store_currencies['IDR']['exchange_rate_value'];
        } else {
          $currency = false;
        }


        if( ! $this->enabled ) return false;
        
        $layanan_jne = get_option( $this->option_layanan );

        $country= WC()->customer->get_shipping_country();
        $state	= WC()->customer->get_shipping_state();
        $city	= WC()->customer->get_shipping_city();

        if( $country != 'ID' ) return false;

        $total_weight = $this->calculate_weight( $package['contents'] );
        $jne_weight = WC_JNE()->shipping->calculate_jne_weight( $total_weight );
        $weight = $jne_weight;

        $cost = $this->_get_costs( $state, $city, $weight );
        

        $totalamount = floatval( preg_replace( '#[^\d.]#', '', WC()->cart->get_cart_total() ) );

        if( empty( $cost ) ) return false;

        if( sizeof( $package ) == 0 ) return;

        foreach( $layanan_jne as $service ){
            $service_id = $service['id'];
            $service_name = $service['name'];
            $service_enable = $service['enable'];
            $service_extra_cost = $service['extra_cost'];

            if( array_key_exists( $service_id, $cost) ){
                $etd = $cost[$service_id]['etd'];

                if($currency) {
                  $tarif = $cost[$service_id]['harga']/$currency;
                } else {
                  $tarif = $cost[$service_id]['harga'];
                }
                


            }else{
                $tarif = 0;
            }

            if( ! empty($tarif) && $tarif != 0 && $service_enable == 1) {

                $tarif = $tarif * $weight;
                
                $args_free_shipping = array(
                    'service_id'    => $service_id,
                    'tarif'         => $tarif,
                    'totalamount'   => $totalamount,
                    'state'         => $state,
                    'city'          => $city,
                    'service_name'  => $service_name,
                    'products'      => $package['contents']
                );
                
                $free_shipping = $this->calculate_free_shipping( $args_free_shipping );

                // Hook filter for another plugin
                $free_shipping = apply_filters( 'woocommerce_jne_shipping_tarif', $free_shipping );

                $tarif = $free_shipping['tarif'];
                $label = $this->set_label( $free_shipping['label'], $etd, $service_id );

                if( ! empty( $service_extra_cost ) ) $tarif += $service_extra_cost;

                $rate = array(
                    'id'	=> $this->id . '_' . $service_id,
                    'label'	=> $label,
                    'cost'	=> $tarif
                );

                $this->add_rate( $rate );

            }

        }//end foreach layanan

    }
		
	/**
	 * Calculate Total Weight
	 * This function will calculated total weight for all product
	 *
	 * @access private
	 * @param mixed $products
	 * @return integer Total Weight in Kilograms
	 * @since 8.0.0
	 **/
	private function calculate_weight( $products ){
        $weight = 0;
        $weight_unit = WC_JNE()->get_woocommerce_weight_unit();
        $default_weight = $this->default_weight;

        // Default weight JNE settings is Kilogram
        // Change default weight settings to gram if woocommerce unit is gram
        if( $weight_unit == 'g' )
            $default_weight = $default_weight * 1000;

        foreach( $products as $item_id => $item ){
            $product = $item['data'];

            if( $product->is_downloadable() == false && $product->is_virtual() == false ) {
                $product_weight = $product->get_weight() ? $product->get_weight() : $default_weight;
                $product_weight = ( $product_weight == 0 ) ? $default_weight : $product_weight;

                /*
                 * Volumetrik
                 */
                $is_volumetrik_enable = get_post_meta( $item['product_id'], '_is_jne_volumetrik_enable', TRUE );
                if( ! empty( $product->length ) && ! empty( $product->width ) && ! empty( $product->height ) ){
                    if( $is_volumetrik_enable == 'yes' ){
                        $volum_weight = $product->length * $product->width * $product->height / 6000;
                        if( $volum_weight > $product_weight ){
                            $product_weight = $volum_weight;
                        }
                    }
                }

                $product_weight = $product_weight * $item['quantity'];

                // Change product weight to kilograms
                if ($weight_unit == 'g')
                    $product_weight = $product_weight / 1000;

                $weight += $product_weight;

            }
        }

        $weight = number_format((float)$weight, 2, '.', '');

        return $weight;
    }
    
	/**
	 * Calculate Free Shipping
	 * Handle the logic of free shipping
	 *
	 * @access private
	 * @param array $args
	 * @return integer
	 */
	private function calculate_free_shipping( $args ){
		$city = explode( ', ', $args['city'] );

        // from option settings jne
        $args['option_free_shipping'] = get_option( $this->option_free_shipping );
        $args['min_price']  = $this->free_shipping_min_price;

        // check is free shipping each rules
		$args['is_free_provinsi']	= $this->is_free_provinsi( $this->_get_provinsi_name( $args['state'] ) );
		$args['is_free_kota']		= $this->is_free_city( $city[1] );
        $args['is_free_product']    = $this->is_free_product( $args['products'], $args['option_free_shipping']['product'] );
        $args['is_free_date']       = $this->is_free_date( $args['option_free_shipping']['date'] );
        $args['is_free_price']      = ( $args['totalamount'] >= $args['min_price'] ) ? TRUE : FALSE;

        // default value
        $valid_rules = false;
        $free_shipping = array(
            'tarif' => $args['tarif'],
            'label' => $this->title .' '. $args['service_name'] .' ',
        );
        
        // check rules
		if( $this->free_shipping_enabled == 'yes' ){
			if( is_array( $this->free_shipping_service ) ){
				if( in_array( $args['service_id'], $this->free_shipping_service ) ){
                    $valid_rules = WC_JNE()->shipping->valid_rules_free_shipping( $args );
				}
			}

            // if valid rules set free shipping
            if( $valid_rules ){
                $free_shipping['tarif'] = 0;
                if( !empty( $this->free_shipping_label ) ){
                    $free_shipping['label'] = $this->free_shipping_label;
                }else{
                    $free_shipping['label'] = __( 'Pengiriman Gratis', 'agenwebsite' );
                }
            }            
        }

		return $free_shipping;
	}

    /**
     * Set Label
     *
     * @access private
     * @param string $label
     * @param string $etd
     * @param string $service_id
     * @return string
     * @since 8.1.10
     */
    private function set_label( $label, $etd, $service_id ){
        $hide_etd = $this->free_shipping_hide_etd;

        $etd = sprintf( __( ' ( %s hari )', 'agenwebsite' ), $etd );

        if( $this->free_shipping_enabled == 'yes' && is_array( $this->free_shipping_service ) && in_array( $service_id, $this->free_shipping_service ) ){
            if( ! empty( $hide_etd ) && $hide_etd == 'yes' ){
                $etd = '';
            }
        }

        $new_label = sprintf( '%s%s', $label, $etd );

        return $new_label;
    }
    /**
     * Sanitize Fields
     *
     * @access public
     * @param array $sanitize_fields
     * @return array $new_sanitize_fields
     * @since 8.1.10
     */
    public function sanitize_fields( $sanitize_fields ){
        /*
         * replace option settings with sanitize fields
         */
        $sanitize_fields = array_replace( $this->settings, $sanitize_fields );
        
        $new_sanitize_fields = $sanitize_fields;
        $options = get_option( $this->plugin_id . $this->id .'_settings' );
        $options_backup = get_option( $this->plugin_id . $this->id . '_settings_backup' );
        
        /*
         * jika license code kosong maka kosongkan post sanitize
         * dan lakukan update option ke settings backup
         * settings backup berfungsi untuk mengembalikan option ke option utama
         * jika license code ada dan option utama kosong maka sanitize field diisi dengan option settings backup
         */
        if( empty( $sanitize_fields['license_code'] ) ){
            $new_sanitize_fields = '';
            if( is_array( $options ) && ! empty( $options ) ){
                update_option( $this->plugin_id . $this->id . '_settings_backup', $options );
            }
        }else{
            if( $options_backup ){
                if( ! $options || empty( $options ) ){
                    $new_sanitize_fields = $options_backup;
                }
            }
        }
        
        return $new_sanitize_fields;
    }
    
    /**
     * Set form fields
     * before show fields, check license code exists or not
     *
     * @access public
     * @param array $sanitize_fields
     * @return array $new_sanitize_fields
     * @since 8.1.10
     */
    public function set_form_fields( $form_fields ){
        
        if( get_option( $this->option_license_code ) ){

            $current_tab = empty( $_GET['tab_jne'] ) ? 'general' : sanitize_title( $_GET['tab_jne'] );
            
            $form_field = WC_JNE()->shipping->get_form_fields();
            foreach( $form_field as $name => $data ){
                if( $name == $current_tab ){
                    $form_fields = $data['fields'];
                }
            }
        }
        return $form_fields;
    }
    
	/**
	 * Settings Tab
	 *
	 * @access private 
	 * @return HTML
	 * @since 8.1.10
	 */
    private function settings_tab(){
        
        $tabs = array();
        
        if( get_option( $this->option_license_code ) ){
            
            foreach( WC_JNE()->shipping->get_form_fields() as $name => $data ){
                $tab[$name] = $data['label'];
                $tabs = array_merge( $tabs, $tab );
            }
            
        }
        
        $current_tab = empty( $_GET['tab_jne'] ) ? 'general' : sanitize_title( $_GET['tab_jne'] );
        
        $tab  = '<h2 class="nav-tab-wrapper woo-nav-tab-wrapper">';
        
        foreach( $tabs as $name => $label ){
            $tab .= '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=shipping&section=wc_jne&tab_jne=' . $name ) . '" class="nav-tab ' . ( $current_tab == $name ? 'nav-tab-active' : '' ) . '">' . $label . '</a>';
        }
        
        $tab .= '</h2>';
        
        return $tab;
    }
	
    /**
     * Validate license code
     * check license code to api
     *
     * @access public
     * @param array $sanitize_fields
     * @return array $new_sanitize_fields
     * @since 8.1.10
     */
    public function validate_license_code_field( $key ){
        $text = $this->get_option( $key );
        $field = $this->get_field_key( $key );
        
        if( isset( $_POST[ $field ] ) ){
            $text = wp_kses_post( trim( stripslashes( $_POST[ $field ] ) ) );   
            
            $valid_license = $this->validate_license_code( $text );
            
            update_option( $this->option_license_code, $valid_license );
        }
        
        return $valid_license;
    }

    /**
	 * Process admin JNE shipping
	 *
	 * @access public
	 * @return void
	 * @since 8.0.0
	 **/
	public function process_admin_jne(){
        
		$free_product = ( $_POST && isset( $_POST['jne_shipping_free_shipping_product'] ) ) ? $_POST['jne_shipping_free_shipping_product'] : '';
		$free_provinsi = ( $_POST && isset( $_POST['jne_shipping_free_shipping_provinsi'] ) ) ? $_POST['jne_shipping_free_shipping_provinsi'] : '';
		$free_kota = ( $_POST && isset( $_POST['jne_shipping_free_shipping_provinsi'] ) ) ? $_POST['jne_shipping_free_shipping_city'] : '';
		$service_id = ( $_POST && isset( $_POST['service_id'] ) ) ? $_POST['service_id'] : '';
		$service_name = ( $_POST && isset( $_POST['service_name'] ) ) ? $_POST['service_name'] : '';
		$service_extra_cost = ( $_POST && isset( $_POST['service_extra_cost'] ) ) ? $_POST['service_extra_cost'] : '';
		$service_enable = ( $_POST && isset( $_POST['service_enable'] ) ) ? $_POST['service_enable'] : '';
		$date = ( $_POST && isset( $_POST['jne_shipping_free_shipping_date'] ) ) ? $_POST['jne_shipping_free_shipping_date'] : '';
        
		// save free shipping product field
		$free_product = ( !empty( $free_product ) || strpos( $free_product, ',' ) ) ? explode( ',', $free_product ) : '';
		$save_free_shipping['product'] = $free_product;
		
		// save free shipping provinsi field
		$free_provinsi = ( !empty( $free_provinsi ) || strpos( $free_provinsi, ',' ) ) ? explode( ',', $free_provinsi ) : '';
		$save_free_shipping['provinsi'] = $free_provinsi;
		
		// save free shipping city field
		$free_kota = ( !empty( $free_kota ) || strpos( $free_kota, ',' ) ) ? explode( ',', $free_kota ) : '';
		$save_free_shipping['kota'] = $free_kota;
        
        // save free shipping date field
        $free_date = ( !empty( $date['from'] ) && !empty( $date['until'] ) && $this->is_valid_date( $date ) ) ? $date : '';
        $save_free_shipping['date'] = ( !empty($free_date) ) ? $free_date : '';
        
        // Update the option setting for free shipping
        if( ( $_POST && isset( $_POST['jne_shipping_free_shipping_product'] ) ) || ( $_POST && isset( $_POST['jne_shipping_free_shipping_provinsi'] ) ) || ( $_POST && isset( $_POST['jne_shipping_free_shipping_provinsi'] ) ) || ( $_POST && isset( $_POST['jne_shipping_free_shipping_date'] ) ) ){
            
            // check valid rentan tanggal
            $save_free_shipping['date'] = ( $date['until'] >= $date['from'] ) ? $free_date : '';
            
            update_option( $this->option_free_shipping, $save_free_shipping );
        }
		
		// Save order, enable, services field
        $save_layanan = array();
        if( ! empty( $service_id ) && is_array( $service_id ) ){
            foreach( $service_id as $i => $name ){
                if ( ! isset( $service_name[ $i ] ) ) {
                    continue;
                }
                if( ! isset( $service_enable[ $i ][ $name ] ) )
                    $service_enable[ $i ][ $name ] = 'no';	

                $save_layanan[] = array(
                    'id'		=> $name,
                    'enable'	=> $service_enable[ $i ][ $name ],
                    'name'		=> $service_name[ $i ][ $name ],
                    'extra_cost'=> $service_extra_cost[ $i ][ $name ],
                );
            }
            
            // Update the option setting for layanan
            update_option( $this->option_layanan, $save_layanan );
		
        }
		
		// If click button reset option
		if( isset( $_POST['reset_default'] ) && ! empty( $_POST['reset_default'] ) ){
			$default = $this->_reset_option();
			
			$save_layanan = $default['save_layanan'];
			$save_free_shipping = $default['save_free_shipping'];
            $valid_license = '';

            update_option( $this->option_layanan, $save_layanan );
            update_option( $this->option_free_shipping, $save_free_shipping );
			update_option( $this->plugin_id . $this->id . '_settings', $default['save_settings'] );
		}

        if( $_POST && wp_verify_nonce( $_REQUEST['_wpnonce'], 'woocommerce-settings' ) ){
            // clear transients
            if( isset( $_POST['jne_shipping_transient'] ) ){
                $this->clear_jne_transient();
            }

            // clear all expired transients
            if( isset( $_POST['clear_expired_transients'] ) ){
                $this->clear_all_expired_transient();
            }

            // create page cek ongkir
            if( isset( $_POST['create_page_cek_ongkir'] ) ){
                $post_id = WC_JNE()->create_page( 'Cek Ongkir', '[jne_cek_ongkir]' );
            }

            // create page jne tracking
            if( isset( $_POST['create_page_jne_tracking'] ) ){
                $post_id = WC_JNE()->create_page( 'Cek Resi', '[jne_tracking]' );
            }            
        }

    }

    /**
     * Clear JNE Transient
     * Will clear jne transient
     *
     * @access private
     * @return HTML notice
     * @since 8.1.10
     */
    private function clear_jne_transient(){
        global $wpdb;
        $sql = "DELETE FROM $wpdb->options WHERE option_name LIKE %s";
        $result = $wpdb->query( $wpdb->prepare( $sql, $wpdb->esc_like( '_transient_wc_ship_jne' ) . '%' ) );
        $wpdb->query( $wpdb->prepare( $sql, $wpdb->esc_like( '_transient_timeout_wc_ship_jne' ) . '%' ) );
        echo '<div class="updated"><p><b>' . sprintf( __( '%d Transients Rows Cleared', 'agenwebsite' ), $result ) . '</b></p></div>';        
    }

    /**
     * Clear all expired transient
     *
     * @access private
     * @return HTML notice
     * @since 8.1.03
     */
    private function clear_all_expired_transient(){
        global $wpdb;
        $sql = "DELETE a, b FROM $wpdb->options a, $wpdb->options b
        WHERE a.option_name LIKE %s
        AND a.option_name NOT LIKE %s
        AND b.option_name = CONCAT( '_transient_timeout_', SUBSTRING( a.option_name, 12 ) )
        AND b.option_value < %d";
        $rows = $wpdb->query( $wpdb->prepare( $sql, $wpdb->esc_like( '_transient_' ) . '%', $wpdb->esc_like( '_transient_timeout_' ) . '%', time() ) );

        $sql = "DELETE a, b FROM $wpdb->options a, $wpdb->options b
        WHERE a.option_name LIKE %s
        AND a.option_name NOT LIKE %s
        AND b.option_name = CONCAT( '_site_transient_timeout_', SUBSTRING( a.option_name, 17 ) )
        AND b.option_value < %d";
        $rows2 = $wpdb->query( $wpdb->prepare( $sql, $wpdb->esc_like( '_site_transient_' ) . '%', $wpdb->esc_like( '_site_transient_timeout_' ) . '%', time() ) );

        echo '<div class="updated"><p><b>' . sprintf( __( '%d Transients Rows Cleared', 'woocommerce' ), $rows + $rows2 ) . '</b></p></div>';        
    }

	/**
	 * Reset option to default
	 * Fungsi untuk tombol reset option pada halaman setting jne shipping
	 *
	 * @access private
	 * @return array
	 * @since 8.0.0
	 **/
	private function _reset_option(){				
		$jne_settings = array();
		
		foreach( WC_JNE()->shipping->form_fields() as $key => $value ){
			$jne_settings[$key] = $value['default'];
		}
		
		$free_shipping_default['provinsi'] = array();
		$free_shipping_default['kota'] = array();
        $free_shipping_default['product'] = array();
        $free_shipping_default['date'] = '';
		
		$data['save_layanan'] = WC_JNE()->shipping->default_service();
		$data['save_free_shipping'] = $free_shipping_default;
		$data['save_settings'] = $jne_settings;
		
		return $data;
	}
    
	/**
	 * Validate license code
	 * Fungsi untuk tombol reset option pada halaman setting jne shipping
	 *
	 * @access private
	 * @return array
	 * @since 8.0.0
	 **/
    private function validate_license_code( $code ){
        
        $saved_license = get_option( $this->option_license_code );
        
        if( empty( $code ) || $saved_license == $code ) return $code;
        
        WC_JNE()->api->license_code = $code;
        $response = WC_JNE()->api->remote_get( 'license_auth' );

        $this->notice['type'] = $response['status'];
        $this->notice['message'] = $response['message'];
        
        if( $response['status'] == 'error' ){
            $code = '';
        }
        
        add_action( 'jne_admin_notices', array( &$this, 'notice' ) );
        
        return $code;
    }
			
	/**
	 * JNE cost
	 * Mendapatkan harga dari api
	 *
	 * @access private
	 * @param string $state
	 * @param string $city
	 * @return array
	 * @since 8.0.0
	 **/
	private function _get_costs( $state, $city, $weight ){
		
        $tarif = 0;
        
		if( ! empty( $city ) ){

            // Check if we need to recalculate shipping for this city
            $city_hash = 'wc_ship_jne_' . md5( $city ) . WC_Cache_Helper::get_transient_version( 'jne_shipping' );

            if( false === ( $stored_tarif = get_transient( $city_hash ) ) ){

                // explode field city to kecamatan and kota
                // pattern is {kecamatan}, {kota}
                $explode_field_city = explode(', ', $city);

                $params = array(
                    'provinsi'  => $this->_get_provinsi_name( $state ),
                    'kecamatan' => $explode_field_city[0],
                    'kota'      => $explode_field_city[1],
                    'berat'     => $weight
                );

                // get data from API
                $response = WC_JNE()->api->remote_get( 'tarif', $params );
                
                // validate response status
                if( $response['status'] != 'error' ){

                    $tarif = $response['result']['tarif'];

                    // Store
                    WC_JNE()->set_transient( $city_hash, $tarif );

                }

            }else{

                $tarif = $stored_tarif;

            }

		}

        return $tarif;

	}
	
	/**
	 * Get nama provinsi Indonesia
	 * Mendapatkan nama provinsi berdasarkan id provinsi dari woocommerce
	 *
	 * @access private
	 * @param string $id of provinsi
	 * @return string
	 * @since 8.0.0
	 **/
	private function _get_provinsi_name( $id ){		
		$provinsi = '';
		$states = WC()->countries->get_states( 'ID' );
		
		foreach( $states as $id_provinsi => $nama_provinsi ){
			if( $id_provinsi == $id ){
				$provinsi = $nama_provinsi;
			}
		}
					
		return $provinsi;
	}
	
	/**
	 * Free city check
	 * Check the city is on the list of free city
	 *
	 * @access private
	 * @param string $city
	 * @return bool
	 * @since 8.0.0
	 **/
	private function is_free_city( $city ){
		$output = FALSE;
		$data_free = get_option( $this->option_free_shipping );
		
		if( $data_free && is_array( $data_free['kota'] ) )
			foreach( $data_free['kota'] as $free_city  )
				if( $free_city === $city )
					$output = TRUE;						
		
		return $output;
	}
    
	/**
	 * Free product check
	 * Check the product is on the list of free product
	 *
	 * @access private
	 * @param array $products
	 * @return bool
	 * @since 8.1.10
	 **/
    private function is_free_product( $products, $free_product ){
        
        if( is_array( $products ) && is_array( $free_product ) ){
            foreach( $products as $item_id => $item ){
                $product = $item['data'];
                $content_product[] = $product->id;
            }

            if( count( array_intersect( $content_product, $free_product ) ) > 0 ){
                return true;
            }
        }
        
        return false;
    }
		
	/**
	 * Free date check
	 * Check the product is on the list of free product
	 *
	 * @access private
	 * @param array $products
	 * @return bool
	 * @since 8.1.10
	 **/
    private function is_free_date( $date ){
        
        $free_date = array_map( 'strtotime', (array) $date );
        $today = date('Y-m-d', current_time( 'timestamp', 0 ) );

        if( !empty( $date ) && is_array( $date ) ){
            if( $free_date['from'] <= strtotime( $today ) && $free_date['until'] >= strtotime( $today ) ){
                return true;
            }
        }
        
        return false;
    }
		
    /**
     * Check valid date
     *
     * @access public
     * @param string $date
     * @return mixed
     * @since 8.1.10
     */
    public function is_valid_date( $date ){
        if( $date['until'] >= $date['from'] ){
            return true;
        }else{
            printf( '<div class="error"><p><b>%s</b></p></div>', __( 'Anda memasukkan tanggal yang tidak valid.', 'agenwebsite' ) );
        }

        return false;
    }

	/**
	 * Free provinsi check
	 * Check the provinsi is on the list of free provinsi
	 *
	 * @access private
	 * @param string $provinsi
	 * @return bool
	 * @since 8.0.0
	 **/
	private function is_free_provinsi( $provinsi ){
		$output = FALSE;
		$data_free = get_option( $this->option_free_shipping );
		
		if( $data_free && is_array( $data_free['provinsi'] ) ){
			foreach( $data_free['provinsi'] as $free_provinsi )
				if( $free_provinsi === $provinsi )
					$output = TRUE;
					
			return $output;	
		}
	}
	
	/**
	 * Notice
	 *
	 * @access private 
	 * @return HTML
	 * @since 8.1.10
	 */
    public function notice(){
        $type = ( $this->notice['type'] == 'error' ) ? 'error' : 'updated';
        echo '<div class="' . $type . '"><p><strong>' . $this->notice['message'] . '</strong></p></div>';
    }
    
	/**
	 * Admin Options
	 * Setup the gateway settings screen.
	 *
	 * @access public
	 * @return HTML of the admin jne settings
	 * @since 8.0.0
	 */
	public function admin_options() {
        $class = empty( $_GET['tab_jne'] ) ? 'general' : sanitize_title( $_GET['tab_jne'] );
        
		$html  = '<div id="agenwebsite_woocommerce" class="' . $class . '">' . "\n";
        
			// AW head logo and links and table status
			ob_start();
			$this->aw_head();
			$html .= ob_get_clean();
	
			$html .= sprintf( '<h3>%s %s</h3>', $this->method_title, __( 'Settings', 'agenwebsite' ) ) . "\n";
			$html .= '<p>' . $this->method_description . '</p>' . "\n";			
			
            $html .= $this->settings_tab();
			
			$html .= '<div id="agenwebsite_notif">';
			ob_start();
			do_action( 'jne_admin_notices' );
			$html .= ob_get_clean();
			$html .= '</div>';
			
			$html .= '<table class="form-table hide-data">' . "\n";
	
				ob_start();
				$this->generate_settings_html();
				$html .= ob_get_clean();
	
			$html .= '</table>' . "\n";
			
		$html .= '</div>' . "\n";
		
		echo $html;
	}
	
	/**
	 * AgenWebsite Head
	 *
	 * @access private static
	 * @return HTML for the admin logo branding and usefull links.
	 * @since 8.0.0
	*/
	private function aw_head(){			
		$html  = '<div class="agenwebsite_head">';
		$html .= '<div class="logo">' . "\n";
		$html .= '<a href="' . esc_url( 'http://agenwebsite.com/' ) . '" target="_blank"><img id="logo" src="' . esc_url( apply_filters( 'aw_logo', WC_JNE()->plugin_url() . '/assets/images/logo.png' ) ) . '" /></a>' . "\n";
		$html .= '</div>' . "\n";
		$html .= '<ul class="useful-links">' . "\n";
			$html .= '<li class="documentation"><a href="' . esc_url( WC_JNE()->url_dokumen ) . '" target="_blank">' . __( 'Dokumentasi', 'agenwebsite' ) . '</a></li>' . "\n";
			$html .= '<li class="support"><a href="' . esc_url( WC_JNE()->url_support ) . '" target="_blank">' . __( 'Bantuan', 'agenwebsite' ) . '</a></li>' . "\n";
		$html .= '</ul>' . "\n";
		
        if( WC_JNE()->get_license_code() != '' ){
            ob_start();
            include_once( WC_JNE()->plugin_path() . '/views/html-admin-jne-settings-status.php' );
            $html .= ob_get_clean();
        }
			
		$html .= '</div>';
		echo $html;
	}
    
	/**
	 * Field type license_code
	 *
	 * @access public
	 * @return HTML
	 * @since 8.1.10
	 **/
    public function generate_license_code_html(){
        $license_code = get_option( $this->option_license_code );
        $html = '';
        if( ! $license_code && empty( $license_code ) ){
            $html .= sprintf('<div class="notice_wc_jne woocommerce-jne"><p><b>%s</b> &#8211; %s</p><p class="submit">%s %s</p></div>',
                   __( 'Masukkan kode lisensi untuk mengaktifkan WooCommerce JNE', 'agenwebsite' ),
                   __( 'anda bisa mendapatkan kode lisensi dari halaman akun AgenWebsite.', 'agenwebsite'  ),
                   '<a href="http://agenwebsite.com/account" target="new" class="button-primary">' . __( 'Dapatkan kode lisensi', 'agenwebsite' ) . '</a>',
                   '<a href="' . esc_url( WC_JNE()->url_dokumen ) . '" class="button-primary" target="new">' . __( 'Baca dokumentasi', 'agenwebsite' ) . '</a>' );
        }
        
        $html .= '<tr valid="top">';
            $html .= '<th scope="row" class="titledesc">';
                $html .= '<label for="' . $this->option_license_code . '">' . __( 'Kode Lisensi', 'agenwebsite' ) . '</label>';
            $html .= '</th>';
            $html .= '<td class="forminp">';
                $html .= '<fieldset>';
                    $html .= '<legend class="screen-reader-text"><span>' . __( 'Kode Lisensi', 'agenwebsite' ) . '</span></legend>';
                    $html .= '<input class="input-text regular-input " type="text" name="' . $this->option_license_code . '" id="' . $this->option_license_code . '" style="" value="' . esc_attr( get_option( $this->option_license_code ) ) . '" placeholder="' . __( 'Kode Lisensi', 'agenwebsite' ) . '">';
                    $html .= '<p class="description">' . __( 'Masukkan kode lisensi yang kamu dapatkan dari halaman akun agenwebsite.', 'agenwebsite' ) . '</p>';
                $html .= '</fieldset>';
            $html .= '</td>';
        $html .= '</tr>';
        
        return $html;
    }
					
	/**
	 * Field type jne_service
	 *
	 * @access public
	 * @return HTML
	 * @since 8.0.0
	 **/
	public function generate_jne_service_html(){
		$html = '<tr valign="top">';
			$html .= '<th scope="row" class="titledesc">' . __( 'Layanan JNE', 'agenwebsite' ) . '</th>';
			$html .= '<td class="forminp">';
				$html .= '<table class="widefat wc_input_table sortable" cellspacing="0">';
					$html .= '<thead>';
						$html .= '<tr>';
							$html .= '<th class="sort">&nbsp;</th>';
							$html .= '<th>Nama Pengiriman ' . WC_JNE()->help_tip( 'Metode pengiriman yang digunakan.' ) . '</th>';
							$html .= '<th>Tambahan Biaya ' . WC_JNE()->help_tip( 'Biaya tambahan, bisa disetting untuk tambahan biaya packing dan lain-lain.' ) . '</th>';
							$html .= '<th style="width:14%;text-align:center;">Aktifkan</th>';
						$html .= '</tr>';
					$html .= '</thead>';
					$html .= '<tbody>';
						
						$i = 0;
						foreach( get_option( $this->option_layanan ) as $service ) :
						
							$html .= '<tr class="service">';
								$html .= '<td class="sort"></td>';
								$html .= '<td><input type="text" value="' . $service['name'] . '" name="service_name[' . $i . '][' . $service['id'] . ']" /></td>';
								$html .= '<td><input type="number" value="' . $service['extra_cost'] . '" name="service_extra_cost[' . $i . '][' . $service['id'] . ']" /></td>';
								$html .= '<td style="text-align:center;"><input type="checkbox" value="1" ' . checked( $service['enable'], 1, FALSE ) . ' name="service_enable[' . $i . '][' . $service['id'] . ']" /><input type="hidden" value="' . $service['id'] . '" name="service_id[' . $i . ']" /></td>';
							$html .= '</tr>';
									
							$i++;
						endforeach;
						
					$html .= '</tbody>';
				$html .= '</table>';
			$html .= '</td>';
		$html .= '</tr>';
		
		return $html;
	}
	
	/**
	 * Field type free_shipping_provinsi
	 *
	 * @access public
	 * @return HTML
	 * @since 8.0.0
	 **/
	public function generate_free_shipping_provinsi_html() {
		$options = get_option( $this->option_free_shipping );
		$selected = ( is_array( $options['provinsi'] ) ) ? esc_attr( json_encode( $options['provinsi'] ) ) : '';
		$value = ( is_array( $options['provinsi'] ) ) ? implode( ',', array_values( $options['provinsi'] ) ) : '';

		$html = '<tr valign="top">' . "\n";
			
			$html .= '<th scope="row" class="titledesc">' . "\n";
				$html .= '<label for="jne_shipping_free_shipping_provinsi">' . __( 'Pilihan Provinsi Gratis', 'agenwebsite' ) . '</label>' . "\n";
			$html .= '</th>' . "\n";
			
			$html .= '<td class="forminp">' . "\n";
				$html .= '<fieldset>' . "\n";
					$html .= '<legend class="screen-reader-text"><span>' . __( 'Pilihan Provinsi Gratis', 'agenwebsite' ) . '</span></legend>' . "\n";
											
					$html .= '<input type="hidden" id="jne_shipping_free_shipping_provinsi" name="jne_shipping_free_shipping_provinsi" data-placeholder="' . __( 'cari provinsi&hellip;', 'agenwebsite' ) . '" data-selected="' . $selected . '" value="' . $value . '" class="input-xlarge" />' . "\n";
					$html .= '<p class="description">' . __( 'Pilih provinsi tujuan yang ingin diberikan pengiriman gratis.', 'agenwebsite' ) . '</p>';
					$html .= '<a class="select_none button" href="#">' . __( 'Hapus semua', 'agenwebsite' ) . '</a>';        
					
				$html .= '</fieldset>' . "\n";
			$html .= '</td>' . "\n";
			
		$html .= '</tr>' . "\n";
								
		return $html;			
	}
	
	/**
	 * Field type free_shipping_city
	 *
	 * @access public
	 * @return HTML
	 * @since 8.0.0
	 **/
	public function generate_free_shipping_city_html() {
		$options = get_option( $this->option_free_shipping );
		$selected = ( is_array( $options['kota'] ) ) ? esc_attr( json_encode( $options['kota'] ) ) : '';
		$value = ( is_array( $options['kota'] ) ) ? implode( ',', array_values( $options['kota'] ) ) : '';

		$html = '<tr valign="top">' . "\n";
			
			$html .= '<th scope="row" class="titledesc">' . "\n";
				$html .= '<label for="jne_shipping_free_shipping_city">' . __( 'Pilihan Kota Gratis', 'agenwebsite' ) . '</label>' . "\n";
			$html .= '</th>' . "\n";
			
			$html .= '<td class="forminp">' . "\n";
				$html .= '<fieldset>' . "\n";
					$html .= '<legend class="screen-reader-text"><span>' . __( 'Pilihan Kota Gratis', 'agenwebsite' ) . '</span></legend>' . "\n";
											
					$html .= '<input type="hidden" id="jne_shipping_free_shipping_city" name="jne_shipping_free_shipping_city" data-placeholder="' . __( 'cari kota&hellip;', 'agenwebsite' ) . '" data-selected="' . $selected . '" value="' . $value . '" class="input-xlarge" />' . "\n";
					$html .= '<p class="description">' . __( 'Pilih kota tujuan yang ingin diberikan pengiriman gratis.', 'agenwebsite' ) . '</p>';
					$html .= '<a class="select_jabodetabek button" href="#">' . __( 'Jabodetabek', 'agenwebsite' ) . '</a> ';
					$html .= '<a class="select_none button" href="#">' . __( 'Hapus semua', 'agenwebsite' ) . '</a>';
					
				$html .= '</fieldset>' . "\n";
			$html .= '</td>' . "\n";
			
		$html .= '</tr>' . "\n";
								
		return $html;
	}
    
	/**
	 * Field type free_shipping_date
	 *
	 * @access public
	 * @return HTML
	 * @since 8.1.10
	 **/
    public function generate_free_shipping_date_html(){
		$options = get_option( $this->option_free_shipping );
        $from = ( array_key_exists( 'date', $options ) && is_array( $options['date'] ) ) ? $options['date']['from'] : '';
        $until = ( array_key_exists( 'date', $options ) && is_array( $options['date'] ) ) ? $options['date']['until'] : '';
        
		$html = '<tr valign="top">' . "\n";
			
			$html .= '<th scope="row" class="titledesc">' . "\n";
				$html .= '<label for="jne_shipping_free_shipping_date_from">' . __( 'Berdasarkan Tanggal', 'agenwebsite' ) . '</label>' . "\n";
			$html .= '</th>' . "\n";
			
			$html .= '<td class="forminp">' . "\n";
				$html .= '<fieldset>' . "\n";
					$html .= '<legend class="screen-reader-text"><span>' . __( 'Berdasarkan Tanggal', 'agenwebsite' ) . '</span></legend>' . "\n";
											
					$html .= '<input type="text" id="jne_shipping_free_shipping_date_from" name="jne_shipping_free_shipping_date[from]" class="jquery-datepicker input-xlarge" placeholder="' . __( 'pilih tanggal awal', 'agenwebsite' ) . '" value="' . $from . '" />' . "\n";
					$html .= '<input type="text" id="jne_shipping_free_shipping_date_until" name="jne_shipping_free_shipping_date[until]" class="jquery-datepicker input-xlarge" placeholder="' . __( 'pilih tanggal akhir', 'agenwebsite' ) . '" value="' . $until . '" />' . "\n";
					$html .= '<p class="description">' . sprintf( __( 'Pilih rentan tanggal yang ingin diberikan pengiriman gratis. <i>Local time is <code>%s</code></i>', 'agenwebsite' ), date('Y-m-d H:i:s', current_time( 'timestamp', 0 ) ) ) . '</p>';
					
				$html .= '</fieldset>' . "\n";
			$html .= '</td>' . "\n";
			
		$html .= '</tr>' . "\n";
								
		return $html;
    }
    
	/**
	 * Field type free_shipping_product
	 *
	 * @access public
	 * @return HTML
	 * @since 8.1.10
	 **/
    public function generate_free_shipping_product_html(){
        $options = get_option( $this->option_free_shipping );
        $product_ids = ( array_key_exists( 'product', $options ) && is_array( $options['product'] ) ) ? array_filter( array_map( 'absint', (array) $options['product'] ) ) : array();
        $json_ids    = array();
        
        foreach ( $product_ids as $product_id ) {
            $product = wc_get_product( $product_id );
            if ( is_object( $product ) ) {
                $json_ids[ $product_id ] = wp_kses_post( html_entity_decode( $product->get_formatted_name(), ENT_QUOTES, get_bloginfo( 'charset' ) ) );
            }
        }
        
        $selected = esc_attr( json_encode( $json_ids ) );
		$value = ( array_key_exists( 'product', $options ) && is_array( $options['product'] ) ) ? implode( ',', array_values( $options['product'] ) ) : '';

		$html = '<tr valign="top">' . "\n";
			
			$html .= '<th scope="row" class="titledesc">' . "\n";
				$html .= '<label for="jne_shipping_free_shipping_product">' . __( 'Berdasarkan Produk', 'agenwebsite' ) . '</label>' . "\n";
			$html .= '</th>' . "\n";
			
			$html .= '<td class="forminp">' . "\n";
				$html .= '<fieldset>' . "\n";
					$html .= '<legend class="screen-reader-text"><span>' . __( 'Berdasarkan Produk', 'agenwebsite' ) . '</span></legend>' . "\n";
											
					$html .= '<input type="hidden" id="jne_shipping_free_shipping_product" name="jne_shipping_free_shipping_product" data-placeholder="' . __( 'cari kota&hellip;', 'agenwebsite' ) . '" data-selected="' . $selected . '" value="' . $value . '" data-multiple="true" class="wc-product-search input-xlarge" />' . "\n";
					$html .= '<p class="description">' . __( 'Pilih produk yang ingin diberikan pengiriman gratis.', 'agenwebsite' ) . '</p>';
					$html .= '<a class="select_none button" href="#">' . __( 'Hapus semua', 'agenwebsite' ) . '</a>';
					
				$html .= '</fieldset>' . "\n";
			$html .= '</td>' . "\n";
			
		$html .= '</tr>' . "\n";
								
		return $html;
    }
    
	/**
	 * Field type button
	 *
	 * @access public
	 * @return HTML
	 * @since 8.1.10
	 **/
    public function generate_button_html( $key, $data ){
        
        $field = $this->get_field_key( $key );
        $defaults = array(
            'title'             => '',
            'disabled'          => false,
            'class'             => '',
            'css'               => '',
            'placeholder'       => '',
            'desc_tip'          => false,
            'description'       => '',
            'custom_attributes' => array()
        );
        
        $data = wp_parse_args( $data, $defaults );
        
        ob_start();
        ?>
            <tr valign="top">
                <th scope="row" class="titledesc">
                    <label for="<?php esc_attr( $field );?>"><?php echo wp_kses_post( $data['label'] );?></label>
                </th>
                <td class="forminp">
                    <fieldset>
                        <legend class="screen-reader-text"><span><?php echo wp_kses_post( $data['title'] );?></span></legend>
                        <button type="submit" name="<?php echo esc_attr( $key ); ?>" id="<?php echo esc_attr( $field ); ?>" class="button <?php echo esc_attr( $data['class'] );?>" style="<?php echo esc_attr( $data['css'] ); ?>" <?php echo $this->get_custom_attribute_html( $data );?>><?php echo wp_kses_post( $data['placeholder'] );?></button>
                        <?php echo $this->get_description_html( $data ); ?>
                    </fieldset>
                </td>
            </tr>
        <?php
        return ob_get_clean();

    }
		
}
	
endif;
