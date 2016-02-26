<?php
/**
 * JNE Cek Ongir Tab Result
 *
 * @author AgenWebsite
 * @package WooCommerce JNE Shipping
 * @since 8.0.0
 */
 
if ( !defined( 'WOOCOMMERCE_JNE' ) ) { exit; } // Exit if accessed directly

?>

<h3><?php echo sprintf( __( 'Estimasi Biaya kirim ke %s ( Berat %u%s )' ), $tujuan['kota'], $tujuan['weight'], $tujuan['weight_unit'] );?></h3>
		
<table cellpadding="0" cellspacing="0" border="0">
	<tr>
		<th><?php echo __( 'Layanan', 'agenwebsite' );?></th>
		<th><?php echo __( 'Jenis Kiriman', 'agenwebsite' );?></th>
		<th><?php echo __( 'Estimasi Hari', 'agenwebsite' );?></th>
		<th><?php echo __( 'Tarif', 'agenwebsite' );?></th>
	</tr>
	
       <?php foreach( $layanan as $nama_layanan => $data_layanan ):?>
		
       		<tr>
            	<td><?php echo sprintf( 'JNE %s', strtoupper( $nama_layanan ) );?></td>
                <td><?php echo __( 'Dokumen/Paket', 'agenwebsite' );?></td>
                <td><?php echo sprintf( '%s hari', $data_layanan['etd'] );?></td>
                <td><?php echo $data_layanan['harga'];?></td>
         	</tr>

        <?php endforeach;?>
		
	<tr>
		<td colspan="4">
			<button id="woocommerce_jne_cekongkir_tab_tutup" class="button cek_ongkir_kembali"><?php echo __( 'Tutup', 'agenwebsite' );?></button>
		</td>
	</tr>
		
</table>
