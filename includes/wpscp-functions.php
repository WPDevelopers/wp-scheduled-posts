<?php

/**
 * WP Scheduled Post Menu
 * @function wp_scheduled_post_menu
 * @since 1.0.0
 */

add_action('admin_bar_menu', 'wpscp_scheduled_post_menu', 1000);
if (!function_exists('wpscp_scheduled_post_menu')) {
	function wpscp_scheduled_post_menu()
	{
		global $wp_admin_bar;
		$wpscp_options = wpscp_get_options();
		if ($wpscp_options['show_in_adminbar'] || $wpscp_options['show_in_front_end_adminbar']) {
			if (is_admin() && !$wpscp_options['show_in_adminbar']) return;
			if (!is_admin() && !$wpscp_options['show_in_front_end_adminbar']) return;


			if (wpscp_permit_user()) {
				global $wpdb;
				$item_id = 0;
				$post_types = ($wpscp_options['allow_post_types'] !== "" ? $wpscp_options['allow_post_types'] : array('post'));
				$result = get_posts(array(
					'post_type' 		=> $post_types,
					'post_status' 		=> 'future',
					'posts_per_page' 	=> -1,
					'order'				=> 'ASC'
				));
				$totalPost = 0;
				if (is_array($result)) {
					$totalPost 	= count($result);
				}
				$wp_admin_bar->add_menu(
					array(
						'id' => 'wpscp',
						'title' => 'Scheduled Posts (' . $totalPost . ')'
					)
				);

				if (is_array($result)) {
					$totalPostAllowed = 0;
					$list_template = $wpscp_options['adminbar_item_template'];
					if ($list_template == '') {
						$list_template = "<strong>%TITLE%</strong> / %AUTHOR% / %DATE%";
					}
					$list_template = stripslashes($list_template);
					$title_length = intval($wpscp_options['adminbar_title_length']);
					if ($title_length == 0) {
						$title_length = 45;
					}
					$date_format = $wpscp_options['adminbar_date_format'];
					if ($date_format == '') {
						$date_format = 'M-d h:i:a';
					}

					$chunk = array_chunk($result, 8, true);

					$counter = 0;
					foreach ($chunk as $scposts) {

						foreach ($scposts as $scpost) {

							$wp_admin_bar->add_menu(
								array(
									'id' => 'wpscp_sub_' . $counter,
									'parent' => 'wpscp',
									'title' => 'Sub ' . $counter,
								)
							);

							// $title = substr($scpost->post_title, 0,$title_length);
							$title = $scpost->post_title;
							$author = get_the_author_meta('user_nicename', $scpost->post_author);
							$date = get_the_date($format = $date_format, $scpost->ID);

							$list_item_template	= str_replace("%TITLE%", $title, $list_template);
							$list_item_template	= str_replace("%AUTHOR%", $author, $list_item_template);
							$list_item_template = str_replace("%DATE%", $date, $list_item_template);
							$item_id++;

							$wp_admin_bar->add_menu(
								array(
									'id' => 'wpscp_sub_' . $counter . '_' . $item_id,
									'parent' => 'wpscp_sub_' . $counter,
									'title' => $list_item_template,
									'href' => get_edit_post_link($scpost->ID),
									'meta' => array('title' => $scpost->post_title)
								)
							);
						}

						$counter++;
						$totalPostAllowed++;
					}

					$item_id++;
?>

					<style>
						#wp-admin-bar-wpscp-default .wpsp_arrow_prev,
						#wp-admin-bar-wpscp-default .wpsp_arrow_next {
							background: url('<?php echo plugins_url(); ?>/wp-scheduled-posts/admin/assets/images/arrow.png') no-repeat;
							background-size: cover;

						}
					</style>

			<?php
					$Powered_by_text = '<div style="margin-top:5px; text-align:center;"><span class="wpsp_arrow_prev wpsp_arrow_pagi"></span>Powered By <span style="color:#fff"><a  style="padding:0;display:inline;" href="https://wpdeveloper.net/in/wpsp">WP Scheduled Posts</a></span>
					<span class="wpsp_arrow_next wpsp_arrow_pagi"></span>
					</div>';

					$wp_admin_bar->add_menu(
						array(
							'id' => 'wpscp_' . $item_id,
							'parent' => 'wpscp',
							'title' => $Powered_by_text,
							'meta' => array('title' => 'WPDeveloper', 'target' => '_blank')
						)
					);

					if ($totalPostAllowed != $totalPost) {
						#oevrwrite previous menu with new count
						$wp_admin_bar->add_menu(
							array(
								'id' => 'wpscp',
								'title' => 'Scheduled Posts (' . $totalPost . ')'
							)
						);
					}
				} //if(is_array($result))
			}
		}
	}
}


