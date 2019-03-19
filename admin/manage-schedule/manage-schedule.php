<?php
/**
 * Add Manage Schedule Sub Menu in pro version
 *
 * @function wpsp_manage_schedule_menu in 'admin_menu' hook
 */
add_action('admin_menu', 'wpsp_manage_schedule_menu');
function wpsp_manage_schedule_menu() {
	$active_plugins = get_option( 'active_plugins' );
	$title = __( 'Pro Setting', 'wp-scheduled-posts' );
	foreach( $active_plugins as $plugin ) {
		if( $plugin === 'wp-scheduled-posts-pro/wp-scheduled-posts-pro.php' ) {
			$title = __( 'Manage Schedule', 'wp-scheduled-posts' );
		}
	}
	//add submenu page
	add_submenu_page(pluginsFOLDER, $title, $title, "manage_options", 'wpsp-manage-schedule', 'wpsp_scheduled_options');
}

/**
 * Manage Schedule callback
 *
 * @function wpsp_scheduled_options in 'add_submenu_page' hook
 */
function wpsp_scheduled_options() {
	do_action( 'wpsp_manage_schedule' );
}

/**
 * Add 'wpsp_manage_schedule' action hook for callback
 * 
 * @function wpsp_mansched_page_display
 */
add_action( 'wpsp_manage_schedule', 'wpsp_free_page_display' );
function wpsp_free_page_display(){
	include_once('pro-setting-page.php');
}