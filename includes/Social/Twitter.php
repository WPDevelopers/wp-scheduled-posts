<?php

namespace WPSP\Social;

use WPSP\Traits\SocialHelper;



class Twitter
{
    use SocialHelper;
    private $template_structure;
    private $is_category_as_tags;
    private $is_show_post_thumbnail;
    private $content_source;
    private $tweet_limit;

    public function __construct()
    {
        $settings = \WPSP\Helper::get_settings('social_templates');
        $settings = json_decode(json_encode($settings->twitter), true);
        $this->template_structure = (isset($settings[0]['template_structure']) ? $settings[0]['template_structure'] : '{title}{content}{url}{tags}');
        $this->is_category_as_tags = (isset($settings[1]['is_category_as_tags']) ? $settings[1]['is_category_as_tags'] : '');
        $this->is_show_post_thumbnail = (isset($settings[2]['is_show_post_thumbnail']) ? $settings[2]['is_show_post_thumbnail'] : '');
        $this->content_source = (isset($settings[3]['content_source']) ? $settings[3]['content_source'] : '');
        $this->tweet_limit = (isset($settings[4]['tweet_limit']) ? $settings[4]['tweet_limit'] : 280);
    }

    public function instance()
    {
        // 'wpsp_twitter_post_event' runs when a Post is Published
        add_action('publish_future_post', array($this, 'wpsp_twitter_post_event'), 20, 1);
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
        $post_details = get_post($post_id);
        if ($post_details->post_status == 'publish') {
            // Schedule the actual event
            wp_schedule_single_event(time(), 'wpsp_twitter_post', array($post_id));
        }
    }



    /**
     * Saved Post Meta info
     */
    public function save_metabox_social_share($post_id, $response, $profile_key)
    {
        $meta_name = '__wpscppro_twitter_share_log';
        $oldData = get_post_meta($post_id, $meta_name, true);
        if ($oldData != "") {
            $oldData[$profile_key] = $response;
            $updateData = $oldData;
            update_post_meta($post_id, $meta_name, $updateData);
        } else {
            add_post_meta($post_id, $meta_name, array($profile_key => $response));
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
            $this->tweet_limit
        );
        $parameters['status'] = $formatedText;
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
        // check post is skip social sharing
        if (empty($app_id) || empty($app_secret) || get_post_meta($post_id, '_wpscppro_dont_share_socialmedia', true) == 'on') {
            return;
        }

        if(get_post_meta($post_id, '_wpsp_is_twitter_share', true) == 'on' || $force_share) {
            $errorFlag = false;
            $response = '';
    
            try {
                $TwitterConnection = new \Abraham\TwitterOAuth\TwitterOAuth($app_id, $app_secret, $oauth_token, $oauth_token_secret);
    
                $parameters = $this->get_share_content_args($post_id);
    
                // allow thumbnail will be share
                if ($this->is_show_post_thumbnail == true) {
                    $socialShareImage = get_post_meta($post_id, '_wpscppro_custom_social_share_image', true);
                    if ($socialShareImage != "") {
                        $thumbnail_src = wp_get_attachment_image_src($socialShareImage, 'full');
                        $featuredImage = $thumbnail_src[0];
                        $uploads = wp_upload_dir();
                        $file_path = str_replace($uploads['baseurl'], $uploads['basedir'], $featuredImage);
                        $media = $TwitterConnection->upload('media/upload', ['media' => $file_path]);
                        $parameters['media_ids'] = $media->media_id_string;
                    } else {
                        if (has_post_thumbnail($post_id)) {
                            $featuredImage = ((has_post_thumbnail($post_id)) ? get_the_post_thumbnail_url($post_id, 'full') : '');
                            $uploads = wp_upload_dir();
                            $file_path = str_replace($uploads['baseurl'], $uploads['basedir'], $featuredImage);
                            $media = $TwitterConnection->upload('media/upload', ['media' => $file_path]);
                            $parameters['media_ids'] = $media->media_id_string;
                        }
                    }
                }
    
                $result = $TwitterConnection->post('statuses/update', $parameters);
                if ($TwitterConnection->getLastHttpCode() == 200) {
                    $shareInfo = array(
                        'share_id' => $result->id,
                        'publish_date' => time(),
                    );
                    // save shareinfo in metabox
                    $this->save_metabox_social_share($post_id, $shareInfo, $profile_key);
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
        if (get_post_meta($post_id, '_wpscppro_dont_share_socialmedia', true) == 'on') {
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
                    $profile_key
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
        if (get_post_meta($post_id, '_wpscppro_dont_share_socialmedia', true) == 'on') {
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
                    $profile_key
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
