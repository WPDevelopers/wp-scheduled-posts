<?php
if (!function_exists('wpscp_get_options')) {
	function wpscp_get_options()
	{
		$wpscp_options = get_option('wpscp_options');
		return $wpscp_options;
	}
}

if (!function_exists('wpscp_permit_user')) {
	function wpscp_permit_user()
	{
		global $current_user;
		$wpscp_options = wpscp_get_options();

		if (!is_array($current_user->roles)) return false;
		if (!is_array($wpscp_options['allow_user_role'])) $wpscp_options['allow_user_role'] = array('administrator');

		foreach ($current_user->roles as $ur) {
			if (in_array($ur, $wpscp_options['allow_user_role'])) {
				return true;
				break;
			}
		}

		return false;
	}
}

if (!function_exists('wpscp_dropdown_roles')) {
	function wpscp_dropdown_roles($selected = array(), $skip_subscribe = false)
	{
		$p = '';
		$r = '';
		$editable_roles = get_editable_roles();
		if (is_array($editable_roles) && count($editable_roles) > 0) {
			foreach ($editable_roles as $role => $details) {
				if ($role == 'subscriber' && $skip_subscribe == true) {
					continue;
				}
				$name = translate_user_role($details['name']);
				if ($selected !== "" && is_array($selected) && in_array($role, $selected)) {
					$p .= "\n\t<option selected='selected' value='" . esc_attr($role) . "'>$name</option>";
				} else {
					$r .= "\n\t<option value='" . esc_attr($role) . "'>$name</option>";
				}
			}
		}

		return $p . $r;
	}
}

if (!function_exists('wpscp_options_page')) {
	function wpscp_options_page()
	{
		global $wpdb;
		$wpscp_options = wpscp_get_options();
?>
		<div class="wpsp-dashboard-body">
			<div class="wpsp_loader">
				<img src="<?php echo plugins_url('/wp-scheduled-posts/admin/assets/images/wpscp-logo.gif'); ?>" alt="Loader">
			</div>
			<?php
			//include topbar page
			include WPSCP_ADMIN_DIR_PATH . '/partials/topbar.php';
			//include license page
			include WPSCP_ADMIN_DIR_PATH . '/partials/license.php';
			// main option pages
			include WPSCP_ADMIN_DIR_PATH . '/partials/options.php';
			// social integations
			include WPSCP_ADMIN_DIR_PATH . 'partials/integrations-settings.php';
			// social templates
			include WPSCP_ADMIN_DIR_PATH . 'partials/social-settings.php';
			// pro setting will be show here
			do_action('wpscp_pro_options_settings');
			//manage schedule template
			do_action('wpsp_manage_schedule');
			?>
		</div>
<?php }
}
