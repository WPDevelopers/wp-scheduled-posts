<?php 

namespace WPSP\Social;

use WPSP\Helper;

class ReconnectHandler
{
    public function __construct()
    {
    }

    /**
     * Option name holding each platform's connected profiles.
     */
    const PROFILE_OPTIONS = [
        'facebook'        => 'facebook_profile_list',
        'twitter'         => 'twitter_profile_list',
        'linkedin'        => 'linkedin_profile_list',
        'pinterest'       => 'pinterest_profile_list',
        'instagram'       => 'instagram_profile_list',
        'threads'         => 'threads_profile_list',
        'google_business' => 'google_business_profile_list',
        'bluesky'         => 'bluesky_profile_list',
        'mastodon'        => 'mastodon_profile_list',
    ];

    public static function handleProfileReconnect($platform, $item)
    {
        wp_send_json_success(self::renew($platform, (array) $item));
    }

    /**
     * How long before a token lapses the background job should renew it.
     *
     * Deliberately generous: a renewal that runs a week early costs one request,
     * while one that runs late costs the author a manual reconnect and, on Meta,
     * loses the grant permanently.
     */
    const RENEW_LEAD_TIME = [
        'linkedin'        => 7  * DAY_IN_SECONDS,
        'pinterest'       => 5  * DAY_IN_SECONDS,
        'instagram'       => 10 * DAY_IN_SECONDS,
        'threads'         => 10 * DAY_IN_SECONDS,
        // Google hands out one-hour access tokens, so the clock this reads is
        // almost always nearly out. What actually has to stay alive is the
        // refresh grant, and exercising it once a day is what proves it.
        'google_business' => 12 * HOUR_IN_SECONDS,
    ];

    /**
     * Marks a profile whose last automatic renewal failed, so the UI can offer a
     * manual reconnect instead of pretending the connection is healthy.
     */
    const RENEWAL_FAILED_FIELD = 'renewal_failed';

    public static function refreshTokenReconnect($platform, $item)
    {
        wp_send_json_success(self::renew($platform, (array) $item));
    }

    public static function threadsReconnect($item)
    {
        wp_send_json_success(self::renew('threads', (array) $item));
    }

    /**
     * Renew one profile's credentials in place, without any user interaction.
     *
     * Shared by the reconnect button and the background maintenance job, so both
     * behave identically. Returns rather than emitting JSON precisely so the cron
     * path can use it.
     *
     * @param string $platform
     * @param array  $item
     * @return array
     */
    public static function renew($platform, $item)
    {
        $item = (array) $item;

        if (!isset(self::PROFILE_OPTIONS[$platform])) {
            return [
                'success'     => false,
                'reconnected' => false,
                /* translators: %s: social platform name */
                'message'     => sprintf(__('Reconnect is not supported for %s.', 'wp-scheduled-posts'), $platform),
            ];
        }

        // Meta extends these long-lived tokens in place; there is no refresh
        // token and no consent screen involved.
        if ($platform === 'threads') {
            return self::renew_meta_long_lived($item, 'threads', 'https://graph.threads.net/refresh_access_token', 'th_refresh_token');
        }
        if ($platform === 'instagram') {
            return self::renew_meta_long_lived($item, 'instagram', 'https://graph.instagram.com/refresh_access_token', 'ig_refresh_token');
        }

        // A Facebook Page token carries no expiry and has no renewal endpoint —
        // it only ever dies by revocation, which needs a human.
        if (!isset(self::RENEW_LEAD_TIME[$platform])) {
            return self::authRequiredResponse($platform, $item, __('This platform has no automatic renewal.', 'wp-scheduled-posts'));
        }

        $refresh_token = !empty($item['refresh_token']) ? $item['refresh_token'] : '';
        if (empty($refresh_token)) {
            return self::authRequiredResponse($platform, $item);
        }

        $middleware = !empty($item['redirectURI'])
            ? $item['redirectURI']
            : WPSP_SOCIAL_OAUTH2_TOKEN_MIDDLEWARE_DEV;

        $response = wp_remote_post($middleware, [
            'timeout' => 30,
            'body'    => [
                'type'          => $platform,
                'refresh_token' => $refresh_token,
                'client_id'     => !empty($item['app_id']) ? $item['app_id'] : '',
            ],
        ]);

        if (is_wp_error($response)) {
            // A network blip is not a dead grant, so this is reported without
            // marking the profile as needing re-authorisation.
            return [
                'success'     => false,
                'reconnected' => false,
                'transient'   => true,
                'platform'    => $platform,
                'message'     => $response->get_error_message(),
            ];
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);
        if (!is_array($data) || empty($data['access_token'])) {
            $reason = '';
            if (is_array($data)) {
                if (!empty($data['error_description'])) {
                    $reason = $data['error_description'];
                } elseif (!empty($data['error']) && is_string($data['error'])) {
                    $reason = $data['error'];
                }
            }
            return self::authRequiredResponse($platform, $item, $reason);
        }

        $expires_in = isset($data['expires_in']) ? (int) $data['expires_in'] : 3600;
        $updates    = [
            'access_token'             => $data['access_token'],
            'expires_in'               => time() + $expires_in,
            self::RENEWAL_FAILED_FIELD => false,
        ];
        if (!empty($data['refresh_token'])) {
            $updates['refresh_token'] = $data['refresh_token'];
        }
        if (!empty($data['refresh_token_expires_in'])) {
            $updates['rt_expires_in'] = time() + (int) $data['refresh_token_expires_in'];
        }

        self::update_profile_fields($platform, $item, $updates);

        return [
            'success'     => true,
            'reconnected' => true,
            'platform'    => $platform,
            'expires_in'  => $updates['expires_in'],
            'message'     => __('Connection renewed.', 'wp-scheduled-posts'),
        ];
    }

