<?php

if ( isset($_GET['task']) )
{
		switch ( strtolower($_GET['task']) )
		{
		
				case "verify":
						if ( empty($_POST) )
						{
							echo "Stop : Access Not Valid";
							die;
						}
													 
						if (substr($this->getMyipaddress(),0,strlen($this->ip_range)) !== $this->ip_range)
						{
							 echo "Stop : IP Not Allowed";
						}
						else
						{	    
							$trx = array();
							
							$transidmerchant = explode( "_", $_POST['TRANSIDMERCHANT'] );
							
							$trx['words']                     = $_POST['WORDS'];
							$trx['amount']                    = $_POST['AMOUNT'];
							$trx['transidmerchant']           = $transidmerchant[1];
							$trx['msc_transidmerchant']       = $_POST['TRANSIDMERCHANT'];
							
							$config = $this->getMyServerConfig();
							
							$words = sha1(trim($trx['amount']).
														trim($config['SHARED_KEY']).
														trim($trx['msc_transidmerchant']));
							
							if ( $trx['words']==$words )
							{		    
								 $trx['ip_address']            = $this->getMyipaddress();
								 $trx['process_datetime']      = date("Y-m-d H:i:s");
								 $trx['process_type']          = 'VERIFY';
								 $trx['message']               = "Verify process message come from MyShortCart";  
														 
								 $result = $this->checkMyTrx($trx);
								 
								 if ( $result < 1 )
								 {
										 echo "Stop : Transaction Not Found";
										 die;		    
								 }
								 else
								 {								
										$order = new WC_Order($trx['transidmerchant']);
										$order->update_status('processing', __( $trx['message'], 'woocommerce' ));
										//$order->update_status('pending', __( $trx['message'], 'woocommerce' ));
										// Reduce stock levels
                                        $order->reduce_order_stock();
										$this->add_myshortcart($trx);										
										echo "Continue";
								 }
							}
							else
							{
								 echo "Stop : Request Not Valid";
								 die;
							}
						}
				break;

				case "notify":
						if ( empty($_POST) )
						{
							 echo "Stop : Access Not Valid";
							 die;
						}
														 
						if (substr($this->getMyipaddress(),0,strlen($this->ip_range)) !== $this->ip_range)
						{
								echo "Stop : IP Not Allowed";
						}
						else
						{	    
							 $trx = array();
							 
							 $transidmerchant = explode( "_", $_POST['TRANSIDMERCHANT'] );
							 
							 $trx['amount']              = $_POST['AMOUNT'];
							 $trx['transidmerchant']     = $transidmerchant[1];
							 $trx['msc_transidmerchant'] = $_POST['TRANSIDMERCHANT'];
							 $trx['result_msg']          = $_POST['RESULT'];
							 $trx['ip_address']          = $this->getMyipaddress();
							 $trx['process_datetime']    = date("Y-m-d H:i:s");
							 $trx['process_type']        = 'NOTIFY';
							 
							 $result = $this->checkMyTrx($trx);
							 
							 if ( $result < 1 )
							 {
									 echo "Stop : Transaction Not Found";
									 die;		    
							 }
							 else
							 {

										$order = new WC_Order($trx['transidmerchant']);										
										
										if ( strtolower($trx['result_msg'])=="success" )
										{
												$trx['message'] = "Notify process message come from MyShortCart. Transaction Success";
												$status         = "completed";
												$order->payment_complete($trx['transidmerchant']);
												$order->update_status($status, __( $trx['message'], 'woocommerce' ));

										}
										else
										{
												$trx['message'] = "Notify process message come from MyShortCart. Transaction Failed";
												$status         = "failed";
												$order->update_status($status, __( $trx['message'], 'woocommerce' ));
												$order->update_status('cancelled', __( $trx['message'], 'woocommerce' ));;				 
										}
					
										//$order->update_status($status, __( $trx['message'], 'woocommerce' ));									

										$this->add_myshortcart($trx);
										echo "Continue";
									
							 }
						}  
				break;

				case "redirect":
						if ( empty($_POST) )
						{
							 echo "Stop : Access Not Valid";
							 die;
						}
						
						$transidmerchant = explode( "_", $_POST['TRANSIDMERCHANT'] );
								
						$trx['amount']               = $_POST['AMOUNT'];
						$trx['transidmerchant']      = $transidmerchant[1];
						$trx['msc_transidmerchant']  = $_POST['TRANSIDMERCHANT'];
						$trx['status_code']          = $_POST['STATUSCODE'];
						
						if ( isset($_POST['PAYMENTCODE']) ) $trx['payment_code'] = $_POST['PAYMENTCODE'];
						
						$trx['result_msg']       = $_POST['RESULT'];
						$trx['payment_datetime'] = $_POST['TRANSDATE'];
						$trx['payment_channel']  = $_POST['PTYPE'];
						$trx['extra_info']       = $_POST['EXTRAINFO'];
						$trx['ip_address']       = $this->getMyipaddress();
						$trx['process_datetime'] = date("Y-m-d H:i:s");
						$trx['process_type']     = 'REDIRECT';
						
						if ( $trx['status_code']=="00" || strtolower($trx['result_msg']) == 'success' )
						{
							 $trx['message'] = "Redirect process message come from MyShortCart. Transaction is Success";  
							 $status         = "completed";				
							 $return_message = "Terima kasih telah berbelanja di EMK-Indonesia.com.</br>Pembayaran Anda menggunakan Kartu Kredit telah berhasil.</br><p style='font-size:16px; width:70%; margin-top:10px; max-width:1000px;'>Pesanan Anda akan kami kami kirimkan 1 hari kerja setelah pembayaran kami terima. Kami akan mengirimkan detail resi paket melalui e-mail 1 hari kerja setelah barang dikirimkan. <strong>Mohon cek email Anda</strong> untuk bukti pembayaran dan detail lainnya.</p></br><img src='/wp-content/uploads/2015/12/emk-indonesia-logo-trans.png'/>";
						}
						else
						{
								$result = $this->checkMyTrx($trx);
							 
								$order = new WC_Order($trx['transidmerchant']);																	 
							 
								if ( ( strtolower($trx['payment_channel'])=="bank transfer" || strtolower($trx['payment_channel'])=="alfamart" ) && $result > 0  )
								{
									 $trx['message'] = "Redirect process message come from MyShortCart. Transaction is wait for payment from ATM / ALFA Mart";
									 $status         = "pending";									
									 $return_message = "This is your Payment Code : ".$trx['payment_code']."<br>Please do the payment in 12 hours before expired.<br>If you need help for payment, please contact our customer service.<br>";
								}
								else
								{
									 $trx['message'] = "Redirect process message come from MyShortCart. Transaction is Failed";  
									 $status         = "cancelled";				
									 $return_message = "<img src='/wp-content/uploads/2015/12/emk-indonesia-logo-trans.png'/></br></br>
									 <p style='font-size:16px; width:70%; margin-top:10px; max-width:1000px;'>Proses pembayaran menggunakan Kartu Kredit gagal.</br>Harap cek ulang informasi kartu kredit Anda dan mencoba kembali. </br></p>
									 <p style='font-size:16px; width:70%; margin-top:10px; max-width:1000px;'>Jika Anda memerlukan bantuan untuk proses pembayaran, silahkan hubungi customer service kami:</br>
									 <strong>Email:</strong> cs@emk-indonesia.com</br>
									 <strong>Telepon:</strong> 0817200536</br>
									 <strong>Whatsapp Chat / SMS:</strong> 0817200536</br>
									 <strong>LINE Chat:</strong> @emk-indonesia</br></p>";
								}
								
								$order->update_status($status, __( $trx['message'], 'woocommerce' ));
						}						
						
						$this->add_myshortcart($trx);
												
						get_header();
						echo "<br /><br /><h3><center>$return_message</center></h3><br /><br />";
						get_footer();						
				break;
				
				default:
						echo "Stop : Request Not Recognize";
				break;
		}
}

?>
