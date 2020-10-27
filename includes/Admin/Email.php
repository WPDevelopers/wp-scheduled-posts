<?php

namespace WPSP\Admin;

class Email
{
    public $notify_author_is_sent_review;
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
    public function __construct()
    {
        $this->set_local_variable_data_from_db();
        $this->send_email_notification();
    }
    public function set_local_variable_data_from_db()
    {
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

    /**
     * Send Email Notification
     */
    public function send_email_notification()
    {
        if ($this->notify_author_schedule_post_is_publish == 1) {
            add_action('publish_future_post', array($this, 'notify_content_author'), 90, 1);
        }
        // add_filter("transition_post_status", array($this, "notify_status_change"), 10, 3);
        add_action('transition_post_status', array($this, "notify_status_change"), 90, 3);
    }
    // publish status
    public function get_publish_post_notify_email_title($post_title, $subject, $post_date)
    {
        $subject = str_replace("%title%", $post_title, $subject);
        $formatedTitle = str_replace("%date%", $post_date, $subject);
        return $formatedTitle;
    }
    public function get_publish_post_notify_email_body($post_title, $permalink, $message, $post_date, $author)
    {
        $message = str_replace("%title%", $post_title, $message);
        $message = str_replace("%permalink%", $permalink, $message);
        $message = str_replace("%date%", $post_date, $message);
        $formatedBody = str_replace("%author%", $author, $message);
        return $formatedBody;
    }

    /**
     * Notify Spacific user from plugin setting page
     * @param array
     */
    public function notify_custom_user($email_id, $post_id, $subject, $message)
    {
        $post_title = wp_trim_words(get_the_title($post_id), 5, '...');
        $author = get_post_field('post_author', $post_id);
        $author_user_name = get_the_author_meta('user_login', $author);
        $post_date = get_the_time(get_option('date_format'), $post_id);
        $to = $email_id;

        $subject = $this->get_publish_post_notify_email_title($post_title, $subject, $post_date);
        $body = $this->get_publish_post_notify_email_body($post_title, get_the_permalink($post_id), $message, $post_date, $author_user_name);
        $headers = array('Content-Type: text/html; charset=UTF-8');
        wp_mail($to, $subject, $body, $headers);
    }

    /**
     * Notify Author for only publish post
     * 
     */
    public function notify_content_author($post_id, $subject, $message)
    {
        // get author from post_id
        $author = get_post_field('post_author', $post_id);
        $author_email_address = get_the_author_meta('email', $author);
        $author_user_name = get_the_author_meta('user_login', $author);
        $post_title = wp_trim_words(get_the_title($post_id), 5, '...');
        $post_date = get_the_time(get_option('date_format'), $post_id);
        $to = $author_email_address;

        $subject = $this->get_publish_post_notify_email_title($post_title, $subject, $post_date);
        $body = $this->get_publish_post_notify_email_body($post_title, get_the_permalink($post_id), $message, $post_date, $author_user_name);
        $headers = array('Content-Type: text/html; charset=UTF-8');
        wp_mail($to, $subject, $body, $headers);
    }
    /**
     * Notify status change
     * the main function which controls posts status changes
     * @param string $new_status 
     * @param string $old_status
     * @param object $post
     * @return void
     */
    public function notify_status_change($new_status, $old_status, $post)
    {
        if ($old_status == $new_status) {
            return;
        }

        // pending review
        if ($this->notify_author_is_sent_review == 1 && $new_status == 'pending') {
            $reviewEmailList = \WPSP\Helper::email_notify_review_email_list();
            if (!empty($reviewEmailList) && is_array($reviewEmailList)) {
                $subject = 'New Post Pending Your Approval.';
                $message = 'Hello Moderator, <br/>A new post written by "%author%" titled "%title%" was submitted for your review. Click here %permalink%';
                $this->notify_custom_user(array_values($reviewEmailList), $post->ID, $subject, $message);
            }
        }
        // review is rejected
        else if ($this->notify_author_post_is_rejected == 1 && $new_status == 'trash') {
            // send mail for rejected
            $subject = 'Your Post titled "%title%" has been Rejected.';
            $message = 'Hello Author, <br/>You recently submitted a new article titled "%title%". Your Article is not well standard. Please, improve it and try again.';
            $this->notify_content_author($post->ID, $subject, $message);
        }
        // post is schedule
        else if ($this->notify_author_post_is_schedule == 1 && $new_status == 'future') {
            $futureEmailList = \WPSP\Helper::email_notify_schedule_email_list();
            if (!empty($futureEmailList) && is_array($futureEmailList)) {
                $subject = 'New post "%title%" is schedule on "%date%"';
                $message = 'Hello Moderator, <br/>Recently Moderator for your site scheduled a new post titled "%title%". The blog is scheduled for "%date%"';
                $this->notify_custom_user(array_values($futureEmailList), $post->ID, $subject, $message);
            }
            // send author 
            $subject = 'Your Post is scheduled for "%date%"';
            $message = 'Hello Author, <br/>Your blog titled "%title%" was scheduled for "%date%"';
            $this->notify_content_author($post->ID, $subject, $message);
        }
        // post is publish
        else if ($this->notify_author_schedule_post_is_publish == 1 && $new_status == 'publish') {
            // send mail for publish post
            $subject = 'Your post titled "%title%" is Live Now.';
            $message = 'Hello Author, <br/>Your blog titled "%title%" was published. Here is your published blog url: %permalink%';
            $this->notify_content_author($post->ID, $subject, $message);
        }
    }
}
