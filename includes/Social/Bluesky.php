<?php

namespace WPSP\Social;

use WPSP\Helper;
use WPSP\Traits\SocialHelper;

/**
 * Bluesky (AT Protocol) social share integration.
 *
 * Authentication is credential based (handle + App Password). Because the
 * session `accessJwt` returned by `com.atproto.server.createSession` is
 * short-lived, a fresh session is created on every share using the stored
 * identifier + App Password (App Passwords do not expire).
 *
 * @since 5.3.0
 */
class Bluesky
{
    use SocialHelper;

    private $template_structure;
    private $is_category_as_tags;
    private $is_show_post_thumbnail;
    private $content_source;
    private $status_limit;
    private $post_share_limit;
    private $current_profile_id;

    public function __construct()
    {
        $settings = \WPSP\Helper::get_settings('social_templates');
        $settings = json_decode(json_encode($settings->bluesky ?? new \stdClass()), true);
        $this->template_structure     = (isset($settings['template_structure']) ? $settings['template_structure'] : '{title}{content}{url}{tags}');
        $this->is_category_as_tags    = (isset($settings['is_category_as_tags']) ? $settings['is_category_as_tags'] : '');
        $this->is_show_post_thumbnail = (isset($settings['is_show_post_thumbnail']) ? $settings['is_show_post_thumbnail'] : '');
        $this->content_source         = (isset($settings['content_source']) ? $settings['content_source'] : '');
        $this->status_limit           = (isset($settings['note_limit']) ? $settings['note_limit'] : 300);
        $this->post_share_limit       = (isset($settings['post_share_limit']) ? $settings['post_share_limit'] : 0);
    }

    public function instance()
    {
        add_action('wpsp_publish_future_post', array($this, 'wpsp_bluesky_post_event'), 20, 1);
        add_action('wpsp_bluesky_post', array($this, 'wpsp_bluesky_post'), 10, 1);
        // republish hook
        $this->schedule_republish_social_share_hook();
    }

    /**
     * Schedule Republish Social Share
     * @return void
     */
    public function schedule_republish_social_share_hook()
    {
        if (\WPSP\Helper::get_settings('is_republish_social_share')) {
            add_action('wpscp_pro_schedule_republish_share', array($this, 'wpscp_republish_bluesky_post'), 15, 1);
        }
    }

    /**
     * Triggered by `wpsp_publish_future_post`; schedules the real share event.
     */
    public function wpsp_bluesky_post_event($post_id)
    {
        $post_details = $post_id;
        if (!is_object($post_id)) {
            $post_details = get_post($post_id);
        }

        if ($post_details->post_status == 'publish') {
            wp_schedule_single_event(time(), 'wpsp_bluesky_post', array($post_details->ID));
        }
    }

    /**
     * Save share response to post meta + bump share count.
     */
    public function save_metabox_social_share($post_id, $response, $profile_key, $ID)
    {
        $meta_name      = '__wpscppro_bluesky_share_log';
        $count_meta_key = '__wpsp_bluesky_share_count_' . $ID;
        $oldData        = get_post_meta($post_id, $meta_name, true);
        if ($oldData != "") {
            $oldData[$profile_key] = $response;
            update_post_meta($post_id, $meta_name, $oldData);
        } else {
            add_post_meta($post_id, $meta_name, array($profile_key => $response));
        }
        $old_share_count = get_post_meta($post_id, $count_meta_key, true);
        if ($old_share_count != '') {
            update_post_meta($post_id, $count_meta_key, intval($old_share_count) + 1);
        } else {
            add_post_meta($post_id, $count_meta_key, 1);
        }
    }

