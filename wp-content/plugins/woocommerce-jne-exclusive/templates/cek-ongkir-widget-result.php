<?php
/**
 * JNE Cek Ongir Widget Result
 *
 * @author AgenWebsite
 * @package WooCommerce JNE Shipping
 * @since 8.0.0
 */
 
if ( !defined( 'WOOCOMMERCE_JNE' ) ) { exit; } // Exit if accessed directly

?>
<h4><?php printf( __( 'Biaya kirim ke %s ( Berat: %u%s )', 'agenwebsite' ), $tujuan['kota'], $tujuan['weight'], $tujuan['weight_unit'] );?></h4>
       
	   	<?php foreach( $layanan as $nama_layanan => $data_layanan ):?>
		
		<div class="layanan">
        	<div class="nama_paket">
				<?php echo sprintf( 'Paket %s', strtoupper( $nama_layanan ) );?>
           	</div>
            <div class="jenis_paket">
            	<?php echo __( 'Dokumen/Paket', 'agenwebsite' );?>
            </div>
            <div class="estimasi_hari">
            	<?php echo sprintf( '%s hari', $data_layanan['etd'] );?>
            </div>
            <div class="harga_paket">
            	<?php echo $data_layanan['harga'];?>
            </div>
       	</div>

        <?php endforeach;?>

<button id="woocommerce_jne_cekongkir_<?php echo $id;?>_tutup" class="button cek_ongkir_kembali"><?php echo __( 'Kembali', 'agenwebsite' );?></button>
