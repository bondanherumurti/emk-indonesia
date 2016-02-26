<?php
/**
 * Plugin update class
 *
 * @package   Woocommerce Multi Currency Store
 * @author    Code Ninjas 
 * @link      http://codeninjas.co
 * @copyright 2014 Code Ninjas
 */
class WMCS_Updates{

	private $plugin_id = '01f77036-81d8-4ad2-8892-afadbe64a9eb';

	/**
	 * Initialize updates
	 *
	 * @since     1.1.0
	 */
	public function __construct()
	{		
		$this->automatic_updates_init();
		add_action( 'admin_init', array( $this, 'update_check' ) );
	}
	
	/**
	 * Update database to current version of plugin
	 *
	 * @since     1.1.0
	 */
	public function update_check()
	{ 
		// Get the db version
		$db_version = get_site_option( 'woocommerce_multi_currency_store_db_version', 0 );
		$plugin_version = WMCS_VERSION;
		
		if( version_compare( $db_version, $plugin_version, '==' ) ) return false;
		
		if( version_compare( $db_version, '1.2', 'lt' ) ) include 'wmcs-update-1-2.php';
		if( version_compare( $db_version, '1.2.1', 'lt' ) ) include 'wmcs-update-1-2-1.php';
		
		update_option( 'woocommerce_multi_currency_store_db_version', $plugin_version );
			
	}
	
	/**
	 * Automatic updates 
	 * Full credit to Janis Elsts @ http://w-shadow.com/ for this class 
	 *
	 * @since     1.1.0
	 */
	public function automatic_updates_init()
	{
		include 'class-wmcs-automatic-updates.php';
		$wmcs_automatic_updates = new PluginUpdateChecker(
			'http://updates.codeninjas.co?key='.$this->plugin_id,
			WMCS_FULL_PATH,
			'woocommerce-multi-currency-store'
		);
		//$wmcs_automatic_updates->checkForUpdates();
	}

}
		
return new WMCS_Updates();
