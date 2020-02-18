<?php
/**
 * WP Scheduled Post Menu
 * @function wp_scheduled_post_menu
 * @since 1.0.0
 */

add_action( 'admin_bar_menu', 'wpscp_scheduled_post_menu', 1000 );
if(!function_exists('wpscp_scheduled_post_menu')){
	function wpscp_scheduled_post_menu() {
		global $wp_admin_bar;
		  $wpscp_options=wpscp_get_options();
		  if($wpscp_options['show_in_adminbar'] || $wpscp_options['show_in_front_end_adminbar']) {
			  if(is_admin() && !$wpscp_options['show_in_adminbar']) return;
			  if(!is_admin() && !$wpscp_options['show_in_front_end_adminbar']) return;
		  	
		  	
			  if(wpscp_permit_user()) {
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
				if(is_array($result)) {
					$totalPost 	= count($result);
				}
				$wp_admin_bar->add_menu( 
					array( 
						'id' => 'wpscp', 
						'title' =>'Scheduled Posts ('.$totalPost.')'
					) 
				);
				
				if(is_array($result)) {
					$totalPostAllowed = 0;
					$list_template = $wpscp_options['adminbar_item_template']; if($list_template=='') {
						$list_template = "<strong>%TITLE%</strong> / %AUTHOR% / %DATE%";
					}
					$list_template = stripslashes($list_template);
					$title_length = intval($wpscp_options['adminbar_title_length']); 
					if( $title_length == 0 ) {
						$title_length = 45;
					}
					$date_format = $wpscp_options['adminbar_date_format']; 
					if($date_format == '') {
						$date_format='M-d h:i:a';
					}
	
					$chunk = array_chunk($result, 8, true);

					$counter = 0;
					foreach($chunk as $scposts) {
						
						foreach ($scposts as $scpost) {
	
							$wp_admin_bar->add_menu( 
								array( 
									'id'=>'wpscp_sub_'.$counter, 
									'parent' => 'wpscp' , 
									'title' => 'Sub '.$counter ,  
								) 
							);
	
							// $title = substr($scpost->post_title, 0,$title_length);
							$title = $scpost->post_title;
							$author = get_the_author_meta( 'user_nicename', $scpost->post_author );
							$date = get_the_date( $format = $date_format, $scpost->ID );
							
							$list_item_template	= str_replace("%TITLE%", $title ,$list_template);
							$list_item_template	= str_replace("%AUTHOR%", $author ,$list_item_template);
							$list_item_template = str_replace("%DATE%", $date ,$list_item_template);
							$item_id++;
	
							$wp_admin_bar->add_menu( 
								array( 
									'id'=>'wpscp_sub_'.$counter.'_'.$item_id,
									'parent' => 'wpscp_sub_'.$counter, 
									'title' =>$list_item_template , 
									'href' =>get_edit_post_link($scpost->ID),
									'meta'=>array('title'=>$scpost->post_title) 
								) 
							);
						}
	
						$counter++;
						$totalPostAllowed++;
					}

					$item_id++;
					?>
	
					<style>
						#wp-admin-bar-wpscp-default .wpsp_arrow_prev,#wp-admin-bar-wpscp-default .wpsp_arrow_next{
							background: url('<?php echo plugins_url(); ?>/wp-scheduled-posts/admin/assets/images/arrow.png') no-repeat;
							background-size: cover;
	
						}
					</style>
	
					<?php
					$Powered_by_text='<div style="margin-top:5px; text-align:center;"><span class="wpsp_arrow_prev wpsp_arrow_pagi"></span>Powered By <span style="color:#fff"><a  style="padding:0;display:inline;" href="https://wpdeveloper.net/in/wpsp">WP Scheduled Posts</a></span>
					<span class="wpsp_arrow_next wpsp_arrow_pagi"></span>
					</div>';
	
					$wp_admin_bar->add_menu( 
						array( 
							'id'=>'wpscp_'.$item_id, 
							'parent' => 'wpscp' , 
							'title' =>$Powered_by_text ,
							'meta'=>array('title'=>'WPDeveloper', 'target'=>'_blank') 
						) 
					);
	
					if($totalPostAllowed != $totalPost) {
						#oevrwrite previous menu with new count
						$wp_admin_bar->add_menu( 
							array( 
								'id' => 'wpscp', 
								'title' =>'Scheduled Posts ('.$totalPost.')'
							) 
						); 
					}
				}//if(is_array($result))
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
if(!function_exists('wpscp_scheduled_posts')){
	function wpscp_scheduled_posts() {
		global $wpdb;
		$wpscp_options 	= wpscp_get_options();
		$post_types 	= implode("', '",$wpscp_options['allow_post_types']); $post_types="'".$post_types."'";
		$result 		= $wpdb->get_results("select * from ".$wpdb->prefix."posts where post_status = 'future' AND post_type IN(".$post_types.") ORDER BY post_date ASC ");

		if(is_array($result))
		{
			echo '<div class="scheduled_posts_box">';
			foreach($result as $scpost)
			{
				echo '<div class="scheduled_post"><div>'.get_date_from_gmt($scpost->post_date_gmt, $format = 'Y-m-d H:i:s')." | ".$scpost->post_title.'</div></div>';
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
if(!function_exists('wpscp_setting_links')){
	function wpscp_setting_links($links, $file) {
		static $wpscp_setting;
		if (!$wpscp_setting) {
			$wpscp_setting = plugin_basename(__FILE__);
		}
		if ($file == $wpscp_setting) {
			$wpscp_settings_link = '<a href="options-general.php?page='.WPSCP_PLUGIN_SLUG.'">Settings</a>';
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
if(!function_exists('wpscp_prevent_future_type')){
	function wpscp_prevent_future_type( $post_data ) {
		if(isset($_POST['prevent_future_post']) && $_POST['prevent_future_post']== true) {
			if ( $post_data['post_status'] == 'future') {
				$post_data['post_status'] = 'publish';
				remove_action('future_post', '_future_post_hook');
			}
		} else if(isset($_POST['wpscp-manual-schedule-date'])) {
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
if(!function_exists('wpscp_prevent_future_post_markup')){
	function wpscp_prevent_future_post_markup($postid) {
		global $post;
		$post_gmt_timestamp=strtotime($post->post_date_gmt);
		$current_gmt_timestamp = current_time('timestamp', $gmt = 1);#http://codex.wordpress.org/Function_Reference/current_time

		$status = get_post_status( $postid );

		if($status !== 'publish') {

			?>
			<div style="padding:10px;" id="prevent_future_post_box">
				<input type="checkbox" name="prevent_future_post" value="yes" id="prevent_future_post_no" <?php echo ($post_gmt_timestamp>$current_gmt_timestamp && $post->post_status!='future')?' checked="checked"':'';?>  />
				<label for="prevent_future_post_no"> <?php esc_html_e('Publish future post immediately', 'wp-scheduled-posts'); ?></label>
				<a id="wpscp-future-post-help-handler" href="javascript:void();" title="Show/Hide Help" ><?php print esc_html('(?)'); ?></a> 
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
if(!function_exists('wpscp_submit_box_future_post')){
	function wpscp_submit_box_future_post() {
		$wpscp_options = wpscp_get_options();
		add_filter('wp_insert_post_data', 'wpscp_prevent_future_type');
		if(isset($_GET['action']) && $_GET['action'] == 'edit' && $wpscp_options['prevent_future_post'] == true) {
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
function wpsp_frontend_head() {
    ?>
    <style>
        .wpsp_arrow_pagi{
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
if( ! function_exists( 'wpscp_scheduled_posts_start_plugin_tracking' ) ) {
	function wpscp_scheduled_posts_start_plugin_tracking() {
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
if(!function_exists('wpscp_dropdown_all_user_name')){
	function wpscp_dropdown_all_user_name( $selected = array()) { 
		$p = '';
		$r = '';
		global $wpdb;
		$all_users = get_users( array( 'fields' => array( 'user_login' ) ) );
		if(is_array($all_users) && count($all_users) > 0){
			foreach ( $all_users as $user ) {
				if ( $selected !== "" && is_array($selected) && in_array($user->user_login, $selected) ){
					$p .= "\n\t<option selected='selected' value='" . esc_attr($user->user_login) . "'>$user->user_login</option>";
				}
				else {
					$r .= "\n\t<option value='" . esc_attr($user->user_login) . "'>$user->user_login</option>";
				}
			}
		}
		
		return $p . $r;
	}
}


if(!function_exists('wpscp_dropdown_all_user_email')){
	function wpscp_dropdown_all_user_email( $selected = array()) { 
		$p = '';
		$r = '';
		global $wpdb;
		$all_users = get_users( array( 'fields' => array( 'user_email' ) ) );
		if(is_array($all_users) && count($all_users) > 0){
			foreach ( $all_users as $user ) {
				if ( $selected !== "" && is_array($selected) && in_array($user->user_email, $selected) ){
					$p .= "\n\t<option selected='selected' value='" . esc_attr($user->user_email) . "'>$user->user_email</option>";
				}
				else {
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
if(!function_exists('wpscp_email_notify_review_email_list')){
	function wpscp_email_notify_review_email_list(){
		global $wpdb;
		$email = '';
		// collect email from role
		$roles = get_option('wpscp_notify_author_role_sent_review');
		if(!empty($roles)){
			$email = wp_list_pluck(get_users( array( 
				'fields' 	=> array( 'user_email' ),
				'role__in'	=> $roles
			)), 'user_email');
		}
		// collect email from email fields
		$meta_email = array_values(get_option('wpscp_notify_author_email_sent_review'));
		if(!empty($meta_email)){
			$email = array_merge($email, $meta_email);
		}
		// get email from username
		$meta_username = get_option('wpscp_notify_author_username_sent_review');
		if(!empty($meta_username)){
			$email = array_merge($email, wp_list_pluck(get_users( array( 
				'fields' 	=> array( 'user_email' ),
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
if(!function_exists('wpscp_email_notify_schedule_email_list')){
	function wpscp_email_notify_schedule_email_list(){
		global $wpdb;
		$email = '';
		// collect email from role
		$roles = get_option('wpscp_notify_author_post_schedule_role');
		if(!empty($roles)){
			$email = wp_list_pluck(get_users( array( 
				'fields' 	=> array( 'user_email' ),
				'role__in'	=> $roles
			)), 'user_email');
		}
		// collect email from email fields
		$meta_email = array_values(get_option('wpscp_notify_author_post_schedule_email'));
		if(!empty($meta_email)){
			$email = array_merge($email, $meta_email);
		}
		// get email from username
		$meta_username = get_option('wpscp_notify_author_post_schedule_username');
		if(!empty($meta_username)){
			$email = array_merge($email, wp_list_pluck(get_users( array( 
				'fields' 	=> array( 'user_email' ),
				'login__in'	=> $meta_username
			)), 'user_email'));
		}
		return array_unique($email);
	}
}