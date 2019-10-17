<?php
class WpScp_Calendar {
    public function __construct() {
        $this->hooks();
    }
    /**
     * Calendar Hooks
     * @method hooks
     * @since 3.0.1
     */
    public function hooks(){
        add_action( 'rest_api_init', array($this, 'wpscp_register_custom_route'));
        add_action( 'wp_ajax_wpscp_calender_ajax_request', array($this, 'calender_ajax_request_php') );
        add_action( 'wp_ajax_wpscp_quick_edit', array($this, 'quick_edit_action') );
        add_action( 'wp_ajax_wpscp_delete_event', array($this, 'delete_event_action') );
    }
    
    public function wpscp_register_custom_route () {
        register_rest_route( 'wpscp/v1', '/post/future', array(
          'methods' => 'GET',
          'callback' => array($this, 'future_post_rest_route_output'),
        ));
    }

    public function future_post_rest_route_output( $data ) {
        $wpscp_all_options  = get_option('wpscp_options');
         $allow_post_types =  ($wpscp_all_options['allow_post_types'] == '' ? array('post') : $wpscp_all_options['allow_post_types']);
    
        $query = new WP_Query(array(
            'post_type'         => $allow_post_types,
            'post_status'       => array('future'),
            'posts_per_page'    => -1
        ));
      return $query;
    }
    

    /**
     * Calendar Main Ajax Operation
     * @method calender_ajax_request_php
     * @version 3.0.1
     */
    public function calender_ajax_request_php() {
        $nonce = $_POST['nonce'];
        if ( ! wp_verify_nonce( $nonce, 'wpscp-calendar-ajax-nonce' ) ) {
            die( __( 'Security check', 'wp-scheduled-posts' ) ); 
        }

        $post_status = '';
        if(!empty($_POST['post_status']) && $_POST['post_status'] != ''){
            $post_status = (($_POST['post_status'] == 'Scheduled') ? 'future' : 'draft');
        }

        $type = (isset($_POST['type']) ? $_POST['type'] : '');
        $dateStr = (isset($_POST['date']) ? $_POST['date'] : '');
        $postid = (isset($_POST['id']) ? $_POST['id'] : '');
        $postTitle = (isset($_POST['postTitle']) ? $_POST['postTitle'] : '');
        $postContent = (isset($_POST['postContent']) ? $_POST['postContent'] : '');

        if($dateStr != "Invalid Date"){
            $postdate = new DateTime(substr($dateStr, 0, 25));
            $postdateformat = $postdate->format('Y-m-d H:i:s');
            $postdate_gmt = ($postdateformat != "" ? gmdate('Y-m-d H:i:s',strtotime($postdateformat)) : '' );
        }
        
        /**
        * Post Status Change and Date modifired
        */
        if($type == 'drop'){ // draft post to future post
            $post_id = wp_update_post(array('ID'    =>  $postid, 'post_status'   => 'future','post_date' => $postdateformat, 'post_date_gmt' => $postdate_gmt, 'edit_date' => true));
            if(!is_wp_error($post_id)){
                print json_encode(query_posts( array( 'p' => $post_id ) ));
            }
        }else if($type == 'draftDrop'){ 
            $post_id = wp_update_post(array('ID'    =>  $postid, 'post_status'   => 'draft'));
            if(!is_wp_error($post_id)){
                print json_encode(query_posts( array( 'p' => $post_id ) ));
            }
        }else if($post_status == 'draft'){
            $post_id = wp_insert_post( array(
                'post_title'    => wp_strip_all_tags($postTitle),
                'post_content'  => $postContent,
                'post_status'   => 'draft',
                'post_author'   => get_current_user_id(),
            ) );
            if($post_id != 0){
                print json_encode(query_posts( array( 'p' => $post_id ) ));
            }
        } else if($type == 'addEvent'){	 
            // only works if update event is fired
            if($postid != ""){
                wp_update_post(array(	
                    'ID'    =>  $postid,
                    'post_title'    => wp_strip_all_tags($postTitle),
                    'post_content'  => $postContent,
                    'post_status'   => 'future',
                    'post_author'   => get_current_user_id(),
                    'post_date' => $postdateformat, 
                    'post_date_gmt' => $postdate_gmt, 
                    'edit_date' => true
                ));
                if(!is_wp_error($postid)){
                    print json_encode(query_posts( array( 'p' => $postid ) ));
                }
            }else {
                // only work new event created
                $post_id = wp_insert_post( array(
                    'post_title'    => wp_strip_all_tags($postTitle),
                    'post_content'  => $postContent,
                    'post_status'   => 'future',
                    'post_author'   => get_current_user_id(),
                    'post_date' => $postdateformat, 
                    'post_date_gmt' => $postdate_gmt, 
                    'edit_date' => true
                ) );
                if($post_id != 0){
                    print json_encode(query_posts( array( 'p' => $post_id ) ));
                }
            }
        }
        else if($post_status != 'draft') { // future post date modify date
            wp_update_post(array('ID'    =>  $postid, 'post_status'   => 'future','post_date' => $postdateformat, 'post_date_gmt' => $postdate_gmt, 'edit_date' => true));
        }


        exit();	
    }

    /**
     * Ajax Request for quick edit
     * @method quick_edit_action
     * @version 3.0.1
     */
    public function quick_edit_action() {
        $postId = (isset($_POST['id']) ? intval( $_POST['id'] ) : '');
        if($postId != 0){
            print json_encode(query_posts( array( 'p' => $postId ) ));
        }
        wp_die(); // this is required to terminate immediately and return a proper response
    }

    /**
     * Ajax Request for delete event action
     * @method delete_event_action
     * @version 3.0.1
     */
    public function delete_event_action() {
        $postId = (isset($_POST['id']) ? intval( $_POST['id'] ) : '');
        if($postId != ""){
            wp_delete_post($postId, true);
            print $postId;
        }
        wp_die(); // this is required to terminate immediately and return a proper response
    }
}
new WpScp_Calendar();