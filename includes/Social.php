<?php

namespace WPSP;

use myPHPNotes\LinkedIn;
use DirkGroenen\Pinterest\Pinterest;


class Social
{
    protected $social_profile;
    protected $instantShare;
    public function __construct()
    {
        $this->define_constants();
        $this->load_dependancy();
        $this->load_third_party_integration();
        $this->instant_social_share();
        add_action('publish_future_post', array($this, 'publish_future_post'), 30, 1);

    }

    public function publish_future_post($post_id){
        do_action('wpsp_publish_future_post', $post_id);
    }

    /**
     * Define WC Constants.
     */
    private function define_constants()
    {
        // facebook
        $this->define('WPSCP_FACEBOOK_OPTION_NAME', 'facebook_profile_list');
        $this->define('WPSCP_INSTAGRAM_OPTION_NAME', 'instagram_profile_list');
        $this->define('WPSCP_MEDIUM_OPTION_NAME', 'medium_profile_list');
        $this->define('WPSCP_THREADS_OPTION_NAME', 'threads_profile_list');
        // $this->define('WPSCP_FACEBOOK_SCOPE', 'pages_show_list,publish_to_groups,pages_read_engagement,pages_manage_metadata,pages_read_user_content,pages_manage_posts,pages_manage_engagement');
        $this->define('WPSCP_FACEBOOK_SCOPE', 'pages_show_list,pages_read_engagement,pages_manage_metadata,pages_read_user_content,pages_manage_posts,business_management,');
        $this->define('WPSCP_INSTAGRAM_SCOPE', 'pages_show_list,pages_read_engagement,pages_manage_metadata,pages_read_user_content,pages_manage_posts,business_management,ads_management,instagram_basic,instagram_content_publish,');
        // twitter
        $this->define('WPSCP_TWITTER_OPTION_NAME', 'twitter_profile_list');
        // linkedin
        $this->define('WPSCP_LINKEDIN_SCOPE', 'r_emailaddress r_liteprofile w_member_social');
        $this->define('WPSCP_LINKEDIN_SCOPE_OPENID', 'openid profile email w_member_social');
        $this->define('WPSCP_LINKEDIN_SCOPE_OPENID_PAGE', 'openid profile email w_member_social r_organization_admin w_organization_social rw_organization_admin');
        $this->define('WPSCP_LINKEDIN_BUSINESS_SCOPE', 'r_emailaddress r_liteprofile w_member_social r_organization_admin w_organization_social');
        $this->define('WPSCP_THREADS_SCOPE', 'threads_basic,threads_content_publish');
        $this->define('WPSCP_LINKEDIN_OPTION_NAME', 'linkedin_profile_list');
        // pinterest
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
        $this->socialProfile();
    }
    public function socialProfile() {
        if (!$this->social_profile) {
            $this->social_profile = new Social\SocialProfile();
        }
        return $this->social_profile;
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
        if (Helper::get_settings('instagram_profile_status') == true) {
            $this->instagram();
        }
        if (Helper::get_settings('medium_profile_status') == true) {
            $this->medium();
        }
        if (Helper::get_settings('threads_profile_status') == true) {
            $this->threads();
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

    public function instagram()
    {
        $WpScp_instagram = new Social\Instagram();
        $WpScp_instagram->instance();
    }

    public function medium()
    {
        $WpScp_medium = new Social\Medium();
        $WpScp_medium->instance();
    }

    public function threads()
    {
        $WpScp_medium = new Social\Threads();
        $WpScp_medium->instance();
    }

    /**
     *
     * return \WPSP\Social\InstantShare
     */
    public function instant_social_share()
    {
		if (!$this->instantShare && Helper::is_user_allow()) {
			$this->instantShare = new Social\InstantShare();
		}
		return $this->instantShare;
    }
}
