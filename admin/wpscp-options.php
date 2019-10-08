<?php
	if(!function_exists('wpscp_get_options')){
		function wpscp_get_options() {
			if(isset($_POST['save_options'])) {
				$show_dashboard_widget=isset($_POST['show_dashboard_widget'])?intval($_POST['show_dashboard_widget']):0; 
				$show_in_front_end_adminbar=isset($_POST['show_in_front_end_adminbar'])?intval($_POST['show_in_front_end_adminbar']):0;
				$allow_user_role=isset($_POST['allow_user_role'])?$_POST['allow_user_role']:'';
				$allow_post_types=isset($_POST['allow_post_types'])?$_POST['allow_post_types']:'';
				$allow_categories=isset($_POST['allow_categories'])?$_POST['allow_categories']:'';
				$adminbar_item_template=isset($_POST['adminbar_item_template'])?trim($_POST['adminbar_item_template']):''; 
				$adminbar_title_length=isset($_POST['adminbar_title_length'])?$_POST['adminbar_title_length']:''; 
				$adminbar_date_format=isset($_POST['adminbar_date_format'])?trim($_POST['adminbar_date_format']):'';

				$options=array(
						'show_dashboard_widget'=>$show_dashboard_widget, 
						'show_in_front_end_adminbar'=>$show_in_front_end_adminbar, 
						'show_in_adminbar'=>isset($_POST['show_in_adminbar']),
						'allow_user_role'=>$allow_user_role,
						'allow_post_types'=>$allow_post_types,
						'allow_categories'=>$allow_categories,
						'adminbar_item_template'=>$adminbar_item_template, 
						'adminbar_title_length'=>$adminbar_title_length, 
						'adminbar_date_format'=>$adminbar_date_format, 
						'prevent_future_post'=>isset($_POST['prevent_future_post'])
				);	
				update_option('wpscp_options',$options);
				$wpscp_options=$options;

			}

			$options=array(
					'show_dashboard_widget'=>1, 
					'show_in_front_end_adminbar'=>1, 
					'show_in_adminbar'=>1,
					'allow_user_role'=>array('administrator'),
					'allow_post_types'=>array('post'),
					'allow_categories'=>array(0),
					'adminbar_item_template'=>"<strong>%TITLE%</strong> / %AUTHOR% / %DATE%",
					'adminbar_title_length'=>45,
					'adminbar_date_format'=>'M-d h:i:a',
					'prevent_future_post'=>1,
			);

			$wpscp_options=get_option('wpscp_options',$options);
			if(!is_array($wpscp_options['allow_categories']))$wpscp_options['allow_categories']=array(0);
			return $wpscp_options;
		}
	}

	if(!function_exists('wpscp_permit_user')){
		function wpscp_permit_user() {
			global $current_user;
			$wpscp_options=wpscp_get_options();

			if(!is_array($current_user->roles)) return false;
			if(!is_array($wpscp_options['allow_user_role']))$wpscp_options['allow_user_role']=array('administrator');

				foreach($current_user->roles as $ur)
				{
					if(in_array($ur, $wpscp_options['allow_user_role'])) {return true; break;}
				}

			return false;
		}
	}

	if(!function_exists('wpscp_dropdown_roles')){
		function wpscp_dropdown_roles( $selected = array() ) { 
			#modified function from function wp_dropdown_roles( $selected = false ) in wp-admin/include/template.php
			$p = '';
			$r = '';
			$editable_roles = get_editable_roles();
			foreach ( $editable_roles as $role => $details ) {
				$name = translate_user_role($details['name'] ); 
				if ( in_array(esc_attr($role),$selected) ) // preselect specified role
					{
					$p .= "\n\t<option selected='selected' value='" . esc_attr($role) . "'>$name</option>";
					}
				else
					$r .= "\n\t<option value='" . esc_attr($role) . "'>$name</option>";
			}
			echo $p . $r;

		}
	}

	if(!function_exists('wpscp_options_page')){
		function wpscp_options_page() {
			global $wpdb;
			$wpscp_options=wpscp_get_options();
			

			?>
			<div class="wpsp-dashboard-body">
				<?php
					//include topbar page
					include WPSCP_ADMIN_DIR_PATH . '/partials/topbar.php';
					//include license page
					include WPSCP_ADMIN_DIR_PATH . '/partials/license.php';
					//manage schedule template
					do_action( 'wpsp_manage_schedule' );
					//integration template
					do_action( 'wpsp_integration' );
					// social template
					do_action( 'wpscp_social_template' );
					// main option pages
					include WPSCP_ADMIN_DIR_PATH . '/partials/options.php';
				?>
			</div>
		<?php }
	}