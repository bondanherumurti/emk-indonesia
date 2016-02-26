<?php
/**
 * JNE Cek Ongir Widget
 *
 * @author AgenWebsite
 * @package WooCommerce JNE Shipping
 * @since 8.0.0
 */
 
if ( !defined( 'WOOCOMMERCE_JNE' ) ) { exit; } // Exit if accessed directly
?>

<div id="woocommerce_jne_cekongkir_<?php echo $id;?>" class="woocommerce_jne_cekongkir_<?php echo $id;?>">

	<div id="woocommerce_jne_cekongkir_<?php echo $id;?>_container">
    
    <?php foreach( $fields as $id_fields => $args ):?>
    	
        <?php $id_field = 'woocommerce_jne_cekongkir_' . $id . '_' . $id_fields;?>
        
        <p class="<?php echo $id_field;?>">
            
			<?php switch( $args['type'] ):
               
           		case 'text' : ?>
                   	
        			<label for="<?php echo $id_field;?>"><?php echo sprintf( __( $args['label'], 'agenwebsite' ) . ' ( %s ) ', $weight['unit'] );?></label>
                    <input type="number" class="widefat" name="<?php echo $id_field;?>" id="<?php echo $id_field;?>" value="<?php echo $weight['product']?>" />
                       
            	<?php break;?>
                 	
                <?php case 'select' : ?>
                   	
        			<label for="<?php echo $id_field;?>"><?php echo __( $args['label'], 'agenwebsite' );?></label>
                    <select name="<?php echo $id_field;?>" id="<?php echo $id_field;?>" required="true">
                    
						<?php foreach( $args['option'] as $value => $label ):?>                                        
							<?php $disabled = ( $value == 1 ) ? 'disabled="disabled"' : '';?>
                            <option value="<?php echo $value;?>" <?php echo $disabled;?>><?php echo __( $label, 'agenwebsite' );?></option>
                        <?php endforeach;?>

                    </select>
                        
                <?php break;?>
                
        	<?php endswitch;?>
        </p>
        
  	<?php endforeach;?>
    
    </div>
    
    <div id="woocommerce_jne_cekongkir_<?php echo $id;?>_result" style="display:none"></div>
    
    <div id="woocommerce_jne_cekongkir_<?php echo $id;?>_loader" style="background-image:url(<?php echo WC()->plugin_url();?>/assets/images/icons/loader.svg);"></div>

</div>