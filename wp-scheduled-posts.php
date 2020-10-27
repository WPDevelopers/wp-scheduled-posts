<?php

use WPSP\Admin\Settings\Builder;
/*
 * Plugin Name: WP Scheduled Posts
 * Description: A complete solution for WordPress Post Schedule. Manage schedule through editorial calendar and enable auto scheduler. Also handles auto social share in Facebook, Twitter, linkedIn, Pinterest & Instagram. Get an admin Bar & Dashboard Widget showing all your scheduled posts.
 * Version: 3.3.2
 * Author: WPDeveloper
 * Author URI: https://wpdeveloper.net
 * Text Domain: wp-scheduled-posts
 */

if (!defined('ABSPATH')) exit;

if (file_exists(dirname(__FILE__) . '/vendor/autoload.php')) {
	require_once dirname(__FILE__) . '/vendor/autoload.php';
}


final class WPSP
{
	private $installer;
	private function __construct()
	{
		$this->define_constants();
		$this->set_global_settings();
		$this->installer = new WPSP\Installer();
		register_activation_hook(__FILE__, [$this, 'activate']);
		add_action('plugins_loaded', [$this, 'init_plugin']);
		add_action('wp_loaded', [$this, 'run_migrator']);
		add_action('admin_init', [$this, 'redirect_to_quick_setup']);
		add_action('init', [$this, 'load_calendar']);
	}

	public static function init()
	{
		static $instance = false;

		if (!$instance) {
			$instance = new self();
		}

		return $instance;
	}
	public function define_constants()
	{
		/**
		 * Defines CONSTANTS for Whole plugins.
		 */
		define('WPSP_VERSION', '3.3.1');
		define('WPSP_SETTINGS_NAME', 'wpsp_settings');
		define('WPSP_PLUGIN_FILE', __FILE__);
		define('WPSP_PLUGIN_BASENAME', plugin_basename(__FILE__));
		define('WPSP_PLUGIN_SLUG', 'wp-scheduled-posts');
		define('WPSP_PLUGIN_ROOT_URI', plugins_url("/", __FILE__));
		define('WPSP_PLUGIN_ROOT_PATH', plugin_basename(dirname(__FILE__)));
		define('WPSP_ADMIN_URL', WPSP_PLUGIN_ROOT_URI . 'includes/Admin/');
		define('WPSP_ROOT_DIR_PATH', plugin_dir_path(__FILE__));
		define('WPSP_INCLUDES_DIR_PATH', WPSP_ROOT_DIR_PATH . 'includes/');
		define('WPSP_VIEW_DIR_PATH', WPSP_ROOT_DIR_PATH . 'views/');
		define('WPSP_ASSETS_DIR_PATH', WPSP_ROOT_DIR_PATH . 'assets/');
		define('WPSP_ASSETS_URI', WPSP_PLUGIN_ROOT_URI . 'assets/');
		// Midleware
		define('WPSP_SOCIAL_OAUTH2_TOKEN_MIDDLEWARE', 'https://api.schedulepress.com/callback.php');
	}

	/**
	 * Initialize the plugin
	 *
	 * @return void
	 */
	public function init_plugin()
	{
		new WPSP\Assets();
		if (is_admin()) {
			new WPSP\Admin();
			new WPSP\Social();
		}
		$this->load_textdomain();
		new WPSP\API();
	}

	public function load_textdomain()
	{

		load_plugin_textdomain(
			'wp-scheduled-posts',
			false,
			dirname(dirname(plugin_basename(__FILE__))) . '/languages/'
		);
	}

	public function set_global_settings()
	{
		$GLOBALS['wpsp_settings'] = json_decode(get_option(WPSP_SETTINGS_NAME));
	}

	/**
	 * Do stuff upon plugin activation
	 *
	 * @return void
	 */
	public function activate()
	{
		$this->installer->run();
		add_option('wpsp_do_activation_redirect', true);
	}

	public function run_migrator()
	{
		$this->installer->migrate();
	}

	public function redirect_to_quick_setup()
	{
		if (get_option('wpsp_do_activation_redirect', false)) {
			delete_option('wpsp_do_activation_redirect');
			wp_redirect("admin.php?page=wpscp-quick-setup-wizard");
		}
	}
	public function load_calendar()
	{
		new WPSP\Admin\Calendar();
	}
}

/**
 * Initializes the main plugin
 *
 * @return \WPSP
 */
function WPSP_Start()
{
	return WPSP::init();
}

// Plugin Start
WPSP_Start();
