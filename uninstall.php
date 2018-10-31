<?php
/* 
 * Removing Plugin data using uninstall.php
 * the below function clears the database table on uninstall
 * only loads this file when uninstalling a plugin.
 */

/* 
 * exit uninstall if not called by WP
 */
if ( !defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit();
}
if ( ! defined(       'ABSPATH'       ) ) exit;

if ( ! defined(        'WPINC'        ) ) exit;

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) exit;
/* 
 * Making WPDB as global
 * to access database information.
 */
global $wpdb;
global $wp_version;

if ( $wp_version < 2.7 ) exit;

$hooks_names = array( 
        'missed_schedule',
        'missed_scheduled',
        'missed_scheduled_cron',
        'wp_missed_schedule',
        'wp_missed_scheduled',
        'wp_missed_schedule_cron',
        'wp_missed_scheduled_cron',
        'wp_schedule_missed',
        'wp_scheduled_missed',
        'wp_schedule_missed_cron',
        'wp_scheduled_missed_cron',
        'schedule_missed',
        'scheduled_missed',
        'schedule_missed_cron',
        'scheduled_missed_cron'
);

$options_names = array( 
        'missed_schedule',
        'missed_scheduled',
        'schedule_missed',
        'scheduled_missed',
        'missed_schedule_cron',
        'missed_scheduled_cron',
        'schedule_missed_cron',
        'scheduled_missed_cron',
        'missed_schedule_options',
        'missed_scheduled_options',
        'schedule_missed_options',
        'scheduled_missed_options',
        'missed_schedule_cron_options',
        'missed_scheduled_cron_options',
        'schedule_missed_cron_options',
        'scheduled_missed_cron_options',
        'wp_missed_schedule',
        'wp_missed_scheduled',
        'wp_schedule_missed',
        'wp_scheduled_missed',
        'wp_missed_schedule_cron',
        'wp_missed_scheduled_cron',
        'wp_schedule_missed_cron',
        'wp_scheduled_missed_cron',
        'wp_missed_schedule_options',
        'wp_missed_scheduled_options',
        'wp_schedule_missed_options',
        'wp_scheduled_missed_options',
        'wp_missed_schedule_cron_options',
        'wp_missed_scheduled_cron_options',
        'wp_schedule_missed_cron_options',
        'wp_scheduled_missed_cron_options'
);

$transients_names = array( 
        'wp_missed_schedule',
        'wp_missed_scheduled',
        'timeout_wp_missed_schedule',
        'timeout_wp_missed_scheduled',
        'wp_schedule_missed',
        'wp_scheduled_missed',
        'timeout_wp_schedule_missed',
        'timeout_wp_scheduled_missed',
        'missed_schedule',
        'missed_scheduled',
        'timeout_missed_schedule',
        'timeout_missed_scheduled',
        'schedule_missed',
        'scheduled_missed',
        'timeout_schedule_missed',
        'timeout_scheduled_missed'
);

if ( $wp_version >= 2.7 )
    {
        foreach ( $hooks_names as $hook_name )
            {
                wp_clear_scheduled_hook( $hook_name );
            }

        foreach ( $options_names as $option_name )
            {
                delete_option( $option_name );
            }
    }

if ( $wp_version >= 2.8 )
    {
        foreach ( $transients_names as $transient_name )
            {
                delete_transient( $transient_name );
            }
    }

if ( $wp_version >= 3.0 )
    {
        flush_rewrite_rules();

        if ( is_multisite() )
            {
                foreach ( $hooks_names as $hook_name )
                    {
                        wp_clear_scheduled_hook( $hook_name );
                    }

                foreach ( $options_names as $option_name )
                    {
                        delete_site_option( $option_name );
                    }

                foreach ( $transients_names as $transient_name )
                    {
                        delete_site_transient( $transient_name );
                    }

                global $wpdb;

                $blog_ids = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );
                $original_blog_id = get_current_blog_id();

                foreach ( $blog_ids as $blog_id )
                    {
                        switch_to_blog( $blog_id );

                        foreach ( $hooks_names as $hook_name )
                            {
                                wp_clear_scheduled_hook( $hook_name );
                            }

                        foreach ( $options_names as $option_name )
                            {
                                delete_option( $option_name );
                                delete_site_option( $option_name );
                            }

                        foreach ( $transients_names as $transient_name )
                            {
                                delete_transient( $transient_name );
                                delete_site_transient( $transient_name );
                            }
                    }
                switch_to_blog( $original_blog_id );
            }
    }

/* 
 * @var $table_name 
 * name of table to be dropped
 * prefixed with $wpdb->prefix from the database
 */
$table_name = 'psm_manage_schedule';

// drop the table from the database.
$wpdb->query( "DROP TABLE IF EXISTS $table_name" );