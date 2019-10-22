<?php 
function get_all_user_role(){
    global $wp_roles;
	$roles = $wp_roles->roles;
	return $roles;
}
// echo '<pre>';
// var_dump(get_all_user_role());
// exit;