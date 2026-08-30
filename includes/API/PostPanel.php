<?php

namespace WPSP\API;

/**
 * Post Panel REST API
 *
 * After saving, fires `schedulepress_after_free_settings_save` so the
 * Pro plugin (and any other extension) can handle their own fields
 * without touching this endpoint.
 *
 * Endpoint: POST /wp-json/wp-scheduled-posts/v1/post-panel/{post_id}
 * Endpoint: GET  /wp-json/wp-scheduled-posts/v1/post-panel/{post_id}
 *
 * @since 5.3.0
 */
class PostPanel {

    /**
     * Singleton instance.
     *
     * @var self|null
     */
    protected static $instance = null;

    private function __construct() {
        add_action( 'rest_api_init', [ $this, 'register_routes' ] );
    }

    /**
     * Register REST routes.
     */
    public function register_routes() {
        $namespace = WPSP_PLUGIN_SLUG . '/v1';
        $route     = '/post-panel/(?P<post_id>\d+)';

        register_rest_route( $namespace, $route, [
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => [ $this, 'save_settings' ],
            'permission_callback' => [ $this, 'permission_check' ],
            'args'                => [
                'post_id' => [
                    'required'          => true,
                    'validate_callback' => fn( $param ) => is_numeric( $param ),
                    'sanitize_callback' => 'absint',
                ],
                'schedule_date' => [
                    'required'          => false,
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                    'default'           => '',
                ],
                'is_scheduled' => [
                    'required' => false,
                    'type'     => 'boolean',
                    'default'  => false,
                ],
            ],
        ] );

        register_rest_route( $namespace, $route, [
            'methods'             => \WP_REST_Server::READABLE,
            'callback'            => [ $this, 'get_settings' ],
            'permission_callback' => [ $this, 'permission_check' ],
        ] );

        // "Publish future post immediately" action endpoint. Moved from the Pro
        // plugin so the feature works in Free. Used by the post-panel buttons.
        register_rest_route( $namespace, '/update-settings/(?P<post_id>\d+)', [
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => [ $this, 'publish_immediately' ],
            'permission_callback' => [ $this, 'permission_check' ],
            'args'                => [
                'post_id' => [
                    'required'          => true,
                    'validate_callback' => fn( $param ) => is_numeric( $param ),
                    'sanitize_callback' => 'absint',
                ],
            ],
        ] );

        // Turning "publish future post immediately" back off. Without this the
        // intent is sticky and invisible: it survives every later save, and the
        // buttons that set it are hidden once the post reaches 'publish'.
        register_rest_route( $namespace, '/update-settings/(?P<post_id>\d+)', [
            'methods'             => \WP_REST_Server::DELETABLE,
            'callback'            => [ $this, 'clear_publish_immediately' ],
            'permission_callback' => [ $this, 'permission_check' ],
            'args'                => [
                'post_id' => [
                    'required'          => true,
                    'validate_callback' => fn( $param ) => is_numeric( $param ),
                    'sanitize_callback' => 'absint',
                ],
            ],
        ] );
    }

