<?php

namespace WPSP\Social;

use Abraham\TwitterOAuth\TwitterOAuth;
use DirkGroenen\Pinterest\Pinterest;
use myPHPNotes\LinkedIn;


class SocialProfile
{
    private $multiProfileErrorMessage;
    public function __construct()
    {
        /**
         * Social Mulit Profile ajax
         * @since 2.5.0
         */
        add_action('wp_ajax_wpsp_social_add_social_profile', array($this, 'add_social_profile'));
        add_action('wp_ajax_wpsp_social_profile_fetch_user_info_and_token', array($this, 'social_profile_fetch_user_info_and_token'));
        add_action('wp_ajax_wpsp_social_profile_fetch_pinterest_section', array($this, 'social_profile_fetch_pinterest_section'));
        add_action('social_profile_fetch_pinterest_section', array($this, 'social_profile_fetch_pinterest_section'));
        $this->multiProfileErrorMessage = '<p>' . esc_html__('Multi Profile is a Premium Feature. To use this feature, Upgrade to Pro.', 'wp-scheduled-posts') . '</p><a target="_blank" href="https://schedulepress.com/#pricing">Upgrade to Pro</a>';


		$allow_post_types = \WPSP\Helper::get_settings('allow_post_types');
        /** @var array */
		$post_types = (!empty($allow_post_types) ? $allow_post_types : array());

		foreach ($post_types as $key => $post_type) {
            add_action("rest_after_insert_$post_type", function($post, $request){
                $post = $request->get_json_params();
                if(!empty($post['meta']['publishImmediately'])){
                    do_action('wpsp_publish_future_post', (object) [
                        'ID'          => $post['id'],
                        'post_status' => $post['status'],
                    ]);
                }
            }, 10, 3);
        }

        add_action('post_updated', function ($post_ID, $post_after, $post_before) use($post_types) {
            $type = get_post_type($post_after);
            if(in_array($type, $post_types) && !empty($_POST['prevent_future_post']) && $_POST['prevent_future_post'] === "yes"){
                do_action('wpsp_publish_future_post', (object) [
                    'ID'          => $post_ID,
                    'post_status' => $post_after->post_status,
                ]);
            }

        }, 10, 3);

    }

    public function social_single_profile_checkpoint($platform)
    {
        if ($platform == 'pinterest') {
            $social_profile = \WPSP\Helper::get_settings('pinterest_profile_list');
            if (!empty($social_profile) && \count($social_profile) >= 1 && !class_exists('WPSP_PRO')) {
                return false;
            }
        } else if ($platform == 'facebook') {
            $social_profile = \WPSP\Helper::get_settings('facebook_profile_list');
            if (!empty($social_profile) && \count($social_profile) >= 1 && !class_exists('WPSP_PRO')) {
                return false;
            }
        } else if ($platform == 'twitter') {
            $social_profile = \WPSP\Helper::get_settings('twitter_profile_list');
            if (!empty($social_profile) && \count($social_profile) >= 1 && !class_exists('WPSP_PRO')) {
                return false;
            }
        } else if ($platform == 'linkedin') {
            $social_profile = \WPSP\Helper::get_settings('linkedin_profile_list');
            if (!empty($social_profile) && \count($social_profile) >= 1 && !class_exists('WPSP_PRO')) {
                return false;
            }
        }
        return true;
    }


