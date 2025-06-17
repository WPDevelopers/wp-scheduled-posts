<?php

namespace WPSP\API;
use WPSP;
use WPSP\Social\ReconnectHandler;
use WPSP\Social\SocialProfile;
use WPSP\Helper;

class Settings
{
    /**
     * Main Setting Option Name
     *
     * @since 1.0.0
     *
     * @var string
     */
    private $settings_name = null;
    /**
     * Instance of this class.
     *
     * @since    1.0.0
     *
     * @var      object
     */
    protected static $instance = null;

    /**
     * Initialize hooks and option name
     */
    private function __construct()
    {
        $this->settings_name = WPSP_SETTINGS_NAME;
        $this->do_hooks();
    }

    /**
     * Set up WordPress hooks and filters
     *
     * @return void
     */
    public function do_hooks()
    {
        add_action('rest_api_init', array($this, 'register_routes'));
        add_action('rest_api_init', array($this, 'register_social_profile_routes'));
        add_action('rest_api_init', array($this, 'meta_rest_api'));
        add_action('wp_insert_post', array($this, 'initialize_custom_templates_meta'), 10, 2);
    }
    public function meta_rest_api() {
        $allow_post_types = \WPSP\Helper::get_all_allowed_post_type();
		$allow_post_types = (!empty($allow_post_types) ? $allow_post_types : array('post'));
        foreach ($allow_post_types as $type) {
            register_post_meta(
                $type,
                '_wpscppro_dont_share_socialmedia',
                [
                    'show_in_rest' => true,
                    'single'       => true,
                    'type'         => ['boolean', 'string'],
                    'auth_callback' => function() {
                        return current_user_can( 'edit_posts' );
                    }
                ]
            );
            register_post_meta(
                $type,
                '_wpscppro_custom_social_share_image',
                [
                    'show_in_rest' => true,
                    'single'       => true,
                    'type'         => 'integer',
                    'auth_callback' => function() {
                        return current_user_can( 'edit_posts' );
                    }
                ]
            );

            $social_media_meta_key = ['_facebook_share_type', '_twitter_share_type', '_linkedin_share_type', '_pinterest_share_type', '_linkedin_share_type_page', '_instagram_share_type', '_medium_share_type', '_threads_share_type'];
            // Social media meta 
            foreach ($social_media_meta_key as $value) {
                register_post_meta(
                    $type,
                    $value,
                    [
                        'show_in_rest' => true,
                        'single'       => true,
                        'type'         => 'string',
                        'auth_callback' => function() {
                            return current_user_can( 'edit_posts' );
                        }
                    ]
                );
            }

            // Save selected profile for specific page
            register_post_meta(
                $type,
                '_selected_social_profile',
                [
                    'show_in_rest' => [
                        'schema' => [
                            'type'  => 'array',
                            'items' => [
                                'type'       => 'object',
                                'properties' => [
                                    'id'                            => ['type' => ['string','integer']],
                                    'postid'                        => ['type' => 'integer'],
                                    'platform'                      => ['type' => 'string'],
                                    'platformKey'                   => ['type' => 'integer'],
                                    'pinterest_custom_board_name'   => ['type' => 'string'],
                                    'pinterest_custom_section_name' => ['type' => 'string'],
                                    'name'                          => ['type' => 'string'],
                                    'thumbnail_url'                 => ['type' => 'string'],
                                    'type'                          => ['type' => 'string'],
                                    'share_type'                    => ['type' => 'string'],
                                    'pinterest_board_type'          => ['type' => 'string'],
                                    'nonce'                         => ['type' => 'string'],
                                ],
                            ],
                        ],
                    ],
                    'single'       => true,
                    'type'         => 'array',
                    'auth_callback' => function() {
                        return current_user_can( 'edit_posts' );
                    }
                ]
            );

            // Custom social templates for platforms
            register_post_meta(
                $type,
                '_wpsp_custom_templates',
                [
                    'show_in_rest' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'facebook' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'template' => ['type' => 'string'],
                                        'profiles' => ['type' => 'array', 'items' => ['type' => ['string', 'integer']]],
                                    ],
                                    'default' => ['template' => '', 'profiles' => []],
                                ],
                                'twitter' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'template' => ['type' => 'string'],
                                        'profiles' => ['type' => 'array', 'items' => ['type' => ['string', 'integer']]],
                                    ],
                                    'default' => ['template' => '', 'profiles' => []],
                                ],
                                'linkedin' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'template' => ['type' => 'string'],
                                        'profiles' => ['type' => 'array', 'items' => ['type' => ['string', 'integer']]],
                                    ],
                                    'default' => ['template' => '', 'profiles' => []],
                                ],
                                'pinterest' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'template' => ['type' => 'string'],
                                        'profiles' => ['type' => 'array', 'items' => ['type' => ['string', 'integer']]],
                                    ],
                                    'default' => ['template' => '', 'profiles' => []],
                                ],
                                'instagram' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'template' => ['type' => 'string'],
                                        'profiles' => ['type' => 'array', 'items' => ['type' => ['string', 'integer']]],
                                    ],
                                    'default' => ['template' => '', 'profiles' => []],
                                ],
                                'medium' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'template' => ['type' => 'string'],
                                        'profiles' => ['type' => 'array', 'items' => ['type' => ['string', 'integer']]],
                                    ],
                                    'default' => ['template' => '', 'profiles' => []],
                                ],
                                'threads' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'template' => ['type' => 'string'],
                                        'profiles' => ['type' => 'array', 'items' => ['type' => ['string', 'integer']]],
                                    ],
                                    'default' => ['template' => '', 'profiles' => []],
                                ],
                            ],
                            'default' => [
                                'facebook' => ['template' => '', 'profiles' => []],
                                'twitter' => ['template' => '', 'profiles' => []],
                                'linkedin' => ['template' => '', 'profiles' => []],
                                'pinterest' => ['template' => '', 'profiles' => []],
                                'instagram' => ['template' => '', 'profiles' => []],
                                'medium' => ['template' => '', 'profiles' => []],
                                'threads' => ['template' => '', 'profiles' => []],
                            ]
                        ]
                    ],
                    'single' => true,
                    'type' => 'object',
                    'auth_callback' => function() {
                        return current_user_can( 'edit_posts' );
                    },
                ]
            );
            
            // Social scheduling data
            register_post_meta(
                $type,
                '_wpsp_social_scheduling',
                [
                    'show_in_rest' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'enabled' => ['type' => 'boolean'],
                                'datetime' => ['type' => ['string', 'null'], 'format' => 'date-time'],
                                'platforms' => ['type' => 'array', 'items' => ['type' => 'string']],
                                'status' => ['type' => 'string'],
                            ],
                        ]
                    ],
                    'single' => true,
                    'type' => 'object',
                    'default' => [
                        'enabled' => false,
                        'datetime' => null,
                        'platforms' => [],
                        'status' => 'template_only'
                    ],
                    'auth_callback' => function() {
                        return current_user_can( 'edit_posts' );
                    }
                ]
            );
        }

    }

    /**
     * Initialize custom templates meta field for new posts
     *
     * @param int $post_id
     * @param WP_Post $post
     */
    public function initialize_custom_templates_meta($post_id, $post) {
        // Only initialize for allowed post types
        $allow_post_types = \WPSP\Helper::get_all_allowed_post_type();
        $allow_post_types = (!empty($allow_post_types) ? $allow_post_types : array('post'));

        if (!in_array($post->post_type, $allow_post_types)) {
            return;
        }

        // Check if meta already exists
        $existing_meta = get_post_meta($post_id, '_wpsp_custom_templates', true);
        if (!empty($existing_meta)) {
            return;
        }

        // Initialize with default structure
        $default_templates = array(
            'facebook' => ['template' => '', 'profiles' => []],
            'twitter' => ['template' => '', 'profiles' => []],
            'linkedin' => ['template' => '', 'profiles' => []],
            'pinterest' => ['template' => '', 'profiles' => []],
            'instagram' => ['template' => '', 'profiles' => []],
            'medium' => ['template' => '', 'profiles' => []],
            'threads' => ['template' => '', 'profiles' => []]
        );

        update_post_meta($post_id, '_wpsp_custom_templates', $default_templates);
    }


    public function register_social_profile_routes()
    {
        $namespace = WPSP_PLUGIN_SLUG . '/v1';
        // Get option data
        register_rest_route($namespace, 'get-option-data', array(
            'methods' => 'GET',
            'callback'   => array($this, 'wpsp_get_options_data'),
            'permission_callback' => function() {
                return current_user_can( 'edit_posts' );
            }
        ));

        // Instant share on social media
        register_rest_route($namespace,'instant-social-share',array(
            'methods' => 'GET',
            'callback'   => array($this, 'wpsp_instant_social_share'),
            'permission_callback' => function() {
                return current_user_can( 'edit_posts' );
            }
        ));

        // Custom Social Template CRUD endpoints
        register_rest_route($namespace, 'custom-templates/(?P<post_id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_custom_templates'),
            'permission_callback' => function() {
                return current_user_can( 'edit_posts' );
            },
            'args' => array(
                'post_id' => array(
                    'required' => true,
                    'validate_callback' => function($param, $request, $key) {
                        return is_numeric($param);
                    }
                ),
            ),
        ));

        register_rest_route($namespace, 'custom-templates/(?P<post_id>\d+)', array(
            'methods' => 'POST',
            'callback' => array($this, 'save_custom_template'),
            'permission_callback' => function() {
                return current_user_can( 'edit_posts' );
            },
            'args' => array(
                'post_id' => array(
                    'required' => true,
                    'validate_callback' => function($param, $request, $key) {
                        return is_numeric($param);
                    }
                ),
                'platform' => array(
                    'required' => true,
                    'validate_callback' => function($param, $request, $key) {
                        return in_array($param, ['facebook', 'twitter', 'linkedin', 'pinterest', 'instagram', 'medium', 'threads']);
                    }
                ),
                'template' => array(
                    'required' => true,
                    // 'sanitize_callback' => 'sanitize_textarea_field'
                ),
            ),
        ));

        register_rest_route($namespace, 'custom-templates/(?P<post_id>\d+)', array(
            'methods' => 'DELETE',
            'callback' => array($this, 'delete_custom_template'),
            'permission_callback' => function() {
                return current_user_can( 'edit_posts' );
            },
            'args' => array(
                'post_id' => array(
                    'required' => true,
                    'validate_callback' => function($param, $request, $key) {
                        return is_numeric($param);
                    }
                ),
                'platform' => array(
                    'required' => true,
                    'validate_callback' => function($param, $request, $key) {
                        return in_array($param, ['facebook', 'twitter', 'linkedin', 'pinterest', 'instagram', 'medium', 'threads']);
                    }
                ),

            ),
        ));

        register_rest_route($namespace,'get-categories',array(
            'methods' => 'GET',
            'callback'   => array($this, 'wpsp_get_categories'),
            'permission_callback' => function() {
                return current_user_can( 'edit_posts' );
            }
        ));
        register_rest_route($namespace,'update-refresh-token',array(
            'methods' => 'POST',
            'callback'   => array($this, 'wpsp_update_refresh_token'),
            'permission_callback' => function() {
                return current_user_can( 'edit_posts' );
            }
        ));
    }


    public function wpsp_update_refresh_token(\WP_REST_Request $request) {
        $platform = $request->get_param('platform');
        $item     = $request->get_param('item');
        $response = ReconnectHandler::handleProfileReconnect($platform, $item);
        die();
    }

    public function wpsp_get_categories(\WP_REST_Request $request)
    {
        $limit      = $request->get_param('limit') ?: 10;
        $page       = $request->get_param('page') ?: 1;
        $categories = $this->get_options_with_pagination($limit, $page);
        return rest_ensure_response($categories);
    }

   /**
     * Get categories with pagination.
     *
     * @param int $limit Number of items per page.
     * @param int $page  Current page number.
     *
     * @return array List of categories with the desired format.
     */
    function get_options_with_pagination($limit, $page)
    {
        $allowed_post_types = \WPSP\Helper::get_all_allowed_post_type(); // Fetch allowed post types
        $result = ['result' => []];
        $offset = ($page - 1) * $limit;

        foreach ($allowed_post_types as $post_type) {
            $taxonomies = get_object_taxonomies($post_type); // Get taxonomies for each post type

            foreach ($taxonomies as $taxonomy) {
                $args = [
                    'taxonomy'   => $taxonomy,
                    'hide_empty' => false, // Include terms without posts
                    'number'     => $limit,
                    'offset'     => $offset,
                ];

                $terms = get_terms($args);

                if (!is_wp_error($terms)) {
                    foreach ($terms as $term) {
                        $result['result'][] = [
                            'term_id'  => $term->term_id,
                            'label'    => $term->name,
                            'slug'     => $term->slug,
                            'taxonomy' => $term->taxonomy,
                            'postType' => $post_type,
                            'value'    => $post_type . '.' . $term->taxonomy . '.' . $term->slug
                        ];
                    }
                }
            }
        }
        return $result['result']; // Returning the 'result' key
    }

    // Instant social share
    public function wpsp_instant_social_share( $data )
    {
        do_action('wpsp_instant_social_single_profile_share', $data->get_params());
    }

    /**
     * Get custom templates for a post
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function get_custom_templates( $request ) {
        $post_id = $request->get_param('post_id');
        // Verify post exists and user can edit it
        if (!get_post($post_id) || !current_user_can('edit_post', $post_id)) {
            return new \WP_REST_Response(array(
                'success' => false,
                'message' => __('Post not found or insufficient permissions.', 'wp-scheduled-posts')
            ), 403);
        }

        // Get templates
        $templates = $this->get_simple_templates($post_id);

        return new \WP_REST_Response(array(
            'success' => true,
            'data' => $templates
        ), 200);
    }

    /**
     * Save custom template for a post-profile combination
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function save_custom_template( $request ) {

        // If post is published then add cron jobs to share on social media now
        // If post is scheduled then add cron jobs to share on social media on scheduled date or after post is published
        // If post is draft then do not add cron jobs

        $post_id = $request->get_param('post_id');
        $platform = $request->get_param('platform');
        $template = $request->get_param('template');
        $profiles = $request->get_param('profiles');
        $scheduling_data = $request->get_param('scheduling');

        // Verify post exists and user can edit it
        if (!get_post($post_id) || !current_user_can('edit_post', $post_id)) {
            return new \WP_REST_Response(array(
                'success' => false,
                'message' => __('Post not found or insufficient permissions.', 'wp-scheduled-posts')
            ), 403);
        }

        // Validate template content
        $validation_result = $this->validate_template_content($template, $platform);
        if (!$validation_result['valid']) {
            return new \WP_REST_Response(array(
                'success' => false,
                'message' => $validation_result['message']
            ), 400);
        }

        // Get existing templates
        $templates = $this->get_simple_templates($post_id);

        // Save template and profiles for platform
        $templates[$platform] = [
            'template' => $template,
            'profiles' => $profiles
        ];

        // Update custom templates post meta
        $template_updated = update_post_meta($post_id, '_wpsp_custom_templates', $templates);

        // Handle scheduling data
        $scheduling_updated = false;
        if (is_array($scheduling_data)) {
            $scheduling_updated = update_post_meta($post_id, '_wpsp_social_scheduling', $scheduling_data);
            $template_updated = true;
            // If post is published then add cron jobs to share on social media now
            // let's write a function to handle this
            // get status of post from request
            if (get_post_status($post_id) === 'publish') {
                $set_schedule_at = $this->handle_published_post_scheduling($post_id, $scheduling_data);
                
                if ($set_schedule_at) {
                    $schedule_at = Helper::getDateFromTimezone($set_schedule_at, 'U', true);
                    $existing_timestamp = wp_next_scheduled('publish_future_post', array($post_id));
                    // If found, remove it
                    if ($existing_timestamp) {
                        wp_unschedule_event($existing_timestamp, 'publish_future_post', array($post_id));
                    }
            
                    // Schedule the new one
                    wp_schedule_single_event($schedule_at, 'publish_future_post', array($post_id));
                }
            }
        }

        if ($template_updated !== false || $scheduling_updated !== false) {
            return new \WP_REST_Response(array(
                'success' => true,
                'message' => __('Template and scheduling saved successfully.', 'wp-scheduled-posts'),
                'data' => [
                    'templates' => $templates,
                    'scheduling' => $scheduling_data
                ]
            ), 200);
        } else {
            return new \WP_REST_Response(array(
                'success' => false,
                'message' => __('Failed to save template and/or scheduling.', 'wp-scheduled-posts')
            ), 500);
        }
    }

    public function handle_published_post_scheduling($post_id, $scheduling_data) {
        if (!empty($scheduling_data)) {
            return \WPSP\Helpers\CustomTemplateHelper::get_scheduled_datetime($scheduling_data);
        }
        return false;
    }

    /**
     * Delete custom template for a post-profile combination
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function delete_custom_template( $request ) {
        $post_id = $request->get_param('post_id');
        $platform = $request->get_param('platform');

        // Verify post exists and user can edit it
        if (!get_post($post_id) || !current_user_can('edit_post', $post_id)) {
            return new \WP_REST_Response(array(
                'success' => false,
                'message' => __('Post not found or insufficient permissions.', 'wp-scheduled-posts')
            ), 403);
        }

        // Get existing templates
        $templates = $this->get_simple_templates($post_id);

        // Check if platform template exists
        if (!isset($templates[$platform]) || empty($templates[$platform])) {
            return new \WP_REST_Response(array(
                'success' => false,
                'message' => __('Template not found.', 'wp-scheduled-posts')
            ), 404);
        }

        // Remove template and profiles for platform
        $templates[$platform] = ['template' => '', 'profiles' => []];

        // Update post meta
        $updated = update_post_meta($post_id, '_wpsp_custom_templates', $templates);

        if ($updated !== false) {
            return new \WP_REST_Response(array(
                'success' => true,
                'message' => __('Template deleted successfully.', 'wp-scheduled-posts'),
                'data' => $templates
            ), 200);
        } else {
            return new \WP_REST_Response(array(
                'success' => false,
                'message' => __('Failed to delete template.', 'wp-scheduled-posts')
            ), 500);
        }
    }

    /**
     * Validate template content based on platform limits
     *
     * @param string $template
     * @param string $platform
     * @return array
     */
    private function validate_template_content( $template, $platform ) {
        // Platform character limits
        $limits = array(
            'twitter' => 280,
            'facebook' => 63206,
            'linkedin' => 3000,
            'pinterest' => 500,
            'instagram' => 2200,
            'medium' => 100000,
            'threads' => 500
        );

        // Check if template is empty
        if (empty(trim($template))) {
            return array(
                'valid' => false,
                'message' => __('Template cannot be empty.', 'wp-scheduled-posts')
            );
        }

        // Check character limit for platform
        $limit = isset($limits[$platform]) ? $limits[$platform] : 1000;
        if (strlen($template) > $limit) {
            return array(
                'valid' => false,
                'message' => sprintf(
                    __('Template exceeds character limit for %s (%d/%d characters).', 'wp-scheduled-posts'),
                    ucfirst($platform),
                    strlen($template),
                    $limit
                )
            );
        }

        // Validate placeholder syntax
        $valid_placeholders = array('{title}', '{content}', '{url}', '{tags}');
        preg_match_all('/\{[^}]+\}/', $template, $matches);
        if (!empty($matches[0])) {
            foreach ($matches[0] as $placeholder) {
                if (!in_array($placeholder, $valid_placeholders)) {
                    return array(
                        'valid' => false,
                        'message' => sprintf(
                            __('Invalid placeholder "%s". Valid placeholders are: %s', 'wp-scheduled-posts'),
                            $placeholder,
                            implode(', ', $valid_placeholders)
                        )
                    );
                }
            }
        }

        return array(
            'valid' => true,
            'message' => __('Template is valid.', 'wp-scheduled-posts')
        );
    }

    /**
     * Get templates with simple platform-based structure
     *
     * @param int $post_id
     * @return array
     */
    private function get_simple_templates( $post_id ) {
        $templates = get_post_meta($post_id, '_wpsp_custom_templates', true);

        $default_platform_data = ['template' => '', 'profiles' => []];

        // Base structure for all platforms, initialized with default data
        $all_platforms_default = [
            'facebook'  => $default_platform_data,
            'twitter'   => $default_platform_data,
            'linkedin'  => $default_platform_data,
            'pinterest' => $default_platform_data,
            'instagram' => $default_platform_data,
            'medium'    => $default_platform_data,
            'threads'   => $default_platform_data,
        ];

        // If no templates or not an array, return the default structure
        if (empty($templates) || !is_array($templates)) {
            return $all_platforms_default;
        }

        $adapted_templates = [];
        foreach ($templates as $platform => $platform_data) {
            if (is_string($platform_data)) {
                // Convert old string format to new object format
                $adapted_templates[$platform] = ['template' => $platform_data, 'profiles' => []];
            } elseif (is_array($platform_data) && (isset($platform_data['template']) || isset($platform_data['profiles'])) ) {
                // Already in new format, ensure keys exist
                $adapted_templates[$platform] = [
                    'template' => isset($platform_data['template']) ? $platform_data['template'] : '',
                    'profiles' => isset($platform_data['profiles']) && is_array($platform_data['profiles']) ? $platform_data['profiles'] : []
                ];
            } else {
                // Fallback for unexpected types, use default for this platform
                $adapted_templates[$platform] = $default_platform_data;
            }
        }

        // Merge adapted templates with default structure to ensure all platforms are present
        $final_templates = array_merge($all_platforms_default, $adapted_templates);
        
        // Update post meta if a migration occurred (i.e., old string formats were found)
        // This prevents re-saving if the data is already in the new format.
        $current_meta = get_post_meta($post_id, '_wpsp_custom_templates', true);
        if (json_encode($current_meta) !== json_encode($final_templates)) {
            update_post_meta($post_id, '_wpsp_custom_templates', $final_templates);
        }

        error_log('WPSP Debug: Final templates from get_simple_templates: ' . print_r($final_templates, true));

        return $final_templates;
    }

    public function wpsp_get_options_data( $request ) {
        $option_value = get_option('wpsp_settings_v5');
        
        if ($option_value !== false) {
            $option_value = json_decode($option_value, true);
            // Check and process `linkedin_profile_list` if it exists
            if (isset($option_value['linkedin_profile_list']) && is_array($option_value['linkedin_profile_list'])) {
                $option_value['linkedin_profile_list'] = array_map(function($profile) {
                    if (isset($profile['__id']) && isset($profile['id'])) {
                        $profile['id'] = $profile['__id'];
                        unset($profile['__id']);
                    }
                    if (!isset($profile['thumbnail_url']) || $profile['thumbnail_url'] === null) {
                        $profile['thumbnail_url'] = '';
                    }
                    return $profile;
                }, $option_value['linkedin_profile_list']);
            }

            // set default thumanil url 
            $social_media_lists = [
                'facebook_profile_list',
                'twitter_profile_list',
                'instagram_profile_list',
                'pinterest_profile_list',
                'threads_profile_list',
                'medium_profile_list',
            ];
            
            foreach ($social_media_lists as $list_key) {
                if (isset($option_value[$list_key]) && is_array($option_value[$list_key])) {
                    $option_value[$list_key] = array_map(function($profile) {
                        // Set default value for thumbnail_url if null
                        if (!isset($profile['thumbnail_url']) || $profile['thumbnail_url'] === null) {
                            $profile['thumbnail_url'] = '';
                        }
                        return $profile;
                    }, $option_value[$list_key]);
                }
            }
            $option_value = json_encode($option_value);
            return rest_ensure_response($option_value);
        } else {
            return new \WP_Error('option_not_found', 'Option not found', array('status' => 40));
        }
    }
    

    /**
     * Return an instance of this class.
     *
     * @since     0.8.1
     *
     * @return    object    A single instance of this class.
     */
    public static function get_instance()
    {

        // If the single instance hasn't been set, set it now.
        if (null == self::$instance) {
            self::$instance = new self;
        }

        return self::$instance;
    }


    /**
     * Register the routes for the objects of the controller.
     */
    public function register_routes()
    {
        $namespace = WPSP_PLUGIN_SLUG . '/v1';
        $endpoint = apply_filters('wpsp_rest_endpoint', '/settings/');

        register_rest_route($namespace, $endpoint, array(
            array(
                'methods'               => \WP_REST_Server::READABLE,
                'callback'              => array($this, 'get_value'),
                'permission_callback'   => array($this, 'wpsp_permissions_check'),
                'args'                  => array(),
            ),
        ));

        register_rest_route($namespace, $endpoint, array(
            array(
                'methods'               => \WP_REST_Server::CREATABLE,
                'callback'              => array($this, 'update_value'),
                'permission_callback'   => array($this, 'wpsp_permissions_check'),
                'args'                  => array(),
            ),
        ));

        register_rest_route($namespace, $endpoint, array(
            array(
                'methods'               => \WP_REST_Server::EDITABLE,
                'callback'              => array($this, 'update_value'),
                'permission_callback'   => array($this, 'wpsp_permissions_check'),
                'args'                  => array(),
            ),
        ));

        register_rest_route($namespace, $endpoint, array(
            array(
                'methods'               => \WP_REST_Server::DELETABLE,
                'callback'              => array($this, 'delete_value'),
                'permission_callback'   => array($this, 'wpsp_permissions_check'),
                'args'                  => array(),
            ),
        ));

        register_rest_route($namespace, 'fetch_pinterest_section', array(
            array(
                'methods'               => \WP_REST_Server::EDITABLE,
                'callback'              => array($this, 'fetch_pinterest_section'),
                'permission_callback'   => array($this, 'wpsp_permissions_check'),
                'args'                  => array(),
            ),
        ));

        register_rest_route( $namespace, '/save-profile', array(
            'methods'             => 'POST',
            'callback'            => [$this, 'save_profile'],
            'permission_callback' => [$this, 'wpsp_permissions_check'],
        ));

    }


    public function save_profile($request)
    {
        $platform        = $request->get_param('platform');
        $profiles        = $request->get_param('profiles');
        foreach ($profiles as $profile) {
            do_action("wpsp_profile_reconnect_{$platform}", [ 'id' => $profile ] );
        }
        
    }


    /**
     * Fetch pinterest section
     *
     * @param $data
    */
    public function fetch_pinterest_section($data)
    {
       do_action('social_profile_fetch_pinterest_section', $data->get_params());
    }

    /**
     * Get wpsp
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|WP_REST_Request
     */
    public function get_value($request)
    {
        $wpsp_option = get_option($this->settings_name);
        // Don't return false if there is no option
        if (!$wpsp_option) {
            return new \WP_REST_Response(array(
                'success' => true,
                'value' => ''
            ), 200);
        }

        return new \WP_REST_Response(array(
            'success' => true,
            'value'   => $wpsp_option,
        ), 200);
    }

    /**
     * Create OR Update wpsp
     *
     * @param \WP_REST_Request $request Full data about the request.
     * @return \WP_Error|\WP_REST_Request
     */
    public function update_value($request)
    {
        $settings = $request->get_params();
        $arr = ['allow_post_types', 'allow_categories', 'allow_user_by_role'];
        $settingObject = WPSP_Start()->getAdmin()->load_settings();

        $settings_arr = $settingObject->get_settings_array();
        $defaults = $settingObject->get_field_names($settings_arr['tabs']);

        foreach($arr as $key){
            if(!empty($defaults[$key]) && empty($settings[$key])){
                $settings[$key] = $defaults[$key];
            }
        }

        $settings = apply_filters('wpsp_settings_before_save', $settings);
        $updated  = update_option($this->settings_name, json_encode($settings));

        return new \WP_REST_Response(array(
            'success'   => $updated,
            'value'     => $request->get_params()
        ), 200);
    }

    /**
     * Delete wpsp
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|WP_REST_Request
     */
    public function delete_value($request)
    {
        $deleted = delete_option($this->settings_name);

        return new \WP_REST_Response(array(
            'success'   => $deleted,
            'value'     => ''
        ), 200);
    }

    /**
     * Check if a given request has access to update a setting
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|bool
     */
    public function wpsp_permissions_check($request)
    {
        return current_user_can('manage_options');
    }

}
