<?php
/**
 * JNE Shipping Packing Kayu
 *
 * This is a module, handles calculate and settings packing kayu JNE Shipping
 *
 * @author AgenWebsite
 * @package WooCommerce JNE Shipping
 * @since 8.1.0
 */

if ( !defined( 'WOOCOMMERCE_JNE' ) ) { exit; } // Exit if accessed directly

if ( !class_exists( 'WC_JNE_Packing_Kayu' ) ):
	
/**
 * Class WooCommerce JNE Packing Kayu
 *
 * @since 8.1.0
 **/
class WC_JNE_Packing_Kayu{

    /* var array */
    private $options;

	/**
	 * Constructor
	 *
	 * @return void
	 * @since 8.1.0
	 **/
	public function __construct(){
		
		$this->options = get_option( 'woocommerce_jne_shipping_settings' );
        
        $is_jne_packing_kayu = '';
        
        if( is_array( $this->options ) ){
            $is_jne_packing_kayu = ( array_key_exists( 'jne_packing_kayu_enabled', $this->options ) ) ? $this->options['jne_packing_kayu_enabled'] : '';
        }
		
		if( $is_jne_packing_kayu == 'yes' ){
            
            // Show total weight in review order table
            add_action( 'woocommerce_review_order_before_shipping', array( &$this, 'on_checkout' ) );
            add_filter( 'woocommerce_jne_shipping_tarif', array( &$this, 'adjust_tarif_packing_kayu' ) );
            
		}
		
	}
					
	/**
	 * Add notice to review order table
	 *
	 * @access public
	 * @return HTML
	 * @since 8.1.0
	 */	
	public function on_checkout(){
		global $woocommerce;
        $shipping_chosen = $woocommerce->session->get( 'chosen_shipping_methods' );
        $message = ( array_key_exists( 'packing_kayu_text', $this->options ) ) ? $this->options['packing_kayu_text'] : '' ;
        $label = ( array_key_exists( 'packing_kayu_label', $this->options ) ) ? $this->options['packing_kayu_label'] : '' ;
        
        if( ! empty( $shipping_chosen[0] ) ){
            if( strpos($shipping_chosen[0], 'jne') !== false ){
                woocommerce_get_template( 'jne-packing-kayu.php', array(
                    'label'                     => $label,
                    'message_jne_packing_kayu'	=> $message,
                ), 'woocommerce-jne-exclusive', untrailingslashit( WC_JNE()->plugin_path() ) . '/templates/checkout/' );
            }
        }

	}

    /**
     * Adjust tarif packing kayu to tarif shipping jne
     *
     * @access public
     * @param array $tarif
     * @return array $tarif
     * @since 8.1.10
     */
    public function adjust_tarif_packing_kayu( $tarif ){
        $shipping_chosen = WC()->session->get( 'chosen_shipping_methods' );
        $label = ( array_key_exists( 'packing_kayu_label', $this->options ) ) ? $this->options['packing_kayu_label'] : '' ;
        
        if( ! empty( $shipping_chosen[0] ) ){
            if( strpos($shipping_chosen[0], 'jne') !== false ){
                $tarif['tarif'] = $tarif['tarif'] * 2;
                
                if( ! empty( $label ) ){
                    $tarif['label'] = $tarif['label'] . ' ( ' . $label . ' )';
                }
            }
        }
        
        return $tarif;
    }
	
}

endif;