<?php

namespace WPSP\Admin;

use WPSP\API\Settings as APISettings;

class Settings {
    protected $builder;
    protected $settings;
    protected $data;
    protected $slug;
    protected $option_name;

    public function __construct($pageSlug, $option_name) {
        $this->slug = $pageSlug;
        $this->option_name = $option_name;
        $this->load_dependency();
    }

    public function load_dependency() {
        // $this->builder = new Settings\Builder();
        // do_action('wpsp/admin/settings/set_settings_config', $this->builder);
        // $this->settings = $this->builder->get_settings();
        new Settings\Assets($this->slug, $this->get_settings_array());
        // $this->data  = new Settings\Data($this->option_name, $this->settings);
        // add_action('wpsp_save_settings_default_value', array($this->data, 'save_option_value'));
    }

    /**
     * Convert `fields` associative array to numeric array recursively.
     * @todo improve implementation.
     *
     * @param array $arr
     * @return array
     */
    public function normalize($arr) {

        if (!empty($arr['fields'])) {
            $arr['fields'] = array_values($arr['fields']);
        }

        if (!empty($arr['options'])) {
            $arr['options'] = array_values($arr['options']);
        }

        if (!empty($arr['tabs'])) {
            $arr['tabs'] = array_values($arr['tabs']);
        }

        if (is_array($arr)) {
            foreach ($arr as $key => $value) {
                if (is_array($value)) {
                    $arr[$key] = $this->normalize($value);
                }
            }
        }
        return $arr;
    }

    public function normalize_options($fields, $key = '', $value = [], $return = []) {

        foreach ($fields as $val => $label) {
            if (empty($return[$val]) && !is_array($label)) {
                $return[$val] = [
                    'value' => $val,
                    'label' => $label,
                ];
            }
            elseif (empty($return[$val])){
                $return[$val] = $label;
            }
            if(!empty($key)){
                $return[$val] = Rules::includes($key, $value, false, $return[$val]);
            }
        }

        return $return;
    }

