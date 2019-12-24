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
                update_option('wpscp_options',$options);
            }
            wp_redirect( admin_url('admin.php?page=wp-scheduled-posts#wpsp_gen') );
            exit;
        }
        // notify email option saved
        public function wpscp_notify_email_options_saved(){
            $nonce = $_POST['wpscp_notify_email_options'];
            if(wp_verify_nonce($nonce, 'nonce_wpscp_notify_email_options')){ 
                $wpscp_sender_email_address = (isset($_POST['wpscp_sender_email_address']) ? $_POST['wpscp_sender_email_address'] : '');
                $wpscp_sender_full_name = (isset($_POST['wpscp_sender_full_name']) ? $_POST['wpscp_sender_full_name'] : '');
                $wpscp_notify_author_is_approve = (isset($_POST['wpscp_notify_author_is_approve']) ? $_POST['wpscp_notify_author_is_approve'] : 0);
                $wpscp_notify_author_is_future_to_publish = (isset($_POST['wpscp_notify_author_is_future_to_publish']) ? $_POST['wpscp_notify_author_is_future_to_publish'] : 0);
                $wpscp_notify_author_is_publish_to_draft = (isset($_POST['wpscp_notify_author_is_publish_to_draft']) ? $_POST['wpscp_notify_author_is_publish_to_draft'] : 0);
                $wpscp_email_publish_template_title = (isset($_POST['wpscp_email_publish_template_title']) ? $_POST['wpscp_email_publish_template_title'] : '');
                $wpscp_email_publish_template_body = (isset($_POST['wpscp_email_publish_template_body']) ? $_POST['wpscp_email_publish_template_body'] : '');
                $wpscp_email_draft_template_title = (isset($_POST['wpscp_email_draft_template_title']) ? $_POST['wpscp_email_draft_template_title'] : '');
                $wpscp_email_draft_template_body = (isset($_POST['wpscp_email_draft_template_body']) ? $_POST['wpscp_email_draft_template_body'] : '');
                
                update_option('wpscp_sender_email_address',$wpscp_sender_email_address);
                update_option('wpscp_sender_full_name',$wpscp_sender_full_name);
                update_option('wpscp_notify_author_is_approve',$wpscp_notify_author_is_approve);
                update_option('wpscp_notify_author_is_future_to_publish',$wpscp_notify_author_is_future_to_publish);
                update_option('wpscp_notify_author_is_publish_to_draft',$wpscp_notify_author_is_publish_to_draft);
                update_option('wpscp_email_publish_template_title',$wpscp_email_publish_template_title);
                update_option('wpscp_email_publish_template_body',$wpscp_email_publish_template_body);
                update_option('wpscp_email_draft_template_title',$wpscp_email_draft_template_title);
                update_option('wpscp_email_draft_template_body',$wpscp_email_draft_template_body);
            }
            wp_redirect( admin_url('admin.php?page=wp-scheduled-posts#wpsp_email') );
            exit;
        }
    }
    new wpscp_options_data();
}