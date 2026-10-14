<?php

namespace WPSP\Social;

/**
 * Registry of social platforms that ship outside this plugin.
 *
 * SchedulePress has nine built-in networks. Anything else — today only Google
 * Business Profile, which lives in SchedulePress Pro — describes itself through
 * the `wpsp_social_platforms` filter and is wired up generically: settings card,
 * template tab, Share Now dispatch, character limits, post panel.
 *
 * A platform listed in PRO_UPSELL but not registered is shown locked, so the
 * feature stays visible and sellable without any of its code being here.
 */
class Platforms
{
    /**
     * Platforms sold with SchedulePress Pro, rendered locked until Pro registers them.
     *
     * Marketing data only — no behaviour, no endpoints, no credentials.
     */
    const PRO_UPSELL = array(
        'google_business' => array(
            'label' => 'Google Business Profile',
            'logo'  => 'images/google-my-business-logo.svg',
            'icon'  => 'images/google_business.svg',
            'icon_white' => 'images/icon-google_business-small-white.png',
            // The white glyph the built-in networks draw on their round icon buttons.
            'glyph' => 'images/icon-google_business-glyph.svg',
            // The glyph on a round black badge, as the post panel and AI caption cards show it.
            'round' => 'images/icon-google_business-round.svg',
            'badge' => 'images/google-business-pro.svg',
            'desc'  => 'Share your posts to Google Business Profile automatically.',
            'doc'   => 'https://wpdeveloper.com/docs/share-wordpress-posts-on-google-business-profile/',
        ),
    );

    /**
     * Every validly described extension platform.
     *
     * Deliberately uncached: this runs after `init` in every consumer, and
     * caching it before Pro's init callback would freeze an empty list.
     *
     * @return array<string,array> slug => definition
     */
    public static function registered()
    {
        $platforms = apply_filters('wpsp_social_platforms', array());

        if (!is_array($platforms)) {
            return array();
        }

        return array_filter($platforms, array(__CLASS__, 'is_valid_definition'));
    }

    /**
     * A definition is only usable if we know what to call it and where its
     * profiles and on/off switch live in the settings option.
     *
     * @param mixed $definition
     * @return bool
     */
    public static function is_valid_definition($definition)
    {
        return is_array($definition)
            && !empty($definition['label'])
            && !empty($definition['list_key'])
            && !empty($definition['status_key']);
    }

    /**
     * @param string $slug
     * @return array|null
     */
    public static function get($slug)
    {
        $platforms = self::registered();

        return isset($platforms[$slug]) ? $platforms[$slug] : null;
    }

    /**
     * @return string[]
     */
    public static function slugs()
    {
        return array_keys(self::registered());
    }

    /**
     * @return array<string,string> slug => settings key holding the profile list
     */
    public static function list_keys()
    {
        $values = array();

        foreach (self::registered() as $slug => $definition) {
            $values[$slug] = $definition['list_key'];
        }

        return $values;
    }

    /**
     * @return array<string,string> slug => settings key holding the on/off switch
     */
    public static function status_keys()
    {
        $values = array();

        foreach (self::registered() as $slug => $definition) {
            $values[$slug] = $definition['status_key'];
        }

        return $values;
    }

    /**
     * @return array<string,string> slug => human label
     */
    public static function labels()
    {
        $values = array();

        foreach (self::registered() as $slug => $definition) {
            $values[$slug] = $definition['label'];
        }

        return $values;
    }

    /**
     * @return array<string,int> slug => character limit
     */
    public static function limits()
    {
        $limits = array();

        foreach (self::registered() as $slug => $definition) {
            $limits[$slug] = isset($definition['char_limit']) ? (int) $definition['char_limit'] : 0;
        }

        return $limits;
    }

    /**
     * Whether a slug is currently served by an extension.
     *
     * @param string $slug
     * @return bool
     */
    public static function is_registered($slug)
    {
        return null !== self::get($slug);
    }

    /**
     * Live platforms, shaped for the two JavaScript apps.
     *
     * @return array<string,array>
     */
    public static function for_js()
    {
        $platforms = array();

        foreach (self::registered() as $slug => $definition) {
            $platforms[$slug] = array(
                'label'      => $definition['label'],
                'list_key'   => $definition['list_key'],
                'status_key' => $definition['status_key'],
                'limit'      => isset($definition['char_limit']) ? (int) $definition['char_limit'] : 0,
                'color'      => isset($definition['color']) ? $definition['color'] : '',
                'icon_url'   => isset($definition['icon_url']) ? $definition['icon_url'] : '',
                'icon_bg_url' => isset($definition['icon_bg_url']) ? $definition['icon_bg_url'] : '',
                'icon_small_white_url' => isset($definition['icon_small_white_url']) ? $definition['icon_small_white_url'] : '',
                'icon_glyph_url' => self::asset_url($slug, $definition, 'icon_glyph_url', 'glyph'),
                'icon_round_url' => self::asset_url($slug, $definition, 'icon_round_url', 'round'),
                'automatic_connect' => !empty($definition['automatic_connect']),
                'locked'     => false,
            );
        }

        return $platforms;
    }

