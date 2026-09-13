<?php

namespace WPSP;

class Migration {
    public static function version_3_to_4(){
        if (get_option('wpsp_data_migration_3_to_4') == false) {
            $settings = json_decode(get_option(WPSP_SETTINGS_NAME), true);;
            $old_settings = get_option('wpscp_options');
            // old version is installed
            if ($old_settings != false) {
                if (isset($old_settings['show_dashboard_widget'])) {
                    $settings['is_show_dashboard_widget'] = $old_settings['show_dashboard_widget'];
                }
                if (isset($old_settings['show_in_front_end_adminbar'])) {
                    $settings['is_show_sitewide_bar_posts'] = $old_settings['show_in_front_end_adminbar'];
                }
                if (isset($old_settings['show_in_adminbar'])) {
                    $settings['is_show_admin_bar_posts'] = $old_settings['show_in_adminbar'];
                }
                if (isset($old_settings['allow_post_types'])) {
                    $settings['allow_post_types'] = $old_settings['allow_post_types'];
                }
                if (isset($old_settings['allow_user_role'])) {
                    $settings['allow_user_by_role'] = $old_settings['allow_user_role'];
                }
                if (isset($old_settings['allow_categories'])) {
                    $settings['allow_categories'] = Helper::get_all_cats_id_to_slugs($old_settings['allow_categories']);
                }
                if (isset($old_settings['adminbar_item_template'])) {
                    $settings['adminbar_list_structure_template'] = $old_settings['adminbar_item_template'];
                }
                if (isset($old_settings['adminbar_title_length'])) {
                    $settings['adminbar_list_structure_title_length'] = $old_settings['adminbar_title_length'];
                }
                if (isset($old_settings['adminbar_date_format'])) {
                    $settings['adminbar_list_structure_date_format'] = $old_settings['adminbar_date_format'];
                }
                if (isset($old_settings['prevent_future_post'])) {
                    $settings['show_publish_post_button'] = $old_settings['prevent_future_post'];
                }
                if (isset($old_settings['calendar_default_schedule_time'])) {
                    $settings['calendar_schedule_time'] = $old_settings['calendar_default_schedule_time'];
                }
                if (isset($old_settings['is_republish_social_share'])) {
                    $settings['is_republish_social_share'] = $old_settings['is_republish_social_share'];
                }

                // email notify
                $wpscp_notify_author_is_sent_review = get_option('wpscp_notify_author_is_sent_review');
                if (!empty($wpscp_notify_author_is_sent_review)) {
                    $settings['notify_author_post_is_review'] = $wpscp_notify_author_is_sent_review;
                }
                $wpscp_notify_author_role_sent_review = get_option('wpscp_notify_author_role_sent_review');
                if (!empty($wpscp_notify_author_role_sent_review)) {
                    $settings['notify_author_post_review_by_role'] = $wpscp_notify_author_role_sent_review;
                }
                $wpscp_notify_author_username_sent_review = get_option('wpscp_notify_author_username_sent_review');
                if (!empty($wpscp_notify_author_username_sent_review)) {
                    $settings['notify_author_post_review_by_username'] = $wpscp_notify_author_username_sent_review;
                }
                $wpscp_notify_author_email_sent_review = get_option('wpscp_notify_author_email_sent_review');
                if (!empty($wpscp_notify_author_email_sent_review)) {
                    $settings['notify_author_post_review_by_email'] = $wpscp_notify_author_email_sent_review;
                }
                $wpscp_notify_author_post_is_rejected = get_option('wpscp_notify_author_post_is_rejected');
                if (!empty($wpscp_notify_author_post_is_rejected)) {
                    $settings['notify_author_post_is_rejected'] = $wpscp_notify_author_post_is_rejected;
                }
                $wpscp_notify_author_post_is_schedule = get_option('wpscp_notify_author_post_is_schedule');
                if (!empty($wpscp_notify_author_post_is_schedule)) {
                    $settings['notify_author_post_is_scheduled'] = $wpscp_notify_author_post_is_schedule;
                }
                $wpscp_notify_author_post_schedule_role = get_option('wpscp_notify_author_post_schedule_role');
                if (!empty($wpscp_notify_author_post_schedule_role)) {
                    $settings['notify_author_post_scheduled_by_role'] = $wpscp_notify_author_post_schedule_role;
                }
                $wpscp_notify_author_post_schedule_username = get_option('wpscp_notify_author_post_schedule_username');
                if (!empty($wpscp_notify_author_post_schedule_username)) {
                    $settings['notify_author_post_scheduled_by_username'] = $wpscp_notify_author_post_schedule_username;
                }
                $wpscp_notify_author_post_schedule_email = get_option('wpscp_notify_author_post_schedule_email');
                if (!empty($wpscp_notify_author_post_schedule_email)) {
                    $settings['notify_author_post_scheduled_by_email'] = $wpscp_notify_author_post_schedule_email;
                }
                $wpscp_notify_author_schedule_post_is_publish = get_option('wpscp_notify_author_schedule_post_is_publish');
                if (!empty($wpscp_notify_author_schedule_post_is_publish)) {
                    $settings['notify_author_post_scheduled_to_publish'] = $wpscp_notify_author_schedule_post_is_publish;
                }
                $wpscp_notify_author_post_is_publish = get_option('wpscp_notify_author_post_is_publish');
                if (!empty($wpscp_notify_author_post_is_publish)) {
                    $settings['notify_author_post_is_publish'] = $wpscp_notify_author_post_is_publish;
                }

                // social profile - facebook
                $facebook = get_option('wpscp_facebook_account');
                $facebook_status = get_option('wpsp_facebook_integration_status');
                $linkedin = get_option('wpscp_linkedin_account');
                $Linkedin_status = get_option('wpsp_linkedin_integration_status');
                $twitter = get_option('wpscp_twitter_account');
                $twitter_status = get_option('wpsp_twitter_integration_status');
                $pinterest = get_option('wpscp_pinterest_account');
                $pinterest_status = get_option('wpsp_pinterest_integration_status');
                if (!empty($facebook) && is_array($facebook)) {
                    $settings['facebook_profile_list'] = $facebook;
                    $settings['facebook_profile_status'] = ($facebook_status == 'on' ? true : false);
                }
                if (!empty($twitter) && is_array(($twitter))) {
                    $settings['twitter_profile_list'] = $twitter;
                    $settings['twitter_profile_status'] = ($twitter_status == 'on' ? true : false);
                }
                if (!empty($linkedin) && is_array($linkedin)) {
                    $settings['linkedin_profile_list'] = $linkedin;
                    $settings['linkedin_profile_status'] = ($Linkedin_status == 'on' ? true : false);
                }
                if (!empty($pinterest) && is_array($pinterest)) {
                    $settings['pinterest_profile_list'] = $pinterest;
                    $settings['pinterest_profile_status'] = ($pinterest_status == 'on' ? true : false);
                }
                // social template - facebook
                $wpscp_pro_fb_meta_head_support = get_option('wpscp_pro_fb_meta_head_support');
                if (!empty($wpscp_pro_fb_meta_head_support)) {
                    $settings['social_templates']['facebook'][0]['is_show_meta'] = $wpscp_pro_fb_meta_head_support;
                }
                $wpscp_pro_fb_content_type = get_option('wpscp_pro_fb_content_type');
                if (!empty($wpscp_pro_fb_content_type)) {
                    $settings['social_templates']['facebook'][1]['content_type'] = ($wpscp_pro_fb_content_type == 'statusandlink' ? 'statuswithlink' : $wpscp_pro_fb_content_type);
                }
                $wpscp_pro_fb_template_category_tags_support = get_option('wpscp_pro_fb_template_category_tags_support');
                if (!empty($wpscp_pro_fb_template_category_tags_support)) {
                    $settings['social_templates']['facebook'][2]['is_category_as_tags'] = $wpscp_pro_fb_template_category_tags_support;
                }
                $wpscp_pro_fb_content_source = get_option('wpscp_pro_fb_content_source');
                if (!empty($wpscp_pro_fb_content_source)) {
                    $settings['social_templates']['facebook'][3]['content_source'] = $wpscp_pro_fb_content_source;
                }
                $wpscp_pro_facebook_template_structure = get_option('wpscp_pro_facebook_template_structure');
                if (!empty($wpscp_pro_facebook_template_structure)) {
                    $settings['social_templates']['facebook'][4]['template_structure'] = $wpscp_pro_facebook_template_structure;
                }
                $wpscp_pro_facebook_status_limit = get_option('wpscp_pro_facebook_status_limit');
                if (!empty($wpscp_pro_facebook_status_limit)) {
                    $settings['social_templates']['facebook'][5]['status_limit'] = $wpscp_pro_facebook_status_limit;
                }
                // social template - twitter
                $wpscp_twitter_template_structure = get_option('wpscp_twitter_template_structure');
                if (!empty($wpscp_twitter_template_structure)) {
                    $settings['social_templates']['twitter'][0]['template_structure'] = $wpscp_twitter_template_structure;
                }
                $wpscp_twitter_template_category_tags_support = get_option('wpscp_twitter_template_category_tags_support');
                if (!empty($wpscp_twitter_template_category_tags_support)) {
                    $settings['social_templates']['twitter'][1]['is_category_as_tags'] = $wpscp_twitter_template_category_tags_support;
                }
                $wpscp_twitter_template_thumbnail = get_option('wpscp_twitter_template_thumbnail');
                if (!empty($wpscp_twitter_template_thumbnail)) {
                    $settings['social_templates']['twitter'][2]['is_show_post_thumbnail'] = $wpscp_twitter_template_thumbnail;
                }
                $wpscp_twitter_content_source = get_option('wpscp_twitter_content_source');
                if (!empty($wpscp_twitter_content_source)) {
                    $settings['social_templates']['twitter'][3]['content_source'] = $wpscp_twitter_content_source;
                }
                $wpscp_twitter_tweet_limit = get_option('wpscp_twitter_tweet_limit');
                if (!empty($wpscp_twitter_tweet_limit)) {
                    $settings['social_templates']['twitter'][4]['tweet_limit'] = $wpscp_twitter_tweet_limit;
                }

                // social template - linkedin
                $wpscp_pro_linkedin_content_type = get_option('wpscp_pro_linkedin_content_type');
                if (!empty($wpscp_pro_linkedin_content_type)) {
                    $settings['social_templates']['linkedin'][0]['content_type'] = $wpscp_pro_linkedin_content_type;
                }
                $wpscp_pro_liinkedin_template_category_tags_support = get_option('wpscp_pro_liinkedin_template_category_tags_support');
                if (!empty($wpscp_pro_liinkedin_template_category_tags_support)) {
                    $settings['social_templates']['linkedin'][1]['is_category_as_tags'] = $wpscp_pro_liinkedin_template_category_tags_support;
                }
                $wpscp_pro_linkedin_content_source = get_option('wpscp_pro_linkedin_content_source');
                if (!empty($wpscp_pro_linkedin_content_source)) {
                    $settings['social_templates']['linkedin'][2]['content_source'] = $wpscp_pro_linkedin_content_source;
                }
                $wpscp_pro_linkedin_template_structure = get_option('wpscp_pro_linkedin_template_structure');
                if (!empty($wpscp_pro_linkedin_template_structure)) {
                    $settings['social_templates']['linkedin'][3]['template_structure'] = $wpscp_pro_linkedin_template_structure;
                }
                $wpscp_pro_linkedin_status_limit = get_option('wpscp_pro_linkedin_status_limit');
                if (!empty($wpscp_pro_linkedin_status_limit)) {
                    $settings['social_templates']['linkedin'][4]['status_limit'] = $wpscp_pro_linkedin_status_limit;
                }
                // social template - pinterest
                $pinterest = get_option('wpscp_pro_pinterest_template_settings');
                if ($pinterest !== false && is_array($pinterest)) {
                    $settings['social_templates']['pinterest'][0]['is_set_image_link'] = $pinterest['add_image_link'];
                    $settings['social_templates']['pinterest'][1]['is_category_as_tags'] = $pinterest['template_category_tags_support'];
                    $settings['social_templates']['pinterest'][2]['content_source'] = $pinterest['content_source'];
                    $settings['social_templates']['pinterest'][3]['template_structure'] = $pinterest['template_structure'];
                    $settings['social_templates']['pinterest'][4]['note_limit'] = $pinterest['pin_note_limit'];
                }
                if (!empty($settings)) {
                    update_option(WPSP_SETTINGS_NAME, json_encode($settings));
                }
                update_option( 'wpsp_data_migration_3_to_4', true );
            }
        }
    }
    public static function allow_categories(){
        if (get_option('wpsp_data_migration_allow_categories') == false) {
            $settings = json_decode(get_option(WPSP_SETTINGS_NAME), true);
            if (!empty($settings['allow_categories'])) {
                foreach ($settings['allow_categories'] as $key => $value) {
                    if($value == 'all') continue;
                    $settings['allow_categories'][$key] = "category." . $value;
                }

                update_option(WPSP_SETTINGS_NAME, json_encode($settings));
                update_option( 'wpsp_data_migration_allow_categories', true );
            }
        }
    }
    public static function scheduled_post_social_share_meta_update(){
        global $wpdb;
        $post_types = \WPSP\Helper::get_settings('allow_post_types');
        if(is_array($post_types) && count($post_types) > 0){
            foreach($post_types as $post_type){
                $results = $wpdb->get_results( $wpdb->prepare( "SELECT ID, post_type FROM {$wpdb->prefix}posts WHERE post_type = %s AND post_status = 'future'", $post_type ), OBJECT );
                if(is_array($results) && count($results) > 0){
                    foreach($results as $result){
                        update_post_meta($result->ID, '_wpsp_is_facebook_share', 'on');
                        update_post_meta($result->ID, '_wpsp_is_twitter_share', 'on');
                        update_post_meta($result->ID, '_wpsp_is_linkedin_share', 'on');
                        update_post_meta($result->ID, '_wpsp_is_pinterest_share', 'on');
                    }
                }
            }
        }
    }

