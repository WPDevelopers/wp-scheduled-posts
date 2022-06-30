<?php

namespace WPSP\Social;

use WPSP\Traits\SocialHelper;

class Pinterest
{
    use SocialHelper;
    private $is_set_image_link;
    private $is_category_as_tags;
    private $content_source;
    private $template_structure;
    private $note_limit;
    public function __construct()
    {
        $settings = \WPSP\Helper::get_settings('social_templates');
        $settings = json_decode(json_encode($settings->pinterest), true);
        $this->is_set_image_link = (isset($settings[0]['is_set_image_link']) ? $settings[0]['is_set_image_link'] : '');
        $this->is_category_as_tags = (isset($settings[1]['is_category_as_tags']) ? $settings[1]['is_category_as_tags'] : '');
        $this->content_source = (isset($settings[2]['content_source']) ? $settings[2]['content_source'] : '');
        $this->template_structure = (isset($settings[3]['template_structure']) ? $settings[3]['template_structure'] : '');
        $this->note_limit = (isset($settings[4]['note_limit']) ? $settings[4]['note_limit'] : 500);
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
        if (\WPSP\Helper::get_settings('is_republish_social_share')) {
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
    public function get_create_pin_args($post_id, $board_name, $board_name_key, $section_name, $instant_share = false)
    {
        $has_url = false;
        $post_details = get_post($post_id);
        $PostTitle = get_the_title($post_id);
        $PostPermalink = esc_url(get_permalink($post_id));;
        $board_type = get_post_meta($post_id, '_wpscppro_pinterestboardtype', true);
        $customThumbnailID = get_post_meta($post_id, '_wpscppro_custom_social_share_image', true);
        if ($customThumbnailID != "") {
            $customThumbnail = wp_get_attachment_image_src($customThumbnailID, 'full', false);
            $PostThumbnailURI = ($customThumbnail != false ? $customThumbnail[0] : '');
        } else {
            $PostThumbnailURI = get_the_post_thumbnail_url($post_id, 'full');
        }

        if(!$instant_share && $board_type === 'custom') {
            // overriding default board name from meta.
            $custom_board_name = get_post_meta($post_id, '_wpscppro_pinterest_board_name', true);
            if($custom_board_name && !empty($custom_board_name[$board_name_key])){
                $board_name = $custom_board_name[$board_name_key];
            }
            else{
                $board_name = '';
            }
            $custom_section_name = get_post_meta($post_id, '_wpscppro_pinterest_section_name', true);
            if($custom_section_name && !empty($custom_section_name[$board_name_key])){
                $section_name = $custom_section_name[$board_name_key];
            }
            else{
                $section_name = '';
            }
        }
        if(is_object($board_name)){
            $board_name = $board_name->value;
        }
        if(is_object($section_name)){
            $section_name = $section_name->value;
        }

        // tags
        $hashTags = (($this->getPostHasTags($post_id) != false) ? $this->getPostHasTags($post_id) : '');
        if ($this->is_category_as_tags == true) {
            $hashTags .= ' ' . $this->getPostHasCats($post_id);
        }

        // content
        if ($this->content_source === 'excerpt' && has_excerpt($post_details->ID)) {
            $desc = wp_strip_all_tags($post_details->post_excerpt);
        } else {
            $desc = wp_strip_all_tags($post_details->post_content);
        }
        if(strpos($this->template_structure, '{url}') !== false){
            $has_url = true;
            $this->template_structure = str_replace('{url}', '', $this->template_structure);
        }
        if(strpos($this->template_structure, '{title}') !== false){
            $this->template_structure = str_replace('{title}', '', $this->template_structure);
        }
        else{
            $PostTitle = '';
        }

        $note_content = $this->social_share_content_template_structure(
            $this->template_structure,
            '',
            $desc,
            '',
            $hashTags,
            $this->note_limit
        );
        // main arguments
        $pinterest_create_args = array(
            "title"       => html_entity_decode($PostTitle),
            "description" => substr($note_content, 0, $this->note_limit),
            'link'        => $has_url ? $PostPermalink : '',
            "board_id"    => $board_name,
        );
        if($section_name){
            $pinterest_create_args['board_section_id'] = $section_name;
        }
        if ($this->is_set_image_link === true && $PostThumbnailURI) {
            $pinterest_create_args['media_source'] = [
                'source_type' => 'image_url',
                'url'         => $PostThumbnailURI,
            ];
        }
        return $pinterest_create_args;
    }

    /**
     * Main share method
     * all logic witten here
     * @since 2.5.0
     * @return array
     */
    public function remote_post($app_id, $app_secret, $app_access_token, $post_id, $board_name, $section_name, $profile_key, $force_share = false, $instant_share = false)
    {
        // check post is skip social sharing
        if (empty($app_id) || empty($app_secret) || get_post_meta($post_id, '_wpscppro_dont_share_socialmedia', true) == 'on') {
            return;
        }

        if(get_post_meta($post_id, '_wpsp_is_pinterest_share', true) == 'on' || $force_share) {
            $errorFlag = false;
            $response = '';

            $pin_args = $this->get_create_pin_args($post_id, $board_name, md5($app_access_token), $section_name, $instant_share);

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
                $errorFlag = false;
                $response = $e->getMessage();
            }
            return array(
                'success' => $errorFlag,
                'log' => $response
            );
        }
        return;
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
                if ($profile->status == false) {
                    continue;
                }
                // share
                $this->remote_post(
                    $profile->app_id,
                    $profile->app_secret,
                    $profile->access_token,
                    $post_id,
                    $profile->default_board_name,
                    $profile->defaultSection,
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
                if ($profile->status == false) {
                    continue;
                }
                // share
                $this->remote_post(
                    $profile->app_id,
                    $profile->app_secret,
                    $profile->access_token,
                    $post_id,
                    $profile->default_board_name,
                    $profile->defaultSection,
                    $profile_key
                );
            }
        }
    }


    public function socialMediaInstantShare($app_id, $app_secret, $app_access_token, $post_id, $board_name, $section_name, $profile_key)
    {
        $response = $this->remote_post($app_id, $app_secret, $app_access_token, $post_id, $board_name, $section_name, $profile_key, true, true);
        if ($response['success'] == false) {
            wp_send_json_error($response['log']);
        } else {
            wp_send_json_success($response['log']);
        }
    }
}
