<?php
if (!defined('ABSPATH')) exit;
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
 * @author     WPDeveloper <support@wpdeveloper.com>
 */
final class WpScp
{
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
    public function __construct()
    {
        if (defined('WPSP_VERSION')) {
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
            $this->admin_notice();
            if (class_exists('WPDeveloper_Dashboard_Widget')) {
                WPDeveloper_Dashboard_Widget::instance();
            }
        }
    }

    /**
     * Loaded Dependency Files
     *
     * @function load_dependencies
     */

    public function load_dependencies()
    {
        require_once WPSCP_INCLUDES_DIR_PATH . 'Traits/Social.php';
        require_once WPSCP_INCLUDES_DIR_PATH .  'class-wpscp-i18n.php';
        require_once WPSCP_ADMIN_DIR_PATH .     'class-wpscp-admin.php';
        require_once WPSCP_INCLUDES_DIR_PATH .  'class-wpscp-options-data.php';
        require_once WPSCP_INCLUDES_DIR_PATH .  'class-plugin-usage-tracker.php';
        require_once WPSCP_INCLUDES_DIR_PATH .  'class-wpdev-core-install.php';
        require_once WPSCP_INCLUDES_DIR_PATH .  'class-wpdev-notices.php';
        require_once WPSCP_INCLUDES_DIR_PATH .  'class-wpdeveloper-dashboard-widget.php';
        require_once WPSCP_INCLUDES_DIR_PATH .  'class-wpscp-notify.php';
        require_once WPSCP_ADMIN_DIR_PATH .     'setup-wizard/wpscp-setup-wizard-config.php';
        require_once WPSCP_INCLUDES_DIR_PATH  . 'integration/class-wpscp-integration.php';
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
    private function set_textdomain()
    {
        $plugin_i18n = new WpScp_i18n();
        add_action('plugins_loaded', array($plugin_i18n, 'load_plugin_textdomain'));
    }


    /**
     * Load Plugin All admin script
     * @method load_scripts
     * @since 2.3.1
     */
    public function load_scripts()
    {
        // admin script
        add_action('enqueue_block_assets', array($this, 'guten_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'plugin_scripts'));
        // adminbar enqueue
        add_action('admin_enqueue_scripts', array($this, 'adminbar_script'));
        add_action('wp_enqueue_scripts', array($this, 'adminbar_script'));
    }

    /**
     * Gutten Support
     * @since 1.2.0
     */
    public function guten_scripts()
    {
        global $post_type;
        $wpspc_options = get_option('wpscp_options');
        $post_types = isset($wpspc_options['allow_post_types']) && !empty($wpspc_options['allow_post_types']) ? $wpspc_options['allow_post_types'] : ['post'];
        if (!in_array($post_type, $post_types)) {
            return;
        }

        wp_enqueue_script('wps-publish-button', WPSCP_ADMIN_URL . 'assets/js/wpspl-admin.min.js', array('wp-components', 'wp-data', 'wp-edit-post', 'wp-editor', 'wp-element', 'wp-i18n', 'wp-plugins'), '1.0.0', true);
        wp_localize_script('wps-publish-button', 'WPSchedulePostsFree', array(
            'publishImmediately' => __('Publish Post Immediately', 'wp-scheduled-posts'),
            'publish_button_off' => $wpspc_options['prevent_future_post'],
            'allowedPostTypes' => $post_types,
            'currentTime' => array(
                'date' => current_time('mysql'),
                'date_gmt' => current_time('mysql', 1),
            ),
        ));
    }

    /**
     * Main Function
     * @method wpscp_functions
     * @since 2.3.1
     */
    public function wpscp_functions()
    {
        include_once WPSCP_INCLUDES_DIR_PATH . 'wpscp-functions.php';
    }

    /**
     * Run the loader to execute all of the hooks with WordPress.
     *
     * @since    2.3.1
     */
    public function run()
    {
        return $this;
    }

    /**
     * Enqueue Files on Start Plugin
     *
     * @function plugin_script
     */
    public function plugin_scripts($hook)
    {
        $current_screen = get_current_screen();
        if (is_admin() && wpscp_is_supported_plugin_page_hook_suffix($current_screen->post_type, $hook)) {
            wp_enqueue_style('select2-css', WPSCP_ADMIN_URL . 'assets/css/vendor/select2.min.css', array(), filemtime(WPSCP_ADMIN_DIR_PATH . 'assets/css/vendor/select2.min.css'), 'all');
            wp_enqueue_style('jquery-datetimepicker', WPSCP_ADMIN_URL . 'assets/css/vendor/jquery.datetimepicker.min.css', array(), filemtime(WPSCP_ADMIN_DIR_PATH . 'assets/css/vendor/jquery.datetimepicker.min.css'), 'all');
            wp_enqueue_style('chung-timepicker', WPSCP_ADMIN_URL . 'assets/css/vendor/chung-timepicker.css', array(), filemtime(WPSCP_ADMIN_DIR_PATH . 'assets/css/vendor/chung-timepicker.css'), 'all');
            wp_enqueue_style('notifi', WPSCP_ADMIN_URL . 'assets/css/vendor/notifi.css', array(), filemtime(WPSCP_ADMIN_DIR_PATH . 'assets/css/vendor/notifi.css'), 'all');
            wp_enqueue_style('full-calendar', WPSCP_ADMIN_URL . 'assets/css/vendor/full-calendar.css', array(), filemtime(WPSCP_ADMIN_DIR_PATH . 'assets/css/vendor/full-calendar.css'), 'all');
            wp_enqueue_style('jquery-modal', WPSCP_ADMIN_URL . 'assets/css/vendor/jquery.modal.min.css', array(), filemtime(WPSCP_ADMIN_DIR_PATH . 'assets/css/vendor/jquery.modal.min.css'), 'all');
            wp_enqueue_style('jquery-timepicker', WPSCP_ADMIN_URL . 'assets/css/vendor/jquery.timepicker.min.css', array(), filemtime(WPSCP_ADMIN_DIR_PATH . 'assets/css/vendor/jquery.timepicker.min.css'), 'all');
            wp_enqueue_style('wpscp-admin', WPSCP_ADMIN_URL . 'assets/css/wpscp-admin.css', array(), filemtime(WPSCP_ADMIN_DIR_PATH . 'assets/css/wpscp-admin.css'), 'all');

            /**
             * JavaScript File
             */
            wp_enqueue_script('jquery-ui-draggable');
            wp_enqueue_script('jquery-ui-droppable');
            wp_enqueue_script('jquery-datetimepicker', WPSCP_ADMIN_URL . 'assets/js/vendor/jquery.datetimepicker.full.min.js', array('jquery'), filemtime(WPSCP_ADMIN_DIR_PATH . 'assets/js/vendor/jquery.datetimepicker.full.min.js'), false);
            wp_enqueue_script('chung-timepicker', WPSCP_ADMIN_URL . 'assets/js/vendor/chung-timepicker.js', array('jquery'), filemtime(WPSCP_ADMIN_DIR_PATH . 'assets/js/vendor/chung-timepicker.js'), false);
            wp_enqueue_script('timepicker', WPSCP_ADMIN_URL . 'assets/js/vendor/jquery.timepicker.min.js', array('jquery'), filemtime(WPSCP_ADMIN_DIR_PATH . 'assets/js/vendor/jquery.timepicker.min.js'), false);
            wp_enqueue_script('jquery-modal', WPSCP_ADMIN_URL . 'assets/js/vendor/jquery.modal.min.js', array('jquery'), filemtime(WPSCP_ADMIN_DIR_PATH . 'assets/js/vendor/jquery.modal.min.js'), false);
            wp_enqueue_script('select2', WPSCP_ADMIN_URL . 'assets/js/vendor/select2.full.js', array('jquery'), filemtime(WPSCP_ADMIN_DIR_PATH . 'assets/js/vendor/select2.full.js'), false);
            wp_enqueue_script('sweetalert', WPSCP_ADMIN_URL . 'assets/js/vendor/sweetalert.min.js', array('jquery'), filemtime(WPSCP_ADMIN_DIR_PATH . 'assets/js/vendor/sweetalert.min.js'), false);
            wp_enqueue_script('notifi', WPSCP_ADMIN_URL . 'assets/js/vendor/notifi.min.js', array('jquery'), filemtime(WPSCP_ADMIN_DIR_PATH . 'assets/js/vendor/notifi.min.js'), false);
            wp_enqueue_script('wpscp-script', WPSCP_ADMIN_URL . 'assets/js/wpscp-script.js', array('jquery'), filemtime(WPSCP_ADMIN_DIR_PATH . 'assets/js/wpscp-script.js'), false);
            wp_localize_script(
                'wpscp-script',
                'wpscp_ajax',
                array('ajax_url' => admin_url('admin-ajax.php'))
            );
            // calendar
            wp_enqueue_script('fullcalendar-core', WPSCP_ADMIN_URL . 'assets/js/vendor/fullcalendar/core/main.js', array('jquery'), filemtime(WPSCP_ADMIN_DIR_PATH . 'assets/js/vendor/fullcalendar/core/main.js'), false);
            wp_enqueue_script('fullcalendar-interaction', WPSCP_ADMIN_URL . 'assets/js/vendor/fullcalendar/interaction/main.js', array('jquery'), filemtime(WPSCP_ADMIN_DIR_PATH . 'assets/js/vendor/fullcalendar/interaction/main.js'), false);
            wp_enqueue_script('fullcalendar-daygrid', WPSCP_ADMIN_URL . 'assets/js/vendor/fullcalendar/daygrid/main.js', array('jquery'), filemtime(WPSCP_ADMIN_DIR_PATH . 'assets/js/vendor/fullcalendar/daygrid/main.js'), false);
            wp_enqueue_script('fullcalendar-timegrid', WPSCP_ADMIN_URL . 'assets/js/vendor/fullcalendar/timegrid/main.js', array('jquery'), filemtime(WPSCP_ADMIN_DIR_PATH . 'assets/js/vendor/fullcalendar/timegrid/main.js'), false);
            wp_enqueue_script('wpscp-fullcalendar', WPSCP_ADMIN_URL . 'assets/js/wpscp-fullcalendar-config.js', array('jquery'), filemtime(WPSCP_ADMIN_DIR_PATH . 'assets/js/wpscp-fullcalendar-config.js'), false);
            // in JavaScript, object properties are accessed as ajax_object.ajax_url, ajax_object.we_value
            $now = new \DateTime('now');
            $month = $now->format('m');
            $year = $now->format('Y');
            wp_localize_script(
                'wpscp-fullcalendar',
                'wpscp_calendar_ajax_object',
                array(
                    'ajax_url' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('wpscp-calendar-ajax-nonce'),
                    'site_url' => site_url('/'),
                    // 'calendar_rest_route' => site_url('/?rest_route=/wpscp/v1/post_type=post/month=' . $month . '/year=' . $year)
                    'calendar_rest_route' => site_url('/?rest_route=/wpscp/v1/calendar&query=' . json_encode(array('post_type' => 'post', 'month' => $month, 'year' => $year)))
                )
            );
            wp_enqueue_script('wpscp-socialprofile', WPSCP_ADMIN_URL . 'assets/js/wpscp-socialprofile.js', array('jquery'), filemtime(WPSCP_ADMIN_DIR_PATH . 'assets/js/wpscp-socialprofile.js'), true);
            wp_localize_script('wpscp-socialprofile', 'wpscpSocialProfile', array(
                'plugin_url'    => WPSCP_ROOT_PLUGIN_URL,
                'nonce'            => wp_create_nonce('wpscp-pro-social-profile'),
                'redirect_url'  => WPSCP_SOCIAL_OAUTH2_TOKEN_MIDDLEWARE,
                'is_active_pro' => class_exists('WpScp_Pro')
            ));
        }
        // admin notice for all wordpress dashboard
        wp_enqueue_style('wpscp-admin-notice', WPSCP_ADMIN_URL . 'assets/css/wpscp-admin-notice.css', array(), filemtime(WPSCP_ADMIN_DIR_PATH . 'assets/css/wpscp-admin-notice.css'), 'all');
    }

    /**
     * Admin bar Script
     * add some css and js in adminbar
     * 
     * @since 2.3.1
     */
    public function adminbar_script()
    {
        if (is_admin_bar_showing()) {
            wp_enqueue_style('wpscp-adminbar', WPSCP_ADMIN_URL . 'assets/css/adminbar.css', array(), filemtime(WPSCP_ADMIN_DIR_PATH . 'assets/css/adminbar.css'), 'all');
            wp_enqueue_script('wpscp-adminbar', WPSCP_ADMIN_URL . 'assets/js/adminbar.js', array('jquery'), filemtime(WPSCP_ADMIN_DIR_PATH . 'assets/js/adminbar.js'), false);
        }
    }

    /**
     * Check Pro version is enabled
     */

    public function pro_enabled()
    {
        if (function_exists('is_plugin_active')) {
            return $this->pro_enabled = is_plugin_active('wp-scheduled-posts-pro/wp-scheduled-posts-pro.php');
        } else {
            if (class_exists('WpScp_Pro')) {
                return $this->pro_enabled = true;
            }
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
        $links[] = sprintf('<a href="admin.php?page=wp-scheduled-posts">' . __('Settings', 'wp-scheduled-posts') . '</a>');

        // go pro
        if (!$this->pro_enabled()) {
            $links[] = sprintf('<a href="https://wpdeveloper.com/in/wp-scheduled-posts-pro" target="_blank" style="color: #39b54a; font-weight: bold;">' . __('Go Pro', 'wp-scheduled-posts') . '</a>');
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
            $links[] = sprintf('<a href="https://wpdeveloper.com/docs/wp-scheduled-posts/?utm_medium=admin&utm_source=wp.org&utm_term=wpsp" target="_blank">' . __('Docs & FAQs', 'wp-scheduled-posts') . '</a>');

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
                    'link' => 'https://wpdeveloper.com/go/review-wpsp',
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
                    'link' => 'https://wpdeveloper.com/support',
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
        $notice->classes('upsale', 'notice is-dismissible ');
        $notice->message('upsale', '<p>' . __('Enjoying <strong>WP Scheduled Posts</strong>? Why not check our <strong><a href="https://wpdeveloper.com/in/wp-scheduled-posts-pro" target="_blank">Pro version</a></strong> which will enable auto schedule, multi social account share and many more features! [<strong><a href="https://wpdeveloper.com/plugins/wp-scheduled-posts/" target="_blank">Learn More</a></strong>]', 'wp-scheduled-posts') . '</p>');
        $notice->thumbnail('upsale', plugins_url('admin/assets/images/wpsp-logo.svg', WPSP_PLUGIN_BASENAME));

        $notice->upsale_args = array(
            'slug'      => 'wp-scheduled-posts-pro',
            'page_slug' => 'wp-scheduled-posts-pro',
            'file'      => 'wp-scheduled-posts-pro.php',
            'btn_text'  => __('Install Pro', 'wp-scheduled-posts'),
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
        // main notice init
        $notice->init();
    }
}