    /**
     * Extend one of Meta's long-lived tokens in place.
     *
     * Instagram and Threads share this contract exactly, down to the 24-hour
     * minimum age and the 60-day window, so they share the implementation.
     *
     * @param array  $item
     * @param string $platform
     * @param string $endpoint
     * @param string $grant_type
     * @return array
     */
    private static function renew_meta_long_lived($item, $platform, $endpoint, $grant_type)
    {
        $token = !empty($item['long_lived_access_token']) ? $item['long_lived_access_token'] : '';
        if (empty($token)) {
            return self::authRequiredResponse($platform, $item);
        }

        $response = wp_remote_get(add_query_arg([
            'grant_type'   => $grant_type,
            'access_token' => $token,
        ], $endpoint), ['timeout' => 30]);

        if (is_wp_error($response)) {
            return [
                'success'     => false,
                'reconnected' => false,
                'transient'   => true,
                'platform'    => $platform,
                'message'     => $response->get_error_message(),
            ];
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);
        if (!is_array($data) || empty($data['access_token'])) {
            $reason = is_array($data) && !empty($data['error']['message']) ? $data['error']['message'] : '';
            return self::authRequiredResponse($platform, $item, $reason);
        }

        $expires_in = isset($data['expires_in']) ? (int) $data['expires_in'] : 5184000;
        $updates    = [
            'long_lived_access_token'  => $data['access_token'],
            self::RENEWAL_FAILED_FIELD => false,
        ];

        if ($platform === 'instagram') {
            // Instagram's record holds a formatted date rather than a lifetime.
            $updates['expires_at'] = Helper::getDateFromTimezone($expires_in);
        } else {
            // Threads holds the raw lifetime, which is only meaningful against
            // added_date — so the anchor has to move with the token.
            $updates['expires_in'] = $expires_in;
            $updates['added_date'] = current_time('mysql');
        }

        self::update_profile_fields($platform, $item, $updates);

        return [
            'success'     => true,
            'reconnected' => true,
            'platform'    => $platform,
            'message'     => __('Connection renewed.', 'wp-scheduled-posts'),
        ];
    }

