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
        if ( !is_plugin_active('wp-scheduled-posts/wp-scheduled-posts.php') ) 
        {
    ?>
    <div class="error">
        <p>
            <?php _e('"WP Scheduled Posts pro" requires "WP Scheduled Posts" Latest Plugin. Please install it. ', 'wp-scheduled-posts'); ?>
            <a href="https://wordpress.org/plugins/wp-scheduled-posts/" target="_blank">WP Scheduled Posts</a>
        </p>
    </div>
    <?php
        }
    }
    add_action( 'admin_notices', 'admin_dependency_plugin');

    include('manage-schedule/manage-schedule.php');

