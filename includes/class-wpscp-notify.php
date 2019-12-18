<?php 
if(!class_exists('WpScp_Author_Notify')){
    class WpScp_Author_Notify{
        public function __construct(){
           $this->send_email_notification();
        }
        public function send_email_notification(){
            $wpscp_options = wpscp_get_options();
            $publish_schedule_post_notify = $wpscp_options['publish_schedule_post_notify'];
            if($publish_schedule_post_notify == 1){
                add_action( 'publish_future_post', array( $this, 'notify_content_author' ), 30, 1 );
            }
        }
        public function notify_content_author(  $post_id ){
            // get author from post_id
            $author = get_post_field('post_author', $post_id );
            $author_email_address = get_the_author_meta('email', $author);
            $post_title = wp_trim_words(get_the_title( $post_id ), 5, '...');
    
    
            $to = $author_email_address;
            $subject = 'Your Scheduled Post "' . $post_title . '" has been published';
            $body = 'A new post is Live on your website. Here iss the link to your new post: '. get_the_permalink($post_id);
            $headers = array('Content-Type: text/html; charset=UTF-8');
            
            $is_mail_send = false;
            $is_mail_send = wp_mail( $to, $subject, $body, $headers );
            update_post_meta($post_id, '_wpscp_is_author_notify', $is_mail_send);
        }
    }
    new WpScp_Author_Notify();
}