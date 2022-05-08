<?php
/*
Plugin Name: IcyMobi
Plugin URI: http://icymobi.com/
Description: Basic plugin to connect your E-commerce mobile app and Woocommerce.
Author: Inspius
Version: 1.0
Author URI: http://inspius.com/
*/

//http://stackoverflow.com/questions/18382740/cors-not-working-php
if (isset($_SERVER['HTTP_ORIGIN'])) {
	header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
	header('Access-Control-Allow-Credentials: true');
	header('Access-Control-Max-Age: 86400'); // cache for 1 day
}

// Access-Control headers are received during OPTIONS requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {

	if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))
		header("Access-Control-Allow-Methods: GET, POST, OPTIONS");

	if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
		header("Access-Control-Allow-Headers:        {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");

	exit(0);
}

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'Inspius_Icymobi' ) ) :

	class Inspius_Icymobi {
		
		public function __construct() {

			$this->includes();

			do_action( 'is_icymobi_loaded' );

		}
		
		public function includes() {
			include_once( 'inc/class-option.php');
			include_once( 'inc/class-quick-config.php');
			include_once( 'inc/class-template.php' );

			include_once( 'api/api.php' );

			new Inspius_API();
		}

	}

	add_action( 'plugins_loaded', 'inspius_icymobi_loaded', 10 );
	function inspius_icymobi_loaded(){
		if(class_exists('WooCommerce')){
			new Inspius_Icymobi();
		}
	}

endif;