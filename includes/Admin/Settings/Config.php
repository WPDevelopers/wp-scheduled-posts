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
        $get_users = get_users(array('fields' => array('user_login', 'user_email')));
        $user_name = wp_list_pluck($get_users, 'user_login', 'user_login');
        $user_email = wp_list_pluck($get_users, 'user_email', 'user_email');
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
            'default' => ['post'],
            'multiple' => true,
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
        // adminbar 
        Builder::add_field('wpsp_general', [
            'id' => 'adminbar_sp_list_structure',
            'type' => 'collapsible',
            'title' => __('Custom item template for scheduled posts list in adminbar:', 'wp-scheduled-posts'),
            'default' => false,
        ]);
        Builder::add_field('wpsp_general', [
            'id' => 'adminbar_sp_list_structure_template',
            'type' => 'text',
            'title' => __('Item template:', 'wp-scheduled-posts'),
            'default'   => '<strong>%TITLE%</strong> / %AUTHOR% / %DATE%',
            'condition' => [
                'adminbar_sp_list_structure' => true,
            ],
        ]);
        Builder::add_field('wpsp_general', [
            'id' => 'adminbar_sp_list_structure_title_length',
            'type' => 'text',
            'title' => __('Title length:', 'wp-scheduled-posts'),
            'default'   => '45',
            'condition' => [
                'adminbar_sp_list_structure' => true,
            ],
        ]);
        Builder::add_field('wpsp_general', [
            'id' => 'adminbar_sp_list_structure_date_format',
            'type' => 'text',
            'title' => __('Date format:', 'wp-scheduled-posts'),
            'default'   => 'M-d h:i:a',
            'desc'   => __('For item template use %TITLE% for post title, %AUTHOR% for post author and %DATE% for post scheduled date-time. You can use HTML tags with styles also', 'wp-scheduled-posts'),
            'condition' => [
                'adminbar_sp_list_structure' => true,
            ]
        ]);

        // publish post button
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
        // under review
        Builder::add_field('wpsp_email_notify', [
            'id' => 'notify_author_post_is_review',
            'type' => 'checkbox',
            'title' => __('Notify User when a post is "Under Review"', 'wp-scheduled-posts'),
            'default' => false,
        ]);
        Builder::add_field('wpsp_email_notify', [
            'id' => 'notify_author_post_review_by_role',
            'type' => 'select',
            'title' => __('Role', 'wp-scheduled-posts'),
            'options' => \WPSP\Helper::get_all_roles(),
            'default'   => '',
            'condition' => [
                'notify_author_post_is_review' => true,
            ],
        ]);
        Builder::add_field('wpsp_email_notify', [
            'id' => 'notify_author_post_review_by_username',
            'type' => 'creatableselect',
            'title' => __('Username', 'wp-scheduled-posts'),
            'options' => $user_name,
            'default'   => [],
            'multiple' => true,
            'condition' => [
                'notify_author_post_is_review' => true,
            ],
        ]);
        Builder::add_field('wpsp_email_notify', [
            'id' => 'notify_author_post_review_by_email',
            'type' => 'creatableselect',
            'title' => __('Email', 'wp-scheduled-posts'),
            'options' => $user_email,
            'default'   => [],
            'multiple' => true,
            'condition' => [
                'notify_author_post_is_review' => true,
            ],
        ]);

        // rejected
        Builder::add_field('wpsp_email_notify', [
            'id' => 'notify_author_post_is_rejected',
            'type' => 'checkbox',
            'title' => __('Notify Author when a post is "Rejected"', 'wp-scheduled-posts'),
            'default' => false,
        ]);
        // scheduled
        Builder::add_field('wpsp_email_notify', [
            'id' => 'notify_author_post_is_scheduled',
            'type' => 'checkbox',
            'title' => __('Notify User when a post is "Scheduled"', 'wp-scheduled-posts'),
            'default' => false,
        ]);
        Builder::add_field('wpsp_email_notify', [
            'id' => 'notify_author_post_scheduled_by_role',
            'type' => 'select',
            'title' => __('Role', 'wp-scheduled-posts'),
            'options' => \WPSP\Helper::get_all_roles(),
            'default'   => '',
            'condition' => [
                'notify_author_post_is_scheduled' => true,
            ],
        ]);
        Builder::add_field('wpsp_email_notify', [
            'id' => 'notify_author_post_scheduled_by_username',
            'type' => 'creatableselect',
            'title' => __('Username', 'wp-scheduled-posts'),
            'options' => $user_name,
            'default'   => [],
            'multiple' => true,
            'condition' => [
                'notify_author_post_is_scheduled' => true,
            ],
        ]);
        Builder::add_field('wpsp_email_notify', [
            'id' => 'notify_author_post_scheduled_by_email',
            'type' => 'creatableselect',
            'title' => __('Email', 'wp-scheduled-posts'),
            'options' => $user_email,
            'default'   => [],
            'multiple' => true,
            'condition' => [
                'notify_author_post_is_scheduled' => true,
            ],
        ]);

        // schedule to publish
        Builder::add_field('wpsp_email_notify', [
            'id' => 'notify_author_post_scheduled_to_publish',
            'type' => 'checkbox',
            'title' => __('Notify Author when a Scheduled Post is "Published"', 'wp-scheduled-posts'),
            'default' => false,
        ]);
        // publish
        Builder::add_field('wpsp_email_notify', [
            'id' => 'notify_author_post_is_publish',
            'type' => 'checkbox',
            'title' => __('Notify Author when a post is "Published"', 'wp-scheduled-posts'),
            'default' => false,
        ]);

        // social profile
        Builder::add_tab([
            'title' => __('Social Profile', 'wp-scheduled-posts'),
            'id' => 'wpsp_social_profile',
        ]);
        Builder::add_sub_tab('wpsp_social_profile', [
            'id' => 'facebook',
            'title' => __('Facebook', 'wp-scheduled-posts'),
        ]);
        Builder::add_sub_field('wpsp_social_profile', 'facebook', [
            'id' => 'facebook_profile',
            'type' => 'socialprofile',
            'title' => __('Facebook Profile', 'wp-scheduled-posts'),
            'subtitle' => __('You can enable/disable facebook social share. For details on facebook configuration, check out this Doc', 'wp-scheduled-posts'),
        ]);
        Builder::add_sub_tab('wpsp_social_profile', [
            'id' => 'twitter',
            'title' => __('Twitter', 'wp-scheduled-posts'),
        ]);
        Builder::add_sub_tab('wpsp_social_profile', [
            'id' => 'linkedin',
            'title' => __('Linekdin', 'wp-scheduled-posts'),
        ]);
        Builder::add_sub_tab('wpsp_social_profile', [
            'id' => 'pinterest',
            'title' => __('Pinterest', 'wp-scheduled-posts'),
        ]);

        // Builder::add_field('wpsp_social_profile', [
        //     'id' => 'social_profiles',
        //     'type' => 'social',
        //     'title' => __('Social Profile', 'wp-scheduled-posts'),
        // ]);
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
