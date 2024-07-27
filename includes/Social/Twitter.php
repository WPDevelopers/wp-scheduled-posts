<?php

namespace WPSP\Social;

use WPSP\Helper;
use WPSP\Traits\SocialHelper;



class Twitter
{
    use SocialHelper;
    private $template_structure;
    private $is_category_as_tags;
    private $is_show_post_thumbnail;
    private $content_source;
    private $tweet_limit;
    private $post_share_limit;

    public function __construct()
    {
        $settings = \WPSP\Helper::get_settings('social_templates');
        $settings = json_decode(json_encode($settings->twitter), true);
        $this->template_structure = (isset($settings['template_structure']) ? $settings['template_structure'] : '{title}{content}{url}{tags}');
        $this->is_category_as_tags = (isset($settings['is_category_as_tags']) ? $settings['is_category_as_tags'] : '');
        $this->is_show_post_thumbnail = (isset($settings['is_show_post_thumbnail']) ? $settings['is_show_post_thumbnail'] : '');
        $this->content_source = (isset($settings['content_source']) ? $settings['content_source'] : '');
        $this->tweet_limit = (isset($settings['tweet_limit']) ? $settings['tweet_limit'] : 280);
        $this->post_share_limit = (isset($settings['post_share_limit']) ? $settings['post_share_limit'] : 0);    
        add_filter('wpsp_filter_social_content_tags', [ $this, 'wpsp_limit_twitter_tags' ], 10, 2);
    }

    public function wpsp_limit_twitter_tags( $tags, $platform ) {
        if( is_array( $tags ) && $platform == 'twitter' ) {
            return array_slice( $tags, 0, 4 );
        }
        return $tags;
    }

