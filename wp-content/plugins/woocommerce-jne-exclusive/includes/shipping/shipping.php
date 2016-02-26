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

if ( !class_exists( 'WC_JNE_Shipping' ) ) :
	
/**
 * Class WooCommerce JNE
 *
 * @since 8.0.0
 **/
class WC_JNE_Shipping{
	
	/**
	 * @var WC_JNE_Cek_Ongkir $cek_ongkir
	 * @since 8.0.0
	 */
	public $cek_ongkir;
	
	/**
	 * Constructor
	 *
	 * @return void
	 * @since 8.0.0
	 **/
	public function __construct(){			
        /**
         * Initialise JNE shipping method.
         *
         * @since 8.0.0
         **/
        add_action( 'woocommerce_shipping_init', array( &$this, 'shipping_method' ) );

        /**
         * Add Shipping Method
         *
         * Tell method JNE shipping to woocommerce. Hey Woo AgenWebsite JNE is Here !! :D
         *
         * @since 8.0.0
         **/
        add_filter( 'woocommerce_shipping_methods', array( &$this, 'add_jne_shipping_method' ) );

        // filter default chosen shipping
        add_filter( 'woocommerce_shipping_chosen_method', array( &$this, 'get_default_method' ), 10, 2 );
        
        // Release the frontend
        if( $this->is_enable() ) new WC_JNE_Frontend();

        // Release JNE CekOngkir
        $this->cek_ongkir = new WC_JNE_Cek_Ongkir();

        // Release JNE Packing Kayu
        new WC_JNE_Packing_Kayu();

        // Release volumetrik
        new WC_JNE_Volumetrik();
    }
	
	/**
	 * Init Shipping method
	 *
	 * @access public
	 * @return void
	 * @since 8.0.0
	 **/
	public function shipping_method(){
		include_once( 'shipping-method.php' );	
	}

	/**
	 * Add JNE shipping method
	 *
	 * @access public
	 * @return void
	 * @since 8.0.0
	 **/
	public function add_jne_shipping_method( $methods ) {
		$methods[] = 'WC_JNE';
		return $methods;	
	}
		
	/**
	 * Get the default method
	 * @param  array  $available_methods
	 * @param  boolean $current_chosen_method
	 * @return string
     * @since 8.1.02
	 */
	public function get_default_method( $default_method, $available_methods ) {
		$selection_priority = get_option( 'woocommerce_shipping_method_selection_priority', array() );
		
		if ( ! empty( $available_methods ) ) {

			// Is a method already chosen?
			if ( ! empty( $current_chosen_method ) && ! isset( $available_methods[ $current_chosen_method ] ) ) {
				foreach ( $available_methods as $method_id => $method ) {
					if ( strpos( $method->id, $current_chosen_method ) === 0 ) {
						return $method->id;
					}
				}
			}

			// Order by priorities and costs
			$prioritized_methods = array();

			foreach ( $available_methods as $method_id => $method ) {
				$priority                         = isset( $selection_priority[ $method_id ] ) ? absint( $selection_priority[ $method_id ] ) : 1;
				if ( empty( $prioritized_methods[ $priority ] ) ) {
					$prioritized_methods[ $priority ] = array();
				}
				$prioritized_methods[ $priority ][ $method_id ] = $method->cost;
			}

			$prioritized_methods = current( $prioritized_methods );

			return current( array_keys( $prioritized_methods ) );
		}

		return false;
	}

	/**
	 * Get total weight
	 *
	 * @access public
	 * @return integer Total weight
	 * @since 8.0.0
	 **/
	public function get_total_weight_checkout(){
        $settings = get_option( 'woocommerce_jne_shipping_settings' );
        $default_weight = $settings['default_weight'];
        $weight = 0;
        $weight_unit = WC_JNE()->get_woocommerce_weight_unit();

        foreach ( WC()->cart->cart_contents as $cart_item_key => $values ) {
            $_product = $values['data'];
            if( $_product->is_downloadable() == false && $_product->is_virtual() == false ) {
                $_product_weight = $_product->get_weight();

                if( $_product_weight == '' ){
                    if( $weight_unit == 'g' ){
                        $default_weight *= 1000;
                    }
                    $_product_weight = $default_weight;   
                }

                $weight += $_product_weight * $values['quantity'];

                $output['virtual'] = 'yes';
            }
        }

        if( $weight_unit == 'g' ){
            if( $weight > 1000 ){
                $weight = $weight / 1000;
                $weight = number_format((float)$weight, 2, '.', '');
                add_filter( 'weight_unit_total_weight', array( &$this, 'change_to_kg' ) );
            }
        }

        $output['weight'] = $weight;

        return $output;
    }
    
