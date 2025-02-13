<?php

namespace WPSP\Social;

final class SocialReconnection 
{
    public function __construct()
    {
        add_action('wpsp_profile_reconnect_linkedin', [$this, 'linkedin_reconnect_cron_event'] );

        // Fire reconnect related hooks
        add_action('wpsp_linkedin_reconnect_cron_event', [$this, 'linkedin_reconnect'] );
    }

    public function linkedin_reconnect_cron_event($id)
    {
        if ( !wp_next_scheduled('wpsp_linkedin_reconnect_cron_event', $id) ) {
            $time = time() + 5000;
            wp_schedule_single_event( $time, 'wpsp_linkedin_reconnect_cron_event', $id );
        }
    }

    public function linkedin_reconnect($profile_id)
    {
        global $wpsp_settings_v5;
    
        // Get stored settings
        $settings = get_option($wpsp_settings_v5);
    
        if (empty($settings['linkedin_profile_list']) || !is_array($settings['linkedin_profile_list'])) {
            return false;
        }
    
        // Find the profile data by ID
        foreach ($settings['linkedin_profile_list'] as $profile) {
            if ($profile->id == $profile_id) {
                $refresh_token = $profile->refresh_token;
                $client_id = $profile->app_id;
                $client_secret = $profile->app_secret;
    
                // Make sure required data exists
                if (!$refresh_token || !$client_id || !$client_secret) {
                    return false;
                }
    
                // LinkedIn API Request
                $response = wp_remote_post('https://www.linkedin.com/oauth/v2/accessToken', [
                    'body' => [
                        'grant_type'    => 'refresh_token',
                        'refresh_token' => $refresh_token,
                        'client_id'     => $client_id,
                        'client_secret' => $client_secret,
                    ],
                    'headers' => [
                        'Content-Type' => 'application/x-www-form-urlencoded',
                    ],
                ]);
    
                // Check if the request was successful
                if (is_wp_error($response)) {
                    return false;
                }
    
                $body = wp_remote_retrieve_body($response);
                $data = json_decode($body, true);
    
                if (!isset($data['access_token']) || !isset($data['expires_in'])) {
                    return false;
                }
    
                // Prepare updated values
                $updates = [
                    'access_token' => $data['access_token'],
                    'expires_in'   => time() + $data['expires_in'], // Convert to UNIX timestamp
                ];
    
                // Call the helper function to update the option
                return $this->update_profile_option_data('linkedin_profile_list', $profile_id, $updates);
            }
        }
    
        return false;
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