/**
 * Showing scheduled posts in homepage
 *
 * @function wp_scheduled_posts
 * @since 1.0.0
 */
if (!function_exists('wpscp_scheduled_posts')) {
	function wpscp_scheduled_posts()
	{
		global $wpdb;
		$wpscp_options 	= wpscp_get_options();
		$post_types 	= implode("', '", $wpscp_options['allow_post_types']);
		$post_types = "'" . $post_types . "'";
		$result 		= $wpdb->get_results("select * from " . $wpdb->prefix . "posts where post_status = 'future' AND post_type IN(" . $post_types . ") ORDER BY post_date ASC ");

		if (is_array($result)) {
			echo '<div class="scheduled_posts_box">';
			foreach ($result as $scpost) {
				echo '<div class="scheduled_post"><div>' . get_date_from_gmt($scpost->post_date_gmt, $format = 'Y-m-d H:i:s') . " | " . $scpost->post_title . '</div></div>';
				//echo $scpost->post_title;
			}
			echo '</div>';
		}
	}
}

/**
 * Settings Page Link in Plugin List Page
 *
 * @function wpscp_setting_links
 * @since 1.0.0
 */
if (!function_exists('wpscp_setting_links')) {
	function wpscp_setting_links($links, $file)
	{
		static $wpscp_setting;
		if (!$wpscp_setting) {
			$wpscp_setting = plugin_basename(__FILE__);
		}
		if ($file == $wpscp_setting) {
			$wpscp_settings_link = '<a href="options-general.php?page=' . WPSCP_PLUGIN_SLUG . '">Settings</a>';
			array_unshift($links, $wpscp_settings_link);
		}
		return $links;
	}
}
add_filter('plugin_action_links', 'wpscp_setting_links', 10, 2);

/**
 * Publish Post Immediately but with a future date
 *
 * @function wpscp_prevent_future_type
 * @since 1.0.0
 */
if (!function_exists('wpscp_prevent_future_type')) {
	function wpscp_prevent_future_type($post_data)
	{
		if (isset($_POST['prevent_future_post']) && $_POST['prevent_future_post'] == true) {
			if ($post_data['post_status'] == 'future') {
				$post_data['post_status'] = 'publish';
				remove_action('future_post', '_future_post_hook');
			}
		} else if (isset($_POST['wpscp-manual-schedule-date'])) {
			$post_data['post_date'] = $_POST['wpscp-manual-schedule-date'];
			$post_data['post_date_gmt'] = $_POST['wpscp-manual-schedule-date'];
		}
		return $post_data;
	}
}


/**
 * WPSP Post Page Prevent Future Option
 *
 * @function wpscp_post_page_prevent_future_option
 */
