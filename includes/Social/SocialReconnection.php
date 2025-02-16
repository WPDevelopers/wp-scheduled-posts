<?php

namespace WPSP\Social;

final class SocialReconnection 
{
    public function __construct()
    {
        add_action('wpsp_profile_reconnect_linkedin', [$this, 'linkedin_reconnect_cron_event']);

        // Fire reconnect related hooks
        add_action('wpsp_linkedin_reconnect_cron_event', [$this, 'linkedin_reconnect'], 10, 1);
    }

    /**
     * Schedule a single event for LinkedIn reconnection
     *
     * @param int $id The ID of the profile to reconnect
     */
    public function linkedin_reconnect_cron_event($id)
    {
        if (!wp_next_scheduled('wpsp_linkedin_reconnect_cron_event', [$id])) {
            $time = time() + 5000; // Schedule after 5000 seconds
            wp_schedule_single_event($time, 'wpsp_linkedin_reconnect_cron_event', [$id]);
        }
    }

    /**
     * Handles LinkedIn reconnection logic
     *
     * @param int $id The ID of the profile to reconnect
     */
    public function linkedin_reconnect($id)
    {
        $profile_id = $id;
        if (empty($profile_id)) {
            error_log('Error: Missing Profile ID in linkedin_reconnect function');
            return;
        }

        error_log('Reconnecting LinkedIn Profile: ' . $profile_id);

        $settings = get_option(WPSP_SETTINGS_NAME, []);
        $settings = json_decode($settings);
        if (empty($settings->linkedin_profile_list) || !is_array($settings->linkedin_profile_list)) {
            error_log('Error: LinkedIn profile list not found in settings.');
            return;
        }

        $profile = null;
        foreach ($settings->linkedin_profile_list as &$p) {
            if (isset($p->id) && $p->id === $profile_id) {
                $profile = &$p;
                break;
            }
        }

        if (!$profile || empty($profile->refresh_token || empty($profile->app_id) || empty($profile->app_secret))) {
            error_log('Error: Profile or refresh token not found.');
            return;
        }

        $response = wp_remote_post('https://www.linkedin.com/oauth/v2/accessToken', [
            'body' => [
                'grant_type'    => 'refresh_token',
                'refresh_token' => $profile->refresh_token,
                'client_id'     => $profile->app_id,
                'client_secret' => $profile->app_secret
            ],
            'headers' => [
                'Content-Type' => 'application/x-www-form-urlencoded'
            ],
            'timeout' => 60
        ]);

        if (is_wp_error($response)) {
            error_log('Error fetching new LinkedIn access token: ' . $response->get_error_message());
            return;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (empty($data['access_token']) || empty($data['expires_in'])) {
            error_log('Error: Invalid response from LinkedIn API: ' . $body);
            return;
        }

        $updated = $this->update_profile_option_data(
            'linkedin_profile_list',
            $profile_id,
            [
                'access_token' => $data['access_token'],
                'expires_in' => time() + $data['expires_in'],
            ]
        );

        if ($updated) {
            error_log('Successfully updated LinkedIn tokens for profile: ' . $profile_id);
        } else {
            error_log('Error updating LinkedIn tokens for profile: ' . $profile_id);
        }
    }
     /**
     * Update specific fields in an array inside the static WordPress option 'wpsp_settings_v5'.
     *
     * @param string $array_key The key inside the option array (e.g., 'linkedin_profile_list').
     * @param string $id The ID to find the specific object.
     * @param array $updates Key-value pairs to update (e.g., ['refresh_token' => 'new_value', 'expires_in' => 123456789]).
     *
     * @return bool True if updated successfully, false otherwise.
    */
    private function update_profile_option_data($array_key, $id, $updates) {
        global $wpsp_settings_v5;
        // Get the existing settings from the static option
        $settings = get_option($wpsp_settings_v5);

        // Check if the key exists and is an array
        if (!empty($settings[$array_key]) && is_array($settings[$array_key])) {
            array_walk($settings[$array_key], function (&$item) use ($id, $updates) {
                if (isset($item->id) && $item->id == $id) {
                    // Update fields dynamically
                    foreach ($updates as $key => $value) {
                        $item->$key = $value;
                    }
                }
            });

            // Save the updated settings
            return update_option($wpsp_settings_v5, $settings);
        }

        return false;
    }
}
