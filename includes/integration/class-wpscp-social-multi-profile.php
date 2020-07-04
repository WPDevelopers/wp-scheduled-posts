<?php

use Abraham\TwitterOAuth\TwitterOAuth;
use DirkGroenen\Pinterest\Pinterest;
use myPHPNotes\LinkedIn;

if (!class_exists('wpscp_social_multi_profile')) {
    class wpscp_social_multi_profile
    {
        private $multiProfileErrorMessage;
        public function __construct()
        {
            /**
             * Social Mulit Profile ajax 
             * @since 2.5.0
             */
            add_action('wp_ajax_wpscp_multi_social_remove_profile', array($this, 'multi_social_remove_profile'));
            // social profile oauth url generator
            add_action('wp_ajax_wpscp_social_add_profile', array($this, 'add_social_profile'));
            add_action('wp_ajax_wpscp_social_profile_fetch_user_info_and_token', array($this, 'social_profile_fetch_user_info_and_token'));

            // pinterest
            add_action('wp_ajax_wpscp_social_profile_pinterest_data_save', array($this, 'social_profile_pinterest_data_save'));
            // facebook
            add_action('wp_ajax_wpscp_social_profile_facebook_data_save', array($this, 'social_profile_facebook_data_save'));
            // profile on/off toggle
            add_action('wp_ajax_wpscp_social_group_profile_switch_toggle', array($this, 'social_group_profile_switch_toggle'));
            add_action('wp_ajax_wpscp_social_single_profile_switch_toggle', array($this, 'social_single_profile_switch_toggle'));
            // token refresh
            add_action('wp_ajax_wpscp_social_profile_access_token_refresh', array($this, 'social_profile_access_token_refresh'));
            // temp account add ajax, it will be delete after app approve
            add_action('wp_ajax_wpscp_social_temp_add_profile', array($this, 'temp_add_profile'));

            $this->multiProfileErrorMessage = esc_html__('Multi profile is pro feature, please upgrade to pro.', 'wp-scheduled-posts');
        }





        /**
         * Ajax Social multi profile item remove
         * @since 2.5.0
         * @return bool
         */
        public function multi_social_remove_profile()
        {
            $ID = (($_POST['ID'] != "") ? $_POST['ID'] : null);
            $option_name = (isset($_POST['option_name']) ? $_POST['option_name'] : '');
            $existingData = get_option($option_name);
            if ($existingData != false) {
                if (array_key_exists($ID, $existingData)) {
                    unset($existingData[$ID]); // remove item
                    $updateData = update_option($option_name, $existingData);
                    wp_send_json_success($updateData);
                    wp_die();
                }
            } else {
                wp_send_json_error("Setting Api Option name or remove id not found. please try again");
                wp_die();
            }
        }

        public function social_single_profile_checkpoint($platform)
        {
            if ($platform == 'pinterest') {
                $social_profile = get_option(WPSCP_PINTEREST_OPTION_NAME);
                if ($social_profile !== false && count($social_profile) >= 1 && !class_exists('WpScp_Pro')) {
                    return false;
                }
            } else if ($platform == 'facebook') {
                $social_profile = get_option(WPSCP_FACEBOOK_OPTION_NAME);
                if ($social_profile !== false && count($social_profile) >= 1 && !class_exists('WpScp_Pro')) {
                    return false;
                }
            } else if ($platform == 'twitter') {
                $social_profile = get_option(WPSCP_TWITTER_OPTION_NAME);
                if ($social_profile !== false && count($social_profile) >= 1 && !class_exists('WpScp_Pro')) {
                    return false;
                }
            } else if ($platform == 'linkedin') {
                $social_profile = get_option(WPSCP_LINKEDIN_OPTION_NAME);
                if ($social_profile !== false && count($social_profile) >= 1 && !class_exists('WpScp_Pro')) {
                    return false;
                }
            }
            return true;
        }

        /**
         * ajax social multi profile oauth token url generator
         * @since 2.5.0
         * @return json
         */
        public function add_social_profile()
        {
            $request = $_POST;
            $type = (isset($_POST['type']) ? $_POST['type'] : '');
            if ($type == 'pinterest') {
                if (!$this->social_single_profile_checkpoint($type)) {
                    wp_send_json_error($this->multiProfileErrorMessage);
                    wp_die();
                }
                try {
                    $request['redirect_URI'] = esc_url(admin_url('/admin.php?page=wp-scheduled-posts'));
                    $pinterest = new Pinterest(
                        WPSCP_PINTEREST_APP_ID,
                        WPSCP_PINTEREST_APP_SECRET
                    );
                    // state
                    if (is_array($request)) {
                        $pinterest->auth->setState(json_encode($request));
                    }
                    $loginurl = $pinterest->auth->getLoginUrl(WPSCP_SOCIAL_OAUTH2_TOKEN_MIDDLEWARE, array('read_public', 'write_public', 'read_relationships', 'write_relationships'));
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
                    $request['redirect_URI'] = esc_url(admin_url('/admin.php?page=wp-scheduled-posts'));
                    $state = base64_encode(json_encode($request));
                    $linkedin = new LinkedIn(
                        WPSCP_LINKEDIN_CLIENT_ID,
                        WPSCP_LINKEDIN_CLIENT_SECRET,
                        WPSCP_SOCIAL_OAUTH2_TOKEN_MIDDLEWARE,
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
            } else if ($type == 'facebook') {
                if (!$this->social_single_profile_checkpoint($type)) {
                    wp_send_json_error($this->multiProfileErrorMessage);
                    wp_die();
                }
                $request['redirect_URI'] = esc_url(admin_url('/admin.php?page=wp-scheduled-posts'));
                $state = base64_encode(json_encode($request));
                $url = "https://www.facebook.com/dialog/oauth?client_id="
                    . WPSCP_FACEBOOK_APP_ID . "&redirect_uri=" . urlencode(WPSCP_SOCIAL_OAUTH2_TOKEN_MIDDLEWARE) . "&state="
                    . $state . "&scope=" . WPSCP_FACEBOOK_SCOPE;
                wp_send_json_success($url);
                wp_die();
            } else if ($type == 'twitter') {
                if (!$this->social_single_profile_checkpoint($type)) {
                    wp_send_json_error($this->multiProfileErrorMessage);
                    wp_die();
                }
                try {
                    $request['redirect_URI'] = esc_url(admin_url('/admin.php?page=wp-scheduled-posts'));
                    $connection = new TwitterOAuth(WPSCP_TWITTER_API_KEY, WPSCP_TWITTER_API_SECRET_KEY);
                    $oauth_callback = WPSCP_SOCIAL_OAUTH2_TOKEN_MIDDLEWARE . '?' . http_build_query($request);
                    $request_token = $connection->oauth('oauth/request_token', array('oauth_callback' => $oauth_callback));
                    $url = $connection->url('oauth/authorize', array('oauth_token' => $request_token['oauth_token']));
                    wp_send_json_success($url);
                    wp_die();
                } catch (\Exception $error) {
                    wp_send_json_error($error->getMessage());
                    wp_die();
                }
            } else {
                wp_send_json_error(esc_html__('Failed, Your app id, app secret and redirect url is not set', 'wp-scheduled-posts-pro'));
                wp_die();
            }
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
            $option_name = (isset($_POST['option_name']) ? $_POST['option_name'] : '');
            $code = (isset($_POST['code']) ? $_POST['code'] : '');
            $app_id = (isset($_POST['appId']) ? $_POST['appId'] : '');
            $app_secret = (isset($_POST['appSecret']) ? $_POST['appSecret'] : '');
            // user
            $current_user = wp_get_current_user();


            if ($type == 'pinterest') {
                try {
                    // in this block, we just sending user info and token, not saving in db
                    $pinterest = new Pinterest(
                        (!empty($app_id) ? $app_id : WPSCP_PINTEREST_APP_ID),
                        (!empty($app_secret) ? $app_secret : WPSCP_PINTEREST_APP_SECRET)
                    );
                    $token = $pinterest->auth->getOAuthToken($code);
                    $pinterest->auth->setOAuthToken($token->access_token);
                    $userinfo = $pinterest->users->me(array(
                        'fields' => 'username,first_name,last_name,image[large]'
                    ));

                    $info = array(
                        'id'            => $userinfo->id,
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
                        'info'      => $info
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
                        (!empty($app_id) ? $app_id : WPSCP_LINKEDIN_CLIENT_ID),
                        (!empty($app_secret) ? $app_secret : WPSCP_LINKEDIN_CLIENT_SECRET),
                        WPSCP_SOCIAL_OAUTH2_TOKEN_MIDDLEWARE,
                        WPSCP_LINKEDIN_SCOPE,
                        true,
                        null
                    );
                    $acessToken = $linkedin->getAccessToken($code);
                    $getPerson = $linkedin->getPerson($acessToken);

                    $oldData  = (empty(get_option(WPSCP_LINKEDIN_OPTION_NAME)) ? array() : get_option(WPSCP_LINKEDIN_OPTION_NAME));
                    $image = $getPerson->profilePicture->{'displayImage~'}->elements[0]->identifiers[0]->identifier;
                    $info = array(
                        'id' => $getPerson->id,
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
                    array_push($oldData, $info);

                    $updatedData = $oldData;
                    $updatedData = update_option(WPSCP_LINKEDIN_OPTION_NAME, $updatedData);
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
                        WPSCP_TWITTER_API_KEY,
                        WPSCP_TWITTER_API_SECRET_KEY,
                        $oauthToken,
                        $oauthVerifier
                    );
                    $access_token = $connection->oauth(
                        "oauth/access_token",
                        ["oauth_verifier" => $oauthVerifier]
                    );


                    $oldData = (empty(get_option(WPSCP_TWITTER_OPTION_NAME)) ? array() : get_option(WPSCP_TWITTER_OPTION_NAME));
                    // get user data
                    $connection = new TwitterOAuth(WPSCP_TWITTER_API_KEY, WPSCP_TWITTER_API_SECRET_KEY, $access_token['oauth_token'], $access_token['oauth_token_secret']);
                    $content = $connection->get("account/verify_credentials");

                    if (is_array($access_token) && count($access_token) > 0) {
                        $info = array(
                            'id' => $content->id,
                            'name' => $content->name,
                            'thumbnail_url' => $content->profile_image_url,
                            'status' => true,
                            'oauth_token' => $access_token['oauth_token'],
                            'oauth_token_secret' => $access_token['oauth_token_secret'],
                            'added_by' => $current_user->user_login,
                            'added_date'    => current_time('mysql')
                        );
                        array_push($oldData, $info);
                    }
                    $updatedData = $oldData;
                    $updatedData = update_option(WPSCP_TWITTER_OPTION_NAME, $updatedData);
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
                        WPSCP_FACEBOOK_APP_ID,
                        WPSCP_FACEBOOK_APP_SECRET,
                        WPSCP_SOCIAL_OAUTH2_TOKEN_MIDDLEWARE,
                        $code
                    );

                    if ($tempAccessToken != "") {
                        $response = wp_remote_get('https://graph.facebook.com/v6.0/oauth/access_token?grant_type=fb_exchange_token&client_id=' . WPSCP_FACEBOOK_APP_ID . '&client_secret=' . WPSCP_FACEBOOK_APP_SECRET . '&fb_exchange_token=' . $tempAccessToken . '');
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
         * ajax social profile pinterest data save
         */
        public function social_profile_pinterest_data_save()
        {
            $info = (isset($_POST['info']) ? $_POST['info'] : '');
            $boardurl = (isset($_POST['boardurl']) ? $_POST['boardurl'] : '');
            if ($boardurl != "") {
                $info['default_board_name'] = substr($boardurl, 26, -1);
            }
            $oldData  = (empty(get_option(WPSCP_PINTEREST_OPTION_NAME)) ? array() : get_option(WPSCP_PINTEREST_OPTION_NAME));
            array_push($oldData, $info);

            $updatedData = $oldData;
            update_option(WPSCP_PINTEREST_OPTION_NAME, $updatedData);

            wp_send_json_success($info);
            wp_die();
        }
        /**
         * ajax social profile facebook data save
         */
        public function social_profile_facebook_data_save()
        {
            $page = (isset($_POST['page']) ? $_POST['page'] : '');
            $group = (isset($_POST['group']) ? $_POST['group'] : '');
            $new_added_profile = array();
            $oldData = (empty(get_option(WPSCP_FACEBOOK_OPTION_NAME)) ? array() : get_option(WPSCP_FACEBOOK_OPTION_NAME));
            if (is_array($page) && count($page) > 0 && is_array($group) && count($group) > 0) {
                $pageAndGroup = array_merge($page, $group);
                $new_added_profile = $pageAndGroup;
                $updatedData = array_merge($oldData, $pageAndGroup);
            } else if (is_array($page) && count($page) > 0 && !is_array($group)) {
                $updatedData = array_merge($oldData, $page);
                $new_added_profile = $page;
            } else if (is_array($group) && count($group) > 0 && !is_array($page)) {
                $updatedData = array_merge($oldData, $group);
                $new_added_profile = $group;
            }
            update_option(WPSCP_FACEBOOK_OPTION_NAME, $updatedData);
            wp_send_json_success($new_added_profile);
            wp_die();
        }

        /**
         * ajax requrest for facebook token refresh
         */
        public function social_profile_access_token_refresh()
        {
            if (wp_verify_nonce($_POST['_wpscpnonce'], 'wpscp-pro-social-profile')) {
                $type = (isset($_POST['type']) ? $_POST['type'] : '');
                $key = (isset($_POST['item']) ? $_POST['item'] : '');
                $option_name = (isset($_POST['option_name']) ? $_POST['option_name'] : '');
                // facebook
                if ($type == 'facebook') {
                    $oldData = get_option($option_name);
                    $existingData = $oldData[$key];
                    // user toke for group
                    if ($existingData['type'] == 'group') {
                        try {
                            $code_response = wp_remote_get("https://graph.facebook.com/v6.0/oauth/client_code?client_id=" . WPSCP_FACEBOOK_APP_ID . "&client_secret=" . WPSCP_FACEBOOK_APP_SECRET . "&redirect_uri=" . WPSCP_SOCIAL_OAUTH2_TOKEN_MIDDLEWARE . "&access_token=" . $existingData['access_token']);
                            $code = wp_remote_retrieve_body($code_response);
                            $code = json_decode($code);
                            $code = $code->{'code'};
                            $token_response = wp_remote_get('https://graph.facebook.com/v6.0/oauth/access_token?code=' . $code . '&client_id=' . WPSCP_FACEBOOK_APP_ID . '&redirect_uri=' . WPSCP_SOCIAL_OAUTH2_TOKEN_MIDDLEWARE);
                            $token = wp_remote_retrieve_body($token_response);
                            $token = json_decode($token);
                            $access_token = $token->{'access_token'};
                            // update old data and insert into db
                            $oldData[$key]['access_token'] = $access_token;
                            $updatedData = $oldData;
                            $is_data_update = update_option($option_name, $updatedData);
                            if ($is_data_update) {
                                wp_send_json_success($is_data_update);
                                wp_die();
                            } else {
                                wp_send_json_error($is_data_update);
                                wp_die();
                            }
                        } catch (\Exception $error) {
                            wp_send_json_error($error->getMessage());
                            wp_die();
                        }
                    } else if ($existingData['type'] == 'page') {
                        $code_response = wp_remote_get("https://graph.facebook.com/v6.0/oauth/client_code?client_id=" . WPSCP_FACEBOOK_APP_ID . "&client_secret=" . WPSCP_FACEBOOK_APP_SECRET . "&redirect_uri=" . WPSCP_SOCIAL_OAUTH2_TOKEN_MIDDLEWARE . "&access_token=" . $existingData['access_token']);
                        $code = wp_remote_retrieve_body($code_response);
                        $code = json_decode($code);
                        $code = $code->{'code'};
                        $token_response = wp_remote_get('https://graph.facebook.com/v6.0/oauth/access_token?code=' . $code . '&client_id=' . WPSCP_FACEBOOK_APP_ID . '&redirect_uri=' . WPSCP_SOCIAL_OAUTH2_TOKEN_MIDDLEWARE);
                        $token = wp_remote_retrieve_body($token_response);
                        $token = json_decode($token);
                        $access_token = $token->{'access_token'};
                        $userInfo = $this->facebookGetUserDetails($access_token);
                        $tokenList = wp_list_pluck($userInfo->accounts->data, 'access_token', 'id');
                        $current_page_refresh_token = $tokenList[$existingData['id']];
                        // update old data and insert into db
                        $oldData[$key]['access_token'] = $current_page_refresh_token;
                        $updatedData = $oldData;
                        $is_data_update = update_option($option_name, $updatedData);
                        if ($is_data_update) {
                            wp_send_json_success($is_data_update);
                            wp_die();
                        } else {
                            wp_send_json_error($is_data_update);
                            wp_die();
                        }
                    }
                }
            } else {
                wp_send_json_error(__('Invailed Nonce', 'wp-scheduled-posts-pro'));
                wp_die();
            }
        }

        /**
         * Ajax Social Multi Profile on/off
         * @since 2.5.0
         * @return bool
         */
        public function social_group_profile_switch_toggle()
        {
            $option_name = (isset($_POST['option_name']) ? $_POST['option_name'] : '');
            $status = (isset($_POST['status']) ? $_POST['status'] : "");
            $message = '';

            if ($option_name != "") {
                update_option($option_name, $status);
            }

            if ($option_name == 'wpsp_facebook_integration_status') {
                if ($status == 'on') {
                    $message = __('Facebook Integration Activated', 'wp-scheduled-posts-pro');
                } else {
                    $message = __('Facebook Integration Deactivated', 'wp-scheduled-posts-pro');
                }
            } else if ($option_name == 'wpsp_twitter_integration_status') {
                if ($status == 'on') {
                    $message = __('Twitter Integration Activated', 'wp-scheduled-posts-pro');
                } else {
                    $message = __('Twitter Integration Deactivated', 'wp-scheduled-posts-pro');
                }
            } else if ($option_name == 'wpsp_linkedin_integration_status') {
                if ($status == 'on') {
                    $message = __('Linkedin Integration Activated', 'wp-scheduled-posts-pro');
                } else {
                    $message = __('Linkedin Integration Deactivated', 'wp-scheduled-posts-pro');
                }
            } else if ($option_name == 'wpsp_pinterest_integration_status') {
                if ($status == 'on') {
                    $message = __('Pinterest Integration Activated', 'wp-scheduled-posts-pro');
                } else {
                    $message = __('Pinterest Integration Deactivated', 'wp-scheduled-posts-pro');
                }
            }
            wp_send_json(array(
                'status' => $status,
                'data'  =>  $message
            ));
            wp_die();
        }
        /**
         * Ajax Social Single Profile on/off
         * @since 2.5.0
         * @return bool
         */
        public function social_single_profile_switch_toggle()
        {

            $option_name = (isset($_POST['option_name']) ? $_POST['option_name'] : '');
            $status = (isset($_POST['status']) ? $_POST['status'] : 0);
            $ID = (isset($_POST['ID']) ? $_POST['ID'] : "");
            $message = '';
            if (!empty($option_name) && $ID != "") {
                $oldData = get_option($option_name);
                $oldData[intval($ID)]['status'] = boolval($status);
                $updatedData = $oldData;
                update_option($option_name, $updatedData);
                // message
                if ($option_name == WPSCP_FACEBOOK_OPTION_NAME) {
                    if ($status == true) {
                        $message = __('Facebook Social Share is Activated', 'wp-scheduled-posts-pro');
                    } else {
                        $message = __('Facebook Social Share is Deactivated', 'wp-scheduled-posts-pro');
                    }
                } else if ($option_name == WPSCP_TWITTER_OPTION_NAME) {
                    if ($status == true) {
                        $message = __('Twitter Social Share is Activated', 'wp-scheduled-posts-pro');
                    } else {
                        $message = __('Twitter Social Share is Deactivated', 'wp-scheduled-posts-pro');
                    }
                } else if ($option_name == WPSCP_LINKEDIN_OPTION_NAME) {
                    if ($status == true) {
                        $message = __('Linkedin Social Share is Activated', 'wp-scheduled-posts-pro');
                    } else {
                        $message = __('Linkedin Social Share is Deactivated', 'wp-scheduled-posts-pro');
                    }
                } else if ($option_name == WPSCP_PINTEREST_OPTION_NAME) {
                    if ($status == true) {
                        $message = __('Pinterest Social Share is Activated', 'wp-scheduled-posts-pro');
                    } else {
                        $message = __('Pinterest Social Share is Deactivated', 'wp-scheduled-posts-pro');
                    }
                }
                // check token is expired or not
                if ($oldData[intval($ID)]['token_expired']) {
                    wp_send_json(array(
                        'token_expired' => $oldData[intval($ID)]['token_expired'],
                        'status'        => $status,
                        'data' => $message
                    ));
                    wp_die();
                }
                wp_send_json(array(
                    'token_expired' => $oldData[intval($ID)]['token_expired'],
                    'status'        => $status,
                    'data'          => $message
                ));
                wp_die();
            }
            wp_die();
        }

        /**
         * Temp app account, it will delete after app approve
         */
        public function temp_add_profile()
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
                    $request['redirect_URI'] = esc_url(admin_url('/admin.php?page=wp-scheduled-posts'));
                    $pinterest = new Pinterest(
                        $app_id,
                        $app_secret
                    );
                    // state
                    if (is_array($request)) {
                        $pinterest->auth->setState(json_encode($request));
                    }
                    $loginurl = $pinterest->auth->getLoginUrl(WPSCP_SOCIAL_OAUTH2_TOKEN_MIDDLEWARE, array('read_public', 'write_public', 'read_relationships', 'write_relationships'));
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
                    $request['redirect_URI'] = esc_url(admin_url('/admin.php?page=wp-scheduled-posts'));
                    $state = base64_encode(json_encode($request));
                    $linkedin = new LinkedIn(
                        $app_id,
                        $app_secret,
                        WPSCP_SOCIAL_OAUTH2_TOKEN_MIDDLEWARE,
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
            }
        }
    }
    new wpscp_social_multi_profile();
}