if (!function_exists('wpscp_prevent_future_post_markup')) {
	function wpscp_prevent_future_post_markup($postid)
	{
		global $post;
		$post_gmt_timestamp = strtotime($post->post_date_gmt);
		$current_gmt_timestamp = current_time('timestamp', $gmt = 1); #http://codex.wordpress.org/Function_Reference/current_time

		$status = get_post_status($postid);

		if ($status !== 'publish') {

			?>
			<div style="padding:10px;" id="prevent_future_post_box">
				<input type="checkbox" name="prevent_future_post" value="yes" id="prevent_future_post_no" <?php echo ($post_gmt_timestamp > $current_gmt_timestamp && $post->post_status != 'future') ? ' checked="checked"' : ''; ?> />
				<label for="prevent_future_post_no"> <?php esc_html_e('Publish future post immediately', 'wp-scheduled-posts'); ?></label>
				<a id="wpscp-future-post-help-handler" href="javascript:void();" title="Show/Hide Help"><?php print esc_html('(?)'); ?></a>
				<div style="border:1px solid #FFEBE8; background:#FEFFE8; padding:5px; display:none;" id="wpscp-future-post-help-info">
					<?php esc_html_e('If you schedule this post and check this option then your post will be published immediately but post date-time will not set current date. Post date-time will be your scheduled future date-time.', 'wp-scheduled-posts'); ?>
				</div>
			</div>
	<?php
		}
	}
}

/**
 * WPSP Initialization an option in post edit page
 *
 * @function wpscp_initialize
 */
if (!function_exists('wpscp_submit_box_future_post')) {
	function wpscp_submit_box_future_post()
	{
		$wpscp_options = wpscp_get_options();
		add_filter('wp_insert_post_data', 'wpscp_prevent_future_type');
		if (isset($_GET['action']) && $_GET['action'] == 'edit' && $wpscp_options['prevent_future_post'] == true) {
			add_action('post_submitbox_misc_actions', 'wpscp_prevent_future_post_markup');
		}
	}
}
add_action('init', 'wpscp_submit_box_future_post');

/**
 * WP Head hook
 *
 * @since 3.0.0
 */
add_action('wp_head', 'wpsp_frontend_head');
function wpsp_frontend_head()
{
	?>
	<style>
		.wpsp_arrow_pagi {
			cursor: pointer;
		}
	</style>

	<?php
}

/**
 * Optional usage tracker
 *
 * @since v2.0.0
 */
if (!function_exists('wpscp_scheduled_posts_start_plugin_tracking')) {
	function wpscp_scheduled_posts_start_plugin_tracking()
	{
		$wisdom = new wpScp_Plugin_Usage_Tracker(
			WPSP_PLUGIN_FILE,
			'http://app.wpdeveloper.net',
			array(),
			true,
			true,
			1
		);
	}
	wpscp_scheduled_posts_start_plugin_tracking();
}

/**
 * Get All Author User
 */
if (!function_exists('wpscp_dropdown_all_user_name')) {
	function wpscp_dropdown_all_user_name($selected = array())
	{
		$p = '';
		$r = '';
		global $wpdb;
		$all_users = get_users(array('fields' => array('user_login')));
		if (is_array($all_users) && count($all_users) > 0) {
			foreach ($all_users as $user) {
				if ($selected !== "" && is_array($selected) && in_array($user->user_login, $selected)) {
					$p .= "\n\t<option selected='selected' value='" . esc_attr($user->user_login) . "'>$user->user_login</option>";
				} else {
					$r .= "\n\t<option value='" . esc_attr($user->user_login) . "'>$user->user_login</option>";
				}
			}
		}

		return $p . $r;
	}
}


if (!function_exists('wpscp_dropdown_all_user_email')) {
	function wpscp_dropdown_all_user_email($selected = array())
	{
		$p = '';
		$r = '';
		global $wpdb;
		$all_users = get_users(array('fields' => array('user_email')));
		if (is_array($all_users) && count($all_users) > 0) {
			foreach ($all_users as $user) {
				if ($selected !== "" && is_array($selected) && in_array($user->user_email, $selected)) {
					$p .= "\n\t<option selected='selected' value='" . esc_attr($user->user_email) . "'>$user->user_email</option>";
				} else {
					$r .= "\n\t<option value='" . esc_attr($user->user_email) . "'>$user->user_email</option>";
				}
			}
		}

		return $p . $r;
	}
}

/**
 * Email Notify review Email List
 * @return array
 */
