<?php

namespace WPSP;

class Assets
{
    public function __construct()
    {
        // admin script
        add_action('enqueue_block_assets', [$this, 'guten_scripts']);
        add_action('admin_enqueue_scripts', [$this, 'plugin_scripts']);
        // adminbar enqueue
        add_action('admin_enqueue_scripts', [$this, 'adminbar_script']);
        add_action('wp_enqueue_scripts', [$this, 'adminbar_script']);

	    add_action( 'elementor/editor/after_enqueue_scripts', function () {
		    wp_enqueue_script( 'wpscp-el-editor', WPSP_ASSETS_URI . 'js/elementor-editor.js', array( 'jquery', 'tipsy' ), WPSP_VERSION, true );
	    } );
    }

    /**
     * Gutten Support
     * @since 1.2.0
     */
    public function guten_scripts()
    {
        global $post_type;
        $allow_post_types = \WPSP\Helper::get_settings('allow_post_types');
        $allow_post_types = (!empty($allow_post_types) ? $allow_post_types : array('post'));
        if (!in_array($post_type, $allow_post_types) || !is_admin()) {
            return;
        }

        wp_enqueue_style('wps-publish-button', WPSP_ASSETS_URI . 'css/wpspl-admin.css', array(), WPSP_VERSION, 'all');
        wp_enqueue_script('wps-publish-button', WPSP_ASSETS_URI . 'js/wpspl-admin.min.js', array('wp-components', 'wp-data', 'wp-edit-post', 'wp-editor', 'wp-element', 'wp-i18n', 'wp-plugins', 'wp-a11y', 'wp-components', 'wp-compose', 'wp-data', 'wp-deprecated', 'wp-element', 'wp-i18n', 'wp-plugins', 'wp-preferences', 'wp-primitives', 'wp-viewport'), '1.0.0', true);
        wp_localize_script('wps-publish-button', 'WPSchedulePostsFree', array(
            'publishImmediately' => __('Current Date', 'wp-scheduled-posts'),
            'publishFutureDate' => __('Future Date', 'wp-scheduled-posts'),
            'publish_button_off' => \WPSP\Helper::get_settings('show_publish_post_button'),
            'allowedPostTypes' => $allow_post_types,
            'currentTime' => array(
                'date' => current_time('mysql'),
                'date_gmt' => current_time('mysql', 1),
            ),
        ));
    }

    /**
     * Enqueue Files on Start Plugin
     *
     * @function plugin_script
     */
    public function plugin_scripts($hook)
    {
        $current_screen = \get_current_screen();
        if (is_admin() && Helper::plugin_page_hook_suffix($current_screen->post_type, $hook)) {
            wp_enqueue_style('select2-css', WPSP_ASSETS_URI . 'css/vendor/select2.min.css', array(), WPSP_VERSION, 'all');
            wp_enqueue_style('wpscp-jquery-datetimepicker', WPSP_ASSETS_URI . 'css/vendor/jquery.datetimepicker.min.css', array(), WPSP_VERSION, 'all');
            wp_enqueue_style('chung-timepicker', WPSP_ASSETS_URI . 'css/vendor/chung-timepicker.css', array(), WPSP_VERSION, 'all');
            wp_enqueue_style('notifi', WPSP_ASSETS_URI . 'css/vendor/notifi.css', array(), WPSP_VERSION, 'all');
            wp_enqueue_style('jquery-kylefoxModal', WPSP_ASSETS_URI . 'css/vendor/jquery.modal.min.css', array(), WPSP_VERSION, 'all');
            wp_enqueue_style('jquery-timepicker', WPSP_ASSETS_URI . 'css/vendor/jquery.timepicker.min.css', array(), WPSP_VERSION, 'all');
            wp_enqueue_style('wpscp-admin', WPSP_ASSETS_URI . 'css/wpscp-admin.css', array(), WPSP_VERSION, 'all');

            /**
             * JavaScript File
             */
            wp_enqueue_script('jquery-ui-draggable');
            wp_enqueue_script('jquery-ui-droppable');
            wp_enqueue_script('wpscp-jquery-datetimepicker', WPSP_ASSETS_URI . 'js/vendor/jquery.datetimepicker.full.min.js', array('jquery'), WPSP_VERSION, false);
            wp_enqueue_script('chung-timepicker', WPSP_ASSETS_URI . 'js/vendor/chung-timepicker.js', array('jquery'), WPSP_VERSION, false);
            wp_enqueue_script('timepicker', WPSP_ASSETS_URI . 'js/vendor/jquery.timepicker.min.js', array('jquery'), WPSP_VERSION, false);
            wp_enqueue_script('jquery-kylefoxModal', WPSP_ASSETS_URI . 'js/vendor/jquery.modal.min.js', array('jquery'), WPSP_VERSION, false);
            wp_enqueue_script('select2', WPSP_ASSETS_URI . 'js/vendor/select2.full.js', array('jquery'), WPSP_VERSION, false);
            wp_enqueue_script('sweetalert', WPSP_ASSETS_URI . 'js/vendor/sweetalert.min.js', array('jquery'), WPSP_VERSION, false);
            wp_enqueue_script('notifi', WPSP_ASSETS_URI . 'js/vendor/notifi.min.js', array('jquery'), WPSP_VERSION, false);
            wp_enqueue_script('wpscp-script', WPSP_ASSETS_URI . 'js/wpscp-script.js', array('jquery'), WPSP_VERSION, false);
            wp_localize_script(
                'wpscp-script',
                'wpscp_ajax',
                array('ajax_url' => admin_url('admin-ajax.php'), '_wpnonce' => wp_create_nonce('wp_rest'))
            );
            wp_enqueue_script('md5.min.js', WPSP_ASSETS_URI . 'js/vendor/md5.min.js', array(), WPSP_VERSION, true);
            wp_enqueue_script('wpsp-socialprofile', WPSP_ASSETS_URI . 'js/wpsp-socialprofile.js', array('jquery', 'jquery-kylefoxModal', 'md5.min.js'), WPSP_VERSION, true);
            wp_localize_script('wpsp-socialprofile', 'wpscpSocialProfile', array(
                'plugin_url'    => WPSP_PLUGIN_ROOT_URI,
                'nonce'         => wp_create_nonce('wpscp-pro-social-profile'),
                'redirect_url'  => WPSP_SOCIAL_OAUTH2_TOKEN_MIDDLEWARE,
                'is_active_pro' => class_exists('WPSP_PRO')
            ));
        }
        // admin notice for all wordpress dashboard
        wp_enqueue_style('wpscp-admin-notice', WPSP_ASSETS_URI . 'css/wpscp-admin-notice.css', array(), WPSP_VERSION, 'all');
    }

    /**
     * Admin bar Script
     * add some css and js in adminbar
     *
     * @since 2.3.1
     */
    public function adminbar_script()
    {
        if (is_admin_bar_showing()) {
            wp_enqueue_style('wpscp-adminbar', WPSP_ASSETS_URI . 'css/adminbar.css', array(), WPSP_VERSION, 'all');
            wp_enqueue_script('wpscp-adminbar', WPSP_ASSETS_URI . 'js/adminbar.js', array('jquery'), WPSP_VERSION, false);
        }
    }
}
