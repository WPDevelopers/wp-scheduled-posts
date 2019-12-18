<?php 
if(!class_exists('wpscp_options_data')){
    class wpscp_options_data {
        public function __construct(){
            add_action('admin_post_wpscp_general_options_saved', array($this, 'wpscp_general_options_saved'));
        }
        public function wpscp_general_options_saved(){
            $nonce = $_POST['nonce_wpscp_general_options'];
            if(wp_verify_nonce($nonce, 'nonce_wpscp_general_options')){
                $publish_schedule_post_notify=isset($_POST['publish_schedule_post_notify'])?intval($_POST['publish_schedule_post_notify']):0; 
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
                        'prevent_future_post'=>isset($_POST['prevent_future_post']),
                        'publish_schedule_post_notify' => $publish_schedule_post_notify
                );	
                update_option('wpscp_options',$options);
            }
            wp_redirect( admin_url('admin.php?page=wp-scheduled-posts#wpsp_gen') );
            exit;
        }
    
    }
    new wpscp_options_data();
}