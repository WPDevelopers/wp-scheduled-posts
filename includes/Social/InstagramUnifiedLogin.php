<?php

namespace WPSP\Social;

/**
 * Instagram API with Unified Login.
 *
 * Meta's current Instagram setup, and the only one that gets new features:
 * https://developers.facebook.com/documentation/instagram-platform/instagram-api-with-unified-login
 *
 * It replaces Business Login for Instagram almost entirely. The author signs in
 * through the Facebook Login for Business dialog, which is driven by a
 * configuration (`config_id`) set up in the Meta app instead of a `scope` list.
 * The code is exchanged with the Meta app's own ID and secret, and everything
 * after that runs on graph.facebook.com — an Instagram User token and
 * graph.instagram.com do not work here.
 *
 * The token belongs to the client's business portfolio rather than to whoever
 * signed in, and by default it never expires, so there is no renewal to run.
 * One token can cover several Instagram accounts.
 *
 * Profiles connected this way carry `auth_mode` => 'unified'. Profiles that
 * were connected through Business Login for Instagram keep working as before;
 * Meta has not set a retirement date for that setup.
 */
class InstagramUnifiedLogin
{
    const AUTH_MODE = 'unified';

    const GRAPH_URL = 'https://graph.facebook.com/v26.0';

    const DIALOG_URL = 'https://www.facebook.com/dialog/oauth';

    /**
     * A user token that lives less than this is a short-lived one, worth
     * swapping for the 60-day kind. A configuration that issues system-user
     * tokens sends no expiry at all, and those are left alone.
     */
    const SHORT_LIVED_TOKEN_MAX_AGE = DAY_IN_SECONDS;

    /**
     * How often, and how far apart, to ask whether a media container is ready.
     * An image is normally ready on the first check; this only has to cover a
     * slow fetch of the image from the site.
     */
    const STATUS_CHECKS = 10;

    const STATUS_CHECK_INTERVAL = 3;

    /**
     * Was this profile connected through Unified Login?
     *
     * @param array|object $profile
     * @return bool
     */
    public static function is_unified($profile)
    {
        $profile = (array) $profile;
        return isset($profile['auth_mode']) && $profile['auth_mode'] === self::AUTH_MODE;
    }

    /**
     * The Facebook Login for Business dialog for a Unified Login configuration.
     *
     * There is no `scope`: the configuration decides which permissions are
     * asked for. `state` is not in Meta's template but the dialog returns it,
     * and the SchedulePress middleware needs it to find its way back here.
     *
     * The rest mirrors the login URL the App Dashboard generates for the
     * configuration. `prompt=login` is the one that matters: it offers "Log in
     * with Instagram" alongside Facebook. Without it, an author already signed
     * in to Facebook goes straight to the Facebook business-asset picker, which
     * only lists Instagram accounts already added to their business portfolio.
     *
     * @param string $app_id       Meta app ID.
     * @param string $config_id    Configuration ID from the Meta app.
     * @param string $redirect_uri Must match the configuration's redirect URL.
     * @param string $state
     * @return string
     */
    public static function auth_url($app_id, $config_id, $redirect_uri, $state)
    {
        return self::DIALOG_URL . '?' . http_build_query([
            'client_id'                      => $app_id,
            'config_id'                      => $config_id,
            'display'                        => 'popup',
            'override_default_response_type' => 'true',
            'redirect_uri'                   => $redirect_uri,
            'response_type'                  => 'code',
            'state'                          => $state,
            'prompt'                         => 'login',
        ], '', '&', PHP_QUERY_RFC3986);
    }

