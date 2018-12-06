<?php
	function wpsp_activation()
		{
			if ( ! current_user_can( 'activate_plugins' ) )
				return;

					global $wp_version;
					flush_rewrite_rules();
					delete_option( 'scheduled_missed' );
					wp_clear_scheduled_hook( 'scheduled_missed' );
					delete_transient( 'scheduled_missed' );
					if ( is_multisite() )
						{			
							delete_site_option( 'scheduled_missed' );
							delete_site_transient( 'scheduled_missed' );
						}	
		}

	register_activation_hook( __FILE__, 'wpsp_activation', 0 );


	if ( ! defined(  'wpsp_OPTION' ) ) define( 'wpsp_OPTION', 'scheduled_missed' );

	function wpsp_init()
		{
			global $wp_version;

			$scheduled_missed = get_option( wpsp_OPTION, false );
			if ( $wp_version < 2.8 )
				{
					if ( ( $scheduled_missed !== false ) && ( $scheduled_missed > ( time() - ( 900 ) ) ) )
						return;
				}

			if ( $wp_version >= 2.8 && $wp_version < 3.0)
				{				
							get_transient( 'scheduled_missed', $scheduled_missed, 900 );
							if ( ( $scheduled_missed !== false ) && ( $scheduled_missed > ( time() - ( 900 ) ) ) )
								return;
							set_transient( 'scheduled_missed', $scheduled_missed, 900 );
						}

			if ( $wp_version >= 3.0 )
				{
							if ( ! is_multisite() )
								{
									get_transient( 'scheduled_missed', $scheduled_missed, 900 );
									if ( ( $scheduled_missed !== false ) && ( $scheduled_missed > ( time() - ( 900 ) ) ) )
										return;
									set_transient( 'scheduled_missed', $scheduled_missed, 900 );
								}

							if ( is_multisite() )
								{
									get_site_transient( 'scheduled_missed', $scheduled_missed, 900 );
									if ( ( $scheduled_missed !== false ) && ( $scheduled_missed > ( time() - ( 900 ) ) ) )
										return;
									set_site_transient( 'scheduled_missed', $scheduled_missed, 900 );
								}		
				}

			update_option( wpsp_OPTION, time() );

		
					global $wpdb;

					$qry = <<<SQL
 SELECT ID FROM {$wpdb->posts} WHERE ( ( post_date > 0 && post_date <= %s ) ) AND post_status = 'future' LIMIT 0,10 
SQL;
					$sql = $wpdb->prepare( $qry, current_time( 'mysql', 0 ) );
					$scheduledIDs = $wpdb->get_col( $sql );
				
			if ( ! count( $scheduledIDs ) )
				return;

			foreach ( $scheduledIDs as $scheduledID )
				{
					if ( ! $scheduledID )
						continue;
					wp_publish_post( $scheduledID );
				}
		}
	add_action( 'init', 'wpsp_init', 0 );


	function wpsp_deactivation()
		{
			if ( ! current_user_can( 'activate_plugins' ) )
				return;

			global $wp_version;

					flush_rewrite_rules();
					delete_site_option( 'scheduled_missed' );
					delete_option( 'scheduled_missed' );
					wp_clear_scheduled_hook( 'scheduled_missed' );
					delete_transient( 'scheduled_missed' );
					delete_site_transient( 'scheduled_missed' );
		}
	register_deactivation_hook( __FILE__, 'wpsp_deactivation', 0 );
