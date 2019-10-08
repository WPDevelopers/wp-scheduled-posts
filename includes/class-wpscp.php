<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    wp-scheduled-posts
 * @subpackage wp-scheduled-posts/includes
 * @author     WPDeveloper <support@wpdeveloper.net>
 */
final class WpScp {
    /**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
    protected $plugin_name;

    /**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
    protected $version;
    
    /**
     * Check Pro Plugin is activated
     */
    protected $pro_enabled = false;

    /**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
    public function __construct() {
		if ( defined( 'WPSP_VERSION' ) ) {
			$this->version = WPSP_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'WP Scheduled Posts';
        $this->load_dependencies();
        $this->set_textdomain();
        $this->load_scripts();
        $this->wpscp_functions();
        $this->pro_enabled();
        if (is_admin()) {
            // Core
            add_filter('plugin_action_links_' . WPSP_PLUGIN_BASENAME, array($this, 'insert_plugin_links'));
            add_filter('plugin_row_meta', array($this, 'insert_plugin_row_meta'), 10, 2);
            
            $this->admin_notice(); // this line of code
        }
    }

    /**
     * Loaded Dependency Files
     *
     * @function load_dependencies
     */
    
    public function load_dependencies() {
        require_once WPSCP_INCLUDES_DIR_PATH . 'class-wpscp-i18n.php';
        require_once WPSCP_ADMIN_DIR_PATH . 'class-wpscp-admin.php';
        require_once WPSCP_INCLUDES_DIR_PATH . 'class-plugin-usage-tracker.php';
        require_once WPSCP_INCLUDES_DIR_PATH . 'class-wpdev-core-install.php';
        require_once WPSCP_INCLUDES_DIR_PATH . 'class-wpdev-notices.php';
    }

    /**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the WpScp_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    2.3.1
	 * @access   private
	 */
    private function set_textdomain(){
		$plugin_i18n = new WpScp_i18n();
		add_action( 'plugins_loaded', array( $plugin_i18n, 'load_plugin_textdomain' ) );
    }
    

    /**
     * Load Plugin All admin script
     * @method load_scripts
     * @since 2.3.1
     */
    public function load_scripts(){
        // admin script
        add_action( 'admin_enqueue_scripts', array($this, 'plugin_scripts') );
        // adminbar enqueue
        add_action( 'admin_enqueue_scripts', array($this, 'adminbar_script') );
        add_action( 'wp_enqueue_scripts', array($this, 'adminbar_script') );
        
    }

    /**
     * Main Function
     * @method wpscp_functions
     * @since 2.3.1
     */
    public function wpscp_functions(){
        include_once WPSCP_INCLUDES_DIR_PATH . 'wpscp-functions.php';
    }

    /**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    2.3.1
	 */
	public function run() {
		return $this;
	}

