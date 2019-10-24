<?php
if(!function_exists('wpscp_get_all_category')){
	function wpscp_get_all_category(){
		$category  = get_categories( array(
			'orderby' => 'name',
			'order'   => 'ASC',
			"hide_empty" => 0,
		) );
		$category = wp_list_pluck($category, 'name', 'term_id');
		array_unshift($category, 'All Categories');
		return $category;
	}
}


if(!function_exists('wpscp_qsw_manage_scheduled_markup')){
	function wpscp_qsw_manage_scheduled_markup(){
		global $wpdb;
		global $plName;
		global $plUrl;
		global $pts_debug;
		global $pts_show_donate;
		global $activate_pub_option;
		$options = get_option("manage-schedule.php");
		?>
		<div class="switch-field">
			<input type="radio" id="autoScheduler" name="wpscpqswmanageshceduled" <?php print (get_option( 'pub_active_option' ) == 'ok' ? 'value="yes" checked' : 'value="no"' ) ?>/>
			<label for="autoScheduler">Auto Scheduler</label>
			<input type="radio" id="manualScheduler" name="wpscpqswmanageshceduled" <?php print (get_option( 'cal_active_option' ) == 'ok' ? 'value="yes" checked' : 'value="no"' ) ?> />
			<label for="manualScheduler">Manual Scheduler</label>
		</div>
		<div id="toggleSwithElementContent">
			<div class="autoScheduler">
				<fieldset class="options" style="margin-top: 30px;">
					<?php
						if ($pts_debug) {
							echo '<h3><strong style="color:red;">' . $plName . ' - <span style="text-decoration:blink">Debug active!</span></strong></h3>';
						}
					?>

					<?php
						$days = array('sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday');

					?>

					<div class="wpsp-schedule-table">

						<h3><?php _e('Set the number of posts you want to schedule for each day throughout the week.', 'wp-scheduled-posts') ?></h3>
						<table>

							<?php
								$iday = 0;
								foreach ($days as $day) {
							?>
							<tr valign="top">
								<th scope="row" align="left"><?php _e(ucfirst($day), 'wp-scheduled-posts') ?> <span>(number of posts)</span></th>

								<td style="padding:5px;">
									<input
										type="text"
										class="wpsp_field_activate"
										id="wpsp_<?php echo $day; ?>"
										name="<?php echo "pts_$iday"; ?>"
										placeholder="00"
										value="<?php
												if (isset($options["pts_$iday"])) {

													if ($options["pts_$iday"] == 'no') 
													{
														echo '0';
													} else if ($options["pts_$iday"] == 'yes') {
														echo '1';
													} else {
														
														echo $options["pts_$iday"];
														
													}

												}
												?>"/>
								</td>

							</tr>
							<?php
								$iday += 1;
								}
							?>

						</table>


					</div> <!-- Schedule table end -->

					<div class="wpsp_time_interval_option_table">

						<h3><?php _e('Specify the time interval in which you want to have your posts scheduled.', 'wp-scheduled-posts') ?></h3>

						<table class="optiontable">
							<tr valign="top">
								<th scope="row" align="left"><?php _e('Start Time', 'wp-scheduled-posts') ?> <span><?php _e(' (Default : 23:59)', 'wp-scheduled-posts') ?></span></th>
								<td><input name="pts_start" class="wpsp_field_activate" type="text" id="wpsp_start" value="<?php echo $options['pts_start']; ?>" placeholder="00:00" size="10" />
								</td>
							</tr>
							<tr valign="top">
								<th scope="row" align="left"><?php _e('End Time', 'wp-scheduled-posts') ?> <span><?php _e(' (Default : 23:59)', 'wp-scheduled-posts') ?></span></th>
								<td><input name="pts_end" type="text" class="wpsp_field_activate" id="wpsp_end" value="<?php echo $options['pts_end']; ?>" placeholder="23:59" size="10" />
								</td>
							</tr>
						</table>

					</div> <!-- Time Interval option table end -->

					</fieldset>
			</div>
			<div class="manualScheduler">
				<!-- Manual scheduled -->
				<h2 class="wpsp-notice-text">
					<?php 
					if (isset($massage)) {
						echo $massage;
					} 
					?>
				</h2>
				<div class="man_options">
					<ul class="wpsp_man_time_setting">
						<li>
							<span>Select Days</span>
							<select name="man_days" id="man_days">
								<option value="saturday">Saturday</option>
								<option value="sunday">Sunday</option>
								<option value="monday">Monday</option>
								<option value="tuesday">Tuesday</option>
								<option value="wednesday">Wednesday</option>
								<option value="thursday">Thursday</option>
								<option value="friday">Friday</option>
							</select>
						</li>
						<li>
							<span>Time Settings</span>
							<input type="text" autocomplete="off" name="man_times" id="man_times" value="00:00" placeholder="select time" class="wpsp_field_activate">
						</li>
						<li>
							<input class="button button-primary" type="submit" id="man_submit" name="man_submit" value="Save Schedule">
							<img id="wpscp_manual_loader" src="<?php echo plugins_url('wp-scheduled-posts/admin/assets/images/ajax.gif'); ?>" alt="loader">
						</li>
					</ul>

				</div>
				<?php
					global $wpdb;
					$my_prefix = 'psm_';
					if($wpdb->get_var("SHOW TABLES LIKE 'psm_manage_schedule'") != 'psm_manage_schedule'){
						$my_prefix = $wpdb->prefix;
					}
					$my_table = $my_prefix . 'manage_schedule';

					/*==========================================
								All delete query for every days
					==========================================*/

					if (isset($_GET['sat_id'])) {
						$sat_id = $_GET['sat_id'];
						$sql = "DELETE FROM " . $my_table . " WHERE id='$sat_id' ";
						$delete_sat = $wpdb->get_results($sql, ARRAY_A);
					}

					if (isset($_GET['sun_id'])) {
						$sun_id = $_GET['sun_id'];
						$sql = "DELETE FROM " . $my_table . " WHERE id='$sun_id' ";
						$delete_sun = $wpdb->get_results($sql, ARRAY_A);
					}

					if (isset($_GET['mon_id'])) {
						$mon_id = $_GET['mon_id'];
						$sql = "DELETE FROM " . $my_table . " WHERE id='$mon_id' ";
						$delete_mon = $wpdb->get_results($sql, ARRAY_A);
					}

					if (isset($_GET['tue_id'])) {
						$tue_id = $_GET['tue_id'];
						$sql = "DELETE FROM " . $my_table . " WHERE id='$tue_id' ";
						$delete_tue = $wpdb->get_results($sql, ARRAY_A);
					}

					if (isset($_GET['wed_id'])) {
						$wed_id = $_GET['wed_id'];
						$sql = "DELETE FROM " . $my_table . " WHERE id='$wed_id' ";
						$delete_wed = $wpdb->get_results($sql, ARRAY_A);
					}

					if (isset($_GET['thu_id'])) {
						$thu_id = $_GET['thu_id'];
						$sql = "DELETE FROM " . $my_table . " WHERE id='$thu_id' ";
						$delete_thu = $wpdb->get_results($sql, ARRAY_A);
					}

					if (isset($_GET['fri_id'])) {
						$fri_id = $_GET['fri_id'];
						$sql = "DELETE FROM " . $my_table . " WHERE id='$fri_id' ";
						$delete_fri = $wpdb->get_results($sql, ARRAY_A);
					}


					/*==========================================
							All select query for every days
					==========================================*/

					$sql = "SELECT * FROM " . $my_table . " WHERE day='saturday'";
					$sat_schedules = $wpdb->get_results($sql, ARRAY_A);

					$sql = "SELECT * FROM " . $my_table . " WHERE day='sunday'";
					$sun_schedules = $wpdb->get_results($sql, ARRAY_A);

					$sql = "SELECT * FROM " . $my_table . " WHERE day='monday'";
					$mon_schedules = $wpdb->get_results($sql, ARRAY_A);

					$sql = "SELECT * FROM " . $my_table . " WHERE day='tuesday'";
					$tue_schedules = $wpdb->get_results($sql, ARRAY_A);

					$sql = "SELECT * FROM " . $my_table . " WHERE day='wednesday'";
					$wed_schedules = $wpdb->get_results($sql, ARRAY_A);

					$sql = "SELECT * FROM " . $my_table . " WHERE day='thursday'";
					$thu_schedules = $wpdb->get_results($sql, ARRAY_A);

					$sql = "SELECT * FROM " . $my_table . " WHERE day='friday'";
					$fri_schedules = $wpdb->get_results($sql, ARRAY_A);
				?>
				<ul class="schedule-list" style="margin-top: 20px;">
					<li data-day="saturday">
						<span>sat</span>
						<?php
							// loop of saturday data
							$count = count($sat_schedules);
							for ($i = 0; $i < $count; $i++) {
								$id = $sat_schedules[$i]['id'];
								$sat = $sat_schedules[$i]['schedule'];
								echo '<span '.currentDayActive("sat").'>' . $sat . ' <button id="'.$id.'"><span class="dashicons dashicons-no-alt"></span></button></span>';
							}
						?>
					</li>
					<li data-day="sunday">
						<span <?php currentDayActive('sun'); ?> >sun</span>
						<?php
						// loop of sunday data
						$count = count($sun_schedules);
						for ($i = 0; $i < $count; $i++) {
							$id = $sun_schedules[$i]['id'];
							$sun = $sun_schedules[$i]['schedule'];
							if (!empty($sun)) {
								echo '<span>' . $sun . ' <button id="'.$id.'"><span class="dashicons dashicons-no-alt"></span></button></span>';
							} else {
								echo '<span>-</span>';
							}
						}
						?>
					</li>
					<li data-day="monday">
						<span <?php currentDayActive('mon'); ?> >mon</span>
						<?php
						// loop of monday data
						$count = count($mon_schedules);
						for ($i = 0; $i < $count; $i++) {
							$id = $mon_schedules[$i]['id'];
							$mon = $mon_schedules[$i]['schedule'];
							if (isset($mon) && !empty($mon)) {
								echo '<span>' . $mon . ' <button id="'.$id.'"><span class="dashicons dashicons-no-alt"></span></button></span>';
							} else {
								echo '<span>-</span>';
							}
						}
						?>
					</li>
					<li data-day="tuesday">
						<span <?php currentDayActive('tue'); ?> >tue</span>
						<?php
						// loop of tuesday data
						$count = count($tue_schedules);
						for ($i = 0; $i < $count; $i++) {
							$id = $tue_schedules[$i]['id'];
							$tue = $tue_schedules[$i]['schedule'];
							if (isset($tue) && !empty($tue)) {
								echo '<span>' . $tue . ' <button id="'.$id.'"><span class="dashicons dashicons-no-alt"></span></button></span>';
							} else {
								echo '<span>-</span>';
							}
						}
						?>
					</li>
					<li data-day="wednesday">
						<span <?php currentDayActive('wed'); ?> >wed</span>
						<?php
						// loop of wednesday data
						$count = count($wed_schedules);
						for ($i = 0; $i < $count; $i++) {
							$id = $wed_schedules[$i]['id'];
							$wed = $wed_schedules[$i]['schedule'];
							if (isset($wed) && !empty($wed)) {
								echo '<span>' . $wed . ' <button id="'.$id.'"><span class="dashicons dashicons-no-alt"></span></button></span>';
							} else {
								echo '<span>-</span>';
							}
						}
						?>
					</li>
					<li data-day="thursday">
						<span <?php currentDayActive('thu'); ?> >thu</span>
						<?php
						// loop of thursday data
						$count = count($thu_schedules);
						for ($i = 0; $i < $count; $i++) {
							$id = $thu_schedules[$i]['id'];
							$thu = $thu_schedules[$i]['schedule'];
							if (isset($thu) && !empty($thu)) {
								echo '<span>' . $thu . ' <button id="'.$id.'"><span class="dashicons dashicons-no-alt"></span></button></span>';
							} else {
								echo '<span>-</span>';
							}
						}
						?>
					</li>
					<li data-day="friday">
						<span <?php currentDayActive('fri'); ?> >fri</span>
						<?php
						// loop of friday data
						$count = count($fri_schedules);
						for ($i = 0; $i < $count; $i++) {
							$id = $fri_schedules[$i]['id'];
							$fri = $fri_schedules[$i]['schedule'];
							if (isset($fri) && !empty($fri)) {
								echo '<span>' . $fri . ' <button id="'.$id.'"><span class="dashicons dashicons-no-alt"></span></button></span>';
							} else {
								echo '<span>-</span>';
							}
						}
						?>
					</li>
				</ul>
				<!-- miss schedule -->
				<div class="miss-schedule-form">
					<?php
						global $wpdb;
						$get_miss_op = get_option('miss_schedule_active_option');
					?>
					<div class="checkbox-toggle">
						<input id="missscheduled" type="checkbox" class="wpsp_field_activate" name="miss_check" 
						value="<?php print ($get_miss_op == 'yes' ? $get_miss_op :  'no'); ?>" <?php  print (($get_miss_op == 'yes') ? 'checked' : ''); ?>>
						
						<svg class="is_checked" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 426.67 426.67">
						<path d="M153.504 366.84c-8.657 0-17.323-3.303-23.927-9.912L9.914 237.265c-13.218-13.218-13.218-34.645 0-47.863 13.218-13.218 34.645-13.218 47.863 0l95.727 95.727 215.39-215.387c13.218-13.214 34.65-13.218 47.86 0 13.22 13.218 13.22 34.65 0 47.863L177.435 356.928c-6.61 6.605-15.27 9.91-23.932 9.91z"></path>
						</svg>
						<svg class="is_unchecked" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 212.982 212.982">
						<path d="M131.804 106.49l75.936-75.935c6.99-6.99 6.99-18.323 0-25.312-6.99-6.99-18.322-6.99-25.312 0L106.49 81.18 30.555 5.242c-6.99-6.99-18.322-6.99-25.312 0-6.99 6.99-6.99 18.323 0 25.312L81.18 106.49 5.24 182.427c-6.99 6.99-6.99 18.323 0 25.312 6.99 6.99 18.322 6.99 25.312 0L106.49 131.8l75.938 75.937c6.99 6.99 18.322 6.99 25.312 0 6.99-6.99 6.99-18.323 0-25.313l-75.936-75.936z" fill-rule="evenodd" clip-rule="evenodd"></path>
						</svg>
					</div>

					<div class="wpsp-scheduler-title">
						<h3>Activate Missed Schedule</h3>
						<p class="wpsp-description-text">WordPress might miss the schedule for a post for various reason. If enabled, <strong>WP Scheduled Posts</strong> will take care of this to publish the missed schedule.</p>
					</div>
				</div><!-- Miss schedule form -->
			</div>
		</div>
		<?php
	}
}