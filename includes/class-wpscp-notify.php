<?php 
if(!class_exists('WpScp_Author_Notify')){
    class WpScp_Author_Notify{
        public $sender_email;
        public $sender_name;
        public $notify_author_is_publish;
        public $notify_author_role_sent_review;
        public $notify_author_username_sent_review;
        public $notify_author_email_sent_review;
        public $notify_author_post_is_rejected;
        public $notify_author_post_is_schedule;
        public $notify_author_post_schedule_role;
        public $notify_author_post_schedule_username;
        public $notify_author_post_schedule_email;
        public $notify_author_schedule_post_is_publish;
        public $notify_author_post_is_publish;
        public function __construct(){
            $this->set_local_variable_data_from_db();
            $this->change_sender_email();
            $this->send_email_notification();
        }
        public function set_local_variable_data_from_db(){
            $this->sender_email = get_option('wpscp_sender_email_address');
            $this->sender_name = get_option('wpscp_sender_full_name');
            $this->notify_author_is_sent_review = get_option('wpscp_notify_author_is_sent_review');
            $this->notify_author_role_sent_review = get_option('wpscp_notify_author_role_sent_review');
            $this->notify_author_username_sent_review = get_option('wpscp_notify_author_username_sent_review');
            $this->notify_author_email_sent_review = get_option('wpscp_notify_author_email_sent_review');
            $this->notify_author_post_is_rejected = get_option('wpscp_notify_author_post_is_rejected');
            $this->notify_author_post_is_schedule = get_option('wpscp_notify_author_post_is_schedule');
            $this->notify_author_post_schedule_role = get_option('wpscp_notify_author_post_schedule_role');
            $this->notify_author_post_schedule_username = get_option('wpscp_notify_author_post_schedule_username');
            $this->notify_author_post_schedule_email = get_option('wpscp_notify_author_post_schedule_email');
            $this->notify_author_schedule_post_is_publish = get_option('wpscp_notify_author_schedule_post_is_publish');
            $this->notify_author_post_is_publish = get_option('wpscp_notify_author_post_is_publish');
        }

        public function change_sender_email(){
            if($this->sender_email != "" && is_email($this->sender_email)){
                add_filter( 'wp_mail_from', array($this, 'set_sender_email') );
            }
            if($this->sender_name != ""){
                add_filter( 'wp_mail_from_name', array($this, 'set_sender_name') );
            }
        }
        public function set_sender_email($arg){
            return $this->sender_email;
        }
        public function set_sender_name($arg){
            return $this->sender_name;
        }
        /**
         * Send Email Notification
         */
        public function send_email_notification(){
            if($this->notify_author_schedule_post_is_publish == 1){
                add_action( 'publish_future_post', array( $this, 'notify_content_author' ), 30, 1 );
            }
            // add_filter("transition_post_status", array($this, "notify_status_change"), 10, 3);
            add_action( 'transition_post_status', array($this, "notify_status_change"), 10, 3 );
        }
        // publish status
        public function get_publish_post_notify_email_title($post_title, $subject){
            $formatedTitle = str_replace("%title%",$post_title, $subject);
            return $formatedTitle;
        }
        public function get_publish_post_notify_email_body($permalink, $message){
            $formatedBody = str_replace("%permalink%",$permalink, $message);
            return $formatedBody;
        }

        /**
         * Notify Spacific user from plugin setting page
         * @param array
         */
        public function notify_custom_user( $email_id, $post_id, $subject, $message ){
            $post_title = wp_trim_words(get_the_title( $post_id ), 5, '...');
            $to = $email_id;

            $subject = $this->get_publish_post_notify_email_title($post_title, $subject);
            $body = $this->get_publish_post_notify_email_body(get_the_permalink($post_id), $message);
            $headers = array('Content-Type: text/html; charset=UTF-8');
            wp_mail( $to, $subject, $body, $headers );
        }
        
        /**
         * Notify Author for only publish post
         * 
         */
        public function notify_content_author(  $post_id, $subject, $message ){
            // get author from post_id
            $author = get_post_field('post_author', $post_id );
            $author_email_address = get_the_author_meta('email', $author);
            $post_title = wp_trim_words(get_the_title( $post_id ), 5, '...');
            $to = $author_email_address;

            $subject = $this->get_publish_post_notify_email_title($post_title, $subject);
            $body = $this->get_publish_post_notify_email_body(get_the_permalink($post_id), $message);
            $headers = array('Content-Type: text/html; charset=UTF-8');
            wp_mail( $to, $subject, $body, $headers );
        }
        /**
         * Notify status change
         * the main function which controls posts status changes
         * @param string $new_status 
         * @param string $old_status
         * @param object $post
         * @return void
         */
        public function notify_status_change($new_status, $old_status, $post) {
            if ( $old_status == $new_status ){
                return;
            }
       
            global $current_user;
            $options = get_option("status_change_notifier");
            $contributor = get_userdata($post->post_author);
            $mail_headers = array("Content-Type"=> "text/html");

            // check current author id and post creator id is equal same
            if($current_user->ID!=$contributor->ID){
                // pending review
                if($this->notify_author_is_sent_review == 1 && $new_status == 'pending'){
                    $reviewEmailList = wpscp_email_notify_review_email_list();
                    if(!empty($reviewEmailList) && is_array($reviewEmailList)){
                        foreach($reviewEmailList as $email_id) {
                            $subject = 'A new post %title% is pending';
                            $message = 'A new post is pending. Please check it now: %permalink%';
                            $this->notify_custom_user( $email_id, $post->ID, $subject, $message);
                       }
                    }
                }
                // review is rejected
                else if($this->notify_author_post_is_rejected == 1 && $new_status == 'trash'){
                    // send mail for rejected
                    $subject = 'Your Submitted Post %title% has been Rejected.';
                    $message = 'Sorry to say that your submitted post is rejected. It was your rejected post url: %permalink%';
                    $this->notify_content_author($post->ID, $subject, $message);
                }
                // post is schedule
                else if($this->notify_author_post_is_schedule == 1 && $new_status == 'future'){
                    $futureEmailList = wpscp_email_notify_schedule_email_list();
                    if(!empty($futureEmailList) && is_array($futureEmailList)){
                        foreach($futureEmailList as $email_id) {
                            $subject = 'A new post %title% is schedule';
                            $message = 'A new post is schedule. Please check it now: %permalink%';
                            $this->notify_custom_user( $email_id, $post->ID, $subject, $message);
                       }
                    }
                }
                // post is publish
                else if($this->notify_author_schedule_post_is_publish == 1 && $new_status == 'publish'){
                    // send mail for publish post
                    $subject = 'Your Submitted Post %title% has been published.';
                    $message = 'A new post is Live on your website. Here is the link to your new post: %permalink%';
                    $this->notify_content_author($post->ID, $subject, $message);
                }
            }
        }
    }
    new WpScp_Author_Notify();
}