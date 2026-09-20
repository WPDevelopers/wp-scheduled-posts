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
                'automatic_connect' => !empty($definition['automatic_connect']),
                'locked'     => false,
            );
        }

        return $platforms;
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
                'badge_url'  => WPSP_ASSETS_URI . $marketing['badge'],
                'doc'        => $marketing['doc'],
                'automatic_connect' => false,
                'locked'     => true,
            );
        }

        return $locked;
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
