<?php
/**
 * Main AJAX Handles
 *
 * Handles for all request file
 *
 * @author AgenWebsite
 * @package WooCommerce JNE Shipping
 * @since 8.0.0
 */

if ( !defined( 'WOOCOMMERCE_JNE' ) ) { exit; } // Exit if accessed directly

if ( ! class_exists( 'WC_JNE_AJAX' ) ):

class WC_JNE_AJAX{
	
	/* @var string */
	private static $nonce_admin = 'woocommerce_jne_admin';
	
	/**
	 * Hook in methods
	 */
	public static function init(){
		
		// ajax_event => nopriv
		$ajax_event = array(
			'get_city_bykeyword'				=> false,
			'get_provinsi_bykeyword'			=> false,
			'check_version'					=> false,
			'shipping_get_kota'				=> true,
			'shipping_get_kecamatan'			=> true,
			'cekongkir_get_harga'				=> true,
            'check_status'                      => false,
		);
			
		foreach( $ajax_event as $ajax_event => $nopriv ){
			add_action( 'wp_ajax_woocommerce_jne_' . $ajax_event, array( __CLASS__, $ajax_event ) );
			
			if( $nopriv ){
				add_action( 'wp_ajax_nopriv_woocommerce_jne_' . $ajax_event, array( __CLASS__, $ajax_event ) );	
			}
		}
			
	}		
	
	/**
	 * AJAX Checking status
	 *
	 * @access public
	 * @return json
	 * @since 4.0.0
	 **/
    public static function check_status(){
        
        check_ajax_referer( self::$nonce_admin );
        
        $license = ( ! empty($_POST['license_code']) ) ? $_POST['license_code'] : WC_JNE()->get_license_code();
        
        WC_JNE()->api->license_code = $license;
        $result = WC_JNE()->api->remote_get( 'license_status' );
        
        ob_start();
        woocommerce_get_template( 'html-aw-product-status.php', array(
            'status' => $result['status'],
            'message' => $result['message'],
            'data' => $result['result'],
        ), 'woocommerce-pos', untrailingslashit( WC_JNE()->plugin_path() ) . '/views/' );
        $output['message'] = ob_get_clean();        
        
        wp_send_json( $output );
        
        wp_die();
        
    }
	
	/**
	 * AJAX Get provinsi indonesia by keyword on jne shipping settings page
	 *
	 * @access public
	 * @return json
	 * @since 8.0.0
	 **/
	public static function get_provinsi_bykeyword(){
						
		check_ajax_referer( self::$nonce_admin );
		
        $args = array(
            'action' => 'get_provinsi_bykeyword',
            'keyword' => $_POST['q'],
        );

        $result = WC_JNE()->api->remote_get( 'kota', $args );

		wp_send_json( $result['result'] );
		
		wp_die();			
	}
	
	/**
	 * AJAX Get city by keyword on jne shipping settings page
	 *
	 * @access public
	 * @return json
	 * @since 8.0.0
	 **/
	public static function get_city_bykeyword(){

		check_ajax_referer( self::$nonce_admin );
		
        $args = array(
            'action' => 'get_city_bykeyword',
            'keyword' => $_POST['q'],
        );

        $result = WC_JNE()->api->remote_get( 'kota', $args );
        
		wp_send_json( $result['result'] );

		wp_die();
	}
	
	/**
	 * AJAX Check version to API AgenWebsite
	 *
	 * @access public
	 * @return json
	 * @since 8.0.0
	 **/
	public static function check_version(){
		
		check_ajax_referer( self::$nonce_admin );
		
		$url = 'api.agenwebsite.com/cek-versi/0.0.1/?action=cek_versi&product=woocommerce_jne_shipping&version=' . WOOCOMMERCE_JNE_VERSION;
		$curl = curl_init();
		curl_setopt_array($curl, array(
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_URL => $url,
			CURLOPT_TIMEOUT => 30
		));
		$result = curl_exec($curl);
		if(curl_errno($curl)) {
			curl_close($curl);
			return false;
		}
		curl_close($curl);
		
		$data = json_decode( $result, TRUE );
		
		$output['result'] = $data['result'];
		$output['version'] = WOOCOMMERCE_JNE_VERSION;
		if( $data['result'] != 1 ){
			$output['update_url'] = $data['update_url'];
			$output['latest_version'] = $data['versi'];				
		}
		
		wp_send_json( $output );
		
		wp_die();

	}
	
