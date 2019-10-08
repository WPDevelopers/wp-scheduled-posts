<?php

class WpScp_Activator {

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
		/**
		 * Reqrite the rules on activation.
		 */
		flush_rewrite_rules();
	}

}