    /**
     * Create an AT Protocol session from the stored credentials.
     *
     * @param string $pds        Personal Data Server base URL (e.g. https://bsky.social).
     * @param string $identifier Handle or email.
     * @param string $password   App Password.
     * @return object|\WP_Error  Decoded session object ({did, accessJwt, ...}) or WP_Error.
     */
    public function create_session($pds, $identifier, $password)
    {
        $pds      = untrailingslashit($pds ?: WPSCP_BLUESKY_PDS);
        $response = Helper::wpsp_curl(
            $pds . '/xrpc/com.atproto.server.createSession',
            json_encode(array('identifier' => $identifier, 'password' => $password)),
            'application/json',
            true,
            array('Accept: application/json')
        );
        $body = json_decode($response['result']);
        if ($response['code'] == 200 && !empty($body->accessJwt)) {
            return $body;
        }
        $message = !empty($body->message) ? $body->message : __('Unable to authenticate with Bluesky. Please check your handle and App Password.', 'wp-scheduled-posts');
        return new \WP_Error('bluesky_auth_failed', $message);
    }

    /**
     * Upload an image to the PDS and return the blob reference for embedding.
     *
     * @return object|null Blob object on success, null otherwise.
     */
    public function upload_blob($pds, $access_jwt, $file_path)
    {
        if (empty($file_path) || !@file_exists($file_path)) {
            return null;
        }
        $contents = @file_get_contents($file_path);
        if ($contents === false) {
            return null;
        }
        $mime = function_exists('mime_content_type') ? mime_content_type($file_path) : 'image/jpeg';
        if (empty($mime) || strpos($mime, 'image/') !== 0) {
            $mime = 'image/jpeg';
        }
        $response = Helper::wpsp_curl(
            untrailingslashit($pds) . '/xrpc/com.atproto.repo.uploadBlob',
            $contents,
            $mime,
            true,
            array('Authorization: Bearer ' . $access_jwt)
        );
        $body = json_decode($response['result']);
        if ($response['code'] == 200 && !empty($body->blob)) {
            return $body->blob;
        }
        return null;
    }

    /**
     * Detect URLs in the text and build AT Protocol richtext link facets so the
     * links are clickable. Offsets are UTF-8 byte offsets (PHP strings are bytes).
     *
     * @return array
     */
    public function build_facets($text)
    {
        $facets = array();
        // Matches http(s) URLs; trims common trailing punctuation.
        if (preg_match_all('/https?:\/\/[^\s\]\)]+/i', $text, $matches, PREG_OFFSET_CAPTURE)) {
            foreach ($matches[0] as $match) {
                $uri        = rtrim($match[0], '.,;:!?)\'"');
                $byte_start = $match[1]; // PREG_OFFSET_CAPTURE returns byte offsets
                $byte_end   = $byte_start + strlen($uri);
                $facets[]   = array(
                    'index'    => array(
                        'byteStart' => $byte_start,
                        'byteEnd'   => $byte_end,
                    ),
                    'features' => array(
                        array(
                            '$type' => 'app.bsky.richtext.facet#link',
                            'uri'   => $uri,
                        ),
                    ),
                );
            }
        }
        return $facets;
    }

    /**
     * Build the share text + resolve the image file path for the post.
     *
     * @return array { text: string, image_path: string|null }
     */
    public function get_share_content_args($post_id)
    {
        $post_details = get_post($post_id);
        $post_link    = esc_url(get_permalink($post_id));
        $title        = $post_details->post_title;

        if ($this->content_source === 'excerpt' && has_excerpt($post_details->ID)) {
            $desc = wp_strip_all_tags($post_details->post_excerpt);
        } else {
            $desc = wp_strip_all_tags($post_details->post_content);
            if (is_visual_composer_post($post_id) && class_exists('WPBMap')) {
                \WPBMap::addAllMappedShortcodes();
                $desc = Helper::strip_all_html_and_keep_single_breaks(do_shortcode($desc));
            }
        }

        $hashTags = (($this->getPostHasTags($post_id, 'bluesky', $this->is_category_as_tags) != false) ? $this->getPostHasTags($post_id, 'bluesky', $this->is_category_as_tags) : '');
        if ($this->is_category_as_tags == true) {
            $hashTags .= ' ' . $this->getPostHasCats($post_id);
        }

        $text = $this->social_share_content_template_structure(
            $this->template_structure,
            $title,
            $desc,
            $post_link,
            $hashTags,
            $this->status_limit - 5,
            null,
            'bluesky',
            $post_id,
            $this->current_profile_id ?? null
        );

        // Resolve image: custom social share image -> featured image -> request fallback.
        $image_path = null;
        if ($this->is_show_post_thumbnail == true) {
            $uploads          = wp_upload_dir();
            $socialShareImage = get_post_meta($post_id, '_wpscppro_custom_social_share_image', true);
            if (!empty($socialShareImage) && $socialShareImage != 0) {
                $image_url = wp_get_attachment_url($socialShareImage);
            } elseif (has_post_thumbnail($post_id)) {
                $image_url = wp_get_attachment_url(get_post_thumbnail_id($post_id));
            } else {
                $featured_image_id = Helper::get_featured_image_id_from_request();
                $image_url         = !empty($featured_image_id) ? wp_get_attachment_image_url($featured_image_id, 'full') : '';
            }
            if (!empty($image_url)) {
                $image_path = str_replace($uploads['baseurl'], $uploads['basedir'], $image_url);
            }
        }

        return array(
            'text'       => $text,
            'image_path' => $image_path,
        );
    }

