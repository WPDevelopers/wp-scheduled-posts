<?php 
	global $wp_version;

	function wpsp_activation()
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
	register_activation_hook( __FILE__, 'wpsp_activation', 0 );

	function wpsp_1st()
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
	add_action( 'activated_plugin', 'wpsp_1st', 0 );

	if ( ! defined(  'wpsp_OPTION' ) ) define( 'wpsp_OPTION', 'scheduled_missed' );

	function wpsp_init()
		{
			global $wp_version;

			if ( $wp_version < 2.8 )
				{
					$scheduled_missed = get_option( wpsp_OPTION, false );

					if ( ( $scheduled_missed !== false ) && ( $scheduled_missed > ( time() - ( 900 ) ) ) )
						return;
				}

			if ( $wp_version >= 2.8 )
				{
					if ( $wp_version < 3.0 )
						{
							$scheduled_missed = get_option( wpsp_OPTION, false );

							get_transient( 'scheduled_missed', $scheduled_missed, 900 );

							if ( ( $scheduled_missed !== false ) && ( $scheduled_missed > ( time() - ( 900 ) ) ) )
								return;

							set_transient( 'scheduled_missed', $scheduled_missed, 900 );
						}

					if ( $wp_version >= 3.0 )
						{
							if ( ! is_multisite() )
								{
									$scheduled_missed = get_option( wpsp_OPTION, false );

									get_transient( 'scheduled_missed', $scheduled_missed, 900 );

									if ( ( $scheduled_missed !== false ) && ( $scheduled_missed > ( time() - ( 900 ) ) ) )
										return;

									set_transient( 'scheduled_missed', $scheduled_missed, 900 );
								}

							if ( is_multisite() )
								{
									$scheduled_missed = get_option( wpsp_OPTION, false );

									get_site_transient( 'scheduled_missed', $scheduled_missed, 900 );

									if ( ( $scheduled_missed !== false ) && ( $scheduled_missed > ( time() - ( 900 ) ) ) )
										return;

									set_site_transient( 'scheduled_missed', $scheduled_missed, 900 );
								}
						}
				}

			update_option( wpsp_OPTION, time() );

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
	add_action( 'init', 'wpsp_init', 0 );

/*

	function wpsp_deactivation()
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
	register_deactivation_hook( __FILE__, 'wpsp_deactivation', 0 );

	if ( $wp_version >= 2.7 )
		{
			function wpsp_uninstall()
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
			register_uninstall_hook( __FILE__, 'wpsp_uninstall', 0 );
		}
	
	*/
