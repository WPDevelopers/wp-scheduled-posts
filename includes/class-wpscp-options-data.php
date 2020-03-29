<?php 
if(!class_exists('wpscp_options_data')){
    class wpscp_options_data {
        public function __construct(){
            add_action('admin_post_wpscp_general_options_saved', array($this, 'wpscp_general_options_saved'));
            add_action('admin_post_wpscp_notify_email_options_saved', array($this, 'wpscp_notify_email_options_saved'));
        }
        // default option saved
        public function wpscp_general_options_saved(){
            $nonce = $_POST['nonce_wpscp_general_options'];
            if(wp_verify_nonce($nonce, 'nonce_wpscp_general_options')){ 
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
                update_option('wpscp_options', apply_filters('wpscp_options', $options ));
            }
            wp_redirect( admin_url('admin.php?page=wp-scheduled-posts#wpsp_gen') );
            exit;
        }
        // notify email option saved
        public function wpscp_notify_email_options_saved(){
            $nonce = $_POST['wpscp_notify_email_options'];
            if(wp_verify_nonce($nonce, 'nonce_wpscp_notify_email_options')){ 
                $notify_sender_email_address = (isset($_POST['notify_sender_email_address']) ? $_POST['notify_sender_email_address'] : '');
                $notify_sender_full_name = (isset($_POST['notify_sender_full_name']) ? $_POST['notify_sender_full_name'] : '');
                
                $notify_author_is_sent_review = (isset($_POST['notify_author_is_sent_review']) ? $_POST['notify_author_is_sent_review'] : 0);
                $notify_author_role_sent_review = (isset($_POST['notify_author_role_sent_review']) ? $_POST['notify_author_role_sent_review'] : '');
                $notify_author_username_sent_review = (isset($_POST['notify_author_username_sent_review']) ? $_POST['notify_author_username_sent_review'] : '');
                $notify_author_email_sent_review = (isset($_POST['notify_author_email_sent_review']) ? $_POST['notify_author_email_sent_review'] : '');
                
                $notify_author_post_is_rejected = (isset($_POST['notify_author_post_is_rejected']) ? $_POST['notify_author_post_is_rejected'] : 0);
                
                $notify_author_post_is_schedule = (isset($_POST['notify_author_post_is_schedule']) ? $_POST['notify_author_post_is_schedule'] : 0);
                $notify_author_post_schedule_role = (isset($_POST['notify_author_post_schedule_role']) ? $_POST['notify_author_post_schedule_role'] : '');
                $notify_author_post_schedule_username = (isset($_POST['notify_author_post_schedule_username']) ? $_POST['notify_author_post_schedule_username'] : '');
                $notify_author_post_schedule_email = (isset($_POST['notify_author_post_schedule_email']) ? $_POST['notify_author_post_schedule_email'] : '');
                
                $notify_author_schedule_post_is_publish = (isset($_POST['notify_author_schedule_post_is_publish']) ? $_POST['notify_author_schedule_post_is_publish'] : 0);
                
                $notify_author_post_is_publish = (isset($_POST['notify_author_post_is_publish']) ? $_POST['notify_author_post_is_publish'] : 0);
               
                update_option('wpscp_notify_author_is_sent_review',$notify_author_is_sent_review);
                update_option('wpscp_notify_author_role_sent_review',$notify_author_role_sent_review);
                update_option('wpscp_notify_author_username_sent_review',$notify_author_username_sent_review);
                update_option('wpscp_notify_author_email_sent_review',$notify_author_email_sent_review);
                update_option('wpscp_notify_author_post_is_rejected',$notify_author_post_is_rejected);
                update_option('wpscp_notify_author_post_is_schedule',$notify_author_post_is_schedule);
                update_option('wpscp_notify_author_post_schedule_role',$notify_author_post_schedule_role);
                update_option('wpscp_notify_author_post_schedule_username',$notify_author_post_schedule_username);
                update_option('wpscp_notify_author_post_schedule_email',$notify_author_post_schedule_email);
                update_option('wpscp_notify_author_schedule_post_is_publish',$notify_author_schedule_post_is_publish);
                update_option('wpscp_notify_author_post_is_publish',$notify_author_post_is_publish);
            }
            wp_redirect( admin_url('admin.php?page=wp-scheduled-posts#wpsp_email') );
            exit;
        }
    }
    new wpscp_options_data();
}