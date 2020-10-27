<?php

namespace WPSP;

use WPSP\Social\Linkedin;

class Installer
{
    public function set_version()
    {
        if (!get_option('WPSP_VERSION')) {
            add_option('WPSP_VERSION', WPSP_VERSION);
        } else {
            update_option('WPSP_VERSION', WPSP_VERSION);
        }
    }
    public function set_settings()
    {
        \WPSP\Admin\Settings\Config::build_settings();
        if (class_exists('WPSP_PRO')) {
            \WPSP_PRO\Admin\Settings\Config::build_settings();
        }
        \WPSP\Admin\Settings\Config::set_default_settings_fields_data();
        if (get_transient(WPSP_SETTINGS_NAME) === false) {
            set_transient(WPSP_SETTINGS_NAME, \WPSP\Admin\Settings\Builder::load());
        } else {
            delete_transient(WPSP_SETTINGS_NAME);
            set_transient(WPSP_SETTINGS_NAME, \WPSP\Admin\Settings\Builder::load());
        }
    }



    /**
     * Short Description. (use period)
     *
     * Long Description.
     *
     * @since    1.0.0
     */
    public function run()
    {
        $this->set_settings();
    }

    public function migrate()
    {
        if (!get_option('wpsp_react_settings_migrate')) {
            // version update
            $this->set_version(); // previous version
            $old_settings = get_option('wpscp_options');
            // general settings
            $settings = json_decode(get_option(WPSP_SETTINGS_NAME), true);
            $settings->is_show_dashboard_widget = $old_settings['show_dashboard_widget'];
            $settings->is_show_sitewide_bar_posts = $old_settings['show_in_front_end_adminbar'];
            $settings->is_show_admin_bar_posts = $old_settings['show_in_adminbar'];
            $settings->allow_post_types = $old_settings['allow_post_types'];
            $settings->allow_user_by_role = $old_settings['allow_user_role'];
            $settings->allow_categories = $old_settings['allow_categories'];
            $settings->adminbar_list_structure_template = $old_settings['adminbar_item_template'];
            $settings->adminbar_list_structure_title_length = $old_settings['adminbar_title_length'];
            $settings->adminbar_list_structure_date_format = $old_settings['adminbar_date_format'];
            $settings->show_publish_post_button = $old_settings['prevent_future_post'];
            $settings->calendar_schedule_time = $old_settings['calendar_default_schedule_time'];
            $settings->is_republish_social_share = $old_settings['is_republish_social_share'];
            // email notify
            $settings->notify_author_post_is_review = get_option('wpscp_notify_author_is_sent_review');
            $settings->notify_author_post_review_by_role = get_option('wpscp_notify_author_role_sent_review');
            $settings->notify_author_post_review_by_username = get_option('wpscp_notify_author_username_sent_review');
            $settings->notify_author_post_review_by_email = get_option('wpscp_notify_author_email_sent_review');
            $settings->notify_author_post_is_rejected = get_option('wpscp_notify_author_post_is_rejected');
            $settings->notify_author_post_is_scheduled = get_option('wpscp_notify_author_post_is_schedule');
            $settings->notify_author_post_scheduled_by_role = get_option('wpscp_notify_author_post_schedule_role');
            $settings->notify_author_post_scheduled_by_username = get_option('wpscp_notify_author_post_schedule_username');
            $settings->notify_author_post_scheduled_by_email = get_option('wpscp_notify_author_post_schedule_email');
            $settings->notify_author_post_scheduled_to_publish = get_option('wpscp_notify_author_schedule_post_is_publish');
            $settings->notify_author_post_is_publish = get_option('wpscp_notify_author_post_is_publish');
            // social profile - facebook
            $facebook = get_option('wpscp_facebook_account');
            $facebook_status = get_option('wpsp_facebook_integration_status');
            $linkedin = get_option('wpscp_linkedin_account');
            $Linkedin_status = get_option('wpsp_linkedin_integration_status');
            $twitter = get_option('wpscp_twitter_account');
            $twitter_status = get_option('wpsp_twitter_integration_status');
            $pinterest = get_option('wpscp_pinterest_account');
            $pinterest_status = get_option('wpsp_pinterest_integration_status');
            if ($facebook) {
                $settings->facebook_profile_list = $facebook;
                $settings->facebook_profile_status = ($facebook_status == 'on' ? true : false);
            }
            if ($twitter) {
                $settings->twitter_profile_list = $twitter;
                $settings->twitter_profile_status = ($twitter_status == 'on' ? true : false);
            }
            if ($linkedin) {
                $settings->linkedin_profile_list = $linkedin;
                $settings->linkedin_profile_status = ($Linkedin_status == 'on' ? true : false);
            }
            if ($pinterest) {
                $settings->pinterest_profile_list = $pinterest;
                $settings->pinterest_profile_status = ($pinterest_status == 'on' ? true : false);
            }
            // social template - facebook
            $settings->social_templates->facebook[0]->is_show_meta = get_option('wpscp_pro_fb_meta_head_support');
            $settings->social_templates->facebook[1]->content_type = get_option('wpscp_pro_fb_content_type');
            $settings->social_templates->facebook[2]->is_category_as_tags = get_option('wpscp_pro_fb_template_category_tags_support');
            $settings->social_templates->facebook[3]->content_source = get_option('wpscp_pro_fb_content_source');
            $settings->social_templates->facebook[4]->template_structure = get_option('wpscp_pro_facebook_template_structure');
            $settings->social_templates->facebook[5]->status_limit = get_option('wpscp_pro_facebook_status_limit');
            // social template - twitter
            $settings->social_templates->twitter[0]->template_structure = get_option('wpscp_twitter_template_structure');
            $settings->social_templates->twitter[1]->is_category_as_tags = get_option('wpscp_twitter_template_category_tags_support');
            $settings->social_templates->twitter[2]->is_show_post_thumbnail = get_option('wpscp_twitter_template_thumbnail');
            $settings->social_templates->twitter[3]->content_source = get_option('wpscp_twitter_content_source');
            $settings->social_templates->twitter[4]->tweet_limit = get_option('wpscp_twitter_tweet_limit');
            // social template - linkedin
            $settings->social_templates->linkedin[0]->content_type = get_option('wpscp_pro_linkedin_content_type');
            $settings->social_templates->linkedin[1]->is_category_as_tags = get_option('wpscp_pro_liinkedin_template_category_tags_support');
            $settings->social_templates->linkedin[2]->content_source = get_option('wpscp_pro_linkedin_content_source');
            $settings->social_templates->linkedin[3]->template_structure = get_option('wpscp_pro_linkedin_template_structure');
            $settings->social_templates->linkedin[4]->status_limit = get_option('wpscp_pro_linkedin_status_limit');
            // social template - pinterest
            $pinterest = get_option('wpscp_pro_pinterest_template_settings');
            if ($pinterest !== false && is_array($pinterest)) {
                $settings->social_templates->pinterest[0]->is_set_image_link = $pinterest['add_image_link'];
                $settings->social_templates->pinterest[1]->is_category_as_tags = $pinterest['template_category_tags_support'];
                $settings->social_templates->pinterest[2]->content_source = $pinterest['content_source'];
                $settings->social_templates->pinterest[3]->template_structure = $pinterest['template_structure'];
                $settings->social_templates->pinterest[4]->note_limit = $pinterest['pin_note_limit'];
            }

            update_option(WPSP_SETTINGS_NAME, json_encode($settings));
            update_option('wpsp_react_settings_migrate', true);
        }
    }
}
