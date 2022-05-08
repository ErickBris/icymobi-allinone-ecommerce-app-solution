<?php

class Inspius_Icymobi_Template extends Inspius_Icymobi_Option{

	public function __construct(){
		$this->init_hooks();
	}

	public function init_hooks(){
		add_action("admin_menu", 				array($this, 'add_theme_menu_item'));

		// Register style and script
		add_action('admin_enqueue_scripts', 	array($this, 'register_scripts'));

		// Ajax Option
		add_action('wp_ajax_icymobi_save_option', 	array($this, 'save_data'));
		
		// Tabs
		add_action('icymobi_template_tab_content', 	array($this, 'render_general_page'), 10);
		add_action('icymobi_template_tab_content', 	array($this, 'render_contact_page'), 10);


		// Section Add Ons
		if ( false === ( $products = get_transient( 'icymobi_addons_sections' ) ) ) {
			$products = wp_remote_get('http://store.inspius.com/is-api/add-ons/');
			$products = json_decode($products['body']);
			
			if($products){
				set_transient( 'icymobi_addons_sections', $products, DAY_IN_SECONDS );
			}
		}
		if(count($products)>0){
			add_action('icymobi_template_tab_content', 	array($this, 'render_add_ons_page'), 10);
			add_action('icymobi_option_tab_title', function(){
				echo '<a href="#icymobi-add-ons" class="nav-tab">Add-ons</a>';
			}, 100);
		}


		// Help Tab
		add_action('icymobi_action_add_help_tab', array($this, 'add_tab_general'), 10, 1);
		
		//Admin footer text
		add_filter( 'admin_footer_text', array( $this, 'admin_footer_text' ), 1 );
		
	}

	

	public function save_data(){
		$data = $_POST['data'];
		$data = json_decode( stripslashes( $data ) );
		$option = array();

		foreach ($data as $key => $value) {
			$option[$value->name] = $value->value;
		}
		update_option( 'icymobi_config_option', $option );
		die;
	}

	public function register_scripts(){
		wp_enqueue_style( 'is-icymobi-style', plugins_url( 'icymobi/assets/css/style.css' ) );
		wp_enqueue_script( 'is-icymobi-map-script', '//maps.googleapis.com/maps/api/js?sensor=false&libraries=places&key=AIzaSyDgLR9YJJ9R46x1thdl8YS5QLSyTAUR7q8', array(), false, true);
		wp_enqueue_script( 'is-icymobi-script', plugins_url( 'icymobi/assets/js/main.js' ), array(), false, true );
	}

	public function theme_settings_page(){
		?>
		    <div class="wrap is-icymobi-wrap">
	            <h2 class="title">IcyMobi Configuration</h2>
	            
	            <div class="message"></div>

	            <h2 class="nav-tab-wrapper">
	                <a href="#icymobi-general" class="nav-tab nav-tab-active">General - c - o d - e l - i s-t .c c</a>
	                <a href="#icymobi-contact-config" class="nav-tab" >Contact Page</a>
	                <?php do_action('icymobi_option_tab_title'); ?>
	                
	            </h2>
	            <form id="icymobi_theme_options">

	            	<?php do_action('icymobi_template_tab_content'); ?>

		           	<div class="submit">
		           		<button class="button button-primary" id="icymobi_save_option" type="button">Save changes</button>
		           		
		           	</div>

		           	<div class="icon-loading">
						<div class='uil-squares-css' style='transform:scale(0.4);'><div><div></div></div><div><div></div></div><div><div></div></div><div><div></div></div><div><div></div></div><div><div></div></div><div><div></div></div><div><div></div></div></div>
		           	</div>
	        	</form>
	        </div>
		<?php
	}

	public function add_theme_menu_item(){
		global $icymobi_help_option;
		$icymobi_help_option = add_menu_page("IcyMobi", "IcyMobi", "manage_options", "icymobi-config", array($this, 'theme_settings_page'), plugins_url( 'icymobi/assets/images/logo.png' ), 30);
		add_action('load-'.$icymobi_help_option, array($this, 'add_help_tab'));
	}

	public function add_help_tab(){
		global $icymobi_help_option;
	    $screen = get_current_screen();

	    if ( $screen->id != $icymobi_help_option )
	        return;

	    do_action('icymobi_action_add_help_tab', $screen);
	}

	public function add_tab_general($screen){
		$screen->add_help_tab( array(
	        'id'	=> 'icymobi_general_tab',
	        'title'	=> __('General'),
	        'content'	=> "<h2><a href=''>IcyMobi</a> – General Settings</h2>".
						   '<iframe width="560" height="315" src="https://www.youtube.com/embed/MHxdFvg_MsE" frameborder="0" allowfullscreen></iframe>'
			,

	    ) );
		$screen->add_help_tab( array(
	        'id'	=> 'icymobi_contact_tab',
	        'title'	=> __('Contact Page'),
	        'content'	=> "<h2><a href=''>IcyMobi</a> – Contact Page Settings</h2>".
						   '<iframe width="560" height="315" src="https://www.youtube.com/embed/pSzMR3QJ0ko" frameborder="0" allowfullscreen></iframe>'
			,

	    ) );


	    $screen->set_help_sidebar(
			'<p><strong>For more information:</strong></p>' .
			'<p><a href="http://icymobi.com/about-us" target="_blank">About IcyMobi</a></p>'.
			'<p><a href="http://store.inspius.com/icymobi" target="_blank">Official Plugins</a></p>'.
			'<p><a href="http://icymobi.com/help" target="_blank">Help & Support</a></p>'.
			'<p><a href="http://icymobi.com/found-a-bug" target="_blank">Found a bug?</a></p>'
		);
	}

	public function render_add_ons_page(){
		include_once('templates/add-ons.php');
	}

	public function render_general_page(){
		include_once('templates/general.php');
	}

	public function render_contact_page(){
		include_once('templates/contact.php');
	}
	
	/**
	 * Change the admin footer text on WooCommerce admin pages.
	 *
	 * @since  2.3
	 * @param  string $footer_text
	 * @return string
	 */
	public function admin_footer_text( $footer_text ) {
		$footer_text = __( 'Thank you for selling with IcyMobi.', 'icymobi' );

		return $footer_text;
	}

	
}

if(is_admin()){
	new Inspius_Icymobi_Template();
}