    /**
     * Main share method.
     *
     * @return array|void { success: bool, log: mixed }
     */
    public function remote_post($identifier, $app_password, $post_id, $profile_key, $did = '', $ID = '', $force_share = false, $pds = '')
    {
        $profile = \WPSP\Helper::get_profile('bluesky', $profile_key);
        $this->current_profile_id = !empty($profile->id) ? $profile->id : $ID;
        $count_meta_key           = '__wpsp_bluesky_share_count_' . $this->current_profile_id;

        // social share type
        $get_share_type = get_post_meta($post_id, '_bluesky_share_type', true);
        if ($get_share_type === 'custom') {
            $get_all_selected_profile = get_post_meta($post_id, '_selected_social_profile', true);
            $check_profile_exists     = Helper::is_profile_exits($this->current_profile_id, $get_all_selected_profile);
            if (!$check_profile_exists) {
                return;
            }
        }

        $is_enabled_custom_template = get_post_meta($post_id, '_wpsp_enable_custom_social_template', true);
        if ($is_enabled_custom_template) {
            $templates     = get_post_meta($post_id, '_wpsp_custom_templates', true);
            $platform_data = isset($templates['bluesky']) ? $templates['bluesky'] : null;
            $profiles      = is_array($platform_data) && isset($platform_data['profiles']) ? $platform_data['profiles'] : array();
            if (is_array($profiles) && !in_array($this->current_profile_id, $profiles)) {
                return;
            }
        }

        $dont_share = get_post_meta($post_id, '_wpscppro_dont_share_socialmedia', true);
        if (empty($identifier) || empty($app_password) || $dont_share == 'on' || $dont_share == 1) {
            return;
        }

        if ((get_post_meta($post_id, $count_meta_key, true)) && $this->post_share_limit != 0 && get_post_meta($post_id, $count_meta_key, true) >= $this->post_share_limit) {
            return array(
                'success' => false,
                'log'     => __('Your max share post limit has been executed!!', 'wp-scheduled-posts'),
            );
        }

        if (get_post_meta($post_id, '_wpsp_is_bluesky_share', true) == 'on' || $force_share) {
            $errorFlag = false;
            $response  = '';

            try {
                $pds     = $pds ?: WPSCP_BLUESKY_PDS;
                $session = $this->create_session($pds, $identifier, $app_password);
                if (is_wp_error($session)) {
                    return array(
                        'success' => false,
                        'log'     => $session->get_error_message(),
                    );
                }
                $access_jwt = $session->accessJwt;
                $repo_did   = !empty($session->did) ? $session->did : $did;

                $args  = $this->get_share_content_args($post_id);
                $text  = $args['text'];

                $record = array(
                    '$type'     => 'app.bsky.feed.post',
                    'text'      => $text,
                    'createdAt' => gmdate('Y-m-d\TH:i:s.000\Z'),
                    'langs'     => array(get_bloginfo('language') ? substr(get_bloginfo('language'), 0, 2) : 'en'),
                );

                $facets = $this->build_facets($text);
                if (!empty($facets)) {
                    $record['facets'] = $facets;
                }

                // Image embed
                if (!empty($args['image_path'])) {
                    $blob = $this->upload_blob($pds, $access_jwt, $args['image_path']);
                    if (!empty($blob)) {
                        $record['embed'] = array(
                            '$type'  => 'app.bsky.embed.images',
                            'images' => array(
                                array(
                                    'alt'   => get_the_title($post_id),
                                    'image' => $blob,
                                ),
                            ),
                        );
                    }
                }

                $create = Helper::wpsp_curl(
                    untrailingslashit($pds) . '/xrpc/com.atproto.repo.createRecord',
                    json_encode(array(
                        'repo'       => $repo_did,
                        'collection' => 'app.bsky.feed.post',
                        'record'     => $record,
                    )),
                    'application/json',
                    true,
                    array('Authorization: Bearer ' . $access_jwt, 'Accept: application/json')
                );
                $result = json_decode($create['result']);

                if ($create['code'] == 200 && !empty($result->uri)) {
                    $shareInfo = array(
                        'share_id'     => $result->uri,
                        'share_cid'    => isset($result->cid) ? $result->cid : '',
                        'publish_date' => time(),
                    );
                    $this->save_metabox_social_share($post_id, $shareInfo, $profile_key, $this->current_profile_id);
                    $errorFlag = true;
                    $response  = $shareInfo;
                } else {
                    $errorFlag = false;
                    $response  = !empty($result->message) ? $result->message : (__('Bluesky Connection Problem. error code: ', 'wp-scheduled-posts') . $create['code']);
                }
            } catch (\Exception $e) {
                $errorFlag = false;
                $response  = $e->getMessage();
            }

            return array(
                'success' => $errorFlag,
                'log'     => $response,
            );
        }
        return;
    }

