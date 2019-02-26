<?php

add_action('admin_menu', function(){
	$active_plugins = get_option( 'active_plugins' );
	$title = __( 'Pro Setting', 'wp-scheduled-posts' );
	foreach( $active_plugins as $plugin ) {
		if( $plugin === 'wp-scheduled-posts-pro/wp-scheduled-posts-pro.php' ) {
			$title = __( 'Manage Schedule', 'wp-scheduled-posts' );
		}
	}

	add_submenu_page(pluginsFOLDER, $title, $title, "manage_options", 'wpsp-manage-schedule', 'wpsp_scheduled_options');
});


add_action( 'wpsp_manage_schedule', 'free_code' );


function wpsp_scheduled_options(){

	do_action( 'wpsp_manage_schedule' );

}

function free_code(){
	echo 'Hello World!';
}