<?php
/*
 * Plugin Name: SchedulePress
 * Description: Automate your content workflow with SchedulePress. Take a quick glance at your content planning with Schedule Calendar, Dashboard widget & Sitewide admin bar. Instantly share your posts on social media platforms such as Facebook, Twitter & many more.
 * Version: 5.0.0
 * Author: WPDeveloper
 * Author URI: https://wpdeveloper.com
 * Text Domain: wp-scheduled-posts
 */

if (!defined('ABSPATH')) exit;

if ( ! version_compare( PHP_VERSION, '7.2', '>=' ) ) {
	add_action( 'admin_notices', 'wpsp_fail_php_version', 51 );
	return;
}
else {
	if (file_exists(dirname(__FILE__) . '/vendor/autoload.php')) {
		require_once dirname(__FILE__) . '/vendor/autoload.php';
	}
	// Plugin Start
	WPSP_Start();
}


final class WPSP
{
	private $installer;
	private $assets;
	private $admin;
	private $email;
	private $social;
	private $api;
	private $basename = 'wp-scheduled-posts-pro/wp-scheduled-posts-pro.php';

	private function __construct()
	{
		$this->define_constants();
		if( $this->check_pro_compatibility() ) {
			add_action( 'admin_notices', [$this, 'wpsp_fail_pro_version'], 52 );
			// deactivate pro plugin
			if(is_plugin_active( $this->basename )){
				deactivate_plugins( $this->basename );
				return;
			}
		}

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
		define('WPSP_VERSION', '5.0.0');
		define('WPSP_SETTINGS_NAME', 'wpsp_settings_v5');
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
		define('WPSCP_ADMIN_DIR_PATH', WPSP_ROOT_DIR_PATH . '/includes/Admin/');
		// Midleware
		define('WPSP_SOCIAL_OAUTH2_TOKEN_MIDDLEWARE', 'https://api.schedulepress.com.test/callback.php');
		define('WPSP_SOCIAL_OAUTH2_PINTEREST_APP_ID', '1477330');
		define('WPSP_SOCIAL_OAUTH2_LINKEDIN_APP_ID', '77nbfvpkganvt6');

	}

	public function check_pro_compatibility(){
		if ( ! function_exists( 'is_plugin_active' ) ) {
			include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		}

		$abs_path = WP_PLUGIN_DIR . '/' . $this->basename;

		if (
			$this->is_plugin_installed( $this->basename ) &&
			version_compare( get_plugin_data( $abs_path )['Version'], '5.0.0', '<' )
		) {
			return true;
		}
		return false;
	}

    /**
     * Check if a plugin is installed
     *
     * @since 2.0.0
     */
    public function is_plugin_installed($basename)
    {
        $plugins = get_plugins();
        return isset($plugins[$basename]);
    }

	public function wpsp_fail_pro_version() {
		?>
		<div class="notice notice-error">
			<p><?php sprintf(_e( 'SchedulePress Free v5.0 needs SchedulePress Pro v5.0 for better performance. Please update SchedulePress Pro plugin to v5.0. Contact our <a href="%s" target="_blank">Support</a> if you need any assistance.', 'wpsp-pro' ), 'https://wpdeveloper.com/support/'); ?></p>
		</div>
		<?php
	}

	/**
	 * Initialize the plugin
	 *
	 * @return void
	 */
	public function init_plugin()
	{
		$this->getAssets();
		$this->getEmail();
		$this->getSocial();
		$this->getAPI();

		if (is_admin()) {
			$this->getAdmin();
			// Your admin code here
		}

		$this->load_textdomain();
	}

	public function getAssets() {
		if (!$this->assets) {
			$this->assets = new WPSP\Assets();
		}
		return $this->assets;
	}

	/**
	 * Undocumented function
	 *
	 * @return WPSP\Admin
	 */
	public function getAdmin() {
		if (!$this->admin) {
			$this->admin = new WPSP\Admin();
		}
		return $this->admin;
	}

	public function getEmail() {
		if (!$this->email) {
			$this->email = new WPSP\Email();
		}
		return $this->email;
	}

	public function getSocial() {
		if (!$this->social) {
			$this->social = new WPSP\Social();
		}
		return $this->social;
	}

	public function getAPI() {
		if (!$this->api) {
			$this->api = new WPSP\API();
		}
		return $this->api;
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

function wpsp_fail_php_version() {
	$message = sprintf(
		/* translators: 1: `<h3>` opening tag, 2: `</h3>` closing tag, 3: PHP version. 4: Link opening tag, 5: Link closing tag. */
		esc_html__( '%1$sSchedulePress isn’t running because PHP is outdated.%2$s Update to PHP version %3$s and get back to creating!', 'wp-scheduled-posts' ),
		'<h3>',
		'</h3>',
		'7.2'
	);
	$html_message = sprintf( '<div class="error">%s</div>', wpautop( $message ) );
	echo wp_kses_post( $html_message );
}