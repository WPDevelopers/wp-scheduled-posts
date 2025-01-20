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

function is_visual_composer_post($post_id) {
    $post = get_post($post_id);
    if (!$post) {
        return false; // Post doesn't exist
    }
    return strpos($post->post_content, '[vc_') !== false;
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
		$allow_post_types = \WPSP\Helper::get_all_allowed_post_type();
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
				$result = apply_filters('wpsp_admin_bar_menu_posts', $result, $post_types);
				usort($result, function($a, $b) {
					$t1 = strtotime($a->post_date_gmt);
					$t2 = strtotime($b->post_date_gmt);
					return $t1 - $t2;
				});

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
						$wp_admin_bar->add_menu(
							array(
								'id' => 'wpscp_sub_' . $counter,
								'parent' => 'wpscp',
								'title' => 'Sub ' . $counter,
							)
						);

						foreach ($scposts as $scpost) {
							if( !empty( $title_length ) ) {
								$title = esc_html( substr($scpost->post_title, 0,$title_length) );
							}else{
								$title = esc_html( $scpost->post_title );
							}
							$author = get_the_author_meta('user_nicename', $scpost->post_author);
							$date = get_the_date($date_format, $scpost);

							$list_item_template	= str_replace("%TITLE%", esc_html($title), $list_template);
							$list_item_template	= str_replace("%AUTHOR%", esc_html($author), $list_item_template);
							$list_item_template = str_replace("%DATE%", esc_html($date), $list_item_template);
							$item_id++;

							$wp_admin_bar->add_menu(
								array(
									'id' => 'wpscp_sub_' . $counter . '_' . $item_id,
									'parent' => 'wpscp_sub_' . $counter,
									'title' => wp_kses( $list_item_template, 'post' ),
									'href' => get_edit_post_link($scpost->ID),
									'meta' => array('title' => esc_html( $scpost->post_title ))
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
			$post_data['post_date_gmt'] = get_gmt_from_date($_POST['wpscp-manual-schedule-date']);
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

		if ($status === 'future' ) {

			?>
			<div style="padding:10px;" id="prevent_future_post_box">
				<input type="checkbox" name="prevent_future_post" value="yes" id="wpsp_prevent_future_post" <?php echo ($post_gmt_timestamp > $current_gmt_timestamp && $post->post_status != 'future') ? ' checked="checked"' : ''; ?> />
				<label for="wpsp_prevent_future_post"> <?php esc_html_e('Publish future post immediately', 'wp-scheduled-posts'); ?><a href="javascript:void();" title="Show/Hide Help" style="text-decoration: none;"><span id="wpscp-future-post-help-handler" class="dashicons dashicons-info"></span></a></label>
				<div id="wpsp_date_type" style="margin-left: 25px; display: none;">
					<input type="radio" id="current_date" name="date_type" value="current">
					<label for="current_date">Current Date</label>
					<input type="radio" id="future_date" name="date_type" value="future" checked>
					<label for="future_date">Future Date</label>
				</div>
				<div style="border:1px solid #FFEBE8; background:#FEFFE8; padding:5px; display:none;" id="wpscp-future-post-help-info">
					<?php esc_html_e('If you choose to publish this future post with the Future Date, it will be published immediately but the post’s date time will not set the current date rather it will be your scheduled future date time.', 'wp-scheduled-posts'); ?>
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
		$allow_post_types = \WPSP\Helper::get_all_allowed_post_type();
		$post_types = (!empty($allow_post_types) ? $allow_post_types : array('post'));

		foreach ($post_types as $key => $post_type) {
			add_filter("rest_prepare_$post_type", 'wpscp_rest_prepare', 10, 3);
		}
	}
}
add_action('init', 'wpscp_submit_box_future_post');


function wpscp_rest_prepare($response, $post, $request){
	if(!empty($request['meta']['prevent_future_post'])){
		update_post_meta( $post->ID, 'prevent_future_post', $post->post_date );
		$data = $response->get_data();
		if( !empty( $data['status'] ) && $data['status'] == 'future' ) {
			$post_data = array(
				'ID'          => $post->ID,
				'post_status' => 'publish',
			);
			wp_update_post($post_data, true);
		}
		$data['status'] = 'publish';
		$response->set_data($data);
	}
	return $response;
}

add_filter('wp_insert_post_data', function($post_data, $postarr){
	$id = isset($postarr['ID']) ? $postarr['ID'] : 0;
	$prevent_future_post = get_post_meta( $id, 'prevent_future_post', true );
	if ($prevent_future_post == $post_data['post_date']) {
		if ($post_data['post_status'] == 'future') {
			$post_data['post_status'] = 'publish';
			remove_action('future_post', '_future_post_hook');
		}
	}
	else if($prevent_future_post) {
		delete_post_meta($id, 'prevent_future_post');
	}
	return $post_data;
}, 10, 2);

add_filter( 'http_request_timeout', function( $timeout ) {
    return 30; // Timeout in seconds (default is usually 15)
} );


