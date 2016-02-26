<?php
/**
 * JNE Shipping Asuransi
 *
 * This is a module, handles calculate and settings asuransi JNE Shipping
 *
 * @author AgenWebsite
 * @package WooCommerce JNE Shipping
 * @since 8.0.0
 */

if ( !defined( 'WOOCOMMERCE_JNE' ) ) { exit; } // Exit if accessed directly

if ( !class_exists( 'WC_JNE_Asuransi' ) ):
	
/**
 * Class WooCommerce JNE Asuransi
 *
 * @since 8.0.0
 **/
class WC_JNE_Asuransi{

    /* var option */
    private $options;

	/**
	 * Constructor
	 *
	 * @return void
	 * @since 8.0.0
	 **/
	public function __construct(){

        $this->options = get_option( 'woocommerce_jne_shipping_settings' );

        // Display on the frontend single product
		add_action( 'woocommerce_after_add_to_cart_button', array( &$this, 'asuransi_html' ), 10 );

		// Filters for cart actions
		add_filter( 'woocommerce_add_cart_item_data', array( &$this, 'add_cart_item_data' ), 10, 2 );
		add_filter( 'woocommerce_get_cart_item_from_session', array( &$this, 'get_cart_item_from_session' ), 10, 2 );
		add_filter( 'woocommerce_get_item_data', array( &$this, 'get_item_data' ), 10, 2 );
		add_filter( 'woocommerce_add_cart_item', array( &$this, 'add_cart_item' ), 10, 1 );
		add_action( 'woocommerce_add_order_item_meta', array( &$this, 'add_order_item_meta' ), 10, 2 );

		// Added option asuransi to Shipping panel
		add_action( 'woocommerce_product_options_dimensions', array( &$this, 'write_panel' ), 10 );
		add_action( 'woocommerce_process_product_meta', array( &$this, 'write_panel_save' ), 10 );

	}

    public function asuransi_is( $post_id ){
        $options = $this->options;
        
        $asuransi_is['jne_enable'] = ( array_key_exists( 'enabled', $options ) && $options['enabled'] == 'yes' ) ? TRUE : FALSE;
        $asuransi_is['override'] = ( array_key_exists( 'asuransi_overide', $options ) && $options['asuransi_overide'] == 'yes' ) ? TRUE : FALSE;
        
        $enable = get_post_meta( $post_id, '_is_jne_asuransi_enable', TRUE );
        $required = get_post_meta( $post_id, '_is_jne_asuransi_required', TRUE );

        $asuransi_is['enable'] = ( $enable !== '' && $enable == 'yes' ) ? TRUE : FALSE;
        $asuransi_is['required'] = ( $required !== '' && $required == 'yes' ) ? TRUE : FALSE;

        return $asuransi_is;
    }
    
	/**
	 * Show JNE asuransi checkbox on the frontend
	 *
	 * @access public
	 * @return void
	 * @since 8.0.0
	 **/
	public function asuransi_html(){
		global $post;

        $options = $this->options;
        $asuransi_is = $this->asuransi_is( $post->ID );

		$product = new WC_Product( $post->ID );
		$asuransi_price = wc_price( $this->get_asuransi_price( $product->price ) );

		if( $asuransi_is['jne_enable'] && ( $asuransi_is['override'] || ( $asuransi_is['enable'] ) ) ){

			$current_value = ! empty( $_REQUEST['jne_asuransi'] ) ? 1 : 0;

			if( $asuransi_is['required'] && ! $asuransi_is['override'] ){

                $message_required = ( array_key_exists( 'asuransi_teks_required', $options ) ) ? $options['asuransi_teks_required'] : '';
                $message_required = str_replace( '{harga}', $asuransi_price, $message_required );

				woocommerce_get_template( 'jne-asuransi.php', array(
					'message_jne_asuransi'	=> sprintf( $message_required, $asuransi_price ),
				), 'woocommerce-jne-exclusive', untrailingslashit( WC_JNE()->plugin_path() ) . '/templates/' );

			} else {

                $message = ( array_key_exists( 'asuransi_teks', $options ) ) ? $options['asuransi_teks'] : '';        
                $message = str_replace( '{harga}', $asuransi_price, $message );
				$checkbox   = '<input type="checkbox" name="jne_asuransi" value="yes" ' . checked( $current_value, 1, false ) . ' />';

				woocommerce_get_template( 'jne-asuransi.php', array(
					'message_jne_asuransi'	=> sprintf( $checkbox . $message, $asuransi_price ),
				), 'woocommerce-jne-exclusive', untrailingslashit( WC_JNE()->plugin_path() ) . '/templates/' );
			
			}
			
		}
	}
	
