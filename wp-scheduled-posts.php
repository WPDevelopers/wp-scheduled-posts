<?php
/*
 * Plugin Name: WP Scheduled Posts
 * Plugin URI: https://wpdeveloper.net/free-plugin/wp-scheduled-posts/
 * Description: A complete solution for WordPress Post Schedule. Get an admin Bar & Dashboard Widget showing all your scheduled posts. And full control.
 * Version: 1.4.0
 * Author: WPDeveloper.net
 * Author URI: https://wpdeveloper.net
 * License: GPL2+
 * Text Domain: wp-scheduled-posts
 * Min WP Version: 2.5.0
 * Max WP Version: 4.2.2
 */


define("WPSCP_PLUGIN_SLUG",'wp-scheduled-posts');
define("WPSCP_PLUGIN_URL",plugins_url("",__FILE__ ));#without trailing slash (/)
define("WPSCP_PLUGIN_PATH",plugin_dir_path(__FILE__)); #with trailing slash (/)

include_once('includes/wpscp-options.php');
include_once('includes/wpdev-dashboard-widget.php');

function add_wpscp_menu_pages()

{
add_options_page( "WP Scheduled Posts", "WP Scheduled Posts" ,'manage_options', WPSCP_PLUGIN_SLUG, 'wpscp_options_page');
}

add_action('admin_menu', 'add_wpscp_menu_pages'); 

	
	function wp_scheduled_post_widget_function() {
		global $wpdb;
		$wpscp_options=wpscp_get_options();
		$post_types=implode("', '",$wpscp_options['allow_post_types']); $post_types="'".$post_types."'";
		$result=$wpdb->get_results("select * from ".$wpdb->prefix."posts where post_status = 'future' AND post_type IN(".$post_types.") ORDER BY post_date ASC ");

		echo '<table class="widefat">';
		foreach($result as $scpost)
		{
		if( $scpost->post_type=="post" && !in_array(0, $wpscp_options['allow_categories']))
			{
			$pcats = get_the_category($scpost->ID);		
			$found=false;	
			foreach($pcats as $c){
				if(in_array($c->term_id,$wpscp_options['allow_categories'])) {$found=true;break;  }
				}
			if(!$found)continue;
			}		
		
		
		echo '<tr><td><a href="'.get_edit_post_link($scpost->ID).'">'.$scpost->post_title.'</a></td><td>'.get_date_from_gmt($scpost->post_date_gmt, $format = 'Y-m-d H:i:s').'</td><td>'.get_the_author_meta( 'user_login', $scpost->post_author ).'</td></tr>';
		
		}
		echo "</table>";
		
	} # END OF wp_scheduled_post_widget_function()

// Create the function use in the action hook

function wp_scp_add_dashboard_widgets()
	{
	$wpscp_options=wpscp_get_options();
	
	if($wpscp_options['show_dashboard_widget'])
		{
			if(wpscp_permit_user())
			{
			wp_add_dashboard_widget('wp_scp_dashboard_widget', 'Scheduled Posts', 'wp_scheduled_post_widget_function');	
			}
		}
	} 
// Hook into the 'wp_dashboard_setup' action to register our other functions
add_action('wp_dashboard_setup', 'wp_scp_add_dashboard_widgets');

