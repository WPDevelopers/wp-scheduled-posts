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

        return new \WP_REST_Response( [
            'success' => true,
            'data'    => [
                'schedule_date' => $post->post_status === 'future' ? $post->post_date : '',
                'post_status'   => $post->post_status,
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
