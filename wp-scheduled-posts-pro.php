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

    function getPluginVersion($allPlugins)
    {
        foreach($allPlugins as $plugins):
            if($plugins['Name'] == "WP Scheduled Posts")
                return $plugins['Version'];
        endforeach;

        return false;

    }
    function admin_dependency_plugin()
    {
    
        $all_plugins = get_plugins();
        if ( ! function_exists( 'get_plugins' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        $plugins=get_plugins();
        $apl=get_option('active_plugins');
        $activated_plugins=array();
        foreach ($apl as $p){           
            if(isset($plugins[$p])){
                 array_push($activated_plugins, $plugins[$p]);
            }           
        }
        $pluginVersion = getPluginVersion($activated_plugins);
        
        if(empty($pluginVersion))
        {
            die('"WP Scheduled Posts pro" requires "WP Scheduled Posts" Latest Plugin. Please install it.<a href="https://wordpress.org/plugins/wp-scheduled-posts/" target="_blank">WP Scheduled Posts</a>');
        }elseif($pluginVersion != '1.9.1' && isset($pluginVersion))
        {
            die('Please Upgrade Your Version');

        }
            //     $dependency_plugin_name = $all_plugins['wp-scheduled-posts/wp-scheduled-posts.php']['Name'];
            
            //     if($dependency_plugin_name == "WP Scheduled Posts")
            //     {

                   // die('WP Scheduled Posts pro" requires "WP Scheduled Posts" Latest Plugin. Please install it.<a href="https://wordpress.org/plugins/wp-scheduled-posts/" target="_blank">WP Scheduled Posts</a>');
    }
  

    register_activation_hook( __FILE__, 'admin_dependency_plugin' );

    include('manage-schedule/manage-schedule.php');

