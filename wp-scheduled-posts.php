<?php
/*
 * Plugin Name: WP Scheduled Posts
 * Description: A complete solution for WordPress Post Schedule. Get an admin Bar & Dashboard Widget showing all your scheduled posts. And full control.
 * Version: 2.0.2
 * Author: WPDeveloper
 * Author URI: https://wpdeveloper.net
 * Text Domain: wp-scheduled-posts
 */


define("WPSCP_PLUGIN_SLUG",'wp-scheduled-posts');
define("WPSCP_PLUGIN_URL",plugins_url("",__FILE__ ));#without trailing slash (/)
define("WPSCP_PLUGIN_PATH",plugin_dir_path(__FILE__)); #with trailing slash (/)

include_once('admin/wpscp-options.php');


if (!class_exists('Wp_Scheduled_Posts')) {
	class Wp_Scheduled_Posts {
		function __construct() {
			$this->define_constant();
			$this->load_dependencies();
			$this->plugin_name = plugin_basename(__FILE__);
			$parent_plugin_file = 'wp-scheduled-posts/wp-scheduled-posts.php';

			
			register_deactivation_hook( $this->plugin_name, array(&$this, 'deactivate') );
			register_activation_hook( $this->plugin_name, array(&$this, 'activate') );
			register_uninstall_hook( $this->plugin_name, 'uninstall' );

			add_action( 'admin_enqueue_scripts', array(&$this, 'start_plugin') );
			add_action( 'admin_init', array(&$this, 'check_some_other_plugin') );
			add_action('admin_notices', 'wpsp_admin_notice');
		}
		
		function define_constant() {
		    //echo WP_PLUGIN_URL; die;
			define('pluginsFOLDER', plugin_basename( dirname(__FILE__)) );
			define('plugins_ABSPATH', trailingslashit( str_replace("\\","/", WP_PLUGIN_DIR . '/' . plugin_basename( dirname(__FILE__) ) ) ) );
			define('plugins_URLPATH', trailingslashit( plugins_url() . '/' . plugin_basename( dirname(__FILE__) ) ) );
		}
		
		function load_dependencies() {
			if ( is_admin() ) {	
				require_once (dirname (__FILE__) . '/admin/admin.php');
				$this->optionAdminPanel = new optionAdminPanel();
			}
		}

		function check_some_other_plugin() {
			remove_submenu_page( 'options-general.php', 'wp-scheduled-posts' );
		}

		
		function activate() {
			include_once (dirname (__FILE__) . '/admin/install.php');
			psm_install();
			return true;
		}



		function deactivate(){
			return true;
		}
		function uninstall(){
			return true;
		}
		
		function start_plugin() {
			if ( is_admin() ) {
				wp_enqueue_style( 'admin-style', plugins_URLPATH . 'admin/css/admin.css' );
				wp_enqueue_style( 'font-awesome', plugins_URLPATH . 'admin/css/font-awesome.min.css' );
				wp_enqueue_style( 'chung-timepicker', plugins_URLPATH . 'admin/css/chung-timepicker.css' );
				wp_enqueue_style( 'sweet-alert-css', plugins_URLPATH . 'admin/assets/vendor/sweetalert2/css/sweetalert2.min.css' );
				wp_enqueue_script( 'custom-script', plugins_URLPATH . 'admin/js/custom-script.js', array('jquery'), '1.0.0', false );
				wp_enqueue_script( 'main-chung-timepicker', plugins_URLPATH . 'admin/js/chung-timepicker.js', array('jquery'), '1.0.0', false );
				wp_enqueue_script( 'sweet-alert-core-js', plugins_URLPATH . 'admin/assets/vendor/sweetalert2/js/core.js', array('jquery'), '1.0.0', false);
				wp_enqueue_script( 'sweet-alert-js', plugins_URLPATH . 'admin/assets/vendor/sweetalert2/js/sweetalert2.min.js', array('jquery'), '1.0.0', false);
			}
		}
		
		
	}
	global $wpsp_op;
	$wpsp_op = new Wp_Scheduled_Posts();
		
include('admin/scheduled-calendar/wpspcalendar.php');
include('admin/manage-schedule/manage-schedule.php');
include('admin/wpsp-missed-schedule/wpsp-missed-schedule.php');

}

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
					$wp_admin_bar->add_menu( array( 'id'=>'wpscp_'.$item_id, 'parent' => 'wpscp' , 'title' =>$Powered_by_text , 'href' =>'https://wpdeveloper.net/in/wpsp','meta'=>array('title'=>'WP Developer', 'target'=>'_blank') ) );

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
<input type="checkbox" name="prevent_future_post" value="yes" id="prevent_future_post_no" <?php echo ($post_gmt_timestamp>$current_gmt_timestamp && $post->post_status!='future')?' checked="checked"':'';?>  /><label for="prevent_future_post_no"> Publish future post immediately</label><a href="javascript:void();" onclick="show_wpscp_help()" title="Show/Hide Help" >(?)</a> 
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

