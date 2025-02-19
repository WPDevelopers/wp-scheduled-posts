<?php

namespace WPSP\Social;

use WPSP\Helper;
use WPSP\Traits\SocialHelper;

class Instagram
{
    use SocialHelper;
    private $is_show_meta;
    private $content_type;
    private $is_category_as_tags;
    private $content_source;
    private $template_structure;
    private $status_limit;
    private $post_share_limit;

    public function __construct()
    {
        $settings = \WPSP\Helper::get_settings('social_templates');
        $settings = json_decode(json_encode($settings->instagram), true);
        $this->is_show_meta = (isset($settings['is_show_meta']) ? $settings['is_show_meta'] : '');
        $this->content_type = (isset($settings['content_type']) ? $settings['content_type'] : '');
        $this->is_category_as_tags = (isset($settings['is_category_as_tags']) ? $settings['is_category_as_tags'] : '');
        $this->content_source = (isset($settings['content_source']) ? $settings['content_source'] : '');
        $this->template_structure = (isset($settings['template_structure']) ? $settings['template_structure'] : '{title}{content}{url}{tags}');
        $this->status_limit = (isset($settings['note_limit']) ? $settings['note_limit'] : 2100);
        $this->post_share_limit = (isset($settings['post_share_limit']) ? $settings['post_share_limit'] : 0);
    }