    /**
     * Enqueue Files on Start Plugin
     *
     * @function plugin_script
     */
    public function plugin_scripts( $hook ) {
        if ( is_admin() ) {
            wp_enqueue_style( 'admin-style', WPSCP_ADMIN_URL . 'assets/css/admin.css' );
            wp_enqueue_style( 'font-awesome', WPSCP_ADMIN_URL . 'assets/css/font-awesome.min.css' );
            wp_enqueue_style( 'select2-css', WPSCP_ADMIN_URL . 'assets/css/select2.min.css' );
            wp_enqueue_style( 'chung-timepicker', WPSCP_ADMIN_URL . 'assets/css/chung-timepicker.css' );
            // js
            wp_enqueue_script( 'main-chung-timepicker', WPSCP_ADMIN_URL . 'assets/js/chung-timepicker.js', array('jquery'), null, false );
            wp_enqueue_script( 'select2', WPSCP_ADMIN_URL . 'assets/js/select2.full.js', array('jquery'), null, false );
            wp_enqueue_script( 'sweetalert', WPSCP_ADMIN_URL . 'assets/js/sweetalert.min.js', array('jquery'), null, false );
            wp_enqueue_script( 'custom-script', WPSCP_ADMIN_URL . 'assets/js/custom-script.js', array('jquery'), null, false );
            wp_enqueue_script( 'wpscp-script', WPSCP_ADMIN_URL . 'assets/js/wpscp-script.js', array('jquery'), null, false );
            wp_localize_script( 'custom-script', 'wpscp_ajax',
            array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
        }
    }

    /**
     * Admin bar Script
     * add some css and js in adminbar
     * 
     * @since 2.3.1
     */
    public function adminbar_script(){
        if(is_admin_bar_showing()){
            wp_enqueue_style( 'wpscp-adminbar', WPSCP_ADMIN_URL . 'assets/css/adminbar.css' );
            wp_enqueue_script( 'wpscp-adminbar', WPSCP_ADMIN_URL . 'assets/js/adminbar.js', array('jquery'), null, false );
        }
    }

    /**
     * Check Pro version is enabled
     */

    public function pro_enabled(){
        if(function_exists('is_plugin_active')){
            return $this->pro_enabled = is_plugin_active('wp-scheduled-posts-pro/wp-scheduled-posts-pro.php');
        } else {
            if(class_exists('WpScp_Pro')){
                return true;
            }
            return false;
        }
    }

     /**
     * Extending plugin links
     *
     * @since 2.3.1
     */
    public function insert_plugin_links($links)
    {
        // settings
        $links[] = sprintf('<a href="admin.php?page=wp-scheduled-posts">' . __('Settings') . '</a>');
        
        // go pro
        if ( !$this->pro_enabled() ) {
            $links[] = sprintf('<a href="https://wpdeveloper.net/in/wp-scheduled-posts-pro" target="_blank" style="color: #39b54a; font-weight: bold;">' . __('Go Pro') . '</a>');
        }

        return $links;
    }

     /**
     * Extending plugin row meta
     *
     * @since 2.3.1
     */
    public function insert_plugin_row_meta($links, $file)
    {
        if (WPSP_PLUGIN_BASENAME == $file) {
            // docs & faq
            $links[] = sprintf('<a href="https://wpdeveloper.net/docs/wp-scheduled-posts/?utm_medium=admin&utm_source=wp.org&utm_term=wpsp" target="_blank">' . __('Docs & FAQs') . '</a>');

            // video tutorials
            // $links[] = sprintf('<a href="https://www.youtube.com/channel/UCOjzLEdsnpnFVkm1JKFurPA?utm_medium=admin&utm_source=wp.org&utm_term=ea" target="_blank">' . __('Video Tutorials') . '</a>');
        }

        return $links;
    }

    public function admin_notice()
    {
        $notice = new WpScp_WPDeveloper_Notice(WPSP_PLUGIN_BASENAME, WPSP_VERSION);
        
        /**
         * Current Notice End Time.
         * Notice will dismiss in 3 days if user does nothing.
         */
        $notice->cne_time = '3 Day';
        /**
         * Current Notice Maybe Later Time.
         * Notice will show again in 7 days
         */
        $notice->maybe_later_time = '7 Day';

        $notice->text_domain = 'wp-scheduled-posts';

        $scheme = (parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY)) ? '&' : '?';
        $url = $_SERVER['REQUEST_URI'] . $scheme;
        $notice->links = [
            'review' => array(
                'later' => array(
                    'link' => 'https://wpdeveloper.net/go/review-wpsp',
                    'target' => '_blank',
                    'label' => __('Ok, you deserve it!', 'wp-scheduled-posts'),
                    'icon_class' => 'dashicons dashicons-external',
                ),
                'allready' => array(
                    'link' => $url,
                    'label' => __('I already did', 'wp-scheduled-posts'),
                    'icon_class' => 'dashicons dashicons-smiley',
                    'data_args' => [
                        'dismiss' => true,
                    ],
                ),
                'maybe_later' => array(
                    'link' => $url,
                    'label' => __('Maybe Later', 'wp-scheduled-posts'),
                    'icon_class' => 'dashicons dashicons-calendar-alt',
                    'data_args' => [
                        'later' => true,
                    ],
                ),
                'support' => array(
                    'link' => 'https://wpdeveloper.net/support',
                    'label' => __('I need help', 'wp-scheduled-posts'),
                    'icon_class' => 'dashicons dashicons-sos',
                ),
                'never_show_again' => array(
                    'link' => $url,
                    'label' => __('Never show again', 'wp-scheduled-posts'),
                    'icon_class' => 'dashicons dashicons-dismiss',
                    'data_args' => [
                        'dismiss' => true,
                    ],
                ),
            ),
        ];

        /**
         * This is review message and thumbnail.
         */
        $notice->message('review', '<p>' . __('We hope you\'re enjoying WP Scheduled Posts! Could you please do us a BIG favor and give it a 5-star rating on WordPress to help us spread the word and boost our motivation?', 'wp-scheduled-posts') . '</p>');
        $notice->thumbnail('review', plugins_url('admin/assets/images/wpsp-logo.svg', WPSP_PLUGIN_BASENAME));
        /**
         * This is upsale notice settings
         * classes for wrapper, 
         * Message message for showing.
         */
        $notice->classes( 'upsale', 'notice is-dismissible ' );
        $notice->message( 'upsale', '<p>'. __( 'We hope you\'re enjoying WP Scheduled Posts! Upgrade to <a href="https://wpdeveloper.net/in/wp-scheduled-posts-pro" target="_blank">Premium Version</a> for more stunning features!', $notice->text_domain ) .'</p>' );
        $notice->thumbnail( 'upsale', plugins_url( 'admin/assets/images/wpsp-logo.svg', WPSP_PLUGIN_BASENAME ) );

        $notice->upsale_args = array(
            'slug'      => 'wp-scheduled-posts-pro',
            'page_slug' => 'wp-scheduled-posts-pro',
            'file'      => 'wp-scheduled-posts-pro.php',
            'btn_text'  => __( 'Install Pro', 'wp-scheduled-posts' ),
            'condition' => [
                'by' => 'class',
                'class' => 'WpScp_Pro'
            ],
        );

    

        $notice->options_args = array(
            'notice_will_show' => [
                'opt_in' => $notice->timestamp,
                'upsale' => $notice->makeTime($notice->timestamp, '7 Day'),
                'review' => $notice->makeTime($notice->timestamp, '3 Day'), // after 3 days
            ],
        );

        $notice->init();
    }
}