	/**
	 * Change to kilograms
	 *
	 * @access public
	 * @return string
	 * @since 8.1.0
	 **/
	public function change_to_kg(){
        return 'kg';
    }
	
	/**
	 * Return the number of decimals after the decimal point.
	 *
	 * @access public
	 * @return int
	 * @since 8.0.1
	 **/
	public function get_price_decimals(){
		if( function_exists( 'wc_get_price_decimals' ) )
            return wc_get_price_decimals();
        else
            return absint( get_option( 'woocommerce_price_num_decimals', 2 ) );
	}
	
	/**
	 * Check plugin is active
	 *
	 * @access public
	 * @return bool
	 * @since 8.0.0
	 **/
	public function is_enable(){
		$settings = get_option( 'woocommerce_jne_shipping_settings' );
        if( $settings && is_array( $settings ) ){
            if( ! array_key_exists( 'enabled', $settings ) ) return false;

            return ( $settings['enabled'] == 'yes' ) ? TRUE : FALSE;
        }
        
        return false;
	}
	
	/**
	 * Calculate JNE Weight
	 * To calculate weight tolerance from jne.
	 *
	 * @link http://jne.co.id/share_article.php?id=2013020404274222
	 * @access public
	 * @param integer $weight
	 * @return integer Total Weight in Kilograms
	 * @since 8.0.0
	 **/
	public function calculate_jne_weight( $weight ){
        if( WC_JNE_Shipping::is_decimal( $weight ) ){
            $desimal = explode( '.', $weight );
            $jne_weight = ( $desimal[0] == 0 || substr($desimal[1], 0, 1) > 3 || substr($desimal[1], 0, 2) > 30) ? ceil($weight) : floor($weight);
            $weight = ( $jne_weight == 0 ) ? 1 : $jne_weight;
        }
        
        return $weight;
	}
    
	/**
	 * Is Decimal
	 * For check the number is decimal.
	 *
	 * @access public
	 * @param integer
	 * @return bool
	 * @since 8.0.0
	 **/
	private static function is_decimal( $num ){
		return is_numeric( $num ) && floor( $num ) != $num;
	}

