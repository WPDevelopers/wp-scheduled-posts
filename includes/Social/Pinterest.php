<?php

namespace WPSP\Social;

use WPSP\Traits\SocialHelper;

class Pinterest
{
    use SocialHelper;
    public function __construct()
    {
    }

    public function instance()
    {
        // hook
        add_action('publish_future_post', array($this, 'WpScp_pinterest_post_event'), 30, 1);
        add_action('WpScp_pinterest_post', array($this, 'WpScp_pinterest_post'), 15, 2);
        // republish hook
        $this->schedule_republish_social_share_hook();
    }
    /**
     * Schedule Republish Social Share
     * @since 2.5.0
     * @return hooks
     */
    public function schedule_republish_social_share_hook()
    {
        $wpscp_options = get_option('wpscp_options');
        $is_republish_social_share = (isset($wpscp_options[0]['is_republish_social_share']) ? $wpscp_options[0]['is_republish_social_share'] : false);
        if ($is_republish_social_share) {
            add_action('wpscp_pro_schedule_republish_share', array($this, 'wpscp_pro_republish_pinterest_post'), 15, 1);
        }
    }
    /**
     * 'WpScp_pinterest_post_event' should be triggered by 'publish_future_post' action
     *
     */
    public function WpScp_pinterest_post_event($post_id)
    {
        //post data
        $post_details = get_post($post_id);
        if ($post_details->post_status == 'publish') {
            // Schedule the actual event
            wp_schedule_single_event(time(), 'WpScp_pinterest_post', array($post_id));
        }
    }


    /**
     * Saved Post Meta info
     * 
     */
    public function save_metabox_social_share_metabox($post_id, $response)
    {
        if (get_post_meta($post_id, '__wpscppro_social_share_pinterest', true) != "") {
            $root_meta_data = get_post_meta($post_id, '__wpscppro_social_share_pinterest', true);
            // new meta value push
            array_push($root_meta_data, $response);
            update_post_meta($post_id, '__wpscppro_social_share_pinterest', $root_meta_data);
        } else {
            $root_meta_data = array();
            array_push($root_meta_data, $response);
            add_post_meta($post_id, '__wpscppro_social_share_pinterest', $root_meta_data);
        }
    }
    /**
     * Build formated content for share
     * @param post_id, board_name
     * @return array
     * @since 2.5.1
     */
    public function get_create_pin_args($post_id, $board_name)
    {
        $post_details = get_post($post_id);
        $PostTitle = get_the_title($post_id);
        $PostPermalink = esc_url(get_permalink($post_id));;
        $customThumbnailID = get_post_meta($post_id, '_wpscppro_custom_social_share_image', true);
        if ($customThumbnailID != "") {
            $customThumbnail = wp_get_attachment_image_src($customThumbnailID, 'full', false);
            $PostThumbnailURI = ($customThumbnail != false ? $customThumbnail[0] : '');
        } else {
            $PostThumbnailURI = get_the_post_thumbnail_url($post_id, 'full');
        }

        // board name
        $custom_board_name = get_post_meta($post_id, '_wpscppro_pinterest_board_name', true);
        if ($custom_board_name != "" && !empty($custom_board_name)) {
            $board_name = $custom_board_name;
        }

        // generate pin note content
        $template_settings = get_option('WpScp_pinterest_template_settings');
        $template_content_strucutre = (isset($template_settings['template_structure']) ? $template_settings['template_structure'] : '{title}');
        $template_category_tags_support = (isset($template_settings['template_category_tags_support']) ? $template_settings['template_category_tags_support'] : '');
        $add_image_link = (isset($template_settings['add_image_link']) ? $template_settings['add_image_link'] : 'yes');
        $pin_note_limit = (isset($template_settings['pin_note_limit']) ? $template_settings['pin_note_limit'] : 500);
        $content_source = (isset($template_settings['content_source']) ? $template_settings['content_source'] : '');
        // tags
        $hashTags = (($this->getPostHasTags($post_id) != false) ? $this->getPostHasTags($post_id) : '');
        if ($template_category_tags_support == 'yes') {
            $hashTags .= ' ' . $this->getPostHasCats($post_id);
        }

        // content
        if ($content_source === 'excerpt' && has_excerpt($post_details->ID)) {
            $desc = wp_strip_all_tags($post_details->post_excerpt);
        } else {
            $desc = wp_strip_all_tags($post_details->post_content);
        }

        $note_content = $this->social_share_content_template_structure(
            $template_content_strucutre,
            $PostTitle,
            $desc,
            $PostPermalink,
            $hashTags,
            $pin_note_limit
        );
        // main arguments
        $pinterest_create_args = array(
            "note" => substr($note_content, 0, 140),
            'link' => $PostPermalink,
            "board" => $board_name,
        );
        if ($add_image_link === 'yes') {
            $pinterest_create_args['image_url'] = $PostThumbnailURI;
        }
        return $pinterest_create_args;
    }

