<?php 
namespace WPSP\Social;

use WPSP\Helper;
use WPSP\Traits\SocialHelper;

class GoogleBusiness {
    use SocialHelper;
    private $is_show_meta;
    private $content_type;
    private $is_category_as_tags;
    private $content_source;
    private $template_structure;
    private $status_limit;
    private $post_share_limit;
    private $remove_css_from_content;

    public function __construct() {
        $settings = Helper::get_settings('social_templates');
        $settings = json_decode(json_encode($settings->google_business), true);
        $this->is_show_meta = isset($settings['is_show_meta']) ? $settings['is_show_meta'] : false;
        $this->content_type = isset($settings['content_type']) ? $settings['content_type'] : 'excerpt';
        $this->is_category_as_tags = isset($settings['is_category_as_tags']) ? $settings['is_category_as_tags'] : false;
        $this->content_source = isset($settings['content_source']) ? $settings['content_source'] : 'post_content';
        $this->template_structure = isset($settings['template_structure']) ? $settings['template_structure'] : '{title}{content}{url}';
        $this->status_limit = isset($settings['status_limit']) ? $settings['status_limit'] : 280;
        $this->post_share_limit = isset($settings['post_share_limit']) ? $settings['post_share_limit'] : 0;
        $this->remove_css_from_content = isset($settings['remove_css_from_content']) ? $settings['remove_css_from_content'] : true;
    }

    public function instance()
    {
        // Schedule Hooks
        add_action('wpsp_publish_future_post', array($this, 'WpScp_GoogleBusiness_post'), 10, 1);
        add_action('wpsp_schedule_republish_share', array($this, 'wpscp_pro_republish_google_business_post'), 10, 1);
    }

    /**
     * Main share method
     * all logic written here
     * @since 2.5.0
     * @return array
     */
    public function remote_post($app_id, $app_secret, $app_access_token, $type, $ID, $post_id, $profile_key, $force_share = false) {
        // get share count 
        $count_meta_key = '__wpsp_google_business_share_count_'.$ID;
        $dont_share = get_post_meta($post_id, '_wpscppro_dont_share_socialmedia', true);

        // get social share type 
        $get_share_type = get_post_meta($post_id, '_google_business_share_type', true);
        if ($get_share_type === 'custom') {
            $get_all_selected_profile = get_post_meta($post_id, '_selected_social_profile', true);
            $check_profile_exists = Helper::is_profile_exits($ID, $get_all_selected_profile);
            if (!$check_profile_exists) {
                return;
            }
        }

        // check post is skip social sharing
        if (empty($app_id) || ( empty($app_access_token) && empty($app_secret) ) || $dont_share == 'on' || $dont_share == 1) {
            return;
        }
        
        if ((get_post_meta($post_id, $count_meta_key, true)) && $this->post_share_limit != 0 && get_post_meta($post_id, $count_meta_key, true) >= $this->post_share_limit) {
            return array(
                'success' => false,
                'log' => __('Your max share post limit has been executed!!', 'wp-scheduled-posts')
            );
        }
        
        if (get_post_meta($post_id, '_wpsp_is_google_business_share', true) == 'on' || $force_share) {
            $errorFlag = false;
            $response = '';

            // Get post data
            $post = get_post($post_id);
            $permalink = get_permalink($post_id);
            $content = 'lorem ipsum dolor sit amet';
            $title = get_the_title($post_id);
            
            // Get featured image
            $featured_image_url = '';
            if (has_post_thumbnail($post_id)) {
                $featured_image_id = get_post_thumbnail_id($post_id);
                $featured_image = wp_get_attachment_image_src($featured_image_id, 'full');
                $featured_image_url = $featured_image[0];
            }

            // Check if refresh token exists and refresh access token if needed
            $google_business = Helper::get_social_profile(WPSCP_GOOGLE_BUSINESS_OPTION_NAME);
            $refresh_token = !empty($google_business[$profile_key]->refresh_token) ? $google_business[$profile_key]->refresh_token : '';
            
            if (!empty($refresh_token)) {
                $social_profile = new SocialProfile();
                $refresh_result = $social_profile->refreshGoogleAccessToken($app_id, $app_secret, $refresh_token);
                
                if (!$refresh_result['error'] && !empty($refresh_result['access_token'])) {
                    $app_access_token = $refresh_result['access_token'];
                    
                    // Update the token in the stored profiles
                    $google_business[$profile_key]->access_token = $app_access_token;
                    $google_business[$profile_key]->expires_in = time() + $refresh_result['expires_in'];
                    update_option(WPSCP_GOOGLE_BUSINESS_OPTION_NAME, $google_business);
                }
            }
            try {
                $account_id = !empty($google_business[$profile_key]->id) ? $google_business[$profile_key]->id : '';
                $location_id = !empty($google_business[$profile_key]->location_id) ?  $google_business[$profile_key]->location_id : '';
            
                if (empty($location_id) || empty($account_id)) {
                    return array(
                        'success' => false,
                        'log' => __('Location or Account ID is missing', 'wp-scheduled-posts')
                    );
                }
            
                $api_url = "https://mybusiness.googleapis.com/v4/{$account_id}/{$location_id}/localPosts";
            
                $post_data = array(
                    'languageCode' => 'en',
                    'summary' => $title,
                    'callToAction' => array(
                        'actionType' => 'LEARN_MORE',
                        'url' => 'https://schedulepress.com/?p=92',
                    ),
                    'topicType' => 'STANDARD'
                );
            
                if (!empty($featured_image_url)) {
                    $post_data['media'] = array(
                        'sourceUrl' => $featured_image_url
                    );
                }
            
                $response = wp_remote_post($api_url, array(
                    'headers' => array(
                        'Authorization' => 'Bearer ' . trim($app_access_token),
                        'Content-Type' => 'application/json'
                    ),
                    'body'    => json_encode($post_data),
                    'timeout' => 20,
                ));
            
                if (is_wp_error($response)) {
                    return array(
                        'success' => false,
                        'log' => __('Request failed: ', 'wp-scheduled-posts') . $response->get_error_message()
                    );
                }
            
                $response_code = wp_remote_retrieve_response_code($response);
                $response_body = json_decode(wp_remote_retrieve_body($response), true);
                if ($response_code >= 200 && $response_code < 300) {
                    $count = (int) get_post_meta($post_id, $count_meta_key, true);
                    update_post_meta($post_id, $count_meta_key, ($count + 1));
                    $share_id = '';
                    if (!empty($response_body['name'])) {
                        $parts = explode('/', $response_body['name']);
                        $share_id = end($parts); // Gets the last segment after 'localPosts/'
                    }
                    $shareInfo = array(
                        'share_id' => $share_id,
                        'publish_date' => time(),
                    );
                    return array(
                        'success' => true,
                        'log' => $shareInfo,
                    );
                } else {
                    // API returned error
                    $error_message = isset($response_body['error']['message']) ? $response_body['error']['message'] : 'Unknown error';
                    $details = $this->format_error_message($response_body);
                    return array(
                        'success' => false,
                        'log' => sprintf(__('Failed to share on Google Business: %s', 'wp-scheduled-posts'), $error_message . "\n" . $details)
                    );
                }
            
            } catch (\Exception $e) {
                return array(
                    'success' => false,
                    'log' => sprintf(__('Exception when sharing to Google Business: %s', 'wp-scheduled-posts'), $e->getMessage())
                );
            }                      
        }
        
        return array(
            'success' => false,
            'log' => __('Google Business share is not enabled for this post', 'wp-scheduled-posts')
        );
    }

