<?php
/**
 * Makes sure Facebook can build the link preview (image, title, description)
 * for the link a share carries.
 *
 * Facebook builds the preview card of a /feed post from its own copy of the
 * link's Open Graph data. When it has no copy yet, it fetches the page while
 * the post is being created, and if that fetch does not finish in time the
 * post shows only the domain. A link with UTM tags (ShareUrl) is a URL
 * Facebook has never seen. Its page also names the clean permalink in og:url,
 * so Facebook has to fetch twice. That is the case that lost the preview.
 *
 * So before posting, Facebook is asked to scrape the clean permalink and then
 * the exact link being shared, and the post goes out only after that. If
 * Facebook still cannot read the tagged link, the share falls back to the
 * clean permalink: a working preview matters more than the UTM tags.
 *
 * @package WPScheduledPosts
 */

namespace WPSP\Helpers;

class FacebookLinkPreview
{
    const GRAPH_URL = 'https://graph.facebook.com/v20.0/';

    /**
     * Seconds to wait for one scrape. Facebook fetches the page itself before it
     * answers, so this has to cover a slow site.
     */
    const SCRAPE_TIMEOUT = 20;

    /**
     * Scrape results for this request, keyed by URL. Several Facebook profiles
     * share the same post in one run, and Facebook's copy is global, so a URL
     * is scraped once per request.
     *
     * @var array<string,bool>
     */
    private static $scraped = array();

    /**
     * Prepare the /feed arguments of a share so that Facebook can show the
     * preview for its link.
     *
     * @param string               $app_id
     * @param string               $app_secret
     * @param array<string,string> $link_data  Arguments for /feed: `link` and/or `message`.
     * @param string               $share_link The link the share carries (it may have UTM tags).
     * @param string               $permalink  The post's clean permalink.
     * @return array<string,string> The arguments to post, with the clean permalink
     *                              in place of the tagged link if Facebook could not read it.
     */
    public static function prepare($app_id, $app_secret, array $link_data, $share_link, $permalink)
    {
        $share_link = (string) $share_link;
        $permalink  = (string) $permalink;

        if ('' === $share_link || !self::carries_link($link_data, $share_link)) {
            return $link_data;
        }

        // The clean permalink first: it is the og:url of the page, so it is the
        // object Facebook ends up attaching. Then the exact link being shared.
        if ('' !== $permalink && $permalink !== $share_link) {
            self::scrape($app_id, $app_secret, $permalink);
        }
        $readable = self::scrape($app_id, $app_secret, $share_link);

        $fallback = apply_filters(
            'wpsp_facebook_share_clean_link_fallback',
            !$readable && '' !== $permalink && $permalink !== $share_link,
            $share_link,
            $permalink
        );
        if ($fallback) {
            $link_data = self::swap_link($link_data, $share_link, $permalink);
        }

        return $link_data;
    }

    /**
     * Ask Facebook to fetch a URL now and refresh its copy of the page's Open
     * Graph data.
     *
     * @param string $app_id
     * @param string $app_secret
     * @param string $url
     * @return bool True when Facebook read the page (it returned a title for it).
     */
    public static function scrape($app_id, $app_secret, $url)
    {
        $url = (string) $url;
        if ('' === $url || '' === (string) $app_id || '' === (string) $app_secret) {
            return false;
        }
        if (isset(self::$scraped[$url])) {
            return self::$scraped[$url];
        }

        // The token goes in the body, not the query string, so it stays out of
        // proxy and server logs.
        $response = wp_remote_post(self::GRAPH_URL, array(
            'timeout' => self::SCRAPE_TIMEOUT,
            'body'    => array(
                'id'           => $url,
                'scrape'       => 'true',
                'access_token' => $app_id . '|' . $app_secret,
            ),
        ));

        $ok = self::is_readable_response($response);
        if (!$ok && defined('WP_DEBUG') && WP_DEBUG && function_exists('wp_trigger_error')) {
            wp_trigger_error('', 'SchedulePress: Facebook could not read ' . $url . ' before sharing.', E_USER_NOTICE);
        }

        self::$scraped[$url] = $ok;
        return $ok;
    }

    /**
     * Whether a scrape response shows Facebook read the page: HTTP 200, no error,
     * and a title. Facebook falls back to <title> when there is no og:title, so a
     * page it really fetched always has one.
     *
     * @param mixed $response A wp_remote_post() result.
     * @return bool
     */
    public static function is_readable_response($response)
    {
        if (is_wp_error($response) || 200 !== (int) wp_remote_retrieve_response_code($response)) {
            return false;
        }
        $data = json_decode((string) wp_remote_retrieve_body($response), true);
        if (!is_array($data) || isset($data['error'])) {
            return false;
        }
        return isset($data['title']) && '' !== trim((string) $data['title']);
    }

    /**
     * Whether the share carries the link: as the `link` argument, or inside the
     * message text (the "status" types put {url} in the message).
     *
     * @param array<string,string> $link_data
     * @param string               $share_link
     * @return bool
     */
    public static function carries_link(array $link_data, $share_link)
    {
        if (!empty($link_data['link'])) {
            return true;
        }
        return isset($link_data['message']) && false !== strpos((string) $link_data['message'], (string) $share_link);
    }

    /**
     * Put the clean permalink in place of the tagged link, in `link` and in the message.
     *
     * @param array<string,string> $link_data
     * @param string               $share_link
     * @param string               $permalink
     * @return array<string,string>
     */
    public static function swap_link(array $link_data, $share_link, $permalink)
    {
        if (isset($link_data['link']) && $link_data['link'] === $share_link) {
            $link_data['link'] = $permalink;
        }
        if (isset($link_data['message'])) {
            $link_data['message'] = str_replace($share_link, $permalink, (string) $link_data['message']);
        }
        return $link_data;
    }

    /**
     * Forget this request's scrape results. For tests.
     *
     * @return void
     */
    public static function reset()
    {
        self::$scraped = array();
    }
}
