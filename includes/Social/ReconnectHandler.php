<?php 

namespace WPSP\Social;

use WPSP\Helper;

class ReconnectHandler
{
    public function __construct()
    {
    }

    /**
     * Reconnect one profile.
     *
     * Always returns an array. Every failure carries success => false plus the
     * HTTP status the REST layer should answer with, so the caller can turn it
     * into a real error response. Nothing here may emit output or die: this
     * runs inside a REST callback, and wp_send_json_*() would exit mid request
     * with a 200 and bypass the REST envelope entirely.
     *
     * An unsupported platform returns an empty array, which the caller reads as
     * "nothing handled this".
     */
    public static function handleProfileReconnect($platform, $item)
    {
        if ($platform == 'instagram') {
            return self::instagramReconnect($item);
        }

        return [];
    }

    public static function instagramReconnect($data)
    {
        if (!is_array($data)) {
            return [
                'success' => false,
                'code'    => 'reconnect_invalid_item',
                'status'  => 400,
                'message' => __('Invalid profile payload.', 'wp-scheduled-posts'),
            ];
        }

        if (empty($data['id'])) {
            return [
                'success' => false,
                'code'    => 'reconnect_missing_profile',
                'status'  => 400,
                'message' => __('No profile was identified in the request.', 'wp-scheduled-posts'),
            ];
        }

        if (empty($data['long_lived_access_token'])) {
            return [
                'success' => false,
                'code'    => 'reconnect_missing_token',
                'status'  => 400,
                'message' => __('No long-lived access token provided.', 'wp-scheduled-posts'),
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
                'code'    => 'reconnect_transport_error',
                'status'  => 502,
                'message' => $response->get_error_message(),
            ];
        }

        $body = wp_remote_retrieve_body($response);
        $result = json_decode($body, true);

        if (isset($result['error'])) {
            return [
                'success' => false,
                'code'    => 'reconnect_instagram_error',
                'status'  => 502,
                'message' => isset($result['error']['message'])
                    ? $result['error']['message']
                    : __('Instagram rejected the reconnect request.', 'wp-scheduled-posts'),
            ];
        }

        if (isset($result['access_token']) && isset($result['expires_in'])) {
            // Update the $data array with new token and expiry date
            $data['long_lived_access_token'] = $result['access_token'];
            $data['expires_at'] = Helper::getDateFromTimezone($result['expires_in']);

            // Instagram handing back a fresh token is only half of a reconnect.
            // Reporting success without checking that the token was written left
            // the stale credential in settings behind a success message, and the
            // next social operation failed with nothing explaining why.
            $saved = self::update_access_token( WPSCP_INSTAGRAM_OPTION_NAME, $data['id'], '', $result['access_token'], $data['expires_at'] );

            if (is_wp_error($saved)) {
                return [
                    'success' => false,
                    'code'    => $saved->get_error_code(),
                    'status'  => 500,
                    'message' => $saved->get_error_message(),
                ];
            }

            return [
                'success' => true,
                'message' => __('Access token refreshed successfully.', 'wp-scheduled-posts'),
                'data'    => $data,
            ];
        }

        return [
            'success' => false,
            'code'    => 'reconnect_unexpected_response',
            'status'  => 502,
            'message' => __('Unexpected response from Instagram API.', 'wp-scheduled-posts'),
        ];
    }

    /**
     * Write a refreshed credential back into the settings option.
     *
     * Returns true on a confirmed write and a WP_Error otherwise. A bare boolean
     * cannot express what happened here: update_option() also returns false when
     * the stored value is byte identical, so "nothing needed saving" and "the
     * database write failed" are the same answer. Callers gate a success message
     * on this, so the difference matters.
     *
     * @return true|\WP_Error
     */
    public static function update_access_token($profile_list_key, $profile_id, $new_access_token = '', $new_long_lived_token = '', $expires_at = '') {
        if( empty( $new_access_token ) && empty( $new_long_lived_token ) ) {
            return new \WP_Error(
                'reconnect_no_token',
                __('No token was supplied to save.', 'wp-scheduled-posts')
            );
        }
        // Step 1: Retrieve the existing option value
        $option_data = get_option(WPSP_SETTINGS_NAME);

        if (!$option_data) {
            return new \WP_Error(
                'reconnect_settings_missing',
                __('Plugin settings could not be read.', 'wp-scheduled-posts')
            );
        }

        // Step 2: Decode the JSON data. The option is expected to be the JSON
        // string written by the settings API, but a damaged/legacy row may be
        // an array or another type. Passing that directly to json_decode()
        // throws a TypeError on supported modern PHP versions.
        if (!is_string($option_data)) {
            return new \WP_Error(
                'reconnect_settings_malformed',
                __('Plugin settings are not in the expected format.', 'wp-scheduled-posts')
            );
        }

        $data = json_decode($option_data, true);
        if (!isset($data[$profile_list_key]) || !is_array($data[$profile_list_key])) {
            return new \WP_Error(
                'reconnect_settings_malformed',
                __('Plugin settings are not in the expected format.', 'wp-scheduled-posts')
            );
        }

        // Step 3: Find and update the specific profile
        $found = false;
        foreach ($data[$profile_list_key] as &$profile) {
            if (isset($profile['id']) && $profile['id'] == $profile_id) {
                $found = true;
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
        unset($profile);

        // An unknown profile id used to fall through and write the settings back
        // untouched, which looked like a successful save.
        if (!$found) {
            return new \WP_Error(
                'reconnect_profile_missing',
                __('That profile is no longer in the saved settings.', 'wp-scheduled-posts')
            );
        }

        // Step 4: Encode the data back to JSON
        $updated_option_data = json_encode($data);

        if (false === $updated_option_data) {
            return new \WP_Error(
                'reconnect_encode_failed',
                __('Updated settings could not be encoded.', 'wp-scheduled-posts')
            );
        }

        // Step 5: Update the wp_options table. update_option() returns false both
        // for a failed write and for a value that did not change, so an unchanged
        // value is confirmed by reading it back rather than treated as a failure.
        if (update_option(WPSP_SETTINGS_NAME, $updated_option_data)) {
            return true;
        }

        if (get_option(WPSP_SETTINGS_NAME) === $updated_option_data) {
            return true;
        }

        return new \WP_Error(
            'reconnect_save_failed',
            __('The refreshed token could not be saved.', 'wp-scheduled-posts')
        );
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
