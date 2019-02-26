<?php


add_action('admin_menu', function(){
	add_submenu_page(pluginsFOLDER, __('Pro Setting'), __('Pro Setting'), "manage_options", 'wpsp-manage-schedule', 'wpsp_scheduled_options');
});


add_action( 'wpsp_manage_schedule', 'free_code' );


function wpsp_scheduled_options(){

	do_action( 'wpsp_manage_schedule' );

}

function free_code(){
	echo 'Hello World!';
}