    public static function convert_to_12_hour_format($time24Hour) {
        list($hours, $minutes) = explode(":", $time24Hour);

        if ($hours >= 0 && $hours <= 11) {
            $meridiem = "AM";
            if ($hours == 0) {
                $hours = 12;
            }
        } else {
            $meridiem = "PM";
            if ($hours != 12) {
                $hours -= 12;
            }
        }

        return sprintf("%02d:%02d %s", $hours, $minutes, $meridiem);
    }

    public static function version_4_to_5(){
        if (get_option('wpsp_data_migration_4_to_5') == false) {
            $old_settings = get_option('wpsp_settings');
            $old_settings = json_decode($old_settings, true);
            $settings      = $old_settings ? $old_settings : [];
            // old version is installed
            if ($old_settings != false) {
                // migration code here.
                // manage_schedule auto_schedule
                if(isset($old_settings['manage_schedule'])){
                    $manage_schedule = $old_settings['manage_schedule'];
                    $old_active_schedule_system = !empty($old_settings['manage_schedule']['activeScheduleSystem']) ? $old_settings['manage_schedule']['activeScheduleSystem'] : '';
                    $settings['manage_schedule'] = [
                        'auto_schedule'   => [],
                        'manual_schedule' => [],
                        'activeScheduleSystem' => $old_active_schedule_system,
                    ];
                    if (isset($manage_schedule['auto_schedule'])) {
                        $auto_schedule = $old_settings['manage_schedule']['auto_schedule'];
                        foreach ($auto_schedule as $arr_value) {
                            if( is_array( $arr_value ) ) {
                                $key   = key($arr_value);
                                $value = current($arr_value);
                                if( 'is_active_status' == $key  ) {
                                    $settings['manage_schedule']['auto_schedule'][$key] = ( 'auto_schedule' == $old_active_schedule_system ) ? true : false;
                                }else{
                                    $settings['manage_schedule']['auto_schedule'][$key] = $value;
                                }
                            }
                        }
                    }
                    if (isset($manage_schedule['manual_schedule'])) {
                        $manual_schedule = $old_settings['manage_schedule']['manual_schedule'];
                        foreach ($manual_schedule as $arr_value) {
                            if( is_array( $arr_value ) ) {
                                $key   = key($arr_value);
                                $value = current($arr_value);
                                if( 'is_active_status' == $key  ) {
                                    $settings['manage_schedule']['manual_schedule'][$key] = ( 'manual_schedule' == $old_active_schedule_system ) ? true : false;
                                }else{
                                    $settings['manage_schedule']['manual_schedule'][$key] = $value;
                                }
                            }
                        }
                    }
                }

                // social_templates
                if(isset($old_settings['social_templates']) && is_array($old_settings['social_templates'])){
                    $social_templates = $old_settings['social_templates'];
                    $settings['social_templates'] = [
                        'facebook'  => [],
                        'twitter'   => [],
                        'linkedin'  => [],
                        'pinterest' => [],
                    ];
                    foreach ($social_templates as $social => $template_arr) {
                        foreach ($template_arr as $key => $arr_value) {
                           if( is_array( $arr_value ) ) {
                                $key   = key($arr_value);
                                $value = current($arr_value);
                                // if('pinterest' === $social && 'note_limit' === $key
                                // ||'twitter' === $social && 'tweet_limit' === $key
                                // ){
                                //     $key = 'status_limit';
                                // }
                                $settings['social_templates'][$social][$key] = $value;
                           }
                        }
                    }
                }
                if( isset( $old_settings['hide_on_elementor_editor'] ) ) {
                    $settings['show_on_elementor_editor'] = !$old_settings['hide_on_elementor_editor'];
                    unset( $settings['hide_on_elementor_editor'] );
                }
                if (!empty($settings)) {
                    update_option(WPSP_SETTINGS_NAME, json_encode($settings));
                }
                update_option( 'wpsp_data_migration_4_to_5', true );
            }
        }

    }

