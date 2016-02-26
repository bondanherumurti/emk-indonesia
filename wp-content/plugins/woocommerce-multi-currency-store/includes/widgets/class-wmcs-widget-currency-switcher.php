<?php
/**
 * Top Rated Products Widget
 *
 * Gets and displays top rated products in an unordered list
 *
 * @author 	WooThemes
 * @category 	Widgets
 * @package 	WooCommerce/Widgets
 * @version 	2.1.0
 * @extends 	WC_Widget
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class WMCS_Widget_Currency_Switcher extends WP_Widget {

	/**
	 * Constructor
	 */
	public function __construct() {
		
		parent::__construct(
			'wmcs_currency_switcher',
			__( 'WMCS Currency Switcher' ),
			array( 'description' => __( 'Allow your customer to switch prices between the various currencies available in you store' ) )
		);
	}
		
	public function widget( $args, $instance ){
		
		$store_currencies = $store_currencies = get_option( 'wmcs_store_currencies', array() );
		
		if( !empty( $store_currencies ) ){
			
			$store_currencies = array( get_option('woocommerce_currency') => array() ) + $store_currencies; //add base currency as selectable
			
			$customers_currency = wmcs_get_customers_currency();
		
			echo $args['before_widget'];
		
			if ( ! empty( $instance['title'] ) ) {
				echo $args['before_title'];
				echo apply_filters( 'widget_title', $instance['title'] );
				echo $args['after_title'];
			}
			
			if( $instance['output_type'] == 'dropdown' ){
			//dropdown
				?>
				
				<select class="wmcs_currency_switcher_dropdown">
					<?php
					foreach( $store_currencies as $k => $v ){
						if( $instance['currency_output'] == 'code' ) $output = $k;
						elseif( $instance['currency_output'] == 'symbol' ) $output = get_woocommerce_currency_symbol( $k );
						else $output = $k . '('.get_woocommerce_currency_symbol( $k ).')';
						?>
						
						<option value="<?php echo add_query_arg( 'wmcs_set_currency', $k ); ?>" <?php echo ( $k == $customers_currency ) ? 'selected="selected"' : ''; ?>><a href="#"><?php echo $output; ?></a></option>
						<?php
					}
					?>
				</select>
				
				<script>			
					jQuery(document).ready(function(){
					
						jQuery('.wmcs_currency_switcher_dropdown').on('change', function () {
							var url = jQuery(this).val(); 
							if (url) {
								window.location = url; 
							}
							return false;
						});
						
					});
				</script>
			
			<?php
			} else {
			//radio
			
				foreach( $store_currencies as $k => $v ){
					if( $instance['currency_output'] == 'code' ) $output = $k;
					elseif( $instance['currency_output'] == 'symbol' ) $output = get_woocommerce_currency_symbol( $k );
					else $output = $k . '('.get_woocommerce_currency_symbol( $k ).')';
					?>
					<label for="currency_<?php echo $k; ?>">
						<input class="wmcs_currency_switcher_radio" name="wmcs_currency_switcher_radio" type="radio" value="<?php echo add_query_arg( 'wmcs_set_currency', $k ); ?>" <?php echo ( $k == $customers_currency ) ? 'checked="checked"' : ''; ?> /> <?php echo $output; ?>
					</label>
					<?php
				}
				?>
				<script>			
					jQuery(document).ready(function(){
					
						jQuery('input[name="wmcs_currency_switcher_radio"]:radio').on('change', function () {
							var url = jQuery('input[name=wmcs_currency_switcher_radio]:checked').val()
							if (url) {
								window.location = url; 
							}
							return false;
						});
						
					});
				</script>
				
				<?php
			}
			
			echo $args['after_widget'];
			/*
			?>
			<script>
			
			jQuery(document).ready(function(){
			
				//dropdown change
				jQuery('#wmcs_currency_switcher_dropdown').on('change', function () {
					var url = jQuery(this).val(); // get selected value
					if (url) { // require a URL
						window.location = url; // redirect
					}
					return false;
				});
				
				//
				
			});
			
			</script>
			<?php*/
		}
		
	}
	
	public function form( $instance ){
		$title = !empty( $instance['title'] ) ? $instance['title'] : __( 'WMCS Currency Switcher' );
		$output_type = !empty( $instance['output_type'] ) ? $instance['output_type'] : 'dropdown'; 
		$currency_output = !empty( $instance['currency_output'] ) ? $instance['currency_output'] : 'both'; 
		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label> 
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'output_type' ); ?>"><?php _e( 'Show currencies as:' ); ?></label>
			<label>
				<input type="radio" id="<?php echo $this->get_field_id( 'output_type' ); ?>" name="<?php echo $this->get_field_name( 'output_type' ); ?>" value="dropdown" <?php echo ( $output_type == 'dropdown' ) ? 'checked="checked"' : ''; ?> /> Dropdown
			</label>
			<label>
				<input type="radio" id="<?php echo $this->get_field_id( 'output_type' ); ?>" name="<?php echo $this->get_field_name( 'output_type' ); ?>" value="radio" <?php echo ( $output_type == 'radio' ) ? 'checked="checked"' : ''; ?> /> Radio
			</label>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'currency_output' ); ?>"><?php _e( 'Currency output:' ); ?></label> 
			<select name="<?php echo $this->get_field_name( 'currency_output' ); ?>">
				<option value="code" <?php echo ( $currency_output == 'code' ) ? 'selected' : ''; ?>>Currency Code</option>
				<option value="symbol" <?php echo ( $currency_output == 'symbol' ) ? 'selected' : ''; ?>>Currency Symbol</option>
				<option value="both" <?php echo ( $currency_output == 'both' ) ? 'selected' : ''; ?>>Both Currency Code and Symbol</option>
			</select>
		</p>
		<?php
	}
	
	public function update( $new_instance, $old_instance ){
		
		$instance = array();
		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
		$instance['output_type'] = $new_instance['output_type'];
		$instance['currency_output'] = $new_instance['currency_output'];
		
		return $instance;
		
	}
	
}
