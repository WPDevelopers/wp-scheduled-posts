<?php

namespace WPSP;

class Installer
{
    public function __construct()
    {
        add_action('plugins_loaded', array($this, 'plugin_redirect'), 90);
        add_action( 'in_plugin_update_message-wp-scheduled-posts/wp-scheduled-posts.php', [$this, 'plugin_update_message'], 10, 2 );
    }

    /**
     * This is for upgrade message.
     *
     * @param mixed $plugin_data
     * @param mixed $response
     * @return void
     */
    public function plugin_update_message( $plugin_data, $response ) {
        if ( isset( $response->upgrade_notice ) && !empty($plugin_data['new_version']) ) {
            $new_version     = explode( '.', $plugin_data['new_version']);
            $current_version = explode( '.', WPSP_VERSION);
            $major           = $new_version[0] !== $current_version[0];
            $minor           = $new_version[1] !== $current_version[1];

            include WPSCP_ADMIN_DIR_PATH . "Notices/upgrade.php";
        }
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
        // Settings FallBack
        $settings = json_decode(get_option('wpsp_settings', '{}'));
        if( ! is_object( $settings ) || ( is_object( $settings ) && ! isset($settings->is_show_dashboard_widget) ) ) {
            do_action('wpsp_save_settings_default_value', WPSP_VERSION );
        }
        // social share meta migration
        if(version_compare(get_option('wpsp_version'), '4.0.1', '<=')){
            Migration::scheduled_post_social_share_meta_update();
        }
        if(version_compare(get_option('wpsp_version'), '4.1.6', '<=')){
            Migration::allow_categories();
        }
        if(version_compare(get_option('wpsp_version'), '5.0.0', '<')){
            Migration::version_4_to_5();
        }

        // update version
        if (version_compare(get_option('wpsp_version'), WPSP_VERSION, '<')) {
            if (get_option('wpsp_version') != WPSP_VERSION) {
                update_option('wpsp_version', WPSP_VERSION);
            }
        }

        // if old version data exists then run migration
        if(get_option('wpscp_options')){
            Migration::version_3_to_4();
        }
    }


   /**
     * Social profile status modification based on plugin version
     * @param $flag [which can be convert or anything like revert]
     */
	public function get_social_profile_status_modified_data( $settings, $flag = 'convert' )
	{
		$profile_list_array = ['facebook_profile_list','twitter_profile_list','linkedin_profile_list','pinterest_profile_list', 'instagram_profile_list'];
		foreach ($profile_list_array as  $profile_name) {
			if( isset( $settings[$profile_name] ) ) {
				$i = 0;
				foreach ($settings[$profile_name] as $key => &$profile) {
					if( $profile_name == 'linkedin_profile_list' && $profile['type'] === 'organization'  ) {
						if( $flag == 'convert' ) {
							$profile['__status'] = $profile['status'];
							$profile['status'] = false;
						}else{
							$profile['status'] = $profile['__status'];
						}
						continue;
					}
					if( $key !== 0 ) {
						if( $flag == 'convert' ) {
							$profile['__status'] = $profile['status'];
							$profile['status'] = false;
						}else{
							$profile['status'] = $profile['__status'];
						}
					}
					$i++;
				}
			}
		}
        update_option(WPSP_SETTINGS_NAME, json_encode($settings));
	}

}
