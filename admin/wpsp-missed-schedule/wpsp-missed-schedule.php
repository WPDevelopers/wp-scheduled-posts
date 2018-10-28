<?php 
	if ( ! defined( 'ABSPATH' ) ) exit;

	if ( ! defined(  'WPINC'  ) ) exit;

	if ( ! function_exists( 'add_action' ) )
		{
			header( 'HTTP/0.9 403 Forbidden' );
			header( 'HTTP/1.0 403 Forbidden' );
			header( 'HTTP/1.1 403 Forbidden' );
			header( 'HTTP/2.0 403 Forbidden' );
			header( 'Status: 403 Forbidden'  );
			header( 'Connection: Close'      );
				exit;
		}

	global $wp_version;

	if ( $wp_version < 2.1 )
		{
			wp_die( __( 'This plugin requires WordPress 2.1+ or greater: Activation Stopped!' ) );
		}
	else
		{
	if ( version_compare( '5.2.0', PHP_VERSION, '>=' ) )
		{
			wp_die( __( 'You can no longer run this plugin in PHP version prior to 5.2+ contact your hosting provider for system upgrade!<br />
			<br />Please note that to continue using a PHP version below 5.2+ need to install the 2013.1231 version of this plugin.' ) );
		}
	else
		{
	if ( defined( 'WPMSP_INTERVAL' ) or defined( 'WPMSP_POST_LIMIT' ) or defined( 'UPDATE_INTERVAL' ) or defined( 'UPDATE_POSTS' ) )
		{
			wp_die( __( '1 - This WordPress installation is infected with a malware variant of WP Missed Schedule: Activation Stopped!<br />
			<br />Uninstall all not original or forked versions of this plugin and retry: read more on the <a href="http://slangjis.org/unauthorized-code-use-and-copy-of-slangjis-plugins-or-themes/">cause of infection</a><br />
			<br /><br /><strong>This is because you used, or have used, an unpublished version of this plugin, and have another similar plugin installed and activated, or you have installed an unauthorized forked version, or have installed a copy of this plugin that does not respect the trademark, and assigning the license to the authors who created it.</strong><br />
			<br /><br />It may also happen that you did not perform the correct uninstallation procedure of the previous version, and the correct installation of the latest current version, or have another similar plugin activated.<br />
			<br />The correct procedure to use is only this:<br />
			<br />- Deactivate all similar plugins that have previously installed and activated.<br />
			<br />- Decide if you need to uninstall all similar plugins to resolve the issue, if this message persists.<br />
			<br />- Clean manually the options table if the similar plugins not cleaning your options data automatically.<br />
			<br />- Deactivate the old version, or the version to be need to update, of plugin WP Missed Schedule.<br />
			<br />- Flushing cache, and wait for the necessary time.<br />
			<br />- Install, or copy manually via FTP, the new version of plugin WP Missed Schedule.<br />
			<br />- Activate the new version of plugin WP Missed Schedule.<br />
			<br />Overwrite an old version of this plugin directly via FTP, or directly overwrite a version to upgrade, it may also be the cause of this type of error.<br />
			<br /><u>Remember that this plugin does not work properly if installed in the mu-plugin directory!</u>' ) );
		}
	else
		{
	if ( get_option( 'wp_scheduled_missed_time' ) )
		{
			wp_die( __( '3 - This WordPress installation is infected with a malware variant of WP Missed Schedule: Activation Stopped!<br />
			<br />Uninstall all not original or forked versions of this plugin and retry: read more on the <a href="http://slangjis.org/unauthorized-code-use-and-copy-of-slangjis-plugins-or-themes/">cause of infection</a><br />
			<br /><br /><strong>This is because you used, or have used, an unpublished version of this plugin, and have another similar plugin installed and activated, or you have installed an unauthorized forked version, or have installed a copy of this plugin that does not respect the trademark, and assigning the license to the authors who created it.</strong><br />
			<br /><br />It may also happen that you did not perform the correct uninstallation procedure of the previous version, and the correct installation of the latest current version, or have another similar plugin activated.<br />
			<br />The correct procedure to use is only this:<br />
			<br />- Deactivate all similar plugins that have previously installed and activated.<br />
			<br />- Decide if you need to uninstall all similar plugins to resolve the issue, if this message persists.<br />
			<br />- Clean manually the options table if the similar plugins not cleaning your options data automatically.<br />
			<br />- Deactivate the old version, or the version to be need to update, of plugin WP Missed Schedule.<br />
			<br />- Flushing cache, and wait for the necessary time.<br />
			<br />- Install, or copy manually via FTP, the new version of plugin WP Missed Schedule.<br />
			<br />- Activate the new version of plugin WP Missed Schedule.<br />
			<br />Overwrite an old version of this plugin directly via FTP, or directly overwrite a version to upgrade, it may also be the cause of this type of error.<br />
			<br /><u>Remember that this plugin does not work properly if installed in the mu-plugin directory!</u>' ) );
		}
	else
		{
	if ( $wp_version >= 2.8 )
	if ( get_transient( 'wp_scheduled_missed_time' ) )
		{
			wp_die( __( '5 - This WordPress installation is infected with a malware variant of WP Missed Schedule: Activation Stopped!<br />
			<br />Uninstall all not original or forked versions of this plugin and retry: read more on the <a href="http://slangjis.org/unauthorized-code-use-and-copy-of-slangjis-plugins-or-themes/">cause of infection</a><br />
			<br /><br /><strong>This is because you used, or have used, an unpublished version of this plugin, and have another similar plugin installed and activated, or you have installed an unauthorized forked version, or have installed a copy of this plugin that does not respect the trademark, and assigning the license to the authors who created it.</strong><br />
			<br /><br />It may also happen that you did not perform the correct uninstallation procedure of the previous version, and the correct installation of the latest current version, or have another similar plugin activated.<br />
			<br />The correct procedure to use is only this:<br />
			<br />- Deactivate all similar plugins that have previously installed and activated.<br />
			<br />- Decide if you need to uninstall all similar plugins to resolve the issue, if this message persists.<br />
			<br />- Clean manually the options table if the similar plugins not cleaning your options data automatically.<br />
			<br />- Deactivate the old version, or the version to be need to update, of plugin WP Missed Schedule.<br />
			<br />- Flushing cache, and wait for the necessary time.<br />
			<br />- Install, or copy manually via FTP, the new version of plugin WP Missed Schedule.<br />
			<br />- Activate the new version of plugin WP Missed Schedule.<br />
			<br />Overwrite an old version of this plugin directly via FTP, or directly overwrite a version to upgrade, it may also be the cause of this type of error.<br />
			<br /><u>Remember that this plugin does not work properly if installed in the mu-plugin directory!</u>' ) );
		}
	else
		{
	if ( function_exists( 'wpms_init' ) )
		{
			function wpms_psd_init()
				{
					deactivate_plugins( plugin_basename( __FILE__ ) );
				}
			add_action( 'admin_init', 'wpms_psd_init', 0 );

			delete_option( 'scheduled_missed' );
			delete_transient( 'scheduled_missed' );
			if ( $wp_version >= 3.0 )
				{
					delete_site_option( 'scheduled_missed' );
					delete_site_transient( 'scheduled_missed' );
				}

			function wpms_ant_init()
				{
?>
<div class="updated notice notice-warning is-dismissible">
<p>Only one instance or task of the same plugin is allowed and recommended to avoid conflicts: <strong>Second instance or task of the plugin WP missed Schedule NOT activated</strong>.</p>
</div>
<div class="updated notice is-dismissible">
<p>Plugin WP Missed Schedule <strong>deactivated</strong>.</p>
</div>
<script>window.jQuery && jQuery( function( $ ) { $( 'div#message.updated' ).remove(); } );</script>
<?php 
				}
			add_action( 'admin_notices', 'wpms_ant_init' );
		}
	else
		{
	if ( function_exists( 'fix_missed_shedule' ) )
		{
			function wpms_psd_fms()
				{
					deactivate_plugins( plugin_basename( __FILE__ ) );
				}
			add_action( 'admin_init', 'wpms_psd_fms', 0 );

			delete_option( 'scheduled_missed' );
			delete_transient( 'scheduled_missed' );
			if ( $wp_version >= 3.0 )
				{
					delete_site_option( 'scheduled_missed' );
					delete_site_transient( 'scheduled_missed' );
				}

			function wpms_ant_fms()
				{
?>
<div class="updated notice notice-error is-dismissible">
<p>Before activate plugin WP Missed Schedule deactivate or uninstall conflicting plugin: <strong>ByREV Fix Missed Schedule</strong>.</p>
</div>
<div class="updated notice notice-warning is-dismissible">
<p>Only one instance of the plugin with the same functionality or task is allowed and recommended to avoid conflicts: <strong>Plugin WP Missed Schedule NOT activated</strong>.</p>
</div>
<div class="updated notice is-dismissible">
<p>Plugin WP Missed Schedule <strong>deactivated</strong>.</p>
</div>
<script>window.jQuery && jQuery( function( $ ) { $( 'div#message.updated' ).remove(); } );</script>
<?php 
				}
			add_action( 'admin_notices', 'wpms_ant_fms' );
		}
	else
		{
	if ( get_option( 'byrev_fixshedule_next_verify' ) )
		{
			function wpms_psd_fms2()
				{
					deactivate_plugins( plugin_basename( __FILE__ ) );
				}
			add_action( 'admin_init', 'wpms_psd_fms2', 0 );

			delete_option( 'scheduled_missed' );
			delete_transient( 'scheduled_missed' );
			if ( $wp_version >= 3.0 )
				{
					delete_site_option( 'scheduled_missed' );
					delete_site_transient( 'scheduled_missed' );
				}

			function wpms_ant_fms2()
				{
?>
<div class="updated notice notice-error is-dismissible">
<p>Before activate plugin WP Missed Schedule please delete conflicting option: <strong>byrev_fixshedule_next_verify</strong>.</p>
</div>
<div class="updated notice notice-warning is-dismissible">
<p>To avoid conflicts please clean orphaned options not cleaned after deactivation or uninstallation of plugin: <strong>ByREV Fix Missed Schedule</strong>.</p>
</div>
<div class="updated notice is-dismissible">
<p>Plugin WP Missed Schedule <strong>deactivated</strong>.</p>
</div>
<script>window.jQuery && jQuery( function( $ ) { $( 'div#message.updated' ).remove(); } );</script>
<?php 
				}
			add_action( 'admin_notices', 'wpms_ant_fms2' );
		}
	else
		{
	if ( function_exists( 'missed_schedule' ) )
		{
			function wpms_psd_mms()
				{
					deactivate_plugins( plugin_basename( __FILE__ ) );
				}
			add_action( 'admin_init', 'wpms_psd_mms', 0 );

			delete_option( 'scheduled_missed' );
			delete_transient( 'scheduled_missed' );
			if ( $wp_version >= 3.0 )
				{
					delete_site_option( 'scheduled_missed' );
					delete_site_transient( 'scheduled_missed' );
				}

			function wpms_ant_mms()
				{
?>
<div class="updated notice notice-error is-dismissible">
<p>Before activate plugin WP Missed Schedule deactivate or uninstall conflicting plugin: <strong>My Missed Schedule</strong>.</p>
</div>
<div class="updated notice notice-warning is-dismissible">
<p>Only one instance of the plugin with the same functionality or task is allowed and recommended to avoid conflicts: <strong>Plugin WP Missed Schedule NOT activated</strong>.</p>
</div>
<div class="updated notice is-dismissible">
<p>Plugin WP Missed Schedule <strong>deactivated</strong>.</p>
</div>
<script>window.jQuery && jQuery( function( $ ) { $( 'div#message.updated' ).remove(); } );</script>
<?php 
				}
			add_action( 'admin_notices', 'wpms_ant_mms' );
		}
	else
		{
	if ( wp_get_schedule( 'missed_schedule_cron' ) )
		{
			function wpms_psd_mms2()
				{
					deactivate_plugins( plugin_basename( __FILE__ ) );
				}
			add_action( 'admin_init', 'wpms_psd_mms2', 0 );

			delete_option( 'scheduled_missed' );
			delete_transient( 'scheduled_missed' );
			if ( $wp_version >= 3.0 )
				{
					delete_site_option( 'scheduled_missed' );
					delete_site_transient( 'scheduled_missed' );
				}

			function wpms_ant_mms2()
				{
?>
<div class="updated notice notice-error is-dismissible">
<p>Before activate plugin WP Missed Schedule please delete conflicting cron event: <strong>missed_schedule_cron</strong>.</p>
</div>
<div class="updated notice notice-warning is-dismissible">
<p>To avoid conflicts please clean orphaned cron events not cleaned after deactivation or uninstallation of plugin: <strong>My Missed Schedule</strong>.</p>
</div>
<div class="updated notice is-dismissible">
<p>Plugin WP Missed Schedule <strong>deactivated</strong>.</p>
</div>
<script>window.jQuery && jQuery( function( $ ) { $( 'div#message.updated' ).remove(); } );</script>
<?php 
				}
			add_action( 'admin_notices', 'wpms_ant_mms2' );
		}
	else
		{
	if ( class_exists( 'Scheduled_Post_Guardian_Plugin' ) )
		{
			function wpms_psd_spg()
				{
					deactivate_plugins( plugin_basename( __FILE__ ) );
				}
			add_action( 'admin_init', 'wpms_psd_spg', 0 );

			delete_option( 'scheduled_missed' );
			delete_transient( 'scheduled_missed' );
			if ( $wp_version >= 3.0 )
				{
					delete_site_option( 'scheduled_missed' );
					delete_site_transient( 'scheduled_missed' );
				}

			function wpms_ant_spg()
				{
?>
<div class="updated notice notice-error is-dismissible">
<p>Before activate plugin WP Missed Schedule deactivate or uninstall conflicting plugin: <strong>Scheduled Post Guardian</strong>.</p>
</div>
<div class="updated notice notice-warning is-dismissible">
<p>Only one instance of the plugin with the same functionality or task is allowed and recommended to avoid conflicts: <strong>Plugin WP Missed Schedule NOT activated</strong>.</p>
</div>
<div class="updated notice is-dismissible">
<p>Plugin WP Missed Schedule <strong>deactivated</strong>.</p>
</div>
<script>window.jQuery && jQuery( function( $ ) { $( 'div#message.updated' ).remove(); } );</script>
<?php 
				}
			add_action( 'admin_notices', 'wpms_ant_spg' );
		}
	else
		{
	if ( get_option( 'scheduled_post_guardian_next_run' ) )
		{
			function wpms_psd_spg2()
				{
					deactivate_plugins( plugin_basename( __FILE__ ) );
				}
			add_action( 'admin_init', 'wpms_psd_spg2', 0 );

			delete_option( 'scheduled_missed' );
			delete_transient( 'scheduled_missed' );
			if ( $wp_version >= 3.0 )
				{
					delete_site_option( 'scheduled_missed' );
					delete_site_transient( 'scheduled_missed' );
				}

			function wpms_ant_spg2()
				{
?>
<div class="updated notice notice-error is-dismissible">
<p>Before activate plugin WP Missed Schedule please delete conflicting option: <strong>scheduled_post_guardian_next_run</strong>.</p>
</div>
<div class="updated notice notice-warning is-dismissible">
<p>To avoid conflicts please clean orphaned options not cleaned after deactivation or uninstallation of plugin: <strong>Scheduled Post Guardian</strong>.</p>
</div>
<div class="updated notice is-dismissible">
<p>Plugin WP Missed Schedule <strong>deactivated</strong>.</p>
</div>
<script>window.jQuery && jQuery( function( $ ) { $( 'div#message.updated' ).remove(); } );</script>
<?php 
				}
			add_action( 'admin_notices', 'wpms_ant_spg2' );
		}
	else
		{
	if ( function_exists( 'pubScheduledPost' ) )
		{
			function wpms_psd_pbs()
				{
					deactivate_plugins( plugin_basename( __FILE__ ) );
				}
			add_action( 'admin_init', 'wpms_psd_pbs', 0 );

			delete_option( 'scheduled_missed' );
			delete_transient( 'scheduled_missed' );
			if ( $wp_version >= 3.0 )
				{
					delete_site_option( 'scheduled_missed' );
					delete_site_transient( 'scheduled_missed' );
				}

			function wpms_ant_pbs()
				{
?>
<div class="updated notice notice-error is-dismissible">
<p>Before activate plugin WP Missed Schedule deactivate or uninstall conflicting plugin: <strong>Scheduled Post Trigger</strong>.</p>
</div>
<div class="updated notice notice-warning is-dismissible">
<p>Only one instance of the plugin with the same functionality or task is allowed and recommended to avoid conflicts: <strong>Plugin WP Missed Schedule NOT activated</strong>.</p>
</div>
<div class="updated notice is-dismissible">
<p>Plugin WP Missed Schedule <strong>deactivated</strong>.</p>
</div>
<script>window.jQuery && jQuery( function( $ ) { $( 'div#message.updated' ).remove(); } );</script>
<?php 
				}
			add_action( 'admin_notices', 'wpms_ant_pbs' );
		}
	else
		{
	if ( function_exists( 'pubMissedPosts' ) )
		{
			function wpms_psd_pbp()
				{
					deactivate_plugins( plugin_basename( __FILE__ ) );
				}
			add_action( 'admin_init', 'wpms_psd_pbp', 0 );

			delete_option( 'scheduled_missed' );
			delete_transient( 'scheduled_missed' );
			if ( $wp_version >= 3.0 )
				{
					delete_site_option( 'scheduled_missed' );
					delete_site_transient( 'scheduled_missed' );
				}

			function wpms_ant_pbp()
				{
?>
<div class="updated notice notice-error is-dismissible">
<p>Before activate plugin WP Missed Schedule deactivate or uninstall conflicting plugin: <strong>Scheduled Post Trigger</strong>.</p>
</div>
<div class="updated notice notice-warning is-dismissible">
<p>Only one instance of the plugin with the same functionality or task is allowed and recommended to avoid conflicts: <strong>Plugin WP Missed Schedule NOT activated</strong>.</p>
</div>
<div class="updated notice is-dismissible">
<p>Plugin WP Missed Schedule <strong>deactivated</strong>.</p>
</div>
<script>window.jQuery && jQuery( function( $ ) { $( 'div#message.updated' ).remove(); } );</script>
<?php 
				}
			add_action( 'admin_notices', 'wpms_ant_pbp' );
		}
	else
		{
	if ( class_exists( 'SimpulMissedSchedule' ) )
		{
			function wpms_psd_sms()
				{
					deactivate_plugins( plugin_basename( __FILE__ ) );
				}
			add_action( 'admin_init', 'wpms_psd_sms', 0 );

			delete_option( 'scheduled_missed' );
			delete_transient( 'scheduled_missed' );
			if ( $wp_version >= 3.0 )
				{
					delete_site_option( 'scheduled_missed' );
					delete_site_transient( 'scheduled_missed' );
				}

			function wpms_ant_sms()
				{
?>
<div class="updated notice notice-error is-dismissible">
<p>Before activate plugin WP Missed Schedule deactivate or uninstall conflicting plugin: <strong>Simpul Missed Schedule</strong>.</p>
</div>
<div class="updated notice notice-warning is-dismissible">
<p>Only one instance of the plugin with the same functionality or task is allowed and recommended to avoid conflicts: <strong>Plugin WP Missed Schedule NOT activated</strong>.</p>
</div>
<div class="updated notice is-dismissible">
<p>Plugin WP Missed Schedule <strong>deactivated</strong>.</p>
</div>
<script>window.jQuery && jQuery( function( $ ) { $( 'div#message.updated' ).remove(); } );</script>
<?php 
				}
			add_action( 'admin_notices', 'wpms_ant_sms' );
		}
	else
		{
	if ( get_option( 'simpul_missed_schedule' ) )
		{
			function wpms_psd_sms2()
				{
					deactivate_plugins( plugin_basename( __FILE__ ) );
				}
			add_action( 'admin_init', 'wpms_psd_sms2', 0 );

			delete_option( 'scheduled_missed' );
			delete_transient( 'scheduled_missed' );
			if ( $wp_version >= 3.0 )
				{
					delete_site_option( 'scheduled_missed' );
					delete_site_transient( 'scheduled_missed' );
				}

			function wpms_ant_sms2()
				{
?>
<div class="updated notice notice-error is-dismissible">
<p>Before activate plugin WP Missed Schedule please delete conflicting option: <strong>simpul_missed_schedule</strong>.</p>
</div>
<div class="updated notice notice-warning is-dismissible">
<p>To avoid conflicts please clean orphaned options not cleaned after deactivation or uninstallation of plugin: <strong>Simpul Missed Schedule</strong>.</p>
</div>
<div class="updated notice is-dismissible">
<p>Plugin WP Missed Schedule <strong>deactivated</strong>.</p>
</div>
<script>window.jQuery && jQuery( function( $ ) { $( 'div#message.updated' ).remove(); } );</script>
<?php 
				}
			add_action( 'admin_notices', 'wpms_ant_sms2' );
		}
	else
		{
	if ( class_exists( 'WP_TimeZone' ) )
		{
			function wpms_psd_wpt()
				{
					deactivate_plugins( plugin_basename( __FILE__ ) );
				}
			add_action( 'admin_init', 'wpms_psd_wpt', 0 );

			delete_option( 'scheduled_missed' );
			delete_transient( 'scheduled_missed' );
			if ( $wp_version >= 3.0 )
				{
					delete_site_option( 'scheduled_missed' );
					delete_site_transient( 'scheduled_missed' );
				}

			function wpms_ant_wpt()
				{
?>
<div class="updated notice notice-error is-dismissible">
<p>Before activate plugin WP Missed Schedule deactivate or uninstall conflicting plugin: <strong>WP TimeZone</strong>.</p>
</div>
<div class="updated notice notice-warning is-dismissible">
<p>Only one instance of the plugin with the same functionality or task is allowed and recommended to avoid conflicts: <strong>Plugin WP Missed Schedule NOT activated</strong>.</p>
</div>
<div class="updated notice is-dismissible">
<p>Plugin WP Missed Schedule <strong>deactivated</strong>.</p>
</div>
<script>window.jQuery && jQuery( function( $ ) { $( 'div#message.updated' ).remove(); } );</script>
<?php 
				}
			add_action( 'admin_notices', 'wpms_ant_wpt' );
		}
	else
		{
	if ( get_option( 'wpt_scheduled_check' ) )
		{
			function wpms_psd_wpt2()
				{
					deactivate_plugins( plugin_basename( __FILE__ ) );
				}
			add_action( 'admin_init', 'wpms_psd_wpt2', 0 );

			delete_option( 'scheduled_missed' );
			delete_transient( 'scheduled_missed' );
			if ( $wp_version >= 3.0 )
				{
					delete_site_option( 'scheduled_missed' );
					delete_site_transient( 'scheduled_missed' );
				}

			function wpms_ant_wpt2()
				{
?>
<div class="updated notice notice-error is-dismissible">
<p>Before activate plugin WP Missed Schedule please delete conflicting option: <strong>wpt_scheduled_check</strong>.</p>
</div>
<div class="updated notice notice-warning is-dismissible">
<p>To avoid conflicts please clean orphaned options not cleaned after deactivation or uninstallation of plugin: <strong>WP TimeZone</strong>.</p>
</div>
<div class="updated notice is-dismissible">
<p>Plugin WP Missed Schedule <strong>deactivated</strong>.</p>
</div>
<script>window.jQuery && jQuery( function( $ ) { $( 'div#message.updated' ).remove(); } );</script>
<?php 
				}
			add_action( 'admin_notices', 'wpms_ant_wpt2' );
		}
	else
		{
	function wpms_activation()
		{
			if ( ! current_user_can( 'activate_plugins' ) )
				return;

			global $wp_version;

			if ( $wp_version >= 2.1 )
				{
//					delete_option( 'byrev_fixshedule_next_verify' );                                                      # Future ALPHA conding reserved
//					delete_option( 'scheduled_post_guardian_next_run' );                                                  # Future ALPHA conding reserved
//					delete_option( 'simpul_missed_schedule' );                                                            # Future ALPHA conding reserved
//					delete_option( 'wpt_scheduled_check' );                                                               # Future ALPHA conding reserved
					delete_option( 'wp_missed_schedule' );
					delete_option( 'wp_missed_scheduled' );
					delete_option( 'wp_schedule_missed' );
					delete_option( 'wp_scheduled_missed' );
					delete_option( 'missed_schedule' );
					delete_option( 'missed_scheduled' );
					delete_option( 'schedule_missed' );
					delete_option( 'scheduled_missed' );

					wp_clear_scheduled_hook( 'missed_schedule' );
					wp_clear_scheduled_hook( 'missed_scheduled' );
//					wp_clear_scheduled_hook( 'missed_schedule_cron' );                                                    # Future ALPHA conding reserved
					wp_clear_scheduled_hook( 'missed_scheduled_cron' );
					wp_clear_scheduled_hook( 'wp_missed_schedule' );
					wp_clear_scheduled_hook( 'wp_missed_scheduled' );
					wp_clear_scheduled_hook( 'wp_missed_schedule_cron' );
					wp_clear_scheduled_hook( 'wp_missed_scheduled_cron' );
					wp_clear_scheduled_hook( 'wp_schedule_missed' );
					wp_clear_scheduled_hook( 'wp_scheduled_missed' );
					wp_clear_scheduled_hook( 'wp_schedule_missed_cron' );
					wp_clear_scheduled_hook( 'wp_scheduled_missed_cron' );
					wp_clear_scheduled_hook( 'schedule_missed' );
					wp_clear_scheduled_hook( 'scheduled_missed' );
					wp_clear_scheduled_hook( 'schedule_missed_cron' );
					wp_clear_scheduled_hook( 'scheduled_missed_cron' );
				}

			if ( $wp_version >= 2.8 )
				{
					delete_transient( 'wp_missed_schedule' );
					delete_transient( 'wp_missed_scheduled' );
					delete_transient( 'timeout_wp_missed_schedule' );
					delete_transient( 'timeout_wp_missed_scheduled' );
					delete_transient( 'wp_schedule_missed' );
					delete_transient( 'wp_scheduled_missed' );
					delete_transient( 'timeout_wp_schedule_missed' );
					delete_transient( 'timeout_wp_scheduled_missed' );
					delete_transient( 'missed_schedule' );
					delete_transient( 'missed_scheduled' );
					delete_transient( 'timeout_missed_schedule' );
					delete_transient( 'timeout_missed_scheduled' );
					delete_transient( 'schedule_missed' );
					delete_transient( 'scheduled_missed' );
					delete_transient( 'timeout_schedule_missed' );
					delete_transient( 'timeout_scheduled_missed' );
				}

			if ( $wp_version >= 3.0 )
				{
					flush_rewrite_rules();
				}

			if ( $wp_version >= 3.0 )
				{
					if ( is_multisite() )
						{
//							delete_site_option( 'byrev_fixshedule_next_verify' );                                         # Future ALPHA conding reserved
//							delete_site_option( 'scheduled_post_guardian_next_run' );                                     # Future ALPHA conding reserved
//							delete_site_option( 'simpul_missed_schedule' );                                               # Future ALPHA conding reserved
//							delete_site_option( 'wpt_scheduled_check' );                                                  # Future ALPHA conding reserved
							delete_site_option( 'wp_missed_schedule' );
							delete_site_option( 'wp_missed_scheduled' );
							delete_site_option( 'wp_schedule_missed' );
							delete_site_option( 'wp_scheduled_missed' );
							delete_site_option( 'missed_schedule' );
							delete_site_option( 'missed_scheduled' );
							delete_site_option( 'schedule_missed' );
							delete_site_option( 'scheduled_missed' );

							delete_site_transient( 'wp_missed_schedule' );
							delete_site_transient( 'wp_missed_scheduled' );
							delete_site_transient( 'timeout_wp_missed_schedule' );
							delete_site_transient( 'timeout_wp_missed_scheduled' );
							delete_site_transient( 'wp_schedule_missed' );
							delete_site_transient( 'wp_scheduled_missed' );
							delete_site_transient( 'timeout_wp_schedule_missed' );
							delete_site_transient( 'timeout_wp_scheduled_missed' );
							delete_site_transient( 'missed_schedule' );
							delete_site_transient( 'missed_scheduled' );
							delete_site_transient( 'timeout_missed_schedule' );
							delete_site_transient( 'timeout_missed_scheduled' );
							delete_site_transient( 'schedule_missed' );
							delete_site_transient( 'scheduled_missed' );
							delete_site_transient( 'timeout_schedule_missed' );
							delete_site_transient( 'timeout_scheduled_missed' );
						}
				}
		}
	register_activation_hook( __FILE__, 'wpms_activation', 0 );

	function wpms_1st()
		{
			if ( ! current_user_can( 'activate_plugins' ) )
				return;

			$wp_path_to_this_file = preg_replace( '/(.*)plugins\/(.*)$/', WP_PLUGIN_DIR . "/$2", __FILE__ );
			$this_plugin          = plugin_basename( trim( $wp_path_to_this_file ) );
			$active_plugins       = get_option( 'active_plugins' );
			$this_plugin_key      = array_search( $this_plugin, $active_plugins );

			if ( $this_plugin_key )
				{
					array_splice( $active_plugins, $this_plugin_key, 1 );
					array_unshift( $active_plugins, $this_plugin );
					update_option( 'active_plugins', $active_plugins );
				}
		}
	add_action( 'activated_plugin', 'wpms_1st', 0 );

	if ( ! defined(  'WPMS_OPTION' ) ) define( 'WPMS_OPTION', 'scheduled_missed' );

	function wpms_init()
		{
			global $wp_version;

			if ( $wp_version < 2.8 )
				{
					$scheduled_missed = get_option( WPMS_OPTION, false );

					if ( ( $scheduled_missed !== false ) && ( $scheduled_missed > ( time() - ( 900 ) ) ) )
						return;
				}

			if ( $wp_version >= 2.8 )
				{
					if ( $wp_version < 3.0 )
						{
							$scheduled_missed = get_option( WPMS_OPTION, false );

							get_transient( 'scheduled_missed', $scheduled_missed, 900 );

							if ( ( $scheduled_missed !== false ) && ( $scheduled_missed > ( time() - ( 900 ) ) ) )
								return;

							set_transient( 'scheduled_missed', $scheduled_missed, 900 );
						}

					if ( $wp_version >= 3.0 )
						{
							if ( ! is_multisite() )
								{
									$scheduled_missed = get_option( WPMS_OPTION, false );

									get_transient( 'scheduled_missed', $scheduled_missed, 900 );

									if ( ( $scheduled_missed !== false ) && ( $scheduled_missed > ( time() - ( 900 ) ) ) )
										return;

									set_transient( 'scheduled_missed', $scheduled_missed, 900 );
								}

							if ( is_multisite() )
								{
									$scheduled_missed = get_option( WPMS_OPTION, false );

									get_site_transient( 'scheduled_missed', $scheduled_missed, 900 );

									if ( ( $scheduled_missed !== false ) && ( $scheduled_missed > ( time() - ( 900 ) ) ) )
										return;

									set_site_transient( 'scheduled_missed', $scheduled_missed, 900 );
								}
						}
				}

			update_option( WPMS_OPTION, time() );

			if ( $wp_version >= 2.3 )
				{
					global $wpdb;

					$qry = <<<SQL
 SELECT ID FROM {$wpdb->posts} WHERE ( ( post_date > 0 && post_date <= %s ) ) AND post_status = 'future' LIMIT 0,10 
SQL;

					$sql = $wpdb->prepare( $qry, current_time( 'mysql', 0 ) );

					$scheduledIDs = $wpdb->get_col( $sql );
				}

			if ( $wp_version < 2.3 )
				{
					global $wpdb;

					$scheduledIDs = $wpdb->get_col( "SELECT`ID`FROM `{$wpdb->posts}` " . " WHERE ( " . " ( ( `post_date` > 0 ) && ( `post_date` <= CURRENT_TIMESTAMP() ) ) OR " . " ( ( `post_date_gmt` > 0 ) && ( `post_date_gmt` <= UTC_TIMESTAMP() ) ) " . " ) AND `post_status` = 'future' LIMIT 0,10" );
				}

			if ( ! count( $scheduledIDs ) )
				return;

			foreach ( $scheduledIDs as $scheduledID )
				{
					if ( ! $scheduledID )
						continue;

					wp_publish_post( $scheduledID );
				}
		}
	add_action( 'init', 'wpms_init', 0 );

	if ( $wp_version >= 2.8 )
		{
			function wpms_pral( $links )
				{
					$links[] = '<a href="tools.php?page=crontrol_admin_manage_page">Cron</a>';
					$links[] = '<a href="edit.php?post_status=future&post_type=post">Miss</a>';
					$links[] = '<a href="https://slangji.wordpress.com/plugins/wp-missed-schedule-pro/" target="_blank">Upgrade</a>';
						return $links;
				}

			global $wp_version;

			if ( $wp_version >= 3.0 )
				{
					if ( ! is_multisite() )
						{
							add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'wpms_pral', 10, 1 );
						}

					if ( is_multisite() )
						{
							add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'wpms_pral', 10, 1 );
						}
				}

			if ( $wp_version < 3.0 )
				{
					add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'wpms_pral', 10, 1 );
				}

			function wpms_prml( $links, $file )
				{
					if ( ! is_admin() && ! current_user_can( 'administrator' ) )
						return;

					if ( $file == plugin_basename( __FILE__ ) )
						{
							$links[] = '<a href="http://slangjis.org/donate/" target="_blank">Donate</a>';
							$links[] = '<a href="http://slangjis.org/contact/" target="_blank">Contact</a>';
							$links[] = '<a href="http://slangjis.org/support/" target="_blank">Support</a>';
						}
					return $links;
				}
			add_filter( 'plugin_row_meta', 'wpms_prml', 10, 2 );
		}

	function wpms_shfl()
		{
			if ( ! is_home() && ! is_front_page() )
				return;

			echo "\r\n<!--Plugin WP Missed Schedule Active - PATCH - Secured with Genuine Authenticity KeyTag-->\r\n";
			echo "\r\n<!-- This site is patched against a big problem not solved since WordPress 2.5 to date -->\r\n\r\n";
		}
	add_action( 'wp_head', 'wpms_shfl', 100 );
	add_action( 'wp_footer', 'wpms_shfl', 100 );

	function wpms_shfl_authag()
		{
			if ( ! is_admin() && ! current_user_can( 'administrator' ) )
				return;

			echo "\r\n<!--Secured AuthTag - ".sha1(sha1("g46FsK338kT29FPANa8lC62b79H8651411574J4YQCb3eLCQM540z78BbFMtmFXj3"."7D6B6E6B01008EC2CA6A5B17D5F6164E98E73CE0"))."-->\r\n";
			echo "\r\n<!--Verified KeyTag - 787c178ab89b0f4378c345b2024af8e2a2aaf1fe-->\r\n";

			if ( sha1(sha1("g46FsK338kT29FPANa8lC62b79H8651411574J4YQCb3eLCQM540z78BbFMtmFXj3"."7D6B6E6B01008EC2CA6A5B17D5F6164E98E73CE0")) == '787c178ab89b0f4378c345b2024af8e2a2aaf1fe' )
				{
					echo "\r\n<!-- Your copy of Plugin WP Missed Schedule (free) is Genuine -->\r\n\r\n";
				}
			else
				{
					echo "\r\n<!-- Your copy of Plugin WP Missed Schedule (free) NO Genuine -->\r\n\r\n";
				}
		}
	add_action( 'admin_head', 'wpms_shfl_authag', 100 );
	add_action( 'admin_footer', 'wpms_shfl_authag', 100 );

	function wpms_deactivation()
		{
			if ( ! current_user_can( 'activate_plugins' ) )
				return;

			global $wp_version;

			if ( $wp_version >= 2.1 )
				{
//					delete_option( 'byrev_fixshedule_next_verify' );                                                      # Future ALPHA conding reserved
//					delete_option( 'scheduled_post_guardian_next_run' );                                                  # Future ALPHA conding reserved
//					delete_option( 'simpul_missed_schedule' );                                                            # Future ALPHA conding reserved
//					delete_option( 'wpt_scheduled_check' );                                                               # Future ALPHA conding reserved
					delete_option( 'wp_missed_schedule' );
					delete_option( 'wp_missed_scheduled' );
					delete_option( 'wp_schedule_missed' );
					delete_option( 'wp_scheduled_missed' );
					delete_option( 'missed_schedule' );
					delete_option( 'missed_scheduled' );
					delete_option( 'schedule_missed' );
					delete_option( 'scheduled_missed' );

					wp_clear_scheduled_hook( 'missed_schedule' );
					wp_clear_scheduled_hook( 'missed_scheduled' );
//					wp_clear_scheduled_hook( 'missed_schedule_cron' );                                                    # Future ALPHA conding reserved
					wp_clear_scheduled_hook( 'missed_scheduled_cron' );
					wp_clear_scheduled_hook( 'wp_missed_schedule' );
					wp_clear_scheduled_hook( 'wp_missed_scheduled' );
					wp_clear_scheduled_hook( 'wp_missed_schedule_cron' );
					wp_clear_scheduled_hook( 'wp_missed_scheduled_cron' );
					wp_clear_scheduled_hook( 'wp_schedule_missed' );
					wp_clear_scheduled_hook( 'wp_scheduled_missed' );
					wp_clear_scheduled_hook( 'wp_schedule_missed_cron' );
					wp_clear_scheduled_hook( 'wp_scheduled_missed_cron' );
					wp_clear_scheduled_hook( 'schedule_missed' );
					wp_clear_scheduled_hook( 'scheduled_missed' );
					wp_clear_scheduled_hook( 'schedule_missed_cron' );
					wp_clear_scheduled_hook( 'scheduled_missed_cron' );
				}

			if ( $wp_version >= 2.8 )
				{
					delete_transient( 'wp_missed_schedule' );
					delete_transient( 'wp_missed_scheduled' );
					delete_transient( 'timeout_wp_missed_schedule' );
					delete_transient( 'timeout_wp_missed_scheduled' );
					delete_transient( 'wp_schedule_missed' );
					delete_transient( 'wp_scheduled_missed' );
					delete_transient( 'timeout_wp_schedule_missed' );
					delete_transient( 'timeout_wp_scheduled_missed' );
					delete_transient( 'missed_schedule' );
					delete_transient( 'missed_scheduled' );
					delete_transient( 'timeout_missed_schedule' );
					delete_transient( 'timeout_missed_scheduled' );
					delete_transient( 'schedule_missed' );
					delete_transient( 'scheduled_missed' );
					delete_transient( 'timeout_schedule_missed' );
					delete_transient( 'timeout_scheduled_missed' );
				}

			if ( $wp_version >= 3.0 )
				{
					flush_rewrite_rules();
				}

			if ( $wp_version >= 3.0 )
				{
					if ( is_multisite() )
						{
//							delete_site_option( 'byrev_fixshedule_next_verify' );                                         # Future ALPHA conding reserved
//							delete_site_option( 'scheduled_post_guardian_next_run' );                                     # Future ALPHA conding reserved
//							delete_site_option( 'simpul_missed_schedule' );                                               # Future ALPHA conding reserved
//							delete_site_option( 'wpt_scheduled_check' );                                                  # Future ALPHA conding reserved
							delete_site_option( 'wp_missed_schedule' );
							delete_site_option( 'wp_missed_scheduled' );
							delete_site_option( 'wp_schedule_missed' );
							delete_site_option( 'wp_scheduled_missed' );
							delete_site_option( 'missed_schedule' );
							delete_site_option( 'missed_scheduled' );
							delete_site_option( 'schedule_missed' );
							delete_site_option( 'scheduled_missed' );

							delete_site_transient( 'wp_missed_schedule' );
							delete_site_transient( 'wp_missed_scheduled' );
							delete_site_transient( 'timeout_wp_missed_schedule' );
							delete_site_transient( 'timeout_wp_missed_scheduled' );
							delete_site_transient( 'wp_schedule_missed' );
							delete_site_transient( 'wp_scheduled_missed' );
							delete_site_transient( 'timeout_wp_schedule_missed' );
							delete_site_transient( 'timeout_wp_scheduled_missed' );
							delete_site_transient( 'missed_schedule' );
							delete_site_transient( 'missed_scheduled' );
							delete_site_transient( 'timeout_missed_schedule' );
							delete_site_transient( 'timeout_missed_scheduled' );
							delete_site_transient( 'schedule_missed' );
							delete_site_transient( 'scheduled_missed' );
							delete_site_transient( 'timeout_schedule_missed' );
							delete_site_transient( 'timeout_scheduled_missed' );
						}
				}
		}
	register_deactivation_hook( __FILE__, 'wpms_deactivation', 0 );

	if ( $wp_version >= 2.7 )
		{
			function wpms_uninstall()
				{
					if ( ! current_user_can( 'activate_plugins' ) )
						return;

					global $wp_version;
					
//					delete_option( 'byrev_fixshedule_next_verify' );                                                      # Future ALPHA conding reserved
//					delete_option( 'scheduled_post_guardian_next_run' );                                                  # Future ALPHA conding reserved
//					delete_option( 'simpul_missed_schedule' );                                                            # Future ALPHA conding reserved
//					delete_option( 'wpt_scheduled_check' );                                                               # Future ALPHA conding reserved
					delete_option( 'wp_missed_schedule' );
					delete_option( 'wp_missed_scheduled' );
					delete_option( 'wp_schedule_missed' );
					delete_option( 'wp_scheduled_missed' );
					delete_option( 'missed_schedule' );
					delete_option( 'missed_scheduled' );
					delete_option( 'schedule_missed' );
					delete_option( 'scheduled_missed' );

					delete_option( 'scheduled_missed_options' );
					delete_option( 'scheduled_missed_cron_options' );

					wp_clear_scheduled_hook( 'missed_schedule' );
					wp_clear_scheduled_hook( 'missed_scheduled' );
//					wp_clear_scheduled_hook( 'missed_schedule_cron' );                                                    # Future ALPHA conding reserved
					wp_clear_scheduled_hook( 'missed_scheduled_cron' );
					wp_clear_scheduled_hook( 'wp_missed_schedule' );
					wp_clear_scheduled_hook( 'wp_missed_scheduled' );
					wp_clear_scheduled_hook( 'wp_missed_schedule_cron' );
					wp_clear_scheduled_hook( 'wp_missed_scheduled_cron' );
					wp_clear_scheduled_hook( 'wp_schedule_missed' );
					wp_clear_scheduled_hook( 'wp_scheduled_missed' );
					wp_clear_scheduled_hook( 'wp_schedule_missed_cron' );
					wp_clear_scheduled_hook( 'wp_scheduled_missed_cron' );
					wp_clear_scheduled_hook( 'schedule_missed' );
					wp_clear_scheduled_hook( 'scheduled_missed' );
					wp_clear_scheduled_hook( 'schedule_missed_cron' );
					wp_clear_scheduled_hook( 'scheduled_missed_cron' );

					if ( $wp_version >= 2.8 )
						{
							delete_transient( 'wp_missed_schedule' );
							delete_transient( 'wp_missed_scheduled' );
							delete_transient( 'timeout_wp_missed_schedule' );
							delete_transient( 'timeout_wp_missed_scheduled' );
							delete_transient( 'wp_schedule_missed' );
							delete_transient( 'wp_scheduled_missed' );
							delete_transient( 'timeout_wp_schedule_missed' );
							delete_transient( 'timeout_wp_scheduled_missed' );
							delete_transient( 'missed_schedule' );
							delete_transient( 'missed_scheduled' );
							delete_transient( 'timeout_missed_schedule' );
							delete_transient( 'timeout_missed_scheduled' );
							delete_transient( 'schedule_missed' );
							delete_transient( 'scheduled_missed' );
							delete_transient( 'timeout_schedule_missed' );
							delete_transient( 'timeout_scheduled_missed' );

							delete_transient( 'scheduled_missed_options' );
							delete_transient( 'scheduled_missed_cron_options' );
							delete_transient( 'timeout_scheduled_missed_options' );
							delete_transient( 'timeout_scheduled_missed_cron_options' );
						}

					if ( $wp_version >= 3.0 )
						{
							flush_rewrite_rules();
						}

					if ( $wp_version >= 3.0 )
						{
							if ( is_multisite() )
								{
//									delete_site_option( 'byrev_fixshedule_next_verify' );                                 # Future ALPHA conding reserved
//									delete_site_option( 'scheduled_post_guardian_next_run' );                             # Future ALPHA conding reserved
//									delete_site_option( 'simpul_missed_schedule' );                                       # Future ALPHA conding reserved
//									delete_site_option( 'wpt_scheduled_check' );                                          # Future ALPHA conding reserved
									delete_site_option( 'wp_missed_schedule' );
									delete_site_option( 'wp_missed_scheduled' );
									delete_site_option( 'wp_schedule_missed' );
									delete_site_option( 'wp_scheduled_missed' );
									delete_site_option( 'missed_schedule' );
									delete_site_option( 'missed_scheduled' );
									delete_site_option( 'schedule_missed' );
									delete_site_option( 'scheduled_missed' );

									delete_site_option( 'scheduled_missed_options' );
									delete_site_option( 'scheduled_missed_cron_options' );

									delete_site_transient( 'wp_missed_schedule' );
									delete_site_transient( 'wp_missed_scheduled' );
									delete_site_transient( 'timeout_wp_missed_schedule' );
									delete_site_transient( 'timeout_wp_missed_scheduled' );
									delete_site_transient( 'wp_schedule_missed' );
									delete_site_transient( 'wp_scheduled_missed' );
									delete_site_transient( 'timeout_wp_schedule_missed' );
									delete_site_transient( 'timeout_wp_scheduled_missed' );
									delete_site_transient( 'missed_schedule' );
									delete_site_transient( 'missed_scheduled' );
									delete_site_transient( 'timeout_missed_schedule' );
									delete_site_transient( 'timeout_missed_scheduled' );
									delete_site_transient( 'schedule_missed' );
									delete_site_transient( 'scheduled_missed' );
									delete_site_transient( 'timeout_schedule_missed' );
									delete_site_transient( 'timeout_scheduled_missed' );

									delete_site_transient( 'scheduled_missed_options' );
									delete_site_transient( 'scheduled_missed_cron_options' );
									delete_site_transient( 'timeout_scheduled_missed_options' );
									delete_site_transient( 'timeout_scheduled_missed_cron_options' );

									global $wpdb;

									$blog_ids = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );
									$original_blog_id = get_current_blog_id();

									foreach ( $blog_ids as $blog_id )
										{
											switch_to_blog( $blog_id );

//											delete_site_option( 'byrev_fixshedule_next_verify' );                         # Future ALPHA conding reserved
//											delete_site_option( 'scheduled_post_guardian_next_run' );                     # Future ALPHA conding reserved
//											delete_site_option( 'simpul_missed_schedule' );                               # Future ALPHA conding reserved
//											delete_site_option( 'wpt_scheduled_check' );                                  # Future ALPHA conding reserved
											delete_site_option( 'wp_missed_schedule' );
											delete_site_option( 'wp_missed_scheduled' );
											delete_site_option( 'wp_schedule_missed' );
											delete_site_option( 'wp_scheduled_missed' );
											delete_site_option( 'missed_schedule' );
											delete_site_option( 'missed_scheduled' );
											delete_site_option( 'schedule_missed' );
											delete_site_option( 'scheduled_missed' );

											delete_site_option( 'scheduled_missed_options' );
											delete_site_option( 'scheduled_missed_cron_options' );

											delete_site_transient( 'wp_missed_schedule' );
											delete_site_transient( 'wp_missed_scheduled' );
											delete_site_transient( 'timeout_wp_missed_schedule' );
											delete_site_transient( 'timeout_wp_missed_scheduled' );
											delete_site_transient( 'wp_schedule_missed' );
											delete_site_transient( 'wp_scheduled_missed' );
											delete_site_transient( 'timeout_wp_schedule_missed' );
											delete_site_transient( 'timeout_wp_scheduled_missed' );
											delete_site_transient( 'missed_schedule' );
											delete_site_transient( 'missed_scheduled' );
											delete_site_transient( 'timeout_missed_schedule' );
											delete_site_transient( 'timeout_missed_scheduled' );
											delete_site_transient( 'schedule_missed' );
											delete_site_transient( 'scheduled_missed' );
											delete_site_transient( 'timeout_schedule_missed' );
											delete_site_transient( 'timeout_scheduled_missed' );

											delete_site_transient( 'scheduled_missed_options' );
											delete_site_transient( 'scheduled_missed_cron_options' );
											delete_site_transient( 'timeout_scheduled_missed_options' );
											delete_site_transient( 'timeout_scheduled_missed_cron_options' );
										}
									switch_to_blog( $original_blog_id );
								}
						}
				}
			register_uninstall_hook( __FILE__, 'wpms_uninstall', 0 );
		}
	}
	}
	}
	}
	}
	}
	}
	}
	}
	}
	}
	}
	}
	}
	}
	}
	}
	}
