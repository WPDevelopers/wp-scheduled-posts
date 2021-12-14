<?php


/**
 * Getting First item from array
 * written as polyfill
 * @since 3.3.0
 */
if (!function_exists('array_key_first')) {
	function array_key_first(array $arr)
	{
		foreach ($arr as $key => $unused) {
			return $key;
		}
		return NULL;
	}
}

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
		$is_show_admin_bar_posts = \WPSP\Helper::get_settings('is_show_admin_bar_posts');
		$is_show_sitewide_bar_posts = \WPSP\Helper::get_settings('is_show_sitewide_bar_posts');
		$allow_post_types = \WPSP\Helper::get_settings('allow_post_types');
		$adminbar_list_structure_template = \WPSP\Helper::get_settings('adminbar_list_structure_template');
		$adminbar_list_structure_title_length = \WPSP\Helper::get_settings('adminbar_list_structure_title_length');
		$adminbar_list_structure_date_format = \WPSP\Helper::get_settings('adminbar_list_structure_date_format');

		if ($is_show_admin_bar_posts || $is_show_sitewide_bar_posts) {
			if (is_admin() && !$is_show_admin_bar_posts) return;
			if (!is_admin() && !$is_show_sitewide_bar_posts) return;
			if (WPSP\Helper::is_user_allow()) {
				global $wpdb;
				$item_id = 0;
				$post_types = (!empty($allow_post_types) ? $allow_post_types : array('post'));
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
					$list_template = $adminbar_list_structure_template;
					if ($list_template == '') {
						$list_template = "<strong>%TITLE%</strong> / %AUTHOR% / %DATE%";
					}
					$list_template = stripslashes($list_template);
					$title_length = intval($adminbar_list_structure_title_length);
					if ($title_length == 0) {
						$title_length = 45;
					}
					$date_format = $adminbar_list_structure_date_format;
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
			<?php
					$Powered_by_text = '<div style="margin-top:5px; text-align:center;"><span class="wpsp_arrow_prev wpsp_arrow_pagi"></span>Powered By <span style="color:#fff"><a  style="padding:0;display:inline;" href="https://wpdeveloper.com/in/schedulepress">SchedulePress</a></span>
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
				}
			}
		}
	}
}




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
				if(isset($_POST['date_type']) && $_POST['date_type'] == 'current'){
					$post_data['post_date'] = current_time( 'mysql' );
					$post_data['post_date_gmt'] = current_time( 'mysql', 1 );
				}
				remove_action('future_post', '_future_post_hook');
			}
		} else if (isset($_POST['wpscp-manual-schedule-date']) && !empty($_POST['wpscp-manual-schedule-date'])) {
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
				<input type="checkbox" name="prevent_future_post" value="yes" id="wpsp_prevent_future_post" <?php echo ($post_gmt_timestamp > $current_gmt_timestamp && $post->post_status != 'future') ? ' checked="checked"' : ''; ?> />
				<label for="wpsp_prevent_future_post"> <?php esc_html_e('Publish future post immediately', 'wp-scheduled-posts'); ?><a id="wpscp-future-post-help-handler" href="javascript:void();" title="Show/Hide Help"><?php print esc_html('(?)'); ?></a></label>
				<div id="wpsp_date_type" style="margin-left: 25px; display: none;">
					<input type="radio" id="current_date" name="date_type" value="current">
					<label for="current_date">Current Date</label>
					<input type="radio" id="future_date" name="date_type" value="future" checked>
					<label for="future_date">Future Date</label>
				</div>
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
		add_filter('wp_insert_post_data', 'wpscp_prevent_future_type');
		if (isset($_GET['action']) && $_GET['action'] == 'edit' && \WPSP\Helper::get_settings('show_publish_post_button') == true) {
			add_action('post_submitbox_misc_actions', 'wpscp_prevent_future_post_markup');
		}
	}
}
add_action('init', 'wpscp_submit_box_future_post');