    public function instance()
    {
        // 'wpsp_twitter_post_event' runs when a Post is Published
        add_action('wpsp_publish_future_post', array($this, 'wpsp_twitter_post_event'), 20, 1);
        add_action('wpsp_twitter_post', array($this, 'wpsp_twitter_post'), 10, 1);
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
            add_action('wpscp_pro_schedule_republish_share', array($this, 'wpscp_republish_twitter_post'), 15, 1);
        }
    }

    /**
     * 'wpsp_twitter_post_event' should be triggered by 'publish_future_post' action
     *
     */
    public function wpsp_twitter_post_event($post_id)
    {
        //post data
        $post_details = $post_id;
        if ( !is_object( $post_id ) ){
            $post_details = get_post($post_id);
        }

        if ($post_details->post_status == 'publish') {
            // Schedule the actual event
            wp_schedule_single_event(time(), 'wpsp_twitter_post', array($post_details->ID));
        }
    }



    /**
     * Saved Post Meta info
     */
    public function save_metabox_social_share($post_id, $response, $profile_key, $ID)
    {
        $meta_name = '__wpscppro_twitter_share_log';
        $count_meta_key = '__wpsp_twitter_share_count_'.$ID;
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


    public function get_share_content_args($post_id)
    {
        //post data
        $post_details = get_post($post_id);
        $post_link = esc_url(get_permalink($post_id));
        $title = $post_details->post_title;
        if ($this->content_source === 'excerpt' && has_excerpt($post_details->ID)) {
            $desc = wp_strip_all_tags($post_details->post_excerpt);
        } else {
            $desc = wp_strip_all_tags($post_details->post_content);
        }


        $hashTags = (($this->getPostHasTags($post_id) != false) ? $this->getPostHasTags($post_id) : '');
        if ($this->is_category_as_tags == true) {
            $hashTags .= ' ' . $this->getPostHasCats($post_id);
        }

        $parameters = [];


        // text formated
        $formatedText = $this->social_share_content_template_structure(
            $this->template_structure,
            $title,
            $desc,
            $post_link,
            $hashTags,
            $this->tweet_limit - 5,
            null,
            'twitter'
        );
        $parameters['text'] = $formatedText;
        return $parameters;
    }


    /**
     * Main share method
     * all logic witten here
     * @since 2.5.0
     * @return array
     */
    public function remote_post($app_id, $app_secret, $oauth_token, $oauth_token_secret, $post_id, $profile_key, $force_share = false)
    {
        $profile     = \WPSP\Helper::get_profile('twitter', $profile_key);
        $count_meta_key = '__wpsp_twitter_share_count_'.$profile->id;

         // get social share type 
         $get_share_type =   get_post_meta($post_id, '_twitter_share_type', true);
         if( $get_share_type === 'custom' ) {
             $get_all_selected_profile     = get_post_meta($post_id, '_selected_social_profile', true);
             $check_profile_exists         = Helper::is_profile_exits( $profile->id, $get_all_selected_profile );
             if( !$check_profile_exists ) {
                 return;
             }
         }
        // check post is skip social sharing
        // if (empty($app_id) || empty($app_secret) || get_post_meta($post_id, '_wpscppro_dont_share_socialmedia', true) == 'on') {
        //     return;
        // }
        $dont_share     = get_post_meta($post_id, '_wpscppro_dont_share_socialmedia', true);
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

        if(get_post_meta($post_id, '_wpsp_is_twitter_share', true) == 'on' || $force_share) {
            $errorFlag = false;
            $response = '';

            try {
                $TwitterConnection = new \Abraham\TwitterOAuth\TwitterOAuth($app_id, $app_secret, $oauth_token, $oauth_token_secret);

                $parameters = $this->get_share_content_args($post_id);

                // allow thumbnail will be share
                if ($this->is_show_post_thumbnail == true) {
                    $uploads = wp_upload_dir();
                    $socialShareImage = get_post_meta($post_id, '_wpscppro_custom_social_share_image', true);
                    if (!empty($socialShareImage) && $socialShareImage != 0) {
                        // $thumbnail_src = wp_get_attachment_image_src($socialShareImage, 'full');
                        // $file_path = str_replace($uploads['baseurl'], $uploads['basedir'], $featuredImage);
                        $thumbnail_src = !empty( wp_get_attachment_metadata($socialShareImage)['file'] ) ? wp_get_attachment_metadata($socialShareImage)['file'] : '';
                        $file_path = !empty( $uploads['basedir'] ) ? esc_url( $uploads['basedir'] . '/' . $thumbnail_src ) : '';
                        $media = $TwitterConnection->upload('media/upload', ['media' => $file_path]);
                        $parameters['media'] = [
                            "media_ids" => [ $media->media_id_string ],
                        ];
                    } else {
                        if (has_post_thumbnail($post_id)) {
                            // $thumbnail_src = !empty( wp_get_attachment_metadata(get_post_thumbnail_id($post_id))['file'] ) ? wp_get_attachment_metadata(get_post_thumbnail_id($post_id))['file'] : '';
                            $featuredImage = ((has_post_thumbnail($post_id)) ? get_the_post_thumbnail_url($post_id, 'full') : '');
                            $file_path = str_replace($uploads['baseurl'], $uploads['basedir'], $featuredImage);
                            // $file_path = !empty( $uploads['basedir'] ) ? esc_url( $uploads['basedir'] . '/' . $thumbnail_src ) : '';
                            $media = $TwitterConnection->upload('media/upload', ['media' => $file_path]);
                            $parameters['media'] = [
                                "media_ids" => [ $media->media_id_string ],
                            ];
                        }
                    }
                }

                $TwitterConnection->setApiVersion(2);
                $result = $TwitterConnection->post('tweets', $parameters, true);
                if ($TwitterConnection->getLastHttpCode() == 201) {
                    $shareInfo = array(
                        'share_id' => $result->data->id,
                        'publish_date' => time(),
                    );
                    // save shareinfo in metabox
                    $this->save_metabox_social_share($post_id, $shareInfo, $profile_key, $profile->id);
                    $errorFlag = true;
                    $response = $shareInfo;
                } else {
                    $errorFlag = false;
                    $response = __('Twitter Connection Problem. error code: ', 'wp-scheduled-posts') . $TwitterConnection->getLastHttpCode();
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
     * Schedule Republish hook
     *
     */
    public function wpscp_republish_twitter_post($post_id)
    {
        // check post is skip social sharing
        // if (get_post_meta($post_id, '_wpscppro_dont_share_socialmedia', true) == 'on') {
        //     return;
        // }
        $dont_share     = get_post_meta($post_id, '_wpscppro_dont_share_socialmedia', true);
        // check post is skip social sharing
        if ($dont_share  == 'on' || $dont_share == 1 ) {
            return;
        }
        $profiles = \WPSP\Helper::get_social_profile(WPSCP_TWITTER_OPTION_NAME);
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
                    $profile->oauth_token,
                    $profile->oauth_token_secret,
                    $post_id,
                    $profile_key,
                    true
                );
            }
        }
    }
    /**
     *  Schedule Publish hook
     *
     */
    public function wpsp_twitter_post($post_id)
    {
        // check post is skip social sharing
        // if (get_post_meta($post_id, '_wpscppro_dont_share_socialmedia', true) == 'on') {
        //     return;
        // }
        $dont_share     = get_post_meta($post_id, '_wpscppro_dont_share_socialmedia', true);
        // check post is skip social sharing
        if ($dont_share  == 'on' || $dont_share == 1 ) {
            return;
        }
        $profiles = \WPSP\Helper::get_social_profile(WPSCP_TWITTER_OPTION_NAME);
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
                    $profile->oauth_token,
                    $profile->oauth_token_secret,
                    $post_id,
                    $profile_key,
                    true
                );
            }
        }
    }

    // response collect and check all hook

    public function socialMediaInstantShare($app_id, $app_secret, $oauth_token, $oauth_token_secret, $post_id, $profile_key)
    {
        $response = $this->remote_post($app_id, $app_secret, $oauth_token, $oauth_token_secret, $post_id, $profile_key, true);
        if ($response['success'] == false) {
            wp_send_json_error($response['log']);
        } else {
            wp_send_json_success($response['log']);
        }
    }
}
