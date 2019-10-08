<?php
/**
 * Delete - Manual Schedule Item
 * 
 * @function deleteManualScheduleItem
 */
add_action( 'wp_ajax_deleteManualScheduleItem', 'deleteManualScheduleItem' );
function deleteManualScheduleItem() {
	if(isset($_POST['man_item_value'])) {
		$man_item_value = $_POST['man_item_value'];

		global $wpdb;
		$my_prefix = 'psm_';
		if($wpdb->get_var("SHOW TABLES LIKE 'psm_manage_schedule'") != 'psm_manage_schedule'){
			$my_prefix = $wpdb->prefix;
		}
		$my_table = $my_prefix . 'manage_schedule';
		$sql = "DELETE FROM " . $my_table . " WHERE id='$man_item_value' ";
		$delete = $wpdb->get_results($sql, ARRAY_A);
	}

	echo json_encode(array( 'man_item_deleted_msg' => $delete),JSON_PRETTY_PRINT);
	exit();
}


/**
 * save - Manage Schedule Datas
 * 
 * @function manage_sched_opt_saved
 */
add_action( 'wp_ajax_manage_sched_opt_saved', 'manage_sched_opt_saved' );
function manage_sched_opt_saved() {
	if(isset($_POST['datas'])) {
		$datas = $_POST['datas'];

		//auto and manual schedule
		$aut_check 	= $datas['pub_check'];
		$man_check 	= $datas['cal_check'];
		//start & end time
		$start 		= $datas['start_time'];
		$end 		= $datas['end_time'];
		//days
		$pts_0 		= $datas['days']['wpsp_pts_0'];
		$pts_1 		= $datas['days']['wpsp_pts_1'];
		$pts_2 		= $datas['days']['wpsp_pts_2'];
		$pts_3 		= $datas['days']['wpsp_pts_3'];
		$pts_4 		= $datas['days']['wpsp_pts_4'];
		$pts_5 		= $datas['days']['wpsp_pts_5'];
		$pts_6 		= $datas['days']['wpsp_pts_6'];

		//activate auto schedule or manual schedule
		if( isset($aut_check) && $aut_check == 'ok' ) {
			delete_option('cal_active_option');
			add_option('pub_active_option', $aut_check);
		}else if ( isset($man_check) && $man_check == 'ok' ) {
			delete_option('pub_active_option');
			add_option('cal_active_option', $man_check);
		}else{
			delete_option('pub_active_option');
		}

		//get schedule options
		$options = get_option("manage-schedule.php");
	
		//set scedule start or end time
		$options['pts_start'] = $start;
		$options['pts_end'] = $end;

		//set days
		$options['pts_0'] = isset($pts_0) ? $pts_0 : '';
		$options['pts_1'] = isset($pts_1) ? $pts_1 : '';
		$options['pts_2'] = isset($pts_2) ? $pts_2 : '';
		$options['pts_3'] = isset($pts_3) ? $pts_3 : '';
		$options['pts_4'] = isset($pts_4) ? $pts_4 : '';
		$options['pts_5'] = isset($pts_5) ? $pts_5 : '';
		$options['pts_6'] = isset($pts_6) ? $pts_6 : '';

       	//When all weeks are NO, change the monday to YES
		$allNo = 0;
		for ($i = 0; $i < 7; $i++) {
			if ($options['pts_' . $i] == 'no') {
				$allNo += 1;
			} else {
				break;
			}
		}
		if ($allNo == 7) {
			$options['pts_1'] = 'Yes';
		}

		while (strlen($options['pts_start']) < 5) {
			$options['pts_start'] = "0" . $options['pts_start'];
		}

		while (strlen($options['pts_end']) < 5) {
			$options['pts_end'] = "0" . $options['pts_end'];
		}

		if (!gmdate('H:i', strtotime($options['pts_start']))) {
			$options['pts_start'] = '00:00';
		}

        //guarantee a valid time
		if (!gmdate('H:i', strtotime($options['pts_end']))) {
			$options['pts_end'] = '23:59';
		}

		$time = explode(":", $options['pts_start']);
		if (strlen($time[0]) < 2) {
			$time[0] = '0' . $time[0];
		}

		if (strlen($time[1]) < 2) {
			$time[1] = '0' . $time[1];
		}

		$options['pts_start'] = date("H:i", mktime($time[0], $time[1], 0, 9, 11, 2001)); 
		$time = explode(":", $options['pts_end']);
		if (strlen($time[0]) < 2) {
			$time[0] = '0' . $time[0];
		}

		if (strlen($time[1]) < 2) {
			$time[1] = '0' . $time[1];
		}

		$options['pts_end'] = date("H:i", mktime($time[0], $time[1], 0, 9, 11, 2001));

        // store the option values
		update_option("manage-schedule.php", $options);

		$updated = 'updated';
	}

	echo json_encode(array( 'updated_msg' => $options),JSON_PRETTY_PRINT);
	exit();
}


/**
 * save - Manual Schedule Inserted Datas
 * 
 * @function wpscp_manual_schedule_opt_saved
 */
add_action( 'wp_ajax_wpscp_manual_schedule_opt_saved', 'wpscp_manual_schedule_opt_saved' );
function wpscp_manual_schedule_opt_saved() {
	if(isset($_POST['days']) || isset($_POST['times']) ) {
		$day = $_POST['days'];
		$time = trim($_POST['times']);

		//start here
		global $wpdb;
		$my_prefix = 'psm_';
		if($wpdb->get_var("SHOW TABLES LIKE 'psm_manage_schedule'") != 'psm_manage_schedule'){
			$my_prefix = $wpdb->prefix;
		}
		$my_table = $my_prefix . 'manage_schedule';

		$get_cal_op = get_option('cal_active_option');
		$activate_cal_option = html_entity_decode(stripslashes($get_cal_op));

		$massage = "";
		if (!empty($day) && !empty($time)) {
			//insert query for psm_manage_schedule table
			$sql = "INSERT INTO  " . $my_table . " (`day`,`schedule`) VALUES ('$day','$time')";
			$insert = $wpdb->query($sql);

			$get_query = "SELECT id FROM ". $my_table ." WHERE day='$day' AND schedule='$time' ";
			$select = $wpdb->get_row($get_query);

			if ($insert) {
				$massage = "Macnual Schedule Inserted";
			} else {
				$massage = "Manual Schedule Not Inserted";
			}
		}
	}

	echo json_encode(array( 'manual_inserted_msg' => $massage, 'result' => $select),JSON_PRETTY_PRINT);
	exit();
}


/**
 * Update missed schedule value
 * 
 * @function missedScheduleVal
 */
add_action( 'wp_ajax_missedScheduleVal', 'missedScheduleVal' );
function missedScheduleVal() {
	if(isset($_POST['missed_sched_val'])) {
		$missed_sched_val = $_POST['missed_sched_val'];

		if(!empty($missed_sched_val)) {
			add_option('miss_schedule_active_option', $missed_sched_val);
		}else{
			delete_option('miss_schedule_active_option');
		}
	}

	echo json_encode(array( 'missed_val' => $missed_sched_val),JSON_PRETTY_PRINT);
	exit();
}