<?php

namespace WPSP\API;
use WPSP\Helper;

/**
 * Custom Social Templates API Handler
 * 
 * Handles all custom social template related functionality including:
 * - REST API endpoints for CRUD operations
 * - Meta field registration
 * - Template validation
 * - Scheduling functionality
 * 
 * @since 2.6.0
 */
class CustomSocialTemplates
{
    /**
     * Instance of this class.
     *
     * @since    2.6.0
     *
     * @var      object
     */
    protected static $instance = null;

    /**
     * Initialize hooks
     */
    private function __construct()
    {
        $this->do_hooks();
    }

    /**
     * Set up WordPress hooks and filters
     *
     * @return void
     */
    public function do_hooks()
    {
        add_action('rest_api_init', array($this, 'register_custom_template_routes'));
        add_action('rest_api_init', array($this, 'register_custom_template_meta'));
        add_action('wp_insert_post', array($this, 'initialize_custom_templates_meta'), 10, 2);

        // Dynamic scheduling hooks
        // add_action('transition_post_status', array($this, 'handle_post_status_change'), 10, 3);
        add_action('post_updated', array($this, 'handle_post_update'), 10, 3);
    }

    /**
     * Register custom template related meta fields
     */
    public function register_custom_template_meta()
    {
        $allow_post_types = Helper::get_all_allowed_post_type();
        $allow_post_types = (!empty($allow_post_types) ? $allow_post_types : array('post'));
        
        foreach ($allow_post_types as $type) {
            // Register enable/disable meta for Add Social Template
            register_post_meta(
                $type,
                '_wpsp_enable_custom_social_template',
                [
                    'show_in_rest' => true,
                    'single' => true,
                    'type' => 'boolean',
                    'default' => false,
                    'auth_callback' => function() {
                        return current_user_can( 'edit_posts' );
                    }
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
                                'dateOption' => ['type' => 'string'],
                                'timeOption' => ['type' => 'string'],
                                'customDays' => ['type' => 'string'],
                                'customHours' => ['type' => 'string'],
                                'customDate' => ['type' => 'string'],
                                'customTime' => ['type' => 'string'],
                                'schedulingType' => ['type' => 'string'],
                            ],
                        ]
                    ],
                    'single' => true,
                    'type' => 'object',
                    'default' => [
                        'enabled' => false,
                        'datetime' => null,
                        'platforms' => [],
                        'status' => 'template_only',
                        'dateOption' => 'today',
                        'timeOption' => 'now',
                        'customDays' => '',
                        'customHours' => '',
                        'customDate' => '',
                        'customTime' => '',
                        'schedulingType' => 'absolute'
                    ],
                    'auth_callback' => function() {
                        return current_user_can( 'edit_posts' );
                    }
                ]
            );

            register_post_meta( $type, '_wpsp_active_default_template', [
                'type'         => 'boolean',
                'single'       => true,
                'show_in_rest' => true,
                'default'      => true,
                'auth_callback' => function() {
                    return current_user_can( 'edit_posts' );
                },
            ] );
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
        $allow_post_types = Helper::get_all_allowed_post_type();
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
            'facebook' => ['template' => '', 'profiles' => [], 'is_global' => false],
            'twitter' => ['template' => '', 'profiles' => [], 'is_global' => false],
            'linkedin' => ['template' => '', 'profiles' => [], 'is_global' => false],
            'pinterest' => ['template' => '', 'profiles' => [], 'is_global' => false],
            'instagram' => ['template' => '', 'profiles' => [], 'is_global' => false],
            'medium' => ['template' => '', 'profiles' => [], 'is_global' => false],
            'threads' => ['template' => '', 'profiles' => [], 'is_global' => false]
        );

        update_post_meta($post_id, '_wpsp_custom_templates', $default_templates);
    }

    /**
     * Handle post status changes for dynamic scheduling
     *
     * @param string $new_status
     * @param string $old_status
     * @param WP_Post $post
     */
    public function handle_post_status_change($new_status, $old_status, $post) {
        // @todo 
    }

    /**
     * Handle post updates for dynamic scheduling
     *
     * @param int $post_id
     * @param WP_Post $post_after
     * @param WP_Post $post_before
     */
    public function handle_post_update($post_id, $post_after, $post_before) {
        // Only process allowed post types
        $allow_post_types = Helper::get_all_allowed_post_type();
        if (!in_array($post_after->post_type, $allow_post_types)) {
            return;
        }

        // Get scheduling data
        $scheduling_data = get_post_meta($post_id, '_wpsp_social_scheduling', true);
        if ( $post_after->post_status === 'future' && $post_before->post_date !== $post_after->post_date ) {
            $this->handle_scheduled_post_scheduling($post_id, $scheduling_data, $post_after);
        }
    }


    /**
     * Register custom template REST API routes
     */
    public function register_custom_template_routes()
    {
        $namespace = WPSP_PLUGIN_SLUG . '/v1';

        // Get custom templates
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

        // Save custom template (supports both single platform and batch operations)
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
                // For backward compatibility - single platform mode
                'platform' => array(
                    'required' => false,
                    'validate_callback' => function($param, $request, $key) {
                        return in_array($param, ['facebook', 'twitter', 'linkedin', 'pinterest', 'instagram', 'medium', 'threads']);
                    }
                ),
                'template' => array(
                    'required' => false,
                ),
                'is_global' => array(
                    'required' => false,
                    'default' => false,
                ),
            ),
        ));

        // Delete custom template
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
     * Save custom template for a post-profile combination (supports batch processing)
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function save_custom_template( $request ) {
        // Get request params
        $post_id = $request->get_param('post_id');
        $scheduling_data = $request->get_param('scheduling');

        // Check if this is batch mode (multiple platforms) or single platform mode
        $platforms_data = $request->get_param('platforms');
        $single_platform = $request->get_param('platform');

        // Verify post exists and user can edit it
        if (!get_post($post_id) || !current_user_can('edit_post', $post_id)) {
            return new \WP_REST_Response(array(
                'success' => false,
                'message' => __('Post not found or insufficient permissions.', 'wp-scheduled-posts')
            ), 403);
        }

        // Get existing templates
        $templates = $this->get_simple_templates($post_id);
        $validation_errors = [];
        $updated_platforms = [];

        if (!empty($platforms_data) && is_array($platforms_data)) {
            // Batch mode - process multiple platforms
            foreach ($platforms_data as $platform_data) {
                $result = $this->process_single_platform_data($platform_data, $templates, $validation_errors);
                if ($result['success']) {
                    $updated_platforms[] = $result['platform'];
                }
            }
        } elseif (!empty($single_platform)) {
            // Single platform mode (backward compatibility)
            $platform_data = [
                'platform' => $single_platform,
                'template' => $request->get_param('template'),
                'profiles' => $request->get_param('profiles'),
                'is_global' => $request->get_param('is_global')
            ];

            $result = $this->process_single_platform_data($platform_data, $templates, $validation_errors);
            if ($result['success']) {
                $updated_platforms[] = $result['platform'];
            }
        } else {
            return new \WP_REST_Response(array(
                'success' => false,
                'message' => __('No platform data provided. Use either "platform" for single mode or "platforms" for batch mode.', 'wp-scheduled-posts')
            ), 400);
        }

        // If there were validation errors, return them
        if (!empty($validation_errors)) {
            return new \WP_REST_Response(array(
                'success' => false,
                'message' => __('Validation errors occurred.', 'wp-scheduled-posts'),
                'errors' => $validation_errors
            ), 400);
        }

        // Update custom templates post meta
        $template_updated = update_post_meta($post_id, '_wpsp_custom_templates', $templates);

        // Handle scheduling data
        $scheduling_updated = false;
        if (is_array($scheduling_data)) {
            $scheduling_updated = update_post_meta($post_id, '_wpsp_social_scheduling', $scheduling_data);
            $template_updated = true;
            // get status of post from request
            if (get_post_status($post_id) === 'publish') {
                $this->handle_published_post_scheduling($post_id, $scheduling_data);
            } elseif ( get_post_status($post_id)  === 'future') {
                // For scheduled posts, calculate the social media timing based on the post's scheduled publication date
                $this->handle_scheduled_post_scheduling($post_id, $scheduling_data);
            }
        }

        if ($template_updated !== false || $scheduling_updated !== false) {
            $message = count($updated_platforms) > 1
                ? sprintf(__('Templates and scheduling saved successfully for %d platforms.', 'wp-scheduled-posts'), count($updated_platforms))
                : __('Template and scheduling saved successfully.', 'wp-scheduled-posts');

            return new \WP_REST_Response(array(
                'success' => true,
                'message' => $message,
                'data' => [
                    'templates' => $templates,
                    'scheduling' => $scheduling_data,
                    'updated_platforms' => $updated_platforms
                ]
            ), 200);
        } else {
            return new \WP_REST_Response(array(
                'success' => false,
                'message' => __('Failed to save template and/or scheduling.', 'wp-scheduled-posts')
            ), 500);
        }
    }

    /**
     * Process single platform data for batch or individual operations
     *
     * @param array $platform_data
     * @param array &$templates
     * @param array &$validation_errors
     * @return array
     */
    private function process_single_platform_data($platform_data, &$templates, &$validation_errors) {
        $platform = $platform_data['platform'] ?? '';
        $template = $platform_data['template'] ?? '';
        $profiles = $platform_data['profiles'] ?? [];
        $is_global = $platform_data['is_global'] ?? false;

        // Validate platform
        $valid_platforms = ['facebook', 'twitter', 'linkedin', 'pinterest', 'instagram', 'medium', 'threads'];
        if (!in_array($platform, $valid_platforms)) {
            $validation_errors[] = sprintf(__('Invalid platform: %s', 'wp-scheduled-posts'), $platform);
            return ['success' => false, 'platform' => $platform];
        }

        // Skip empty templates and profiles
        if (empty(trim($template)) && empty($profiles)) {
            return ['success' => true, 'platform' => $platform]; // Skip but don't error
        }

        // Validate template content if not empty
        if (!empty(trim($template))) {
            $validation_result = $this->validate_template_content($template, $platform);
            if (!$validation_result['valid']) {
                $validation_errors[] = sprintf(__('%s: %s', 'wp-scheduled-posts'), ucfirst($platform), $validation_result['message']);
                return ['success' => false, 'platform' => $platform];
            }
        }

        // Save template and profiles for platform
        $templates[$platform] = [
            'template' => $template,
            'profiles' => is_array($profiles) ? $profiles : [],
            'is_global' => $is_global ? 1 : ''
        ];

        return ['success' => true, 'platform' => $platform];
    }

    public function handle_scheduled_post_scheduling($post_id, $scheduling_data, $post = null) {
        if (empty($scheduling_data)) {
            return false;
        }
    
        // Get the post object
        $post = $post ? $post : get_post($post_id);
        if (!$post) {
            return false;
        }
    
        // Calculate the social sharing datetime based on the post's scheduled publication date
        $social_datetime = \WPSP\Helpers\CustomTemplateHelper::get_scheduled_datetime(
            $scheduling_data,
            $post->post_date_gmt
        );
    
        if (!$social_datetime) {
            return false;
        }
    
        // Update scheduling data
        $scheduling_data['datetime'] = $social_datetime;
        $scheduling_data['enabled'] = true;
        $scheduling_data['status'] = 'pending_publication';
    
        update_post_meta($post_id, '_wpsp_social_scheduling', $scheduling_data);
    
        // Schedule the cron event
        $timestamp = (new \DateTime($social_datetime, new \DateTimeZone('UTC')))->getTimestamp();
        $hook = 'wpsp_publish_future_post';
        $args = [intval($post_id)];
    
        // Remove previously scheduled event if any
        if ($existing = wp_next_scheduled($hook, $args)) {
            wp_unschedule_event($existing, $hook, $args);
        }
    
        wp_schedule_single_event($timestamp, $hook, $args);
    
        return $social_datetime;
    }    

    public function handle_published_post_scheduling($post_id, $scheduling_data) {
        if (empty($scheduling_data)) {
            return false;
        }

        $event_hook = 'wpsp_publish_future_post';
        // For absolute scheduling on published posts, use current time as base
        $datetime_str = \WPSP\Helpers\CustomTemplateHelper::get_scheduled_datetime($scheduling_data);
        if (!$datetime_str) {
            return;
        }
    
        $datetime_obj = \DateTime::createFromFormat('Y-m-d H:i:s', $datetime_str, new \DateTimeZone('UTC'));
        if (!$datetime_obj) {
            return;
        }
        $args = array(intval($post_id));
        $timestamp = $datetime_obj->getTimestamp();
        // Unschedule if an existing event is already scheduled
        $scheduled_timestamp = wp_next_scheduled($event_hook, $args);
        if ($scheduled_timestamp !== false) {
            wp_unschedule_event($scheduled_timestamp, $event_hook, $args);
        }
        // Schedule the new event
        wp_schedule_single_event($timestamp, $event_hook, $args);
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

        $default_platform_data = ['template' => '', 'profiles' => [], 'is_global' => false];

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
                $adapted_templates[$platform] = ['template' => $platform_data, 'profiles' => [], 'is_global' => false];
            } elseif (is_array($platform_data) && (isset($platform_data['template']) || isset($platform_data['profiles']) || isset($platform_data['is_global'])) ) {
                // Already in new format, ensure keys exist
                $adapted_templates[$platform] = [
                    'template' => isset($platform_data['template']) ? $platform_data['template'] : '',
                    'profiles' => isset($platform_data['profiles']) && is_array($platform_data['profiles']) ? $platform_data['profiles'] : [],
                    'is_global' => isset($platform_data['is_global']) ? $platform_data['is_global'] : false
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

        return $final_templates;
    }

    /**
     * Return an instance of this class.
     *
     * @since     2.6.0
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
}
