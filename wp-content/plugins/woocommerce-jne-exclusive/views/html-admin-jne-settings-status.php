<?php
/**
 * Admin View: Page - Status Report
 */

if ( ! defined( 'WOOCOMMERCE_JNE' ) ) {
	exit;
}

// Link dokumentasi
$dok = 'http://docs.agenwebsite.com/wp-content/plugins/documente/documentations/woocommerce_jne_shipping/index.html';
$dok_table =                $dok . '#7';
$dok_weight_unit =          $dok . '#9';
$dok_currency_symbol =      $dok . '#8';
$dok_currency_decimals =    $dok . '#8';
?>

<table class="woocommerce_jne_status_table widefat is-large-screen" id="wc_jne_status" cellspacing="0">
	<thead>
    	<tr>
        	<th colspan="3"><?php echo __( 'AgenWebsite Product Status', 'agenwebsite' );?></th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td colspan="3" class="load_status"><center><img src="<?php echo WC_JNE()->plugin_url();?>/assets/images/progress.gif" /> <span>Connecting to AgenWebsite ...</span></center></td>
        </tr>
   	</tbody>
</table>
<br><br>
<table class="woocommerce_jne_status_table widefat is-large-screen" cellspacing="0">
	<thead>
    	<tr>
        	<th colspan="3"><?php echo __( 'WooCommerce JNE Settings Status', 'agenwebsite' ); printf( '<a href="%s" target="new">%s</a>', $dok_table, WC_JNE()->help_tip( 'Klik untuk melihat penjelasan tentang table ini' ) )?></th>
        </tr>
    </thead>
    <tbody>
        <tr>
        	<td><?php echo __( 'Plugin JNE Version', 'agenwebsite' );?></td>
            <td><span id="aw_status_version"><mark class="yes"><?php echo WOOCOMMERCE_JNE_VERSION;?></mark></span></td>
            <td><span id="aw_status_version_help" style="display:none"><?php echo WC_JNE()->link_tip( 'Klik untuk download update terbaru di my account page', 'Download', '', 'new' );?></span></td>
        </tr>
        <tr>
        	<td>
				<?php echo __( 'WC Weight Unit', 'agenwebsite' );?>
                <?php echo WC_JNE()->help_tip( 'Plugin ini akan berfungsi maksimal dengan kg dan g di pengaturan WooCommerce Weight Unit', 'right' );?>
            </td>
            <td>
				<?php
				$weight_status = WC_JNE()->get_status_weight();
				echo ( $weight_status['message'] != 'error' ) ? '<mark class="yes">' . $weight_status['unit'] . '</mark>' : '<mark class="no">' . $weight_status['unit'] . '</mark>';
				?>
            </td>
            <td>
            	<?php 
				if( $weight_status['message'] == 'error'):
                	echo WC_JNE()->link_tip( 'Klik untuk melihat cara ganti weight unit', 'Bantuan', $dok_weight_unit, 'new' );
                endif;
                ?>
            </td>
        </tr>
        <tr>
        	<td>
				<?php echo __( 'WC Currency Symbol', 'agenwebsite' );?>
                <?php echo WC_JNE()->help_tip( 'Tarif jne menggunakan mata uang rupiah, pilih Rp. di pengaturan WooCommerce', 'right' );?>
            </td>
            <td><?php echo ( get_woocommerce_currency_symbol() == 'Rp' ) ? '<mark class="yes">' . get_woocommerce_currency_symbol() . '</mark>' : '<mark class="no">' . get_woocommerce_currency_symbol() . '</mark>';?></td>
            <td>
            <?php if( get_woocommerce_currency_symbol() != 'Rp' ) :?>
                <?php echo WC_JNE()->link_tip( 'Saran: Gunakan Rp. Klik untuk melihat cara ganti currency symbol', 'Bantuan', $dok_currency_symbol, 'new' );?>
            <?php endif;?>
            </td>
        </tr>
        <tr>
        	<td>
				<?php echo __( 'WC Currency Decimals', 'agenwebsite' );?>
                <?php echo WC_JNE()->help_tip( 'Pengaturan WooCommmerce untuk jumlah angka nol dibelakang koma. saran: maksimal 2', 'right' );?>                
            </td>
            <td><?php echo ( WC_JNE()->shipping->get_price_decimals() > 2 ) ? '<mark class="no">' . WC_JNE()->shipping->get_price_decimals() . '</mark>' : '<mark class="yes">' . WC_JNE()->shipping->get_price_decimals() . '</mark>'?></td>
            <td>
            	<?php if( WC_JNE()->shipping->get_price_decimals() > 2 ):?>
                    <?php echo WC_JNE()->link_tip( 'Saran: maksimal sampai 2. Klik untuk melihat cara ganti currency decimals', 'Bantuan', $dok_currency_decimals, 'new' );?>
				<?php endif;?>
            </td>
        </tr>
   	</tbody>
</table>
