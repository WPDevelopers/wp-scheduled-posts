<?php

namespace WPSP;

class Migration {
    public static function version_3_to_4(){
        if (get_option('wpsp_data_migration_3_to_4') == false) {
            $settings = json_decode(get_option(WPSP_SETTINGS_NAME), true);;
            $old_settings = get_option('wpscp_options');
            // old version is installed
            if ($old_settings != false) {
                if (isset($old_settings['show_dashboard_widget'])) {
                    $settings['is_show_dashboard_widget'] = $old_settings['show_dashboard_widget'];
                }
                if (isset($old_settings['show_in_front_end_adminbar'])) {
                    $settings['is_show_sitewide_bar_posts'] = $old_settings['show_in_front_end_adminbar'];
                }
                if (isset($old_settings['show_in_adminbar'])) {
                    $settings['is_show_admin_bar_posts'] = $old_settings['show_in_adminbar'];
                }
                if (isset($old_settings['allow_post_types'])) {
                    $settings['allow_post_types'] = $old_settings['allow_post_types'];
                }
                if (isset($old_settings['allow_user_role'])) {
                    $settings['allow_user_by_role'] = $old_settings['allow_user_role'];
                }
                if (isset($old_settings['allow_categories'])) {
                    $settings['allow_categories'] = Helper::get_all_cats_id_to_slugs($old_settings['allow_categories']);
                }
                if (isset($old_settings['adminbar_item_template'])) {
                    $settings['adminbar_list_structure_template'] = $old_settings['adminbar_item_template'];
                }
                if (isset($old_settings['adminbar_title_length'])) {
                    $settings['adminbar_list_structure_title_length'] = $old_settings['adminbar_title_length'];
                }
                if (isset($old_settings['adminbar_date_format'])) {
                    $settings['adminbar_list_structure_date_format'] = $old_settings['adminbar_date_format'];
                }
                if (isset($old_settings['prevent_future_post'])) {
                    $settings['show_publish_post_button'] = $old_settings['prevent_future_post'];
                }
                if (isset($old_settings['calendar_default_schedule_time'])) {
                    $settings['calendar_schedule_time'] = $old_settings['calendar_default_schedule_time'];
                }
                if (isset($old_settings['is_republish_social_share'])) {
                    $settings['is_republish_social_share'] = $old_settings['is_republish_social_share'];
                }

                // email notify
                $wpscp_notify_author_is_sent_review = get_option('wpscp_notify_author_is_sent_review');
                if (!empty($wpscp_notify_author_is_sent_review)) {
                    $settings['notify_author_post_is_review'] = $wpscp_notify_author_is_sent_review;
                }
                $wpscp_notify_author_role_sent_review = get_option('wpscp_notify_author_role_sent_review');
                if (!empty($wpscp_notify_author_role_sent_review)) {
                    $settings['notify_author_post_review_by_role'] = $wpscp_notify_author_role_sent_review;
                }
                $wpscp_notify_author_username_sent_review = get_option('wpscp_notify_author_username_sent_review');
                if (!empty($wpscp_notify_author_username_sent_review)) {
                    $settings['notify_author_post_review_by_username'] = $wpscp_notify_author_username_sent_review;
                }
                $wpscp_notify_author_email_sent_review = get_option('wpscp_notify_author_email_sent_review');
                if (!empty($wpscp_notify_author_email_sent_review)) {
                    $settings['notify_author_post_review_by_email'] = $wpscp_notify_author_email_sent_review;
                }
                $wpscp_notify_author_post_is_rejected = get_option('wpscp_notify_author_post_is_rejected');
                if (!empty($wpscp_notify_author_post_is_rejected)) {
                    $settings['notify_author_post_is_rejected'] = $wpscp_notify_author_post_is_rejected;
                }
                $wpscp_notify_author_post_is_schedule = get_option('wpscp_notify_author_post_is_schedule');
                if (!empty($wpscp_notify_author_post_is_schedule)) {
                    $settings['notify_author_post_is_scheduled'] = $wpscp_notify_author_post_is_schedule;
                }
                $wpscp_notify_author_post_schedule_role = get_option('wpscp_notify_author_post_schedule_role');
                if (!empty($wpscp_notify_author_post_schedule_role)) {
                    $settings['notify_author_post_scheduled_by_role'] = $wpscp_notify_author_post_schedule_role;
                }
                $wpscp_notify_author_post_schedule_username = get_option('wpscp_notify_author_post_schedule_username');
                if (!empty($wpscp_notify_author_post_schedule_username)) {
                    $settings['notify_author_post_scheduled_by_username'] = $wpscp_notify_author_post_schedule_username;
                }
                $wpscp_notify_author_post_schedule_email = get_option('wpscp_notify_author_post_schedule_email');
                if (!empty($wpscp_notify_author_post_schedule_email)) {
                    $settings['notify_author_post_scheduled_by_email'] = $wpscp_notify_author_post_schedule_email;
                }
                $wpscp_notify_author_schedule_post_is_publish = get_option('wpscp_notify_author_schedule_post_is_publish');
                if (!empty($wpscp_notify_author_schedule_post_is_publish)) {
                    $settings['notify_author_post_scheduled_to_publish'] = $wpscp_notify_author_schedule_post_is_publish;
                }
                $wpscp_notify_author_post_is_publish = get_option('wpscp_notify_author_post_is_publish');
                if (!empty($wpscp_notify_author_post_is_publish)) {
                    $settings['notify_author_post_is_publish'] = $wpscp_notify_author_post_is_publish;
                }

                // social profile - facebook
                $facebook = get_option('wpscp_facebook_account');
                $facebook_status = get_option('wpsp_facebook_integration_status');
                $linkedin = get_option('wpscp_linkedin_account');
                $Linkedin_status = get_option('wpsp_linkedin_integration_status');
                $twitter = get_option('wpscp_twitter_account');
                $twitter_status = get_option('wpsp_twitter_integration_status');
                $pinterest = get_option('wpscp_pinterest_account');
                $pinterest_status = get_option('wpsp_pinterest_integration_status');
                if (!empty($facebook) && is_array($facebook)) {
                    $settings['facebook_profile_list'] = $facebook;
                    $settings['facebook_profile_status'] = ($facebook_status == 'on' ? true : false);
                }
                if (!empty($twitter) && is_array(($twitter))) {
                    $settings['twitter_profile_list'] = $twitter;
                    $settings['twitter_profile_status'] = ($twitter_status == 'on' ? true : false);
                }
                if (!empty($linkedin) && is_array($linkedin)) {
                    $settings['linkedin_profile_list'] = $linkedin;
                    $settings['linkedin_profile_status'] = ($Linkedin_status == 'on' ? true : false);
                }
                if (!empty($pinterest) && is_array($pinterest)) {
                    $settings['pinterest_profile_list'] = $pinterest;
                    $settings['pinterest_profile_status'] = ($pinterest_status == 'on' ? true : false);
                }
                // social template - facebook
                $wpscp_pro_fb_meta_head_support = get_option('wpscp_pro_fb_meta_head_support');
                if (!empty($wpscp_pro_fb_meta_head_support)) {
                    $settings['social_templates']['facebook'][0]['is_show_meta'] = $wpscp_pro_fb_meta_head_support;
                }
                $wpscp_pro_fb_content_type = get_option('wpscp_pro_fb_content_type');
                if (!empty($wpscp_pro_fb_content_type)) {
                    $settings['social_templates']['facebook'][1]['content_type'] = ($wpscp_pro_fb_content_type == 'statusandlink' ? 'statuswithlink' : $wpscp_pro_fb_content_type);
                }
                $wpscp_pro_fb_template_category_tags_support = get_option('wpscp_pro_fb_template_category_tags_support');
                if (!empty($wpscp_pro_fb_template_category_tags_support)) {
                    $settings['social_templates']['facebook'][2]['is_category_as_tags'] = $wpscp_pro_fb_template_category_tags_support;
                }
                $wpscp_pro_fb_content_source = get_option('wpscp_pro_fb_content_source');
                if (!empty($wpscp_pro_fb_content_source)) {
                    $settings['social_templates']['facebook'][3]['content_source'] = $wpscp_pro_fb_content_source;
                }
                $wpscp_pro_facebook_template_structure = get_option('wpscp_pro_facebook_template_structure');
                if (!empty($wpscp_pro_facebook_template_structure)) {
                    $settings['social_templates']['facebook'][4]['template_structure'] = $wpscp_pro_facebook_template_structure;
                }
                $wpscp_pro_facebook_status_limit = get_option('wpscp_pro_facebook_status_limit');
                if (!empty($wpscp_pro_facebook_status_limit)) {
                    $settings['social_templates']['facebook'][5]['status_limit'] = $wpscp_pro_facebook_status_limit;
                }
                // social template - twitter
                $wpscp_twitter_template_structure = get_option('wpscp_twitter_template_structure');
                if (!empty($wpscp_twitter_template_structure)) {
                    $settings['social_templates']['twitter'][0]['template_structure'] = $wpscp_twitter_template_structure;
                }
                $wpscp_twitter_template_category_tags_support = get_option('wpscp_twitter_template_category_tags_support');
                if (!empty($wpscp_twitter_template_category_tags_support)) {
                    $settings['social_templates']['twitter'][1]['is_category_as_tags'] = $wpscp_twitter_template_category_tags_support;
                }
                $wpscp_twitter_template_thumbnail = get_option('wpscp_twitter_template_thumbnail');
                if (!empty($wpscp_twitter_template_thumbnail)) {
                    $settings['social_templates']['twitter'][2]['is_show_post_thumbnail'] = $wpscp_twitter_template_thumbnail;
                }
                $wpscp_twitter_content_source = get_option('wpscp_twitter_content_source');
                if (!empty($wpscp_twitter_content_source)) {
                    $settings['social_templates']['twitter'][3]['content_source'] = $wpscp_twitter_content_source;
                }
                $wpscp_twitter_tweet_limit = get_option('wpscp_twitter_tweet_limit');
                if (!empty($wpscp_twitter_tweet_limit)) {
                    $settings['social_templates']['twitter'][4]['tweet_limit'] = $wpscp_twitter_tweet_limit;
                }

                // social template - linkedin
                $wpscp_pro_linkedin_content_type = get_option('wpscp_pro_linkedin_content_type');
                if (!empty($wpscp_pro_linkedin_content_type)) {
                    $settings['social_templates']['linkedin'][0]['content_type'] = $wpscp_pro_linkedin_content_type;
                }
                $wpscp_pro_liinkedin_template_category_tags_support = get_option('wpscp_pro_liinkedin_template_category_tags_support');
                if (!empty($wpscp_pro_liinkedin_template_category_tags_support)) {
                    $settings['social_templates']['linkedin'][1]['is_category_as_tags'] = $wpscp_pro_liinkedin_template_category_tags_support;
                }
                $wpscp_pro_linkedin_content_source = get_option('wpscp_pro_linkedin_content_source');
                if (!empty($wpscp_pro_linkedin_content_source)) {
                    $settings['social_templates']['linkedin'][2]['content_source'] = $wpscp_pro_linkedin_content_source;
                }
                $wpscp_pro_linkedin_template_structure = get_option('wpscp_pro_linkedin_template_structure');
                if (!empty($wpscp_pro_linkedin_template_structure)) {
                    $settings['social_templates']['linkedin'][3]['template_structure'] = $wpscp_pro_linkedin_template_structure;
                }
                $wpscp_pro_linkedin_status_limit = get_option('wpscp_pro_linkedin_status_limit');
                if (!empty($wpscp_pro_linkedin_status_limit)) {
                    $settings['social_templates']['linkedin'][4]['status_limit'] = $wpscp_pro_linkedin_status_limit;
                }
                // social template - pinterest
                $pinterest = get_option('wpscp_pro_pinterest_template_settings');
                if ($pinterest !== false && is_array($pinterest)) {
                    $settings['social_templates']['pinterest'][0]['is_set_image_link'] = $pinterest['add_image_link'];
                    $settings['social_templates']['pinterest'][1]['is_category_as_tags'] = $pinterest['template_category_tags_support'];
                    $settings['social_templates']['pinterest'][2]['content_source'] = $pinterest['content_source'];
                    $settings['social_templates']['pinterest'][3]['template_structure'] = $pinterest['template_structure'];
                    $settings['social_templates']['pinterest'][4]['note_limit'] = $pinterest['pin_note_limit'];
                }
                if (!empty($settings)) {
                    update_option(WPSP_SETTINGS_NAME, json_encode($settings));
                }
                update_option( 'wpsp_data_migration_3_to_4', true );
            }
        }
    }
    public static function allow_categories(){
        if (get_option('wpsp_data_migration_allow_categories') == false) {
            $settings = json_decode(get_option(WPSP_SETTINGS_NAME), true);
            if (!empty($settings['allow_categories'])) {
                foreach ($settings['allow_categories'] as $key => $value) {
                    if($value == 'all') continue;
                    $settings['allow_categories'][$key] = "category." . $value;
                }

                update_option(WPSP_SETTINGS_NAME, json_encode($settings));
                update_option( 'wpsp_data_migration_allow_categories', true );
            }
        }
    }
    public static function scheduled_post_social_share_meta_update(){
        global $wpdb;
        $post_types = \WPSP\Helper::get_settings('allow_post_types');
        if(is_array($post_types) && count($post_types) > 0){
            foreach($post_types as $post_type){
                $results = $wpdb->get_results( "SELECT ID, post_type FROM {$wpdb->prefix}posts WHERE post_type = '{$post_type}' AND post_status = 'future'", OBJECT );
                if(is_array($results) && count($results) > 0){
                    foreach($results as $result){
                        update_post_meta($result->ID, '_wpsp_is_facebook_share', 'on');
                        update_post_meta($result->ID, '_wpsp_is_twitter_share', 'on');
                        update_post_meta($result->ID, '_wpsp_is_linkedin_share', 'on');
                        update_post_meta($result->ID, '_wpsp_is_pinterest_share', 'on');
                    }
                }
            }
        }
    }
}