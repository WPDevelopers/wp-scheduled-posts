<?php

namespace WPSP\API;

use WPSP;

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
    }
    public function meta_rest_api() {
        $allow_post_types = \WPSP\Helper::get_settings('allow_post_types');
		$allow_post_types = (!empty($allow_post_types) ? $allow_post_types : array('post'));
        foreach ($allow_post_types as $type) {
            register_post_meta(
                $type,
                '_wpscppro_dont_share_socialmedia',
                [
                    'show_in_rest' => true,
                    'single'       => true,
                    'type'         => 'boolean',
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
        }
        
    }


    public function register_social_profile_routes()
    {
        $namespace = WPSP_PLUGIN_SLUG . '/v1';
        $allow_post_types = \WPSP\Helper::get_settings('allow_post_types');
		$allow_post_types = (!empty($allow_post_types) ? $allow_post_types : array('post'));
        foreach ($allow_post_types as $type) {
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
        }
    }

    // Instant social share
    public function wpsp_instant_social_share( $data )
    {
        do_action('wpsp_instant_social_single_profile_share', $data->get_params());
    }

     // Fetch option table data 
    public function wpsp_get_options_data( $request ) {
        $option_name = $request->get_param('option_name');
        if ($option_name) {
            $option_value = get_option($option_name);
            if ($option_value !== false) {
                return rest_ensure_response($option_value);
            } else {
                return new \WP_Error('option_not_found', 'Option not found', array('status' => 404));
            }
        } else {
            return new \WP_Error('missing_option_name', 'Option name parameter is missing', array('status' => 400));
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