if (!function_exists('wpscp_email_notify_review_email_list')) {
	function wpscp_email_notify_review_email_list()
	{
		global $wpdb;
		$email = '';
		// collect email from role
		$roles = get_option('wpscp_notify_author_role_sent_review');
		if (!empty($roles)) {
			$email = wp_list_pluck(get_users(array(
				'fields' 	=> array('user_email'),
				'role__in'	=> $roles
			)), 'user_email');
		}
		// collect email from email fields
		$meta_email = array_values(get_option('wpscp_notify_author_email_sent_review'));
		if (!empty($meta_email)) {
			$email = array_merge($email, $meta_email);
		}
		// get email from username
		$meta_username = get_option('wpscp_notify_author_username_sent_review');
		if (!empty($meta_username)) {
			$email = array_merge($email, wp_list_pluck(get_users(array(
				'fields' 	=> array('user_email'),
				'login__in'	=> $meta_username
			)), 'user_email'));
		}
		return array_unique($email);
	}
}
/**
 * Email Notify for schedule Email List
 * @return array
 */
if (!function_exists('wpscp_email_notify_schedule_email_list')) {
	function wpscp_email_notify_schedule_email_list()
	{
		global $wpdb;
		$email = '';
		// collect email from role
		$roles = get_option('wpscp_notify_author_post_schedule_role');
		if (!empty($roles)) {
			$email = wp_list_pluck(get_users(array(
				'fields' 	=> array('user_email'),
				'role__in'	=> $roles
			)), 'user_email');
		}
		// collect email from email fields
		$meta_email = array_values(get_option('wpscp_notify_author_post_schedule_email'));
		if (!empty($meta_email)) {
			$email = array_merge($email, $meta_email);
		}
		// get email from username
		$meta_username = get_option('wpscp_notify_author_post_schedule_username');
		if (!empty($meta_username)) {
			$email = array_merge($email, wp_list_pluck(get_users(array(
				'fields' 	=> array('user_email'),
				'login__in'	=> $meta_username
			)), 'user_email'));
		}
		return array_unique($email);
	}
}

/**
 * Check Supported Post type for admin page and plugin main settings page
 * 
 * @return bool
 * @version 3.1.12
 */
if (!function_exists('wpscp_is_supported_plugin_page_hook_suffix')) {
	function wpscp_is_supported_plugin_page_hook_suffix($current_post_type, $hook)
	{
		$wpscp_options = wpscp_get_options();
		$post_types = wpscp_get_all_post_type();
		$allow_post_types = ($wpscp_options['allow_post_types'] == '' ? array('post') : $wpscp_options['allow_post_types']);
		if (
			in_array($current_post_type, $allow_post_types) ||
			$hook == 'posts_page_wp-scheduled-calendar-post' ||
			$hook == 'toplevel_page_wp-scheduled-posts' ||
			$hook == 'admin_page_wpscp-quick-setup-wizard' ||
			$hook == 'scheduled-posts_page_wp-scheduled-calendar'
		) {
			return true;
		}
		return false;
	}
}


