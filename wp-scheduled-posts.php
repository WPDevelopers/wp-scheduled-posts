<?php
/*
 * Plugin Name: SchedulePress (Formerly Known as WP Scheduled Posts)
 * Description: Automate your content workflow with SchedulePress. Take a quick glance at your content planning with Schedule Calendar, Dashboard widget & Sitewide admin bar. Instantly share your posts on social media platforms such as Facebook, Twitter & many more.
 * Version: 4.1.6
 * Author: WPDeveloper
 * Author URI: https://wpdeveloper.com
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
		register_activation_hook(__FILE__, [$this, 'activate']);
		register_deactivation_hook(__FILE__, [$this, 'deactivate']);
		$this->installer = new WPSP\Installer();
		add_action('plugins_loaded', [$this, 'init_plugin']);
		add_action('wp_loaded', [$this, 'run_migrator']);
		add_action('init', [$this, 'load_calendar']);
		add_filter('jwt_auth_whitelist', array($this, 'whitelist_API'));
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
		define('WPSP_VERSION', '4.1.6');
		define('WPSP_SETTINGS_NAME', 'wpsp_settings');
		define('WPSP_PLUGIN_FILE', __FILE__);
		define('WPSP_PLUGIN_BASENAME', plugin_basename(__FILE__));
		define('WPSP_PLUGIN_SLUG', 'wp-scheduled-posts');
		define('WPSP_SETTINGS_SLUG', 'schedulepress');
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
		}
		new WPSP\Email();
		new WPSP\Social();
		$this->load_textdomain();
		new WPSP\API();
		new WPSP\Ajax();
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
		update_option('wpsp_do_activation_redirect', true);
	}

	/**
	 * Do stuff upon plugin deactive
	 *
	 * @return void
	 */
	public function deactivate()
	{
		do_action('wpsp_run_deactivate_installer');
	}


	public function run_migrator()
	{
		$this->installer->migrate();
	}
	public function load_calendar()
	{
		new WPSP\Admin\Calendar();
	}
	public function whitelist_API($endpoints)
    {
        $endpoints[] = '/wp-json/wp-scheduled-posts/v1/*';
        $endpoints[] = '/index.php?rest_route=/wp-scheduled-posts/v1/*';
        return $endpoints;
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