    /**
     * Facebook access token
     */
    public function facebookGetAccessTokenDetails($app_id, $app_secret, $redirect_url, $code)
    {
        $token_url = "https://graph.facebook.com/oauth/access_token?"
            . "client_id=" . $app_id . "&redirect_uri=" . urlencode($redirect_url)
            . "&client_secret=" . $app_secret . "&code=" . $code;

        $response = wp_remote_get($token_url);
        if (is_wp_error($response)) {
            return false;
        } else {
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body);
            return $data->access_token;
        }
        return false;
    }
    /**
     * Facebook User Details
     */
    public function facebookGetUserDetails($access_token)
    {
        $graph_url = "https://graph.facebook.com/me?fields=accounts{name,access_token,picture}&access_token=" . $access_token;

        $response = wp_remote_get($graph_url);
        if (is_wp_error($response)) {
            return false;
        } else {
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body);
            return $data;
        }
        return null;
    }
    /**
     * Facebook user group Details
     */
    public function facebookGetGroupDetails($access_token)
    {
        $graph_url = "https://graph.facebook.com/me/groups?fields=id,name,administrator,picture&access_token=" . $access_token;

        $response = wp_remote_get($graph_url);
        if (is_wp_error($response)) {
            return false;
        } else {
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body);
            return $data;
        }
        return null;
    }

    /**
     * Facebook user group Details
     */
    public function social_profile_fetch_pinterest_section($params)
    {
       if( wp_doing_ajax() ) {
        $params = $_POST;
       }
        $defaultBoard = (isset($params['defaultBoard']) ? $params['defaultBoard'] : '');
        $profile = (isset($params['profile']) ? $params['profile'] : '');
        if(!is_array($profile)){
            $pinterest = \WPSP\Helper::get_social_profile(WPSCP_PINTEREST_OPTION_NAME);
            $profile = (array) $pinterest[(int) $profile];
        }
        
        $pinterest = new \DirkGroenen\Pinterest\Pinterest($profile['app_id'], $profile['app_secret']);
        $pinterest->auth->setOAuthToken($profile['access_token']);
        $sections = $pinterest->sections->get($defaultBoard, [
            'page_size' => 100,
        ]);
        $sections = $sections->toArray();

        wp_send_json_success($sections['data']);
        wp_die();
    }

    /**
     * ajax social multi profile fetch user info and generate token from oauth code
     * @since 2.5.0
     * @return json
     */
    public function social_profile_fetch_user_info_and_token()
    {
        $type          = (isset($_POST['type']) ? $_POST['type'] : '');
        $code          = (isset($_POST['code']) ? $_POST['code'] : '');
        $app_id        = (isset($_POST['appId']) ? $_POST['appId'] : '');
        $app_secret    = (isset($_POST['appSecret']) ? $_POST['appSecret'] : '');
        $redirectURI   = (isset($_POST['redirectURI']) ? $_POST['redirectURI'] : '');
        $access_token  = (isset($_POST['access_token']) ? $_POST['access_token'] : '');
        $refresh_token = (isset($_POST['refresh_token']) ? $_POST['refresh_token'] : '');
        $expires_in    = (isset($_POST['expires_in']) ? $_POST['expires_in'] : '');
        $rt_expires_in = (isset($_POST['rt_expires_in']) ? $_POST['rt_expires_in'] : '');
        // user
        $current_user = wp_get_current_user();


        if ($type == 'pinterest') {
            try {
                // in this block, we just sending user info and token, not saving in db
                $pinterest = new Pinterest(
                    $app_id,
                    $app_secret
                );
                if(empty($access_token) && !empty($code)){
                    $token         = $pinterest->auth->getOAuthToken($code, $redirectURI);
                    $access_token  = $token->access_token;
                    $refresh_token = $token->refresh_token;
                    $expires_in    = time() + $token->expires_in;
                    $rt_expires_in = time() + $token->refresh_token_expires_in;
                }
                $pinterest->auth->setOAuthToken($access_token);
                $userinfo = $pinterest->users->me();

                $info = array(
                    'id'            => $userinfo->username,
                    'app_id'        => $app_id,
                    'app_secret'    => $app_secret,
                    'name'          => $userinfo->username,
                    'website_url'   => $userinfo->website_url,
                    'account_type'  => $userinfo->account_type,
                    'thumbnail_url' => $userinfo->profile_image,
                    'status'        => true,
                    'redirectURI'   => $redirectURI,
                    'access_token'  => $access_token,
                    'refresh_token' => $refresh_token,
                    'expires_in'    => $expires_in,
                    'rt_expires_in' => $rt_expires_in,
                    'added_by'      => $current_user->user_login,
                    'added_date'    => current_time('mysql')
                );
                // if app id is exists then app secret, redirect uri will be also there, it will be delete after approve real app
                if (!empty($app_id)) {
                    $info['app_id']         = $app_id;
                    $info['app_secret']     = $app_secret;
                }


                // get all board list
                $boards_arr = [];
                $page_size  = 1;
                $_boards_arr = ['page' => null];
                do {
                    $boards      = $pinterest->users->getMeBoards(array(
                        'page_size' => $page_size,
                        'bookmark'  => $_boards_arr['page'],
                    ));
                    $_boards_arr = $boards->toArray();
                    $boards_arr  = array_merge($boards_arr, $_boards_arr['data']);
                }
                while(!empty($_boards_arr['page']));

                $response = array(
                    'success' => true,
                    'boards'  => $boards_arr,
                    'type'    => 'pinterest',
                    'data'    => $info
                );
                wp_send_json($response);
                wp_die();
            } catch (\Exception $error) {
                wp_send_json_error($error->getMessage());
                wp_die();
            }
        } else if ($type == 'linkedin') {
            try {
                $app_id = $app_id ? $app_id : WPSP_SOCIAL_OAUTH2_LINKEDIN_APP_ID;
                $linkedin = new LinkedIn(
                    $app_id,
                    $app_secret,
                    $redirectURI,
                    null,
                    true,
                    null
                );
                if( (empty($access_token) || $access_token == 'null') && !empty($code)){
                    $accessToken = $linkedin->getAccessToken($code);
                    $access_token = $accessToken->access_token;
                }
                $pages    = $linkedin->getCompanyPages($access_token);
                $profiles = $linkedin->getPerson($access_token);

                $info = array(
                    'app_id'       => $app_id,
                    'app_secret'   => $app_secret,
                    'status'       => true,
                    'redirectURI'  => $redirectURI,
                    'access_token' => $access_token,
                    'expires_in'   => $expires_in,
                    'profiles'     => [$profiles],
                    'pages'        => $pages,
                    'added_by'     => $current_user->user_login,
                    'added_date'   => current_time('mysql'),
                );
                // if app id is exists then app secret, redirect uri will be also there, it will be delete after approve real app
                if (!empty($app_id)) {
                    $info['app_id']         = $app_id;
                    $info['app_secret']     = $app_secret;
                }

                $response = array(
                    'success'  => true,
                    'linkedin' => $info,
                    'type'     => 'linkedin',
                );
                wp_send_json($response);
                wp_die();
            } catch (\Exception $error) {
                wp_send_json_error($error->getMessage());
                wp_die();
            }
        } else if ($type == 'twitter') {
            $oauthToken = (isset($_POST['oauthToken']) ? $_POST['oauthToken'] : '');
            $oauthVerifier = (isset($_POST['oauthVerifier']) ? $_POST['oauthVerifier'] : '');

            try {
                $connection = new TwitterOAuth(
                    $app_id,
                    $app_secret,
                    $oauthToken,
                    $oauthVerifier
                );
                $access_token = $connection->oauth(
                    "oauth/access_token",
                    ["oauth_verifier" => $oauthVerifier]
                );



                // get user data
                $connection = new TwitterOAuth(
                    $app_id,
                    $app_secret,
                    $access_token['oauth_token'],
                    $access_token['oauth_token_secret']
                );
                $content = $connection->get("account/verify_credentials");

                if (is_array($access_token) && count($access_token) > 0) {
                    $info = array(
                        'id' => $content->id,
                        'app_id' => $app_id,
                        'app_secret' => $app_secret,
                        'name' => $content->name,
                        'thumbnail_url' => $content->profile_image_url,
                        'status' => true,
                        'oauth_token' => $access_token['oauth_token'],
                        'oauth_token_secret' => $access_token['oauth_token_secret'],
                        'added_by' => $current_user->user_login,
                        'added_date'    => current_time('mysql')
                    );
                    // if app id is exists then app secret, redirect uri will be also there, it will be delete after approve real app
                    if (!empty($app_id)) {
                        $info['app_id']         = $app_id;
                        $info['app_secret']     = $app_secret;
                    }
                }

                $response = array(
                    'success'   => true,
                    'data'      => $info,
                    'type'      => 'twitter',
                );
                wp_send_json($response);
                wp_die();
            } catch (\Exception $error) {
                wp_send_json_error($error->getMessage());
                wp_die();
            }
        } else if ($type == 'facebook' && $code != "") {
            try {
                $tempAccessToken = $this->facebookGetAccessTokenDetails(
                    $app_id,
                    $app_secret,
                    $redirectURI,
                    $code
                );

                if ($tempAccessToken != "") {
                    $response = wp_remote_get('https://graph.facebook.com/v6.0/oauth/access_token?grant_type=fb_exchange_token&client_id=' . $app_id . '&client_secret=' . $app_secret . '&fb_exchange_token=' . $tempAccessToken . '');
                    if (is_array($response)) {
                        $header = $response['headers']; // array of http header lines
                        $body = $response['body']; // use the content
                    }
                    $longAcessTokenBody = json_decode($body);
                    $userAcessToken = $longAcessTokenBody->{'access_token'};
                }

                $userInfo = $this->facebookGetUserDetails($tempAccessToken);
                $groupInfo = $this->facebookGetGroupDetails($tempAccessToken);

                // page
                $page_array = array();
                if (is_array($userInfo->accounts->data) && count($userInfo->accounts->data) > 0) {
                    foreach ($userInfo->accounts->data as $page_item) {
                        array_push($page_array, array(
                            'id' => $page_item->id,
                            'app_id' => $app_id,
                            'app_secret' => $app_secret,
                            'name' => $page_item->name,
                            'thumbnail_url' => $page_item->picture->data->url,
                            'type' => 'page',
                            'status' => true,
                            'access_token' => $page_item->access_token,
                            'added_by' => $current_user->user_login,
                            'added_date'    => current_time('mysql')
                        ));
                    }
                }


                // group
                $group_array = array();
                if (is_array($groupInfo->data) && count($groupInfo->data) > 0) {
                    foreach ($groupInfo->data as $group) {
                        if ($group->administrator === true) {
                            array_push($group_array, array(
                                'id' => $group->id,
                                'app_id' => $app_id,
                                'app_secret' => $app_secret,
                                'name' => $group->name,
                                'thumbnail_url' => $group->picture->data->url,
                                'type' => 'group',
                                'status' => true,
                                'access_token' => $access_token,
                                'added_by' => $current_user->user_login,
                                'added_date'    => current_time('mysql')
                            ));
                        }
                    }
                }
                // response
                $response = array(
                    'success' => true,
                    'page'   => $page_array,
                    'group'   => $group_array,
                    'type'      => 'facebook',
                );
                wp_send_json($response);
                wp_die();
            } catch (\Exception $error) {
                wp_send_json_error($error->getMessage());
                wp_die();
            }
        }
        wp_send_json_error("Option name and request type missing. please try again");
        wp_die();
    }

    /**
     * Add Social Profile
     */
    public function add_social_profile()
    {
        $request = $_POST;
        $type = (isset($_POST['type']) ? $_POST['type'] : '');
        $app_id = (isset($_POST['appId']) ? $_POST['appId'] : '');
        $app_secret = (isset($_POST['appSecret']) ? $_POST['appSecret'] : '');
        $redirectURI = (isset($_POST['redirectURI']) ? $_POST['redirectURI'] : '');


        if ($type == 'pinterest') {
            if (!$this->social_single_profile_checkpoint($type)) {
                wp_send_json_error($this->multiProfileErrorMessage);
                wp_die();
            }
            try {
                $request['redirect_URI'] = esc_url(admin_url('/admin.php?page=' . WPSP_SETTINGS_SLUG));
                $pinterest = new Pinterest(
                    $app_id ? $app_id : WPSP_SOCIAL_OAUTH2_PINTEREST_APP_ID,
                    $app_secret // unnecessary
                );
                // state
                if (is_array($request)) {
                    $pinterest->auth->setState(json_encode($request));
                }
                $loginurl = $pinterest->auth->getLoginUrl($redirectURI, array('boards:read', 'boards:write', 'pins:read', 'pins:write', 'user_accounts:read'));
                wp_send_json_success($loginurl);
                wp_die();
            } catch (\Exception $error) {
                wp_send_json_error($error->getMessage());
                wp_die();
            }
        } else if ($type == 'linkedin') {
            if (!$this->social_single_profile_checkpoint($type)) {
                wp_send_json_error($this->multiProfileErrorMessage);
                wp_die();
            }
            try {
                $request['redirect_URI'] = esc_url(admin_url('/admin.php?page=' . WPSP_SETTINGS_SLUG));
                $request['appId'] = $app_id ? $app_id : WPSP_SOCIAL_OAUTH2_LINKEDIN_APP_ID;
                $state = base64_encode(json_encode($request));
                $linkedin = new LinkedIn(
                    $request['appId'],
                    $app_secret,  // unnecessary
                    $redirectURI,
                    urlencode($request['appId'] === WPSP_SOCIAL_OAUTH2_LINKEDIN_APP_ID ? WPSCP_LINKEDIN_BUSINESS_SCOPE : WPSCP_LINKEDIN_SCOPE),
                    true,
                    $state
                );
                wp_send_json_success($linkedin->getAuthUrl());
                wp_die();
            } catch (\Exception $error) {
                wp_send_json_error($error->getMessage());
                wp_die();
            }
        } else if ($type == 'twitter') {
            if (!$this->social_single_profile_checkpoint($type)) {
                wp_send_json_error($this->multiProfileErrorMessage);
                wp_die();
            }
            try {
                $request['redirect_URI'] = esc_url(admin_url('/admin.php?page=' . WPSP_SETTINGS_SLUG));
                $connection = new TwitterOAuth(
                    $app_id,
                    $app_secret
                );
                $oauth_callback = $redirectURI . '?' . http_build_query($request);
                $request_token = $connection->oauth('oauth/request_token', array('oauth_callback' => $oauth_callback));
                $url = $connection->url('oauth/authorize', array('oauth_token' => $request_token['oauth_token']));
                wp_send_json_success($url);
                wp_die();
            } catch (\Exception $error) {
                wp_send_json_error($error->getMessage());
                wp_die();
            }
        } else if ($type == 'facebook') {
            if (!$this->social_single_profile_checkpoint($type)) {
                wp_send_json_error($this->multiProfileErrorMessage);
                wp_die();
            }
            try {
                $request['redirect_URI'] = esc_url(admin_url('/admin.php?page=' . WPSP_SETTINGS_SLUG));
                $state = base64_encode(json_encode($request));
                $url = "https://www.facebook.com/dialog/oauth?client_id="
                    . $app_id . "&redirect_uri=" . urlencode($redirectURI) . "&state="
                    . $state . "&scope=" . WPSCP_FACEBOOK_SCOPE;
                wp_send_json_success($url);
                wp_die();
            } catch (\Exception $error) {
                wp_send_json_error($error->getMessage());
                wp_die();
            }
        }
    }
}
