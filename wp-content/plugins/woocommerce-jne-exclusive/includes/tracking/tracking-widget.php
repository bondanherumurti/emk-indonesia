<?php
/**
 * JNE Tracking Widget
 *
 * Widget for check number tracking JNE
 *
 * @author AgenWebsite
 * @package WooCommerce JNE Shipping
 * @since 8.0.0
 */

if ( !defined( 'WOOCOMMERCE_JNE' ) ) { exit; } // Exit if accessed directly

if ( !class_exists( 'WC_JNE_Tracking_Widget' ) ) :
	
/**
 * Class WooCommerce JNE Tracking Widget
 *
 * @since 8.0.0
 **/
class WC_JNE_Tracking_Widget extends WP_Widget{
	
	/**
	 * Register widget with WordPress.
	 *
	 * @return void
	 * @since 8.0.0
	 **/
	public function __construct(){
		parent::__construct(
			'JNE_Tracking',
			'JNE Tracking',
			array( 'description' => __( 'Tambahkan widget JNE Tracking.', 'agenwebsite' ) )
		);	
	}

	/**
	 * Front-end display of widget.
	 *
	 * @see WP_Widget::widget()
	 * @access public
	 * @param array $args		Widget arguments.
	 * @param array $instance	Saved values from database
	 * @return void
	 * @since 8.0.0
	 **/
	public function widget( $args, $instance ) {
		extract( $args );
		extract( $instance );
		
		wp_enqueue_style( 'woocommerce-jne-tracking-widget' );
					
		$output = $before_widget;
			$output .= $before_title;
				$output .= $title;
			$output .= $after_title;
			$output .= '<form method="post" action="' . WC_JNE()->tracking->base_url . WC_JNE()->tracking->endpoint_url . '" target="_blank" class="jne_tracking" id="jne_tracking_widget">' . "\n";
				$output .= '<label for="awbnum_widget">' . $placeholder_tracking_number . '</label>' . "\n";
				$output .= '<input type="text" id="awbnum_widget" name="awbnum" placeholder="' . $placeholder_tracking_number . '" class="input_resi" style="margin-bottom:15px;" required />' . "\n";
				
				$output .= '<div class="captcha_img">' . "\n";
				$output .= '<img id="captcha_widget" alt="" src="' . WC_JNE()->tracking->base_url . WC_JNE()->tracking->captcha_url . '" width="140" /><br />' . "\n";
				$output .= '<a id="change-image-widget" href="javascript:void">' . __( 'Refresh', 'agenwebsite' ) . ' <span class="dashicons dashicons-update"></span></a><br />' . "\n";
				$output .= '</div>' . "\n";
				$output .= '<script type="text/javascript">' . "\n";
					$output .= '
							jQuery(document).ready(function($) {
								var captchaID = $(\'#captcha_widget\');
								$( "#change-image-widget" ).click(function(){
									$( "form#jne_tracking_widget" ).addClass("updating-captcha");
									
									$( captchaID ).one("load", function() {
										$( "form#jne_tracking_widget" ).removeClass("updating-captcha");
									}).attr( "src", \'' . WC_JNE()->tracking->base_url . WC_JNE()->tracking->captcha_url . '?\' + Math.random() );
								});
							});
					';
				$output .= '</script>' . "\n";
				
				$output .= '<label for="awbcaptcha_widget">' . $placeholder_captcha . '</label>' . "\n";
				$output .= '<input id="awbcaptcha_widget" name="captcha" type="text" placeholder="' . $placeholder_captcha . '" class="input_captcha" required />' . "\n";
				$output .= '<p><input type="submit" value="' . $button . '" class="button alt" name="submittracking" /></p>' . "\n";
			$output .= '</form>' . "\n";
		$output .= $after_widget;
	
		echo $output;
	}
	
	/**
	 * Sanitize widget form values as they are saved.
	 *
	 * @see WP_Widget::update()
	 * @access public
	 * @param mixed $new_instance Values just sent to be saved.
	 * @param mixed $old_instance Previously saved values from database.
	 * @return array Updated safe values to be saved.
	 * @since 8.0.0
	 **/
	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['title'] = ( ! empty( $new_instance['title'] ) ? strip_tags( $new_instance['title'] ) : '' );
		$instance['placeholder_tracking_number'] = ( ! empty( $new_instance['placeholder_tracking_number'] ) ? strip_tags( $new_instance['placeholder_tracking_number'] ) : '' );
		$instance['placeholder_captcha'] = ( ! empty( $new_instance['placeholder_captcha'] ) ? strip_tags( $new_instance['placeholder_captcha'] ) : '' );
		$instance['button'] = ( ! empty( $new_instance['button'] ) ? strip_tags( $new_instance['button'] ) : '' );
		return $instance;
	}

	/**
	 * Back-end widget form.
	 *
	 * @see WP_Widget::form()
	 * @access public
	 * @param array $instance Previously saved values from database.
	 * @return void
	 * @since 8.0.0
	 **/
	public function form( $instance ) {
		extract( $instance );
		
		$placeholder_tracking_number = ( empty( $placeholder_tracking_number ) ? 'Masukkan nomor resi JNE' : $placeholder_tracking_number );
		$placeholder_captcha = ( empty( $placeholder_captcha ) ? 'Masukkan teks captcha' : $placeholder_captcha );
		$button = ( empty( $button ) ? 'Submit' : $button );
		
		$forms = array( 
			'title' => array(
				'label'	=> __( 'Title', 'agenwebsite' ),
				'value'	=> ! empty( $title ) ? $title : ''
			),
			'placeholder_tracking_number' => array(
				'label'	=> __( 'Placeholder Tracking Number', 'agenwebsite' ),
				'value'	=> $placeholder_tracking_number
			),
			'placeholder_captcha' => array(
				'label'	=> __( 'Placeholder Captcha', 'agenwebsite' ),
				'value'	=> $placeholder_captcha
			),
			'button' => array(
				'label'	=> __( 'Button Text', 'agenwebsite' ),
				'value'	=> $button
			));
			
		$output = '';
		foreach( $forms as $id => $values ){
			extract( $values );
			$output .= '<p>';
				$output .= '<label for="' . $this->get_field_id( $id ) . '">' . __( $label, 'agenwebsite' ) . '</label>';
				$output .= '<input type="text" name="' . $this->get_field_name( $id ) . '" value="' . esc_attr( $value ) . '" class="widefat" id="' . $this->get_field_id( $id ) . '" />';
			$output .= '</p>';
		}
		
		echo $output;
		?>
		<?php
	}
			
}
 
// Register widget
if( ! function_exists( 'register_jne_tracking_widget' ) ) {
	add_action( 'widgets_init', 'register_awjne_tracking_widget' );
	function register_awjne_tracking_widget(){
		register_widget( 'WC_JNE_Tracking_Widget' );
	}
}

endif;