add_action('wpscp_social_profile_template_list_view', 'wpscp_social_profile_facebook_template_markup');
function wpscp_social_profile_facebook_template_markup($arg)
{
	if ($arg['social_profile'] == 'facebook') {
		$facebookStatus = $arg['status'];
		$facebookSocialProfile = get_option(WPSCP_FACEBOOK_OPTION_NAME);
		if (is_array($facebookSocialProfile)) {
	?>
			<div class="wpscp-social-tab__item-list__single_item<?php echo ($facebookStatus != 'on' ? ' disable' : ''); ?>" data-type="facebook" data-item="0" data-option_name="<?php print WPSCP_FACEBOOK_OPTION_NAME; ?>">
				<div class="entry-thumbnail">
					<img src="<?php print(isset($facebookSocialProfile[0]['thumbnail_url']) ? $facebookSocialProfile[0]['thumbnail_url'] : 'http://0.gravatar.com/avatar/64e1b8d34f425d19e1ee2ea7236d3028?s=96&d=mm&r=g'); ?>" alt="<?php _e('icon', 'wp-scheduled-posts-pro'); ?>">
				</div>
				<div class="entry-content">
					<h4 class="entry-content__title">
						<?php
						if (isset($facebookSocialProfile[0]['name']) && $facebookSocialProfile[0]['name'] != "") {
							echo $facebookSocialProfile[0]['name'];
						}
						?>
					</h4>
					<p class="entry-content__doc">
						<?php esc_html_e('Added by', 'wp-scheduled-posts-pro'); ?>
						<strong>
							<?php print(isset($facebookSocialProfile[0]['added_by']) ? $facebookSocialProfile[0]['added_by'] : ''); ?>
						</strong>
						<?php
						_e('on ', 'wp-scheduled-posts-pro');
						print(isset($facebookSocialProfile[0]['added_date']) ? $facebookSocialProfile[0]['added_date'] : '');
						?>
					</p>
				</div>
				<div class="entry-control">
					<div class="checkbox-toggle">
						<form method="post">
							<input type="checkbox" class="wpsp_field_activate" <?php checked(boolval($facebookSocialProfile[0]['status']), 1) ?>>
							<svg class="is_checked" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 426.67 426.67">
								<path d="M153.504 366.84c-8.657 0-17.323-3.303-23.927-9.912L9.914 237.265c-13.218-13.218-13.218-34.645 0-47.863 13.218-13.218 34.645-13.218 47.863 0l95.727 95.727 215.39-215.387c13.218-13.214 34.65-13.218 47.86 0 13.22 13.218 13.22 34.65 0 47.863L177.435 356.928c-6.61 6.605-15.27 9.91-23.932 9.91z" />
							</svg>
							<svg class="is_unchecked" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 212.982 212.982">
								<path d="M131.804 106.49l75.936-75.935c6.99-6.99 6.99-18.323 0-25.312-6.99-6.99-18.322-6.99-25.312 0L106.49 81.18 30.555 5.242c-6.99-6.99-18.322-6.99-25.312 0-6.99 6.99-6.99 18.323 0 25.312L81.18 106.49 5.24 182.427c-6.99 6.99-6.99 18.323 0 25.312 6.99 6.99 18.322 6.99 25.312 0L106.49 131.8l75.938 75.937c6.99 6.99 18.322 6.99 25.312 0 6.99-6.99 6.99-18.323 0-25.313l-75.936-75.936z" fill-rule="evenodd" clip-rule="evenodd" />
							</svg>
						</form>
					</div>
					<div class="entry-control__more-link">
						<button class="btn-more-link"><img src="<?php print plugin_dir_url(__FILE__) . './../assets/images/icon-more.png'; ?>" alt="<?php _e('more item', 'wp-scheduled-posts'); ?>"></button>
						<ul class="entry-control__more-link__group_absolute">
							<li>
								<button class="btn btn-refresh"><?php _e('Refresh', 'wp-scheduled-posts-pro'); ?></button>
								<button class="btn btn-remove"><?php _e('Remove', 'wp-scheduled-posts-pro'); ?></button>
							</li>
						</ul>
					</div>
				</div>
			</div>
		<?php
		}
	}
}

