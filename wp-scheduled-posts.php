<?php
/*
 * Plugin Name: WP Scheduled Posts
 * Description: A complete solution for WordPress Post Schedule. Get an admin Bar & Dashboard Widget showing all your scheduled posts.
 * Version: 3.2.1
 * Author: WPDeveloper
 * Author URI: https://wpdeveloper.net
 * Text Domain: wp-scheduled-posts
 */

if (!defined('ABSPATH')) exit;

/**
 * Defines CONSTANTS for Whole plugins.
 */
define('WPSP_VERSION', '3.2.1');
define('WPSP_PLUGIN_FILE', __FILE__);
define('WPSP_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('WPSCP_PLUGIN_SLUG', 'wp-scheduled-posts');
define('WPSCP_ROOT_PLUGIN_URL', plugins_url("/", __FILE__));
define('pluginsFOLDER', plugin_basename(dirname(__FILE__)));
define('WPSCP_ADMIN_URL', WPSCP_ROOT_PLUGIN_URL . 'admin/');
define('WPSCP_ROOT_DIR_PATH', plugin_dir_path(__FILE__));
define('WPSCP_INCLUDES_DIR_PATH', WPSCP_ROOT_DIR_PATH . 'includes/');
define('WPSCP_ADMIN_DIR_PATH', WPSCP_ROOT_DIR_PATH . 'admin/');

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-wpscp-activator.php
 * @since 3.0.0
 */
function activate_wpscp()
{
	require_once WPSCP_ROOT_DIR_PATH . 'includes/class-wpscp-activator.php';
	WpScp_Activator::activate();
}
register_activation_hook(__FILE__, 'activate_wpscp');

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-wpscp-deactivator.php
 * @since 3.0.0
 */
function deactivate_wpscp()
{
	require_once WPSCP_ROOT_DIR_PATH . 'includes/class-wpscp-deactivator.php';
	WpScp_Deactivator::deactivate();
}
register_deactivation_hook(__FILE__, 'deactivate_wpscp');



/**
 * Plugin Redirection After Active
 * @since 3.0.0
 */
function wpscp_lite_plugin_activate()
{
	add_option('wpscp_do_activation_redirect', true);
}
register_activation_hook(__FILE__, 'wpscp_lite_plugin_activate');

function wpscp_lite_plugin_redirect()
{
	if (get_option('wpscp_do_activation_redirect', false)) {
		delete_option('wpscp_do_activation_redirect');
		wp_redirect("admin.php?page=wpscp-quick-setup-wizard");
	}
}
add_action('admin_init', 'wpscp_lite_plugin_redirect');

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 * @since 3.0.0
 */
require_once WPSCP_ROOT_DIR_PATH . 'includes/class-wpscp.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    3.0.0
 */
function run_WpScp()
{
	$plugin = new WpScp();
	$plugin->run();
}
run_WpScp();