    private static function authRequiredResponse($platform, $item, $reason = '')
    {
        $shared_app_ids = [
            'linkedin'        => defined('WPSP_SOCIAL_OAUTH2_LINKEDIN_APP_ID') ? WPSP_SOCIAL_OAUTH2_LINKEDIN_APP_ID : '',
            'pinterest'       => defined('WPSP_SOCIAL_OAUTH2_PINTEREST_APP_ID') ? WPSP_SOCIAL_OAUTH2_PINTEREST_APP_ID : '',
            'google_business' => defined('WPSP_SOCIAL_OAUTH2_GOOGLE_BUSINESS_APP_ID') ? WPSP_SOCIAL_OAUTH2_GOOGLE_BUSINESS_APP_ID : '',
        ];

        $app_id     = !empty($item['app_id']) ? $item['app_id'] : '';
        $app_secret = !empty($item['app_secret']) ? $item['app_secret'] : '';

        // Only these three ever offered the automatic button, so everything else
        // is manual by construction no matter what the stored fields look like.
        $is_automatic = isset($shared_app_ids[$platform])
            && empty($app_secret)
            && ($app_id === '' || $app_id === $shared_app_ids[$platform]);

        return [
            'success'     => true,
            'reconnected' => false,
            'needs_auth'  => true,
            'platform'    => $platform,
            'method'      => $is_automatic ? 'automatic' : 'manual',
            // The automatic route deliberately sends neither, so the server can
            // fall back to the plugin's own app.
            'app_id'      => $is_automatic ? '' : $app_id,
            'app_secret'  => $is_automatic ? '' : $app_secret,
            'reason'      => $reason,
            'message'     => __('This connection has to be authorised again.', 'wp-scheduled-posts'),
        ];
    }

    /**
     * Write changed fields back onto one stored profile.
     *
     * @param string $platform
     * @param array  $item    Identifies the profile (by id, falling back to __id).
     * @param array  $updates
     * @return bool
     */
    private static function update_profile_fields($platform, $item, $updates)
    {
        if (!isset(self::PROFILE_OPTIONS[$platform])) {
            return false;
        }

        $settings = json_decode(get_option(WPSP_SETTINGS_NAME), true);
        $key      = self::PROFILE_OPTIONS[$platform];
        if (!is_array($settings) || empty($settings[$key]) || !is_array($settings[$key])) {
            return false;
        }

        $target_id  = isset($item['id']) ? (string) $item['id'] : '';
        $target__id = isset($item['__id']) ? (string) $item['__id'] : '';
        $changed    = false;

        foreach ($settings[$key] as &$profile) {
            $profile_id  = isset($profile['id']) ? (string) $profile['id'] : '';
            $profile__id = isset($profile['__id']) ? (string) $profile['__id'] : '';
            if (($target_id !== '' && $profile_id === $target_id)
                || ($target__id !== '' && $profile__id === $target__id)) {
                foreach ($updates as $field => $value) {
                    $profile[$field] = $value;
                }
                $changed = true;
                break;
            }
        }
        unset($profile);

        if (!$changed) {
            return false;
        }

        return update_option(WPSP_SETTINGS_NAME, wp_json_encode($settings));
    }

    /**
     * Cron hook for the once-daily connection maintenance pass.
     */
    const MAINTENANCE_HOOK = 'wpsp_social_connection_maintenance';

    /**
     * Set to the time of the first maintenance pass, so it only ever runs once
     * on demand rather than on every request.
     */
    const INITIAL_CHECK_OPTION = 'wpsp_social_connection_initial_check';

    /**
     * Keep the daily maintenance pass queued, and make the very first one due
     * straight away.
     *
     * A site updating to this version may already be carrying lapsed
     * connections, and those are exactly the ones worth repairing before the
     * author next tries to share. Waiting a full day — or even an hour — to
     * notice would mean shipping a fix that does nothing on the sites that need
     * it most. After that first pass the event settles into its daily rhythm.
     *
     * One event for the whole site, not one per profile.
     *
     * @return void
     */
    public static function schedule_maintenance()
    {
        if (get_option(self::INITIAL_CHECK_OPTION) === false) {
            // Replace anything an earlier build queued, so the first pass is not
            // stuck behind its old start time.
            wp_clear_scheduled_hook(self::MAINTENANCE_HOOK);
            wp_schedule_event(time(), 'daily', self::MAINTENANCE_HOOK);
            update_option(self::INITIAL_CHECK_OPTION, time(), false);
            return;
        }

        if (!wp_next_scheduled(self::MAINTENANCE_HOOK)) {
            wp_schedule_event(time() + HOUR_IN_SECONDS, 'daily', self::MAINTENANCE_HOOK);
        }
    }