    /**
     * Swap the authorisation code for an access token.
     *
     * @param string $app_id
     * @param string $app_secret
     * @param string $redirect_uri The one the dialog was opened with.
     * @param string $code
     * @return array{access_token:string,expires_in:int|null}
     * @throws \Exception With Meta's reason when the exchange is refused.
     */
    public static function exchange_code($app_id, $app_secret, $redirect_uri, $code)
    {
        $data = self::get('/oauth/access_token', [
            'client_id'     => $app_id,
            'client_secret' => $app_secret,
            'redirect_uri'  => $redirect_uri,
            'code'          => $code,
        ]);

        if (empty($data['access_token'])) {
            throw new \Exception(self::error_message($data, __('Instagram did not return an access token.', 'wp-scheduled-posts')));
        }

        $token      = $data['access_token'];
        $expires_in = isset($data['expires_in']) ? (int) $data['expires_in'] : null;

        // A configuration set to issue user tokens hands back an hour-long one.
        // Sharing runs on a schedule, so take the 60-day token while the author
        // is still here to have granted it.
        if ($expires_in !== null && $expires_in > 0 && $expires_in < self::SHORT_LIVED_TOKEN_MAX_AGE) {
            $long = self::get('/oauth/access_token', [
                'grant_type'        => 'fb_exchange_token',
                'client_id'         => $app_id,
                'client_secret'     => $app_secret,
                'fb_exchange_token' => $token,
            ]);
            if (!empty($long['access_token'])) {
                $token      = $long['access_token'];
                $expires_in = isset($long['expires_in']) ? (int) $long['expires_in'] : null;
            }
        }

        return [
            'access_token' => $token,
            'expires_in'   => $expires_in ?: null,
        ];
    }

    /**
     * Every Instagram professional account the token can publish to.
     *
     * A system-user token lists the accounts the client shared through
     * `assigned_instagram_accounts`. A user token has no such edge, so it falls
     * back to the accounts linked to the Pages the author manages.
     *
     * `/me` is never the Instagram account here — with a system-user token it
     * is the system user.
     *
     * @param string $access_token
     * @return array<int,array{id:string,name:string,username:string,profile_picture_url:string}>
     * @throws \Exception When neither route answers.
     */
    public static function fetch_accounts($access_token)
    {
        $assigned = self::get('/me/assigned_instagram_accounts', [
            'fields'       => 'id,username',
            'limit'        => 100,
            'access_token' => $access_token,
        ]);

        $accounts = [];
        if (isset($assigned['data']) && is_array($assigned['data'])) {
            foreach ($assigned['data'] as $account) {
                if (empty($account['id'])) {
                    continue;
                }
                $details = self::get('/' . rawurlencode($account['id']), [
                    'fields'       => 'id,name,username,profile_picture_url',
                    'access_token' => $access_token,
                ]);
                $accounts[] = self::normalise_account(!empty($details['id']) ? $details : $account);
            }
        }
        if (!empty($accounts)) {
            return $accounts;
        }

        $pages = self::get('/me/accounts', [
            'fields'       => 'instagram_business_account{id,name,username,profile_picture_url}',
            'limit'        => 100,
            'access_token' => $access_token,
        ]);
        if (isset($pages['data']) && is_array($pages['data'])) {
            foreach ($pages['data'] as $page) {
                if (!empty($page['instagram_business_account']['id'])) {
                    $accounts[] = self::normalise_account($page['instagram_business_account']);
                }
            }
        }

        if (empty($accounts) && !isset($assigned['data']) && !isset($pages['data'])) {
            throw new \Exception(self::error_message($assigned, __('Could not load the Instagram accounts for this login.', 'wp-scheduled-posts')));
        }

        return $accounts;
    }

    /**
     * The business portfolio the token belongs to, when it is a system-user
     * token. Kept with the profile because Meta's token management API is
     * addressed by it. Empty for a user token.
     *
     * @param string $access_token
     * @return string
     */
    public static function fetch_client_business_id($access_token)
    {
        $data = self::get('/me', [
            'fields'       => 'client_business_id',
            'access_token' => $access_token,
        ]);
        return !empty($data['client_business_id']) ? (string) $data['client_business_id'] : '';
    }

