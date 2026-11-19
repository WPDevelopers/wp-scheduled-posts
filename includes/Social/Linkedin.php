<?php

namespace WPSP\Social;

use WPSP\Helper;
use WPSP\Traits\SocialHelper;


class Linkedin
{
    use SocialHelper;
    private $content_type;
    private $is_category_as_tags;
    private $content_source;
    private $template_structure;
    private $status_limit;
    private $post_share_limit;
    private $remove_css_from_content;
    private $current_profile_id;

    public function __construct()
    {
        $settings = \WPSP\Helper::get_settings('social_templates');
        $settings = json_decode(json_encode($settings->linkedin), true);
        $this->content_type = (isset($settings['content_type']) ? $settings['content_type'] : '');
        $this->is_category_as_tags = (isset($settings['is_category_as_tags']) ? $settings['is_category_as_tags'] : '');
        $this->content_source = (isset($settings['content_source']) ? $settings['content_source'] : '');
        $this->template_structure = (isset($settings['template_structure']) ? $settings['template_structure'] : '{title}{content}{url}{tags}');
        $this->status_limit = (isset($settings['status_limit']) ? $settings['status_limit'] : 1300);
        $this->post_share_limit = (isset($settings['post_share_limit']) ? $settings['post_share_limit'] : 0);
        $this->remove_css_from_content = (isset($settings['remove_css_from_content']) ? $settings['remove_css_from_content'] : true);
    }

