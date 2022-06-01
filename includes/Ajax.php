<?php
namespace WPSP;
class Ajax {
    public function __construct()
    {
        add_action( 'wp_ajax_wpsp_get_select2_field_data', array($this, 'select2_field_data') );
    }

    public function select2_field_data(){
        check_ajax_referer( 'wp_rest', '_wpnonce' );
        $type = (isset($_POST['type']) ? $_POST['type'] : '');
        $data = [];
        if($type == 'allow_post_types'){
            $data = Helper::get_all_post_type();
        } else if($type == 'allow_categories'){
            $data = Helper::_get_all_category();
        } else if($type == 'allow_user_by_role' || $type == 'notify_author_post_review_by_role' || $type == 'notify_author_post_scheduled_by_role'){
            $data = Helper::get_all_roles();
        } else if($type == 'notify_author_post_review_by_username' || $type == 'notify_author_post_scheduled_by_username'){
            $get_users = get_users(array('fields' => array('user_login', 'user_email')));
            $data = wp_list_pluck($get_users, 'user_login', 'user_login');
        }else if($type == 'notify_author_post_review_by_email' || $type == 'notify_author_post_scheduled_by_email'){
            $get_users = get_users(array('fields' => array('user_login', 'user_email')));
            $data = wp_list_pluck($get_users, 'user_email', 'user_email');
        }
        wp_send_json_success($data);
        wp_die();
    }
}