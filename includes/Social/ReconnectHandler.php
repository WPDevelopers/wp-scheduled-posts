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

    /**
     * Reconnect one profile.
     *
     * Always returns an array. Every failure carries success => false plus the
     * HTTP status the REST layer should answer with, so the caller can turn it
     * into a real error response. Nothing here may emit output or die: this
     * runs inside a REST callback, and wp_send_json_*() would exit mid request
     * with a 200 and bypass the REST envelope entirely.
     */
    public static function handleProfileReconnect($platform, $item)
    {
        return self::renew($platform, (array) $item);
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
        return self::renew($platform, (array) $item);
    }

    public static function threadsReconnect($item)
    {
        return self::renew('threads', (array) $item);
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
                'code'        => 'reconnect_unsupported_platform',
                'status'      => 400,
                /* translators: %s: social platform name */
                'message'     => sprintf(__('Reconnect is not supported for %s.', 'wp-scheduled-posts'), $platform),
            ];
        }

        if (empty($item['id'])) {
            return [
                'success'     => false,
                'reconnected' => false,
                'code'        => 'reconnect_missing_profile',
                'status'      => 400,
                'message'     => __('No profile was identified in the request.', 'wp-scheduled-posts'),
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

        $saved = self::update_profile_fields($platform, $item, $updates);
        if (!$saved) {
            // The new token exists at the provider but is not on this site, so
            // sharing would still use the old one. Saying "renewed" here left the
            // author with a green connection that could not post.
            return [
                'success'     => false,
                'reconnected' => false,
                'platform'    => $platform,
                'code'        => 'reconnect_not_saved',
                'status'      => 500,
                'message'     => __('Could not save the renewed connection.', 'wp-scheduled-posts'),
            ];
        }

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

        $saved = self::update_profile_fields($platform, $item, $updates);
        if (!$saved) {
            // The new token exists at the provider but is not on this site, so
            // sharing would still use the old one. Saying "renewed" here left the
            // author with a green connection that could not post.
            return [
                'success'     => false,
                'reconnected' => false,
                'platform'    => $platform,
                'code'        => 'reconnect_not_saved',
                'status'      => 500,
                'message'     => __('Could not save the renewed connection.', 'wp-scheduled-posts'),
            ];
        }

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

        // __id is the per-entry identifier, so when the caller has one it points
        // at exactly one profile. id does not: every Pinterest board of an
        // account is stored as its own entry under the same id (the username),
        // and the token being renewed belongs to the account, not the board. So
        // an id match has to write to all of them — stopping at the first left
        // the remaining boards holding the token that just expired.
        $matches = self::matching_profile_indexes($settings[$key], '__id', $target__id);
        if (empty($matches)) {
            $matches = self::matching_profile_indexes($settings[$key], 'id', $target_id);
        }

        if (empty($matches)) {
            return false;
        }

        $differs = false;
        foreach ($matches as $index) {
            $profile = (array) $settings[$key][$index];
            foreach ($updates as $field => $value) {
                if (!array_key_exists($field, $profile) || $profile[$field] !== $value) {
                    $differs = true;
                }
                $profile[$field] = $value;
            }
            $settings[$key][$index] = $profile;
        }

        // update_option() reports false for a write that changes nothing, which
        // is not a failure: the profile already holds what was about to be
        // written. Renewing a token the provider handed back unchanged lands
        // here, and reporting it as a failed save told the author to reconnect a
        // connection that was fine.
        if (!$differs) {
            return true;
        }

        return update_option(WPSP_SETTINGS_NAME, wp_json_encode($settings));
    }

    /**
     * Indexes of every stored profile whose $field equals $target.
     *
     * @param array  $profiles
     * @param string $field
     * @param string $target
     * @return int[]
     */
    private static function matching_profile_indexes($profiles, $field, $target)
    {
        if ($target === '') {
            return [];
        }

        $found = [];
        foreach ($profiles as $index => $profile) {
            $profile = (array) $profile;
            if (isset($profile[$field]) && (string) $profile[$field] === $target) {
                $found[] = $index;
            }
        }

        return $found;
    }

    /**
     * Fields worth carrying over from a fresh authorisation onto a profile that
     * is already connected. Everything else about the profile - its name, the
     * board or location it posts to, who added it - must survive untouched.
     */
    const CREDENTIAL_FIELDS = [
        'access_token',
        'long_lived_access_token',
        'oauth_token',
        'oauth_token_secret',
        'refresh_token',
        'expires_in',
        'expires_at',
        'rt_expires_in',
    ];

    /**
     * Finish a reconnect that had to go through the provider's consent screen.
     *
     * The normal connect flow hands the fetched accounts to the editor so the
     * author can pick one, which is why it ends on a full page. A reconnect
     * already knows which profile it is renewing, so the matching entry is found
     * here and its credentials copied onto the existing record - no page to
     * return to, and no chance of the profile being duplicated.
     *
     * @param string $platform
     * @param string $profile_id Profile being reconnected.
     * @param mixed  $payload    Whatever the fetch step returned.
     * @return array
     */
    public static function complete_reconnect($platform, $profile_id, $payload)
    {
        if (!isset(self::PROFILE_OPTIONS[$platform]) || $profile_id === '' || $profile_id === null) {
            return [
                'success' => false,
                'message' => __('Reconnect could not be completed.', 'wp-scheduled-posts'),
            ];
        }

        $match = self::find_reauthorised_account($payload, (string) $profile_id);
        if (empty($match)) {
            // The author authorised a different account than the one being
            // reconnected, so nothing here belongs to this profile.
            return [
                'success' => false,
                'mismatch' => true,
                'message' => __('That authorisation was for a different account. Reconnect the profile with the same account it was added with.', 'wp-scheduled-posts'),
            ];
        }

        $updates = [];
        foreach (self::CREDENTIAL_FIELDS as $field) {
            if (isset($match[$field]) && $match[$field] !== '') {
                $updates[$field] = $match[$field];
            }
        }

        if (empty($updates)) {
            return [
                'success' => false,
                'message' => __('The authorisation returned no usable credentials.', 'wp-scheduled-posts'),
            ];
        }

        // Threads measures its lifetime from this, so it has to move with the token.
        if ($platform === 'threads' && isset($updates['expires_in'])) {
            $updates['added_date'] = current_time('mysql');
        }
        $updates[self::RENEWAL_FAILED_FIELD] = false;

        $saved = self::update_profile_fields($platform, ['id' => $profile_id], $updates);
        if (!$saved) {
            return [
                'success' => false,
                'message' => __('Could not save the renewed connection.', 'wp-scheduled-posts'),
            ];
        }

        return [
            'success'     => true,
            'reconnected' => true,
            'platform'    => $platform,
            'message'     => __('Connection renewed.', 'wp-scheduled-posts'),
        ];
    }

    /**
     * Hunt through a fetch response for the account that was just reauthorised.
     *
     * Each platform wraps its accounts differently - pages, groups, boards,
     * profiles, a bare object - so rather than encode all of those shapes this
     * walks the structure for the first entry carrying the id being reconnected.
     *
     * Credentials are carried down the walk because the platforms do not agree
     * on where they belong either: Facebook, Instagram, Threads and Google put a
     * token on every account they return, while LinkedIn returns one token for
     * the whole authorisation and lists the member and their pages underneath
     * it. Without this a LinkedIn reconnect finds its account and no token to go
     * with it. Anything the account carries itself still wins.
     *
     * @param mixed  $payload
     * @param string $profile_id
     * @param array  $inherited Credentials seen further up the response.
     * @return array|null
     */
    private static function find_reauthorised_account($payload, $profile_id, $inherited = [])
    {
        if (is_object($payload)) {
            $payload = (array) $payload;
        }
        if (!is_array($payload)) {
            return null;
        }

        $credentials = $inherited;
        foreach (self::CREDENTIAL_FIELDS as $field) {
            if (isset($payload[$field]) && is_scalar($payload[$field]) && $payload[$field] !== '') {
                $credentials[$field] = $payload[$field];
            }
        }

        $has_id = isset($payload['id']) && (string) $payload['id'] === $profile_id;
        if ($has_id) {
            $account = $payload;
            foreach ($credentials as $field => $value) {
                if (!isset($account[$field]) || $account[$field] === '') {
                    $account[$field] = $value;
                }
            }
            return $account;
        }

        foreach ($payload as $value) {
            if (!is_array($value) && !is_object($value)) {
                continue;
            }
            $found = self::find_reauthorised_account($value, $profile_id, $credentials);
            if (!empty($found)) {
                return $found;
            }
        }

        return null;
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
