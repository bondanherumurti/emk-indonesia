<?php
/**
 * Shipping Cek Ongkir JNE
 *
 * Main file cek ongkir
 *
 * @author AgenWebsite
 * @package WooCommerce JNE Shipping
 * @since 8.0.0
 */

if ( !defined( 'WOOCOMMERCE_JNE' ) ) { exit; } // Exit if accessed directly

if ( ! class_exists( 'WC_JNE_Cek_Ongkir' ) ):

/**
 * Initiliase Class
 *
 * @since 8.0.0
 **/
class WC_JNE_Cek_Ongkir{
	
	public $id = 'woocommerce_jne_cekongkir';
	
	/**
	 * Constructor
	 *
	 * @since 8.0.0
	 */	
	public function __construct(){
		
		// Release cekongkir on tab single product
		if( $this->tab_is_enable() ) new WC_JNE_Cek_Ongkir_Tab();
		
		// Adding cekongkir as a widget
		add_action( 'widgets_init', array( &$this, 'register_widget' ) );
        
		// Adding cekongkir shortcode
		add_shortcode( 'jne_cek_ongkir', array( &$this, 'jne_cek_ongkir' ) );
        // Adding shortcode list in settings jne
        add_filter( 'woocommerce_jne_shortcodes', array( &$this, 'add_shortcode_to_list' ) );
		
	}

	/**
	 * Register widget Cek Ongkir
	 *
	 * @access public
	 * @return void
	 * @since 8.0.0
	 **/
	public function register_widget(){
		register_widget( 'WC_JNE_Cek_Ongkir_Widget' );
	}
	
	/**
	 * Init weight product
	 *
	 * @access public
	 * @return mixed
	 * @since 8.0.0
	 **/
	public function weight_product(){
		global $post;
		
		$jne_shipping_settings = get_option( 'woocommerce_jne_shipping_settings' );
		$default_weight = $jne_shipping_settings['default_weight'];
		
		$weight_product	= get_post_meta( $post->ID, '_weight', TRUE );
		$woo_weight_unit = WC_JNE()->get_woocommerce_weight_unit();

		$weight_unit = $woo_weight_unit;
		if( empty( $weight_product ) ){
			$weight_product = $default_weight;
			if( $woo_weight_unit == 'g' )
				$weight_unit = 'kg';
		} else {
			$weight_product = $weight_product;
			$weight_unit = $woo_weight_unit;
			if( $woo_weight_unit == 'g' ){
				$weight_product = $weight_product / 1000;
				$weight_unit = 'kg';
			}
		}
			
		$weight['product']	= $weight_product;
		$weight['unit']		= $weight_unit;
			
		return $weight;
	}
    
	/**
	 * Register shortcode Cek Ongkir
	 *
	 * @access public
	 * @return void
	 * @since 8.1.02
	 **/
    public function jne_cek_ongkir(){

        $weight['unit']	= 'kg';
        $weight['product'] = '1';
        
        ob_start();

        woocommerce_get_template( 'cek-ongkir-widget.php', array(
            'id'        => 'shortcode',
            'weight'	=> $weight,
            'fields'	=> apply_filters( 'woocommerce_jne_cek_ongkir_fields', $this->fields() )
        ), 'woocommerce-jne-exclusive', untrailingslashit( WC_JNE()->plugin_path() ) . '/templates/' );

        return ob_get_clean();

    }
		
    /**
     * Add cek ongkir to shortcode list
     *
     * @access public
     * @param array $shortcodes
     * @return array
     * @since 8.1.10
     */
    public function add_shortcode_to_list( $shortcodes = array() ){
        return WC_JNE()->add_shortcode_list( $shortcodes, 'jne_cek_ongkir', 'jne cek ongkir' );
    }

	/**
	 * Modul tab is enable
	 * Check enabled or disabled.
	 *
	 * @access public
	 * @return bool
	 * @since 8.0.0
	 **/
	public function tab_is_enable(){
		$options = get_option( 'woocommerce_jne_shipping_settings' );
        if( is_array( $options ) ){
            if( ! array_key_exists( 'jne_cekongkir_tab_enabled', $options ) ) return false;

            return ( $options['jne_cekongkir_tab_enabled'] == 'yes' ) ? TRUE : FALSE;
        }
	}
	
	/**
	 * Fields form to action cek ongkir
	 *
	 * @access public
	 * @return array
	 * @since 8.0.0
	 **/
	public function fields(){
		$fields = array(
			'berat'		=> array(
				'type'		=> 'text',
				'label'		=> __( 'Berat', 'agenwebsite' ),
			),
			'provinsi'	=> array(
				'type'		=> 'select',
				'option'	=>	self::get_provinsi(),
				'label'		=> __( 'Provinsi', 'agenwebsite' ),
			),
			'kota'		=> array(
				'type'		=> 'select',
				'option'	=>	array( '0' => __( 'Pilih Kotamadya/Kabupaten', 'agenwebsite' ), '1' => __( 'Pilih Provinsi terlebih dahulu', 'agenwebsite' ) ),
				'label'		=> __( 'Kotamadya/Kabupaten', 'agenwebsite' ),
			),
			'kecamatan'	=> array(
				'type'		=> 'select',
				'option'	=>	array( '0' => __( 'Pilih Kecamatan', 'agenwebsite' ), '1' => __( 'Pilih Kota terlebih dahulu', 'agenwebsite' ) ),
				'label'		=> __( 'Kecamatan', 'agenwebsite' ),
			)
		);
		
		return $fields;			
	}
	
	/**
	 * Get provinsi of indonesia from woocommerce data array
	 *
	 * @access private
	 * @return array
	 * @since 8.0.0
	 **/
	private function get_provinsi(){			
		$provinsi = array( '0' => __( 'Pilih Provinsi', 'agenwebsite' ) );
		$states = WC()->countries->get_states( 'ID' );
		
		foreach( $states as $id_states => $nama_provinsi ){
			$provinsi[ $nama_provinsi ] = $nama_provinsi;
		}
			return $provinsi;			
	}

	/**
	 * JNE get provinsi by kota
	 * Mendapatkan nama provinsi berdasarkan kota
	 *
	 * @access public
	 * @param string $kota
	 * @return string
	 * @since 8.0.0
	 */	
	public function get_provinsi_by_kota( $kota ){
		
		$array_data = WC_JNE()->shipping->get_datakota();

        $provinsi = '';
		
		if( strpos( $kota, 'Kota' ) != TRUE ){
			$kota = 'Kota ' . $kota;
		}
		if(count($array_data) > 0) {
			if(is_array($array_data) && count($array_data) > 0){
				foreach( $array_data as $nama_provinsi => $data_kota ){
					foreach( $data_kota as $nama_kota => $data_kecamatan ){
						if( $kota == $nama_kota )
							$provinsi = $nama_provinsi;
					}
				}
			}
		}
					
		return $provinsi;
			
	}

}
	
endif;