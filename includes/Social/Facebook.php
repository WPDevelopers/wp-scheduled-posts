<?php

namespace WPSP\Social;

use WPSP\Traits\SocialHelper;

class Facebook
{
    use SocialHelper;
    private $is_show_meta;
    private $content_type;
    private $is_category_as_tags;
    private $content_source;
    private $template_structure;
    private $status_limit;

    public function __construct()
    {
        $settings = \WPSP\Helper::get_settings('social_templates');
        $settings = json_decode(json_encode($settings->facebook), true);
        $this->is_show_meta = (isset($settings[0]['is_show_meta']) ? $settings[0]['is_show_meta'] : '');
        $this->content_type = (isset($settings[1]['content_type']) ? $settings[1]['content_type'] : '');
        $this->is_category_as_tags = (isset($settings[2]['is_category_as_tags']) ? $settings[2]['is_category_as_tags'] : '');
        $this->content_source = (isset($settings[3]['content_source']) ? $settings[3]['content_source'] : '');
        $this->template_structure = (isset($settings[4]['template_structure']) ? $settings[4]['template_structure'] : '{title}{content}{url}{tags}');
        $this->status_limit = (isset($settings[5]['status_limit']) ? $settings[5]['status_limit'] : 63206);
        $this->facebook_head_meta_data();
    }

