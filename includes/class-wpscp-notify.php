<?php 
if(!class_exists('WpScp_Author_Notify')){
    class WpScp_Author_Notify{
        public $sender_email;
        public $sender_name;
        public $notify_author_is_publish;
        public $notify_author_is_future_to_publish;
        public $notify_author_is_publish_to_draft;
        public $notify_author_publish_email_template_title;
        public $notify_author_publish_email_template_body;
        public $notify_author_draft_email_template_title;
        public $notify_author_draft_email_template_body;
        public function __construct(){
            $this->set_local_variable_data_from_db();
            $this->change_sender_email();
            $this->send_email_notification();
        }
        public function set_local_variable_data_from_db(){
            $this->sender_email = get_option('wpscp_sender_email_address');
            $this->sender_name = get_option('wpscp_sender_full_name');
            $this->notify_author_is_publish = get_option('wpscp_notify_author_is_approve');
            $this->notify_author_is_future_to_publish = get_option('wpscp_notify_author_is_future_to_publish');
            $this->notify_author_is_publish_to_draft = get_option('wpscp_notify_author_is_publish_to_draft');
            $this->notify_author_publish_email_template_title = get_option('wpscp_email_publish_template_title');
            $this->notify_author_publish_email_template_body = get_option('wpscp_email_publish_template_body');
            $this->notify_author_draft_email_template_title = get_option('wpscp_email_draft_template_title');
            $this->notify_author_draft_email_template_body = get_option('wpscp_email_draft_template_body');
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
        // publish status
        public function get_publish_post_notify_email_title($post_title){
            $subject = (($this->notify_author_publish_email_template_title !="") ? $this->notify_author_publish_email_template_title : 'Your Scheduled Post %title% has been published.');
            $formatedTitle = str_replace("%title%",$post_title, $subject);
            return $formatedTitle;
        }
        public function get_publish_post_notify_email_body($permalink){
            $body = (($this->notify_author_publish_email_template_body !="") ? $this->notify_author_publish_email_template_body : 'A new post is Live on your website. Here is the link to your new post: %permalink%');
            $formatedBody = str_replace("%permalink%",$permalink, $body);
            return $formatedBody;
        }
        // draft status
        public function get_draft_post_notify_email_title($post_title){
            $subject = (($this->notify_author_draft_email_template_title !="") ? $this->notify_author_draft_email_template_title : 'Your Publish Post %title% move to draft.');
            $formatedTitle = str_replace("%title%",$post_title, $subject);
            return $formatedTitle;
        }
        public function get_draft_post_notify_email_body($permalink){
            $body = (($this->notify_author_draft_email_template_body !="") ? $this->notify_author_draft_email_template_body : 'Here is the link to your draft post: %permalink%');
            $formatedBody = str_replace("%permalink%",$permalink, $body);
            return $formatedBody;
        }
        // hook
        public function send_email_notification(){
            if($this->notify_author_is_future_to_publish == 1){
                add_action( 'publish_future_post', array( $this, 'notify_content_author' ), 30, 1 );
            }
            add_filter("transition_post_status", array($this, "notify_status_change"), 10, 3);
        }
        public function notify_content_author(  $post_id ){
            // get author from post_id
            $author = get_post_field('post_author', $post_id );
            $author_email_address = get_the_author_meta('email', $author);
            $post_title = wp_trim_words(get_the_title( $post_id ), 5, '...');
            $to = $author_email_address;
            $subject = $this->get_publish_post_notify_email_title($post_title);
            $body = $this->get_publish_post_notify_email_body(get_the_permalink($post_id));
            $headers = array('Content-Type: text/html; charset=UTF-8');
            $is_mail_send = false;
            $is_mail_send = wp_mail( $to, $subject, $body, $headers );
            update_post_meta($post_id, '_wpscp_is_author_notify', $is_mail_send);
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
            global $current_user;
            
            $options = get_option("status_change_notifier");
            $contributor = get_userdata($post->post_author);
            $mail_headers = array("Content-Type"=> "text/html");
            
            // send notification for pending to publish
            if($this->notify_author_is_publish == true && $new_status == 'publish' && $current_user->ID!=$contributor->ID){
                $this->notify_content_author($post->ID);
            }
            elseif($this->notify_author_is_publish_to_draft == true && $new_status == 'draft' && $current_user->ID!=$contributor->ID) {
                // get author from post_id
                $author = get_post_field('post_author', $post->ID );
                $author_email_address = get_the_author_meta('email', $author);
                $post_title = wp_trim_words(get_the_title( $post->ID ), 5, '...');
                $to = $author_email_address;
                $subject = $this->get_draft_post_notify_email_title($post_title);
                $body = $this->get_draft_post_notify_email_body(get_the_permalink($post->ID));
                $headers = array('Content-Type: text/html; charset=UTF-8');
                $is_mail_send = false;
                $is_mail_send = wp_mail( $to, $subject, $body, $headers );
                update_post_meta($post->ID, '_wpscp_is_author_notify', $is_mail_send);
            }
        }
    }
    new WpScp_Author_Notify();
}