    /**
	 * Validate rules free shipping
	 * For check rules of free shipping
	 *
	 * @access public
	 * @param array $args
	 * @return bool
	 * @since 8.1.10
	 **/
    public function valid_rules_free_shipping( $args ){
        
		// settings of free shipping
		$data_settings		= $args['option_free_shipping'];
		$min_price			= $args['min_price'];
		$free_provinsi 		= $data_settings['provinsi'];
		$free_kota 			= $data_settings['kota'];
        $free_product       = $data_settings['product'];
        $free_date          = $data_settings['date'];

        // validate rule
		$is_free_provinsi	= $args['is_free_provinsi'];
		$is_free_kota		= $args['is_free_kota'];
        $is_free_product    = $args['is_free_product'];
        $is_free_date       = $args['is_free_date'];
        $is_free_price      = $args['is_free_price'];
        
        $totalamount = $args['totalamount'];
        $valid_rules = false;
        
        // harga
        if( !empty( $min_price ) && empty( $free_provinsi ) && empty( $free_kota ) && empty( $free_product ) && empty( $free_date ) ){
            if( $is_free_price ){
                $valid_rules = true;
            }
        }

        // provinsi
        if( !empty( $free_provinsi ) && empty( $min_price ) && empty( $free_kota ) && empty( $free_product ) && empty( $free_date ) ) {
            if( $is_free_provinsi ){
                $valid_rules = true;
            }
        }

        // kota
        if( !empty( $free_kota ) && empty( $min_price ) && empty( $free_provinsi ) && empty( $free_product ) && empty( $free_date ) ){
            if( $is_free_kota ){
                $valid_rules = true;
            }
        }

        // produk
        if( empty( $free_kota ) && empty( $min_price ) && empty( $free_provinsi ) && !empty( $free_product ) && empty( $free_date ) ){
            if( $is_free_product ){
                $valid_rules = true;
            }
        }

        // tanggal
        if( empty( $free_kota ) && empty( $min_price ) && empty( $free_provinsi ) && empty( $free_product ) && !empty( $free_date ) ){
            if( $is_free_date ){
                $valid_rules = true;
            }
        }

        // harga + provinsi
        if( !empty( $min_price ) && !empty( $free_provinsi ) && empty( $free_kota ) && empty( $free_product ) && empty( $free_date ) ){
            if( $is_free_price && $is_free_provinsi  ){
                $valid_rules = true;
            }
        }

        // harga + kota
        if( !empty( $min_price ) && empty( $free_provinsi ) && !empty( $free_kota ) && empty( $free_product ) && empty( $free_date ) ){
            if( $is_free_price && $is_free_kota ){
                $valid_rules = true;
            }
        }

        // harga + produk
        if( !empty( $min_price ) && empty( $free_provinsi ) && empty( $free_kota ) && !empty( $free_product ) && empty( $free_date ) ){
            if( $is_free_price && $is_free_product ){
                $valid_rules = true;
            }
        }

        // harga + tanggal
        if( !empty( $min_price ) && empty( $free_provinsi ) && empty( $free_kota ) && empty( $free_product ) && !empty( $free_date ) ){
            if( $is_free_price && $is_free_date ){
                $valid_rules = true;
            }
        }

        // provinsi + kota
        if( empty( $min_price ) && !empty( $free_provinsi ) && !empty( $free_kota ) && empty( $free_product ) && empty( $free_date ) ){
            if( $is_free_provinsi || $is_free_kota ){
                $valid_rules = true;
            }
        }

        // provinsi + produk
        if( empty( $min_price ) && !empty( $free_provinsi ) && empty( $free_kota ) && !empty( $free_product ) && empty( $free_date ) ){
            if( $is_free_provinsi && $is_free_product ){
                $valid_rules = true;
            }
        }

        // provinsi + tanggal
        if( empty( $min_price ) && !empty( $free_provinsi ) && empty( $free_kota ) && empty( $free_product ) && !empty( $free_date ) ){
            if( $is_free_provinsi && $is_free_date ){
                $valid_rules = true;
            }
        }

        // kota + produk
        if( empty( $min_price ) && empty( $free_provinsi ) && !empty( $free_kota ) && !empty( $free_product ) && empty( $free_date ) ){
            if( $is_free_kota && $is_free_product ){
                $valid_rules = true;
            }
        }

        // kota + tanggal
        if( empty( $min_price ) && empty( $free_provinsi ) && !empty( $free_kota ) && empty( $free_product ) && !empty( $free_date ) ){
            if( $is_free_kota && $is_free_date ){
                $valid_rules = true;
            }
        }

        // produk + tanggal
        if( empty( $min_price ) && empty( $free_provinsi ) && empty( $free_kota ) && !empty( $free_product ) && !empty( $free_date ) ){
            if( $is_free_product && $is_free_date ){
                $valid_rules = true;
            }
        }

        // harga + provinsi + kota
        if( !empty( $min_price ) && !empty( $free_provinsi ) && !empty( $free_kota ) && empty( $free_product ) && empty( $free_date ) ){
            if( $is_free_price && ( $is_free_provinsi || $is_free_kota ) ){
                $valid_rules = true;
            }
        }

        // harga + provinsi + produk
        if( !empty( $min_price ) && !empty( $free_provinsi ) && empty( $free_kota ) && !empty( $free_product ) && empty( $free_date ) ){
            if( $is_free_price && $is_free_provinsi && $is_free_product ){
                $valid_rules = true;
            }
        }

        // harga + provinsi + tanggal
        if( !empty( $min_price ) && !empty( $free_provinsi ) && empty( $free_kota ) && empty( $free_product ) && !empty( $free_date ) ){
            if( ( $is_free_price && $is_free_provinsi && $is_free_date ) ){
                $valid_rules = true;
            }
        }

        // harga + kota + produk
        if( !empty( $min_price ) && empty( $free_provinsi ) && !empty( $free_kota ) && !empty( $free_product ) && empty( $free_date ) ){
            if( ( $is_free_price && $is_free_kota && $is_free_product ) ){
                $valid_rules = true;
            }
        }

        // provinsi + kota + produk
        if( empty( $min_price ) && !empty( $free_provinsi ) && !empty( $free_kota ) && !empty( $free_product ) && empty( $free_date ) ){
            if( ( ( $is_free_provinsi || $is_free_kota ) && $is_free_product ) ){
                $valid_rules = true;
            }
        }

        // provinsi + kota + tanggal
        if( empty( $min_price ) && !empty( $free_provinsi ) && !empty( $free_kota ) && empty( $free_product ) && !empty( $free_date ) ){
            if( ( ( $is_free_provinsi || $is_free_kota ) && $is_free_date ) ){
                $valid_rules = true;
            }
        }

        // kota + produk + tanggal
        if( empty( $min_price ) && empty( $free_provinsi ) && !empty( $free_kota ) && !empty( $free_product ) && !empty( $free_date ) ){
            if( ( $is_free_kota && $is_free_product && $is_free_date ) ){
                $valid_rules = true;
            }
        }
            
        // harga + produk + tanggal
        if( !empty( $min_price ) && empty( $free_provinsi ) && empty( $free_kota ) && !empty( $free_product ) && !empty( $free_date ) ){
            if( ( $is_free_price && $is_free_product && $is_free_date ) ){
                $valid_rules = true;
            }
        }
            
        // provinsi + produk + tanggal
        if( empty( $min_price ) && !empty( $free_provinsi ) && empty( $free_kota ) && !empty( $free_product ) && !empty( $free_date ) ){
            if( ( $is_free_provinsi && $is_free_product && $is_free_date ) ){
                $valid_rules = true;
            }
        }
            
        // layanan jne
        if( empty( $min_price ) && empty( $free_provinsi ) && empty( $free_kota ) && empty( $free_product ) && empty( $free_date ) ){
            $valid_rules = true;
        }
        
        return $valid_rules;
    }

