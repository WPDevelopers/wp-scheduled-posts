<?php

namespace WPSP\Social;

use WPSP\Helper;
use WPSP\Traits\SocialHelper;

/**
 * Mastodon social share integration.
 *
 * Mastodon is federated: every account lives on its own instance, so the API
 * base URL is stored per profile (`instance_url`) instead of being a global
 * constant like Bluesky's PDS. Authentication is credential based — the user
 * pastes an access token created at
 * `{instance}/settings/applications` with the `write:statuses` +
 * `write:media` (and `read:accounts` for the profile lookup) scopes. Mastodon
 * access tokens do not expire, so the token is used directly as a bearer
 * token on every share; there is no session exchange step.
 *
 * @since 5.3.0
 */
class Mastodon
{
    use SocialHelper;

    /**
     * Default status character limit. Instances may configure their own
     * (exposed as `configuration.statuses.max_characters` on
     * `/api/v1/instance`), but 500 is the Mastodon default and the safe
     * assumption when the instance does not advertise one.
     */
    const STATUS_LIMIT = 500;

    /**
     * How many times to poll `/api/v1/media/{id}` while an uploaded attachment
     * is still being processed, and how long to wait between polls (seconds).
     * `/api/v2/media` returns 202 for anything that needs transcoding; posting
     * a status with an unprocessed media id fails with 422.
     */
    const MEDIA_POLL_ATTEMPTS = 5;
    const MEDIA_POLL_INTERVAL = 1;

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
        $settings = json_decode(json_encode($settings->mastodon ?? new \stdClass()), true);
        $this->template_structure     = (isset($settings['template_structure']) ? $settings['template_structure'] : '{title}{content}{url}{tags}');
        $this->is_category_as_tags    = (isset($settings['is_category_as_tags']) ? $settings['is_category_as_tags'] : '');
        $this->is_show_post_thumbnail = (isset($settings['is_show_post_thumbnail']) ? $settings['is_show_post_thumbnail'] : '');
        $this->content_source         = (isset($settings['content_source']) ? $settings['content_source'] : '');
        $note_limit                   = (isset($settings['note_limit']) ? intval($settings['note_limit']) : self::STATUS_LIMIT);
        $this->status_limit           = ($note_limit > 0) ? $note_limit : self::STATUS_LIMIT;
        $this->post_share_limit       = (isset($settings['post_share_limit']) ? $settings['post_share_limit'] : 0);
    }

    public function instance()
    {
        add_action('wpsp_publish_future_post', array($this, 'wpsp_mastodon_post_event'), 20, 1);
        add_action('wpsp_mastodon_post', array($this, 'wpsp_mastodon_post'), 10, 1);
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
            add_action('wpscp_pro_schedule_republish_share', array($this, 'wpscp_republish_mastodon_post'), 15, 1);
        }
    }

    /**
     * Normalise a user supplied instance URL to a scheme-qualified origin.
     *
     * Users paste `mastodon.social`, `https://mastodon.social/` or even a full
     * profile URL; everything downstream concatenates `/api/v1/...` onto this.
     *
     * @param string $instance_url
     * @return string
     */
    public static function normalize_instance_url($instance_url)
    {
        $instance_url = trim((string) $instance_url);
        if ($instance_url === '') {
            return '';
        }
        if (!preg_match('#^https?://#i', $instance_url)) {
            $instance_url = 'https://' . $instance_url;
        }
        $parts = wp_parse_url($instance_url);
        if (empty($parts['host'])) {
            return '';
        }
        $scheme = !empty($parts['scheme']) ? $parts['scheme'] : 'https';
        $port   = !empty($parts['port']) ? ':' . $parts['port'] : '';

        return $scheme . '://' . $parts['host'] . $port;
    }

    /**
     * Triggered by `wpsp_publish_future_post`; schedules the real share event.
     */
    public function wpsp_mastodon_post_event($post_id)
    {
        $post_details = $post_id;
        if (!is_object($post_id)) {
            $post_details = get_post($post_id);
        }

        if ($post_details->post_status == 'publish') {
            wp_schedule_single_event(time(), 'wpsp_mastodon_post', array($post_details->ID));
        }
    }

    /**
     * Save share response to post meta + bump share count.
     */
    public function save_metabox_social_share($post_id, $response, $profile_key, $ID)
    {
        $meta_name      = '__wpscppro_mastodon_share_log';
        $count_meta_key = '__wpsp_mastodon_share_count_' . $ID;
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
     * Verify an access token and return the authenticated account.
     *
     * @param string $instance_url
     * @param string $access_token
     * @return object|\WP_Error Account object ({id, username, display_name, avatar}) or WP_Error.
     */
    public function verify_credentials($instance_url, $access_token)
    {
        $instance_url = self::normalize_instance_url($instance_url);
        if (empty($instance_url)) {
            return new \WP_Error('mastodon_invalid_instance', __('Please enter a valid Mastodon instance URL (e.g. https://mastodon.social).', 'wp-scheduled-posts'));
        }

        $response = Helper::wpsp_curl(
            $instance_url . '/api/v1/accounts/verify_credentials',
            '',
            'application/json',
            false,
            array('Authorization: Bearer ' . $access_token, 'Accept: application/json')
        );
        $body = json_decode($response['result']);

        if ($response['code'] == 200 && !empty($body->id)) {
            return $body;
        }

        $message = !empty($body->error) ? $body->error : __('Unable to authenticate with Mastodon. Please check your instance URL and access token.', 'wp-scheduled-posts');
        return new \WP_Error('mastodon_auth_failed', $message);
    }

    /**
     * Upload an image and return the media attachment id.
     *
     * Mastodon's `/api/v2/media` responds 202 while the attachment is still
     * being processed; attaching an unprocessed id to a status is rejected, so
     * we poll `/api/v1/media/{id}` until it reports ready (200) before using it.
     *
     * @return string|null Media id on success, null otherwise.
     */
    public function upload_media($instance_url, $access_token, $file_path, $description = '')
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

        // Build the multipart body by hand: Helper::wpsp_curl always sets an
        // explicit Content-Type header, which would clobber the boundary curl
        // generates for a CURLFile payload.
        $boundary = wp_generate_password(24, false);
        $eol      = "\r\n";
        $body     = '--' . $boundary . $eol;
        $body    .= 'Content-Disposition: form-data; name="file"; filename="' . basename($file_path) . '"' . $eol;
        $body    .= 'Content-Type: ' . $mime . $eol . $eol;
        $body    .= $contents . $eol;
        if (!empty($description)) {
            $body .= '--' . $boundary . $eol;
            $body .= 'Content-Disposition: form-data; name="description"' . $eol . $eol;
            $body .= $description . $eol;
        }
        $body .= '--' . $boundary . '--' . $eol;

        $response = Helper::wpsp_curl(
            $instance_url . '/api/v2/media',
            $body,
            'multipart/form-data; boundary=' . $boundary,
            true,
            array('Authorization: Bearer ' . $access_token, 'Accept: application/json')
        );
        $result = json_decode($response['result']);

        if (empty($result->id) || ($response['code'] != 200 && $response['code'] != 202)) {
            return null;
        }

        // 200 means the attachment is ready to use immediately.
        if ($response['code'] == 200) {
            return $result->id;
        }

        return $this->wait_for_media($instance_url, $access_token, $result->id);
    }

    /**
     * Poll a processing media attachment until the instance reports it ready.
     *
     * @return string|null The media id once ready, null if it never finished.
     */
    private function wait_for_media($instance_url, $access_token, $media_id)
    {
        for ($attempt = 0; $attempt < self::MEDIA_POLL_ATTEMPTS; $attempt++) {
            sleep(self::MEDIA_POLL_INTERVAL);
            $check = Helper::wpsp_curl(
                $instance_url . '/api/v1/media/' . $media_id,
                '',
                'application/json',
                false,
                array('Authorization: Bearer ' . $access_token, 'Accept: application/json')
            );
            if ($check['code'] == 200) {
                return $media_id;
            }
            // 206 = still processing; anything else is a hard failure.
            if ($check['code'] != 206) {
                return null;
            }
        }
        return null;
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

        $hashTags = (($this->getPostHasTags($post_id, 'mastodon', $this->is_category_as_tags) != false) ? $this->getPostHasTags($post_id, 'mastodon', $this->is_category_as_tags) : '');
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
            'mastodon',
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
    public function remote_post($access_token, $post_id, $profile_key, $account_id = '', $ID = '', $force_share = false, $instance_url = '')
    {
        $profile = \WPSP\Helper::get_profile('mastodon', $profile_key);
        $this->current_profile_id = !empty($profile->id) ? $profile->id : $ID;
        $count_meta_key           = '__wpsp_mastodon_share_count_' . $this->current_profile_id;

        // social share type
        $get_share_type = get_post_meta($post_id, '_mastodon_share_type', true);
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
            $platform_data = isset($templates['mastodon']) ? $templates['mastodon'] : null;
            $profiles      = is_array($platform_data) && isset($platform_data['profiles']) ? $platform_data['profiles'] : array();
            // An empty list means no profile was picked, which is "no restriction" rather than "share to nobody".
            if (is_array($profiles) && !empty($profiles) && !in_array($this->current_profile_id, $profiles)) {
                return;
            }
        }

        $dont_share = get_post_meta($post_id, '_wpscppro_dont_share_socialmedia', true);
        if (empty($access_token) || $dont_share == 'on' || $dont_share == 1) {
            return;
        }

        if ((get_post_meta($post_id, $count_meta_key, true)) && $this->post_share_limit != 0 && get_post_meta($post_id, $count_meta_key, true) >= $this->post_share_limit) {
            return array(
                'success' => false,
                'log'     => __('Your max share post limit has been executed!!', 'wp-scheduled-posts'),
            );
        }

        if (get_post_meta($post_id, '_wpsp_is_mastodon_share', true) == 'on' || $force_share) {
            $errorFlag = false;
            $response  = '';

            try {
                $instance_url = self::normalize_instance_url($instance_url);
                if (empty($instance_url)) {
                    return array(
                        'success' => false,
                        'log'     => __('This Mastodon profile has no valid instance URL. Please reconnect the account.', 'wp-scheduled-posts'),
                    );
                }

                $args = $this->get_share_content_args($post_id);

                $status = array(
                    'status'   => $args['text'],
                    'language' => get_bloginfo('language') ? substr(get_bloginfo('language'), 0, 2) : 'en',
                );

                // Image attachment
                if (!empty($args['image_path'])) {
                    $media_id = $this->upload_media($instance_url, $access_token, $args['image_path'], get_the_title($post_id));
                    if (!empty($media_id)) {
                        $status['media_ids'] = array($media_id);
                    }
                }

                $create = Helper::wpsp_curl(
                    $instance_url . '/api/v1/statuses',
                    json_encode($status),
                    'application/json',
                    true,
                    array(
                        'Authorization: Bearer ' . $access_token,
                        'Accept: application/json',
                        // Guards against a retried cron run double-posting the
                        // same status; Mastodon returns the original status.
                        'Idempotency-Key: wpsp-' . $post_id . '-' . $this->current_profile_id . '-' . gmdate('YmdH'),
                    )
                );
                $result = json_decode($create['result']);

                if ($create['code'] == 200 && !empty($result->id)) {
                    $shareInfo = array(
                        'share_id'     => $result->id,
                        'share_url'    => isset($result->url) ? $result->url : '',
                        'publish_date' => time(),
                    );
                    $this->save_metabox_social_share($post_id, $shareInfo, $profile_key, $this->current_profile_id);
                    $errorFlag = true;
                    $response  = $shareInfo;
                } else {
                    $errorFlag = false;
                    $response  = !empty($result->error) ? $result->error : (__('Mastodon Connection Problem. error code: ', 'wp-scheduled-posts') . $create['code']);
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
    public function wpscp_republish_mastodon_post($post_id)
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
    public function wpsp_mastodon_post($post_id)
    {
        $dont_share = get_post_meta($post_id, '_wpscppro_dont_share_socialmedia', true);
        if ($dont_share == 'on' || $dont_share == 1) {
            return;
        }
        $this->share_to_all_profiles($post_id);
    }

    /**
     * Loop over every active Mastodon profile and share.
     */
    private function share_to_all_profiles($post_id)
    {
        $profiles = \WPSP\Helper::get_social_profile(WPSCP_MASTODON_OPTION_NAME);
        if (is_array($profiles) && count($profiles) > 0) {
            foreach ($profiles as $profile_key => $profile) {
                if ($profile->status == false) {
                    continue;
                }
                $this->remote_post(
                    $profile->access_token,
                    $post_id,
                    $profile_key,
                    isset($profile->__id) ? $profile->__id : '',
                    $profile->id,
                    true,
                    isset($profile->instance_url) ? $profile->instance_url : WPSCP_MASTODON_INSTANCE
                );
            }
        }
    }

    /**
     * Instant share (AJAX).
     */
    public function socialMediaInstantShare($access_token, $account_id, $ID, $post_id, $profile_key, $instance_url = '', $is_share_on_publish = false)
    {
        $response = $this->remote_post($access_token, $post_id, $profile_key, $account_id, $ID, true, $instance_url);
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
