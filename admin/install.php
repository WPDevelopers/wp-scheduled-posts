<?php
	function psm_install() { 
		global $wpdb;
		

		if ( !current_user_can('activate_plugins') ) 
			return;
			
		if(!defined('DB_CHARSET') || !($db_charset = DB_CHARSET))
			$db_charset = 'utf8';
		$db_charset = "CHARACTER SET ".$db_charset;
		if(defined('DB_COLLATE') && $db_collate = DB_COLLATE) 
			$db_collate = "COLLATE ".$db_collate;

		//Create psm_manage_schedule Table
		$my_prefix = 'psm_';
		$table_name_manage_schedule = $my_prefix . "manage_schedule";
		if($wpdb->get_var("SHOW TABLES LIKE '$table_name_manage_schedule'") != $table_name_manage_schedule) {
			$sql = "CREATE TABLE IF NOT EXISTS ". $table_name_manage_schedule ." (
					 `id` int(11) NOT NULL AUTO_INCREMENT,
					 `day` varchar(255) NOT NULL,
					 `schedule` varchar(255) NOT NULL,
					 PRIMARY KEY (`id`)
					) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT {$db_charset} {$db_collate};";

			$results = $wpdb->query( $sql );
		}
			
		
	}
	


?>