<?php

namespace myPHPNotes;

use GuzzleHttp\Client;

class LinkedIn {
    protected $app_id;
    protected $app_secret;
    protected $callback;
    protected $csrf;
    protected $scopes;
    protected $ssl;
    public function __construct($app_id, $app_secret, $callback, $scopes, $ssl = true, $state = null) {
        $this->app_id = $app_id;
        $this->app_secret = $app_secret;
        $this->scopes =  $scopes;
        $this->csrf = $state;
        $this->callback = $callback;
        $this->ssl = $ssl;
    }
    public function getAuthUrl() {
        $_SESSION['linkedincsrf']  = $this->csrf;
        return "https://www.linkedin.com/oauth/v2/authorization?response_type=code&client_id=" . $this->app_id . "&redirect_uri=" . $this->callback . "&state=" . $this->csrf . "&scope=" . $this->scopes;
    }
    public function getAccessToken($code) {
        $url = "https://www.linkedin.com/oauth/v2/accessToken";
        $params = [
            'client_id' => $this->app_id,
            'client_secret' => $this->app_secret,
            'redirect_uri' => $this->callback,
            'code' => $code,
            'grant_type' => 'authorization_code',
        ];
        $response = $this->curl($url, http_build_query($params), "application/x-www-form-urlencoded");
        $accessToken = json_decode($response['result']);
        return $accessToken;
    }
    public function getPerson($accessToken) {
        $url = "https://api.linkedin.com/v2/me?projection=(id,firstName,lastName,profilePicture(displayImage~:playableStreams))&oauth2_access_token=" . $accessToken;
        $params = [];
        $response = $this->curl($url, http_build_query($params), "application/x-www-form-urlencoded", false);
        $person = json_decode($response['result']);
        return $person;
    }
    public function getPersonID($accessToken) {
        $url = "https://api.linkedin.com/v2/me?oauth2_access_token=" . $accessToken;
        $params = [];
        $response = $this->curl($url, http_build_query($params), "application/x-www-form-urlencoded", false);
        $personID = json_decode($response['result'])->id;
        return $personID;
    }
    public function getCompanyPages($accessToken) {

        $company_pages = "https://api.linkedin.com/v2/organizations/55042594?format=json&is-company-admin=true&oauth2_access_token=" . trim($accessToken);
        $pages = $this->curl($company_pages, json_encode([]), "application/json", false);
        return json_decode($pages['result']);
    }
    public function linkedInTextPost($accessToken, $person_id,  $message, $visibility = "PUBLIC") {
        $post_url = "https://api.linkedin.com/rest/posts";
        $header = [
            "Authorization: Bearer {$accessToken}",
            'X-Restli-Protocol-Version: 2.0.0',
            'LinkedIn-Version: 202301',
        ];
        $request = [
            // "author": "urn:li:organization:5515715",
            "author"         => "urn:li:person:" . $person_id,
            "commentary"     => html_entity_decode($message),
            "visibility"     => $visibility,
            "lifecycleState" => "PUBLISHED",
            "distribution"   => [
                "feedDistribution"               => "MAIN_FEED",
                "targetEntities"                 => [],
                "thirdPartyDistributionChannels" => [],
            ],
            "isReshareDisabledByAuthor" => false,
        ];
        $post = $this->curl($post_url, json_encode($request), "application/json", true, $header);

        if ($post['code'] === 201) {
            return json_encode([
                'id' => rand(),
            ]);
        }
        return $post['result'];
    }


    // page post
    public function linkedInPageTextPost($accessToken, $person_id,  $message, $visibility = "PUBLIC") {
        $post_url = "https://api.linkedin.com/v2/shares?oauth2_access_token=" . $accessToken;
        $request = array(
            'distribution' => array(
                'linkedInDistributionTarget' => new \ArrayObject(),
            ),
            "owner"            => "urn:li:organization:55042594",
            'subject'           => 'linkedin post share testing for schedule posts',
            'text'              => array('text' => 'now testing linkedin posts again')
        );
        $post = $this->curl($post_url, json_encode($request), "application/json", true);
        return $post;
    }


