<?php
class optionAdminPanel{
	var $user_level = 'manage_options';
	
	function __construct() {

		add_action( 'admin_menu', array(&$this, 'add_menu') );
		add_action( 'admin_head', array(&$this, 'add_jax') );
	}

	function add_jax(){
		echo '<script type="text/javascript"> var ajax_url="'.admin_url("admin-ajax.php").'"';
		echo '</script>';
	}


	function add_menu()  {
		add_menu_page( __( 'WP Scheduled Post'), __( 'WP Scheduled Post' ), 'manage_options', pluginsFOLDER, 'wpscp_options_page', plugin_dir_url( __FILE__ ).'images/wp-post-scheduled.png',80 );
	}
	

	function show_menus() {
  		switch ($_GET['page']){
				
			case "cal" :
				include_once ( dirname (__FILE__) . '/editorial-calendar/edcal.php' );
				break;

			case "f_vs_p" :
				include_once ( dirname (__FILE__) . '/f_vs_p.php' );
				break;
					
		}
	}
}

?>