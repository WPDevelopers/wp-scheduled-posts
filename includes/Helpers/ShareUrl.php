<?php
/**
 * The link a social share carries for a post, with automatic UTM tags.
 *
 * @package WPScheduledPosts
 */

namespace WPSP\Helpers;

class ShareUrl
{
    /**
     * The post's permalink for a share, tagged for Google Analytics when the
     * "UTM tags on shared links" setting is on (it is on unless switched off).
     *
     * @param int    $post_id
     * @param string $platform Network key: facebook, x, linkedin, pinterest, bluesky, mastodon, threads, medium.
     * @return string
     */
    public static function for_post($post_id, $platform)
    {
        $url = get_permalink($post_id);
        if (self::is_enabled()) {
            $tags = apply_filters('wpsp_share_utm_tags', self::default_tags($platform), $platform, $post_id);
            $url  = self::tag($url, is_array($tags) ? $tags : array());
        }
        // esc_url_raw, not esc_url: esc_url turns "&" between the tags into "&#038;".
        return esc_url_raw($url);
    }

    /**
     * On when the setting is missing (a site that has not saved it yet) or truthy.
     *
     * @return bool
     */
    public static function is_enabled()
    {
        $value = \WPSP\Helper::get_settings('utm_tags_on_shared_links');
        return null === $value ? true : (bool) $value;
    }

    /**
     * @param string $platform
     * @return array<string,string>
     */
    public static function default_tags($platform)
    {
        return array(
            'utm_source'   => $platform,
            'utm_medium'   => 'social',
            'utm_campaign' => 'schedulepress',
        );
    }

    /**
     * Add tags to a URL. A URL that already carries any utm_ tag is returned as it is,
     * and a tag with an empty value is skipped. Pure, so it can be unit tested.
     *
     * @param string               $url
     * @param array<string,string> $tags
     * @return string
     */
    public static function tag($url, array $tags)
    {
        $url  = (string) $url;
        $tags = array_filter($tags, function ($value) {
            return '' !== (string) $value;
        });
        if ('' === $url || empty($tags)) {
            return $url;
        }

        $fragment = '';
        $hash_at  = strpos($url, '#');
        if (false !== $hash_at) {
            $fragment = substr($url, $hash_at);
            $url      = substr($url, 0, $hash_at);
        }

        $query_at = strpos($url, '?');
        if (false !== $query_at) {
            parse_str(substr($url, $query_at + 1), $existing);
            foreach (array_keys($existing) as $key) {
                if (0 === stripos((string) $key, 'utm_')) {
                    return $url . $fragment;
                }
            }
        }

        $separator = false === $query_at ? '?' : (substr($url, -1) === '?' || substr($url, -1) === '&' ? '' : '&');
        return $url . $separator . http_build_query($tags, '', '&', PHP_QUERY_RFC3986) . $fragment;
    }
}
