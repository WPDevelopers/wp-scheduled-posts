<?php

namespace WPSP\Social;

use WPSP\Helper;
use WPSP\Traits\SocialHelper;

class Medium
{
    use SocialHelper;
    private $is_show_meta;
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
        $settings = json_decode(json_encode($settings->medium), true);
        $this->is_show_meta = (isset($settings['is_show_meta']) ? $settings['is_show_meta'] : '');
        $this->content_type = (isset($settings['content_type']) ? $settings['content_type'] : '');
        $this->is_category_as_tags = (isset($settings['is_category_as_tags']) ? $settings['is_category_as_tags'] : '');
        $this->content_source = (isset($settings['content_source']) ? $settings['content_source'] : '');
        $this->template_structure = (isset($settings['template_structure']) ? $settings['template_structure'] : '{title}{content}{url}{tags}');
        $this->status_limit = (isset($settings['note_limit']) ? $settings['note_limit'] : 2100);
        $this->post_share_limit = (isset($settings['post_share_limit']) ? $settings['post_share_limit'] : 0);
        $this->remove_css_from_content = (isset($settings['remove_css_from_content']) ? $settings['remove_css_from_content'] : true);
    }

    public function instance()
    {
        // hook
        add_action('wpsp_publish_future_post', array($this, 'WpScp_Medium_post_event'), 30, 1);
        add_action('WpScp_Medium_post', array($this, 'WpScp_Medium_post'), 15, 1);
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
            add_action('wpscp_pro_schedule_republish_share', array($this, 'wpscp_pro_republish_medium_post'), 15, 1);
        }
    }

    /**
     * 'WpScp_Medium_post_event' should be triggered by 'publish_future_post' action
     *
     */
    public function WpScp_Medium_post_event($post_id)
    {
        //post data
        $post_details = $post_id;
        if ( !is_object( $post_id ) ){
            $post_details = get_post($post_id);
        }

        if ($post_details->post_status == 'publish') {
            // Schedule the actual event
            wp_schedule_single_event(time(), 'WpScp_Medium_post', array($post_details->ID));
        }
    }

    /**
     * Saved Post Meta info
     */
    public function save_metabox_social_share_metabox($post_id, $response, $profile_key, $ID)
    {
        $meta_name = '__wpscppro_medium_share_log';
        $count_meta_key = '__wpsp_medium_share_count_'.$ID;
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
     * @param int $post_id
     * @return array
     * @since 2.5.1
     */
    public function get_share_content_args($post_id)
    {
        $post = get_post($post_id);
        // Retrieve the post content and other necessary fields
        $title = get_the_title($post_id);
        $title = sanitize_text_field(html_entity_decode($title, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        if ($this->content_source === 'excerpt' && has_excerpt($post->ID)) {
            $content = wp_strip_all_tags($post->post_excerpt);
        } else {
            $content = apply_filters('the_content', $post->post_content);
            if( is_visual_composer_post($post_id) && class_exists('WPBMap') ){
                \WPBMap::addAllMappedShortcodes();
                $content = do_shortcode($content);
            }
        }

        $canonical_url = get_permalink($post_id);
        $tags = $this->getPostHasTags($post_id, 'medium', $this->is_category_as_tags) ?: '';
        if ($this->is_category_as_tags) {
            $categories = $this->getPostHasCats($post_id, 'medium');
            if (is_array($tags)) {
                $tags = is_array($categories) ? array_merge($tags, $categories) : $tags;
            } else {
                $tags = $categories;
            }
        }
        $tags = array_values($tags);
        $post_link = esc_url(get_permalink($post_id));

        // Retrieve custom social share image meta value
        $socialshareimage_id = get_post_meta($post_id, '_wpscppro_custom_social_share_image', true);
        $socialshareimage_url = '';

        if (!empty($socialshareimage_id)) {
            // Get the image URL from the meta value
            $socialshareimage_url = wp_get_attachment_url($socialshareimage_id);
        } else {
            // Fall back to the featured image if meta value is empty
            if (has_post_thumbnail($post_id)) {
                $socialshareimage_url = get_the_post_thumbnail_url($post_id, 'full');
            }else {
                $featured_image_id = Helper::get_featured_image_id_from_request();
                if( !empty( $featured_image_id ) ) {
                    $socialshareimage_url = wp_get_attachment_image_url($featured_image_id, 'full');
                }
            }
        }

        // Create image HTML
        $image_html = '';
        if (!empty($socialshareimage_url)) {
            $image_html = '<img src="' . esc_url($socialshareimage_url) . '" alt="' . esc_attr($title) . '" />';
        }

        // Format the text and prepend the image HTML
        $formatedText = $image_html . $this->social_share_content_template_structure(
            $this->template_structure,
            $title,
            $content,
            $post_link,
            '',
            $this->status_limit,
            null,
            'medium',
            $post_id,
            $this->current_profile_id ?? null
        );

        $data = [
            'title'         => $title,
            'contentFormat' => 'html',
            'content'       => '<h1>'.$title.'</h1>' . $formatedText,
            'canonicalUrl'  => $canonical_url,
            'tags'          => $tags,
            'publishStatus' => 'public'
        ];

        return $data;
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
    public function remote_post($app_id, $app_secret, $app_access_token, $type, $ID, $post_id, $profile_key, $force_share = false, $medium_id = '')
    {
        // Set current profile ID for custom template resolution
        $this->current_profile_id = $medium_id ?: $ID;

        // get share count
        $count_meta_key = '__wpsp_medium_share_count_'.$ID;
        $dont_share     = get_post_meta($post_id, '_wpscppro_dont_share_socialmedia', true);

        // get social share type 
        $get_share_type =   get_post_meta($post_id, '_medium_share_type', true);
        if( $get_share_type === 'custom' ) {
            $get_all_selected_profile     = get_post_meta($post_id, '_selected_social_profile', true);
            $check_profile_exists         = Helper::is_profile_exits( $medium_id, $get_all_selected_profile );
            if( !$check_profile_exists ) {
                return;
            }
        }

        // check post is skip social sharing
        if (empty($app_id) || $dont_share  == 'on' || $dont_share == 1 ) {
            return;
        }

        $is_enabled_custom_template = get_post_meta($post_id, '_wpsp_enable_custom_social_template', true);
        // if enabled custom template then check current social profile is selected or not
        if( $is_enabled_custom_template ) {
            $templates = get_post_meta($post_id, '_wpsp_custom_templates', true);
            $platform_data = isset($templates['medium']) ? $templates['medium'] : null;
            $profiles = is_array($platform_data) && isset($platform_data['profiles']) ? $platform_data['profiles'] : [];
            if ( is_array($profiles) && !in_array($this->current_profile_id, $profiles) ) {
                return;
            }
        }
        
        if( ( get_post_meta( $post_id, $count_meta_key, true ) ) && $this->post_share_limit != 0 && get_post_meta( $post_id, $count_meta_key, true ) >= $this->post_share_limit ) {
            return array(
                'success' => false,
                'log' => __('Your max share post limit has been executed!!','wp-scheduled-posts')
            );
        }
        
        if(get_post_meta($post_id, '_wpsp_is_medium_share', true) == 'on' || $force_share) {
            $errorFlag = false;
            $response = '';
            if( $type == 'profile' ) {
                try {
                    $ch = curl_init();
                    $headers = [
                        "Authorization: Bearer $app_id",
                        'Content-Type: application/json',
                        'Accept: application/json',
                        'Accept-Charset: utf-8',
                    ];
                    $data = $this->get_share_content_args($post_id);
                    curl_setopt($ch, CURLOPT_URL, "https://api.medium.com/v1/users/$ID/posts" );
                    curl_setopt($ch, CURLOPT_POST, 1);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                    $response = curl_exec($ch);
                    curl_close($ch);
                    $response = json_decode($response);
                    if( !empty($response) && !empty( $response->data->title ) ) {
                        $response = array(
                            'share_id' => (isset($response->data->id) ? $response->data->id : ''),
                            'publish_date' => time(),
                        );
                        $errorFlag = true;
                    }else if(!empty($response->errors[0]->message)){
						$response = $response->errors[0]->message;
					}else {
                        $errorFlag = false;
                        $response = 'Something went wrong..';
                    }
                } catch (\Facebook\Exceptions\FacebookResponseException $e) {
                    $errorFlag = false;
                    $response = 'Graph returned an error: ' . $e->getMessage();
                } catch (\Facebook\Exceptions\FacebookSDKException $e) {
                    $errorFlag = false;
                    $response = 'SDK returned an error: ' . $e->getMessage();
                }
            }
            return array(
                'success' => $errorFlag,
                'log'     => $response
            );
        }
        return;
    }

    
    /**
     * Schedule Republish social share hook
     * @since 2.5.0
     * @return void
     */
    public function wpscp_pro_republish_medium_post($post_id)
    {
        $profiles = \WPSP\Helper::get_social_profile(WPSCP_MEDIUM_OPTION_NAME);
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
                    $profile->__id,
                    $post_id,
                    $profile_key,
                    true,
                    $profile->id
                );
            }
        }
    }

    /**
     * Schedule Future post publish
     * @since 2.5.0
     * @return void
     */
    public function WpScp_Medium_post($post_id)
    {
        $profiles = \WPSP\Helper::get_social_profile(WPSCP_MEDIUM_OPTION_NAME);
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
                    $profile->__id,
                    $post_id,
                    $profile_key,
                    true,
                    $profile->id,
                );
            }
        }
    }


    /**
     * This method Call for Instant social share - it will be happend by ajax call
     * @since 2.5.0
     * @return ajax response
     */
    public function socialMediaInstantShare($app_id, $app_secret, $app_access_token, $type, $ID, $post_id, $profile_key, $medium_id = '', $is_share_on_publish = false)
    {
        $response = $this->remote_post($app_id, $app_secret, $app_access_token, $type, $ID, $post_id, $profile_key, true, $medium_id);
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