	/**
	 * JNE get jabodetabek
	 * Mendapatkan kota wilayah jabodetabek
	 *
	 * @access public
	 * @return array
	 * @since 8.1.0
	 **/
	public function get_jabodetabek(){
		$jabodetabek = array(
			'DKI Jakarta',
			'Kota Administrasi Jakarta Barat',
			'Kota Administrasi Jakarta Selatan',
			'Kota Administrasi Jakarta Pusat',
			'Kota Administrasi Jakarta Utara',
			'Kota Administrasi Jakarta Timur',
			'Kota Administrasi Kepulauan Seribu',
			'Kota Bogor',
			'Kab. Bogor',
			'Kota Depok',
			'Kota Tangerang',
			'Kab. Tangerang',
			'Kota Bekasi',
			'Kab. Bekasi'
		);
		
		return $jabodetabek;
	}
    
	/**
	 * Shipping service option default
	 *
	 * @access public
	 * @return array
	 * @since 8.0.0
	 **/
	public function default_service(){
		return array(
			array(
                'id'        => 'oke',
                'enable'    => 1,
                'name'      => 'OKE',
                'extra_cost'=> 0
			),
			array(
                'id'        => 'reg',
                'enable'    => 1,
                'name'      => 'REG',
                'extra_cost'=> 0
			),
			array(
                'id'        => 'yes',
                'enable'    => 1,
                'name'      => 'YES',
                'extra_cost'=> 0
			)
		);			
	}
	
	/**
	 * Shipping form fields settings
	 *
	 * @access public
	 * @return array
	 * @since 8.0.0
	 **/
	public function form_fields(){
        $form_fields = array(
            'license_code'  => array(
                'type'          => 'license_code',
                'default'       => '',
            )
        );
        
        return apply_filters( 'woocommerce_jne_form_fields_settings', $form_fields );
    }
    
