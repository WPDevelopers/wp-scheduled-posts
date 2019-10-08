<?php

class WpScp_Deactivator {
    /**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function deactivate() {
		
		/**
		 * Reqrite the rules on deactivation.
		 */
		flush_rewrite_rules();
	}
}