    public function get_settings_array() {
        $wpsp_option = get_option($this->option_name);
        $wpsp_option = json_decode($wpsp_option);

        return $this->normalize([
            'id'              => 'tab-sidebar-layout',
            'name'            => 'tab_sidebar_layout',
            'label'           => __('Layout', 'wp-scheduled-posts'),
            'classes'         => 'tab-layout',
            'type'            => "tab",
            'active'          => "layout_general",
            'completionTrack' => true,
            'sidebar'         => false,
            'title'           => false,
            'submit'          => [
                'show' => true
            ],
            'step'            => [
                'show' => false
            ],
            'is_pro_active'   => (defined('WPSP_PRO_VERSION') ? WPSP_PRO_VERSION : ''),
            'savedValues'     => $wpsp_option,
            'values'          => $wpsp_option,
            'fields'          => [
                'layout_general' => [
                    'id'       => 'layout_general',
                    'name'     => 'layout_general',
                    'label'    => __('General', 'wp-scheduled-posts'),
                    'priority' => 5,
                    'fields'   => apply_filters('wpsp_general_fields',[
                        'pro_features_section'  => [
                            'name'     => 'pro_features_section',
                            'type'     => 'section',
                            'label'    => null,
                            'priority' => 2,
                            'fields'   => [
                                'pro_features'      => [
                                    'name'          => 'pro_features',
                                    'type'          => 'features',
                                    'priority'      => 2,
                                    'is_pro'        => true,
                                    'content'  => [
                                        'heading'       => 'SchedulePress - Pro Features',
                                        'button_text'   => __('View All Features','wp-scheduled-posts'),
                                        'button_link'   => 'https://google.com',
                                        'options'   => [
                                            [
                                                'icon'  => 'wpsp-',
                                                'title' => __('Auto Scheduler','wp-scheduled-posts'),
                                                'link'  => 'https://wpdeveloper.com',
                                            ],
                                            [
                                                'icon'  => 'icon',
                                                'title' => __('Manual Scheduler','wp-scheduled-posts'),
                                                'link'  => 'https://wpdeveloper.com',
                                            ],
                                            [
                                                'icon'  => 'icon',
                                                'title' => __('Missed Scheduler Handler','wp-scheduled-posts'),
                                                'link'  => 'https://wpdeveloper.com',
                                            ],
                                            [
                                                'icon'  => 'icon',
                                                'title' => __('Premium Support','wp-scheduled-posts'),
                                                'link'  => 'https://wpdeveloper.com',
                                            ],
                                        ],
                                    ],
                                    'label'    => __('Show Scheduled Posts in Dashboard Widget', 'wp-scheduled-posts'),
                                ],
                            ]
                        ],

                        'general_settings'     => [
                            'name'     => 'general_settings',
                            'type'     => 'section',
                            'label'    => __( 'General Settings', 'wp-scheduled-posts' ),
                            'priority' => 6,
                            'fields'    => [

                                'is_show_dashboard_widget'       => [
                                    'name'     => 'is_show_dashboard_widget',
                                    'type'     => 'toggle',
                                    'label'    => __('Show Scheduled Posts in Dashboard Widget', 'wp-scheduled-posts'),
                                    'default'  => 1,
                                    'priority' => 3,
                                ],
                                'is_show_sitewide_bar_posts'  => [
                                    'name'     => 'is_show_sitewide_bar_posts',
                                    'type'     => 'toggle',
                                    'label'    => __('Show Scheduled Posts in Sitewide Admin Bar', 'wp-scheduled-posts'),
                                    'priority' => 5,
                                ],
                                'is_show_admin_bar_posts'       => [
                                    'name'     => 'is_show_admin_bar_posts',
                                    'type'     => 'toggle',
                                    'label'    => __('Show Scheduled Posts in Admin Bar', 'wp-scheduled-posts'),
                                    'default'  => 1,
                                    'priority' => 10,
                                ],
                                'allow_post_types'  => [
                                    'name'     => 'allow_post_types',
                                    'label'    => __('Show Post Types:', 'notificationx'),
                                    'type'     => 'checkbox-select',
                                    'multiple' => true,
                                    'priority' => 7,
                                    'option'  => $this->normalize_options(\WPSP\Helper::get_all_post_type()),
                                ],
                                'allow_categories' => [
                                    'name'     => 'allow_categories',
                                    'label'    => __('Show Categories:', 'notificationx'),
                                    'type'     => 'checkbox-select',
                                    'multiple' => true,
                                    'priority' => 8,
                                    'option'  => $this->normalize_options(\WPSP\Helper::_get_all_category()),
                                ],
                                'allow_user_by_role' => [
                                    'name'     => 'allow_user_by_role',
                                    'label'    => __('Allow users:', 'notificationx'),
                                    'type'     => 'checkbox-select',
                                    'multiple' => true,
                                    'priority' => 9,
                                    'option'  => $this->normalize_options(\WPSP\Helper::get_all_roles()),
                                ],
                                'calendar_schedule_time' => [
                                    'name'     => 'calendar_schedule_time',
                                    'label'    => __('Calendar Default Schedule Time:', 'notificationx'),
                                    'type'     => 'time',
                                    'priority' => 10,
                                ],
                                'adminbar_list_structure' => [
                                    'name'     => 'adminbar_list_structure',
                                    'type'     => 'section',
                                    'label'    => __('Custom item template for scheduled posts list in the admin bar:', 'wp-scheduled-posts'),
                                    'collapsible'  => true,
                                    'classes'   => 'section-collapsible',
                                    'default'  => 1,
                                    'priority' => 15,
                                    'fields'   => [
                                        'adminbar_list_structure_template'  => [
                                            'id'            => 'adminbar_list_structure_template',
                                            'name'          => 'adminbar_list_structure_template',
                                            'type'          => 'text',
                                            'label'         => __('Item template:', 'wp-scheduled-posts'),
                                            'default'       => '<strong>%TITLE%</strong> / %AUTHOR% / %DATE%',
                                            'priority'      => 5,
                                        ],
                                        'adminbar_list_structure_title_length'  => [
                                            'id'            => 'adminbar_list_structure_title_length',
                                            'name'          => 'adminbar_list_structure_title_length',
                                            'type'          => 'text',
                                            'label'         => __('Title length:', 'wp-scheduled-posts'),
                                            'default'       => '45',
                                            'priority'      => 10,
                                        ],
                                        'adminbar_list_structure_date_format'  => [
                                            'id'            => 'adminbar_list_structure_date_format',
                                            'name'          => 'adminbar_list_structure_date_format',
                                            'type'          => 'text',
                                            'label'         => __('Date format:', 'wp-scheduled-posts'),
                                            'default'       => 'M-d h:i:a',
                                            'description'   => __('For item template use %TITLE% for the post title, %AUTHOR% for post author, and %DATE% for post scheduled date-time. You can use HTML tags with styles also.', 'wp-scheduled-posts'),
                                            'priority'      => 15,
                                        ],
                                    ]
                                ],
                                'show_publish_post_button' => [
                                    'name'     => 'show_publish_post_button',
                                    'type'     => 'toggle',
                                    'label'    => __('Show Publish Post Immediately Button', 'wp-scheduled-posts'),
                                    'default'  => 1,
                                    'priority' => 20,
                                ],
                                'hide_on_elementor_editor' => [
                                    'name'     => 'hide_on_elementor_editor',
                                    'type'     => 'toggle',
                                    'label'    => __('Show Scheduled Posts in Elementor', 'wp-scheduled-posts'),
                                    'priority' => 25,
                                ],
                                'republish_social_share' => [
                                    'name'          => 'republish_social_share',
                                    'type'          => 'toggle',
                                    'label'         => __('Active Republish Social Share', 'wp-scheduled-posts'),
                                    'description'   => 'Upgrade to Premium',
                                    'priority'      => 30,
                                    'is_pro'        => true,
                                ],
                                'post_republish_unpublish' => [
                                    'name'          => 'post_republish_unpublish',
                                    'type'          => 'toggle',
                                    'label'         => __('Post Republish and Unpublish', 'wp-scheduled-posts'),
                                    'priority'      => 35,
                                    'description'   => 'Upgrade to Premium',
                                    'is_pro'        => true,
                                ],

                            ],
                        ],
                    ])
                ],
                'layout_calender'         => [
                    'id'       => 'layout_calender',
                    'name'     => 'layout_calender',
                    'type'     => 'section',
                    'label'    => __('Calender', 'wp-scheduled-posts'),
                    'priority' => 10,
                    'fields'   => [

                    ]
                ],
                'layout_email_notify'         => [
                    'id'       => 'layout_email_notify',
                    'name'     => 'layout_email_notify',
                    'type'     => 'section',
                    'label'    => __('Email Notify', 'wp-scheduled-posts'),
                    'priority' => 15,
                    'fields'   => [
                        'email_notify'     => [
                            'name'     => 'email_notify',
                            'type'     => 'section',
                            'label'    => __( 'Email Notify', 'wp-scheduled-posts' ),
                            'priority' => 1,
                            'fields'    => [
                                'notify_author_post_is_review'       => [
                                    'name'     => 'notify_author_post_is_review',
                                    'type'     => 'toggle',
                                    'label'    => __('Notify User when a post is "Under Review"', 'wp-scheduled-posts'),
                                    'default'  => 1,
                                    'priority' => 5,
                                ],
                                'notify_author_post_review_by_role' => [
                                    'name'     => 'notify_author_post_review_by_role',
                                    'label'    => __('Role', 'notificationx'),
                                    'type'     => 'select',
                                    'multiple' => true,
                                    'priority' => 10,
                                    'options'  => $this->normalize_options( \WPSP\Helper::get_all_roles() ),
                                    'rules'       => Rules::logicalRule([
                                        Rules::is( 'notify_author_post_is_review', true ),
                                    ]),
                                ],
                                'notify_author_post_review_by_username' => [
                                    'name'     => 'notify_author_post_review_by_username',
                                    'label'    => __('Username:', 'notificationx'),
                                    'type'     => 'select',
                                    'multiple' => true,
                                    'priority' => 11,
                                    'options'  => $this->normalize_options( \wp_list_pluck(\get_users(array('fields' => array('user_login', 'user_email'))), 'user_login', 'user_login') ),
                                    'rules'       => Rules::logicalRule([
                                        Rules::is( 'notify_author_post_is_review', true ),
                                    ]),
                                ],
                                'notify_author_post_review_by_email' => [
                                    'name'     => 'notify_author_post_review_by_email',
                                    'label'    => __('Email:', 'notificationx'),
                                    'type'     => 'select',
                                    'multiple' => true,
                                    'priority' => 12,
                                    'options'  => $this->normalize_options( \wp_list_pluck(\get_users(array('fields' => array('user_login', 'user_email'))), 'user_email', 'user_email') ),
                                    'rules'       => Rules::logicalRule([
                                        Rules::is( 'notify_author_post_is_review', true ),
                                    ]),
                                ],
                                'notify_author_post_is_rejected'       => [
                                    'name'     => 'notify_author_post_is_rejected',
                                    'type'     => 'toggle',
                                    'label'    => __('Notify Author when a post is "Rejected"', 'wp-scheduled-posts'),
                                    'default'  => 1,
                                    'priority' => 15,
                                ],
                                'notify_author_post_is_scheduled'       => [
                                    'name'     => 'notify_author_post_is_scheduled',
                                    'type'     => 'toggle',
                                    'label'    => __('Notify User when a post is "Scheduled"', 'wp-scheduled-posts'),
                                    'priority' => 20,
                                ],
                                'notify_author_post_scheduled_by_role' => [
                                    'name'     => 'notify_author_post_scheduled_by_role',
                                    'label'    => __('Role', 'notificationx'),
                                    'type'     => 'select',
                                    'multiple' => true,
                                    'priority' => 25,
                                    'options'  => $this->normalize_options( \WPSP\Helper::get_all_roles() ),
                                    'rules'       => Rules::logicalRule([
                                        Rules::is( 'notify_author_post_is_scheduled', true ),
                                    ]),
                                ],
                                'notify_author_post_scheduled_by_username' => [
                                    'name'     => 'notify_author_post_scheduled_by_username',
                                    'label'    => __('Username:', 'notificationx'),
                                    'type'     => 'select',
                                    'multiple' => true,
                                    'priority' => 30,
                                    'options'  => $this->normalize_options( \wp_list_pluck(\get_users(array('fields' => array('user_login', 'user_email'))), 'user_login', 'user_login') ),
                                    'rules'       => Rules::logicalRule([
                                        Rules::is( 'notify_author_post_is_scheduled', true ),
                                    ]),
                                ],
                                'notify_author_post_scheduled_by_email' => [
                                    'name'     => 'notify_author_post_scheduled_by_email',
                                    'label'    => __('Email:', 'notificationx'),
                                    'type'     => 'select',
                                    'multiple' => true,
                                    'priority' => 35,
                                    'options'  => $this->normalize_options( \wp_list_pluck(\get_users(array('fields' => array('user_login', 'user_email'))), 'user_email', 'user_email') ),
                                    'rules'       => Rules::logicalRule([
                                        Rules::is( 'notify_author_post_is_scheduled', true ),
                                    ]),
                                ],
                                'notify_author_post_scheduled_to_publish'       => [
                                    'name'     => 'notify_author_post_scheduled_to_publish',
                                    'type'     => 'toggle',
                                    'label'    => __('Notify Author when a Scheduled Post is "Published"', 'wp-scheduled-posts'),
                                    'priority' => 40,
                                ],
                                'notify_author_post_is_publish'       => [
                                    'name'     => 'notify_author_post_is_publish',
                                    'type'     => 'toggle',
                                    'label'    => __('Notify Author when a post is "Published"', 'wp-scheduled-posts'),
                                    'default'  => 1,
                                    'priority' => 45,
                                ],
                            ],
                        ],
                    ]
                ],
                'layout_social_profile'       => [
                    'id'       => 'layout_social_profile',
                    'name'     => 'layout_social_profile',
                    'type'     => 'section',
                    'label'    => __('Social Profile', 'wp-scheduled-posts'),
                    'priority' => 20,
                    'fields'   => [
                        'social_profile_wrapper' => [
                            'id'            => 'social_profile_wrapper',
                            'name'          => 'social_profile_wrapper',
                            'type'          => 'section',
                            'label'         => __('Social Profile', 'wp-scheduled-posts'),
                            'priority'      => 5,
                            'fields'        => [
                                'facebook_profile_list'  => [
                                    'id'       => 'facebook_profile_list',
                                    'name'     => 'facebook_profile_list',
                                    'type'     => 'facebook',
                                    'label'    => __('Facebook', 'wp-scheduled-posts'),
                                    'default'  => false,
                                    'doc_link' => 'https://google.com',
                                    'logo'     => 'https://upload.wikimedia.org/wikipedia/en/thumb/0/04/Facebook_f_logo_%282021%29.svg/480px-Facebook_f_logo_%282021%29.svg.png',
                                    'priority' => 5,
                                ],
                                'linkedin_profile_list'  => [
                                    'id'       => 'linkedin_profile_list',
                                    'name'     => 'linkedin_profile_list',
                                    'type'     => 'linkedin',
                                    'label'    => __('Linkedin', 'wp-scheduled-posts'),
                                    'priority' => 10,
                                ],
                                'pinterest_profile_list'  => [
                                    'id'       => 'pinterest_profile_list',
                                    'name'     => 'pinterest_profile_list',
                                    'type'     => 'pinterest',
                                    'label'    => __('Pinterest', 'wp-scheduled-posts'),
                                    'priority' => 1,
                                ],
                                'twitter_profile_list'  => [
                                    'id'       => 'twitter_profile_list',
                                    'name'     => 'twitter_profile_list',
                                    'type'     => 'twitter',
                                    'label'    => __('Twitter', 'wp-scheduled-posts'),
                                    'priority' => 15,
                                ],
                            ]
                        ]

                    ]
                ],
                'layout_social_template'       => [
                    'id'       => 'layout_social_template',
                    'name'     => 'layout_social_template',
                    'type'     => 'section',
                    'label'    => __('Social Template', 'wp-scheduled-posts'),
                    'priority' => 25,
                    'fields'   => [
                        'tab_social_template'  => [
                            'id'              => 'tab_social_template',
                            'name'            => 'tab_social_template',
                            'type'            => 'tab',
                            'priority'        => 25,
                            'completionTrack' => true,
                            'sidebar'         => true,
                            'title'           => false,
                            'default'         => 'layouts_facebook',
                            'submit'          => [
                                'show' => false
                            ],
                            'step'            => [
                                'show' => false
                            ],
                            'fields'   => [
                                'layouts_facebook'  => [
                                    'id'            => 'layouts_facebook',
                                    'name'          => 'layouts_facebook',
                                    'label'         => __('Facebook', 'wp-scheduled-posts'),
                                    'priority'      => 10,
                                    'fields'        => [
                                        'facebook_wrapper'     => [
                                            'id'            => 'facebook_wrapper',
                                            'type'          => 'section',
                                            'name'          => 'facebook_wrapper',
                                            'label'         => __('Facebook', 'wp-scheduled-posts'),
                                            'priority'      => 10,
                                            'fields'        => [
                                                'facebook'  => [
                                                    'name'     => "facebook",
                                                    'type'     => "group",
                                                    'priority' => 10,
                                                    'fields'    => [
                                                        'is_show_meta'  => [
                                                            'id'            => 'facebook_show_meta',
                                                            'name'          => 'is_show_meta',
                                                            'type'          => 'toggle',
                                                            'default'       => 1,
                                                            'label'         => __('Facebook Status Settings', 'wp-scheduled-posts'),
                                                            'description'   => __('Add Open Graph metadata to your site head section and other social networks use this data when your pages are shared.', 'wp-scheduled-posts'),
                                                            'priority'      => 5,
                                                        ],
                                                        'content_type' => [
                                                            'label'   => __('Content Type:','wp-scheduled-posts'),
                                                            'name'    => "content_type",
                                                            'type'    => "radio-card",
                                                            'default' => "link",
                                                            'priority'=> 6,
                                                            'options' => [ 
                                                                [
                                                                    'label' => __( 'Link','wp-scheduled-posts' ),
                                                                    'value' => 'link',
                                                                ],
                                                                [
                                                                    'label' => __( 'Status','wp-scheduled-posts' ),
                                                                    'value' => 'status',
                                                                ],
                                                                [
                                                                    'label' => __( 'Status + Link','wp-scheduled-posts' ),
                                                                    'value' => 'statuswithlink',
                                                                ],
                                                             ],
                                                        ],
                                                        'is_category_as_tags'  => [
                                                            'id'            => 'facebook_cat_tags',
                                                            'name'          => 'is_category_as_tags',
                                                            'type'          => 'toggle',
                                                            'label'         => __('Add Category as a tags', 'wp-scheduled-posts'),
                                                            'priority'      => 10,
                                                        ],
                                                        'content_source' => [
                                                            'label'   => __('Content Source:','wp-scheduled-posts'),
                                                            'name'    => "content_source",
                                                            'type'    => "radio-card",
                                                            'default' => "excerpt",
                                                            'priority'=> 11,
                                                            'options' => [ 
                                                                [
                                                                    'label' => __( 'Excerpt','wp-scheduled-posts' ),
                                                                    'value' => 'excerpt',
                                                                ],
                                                                [
                                                                    'label' => __( 'Content','wp-scheduled-posts' ),
                                                                    'value' => 'content',
                                                                ],
                                                             ],
                                                        ],
                                                        'template_structure'  => [
                                                            'id'            => 'facebook_structure',
                                                            'name'          => 'template_structure',
                                                            'type'          => 'text',
                                                            'label'         => __('Status Template Settings', 'wp-scheduled-posts'),
                                                            'default'       => '{title}{content}{url}{tags}',
                                                            'description'   => __('Default Structure: {title}{content}{url}{tags}', 'wp-scheduled-posts'),
                                                            'priority'      => 15,
                                                        ],
                                                        'status_limit'  => [
                                                            'id'            => 'facebook_status_limit',
                                                            'name'          => 'status_limit',
                                                            'type'          => 'number',
                                                            'label'         => __('Status Limit', 'wp-scheduled-posts'),
                                                            'priority'      => 20,
                                                            'default'       => 63206,
                                                            'max'           => 63206,
                                                            'description'   => __('Maximum Limit: 63206 character', 'wp-scheduled-posts'),
                                                        ],
                                                    ]
                                                ]   

                                            ]
                                        ]
                                    ]
                                ],
                                'layouts_twitter'  => [
                                    'id'            => 'layouts_twitter',
                                    'name'          => 'layouts_twitter',
                                    'label'         => __('Twitter', 'wp-scheduled-posts'),
                                    'priority'      => 20,
                                    'fields'        => [
                                        'twitter_wrapper'     => [
                                            'id'            => 'twitter_wrapper',
                                            'type'          => 'section',
                                            'name'          => 'twitter_wrapper',
                                            'label'         => __('Twitter', 'wp-scheduled-posts'),
                                            'priority'      => 10,
                                            'fields'        => [
                                                'twitter'  => [
                                                    'name'     => "twitter",
                                                    'type'     => "group",
                                                    'priority' => 10,
                                                    'fields'    => [
                                                        'template_structure'  => [
                                                            'id'            => 'twitter_template',
                                                            'name'          => 'template_structure',
                                                            'type'          => 'text',
                                                            'label'         => __('Tweet Template Settings', 'wp-scheduled-posts'),
                                                            'default'       => '{title}{content}{url}{tags}',
                                                            'desc'          => __('Default Structure: {title}{content}{url}{tags}', 'wp-scheduled-posts'),
                                                            'priority'      => 5,
                                                        ],
                                                        'is_category_as_tags'  => [
                                                            'id'            => 'twitter_cat_tags',
                                                            'name'          => 'is_category_as_tags',
                                                            'type'          => 'toggle',
                                                            'label'         => __('Add Category as a tags', 'wp-scheduled-posts'),
                                                            'priority'      => 10,
                                                        ],
                                                        'content_source' => [
                                                            'label'         => __('Content Source:','wp-scheduled-posts'),
                                                            'name'          => "content_source",
                                                            'type'          => "radio-card",
                                                            'default'       => "excerpt",
                                                            'priority'      => 11,
                                                            'options' => [ 
                                                                [
                                                                    'label' => __( 'Excerpt','wp-scheduled-posts' ),
                                                                    'value' => 'excerpt',
                                                                ],
                                                                [
                                                                    'label' => __( 'Content','wp-scheduled-posts' ),
                                                                    'value' => 'content',
                                                                ],
                                                             ],
                                                        ],
                                                        'is_show_post_thumbnail'  => [
                                                            'id'            => 'twitter_post_thumbnail',
                                                            'name'          => 'is_show_post_thumbnail',
                                                            'type'          => 'toggle',
                                                            'label'         => __('Show Post Thumbnail', 'wp-scheduled-posts'),
                                                            'default'       => false,
                                                            'priority'      => 15,
                                                        ],
                                                        'status_limit'  => [
                                                            'id'            => 'twitter_status_limit',
                                                            'name'          => 'status_limit',
                                                            'type'          => 'number',
                                                            'label'         => __('Tweet Limit', 'wp-scheduled-posts'),
                                                            'priority'      => 20,
                                                            'default'       => 280,
                                                            'max'           => 280,
                                                            'description'   => __('Maximum Limit: 280 character', 'wp-scheduled-posts'),
                                                        ],
                                                    ]
                                                ]

                                            ]
                                        ]
                                    ]
                                ],
                                'layouts_linkedin'  => [
                                    'id'            => 'layouts_linkedin',
                                    'name'          => 'layouts_linkedin',
                                    'label'         => __('Linkedin', 'wp-scheduled-posts'),
                                    'priority'      => 30,
                                    'fields'        => [
                                        'linkedin_wrapper'     => [
                                            'id'            => 'linkedin_wrapper',
                                            'type'          => 'section',
                                            'name'          => 'linkedin_wrapper',
                                            'label'         => __('Linkedin', 'wp-scheduled-posts'),
                                            'priority'      => 10,
                                            'fields'        => [
                                                'linkedin'  => [
                                                    'name'     => "linkedin",
                                                    'type'     => "group",
                                                    'priority' => 10,
                                                    'fields'    => [
                                                        'content_type' => [
                                                            'label'   => __('Content Type:','wp-scheduled-posts'),
                                                            'name'    => "content_type",
                                                            'type'    => "radio-card",
                                                            'default' => "link",
                                                            'priority'=> 6,
                                                            'options' => [ 
                                                                [
                                                                    'label' => __( 'Link','wp-scheduled-posts' ),
                                                                    'value' => 'link',
                                                                ],
                                                                [
                                                                    'label' => __( 'Status','wp-scheduled-posts' ),
                                                                    'value' => 'status',
                                                                ],
                                                                [
                                                                    'label' => __( 'Media','wp-scheduled-posts' ),
                                                                    'value' => 'media',
                                                                ],
                                                             ],
                                                        ],
                                                        'is_category_as_tags'  => [
                                                            'id'            => 'linkedin_cat_tags',
                                                            'name'          => 'is_category_as_tags',
                                                            'type'          => 'toggle',
                                                            'label'         => __('Add Category as a tags', 'wp-scheduled-posts'),
                                                            'priority'      => 10,
                                                        ],
                                                        'content_source' => [
                                                            'label'         => __('Content Type:','wp-scheduled-posts'),
                                                            'name'          => "content_source",
                                                            'type'          => "radio-card",
                                                            'default'       => "excerpt",
                                                            'priority'      => 11,
                                                            'options' => [ 
                                                                [
                                                                    'label' => __( 'Excerpt','wp-scheduled-posts' ),
                                                                    'value' => 'excerpt',
                                                                ],
                                                                [
                                                                    'label' => __( 'Content','wp-scheduled-posts' ),
                                                                    'value' => 'content',
                                                                ],
                                                             ],
                                                        ],
                                                        'template_structure'  => [
                                                            'id'            => 'linkedin_template',
                                                            'name'          => 'template_structure',
                                                            'type'          => 'text',
                                                            'label'         => __('Tweet Template Settings', 'wp-scheduled-posts'),
                                                            'default'       => '{title}{content}{tags}',
                                                            'desc'          => __('Default Structure: {title}{content}{url}{tags}', 'wp-scheduled-posts'),
                                                            'priority'      => 5,
                                                        ],
                                                        'status_limit'  => [
                                                            'id'            => 'linkedin_status_limit',
                                                            'name'          => 'status_limit',
                                                            'type'          => 'number',
                                                            'label'         => __('Status Limit', 'wp-scheduled-posts'),
                                                            'priority'      => 20,
                                                            'default'       => 1300,
                                                            'max'           => 1300,
                                                            'description'   => __('Maximum Limit: 1300 character', 'wp-scheduled-posts'),
                                                        ],
                                                    ]
                                                ]

                                            ]
                                        ]
                                    ]
                                ],
                                'layouts_pinterest'  => [
                                    'id'            => 'layouts_pinterest',
                                    'name'          => 'layouts_pinterest',
                                    'label'         => __('Pinterest', 'wp-scheduled-posts'),
                                    'priority'      => 40,
                                    'fields'        => [
                                        'pinterest_wrapper'     => [
                                            'id'            => 'pinterest_wrapper',
                                            'type'          => 'section',
                                            'name'          => 'pinterest_wrapper',
                                            'label'         => __('Linkedin', 'wp-scheduled-posts'),
                                            'priority'      => 10,
                                            'fields'        => [
                                                'pinterest'  => [
                                                    'name'     => "pinterest",
                                                    'type'     => "group",
                                                    'priority' => 10,
                                                    'fields'    => [
                                                        'is_set_image_link'  => [
                                                            'id'            => 'pinterest_image_link',
                                                            'name'          => 'is_set_image_link',
                                                            'type'          => 'toggle',
                                                            'label'         => __('Add Image Link', 'wp-scheduled-posts'),
                                                            'priority'      => 5,
                                                            'default'       => 1,
                                                        ],
                                                        'is_category_as_tags'  => [
                                                            'id'            => 'pinterest_cat_tags',
                                                            'name'          => 'is_category_as_tags',
                                                            'type'          => 'toggle',
                                                            'label'         => __('Add Category as a tags', 'wp-scheduled-posts'),
                                                            'priority'      => 10,
                                                        ],
                                                        'content_source' => [
                                                            'label'         => __('Content Type:','wp-scheduled-posts'),
                                                            'name'          => "content_source",
                                                            'type'          => "radio-card",
                                                            'default'       => "excerpt",
                                                            'priority'      => 11,
                                                            'options' => [ 
                                                                [
                                                                    'label' => __( 'Excerpt','wp-scheduled-posts' ),
                                                                    'value' => 'excerpt',
                                                                ],
                                                                [
                                                                    'label' => __( 'Content','wp-scheduled-posts' ),
                                                                    'value' => 'content',
                                                                ],
                                                             ],
                                                        ],
                                                        'template_structure'  => [
                                                            'id'            => 'template_structure',
                                                            'name'          => 'template_structure',
                                                            'type'          => 'text',
                                                            'label'         => __('Status Template Settings', 'wp-scheduled-posts'),
                                                            'desc'          => __('Default Structure: {title}{content}{url}{tags}', 'wp-scheduled-posts'),
                                                            'default'       => '{title}',
                                                            'priority'      => 15,
                                                        ],
                                                        'status_limit'  => [
                                                            'id'            => 'linkedin_status_limit',
                                                            'name'          => 'status_limit',
                                                            'type'          => 'number',
                                                            'label'         => __('Status Limit', 'wp-scheduled-posts'),
                                                            'priority'      => 20,
                                                            'default'       => '500',
                                                            'max'           => '500',
                                                            'description'   => __('Maximum Limit: 500 character', 'wp-scheduled-posts'),
                                                        ],
                                                    ]
                                                ]

                                            ]
                                        ]
                                    ]
                                ],
                            ]
                        ]
                    ]
                ],
                'layout_manage_schedule'       => [
                    'id'       => 'layout_manage_schedule',
                    'name'     => 'layout_manage_schedule',
                    'type'     => 'section',
                    'label'    => __('Manage Schedule', 'wp-scheduled-posts'),
                    'priority' => 30,
                    'is_pro'   => true,
                    'classes'  => 'pro_feature',
                    'fields'   => [

                    ]
                ],
                'layout_advance_schedule'       => [
                    'id'       => 'layout_advance_schedule',
                    'name'     => 'layout_advance_schedule',
                    'type'     => 'section',
                    'label'    => __('Advance Schedule', 'wp-scheduled-posts'),
                    'priority' => 35,
                    'is_pro'   => true,
                    'classes'  => 'pro_feature',
                    'fields'   => [

                    ]
                ],
                'layout_missed_schedule'       => [
                    'id'       => 'layout_missed_schedule',
                    'name'     => 'layout_missed_schedule',
                    'type'     => 'section',
                    'label'    => __('Missed Schedule', 'wp-scheduled-posts'),
                    'priority' => 40,
                    'is_pro'   => true,
                    'classes'  => 'pro_feature',
                    'fields'   => [

                    ]
                ],
            ]
        ]);
    }

