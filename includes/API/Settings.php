<?php

namespace WPSP\API;


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
            'value' => $wpsp_option
        ), 200);
    }

    /**
     * Create OR Update wpsp
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|WP_REST_Request
     */
    public function update_value($request)
    {
        $updated = update_option($this->settings_name, $request->get_param('wpspSetting'));

        return new \WP_REST_Response(array(
            'success'   => $updated,
            'value'     => $request->get_param('wpspSetting')
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
