<?php

namespace WPSP;

class Installer
{
    public function __construct()
    {
        add_action('plugins_loaded', array($this, 'plugin_redirect'), 90);
    }

    public function plugin_redirect()
    {
        if (get_option('wpsp_do_activation_redirect', false)) {
            delete_option('wpsp_do_activation_redirect');
            wp_safe_redirect(admin_url('admin.php?page=' . WPSP_SETTINGS_SLUG));
        }
    }


    public function migrate()
    {
        // if old version data exists then run migration
        if(get_option('wpscp_options')){
            Migration::version_3_to_4();
        }
        // update version
        if (version_compare(get_option('wpsp_version'), WPSP_VERSION, '<')) {
            if (get_option('wpsp_version') != WPSP_VERSION) {
                update_option('wpsp_version', WPSP_VERSION);
            }
        }
    }
}
