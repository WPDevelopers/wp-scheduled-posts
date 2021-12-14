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
        $this->multiProfileErrorMessage = '<p>' . esc_html__('Multi Profile is a Premium Feature. To use this feature, Upgrade to Pro.', 'wp-scheduled-posts') . '</p><a href="https://wpdeveloper.com/in/wpsp">Upgrade to Pro</a>';
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
     * ajax social multi profile fetch user info and generate token from oauth code
     * @since 2.5.0
     * @return json
     */
    public function social_profile_fetch_user_info_and_token()
    {
        $type = (isset($_POST['type']) ? $_POST['type'] : '');
        $code = (isset($_POST['code']) ? $_POST['code'] : '');
        $app_id = (isset($_POST['appId']) ? $_POST['appId'] : '');
        $app_secret = (isset($_POST['appSecret']) ? $_POST['appSecret'] : '');
        // user
        $current_user = wp_get_current_user();


        if ($type == 'pinterest') {
            try {
                // in this block, we just sending user info and token, not saving in db
                $pinterest = new Pinterest(
                    $app_id,
                    $app_secret
                );
                $token = $pinterest->auth->getOAuthToken($code);
                $pinterest->auth->setOAuthToken($token->access_token);
                $userinfo = $pinterest->users->me(array(
                    'fields' => 'username,first_name,last_name,image[large]'
                ));

                $info = array(
                    'id'            => $userinfo->id,
                    'app_id' => $app_id,
                    'app_secret' => $app_secret,
                    'name'          => $userinfo->first_name . " " . $userinfo->last_name,
                    'thumbnail_url' => $userinfo->image['large']['url'],
                    'status'        => true,
                    'access_token'  => $token->access_token,
                    'added_by'      => $current_user->user_login,
                    'added_date'    => current_time('mysql')
                );
                // if app id is exists then app secret, redirect uri will be also there, it will be delete after approve real app
                if (!empty($app_id)) {
                    $info['app_id']         = $app_id;
                    $info['app_secret']     = $app_secret;
                }


                // get all board list  
                $boardEndPoint      = 'https://api.pinterest.com/v1/me/boards/?access_token=' . $token->access_token . '&fields=id%2Cname%2Curl';
                $boards = wp_remote_get(esc_url_raw($boardEndPoint));
                $response = array(
                    'success' => true,
                    'boards'   => wp_remote_retrieve_body($boards),
                    'type'      => 'pinterest',
                    'data'      => $info
                );
                wp_send_json($response);
                wp_die();
            } catch (\Exception $error) {
                wp_send_json_error($error->getMessage());
                wp_die();
            }
        } else if ($type == 'linkedin') {
            try {
                $linkedin = new LinkedIn(
                    $app_id,
                    $app_secret,
                    WPSP_SOCIAL_OAUTH2_TOKEN_MIDDLEWARE,
                    WPSCP_LINKEDIN_SCOPE,
                    true,
                    null
                );
                $acessToken = $linkedin->getAccessToken($code);
                $getPerson = $linkedin->getPerson($acessToken);

                $image = $getPerson->profilePicture->{'displayImage~'}->elements[0]->identifiers[0]->identifier;
                $info = array(
                    'id' => $getPerson->id,
                    'app_id' => $app_id,
                    'app_secret' => $app_secret,
                    'name' => $getPerson->firstName->localized->en_US . " " . $getPerson->lastName->localized->en_US,
                    'thumbnail_url' => $image,
                    'status' => true,
                    'access_token' => $acessToken,
                    'added_by' => $current_user->user_login,
                    'added_date'    => current_time('mysql')
                );
                // if app id is exists then app secret, redirect uri will be also there, it will be delete after approve real app
                if (!empty($app_id)) {
                    $info['app_id']         = $app_id;
                    $info['app_secret']     = $app_secret;
                }

                $response = array(
                    'success'   => true,
                    'data'      => $info,
                    'type'      => 'linkedin',
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
            } catch (\Exception $e) {
                wp_send_json_error($error->getMessage());
                wp_die();
            }
        } else if ($type == 'facebook' && $code != "") {
            try {
                $tempAccessToken = $this->facebookGetAccessTokenDetails(
                    $app_id,
                    $app_secret,
                    WPSP_SOCIAL_OAUTH2_TOKEN_MIDDLEWARE,
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
                                'access_token' => $userAcessToken,
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


        if ($type == 'pinterest') {
            if (!$this->social_single_profile_checkpoint($type)) {
                wp_send_json_error($this->multiProfileErrorMessage);
                wp_die();
            }
            try {
                $request['redirect_URI'] = esc_url(admin_url('/admin.php?page=' . WPSP_SETTINGS_SLUG));
                $pinterest = new Pinterest(
                    $app_id,
                    $app_secret
                );
                // state
                if (is_array($request)) {
                    $pinterest->auth->setState(json_encode($request));
                }
                $loginurl = $pinterest->auth->getLoginUrl(WPSP_SOCIAL_OAUTH2_TOKEN_MIDDLEWARE, array('read_public', 'write_public', 'read_relationships', 'write_relationships'));
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
                $state = base64_encode(json_encode($request));
                $linkedin = new LinkedIn(
                    $app_id,
                    $app_secret,
                    WPSP_SOCIAL_OAUTH2_TOKEN_MIDDLEWARE,
                    WPSCP_LINKEDIN_SCOPE,
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
                $oauth_callback = WPSP_SOCIAL_OAUTH2_TOKEN_MIDDLEWARE . '?' . http_build_query($request);
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
                    . $app_id . "&redirect_uri=" . urlencode(WPSP_SOCIAL_OAUTH2_TOKEN_MIDDLEWARE) . "&state="
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