    public function instance()
    {
        // hook
        add_action('wpsp_publish_future_post', array($this, 'WpScp_Instagram_post_event'), 30, 1);
        add_action('WpScp_Instagram_post', array($this, 'WpScp_Instagram_post'), 15, 1);
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
            add_action('wpscp_pro_schedule_republish_share', array($this, 'wpscp_pro_republish_instagram_post'), 15, 1);
        }
    }

    /**
     * 'WpScp_Instagram_post_event' should be triggered by 'publish_future_post' action
     *
     */
    public function WpScp_Instagram_post_event($post_id)
    {
        //post data
        $post_details = $post_id;
        if ( !is_object( $post_id ) ){
            $post_details = get_post($post_id);
        }

        if ($post_details->post_status == 'publish') {
            // Schedule the actual event
            wp_schedule_single_event(time(), 'WpScp_Instagram_post', array($post_details->ID));
        }
    }

    /**
     * Saved Post Meta info
     */
    public function save_metabox_social_share_metabox($post_id, $response, $profile_key, $ID)
    {
        $meta_name = '__wpscppro_instagram_share_log';
        $count_meta_key = '__wpsp_instagram_share_count_'.$ID;
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

    /**
     * Build share content args
     * @param post_id
     * @return array
     * @since 2.5.1
     */
    public function get_share_content_args($post_id)
    {
        //post data
        $post_details = get_post($post_id);
        // post permalink
        $title = get_the_title($post_id);
        $post_link = esc_url(get_permalink($post_id));
        if ($this->content_source === 'excerpt' && has_excerpt($post_details->ID)) {
            $desc = wp_strip_all_tags($post_details->post_excerpt);
        } else {
            // $desc = wp_strip_all_tags($post_details->post_content);
            $desc = Helper::format_post_content($post_id);
            if( is_visual_composer_post($post_id) && class_exists('WPBMap') ){
                \WPBMap::addAllMappedShortcodes();
                $desc = Helper::strip_all_html_and_keep_single_breaks(do_shortcode($desc));
            }
        }


        $hashTags = (($this->getPostHasTags($post_id, 'instagram', $this->is_category_as_tags) != false) ? $this->getPostHasTags($post_id, 'instagram', $this->is_category_as_tags) : '');
        if ($this->is_category_as_tags == true) {
            $hashTags .= ' ' . $this->getPostHasCats($post_id);
        }
        $thumbnail_src = $this->get_image_url($post_details);
        // text formated
        $formatedText = $this->social_share_content_template_structure(
            $this->template_structure,
            $title,
            $desc,
            $post_link,
            $hashTags,
            $this->status_limit
        );
        $linkData = [
            'caption'   => $formatedText,
            'image_url' => !empty( $thumbnail_src ) ? $thumbnail_src : '',
        ];
        
        return $linkData;
    }
    

    public function get_image_url( $post ) {
        $socialShareImage = get_post_meta($post->ID, '_wpscppro_custom_social_share_image', true);
        if ($socialShareImage != "" && $socialShareImage != 0) {
            $thumbnail_src = wp_get_attachment_image_src($socialShareImage, 'full');
            if( !empty( $thumbnail_src[0] ) ) {
                return $thumbnail_src[0];
            }
        } else {
            if (has_post_thumbnail($post->ID)) { //the post does not have featured image, use a default image
                $thumbnail_src = wp_get_attachment_image_src(get_post_thumbnail_id($post->ID), 'large');
                return $thumbnail_src[0];
            }
        }
    }

    /**
     * Main share method
     * all logic witten here
     * @since 2.5.0
     * @return array
     */
    public function remote_post($app_id, $app_secret, $app_access_token, $type, $ID, $post_id, $profile_key, $force_share = false)
    {
        // get share count 
        $count_meta_key = '__wpsp_instagram_share_count_'.$ID;
        $dont_share     = get_post_meta($post_id, '_wpscppro_dont_share_socialmedia', true);

        // get social share type 
        $get_share_type =   get_post_meta($post_id, '_instagram_share_type', true);
        if( $get_share_type === 'custom' ) {
            $get_all_selected_profile     = get_post_meta($post_id, '_selected_social_profile', true);
            $check_profile_exists         = Helper::is_profile_exits( $ID, $get_all_selected_profile );
            if( !$check_profile_exists ) {
                return;
            }
        }

        // get long lived access token 
        $instagram = \WPSP\Helper::get_social_profile(WPSCP_INSTAGRAM_OPTION_NAME);
        $long_lived_access_token = !empty( $instagram[$profile_key]->long_lived_access_token ) ? $instagram[$profile_key]->long_lived_access_token : '';

        // check post is skip social sharing
        if (empty($app_id) || empty($app_secret) || $dont_share  == 'on' || $dont_share == 1 ) {
            return;
        }
        
        if( ( get_post_meta( $post_id, $count_meta_key, true ) ) && $this->post_share_limit != 0 && get_post_meta( $post_id, $count_meta_key, true ) >= $this->post_share_limit ) {
            return array(
                'success' => false,
                'log' => __('Your max share post limit has been executed!!','wp-scheduled-posts')
            );
        }
        
        if(get_post_meta($post_id, '_wpsp_is_instagram_share', true) == 'on' || $force_share) {
            $errorFlag = false;
            $response = '';

            $fb = new \Facebook\Facebook([
                'app_id'                => $app_id,
                'app_secret'            => $app_secret,
                'default_graph_version' => 'v6.0',
            ]);

            if( $type == 'profile' ) {
                try {
                    $linkData = $this->get_share_content_args($post_id);
                    $response = $fb->post('/' . $ID . '/media', $linkData, $long_lived_access_token);
                    $isError = $response->isError();
                    if ($isError == false) {
                        $graphNode = $response->getGraphNode();
                        $creation_id = $graphNode['id'];
                        $_params = [
                            'creation_id'   => $creation_id,
                        ];
                        $__response = $fb->post('/' . $ID . '/media_publish', $_params, $long_lived_access_token);
                        $__isError = $__response->isError();
                        if( $__isError == false ) {
                            $_graphNode = $__response->getGraphNode();
                            $shareInfo = array(
                                'share_id'     => $_graphNode['id'],
                                'publish_date' => time(),
                            );
                            // save shareinfo in metabox
                            $this->save_metabox_social_share_metabox($post_id, $shareInfo, $profile_key, $ID);
                            $errorFlag = true;
                            $response = $shareInfo;
                        }
                    }
                } catch (\Facebook\Exceptions\FacebookResponseException $e) {
                    $errorFlag = false;
                    $response = 'Graph returned an error: ' . $e->getMessage();
                } catch (\Facebook\Exceptions\FacebookSDKException $e) {
                    $errorFlag = false;
                    $response = 'SDK returned an error: ' . $e->getMessage();
                }
            }

            $linkData = $this->get_share_content_args($post_id);

            return array(
                'success' => $errorFlag,
                'log' => $response
            );
        }
        return;
    }

    
    /**
     * Schedule Republish social share hook
     * @since 2.5.0
     * @return void
     */
    public function wpscp_pro_republish_instagram_post($post_id)
    {
        $profiles = \WPSP\Helper::get_social_profile(WPSCP_INSTAGRAM_OPTION_NAME);
        if (is_array($profiles) && count($profiles) > 0) {
            foreach ($profiles as $profile_key => $profile) {
                // skip if status is false
                if ($profile->status == false) {
                    continue;
                }
                // call social share method
                $this->remote_post(
                    $profile->app_id,
                    $profile->app_secret,
                    $profile->access_token,
                    $profile->type,
                    $profile->id,
                    $post_id,
                    $profile_key,
                    true
                );
            }
        }
    }

    /**
     * Schedule Future post publish
     * @since 2.5.0
     * @return void
     */
    public function WpScp_Instagram_post($post_id)
    {
        $profiles = \WPSP\Helper::get_social_profile(WPSCP_INSTAGRAM_OPTION_NAME);
        if (is_array($profiles) && count($profiles) > 0) {
            foreach ($profiles as $profile_key => $profile) {
                if ($profile->status == false) {
                    continue;
                }
                // call social share method
                $this->remote_post(
                    $profile->app_id,
                    $profile->app_secret,
                    $profile->access_token,
                    $profile->type,
                    $profile->id,
                    $post_id,
                    $profile_key,
                    true
                );
            }
        }
    }


    /**
     * This method Call for Instant social share - it will be happend by ajax call
     * @since 2.5.0
     * @return ajax response
     */
    public function socialMediaInstantShare($app_id, $app_secret, $app_access_token, $type, $ID, $post_id, $profile_key,$is_share_on_publish)
    {
        $response = $this->remote_post($app_id, $app_secret, $app_access_token, $type, $ID, $post_id, $profile_key, true);
        if( $is_share_on_publish ) {
            return;
        }
        if ($response['success'] == false) {
            wp_send_json_error($response['log']);
        } else {
            wp_send_json_success($response['log']);
        }
    }
}
