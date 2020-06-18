<?php

use Abraham\TwitterOAuth\TwitterOAuth;

if (!class_exists('WPSP_Twitter')) {
    class WPSP_Twitter
    {
        use WpScp_Social;
        public function __construct()
        {
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
            $wpscp_options = get_option('wpscp_options');
            $is_republish_social_share = (isset($wpscp_options[0]['is_republish_social_share']) ? $wpscp_options[0]['is_republish_social_share'] : false);
            if ($is_republish_social_share) {
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
                wp_schedule_single_event(time(), 'wpsp_twitter_post', array($post_datas->ID));
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
            if (get_option('wpscp_twitter_content_source') === 'excerpt' && has_excerpt($post_details->ID)) {
                $desc = wp_strip_all_tags($post_details->post_excerpt);
            } else {
                $desc = wp_strip_all_tags($post_details->post_content);
            }


            $hashTags = (($this->getPostHasTags($post_id) != false) ? $this->getPostHasTags($post_id) : '');
            if (get_option('wpscp_twitter_template_category_tags_support') == 'yes') {
                $hashTags .= ' ' . $this->getPostHasCats($post_id);
            }

            $parameters = [];

            // change structure
            $twitter_template_structure = get_option('wpscp_twitter_template_structure');
            if (empty($twitter_template_structure) || $twitter_template_structure == '') {
                $twitter_template_structure = '{title}{content}{url}{tags}';
            }
            // limit
            $tweet_limit = (get_option('wpscp_twitter_tweet_limit') !== false ? get_option('wpscp_twitter_tweet_limit') : 280);

            // text formated
            $formatedText = $this->social_share_content_template_structure(
                $twitter_template_structure,
                $title,
                $desc,
                $post_link,
                $hashTags,
                $tweet_limit
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
        public function remote_post($app_id, $app_secret, $oauth_token, $oauth_token_secret, $post_id, $profile_key)
        {
            // check post is skip social sharing
            if (get_post_meta($post_id, '_wpscppro_dont_share_socialmedia', true) == 'on') {
                return;
            }

            $errorFlag = false;
            $response = '';

            try {
                $TwitterConnection = new TwitterOAuth($app_id, $app_secret, $oauth_token, $oauth_token_secret);

                $parameters = $this->get_share_content_args($post_id);

                // allow thumbnail will be share
                if (get_option('wpscp_twitter_template_thumbnail') == 'yes') {
                    $socialShareImage = get_post_meta($post_id, '_wpscppro_custom_social_share_image', true);
                    if ($socialShareImage != "") {
                        $thumbnail_src = wp_get_attachment_image_src($socialShareImage, 'full');
                        $featuredImage = $thumbnail_src[0];
                        $uploads = wp_upload_dir();
                        $file_path = str_replace($uploads['baseurl'], $uploads['basedir'], $featuredImage);
                        $media = $TwitterConnection->upload('media/upload', ['media' => $file_path]);
                    } else {
                        if (has_post_thumbnail($post_id)) {
                            $featuredImage = ((has_post_thumbnail($post_id)) ? get_the_post_thumbnail_url($post_id, 'full') : '');
                            $uploads = wp_upload_dir();
                            $file_path = str_replace($uploads['baseurl'], $uploads['basedir'], $featuredImage);
                            $media = $TwitterConnection->upload('media/upload', ['media' => $file_path]);
                        }
                    }
                    $parameters['media_ids'] = $media->media_id_string;
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
                    $response = __('Twitter Connection Problem. error code: ', 'wp-scheduled-posts-pro') . $TwitterConnection->getLastHttpCode();
                }
            } catch (\Exception $e) {
                // update option meta if token expire
                if ($e->getCode() == 89) {
                    $this->setOptionForSocialTokenExpired(WPSCP_TWITTER_OPTION_NAME, $profile_key);
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
         * Schedule Republish hook
         *
         */
        public function wpscp_republish_twitter_post($post_id)
        {
            // check post is skip social sharing
            if (get_post_meta($post_id, '_wpscppro_dont_share_socialmedia', true) == 'on') {
                return;
            }
            $multiProfile = get_option(WPSCP_TWITTER_OPTION_NAME);
            if (is_array($multiProfile) && count($multiProfile) > 0) {
                foreach ($multiProfile as $profile_key => $profile) {
                    // skip if status is false
                    if ($profile['status'] == false) {
                        continue;
                    }
                    // call social share method
                    $this->remote_post(
                        (isset($profile_key['app_id']) ? $profile_key['app_id'] : WPSCP_TWITTER_API_KEY),
                        (isset($profile_key['app_secret']) ? $profile_key['app_secret'] : WPSCP_TWITTER_API_SECRET_KEY),
                        $profile['oauth_token'],
                        $profile['oauth_token_secret'],
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
            $multiProfile = get_option(WPSCP_TWITTER_OPTION_NAME);
            if (is_array($multiProfile) && count($multiProfile) > 0) {
                foreach ($multiProfile as $profile_key => $profile) {
                    // skip if status is false
                    if ($profile['status'] == false) {
                        continue;
                    }
                    // call social share method
                    $this->remote_post(
                        (isset($profile_key['app_id']) ? $profile_key['app_id'] : WPSCP_TWITTER_API_KEY),
                        (isset($profile_key['app_secret']) ? $profile_key['app_secret'] : WPSCP_TWITTER_API_SECRET_KEY),
                        $profile['oauth_token'],
                        $profile['oauth_token_secret'],
                        $post_id,
                        $profile_key
                    );
                }
            }
        }

        // response collect and check all hook

        public function socialMediaInstantShare($app_id, $app_secret, $oauth_token, $oauth_token_secret, $post_id, $profile_key)
        {
            $response = $this->remote_post($app_id, $app_secret, $oauth_token, $oauth_token_secret, $post_id, $profile_key);
            if ($response['success'] == false) {
                wp_send_json_error($response['log']);
            } else {
                wp_send_json_success($response['log']);
            }
        }
    }
    $wpscptwitter = new WPSP_Twitter();
    $wpscptwitter->instance();
}
