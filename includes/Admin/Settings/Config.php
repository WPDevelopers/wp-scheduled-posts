<?php

namespace WPSP\Admin\Settings;

class Config
{


    /**
     * Main Option Name
     */
    private static $settings_name = WPSP_SETTINGS_NAME;

    /**
     * settings fields array
     *
     * @since 1.0.0
     */
    private static $setting_array = array();




    public static function build_settings()
    {
        // build settings
        Builder::add_tab([
            'title' => __('General', 'wp-scheduled-posts'),
            'id' => 'wpsp_general',
        ]);
        Builder::add_field('wpsp_general', [
            'id' => 'is_show_dashboard_widget',
            'type' => 'checkbox',
            'title' => __('Show Scheduled Posts in Dashboard Widget', 'wp-scheduled-posts'),
            'default' => true,
        ]);
        Builder::add_field('wpsp_general', [
            'id' => 'is_show_sitewide_bar_posts',
            'type' => 'checkbox',
            'title' => __('Show Scheduled Posts in Sitewide Admin Bar', 'wp-scheduled-posts'),
            'default' => true,
        ]);
        Builder::add_field('wpsp_general', [
            'id' => 'is_show_admin_bar_posts',
            'type' => 'checkbox',
            'title' => __('Show Scheduled Posts in Admin Bar', 'wp-scheduled-posts'),
            'default' => true,
        ]);
        Builder::add_field('wpsp_general', [
            'id' => 'allow_post_types',
            'type' => 'select',
            'title' => __('Show Post Types:', 'wp-scheduled-posts'),
            'default' => 'post',
            'options' => \WPSP\Helper::get_all_post_type(),
        ]);
        Builder::add_field('wpsp_general', [
            'id' => 'allow_categories',
            'type' => 'select',
            'title' => __('Show Categories:', 'wp-scheduled-posts'),
            'default' => 'all',
            'options' => \WPSP\Helper::get_all_category(),
        ]);
        Builder::add_field('wpsp_general', [
            'id' => 'allow_user_by_role',
            'type' => 'select',
            'title' => __('Allow users:', 'wp-scheduled-posts'),
            'default' => 'administrator',
            'options' => \WPSP\Helper::get_all_roles(),
        ]);
        Builder::add_field('wpsp_general', [
            'id' => 'calendar_schedule_time',
            'type' => 'text',
            'title' => __('Calendar Default Schedule Time:', 'wp-scheduled-posts'),
            'default' => '12:00 am',
        ]);
        Builder::add_field('wpsp_general', [
            'id' => 'show_publish_post_button',
            'type' => 'checkbox',
            'title' => __('Show Publish Post Immediately Button', 'wp-scheduled-posts'),
            'default' => true,
        ]);


        // second tab
        Builder::add_tab([
            'title' => __('Email Notify', 'wp-scheduled-posts'),
            'id' => 'wpsp_email_notify',
        ]);
        Builder::add_field('wpsp_email_notify', [
            'id' => 'notify_author_post_is_rejected',
            'type' => 'checkbox',
            'title' => __('Notify Author when a post is "Rejected"', 'wp-scheduled-posts'),
            'default' => false,
        ]);
        Builder::add_field('wpsp_email_notify', [
            'id' => 'notify_author_post_scheduled_to_publish',
            'type' => 'checkbox',
            'title' => __('Notify Author when a Scheduled Post is "Published"', 'wp-scheduled-posts'),
            'default' => false,
        ]);
        Builder::add_field('wpsp_email_notify', [
            'id' => 'notify_author_post_is_publish',
            'type' => 'checkbox',
            'title' => __('Notify Author when a post is "Published"', 'wp-scheduled-posts'),
            'default' => false,
        ]);
    }

    /**
     * Set default settings data in database
     *
     * @return null save option in database
     *
     * @since 1.0.0
     */
    public static function set_default_settings_fields_data()
    {
        self::$setting_array =  Builder::load();
        $list_column = array_column(self::$setting_array, 'fields');
        $list_array = array_merge(...$list_column);
        $new_value = \json_encode(wp_list_pluck($list_array, 'default', 'id'));

        if (get_option(self::$settings_name) !== false) {
            update_option(self::$settings_name, $new_value);
        } else {
            add_option(self::$settings_name, $new_value);
        }
    }
}
