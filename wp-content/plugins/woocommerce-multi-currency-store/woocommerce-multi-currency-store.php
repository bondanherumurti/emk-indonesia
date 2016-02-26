<?php
/**
 * @package   Woocommerce Multi Currency Store
 * @author    Code Ninjas
 * @link      http://codeninjas.co
 * @copyright 2014 Code Ninjas
 *
 * Plugin Name:       Woocommerce Multi Currency Store
 * Plugin URI:        http://codeninjas.co/plugins/woocommerce-multi-currency-store
 * Description:       Turn your single currency Woocommerce store into a multi currency store!
 * Version:           1.2.4
 * Author:            Code Ninjas
 * Author URI:        http://codeninjas.co
 * Documentation URI: http://docs.codeninjas.co/woocommerce-multi-currency-store
 */
 
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if( ! class_exists( 'Woocommerce_Multi_Currency_Store' ) ){

class Woocommerce_Multi_Currency_Store
{
	/**
	 * Plugin version
	 *
	 * @since   1.0.0
	 */
	public $version = '1.2.4';
	
	/**
	 * Required version of Wordpress
	 *
	 * @since   1.0.0
	 */
	protected $required_wp_version = '3.5';
	
	/**
	 * Plugins that are required by this plugin
	 *
	 * @since 1.0.0
	 */
	protected $required_plugins = array(
        'woocommerce/woocommerce.php' => array(
            'name'    => 'Woocommerce',
            'slug'    => 'woocommerce/woocommerce.php',
            'version' => '2.3'
        )
    );

	/**
	 * Plugin slug
	 *
	 * @since	1.1.0
	 */
	protected $plugin_slug = 'woocommerce-multi-currency-store';

	/**
	 * Instance of this class.
	 *
	 * @since	1.1.0
	 */
	protected static $instance = null;
	
	/**
	 * Admin notice errors
	 *
	 * since 1.0.0
	 */
	protected $errors = array(
        'dependency_not_installed' => 
			'<strong>%2$s</strong> has been deactivated because <strong>%1$s</strong> is not installed.  Please install and activate <strong>%1$s</strong> before activating <strong>%2$s</strong>.',
        'dependency_not_active' => 
			'<strong>%2$s</strong> has been deactivated because <strong>%1$s</strong> is currently inactive.  Please activate <strong>%1$s</strong> before activating <strong>%2$s</strong>.',
        'dependency_wrong_version' => 
			'<strong>%1$s</strong> requires <strong>%2$s %3$s</strong> or greater.  Please update <strong>%2$s</strong> before activating <strong>%1$s</strong>.',
        'wordpress_wrong_version' => 
			'<strong>%1$s</strong> requires <strong>Wordpress %2$s</strong> or greater. Please update Wordpress before activating <strong>%1$s</strong>.'
    );
	
	/**
	 * Admin notices to be output
	 *
	 * since 1.0.0
	 */
    public static $admin_notices = array();

	/**
	 * Initialize the plugin
	 *
	 * @since	1.0.0
	 */
	public function __construct() {
		
		if( !$this->check_requirements() )
			return;
		
		register_activation_hook( __FILE__, array( $this, 'activate' ) );
		register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );
		
		//Constants
		DEFINE( 'WMCS_VERSION', $this->version );
		DEFINE( 'WMCS_DIR', plugin_dir_path( __FILE__ ) ); 
		DEFINE( 'WMCS_BASE', plugin_basename( __FILE__ ) );
		DEFINE( 'WMCS_FULL_PATH', __FILE__ ); 
		DEFINE( 'WMCS_URI', trailingslashit( plugins_url() ) . $this->plugin_slug );		
		
		//Includes
		include 'includes/class-wmcs-common-init.php';
		
		add_action( 'wmcs_cron_check_exchange_rates', array( $this, 'cron_check_exchange_rates' ) );
		add_filter( 'cron_schedules', array( $this, 'wmcs_cron_schedules' ) );
		
		//Initialise frontend/admin
		if ( is_admin() ) {
			
			require_once( WMCS_DIR . 'admin/class-wmcs-admin.php' );
			add_action( 'plugins_loaded', array( 'WMCS_Admin', 'get_instance' ) );
			
			// Add extra action links 
			add_action( 'extra_plugin_headers', array( $this, 'extra_plugin_headers' ) );
			add_filter( 'plugin_action_links_' . WMCS_BASE, array( $this, 'add_action_links' ) );
			
		} else {
			/*
			require_once( WMCS_DIR . 'includes/class-wmcs-init.php' );
			add_action( 'plugins_loaded', array( 'WMCS_Init', 'get_instance' ) );*/
			
		}
		
	}
	
	/**
	 * Return the plugin slug.
	 *
	 * @since   1.0.0
	 *
	 * @return	string	Plugin slug variable.
	 */
	public function get_plugin_slug() {
		return $this->plugin_slug;
	}

	/**
	 * Return an instance of this class.
	 *
	 * @since     1.0.0
	 *
	 * @return	object	A single instance of this class.
	 */
	public static function get_instance() {

		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Fired when the plugin is activated.
	 *
	 * @since    1.0.0
	 */
	public static function activate( )
	{
		wp_schedule_event( time(), 'wmcs_daily', 'wmcs_cron_check_exchange_rates' ); 
	}

	/**
	 * Fired when the plugin is deactivated.
	 *
	 * @since    1.0.0
	 */
	public static function deactivate( )
	{	
		$timestamp = wp_next_scheduled( 'wmcs_cron_check_exchange_rates' );
		wp_unschedule_event( $timestamp, 'wmcs_cron_check_exchange_rates' );
	}
	
	/**
    * Checks that the WordPress setup meets the plugin requirements
    *
    * @return boolean
	*
	* @since 1.0.0
    */
    public function check_requirements()
    {   
		// When Wordpress deactivates a required plugin during updating, these checks fail and this plugin gets deactivated too
		// and isn't reactivated after the required plugin is reactivated. so we'll exit if we are updating so this plugin stays active.
		// If required plugin re-activation fails, then this plugin should deactivate as normal as soon as the user browses to another page
		global $pagenow;
		if( $pagenow == 'update.php' )
			if( isset($_GET['plugin']) && array_key_exists( $_GET['plugin'], $this->required_plugins ) ) return;
			
        $return = true;

        include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
        $plugin = get_plugin_data( __FILE__ );

        //Wordpress version
        global $wp_version;
        if ( ! version_compare( $wp_version, $this->required_wp_version, '>=' ) ) {
            self::$admin_notices[] = array( 'error' => sprintf( $this->errors['wordpress_wrong_version'], $plugin['Name'], $this->required_wp_version ) );
            $return = false;
        }

        //Dependencies
        if( !empty( $this->required_plugins ) ){

            $installed_plugins = get_plugins();     
            foreach( $this->required_plugins as $dependency ){ 
                //dependency installed?
                if( array_key_exists( $dependency['slug'], $installed_plugins ) ){
                    //dependency active?
                    if( is_plugin_active( $dependency['slug'] ) ){
                        //dependency version?
                        if( isset( $dependency['version'] ) ){
                            $dependency_current_version = $installed_plugins[$dependency['slug']]['Version'];
                            if( version_compare( $dependency_current_version, $dependency['version'], '<' ) ){
                                //wrong dependency version
                                self::$admin_notices[] = array( 'error' => sprintf( $this->errors['dependency_wrong_version'], $plugin['Name'], $dependency['name'], $dependency['version'] ) );
                                $return = false;
                            } 
                        }

                    } else { //dependency not active
                        self::$admin_notices[] = array( 'error' => sprintf( $this->errors['dependency_not_active'], $dependency['name'], $plugin['Name'] ) );
                        $return = false;
                    }

                } else { //dependency not installed
                    self::$admin_notices[] = array( 'error' => sprintf( $this->errors['dependency_not_installed'], $dependency['name'], $plugin['Name'] ) );
                    $return = false;
                }

            }

        }
        
        if( $return == false ){
            add_action( 'admin_notices', array( $this, 'display_admin_notices' ) );
            deactivate_plugins( __FILE__ ); //if anything wrong above, deactivate plugin
        }

        return $return;
    }
	
	/**
    * Display the requirement notice
	*
	* @since 1.0.0
    */
    public function display_admin_notices()
    {
        foreach(self::$admin_notices as $notice){
            echo '<div class="'.key($notice).'"><p>'.$notice[key($notice)].'</p></div>';
        }
		self::$admin_notices = array();
    }
	
	/**
    * Add extra plugin header info
	*
    * @param array $headers
    * @return array 
	*
	* @since 1.0.0
    */
    public function extra_plugin_headers($headers){
	
        $headers['Documentation URI'] = 'Documentation URI';
        return $headers;
		
    }
	
	/**
	 * Add action link to the plugins page.
	 *
	 * @since    1.0.0
	 */
	public function add_action_links( $links ) {

		$plugin_data = get_plugin_data( __FILE__ );
	
		return array_merge(
			array(
				'documentation' => '<a href="' . $plugin_data['Documentation URI'] . '">' . __( 'Documentation', $this->plugin_slug ) . '</a>'
			),
			$links
		);

	}	
	
	/**
	 * Customer schedules
	 *
	 * @since    1.0.0
	 */
	public function wmcs_cron_schedules( $schedules ){
	
		$schedules['wmcs_5min'] = array(
			'interval' => 60 * 5,
			'display' => __( 'Every 5 mins' )
		);
		
		$schedules['wmcs_10min'] = array(
			'interval' => 60 * 10,
			'display' => __( 'Every 10 mins' )
		);
		
		$schedules['wmcs_15min'] = array(
			'interval' => 60 * 15,
			'display' => __( 'Every 15 mins' )
		);
		
		$schedules['wmcs_30min'] = array(
			'interval' => 60 * 30,
			'display' => __( 'Every 30 mins' )
		);
		
		$schedules['wmcs_hour'] = array(
			'interval' => 60 * 60,
			'display' => __( 'Every hour' )
		);
		
		$schedules['wmcs_3hour'] = array(
			'interval' => 60 * 60 * 3,
			'display' => __( 'Every 3 hours' )
		);
		
		$schedules['wmcs_6hour'] = array(
			'interval' => 60 * 60 * 6,
			'display' => __( 'Every 6 hours' )
		);
		
		$schedules['wmcs_12hour'] = array(
			'interval' => 60 * 60 * 12,
			'display' => __( 'Every 12 hours' )
		);
		
		$schedules['wmcs_daily'] = array(
			'interval' => 60 * 60 * 24,
			'display' => __( 'Every Day' )
		);
	
		return $schedules;
	}
	
	/**
	 * Cron to check exchange rates using an API
	 *
	 * @since    1.0.0
	 */
	public function cron_check_exchange_rates() {
		
		include_once 'includes/class-wmcs-exchange-api.php';
		
		$exchange_rate_source = get_option( 'wmcs_exchange_rate_source', 'custom' );

		$api = new WMCS_Exchange_Api();
		$api->get_exchange_rates( $exchange_rate_source );
		
	}

} /* class Woocommerce_Multi_Currency_Store */

return new Woocommerce_Multi_Currency_Store();

} /* class_exists Woocommerce_Multi_Currency_Store */