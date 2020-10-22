<?php

namespace WPSP;

class Installer
{
    public static function set_settings_transient()
    {
        \WPSP\Admin\Settings\Config::build_settings();
        if (class_exists('WPSP_PRO')) {
            \WPSP_PRO\Admin\Settings\Config::build_settings();
        }
        \WPSP\Admin\Settings\Config::set_default_settings_fields_data();
        if (get_transient(WPSP_SETTINGS_NAME) === false) {
            set_transient(WPSP_SETTINGS_NAME, \WPSP\Admin\Settings\Builder::load());
        } else {
            delete_transient(WPSP_SETTINGS_NAME);
            set_transient(WPSP_SETTINGS_NAME, \WPSP\Admin\Settings\Builder::load());
        }
    }
    /**
     * Default Data Saving
     */
    public static function set_default_settings()
    {
        $options = array(
            'show_dashboard_widget' => 1,
            'show_in_front_end_adminbar' => 1,
            'show_in_adminbar' => 1,
            'allow_user_role' => array('administrator'),
            'allow_post_types' => array('post'),
            'allow_categories' => array(0),
            'adminbar_item_template' => "<strong>%TITLE%</strong> / %AUTHOR% / %DATE%",
            'adminbar_title_length' => 45,
            'adminbar_date_format' => 'M-d h:i:a',
            'prevent_future_post' => 1,
        );
        if (!get_option('wpscp_options')) {
            add_option('wpscp_options', $options);
        }
    }

    public static function create_databse_table()
    {
        global $wpdb;
        if (!current_user_can('activate_plugins'))
            return;

        if (!defined('DB_CHARSET') || !($db_charset = DB_CHARSET))
            $db_charset = 'utf8';
        $db_charset = "CHARACTER SET " . $db_charset;
        if (defined('DB_COLLATE') && $db_collate = DB_COLLATE)
            $db_collate = "COLLATE " . $db_collate;

        /**
         * Create psm_manage_schedule Table 
         * check old database table, if not then add wpdb prefix
         */
        $my_prefix = 'psm_';
        if ($wpdb->get_var("SHOW TABLES LIKE 'psm_manage_schedule'") != 'psm_manage_schedule') {
            $my_prefix = $wpdb->prefix;
        }
        $table_name_manage_schedule = $my_prefix . "manage_schedule";
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name_manage_schedule'") != $table_name_manage_schedule) {
            $sql = "CREATE TABLE IF NOT EXISTS " . $table_name_manage_schedule . " (
					 `id` int(11) NOT NULL AUTO_INCREMENT,
					 `day` varchar(255) NOT NULL,
					 `schedule` varchar(255) NOT NULL,
					 PRIMARY KEY (`id`)
					) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT {$db_charset} {$db_collate};";

            $results = $wpdb->query($sql);
        }
    }

    /**
     * Short Description. (use period)
     *
     * Long Description.
     *
     * @since    1.0.0
     */
    public static function run()
    {
        self::create_databse_table();
        self::set_settings_transient();

        self::set_default_settings();
        /**
         * Reqrite the rules on activation.
         */
        flush_rewrite_rules();
    }
}
