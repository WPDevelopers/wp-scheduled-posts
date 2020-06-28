<?php

use myPHPNotes\LinkedIn;

if (!class_exists('WpScp_linkedin')) {
    class WpScp_linkedin
    {
        use WpScp_Social;
        public function __construct()
        {
        }

        public function instance()
        {
            // hook
            add_action('publish_future_post', array($this, 'WpScp_linkedin_post_event'), 30, 1);
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
            $wpscp_options = get_option('wpscp_options');
            $is_republish_social_share = (isset($wpscp_options[0]['is_republish_social_share']) ? $wpscp_options[0]['is_republish_social_share'] : false);
            if ($is_republish_social_share) {
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
            $post_details = get_post($post_id);
            if ($post_details->post_status == 'publish') {
                // Schedule the actual event
                wp_schedule_single_event(time(), 'WpScp_linkedin_post', array($post_id));
            }
        }




        /**
         * Saved Post Meta info
         */
        public function save_metabox_social_share($post_id, $response, $profile_key)
        {
            $meta_name = '__wpscppro_linkedin_share_log';
            $oldData = get_post_meta($post_id, $meta_name, true);
            if ($oldData != "") {
                $oldData[$profile_key] = $response;
                $updateData = $oldData;
                update_post_meta($post_id, $meta_name, $updateData);
            } else {
                add_post_meta($post_id, $meta_name, array($profile_key => $response));
            }
        }

        public function get_formatted_text($post_id)
        {
            $post_details = get_post($post_id);
            $title = get_the_title($post_id);
            $post_link = esc_url(get_permalink($post_id));;
            if (get_option('WpScp_linkedin_content_source') === 'excerpt' && has_excerpt($post_details->ID)) {
                $desc = wp_strip_all_tags($post_details->post_excerpt);
            } else {
                $desc = wp_strip_all_tags($post_details->post_content);
            }
            $hashTags = (($this->getPostHasTags($post_id) != false) ? $this->getPostHasTags($post_id) : '');
            if (get_option('wpscp_pro_liinkedin_template_category_tags_support') == 'yes') {
                $hashTags .= ' ' . $this->getPostHasCats($post_id);
            }

            // status limit
            $status_limit = (get_option('WpScp_linkedin_status_limit') !== false ? get_option('WpScp_linkedin_status_limit') : 1300);

            // change structure
            $linkedin_template_structure = get_option('WpScp_linkedin_template_structure');
            if (empty($linkedin_template_structure) || $linkedin_template_structure == '') {
                $linkedin_template_structure = '{title}{content}{url}{tags}';
            }
            $formatedText = $this->social_share_content_template_structure(
                $linkedin_template_structure,
                $title,
                $desc,
                $post_link,
                $hashTags,
                $status_limit
            );
            return $formatedText;
        }

        /**
         * Main share method
         * all logic witten here
         * @since 2.5.0
         * @return array
         */
        public function remote_post($app_id, $app_secret, $access_token, $post_id, $profile_key)
        {
            // check post is skip social sharing
            if (get_post_meta($post_id, '_wpscppro_dont_share_socialmedia', true) == 'on') {
                return;
            }

            $errorFlag = false;
            $response = '';

            try {
                $linkedin = new LinkedIn(
                    $app_id,
                    $app_secret,
                    WPSCP_SOCIAL_OAUTH2_TOKEN_MIDDLEWARE,
                    WPSCP_LINKEDIN_SCOPE
                );
                $acessToken = $access_token;
                $getPersonID = $linkedin->getPersonID($acessToken);

                $results = "";
                if (get_option('WpScp_linkedin_content_type') == 'status') {
                    $formatedText = $this->get_formatted_text($post_id);
                    $results = $linkedin->linkedInTextPost($acessToken, $getPersonID, $formatedText);
                } else {
                    $post_details = get_post($post_id);
                    $title = get_the_title($post_id);
                    $post_link = get_permalink($post_id);
                    if (get_option('WpScp_linkedin_content_source') === 'excerpt' && has_excerpt($post_details->ID)) {
                        $desc = wp_strip_all_tags($post_details->post_excerpt);
                    } else {
                        $desc = wp_strip_all_tags($post_details->post_content);
                    }
                    $results = $linkedin->linkedInLinkPost($acessToken, $getPersonID, $this->get_formatted_text($post_id), $title, wp_trim_words($desc, 10, '...'), $post_link);
                }
                $result = json_decode($results);
                // linkedin sdk has no Exception handler, that's why we handle it 
                if (property_exists($result, 'id') && $result->id != "") {
                    $shareInfo = array(
                        'share_id' => (isset($result->id) ? $result->id : ''),
                        'publish_date' => time(),
                    );
                    $this->save_metabox_social_share($post_id, $shareInfo, $profile_key);
                    $errorFlag = true;
                    $response = $shareInfo;
                } else if (property_exists($result, 'serviceErrorCode') && $result->serviceErrorCode != "") {
                    $errorFlag = false;
                    $response = $result->message;
                }
            } catch (\Exception $e) {
                // update option meta if token expire
                if ($e->getCode() == 401) {
                    $this->setOptionForSocialTokenExpired(WPSCP_LINKEDIN_OPTION_NAME, $profile_key);
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
         * Schedule Republish Hook call back
         */
        public function wpscp_pro_republish_linkedin_post($post_id)
        {
            // check post is skip social sharing
            if (get_post_meta($post_id, '_wpscppro_dont_share_socialmedia', true) == 'on') {
                return;
            }

            $profiles = wpscp_get_social_profile(WPSCP_LINKEDIN_OPTION_NAME);
            if (is_array($profiles) && count($profiles) > 0) {
                foreach ($profiles as $profile_key => $profile) {
                    // skip if status is false
                    if ($profile['status'] == false) {
                        continue;
                    }
                    // call social share method
                    $this->remote_post(
                        (isset($profile['app_id']) ? $profile['app_id'] : WPSCP_LINKEDIN_CLIENT_ID),
                        (isset($profile['app_secret']) ? $profile['app_secret'] : WPSCP_LINKEDIN_CLIENT_SECRET),
                        $profile['access_token'],
                        $post_id,
                        $profile_key
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
            if (get_post_meta($post_id, '_wpscppro_dont_share_socialmedia', true) == 'on') {
                return;
            }

            $profiles = wpscp_get_social_profile(WPSCP_LINKEDIN_OPTION_NAME);
            if (is_array($profiles) && count($profiles) > 0) {
                foreach ($profiles as $profile_key => $profile) {
                    // skip if status is false
                    if ($profile['status'] == false) {
                        continue;
                    }
                    // call social share method
                    $this->remote_post(
                        (isset($profile['app_id']) ? $profile['app_id'] : WPSCP_LINKEDIN_CLIENT_ID),
                        (isset($profile['app_secret']) ? $profile['app_secret'] : WPSCP_LINKEDIN_CLIENT_SECRET),
                        $profile['access_token'],
                        $post_id,
                        $profile_key
                    );
                }
            }
        }



        public function socialMediaInstantShare($app_id, $app_secret, $access_token, $post_id, $profile_key)
        {
            $response = $this->remote_post($app_id, $app_secret, $access_token, $post_id, $profile_key);
            if ($response['success'] == false) {
                wp_send_json_error($response['log']);
            } else {
                wp_send_json_success($response['log']);
            }
        }
    }
    $WpScp_linkedin = new WpScp_linkedin();
    $WpScp_linkedin->instance();
}
