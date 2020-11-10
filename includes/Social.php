<?php

namespace WPSP;

use myPHPNotes\LinkedIn;
use DirkGroenen\Pinterest\Pinterest;


class Social
{
    public function __construct()
    {
        $this->define_constants();
        $this->load_dependancy();
        $this->load_third_party_integration();
        $this->instant_social_share();
    }
    /**
     * Define WC Constants.
     */
    private function define_constants()
    {
        // facebook
        $this->define('WPSCP_FACEBOOK_APP_ID', '2550061111706782');
        $this->define('WPSCP_FACEBOOK_APP_SECRET', '8bfa7101ac90a6cafd37d260a54c195b');
        $this->define('WPSCP_FACEBOOK_OPTION_NAME', 'facebook_profile_list');
        $this->define('WPSCP_FACEBOOK_SCOPE', 'publish_pages,manage_pages,publish_to_groups');
        // twitter
        $this->define('WPSCP_TWITTER_API_KEY', 'mN2t8LSxPAbp989EyeEL8GGdP');
        $this->define('WPSCP_TWITTER_API_SECRET_KEY', 'vbpvkuYbSOkueDLiaho047vMs4TY6V3j6qS1Qfwbi5skooMNp1');
        $this->define('WPSCP_TWITTER_OPTION_NAME', 'twitter_profile_list');
        // linkedin
        $this->define('WPSCP_LINKEDIN_CLIENT_ID', '78iadscla7c407');
        $this->define('WPSCP_LINKEDIN_CLIENT_SECRET', 'fwYuo1rXKQdahIL7');
        $this->define('WPSCP_LINKEDIN_SCOPE', 'r_emailaddress r_liteprofile w_member_social');
        $this->define('WPSCP_LINKEDIN_OPTION_NAME', 'linkedin_profile_list');
        // pinterest
        $this->define('WPSCP_PINTEREST_APP_ID', '5078354275936023710');
        $this->define('WPSCP_PINTEREST_APP_SECRET', '88ced81e088c6c2e4c0ba60701bec5bf892a7f3f22f2a6fa1e1f5ed6c7ed1f93');
        $this->define('WPSCP_PINTEREST_OPTION_NAME', 'pinterest_profile_list');
    }
    /**
     * Define constant if not already set.
     *
     * @param string      $name  Constant name.
     * @param string|bool $value Constant value.
     */
    private function define($name, $value)
    {
        if (!defined($name)) {
            define($name, $value);
        }
    }
    public function load_dependancy()
    {
        new Social\MultiProfile();
    }
    public function load_third_party_integration()
    {

        if (Helper::get_settings('twitter_profile_status') == true) {
            $this->twitter();
        }
        if (Helper::get_settings('facebook_profile_status') == true) {
            $this->facebook();
        }
        if (Helper::get_settings('linkedin_profile_status') == true) {
            $this->linkedin();
        }

        if (Helper::get_settings('pinterest_profile_status') == true) {
            $this->pinterest();
        }
    }



    public function facebook()
    {
        $WpScp_Facebook = new Social\Facebook();
        $WpScp_Facebook->instance();
    }
    public function twitter()
    {
        $wpscptwitter = new Social\Twitter();
        $wpscptwitter->instance();
    }
    public function linkedin()
    {
        $WpScp_linkedin = new Social\Linkedin();
        $WpScp_linkedin->instance();
    }

    public function pinterest()
    {
        $WpScp_pinterest = new Social\Pinterest();
        $WpScp_pinterest->instance();
    }
    public function instant_social_share()
    {
        new Social\InstantShare();
    }
}
