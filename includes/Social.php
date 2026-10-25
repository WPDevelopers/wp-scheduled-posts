<?php

namespace WPSP;

use WPSP\Social\ReconnectHandler;
use WPSP\Social\OAuthPopup;
use myPHPNotes\LinkedIn;
use DirkGroenen\Pinterest\Pinterest;
use WPSP\Social\SocialReconnection;

class Social
{
    /**
     * The WP-Cron events each platform queues from `wpsp_publish_future_post`
     * to do its actual share.
     */
    const SHARE_EVENT_HOOKS = array(
        'WpScp_Facebook_post',
        'wpsp_twitter_post',
        'WpScp_linkedin_post',
        'WpScp_pinterest_post',
        'WpScp_Instagram_post',
        'WpScp_Medium_post',
        'WpScp_Threads_post',
        'wpsp_bluesky_post',
        'wpsp_mastodon_post',
    );

    protected $social_profile;
    protected $instantShare;
    protected $reconnect_handler;

    public function __construct()
    {
        $this->define_constants();
        $this->load_dependancy();
        $this->load_third_party_integration();
        $this->instant_social_share();
        add_action('publish_future_post', array($this, 'publish_future_post'), 30, 1);
        add_action('wpsp_custom_social_template', array($this, 'publish_future_post'), 30, 1);
        // Last, so every platform has already queued its share event.
        add_action('wpsp_publish_future_post', array(__CLASS__, 'run_queued_shares_now'), PHP_INT_MAX, 1);
    }

    /**
     * Run the share events a post has just queued instead of leaving them for
     * the next cron run.
     *
     * A scheduled post is published from inside a WP-Cron run, and WP-Cron only
     * runs the events that were due when the run started. The share events the
     * platforms queue during that run therefore waited for the next one — up
     * to an hour on a site whose server cron fires hourly. No visitor is
     * waiting on a cron or WP-CLI request, so the shares run right here.
     *
     * Each event is unscheduled before it runs, exactly as wp-cron.php does, so
     * a run that dies part-way leaves the platforms it never reached queued for
     * the next run rather than losing them. In a web request the events stay
     * queued, so a page load or an editor's save never waits on social APIs.
     *
     * @param int|\WP_Post|object $post Post ID, or an object with an `ID`.
     */
    public static function run_queued_shares_now($post)
    {
        $post_id = (int) (is_object($post) ? $post->ID : $post);
        if ( ! $post_id ) {
            return;
        }

        /**
         * Whether to share right away rather than on the next cron run.
         *
         * @param bool $run_now True inside a WP-Cron run or WP-CLI.
         * @param int  $post_id
         */
        $run_now = (bool) apply_filters(
            'wpsp_run_queued_shares_now',
            wp_doing_cron() || ( defined('WP_CLI') && WP_CLI ),
            $post_id
        );
        if ( ! $run_now ) {
            return;
        }

        /**
         * Share event hooks to run right away, for platforms added outside
         * this plugin.
         *
         * @param string[] $hooks
         * @param int      $post_id
         */
        $hooks = (array) apply_filters('wpsp_share_event_hooks', self::SHARE_EVENT_HOOKS, $post_id);
        $args  = array($post_id);
        foreach ($hooks as $hook) {
            $timestamp = wp_next_scheduled($hook, $args);
            if ( ! $timestamp || $timestamp > time() ) {
                continue;
            }
            wp_unschedule_event($timestamp, $hook, $args);
            do_action_ref_array($hook, $args);
        }
    }

    public function publish_future_post($post_id){
        $post_id = (int) $post_id;
        if ( ! $post_id ) {
            return;
        }

        // A share is already queued for this post — let that event do the sharing
        // rather than firing a second time here.
        if ( wp_next_scheduled('wpsp_publish_future_post', array($post_id)) || wp_next_scheduled('wpsp_custom_social_template', array($post_id))) {
            return;
        }

        // The post's scheduled social share has already gone out. Reaching the
        // post's own publication time afterwards must not share it again.
        if ( get_post_meta($post_id, \WPSP\API\CustomSocialTemplates::SHARED_MARKER_META, true) ) {
            return;
        }

        if ( ! self::claim_share_dispatch($post_id) ) {
            return;
        }

        do_action('wpsp_publish_future_post', $post_id);
    }

    /**
     * Take a short-lived lock so a single post cannot be fanned out to the social
     * platforms twice in quick succession — duplicate cron delivery, a missed
     * schedule catch-up racing the scheduled share, or two concurrent requests.
     *
     * @param int $post_id
     * @return bool True when the caller owns the dispatch.
     */
    protected static function claim_share_dispatch($post_id)
    {
        $key = 'wpsp_share_dispatch_' . $post_id;
        if ( get_transient($key) ) {
            return false;
        }
        /**
         * How long the same post is blocked from being auto-shared again.
         *
         * @param int $seconds
         * @param int $post_id
         */
        $window = (int) apply_filters('wpsp_social_share_dedup_window', MINUTE_IN_SECONDS, $post_id);
        set_transient($key, time(), max(1, $window));
        return true;
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
        // Only what sharing uses: /me/accounts lists the pages, /{page}/feed posts
        // with the page token, and business_management reaches pages owned
        // through a Business Manager. Apps without access to an unused scope fail
        // the consent screen outright.
        $this->define('WPSCP_FACEBOOK_SCOPE', 'pages_show_list,pages_read_engagement,pages_manage_posts,business_management');
        $this->define('WPSCP_INSTAGRAM_SCOPE', 'instagram_business_basic,instagram_business_content_publish,');
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
        // bluesky (AT Protocol)
        $this->define('WPSCP_BLUESKY_OPTION_NAME', 'bluesky_profile_list');
        $this->define('WPSCP_BLUESKY_PDS', 'https://bsky.social');
        // mastodon (federated — the real instance URL is stored per profile)
        $this->define('WPSCP_MASTODON_OPTION_NAME', 'mastodon_profile_list');
        $this->define('WPSCP_MASTODON_INSTANCE', 'https://mastodon.social');
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

        // Social profile reconnection process handler
        new ReconnectHandler;
        new SocialReconnection();

        // Keep connected profiles alive on their own. One daily event for the
        // whole site, and it only reaches out for profiles actually near expiry,
        // so an ordinary request costs nothing more than the scheduled-check.
        add_action(ReconnectHandler::MAINTENANCE_HOOK, array(ReconnectHandler::class, 'run_maintenance'));
        add_action('wp_loaded', array(ReconnectHandler::class, 'schedule_maintenance'));

        // A reconnect runs the provider's consent screen in a popup, so the
        // callback has to be caught before it turns that popup into a second
        // copy of the settings screen.
        if (is_admin()) {
            OAuthPopup::hooks();
        }
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
        if (Helper::get_settings('bluesky_profile_status') == true) {
            $this->bluesky();
        }
        if (Helper::get_settings('mastodon_profile_status') == true) {
            $this->mastodon();
        }

        /**
         * Built-in platforms are wired up. Anything registered through
         * `wpsp_social_platforms` boots itself; this marks the ordering point.
         *
         * @param \WPSP\Social $social
         */
        do_action('wpsp_social_platforms_loaded', $this);
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

    public function bluesky()
    {
        $WpScp_bluesky = new Social\Bluesky();
        $WpScp_bluesky->instance();
    }

    public function mastodon()
    {
        $WpScp_mastodon = new Social\Mastodon();
        $WpScp_mastodon->instance();
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
