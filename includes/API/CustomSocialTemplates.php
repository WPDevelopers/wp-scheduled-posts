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
        add_action('transition_post_status', array($this, 'handle_post_status_change'), 10, 3);
        add_action('post_updated', array($this, 'handle_post_update'), 10, 3);
        add_action('before_delete_post', array($this, 'cleanup_scheduled_events'));
        add_action('wpsp_recalculate_social_scheduling', array($this, 'recalculate_social_scheduling'), 10, 2);

        // Custom action for manual rescheduling
        add_action('wpsp_reschedule_social_posts', array($this, 'reschedule_social_posts'), 10, 3);
    }

    /**
     * Register custom template related meta fields
     */
    public function register_custom_template_meta()
    {
        $allow_post_types = Helper::get_all_allowed_post_type();
        $allow_post_types = (!empty($allow_post_types) ? $allow_post_types : array('post'));
        
        foreach ($allow_post_types as $type) {
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
                                        'is_global' => ['type' => 'boolean'],
                                    ],
                                    'default' => ['template' => '', 'profiles' => [], 'is_global' => false],
                                ],
                                'twitter' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'template' => ['type' => 'string'],
                                        'profiles' => ['type' => 'array', 'items' => ['type' => ['string', 'integer']]],
                                        'is_global' => ['type' => 'boolean'],
                                    ],
                                    'default' => ['template' => '', 'profiles' => [], 'is_global' => false],
                                ],
                                'linkedin' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'template' => ['type' => 'string'],
                                        'profiles' => ['type' => 'array', 'items' => ['type' => ['string', 'integer']]],
                                        'is_global' => ['type' => 'boolean'],
                                    ],
                                    'default' => ['template' => '', 'profiles' => [], 'is_global' => false],
                                ],
                                'pinterest' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'template' => ['type' => 'string'],
                                        'profiles' => ['type' => 'array', 'items' => ['type' => ['string', 'integer']]],
                                        'is_global' => ['type' => 'boolean'],
                                    ],
                                    'default' => ['template' => '', 'profiles' => [], 'is_global' => false],
                                ],
                                'instagram' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'template' => ['type' => 'string'],
                                        'profiles' => ['type' => 'array', 'items' => ['type' => ['string', 'integer']]],
                                        'is_global' => ['type' => 'boolean'],
                                    ],
                                    'default' => ['template' => '', 'profiles' => [], 'is_global' => false],
                                ],
                                'medium' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'template' => ['type' => 'string'],
                                        'profiles' => ['type' => 'array', 'items' => ['type' => ['string', 'integer']]],
                                        'is_global' => ['type' => 'boolean'],
                                    ],
                                    'default' => ['template' => '', 'profiles' => [], 'is_global' => false],
                                ],
                                'threads' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'template' => ['type' => 'string'],
                                        'profiles' => ['type' => 'array', 'items' => ['type' => ['string', 'integer']]],
                                        'is_global' => ['type' => 'boolean'],
                                    ],
                                    'default' => ['template' => '', 'profiles' => [], 'is_global' => false],
                                ],
                            ],
                            'default' => [
                                'facebook' => ['template' => '', 'profiles' => [], 'is_global' => false],
                                'twitter' => ['template' => '', 'profiles' => [], 'is_global' => false],
                                'linkedin' => ['template' => '', 'profiles' => [], 'is_global' => false],
                                'pinterest' => ['template' => '', 'profiles' => [], 'is_global' => false],
                                'instagram' => ['template' => '', 'profiles' => [], 'is_global' => false],
                                'medium' => ['template' => '', 'profiles' => [], 'is_global' => false],
                                'threads' => ['template' => '', 'profiles' => [], 'is_global' => false],
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
            
            // Register enable/disable meta for Add Social Template
            register_post_meta(
                $type,
                '_wpsp_enable_custom_social_template',
                [
                    'show_in_rest' => true,
                    'single' => true,
                    'type' => 'boolean',
                    'default' => true,
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
        // Only process allowed post types
        $allow_post_types = Helper::get_all_allowed_post_type();
        if (!in_array($post->post_type, $allow_post_types)) {
            return;
        }

        // Log the status change for debugging
        error_log("WPSP: Post {$post->ID} status changed from {$old_status} to {$new_status}");

        // Handle transitions to published status
        if ($new_status === 'publish' && in_array($old_status, ['draft', 'pending', 'future', 'auto-draft'])) {
            $this->handle_post_publication($post);
        }

        // Handle transitions from published to other statuses
        if ($old_status === 'publish' && $new_status !== 'publish') {
            $this->cleanup_scheduled_social_events($post->ID);
        }

        // Handle scheduled post becoming published (future -> publish)
        if ($old_status === 'future' && $new_status === 'publish') {
            $this->handle_scheduled_post_publication($post);
        }
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

        // Check if scheduled publication time changed
        if ($post_after->post_status === 'future' &&
            $post_before->post_date !== $post_after->post_date) {

            error_log("WPSP: Scheduled time changed for post {$post_id}");
            $this->handle_scheduled_time_change($post_id, $post_after, $post_before);
        }

        // Check if published post date changed
        if ($post_after->post_status === 'publish' &&
            $post_before->post_date !== $post_after->post_date) {

            error_log("WPSP: Published post date changed for post {$post_id}");
            $this->handle_published_post_date_change($post_id, $post_after, $post_before);
        }
    }

    /**
     * Handle when a draft/scheduled post gets published
     *
     * @param WP_Post $post
     */
    private function handle_post_publication($post) {
        $scheduling_data = get_post_meta($post->ID, '_wpsp_social_scheduling', true);
        $templates = get_post_meta($post->ID, '_wpsp_custom_templates', true);

        if (empty($scheduling_data) || empty($templates)) {
            return;
        }

        // Check if we have relative scheduling that needs conversion
        if (isset($scheduling_data['schedulingType']) && $scheduling_data['schedulingType'] === 'relative') {
            error_log("WPSP: Converting relative scheduling to absolute for post {$post->ID}");

            // Convert relative scheduling to absolute
            $absolute_datetime = $this->convert_relative_to_absolute_scheduling($scheduling_data, $post->post_date);

            if ($absolute_datetime) {
                // Update scheduling data to absolute
                $scheduling_data['schedulingType'] = 'absolute';
                $scheduling_data['datetime'] = $absolute_datetime;
                $scheduling_data['enabled'] = true;
                $scheduling_data['status'] = 'scheduled';

                update_post_meta($post->ID, '_wpsp_social_scheduling', $scheduling_data);

                // Schedule the social media posts
                $this->schedule_social_media_posts($post->ID, $absolute_datetime);
            }
        } else {
            // Handle existing absolute scheduling
            $this->recalculate_and_schedule($post->ID, $scheduling_data);
        }
    }

    /**
     * Handle when a scheduled post becomes published
     *
     * @param WP_Post $post
     */
    private function handle_scheduled_post_publication($post) {
        $scheduling_data = get_post_meta($post->ID, '_wpsp_social_scheduling', true);

        if (empty($scheduling_data)) {
            return;
        }

        // If we have relative scheduling, convert it now
        if (isset($scheduling_data['schedulingType']) && $scheduling_data['schedulingType'] === 'relative') {
            $this->handle_post_publication($post);
        } else {
            // For absolute scheduling, just ensure events are properly scheduled
            $this->recalculate_and_schedule($post->ID, $scheduling_data);
        }
    }

    /**
     * Handle when scheduled publication time changes
     *
     * @param int $post_id
     * @param WP_Post $post_after
     * @param WP_Post $post_before
     */
    private function handle_scheduled_time_change($post_id, $post_after, $post_before) {
        $scheduling_data = get_post_meta($post_id, '_wpsp_social_scheduling', true);

        if (empty($scheduling_data)) {
            return;
        }

        // Clean up old scheduled events
        $this->cleanup_scheduled_social_events($post_id);

        // For scheduled posts, we always recalculate based on the new publication time
        // This maintains the relative relationship regardless of scheduling type
        if (isset($scheduling_data['schedulingType']) && $scheduling_data['schedulingType'] === 'relative') {
            $new_absolute_datetime = $this->convert_relative_to_absolute_scheduling($scheduling_data, $post_after->post_date);

            if ($new_absolute_datetime) {
                // Update the calculated datetime but keep it as relative type
                $scheduling_data['datetime'] = $new_absolute_datetime;
                update_post_meta($post_id, '_wpsp_social_scheduling', $scheduling_data);
                // Don't schedule cron job yet - wait for actual publication
            } else {
                error_log("WPSP: Failed to recalculate relative scheduling for post {$post_id}");
            }
        } else {
            // Calculate the time difference
            $old_pub_time = strtotime($post_before->post_date);
            $new_pub_time = strtotime($post_after->post_date);
            $time_diff = $new_pub_time - $old_pub_time;

            if (isset($scheduling_data['datetime'])) {
                $old_social_time = strtotime($scheduling_data['datetime']);
                $new_social_time = $old_social_time + $time_diff;

                $scheduling_data['datetime'] = date('Y-m-d H:i:s', $new_social_time);
                update_post_meta($post_id, '_wpsp_social_scheduling', $scheduling_data);

            }
        }
    }

    /**
     * Handle when published post date changes
     *
     * @param int $post_id
     * @param WP_Post $post_after
     * @param WP_Post $post_before
     */
    private function handle_published_post_date_change($post_id, $post_after, $post_before) {
        $scheduling_data = get_post_meta($post_id, '_wpsp_social_scheduling', true);

        if (empty($scheduling_data)) {
            return;
        }

        // Calculate time difference
        $old_pub_time = strtotime($post_before->post_date);
        $new_pub_time = strtotime($post_after->post_date);
        $time_diff = $new_pub_time - $old_pub_time;

        // Clean up old scheduled events
        $this->cleanup_scheduled_social_events($post_id);

        // Adjust social media scheduling time
        if (isset($scheduling_data['datetime'])) {
            $old_social_time = strtotime($scheduling_data['datetime']);
            $new_social_time = $old_social_time + $time_diff;

            $scheduling_data['datetime'] = date('Y-m-d H:i:s', $new_social_time);
            update_post_meta($post_id, '_wpsp_social_scheduling', $scheduling_data);

            // Reschedule if the new time is in the future
            if ($new_social_time > time()) {
                $this->schedule_social_media_posts($post_id, $scheduling_data['datetime']);
            }

            error_log("WPSP: Rescheduled social media posts for post {$post_id} due to publication date change");
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

        // Get scheduling status
        register_rest_route($namespace, 'custom-templates/(?P<post_id>\d+)/scheduling-status', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_scheduling_status_endpoint'),
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

        // Manual reschedule endpoint
        register_rest_route($namespace, 'custom-templates/(?P<post_id>\d+)/reschedule', array(
            'methods' => 'POST',
            'callback' => array($this, 'manual_reschedule_endpoint'),
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
                'datetime' => array(
                    'required' => true,
                    'validate_callback' => function($param, $request, $key) {
                        return !empty($param);
                    }
                ),
            ),
        ));

        // Fix duplicate cron jobs endpoint
        register_rest_route($namespace, 'custom-templates/(?P<post_id>\d+)/fix-duplicates', array(
            'methods' => 'POST',
            'callback' => array($this, 'fix_duplicate_cron_jobs_endpoint'),
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

        // Force reschedule endpoint (for debugging)
        register_rest_route($namespace, 'custom-templates/(?P<post_id>\d+)/force-reschedule', array(
            'methods' => 'POST',
            'callback' => array($this, 'force_reschedule_endpoint'),
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
            // If post is published then add cron jobs to share on social media now
            // let's write a function to handle this
            // get status of post from request
            if (get_post_status($post_id) === 'publish') {
                $set_schedule_at = $this->handle_published_post_scheduling($post_id, $scheduling_data);
                
                if ($set_schedule_at) {
                    $seconds = \strtotime($set_schedule_at) - time();
                    $schedule_at = Helper::getDateFromTimezone($seconds, 'U', true);
                    $existing_timestamp = wp_next_scheduled('publish_future_post', array($post_id));
                    // If found, remove it
                    if ($existing_timestamp) {
                        wp_unschedule_event($existing_timestamp, 'publish_future_post', array($post_id));
                    }
            
                    // Schedule the new one
                    wp_schedule_single_event($schedule_at, 'publish_future_post', array($post_id));
                }
            } elseif ( get_post_status($post_id)  === 'future') {
                error_log("WPSP: Handling scheduling for SCHEDULED post {$post_id}");

                // For scheduled posts, calculate the social media timing based on the post's scheduled publication date
                $social_datetime = $this->handle_scheduled_post_scheduling($post_id, $scheduling_data);

                if ($social_datetime) {
                    error_log("WPSP: Calculated social media datetime for scheduled post: {$social_datetime}");

                    // Store the calculated datetime in the scheduling data
                    $scheduling_data['datetime'] = $social_datetime;
                    $scheduling_data['enabled'] = true;
                    $scheduling_data['status'] = 'pending_publication';

                    // Update the scheduling data with the calculated datetime
                    $scheduling_updated = update_post_meta($post_id, '_wpsp_social_scheduling', $scheduling_data);
                    error_log("WPSP: Updated scheduling data for scheduled post: " . ($scheduling_updated ? 'SUCCESS' : 'FAILED'));

                    $template_updated = true;
                    $scheduled_successfully = true;
                } else {
                    error_log("WPSP: ERROR - Failed to calculate social media datetime for scheduled post");
                    $scheduled_successfully = false;
                }
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

    public function handle_scheduled_post_scheduling($post_id, $scheduling_data) {
        if (empty($scheduling_data)) {
            error_log("WPSP: handle_scheduled_post_scheduling - No scheduling data provided");
            return false;
        }

        // Get the post to access its scheduled publication date
        $post = get_post($post_id);
        if (!$post) {
            error_log("WPSP: handle_scheduled_post_scheduling - Post {$post_id} not found");
            return false;
        }

        error_log("WPSP: handle_scheduled_post_scheduling - Processing for scheduled post {$post_id}");
        error_log("WPSP: Post scheduled publication date: " . $post->post_date);
        error_log("WPSP: Scheduling data: " . json_encode($scheduling_data));

        // For scheduled posts, use the post's publication date as the base datetime
        // This ensures that date/time calculations are relative to when the post will be published
        $result = \WPSP\Helpers\CustomTemplateHelper::get_scheduled_datetime($scheduling_data, $post->post_date);

        error_log("WPSP: handle_scheduled_post_scheduling result: " . ($result ?: 'null'));
        return $result;
    }

    public function handle_published_post_scheduling($post_id, $scheduling_data) {
        if (empty($scheduling_data)) {
            error_log("WPSP: handle_published_post_scheduling - No scheduling data provided");
            return false;
        }

        error_log("WPSP: handle_published_post_scheduling - Processing for published post {$post_id}");
        error_log("WPSP: Scheduling data: " . json_encode($scheduling_data));

        // Determine scheduling type
        $scheduling_type = isset($scheduling_data['schedulingType']) ? $scheduling_data['schedulingType'] : 'absolute';
        error_log("WPSP: Scheduling type: {$scheduling_type}");

        if ($scheduling_type === 'relative') {
            // For relative scheduling on published posts, convert to absolute first
            error_log("WPSP: Converting relative scheduling to absolute for published post");
            $post = get_post($post_id);
            if ($post) {
                $result = $this->convert_relative_to_absolute_scheduling($scheduling_data, $post->post_date);
                error_log("WPSP: Relative conversion result: " . ($result ?: 'null'));
                return $result;
            } else {
                error_log("WPSP: Failed to get post object for post {$post_id}");
                return false;
            }
        } else {
            // For absolute scheduling on published posts, use current time as base
            error_log("WPSP: Using absolute scheduling with current time as base");
            $result = \WPSP\Helpers\CustomTemplateHelper::get_scheduled_datetime($scheduling_data);
            error_log("WPSP: Absolute scheduling result: " . ($result ?: 'null'));
            return $result;
        }
    }

    /**
     * Handle scheduling for published posts
     *
     * @param int $post_id
     * @param array $scheduling_data
     * @return string|false
     */
    // public function handle_published_post_scheduling($post_id, $scheduling_data) {
    //     if (empty($scheduling_data)) {
    //         error_log("WPSP: handle_published_post_scheduling - No scheduling data provided");
    //         return false;
    //     }

    //     error_log("WPSP: handle_published_post_scheduling - Processing for post {$post_id}");
    //     error_log("WPSP: Scheduling data: " . json_encode($scheduling_data));

    //     // Check if we have a pre-calculated datetime (from relative conversion)
    //     if (isset($scheduling_data['datetime']) && !empty($scheduling_data['datetime'])) {
    //         error_log("WPSP: Using pre-calculated datetime: " . $scheduling_data['datetime']);
    //         return $scheduling_data['datetime'];
    //     }

    //     // Determine scheduling type
    //     $scheduling_type = isset($scheduling_data['schedulingType']) ? $scheduling_data['schedulingType'] : 'absolute';
    //     error_log("WPSP: Scheduling type: {$scheduling_type}");

    //     // For absolute scheduling, use the CustomTemplateHelper
    //     if ($scheduling_type === 'absolute') {
    //         error_log("WPSP: Using absolute scheduling via CustomTemplateHelper");
    //         $result = \WPSP\Helpers\CustomTemplateHelper::get_scheduled_datetime($scheduling_data);
    //         error_log("WPSP: CustomTemplateHelper result: " . ($result ?: 'null'));
    //         return $result;
    //     }

    //     // For relative scheduling on published posts, convert to absolute first
    //     if ($scheduling_type === 'relative') {
    //         error_log("WPSP: Converting relative scheduling to absolute for published post");
    //         $post = get_post($post_id);
    //         if ($post) {
    //             $result = $this->convert_relative_to_absolute_scheduling($scheduling_data, $post->post_date);
    //             error_log("WPSP: Relative conversion result: " . ($result ?: 'null'));
    //             return $result;
    //         } else {
    //             error_log("WPSP: Failed to get post object for post {$post_id}");
    //             return false;
    //         }
    //     }

    //     error_log("WPSP: Unknown scheduling type, falling back to CustomTemplateHelper");
    //     // Fallback to CustomTemplateHelper for backward compatibility
    //     return \WPSP\Helpers\CustomTemplateHelper::get_scheduled_datetime($scheduling_data);
    // }

    /**
     * Get scheduling status endpoint
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function get_scheduling_status_endpoint($request) {
        $post_id = $request->get_param('post_id');

        // Verify post exists and user can edit it
        if (!get_post($post_id) || !current_user_can('edit_post', $post_id)) {
            return new \WP_REST_Response(array(
                'success' => false,
                'message' => __('Post not found or insufficient permissions.', 'wp-scheduled-posts')
            ), 403);
        }

        $status = $this->get_scheduling_status($post_id);

        return new \WP_REST_Response(array(
            'success' => true,
            'data' => $status
        ), 200);
    }

    /**
     * Manual reschedule endpoint
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function manual_reschedule_endpoint($request) {
        $post_id = $request->get_param('post_id');
        $datetime = $request->get_param('datetime');
        $platforms = $request->get_param('platforms');

        // Verify post exists and user can edit it
        if (!get_post($post_id) || !current_user_can('edit_post', $post_id)) {
            return new \WP_REST_Response(array(
                'success' => false,
                'message' => __('Post not found or insufficient permissions.', 'wp-scheduled-posts')
            ), 403);
        }

        // Validate datetime
        $validation = $this->validate_scheduling_datetime($datetime, $post_id);
        if (!$validation['valid']) {
            return new \WP_REST_Response(array(
                'success' => false,
                'message' => $validation['message']
            ), 400);
        }

        // Perform rescheduling
        $this->reschedule_social_posts($post_id, $datetime, $platforms);

        // Get updated status
        $status = $this->get_scheduling_status($post_id);

        return new \WP_REST_Response(array(
            'success' => true,
            'message' => __('Social media posts rescheduled successfully.', 'wp-scheduled-posts'),
            'data' => $status
        ), 200);
    }

    /**
     * Fix duplicate cron jobs endpoint
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function fix_duplicate_cron_jobs_endpoint($request) {
        $post_id = $request->get_param('post_id');

        // Verify post exists and user can edit it
        if (!get_post($post_id) || !current_user_can('edit_post', $post_id)) {
            return new \WP_REST_Response(array(
                'success' => false,
                'message' => __('Post not found or insufficient permissions.', 'wp-scheduled-posts')
            ), 403);
        }

        // Check for duplicates before fixing
        $before_check = $this->check_duplicate_cron_jobs($post_id);

        // Clean up all scheduled events for this post
        $this->cleanup_scheduled_social_events($post_id);

        // Check for duplicates after fixing
        $after_check = $this->check_duplicate_cron_jobs($post_id);

        // Get current scheduling data to reschedule if needed
        $scheduling_data = get_post_meta($post_id, '_wpsp_social_scheduling', true);
        $rescheduled = false;

        if (!empty($scheduling_data) && isset($scheduling_data['enabled']) && $scheduling_data['enabled']) {
            if (isset($scheduling_data['datetime'])) {
                $schedule_time = strtotime($scheduling_data['datetime']);
                if ($schedule_time > time()) {
                    $this->schedule_social_media_posts($post_id, $scheduling_data['datetime']);
                    $rescheduled = true;
                }
            }
        }

        $final_check = $this->check_duplicate_cron_jobs($post_id);

        return new \WP_REST_Response(array(
            'success' => true,
            'message' => __('Duplicate cron jobs fixed successfully.', 'wp-scheduled-posts'),
            'data' => array(
                'before' => $before_check,
                'after_cleanup' => $after_check,
                'final_status' => $final_check,
                'rescheduled' => $rescheduled
            )
        ), 200);
    }

    /**
     * Force reschedule endpoint
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function force_reschedule_endpoint($request) {
        $post_id = $request->get_param('post_id');

        // Verify post exists and user can edit it
        if (!get_post($post_id) || !current_user_can('edit_post', $post_id)) {
            return new \WP_REST_Response(array(
                'success' => false,
                'message' => __('Post not found or insufficient permissions.', 'wp-scheduled-posts')
            ), 403);
        }

        $result = $this->force_reschedule($post_id);

        return new \WP_REST_Response(array(
            'success' => $result['success'],
            'message' => $result['message'],
            'data' => $result
        ), $result['success'] ? 200 : 400);
    }

    /**
     * Delete custom template for a post-platform combination
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
     * Convert relative scheduling to absolute datetime
     *
     * @param array $scheduling_data
     * @param string $publication_date
     * @return string|false
     */
    private function convert_relative_to_absolute_scheduling($scheduling_data, $publication_date) {
        try {
            error_log("WPSP: Converting relative scheduling - Data: " . json_encode($scheduling_data) . ", Publication date: " . $publication_date);

            $pub_datetime = new \DateTime($publication_date);
            $social_datetime = clone $pub_datetime;

            error_log("WPSP: Base publication datetime: " . $pub_datetime->format('Y-m-d H:i:s'));

            // Handle date options (relative to publication date)
            switch ($scheduling_data['dateOption']) {
                case 'same_day':
                    // No date change needed - use publication date
                    error_log("WPSP: Using same day as publication");
                    break;
                case 'day_after':
                    $social_datetime->add(new \DateInterval('P1D'));
                    error_log("WPSP: Adding 1 day after publication");
                    break;
                case 'week_after':
                    $social_datetime->add(new \DateInterval('P7D'));
                    error_log("WPSP: Adding 7 days after publication");
                    break;
                case 'month_after':
                    $social_datetime->add(new \DateInterval('P1M'));
                    error_log("WPSP: Adding 1 month after publication");
                    break;
                case 'days_after':
                    if (!empty($scheduling_data['customDays']) && is_numeric($scheduling_data['customDays'])) {
                        $days = intval($scheduling_data['customDays']);
                        $social_datetime->add(new \DateInterval("P{$days}D"));
                        error_log("WPSP: Adding {$days} days after publication");
                    } else {
                        error_log("WPSP: Invalid or missing customDays value");
                    }
                    break;
                case 'custom_date':
                    if (!empty($scheduling_data['customDate'])) {
                        $custom_date = new \DateTime($scheduling_data['customDate']);
                        error_log("WPSP: Custom date parsed: " . $custom_date->format('Y-m-d H:i:s'));

                        // For custom date, we set the date but preserve the publication time initially
                        $social_datetime->setDate(
                            $custom_date->format('Y'),
                            $custom_date->format('m'),
                            $custom_date->format('d')
                        );
                        error_log("WPSP: Using custom date: " . $custom_date->format('Y-m-d') . " with publication time: " . $social_datetime->format('H:i:s'));
                    } else {
                        error_log("WPSP: Custom date option selected but no date provided");
                    }
                    break;
                default:
                    error_log("WPSP: Unknown date option: " . $scheduling_data['dateOption']);
                    break;
            }

            error_log("WPSP: After date calculation: " . $social_datetime->format('Y-m-d H:i:s'));

            // Handle time options (relative to publication time)
            switch ($scheduling_data['timeOption']) {
                case 'same_time':
                    // No time change needed - use publication time
                    error_log("WPSP: Using same time as publication");
                    break;
                case 'hour_after':
                    $social_datetime->add(new \DateInterval('PT1H'));
                    error_log("WPSP: Adding 1 hour after publication");
                    break;
                case 'three_hours_after':
                    $social_datetime->add(new \DateInterval('PT3H'));
                    error_log("WPSP: Adding 3 hours after publication");
                    break;
                case 'five_hours_after':
                    $social_datetime->add(new \DateInterval('PT5H'));
                    error_log("WPSP: Adding 5 hours after publication");
                    break;
                case 'hours_after':
                    if (!empty($scheduling_data['customHours']) && is_numeric($scheduling_data['customHours'])) {
                        $hours = intval($scheduling_data['customHours']);
                        $social_datetime->add(new \DateInterval("PT{$hours}H"));
                        error_log("WPSP: Adding {$hours} hours after publication");
                    } else {
                        error_log("WPSP: Invalid or missing customHours value");
                    }
                    break;
                case 'custom_time':
                    if (!empty($scheduling_data['customTime'])) {
                        $time_parts = explode(':', $scheduling_data['customTime']);
                        if (count($time_parts) >= 2) {
                            $social_datetime->setTime(intval($time_parts[0]), intval($time_parts[1]), 0);
                            error_log("WPSP: Using custom time: " . $scheduling_data['customTime']);
                        } else {
                            error_log("WPSP: Invalid custom time format: " . $scheduling_data['customTime']);
                        }
                    } else {
                        error_log("WPSP: Custom time option selected but no time provided");
                    }
                    break;
                default:
                    error_log("WPSP: Unknown time option: " . $scheduling_data['timeOption']);
                    break;
            }

            $result = $social_datetime->format('Y-m-d H:i:s');
            error_log("WPSP: Relative to absolute conversion result: " . $result);
            return $result;

        } catch (\Exception $e) {
            error_log("WPSP: Error converting relative to absolute scheduling: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Schedule social media posts
     *
     * @param int $post_id
     * @param string $datetime
     * @return bool
     */
    private function schedule_social_media_posts($post_id, $datetime) {
        try {
            // Validate the scheduling datetime
            $validation = $this->validate_scheduling_datetime($datetime, $post_id);
            if (!$validation['valid']) {
                error_log("WPSP: Scheduling validation failed for post {$post_id}: " . $validation['message']);
                $this->log_scheduling_activity('validation_failed', $post_id, $validation);
                return false;
            }

            $schedule_timestamp = Helper::getDateFromTimezone($datetime, 'U', true);

            // Clean up any existing scheduled events first
            $this->cleanup_scheduled_social_events($post_id);

            // Ensure post_id is an integer for consistency
            $post_id = intval($post_id);

            // Schedule the new event with integer post_id
            $scheduled = wp_schedule_single_event($schedule_timestamp, 'publish_future_post', array($post_id));

            if ($scheduled === false) {
                error_log("WPSP: Failed to schedule social media posts for post {$post_id}");
                $this->log_scheduling_activity('schedule_failed', $post_id, array('datetime' => $datetime));
                return false;
            } else {
                // Verify the event was actually scheduled
                $verification = wp_next_scheduled('publish_future_post', array($post_id));
                if ($verification) {
                    $log_data = array(
                        'datetime' => $datetime,
                        'timestamp' => $schedule_timestamp,
                        'formatted_time' => date('Y-m-d H:i:s', $schedule_timestamp),
                        'verified_timestamp' => $verification,
                        'verified_time' => date('Y-m-d H:i:s', $verification)
                    );
                    error_log("WPSP: Successfully scheduled and verified social media posts for post {$post_id} at " . date('Y-m-d H:i:s', $schedule_timestamp));
                    $this->log_scheduling_activity('scheduled', $post_id, $log_data);
                    return true;
                } else {
                    error_log("WPSP: Event scheduled but verification failed for post {$post_id}");
                    $this->log_scheduling_activity('schedule_verification_failed', $post_id, array('datetime' => $datetime));
                    return false;
                }
            }

        } catch (\Exception $e) {
            error_log("WPSP: Error scheduling social media posts: " . $e->getMessage());
            $this->log_scheduling_activity('error', $post_id, array('error' => $e->getMessage()));
            return false;
        }
    }

    /**
     * Clean up scheduled social media events for a post
     *
     * @param int $post_id
     */
    public function cleanup_scheduled_social_events($post_id) {
        $post_id = intval($post_id); // Ensure we have an integer
        $cleaned_count = 0;

        // Check for both integer and string versions of post_id
        $arg_variations = array(
            array($post_id),           // Integer version: [1170]
            array(strval($post_id)),   // String version: ["1170"]
        );

        foreach ($arg_variations as $args) {
            $existing_timestamp = wp_next_scheduled('publish_future_post', $args);

            while ($existing_timestamp) {
                $unscheduled = wp_unschedule_event($existing_timestamp, 'publish_future_post', $args);
                if ($unscheduled) {
                    $cleaned_count++;
                    error_log("WPSP: Cleaned up scheduled event for post {$post_id} with args: " . json_encode($args));
                } else {
                    error_log("WPSP: Failed to unschedule event for post {$post_id} with args: " . json_encode($args));
                }

                // Check for more events with the same arguments
                $existing_timestamp = wp_next_scheduled('publish_future_post', $args);
            }
        }

        // Also check the entire cron array for any missed events
        $this->cleanup_orphaned_events($post_id);

        if ($cleaned_count > 0) {
            error_log("WPSP: Total cleaned up {$cleaned_count} scheduled events for post {$post_id}");
        }
    }

    /**
     * Clean up orphaned events by checking the entire cron array
     *
     * @param int $post_id
     */
    private function cleanup_orphaned_events($post_id) {
        $post_id = intval($post_id);
        $cron_array = _get_cron_array();
        $cleaned_orphans = 0;

        if (is_array($cron_array)) {
            foreach ($cron_array as $timestamp => $cron_events) {
                if (isset($cron_events['publish_future_post'])) {
                    foreach ($cron_events['publish_future_post'] as $event_key => $event) {
                        if (isset($event['args'][0])) {
                            $event_post_id = $event['args'][0];

                            // Check if this event belongs to our post (handle both int and string)
                            if (intval($event_post_id) === $post_id) {
                                // Remove this specific event
                                $unscheduled = wp_unschedule_event($timestamp, 'publish_future_post', $event['args']);
                                if ($unscheduled) {
                                    $cleaned_orphans++;
                                    error_log("WPSP: Cleaned up orphaned event for post {$post_id} at timestamp {$timestamp} with args: " . json_encode($event['args']));
                                }
                            }
                        }
                    }
                }
            }
        }

        if ($cleaned_orphans > 0) {
            error_log("WPSP: Cleaned up {$cleaned_orphans} orphaned events for post {$post_id}");
        }
    }

    /**
     * Clean up all scheduled events when post is deleted
     *
     * @param int $post_id
     */
    public function cleanup_scheduled_events($post_id) {
        $this->cleanup_scheduled_social_events($post_id);
        error_log("WPSP: Cleaned up all scheduled events for deleted post {$post_id}");
    }

    /**
     * Recalculate and schedule social media posts
     *
     * @param int $post_id
     * @param array $scheduling_data
     */
    private function recalculate_and_schedule($post_id, $scheduling_data) {
        if (empty($scheduling_data) || !isset($scheduling_data['enabled']) || !$scheduling_data['enabled']) {
            return;
        }

        $datetime = null;

        // For absolute scheduling, use the stored datetime
        if (isset($scheduling_data['datetime'])) {
            $datetime = $scheduling_data['datetime'];
        } else {
            // For relative scheduling, calculate from current post date
            $post = get_post($post_id);
            if ($post && isset($scheduling_data['schedulingType']) && $scheduling_data['schedulingType'] === 'relative') {
                $datetime = $this->convert_relative_to_absolute_scheduling($scheduling_data, $post->post_date);
            }
        }

        if ($datetime) {
            $this->schedule_social_media_posts($post_id, $datetime);
        }
    }

    /**
     * Recalculate social scheduling (hook callback)
     *
     * @param int $post_id
     * @param array $scheduling_data
     */
    public function recalculate_social_scheduling($post_id, $scheduling_data = null) {
        if (!$scheduling_data) {
            $scheduling_data = get_post_meta($post_id, '_wpsp_social_scheduling', true);
        }

        $this->recalculate_and_schedule($post_id, $scheduling_data);
    }

    /**
     * Reschedule social posts (manual rescheduling)
     *
     * @param int $post_id
     * @param string $new_datetime
     * @param array $platforms
     */
    public function reschedule_social_posts($post_id, $new_datetime, $platforms = array()) {
        // Clean up existing events
        $this->cleanup_scheduled_social_events($post_id);

        // Update scheduling data
        $scheduling_data = get_post_meta($post_id, '_wpsp_social_scheduling', true);
        if (!$scheduling_data) {
            $scheduling_data = array();
        }

        $scheduling_data['datetime'] = $new_datetime;
        $scheduling_data['enabled'] = true;
        $scheduling_data['status'] = 'scheduled';

        if (!empty($platforms)) {
            $scheduling_data['platforms'] = $platforms;
        }

        update_post_meta($post_id, '_wpsp_social_scheduling', $scheduling_data);

        // Schedule new events
        $this->schedule_social_media_posts($post_id, $new_datetime);

        error_log("WPSP: Manually rescheduled social posts for post {$post_id}");
    }

    /**
     * Validate scheduling datetime to prevent conflicts
     *
     * @param string $datetime
     * @param int $post_id
     * @return array
     */
    private function validate_scheduling_datetime($datetime, $post_id) {
        try {
            $schedule_time = strtotime($datetime);
            $current_time = time();

            // Check if time is in the past
            if ($schedule_time <= $current_time) {
                return array(
                    'valid' => false,
                    'message' => __('Scheduling time cannot be in the past.', 'wp-scheduled-posts'),
                    'code' => 'past_time'
                );
            }

            // Check if time is too far in the future (1 year limit)
            $one_year_from_now = $current_time + (365 * 24 * 60 * 60);
            if ($schedule_time > $one_year_from_now) {
                return array(
                    'valid' => false,
                    'message' => __('Scheduling time cannot be more than 1 year in the future.', 'wp-scheduled-posts'),
                    'code' => 'too_far_future'
                );
            }

            // Check for existing scheduled events for this post (cleanup check)
            $existing_scheduled = wp_next_scheduled('publish_future_post', array($post_id));
            if ($existing_scheduled) {
                error_log("WPSP: Found existing scheduled event for post {$post_id}, will be cleaned up before new scheduling");
            }

            // Note: Conflict detection with other posts is optional and can be resource-intensive
            // For now, we rely on WordPress cron system to handle scheduling conflicts gracefully

            return array(
                'valid' => true,
                'message' => __('Scheduling time is valid.', 'wp-scheduled-posts'),
                'timestamp' => $schedule_time
            );

        } catch (\Exception $e) {
            return array(
                'valid' => false,
                'message' => __('Invalid datetime format.', 'wp-scheduled-posts'),
                'code' => 'invalid_format'
            );
        }
    }

    /**
     * Get timezone-aware datetime
     *
     * @param string $datetime
     * @param string $timezone
     * @return string
     */
    private function get_timezone_aware_datetime($datetime, $timezone = null) {
        if (!$timezone) {
            $timezone = wp_timezone_string();
        }

        try {
            $dt = new \DateTime($datetime, new \DateTimeZone($timezone));
            return $dt->format('Y-m-d H:i:s');
        } catch (\Exception $e) {
            error_log("WPSP: Timezone conversion error: " . $e->getMessage());
            return $datetime;
        }
    }

    /**
     * Log scheduling activity for debugging
     *
     * @param string $action
     * @param int $post_id
     * @param array $data
     */
    private function log_scheduling_activity($action, $post_id, $data = array()) {
        $log_data = array(
            'action' => $action,
            'post_id' => $post_id,
            'timestamp' => current_time('mysql'),
            'data' => $data
        );

        error_log("WPSP Scheduling Activity: " . json_encode($log_data));

        // Optionally store in database for admin interface
        $activity_log = get_option('wpsp_scheduling_activity_log', array());
        $activity_log[] = $log_data;

        // Keep only last 100 entries
        if (count($activity_log) > 100) {
            $activity_log = array_slice($activity_log, -100);
        }

        update_option('wpsp_scheduling_activity_log', $activity_log);
    }

    /**
     * Check for duplicate cron jobs for a post
     *
     * @param int $post_id
     * @return array
     */
    public function check_duplicate_cron_jobs($post_id) {
        $post_id = intval($post_id);
        $cron_array = _get_cron_array();
        $found_events = array();

        if (is_array($cron_array)) {
            foreach ($cron_array as $timestamp => $cron_events) {
                if (isset($cron_events['publish_future_post'])) {
                    foreach ($cron_events['publish_future_post'] as $event_key => $event) {
                        if (isset($event['args'][0]) && intval($event['args'][0]) === $post_id) {
                            $found_events[] = array(
                                'timestamp' => $timestamp,
                                'formatted_time' => date('Y-m-d H:i:s', $timestamp),
                                'args' => $event['args'],
                                'event_key' => $event_key
                            );
                        }
                    }
                }
            }
        }

        return array(
            'count' => count($found_events),
            'has_duplicates' => count($found_events) > 1,
            'events' => $found_events
        );
    }

    /**
     * Get scheduling status for a post
     *
     * @param int $post_id
     * @return array
     */
    public function get_scheduling_status($post_id) {
        $scheduling_data = get_post_meta($post_id, '_wpsp_social_scheduling', true);
        $next_scheduled = wp_next_scheduled('publish_future_post', array($post_id));

        // Check for duplicates
        $duplicate_check = $this->check_duplicate_cron_jobs($post_id);

        $status = array(
            'has_scheduling' => !empty($scheduling_data),
            'is_scheduled' => $next_scheduled !== false,
            'next_run' => $next_scheduled ? date('Y-m-d H:i:s', $next_scheduled) : null,
            'scheduling_type' => isset($scheduling_data['schedulingType']) ? $scheduling_data['schedulingType'] : 'unknown',
            'enabled' => isset($scheduling_data['enabled']) ? $scheduling_data['enabled'] : false,
            'cron_jobs_count' => $duplicate_check['count'],
            'has_duplicates' => $duplicate_check['has_duplicates'],
            'all_scheduled_events' => $duplicate_check['events']
        );

        // Log warning if duplicates found
        if ($duplicate_check['has_duplicates']) {
            error_log("WPSP: WARNING - Found {$duplicate_check['count']} duplicate cron jobs for post {$post_id}");
        }

        return $status;
    }

    /**
     * Force reschedule for a post (useful for debugging)
     *
     * @param int $post_id
     * @return array
     */
    public function force_reschedule($post_id) {
        $post = get_post($post_id);
        if (!$post) {
            return array('success' => false, 'message' => 'Post not found');
        }

        $scheduling_data = get_post_meta($post_id, '_wpsp_social_scheduling', true);
        if (empty($scheduling_data)) {
            return array('success' => false, 'message' => 'No scheduling data found');
        }

        // Clean up existing events
        $this->cleanup_scheduled_social_events($post_id);

        $result = array(
            'success' => false,
            'post_status' => $post->post_status,
            'scheduling_data' => $scheduling_data,
            'message' => 'Unknown error'
        );

        if ($post->post_status === 'publish') {
            if ($scheduling_data['schedulingType'] === 'relative') {
                $absolute_datetime = $this->convert_relative_to_absolute_scheduling($scheduling_data, $post->post_date);
                if ($absolute_datetime) {
                    $scheduled = $this->schedule_social_media_posts($post_id, $absolute_datetime);
                    $result['success'] = $scheduled;
                    $result['scheduled_time'] = $absolute_datetime;
                    $result['message'] = $scheduled ? 'Successfully rescheduled (relative converted)' : 'Failed to schedule';
                }
            } else {
                $set_schedule_at = $this->handle_published_post_scheduling($post_id, $scheduling_data);
                if ($set_schedule_at) {
                    $scheduled = $this->schedule_social_media_posts($post_id, $set_schedule_at);
                    $result['success'] = $scheduled;
                    $result['scheduled_time'] = $set_schedule_at;
                    $result['message'] = $scheduled ? 'Successfully rescheduled (absolute)' : 'Failed to schedule';
                }
            }
        } else {
            $result['message'] = 'Post not published - scheduling stored for later';
            $result['success'] = true;
        }

        $result['final_status'] = $this->get_scheduling_status($post_id);
        return $result;
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