    public function instance()
    {
        // hook
        add_action('wpsp_publish_future_post', array($this, 'WpScp_linkedin_post_event'), 30, 1);
        add_action('WpScp_linkedin_post', array($this, 'WpScp_linkedin_post'), 15, 1);
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
            add_action('wpscp_pro_schedule_republish_share', array($this, 'wpscp_pro_republish_linkedin_post'), 15, 1);
        }
    }

    /**
     * 'WpScp_linkedin_post_event' should be triggered by 'publish_future_post' action
     *
     */
    public function WpScp_linkedin_post_event($post_id)
    {
        //post data
        $post_details = $post_id;
        if ( !is_object( $post_id ) ){
            $post_details = get_post($post_id);
        }

        if ($post_details->post_status == 'publish') {
            // Schedule the actual event
            wp_schedule_single_event(time(), 'WpScp_linkedin_post', array($post_details->ID));
        }
    }




    /**
     * Saved Post Meta info
     */
    public function save_metabox_social_share($post_id, $response, $profile_key, $ID)
    {
        $meta_name = '__wpscppro_linkedin_share_log';
        $count_meta_key = '__wpsp_linkedin_share_count_'.$ID;
        $oldData = get_post_meta($post_id, $meta_name, true);
        if ($oldData != "") {
            $oldData[$profile_key] = $response;
            $updateData = $oldData;
            update_post_meta($post_id, $meta_name, $updateData);
        } else {
            add_post_meta($post_id, $meta_name, array($profile_key => $response));
        }
        $old_share_count = get_post_meta( $post_id, $count_meta_key, true );
        if( $old_share_count != '' ) {
            update_post_meta($post_id, $count_meta_key, intval( $old_share_count ) + 1);
        }else{
            add_post_meta($post_id, $count_meta_key, 1);
        }
    }

    public function get_formatted_text($post_id)
    {
        $post_details = get_post($post_id);
        $title = get_the_title($post_id);
        $post_link = esc_url(get_permalink($post_id));;
        if ($this->content_source === 'excerpt' && has_excerpt($post_details->ID)) {
            $desc = get_the_excerpt($post_details);
        } else {
            $desc = wp_strip_all_tags($post_details->post_content);
            if( is_visual_composer_post($post_id) && class_exists('WPBMap') ){
                \WPBMap::addAllMappedShortcodes();
                $desc = Helper::strip_all_html_and_keep_single_breaks(do_shortcode($desc));
            }
        }

        if(class_exists('Elementor\Plugin')){
            $document = \Elementor\Plugin::$instance->documents->get($post_id);
            if($document && $document->is_built_with_elementor()){
                $desc = get_the_excerpt($post_details);
                $desc = Helper::strip_all_html_and_keep_single_breaks(do_shortcode($desc));
            }
        }
        $desc = preg_replace('/(\r?\n){2,}/', "\n", $desc);

        $hashTags = (($this->getPostHasTags($post_id, 'linkedin', $this->is_category_as_tags) != false) ? $this->getPostHasTags($post_id, 'linkedin', $this->is_category_as_tags) : '');
        if ($this->is_category_as_tags == true) {
            $hashTags .= ' ' . $this->getPostHasCats($post_id);
        }

        $formatedText = $this->social_share_content_template_structure(
            $this->template_structure,
            $this->filter_little_text($title),
            $this->filter_little_text($desc),
            $post_link,
            $hashTags,
            $this->status_limit,
            null,
            'linkedin',
            $post_id,
            $this->current_profile_id ?? null
        );
        return $formatedText;
    }

    function filter_little_text($text) {

        $filtered_text = preg_replace_callback('/([\(\)\{\}\[\]])|([@*<>|\\\\\_~])/m', function ($matches) {
            return '\\'.$matches[0];
        }, $text);

        return substr($filtered_text, 0, 4086);
    }

    /**
     * Main share method
     * all logic witten here
     * @since 2.5.0
     * @return array
     */
    public function remote_post($post_id, $profile_key, $force_share = false)
    {
        $profile     = \WPSP\Helper::get_profile('linkedin', $profile_key);
        $accessToken = \WPSP\Helper::get_access_token('linkedin', $profile_key);

        // Set current profile ID for custom template resolution
        $this->current_profile_id = isset($profile->__id) ? $profile->__id : $profile->id;
        // check post is skip social sharing
        // if (get_post_meta($post_id, '_wpscppro_dont_share_socialmedia', true) == 'on') {
        //     return;
        // }
        $dont_share     = get_post_meta($post_id, '_wpscppro_dont_share_socialmedia', true);

        // get social share type 
        $get_share_type =   get_post_meta($post_id, '_linkedin_share_type', true);
        if( $profile->type !== 'organization' && $get_share_type === 'custom' ) {
            $get_all_selected_profile     = Helper::get_selected_social_profiles($post_id);
            $check_profile_exists         = Helper::is_profile_exits( isset( $profile->__id ) ? $profile->__id : $profile->id , $get_all_selected_profile );
            if( !$check_profile_exists ) {
                return;
            }
        }

        $is_enabled_custom_template = get_post_meta($post_id, '_wpsp_enable_custom_social_template', true);
        // if enabled custom template then check current social profile is selected or not
        if( $is_enabled_custom_template ) {
            $templates = get_post_meta($post_id, '_wpsp_custom_templates', true);
            $platform_data = isset($templates['linkedin']) ? $templates['linkedin'] : null;
            $profiles = is_array($platform_data) && isset($platform_data['profiles']) ? $platform_data['profiles'] : [];
            if ( is_array($profiles) && !in_array($this->current_profile_id, $profiles) ) {
                return;
            }
        }

        // get social share type linkedin page 
        $get_share_type_page =   get_post_meta($post_id, '_linkedin_share_type_page', true);
        if( $profile->type === 'organization' && $get_share_type_page === 'custom' ) {
            $get_all_selected_profile     = Helper::get_selected_social_profiles($post_id);
            $check_profile_exists_for_page         = Helper::is_profile_exits( isset( $profile->__id ) ? $profile->__id : $profile->id , $get_all_selected_profile );
            if( !$check_profile_exists_for_page ) {
                return;
            }
        }
        
        if ($dont_share  == 'on' || $dont_share == 1 ) {
            return;
        }
        
        $count_meta_key = '__wpsp_linkedin_share_count_'.$profile->id;
        if( ( get_post_meta( $post_id, $count_meta_key, true ) ) && $this->post_share_limit != 0 && get_post_meta( $post_id, $count_meta_key, true ) >= $this->post_share_limit ) {
            return array(
                'success' => false,
                'log' => __('Your max share post limit has been executed!!','wp-scheduled-posts')
            );
        }
        if(get_post_meta($post_id, '_wpsp_is_linkedin_share', true) == 'on' || $force_share) {
            $errorFlag = false;
            $response = '';

            try {
                $linkedin = new \myPHPNotes\LinkedIn(
                    null,
                    '',
                    null,
                    null
                );
                $getPersonID      = $profile->id;
                $type             = isset($profile->type) ? $profile->type : 'person';
                $image_path       = '';
                $socialShareImage = get_post_meta($post_id, '_wpscppro_custom_social_share_image', true);
                if ($socialShareImage != "" && $socialShareImage != 0) {
                    $image_path = wp_get_original_image_path($socialShareImage);
                } else {
                    if (has_post_thumbnail($post_id)) { //the post does not have featured image, use a default image
                        $image_path = wp_get_original_image_path(get_post_thumbnail_id($post_id));
                    }else{
                        $featured_image_id = Helper::get_featured_image_id_from_request();
                        $image_path = wp_get_attachment_image_url($featured_image_id, 'full');
                    }
                }

                $results = "";
                if ($this->content_type == 'status') {
                    $formatedText = $this->get_formatted_text($post_id);
                    $results = $linkedin->linkedInTextPost($accessToken, $type, $getPersonID, $formatedText);
                } else if ($this->content_type == 'media' && $image_path) {
                    $post_details = get_post($post_id);
                    $title        = get_the_title($post_id);
                    $post_link    = get_permalink($post_id);
                    if ($this->content_source == 'excerpt' && has_excerpt($post_details->ID)) {
                        $desc = wp_strip_all_tags($post_details->post_excerpt);
                    } else {
                        $desc = wp_strip_all_tags($post_details->post_content);
                    }

                    $formatedText = $this->get_formatted_text($post_id);
                    $results = $linkedin->uploadImage( $accessToken, $type, $getPersonID, $image_path);
                    $imageUrn = isset($results['value']['image']) ? $results['value']['image'] : '';
                    $results = $linkedin->linkedInPhotoPost( $accessToken, $type, $getPersonID, $imageUrn, $this->filter_little_text($title), $formatedText );
                } else {
                    $post_details = get_post($post_id);
                    $title = get_the_title($post_id);
                    $formatedText = $this->get_formatted_text($post_id);
                    $post_link = get_permalink($post_id);
                    if ($this->content_source == 'excerpt' && has_excerpt($post_details->ID)) {
                        $desc = wp_strip_all_tags($post_details->post_excerpt);
                    } else {
                        $desc = wp_strip_all_tags($post_details->post_content);
                    }
                    $upload_url = '';
                    if($image_path){
                        $results = $linkedin->uploadImage( $accessToken, $type, $getPersonID, $image_path);
                        $upload_url = isset($results['value']['image']) ? $results['value']['image'] : '';
                    }
                    $results = $linkedin->linkedInLinkPost($accessToken, $type, $getPersonID, $formatedText, $post_link, $upload_url, html_entity_decode($this->filter_little_text($title)), html_entity_decode($this->filter_little_text($desc)));
                }
                $result = json_decode($results);
                // linkedin sdk has no Exception handler, that's why we handle it
                if (!empty($result) && property_exists($result, 'id') && $result->id != "") {
                    $shareInfo = array(
                        'share_id' => (isset($result->id) ? $result->id : ''),
                        'publish_date' => time(),
                    );
                    $this->save_metabox_social_share($post_id, $shareInfo, $profile_key, $getPersonID);
                    $errorFlag = true;
                    $response = $shareInfo;
                } else {
                    // Any answer without a post id is a failure; say why instead of leaving the log empty.
                    $errorFlag = false;
                    $response = self::error_message($results);
                }
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
     * Error text for a failed post: LinkedIn's own reason, and what to do for the common cases.
     *
     * @param mixed $raw Raw response body from the LinkedIn SDK (JSON string, other text, or false).
     * @return string
     */
    public static function error_message($raw)
    {
        if ($raw === false || $raw === null || $raw === '') {
            return __('LinkedIn sent no answer (network error or timeout). Please try sharing again.', 'wp-scheduled-posts');
        }

        $body   = is_string($raw) ? json_decode($raw) : null;
        $status = (is_object($body) && isset($body->status)) ? (int) $body->status : 0;
        $code   = (is_object($body) && !empty($body->code) && is_string($body->code)) ? $body->code : '';
        $reason = (is_object($body) && !empty($body->message) && is_string($body->message)) ? sanitize_text_field($body->message) : '';
        if ($reason !== '' && !preg_match('/[.!?]$/', $reason)) {
            $reason .= '.';
        }

        $message = __('LinkedIn did not publish the post.', 'wp-scheduled-posts');
        if ($status) {
            /* translators: %d: HTTP status code from LinkedIn. */
            $message .= ' ' . sprintf(__('Error code: %d.', 'wp-scheduled-posts'), $status);
        }
        $message .= ' ' . ($reason !== '' ? $reason : __('LinkedIn returned an unexpected response.', 'wp-scheduled-posts'));

        if (401 === $status || in_array($code, array('REVOKED_ACCESS_TOKEN', 'EXPIRED_ACCESS_TOKEN', 'INVALID_ACCESS_TOKEN'), true)) {
            $message .= ' ' . __('The LinkedIn connection has expired or was revoked. Reconnect this profile in SchedulePress > Settings > Social Profile, then share again.', 'wp-scheduled-posts');
        } elseif (403 === $status || 'ACCESS_DENIED' === $code) {
            $message .= ' ' . __('LinkedIn did not allow this post. Reconnect the profile; for a page, check that your LinkedIn account is an admin of it.', 'wp-scheduled-posts');
        } elseif (426 === $status || 'NONEXISTENT_VERSION' === $code) {
            $message .= ' ' . __('LinkedIn no longer supports the API version this plugin uses. Please update SchedulePress.', 'wp-scheduled-posts');
        } elseif (429 === $status) {
            $message .= ' ' . __('LinkedIn\'s daily limit was reached. Please try again later.', 'wp-scheduled-posts');
        } elseif (422 === $status && stripos($reason, 'duplicate') !== false) {
            $message .= ' ' . __('LinkedIn does not accept the same post twice. Change the text, then share again.', 'wp-scheduled-posts');
        }

        return $message;
    }

    /**
     * Schedule Republish Hook call back
     */
    public function wpscp_pro_republish_linkedin_post($post_id)
    {
        // check post is skip social sharing
        // if (get_post_meta($post_id, '_wpscppro_dont_share_socialmedia', true) == 'on') {
        //     return;
        // }
        $dont_share     = get_post_meta($post_id, '_wpscppro_dont_share_socialmedia', true);
        if ($dont_share  == 'on' || $dont_share == 1 ) {
            return;
        }
        $profiles = \WPSP\Helper::get_social_profile(WPSCP_LINKEDIN_OPTION_NAME);
        if (is_array($profiles) && count($profiles) > 0) {
            foreach ($profiles as $profile_key => $profile) {
                // skip if status is false
                if ($profile->status == false) {
                    continue;
                }
                // call social share method
                $this->remote_post(
                    $post_id,
                    $profile_key,
                    true
                );
            }
        }
    }
    /**
     * Schedule Publish Hook call back
     */
    public function WpScp_linkedin_post($post_id)
    {
        // check post is skip social sharing
        // if (get_post_meta($post_id, '_wpscppro_dont_share_socialmedia', true) == 'on') {
        //     return;
        // }
        $dont_share     = get_post_meta($post_id, '_wpscppro_dont_share_socialmedia', true);
        if ($dont_share  == 'on' || $dont_share == 1 ) {
            return;
        }
        $profiles = \WPSP\Helper::get_social_profile(WPSCP_LINKEDIN_OPTION_NAME);
        if (is_array($profiles) && count($profiles) > 0) {
            foreach ($profiles as $profile_key => $profile) {
                // skip if status is false
                if ($profile->status == false) {
                    continue;
                }
                // call social share method
                $this->remote_post(
                    $post_id,
                    $profile_key,
                    true
                );
            }
        }
    }



    public function socialMediaInstantShare($post_id, $profile_key, $is_share_on_publish)
    {
        $response = $this->remote_post($post_id, $profile_key, true);
        if( $is_share_on_publish ) {
            return;
        }
        // remote_post() bails with a bare `return;` on its skip conditions, so this can be
        // null. Without the guard the array access warns and the UI shows a blank error.
        if ( !is_array($response) ) {
            wp_send_json_error(__('Sharing was skipped for this profile. Check the post\'s social share settings.', 'wp-scheduled-posts'));
        }
        if ($response['success'] == false) {
            wp_send_json_error($response['log']);
        } else {
            wp_send_json_success($response['log']);
        }
    }


}
