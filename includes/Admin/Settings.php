<?php

namespace WPSP\Admin;

class Settings
{
    protected $builder;
    protected $settings;
    protected $data;
    protected $slug;
    protected $option_name;

    public function __construct($pageSlug, $option_name)
    {
        $this->slug = $pageSlug;
        $this->option_name = $option_name;
        $this->load_dependency();
    }

    public function load_dependency()
    {
        $this->builder = new Settings\Builder();
        $this->set_settings_config_callback($this->builder);
        do_action('wpsp/admin/settings/set_settings_config', $this->builder);
        $this->settings = $this->builder->get_settings();
        new Settings\Assets($this->slug, $this->settings);
        $this->data  = new Settings\Data($this->option_name, $this->settings);
        add_action('wpsp_save_settings_default_value', array($this->data, 'save_option_value'));
    }
    public function set_settings_config_callback($Builder)
    {
        $get_users = \get_users(array('fields' => array('user_login', 'user_email')));
        $user_name = \wp_list_pluck($get_users, 'user_login', 'user_login');
        $user_email = \wp_list_pluck($get_users, 'user_email', 'user_email');
        // build settings
        $Builder::add_tab([
            'title' => __('General', 'wp-scheduled-posts'),
            'id' => 'wpsp_general',
        ]);
        $Builder::add_field('wpsp_general', [
            'id' => 'is_show_dashboard_widget',
            'type' => 'checkbox',
            'title' => __('Show Scheduled Posts in Dashboard Widget', 'wp-scheduled-posts'),
            'default' => true,
        ]);
        $Builder::add_field('wpsp_general', [
            'id' => 'is_show_sitewide_bar_posts',
            'type' => 'checkbox',
            'title' => __('Show Scheduled Posts in Sitewide Admin Bar', 'wp-scheduled-posts'),
            'default' => true,
        ]);
        $Builder::add_field('wpsp_general', [
            'id' => 'is_show_admin_bar_posts',
            'type' => 'checkbox',
            'title' => __('Show Scheduled Posts in Admin Bar', 'wp-scheduled-posts'),
            'default' => true,
        ]);

        $Builder::add_field('wpsp_general', [
            'id' => 'allow_post_types',
            'type' => 'select',
            'title' => __('Show Post Types:', 'wp-scheduled-posts'),
            'default' => ['post'],
            'multiple' => true,
            'options' => \WPSP\Helper::get_all_post_type(),
        ]);
        $Builder::add_field('wpsp_general', [
            'id' => 'allow_categories',
            'type' => 'select',
            'title' => __('Show Categories:', 'wp-scheduled-posts'),
            'default' => ['all'],
            'multiple' => true,
            'options' => \WPSP\Helper::_get_all_category(),
        ]);
        $Builder::add_field('wpsp_general', [
            'id' => 'allow_user_by_role',
            'type' => 'select',
            'title' => __('Allow users:', 'wp-scheduled-posts'),
            'default' => ['administrator'],
            'multiple' => true,
            'options' => \WPSP\Helper::get_all_roles(),
        ]);
        $Builder::add_field('wpsp_general', [
            'id' => 'calendar_schedule_time',
            'type' => 'time',
            'title' => __('Calendar Default Schedule Time:', 'wp-scheduled-posts'),
            'default' => '12:00 am',
        ]);
        // adminbar
        $Builder::add_field('wpsp_general', [
            'id' => 'adminbar_list_structure',
            'type' => 'collapsible',
            'title' => __('Custom item template for scheduled posts list in the admin bar:', 'wp-scheduled-posts'),
            'default' => false,
        ]);
        $Builder::add_field('wpsp_general', [
            'id' => 'adminbar_list_structure_template',
            'type' => 'text',
            'title' => __('Item template:', 'wp-scheduled-posts'),
            'default'   => '<strong>%TITLE%</strong> / %AUTHOR% / %DATE%',
            'condition' => [
                'adminbar_list_structure' => true,
            ],
        ]);
        $Builder::add_field('wpsp_general', [
            'id' => 'adminbar_list_structure_title_length',
            'type' => 'text',
            'title' => __('Title length:', 'wp-scheduled-posts'),
            'default'   => '45',
            'condition' => [
                'adminbar_list_structure' => true,
            ],
        ]);
        $Builder::add_field('wpsp_general', [
            'id' => 'adminbar_list_structure_date_format',
            'type' => 'text',
            'title' => __('Date format:', 'wp-scheduled-posts'),
            'default'   => 'M-d h:i:a',
            'desc'   => __('For item template use %TITLE% for the post title, %AUTHOR% for post author, and %DATE% for post scheduled date-time. You can use HTML tags with styles also.', 'wp-scheduled-posts'),
            'condition' => [
                'adminbar_list_structure' => true,
            ]
        ]);

        // publish post button
        $Builder::add_field('wpsp_general', [
            'id' => 'show_publish_post_button',
            'type' => 'checkbox',
            'title' => __('Show Publish Post Immediately Button', 'wp-scheduled-posts'),
            'default' => true,
        ]);
	    $Builder::add_field('wpsp_general', [
		    'id' => 'hide_on_elementor_editor',
		    'type' => 'checkbox',
		    'title' => __('Disable Scheduled Posts in Elementor', 'wp-scheduled-posts'),
		    'default' => false,
	    ]);

        // second tab
        $Builder::add_tab([
            'title' => __('Email Notify', 'wp-scheduled-posts'),
            'id' => 'wpsp_email_notify',
        ]);
        // info
        $Builder::add_field('wpsp_email_notify', [
            'id' => 'notify_doc',
            'type' => 'rawhtml',
            'content'   => esc_html__('To configure the Email Notify Settings, check out this', 'wp-scheduled-posts') . ' <a class="docs" href="https://wpdeveloper.com/docs/email-notification-wordpress" target="_blank">' . esc_html__('Doc', 'wp-scheduled-posts') . '</a>'
        ]);
        // under review
        $Builder::add_field('wpsp_email_notify', [
            'id' => 'notify_author_post_is_review',
            'type' => 'checkbox',
            'title' => __('Notify User when a post is "Under Review"', 'wp-scheduled-posts'),
            'default' => false,
        ]);
        $Builder::add_field('wpsp_email_notify', [
            'id' => 'notify_author_post_review_by_role',
            'type' => 'select',
            'title' => __('Role', 'wp-scheduled-posts'),
            'options' => \WPSP\Helper::get_all_roles(),
            'default'   => [],
            'multiple' => true,
            'condition' => [
                'notify_author_post_is_review' => true,
            ],
        ]);
        $Builder::add_field('wpsp_email_notify', [
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
        $Builder::add_field('wpsp_email_notify', [
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
        $Builder::add_field('wpsp_email_notify', [
            'id' => 'notify_author_post_is_rejected',
            'type' => 'checkbox',
            'title' => __('Notify Author when a post is "Rejected"', 'wp-scheduled-posts'),
            'default' => false,
        ]);
        // scheduled
        $Builder::add_field('wpsp_email_notify', [
            'id' => 'notify_author_post_is_scheduled',
            'type' => 'checkbox',
            'title' => __('Notify User when a post is "Scheduled"', 'wp-scheduled-posts'),
            'default' => false,
        ]);
        $Builder::add_field('wpsp_email_notify', [
            'id' => 'notify_author_post_scheduled_by_role',
            'type' => 'select',
            'title' => __('Role', 'wp-scheduled-posts'),
            'options' => \WPSP\Helper::get_all_roles(),
            'default'   => [],
            'multiple' => true,
            'condition' => [
                'notify_author_post_is_scheduled' => true,
            ],
        ]);
        $Builder::add_field('wpsp_email_notify', [
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
        $Builder::add_field('wpsp_email_notify', [
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
        $Builder::add_field('wpsp_email_notify', [
            'id' => 'notify_author_post_scheduled_to_publish',
            'type' => 'checkbox',
            'title' => __('Notify Author when a Scheduled Post is "Published"', 'wp-scheduled-posts'),
            'default' => false,
        ]);
        // publish
        $Builder::add_field('wpsp_email_notify', [
            'id' => 'notify_author_post_is_publish',
            'type' => 'checkbox',
            'title' => __('Notify Author when a post is "Published"', 'wp-scheduled-posts'),
            'default' => false,
        ]);

        // social profile
        $Builder::add_tab([
            'title' => __('Social Profile', 'wp-scheduled-posts'),
            'id' => 'social_profile',
        ]);
        $Builder::add_sub_tab('social_profile', [
            'id' => 'facebook',
            'title' => __('Facebook', 'wp-scheduled-posts'),
        ]);
        $Builder::add_sub_field('social_profile', 'facebook', [
            'id' => 'facebook_profile',
            'type' => 'socialprofile',
            'title' => __('Facebook Profile', 'wp-scheduled-posts'),
            'app'   => [
                'platform'  => 'facebook',
                'type'      => 'custom'
            ]
        ]);
        $Builder::add_sub_tab('social_profile', [
            'id' => 'twitter',
            'title' => __('Twitter', 'wp-scheduled-posts'),
        ]);
        $Builder::add_sub_field('social_profile', 'twitter', [
            'id' => 'twitter_profile',
            'type' => 'socialprofile',
            'title' => __('Twitter Profile', 'wp-scheduled-posts'),
            'app'   => [
                'platform'  => 'twitter',
                'type'      => 'custom'
            ]
        ]);
        $Builder::add_sub_tab('social_profile', [
            'id' => 'linkedin',
            'title' => __('LinkedIn', 'wp-scheduled-posts'),
        ]);
        $Builder::add_sub_field('social_profile', 'linkedin', [
            'id' => 'linkedin_profile',
            'type' => 'socialprofile',
            'title' => __('LinkedIn Profile', 'wp-scheduled-posts'),
            'app'   => [
                'platform'  => 'linkedin',
                'type'      => 'custom'
            ]
        ]);
        $Builder::add_sub_tab('social_profile', [
            'id' => 'pinterest',
            'title' => __('Pinterest', 'wp-scheduled-posts'),
        ]);
        $Builder::add_sub_field('social_profile', 'pinterest', [
            'id' => 'pinterest_profile',
            'type' => 'socialprofile',
            'title' => __('Pinterest Profile', 'wp-scheduled-posts'),
            'app'   => [
                'platform'  => 'pinterest',
                'type'      => 'custom'
            ]
        ]);
        // social template
        $Builder::add_tab([
            'title' => __('Social Templates', 'wp-scheduled-posts'),
            'id' => 'social_templates',
        ]);
        // facebook
        $Builder::add_group('social_templates', [
            'id' => 'facebook',
            'title' => __(' Facebook Status Settings', 'wp-scheduled-posts'),
            'subtitle'  => 'To configure the Facebook Status Settings, check out this<a className="docs" href="https://wpdeveloper.com/docs/share-scheduled-posts-facebook/" target="_blank">Doc.</a>'
        ]);
        $Builder::add_group_field('social_templates', 'facebook', [
            'id' => 'is_show_meta',
            'type' => 'checkbox',
            'title' => __('Facebook Meta Data', 'wp-scheduled-posts'),
            'desc'  => __('Add Open Graph metadata to your site head section and other social networks use this data when your pages are shared.', 'wp-scheduled-posts'),
            'default' => true,
        ]);
        $Builder::add_group_field('social_templates', 'facebook', [
            'id' => 'content_type',
            'type' => 'radio',
            'title' => __('Content Type', 'wp-scheduled-posts'),
            'options' => array(
                'link' => __('Link', 'wp-scheduled-posts'),
                'status' => __('Status', 'wp-scheduled-posts'),
                'statuswithlink' => __('Status + Link', 'wp-scheduled-posts'),
            ),
            'default' => 'link',
        ]);
        $Builder::add_group_field('social_templates', 'facebook', [
            'id' => 'is_category_as_tags',
            'type' => 'checkbox',
            'title' => __('Add Category as a tags', 'wp-scheduled-posts'),
            'default' => false,
        ]);
        $Builder::add_group_field('social_templates', 'facebook', [
            'id' => 'content_source',
            'type' => 'radio',
            'title' => __('Content Source', 'wp-scheduled-posts'),
            'options' => array(
                'excerpt' => __('Excerpt', 'wp-scheduled-posts'),
                'content' => __('Content', 'wp-scheduled-posts')
            ),
            'default' => 'excerpt',
        ]);
        $Builder::add_group_field('social_templates', 'facebook', [
            'id' => 'template_structure',
            'type' => 'text',
            'title' => __('Status Template Settings', 'wp-scheduled-posts'),
            'desc'  => __('Default Structure: {title}{content}{url}{tags}', 'wp-scheduled-posts'),
            'default' => '{title}{content}{url}{tags}',
        ]);
        $Builder::add_group_field('social_templates', 'facebook', [
            'id' => 'status_limit',
            'type' => 'number',
            'title' => __('Status Limit', 'wp-scheduled-posts'),
            'desc'  => __('Maximum Limit: 63206 character', 'wp-scheduled-posts'),
            'default' => '63206',
            'max' => '63206',
        ]);
        // twitter
        $Builder::add_group('social_templates', [
            'id' => 'twitter',
            'title' => __('Twitter Tweet Settings', 'wp-scheduled-posts'),
            'subtitle'  => 'To configure the Twitter Tweet Settings, check out this<a className="docs" href="https://wpdeveloper.com/docs/automatically-tweet-wordpress-posts/" target="_blank">Doc.</a>'
        ]);
        $Builder::add_group_field('social_templates', 'twitter', [
            'id' => 'template_structure',
            'type' => 'text',
            'title' => __('Tweet Template Settings', 'wp-scheduled-posts'),
            'desc'  => __('Default Structure: {title}{content}{url}{tags}', 'wp-scheduled-posts'),
            'default' => '{title}{content}{url}{tags}',
        ]);
        $Builder::add_group_field('social_templates', 'twitter', [
            'id' => 'is_category_as_tags',
            'type' => 'checkbox',
            'title' => __('Add Category as a tags', 'wp-scheduled-posts'),
            'default' => false,
        ]);
        $Builder::add_group_field('social_templates', 'twitter', [
            'id' => 'is_show_post_thumbnail',
            'type' => 'checkbox',
            'title' => __('Show Post Thumbnail', 'wp-scheduled-posts'),
            'default' => false,
        ]);
        $Builder::add_group_field('social_templates', 'twitter', [
            'id' => 'content_source',
            'type' => 'radio',
            'title' => __('Content Source', 'wp-scheduled-posts'),
            'options' => array(
                'excerpt' => __('Excerpt', 'wp-scheduled-posts'),
                'content' => __('Content', 'wp-scheduled-posts')
            ),
            'default' => 'excerpt',
        ]);
        $Builder::add_group_field('social_templates', 'twitter', [
            'id' => 'tweet_limit',
            'type' => 'number',
            'title' => __('Tweet Limit', 'wp-scheduled-posts'),
            'desc'  => __('Maximum Limit: 280 character', 'wp-scheduled-posts'),
            'default' => '280',
            'max' => '280',
        ]);
        // linkedin
        $Builder::add_group('social_templates', [
            'id' => 'linkedin',
            'title' => __('LinkedIn Status Settings', 'wp-scheduled-posts'),
            'subtitle'  => 'To configure the LinkedIn Status Settings, check out this<a className="docs" href="https://wpdeveloper.com/docs/share-wordpress-posts-on-linkedin/" target="_blank">Doc.</a>'
        ]);
        $Builder::add_group_field('social_templates', 'linkedin', [
            'id' => 'content_type',
            'type' => 'radio',
            'title' => __('Content Type', 'wp-scheduled-posts'),
            'options' => array(
                'link'   => __('Link', 'wp-scheduled-posts'),
                'status' => __('Status', 'wp-scheduled-posts'),
                'media'  => __('Media', 'wp-scheduled-posts'),
            ),
            'default' => 'link',
        ]);
        $Builder::add_group_field('social_templates', 'linkedin', [
            'id' => 'is_category_as_tags',
            'type' => 'checkbox',
            'title' => __('Add Category as a tags', 'wp-scheduled-posts'),
            'default' => false,
        ]);
        $Builder::add_group_field('social_templates', 'linkedin', [
            'id' => 'content_source',
            'type' => 'radio',
            'title' => __('Content Source', 'wp-scheduled-posts'),
            'options' => array(
                'excerpt' => __('Excerpt', 'wp-scheduled-posts'),
                'content' => __('Content', 'wp-scheduled-posts')
            ),
            'default' => 'excerpt',
        ]);
        $Builder::add_group_field('social_templates', 'linkedin', [
            'id' => 'template_structure',
            'type' => 'text',
            'title' => __('Status Template Settings', 'wp-scheduled-posts'),
            'desc'  => __('Default Structure: {title}{content}{url}{tags}', 'wp-scheduled-posts'),
            'default' => '{title}{content}{tags}',
        ]);
        $Builder::add_group_field('social_templates', 'linkedin', [
            'id' => 'status_limit',
            'type' => 'number',
            'title' => __('Status Limit', 'wp-scheduled-posts'),
            'desc'  => __('Maximum Limit: 1300 character', 'wp-scheduled-posts'),
            'default' => '1300',
            'max' => '1300',
        ]);
        // pinterest
        $Builder::add_group('social_templates', [
            'id' => 'pinterest',
            'title' => __(' Pinterest Pin Settings', 'wp-scheduled-posts'),
            'subtitle'  => 'To configure the Pinterest Pin Settings, check out this<a className="docs" href="https://wpdeveloper.com/docs/wordpress-posts-on-pinterest/" target="_blank">Doc.</a>'
        ]);
        $Builder::add_group_field('social_templates', 'pinterest', [
            'id' => 'is_set_image_link',
            'type' => 'checkbox',
            'title' => __('Add Image Link', 'wp-scheduled-posts'),
            'default' => true,
        ]);
        $Builder::add_group_field('social_templates', 'pinterest', [
            'id' => 'is_category_as_tags',
            'type' => 'checkbox',
            'title' => __('Add Category as a tags', 'wp-scheduled-posts'),
            'default' => false,
        ]);
        $Builder::add_group_field('social_templates', 'pinterest', [
            'id' => 'content_source',
            'type' => 'radio',
            'title' => __('Content Source', 'wp-scheduled-posts'),
            'options' => array(
                'excerpt' => __('Excerpt', 'wp-scheduled-posts'),
                'content' => __('Content', 'wp-scheduled-posts')
            ),
            'default' => 'excerpt',
        ]);
        $Builder::add_group_field('social_templates', 'pinterest', [
            'id' => 'template_structure',
            'type' => 'text',
            'title' => __('Status Template Settings', 'wp-scheduled-posts'),
            'desc'  => __('Default Structure: {title}{content}{url}{tags}', 'wp-scheduled-posts'),
            'default' => '{title}',
        ]);
        $Builder::add_group_field('social_templates', 'pinterest', [
            'id' => 'note_limit',
            'type' => 'number',
            'title' => __('Pin Note Limit', 'wp-scheduled-posts'),
            'desc'  => __('Maximum Limit: 500 character', 'wp-scheduled-posts'),
            'default' => '500',
            'max' => '500',
        ]);
    }
}