    /**
     * Republish hook.
     */
    public function wpscp_republish_bluesky_post($post_id)
    {
        $dont_share = get_post_meta($post_id, '_wpscppro_dont_share_socialmedia', true);
        if ($dont_share == 'on' || $dont_share == 1) {
            return;
        }
        $this->share_to_all_profiles($post_id);
    }

    /**
     * Scheduled publish hook.
     */
    public function wpsp_bluesky_post($post_id)
    {
        $dont_share = get_post_meta($post_id, '_wpscppro_dont_share_socialmedia', true);
        if ($dont_share == 'on' || $dont_share == 1) {
            return;
        }
        $this->share_to_all_profiles($post_id);
    }

    /**
     * Loop over every active Bluesky profile and share.
     */
    private function share_to_all_profiles($post_id)
    {
        $profiles = \WPSP\Helper::get_social_profile(WPSCP_BLUESKY_OPTION_NAME);
        if (is_array($profiles) && count($profiles) > 0) {
            foreach ($profiles as $profile_key => $profile) {
                if ($profile->status == false) {
                    continue;
                }
                $this->remote_post(
                    $profile->app_id,
                    $profile->app_secret,
                    $post_id,
                    $profile_key,
                    isset($profile->__id) ? $profile->__id : '',
                    $profile->id,
                    true,
                    isset($profile->pds) ? $profile->pds : WPSCP_BLUESKY_PDS
                );
            }
        }
    }

    /**
     * Instant share (AJAX).
     */
    public function socialMediaInstantShare($identifier, $app_password, $did, $ID, $post_id, $profile_key, $pds = '', $is_share_on_publish = false)
    {
        $response = $this->remote_post($identifier, $app_password, $post_id, $profile_key, $did, $ID, true, $pds);
        if ($is_share_on_publish) {
            return;
        }
        if ($response['success'] == false) {
            wp_send_json_error($response['log']);
        } else {
            wp_send_json_success($response['log']);
        }
    }
}
