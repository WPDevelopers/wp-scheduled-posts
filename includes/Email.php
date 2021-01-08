<?php

namespace WPSP;

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
        $this->notify_author_is_sent_review = \WPSP\Helper::get_settings('notify_author_post_is_review');
        $this->notify_author_role_sent_review = \WPSP\Helper::get_settings('notify_author_post_review_by_role');
        $this->notify_author_username_sent_review = \WPSP\Helper::get_settings('notify_author_post_review_by_username');
        $this->notify_author_email_sent_review = \WPSP\Helper::get_settings('notify_author_post_review_by_email');
        $this->notify_author_post_is_rejected = \WPSP\Helper::get_settings('notify_author_post_is_rejected');
        $this->notify_author_post_is_schedule = \WPSP\Helper::get_settings('notify_author_post_is_scheduled');
        $this->notify_author_post_schedule_role = \WPSP\Helper::get_settings('notify_author_post_scheduled_by_role');
        $this->notify_author_post_schedule_username = \WPSP\Helper::get_settings('notify_author_post_scheduled_by_username');
        $this->notify_author_post_schedule_email = \WPSP\Helper::get_settings('notify_author_post_scheduled_by_email');
        $this->notify_author_schedule_post_is_publish = \WPSP\Helper::get_settings('notify_author_post_scheduled_to_publish');
        $this->notify_author_post_is_publish = \WPSP\Helper::get_settings('notify_author_post_is_publish');
        /**
         * Send Email Notification
         */
        add_action('transition_post_status', array($this, "transition_post_action"), 90, 3);
    }

    public function transition_post_action($new_status, $old_status, $post)
    {
        if (!function_exists('get_current_screen')) {
            require_once ABSPATH . '/wp-admin/includes/screen.php';
        }
        $current_screen = \get_current_screen();
        if (\is_object($current_screen) && \method_exists($current_screen, 'is_block_editor')) {
            if (isset($_POST['original_' . $post->post_type . '_status'])) {
                $old_status = $_POST['original_' . $post->post_type . '_status'];
            }
            $this->notify_status_change($new_status, $old_status, $post);
        } else {
            $this->notify_status_change($new_status, $old_status, $post);
        }
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
        if ($old_status == $new_status ||  get_transient('wpsp_email_is_send_flag') !== false) {
            return;
        }

        // pending review
        if ($this->notify_author_is_sent_review == 1 && $new_status == 'pending') {
            $reviewEmailList = \WPSP\Helper::email_notify_review_email_list();
            if (!empty($reviewEmailList) && is_array($reviewEmailList)) {
                $subject = 'New Post Pending Your Approval.';
                $message = 'Hello Moderator, <br/>A new post written by "%author%" titled "%title%" was submitted for your review. Click here %permalink%';
                $this->send_mail_to_custom_users(array_values($reviewEmailList), $post->ID, $subject, $message);
            }
        }
        // review is rejected
        else if ($this->notify_author_post_is_rejected == 1 && $new_status == 'trash') {
            // send mail for rejected
            $subject = 'Your Post titled "%title%" has been Rejected.';
            $message = 'Hello Author, <br/>You recently submitted a new article titled "%title%". Your Article is not well standard. Please, improve it and try again.';
            $this->send_mail_to_author($post->ID, $subject, $message);
        }
        // post is schedule
        else if ($this->notify_author_post_is_schedule == 1 && $new_status == 'future') {
            $futureEmailList = \WPSP\Helper::email_notify_schedule_email_list();
            if (!empty($futureEmailList) && is_array($futureEmailList)) {
                $subject = 'New post "%title%" is schedule on "%date%"';
                $message = 'Hello Moderator, <br/>Recently Moderator for your site scheduled a new post titled "%title%". The blog is scheduled for "%date%"';
                $this->send_mail_to_custom_users(array_values($futureEmailList), $post->ID, $subject, $message);
            }
            // send author 
            $subject = 'Your Post is scheduled for "%date%"';
            $message = 'Hello Author, <br/>Your blog titled "%title%" was scheduled for "%date%"';
            $this->send_mail_to_author($post->ID, $subject, $message);
        }
        // post is publish
        else if ($this->notify_author_schedule_post_is_publish == 1 && $new_status == 'publish') {
            // send mail for publish post
            $subject = 'Your post titled "%title%" is Live Now.';
            $message = 'Hello Author, <br/>Your blog titled "%title%" was published. Here is your published blog url: %permalink%';
            $this->send_mail_to_author($post->ID, $subject, $message);
        }
    }


    // publish status
    public function get_formatted_subject($post_title, $subject, $post_date)
    {
        $subject = str_replace("%title%", $post_title, $subject);
        $formatedTitle = str_replace("%date%", $post_date, $subject);
        return $formatedTitle;
    }
    public function get_formatted_body($post_title, $permalink, $message, $post_date, $author)
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
    public function send_mail_to_custom_users($email_id, $post_id, $subject, $message)
    {
        $post_title = wp_trim_words(get_the_title($post_id), 5, '...');
        $author = get_post_field('post_author', $post_id);
        $author_user_name = get_the_author_meta('user_login', $author);
        $post_date = get_the_time(get_option('date_format'), $post_id);
        $to = $email_id;

        $subject = $this->get_formatted_subject($post_title, $subject, $post_date);
        $body = $this->get_formatted_body($post_title, get_the_permalink($post_id), $message, $post_date, $author_user_name);
        $headers = array('Content-Type: text/html; charset=UTF-8');
        wp_mail($to, $subject, $body, $headers);
        set_transient('wpsp_email_is_send_flag', 'done', 10);
    }

    /**
     * Notify Author for only publish post
     * 
     */
    public function send_mail_to_author($post_id, $subject, $message)
    {
        // get author from post_id
        $author = get_post_field('post_author', $post_id);
        $author_email_address = get_the_author_meta('email', $author);
        $author_user_name = get_the_author_meta('user_login', $author);
        $post_title = wp_trim_words(get_the_title($post_id), 5, '...');
        $post_date = get_the_time(get_option('date_format'), $post_id);
        $to = $author_email_address;

        $subject = $this->get_formatted_subject($post_title, $subject, $post_date);
        $body = $this->get_formatted_body($post_title, get_the_permalink($post_id), $message, $post_date, $author_user_name);
        $headers = array('Content-Type: text/html; charset=UTF-8');
        wp_mail($to, $subject, $body, $headers);
        set_transient('wpsp_email_is_send_flag', 'done', 10);
    }
}
