<?php

namespace WPSP\Admin\Settings;

use WPSP\Admin\Settings;
use WPSP\Helper;

class Assets
{
    protected $pageSlug;
    public $settings;

    public function __construct($slug, $settings)
    {
        $this->pageSlug = $slug;
        $this->settings = $settings;
        // settings enqueue
        add_action('admin_enqueue_scripts', [$this, 'settings_scripts']);
    }

    public function settings_scripts($hook)
    {
        // $current_screen = \get_current_screen();
        if(!(strpos($hook, '_page_' . WPSP_SETTINGS_SLUG) !== false)){
            return;
        }

        add_action('wp_print_scripts', function () {
            $isSkip = apply_filters('schedulepress_skip_no_conflict', false);

            if ($isSkip) {
                return;
            }

            global $wp_scripts;
            if (!$wp_scripts) {
                return;
            }

            $pluginUrl = plugins_url();
            foreach ($wp_scripts->queue as $script) {
                $src = $wp_scripts->registered[$script]->src;
                if (strpos($src, $pluginUrl) !== false && !strpos($src, WPSP_PLUGIN_SLUG) !== false) {
                    wp_dequeue_script($wp_scripts->registered[$script]->handle);
                }
            }
        }, 1);

        if ($hook === 'toplevel_page_' . WPSP_SETTINGS_SLUG || WPSP_SETTINGS_SLUG . '_page_' . WPSP_SETTINGS_SLUG . '-calendar' === $hook){            // Load admin style sheet and JavaScript
            $dep = include WPSCP_ADMIN_DIR_PATH . 'Settings/assets/js/admin.asset.php';
            wp_enqueue_style(WPSP_PLUGIN_SLUG.'-icon', WPSP_ADMIN_URL . 'Settings/assets/icon/style.css', array(), $dep['version']);
            wp_enqueue_style(WPSP_PLUGIN_SLUG, WPSP_ADMIN_URL . 'Settings/assets/css/admin.css', array(WPSP_PLUGIN_SLUG.'-icon'), $dep['version']);
            wp_enqueue_script(WPSP_PLUGIN_SLUG, WPSP_ADMIN_URL . 'Settings/assets/js/admin.js', $dep['dependencies'], $dep['version']);
            wp_localize_script(WPSP_PLUGIN_SLUG, 'wpspSettingsGlobal', apply_filters('wpsp_settings_global', array(
                'api_nonce' => wp_create_nonce('wp_rest'),
                'api_url' => rest_url(WPSP_PLUGIN_SLUG . '/v1/'),
                'settings' => $this->settings->get_settings_array(),
                'plugin_root_uri' => WPSP_PLUGIN_ROOT_URI,
                'plugin_root_path' => WPSP_ROOT_DIR_PATH,
                'assets_path'     => WPSP_PLUGIN_ROOT_URI.'assets/',
                'image_path'     => WPSP_PLUGIN_ROOT_URI.'assets/images/',
                'admin_image_path'  => WPSP_PLUGIN_ROOT_URI.'includes/Admin/Settings/app/assets/images',
                'free_version'     => WPSP_VERSION,
                'admin_ajax'       => admin_url( 'admin-ajax.php' ),
                'pro_version'      => (defined('WPSP_PRO_VERSION') ? WPSP_PRO_VERSION : '')
            )));
        }
        else if (strpos($hook, '_page_' . WPSP_SETTINGS_SLUG) !== false){
            $dep = include WPSCP_ADMIN_DIR_PATH . 'Settings/assets/js/calendar.asset.php';
            wp_enqueue_style(WPSP_PLUGIN_SLUG.'-icon', WPSP_ADMIN_URL . 'Settings/assets/icon/style.css', array(), $dep['version']);
            wp_enqueue_style(WPSP_PLUGIN_SLUG, WPSP_ADMIN_URL . 'Settings/assets/css/calendar.css', array(WPSP_PLUGIN_SLUG.'-icon'), $dep['version']);
            wp_enqueue_script(WPSP_PLUGIN_SLUG, WPSP_ADMIN_URL . 'Settings/assets/js/calendar.js', $dep['dependencies'], $dep['version']);
            wp_localize_script(WPSP_PLUGIN_SLUG, 'wpspSettingsGlobal', apply_filters('wpsp_settings_calendar', array(
                'name'          => 'calendar',
                'type'          => 'calendar',
                'label'         => null,
                'priority'      => 5,
                'start_of_week' => (int) get_option('start_of_week', 0),
                'schedule_time' => Helper::get_settings('calendar_schedule_time'),
                'rest_route'    => '/wpscp/v1/calendar',
                'timeZone'      => wp_timezone_string(),
                'image_path'    => WPSP_PLUGIN_ROOT_URI.'assets/images/',
                'post_types'    => array_values(Settings::normalize_options(\WPSP\Helper::get_allow_post_types())),
            )));
        }
    }
}