	/**
	 * When added to cart, save jne asuransi data
	 *
	 * @access public
	 * @param mixed $cart_item_meta
	 * @param mixed $product_id
	 * @return void
	 * @since 8.0.0
	 */
	public function add_cart_item_data( $cart_item_meta, $product_id ){
        $asuransi_is = $this->asuransi_is( $product_id );
        
		$product = new WC_Product( $product_id );
        
		if ( ! empty( $_POST['jne_asuransi'] ) && ( $asuransi_is['override'] || ( $asuransi_is['enable'] && $asuransi_is['required'] ) ) ) {
			$cart_item_meta['jne_asuransi'] = true;
			$cart_item_meta['jne_asuransi_price'] = $this->get_asuransi_price( $product->price );
		}

		return $cart_item_meta;
	}
	
	/**
	 * Get the asuransi data from the session on page load
	 *
	 * @access public
	 * @param mixed $cart_item
	 * @param mixed $values
	 * @return void
	 * @since 8.0.0
	 */
	public function get_cart_item_from_session( $cart_item, $values ){
		if( ! empty( $values['jne_asuransi'] ) && ! empty( $values['jne_asuransi_price'] ) ){
			$cart_item['jne_asuransi'] = true;
			$cart_item['data']->adjust_price( $cart_item['jne_asuransi_price'] );
		}

		return $cart_item;
	}

	/**
	 * Display gift data if present in the cart
	 *
	 * @access public
	 * @param mixed $item_data
	 * @param mixed $cart_item
	 * @return void
	 * @since 8.0.0
	 */
	public function get_item_data( $item_data, $cart_item ){
		if( ! empty( $cart_item['jne_asuransi'] ) ){
			$price = $cart_item['jne_asuransi_price'] * $cart_item['quantity'];
			$item_data[] = array(
				'name'	=> sprintf( __( 'JNE Asuransi x %u ', 'woocommerce-jne' ), $cart_item['quantity'] ),
				'value'	=> wc_price( $price ),
				'display'	=> wc_price( $price )
			);
		}

		return $item_data;
	}

	/**
	 * Adjust price after adding to cart
	 *
	 * @access public
	 * @param mixed $cart_item
	 * @return void
	 * @since 8.0.0
	 */
	public function add_cart_item( $cart_item ){
		if( ! empty( $cart_item['jne_asuransi'] ) ){
			$cart_item['data']->adjust_price( $cart_item['jne_asuransi_price'] );
		}

		return $cart_item;
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
		if( ! empty( $cart_item['jne_asuransi'] ) ){
			woocommerce_add_order_item_meta( $item_id, __( 'JNE Asuransi', 'woocommerce-jne' ), wc_price( $cart_item['jne_asuransi_price'] ) );	
		}
	}

	/**
	 * Added jne asuransi enable option to the woocommerce metabox
	 *
	 * @access public
	 * @return void
	 * @since 8.0.0
	 **/
	public function write_panel(){	
		echo '</div><div class="options_group show_if_simple show_if_variable">';

		woocommerce_wp_checkbox( array(
			'id'            => '_is_jne_asuransi_enable',
			'wrapper_class' => '',
			'label'         => __( 'JNE Asuransi', 'agenwebsite' ),
			'description'   => __( 'Aktifkan opsi ini jika pelanggan anda dapat memilih menggunakan asuransi.', 'agenwebsite' ),
		) );

		woocommerce_wp_checkbox( array(
			'id'            => '_is_jne_asuransi_required',
			'wrapper_class' => '',
			'label'         => '',
			'description'   => __( 'Aktifkan opsi ini untuk mewajibkan asuransi pada produk ini.', 'agenwebsite' ),
		) );

		wc_enqueue_js( "
			jQuery('input#_is_jne_asuransi_enable').change(function(){

				jQuery('._is_jne_asuransi_required_field').hide();

				if ( jQuery('#_is_jne_asuransi_enable').is(':checked') ) {
					jQuery('._is_jne_asuransi_required_field').show();
				}

			}).change();
		" );

	}
	
	/**
	 * Saved jne asuransi enable option
	 *
	 * @access public
     * @param string $post_id
	 * @return void
	 * @since 8.0.0
	 **/
	public function write_panel_save( $post_id ){

		$_is_jne_asuransi_enable = ! empty( $_POST['_is_jne_asuransi_enable'] ) ? 'yes' : 'no';	
		$_is_jne_asuransi_required = ! empty( $_POST['_is_jne_asuransi_required'] ) ? 'yes' : 'no';	

		update_post_meta( $post_id, '_is_jne_asuransi_enable', $_is_jne_asuransi_enable );
		update_post_meta( $post_id, '_is_jne_asuransi_required', $_is_jne_asuransi_required );

	}
		
	/**
	 * Price asuransi JNE
	 *
	 * @access private
	 * @param mixed $product_price
	 * @return string
	 * @since 8.0.0
	 */
	private function get_asuransi_price( $product_price ){
		return $product_price / 100 * 0.2 + 5000;
	}

}

endif;