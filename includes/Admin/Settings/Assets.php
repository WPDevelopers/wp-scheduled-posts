<?php

namespace WPSP\Admin\Settings;

class Assets
{
    protected $pageSlug;
    public $setting_array = [];

    public function __construct($slug, $settings)
    {
        $this->pageSlug = $slug;
        $this->setting_array =  $settings;
        // settings enqueue
        add_action('admin_enqueue_scripts', [$this, 'settings_scripts']);
    }

    public function settings_scripts($hook)
    {
        if ($hook !== 'toplevel_page_' . WPSP_SETTINGS_SLUG) return;
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

        // Load admin style sheet and JavaScript
        wp_enqueue_style(WPSP_PLUGIN_SLUG, WPSP_ADMIN_URL . 'Settings/assets/css/admin.css', array());

        wp_enqueue_script(WPSP_PLUGIN_SLUG, WPSP_ADMIN_URL . 'Settings/assets/js/admin.js', array());
        wp_localize_script(WPSP_PLUGIN_SLUG, 'wpspSettingsGlobal', apply_filters('wpsp_settings_global', array(
            'api_nonce' => wp_create_nonce('wp_rest'),
            'api_url' => rest_url(WPSP_PLUGIN_SLUG . '/v1/'),
            'settings' => $this->setting_array,
            'plugin_root_uri' => WPSP_PLUGIN_ROOT_URI,
            'plugin_root_path' => WPSP_ROOT_DIR_PATH,
            'free_version'     => WPSP_VERSION,
            'pro_version'      => (defined('WPSP_PRO_VERSION') ? WPSP_PRO_VERSION : '')
        )));
    }
}
