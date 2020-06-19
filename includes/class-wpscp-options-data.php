<?php
if (!class_exists('wpscp_options_data')) {
    class wpscp_options_data
    {
        public function __construct()
        {
            add_action('admin_post_wpscp_general_options_saved', array($this, 'wpscp_general_options_saved'));
            add_action('admin_post_wpscp_notify_email_options_saved', array($this, 'wpscp_notify_email_options_saved'));

            add_action('admin_post_twitter_template_structure', array($this, 'wpscp_twitter_template_data_saving'));
            add_action('admin_post_facebook_template_structure', array($this, 'wpscp_facebook_template_settings_data_saving'));
            add_action('admin_post_wpscppro_linkedin_template_structure', array($this, 'wpscp_linkedin_template_settings_data_saving'));
            add_action('admin_post_wpscppro_pinterest_template_structure', array($this, 'wpscp_pinterest_template_settings_data_saving'));
        }
        // default option saved
        public function wpscp_general_options_saved()
        {
            $nonce = $_POST['nonce_wpscp_general_options'];
            if (wp_verify_nonce($nonce, 'nonce_wpscp_general_options')) {
                $show_dashboard_widget = isset($_POST['show_dashboard_widget']) ? intval($_POST['show_dashboard_widget']) : 0;
                $show_in_front_end_adminbar = isset($_POST['show_in_front_end_adminbar']) ? intval($_POST['show_in_front_end_adminbar']) : 0;
                $allow_user_role = isset($_POST['allow_user_role']) ? $_POST['allow_user_role'] : '';
                $allow_post_types = isset($_POST['allow_post_types']) ? $_POST['allow_post_types'] : '';
                $allow_categories = isset($_POST['allow_categories']) ? $_POST['allow_categories'] : '';
                $adminbar_item_template = isset($_POST['adminbar_item_template']) ? trim($_POST['adminbar_item_template']) : '';
                $adminbar_title_length = isset($_POST['adminbar_title_length']) ? $_POST['adminbar_title_length'] : '';
                $adminbar_date_format = isset($_POST['adminbar_date_format']) ? trim($_POST['adminbar_date_format']) : '';
                $calendar_default_schedule_time = isset($_POST['calendar_default_schedule_time']) ? trim($_POST['calendar_default_schedule_time']) : '12:00 am';
                $options = array(
                    'show_dashboard_widget'          => $show_dashboard_widget,
                    'show_in_front_end_adminbar'     => $show_in_front_end_adminbar,
                    'show_in_adminbar'               => isset($_POST['show_in_adminbar']),
                    'allow_user_role'                => $allow_user_role,
                    'allow_post_types'               => $allow_post_types,
                    'allow_categories'               => $allow_categories,
                    'adminbar_item_template'         => $adminbar_item_template,
                    'adminbar_title_length'          => $adminbar_title_length,
                    'adminbar_date_format'           => $adminbar_date_format,
                    'prevent_future_post'            => isset($_POST['prevent_future_post']),
                    'calendar_default_schedule_time' => $calendar_default_schedule_time,
                );
                update_option('wpscp_options', apply_filters('wpscp_options', $options));
            }
            wp_redirect(admin_url('admin.php?page=wp-scheduled-posts#wpsp_gen'));
            exit;
        }
        // notify email option saved
        public function wpscp_notify_email_options_saved()
        {
            $nonce = $_POST['wpscp_notify_email_options'];
            if (wp_verify_nonce($nonce, 'nonce_wpscp_notify_email_options')) {
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

                update_option('wpscp_notify_author_is_sent_review', $notify_author_is_sent_review);
                update_option('wpscp_notify_author_role_sent_review', $notify_author_role_sent_review);
                update_option('wpscp_notify_author_username_sent_review', $notify_author_username_sent_review);
                update_option('wpscp_notify_author_email_sent_review', $notify_author_email_sent_review);
                update_option('wpscp_notify_author_post_is_rejected', $notify_author_post_is_rejected);
                update_option('wpscp_notify_author_post_is_schedule', $notify_author_post_is_schedule);
                update_option('wpscp_notify_author_post_schedule_role', $notify_author_post_schedule_role);
                update_option('wpscp_notify_author_post_schedule_username', $notify_author_post_schedule_username);
                update_option('wpscp_notify_author_post_schedule_email', $notify_author_post_schedule_email);
                update_option('wpscp_notify_author_schedule_post_is_publish', $notify_author_schedule_post_is_publish);
                update_option('wpscp_notify_author_post_is_publish', $notify_author_post_is_publish);
            }
            wp_redirect(admin_url('admin.php?page=wp-scheduled-posts#wpsp_email'));
            exit;
        }

        // twitter template save data
        public function wpscp_twitter_template_data_saving()
        {
            $wpscp_twitter_template_settings = sanitize_text_field(trim($_POST['wpscp_twitter_template_settings']));
            $wpscp_twitter_content_source = sanitize_text_field(trim($_POST['wpscp_twitter_content_source']));
            $wpscp_twitter_template_thumbnail = sanitize_text_field(trim($_POST['wpscp_twitter_template_thumbnail']));
            $wpscp_twitter_template_category_tags_support = sanitize_text_field(trim($_POST['wpscp_twitter_template_category_tags_support']));
            $wpscp_twitter_tweet_limit = sanitize_text_field(trim($_POST['wpscp_twitter_tweet_limit']));

            update_option('wpscp_twitter_content_source', $wpscp_twitter_content_source);
            update_option('wpscp_twitter_template_structure', $wpscp_twitter_template_settings);
            update_option('wpscp_twitter_template_thumbnail', $wpscp_twitter_template_thumbnail);
            update_option('wpscp_twitter_template_category_tags_support', $wpscp_twitter_template_category_tags_support);
            update_option('wpscp_twitter_tweet_limit', $wpscp_twitter_tweet_limit);
            wp_redirect(admin_url('admin.php?page=wp-scheduled-posts#wpsp_social_templates'));
            exit;
        }
        // facebook template data saving
        public function wpscp_facebook_template_settings_data_saving()
        {
            if (wp_verify_nonce($_POST['_wpnonce'], 'facebook_template_structure')) {
                $fb_head_support = sanitize_text_field(trim($_POST['wpscp_pro_fb_meta_head_support']));
                $fb_content_support = sanitize_text_field(trim($_POST['wpscp_pro_fb_content_type']));
                $fb_content_source = sanitize_text_field(trim($_POST['wpscp_pro_fb_content_source']));
                $fb_template_settings = sanitize_text_field(trim($_POST['wpscp_pro_facebook_template_structure']));
                $fb_template_category_tags_support = sanitize_text_field(trim($_POST['wpscp_pro_fb_template_category_tags_support']));
                $wpscp_pro_facebook_status_limit = sanitize_text_field(trim($_POST['wpscp_pro_facebook_status_limit']));

                update_option('wpscp_pro_fb_meta_head_support', $fb_head_support);
                update_option('wpscp_pro_fb_content_type', $fb_content_support);
                update_option('wpscp_pro_fb_content_source', $fb_content_source);
                update_option('wpscp_pro_facebook_template_structure', $fb_template_settings);
                update_option('wpscp_pro_fb_template_category_tags_support', $fb_template_category_tags_support);
                update_option('wpscp_pro_facebook_status_limit', $wpscp_pro_facebook_status_limit);
                wp_redirect(admin_url('admin.php?page=wp-scheduled-posts#wpsp_social_templates'));
                exit;
            }
        }
        // linkedin template data saving
        public function wpscp_linkedin_template_settings_data_saving()
        {
            if (wp_verify_nonce($_POST['_wpnonce'], 'linkedin_template_structure')) {
                $linkedin_content_support = sanitize_text_field(trim($_POST['wpscp_pro_linkedin_content_type']));
                $wpscp_pro_linkedin_content_source = sanitize_text_field(trim($_POST['wpscp_pro_linkedin_content_source']));
                $linkedin_template_settings = sanitize_text_field(trim($_POST['wpscp_pro_linkedin_template_structure']));
                $linkedin_template_category_tags_support = sanitize_text_field(trim($_POST['wpscp_pro_liinkedin_template_category_tags_support']));
                $wpscp_pro_linkedin_status_limit = sanitize_text_field(trim($_POST['wpscp_pro_linkedin_status_limit']));
                update_option('wpscp_pro_linkedin_content_type', $linkedin_content_support);
                update_option('wpscp_pro_linkedin_content_source', $wpscp_pro_linkedin_content_source);
                update_option('wpscp_pro_linkedin_template_structure', $linkedin_template_settings);
                update_option('wpscp_pro_liinkedin_template_category_tags_support', $linkedin_template_category_tags_support);
                update_option('wpscp_pro_linkedin_status_limit', $wpscp_pro_linkedin_status_limit);
                wp_redirect(admin_url('admin.php?page=wp-scheduled-posts#wpsp_social_templates'));
                exit;
            }
        }
        // pinterest template data saving
        public function wpscp_pinterest_template_settings_data_saving()
        {
            if (wp_verify_nonce($_POST['_wpnonce'], 'pinterest_template_structure')) {
                $add_image_link = sanitize_text_field(trim($_POST['add_image_link']));
                $template_category_tags_support = sanitize_text_field(trim($_POST['template_category_tags_support']));
                $template_structure = sanitize_text_field(trim($_POST['template_structure']));
                $pin_note_limit = sanitize_text_field(trim($_POST['pin_note_limit']));
                $content_source = sanitize_text_field(trim($_POST['content_source']));

                $new_option_data = array(
                    'add_image_link'                    => $add_image_link,
                    'template_category_tags_support'    => $template_category_tags_support,
                    'template_structure'                => $template_structure,
                    'pin_note_limit'                    => $pin_note_limit,
                    'content_source'                    => $content_source
                );

                $old_option_data = get_option('wpscp_pro_pinterest_template_settings');
                if ($old_option_data !== false && is_array($old_option_data) && count($old_option_data) > 0) {
                    $updated_data = wp_parse_args($new_option_data, $old_option_data);
                    update_option('wpscp_pro_pinterest_template_settings',  $updated_data);
                } else {
                    add_option('wpscp_pro_pinterest_template_settings', $new_option_data);
                }
                wp_redirect(admin_url('admin.php?page=wp-scheduled-posts#wpsp_social_templates'));
                exit;
            }
        }
    }
    new wpscp_options_data();
}
