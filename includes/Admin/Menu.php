<?php

namespace WPSP\Admin;

class Menu
{
    /**
     * add plugin menu page and submenu pages
     */
    public function __construct()
    {
        $this->hooks();
    }



    /**
     * All Hooks Written Here
     * @method hooks
     * @since 1.0.0
     *
     */
    public function hooks()
    {
        add_action('admin_menu', array($this, 'admin_menu'));
    }
    /**
     * add plugin main & sub menu for supported post type
     * @since 2.5.0
     * @return hooks
     */
    public function admin_menu()
    {
        add_menu_page(__('SchedulePress', 'wp-scheduled-posts'), __('SchedulePress', 'wp-scheduled-posts'), 'manage_options', WPSP_SETTINGS_SLUG, [$this, 'load_settings_template'], esc_url(WPSP_ASSETS_URI . 'images/wpsp-icon.png'), 80);
        add_submenu_page(WPSP_SETTINGS_SLUG, __('Settings', 'wp-scheduled-posts'), __('Settings', 'wp-scheduled-posts'), 'manage_options', WPSP_SETTINGS_SLUG, [$this, 'load_settings_template']);
        add_submenu_page(WPSP_SETTINGS_SLUG, 'Calendar', 'Calendar', 'manage_options', WPSP_SETTINGS_SLUG . '-calendar', array($this, 'load_calendar_template'));
        $this->add_sub_menu_for_calendar_supported_post_type();
    }

    /**
     * Add Calendar Menu for supported post type
     */
    public function add_sub_menu_for_calendar_supported_post_type()
    {
        $allow_post_types = \WPSP\Helper::get_settings('allow_post_types');
        if (is_array($allow_post_types)) {
            foreach ($allow_post_types as $post_type) {
                $admin_menu_url = ($post_type != 'post' ? 'edit.php?post_type=' . $post_type : 'edit.php');
                add_submenu_page($admin_menu_url, __('Calendar', 'wp-scheduled-posts'), __('Calendar', 'wp-scheduled-posts'), 'edit_posts', WPSP_SETTINGS_SLUG . '-' . $post_type, array($this, 'load_calendar_template'));
            }
        }
    }


    public function load_settings_template()
    {
        echo '<div id="wpsp-dashboard-body" class="wpsp-dashboard-body"></div>';
    }



    /**
     * Load Calendar Template
     * @method load_calendar_template
     * @since 3.0.1
     */
    public function load_calendar_template()
    {
        include_once WPSP_VIEW_DIR_PATH . 'calendar.php';
    }
}
