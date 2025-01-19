<?php

namespace WPSP\Social;

use DirkGroenen\Pinterest\Pinterest;
use myPHPNotes\LinkedIn;
use WPSP\Helper;

class ActionBeforePublish
{
    public function __construct()
    {
        if (Helper::get_settings('is_share_on_post_publish')) {
            add_action('wp_insert_post', [$this, 'share_post_on_publish'], 100, 3);
        }

        if (Helper::get_settings('set_future_date_on_post_publish')) {
            add_action('wp_insert_post_data', [$this, 'handle_future_post_status']);
        }
    }

    /**
     * Prevents future post status for specific post types and sets it to publish.
     *
     * @param array $post_data Post data being saved.
     * @return array Modified post data.
     */
    public function handle_future_post_status($post_data)
    {
        $allowed_post_types = Helper::get_settings('allow_post_type_for_future_date_and_published_share') ?? [];

        if (!function_exists('is_plugin_active')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        if (!is_plugin_active('wp-scheduled-posts/wp-scheduled-posts.php') || $post_data['post_status'] !== 'future') {
            return $post_data;
        }

        if (in_array($post_data['post_type'], $allowed_post_types, true)) {
            $post_data['post_status'] = 'publish';
            if (isset($_POST['date_type']) && $_POST['date_type'] === 'current') {
                $post_data['post_date'] = current_time('mysql');
                $post_data['post_date_gmt'] = current_time('mysql', 1);
            }
            remove_action('future_post', '_future_post_hook');
        }

        return $post_data;
    }

    /**
     * Retrieves the selected social profiles from a REST API request.
     *
     * @return array Selected social profiles or an empty array.
     */
    private function get_selected_social_profiles_from_request()
    {
        if (defined('REST_REQUEST') && REST_REQUEST) {
            $raw_input = file_get_contents('php://input');
            $decoded_input = json_decode($raw_input, true);

            return $decoded_input['meta']['_selected_social_profile'] ?? [];
        }

        return [];
    }

    /**
     * Retrieves the selected social profiles from a REST API request.
     *
     * @return array Selected social profiles or an empty array.
     */
    private function get_meta_from_request()
    {
        if (defined('REST_REQUEST') && REST_REQUEST) {
            $raw_input = file_get_contents('php://input');
            $decoded_input = json_decode($raw_input, true);

            return $decoded_input['meta'] ?? [];
        }

        return [];
    }

    /**
     * Determines if the request is from the Classic Editor.
     *
     * @return bool True if Classic Editor, false otherwise.
     */
    private function is_classic_editor_request()
    {
        return isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], 'post.php') !== false;
    }

    /**
     * Handles sharing posts on social media when published.
     *
     * @param int     $post_id Post ID.
     * @param WP_Post $post Post object.
     * @param bool    $update Whether the post is being updated or not.
     */
    public function share_post_on_publish($post_id, $post, $update)
    {
        $allowed_post_types = Helper::get_settings('allow_post_type_for_future_date_and_published_share') ?? [];

        if (!in_array($post->post_type, $allowed_post_types) || wp_is_post_revision($post_id)) {
            return;
        }

        if ($post->post_status === 'publish' && !get_post_meta($post_id, 'wpsp_post_share_on_publish', true)) {
            $nonce = wp_create_nonce('wpscp-pro-social-profile');

            if ($this->is_classic_editor_request()) {
                $this->share_via_classic_editor($post_id, $nonce);
            } else {
                $this->share_via_rest_api($post_id, $nonce);
            }

            update_post_meta($post_id, 'wpsp_post_share_on_publish', true);
        }
    }

    /**
     * Shares posts via the Classic Editor.
     *
     * @param int    $post_id Post ID.
     * @param string $nonce Nonce for security.
     */
    private function share_via_classic_editor($post_id, $nonce)
    {
        $social_platforms = [
            'facebook'  => WPSCP_FACEBOOK_OPTION_NAME,
            'twitter'   => WPSCP_TWITTER_OPTION_NAME,
            'linkedin'  => WPSCP_LINKEDIN_OPTION_NAME,
            'pinterest' => WPSCP_PINTEREST_OPTION_NAME,
            'instagram' => WPSCP_INSTAGRAM_OPTION_NAME,
            'medium'    => WPSCP_MEDIUM_OPTION_NAME,
            'threads'   => WPSCP_THREADS_OPTION_NAME,
        ];

        foreach ($social_platforms as $platform => $option_name) {
            $profiles = Helper::get_social_profile($option_name);

            if ($platform === 'pinterest') {
                $selected_profiles = $this->get_selected_social_profiles_from_request();
                $get_meta          = $this->get_meta_from_request();
                $profiles = array_filter($profiles, function ($profile) use ($selected_profiles) {
                    return in_array($profile->default_board_name->value, $selected_profiles, true);
                });
            }

            $this->trigger_social_shares($post_id, $profiles, $nonce, $platform, $get_meta);
        }
    }

    /**
     * Shares posts via the REST API.
     *
     * @param int    $post_id Post ID.
     * @param string $nonce Nonce for security.
     */
    private function share_via_rest_api($post_id, $nonce)
    {
        $selected_profiles = $this->get_selected_social_profiles_from_request();

        foreach ($selected_profiles as $settings) {
            $query_args = $this->build_query_args($post_id, $settings, $nonce);
            do_action('wpsp_instant_social_single_profile_share', $query_args);
        }
    }

    /**
     * Triggers social shares for the given profiles.
     *
     * @param int    $post_id Post ID.
     * @param array  $profiles Social profiles.
     * @param string $nonce Nonce for security.
     * @param string $platform Social media platform.
     */
    private function trigger_social_shares($post_id, $profiles, $nonce, $platform)
    {
        foreach ($profiles as $key => $settings) {
            if ($settings->status) {
                $query_args = $this->build_query_args($post_id, [
                    'platform'   => $platform,
                    'id'         => $settings->id,
                    'platformKey'=> $key,
                    'pinterest_board_type' => $settings->pinterest_board_type ?? '',
                    'pinterest_custom_board_name' => $settings->pinterest_board_name ?? '',
                    'pinterest_custom_section_name' => $settings->pinterest_section_name ?? '',
                ], $nonce);

                do_action('wpsp_instant_social_single_profile_share', $query_args);
            }
        }
    }

    /**
     * Builds query arguments for social sharing.
     *
     * @param int    $post_id Post ID.
     * @param array  $settings Social profile settings.
     * @param string $nonce Nonce for security.
     * @return array Query arguments.
     */
    private function build_query_args($post_id, $settings, $nonce)
    {
        return [
            'nonce'                         => $nonce,
            'postid'                        => $post_id,
            'platform'                      => $settings['platform'] ?? '',
            'id'                            => $settings['id'] ?? '',
            'platformKey'                   => $settings['platformKey'] ?? '',
            'pinterest_board_type'          => $settings['pinterest_board_type'] ?? '',
            'pinterest_custom_board_name'   => $settings['pinterest_custom_board_name'] ?? '',
            'pinterest_custom_section_name' => $settings['pinterest_section_name'] ?? '',
            'share_on_publish'              => true,
        ];
    }
}