#-----------ADMINBAR---------------------
add_action( 'admin_bar_menu', 'wp_scheduled_post_menu', 1000 );


  function wp_scheduled_post_menu() {
  	  global $wp_admin_bar;
	  $wpscp_options=wpscp_get_options();
	  if($wpscp_options['show_in_adminbar'] || $wpscp_options['show_in_front_end_adminbar'])
	  {
		  if(is_admin() && !$wpscp_options['show_in_adminbar']) return;
		  if(!is_admin() && !$wpscp_options['show_in_front_end_adminbar']) return;
		  
		  if(wpscp_permit_user())
		  {
				global $wpdb;
				$item_id=0;
				$post_types=implode("', '",$wpscp_options['allow_post_types']); $post_types="'".$post_types."'";
				$result=$wpdb->get_results("select * from ".$wpdb->prefix."posts where post_status = 'future' AND post_type IN(".$post_types.") ORDER BY post_date ASC ");
				$totalPost=0;
				if(is_array($result)){$totalPost=count($result);}
				$wp_admin_bar->add_menu( array( 'id' => 'wpscp', 'title' =>'Scheduled Posts ('.$totalPost.')') );
				
				if(is_array($result))
				{
					$totalPostAllowed=0;
					$list_template=$wpscp_options['adminbar_item_template']; if($list_template==''){$list_template="<strong>%TITLE%...</strong> by %AUTHOR% for %DATE%";}
					$list_template=stripslashes($list_template);
					$title_length=intval($wpscp_options['adminbar_title_length']); if($title_length==0){$title_length=45;}
					$date_format=$wpscp_options['adminbar_date_format']; if($date_format==''){$date_format='M-d h:i:a';}
					foreach($result as $scpost)
					{ 
					
					if( $scpost->post_type=="post" && !in_array(0, $wpscp_options['allow_categories']))
						{
						$pcats = get_the_category($scpost->ID);		
						$found=false;	
						foreach($pcats as $c){
							if(in_array($c->term_id,$wpscp_options['allow_categories'])) {$found=true;break;  }
							}
						if(!$found)continue;
						}
					$title=substr($scpost->post_title, 0,$title_length);
					$author=get_the_author_meta( 'user_nicename', $scpost->post_author );
					$date=get_date_from_gmt($scpost->post_date_gmt, $format = $date_format);
					
					$list_item_template=str_replace("%TITLE%", $title ,$list_template);
					$list_item_template=str_replace("%AUTHOR%", $author ,$list_item_template);
					$list_item_template=str_replace("%DATE%", $date ,$list_item_template);
					$item_id++;
					$wp_admin_bar->add_menu( array( 'id'=>'wpscp_'.$item_id, 'parent' => 'wpscp' , 'title' =>$list_item_template , 'href' =>get_edit_post_link($scpost->ID),'meta'=>array('title'=>$scpost->post_title) ) );
					$totalPostAllowed++;
					}
					$item_id++;
					$Powered_by_text='<div style="border-top:1px solid #7AD03A;margin-top:5px; text-align:center;">Powered By <span style="color:#7AD03A">WP Scheduled Posts</span></div>';
					$wp_admin_bar->add_menu( array( 'id'=>'wpscp_'.$item_id, 'parent' => 'wpscp' , 'title' =>$Powered_by_text , 'href' =>'https://wpdeveloper.net/go/WPSP-Main','meta'=>array('title'=>'WP Developer', 'target'=>'_blank') ) );

					if($totalPostAllowed!=$totalPost)
					{
					#oevrwrite previous menu with new count
					$wp_admin_bar->add_menu( array( 'id' => 'wpscp', 'title' =>'Scheduled Posts ('.$totalPostAllowed.')') ); 
					}
				}//if(is_array($result))
				
		  }
	  }
  }

#-----------------------------SHOWING scheduled POSTS ON HOMEPAGE--------------------------------------
function wp_scheduled_posts()
{
	global $wpdb;
		$wpscp_options=wpscp_get_options();
		$post_types=implode("', '",$wpscp_options['allow_post_types']); $post_types="'".$post_types."'";
		$result=$wpdb->get_results("select * from ".$wpdb->prefix."posts where post_status = 'future' AND post_type IN(".$post_types.") ORDER BY post_date ASC ");

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
	
}#end wp_scheduled_posts()

#---------------------------------------Settings Page Link in Plugin List Page------------------------------------------------------------
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
add_filter('plugin_action_links', 'wpscp_setting_links', 10, 2);	

#------------------------------------------Publish Post Immediately but with a future date -------------------------------------------------------------



