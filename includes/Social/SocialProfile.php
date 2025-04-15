<?php

namespace WPSP\Social;

use Abraham\TwitterOAuth\TwitterOAuth;
use DirkGroenen\Pinterest\Pinterest;
use DOMDocument;
use myPHPNotes\LinkedIn;
use WPSP\Helper;

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
        add_filter('wpsp_instagram_data', [ $this, 'wpsp_format_instagram_profile_data' ], 10, 2);
        $this->multiProfileErrorMessage = '<p>' . esc_html__('Multi Profile is a Premium Feature. To use this feature, Upgrade to Pro.', 'wp-scheduled-posts') . '</p><a target="_blank" href="https://schedulepress.com/#pricing">Upgrade to Pro</a>';


		$allow_post_types = \WPSP\Helper::get_all_allowed_post_type();
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
        // add profile id to linkedin page
        add_filter('wpsp_filter_linkedin_pages', [ $this, 'filter_linkedin_page_data' ], 10, 2);

        add_action('admin_init', [ $this, 'store_social_code_to_transient' ], -1 );
    }

    public function store_social_code_to_transient()
    {
        $request = $_REQUEST;
        if( !empty( $request['code'] ) ) {
            set_transient('wpsp_social_auth_code', $request['code'], 3600);
        }

    }

    // Format instagram profile data 
    public function wpsp_format_instagram_profile_data( $data, $access_token ) {
        if( empty( $data->accounts->data ) ) {
            return __('Something went wrong.', 'wp-scheduled-posts');
        }
        $connected_instagram_profiles = array_filter($data->accounts->data, function($item) {
            return property_exists($item, 'connected_instagram_account');;
        });
        $instagram_profiles = [];
        foreach ($connected_instagram_profiles as $instagram_profile) {
            $graph_url = "https://graph.facebook.com/". $instagram_profile->connected_instagram_account->id ."?fields=name,profile_picture_url,username&access_token=" . $access_token;
            $response = wp_remote_get($graph_url);
            if (is_wp_error($response)) {
                return false;
            } else {
                $body = wp_remote_retrieve_body($response);
                $data = json_decode($body);
                array_push($instagram_profiles, $data);
            }
        }
        return $instagram_profiles;
    }
    // Function to add LinkedIn profile ID to a page
    public function wpsp_add_linkedin_profile_id_to_page( $page, $profile ) {
        if( !empty( $profile['id'] ) ) {
            $page['profile_id'] = $profile['id'];
        }
        return $page;
    }

    // Function to filter LinkedIn page data
    public function filter_linkedin_page_data( $pages, $profile ) {
        return array_map(function($page) use ($profile) {
            return $this->wpsp_add_linkedin_profile_id_to_page($page, $profile);
        }, $pages);
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
     * Facebook access token
     */
    public function instagramGetAccessTokenDetails($app_id, $app_secret, $redirect_url, $code)
    {
        // The API endpoint
        $token_url = "https://api.instagram.com/oauth/access_token";

        // Data to send in the POST request
        $post_data = [
            'client_id'     => $app_id,
            'client_secret' => $app_secret,
            'grant_type'    => 'authorization_code',
            'redirect_uri'  => $redirect_url,
            'code'          => $code,
        ];

        // Make the POST request
        $response = wp_remote_post($token_url, [
            'body' => $post_data,
        ]);

        // Check for errors
        if (is_wp_error($response)) {
            return false;
        } else {
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body);

            // Check if access token exists in response
            if (isset($data->access_token)) {
                return $data;
            }
        }
        return false;
    }


    /**
     * Facebook access token
     */
    public function threadsGetAccessTokenDetails($app_id, $app_secret, $redirect_url, $code)
    {
        $token_url = "https://graph.threads.net/oauth/access_token?"
            . "client_id=" . $app_id . "&redirect_uri=" . urlencode($redirect_url)
            . "&client_secret=" . $app_secret . "&code=" . $code . "&grant_type=authorization_code";

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
     * Get long-lived access token from Threads API.
     *
     * @param string $app_secret The Threads app secret.
     * @param string $short_lived_access_token The short-lived access token.
     * @return array|false Returns an array containing the 'long_lived_access_token' and 'expires_in' or false on failure.
     */
    public function threadsGetLongLivedAccessToken($app_secret, $short_lived_access_token)
    {
        // Prepare the URL for exchanging short-lived token for long-lived token
        $long_token_url = "https://graph.threads.net/access_token"
            . "?grant_type=th_exchange_token"
            . "&client_secret=" . $app_secret
            . "&access_token=" . $short_lived_access_token;

        // Make the request using WordPress HTTP API
        $response = wp_remote_get($long_token_url);

        // Check if request failed
        if (is_wp_error($response)) {
            return false;
        }

        // Retrieve the body of the response
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body);

        // Ensure the long-lived access token is in the response
        if (!isset($data->access_token)) {
            return false;
        }

        // Return the long-lived token and expiry details
        return [
            'long_lived_access_token' => $data->access_token,
            'expires_in'              => $data->expires_in
        ];
    }


    public function getInstagramProfile($access_token) {
        // Define the Instagram Graph API URL for fetching profile details
        $graph_url = add_query_arg([
            'fields' => 'user_id,username,profile_picture_url,name,account_type',
            'access_token' => $access_token,
        ], 'https://graph.instagram.com/v21.0/me');
    
        // Send the GET request using wp_remote_get
        $response = wp_remote_get($graph_url);
    
        // Check for errors in the response
        if (is_wp_error($response)) {
            return false; // Return false if there's an error
        }
    
        // Retrieve and decode the body of the response
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body);
        
        // Apply WordPress filter for further processing, if needed
        // $data = apply_filters('wpsp_instagram_data', $data, $access_token);
    
        return [$data]; // Return the decoded data
    }
    

    // public function getInstagramProfile( $access_token ) {
    //     $graph_url = "https://graph.facebook.com/me?fields=accounts{connected_instagram_account,name,access_token,picture}&access_token=" . $access_token;

    //     $response = wp_remote_get($graph_url);
    //     if (is_wp_error($response)) {
    //         return false;
    //     } else {
    //         $body = wp_remote_retrieve_body($response);
    //         $data = json_decode($body);
    //         $data = apply_filters('wpsp_instagram_data', $data, $access_token);
    //         return $data;
    //     }
    //     return null;
    // }
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
     * Threads User Details
     */
    public function threadsGetUserDetails($access_token)
    {
        $graph_url = "https://graph.threads.net/v1.0/me?fields=id,username,name,threads_profile_picture_url,threads_biography&access_token=" . $access_token;

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
        try {
            if( wp_doing_ajax() ) {
                $params = $_POST;
                // Verify nonce
                $nonce = sanitize_text_field($_POST['_wpnonce']);
                if (!wp_verify_nonce($nonce, 'wp_rest')) {
                    wp_send_json_error(['message' => __('Invalid nonce.', 'wp-scheduled-posts')], 401);
                    die();
                }
                if( !Helper::is_user_allow() ) {
                    wp_send_json_error( [ 'message' => __('You are unauthorized to access social profiles.', 'wp-scheduled-posts') ], 401 );
                    wp_die();
                }
           }
    
            $defaultBoard = (isset($params['defaultBoard']) ? $params['defaultBoard'] : '');
            $profile = (isset($params['profile']) ? $params['profile'] : '');
            if(!is_array($profile)){
                $pinterest = \WPSP\Helper::get_social_profile(WPSCP_PINTEREST_OPTION_NAME);
                if( isset( $pinterest[(int) $profile] ) ) {
                    $profile = (array) $pinterest[(int) $profile];
                }else{
                    return;
                }
            }
           
            $pinterest = new \DirkGroenen\Pinterest\Pinterest($profile['app_id'], $profile['app_secret']);
            $pinterest->auth->setOAuthToken($profile['access_token']);
            
            $sections = $pinterest->sections->get($defaultBoard, [
                'page_size' => 100,
            ]);
            $sections = $sections->toArray();
            if( !empty( $params['method_called'] ) ) {
                return $sections['data'];
                wp_die();
            }
            wp_send_json_success($sections['data']);
            wp_die();
        } catch (\Throwable $th) {
            return [];
            wp_die();
        }
      
    }


    public function social_fetch_pinterest_section_array( $board_id, $section_id ) {
        $pinterest = \WPSP\Helper::get_social_profile(WPSCP_PINTEREST_OPTION_NAME);
        // Use array_filter to find the object based on default_board_name->value
        $filteredData = array_filter($pinterest, function ($item) use ($board_id) {
            return isset($item->default_board_name->value) && $item->default_board_name->value == $board_id;
        });
        $profile = reset($filteredData);
        $pinterest_profile = new \DirkGroenen\Pinterest\Pinterest($profile->app_id, $profile->app_secret);
        $pinterest_profile->auth->setOAuthToken($profile->access_token);
        $sections = $pinterest_profile->sections->get( intval($board_id), [
            'page_size' => 100,
        ]);
        $sections = $sections->toArray();
        if( !empty( $sections['data'] ) ) {
            $filteredSection = array_filter($sections['data'], function ($item) use ($section_id) {
                return isset($item['id']) && $item['id'] == $section_id;
            });
            $filteredSection = reset($filteredSection);
            return [
                'label' => $filteredSection['name'],
                'value' => $filteredSection['id'],
                'board' => $board_id,
            ];
        }
    }

    /**
     * ajax social multi profile fetch user info and generate token from oauth code
     * @since 2.5.0
     * @return json
     */
    public function social_profile_fetch_user_info_and_token()
    {
        // Verify nonce
        $nonce = sanitize_text_field($_POST['nonce']);
        if (!wp_verify_nonce($nonce, 'wp_rest')) {
            wp_send_json_error(['message' => __('Invalid nonce.', 'wp-scheduled-posts')], 401);
            die();
        }

        // Check user capability
        if( !Helper::is_user_allow() ) {
            wp_send_json_error( [ 'message' => __('You are unauthorized to access social profiles.', 'wp-scheduled-posts') ], 401 );
            wp_die();
        }

        $type          = (isset($_POST['type']) ? $_POST['type'] : '');
        $code          = (isset($_POST['code']) ? $_POST['code'] : '');
        $app_id        = (isset($_POST['appId']) ? $_POST['appId'] : '');
        $app_secret    = (isset($_POST['appSecret']) ? $_POST['appSecret'] : '');
        $redirectURI   = (isset($_POST['redirectURI']) ? $_POST['redirectURI'] : '');
        $access_token  = (isset($_POST['access_token']) ? $_POST['access_token'] : '');
        $refresh_token = (isset($_POST['refresh_token']) ? $_POST['refresh_token'] : '');
        $expires_in    = (isset($_POST['expires_in']) ? $_POST['expires_in'] : '');
        $rt_expires_in = (isset($_POST['rt_expires_in']) ? $_POST['rt_expires_in'] : '');
        $openIDConnect = (isset($_POST['openIDConnect']) ? $_POST['openIDConnect'] : '');
        // user
        $current_user = wp_get_current_user();
        
        // get code from request if params code is empty
        if( empty( $code ) ) {
            $code = get_transient('wpsp_social_auth_code');
            delete_transient('wpsp_social_auth_code');
        }

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
                    if( !empty( $accessToken->refresh_token ) ) {
                        $refresh_token = $accessToken->refresh_token;
                        $expires_in    = time() + $accessToken->expires_in;
                        $rt_expires_in = time() + $accessToken->refresh_token_expires_in;
                    }
                }

                $pages    = $linkedin->getCompanyPages($access_token);
                if( $openIDConnect && $openIDConnect !== 'false' && $openIDConnect !== 'undefined' ) {
                    $profiles = $linkedin->userinfo($access_token);
                }else{
                    $profiles = $linkedin->getPerson($access_token);
                }
                $pages = apply_filters('wpsp_filter_linkedin_pages', $pages, $profiles);
                $info = array(
                    '__id'          => time(),
                    'app_id'        => $app_id,
                    'app_secret'    => $app_secret,
                    'status'        => true,
                    'redirectURI'   => $redirectURI,
                    'access_token'  => $access_token,
                    'refresh_token' => $refresh_token,
                    'rt_expires_in' => $rt_expires_in,
                    'expires_in'    => $expires_in,
                    'profiles'      => [$profiles],
                    'pages'         => $pages,
                    'added_by'      => $current_user->user_login,
                    'added_date'    => current_time('mysql'),
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
                        'id'                 => $content->id,
                        'app_id'             => $app_id,
                        'app_secret'         => $app_secret,
                        'name'               => $content->name,
                        'thumbnail_url'      => $content->profile_image_url,
                        'status'             => true,
                        'oauth_token'        => $access_token['oauth_token'],
                        'oauth_token_secret' => $access_token['oauth_token_secret'],
                        'added_by'           => $current_user->user_login,
                        'added_date'         => current_time('mysql')
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
                $userAcessToken = '';
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
                        $uploaded_image_url = $this->handle_thumbnail_upload($page_item->picture->data->url, $page_item->name);
                        array_push($page_array, array(
                            'id'                      => $page_item->id,
                            'app_id'                  => $app_id,
                            'app_secret'              => $app_secret,
                            'name'                    => $page_item->name,
                            'thumbnail_url'           => !empty( $uploaded_image_url ) ? $uploaded_image_url : $page_item->picture->data->url,
                            'type'                    => 'page',
                            'status'                  => true,
                            'access_token'            => $page_item->access_token,
                            'long_lived_access_token' => $userAcessToken,
                            'added_by'                => $current_user->user_login,
                            'added_date'              => current_time('mysql')
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
                                'access_token' => !empty( $access_token ) ? $access_token : $userAcessToken ,
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
        } else if ($type == 'instagram' && $code != "") {
            try {
                $tempAccessTokenDetails = $this->instagramGetAccessTokenDetails(
                    $app_id,
                    $app_secret,
                    $redirectURI,
                    $code
                );
                $userAcessToken = '';
                $tempAccessToken = '';
                if ( !empty( $tempAccessTokenDetails->access_token ) ) {
                    $tempAccessToken = $tempAccessTokenDetails->access_token;
                    $expires_in      = $tempAccessTokenDetails->expires_in;
                    $response = wp_remote_get('https://graph.instagram.com/access_token?grant_type=ig_exchange_token&client_secret=' . $app_secret . '&access_token=' . $tempAccessToken . '');
                    if (is_array($response)) {
                        $body = $response['body']; // use the content
                        $longAcessTokenBody = json_decode($body);
                        $userAcessToken = $longAcessTokenBody->{'access_token'};
                        $expires_in = $longAcessTokenBody->{'expires_in'};
                    }
                    
                }

                $userInfo = $this->getInstagramProfile($tempAccessToken);
                $profile_array = array();
                if (is_array($userInfo) && count($userInfo) > 0) {
                    foreach ($userInfo as $profile) {
                        $uploaded_image_url = $this->handle_thumbnail_upload($profile->profile_picture_url, $profile->name);
                        array_push($profile_array, array(
                            'id'                      => $profile->id,
                            'app_id'                  => $app_id,
                            'app_secret'              => $app_secret,
                            'name'                    => $profile->name,
                            'thumbnail_url'           => !empty( $uploaded_image_url ) ? $uploaded_image_url : $profile->profile_picture_url,
                            'type'                    => 'profile',
                            'status'                  => true,
                            'access_token'            => $tempAccessToken,
                            'long_lived_access_token' => $userAcessToken,
                            'expires_at'              => Helper::getDateFromTimezone($expires_in),
                            'added_by'                => $current_user->user_login,
                            'instagram_app'           => true,
                            'added_date'              => current_time('mysql')
                        ));
                    }
                }
                // response
                $response = array(
                    'success'  => true,
                    'profiles' => $profile_array,
                    'type'     => 'instagram',
                );
                wp_send_json($response);
                wp_die();
            } catch (\Exception $error) {
                wp_send_json_error($error->getMessage());
                wp_die();
            }
        } else if ($type == 'threads' && $code != "") {
            try {
                $tempAccessToken = $this->threadsGetAccessTokenDetails(
                    $app_id,
                    $app_secret,
                    $redirectURI,
                    $code
                );
                $userInfo = $this->threadsGetUserDetails($tempAccessToken);

                $longTokenDetails     = $this->threadsGetLongLivedAccessToken($app_secret, $tempAccessToken);
                $longLivedAccessToken = !empty( $longTokenDetails['long_lived_access_token'] ) ? $longTokenDetails['long_lived_access_token'] : '';
                $expires_in           = !empty( $longTokenDetails['expires_in'] ) ? $longTokenDetails['expires_in'] : '';

                // page
                $profile_array = array();
                if ( !empty($userInfo) && is_object($userInfo) ) {
                    $uploaded_image_url = $this->handle_thumbnail_upload($userInfo->threads_profile_picture_url, $userInfo->name);
                    array_push($profile_array, array(
                        'id'                      => $userInfo->id,
                        'app_id'                  => $app_id,
                        'app_secret'              => $app_secret,
                        'name'                    => $userInfo->name,
                        'thumbnail_url'           => !empty( $uploaded_image_url ) ? $uploaded_image_url : $userInfo->threads_profile_picture_url,
                        'type'                    => 'profile',
                        'status'                  => true,
                        'access_token'            => $tempAccessToken,
                        'long_lived_access_token' => $longLivedAccessToken,
                        'expires_in'              => $expires_in,
                        'added_by'                => $current_user->user_login,
                        'added_date'              => current_time('mysql')
                    ));
                }

                // response
                $response = array(
                    'success'  => true,
                    'profiles' => $profile_array,
                    'type'     => 'threads',
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

    public function handle_thumbnail_upload($imageUrl, $imageTitle = '')
    {
        // Check if user is logged in
        if (is_user_logged_in()) {
            // Download and attach the image
            $attachmentHtml = media_sideload_image($imageUrl, 0, $imageTitle);
            // Check for successful upload
            if (!is_wp_error($attachmentHtml)) {
                 // Extract URL using regular expression
                if (preg_match('/<img.*?src=["\']([^"\']+)["\'].*?>/', $attachmentHtml, $matches)) {
                    $imageUrl = !empty( $matches[1] ) ? $matches[1] : '';
                    return $imageUrl;
                }
            }
        }
    }

    /**
     * Add Social Profile
     */
    public function add_social_profile()
    {
         // Verify nonce
        $nonce = sanitize_text_field($_POST['nonce']);
        if (!wp_verify_nonce($nonce, 'wp_rest')) {
            wp_send_json_error(['message' => __('Invalid nonce.', 'wp-scheduled-posts')], 401);
            die();
        }

        // Check user capability
        if ( !Helper::is_user_allow() ) {
            wp_send_json_error(['message' => __('You are unauthorized to access social profiles.', 'wp-scheduled-posts')], 401);
            die();
        }

        $request = $_POST;
        $type = (isset($_POST['type']) ? $_POST['type'] : '');
        $app_id = (isset($_POST['appId']) ? $_POST['appId'] : '');
        $app_secret = (isset($_POST['appSecret']) ? $_POST['appSecret'] : '');
        $redirectURI = (isset($_POST['redirectURI']) ? $_POST['redirectURI'] : '');
        $openIDConnect = (isset($_POST['openIDConnect']) ? $_POST['openIDConnect'] : '');
        $accountType = (isset($_POST['accountType']) ? $_POST['accountType'] : '');

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
            $scope = WPSCP_LINKEDIN_SCOPE;
            if($openIDConnect && $openIDConnect !== 'false' && $openIDConnect !== 'undefined' && $accountType === 'profile'){
                $scope = WPSCP_LINKEDIN_SCOPE_OPENID;
            }
            elseif($openIDConnect && $openIDConnect !== 'false' && $openIDConnect !== 'undefined' && $accountType === 'page') {
                $scope = WPSCP_LINKEDIN_SCOPE_OPENID_PAGE;
            }
            elseif($accountType === 'page'){
                $scope = WPSCP_LINKEDIN_BUSINESS_SCOPE;
            }
            elseif($accountType === 'profile'){
                $scope = WPSCP_LINKEDIN_SCOPE;
            }

            try {
                $request['redirect_URI'] = esc_url(admin_url('/admin.php?page=' . WPSP_SETTINGS_SLUG));
                $request['appId'] = $app_id ? $app_id : WPSP_SOCIAL_OAUTH2_LINKEDIN_APP_ID;
                $state = base64_encode(json_encode($request));
                $linkedin = new LinkedIn(
                    $request['appId'],
                    $app_secret,  // unnecessary
                    $redirectURI,
                    urlencode( $scope ),
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
        } else if( $type == 'instagram' ) {
            if (!$this->social_single_profile_checkpoint($type)) {
                wp_send_json_error($this->multiProfileErrorMessage);
                wp_die();
            }
            try {
                $request['redirect_URI'] = esc_url(admin_url('/admin.php?page=' . WPSP_SETTINGS_SLUG));
                $state = base64_encode(json_encode($request));
                $url = "https://www.instagram.com/oauth/authorize?enable_fb_login=0&response_type=code&force_authentication=1&client_id="
                    . $app_id . "&redirect_uri=" . urlencode($redirectURI) . "&state="
                    . $state . "&scope=instagram_business_basic,instagram_business_content_publish";
                wp_send_json_success($url);
                wp_die();
            } catch (\Exception $error) {
                wp_send_json_error($error->getMessage());
                wp_die();
            }
        } else if( $type == 'medium' ) {
            try {
                $headers = [
                    "Authorization: Bearer $app_id",
                ];
                // $response           = Helper::get_medium_data( 'https://api.medium.com/v1/me', $headers );
                $response           = Helper::wpsp_medium_curl('https://api.medium.com/v1/me','', 'application/json', false, $headers);
                $response           = json_decode( $response['result'] );
                $data               = !empty( $response->data ) ? $response->data : [];
                $current_user       = wp_get_current_user();
                $uploaded_image_url = '';
                if( !empty( $data->imageUrl ) && !empty( $data->name ) ) {
                    $uploaded_image_url = $this->handle_thumbnail_upload($data->imageUrl, $data->name );
                }
                $res =  [
                    'id'            => time(),
                    '__id'          => !empty( $data->id ) ? esc_html( $data->id ) : '',
                    'app_id'        => $app_id,
                    'app_secret'    => $app_secret,
                    'name'          => !empty( $data->name ) ? esc_html( $data->name ) : '',
                    'thumbnail_url' => !empty( $uploaded_image_url ) ? $uploaded_image_url : $data->imageUrl,
                    'type'          => 'profile',
                    'status'        => true,
                    'access_token'  => $app_id,
                    'added_by'      => $current_user->user_login,
                    'added_date'    => current_time('mysql')
                ];
                if( !empty( $response->errors ) ) {
                    $res = [
                        'message' => $response->errors[0]->message,
                        'code'    => $response->errors[0]->code,
                    ];
                }
                wp_send_json_success($res);
                wp_die();
            } catch (\Exception $error) {
                wp_send_json_error($error->getMessage());
                wp_die();
            }
        } else if ($type == 'threads') {
            // if (!$this->social_single_profile_checkpoint($type)) {
            //     wp_send_json_error($this->multiProfileErrorMessage);
            //     wp_die();
            // }
            try {
                $request['redirect_URI'] = esc_url(admin_url('/admin.php?page=' . WPSP_SETTINGS_SLUG));
                $state = base64_encode(json_encode($request));
                $url = "https://threads.net/oauth/authorize?client_id="
                    . $app_id . "&redirect_uri=" . urlencode($redirectURI) . "&state="
                    . $state . "&scope=" . WPSCP_THREADS_SCOPE;
                wp_send_json_success($url);
                wp_die();
            } catch (\Exception $error) {
                wp_send_json_error($error->getMessage());
                wp_die();
            }
        }
    }

    public function handle_reconnect()
    {
        
    }

}
