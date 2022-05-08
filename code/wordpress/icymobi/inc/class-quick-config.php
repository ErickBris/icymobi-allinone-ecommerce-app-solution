<?php

class Inspius_Icymobi_Quick_Config{

	public function __construct(){
		$this->setup_config();
	}

	public function setup_config(){
		if($this->check_key()){
			$this->create_keys();
		}
	}

	private function check_key(){
		global $wpdb;
		$key_count = $wpdb->get_var( $wpdb->prepare( 
			"
				SELECT COUNT(*) 
				FROM {$wpdb->prefix}woocommerce_api_keys
				WHERE description = %s
			", 
			'ICYMOBI API'
		) );
		if($key_count>0)
			return false;
		return true;
	}


	private function create_keys() {
		global $wpdb;

		$description = 'ICYMOBI API';
		$user        = wp_get_current_user();

		// Created API keys.
		$permissions     = 'read_write';
		$consumer_key    = 'ck_' . wc_rand_hash();
		$consumer_secret = 'cs_' . wc_rand_hash();

		$wpdb->insert(
			$wpdb->prefix . 'woocommerce_api_keys',
			array(
				'user_id'         => $user->ID,
				'description'     => $description,
				'permissions'     => $permissions,
				'consumer_key'    => wc_api_hash( $consumer_key ),
				'consumer_secret' => $consumer_secret,
				'truncated_key'   => substr( $consumer_key, -7 )
			),
			array(
				'%d',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s'
			)
		);
		update_option( 'icymobi_api_tokens', array(
			'consumer_key' 		=> $consumer_key,
			'consumer_secret' 	=> $consumer_secret
		) );
	}

}
new Inspius_Icymobi_Quick_Config();