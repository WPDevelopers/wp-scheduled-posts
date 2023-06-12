<?php

namespace WPSP\Admin;


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
        $this->builder = new Settings\Builder();
        do_action('wpsp/admin/settings/set_settings_config', $this->builder);
        $this->settings = $this->builder->get_settings();
        new Settings\Assets($this->slug, $this->get_settings_array());
        $this->data  = new Settings\Data($this->option_name, $this->settings);
        add_action('wpsp_save_settings_default_value', array($this->data, 'save_option_value'));
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
        return $this->normalize([
            'id'              => 'tab-sidebar-layout',
            'name'            => 'tab_sidebar_layout',
            'label'           => __('Layout', 'betterdocs'),
            'classes'         => 'tab-layout',
            'type'            => "tab",
            'active'          => "layout_documentation_page",
            'completionTrack' => true,
            'sidebar'         => false,
            'config'          => [
                'active'  => 'layout_documentation_page',
                'sidebar' => false,
                'title'   => false
            ],
            'submit'          => [
                'show' => true
            ],
            'step'            => [
                'show' => false
            ],
            'priority'        => 20,
            'fields'          => [
                'layout_documentation_page' => [
                    'id'       => 'layout_documentation_page',
                    'name'     => 'layout_documentation_page',
                    'type'     => 'section',
                    'label'    => __('Documentation Page', 'betterdocs'),
                    'priority' => 5,
                    'fields'   => [
                        // 'doc_page'                  => [
                        //     'name'     => 'doc_page',
                        //     'type'     => 'title',
                        //     'label'    => __( 'Documentation Page', 'betterdocs' ),
                        //     'priority' => 1
                        // ],
                        'live_search'               => [
                            'name'     => 'live_search',
                            'type'     => 'checkbox',
                            'label'    => __('Enable Live Search', 'betterdocs'),
                            'default'  => 1,
                            'priority' => 2
                        ],
                        'advance_search'            => apply_filters('betterdocs_advance_search_settings', [
                            'name'     => 'advance_search',
                            'type'     => 'checkbox',
                            'label'    => __('Enable Advanced Search', 'betterdocs'),
                            'default'  => '',
                            'priority' => 3,
                            'is_pro'   => true
                        ]),
                        'child_category_exclude'    => apply_filters('child_category_exclude', [
                            'name'     => 'child_category_exclude',
                            'type'     => 'checkbox',
                            'label'    => __('Exclude Child Terms In Category Search', 'betterdocs'),
                            'default'  => '',
                            'priority' => 4,
                            'is_pro'   => true
                        ]),
                        'popular_keyword_limit'     => apply_filters('betterdocs_popular_keyword_limit_settings', [
                            'name'     => 'popular_keyword_limit',
                            'type'     => 'number',
                            'label'    => __('Minimum amount of Keywords Search', 'betterdocs'),
                            'default'  => 5,
                            'priority' => 5,
                            'is_pro'   => true
                        ]),
                        'search_letter_limit'       => [
                            'name'     => 'search_letter_limit',
                            'type'     => 'number',
                            'label'    => __('Minimum Character Limit For Search Result', 'betterdocs'),
                            'priority' => 6,
                            'default'  => 3
                        ],
                        'search_placeholder'        => [
                            'name'     => 'search_placeholder',
                            'type'     => 'text',
                            'label'    => __('Search Placeholder', 'betterdocs'),
                            'default'  => 'Search..',
                            'priority' => 7
                        ],
                        'search_button_text'        => apply_filters('betterdocs_search_button_text', [
                            'name'     => 'search_button_text',
                            'type'     => 'text',
                            'label'    => __('Search Button Text', 'betterdocs'),
                            'priority' => 8,
                            'default'  => __('Search', 'betterdocs'),
                            'is_pro'   => true
                        ]),
                        'search_not_found_text'     => [
                            'name'     => 'search_not_found_text',
                            'type'     => 'text',
                            'label'    => __('Search Not Found Text', 'betterdocs'),
                            'default'  => 'Sorry, no docs were found.',
                            'priority' => 9
                        ],
                        'search_result_image'       => [
                            'name'     => 'search_result_image',
                            'type'     => 'checkbox',
                            'label'    => __('Search Result Image', 'betterdocs'),
                            'default'  => 1,
                            'priority' => 10
                        ],
                        'kb_based_search'           => apply_filters('betterdocs_kb_based_search_settings', [
                            'name'     => 'kb_based_search',
                            'type'     => 'checkbox',
                            'label'    => __('Search Result based on KB', 'betterdocs'),
                            'default'  => '',
                            'priority' => 11,
                            'is_pro'   => true
                        ]),
                        'masonry_layout'            => [
                            'name'     => 'masonry_layout',
                            'type'     => 'checkbox',
                            'label'    => __('Enable Masonry', 'betterdocs'),
                            'default'  => 1,
                            'priority' => 12
                        ],
                        'terms_orderby'             => [
                            'name'     => 'terms_orderby',
                            'type'     => 'select',
                            'label'    => __('Terms Order By', 'betterdocs'),
                            'default'  => 'betterdocs_order',
                            'options'  => $this->normalize_options(
                                apply_filters('betterdocs_terms_orderby_options', [
                                    'none'             => __('No order', 'betterdocs'),
                                    'name'             => __('Name', 'betterdocs'),
                                    'slug'             => __('Slug', 'betterdocs'),
                                    'term_group'       => __('Term Group', 'betterdocs'),
                                    'term_id'          => __('Term ID', 'betterdocs'),
                                    'id'               => __('ID', 'betterdocs'),
                                    'description'      => __('Description', 'betterdocs'),
                                    'parent'           => __('Parent', 'betterdocs'),
                                    'betterdocs_order' => __('BetterDocs Order', 'betterdocs')
                                ])
                            ),
                            'priority' => 13
                        ],
                        'alphabetically_order_term' => [
                            'name'     => 'alphabetically_order_term',
                            'type'     => 'checkbox',
                            'label'    => __('Order Terms Alphabetically', 'betterdocs'),
                            'default'  => '',
                            'priority' => 14
                        ],
                        'terms_order'               => [
                            'name'     => 'terms_order',
                            'type'     => 'select',
                            'label'    => __('Terms Order', 'betterdocs'),
                            'default'  => 'ASC',
                            'options'  => $this->normalize_options([
                                'ASC'  => 'Ascending',
                                'DESC' => 'Descending'
                            ]),
                            'priority' => 15
                        ],
                        'alphabetically_order_post' => [
                            'name'     => 'alphabetically_order_post',
                            'type'     => 'select',
                            'label'    => __('Docs Order By', 'betterdocs'),
                            'default'  => 'betterdocs_order',
                            'options'  => $this->normalize_options([
                                'none'             => __('No order', 'betterdocs'),
                                'ID'               => __('Post ID', 'betterdocs'),
                                'author'           => __('Post Author', 'betterdocs'),
                                '1'                => __('Title', 'betterdocs'), // value is 1 to cope up with existing user data
                                'date'             => __('Date', 'betterdocs'),
                                'modified'         => __('Last Modified Date', 'betterdocs'),
                                'parent'           => __('Parent Id', 'betterdocs'),
                                'rand'             => __('Random', 'betterdocs'),
                                'comment_count'    => __('Comment Count', 'betterdocs'),
                                'menu_order'       => __('Menu Order', 'betterdocs'),
                                'betterdocs_order' => __('BetterDocs Order', 'betterdocs')
                            ]),
                            'priority' => 16
                        ],
                        'docs_order'                => [
                            'name'     => 'docs_order',
                            'type'     => 'select',
                            'label'    => __('Docs Order', 'betterdocs'),
                            'default'  => 'ASC',
                            'options'  => $this->normalize_options([
                                'ASC'  => 'Ascending',
                                'DESC' => 'Descending'
                            ]),
                            'priority' => 17
                        ],
                        'nested_subcategory'        => [
                            'name'     => 'nested_subcategory',
                            'type'     => 'checkbox',
                            'label'    => __('Nested Subcategory', 'betterdocs'),
                            'default'  => '',
                            'priority' => 18
                        ],
                        'column_number'             => [
                            'name'     => 'column_number',
                            'type'     => 'number',
                            'label'    => __('Number of Columns', 'betterdocs'),
                            'default'  => 3,
                            'priority' => 19
                        ],
                        'posts_number'              => apply_filters('betterdocs_posts_number', [
                            'name'     => 'posts_number',
                            'type'     => 'number',
                            'label'    => __('Number of Docs', 'betterdocs'),
                            'default'  => 10,
                            'priority' => 20
                        ]),
                        'post_count'                => [
                            'name'     => 'post_count',
                            'type'     => 'checkbox',
                            'label'    => __('Enable Doc Count', 'betterdocs'),
                            'default'  => 1,
                            'priority' => 21
                        ],
                        'count_text'                => [
                            'name'     => 'count_text',
                            'type'     => 'text',
                            'label'    => __('Count Text', 'betterdocs'),
                            'default'  => __('articles', 'betterdocs'),
                            'priority' => 22
                        ],
                        'count_text_singular'       => [
                            'name'     => 'count_text_singular',
                            'type'     => 'text',
                            'label'    => __('Count Text Singular', 'betterdocs'),
                            'default'  => __('article', 'betterdocs'),
                            'priority' => 23
                        ],
                        'exploremore_btn'           => [
                            'name'     => 'exploremore_btn',
                            'type'     => 'checkbox',
                            'label'    => __('Enable Explore More Button', 'betterdocs'),
                            'default'  => 1,
                            'priority' => 24
                        ],
                        'exploremore_btn_txt'       => [
                            'name'     => 'exploremore_btn_txt',
                            'type'     => 'text',
                            'label'    => __('Button Text', 'betterdocs'),
                            'default'  => __('Explore More', 'betterdocs'),
                            'priority' => 25,
                            'rules'    => Rules::is('exploremore_btn', true)
                        ]
                    ]
                ],
                'layout_single_doc'         => [
                    'id'       => 'layout_single_doc',
                    'name'     => 'layout_single_doc',
                    'type'     => 'section',
                    'label'    => __('Single Doc', 'betterdocs'),
                    'priority' => 6,
                    'fields'   => [
                        // 'doc_single'                 => [
                        //     'name'     => 'doc_single',
                        //     'type'     => 'title',
                        //     'label'    => __( 'Single Doc', 'betterdocs' ),
                        //     'priority' => 1
                        // ],
                        'enable_toc'                 => [
                            'name'     => 'enable_toc',
                            'type'     => 'checkbox',
                            'label'    => __('Enable Table of Contents (TOC)', 'betterdocs'),
                            'default'  => 1,
                            'priority' => 2
                        ],
                        'toc_title'                  => [
                            'name'     => 'toc_title',
                            'type'     => 'text',
                            'label'    => __('TOC Title', 'betterdocs'),
                            'default'  => __('Table of Contents', 'betterdocs'),
                            'priority' => 3,
                            'rules'    => Rules::is('enable_toc', true)

                        ],
                        'toc_hierarchy'              => [
                            'name'     => 'toc_hierarchy',
                            'type'     => 'checkbox',
                            'label'    => __('TOC Hierarchy', 'betterdocs'),
                            'default'  => 1,
                            'priority' => 4,
                            'rules'    => Rules::is('enable_toc', true)
                        ],
                        'toc_list_number'            => [
                            'name'     => 'toc_list_number',
                            'type'     => 'checkbox',
                            'label'    => __('TOC List Number', 'betterdocs'),
                            'default'  => 1,
                            'priority' => 5,
                            'rules'    => Rules::is('enable_toc', true)
                        ],
                        'toc_dynamic_title'          => [
                            'name'     => 'toc_dynamic_title',
                            'type'     => 'checkbox',
                            'label'    => __('Show TOC Title in Anchor Links', 'betterdocs'),
                            'default'  => 0,
                            'priority' => 6,
                            'rules'    => Rules::is('enable_toc', true)
                        ],
                        'enable_sticky_toc'          => [
                            'name'     => 'enable_sticky_toc',
                            'type'     => 'checkbox',
                            'label'    => __('Enable Sticky TOC', 'betterdocs'),
                            'default'  => 1,
                            'priority' => 7,
                            'rules'    => Rules::is('enable_toc', true)
                        ],
                        'sticky_toc_offset'          => [
                            'name'        => 'sticky_toc_offset',
                            'type'        => 'number',
                            'label'       => __('Content Offset', 'betterdocs'),
                            'default'     => 100,
                            'priority'    => 8,
                            'description' => __('content offset from top on scroll.', 'betterdocs'),
                            'rules'       => Rules::is('enable_toc', true)
                        ],
                        'collapsible_toc_mobile'     => [
                            'name'     => 'collapsible_toc_mobile',
                            'type'     => 'checkbox',
                            'label'    => __('Collapsible TOC on small devices', 'betterdocs'),
                            'default'  => '',
                            'priority' => 9,
                            'rules'    => Rules::is('enable_toc', true)
                        ],
                        'supported_heading_tag'      => [
                            'name'     => 'supported_heading_tag',
                            'label'    => __('TOC Supported Heading Tag', 'betterdocs'),
                            'type'     => 'select',
                            'multiple' => true,
                            'priority' => 10,
                            'default'  => ['1', '2', '3', '4', '5', '6'],
                            'options'  => $this->normalize_options([
                                '1' => 'h1',
                                '2' => 'h2',
                                '3' => 'h3',
                                '4' => 'h4',
                                '5' => 'h5',
                                '6' => 'h6'
                            ]),
                            'rules'    => Rules::is('enable_toc', true)
                        ],
                        'enable_post_title'          => [
                            'name'     => 'enable_post_title',
                            'type'     => 'checkbox',
                            'label'    => __('Enable Post Title', 'betterdocs'),
                            'default'  => 1,
                            'priority' => 11
                        ],
                        'title_link_ctc'             => [
                            'name'     => 'title_link_ctc',
                            'type'     => 'checkbox',
                            'label'    => __('Title Link Copy To Clipboard', 'betterdocs'),
                            'default'  => 1,
                            'priority' => 12
                        ],
                        'enable_breadcrumb'          => [
                            'name'     => 'enable_breadcrumb',
                            'type'     => 'checkbox',
                            'label'    => __('Enable Breadcrumb', 'betterdocs'),
                            'default'  => 1,
                            'priority' => 13
                        ],
                        'breadcrumb_home_text'       => [
                            'name'     => 'breadcrumb_home_text',
                            'type'     => 'text',
                            'label'    => __('Breadcrumb Home Text', 'betterdocs'),
                            'default'  => __('Home', 'betterdocs'),
                            'priority' => 14,
                            'rules'    => Rules::is('enable_breadcrumb', true)

                        ],
                        'breadcrumb_home_url'        => [
                            'name'     => 'breadcrumb_home_url',
                            'type'     => 'text',
                            'label'    => __('Breadcrumb Home URL', 'betterdocs'),
                            'priority' => 15,
                            'default'  => get_home_url(),
                            'rules'    => Rules::is('enable_breadcrumb', true)
                        ],
                        'enable_breadcrumb_category' => [
                            'name'     => 'enable_breadcrumb_category',
                            'type'     => 'checkbox',
                            'label'    => __('Enable Category on Breadcrumb', 'betterdocs'),
                            'default'  => 1,
                            'priority' => 16,
                            'rules'    => Rules::is('enable_breadcrumb', true)
                        ],
                        'enable_breadcrumb_title'    => [
                            'name'     => 'enable_breadcrumb_title',
                            'type'     => 'checkbox',
                            'label'    => __('Enable Title on Breadcrumb', 'betterdocs'),
                            'default'  => 1,
                            'priority' => 17,
                            'rules'    => Rules::is('enable_breadcrumb', true)
                        ],
                        'enable_sidebar_cat_list'    => [
                            'name'     => 'enable_sidebar_cat_list',
                            'type'     => 'checkbox',
                            'label'    => __('Enable Sidebar Category List', 'betterdocs'),
                            'default'  => 1,
                            'priority' => 18
                        ],
                        'enable_print_icon'          => [
                            'name'     => 'enable_print_icon',
                            'type'     => 'checkbox',
                            'label'    => __('Enable Print Icon', 'betterdocs'),
                            'default'  => 1,
                            'priority' => 19
                        ],
                        'enable_tags'                => [
                            'name'     => 'enable_tags',
                            'type'     => 'checkbox',
                            'label'    => __('Enable Tags', 'betterdocs'),
                            'default'  => 1,
                            'priority' => 20
                        ],
                        'email_feedback'             => [
                            'name'     => 'email_feedback',
                            'type'     => 'checkbox',
                            'label'    => __('Enable Email Feedback', 'betterdocs'),
                            'default'  => 1,
                            'priority' => 21
                        ],
                        'feedback_link_text'         => [
                            'name'     => 'feedback_link_text',
                            'type'     => 'text',
                            'label'    => __('Feedback Link Text', 'betterdocs'),
                            'default'  => __('Still stuck? How can we help?', 'betterdocs'),
                            'priority' => 22,
                            'rules'    => Rules::is('email_feedback', true)
                        ],
                        'feedback_url'               => [
                            'name'     => 'feedback_url',
                            'type'     => 'text',
                            'label'    => __('Feedback URL', 'betterdocs'),
                            'default'  => '',
                            'priority' => 23,
                            'rules'    => Rules::is('email_feedback', true)
                        ],
                        'feedback_form_title'        => [
                            'name'     => 'feedback_form_title',
                            'type'     => 'text',
                            'label'    => __('Feedback Form Title', 'betterdocs'),
                            'default'  => __('How can we help?', 'betterdocs'),
                            'priority' => 24,
                            'rules'    => Rules::is('email_feedback', true)
                        ],
                        'email_address'              => [
                            'name'        => 'email_address',
                            'type'        => 'text',
                            'label'       => __('Email Address', 'betterdocs'),
                            'default'     => get_option('admin_email'),
                            'priority'    => 25,
                            'description' => __('The email address where the Feedback from will be sent', 'betterdocs'),
                            'rules'       => Rules::is('email_feedback', true)
                        ],
                        'show_last_update_time'      => [
                            'name'     => 'show_last_update_time',
                            'type'     => 'checkbox',
                            'label'    => __('Show Last Update Time', 'betterdocs'),
                            'default'  => 1,
                            'priority' => 26
                        ],
                        'enable_navigation'          => [
                            'name'     => 'enable_navigation',
                            'type'     => 'checkbox',
                            'label'    => __('Enable Navigation', 'betterdocs'),
                            'default'  => 1,
                            'priority' => 27
                        ],
                        'enable_comment'             => [
                            'name'     => 'enable_comment',
                            'type'     => 'checkbox',
                            'label'    => __('Enable Comment', 'betterdocs'),
                            'default'  => '',
                            'priority' => 28
                        ],
                        'enable_credit'              => [
                            'name'     => 'enable_credit',
                            'type'     => 'checkbox',
                            'label'    => __('Enable Credit', 'betterdocs'),
                            'default'  => 1,
                            'priority' => 29
                        ]
                    ]
                ],
                'layout_archive_page'       => [
                    'id'       => 'layout_archive_page',
                    'name'     => 'layout_archive_page',
                    'type'     => 'section',
                    'label'    => __('Archive Page', 'betterdocs'),
                    'priority' => 7,
                    'fields'   => [
                        // 'archive_page_title'         => [
                        //     'name'     => 'archive_page_title',
                        //     'type'     => 'title',
                        //     'label'    => __( 'Archive Page', 'betterdocs' ),
                        //     'priority' => 30
                        // ],
                        'enable_archive_sidebar'     => [
                            'name'     => 'enable_archive_sidebar',
                            'type'     => 'checkbox',
                            'label'    => __('Enable Sidebar Category List', 'betterdocs'),
                            'default'  => 1,
                            'priority' => 31
                        ],
                        'archive_nested_subcategory' => [
                            'name'     => 'archive_nested_subcategory',
                            'type'     => 'checkbox',
                            'label'    => __('Nested Subcategory', 'betterdocs'),
                            'default'  => 1,
                            'priority' => 32
                        ]
                    ]
                ]
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
