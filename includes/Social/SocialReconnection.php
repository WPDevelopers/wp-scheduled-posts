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
     * Schedule a single event for LinkedIn reconnection one day before token expiry
     *
     * @param int $id The ID of the profile to reconnect
     */
    public function linkedin_reconnect_cron_event($params)
    {
        if( empty( $params['id'] ) ) {
            return;
        }
        // $profile = $this->get_single_profile($params['id'], 'linkedin_profile_list');

        // if (!$profile || empty($profile->expires_in)) {
        //     return;
        // }

        // $expiry_time = intval($profile->expires_in);
        
        $schedule_time = time() + (60 * 60 * 24 * 59); // 2 months - 1 day (59 days in seconds)
        // Ensure the event is scheduled only if it’s not already scheduled
        if (!wp_next_scheduled('wpsp_linkedin_reconnect_cron_event', [$params['id']])) {
            wp_schedule_single_event($schedule_time, 'wpsp_linkedin_reconnect_cron_event', [$params['id']]);
        }
    }


    /**
     * Handles LinkedIn reconnection logic
     *
     * @param any $id The ID of the profile to reconnect
     */
    public function linkedin_reconnect($id)
    {
        $profile_id = $id;
        if (empty($profile_id)) {
            return;
        }

        $settings = get_option(WPSP_SETTINGS_NAME, []);
        $settings = json_decode($settings);
        if (empty($settings->linkedin_profile_list) || !is_array($settings->linkedin_profile_list)) {
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                wp_trigger_error( '', 'Error: LinkedIn profile list not found in settings.', E_USER_WARNING );
            }
            return;
        }

        $profile = null;
        foreach ($settings->linkedin_profile_list as &$p) {
            if (isset($p->id) && ($p->id === $profile_id)) {
                $profile = &$p;
                break;
            }
        }

        if (!$profile || empty($profile->refresh_token || empty($profile->app_id) || empty($profile->app_secret))) {
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
            return;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (empty($data['access_token']) || empty($data['expires_in'])) {
            return;
        }

        $updates = $this->update_profile_option_data(
            'linkedin_profile_list',
            $profile_id,
            [
                'access_token' => $data['access_token'],
                'expires_in' => time() + $data['expires_in'],
            ]
        );
        if( $updates ) {
            $this->linkedin_reconnect_cron_event($profile_id);
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
       // Get the existing settings from the static option
        $settings = get_option(WPSP_SETTINGS_NAME, '{}'); // Default to '{}' if empty
        $settings = json_decode($settings, true); // Decode as an associative array

        // Check if the key exists and is an array
        if (!empty($settings[$array_key]) && is_array($settings[$array_key])) {
            foreach ($settings[$array_key] as &$item) {
                if (isset($item['id']) && $item['id'] == $id) {
                    // Update fields dynamically
                    foreach ($updates as $key => $value) {
                        $item[$key] = $value;
                    }
                }
            }
            unset($item); // Unset reference to avoid accidental modifications
            return update_option(WPSP_SETTINGS_NAME, json_encode($settings));
        }

        return false; // Return false if update didn't happen

    }
}