function wpscp_prevent_future_type( $post_data ) {
if(isset($_POST['prevent_future_post']) && $_POST['prevent_future_post']=='yes')
{
	
	if ( $post_data['post_status'] == 'future')
	{
	$post_data['post_status'] = 'publish';
	remove_action('future_post', '_future_post_hook');
	}
}
return $post_data;
}

function wpscp_post_page_prevent_future_option($postid)
{
global $post;

$post_gmt_timestamp=strtotime($post->post_date_gmt);
$current_gmt_timestamp = current_time('timestamp', $gmt = 1);#http://codex.wordpress.org/Function_Reference/current_time
?>
<script type="text/javascript">
	function show_wpscp_help(){ jQuery("#wpscp_help").toggle();}
	jQuery(document).ready(function($){
		$(".save-timestamp:first").before($("#prevent_future_post_box"));
	});
</script>
<div style="padding:10px;" id="prevent_future_post_box">
<input type="checkbox" name="prevent_future_post" value="yes" id="prevent_future_post_no" <?php echo ($post_gmt_timestamp>$current_gmt_timestamp && $post->post_status!='future')?' checked="checked"':'';?>  /><label for="prevent_future_post_no"> Publish future post immediately </label><a href="javascript:void();" onclick="show_wpscp_help()" title="Show/Hide Help" >(?)</a> 
    <div style="border:1px solid #FFEBE8; background:#FEFFE8; padding:5px; display:none;" id="wpscp_help">
    	If you schedule this post and check this option then your post will be published immediately but post date-time will not set current date. Post date-time will be your scheduled future date-time. 
    </div>
</div>
<?php
}


function wpscp_initialize()
{
$wpscp_options=wpscp_get_options();
	if($wpscp_options['prevent_future_post']==1)
	{
		add_filter('wp_insert_post_data', 'wpscp_prevent_future_type');
		#show an option in post edit page
		
		
		add_action('post_submitbox_misc_actions', 'wpscp_post_page_prevent_future_option');
	}#end if($wpscp_options['...
}


add_action('init', 'wpscp_initialize');

/* Display a notice that can be dismissed */

add_action('admin_notices', 'wpscp_admin_notice');

function wpscp_admin_notice() {
if ( current_user_can( 'install_plugins' ) )
   {
     global $current_user ;
        $user_id = $current_user->ID;
        /* Check that the user hasn't already clicked to ignore the message */
     if ( ! get_user_meta($user_id, 'wpscp_ignore_notice140a') ) {
        echo '<div class="updated"><p>';
        printf(__('<b>[Notice]</b> If <b><a href="https://wpdeveloper.net/go/wpsp-free" target="_blank">WP Scheduled Posts</a> </b>plugin helped you, why not <strong><a href="https://wpdeveloper.net/go/wpsp-rating" target="_blank">rate us</a></strong> in <strong><a href="https://wpdeveloper.net/go/wpsp-rating" target="_blank">WordPress.org</a></strong>, it takes only few second. If you have any issue with the plugin please contact <strong><a href="https://wpdeveloper.net/support/" target="_blank">support</a></strong>. Make sure you checked other <strong>cool free plugins</strong> we created at <strong><a href="https://wpdeveloper.net/" target="_blank">WPDeveloper.net</a></strong>
 | <a href="%1$s">[Hide Notice]</a>'),  admin_url( 'admin.php?page=wp-scheduled-posts&wpscp_nag_ignore=0' ));
        echo "</p></div>";
     }
    }
}

add_action('admin_init', 'wpscp_nag_ignore');

function wpscp_nag_ignore() {
     global $current_user;
        $user_id = $current_user->ID;
        /* If user clicks to ignore the notice, add that to their user meta */
        if ( isset($_GET['wpscp_nag_ignore']) && '0' == $_GET['wpscp_nag_ignore'] ) {
             add_user_meta($user_id, 'wpscp_ignore_notice140a', 'true', true);
     }
}

?>