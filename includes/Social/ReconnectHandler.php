<?php 

namespace WPSP\Social;

use WPSP\Helper;

class ReconnectHandler
{
    public function __construct()
    {
    }

    public static function handleProfileReconnect($platform, $item)
    {
        if ($platform == 'instagram') {
            return self::instagramReconnect($item);
        }
    }

    public static function instagramReconnect($data)
    {
        if (empty($data['long_lived_access_token'])) {
            return [
                'success' => false,
                'message' => 'No long-lived access token provided.',
            ];
        }

        $long_lived_access_token = $data['long_lived_access_token'];

        $url = add_query_arg(
            [
                'grant_type'   => 'ig_refresh_token',
                'access_token' => $long_lived_access_token,
            ],
            'https://graph.instagram.com/refresh_access_token'
        );

        $response = wp_remote_get($url);

        if (is_wp_error($response)) {
            return [
                'success' => false,
                'message' => $response->get_error_message(),
            ];
        }

        $body = wp_remote_retrieve_body($response);
        $result = json_decode($body, true);

        if (isset($result['error'])) {
            return [
                'success' => false,
                'message' => $result['error']['message'],
            ];
        }

        if (isset($result['access_token']) && isset($result['expires_in'])) {
            // Update the $data array with new token and expiry date
            $data['long_lived_access_token'] = $result['access_token'];
            $data['expires_at'] = Helper::getDateFromTimezone($result['expires_in']);
            
            // Save the updated $data to the database (if needed)
            // Assuming you have a function to save the data
            self::update_access_token( WPSCP_INSTAGRAM_OPTION_NAME, $data['id'], '', $result['access_token'], $data['expires_at'] );
            $success = [
                'success' => true,
                'message' => 'Access token refreshed successfully.',
                'data'    => $data,
            ];
            wp_send_json_success($success, 200);
        }

        $error = [
            'success' => false,
            'message' => 'Unexpected response from Instagram API.',
        ];
        wp_send_json_error($error);
    }

    public static function update_access_token($profile_list_key, $profile_id, $new_access_token = '', $new_long_lived_token = '', $expires_at = '') {
        if( empty( $new_access_token ) && empty( $new_long_lived_token ) ) {
            return;
        }
        // Step 1: Retrieve the existing option value
        $option_data = get_option(WPSP_SETTINGS_NAME);

        if (!$option_data) {
            return false; // Option not found
        }

        // Step 2: Decode the JSON data
        $data = json_decode($option_data, true);
        if (!isset($data[$profile_list_key]) || !is_array($data[$profile_list_key])) {
            return false; // Invalid structure
        }

        // Step 3: Find and update the specific profile
        foreach ($data[$profile_list_key] as &$profile) {
            if (isset($profile['id']) && $profile['id'] == $profile_id) {
                if( !empty( $new_access_token ) ) {
                    $profile['access_token'] = $new_access_token;
                }
                if ( !empty( $new_long_lived_token ) ) {
                    $profile['long_lived_access_token'] = $new_long_lived_token;
                }
                if ( !empty( $expires_at ) ) {
                    $profile['expires_at'] = $expires_at;
                }
                break;
            }
        }

        // Step 4: Encode the data back to JSON
        $updated_option_data = json_encode($data);

        // Step 5: Update the wp_options table
        return update_option(WPSP_SETTINGS_NAME, $updated_option_data);
    }

    private static function saveReconnectedProfile($data)
    {
        // Logic to save the updated $data to your database.
        // Example:
        global $wpdb;
        $table_name = $wpdb->prefix . 'instagram_profiles';

        $wpdb->update(
            $table_name,
            [
                'long_lived_access_token' => $data['long_lived_access_token'],
                'expires_at'              => $data['expires_at'],
            ],
            [ 'id' => $data['id'] ]
        );
    }
}