add_action('wpscp_social_profile_template_list_view', 'wpscp_social_profile_twitter_template_markup');
function wpscp_social_profile_twitter_template_markup($arg)
{
	if ($arg['social_profile'] == 'twitter') {
		$twitterStatus = $arg['status'];
		$twitterSocialProfile = get_option(WPSCP_TWITTER_OPTION_NAME);
		if (is_array($twitterSocialProfile)) {
		?>
			<div class="wpscp-social-tab__item-list__single_item<?php echo ($twitterStatus != 'on' ? ' disable' : ''); ?>" data-type="twitter" data-item="0" data-option_name="<?php print WPSCP_TWITTER_OPTION_NAME; ?>">
				<div class="entry-thumbnail">
					<img src="<?php print(isset($twitterSocialProfile[0]['thumbnail_url']) ? $twitterSocialProfile[0]['thumbnail_url'] : 'http://0.gravatar.com/avatar/64e1b8d34f425d19e1ee2ea7236d3028?s=96&d=mm&r=g'); ?>" alt="<?php _e('icon', 'wp-scheduled-posts-pro'); ?>">
				</div>
				<div class="entry-content">
					<h4 class="entry-content__title">
						<?php
						if (isset($twitterSocialProfile[0]['name']) && $twitterSocialProfile[0]['name'] != "") {
							echo $twitterSocialProfile[0]['name'];
						}
						?>
					</h4>
					<p class="entry-content__doc">
						<?php esc_html_e('Added by', 'wp-scheduled-posts-pro'); ?>
						<strong>
							<?php print(isset($twitterSocialProfile[0]['added_by']) ? $twitterSocialProfile[0]['added_by'] : ''); ?>
						</strong>
						<?php
						_e('on ', 'wp-scheduled-posts-pro');
						print(isset($twitterSocialProfile[0]['added_date']) ? $twitterSocialProfile[0]['added_date'] : '');
						?>
					</p>
				</div>
				<div class="entry-control">
					<div class="checkbox-toggle">
						<form method="post">
							<input type="checkbox" class="wpsp_field_activate" <?php checked(boolval($twitterSocialProfile[0]['status']), 1) ?>>
							<svg class="is_checked" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 426.67 426.67">
								<path d="M153.504 366.84c-8.657 0-17.323-3.303-23.927-9.912L9.914 237.265c-13.218-13.218-13.218-34.645 0-47.863 13.218-13.218 34.645-13.218 47.863 0l95.727 95.727 215.39-215.387c13.218-13.214 34.65-13.218 47.86 0 13.22 13.218 13.22 34.65 0 47.863L177.435 356.928c-6.61 6.605-15.27 9.91-23.932 9.91z" />
							</svg>
							<svg class="is_unchecked" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 212.982 212.982">
								<path d="M131.804 106.49l75.936-75.935c6.99-6.99 6.99-18.323 0-25.312-6.99-6.99-18.322-6.99-25.312 0L106.49 81.18 30.555 5.242c-6.99-6.99-18.322-6.99-25.312 0-6.99 6.99-6.99 18.323 0 25.312L81.18 106.49 5.24 182.427c-6.99 6.99-6.99 18.323 0 25.312 6.99 6.99 18.322 6.99 25.312 0L106.49 131.8l75.938 75.937c6.99 6.99 18.322 6.99 25.312 0 6.99-6.99 6.99-18.323 0-25.313l-75.936-75.936z" fill-rule="evenodd" clip-rule="evenodd" />
							</svg>
						</form>
					</div>
					<div class="entry-control__more-link">
						<button class="btn-more-link"><img src="<?php print plugin_dir_url(__FILE__) . './../assets/images/icon-more.png'; ?>" alt="<?php _e('more item', 'wp-scheduled-posts'); ?>"></button>
						<ul class="entry-control__more-link__group_absolute">
							<li>
								<button class="btn btn-refresh"><?php _e('Refresh', 'wp-scheduled-posts-pro'); ?></button>
								<button class="btn btn-remove"><?php _e('Remove', 'wp-scheduled-posts-pro'); ?></button>
							</li>
						</ul>
					</div>
				</div>
			</div>
		<?php
		}
	}
}

