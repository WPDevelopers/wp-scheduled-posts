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
        $this->status_limit = isset($settings['status_limit']) ? $settings['status_limit'] : 1450;
        $this->post_share_limit = isset($settings['post_share_limit']) ? $settings['post_share_limit'] : 0;
        $this->remove_css_from_content = isset($settings['remove_css_from_content']) ? $settings['remove_css_from_content'] : true;
    }

    public function instance()
    {
        // Schedule Hooks
        add_action('wpsp_publish_future_post', array($this, 'WpScp_GoogleBusiness_post'), 10, 1);
        add_action('wpsp_schedule_republish_share', array($this, 'wpscp_pro_republish_google_business_post'), 10, 1);

        // Token refresh hooks
        add_action('init', array($this, 'init_token_refresh'));


        // Register the common hook
        add_action('wpsp_google_business_token_refresh', array($this, 'refresh_access_token_cron'), 10, 1);
    }

    /**
     * Main share method
     * all logic written here
     * @since 2.5.0
     * @return array
     */
    public function remote_post($app_id, $app_secret, $app_access_token, $type, $ID, $post_id, $profile_key, $force_share = false) {
        // Early returns for conditions that prevent sharing
        $count_meta_key = '__wpsp_google_business_share_count_' . $ID;
        $dont_share = get_post_meta($post_id, '_wpscppro_dont_share_socialmedia', true);

        $transient_key = 'wpsp_google_business_share_in_progress_' . $ID . '_' . $post_id;
        if ( get_transient($transient_key) ) {
            return [
                'success' => false,
                'log' => __('Share already in progress or recently shared. Please wait 40 seconds before trying again.', 'wp-scheduled-posts')
            ];
        }
        // Set transient to prevent duplicate share attempts
        set_transient($transient_key, true, 40);

        // check if schedulepress pro is active
        if( !defined('WPSP_PRO_VERSION') ) {
            return;
        }

        // Check custom share type
        $get_share_type = get_post_meta($post_id, '_google_business_share_type', true);
        if ($get_share_type === 'custom') {
            $get_all_selected_profile = get_post_meta($post_id, '_selected_social_profile', true);
            if (!Helper::is_profile_exits($ID, $get_all_selected_profile)) {
                return;
            }
        }

        // Check if sharing is disabled
        if (empty($app_id) || (empty($app_access_token) && empty($app_secret)) || $dont_share == 'on' || $dont_share == 1) {
            return;
        }

        // Check share limit
        $share_count = (int)get_post_meta($post_id, $count_meta_key, true);
        if ($share_count && $this->post_share_limit > 0 && $share_count >= $this->post_share_limit) {
            return [
                'success' => false,
                'log' => __('Your max share post limit has been executed!!', 'wp-scheduled-posts')
            ];
        }

        $is_enabled_custom_template = get_post_meta($post_id, '_wpsp_enable_custom_social_template', true);
        // if enabled custom template then check current social profile is selected or not
        if( $is_enabled_custom_template ) {
            $templates = get_post_meta($post_id, '_wpsp_custom_templates', true);
            $platform_data = isset($templates['google_business']) ? $templates['google_business'] : null;
            $profiles = is_array($platform_data) && isset($platform_data['profiles']) ? $platform_data['profiles'] : [];
            if ( is_array($profiles) && !in_array($ID, $profiles) ) {
                return;
            }
        }

        // Check if sharing is enabled for this post
        if (get_post_meta($post_id, '_wpsp_is_google_business_share', true) != 'on' && !$force_share) {
            return [
                'success' => false,
                'log' => __('Google Business share is not enabled for this post', 'wp-scheduled-posts')
            ];
        }

        try {
            // Get post data
            $title   = get_the_title($post_id);
            $content = get_post_field('post_content', $post_id);
            $clean_content = wp_strip_all_tags($content);
            $clean_content = wp_trim_words($clean_content, 30, '...'); // limit to 30 words
            $permalink = get_permalink($post_id);

            // Get featured image
            $featured_image_url = '';
            if (has_post_thumbnail($post_id)) {
                $featured_image = wp_get_attachment_image_src(get_post_thumbnail_id($post_id), 'full');
                $featured_image_url = $featured_image[0];
            }
            // Refresh token if needed
            $google_business = Helper::get_social_profile(WPSCP_GOOGLE_BUSINESS_OPTION_NAME);
            $refresh_token = !empty($google_business[$profile_key]->refresh_token) ? $google_business[$profile_key]->refresh_token : '';

            if (!empty($refresh_token)) {
                $refresh_result = $this->refresh_generate_access_token($app_id, $refresh_token);

                if (!$refresh_result['error'] && !empty($refresh_result['access_token'])) {
                    $app_access_token = $refresh_result['access_token'];

                    // Update stored token
                    $google_business[$profile_key]->access_token = $app_access_token;
                    $google_business[$profile_key]->expires_in = time() + $refresh_result['expires_in'];
                    update_option(WPSCP_GOOGLE_BUSINESS_OPTION_NAME, $google_business);
                }
            }

            // Get account and location IDs
            $account_id = !empty($google_business[$profile_key]->account_id) ? $google_business[$profile_key]->account_id : $google_business[$profile_key]->id;
            $location_id = !empty($google_business[$profile_key]->location_id) ? $google_business[$profile_key]->location_id : '';

            if (empty($location_id) || empty($account_id)) {
                return [
                    'success' => false,
                    'log' => __('Location or Account ID is missing', 'wp-scheduled-posts')
                ];
            }

            // Prepare API request
            $api_url = "https://mybusiness.googleapis.com/v4/{$account_id}/{$location_id}/localPosts";

            // Get post data for template formatting
            $post_details = get_post($post_id);
            $title = get_the_title($post_id);
            $post_link = esc_url(get_permalink($post_id));

            // Get content based on source setting
            if ($this->content_source === 'excerpt' && has_excerpt($post_details->ID)) {
                $desc = wp_strip_all_tags($post_details->post_excerpt);
            } else {
                $desc = $this->format_plain_text_with_paragraphs($post_details->post_content);
                if (is_visual_composer_post($post_id) && class_exists('WPBMap')) {
                    \WPBMap::addAllMappedShortcodes();
                    $desc = Helper::strip_all_html_and_keep_single_breaks(do_shortcode($desc));
                }
            }

            // Get hashtags if category as tags is enabled
            $hashTags = (($this->getPostHasTags($post_id, 'google_business', $this->is_category_as_tags) != false) ? $this->getPostHasTags($post_id, 'google_business', $this->is_category_as_tags) : '');
            if ($this->is_category_as_tags) {
                $tags = $this->getPostHasTags($post_id, 'google_business', $this->is_category_as_tags);
                $cats = $this->getPostHasCats($post_id, 'google_business');
                $hashTags = ($tags ? $tags : '') . ($cats ? ' ' . $cats : '');
            }

            // Format text using social_share_content_template_structure
            $formatted_summary = $this->social_share_content_template_structure(
                $this->template_structure,
                $title,
                $desc,
                $post_link,
                $hashTags,
                $this->status_limit,
                null,
                'google_business',
                $post_id,
                $ID // profile_id
            );

            $post_data = [
                'languageCode' => 'en',
                'summary'      => $formatted_summary,
                'topicType'    => 'STANDARD'
            ];

            if( strpos($template_structure, '{url}') ) {
                $post_data['callToAction'] = [
                    'actionType' => 'LEARN_MORE',
                    'url'        => $post_link,
                ];
            }

            if (!empty($featured_image_url)) {
                $post_data['media'] = [[
                    'mediaFormat' => 'PHOTO',
                    'sourceUrl'   => $featured_image_url,
                ]];
            }

            // Make API request
            $response = wp_remote_post($api_url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . trim($app_access_token),
                    'Content-Type' => 'application/json'
                ],
                'body' => json_encode($post_data),
                'timeout' => 20,
            ]);

            if (is_wp_error($response)) {
                return [
                    'success' => false,
                    'log' => __('Request failed: ', 'wp-scheduled-posts') . $response->get_error_message()
                ];
            }

            $response_code = wp_remote_retrieve_response_code($response);
            $response_body = json_decode(wp_remote_retrieve_body($response), true);

            if ($response_code >= 200 && $response_code < 300) {
                // Update share count
                update_post_meta($post_id, $count_meta_key, $share_count + 1);

                // Extract share ID
                $share_id = '';
                if (!empty($response_body['name'])) {
                    $parts = explode('/', $response_body['name']);
                    $share_id = end($parts);
                }

                return [
                    'success' => true,
                    'log' => [
                        'share_id' => $share_id,
                        'publish_date' => time(),
                    ],
                ];
            } else {
                // Handle API error
                $error_message = isset($response_body['error']['message']) ? $response_body['error']['message'] : 'Unknown error';
                $details = $this->format_error_message($response_body);

                return [
                    'success' => false,
                    'log' => sprintf(
                        /* translators: %s: Error message and details returned when sharing on Google Business fails */
                        __( 'Failed to share on Google Business: %s', 'wp-scheduled-posts' ),
                        $error_message . "\n" . $details
                    ),
                ];
            }

        } catch (\Exception $e) {
            return [
                'success' => false,
                'log' => sprintf(
                    /* translators: %s: Exception message returned when sharing to Google Business fails */
                    __( 'Exception when sharing to Google Business: %s', 'wp-scheduled-posts' ),
                    $e->getMessage()
                ),
            ];
        }
    }

    public function format_plain_text_with_paragraphs( $content ) {
        // Convert HTML breaks and block elements into double line breaks
        $content = str_ireplace( [ '</p>', '</div>', '<br>', '<br/>', '<br />' ], "\n\n", $content );

        // Strip all remaining HTML tags
        $content = wp_strip_all_tags( $content );

        // Normalize newlines
        $content = str_replace( [ "\r\n", "\r" ], "\n", $content );

        // Collapse 3+ newlines into just 2
        $content = preg_replace( "/\n{3,}/", "\n\n", $content );

        // Trim each line and rebuild
        $lines = array_map( 'trim', explode( "\n", $content ) );
        $content = implode( "\n", $lines );

        return trim( $content );
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

    public function init_token_refresh() {
        static $initialized = false;
        if ($initialized) return;
        $initialized = true;

        $profiles = Helper::get_social_profile(WPSCP_GOOGLE_BUSINESS_OPTION_NAME);
        if (!is_array($profiles)) return;

        // Initialize profiles
        foreach ($profiles as $profile_key => $profile) {
            if (empty($profile->id)) continue;

            $account_id = strpos($profile->id, 'accounts/') === 0 ? substr($profile->id, 9) : $profile->id;

            // Schedule if needed
            if ($profile->status && !empty($profile->refresh_token) && !empty($profile->expires_in)) {
                if (!wp_next_scheduled('wpsp_google_business_token_refresh', array($account_id))) {
                    $refresh_time = $profile->expires_in - 600; // 1 hour before expiry
                    if ($refresh_time <= time()) $refresh_time = time() + 60;
                    wp_schedule_single_event($refresh_time, 'wpsp_google_business_token_refresh', array($account_id));
                    if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                        wp_trigger_error( '', "WPSP: Scheduled token refresh for account_id: " . $account_id . " at " . date('Y-m-d H:i:s', $refresh_time), E_USER_NOTICE );
                    }
                }
            }
        }

    }

    public function schedule_token_refresh($profile_key, $profile) {
        if (empty($profile->refresh_token) || empty($profile->expires_in) || empty($profile->id)) return;

        $account_id = strpos($profile->id, 'accounts/') === 0 ? substr($profile->id, 9) : $profile->id;

        // Clear existing
        if ($timestamp = wp_next_scheduled('wpsp_google_business_token_refresh', array($account_id))) {
            wp_unschedule_event($timestamp, 'wpsp_google_business_token_refresh', array($account_id));
        }

        // Schedule new
        $refresh_time = $profile->expires_in - 600; // 1 hour before expiry
        if ($refresh_time <= time()) $refresh_time = time() + 60;
        wp_schedule_single_event($refresh_time, 'wpsp_google_business_token_refresh', array($account_id));
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            wp_trigger_error( '', "WPSP: Scheduled token refresh for account_id: " . $account_id . " at " . date('Y-m-d H:i:s', $refresh_time), E_USER_NOTICE );
        }
    }

    public function refresh_access_token_cron($account_id = null) {
        // Debug logging
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            wp_trigger_error( '', "WPSP: refresh_access_token_cron called with account_id: " . is_scalar($account_id) ? $account_id : wp_json_encode($account_id), E_USER_NOTICE );
        }

        if (empty($account_id)) {
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                wp_trigger_error( '', "WPSP: No account_id provided to refresh_access_token_cron", E_USER_WARNING );
            }
            return;
        }

        $profiles = Helper::get_social_profile(WPSCP_GOOGLE_BUSINESS_OPTION_NAME);
        if (!is_array($profiles)) {
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                wp_trigger_error( '', "WPSP: No profiles found", E_USER_WARNING );
            }
            return;
        }

        // Find profile by account ID
        $profile_key = null;
        $profile = null;
        foreach ($profiles as $key => $p) {
            if (!empty($p->id)) {
                $p_account_id = strpos($p->id, 'accounts/') === 0 ? substr($p->id, 9) : $p->id;
                if ($p_account_id === $account_id) {
                    $profile_key = $key;
                    $profile = $p;
                    break;
                }
            }
        }

        if (!$profile) {
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                wp_trigger_error( '', "WPSP: Profile not found for account_id: " . $account_id, E_USER_WARNING );
            }
            return;
        }

        if (empty($profile->refresh_token) || empty($profile->app_id) ) {
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                wp_trigger_error( '', "WPSP: Missing required data for account_id: " . $account_id, E_USER_WARNING );
            }
            // return;
        }
        // Generate refresh token
        $refresh_result = $this->refresh_generate_access_token( $profile->app_id, $profile->refresh_token );

        if (!$refresh_result['error'] && !empty($refresh_result['access_token'])) {
            $profiles[$profile_key]->access_token = $refresh_result['access_token'];
            $profiles[$profile_key]->expires_in = time() + $refresh_result['expires_in'];
            update_option(WPSCP_GOOGLE_BUSINESS_OPTION_NAME, $profiles);
            $this->schedule_token_refresh($profile_key, $profiles[$profile_key]);
        } else {
            // Retry in 1 hour
            wp_schedule_single_event(time() + 3600, 'wpsp_google_business_token_refresh', array($account_id));
        }
    }

    public function refresh_generate_access_token( $app_id, $refresh_token ) {
        $response = wp_remote_post( WPSP_SOCIAL_OAUTH2_TOKEN_MIDDLEWARE_DEV, [
            'body' => [
                'type'          => 'google_business',   // or ''
                'refresh_token' => $refresh_token,
                'client_id'     => $app_id,
            ]
        ]);

        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            echo "Something went wrong: $error_message";
        } else {
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);
            return $data;
        }
    }

}
