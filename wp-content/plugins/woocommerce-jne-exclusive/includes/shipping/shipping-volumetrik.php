<?php
/**
 * Volumetrik JNE Shipping
 *
 * Handles calculate volumetrik
 *
 * @author AgenWebsite
 * @package WooCommerce JNE Shipping
 * @since 8.0.0
 */

if ( !defined( 'WOOCOMMERCE_JNE' ) ) { exit; } // Exit if accessed directly

if ( !class_exists( 'WC_JNE_Volumetrik' ) ):
	
/**
 * Class WooCommerce JNE Frontend
 *
 * @since 8.0.0
 **/
class WC_JNE_Volumetrik{
	
	/**
	 * Meta name volumetrik
	 *
	 * @var string
	 * @since 8.0.0
	 **/
	private $meta = '_is_jne_volumetrik_enable';
    
    /**
	 * Constructor
	 *
	 * @return void
	 * @since 8.0.0
	 **/
	public function __construct(){
		
		// Filters for cart actions
		add_filter( 'woocommerce_get_item_data', array( &$this, 'get_item_data' ), 10, 2 );
		add_action( 'woocommerce_add_order_item_meta', array( &$this, 'add_order_item_meta' ), 10, 2 );
		
		// Added option volumetrik to Shipping panel
		add_action( 'woocommerce_product_options_dimensions', array( &$this, 'write_panel' ) );
		add_action( 'woocommerce_process_product_meta', array( &$this, 'write_panel_save' ) );
		
	}
	
	/**
	 * Display volumetrik data if present in the cart
	 *
	 * @access public
	 * @param mixed $item_data
	 * @param mixed $cart_item
	 * @return void
	 * @since 8.0.0
	 */
	public function get_item_data( $item_data, $cart_item ){
		$product_id = $cart_item['product_id'];
		
		$length					= get_post_meta( $product_id, '_length', TRUE );
		$width					= get_post_meta( $product_id, '_width', TRUE );
		$height					= get_post_meta( $product_id, '_height', TRUE );
		$is_volumetrik_enabled 	= get_post_meta( $product_id, $this->meta, TRUE );
		
		if( $is_volumetrik_enabled == 'yes' && ! empty( $length ) && ! empty( $width ) && ! empty( $height ) ){
			$item_data[] = array(
				'name'	=> __( 'Dimension', 'agenwebsite' ),
				'value'	=> sprintf( '%u x %u x %u cm', $length, $width, $height ),
				'display'	=> sprintf( '%u x %u x %u cm', $length, $width, $height ),
			);
		}
		
		return $item_data;
	}

	/**
	 * After ordering, add the data to the order line items.
	 *
	 * @access public
	 * @param mixed $item_id
	 * @param mixed $cart_item
	 * @return void
	 * @since 8.0.0
	 */
	public function add_order_item_meta( $item_id, $cart_item ){
		$length					= get_post_meta( $cart_item['product_id'], '_length', TRUE );
		$width					= get_post_meta( $cart_item['product_id'], '_width', TRUE );
		$height					= get_post_meta( $cart_item['product_id'], '_height', TRUE );
		$is_volumetrik_enabled 	= get_post_meta( $cart_item['product_id'], $this->meta, TRUE );
		if( $is_volumetrik_enabled == 'yes' && ! empty( $length ) && ! empty( $width ) && ! empty( $height ) ){
			woocommerce_add_order_item_meta( $item_id, __( 'Dimension', 'agenwebsite' ), sprintf( '%u x %u x %u cm', $length, $width, $height ) );
		}
	}
	
	/**
	 * Added jne volumetrik enable option to the woocommerce metabox
	 *
	 * @access public
	 * @return void
	 * @since 8.0.0
	 **/
	public function write_panel(){	
		echo '</div><div class="options_group show_if_simple show_if_variable">';
		
		woocommerce_wp_checkbox( array(
			'id'            => $this->meta,
			'wrapper_class' => '',
			'label'         => __( 'JNE Volumetrik', 'agenwebsite' ),
			'description'   => __( 'Aktifkan opsi ini jika produk ini ingin dihitung dengan perhitungan volumetrik.', 'agenwebsite' ),
		) );

	}
	
	/**
	 * Saved jne volumetrik enable option
	 *
	 * @access public
     * @param string $post_id
	 * @return void
	 * @since 8.0.0
	 **/
	public function write_panel_save( $post_id ){
		
		$_is_jne_volumetrik_enable = ! empty( $_POST[ $this->meta ] ) ? 'yes' : 'no';	
		update_post_meta( $post_id, $this->meta, $_is_jne_volumetrik_enable );
		
	}
	
}
	
endif;