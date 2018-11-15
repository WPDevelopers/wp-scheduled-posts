<?php
/*
 * Plugin Name: WP Scheduled Posts Pro
 * Plugin URI: https://wpdeveloper.net/free-plugin/wp-scheduled-posts/
 * Description: A complete solution for WordPress Post Schedule. Get an admin Bar & Dashboard Widget showing all your scheduled posts. And full control.
 * Version: 2.0
 * Author: WP Developer
 * Author URI: https://wpdeveloper.net
 * License: GPL2+
 * Text Domain: wp-scheduled-posts
 * Min WP Version: 2.5.0
 * Max WP Version: 4.8
 */
    function admin_dependency_plugin()
    {
        if(!class_exists('wpsp_addon'))
        {
            die('"WP Scheduled Posts pro" requires "WP Scheduled Posts" Latest Plugin. Please install it.<a href="https://wordpress.org/plugins/wp-scheduled-posts/" target="_blank">WP Scheduled Posts</a>');
        }
        
        
    }
    register_activation_hook( __FILE__, 'admin_dependency_plugin' );


