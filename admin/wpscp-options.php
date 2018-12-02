<?php 

function wpscp_get_options()
{
	$options=array(
			'show_dashboard_widget'=>1, 
			'show_in_front_end_adminbar'=>1, 
			'show_in_adminbar'=>1,
			'allow_user_role'=>array('administrator'),
			'allow_post_types'=>array('post'),
			'allow_categories'=>array(0),
			'adminbar_item_template'=>"<strong>%TITLE%...</strong> by %AUTHOR% for %DATE%",
			'adminbar_title_length'=>45,
			'adminbar_date_format'=>'M-d h:i:a',
			'prevent_future_post'=>0,
	);
	$wpscp_options=get_option('wpscp_options',$options);
	if(!is_array($wpscp_options['allow_categories']))$wpscp_options['allow_categories']=array(0);
	return $wpscp_options;
}

function wpscp_permit_user()
{
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

function wpscp_dropdown_roles( $selected = array() ) { #modified function from function wp_dropdown_roles( $selected = false ) in wp-admin/include/template.php
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


function wpscp_options_page()
{
	global $wpdb;
	$wpscp_options=wpscp_get_options();
	
	if(isset($_POST['save_options']))
	{
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
	}#end if(isset($_POST['save_options']))
	?>

	<div class="wrap wpsp-dashboard-body">
		<div class="wpsp-header">
			<h1>WP Scheduled Posts Options</h1>
		</div>
	    <div class="wpsp-settings-wrap">
			<div class="wpsp-options-wrap">
				<form action="" method="post">
	            <table class="form-table">
	            <tr><td  colspan="2" align="left"><input type="checkbox" name="show_dashboard_widget" value="1" <?php echo ($wpscp_options['show_dashboard_widget'])?' checked="checked"': '';?> />&nbsp;&nbsp;Show Scheduled Posts in Dashboard Widget</td></tr>
	            <tr><td  colspan="2" align="left"><input type="checkbox" name="show_in_front_end_adminbar" value="1" <?php echo ($wpscp_options['show_in_front_end_adminbar'])?' checked="checked"': '';?>/>&nbsp; &nbsp;Show Scheduled Posts in Sitewide Admin Bar</td></tr>
	            <tr><td  colspan="2" align="left"><input type="checkbox" name="show_in_adminbar" value="1" <?php echo ($wpscp_options['show_in_adminbar'])?' checked="checked"': '';?>/>&nbsp;&nbsp;Show Scheduled Posts in Admin Bar</td></tr>

				<tr>
	            <td scope="row" align="left" style="vertical-align:top;">Show Post Types: </td>
	            <td>
	            <select name="allow_post_types[]" MULTIPLE style="height:80px;width:200px;">
				<?php
				$typeswehave = array('post,revision'); //oneTarek
				$post_types=get_post_types('','names'); 
				$rempost = array('attachment','revision','nav_menu_item');
				$post_types = array_diff($post_types,$rempost);
				foreach ($post_types as $post_type ) {
					echo "<option ";
					
					if(in_array($post_type,$wpscp_options['allow_post_types'])) echo "selected ";
					echo 'value="'.$post_type.'">'.$post_type.'</option>';
				}
				
				?>
				</select>
	            </td>
	            </tr>
	 
	 			<tr>
	            <td scope="row" align="left" style="vertical-align:top;">Show Categories:<br /><small>Category filter works only for "post" post type</small> </td>
	            <td>
	         <select name="allow_categories[]" MULTIPLE style="height:100px;width:200px;">
				<?php
				$args = array(
					'type'                     => 'post',
					'child_of'                 => 0,
					'parent'                   => '',
					'orderby'                  => 'name',
					'order'                    => 'ASC',
					'hide_empty'               => 0,
					'hierarchical'             => 0,
					'exclude'                  => '',
					'include'                  => '',
					'number'                   => '',
					'taxonomy'                 => 'category',
					'pad_counts'               => false 
				
				); 
				$categories = get_categories( $args );
				array_unshift($categories, (object)array("term_id"=>0, "name"=>"All Categories"));
				//echo "<pre>"; print_r($categories); echo "</pre>";

				foreach ($categories as $cat ) {
					echo "<option ";
					
					if(in_array($cat->term_id,$wpscp_options['allow_categories'])) echo "selected ";
					echo 'value="'.$cat->term_id.'">'.$cat->name.'</option>';
				}
				?>
				</select>
	            </td>
	            </tr>
	 
	            
	            <tr valign="top">
	            <td width="150" scope="row" align="align="left""><label for="allow_user_role">Allow users:</label></td>
	            <td>
	            <select name="allow_user_role[]" id="allow_user_role" multiple="multiple"  style="height:80px;width:200px;" ><?php  wpscp_dropdown_roles( $wpscp_options['allow_user_role'] ); ?></select>
	            </td>
	            </tr>
	            <tr><td  colspan="2" align="left">
	            	<div style="border:1px solid #eeeeee; padding:5px;">
	                	<strong>Custom item template for scheduled posts list in adminbar:</strong><br />
	                    Item template: <input type="text" name="adminbar_item_template" size="50" placeholder="<strong>%TITLE%...</strong> by %AUTHOR% for %DATE%"  value="<?php echo htmlspecialchars(stripslashes($wpscp_options['adminbar_item_template'])) ?>"  /> 
	                    Title length: <input type="text" name="adminbar_title_length" size="5" placeholder="45"  value="<?php echo $wpscp_options['adminbar_title_length'] ?>" /> 
	                    <br />
	                    Date format: <input type="text" name="adminbar_date_format" size="10" placeholder="M-d h:i:a"  value="<?php echo htmlspecialchars(stripslashes($wpscp_options['adminbar_date_format'])) ?>" />
	                	<div style="color:#999999; padding: 10px;">For item template use <strong>%TITLE%</strong> for post title, <strong>%AUTHOR%</strong> for post author and <strong>%DATE%</strong> for post scheduled date-time. You can use HTML tags with styles also </div>
	                </div>
	            </td></tr>
	            <tr><td  colspan="2" align="left">
	            <div style="border:1px solid #FFEBE8; background:#FEFFE8; padding:5px;">
	            <input type="checkbox" name="prevent_future_post" value="1" <?php echo ($wpscp_options['prevent_future_post'])?' checked="checked"': '';?> />&nbsp;&nbsp;Show option to publish post immediately but with future date-time <span style="color:#666666"> (A checkbox will be appeared in date-time edit section in the post edit panel)</span> 
	            </div>    
	            </td></tr>  
	            <tr><td><input type="submit" name="save_options" value="Save Options" class='button-primary'/></td><td>&nbsp;</td></tr>
	            </table>
	            </form>
	        </div>

  			<div class="wpsp-admin-sidebar">
  				<div class="wpsp-sidebar-block">
  					<div class="wpsp-admin-sidebar-logo">
  						<img src="<?php echo plugins_url( '/', __FILE__ ).'assets/images/wpsp-logo.svg'; ?>">
  					</div>
  					<div class="wpsp-admin-sidebar-cta">
  						<?php     
	  						if(function_exists('wpsp_install_core_notice')) {
	  							printf( __( '<a href="%s" target="_blank">Manage License</a>', 'wp-scheduled-posts' ), 'https://wpdeveloper.net/account' ); 
	  						}else{
	  							printf( __( '<a href="%s" target="_blank">Upgrade to Pro</a>', 'wp-scheduled-posts' ), 'https://wpdeveloper.net/in/wpsp' );
	  						}
  						?>
  					</div>
  				</div>
  				<div class="wpsp-sidebar-block wpsp-license-block">
  					<?php
  					    if(function_exists('wpsp_install_core_notice')) {
							do_action( 'wpsp_licensing' );
						}
					?>
  				</div>
			</div><!--admin sidebar end-->

	    </div>
	</div>

<?php } ?>