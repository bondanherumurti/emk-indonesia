<?php
/**
 * JNE Cek Ongkir Widget
 *
 * @author AgenWebsite
 * @package WooCommerce JNE Shipping
 * @since 8.0.0
 */

if ( !defined( 'WOOCOMMERCE_JNE' ) ) { exit; } // Exit if accessed directly

if ( !class_exists( 'WC_JNE_Cek_Ongkir_Widget' ) ) :
	
/**
 * Class WooCommerce JNE Tracking
 *
 * @since 8.0.0
 **/
class WC_JNE_Cek_Ongkir_Widget extends WP_Widget{
	
	public $id_base = 'jne_cekongkir_widget';
	
	/**
	 * Register widget
	 *
	 * @return void
	 * @since 8.0.0
	 **/
	function __construct(){
		parent::__construct(
			$this->id_base,
			'JNE Cek Ongkir',
			array( 'description' => __( 'Tambahkan widget JNE Tracking.', 'agenwebsite' ) )
		);			
	}
	
	/**
     * Front-end display of widget.
     *
     * @see WP_Widget::widget()
     *
     * @access public
     * @param array $args     Widget arguments.
     * @param array $instance Saved values from database.
     * @since 8.0.0
     */
    public function widget( $args, $instance ){
		extract( $args );
		extract( $instance );
		
		$output = $before_widget;
			$output .= $before_title;
				$output .= $title;
			$output .= $after_title;
			
			/*
			 * Get template form to action cek ongkir
			 */
			ob_start();
			$this->cek_ongkir_widget();
			$output .= ob_get_clean();
			
		$output .= $after_widget;
		
		echo $output;
	}
	
	/**
     * Back-end widget form.
     *
     * @see WP_Widget::form()
     *
     * @access public
     * @param array $instance Previously saved values from database.
     * @since 8.0.0
     */			
	public function form( $instance ){
		$title = ! empty( $instance['title'] ) ? $instance['title'] : __( 'JNE Cek Ongkir', 'text_domain' );
		$output = '<p>';
			$output .= '<label for="' . $this->get_field_id( 'title' ) . '">' . __( 'Title:', 'agenwebsite' ) . '</label>';
			$output .= '<input type="text" class="widefat" name="' . $this->get_field_name( 'title' ) . '" value="' . esc_attr( $title ) . '" id="' . $this->get_field_id( 'title' ) . '" />';
		$output .= '</p>';	
		
		echo $output;		
	}
	
	/**
	 * Sanitize widget form values as they are saved.
	 *
	 * @see WP_Widget::update()
	 *
     * @access public
     *
	 * @param array $new_instance Values just sent to be saved.
	 * @param array $old_instance Previously saved values from database.
	 *
	 * @return array Updated safe values to be saved.
     *
     * @since 8.0.0
	 */
	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
		return $instance;
	}
	
	/**
	 * Load template jne cek ongkir widget
	 *
	 * @access public
	 * @return void
	 * @since 8.0.0
	 */
	public function cek_ongkir_widget(){
		
		$weight['unit']	= 'kg';
		$weight['product'] = '1';
		
		woocommerce_get_template( 'cek-ongkir-widget.php', array(
			'id'         => 'widget',
			'weight'     => $weight,
			'fields'     => apply_filters( 'woocommerce_jne_cek_ongkir_fields', WC_JNE()->shipping->cek_ongkir->fields() )
		), 'woocommerce-jne-exclusive', untrailingslashit( WC_JNE()->plugin_path() ) . '/templates/' );				
	}		
	
}

endif;