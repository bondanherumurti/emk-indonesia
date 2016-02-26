<?php
/**
 * JNE Cek Ongkir Tab
 *
 * @author AgenWebsite
 * @package WooCommerce JNE Shipping
 * @since 8.0.0
 */
 
if ( !defined( 'WOOCOMMERCE_JNE' ) ) { exit; } // Exit if accessed directly
?>

<div id="woocommerce_jne_cekongkir_tab" class="woocommerce_jne_cekongkir_tab">

	<h3 class="tab-title"><?php printf( '%s', __( 'Tujuan Pengiriman', 'agenwebsute' ) );?></h3>

    <div id="woocommerce_jne_cekongkir_tab_container" class="row">
    
    <?php foreach( $fields as $id_fields => $args ):?>
    	
        <?php $id_field = 'woocommerce_jne_cekongkir_tab_' . $id_fields;?>
        
            	<?php switch( $args['type'] ):
                
                	case 'text' : ?>
                    	
                        <input type="hidden" name="<?php echo $id_field;?>" id="<?php echo $id_field;?>" value="<?php echo $weight['product']?>" />
                        <input type="hidden" name="woocommerce_jne_cekongkir_tab_weight_unit" id="woocommerce_jne_cekongkir_tab_weight_unit" value="<?php echo $weight['unit']?>" />                        
                        
                    <?php break;?>
                  	
                    <?php case 'select' : ?>
                    	
	   					<div class="co-lom co-3">
                        
                        <select name="<?php echo $id_field;?>" id="<?php echo $id_field;?>" required="true">
                        
                        	<?php foreach( $args['option'] as $value => $label ):?>
	                            <option value="<?php echo $value;?>"><?php echo __( $label, 'agenwebsite' );?></option>
                            <?php endforeach;?>
                        
                        </select>
                        
                        </div>
                        
                    <?php break;?>
                
                <?php endswitch;?>
        
    <?php endforeach;?>
	
    </div>
    
	<div id="woocommerce_jne_cekongkir_tab_result" style="display:none"></div>
    
	<div id="woocommerce_jne_cekongkir_tab_loader" style="background-image:url(<?php echo WC()->plugin_url();?>/assets/images/icons/loader.svg)"></div>
    
</div>