    public function instance()
    {
        // hook
        add_action('wpsp_publish_future_post', array($this, 'WpScp_Facebook_post_event'), 30, 1);
        add_action('WpScp_Facebook_post', array($this, 'WpScp_Facebook_post'), 15, 1);
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
            add_action('wpscp_pro_schedule_republish_share', array($this, 'wpscp_pro_republish_facebook_post'), 15, 1);
        }
    }

    /**
     * 'WpScp_Facebook_post_event' should be triggered by 'publish_future_post' action
     *
     */
    public function WpScp_Facebook_post_event($post_id)
    {
        //post data
        $post_details = $post_id;
        if ( !is_object( $post_id ) ){
            $post_details = get_post($post_id);
        }

        if ($post_details->post_status == 'publish') {
            // Schedule the actual event
            wp_schedule_single_event(time(), 'WpScp_Facebook_post', array($post_details->ID));
        }
    }




    public function facebook_head_meta_data()
    {
        if ($this->is_show_meta) {
            add_filter('language_attributes', array($this, 'set_opengraph_doctype'));
            add_action('wp_head', array($this, 'meta_head'), 5);
        }
    }

    /**
     * Saved Post Meta info
     */
    public function save_metabox_social_share_metabox($post_id, $response, $profile_key)
    {
        $meta_name = '__wpscppro_facebook_share_log';
        $oldData = get_post_meta($post_id, $meta_name, true);
        if ($oldData != "") {
            $oldData[$profile_key] = $response;
            $updateData = $oldData;
            update_post_meta($post_id, $meta_name, $updateData);
        } else {
            add_post_meta($post_id, $meta_name, array($profile_key => $response));
        }
    }


    //Adding the Open Graph in the Language Attributes
    public function set_opengraph_doctype($output)
    {
        return $output . ' xmlns:og="http://opengraphprotocol.org/schema/" xmlns:fb="http://www.facebook.com/2008/fbml"';
    }

    //Lets add Open Graph Meta Info
    public function meta_head()
    {
        global $post;
        if (!is_singular()) // work only single pages
        {
            return;
        }

        echo '<meta property="og:title" content="' . get_the_title() . '"/>';
        echo '<meta property="og:type" content="article"/>';
        echo '<meta property="og:url" content="' . get_permalink() . '"/>';
        echo '<meta property="og:site_name" content=" ' . get_bloginfo() . ' "/>';

        $socialShareImage = get_post_meta($post->ID, '_wpscppro_custom_social_share_image', true);
        if ($socialShareImage != "") {
            $thumbnail_src = wp_get_attachment_image_src($socialShareImage, 'full');
            echo '<meta property="og:image" content="' . esc_attr($thumbnail_src[0]) . '"/>';
        } else {
            if (has_post_thumbnail($post->ID)) { //the post does not have featured image, use a default image
                $thumbnail_src = wp_get_attachment_image_src(get_post_thumbnail_id($post->ID), 'full');
                echo '<meta property="og:image" content="' . esc_attr($thumbnail_src[0]) . '"/>';
            }
        }
        echo "";
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
        }


        $hashTags = (($this->getPostHasTags($post_id) != false) ? $this->getPostHasTags($post_id) : '');
        if ($this->is_category_as_tags == true) {
            $hashTags .= ' ' . $this->getPostHasCats($post_id);
        }
        if ($this->content_type == 'status' || $this->content_type == 'statuswithlink') {
            // text formated
            $formatedText = $this->social_share_content_template_structure(
                $this->template_structure,
                $title,
                $desc,
                $post_link,
                $hashTags,
                $this->status_limit
            );
            if ($this->content_type == 'status') {
                $linkData = [
                    'message' => $formatedText,
                ];
            } else if ($this->content_type == 'statuswithlink') {
                $linkData = [
                    'message' => $formatedText,
                    'link' => $post_link,
                ];
            }
        } else {
            $linkData = [
                'link' => $post_link,
            ];
        }
        return $linkData;
    }

    /**
     * Main share method
     * all logic witten here
     * @since 2.5.0
     * @return array
     */
    public function remote_post($app_id, $app_secret, $app_access_token, $type, $ID, $post_id, $profile_key, $force_share = false)
    {
        // check post is skip social sharing
        if (empty($app_id) || empty($app_secret) || get_post_meta($post_id, '_wpscppro_dont_share_socialmedia', true) == 'on') {
            return;
        }

        if(get_post_meta($post_id, '_wpsp_is_facebook_share', true) == 'on' || $force_share) {
            $errorFlag = false;
            $response = '';

            $fb = new \Facebook\Facebook([
                'app_id'        => $app_id,
                'app_secret'    => $app_secret,
                'default_graph_version' => 'v6.0',
            ]);

            $linkData = $this->get_share_content_args($post_id);


            // group api
            if ($type === 'group') {
                // group post
                $response = $this->post('/' . $ID . '/feed',  $linkData, $app_access_token);
                // Call the helper function to handle the response
                return $this->handle_response($response, $post_id, $profile_key);
            }

            // page api
            if ($type === 'page') {
                // page post
                $response = $this->post('/' . $ID . '/feed',  $linkData, $app_access_token);
                // Call the helper function to handle the response
                return $this->handle_response($response, $post_id, $profile_key);
            }

            // old user option
            if ($type == 'oldAccount') {
                // old user post
                $response = $this->post('/me/feed',  $linkData, $app_access_token);
                // Call the helper function to handle the response
                return $this->handle_response($response, $post_id, $profile_key);
            }

            return array(
                'success' => $errorFlag,
                'log' => $response
            );
        }
        return;
    }

    /**
     * Define a helper function to handle the response and save the share info
     *
     * @param array|\WP_Error $response
     * @param int $post_id
     * @param string $profile_key
     * @return void
     */
    public function handle_response($response, $post_id, $profile_key) {
        // Check if the response is a WP_Error object
        $isError = is_wp_error($response);
        if ($isError == false) {
            // Get the body of the response as an array
            $body = json_decode(wp_remote_retrieve_body($response), true);
            // Get the share ID from the body
            $shareInfo = array(
                'share_id'     => $body['id'],
                'publish_date' => current_time('mysql'),
            );
            // Save share info in metabox
            $this->save_metabox_social_share_metabox($post_id, $shareInfo, $profile_key);
            $errorFlag = true;
            $response  = $shareInfo;
        } else {
            // Get the error message from the WP_Error object
            $errorFlag = false;
            $response  = 'Error: ' . $response->get_error_message();
        }
        // Return an array with the success flag and the response
        return array(
            'success' => $errorFlag,
            'log'     => $response
        );
    }

    /**
     * Define a custom function that takes the endpoint, params, accessToken, eTag, and graphVersion as arguments
     *
     * @param string $endpoint
     * @param array $params
     * @param string $accessToken
     * @param string $eTag
     * @param string $graphVersion
     * @return array|\WP_Error
     */
    public function post($endpoint, array $params = [], $accessToken = null, $eTag = null, $graphVersion = null) {
        $graphVersion = $graphVersion ? $graphVersion : 'v6.0';

        // Build the URL from the endpoint and the graphVersion
        $url = 'https://graph.facebook.com/' . $graphVersion . $endpoint;
        // Build the headers from the accessToken and the eTag
        $headers = array(
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $accessToken,
            'If-None-Match' => $eTag
        );
        // Build the body from the params
        $body = json_encode($params);
        // Build the args array from the headers and the body
        $args = array(
            'method'  => 'POST',
            'headers' => $headers,
            'body'    => $body
        );
        // Call wp_remote_post with the url and the args
        $response = wp_remote_post($url, $args);
        // Return the response or handle errors
        return $response;
    }

    /**
     * Schedule Republish social share hook
     * @since 2.5.0
     * @return void
     */
    public function wpscp_pro_republish_facebook_post($post_id)
    {
        $profiles = \WPSP\Helper::get_social_profile(WPSCP_FACEBOOK_OPTION_NAME);
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
                    $profile_key
                );
            }
        }
    }

    /**
     * Schedule Future post publish
     * @since 2.5.0
     * @return void
     */
    public function WpScp_Facebook_post($post_id)
    {
        $profiles = \WPSP\Helper::get_social_profile(WPSCP_FACEBOOK_OPTION_NAME);
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
                    $profile_key
                );
            }
        }
    }


    /**
     * This method Call for Instant social share - it will be happend by ajax call
     * @since 2.5.0
     * @return ajax response
     */
    public function socialMediaInstantShare($app_id, $app_secret, $app_access_token, $type, $ID, $post_id, $profile_key)
    {
        $response = $this->remote_post($app_id, $app_secret, $app_access_token, $type, $ID, $post_id, $profile_key, true);
        if ($response['success'] == false) {
            wp_send_json_error($response['log']);
        } else {
            wp_send_json_success($response['log']);
        }
    }
}
