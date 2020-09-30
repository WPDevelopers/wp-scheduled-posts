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
        add_menu_page(__('Scheduled Posts', 'wp-scheduled-posts'), __('Scheduled Posts', 'wp-scheduled-posts'), 'manage_options', WPSP_PLUGIN_ROOT_PATH, [$this, 'load_settings_template'], esc_url(WPSP_ASSETS_URI . 'images/wpsp-icon.png'), 80);
        add_submenu_page(WPSP_PLUGIN_ROOT_PATH, __('Settings', 'wp-scheduled-posts'), __('Settings', 'wp-scheduled-posts'), 'manage_options', WPSP_PLUGIN_ROOT_PATH, [$this, 'load_settings_template']);
        add_submenu_page(WPSP_PLUGIN_ROOT_PATH, 'Calendar', 'Calendar', 'manage_options', 'wp-scheduled-calendar', array($this, 'load_calendar_template'));
        $this->add_sub_menu_for_calendar_supported_post_type();
    }

    /**
     * Add Calendar Menu for supported post type
     */
    public function add_sub_menu_for_calendar_supported_post_type()
    {
        $wpscp_all_options  = get_option('wpscp_options');
        $allow_post_types =  ($wpscp_all_options['allow_post_types'] == '' ? array('post') : $wpscp_all_options['allow_post_types']);
        foreach ($allow_post_types as $post_types) {
            $admin_menu_url = ($post_types != 'post' ? 'edit.php?post_type=' . $post_types : 'edit.php');
            add_submenu_page($admin_menu_url, __('Calendar', 'wp-scheduled-posts'), __('Calendar', 'wp-scheduled-posts'), 'edit_posts', 'wp-scheduled-calendar-' . $post_types, array($this, 'load_calendar_template'));
        }
    }


    public function load_settings_template()
    {
        include_once WPSP_VIEW_DIR_PATH . 'settings.php';
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