    public function uploadImage($access_token, $person_id, $image_path) {
        $url = "https://api.linkedin.com/rest/images?action=initializeUpload";
        $content_type = "application/json";
        $parameters = json_encode([
            "initializeUploadRequest" => [
                "owner" => "urn:li:person:" . $person_id,
            ]
        ]);
        $headers = [
            "Authorization: Bearer {$access_token}",
            'LinkedIn-Version: 202301',
            "X-RestLi-Protocol-Version: 2.0.0"
        ];

        $response = $this->curl($url, $parameters, $content_type, true, $headers);
        $result = json_decode($response['result'], true);
        $upload_url = $result['value']['uploadUrl'];

        $url = $upload_url;
        $parameters = file_get_contents($image_path);
        $content_type = "image/jpeg";
        $headers = [
            "Authorization: Bearer {$access_token}",
            'LinkedIn-Version: 202301',
            "X-RestLi-Protocol-Version: 2.0.0",
            "Content-Length: " . strlen($parameters),
        ];
        $upload = $this->curl($url, $parameters, $content_type, true, $headers);

        if($upload['code'] !== 201){
            return $upload;
        }
        else{
            return $result;
        }
    }

    public function linkedInLinkPost($access_token, $person_id, $commentary, $source, $thumbnail, $title, $description) {
        $url = "https://api.linkedin.com/rest/posts";
        $data = array(
            "author"       => "urn:li:person:$person_id",
            "commentary"   => $commentary,
            "visibility"   => "PUBLIC",
            "distribution" => [
                "feedDistribution"               => "MAIN_FEED",
                "targetEntities"                 => [],
                "thirdPartyDistributionChannels" => []
            ],
            "content" => array(
                "article" => array(
                    "source"      => $source,
                    "thumbnail"   => $thumbnail,
                    "title"       => $title,
                    "description" => $description
                )
            ),
            "lifecycleState"            => "PUBLISHED",
            "isReshareDisabledByAuthor" => false
        );
        if(empty($data['content']['article']['thumbnail'])){
            unset($data['content']['article']['thumbnail']);
        }

        $parameters = json_encode($data);
        $content_type = "application/json";
        $headers = [
            "Authorization: Bearer {$access_token}",
            'LinkedIn-Version: 202301',
            "X-RestLi-Protocol-Version: 2.0.0",
            "Content-Length: " . strlen($parameters),
        ];

        $post = $this->curl($url, $parameters, $content_type, true, $headers);

        if ($post['code'] === 201) {
            return json_encode([
                'id' => rand(),
            ]);
        }
        return $post['result'];
    }
    public function linkedInPhotoPost($accessToken, $person_id, $imageUrn, $title, $commentary) {
        $url = 'https://api.linkedin.com/rest/posts';

        $postData = [
            "author"       => "urn:li:person:$person_id",
            "commentary"   => $commentary,
            "visibility"   => "PUBLIC",
            "distribution" => [
                "feedDistribution"               => "MAIN_FEED",
                "targetEntities"                 => [],
                "thirdPartyDistributionChannels" => []
            ],
            "content" => [
                "media" => [
                    "id"      => $imageUrn,
                    "title"   => $title,
                    "altText" => "",
                ]
            ],
            "lifecycleState"            => "PUBLISHED",
            "isReshareDisabledByAuthor" => false
        ];

        $parameters = json_encode($postData);

        $headers = [
            "Authorization: Bearer {$accessToken}",
            'LinkedIn-Version: 202301',
            "X-RestLi-Protocol-Version: 2.0.0",
            "Content-Length: " . strlen($parameters),
        ];

        $result = $this->curl($url, $parameters, 'application/json', true, $headers);

        if ($result['code'] === 201) {
            return json_encode([
                'id' => rand(),
            ]);
        }
        return $result['result'];
    }
    public function curl($url, $parameters, $content_type, $post = true, $headers = []) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $this->ssl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        if ($post) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $parameters);
        }
        curl_setopt($ch, CURLOPT_POST, $post);

        $headers[] = "Content-Type: {$content_type}";
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $result = curl_exec($ch);
        $response_code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);
        return [
            'result' => $result,
            'code'   => $response_code
        ];
    }
}