add_action('wpscp_social_profile_template_list_view', 'wpscp_social_profile_linkedin_template_markup');
function wpscp_social_profile_linkedin_template_markup($arg)
{
	if ($arg['social_profile'] == 'linkedin') {
		$linkedinStatus = $arg['status'];
		$linkedinSocialProfile = get_option(WPSCP_LINKEDIN_OPTION_NAME);
		if (is_array($linkedinSocialProfile)) {
		?>
			<div class="wpscp-social-tab__item-list__single_item<?php echo ($linkedinStatus != 'on' ? ' disable' : ''); ?>" data-type="linkedin" data-item="0" data-option_name="<?php print WPSCP_LINKEDIN_OPTION_NAME; ?>">
				<div class="entry-thumbnail">
					<img src="<?php print(isset($linkedinSocialProfile[0]['thumbnail_url']) ? $linkedinSocialProfile[0]['thumbnail_url'] : 'http://0.gravatar.com/avatar/64e1b8d34f425d19e1ee2ea7236d3028?s=96&d=mm&r=g'); ?>" alt="<?php _e('icon', 'wp-scheduled-posts-pro'); ?>">
				</div>
				<div class="entry-content">
					<h4 class="entry-content__title">
						<?php
						if (isset($linkedinSocialProfile[0]['name']) && $linkedinSocialProfile[0]['name'] != "") {
							echo $linkedinSocialProfile[0]['name'];
						}
						?>
					</h4>
					<p class="entry-content__doc">
						<?php esc_html_e('Added by', 'wp-scheduled-posts-pro'); ?>
						<strong>
							<?php print(isset($linkedinSocialProfile[0]['added_by']) ? $linkedinSocialProfile[0]['added_by'] : ''); ?>
						</strong>
						<?php
						_e('on ', 'wp-scheduled-posts-pro');
						print(isset($linkedinSocialProfile[0]['added_date']) ? $linkedinSocialProfile[0]['added_date'] : '');
						?>
					</p>
				</div>
				<div class="entry-control">
					<div class="checkbox-toggle">
						<form method="post">
							<input type="checkbox" class="wpsp_field_activate" <?php checked(boolval($linkedinSocialProfile[0]['status']), 1) ?>>
							<svg class="is_checked" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 426.67 426.67">
								<path d="M153.504 366.84c-8.657 0-17.323-3.303-23.927-9.912L9.914 237.265c-13.218-13.218-13.218-34.645 0-47.863 13.218-13.218 34.645-13.218 47.863 0l95.727 95.727 215.39-215.387c13.218-13.214 34.65-13.218 47.86 0 13.22 13.218 13.22 34.65 0 47.863L177.435 356.928c-6.61 6.605-15.27 9.91-23.932 9.91z" />
							</svg>
							<svg class="is_unchecked" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 212.982 212.982">
								<path d="M131.804 106.49l75.936-75.935c6.99-6.99 6.99-18.323 0-25.312-6.99-6.99-18.322-6.99-25.312 0L106.49 81.18 30.555 5.242c-6.99-6.99-18.322-6.99-25.312 0-6.99 6.99-6.99 18.323 0 25.312L81.18 106.49 5.24 182.427c-6.99 6.99-6.99 18.323 0 25.312 6.99 6.99 18.322 6.99 25.312 0L106.49 131.8l75.938 75.937c6.99 6.99 18.322 6.99 25.312 0 6.99-6.99 6.99-18.323 0-25.313l-75.936-75.936z" fill-rule="evenodd" clip-rule="evenodd" />
							</svg>
						</form>
					</div>
					<div class="entry-control__more-link">
						<button class="btn-more-link"><img src="<?php print plugin_dir_url(__FILE__) . './../assets/images/icon-more.png'; ?>" alt="<?php _e('more item', 'wp-scheduled-posts'); ?>"></button>
						<ul class="entry-control__more-link__group_absolute">
							<li>
								<!-- <button class="btn btn-refresh"><?php // _e('Refresh', 'wp-scheduled-posts-pro'); 
																		?></button> -->
								<button class="btn btn-remove"><?php _e('Remove', 'wp-scheduled-posts-pro'); ?></button>
							</li>
						</ul>
					</div>
				</div>
			</div>
		<?php

		}
	}
}