// add_action('admin_notices', 'wpscp_admin_notice'); Hakim commented this line because function was called without declaration


/**
 * Admin Notice
 *
 * @since v2.0.0
 */
function wpsp_admin_notice() {
  if ( current_user_can( 'install_plugins' ) ) {
    global $current_user ;
    $user_id = $current_user->ID;
    /* Check that the user hasn't already clicked to ignore the message */
    if ( ! get_user_meta($user_id, 'wpsp_ignore_notice202') ) {
      echo '<div class="wpsp-admin-notice updated" style="display: flex; align-items: center; padding-left: 0; border-left-color: #6648FE"><p style="width: 36px;background-color: #f1f2f9;border-radius: 50%;margin: 0.5em;">';
      echo '<img style="width: 100%; display: block;"  src="' . plugins_url( '/', __FILE__ ).'admin/assets/images/wpsp-logo.svg'. '" ></p><p> ';
      printf(__('Thank you for upgrading to <a href="https://wpdeveloper.net/in/wpsp-intro-20">WP Scheduled Posts 2.0</a>, which brings free feature like Drag &amp; Drop Calendar. <strong>Auto Scheduler</strong> and <strong>Missed Scheduler</strong> features are available in <a href="https://wpdeveloper.net/in/wpsp">Pro</a>! Use the coupon code <strong>WPSP-EARLYBIRD</strong> for a <strong>50&#37; </strong>launching discount!
        <a href="%1$s" style="text-decoration: none; margin-left: 10px;"><span class="dashicons dashicons-dismiss"></span> I\'m good with free version</a>'),  admin_url( 'admin.php?page=wp-scheduled-posts&wpsp_nag_ignore=0' ));
      echo "</p></div>";
    }
  }
}


/**
 * Nag Ignore
 */
function wpsp_nag_ignore() {
  global $current_user;
        $user_id = $current_user->ID;
        /* If user clicks to ignore the notice, add that to their user meta */
        if ( isset($_GET['wpsp_nag_ignore']) && '0' == $_GET['wpsp_nag_ignore'] ) {
             add_user_meta($user_id, 'wpsp_ignore_notice202', 'true', true);
  }
}
add_action('admin_init', 'wpsp_nag_ignore');


/**
 * Optional usage tracker
 *
 * @since v2.0.0
 */

if( ! class_exists( 'Wpsp_Plugin_Usage_Tracker') ) {
	require_once dirname( __FILE__ ) . '/includes/class-plugin-usage-tracker.php';
}
if( ! function_exists( 'wp_scheduled_posts_start_plugin_tracking' ) ) {
	function wp_scheduled_posts_start_plugin_tracking() {
		$wisdom = new Wpsp_Plugin_Usage_Tracker(
			__FILE__,
			'https://wpdeveloper.net',
			array(),
			true,
			true,
			1
		);
	}
	wp_scheduled_posts_start_plugin_tracking();
}

?>