    /**
     * Drop social share events that were queued without the author asking for them.
     *
     * Up to 5.3.3, saving a caption always scheduled a `wpsp_custom_social_template`
     * event, even with scheduling switched off — so a post shared manually was
     * shared again a couple of hours later. Sites updating from an affected version
     * still carry those events, and each one would fire once more after the update.
     *
     * @return int Number of events removed.
     */
    public static function clear_unrequested_social_share_events() {
        $hook  = \WPSP\API\CustomSocialTemplates::SHARE_EVENT_HOOK;
        $crons = _get_cron_array();
        if ( ! is_array( $crons ) ) {
            return 0;
        }

        $removed = 0;
        foreach ( $crons as $timestamp => $hooks ) {
            if ( empty( $hooks[ $hook ] ) || ! is_array( $hooks[ $hook ] ) ) {
                continue;
            }
            foreach ( $hooks[ $hook ] as $event ) {
                $args    = isset( $event['args'] ) ? (array) $event['args'] : array();
                $post_id = isset( $args[0] ) ? (int) $args[0] : 0;
                if ( ! $post_id ) {
                    continue;
                }

                $scheduling = get_post_meta( $post_id, '_wpsp_social_scheduling', true );
                if ( is_array( $scheduling ) && ! empty( $scheduling['enabled'] ) ) {
                    // The author really did ask for this one — leave it alone.
                    continue;
                }

                wp_unschedule_event( $timestamp, $hook, $args );
                $removed++;
            }
        }

        return $removed;
    }