    public function set_settings_config_callback($Builder) {
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

        if (!defined('WPSP_PRO_VERSION')) {
            // Manage Schedule
            $Builder::add_tab([
                'title' => __('Manage Schedule', 'wp-scheduled-posts-pro'),
                'id' => '_manage_schedule',
            ]);

            $Builder::add_field('_manage_schedule', [
                'id' => 'is_show_dashboard_widget',
                'type' => 'screenshot',
                'title' => __('Show Scheduled Posts in Dashboard Widget', 'wp-scheduled-posts'),
                'src' => WPSP_ASSETS_URI . 'images/screenshot/manage-schedule.jpg',
                'link' => 'https://wpdeveloper.com/in/schedulepress-pro',
            ]);

            // active missed schedule
            $Builder::add_tab([
                'title' => __('Missed Schedule', 'wp-scheduled-posts-pro'),
                'id' => 'wpsp_pro_miss_schedule',
            ]);

            $Builder::add_field('wpsp_pro_miss_schedule', [
                'id' => 'is_show_dashboard_widget',
                'type' => 'screenshot',
                'title' => __('Show Scheduled Posts in Dashboard Widget', 'wp-scheduled-posts'),
                'src' => WPSP_ASSETS_URI . 'images/screenshot/missed-schedule.jpg',
                'link' => 'https://wpdeveloper.com/in/schedulepress-pro',
            ]);
        }
    }
}
