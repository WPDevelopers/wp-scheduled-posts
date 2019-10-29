<?php

class WpScp_Activator {
	/**
	 * Default Data Saving
	 */
	public static function wpscp_default_settings_saving(){
		$options= array(
			'show_dashboard_widget'=>1, 
			'show_in_front_end_adminbar'=>1, 
			'show_in_adminbar'=>1,
			'allow_user_role'=>array('administrator'),
			'allow_post_types'=>array('post'),
			'allow_categories'=>array(0),
			'adminbar_item_template'=>"<strong>%TITLE%</strong> / %AUTHOR% / %DATE%",
			'adminbar_title_length'=>45,
			'adminbar_date_format'=>'M-d h:i:a',
			'prevent_future_post'=>1,
		);
		if(!get_option('wpscp_options')){
			add_option('wpscp_options', $options);
		}
	}

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
		include_once WPSCP_ADMIN_DIR_PATH . 'wpscp-db.php';
		wpscp_database_table_install();
		self::wpscp_default_settings_saving();
		/**
		 * Reqrite the rules on activation.
		 */
		flush_rewrite_rules();
	}

}