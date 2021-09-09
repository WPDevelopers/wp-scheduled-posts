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
		    wp_enqueue_script( 'wpscp-el-editor', WPSP_ASSETS_URI . 'js/elementor-editor.js', array( 'jquery', 'tipsy' ), time(), true );
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
        if (!in_array($post_type, $allow_post_types)) {
            return;
        }

        wp_enqueue_script('wps-publish-button', WPSP_ASSETS_URI . 'js/wpspl-admin.min.js', array('wp-components', 'wp-data', 'wp-edit-post', 'wp-editor', 'wp-element', 'wp-i18n', 'wp-plugins'), '1.0.0', true);
        wp_localize_script('wps-publish-button', 'WPSchedulePostsFree', array(
            'publishImmediately' => __('Publish Post Immediately', 'wp-scheduled-posts'),
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
            wp_enqueue_style('select2-css', WPSP_ASSETS_URI . 'css/vendor/select2.min.css', array(), filemtime(WPSP_ASSETS_DIR_PATH . 'css/vendor/select2.min.css'), 'all');
            wp_enqueue_style('jquery-datetimepicker', WPSP_ASSETS_URI . 'css/vendor/jquery.datetimepicker.min.css', array(), filemtime(WPSP_ASSETS_DIR_PATH . 'css/vendor/jquery.datetimepicker.min.css'), 'all');
            wp_enqueue_style('chung-timepicker', WPSP_ASSETS_URI . 'css/vendor/chung-timepicker.css', array(), filemtime(WPSP_ASSETS_DIR_PATH . 'css/vendor/chung-timepicker.css'), 'all');
            wp_enqueue_style('notifi', WPSP_ASSETS_URI . 'css/vendor/notifi.css', array(), filemtime(WPSP_ASSETS_DIR_PATH . 'css/vendor/notifi.css'), 'all');
            wp_enqueue_style('full-calendar', WPSP_ASSETS_URI . 'css/vendor/full-calendar.css', array(), filemtime(WPSP_ASSETS_DIR_PATH . 'css/vendor/full-calendar.css'), 'all');
            wp_enqueue_style('jquery-modal', WPSP_ASSETS_URI . 'css/vendor/jquery.modal.min.css', array(), filemtime(WPSP_ASSETS_DIR_PATH . 'css/vendor/jquery.modal.min.css'), 'all');
            wp_enqueue_style('jquery-timepicker', WPSP_ASSETS_URI . 'css/vendor/jquery.timepicker.min.css', array(), filemtime(WPSP_ASSETS_DIR_PATH . 'css/vendor/jquery.timepicker.min.css'), 'all');
            wp_enqueue_style('wpscp-admin', WPSP_ASSETS_URI . 'css/wpscp-admin.css', array(), filemtime(WPSP_ASSETS_DIR_PATH . 'css/wpscp-admin.css'), 'all');

            /**
             * JavaScript File
             */
            wp_enqueue_script('jquery-ui-draggable');
            wp_enqueue_script('jquery-ui-droppable');
            wp_enqueue_script('jquery-datetimepicker', WPSP_ASSETS_URI . 'js/vendor/jquery.datetimepicker.full.min.js', array('jquery'), filemtime(WPSP_ASSETS_DIR_PATH . 'js/vendor/jquery.datetimepicker.full.min.js'), false);
            wp_enqueue_script('chung-timepicker', WPSP_ASSETS_URI . 'js/vendor/chung-timepicker.js', array('jquery'), filemtime(WPSP_ASSETS_DIR_PATH . 'js/vendor/chung-timepicker.js'), false);
            wp_enqueue_script('timepicker', WPSP_ASSETS_URI . 'js/vendor/jquery.timepicker.min.js', array('jquery'), filemtime(WPSP_ASSETS_DIR_PATH . 'js/vendor/jquery.timepicker.min.js'), false);
            wp_enqueue_script('jquery-modal', WPSP_ASSETS_URI . 'js/vendor/jquery.modal.min.js', array('jquery'), filemtime(WPSP_ASSETS_DIR_PATH . 'js/vendor/jquery.modal.min.js'), false);
            wp_enqueue_script('select2', WPSP_ASSETS_URI . 'js/vendor/select2.full.js', array('jquery'), filemtime(WPSP_ASSETS_DIR_PATH . 'js/vendor/select2.full.js'), false);
            wp_enqueue_script('sweetalert', WPSP_ASSETS_URI . 'js/vendor/sweetalert.min.js', array('jquery'), filemtime(WPSP_ASSETS_DIR_PATH . 'js/vendor/sweetalert.min.js'), false);
            wp_enqueue_script('notifi', WPSP_ASSETS_URI . 'js/vendor/notifi.min.js', array('jquery'), filemtime(WPSP_ASSETS_DIR_PATH . 'js/vendor/notifi.min.js'), false);
            wp_enqueue_script('wpscp-script', WPSP_ASSETS_URI . 'js/wpscp-script.js', array('jquery'), filemtime(WPSP_ASSETS_DIR_PATH . 'js/wpscp-script.js'), false);
            wp_localize_script(
                'wpscp-script',
                'wpscp_ajax',
                array('ajax_url' => admin_url('admin-ajax.php'))
            );
            // calendar
            wp_enqueue_script('fullcalendar-core', WPSP_ASSETS_URI . 'js/vendor/fullcalendar/core/main.js', array('jquery'), filemtime(WPSP_ASSETS_DIR_PATH . 'js/vendor/fullcalendar/core/main.js'), false);
            wp_enqueue_script('fullcalendar-interaction', WPSP_ASSETS_URI . 'js/vendor/fullcalendar/interaction/main.js', array('jquery'), filemtime(WPSP_ASSETS_DIR_PATH . 'js/vendor/fullcalendar/interaction/main.js'), false);
            wp_enqueue_script('fullcalendar-daygrid', WPSP_ASSETS_URI . 'js/vendor/fullcalendar/daygrid/main.js', array('jquery'), filemtime(WPSP_ASSETS_DIR_PATH . 'js/vendor/fullcalendar/daygrid/main.js'), false);
            wp_enqueue_script('fullcalendar-timegrid', WPSP_ASSETS_URI . 'js/vendor/fullcalendar/timegrid/main.js', array('jquery'), filemtime(WPSP_ASSETS_DIR_PATH . 'js/vendor/fullcalendar/timegrid/main.js'), false);
            wp_enqueue_script('wpscp-fullcalendar', WPSP_ASSETS_URI . 'js/wpscp-fullcalendar-config.js', array('jquery'), filemtime(WPSP_ASSETS_DIR_PATH . 'js/wpscp-fullcalendar-config.js'), false);
            // in JavaScript, object properties are accessed as ajax_object.ajax_url, ajax_object.we_value
            $now = new \DateTime('now');
            $month = $now->format('m');
            $year = $now->format('Y');
            wp_localize_script(
                'wpscp-fullcalendar',
                'wpscp_calendar_ajax_object',
                array(
                    'ajax_url' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('wpscp-calendar-ajax-nonce'),
                    'calendar_rest_route' => site_url('/?rest_route=/wpscp/v1/post_type=post/month=' . $month . '/year=' . $year)
                )
            );
            wp_enqueue_script('wpsp-socialprofile', WPSP_ASSETS_URI . 'js/wpsp-socialprofile.js', array('jquery'), filemtime(WPSP_ASSETS_DIR_PATH . 'js/wpsp-socialprofile.js'), true);
            wp_localize_script('wpsp-socialprofile', 'wpscpSocialProfile', array(
                'plugin_url'    => WPSP_PLUGIN_ROOT_URI,
                'nonce'            => wp_create_nonce('wpscp-pro-social-profile'),
                'redirect_url'  => WPSP_SOCIAL_OAUTH2_TOKEN_MIDDLEWARE,
                'is_active_pro' => class_exists('WPSP_PRO')
            ));
        }
        // admin notice for all wordpress dashboard
        wp_enqueue_style('wpscp-admin-notice', WPSP_ASSETS_URI . 'css/wpscp-admin-notice.css', array(), filemtime(WPSP_ASSETS_DIR_PATH . 'css/wpscp-admin-notice.css'), 'all');
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
            wp_enqueue_style('wpscp-adminbar', WPSP_ASSETS_URI . 'css/adminbar.css', array(), filemtime(WPSP_ASSETS_DIR_PATH . 'css/adminbar.css'), 'all');
            wp_enqueue_script('wpscp-adminbar', WPSP_ASSETS_URI . 'js/adminbar.js', array('jquery'), filemtime(WPSP_ASSETS_DIR_PATH . 'js/adminbar.js'), false);
        }
    }
}
