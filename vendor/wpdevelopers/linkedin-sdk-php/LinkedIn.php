<?php

namespace myPHPNotes;


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
    public function userinfo($accessToken) {
        $url = "https://api.linkedin.com/v2/userinfo?oauth2_access_token=" . $accessToken;
        $params = [];
        $response = $this->curl($url, http_build_query($params), "application/x-www-form-urlencoded", false);
        $person = json_decode($response['result']);

        $person = [
            'type'          => 'person',
            'id'            => isset($person->sub) ? $person->sub : null,
            'name'          => isset($person->name) ? $person->name : '',
            'given_name'    => isset($person->given_name) ? $person->given_name : '',
            'family_name'   => isset($person->family_name) ? $person->family_name : '',
            // A member without a photo has no picture field.
            'thumbnail_url' => isset($person->picture) ? $person->picture : '',
            'error'         => $this->errorMessage($person),
        ];
        return $person;
    }
    public function getPerson($accessToken) {
        $url = "https://api.linkedin.com/v2/me?projection=(id,firstName,lastName,profilePicture(displayImage~:playableStreams))&oauth2_access_token=" . $accessToken;
        $params = [];
        $response = $this->curl($url, http_build_query($params), "application/x-www-form-urlencoded", false);
        $person = json_decode($response['result']);
        // A member without a photo has no profilePicture.
        $image = isset($person->profilePicture->{'displayImage~'}->elements[0]->identifiers[0]->identifier) ? $person->profilePicture->{'displayImage~'}->elements[0]->identifiers[0]->identifier : '';
        $person = [
            'type'          => 'person',
            'id'            => isset($person->id) ? $person->id : null,
            'name'          => trim($this->localizedName(isset($person->firstName) ? $person->firstName : null) . ' ' . $this->localizedName(isset($person->lastName) ? $person->lastName : null)),
            'thumbnail_url' => $image,
            'error'         => $this->errorMessage($person),
        ];
        return $person;
    }
    // The name is stored under the member's own locale (de_DE, bn_BD...), not always en_US.
    private function localizedName($field) {
        if (empty($field->localized)) {
            return '';
        }
        $names = (array) $field->localized;
        if (isset($field->preferredLocale->language, $field->preferredLocale->country)) {
            $locale = $field->preferredLocale->language . '_' . $field->preferredLocale->country;
            if (isset($names[$locale])) {
                return $names[$locale];
            }
        }
        return (string) reset($names);
    }
    // LinkedIn's reason when it sent no member (e.g. 403 ACCESS_DENIED), so no blank profile is added.
    private function errorMessage($answer) {
        if (!empty($answer->id) || !empty($answer->sub)) {
            return '';
        }
        return isset($answer->message) && is_string($answer->message) && $answer->message !== '' ? $answer->message : 'LinkedIn did not return your profile. Please try again.';
    }
    public function getPersonID($accessToken) {
        $url = "https://api.linkedin.com/v2/me?oauth2_access_token=" . $accessToken;
        $params = [];
        $response = $this->curl($url, http_build_query($params), "application/x-www-form-urlencoded", false);
        $personID = json_decode($response['result'])->id;
        return $personID;
    }
    public function getCompanyPages($accessToken) {
        $header = [
            "Authorization: Bearer {$accessToken}",
            'X-Restli-Protocol-Version: 2.0.0',
            'LinkedIn-Version: 202607',
        ];

        $company_pages = "https://api.linkedin.com/v2/organizationalEntityAcls?q=roleAssignee&role=ADMINISTRATOR&state=APPROVED&projection=(elements*(organizationalTarget~(id,localizedName,logoV2(original~:playableStreams))))";
        $pages = $this->curl($company_pages, json_encode([]), "application/json", false, $header);
        $pages = json_decode($pages['result'], true);

        $companies = [];
        if(!empty($pages['elements']) && is_array($pages['elements']) && count($pages['elements']) > 0){

            foreach ($pages['elements'] as $company) {
                $logo = !empty($company['organizationalTarget~']['logoV2']) ? $company['organizationalTarget~']['logoV2']['original~']['elements'][0]['identifiers'][0]['identifier'] : '';
                $companies[] = [
                    'type'          => 'organization',
                    'urn'           => $company['organizationalTarget'],
                    'id'            => $company['organizationalTarget~']['id'],
                    'name'          => $company['organizationalTarget~']['localizedName'],
                    'thumbnail_url' => $logo,
                ];
            }
        }

        return $companies;
    }
    public function linkedInTextPost($accessToken, $type, $person_id,  $message, $visibility = "PUBLIC") {
        $post_url = "https://api.linkedin.com/rest/posts";
        $header = [
            "Authorization: Bearer {$accessToken}",
            'X-Restli-Protocol-Version: 2.0.0',
            'LinkedIn-Version: 202607',
        ];
        $request = [
            // "author": "urn:li:organization:5515715",
            "author"         => "urn:li:$type:" . $person_id,
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
    public function linkedInPageTextPost($accessToken, $type, $person_id,  $message, $visibility = "PUBLIC") {
        $post_url = "https://api.linkedin.com/v2/shares?oauth2_access_token=" . $accessToken;
        $request = array(
            'distribution' => array(
                'linkedInDistributionTarget' => new \ArrayObject(),
            ),
            "owner"            => "urn:li:$type:55042594",
            'subject'           => 'linkedin post share testing for schedule posts',
            'text'              => array('text' => 'now testing linkedin posts again')
        );
        $post = $this->curl($post_url, json_encode($request), "application/json", true);
        return $post;
    }


    public function uploadImage($access_token, $type, $person_id, $image_path) {
        $url = "https://api.linkedin.com/rest/images?action=initializeUpload";
        $content_type = "application/json";
        $parameters = json_encode([
            "initializeUploadRequest" => [
                "owner" => "urn:li:$type:" . $person_id,
            ]
        ]);
        $headers = [
            "Authorization: Bearer {$access_token}",
            'LinkedIn-Version: 202607',
            "X-RestLi-Protocol-Version: 2.0.0"
        ];

        $response = $this->curl($url, $parameters, $content_type, true, $headers);
        $result = json_decode($response['result'], true);
        if(isset($result['value']['uploadUrl'])){
            $upload_url = $result['value']['uploadUrl'];

            $url = $upload_url;
            $parameters = file_get_contents($image_path);
            $content_type = "image/jpeg";
            $headers = [
                "Authorization: Bearer {$access_token}",
                'LinkedIn-Version: 202607',
                "X-RestLi-Protocol-Version: 2.0.0",
                "Content-Length: " . strlen($parameters),
            ];
            $upload = $this->curl($url, $parameters, $content_type, true, $headers);
        }

        if(isset($upload['code']) && $upload['code'] !== 201){
            return $upload;
        }
        else{
            return $result;
        }
    }

    public function linkedInLinkPost($access_token, $type, $person_id, $commentary, $source, $thumbnail, $title, $description) {
        $url = "https://api.linkedin.com/rest/posts";
        $data = array(
            "author"       => "urn:li:$type:$person_id",
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
            'LinkedIn-Version: 202607',
            "X-RestLi-Protocol-Version: 2.0.0",
            "Content-Length: " . strlen($parameters),
        ];

        $post = $this->curl($url, $parameters, $content_type, true, $headers);

        if ($post['code'] == 201) {
            return json_encode([
                'id' => rand(),
            ]);
        }
        return $post['result'];
    }
    public function linkedInPhotoPost($accessToken, $type, $person_id, $imageUrn, $title, $commentary) {
        $url = 'https://api.linkedin.com/rest/posts';

        $postData = [
            "author"       => "urn:li:$type:$person_id",
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
            'LinkedIn-Version: 202607',
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