    /**
     * Main share method
     * all logic witten here
     * @since 2.5.0
     * @return array
     */
    public function remote_post($app_id, $app_secret, $app_access_token, $post_id, $board_name, $profile_key)
    {
        // check post is skip social sharing
        if (get_post_meta($post_id, '_wpscppro_dont_share_socialmedia', true) == 'on') {
            return;
        }

        $errorFlag = false;
        $response = '';

        $pin_args = $this->get_create_pin_args($post_id, $board_name);

        try {
            $pinterest = new \DirkGroenen\Pinterest\Pinterest($app_id, $app_secret);
            $pinterest->auth->setOAuthToken($app_access_token);
            $results = $pinterest->pins->create($pin_args);
            if ($results != "") {
                $shareInfo = array(
                    'share_id' => $results->id,
                    'publish_date' => time(),
                );
                $this->save_metabox_social_share_metabox($post_id, $shareInfo, $profile_key);
            }
            $errorFlag = true;
            $response = $results;
        } catch (\Exception $e) {
            // update option meta if token expire
            if ($e->getCode() == 401) {
                $this->setOptionForSocialTokenExpired(WPSCP_PINTEREST_OPTION_NAME, $profile_key);
            }
            $errorFlag = false;
            $response = $e->getMessage();
        }
        return array(
            'success' => $errorFlag,
            'log' => $response
        );
    }

    /**
     * Schedule Republish
     */
    public function wpscp_pro_republish_pinterest_post($post_id)
    {
        // check post is skip social sharing
        if (get_post_meta($post_id, '_wpscppro_dont_share_socialmedia', true) == 'on') {
            return;
        }

        $profiles = \WPSP\Helper::get_social_profile(WPSCP_PINTEREST_OPTION_NAME);
        if (is_array($profiles) && count($profiles) > 0) {
            foreach ($profiles as $profile_key => $profile) {
                // skip if status is false
                if ($profile['status'] == false) {
                    continue;
                }
                // share
                $this->remote_post(
                    (isset($profile['app_id']) ? $profile['app_id'] : WPSCP_PINTEREST_APP_ID),
                    (isset($profile['app_secret']) ? $profile['app_secret'] : WPSCP_PINTEREST_APP_SECRET),
                    $profile['access_token'],
                    $post_id,
                    (isset($profile['default_board_name']) ? $profile['default_board_name'] : ''),
                    $profile_key
                );
            }
        }
    }
    /**
     * Schedule Publish
     */
    public function WpScp_pinterest_post($post_id)
    {
        // check post is skip social sharing
        if (get_post_meta($post_id, '_wpscppro_dont_share_socialmedia', true) == 'on') {
            return;
        }

        $profiles = \WPSP\Helper::get_social_profile(WPSCP_PINTEREST_OPTION_NAME);
        if (is_array($profiles) && count($profiles) > 0) {
            foreach ($profiles as $profile_key => $profile) {
                // skip if status is false
                if ($profile['status'] == false) {
                    continue;
                }
                // share
                $this->remote_post(
                    (isset($profile['app_id']) ? $profile['app_id'] : WPSCP_PINTEREST_APP_ID),
                    (isset($profile['app_secret']) ? $profile['app_secret'] : WPSCP_PINTEREST_APP_SECRET),
                    $profile['access_token'],
                    $post_id,
                    (isset($profile['default_board_name']) ? $profile['default_board_name'] : ''),
                    $profile_key
                );
            }
        }
    }


    public function socialMediaInstantShare($app_id, $app_secret, $app_access_token, $post_id, $board_name, $profile_key)
    {
        $response = $this->remote_post($app_id, $app_secret, $app_access_token, $post_id, $board_name, $profile_key);
        if ($response['success'] == false) {
            wp_send_json_error($response['log']);
        } else {
            wp_send_json_success($response['log']);
        }
    }
}