    /**
     * An icon URL the extension supplied, or the one shipped here for it.
     *
     * The glyph and round icons match the built-in networks' inline SVGs. An
     * extension can supply its own; a platform free sells for Pro falls back to
     * the copy in PRO_UPSELL, so Pro needs no change for it.
     *
     * @param string $slug
     * @param array  $definition
     * @param string $key        Definition key, e.g. `icon_glyph_url`.
     * @param string $upsell_key PRO_UPSELL key holding the shipped path.
     * @return string
     */
    private static function asset_url($slug, $definition, $key, $upsell_key)
    {
        if (!empty($definition[$key])) {
            return $definition[$key];
        }
        if (!empty(self::PRO_UPSELL[$slug][$upsell_key])) {
            return WPSP_ASSETS_URI . self::PRO_UPSELL[$slug][$upsell_key];
        }

        return '';
    }

    /**
     * Pro platforms with nothing serving them yet — the upsell set.
     *
     * An entry disappears from here the moment Pro registers the same slug, so
     * one build serves both tiers without any is_pro branching in the UI.
     *
     * @return array<string,array>
     */
    public static function locked_for_js()
    {
        $locked  = array();
        $strings = self::upsell_strings();
        $cta     = self::upsell_cta();

        foreach (self::PRO_UPSELL as $slug => $marketing) {
            if (self::is_registered($slug)) {
                continue;
            }

            $locked[$slug] = array(
                'label'      => isset($strings[$slug]['label']) ? $strings[$slug]['label'] : $marketing['label'],
                'desc'       => isset($strings[$slug]['desc']) ? $strings[$slug]['desc'] : $marketing['desc'],
                'list_key'   => '',
                'status_key' => '',
                'limit'      => 0,
                'color'      => '',
                'icon_url'   => WPSP_ASSETS_URI . $marketing['icon'],
                'icon_bg_url' => WPSP_ASSETS_URI . $marketing['logo'],
                'icon_small_white_url' => WPSP_ASSETS_URI . $marketing['icon_white'],
                'icon_glyph_url' => WPSP_ASSETS_URI . $marketing['glyph'],
                'icon_round_url' => WPSP_ASSETS_URI . $marketing['round'],
                'badge_url'  => WPSP_ASSETS_URI . $marketing['badge'],
                'doc'        => $marketing['doc'],
                'automatic_connect' => false,
                'locked'     => true,
                'cta_label'  => $cta['label'],
                'cta_text'   => $cta['text'],
                'cta_url'    => $cta['url'],
                'needs_update' => $cta['needs_update'],
            );
        }

        return $locked;
    }

    /**
     * What a locked surface should ask the user for.
     *
     * Pro absent means the feature is still for sale. Pro present but not
     * serving the slug means it predates the release that owns the platform,
     * so asking for money again is wrong — ask for an update instead.
     *
     * @param string|null $pro_version Defaults to the running Pro, null when absent.
     * @return array{needs_update:bool,label:string,text:string,url:string}
     */
    public static function upsell_cta($pro_version = null)
    {
        if (null === $pro_version) {
            $pro_version = defined('WPSP_PRO_VERSION') ? WPSP_PRO_VERSION : null;
        }

        if (!empty($pro_version)) {
            $minimum = defined('WPSP_MIN_PRO_VERSION') ? WPSP_MIN_PRO_VERSION : '';

            return array(
                'needs_update' => true,
                'label'        => __('UPDATE', 'wp-scheduled-posts'),
                'text'         => sprintf(
                    /* translators: %s: minimum required SchedulePress Pro version */
                    __('Update SchedulePress Pro to %s or newer to share here.', 'wp-scheduled-posts'),
                    $minimum
                ),
                'url'          => admin_url('plugins.php'),
            );
        }

        return array(
            'needs_update' => false,
            'label'        => __('PRO', 'wp-scheduled-posts'),
            'text'         => __('Upgrade to SchedulePress Pro to share here.', 'wp-scheduled-posts'),
            'url'          => 'https://schedulepress.com/#pricing',
        );
    }

    /**
     * Translatable copy for the locked cards.
     *
     * Kept out of PRO_UPSELL because a class constant cannot call __() and the
     * .pot scanner only sees literal strings.
     *
     * @return array<string,array>
     */
    public static function upsell_strings()
    {
        return array(
            'google_business' => array(
                'label' => __('Google Business Profile', 'wp-scheduled-posts'),
                'desc'  => __('Share your posts to Google Business Profile automatically.', 'wp-scheduled-posts'),
            ),
        );
    }
}
