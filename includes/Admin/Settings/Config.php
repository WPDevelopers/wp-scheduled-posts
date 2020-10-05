<?php

namespace WPSP\Admin\Settings;

class Config
{


    /**
     * Main Option Name
     */
    private static $settings_name = 'wpsp_settings';

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


        // second tab
        Builder::add_tab([
            'title' => __('Email Notify', 'wp-scheduled-posts'),
            'id' => 'wpsp_email_notify',
        ]);
        Builder::add_field('wpsp_email_notify', [
            'id' => 'is_send_email_author_rejected_posts',
            'type' => 'checkbox',
            'title' => __('Notify Author when a post is "Rejected"', 'wp-scheduled-posts'),
            'default' => true,
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