    /**
     * Publish one image post.
     *
     * A container is created from the image URL, which Meta fetches itself, so
     * the image has to be publicly reachable and a JPEG. The container is only
     * published once Meta reports it FINISHED; publishing earlier is what the
     * old fixed five-second wait was guessing at.
     *
     * @param string $ig_id     Instagram professional account ID.
     * @param string $token
     * @param string $image_url
     * @param string $caption
     * @return array{success:bool,id?:string,error?:string}
     */
    public static function publish($ig_id, $token, $image_url, $caption)
    {
        if (empty($image_url)) {
            return [
                'success' => false,
                'error'   => __('Instagram needs an image. Add a featured image or a social share image to this post.', 'wp-scheduled-posts'),
            ];
        }

        $container = self::post('/' . rawurlencode($ig_id) . '/media', [
            'image_url'    => $image_url,
            'caption'      => (string) $caption,
            'access_token' => $token,
        ]);
        if (empty($container['id'])) {
            return [
                'success' => false,
                'error'   => self::error_message($container, __('Instagram did not accept the image.', 'wp-scheduled-posts')),
            ];
        }

        $ready = self::wait_for_container($container['id'], $token);
        if ($ready !== true) {
            return ['success' => false, 'error' => $ready];
        }

        $published = self::post('/' . rawurlencode($ig_id) . '/media_publish', [
            'creation_id'  => $container['id'],
            'access_token' => $token,
        ]);
        if (empty($published['id'])) {
            return [
                'success' => false,
                'error'   => self::error_message($published, __('Instagram did not publish the post.', 'wp-scheduled-posts')),
            ];
        }

        return ['success' => true, 'id' => (string) $published['id']];
    }

    /**
     * Wait until a container can be published.
     *
     * @param string $container_id
     * @param string $token
     * @return true|string True when ready, otherwise the reason it is not.
     */
    private static function wait_for_container($container_id, $token)
    {
        $status = '';
        for ($check = 0; $check < self::STATUS_CHECKS; $check++) {
            if ($check > 0) {
                sleep(self::STATUS_CHECK_INTERVAL);
            }

            $data   = self::get('/' . rawurlencode($container_id), [
                'fields'       => 'status_code,status',
                'access_token' => $token,
            ]);
            $status = isset($data['status_code']) ? $data['status_code'] : '';

            if ($status === 'FINISHED') {
                return true;
            }
            if ($status === 'ERROR' || $status === 'EXPIRED') {
                $detail = !empty($data['status']) ? $data['status'] : $status;
                /* translators: %s: Instagram's reason for rejecting the media */
                return sprintf(__('Instagram could not process the image: %s', 'wp-scheduled-posts'), $detail);
            }
            if ($status === '' && isset($data['error'])) {
                return self::error_message($data, __('Could not check the image upload on Instagram.', 'wp-scheduled-posts'));
            }
        }

        return __('Instagram is still processing the image. Try sharing again in a few minutes.', 'wp-scheduled-posts');
    }

    /**
     * @param array $account
     * @return array{id:string,name:string,username:string,profile_picture_url:string}
     */
    private static function normalise_account($account)
    {
        $username = isset($account['username']) ? (string) $account['username'] : '';
        $name     = !empty($account['name']) ? (string) $account['name'] : $username;

        return [
            'id'                  => (string) $account['id'],
            'name'                => $name,
            'username'            => $username,
            'profile_picture_url' => isset($account['profile_picture_url']) ? (string) $account['profile_picture_url'] : '',
        ];
    }

    /**
     * Meta's own explanation where it gave one.
     *
     * @param mixed  $data
     * @param string $fallback
     * @return string
     */
    private static function error_message($data, $fallback)
    {
        if (is_array($data) && isset($data['error']) && is_array($data['error'])) {
            if (!empty($data['error']['error_user_msg'])) {
                return $data['error']['error_user_msg'];
            }
            if (!empty($data['error']['message'])) {
                return $data['error']['message'];
            }
        }
        if (is_array($data) && !empty($data['transport_error'])) {
            return $data['transport_error'];
        }
        return $fallback;
    }

    /**
     * @param string $path
     * @param array  $query
     * @return array Decoded body; `transport_error` when the request never landed.
     */
    private static function get($path, $query)
    {
        $url = self::GRAPH_URL . $path . '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
        return self::decode(wp_remote_get($url, ['timeout' => 30]));
    }

    /**
     * @param string $path
     * @param array  $body
     * @return array Decoded body; `transport_error` when the request never landed.
     */
    private static function post($path, $body)
    {
        return self::decode(wp_remote_post(self::GRAPH_URL . $path, ['timeout' => 60, 'body' => $body]));
    }

    /**
     * @param mixed $response
     * @return array
     */
    private static function decode($response)
    {
        if (is_wp_error($response)) {
            return ['transport_error' => $response->get_error_message()];
        }
        $data = json_decode(wp_remote_retrieve_body($response), true);
        return is_array($data) ? $data : [];
    }
}
