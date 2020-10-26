<?php

namespace WPSP;

class Installer
{
    public function set_version()
    {
        if (!get_option('WPSP_VERSION')) {
            add_option('WPSP_VERSION', WPSP_VERSION);
        } else {
            update_option('WPSP_VERSION', WPSP_VERSION);
        }
    }
    public function set_settings()
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
     * Short Description. (use period)
     *
     * Long Description.
     *
     * @since    1.0.0
     */
    public function run()
    {
        $this->set_version();
        $this->set_settings();
    }

    public function migrate()
    {
        if (!get_option('wpsp_react_settings_migrate')) {
            $old_settings = get_option('wpscp_options');
            // general settings
            $settings = json_decode(get_option(WPSP_SETTINGS_NAME), true);
            $settings->is_show_dashboard_widget = $old_settings['show_dashboard_widget'];
            $settings->is_show_sitewide_bar_posts = $old_settings['show_in_front_end_adminbar'];
            $settings->is_show_admin_bar_posts = $old_settings['show_in_adminbar'];
            $settings->allow_post_types = $old_settings['allow_post_types'];
            $settings->allow_user_by_role = $old_settings['allow_user_role'];
            $settings->allow_categories = $old_settings['allow_categories'];
            $settings->adminbar_list_structure_template = $old_settings['adminbar_item_template'];
            $settings->adminbar_list_structure_title_length = $old_settings['adminbar_title_length'];
            $settings->adminbar_list_structure_date_format = $old_settings['adminbar_date_format'];
            $settings->show_publish_post_button = $old_settings['prevent_future_post'];
            // email notify
            $settings->notify_author_post_is_review = get_option('wpscp_notify_author_is_sent_review');
            $settings->notify_author_post_review_by_role = get_option('wpscp_notify_author_role_sent_review');
            $settings->notify_author_post_review_by_username = get_option('wpscp_notify_author_username_sent_review');
            $settings->notify_author_post_review_by_email = get_option('wpscp_notify_author_email_sent_review');
            $settings->notify_author_post_is_rejected = get_option('wpscp_notify_author_post_is_rejected');
            $settings->notify_author_post_is_scheduled = get_option('wpscp_notify_author_post_is_schedule');
            $settings->notify_author_post_scheduled_by_role = get_option('wpscp_notify_author_post_schedule_role');
            $settings->notify_author_post_scheduled_by_username = get_option('wpscp_notify_author_post_schedule_username');
            $settings->notify_author_post_scheduled_by_email = get_option('wpscp_notify_author_post_schedule_email');
            $settings->notify_author_post_scheduled_to_publish = get_option('wpscp_notify_author_schedule_post_is_publish');
            $settings->notify_author_post_is_publish = get_option('wpscp_notify_author_post_is_publish');


            update_option(WPSP_SETTINGS_NAME, json_encode($settings));

            update_option('wpsp_react_settings_migrate', true);
        }
    }
}
