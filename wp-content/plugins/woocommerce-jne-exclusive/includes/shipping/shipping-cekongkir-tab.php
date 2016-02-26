<?php
/**
 * JNE Cek Ongkir Tab
 *
 * @author AgenWebsite
 * @package WooCommerce JNE Shipping
 * @since 8.0.0
 */

if ( !defined( 'WOOCOMMERCE_JNE' ) ) { exit; } // Exit if accessed directly

if ( !class_exists( 'WC_JNE_Cek_Ongkir_Tab' ) ):
	
/**
 * Class WooCommerce JNE Tracking
 *
 * @since 8.0.0
 **/
class WC_JNE_Cek_Ongkir_Tab extends WC_JNE_Cek_Ongkir{
	
	/**
	 * Constructor
	 *
	 * @return void
	 * @since 8.0.0
	 **/
	public function __construct(){
		
		// Frontend Stuff
		add_filter( 'woocommerce_product_tabs', array( &$this, 'add_product_tabs' ) );
		
	}
			
	/**
	 * Add the custom product tab
	 *
	 * $tabs structure:
	 * Array(
	 *   id => Array(
	 *     'title'    => (string) Tab title,
	 *     'priority' => (string) Tab priority,
	 *     'callback' => (mixed) callback function,
	 *   )
	 * )
	 *
	 * @param array $tabs array representing the product tabs
	 * @return array representing the product tabs
	 * @since 8.0.0
	 */
	public function add_product_tabs( $tabs ){
		
		$option = get_option( 'woocommerce_jne_shipping_settings' );
		$title = ( array_key_exists( 'jne_cekongkir_tab_title', $option ) ) ? $option['jne_cekongkir_tab_title'] : __( 'Cek Ongkir', 'agenwebsite' );
		
		$tabs[ 'cekongkir' ] = array(
			'title'    => $title,
			'priority' => 50,
			'callback' => array( &$this, 'product_tabs_panel_content' ),
		);
	
		return $tabs;
	}

	/**
	 * Render the custom product tab panel content for the given $tab
	 *
	 * $tab structure:
	 * Array(
	 *   'title'    => (string) Tab title,
	 *   'priority' => (string) Tab priority,
	 *   'callback' => (mixed) callback function,
	 *   'id'       => (int) tab post identifier,
	 *   'content'  => (sring) tab content,
	 * )
	 *
	 * @param string $key tab key
	 * @param array $tab tab data
	 *
	 * @since 8.0.0
	 */
	public function product_tabs_panel_content( $key, $tab ) {			
		woocommerce_get_template( 'single-product/tabs/cek-ongkir.php', array(
			'id'		=> $this->id,
			'weight'	=> WC_JNE()->shipping->cek_ongkir->weight_product(),
			'fields'	=> apply_filters( 'woocommerce_jne_cek_ongkir_fields', WC_JNE()->shipping->cek_ongkir->fields() )
		), 'woocommerce-jne-exclusive', untrailingslashit( WC_JNE()->plugin_path() ) . '/templates/' );				
	}
	
}
 	 
endif;