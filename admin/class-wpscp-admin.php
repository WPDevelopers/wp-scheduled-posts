<?php
class WpScp_Admin{
	/**
	 * add plugin menu page and submenu pages 
	 */
	public function __construct() {
		$this->hooks();
		$this->load_plugin_submenu_option_page();
		$this->load_widgets();
	}
	
	/**
	 * All Hooks Written Here
	 * @method hooks
	 * @since 1.0.0
	 * 
	 */
	public function hooks(){
		add_action( 'admin_menu', array($this, 'add_main_menu') );
		add_action( 'admin_init', array($this, 'remove_submenu_wp_scheduled_posts') );
	}
	/**
	 * Add Plugin Main Menu
	 * @method add_main_menu
	 * @since 1.0.0
	 */
	public function add_main_menu()  {
		add_menu_page( __( 'Scheduled Posts'), __( 'Scheduled Posts' ), 'manage_options', pluginsFOLDER, 'wpscp_options_page', plugin_dir_url( __FILE__ ).'assets/images/wpsp-icon.png', 80 );
	}
	/**
	 * Load All Widgets
	 * @method load_widgets
	 * @since 2.3.1
	 */
	public function load_widgets(){
		include_once WPSCP_ADMIN_DIR_PATH . 'class-wpscp-widget.php';
	}
	/**
	 * Remove Sub Menu From admin Setting Option
	 * @method remove_submenu_wp_scheduled_posts
	 * @since 1.0.0
	 */
	public function remove_submenu_wp_scheduled_posts() {
        remove_submenu_page( 'options-general.php', 'wp-scheduled-posts' );
	}
	/**
     * Load Plugin Option pages
     * @method load_plugin_option
     * @since 2.3.1
     */
    public function load_plugin_submenu_option_page(){
		include_once WPSCP_ADMIN_DIR_PATH . 'wpscp-options.php';
        include_once WPSCP_ADMIN_DIR_PATH . 'manage-schedule/manage-schedule.php';
    }
}
new WpScp_Admin;