    /**
     * Renew every connection that is close enough to lapsing to be worth a call.
     *
     * Runs once a day and makes no outbound request at all for profiles that are
     * still comfortably valid, so on a typical site most days cost nothing beyond
     * reading one option. A profile that cannot be renewed is flagged so the
     * editor can offer a manual reconnect rather than silently failing at share
     * time.
     *
     * Sharing is deliberately untouched by this: it only ever rewrites the token
     * fields on a profile that is already connected.
     *
     * @return array Counts, for logging and tests.
     */
    public static function run_maintenance()
    {
        $settings = json_decode(get_option(WPSP_SETTINGS_NAME), true);
        if (!is_array($settings)) {
            return ['checked' => 0, 'renewed' => 0, 'failed' => 0, 'skipped' => 0];
        }

        $report = ['checked' => 0, 'renewed' => 0, 'failed' => 0, 'skipped' => 0];

        foreach (self::RENEW_LEAD_TIME as $platform => $lead_time) {
            $key = self::PROFILE_OPTIONS[$platform];
            if (empty($settings[$key]) || !is_array($settings[$key])) {
                continue;
            }

            foreach ($settings[$key] as $profile) {
                $profile = (array) $profile;
                // A profile the author switched off is not sharing anything, so
                // there is nothing to keep alive.
                if (isset($profile['status']) && !$profile['status']) {
                    continue;
                }

                $report['checked']++;

                if (!self::needs_renewal($platform, $profile, $lead_time)) {
                    $report['skipped']++;
                    continue;
                }

                // Nothing to renew with — a LinkedIn grant issued without a
                // refresh token, for instance. Flagging it here would claim the
                // connection had expired while it still had days left to run, so
                // it is left to lapse honestly and go red on its own date.
                if (!self::has_renewal_credential($platform, $profile)) {
                    $report['skipped']++;
                    continue;
                }

                $result = self::renew($platform, $profile);

                if (!empty($result['reconnected'])) {
                    $report['renewed']++;
                    continue;
                }

                // A network failure is not a dead grant — leave the profile alone
                // and try again tomorrow rather than telling the author to
                // reconnect over a timeout.
                if (!empty($result['transient'])) {
                    continue;
                }

                self::update_profile_fields($platform, $profile, [
                    self::RENEWAL_FAILED_FIELD => true,
                ]);
                $report['failed']++;
            }
        }

        return $report;
    }

    /**
     * Does this profile hold something the provider will actually renew?
     *
     * Meta extends its own long-lived token; everyone else needs a refresh token.
     *
     * @param string $platform
     * @param array  $profile
     * @return bool
     */
    public static function has_renewal_credential($platform, $profile)
    {
        if ($platform === 'instagram' || $platform === 'threads') {
            return !empty($profile['long_lived_access_token']);
        }

        return !empty($profile['refresh_token']);
    }

    /**
     * Is this profile close enough to expiry to be worth renewing now?
     *
     * @param string $platform
     * @param array  $profile
     * @param int    $lead_time
     * @return bool
     */
    public static function needs_renewal($platform, $profile, $lead_time = null)
    {
        if ($lead_time === null) {
            $lead_time = isset(self::RENEW_LEAD_TIME[$platform]) ? self::RENEW_LEAD_TIME[$platform] : DAY_IN_SECONDS;
        }

        $expires_at = self::resolve_expiry($platform, $profile);
        if ($expires_at === null) {
            // Nothing recorded to measure against. Renew only where a renewal is
            // actually possible, so this cannot turn into a daily no-op request.
            return !empty($profile['refresh_token']) || !empty($profile['long_lived_access_token']);
        }

        return ($expires_at - time()) <= $lead_time;
    }

    /**
     * Normalise the three shapes the platforms store their expiry in.
     *
     * Pinterest, LinkedIn and Google Business hold an absolute timestamp;
     * Threads holds a lifetime that only means anything against added_date;
     * Instagram holds a formatted date string.
     *
     * @param string $platform
     * @param array  $profile
     * @return int|null Unix timestamp, or null when nothing is recorded.
     */
    public static function resolve_expiry($platform, $profile)
    {
        if (!empty($profile['expires_at'])) {
            $parsed = strtotime($profile['expires_at']);
            return $parsed ? $parsed : null;
        }

        if (empty($profile['expires_in'])) {
            return null;
        }

        $raw = (int) $profile['expires_in'];
        if ($raw > 1000000000) {
            return $raw;
        }

        if (empty($profile['added_date'])) {
            return null;
        }

        $added = strtotime($profile['added_date']);
        return $added ? $added + $raw : null;
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
