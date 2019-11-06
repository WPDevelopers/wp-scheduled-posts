<?php
if(!class_exists('WpScp_Admin')){
	class WpScp_Admin{
		/**
		 * add plugin menu page and submenu pages 
		 */
		public function __construct() {
			$this->hooks();
			$this->load_dependency();
			$this->load_plugin_submenu_option_page();
			$this->load_widgets();
		}

		/**
		 * Load Admin require file
		 * @method load_dependency
		 * @since 3.0.1
		 */
		public function load_dependency(){
			include_once WPSCP_ADMIN_DIR_PATH . 'class-wpscp-calendar.php';
		}
		
		/**
		 * All Hooks Written Here
		 * @method hooks
		 * @since 1.0.0
		 * 
		 */
		public function hooks(){
			add_action( 'admin_menu', array($this, 'add_main_menu') );
			add_action( 'admin_menu', array($this, 'add_sub_menu') );
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
		 * Add Plugin Main Menu
		 * @method add_main_menu
		 * @since 1.0.0
		 */
		public function add_sub_menu()  {
			add_submenu_page( 'wp-scheduled-posts', 'Schedule Calendar', 'Schedule Calendar', 'manage_options', 'wp-scheduled-calendar', array($this, 'load_calendar_template'));
			// add calendar sub menu for allow calendar post type
			$this->add_sub_menu_for_calendar_supported_post_type();
		}

		public function add_sub_menu_for_calendar_supported_post_type() {
			$wpscp_all_options  = get_option('wpscp_options');
			$allow_post_types =  ($wpscp_all_options['allow_post_types'] == '' ? array('post') : $wpscp_all_options['allow_post_types']);
			foreach ($allow_post_types as $post_types) {
				$admin_menu_url = ($post_types != 'post' ? 'edit.php?post_type=' . $post_types : 'edit.php');
				add_submenu_page($admin_menu_url, __('Schedule Calendar', 'wpspcalendar'), __('Schedule Calendar', 'wpspcalendar'), 'edit_posts', 'wp-scheduled-calendar-'.$post_types, array($this, 'load_calendar_template'));
			}
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
		}
		
		/**
		 * Load Calendar Template
		 * @method load_calendar_template
		 * @since 3.0.1
		 */
		public function load_calendar_template(){
			include_once WPSCP_ADMIN_DIR_PATH . 'partials/calendar.php';
		}
	}
	new WpScp_Admin;
}