add_action('wpscp_social_profile_template_list_view', 'wpscp_social_profile_pinterest_template_markup');
function wpscp_social_profile_pinterest_template_markup($arg)
{
	if ($arg['social_profile'] == 'pinterest') {
		$pinterestStatus = $arg['status'];
		$pinterestSocialProfile = get_option(WPSCP_PINTEREST_OPTION_NAME);
		if (is_array($pinterestSocialProfile)) {
		?>
			<div class="wpscp-social-tab__item-list__single_item<?php echo ($pinterestStatus != 'on' ? ' disable' : ''); ?>" data-type="pinterest" data-item="0" data-option_name="<?php print WPSCP_PINTEREST_OPTION_NAME; ?>">
				<div class="entry-thumbnail">
					<img src="<?php print(isset($pinterestSocialProfile[0]['thumbnail_url']) ? $pinterestSocialProfile[0]['thumbnail_url'] : 'http://0.gravatar.com/avatar/64e1b8d34f425d19e1ee2ea7236d3028?s=96&d=mm&r=g'); ?>" alt="<?php _e('icon', 'wp-scheduled-posts-pro'); ?>">
				</div>
				<div class="entry-content">
					<h4 class="entry-content__title">
						<?php
						if (isset($pinterestSocialProfile[0]['name']) && $pinterestSocialProfile[0]['name'] != "") {
							echo $pinterestSocialProfile[0]['name'];
						}
						?>
					</h4>
					<p class="entry-content__doc">
						<?php esc_html_e('Added by', 'wp-scheduled-posts-pro'); ?>
						<strong>
							<?php print(isset($pinterestSocialProfile[0]['added_by']) ? $pinterestSocialProfile[0]['added_by'] : ''); ?>
						</strong>
						<?php
						_e('on ', 'wp-scheduled-posts-pro');
						print(isset($pinterestSocialProfile[0]['added_date']) ? $pinterestSocialProfile[0]['added_date'] : '');
						?>
						<br />
						<?php
						print(isset($pinterestSocialProfile[0]['default_board_name']) ? "<strong>" . __('Default Board: ', 'wp-scheduled-posts-pro') . "</strong>" . $pinterestSocialProfile[0]['default_board_name'] : '');
						?>
					</p>
				</div>
				<div class="entry-control">
					<div class="checkbox-toggle">
						<form method="post">
							<input type="checkbox" class="wpsp_field_activate" <?php checked(boolval($pinterestSocialProfile[0]['status']), 1) ?>>
							<svg class="is_checked" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 426.67 426.67">
								<path d="M153.504 366.84c-8.657 0-17.323-3.303-23.927-9.912L9.914 237.265c-13.218-13.218-13.218-34.645 0-47.863 13.218-13.218 34.645-13.218 47.863 0l95.727 95.727 215.39-215.387c13.218-13.214 34.65-13.218 47.86 0 13.22 13.218 13.22 34.65 0 47.863L177.435 356.928c-6.61 6.605-15.27 9.91-23.932 9.91z" />
							</svg>
							<svg class="is_unchecked" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 212.982 212.982">
								<path d="M131.804 106.49l75.936-75.935c6.99-6.99 6.99-18.323 0-25.312-6.99-6.99-18.322-6.99-25.312 0L106.49 81.18 30.555 5.242c-6.99-6.99-18.322-6.99-25.312 0-6.99 6.99-6.99 18.323 0 25.312L81.18 106.49 5.24 182.427c-6.99 6.99-6.99 18.323 0 25.312 6.99 6.99 18.322 6.99 25.312 0L106.49 131.8l75.938 75.937c6.99 6.99 18.322 6.99 25.312 0 6.99-6.99 6.99-18.323 0-25.313l-75.936-75.936z" fill-rule="evenodd" clip-rule="evenodd" />
							</svg>
						</form>
					</div>
					<div class="entry-control__more-link">
						<button class="btn-more-link"><img src="<?php print plugin_dir_url(__FILE__) . './../assets/images/icon-more.png'; ?>" alt="<?php _e('more item', 'wp-scheduled-posts'); ?>"></button>
						<ul class="entry-control__more-link__group_absolute">
							<li>
								<!-- <button class="btn btn-refresh"><?php //_e('Refresh', 'wp-scheduled-posts-pro'); 
																		?></button> -->
								<button class="btn btn-remove"><?php _e('Remove', 'wp-scheduled-posts-pro'); ?></button>
							</li>
						</ul>
					</div>
				</div>
			</div>
<?php

		}
	}
}
