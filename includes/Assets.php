<?php

namespace WPSP;

use WPSP\Social\InstantShare;

class Assets
{
    public function __construct()
    {
        // admin script
        add_action('enqueue_block_assets', [$this, 'guten_scripts']);
        add_action('admin_enqueue_scripts', [$this, 'plugin_scripts']);
        // adminbar enqueue
        add_action('admin_enqueue_scripts', [$this, 'adminbar_script']);
        add_action('admin_enqueue_scripts', [$this, 'dequeue_script']);
        add_action('wp_enqueue_scripts', [$this, 'adminbar_script']);
        
        add_action( 'elementor/editor/after_enqueue_scripts', function () {
            $allow_post_types = \WPSP\Helper::get_all_allowed_post_type();
            wp_enqueue_script('jquery-kylefoxModal', WPSP_ASSETS_URI . 'js/vendor/jquery.modal.min.js', array('jquery'), WPSP_VERSION, false);
            wp_enqueue_script( 'wpscp-el-editor', WPSP_ASSETS_URI . 'js/elementor-editor.js', array( 'jquery', 'tipsy' ), WPSP_VERSION, true );
            wp_enqueue_style('jquery-kylefoxModal', WPSP_ASSETS_URI . 'css/vendor/jquery.modal.min.css', array(), WPSP_VERSION, 'all');
            wp_enqueue_style( 'wpscp-el-editor', WPSP_ASSETS_URI . 'css/elementor-editor.css',array(), WPSP_VERSION, 'all' );
            wp_localize_script('wpscp-el-editor', 'wpscpSocialProfile', array(
                'nonce'                 => wp_create_nonce('wpscp-pro-social-profile'),
                'is_post_type_selected' => in_array( get_post_type( get_the_ID() ), $allow_post_types),
            ));
        } );

	    
    }

    /**
     * Gutten Support
     * @since 1.2.0
     */
    public function guten_scripts()
    {
        global $post_type;
        $allow_post_types = \WPSP\Helper::get_all_allowed_post_type();
        $allow_post_types = (!empty($allow_post_types) ? $allow_post_types : array('post'));
        if (!in_array($post_type, $allow_post_types) || !is_admin()) {
            return;
        }
        $socialshareimage = get_post_meta( get_the_id(), '_wpscppro_custom_social_share_image', true);
        $imageUrl = '';
        if( $socialshareimage != '' ) {
            $imageUrl = wp_get_attachment_image_src($socialshareimage, 'full');
            if( !empty( $imageUrl[0] ) ) {
                $imageUrl = $imageUrl[0];
            }
        }

        wp_enqueue_style('google-fonts', 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap', array(), WPSP_VERSION, 'all');
        wp_enqueue_style('wps-publish-button', WPSP_ASSETS_URI . 'css/wpspl-admin.css', array(), WPSP_VERSION, 'all');
        wp_enqueue_style(WPSP_PLUGIN_SLUG.'-icon', WPSP_ADMIN_URL . 'Settings/assets/icon/style.css', array(), WPSP_VERSION );
        wp_enqueue_script('wps-publish-button', WPSP_ASSETS_URI . 'js/wpspl-admin.min.js', array('wp-components', 'wp-data', 'wp-edit-post', 'wp-editor', 'wp-element', 'wp-i18n', 'wp-plugins'), WPSP_VERSION, true);
        wp_localize_script('wps-publish-button', 'WPSchedulePostsFree', array(
            'nonce'                               => wp_create_nonce('wpscp-pro-social-profile'),
            'publishImmediately'                  => __('Current Date', 'wp-scheduled-posts'),
            'publishFutureDate'                   => __('Future Date', 'wp-scheduled-posts'),
            'publish_button_off'                  => \WPSP\Helper::get_settings('show_publish_post_button'),
            'allowedPostTypes'                    => $allow_post_types,
            'assetsURI'                           => WPSP_ASSETS_URI,
            'adminURL'                            => admin_url(),
            'wpsp_settings_name'                  => WPSP_SETTINGS_NAME,
            '_wpscppro_custom_social_share_image' => $imageUrl,
            'is_pro'                              => class_exists('WPSP_PRO') ? true : false,
            'currentTime'                         => array(
                'date'     => current_time('mysql'),
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
        $_wpscppro_custom_social_share_image = get_post_meta(get_the_id(), '_wpscppro_custom_social_share_image', true);

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
                array( 
                    'is_active_classic_editor' => Helper::is_enable_classic_editor(),
                    'ajax_url' => admin_url('admin-ajax.php'), 
                    '_wpnonce' => wp_create_nonce('wp_rest'),
                    '_wpscppro_custom_social_share_image'   => $_wpscppro_custom_social_share_image,
                )
            );
            wp_enqueue_script('md5.min.js', WPSP_ASSETS_URI . 'js/vendor/md5.min.js', array(), WPSP_VERSION, true);
            wp_enqueue_script('wpsp-socialprofile', WPSP_ASSETS_URI . 'js/wpsp-socialprofile.js', array('jquery', 'jquery-kylefoxModal', 'md5.min.js'), WPSP_VERSION, true);
            wp_localize_script('wpsp-socialprofile', 'wpscpSocialProfile', array(
                'plugin_url'               => WPSP_PLUGIN_ROOT_URI,
                'nonce'                    => wp_create_nonce('wpscp-pro-social-profile'),
                'redirect_url'             => WPSP_SOCIAL_OAUTH2_TOKEN_MIDDLEWARE,
                'is_active_pro'            => class_exists('WPSP_PRO'),
                'is_active_classis_editor' => Helper::is_enable_classic_editor(),
            ));
        }
        // admin notice for all wordpress dashboard
        wp_enqueue_style('wpscp-admin-notice', WPSP_ASSETS_URI . 'css/wpscp-admin-notice.css', array(), WPSP_VERSION, 'all');
    }

    public function get_current_page_slug() {
        if (isset($_GET['page'])) {
            return sanitize_text_field($_GET['page']);
        }
        return '';
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

    public function dequeue_script()
    {
        if ( 'schedulepress' === $this->get_current_page_slug() || 'schedulepress-calendar' === $this->get_current_page_slug() ) {
            wp_dequeue_style( 'pvfw-admin-css' );
        }
    }

}