    /**
     * Repair the social shares an author really did schedule on an affected build.
     *
     * clear_unrequested_social_share_events() only drops the events nobody asked
     * for. The ones that were asked for are still wrong in two ways the new code
     * cannot reach on its own:
     *
     * - A future post's share took its time-of-day from whenever Save was clicked
     *   rather than from the publication time, so it sits at the wrong hour and can
     *   even fall before the post it links to.
     * - A share that already went out left no record of having done so, so the post
     *   reaching its own publication time shares it a second time.
     *
     * Both are repaired in place here. Anything ambiguous is re-armed rather than
     * silently suppressed: losing a share is as bad as duplicating one.
     *
     * @return array Report keyed by action, each value a list of post IDs.
     */
    public static function repair_legacy_social_share_schedules() {
        $hook   = \WPSP\API\CustomSocialTemplates::SHARE_EVENT_HOOK;
        $marker = \WPSP\API\CustomSocialTemplates::SHARED_MARKER_META;
        $api    = \WPSP\API\CustomSocialTemplates::get_instance();
        $now    = time();
        $report = array(
            'rescheduled'    => array(),
            'rolled_forward' => array(),
            'marked_shared'  => array(),
            'disarmed'       => array(),
            'orphans'        => array(),
        );

        // Pass 1 — events still queued. A future post's share has to be recomputed
        // from the publication anchor. A published post's was already measured from
        // "now" when it was saved, so it is only wrong when it has slipped into the
        // past, where it would fire on the very next cron pass and read as an
        // instant duplicate.
        $seen  = array();
        $crons = _get_cron_array();
        if ( is_array( $crons ) ) {
            foreach ( $crons as $timestamp => $hooks ) {
                if ( empty( $hooks[ $hook ] ) || ! is_array( $hooks[ $hook ] ) ) {
                    continue;
                }
                foreach ( $hooks[ $hook ] as $event ) {
                    $args    = isset( $event['args'] ) ? (array) $event['args'] : array();
                    $post_id = isset( $args[0] ) ? (int) $args[0] : 0;
                    if ( ! $post_id || isset( $seen[ $post_id ] ) ) {
                        continue;
                    }

                    $post = get_post( $post_id );
                    if ( ! $post ) {
                        // The post is gone; the event can never do anything useful.
                        wp_unschedule_event( $timestamp, $hook, $args );
                        $report['orphans'][] = $post_id;
                        continue;
                    }

                    $scheduling = get_post_meta( $post_id, '_wpsp_social_scheduling', true );
                    if ( ! is_array( $scheduling ) || empty( $scheduling['enabled'] ) ) {
                        // Not the author's doing — clear_unrequested_social_share_events() owns these.
                        continue;
                    }

                    $seen[ $post_id ] = true;

                    if ( 'future' === $post->post_status ) {
                        // Recomputes from post_date_gmt, clamps to the publication
                        // moment and replaces the queued event.
                        if ( $api->handle_scheduled_post_scheduling( $post_id, $scheduling, $post ) ) {
                            $report['rescheduled'][] = $post_id;
                        }
                        continue;
                    }

                    if ( $timestamp <= $now ) {
                        $rolled = $timestamp;
                        while ( $rolled <= $now ) {
                            $rolled += DAY_IN_SECONDS;
                        }
                        wp_unschedule_event( $timestamp, $hook, $args );
                        wp_schedule_single_event( $rolled, $hook, array( $post_id ) );
                        $scheduling['datetime'] = gmdate( 'Y-m-d H:i:s', $rolled );
                        update_post_meta( $post_id, '_wpsp_social_scheduling', $scheduling );
                        $report['rolled_forward'][] = $post_id;
                    }
                }
            }
        }

        // Pass 2 — a future post that is still armed but has no event left to fire
        // it. If its share time has passed, the share went out back when nothing
        // recorded that it had, and publication would repeat it. If the time has
        // not passed the event was simply lost, so put it back.
        $armed = get_posts(
            array(
                'post_type'        => 'any',
                'post_status'      => 'future',
                'posts_per_page'   => -1,
                'fields'           => 'ids',
                'meta_key'         => '_wpsp_social_scheduling', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
                'no_found_rows'    => true,
                'suppress_filters' => true,
            )
        );

        foreach ( $armed as $post_id ) {
            $post_id    = (int) $post_id;
            $scheduling = get_post_meta( $post_id, '_wpsp_social_scheduling', true );
            if ( ! is_array( $scheduling ) || empty( $scheduling['enabled'] ) ) {
                continue;
            }
            if ( wp_next_scheduled( $hook, array( $post_id ) ) ) {
                continue; // Pass 1 already re-armed it.
            }
            if ( get_post_meta( $post_id, $marker, true ) ) {
                continue; // Already recorded as shared.
            }

            $fired_at = ! empty( $scheduling['datetime'] )
                ? strtotime( $scheduling['datetime'] . ' UTC' )
                : 0;

            if ( ! $fired_at || $fired_at > $now ) {
                if ( $api->handle_scheduled_post_scheduling( $post_id, $scheduling ) ) {
                    $report['rescheduled'][] = $post_id;
                }
                continue;
            }

            update_post_meta( $post_id, $marker, $fired_at );
            $scheduling['enabled']  = false;
            $scheduling['status']   = 'template_only';
            $scheduling['datetime'] = null;
            update_post_meta( $post_id, '_wpsp_social_scheduling', $scheduling );
            $report['marked_shared'][] = $post_id;
        }

        // Pass 3 — meta left claiming a share is pending with no event behind it.
        // Harmless to the schedule but the editor shows a share that will never go
        // out, so bring the record back in line with reality.
        global $wpdb;
        $meta_post_ids = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT DISTINCT post_id FROM {$wpdb->postmeta} WHERE meta_key = %s",
                '_wpsp_social_scheduling'
            )
        );

        foreach ( (array) $meta_post_ids as $post_id ) {
            $post_id    = (int) $post_id;
            $scheduling = get_post_meta( $post_id, '_wpsp_social_scheduling', true );
            if ( ! is_array( $scheduling ) ) {
                continue;
            }
            if ( 'pending_publication' !== ( isset( $scheduling['status'] ) ? $scheduling['status'] : '' ) ) {
                continue;
            }
            if ( wp_next_scheduled( $hook, array( $post_id ) ) ) {
                continue;
            }

            $scheduling['enabled']  = false;
            $scheduling['status']   = 'template_only';
            $scheduling['datetime'] = null;
            update_post_meta( $post_id, '_wpsp_social_scheduling', $scheduling );
            $report['disarmed'][] = $post_id;
        }

        return $report;
    }
}