    public function format_error_message($response_body) {
        $details = '';
        if (!empty($response_body['error']['details'][0]['errorDetails'])) {
            foreach ($response_body['error']['details'][0]['errorDetails'] as $detail) {
                $field   = isset($detail['field']) ? $detail['field'] : '';
                $message = isset($detail['message']) ? $detail['message'] : '';
                $value   = isset($detail['value']) ? $detail['value'] : '';
                $code    = isset($detail['code']) ? $detail['code'] : '';
                $subCode = isset($detail['subErrorCode']) ? $detail['subErrorCode'] : '';
                $details .= "\nField: $field\nMessage: $message\nValue: $value\nCode: $code\nSubCode: $subCode\n";
            }
        }
        return $details;
    }

    /**
     * Schedule Republish social share hook
     * @since 2.5.0
     * @return void
     */
    public function wpscp_pro_republish_google_business_post($post_id) {
        $profiles = Helper::get_social_profile(WPSCP_GOOGLE_BUSINESS_OPTION_NAME);
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
    public function WpScp_GoogleBusiness_post($post_id) {
        $profiles = Helper::get_social_profile(WPSCP_GOOGLE_BUSINESS_OPTION_NAME);
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
    public function socialMediaInstantShare($app_id, $app_secret, $app_access_token, $type, $ID, $post_id, $profile_key, $is_share_on_publish = false) {
        $response = $this->remote_post($app_id, $app_secret, $app_access_token, $type, $ID, $post_id, $profile_key, true);
        if ($is_share_on_publish) {
            return;
        }
        if ($response['success'] == false) {
            wp_send_json_error($response['log']);
        } else {
            wp_send_json_success($response['log']);
        }
    }
}