	/**
	 * AJAX Get data kota
	 *
	 * @access public
	 * @return json
	 * @since 8.0.0
	 **/
	public static function shipping_get_kota(){
		
		check_ajax_referer( WC_JNE()->get_nonce() );
		
        // Check if we need to recalculate shipping for this city
        $city_hash = 'wc_ship_jne_kota_' . md5( $_POST['provinsi'] ) . WC_Cache_Helper::get_transient_version( 'jne_shipping_kota' );
        
        if( false === ( $stored_city = get_transient( $city_hash ) ) ){
            
            $args = array(
                'action' => 'get_kota',
                'keyword' => $_POST['provinsi'],
            );

            $response = WC_JNE()->api->remote_get( 'kota', $args );
            
            // store transient
            WC_JNE()->set_transient( $city_hash, $response['result'] );
            
        }else{
            
            $response['result'] = $stored_city;
            
        }

		wp_send_json( $response['result'] );
		
		wp_die();
		
	}
		
	/**
	 * AJAX Get data kecamatan
	 *
	 * @access public
	 * @return json
	 * @since 8.0.0
	 **/
	public static function shipping_get_kecamatan(){

		check_ajax_referer( WC_JNE()->get_nonce() );
		
        // Check if we need to recalculate shipping for this kecamatan
        $kec_hash = 'wc_ship_jne_kec_' . md5( $_POST['kota'] ) . WC_Cache_Helper::get_transient_version( 'jne_shipping_kec' );
        
        if( false === ( $stored_kec = get_transient( $kec_hash ) ) ){

            $args = array(
                'action' => 'get_kecamatan',
                'keyword' => $_POST['provinsi'] .'|'. $_POST['kota'],
            );

            $response = WC_JNE()->api->remote_get( 'kota', $args );
            
            // store transient
            WC_JNE()->set_transient( $kec_hash, $response['result'] );

        }else{

            $response['result'] = $stored_kec;

        }

		wp_send_json( $response['result'] );

		wp_die();			
	}
		
	/**
	 * AJAX Get data harga jne on cekongkir tab
	 *
	 * @access public
	 * @return json
	 * @since 8.0.0
	 **/
	public static function cekongkir_get_harga(){
		
		check_ajax_referer( WC_JNE()->get_nonce() );

		$tujuan['weight']		= WC_JNE()->shipping->calculate_jne_weight( $_POST['berat'] );
		$tujuan['weight_unit']	= $_POST['type'] == 'tab' ? $_POST['weight_unit'] : WC_JNE()->get_woocommerce_weight_unit();
		$tujuan['provinsi'] 	= $_POST['provinsi'];
		$tujuan['kota'] 		= $_POST['kota'];
		$tujuan['kecamatan'] 	= $_POST['kecamatan'];
        
        $args = array(
            'provinsi'  => $_POST['provinsi'],
            'kota'      => $_POST['kota'],
            'kecamatan' => $_POST['kecamatan'],
            'berat'     => $_POST['berat']
        );
        
        $tarif = WC_JNE()->api->remote_get( 'tarif', $args );
        
		$layanan = array();
		foreach( $tarif['result']['tarif'] as $nama_layanan => $data_layanan ){
			if( ! empty( $data_layanan['harga'] ) ){
				$layanan[ $nama_layanan ][ 'harga' ]	= wc_price( $data_layanan['harga'] * $tujuan['weight'] );
				$layanan[ $nama_layanan ][ 'etd' ]		= $data_layanan['etd'];
			}
		}
		
		$template_path = $_POST['type'] == 'tab' ? 'single-product/tabs/cek-ongkir-result.php' : 'cek-ongkir-widget-result.php';
					
		ob_start();   
		
			woocommerce_get_template( $template_path, array(
                'id'        => ( $_POST['type'] == 'widget' ) ? 'widget' : 'shortcode',
				'tujuan'	=> $tujuan,
				'layanan'	=> $layanan
			), 'woocommerce-jne-exclusive', untrailingslashit( WC_JNE()->plugin_path() ) . '/templates/' );			
		
		$result = ob_get_clean();
		
		wp_send_json( $result );
		
		wp_die();	

	}
		
}

WC_JNE_AJAX::init();
	
endif;