	/**
	 * Shipping form fields settings
	 *
	 * @access public
	 * @return array
	 * @since 8.0.0
	 **/
	public function get_form_fields(){
		$form_fields = array(
            'general'   => array(
                'label' => __( 'General', 'agenwebsite' ),
                'fields'    => array(
                    'enabled' => array(
                        'title'         => __( 'Aktifkan JNE Shipping', 'agenwebsite' ), 
                        'type'          => 'checkbox', 
                        'label'         => __( 'Aktifkan WooCommerce JNE Shipping', 'agenwebsite' ), 
                        'default'       => 'no',
                    ), 
                    'title' => array(
                        'title'         => __( 'Label', 'agenwebsite' ), 
                        'description' 	=> __( 'Ubah label untuk fitur pengiriman kamu.', 'agenwebsite' ),
                        'type'          => 'text',
                        'default'       => __( 'JNE Shipping', 'agenwebsite' ),
                    ),
                    'default_weight' => array(
                        'title'         => __( 'Berat default ( kg )', 'agenwebsite' ), 
                        'description' 	=> __( 'Otomatis setting berat produk jika kamu tidak setting pada masing-masing produk.', 'agenwebsite' ),
                        'type'          => 'number',
                        'custom_attributes' => array(
                            'step'	=>	'any',
                            'min'	=> '0'
                        ),
                        'placeholder'	=> '0.00',
                        'default'		=> '1',
                    ),
                    'license_code'  => array(
                        'type'          => 'license_code',
                        'default'       => '',
                    ),
                    'jne_service' => array(
                        'type'          => 'jne_service',
                        'default'		=> 'yes',
                    ),
                )
            ),
            'cek_ongkir' => array(
                'label' => __( 'Cek Ongkir', 'agenwebsite' ),
                'fields'=> array(
                    'jne_cekongkir_tab' => array(
                        'title'			=> __( 'Cek Ongkir Tab', 'agenwebsite' ), 
                        'type'          => 'title',
                        'description'   => __( 'Fitur ini berfungsi menghitung estimasi biaya pengiriman berdasarkan berat produk. fitur ini muncul pada tab single product.', 'agenwebsite' ),
                        'default'		=> 'yes'
                    ),
                    'jne_cekongkir_tab_enabled' => array(
                        'title'         => __( 'Aktifkan/Non-aktifkan', 'agenwebsite' ), 
                        'type'          => 'checkbox', 
                        'label'         => __( 'Aktifkan WooCommerce JNE Shipping Cek Ongkir', 'agenwebsite' ), 
                        'default'       => 'no',
                    ),
                    'jne_cekongkir_tab_title' => array(
                        'title'         => __( 'Label', 'agenwebsite' ), 
                        'type'          => 'text', 
                        'description'   => __( 'Ubah label untuk fitur jne cek ongkir tab', 'agenwebsite' ), 
                        'default'       => __( 'Cek Ongkir', 'agenwebsite' ),
                    ),
                ),
            ),
            'asuransi' => array(
                'label' => __( 'Asuransi', 'agenwebsite' ),
                'fields'=> array(
                    'jne_asuransi' => array(
                        'title'			=> __( 'Asuransi', 'agenwebsite' ), 
                        'type'          => 'title',
                        'description'   => __( 'Fitur ini berfungsi untuk memberika biaya asuransi pada setiap produk atau pesanan.', 'agenwebsite' ),
                        'default'		=> 'yes'
                    ),
                    'asuransi_overide' => array(
                        'title'			=> __( 'Aktifkan ke semua produk', 'agenwebsite' ), 
                        'type'          => 'checkbox',
                        'description'   => __( 'Dengan mencentang ini, maka semua produk yang ada akan diasuransikan dan pengaturan di metapost akan diabaikan.', 'agenwebsite' ),
                        'default'		=> 'no',
                    ),
                    'asuransi_teks' => array(
                        'title'			=> __( 'Teks asuransi', 'agenwebsite' ), 
                        'type'          => 'textarea',
                        'description'   => __( 'Teks ini akan muncul dihalaman produk yang diasuransikan. gunakan <code>{harga}</code> untuk menampilkan harga asuransi.', 'agenwebsite' ),
                        'default'		=> 'Asuransikan produk ini seharga {harga} ?'
                    ),
                    'asuransi_teks_required' => array(
                        'title'			=> __( 'Teks asuransi yang diwajibkan', 'agenwebsite' ), 
                        'type'          => 'textarea',
                        'description'   => __( 'Teks ini akan muncul dihalaman produk yang <code>wajib</code> diasuransikan. gunakan <code>{harga}</code> untuk menampilkan harga asuransi.', 'agenwebsite' ),
                        'default'		=> 'Produk ini di asuransikan seharga {harga}.'
                    ),
                )
            ),
            'packing_kayu' => array(
                'label' => __( 'Packing Kayu', 'agenwebsite' ),
                'fields'=> array(
                    'jne_packing_kayu' => array(
                        'title'			=> __( 'Packing Kayu', 'agenwebsite' ), 
                        'type'          => 'title',
                        'description'   => __( 'Fitur ini berfungsi untuk menghitung otomatis biaya packing kayu.', 'agenwebsite' ),
                        'default'		=> 'yes'
                    ),
                    'jne_packing_kayu_enabled' => array(
                        'title'         => __( 'Aktifkan/Non-aktifkan', 'agenwebsite' ), 
                        'type'          => 'checkbox', 
                        'label'         => __( 'Aktifkan WooCommerce JNE Shipping Packing Kayu', 'agenwebsite' ), 
                        'default'       => 'no',
                    ),
                    'packing_kayu_label' => array(
                        'title'			=> __( 'Label Packing Kayu', 'agenwebsite' ), 
                        'type'          => 'text',
                        'description'   => __( 'Masukkan label yang kamu inginkan untuk packing kayu. Kosongkan apabila kamu ingin sembunyikan label.', 'agenwebsite' ),
                        'default'		=> 'Packing Kayu'
                    ),
                    'packing_kayu_text' => array(
                        'title'			=> __( 'Teks Packing Kayu', 'agenwebsite' ), 
                        'type'          => 'textarea',
                        'description'   => __( 'Masukkan teks untuk packing kayu yang akan muncul di halaman checkout.', 'agenwebsite' ),
                        'default'		=> 'Pesanan ini wajib menggunakan packing kayu sehingga biaya shipping menjadi 2x lipat.'
                    ),
                ),
            ),
            'free_shipping' => array(
                'label' => __( 'Free Shipping', 'agenwebsite' ),
                'fields'=> array(
                    'jne_free_shipping' => array(
                        'title'			=> __( 'JNE Free Shipping', 'agenwebsite' ),
                        'type'          => 'title',
                        'description'	=> __( 'Kamu bisa menambahkan pengiriman gratis untuk layanan jne kamu.', 'agenwebsite' ),
                        'default'		=> ''
                    ),
                    'free_shipping_enabled' => array(
                        'title'         => __( 'Aktifkan/Non-aktifkan', 'agenwebsite' ), 
                        'type'          => 'checkbox', 
                        'label'         => __( 'Aktifkan Free Shipping', 'agenwebsite' ), 
                        'default'       => 'no',
                    ),
                    'free_shipping_service' => array(
                        'title'         => __( 'Layanan Gratis', 'agenwebsite' ), 
                        'description' 	=> __( 'Pilih layanan jne yang ingin diberikan pengiriman gratis.', 'agenwebsite' ),
                        'type'			=> 'multiselect',
                        'class'			=> 'chosen_select',
                        'default'		=> '',
                        'css'			=> 'width: 450px;',
                        'options'		=> array(
                            'reg' => 'REG',
                            'yes' => 'YES',
                            'oke' => 'OKE',
                        ),
                    ),
                    'free_shipping_label'  => array(
                        'title'         => __( 'Label Free Shipping', 'agenwebsite' ),
                        'description'   => __( 'Ganti nama layanan free shipping jne sesuai keinginan kamu. <code><i>default: Pengiriman Gratis</i></code>' ),
                        'type'          => 'text',
                        'default'       => 'Pengiriman Gratis',
                    ),
                    'free_shipping_hide_estimasi'  => array(
                        'title'         => __( 'Hilangkan ETD', 'agenwebsite' ),
                        'label'         => __( 'Hilangkan Estimasi Hari' ),
                        'description'   => __( 'Checklist ini akan menghilangkan estimasi hari.' ),
                        'type'          => 'checkbox',
                        'default'       => 'no',
                    ),
                    'free_shipping_min_price' => array(
                        'title'		=> __( 'Minimum Biaya', 'agenwebsite' ),
                        'type'		=> 'price',
                        'label'		=> __( 'Minimun Biaya', 'agenwebsite' ),
                        'description' 	=> __( 'Masukan minimum total belanja untuk gratis biaya pengiriman.', 'agenwebsite' ),
                        'default'	=> ''
                    ),
                    'free_shipping_date' => array(
                        'type'      => 'free_shipping_date',
                        'default'   => 'yes'
                    ),
                    'free_shipping_product' => array(
                        'type'      => 'free_shipping_product',
                        'default'   => 'yes'
                    ),
                    'free_shipping_provinsi' => array(
                        'type'		=> 'free_shipping_provinsi',
                        'default'	=> 'yes'
                    ),
                    'free_shipping_city' => array(
                        'type'		=> 'free_shipping_city',
                        'default'	=> 'yes'
                    )
                )
            ),
            'shortcodes' => array(
                'label' => __( 'Shortcodes', 'agenwebsite' ),
                'fields'=> apply_filters( 'woocommerce_jne_shortcodes', array())
            ),
            'tools' => array(
                'label' => __( 'Tools', 'agenwebsite' ),
                'fields'=> array(
                    'jne_shipping_transient' => array(
                        'title'         => __( 'JNE Transients', 'agenwebsite' ),
                        'type'          => 'button',
                        'label'         => __( 'JNE Transients', 'agenwebsite' ),
                        'description'   => __( 'Tool ini akan menghapus semua transient harga jne shipping.', 'agenwebsite' ),
                        'placeholder'   => __( 'Clear JNE shipping transient', 'agenwebsite' ),
                        'class'         => 'clear_jne_transient',
                        'default'       => ''
                    ),
                    'clear_expired_transients' => array(
                        'title'         => __( 'Expired Transients', 'agenwebsite' ),
                        'type'          => 'button',
                        'label'         => __( 'Clear expired transients', 'agenwebsite' ),
                        'description'   => __( 'Tool ini akan menghapus semua transient kadaluarsa dari WordPress.', 'agenwebsite' ),
                        'placeholder'   => __( 'Clear expired transient', 'agenwebsite' ),
                        'class'         => 'clear_expired_transient',
                        'default'       => ''
                    ),
                    'create_page_cek_ongkir' => array(
                        'title'         => __( 'Buat Halaman Cek Ongkir', 'agenwebsite' ),
                        'type'          => 'button',
                        'label'         => __( 'Buat Halaman Cek Ongkir', 'agenwebsite' ),
                        'description'   => __( 'Tool ini akan membuat halaman cek ongkir.', 'agenwebsite' ),
                        'placeholder'   => __( 'Buat Halaman Cek Ongkir', 'agenwebsite' ),
                        'class'         => 'create_page_cek_ongkir',
                        'default'       => ''
                    ),
                    'create_page_jne_tracking' => array(
                        'title'         => __( 'Buat Halaman JNE Tracking ( Cek Resi )', 'agenwebsite' ),
                        'type'          => 'button',
                        'label'         => __( 'Buat Halaman JNE Tracking', 'agenwebsite' ),
                        'description'   => __( 'Tool ini akan membuat halaman JNE tracking / cek resi.', 'agenwebsite' ),
                        'placeholder'   => __( 'Buat Halaman JNE Tracking', 'agenwebsite' ),
                        'class'         => 'create_page_jne_tracking',
                        'default'       => ''
                    )
                ),
            ),
        );
        
        return $form_fields;
	}
	
}

endif;