    /**
     * Permission callback – user must be able to edit the specific post.
     *
     * @param \WP_REST_Request $request
     * @return bool|\WP_Error
     */
    public function permission_check( \WP_REST_Request $request ) {
        $post_id = (int) $request->get_param( 'post_id' );
        if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) ) {
            return new \WP_Error(
                'rest_forbidden',
                __( 'You do not have permission to edit this post.', 'wp-scheduled-posts' ),
                [ 'status' => 403 ]
            );
        }
        return true;
    }

    /**
     * GET handler – return current scheduling state.
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function get_settings( \WP_REST_Request $request ) {
        $post_id = (int) $request->get_param( 'post_id' );
        $post    = get_post( $post_id );

        if ( ! $post ) {
            return new \WP_REST_Response( [
                'success' => false,
                'message' => __( 'Post not found.', 'wp-scheduled-posts' ),
            ], 404 );
        }

        // The stored value is the post_date the intent was recorded against.
        // includes/functions.php only keeps forcing 'publish' while the two
        // still match, so a stale row is not active state and is not reported.
        $prevent_future_post = get_post_meta( $post_id, 'prevent_future_post', true );
        $is_preventing       = ! empty( $prevent_future_post )
            && $prevent_future_post === $post->post_date;

        return new \WP_REST_Response( [
            'success' => true,
            'data'    => [
                'schedule_date'            => $post->post_status === 'future' ? $post->post_date : '',
                'post_status'              => $post->post_status,
                'prevent_future_post'      => $is_preventing,
                'prevent_future_post_date' => $is_preventing ? $prevent_future_post : '',
            ],
        ], 200 );
    }

    /**
     * POST handler – save free-tier fields then fire hook for extensions.
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function save_settings( \WP_REST_Request $request ) {
        $post_id       = (int) $request->get_param( 'post_id' );
        $schedule_date = $request->get_param( 'schedule_date' );
        $is_scheduled  = (bool) $request->get_param( 'is_scheduled' );

        $post = get_post( $post_id );
        if ( ! $post ) {
            return new \WP_REST_Response( [
                'success' => false,
                'message' => __( 'Post not found.', 'wp-scheduled-posts' ),
            ], 404 );
        }

        // ── Free feature: schedule_date ───────────────────────────────────────
        if ( $is_scheduled && ! empty( $schedule_date ) ) {
            $post_date     = date( 'Y-m-d H:i:s', strtotime( $schedule_date ) );
            $post_date_gmt = get_gmt_from_date( $post_date );

            wp_update_post( [
                'ID'            => $post_id,
                'post_date'     => $post_date,
                'post_date_gmt' => $post_date_gmt,
                'post_status'   => 'future',
                'edit_date'     => true,
            ] );
        }

        /**
         * Fires after the Free plugin has saved its own post-panel fields.
         *
         * Pro plugin and any third-party extension should hook here to process
         * their own fields (unpublish_on, republish_on, advanced scheduling, …).
         * Do NOT handle free-tier fields (e.g. schedule_date) inside this hook.
         *
         * @since 5.3.0
         *
         * @param int              $post_id The post ID.
         * @param \WP_REST_Request $request The full REST request object.
         *                                  Extensions can read any additional
         *                                  params they need directly from it.
         */
        do_action( 'schedulepress_after_free_settings_save', $post_id, $request );

        return new \WP_REST_Response( [
            'success' => true,
            'message' => __( 'Settings saved successfully.', 'wp-scheduled-posts' ),
        ], 200 );
    }

    /**
     * POST handler – immediately publish a scheduled (future) post.
     *
     * Backs the "Publish future post immediately" controls in the post panel.
     * Moved from the Pro plugin so the feature is available in Free.
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function publish_immediately( \WP_REST_Request $request ) {
        $post_id = (int) $request->get_param( 'post_id' );
        $post    = get_post( $post_id );

        if ( ! $post ) {
            return new \WP_REST_Response( [
                'success' => false,
                'message' => __( 'Post not found.', 'wp-scheduled-posts' ),
            ], 404 );
        }

        $use_current_date = $this->is_flag_set( $request->get_param( 'publish_immediately_current_date' ) );
        $use_future_date  = $this->is_flag_set( $request->get_param( 'publish_immediately_future_date' ) );

        // Exactly one action has to be named. Neither flag meant nothing ran and
        // the route still answered "Post published successfully", and both flags
        // meant two conflicting writes with only the last one surviving.
        if ( $use_current_date === $use_future_date ) {
            return new \WP_REST_Response( [
                'success' => false,
                'message' => __( 'Choose exactly one of publish_immediately_current_date or publish_immediately_future_date.', 'wp-scheduled-posts' ),
            ], 400 );
        }

        $result = $use_current_date
            ? $this->handle_post_published( $post_id )
            : $this->handle_post_publish_on_future_date( $post_id );

        if ( is_wp_error( $result ) ) {
            $status = $result->get_error_code() === 'wpsp_not_future_dated' ? 400 : 500;
            return new \WP_REST_Response( [
                'success' => false,
                'message' => $result->get_error_message(),
            ], $status );
        }

        return new \WP_REST_Response( [
            'success' => true,
            'message' => __( 'Post published successfully.', 'wp-scheduled-posts' ),
            'data'    => [
                'post_status' => get_post_status( $post_id ),
            ],
        ], 200 );
    }

    /**
     * Whether a request flag was actually set.
     *
     * The panel sends a JSON boolean, but the same route is reachable with form
     * encoded input where it arrives as the string "true"/"1".
     *
     * @param  mixed $value
     * @return bool
     */
    private function is_flag_set( $value ) {
        return $value === true || $value === 'true' || $value === 1 || $value === '1';
    }

    /**
     * DELETE handler – turn "publish future post immediately" back off.
     *
     * Deletes the prevent_future_post meta and, when the post is still dated in
     * the future, returns it to 'future' so WordPress schedules it again. That
     * is the actual undo: leaving the post published while dropping the meta
     * would keep it visible with a date it has not reached.
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function clear_publish_immediately( \WP_REST_Request $request ) {
        $post_id = (int) $request->get_param( 'post_id' );
        $post    = get_post( $post_id );

        if ( ! $post ) {
            return new \WP_REST_Response( [
                'success' => false,
                'message' => __( 'Post not found.', 'wp-scheduled-posts' ),
            ], 404 );
        }

        // Require an active intent, using the same rule the GET handler reports
        // it by. Without this precondition the route would reschedule any
        // published future-dated post, including one this feature never touched.
        $prevent_future_post = get_post_meta( $post_id, 'prevent_future_post', true );
        $is_active           = ! empty( $prevent_future_post )
            && $prevent_future_post === $post->post_date;

        if ( ! $is_active ) {
            // A stale row is not active state, but it should not be left behind.
            if ( ! empty( $prevent_future_post ) ) {
                delete_post_meta( $post_id, 'prevent_future_post' );
            }

            // Idempotent: nothing to turn off, and the post status is untouched.
            return new \WP_REST_Response( [
                'success' => true,
                'message' => __( 'Immediate publishing was not active for this post.', 'wp-scheduled-posts' ),
                'data'    => [
                    'post_status'         => $post->post_status,
                    'prevent_future_post' => false,
                    'rescheduled'         => false,
                ],
            ], 200 );
        }

        if ( ! delete_post_meta( $post_id, 'prevent_future_post' ) ) {
            return new \WP_REST_Response( [
                'success' => false,
                'message' => __( 'Could not clear the stored publishing intent.', 'wp-scheduled-posts' ),
            ], 500 );
        }

        $rescheduled = false;
        if ( $post->post_status === 'publish' && strtotime( $post->post_date_gmt ) > time() ) {
            $updated = wp_update_post( [
                'ID'          => $post_id,
                'post_status' => 'future',
            ], true );

            if ( is_wp_error( $updated ) ) {
                // Put the intent back. Leaving it deleted after a failed
                // reschedule strands the post published on a date it has not
                // reached, with nothing to re-assert that state on the next save
                // and nothing left for the user to turn off.
                update_post_meta( $post_id, 'prevent_future_post', $prevent_future_post );

                return new \WP_REST_Response( [
                    'success' => false,
                    'message' => $updated->get_error_message(),
                ], 500 );
            }

            $rescheduled = true;

            // Let Pro (when active) reschedule its unpublish/republish cron jobs.
            do_action( 'wpsp_pro_update_post', $post_id );
        }

        return new \WP_REST_Response( [
            'success' => true,
            'message' => $rescheduled
                ? __( 'Post returned to its schedule.', 'wp-scheduled-posts' )
                : __( 'Immediate publishing turned off.', 'wp-scheduled-posts' ),
            'data'    => [
                'post_status'         => get_post_status( $post_id ),
                'prevent_future_post' => false,
                'rescheduled'         => $rescheduled,
            ],
        ], 200 );
    }

    /**
     * Publish a post immediately using the current date/time.
     *
     * @param int $post_id
     * @return true|\WP_Error
     */
    public function handle_post_published( $post_id ) {
        if ( ! $post_id ) {
            return new \WP_Error(
                'wpsp_missing_post',
                __( 'Post not found.', 'wp-scheduled-posts' )
            );
        }

        // wp_update_post() returns 0 on failure unless the third argument asks
        // for a WP_Error, so without it a failed publish was indistinguishable
        // from a successful one.
        $updated = wp_update_post( [
            'ID'            => $post_id,
            'post_status'   => 'publish',
            'post_date'     => current_time( 'mysql' ),
            'post_date_gmt' => current_time( 'mysql', 1 ),
        ], true );

        if ( is_wp_error( $updated ) ) {
            return $updated;
        }

        return true;
    }

    /**
     * Publish a future-dated post immediately while preserving its future date.
     *
     * @param int $post_id
     * @return true|\WP_Error
     */
    public function handle_post_publish_on_future_date( $post_id ) {
        if ( ! $post_id ) {
            return new \WP_Error(
                'wpsp_missing_post',
                __( 'Post not found.', 'wp-scheduled-posts' )
            );
        }

        $post = get_post( $post_id );
        if ( ! $post ) {
            return new \WP_Error(
                'wpsp_missing_post',
                __( 'Post not found.', 'wp-scheduled-posts' )
            );
        }

        // Only proceed if the post date is still in the future. This is a bad
        // request rather than a server failure: there is no future date to
        // publish ahead of.
        $is_future_date = strtotime( $post->post_date_gmt ) > time();
        if ( ! $is_future_date ) {
            return new \WP_Error(
                'wpsp_not_future_dated',
                __( 'This post is not dated in the future.', 'wp-scheduled-posts' )
            );
        }

        // Bypass WordPress forcing 'future' status when the date is in the future.
        // Scoped to this post so nothing else saved during the request is affected.
        $filter_callback = function ( $data, $postarr ) use ( $post_id ) {
            if ( (int) ( $postarr['ID'] ?? 0 ) === $post_id && $data['post_status'] === 'future' ) {
                $data['post_status'] = 'publish';
            }
            return $data;
        };
        add_filter( 'wp_insert_post_data', $filter_callback, 10, 2 );

        // Publish while preserving the scheduled date.
        $updated = wp_update_post( [
            'ID'            => $post_id,
            'post_status'   => 'publish',
            'post_date'     => $post->post_date,
            'post_date_gmt' => $post->post_date_gmt,
            'edit_date'     => true,
        ], true );

        remove_filter( 'wp_insert_post_data', $filter_callback );

        // The intent is only persisted, and Pro only notified, once the post has
        // actually been published. Recording it after a failed write would leave
        // the meta forcing 'publish' on a post that never moved.
        if ( is_wp_error( $updated ) ) {
            return $updated;
        }

        // Persist the intent, otherwise the next save lets WordPress force the
        // post back to 'future'. The filter in includes/functions.php re-asserts
        // 'publish' for as long as this meta matches the post date.
        update_post_meta( $post_id, 'prevent_future_post', get_post( $post_id )->post_date );

        // Let Pro (when active) reschedule its unpublish/republish cron jobs.
        do_action( 'wpsp_pro_update_post', $post_id );

        return true;
    }

    /**
     * Return singleton instance.
     *
     * @return self
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }
}
