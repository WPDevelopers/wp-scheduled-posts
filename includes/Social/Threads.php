<?php

namespace WPSP\Social;

use WPSP\Helper;
use WPSP\Traits\SocialHelper;

class Threads
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
        $settings = json_decode(json_encode($settings->threads), true);
        $this->is_category_as_tags = (isset($settings['is_category_as_tags']) ? $settings['is_category_as_tags'] : '');
        $this->content_source = (isset($settings['content_source']) ? $settings['content_source'] : '');
        $this->template_structure = (isset($settings['template_structure']) ? $settings['template_structure'] : '{title}{content}{url}{tags}');
        $this->status_limit = (isset($settings['note_limit']) ? $settings['note_limit'] : 490);
        $this->post_share_limit = (isset($settings['post_share_limit']) ? $settings['post_share_limit'] : 0);
    }

    public function instance()
    {
        // hook
        add_action('wpsp_publish_future_post', array($this, 'WpScp_Threads_post_event'), 30, 1);
        add_action('WpScp_Threads_post', array($this, 'WpScp_Threads_post'), 15, 1);
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
            add_action('wpscp_pro_schedule_republish_share', array($this, 'wpscp_pro_republish_threads_post'), 15, 1);
        }
    }

    /**
     * 'WpScp_Threads_post_event' should be triggered by 'publish_future_post' action
     *
     */
    public function WpScp_Threads_post_event($post_id)
    {
        //post data
        $post_details = $post_id;
        if ( !is_object( $post_id ) ){
            $post_details = get_post($post_id);
        }

        if ($post_details->post_status == 'publish') {
            // Schedule the actual event
            wp_schedule_single_event(time(), 'WpScp_Threads_post', array($post_details->ID));
        }
    }

    /**
     * Saved Post Meta info
     */
    public function save_metabox_social_share_metabox($post_id, $response, $profile_key, $ID)
    {
        $meta_name = '__wpscppro_threads_share_log';
        $count_meta_key = '__wpsp_threads_share_count_'.$ID;
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
            $desc = wp_strip_all_tags($post_details->post_content);
            if( is_visual_composer_post($post_id) && class_exists('WPBMap') ){
                \WPBMap::addAllMappedShortcodes();
                $desc = Helper::strip_all_html_and_keep_single_breaks(do_shortcode($desc));
            }
        }


        $hashTags = (($this->getPostHasTags($post_id, 'threads', $this->is_category_as_tags) != false) ? $this->getPostHasTags($post_id, 'threads', $this->is_category_as_tags) : '');
        if ($this->is_category_as_tags == true) {
            $hashTags .= ' ' . $this->getPostHasCats($post_id);
        }
        $formatedText = $this->social_share_content_template_structure(
            $this->template_structure,
            $title,
            $desc,
            $post_link,
            $hashTags,
            $this->status_limit
        );
        return $formatedText;
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
        $count_meta_key = '__wpsp_threads_share_count_'.$ID;
        $dont_share     = get_post_meta($post_id, '_wpscppro_dont_share_socialmedia', true);
        // get social share type 
        $get_share_type =   get_post_meta($post_id, '_threads_share_type', true);
        if( $get_share_type === 'custom' ) {
            $get_all_selected_profile     = get_post_meta($post_id, '_selected_social_profile', true);
            $check_profile_exists         = Helper::is_profile_exits( $ID, $get_all_selected_profile );
            if( !$check_profile_exists ) {
                return;
            }
        }

        // get long lived access token 
        $threads = \WPSP\Helper::get_social_profile(WPSCP_THREADS_OPTION_NAME);
        $app_access_token = !empty( $threads[$profile_key]->long_lived_access_token ) ? $threads[$profile_key]->long_lived_access_token : $app_access_token;

        // check post is skip social sharing
        if ( empty($app_id) || empty($app_secret) || $dont_share  == 'on' || $dont_share == 1 ) {
            return;
        }
        
        if( ( get_post_meta( $post_id, $count_meta_key, true ) ) && $this->post_share_limit != 0 && get_post_meta( $post_id, $count_meta_key, true ) >= $this->post_share_limit ) {
            return array(
                'success' => false,
                'log' => __('Your max share post limit has been executed!!','wp-scheduled-posts')
            );
        }
        
        if(get_post_meta($post_id, '_wpsp_is_threads_share', true) == 'on' || $force_share) {
            $errorFlag = false;
            $response = '';
            $text = $this->get_share_content_args($post_id);
            if( has_post_thumbnail($post_id) ) {
                $image_url = get_the_post_thumbnail_url($post_id, 'full');
            }else{
                $featured_image_id = Helper::get_featured_image_id_from_request();
                if( !empty( $featured_image_id ) ) {
                    $image_url = wp_get_attachment_image_url($featured_image_id, 'full');
                }
            }

            // Profile api
            if ($type === 'profile') {
                try {
                    $api_base_url = 'https://graph.threads.net/v1.0/' . $ID;
                    $api_threads_url = $api_base_url . '/threads';
                    $body = [
                        'media_type'   => 'IMAGE',
                        'image_url'    => $image_url,
                        'text'         => $text,
                        'access_token' => $app_access_token,
                    ];
                
                    // Common arguments for wp_remote_post
                    $common_args = [
                        'timeout' => 60,
                        'headers' => [
                            'Content-Type' => 'application/json',
                        ],
                    ];
                
                    // First API request
                    $args = array_merge($common_args, [
                        'body' => wp_json_encode($body),
                    ]);
                
                    $response = wp_remote_post($api_threads_url, $args);
                
                    if (is_wp_error($response)) {
                        throw new \Exception('Error in first API request: ' . $response->get_error_message());
                    }
                
                    $response_code = wp_remote_retrieve_response_code($response);
                    if ($response_code === 200) {
                        $response_body = json_decode(wp_remote_retrieve_body($response));
                        
                        if (empty($response_body->id)) {
                            throw new \Exception('Invalid response: Creation ID is missing.');
                        }
                
                        // Second API request to publish the thread
                        $api_publish_url = $api_base_url . '/threads_publish';
                        $_args = array_merge($common_args, [
                            'body' => wp_json_encode([
                                'creation_id'  => $response_body->id,
                                'access_token' => $app_access_token,
                            ]),
                        ]);
                
                        $publish_response = wp_remote_post($api_publish_url, $_args);
                
                        if (is_wp_error($publish_response)) {
                            throw new \Exception('Error in publish request: ' . $publish_response->get_error_message());
                        }
                
                        $publish_code = wp_remote_retrieve_response_code($publish_response);
                        if ($publish_code === 200) {
                            $publish_body = json_decode(wp_remote_retrieve_body($publish_response));
                
                            if (!empty($publish_body->id)) {
                                $errorFlag = true;
                                $response = [
                                    'share_id'     => $publish_body->id,
                                    'publish_date' => time(),
                                ];
                            } else {
                                throw new \Exception('Publishing failed: Response ID missing.');
                            }
                        } else {
                            throw new \Exception('Publishing request failed with status: ' . $publish_code);
                        }
                    } else {
                        throw new \Exception('Initial request failed with status: ' . $response_code);
                    }
                } catch (\Exception $e) {
                    $response = 'SDK returned an error: ' . $e->getMessage();
                }                
            }

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
    public function wpscp_pro_republish_threads_post($post_id)
    {
        $profiles = \WPSP\Helper::get_social_profile(WPSCP_THREADS_OPTION_NAME);
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
    public function WpScp_Threads_post($post_id)
    {
        $profiles = \WPSP\Helper::get_social_profile(WPSCP_THREADS_OPTION_NAME);
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
    public function socialMediaInstantShare($app_id, $app_secret, $app_access_token, $type, $ID, $post_id, $profile_key, $is_share_on_publish)
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
