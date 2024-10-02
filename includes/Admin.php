<?php

namespace WPSP;

use Exception;
use PriyoMukul\WPNotice\Notices;
use PriyoMukul\WPNotice\Utils\CacheBank;
use PriyoMukul\WPNotice\Utils\NoticeRemover;
use WPSP\Social\SocialProfile;

class Admin
{
    /**
     * @var bool
     */
    private $pro_enabled;

    private $insights = null;

    private $settings;

    /**
     * @var CacheBank
     */
    private static $cache_bank;

    public function __construct()
    {
        $this->load_plugin_menu_pages();
        $this->pro_enabled();
        // Core
        add_filter('plugin_action_links_' . WPSP_PLUGIN_BASENAME, array($this, 'insert_plugin_links'));
        add_filter('plugin_row_meta', array($this, 'insert_plugin_row_meta'), 10, 2);
        $this->usage_tracker();
        $this->load_dashboard_widgets();
        $this->load_settings();
        $this->load_elementor_panel_icon();
        if ( ! $this->pro_enabled ) {
            add_action( 'wpsp_el_modal_pro_fields', [ $this, 'wpsp_el_modal_pro_fields' ] );
        }

        add_action( 'wp_ajax_wpsp_el_editor_form', [ $this, 'wpsp_el_tab_action' ] );
        add_action('wpsp_el_modal_social_share_profile', [ $this, 'wpsp_el_modal_social_share_profile' ] );

        self::$cache_bank = CacheBank::get_instance();
        try {
            $this->admin_notice();
        } catch ( Exception $e ) {
            unset( $e );
        }

        // Remove OLD notice from 1.0.0 (if other WPDeveloper plugin has notice)
        NoticeRemover::get_instance( '1.0.0' );
    }

    public function load_plugin_menu_pages()
    {
        new Admin\Menu();
    }
    public function load_dashboard_widgets()
    {
        new Admin\Widgets\ScheduledPostList();
    }

    public function load_elementor_panel_icon() {
        $show_on_elementor_editor = Helper::get_settings('show_on_elementor_editor');
        if ( $show_on_elementor_editor ) {
            add_action( 'elementor/editor/footer', [ $this, 'schedulepress_el_tab' ], 100 );
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
        $links[] = sprintf('<a href="admin.php?page=' . WPSP_SETTINGS_SLUG . '">' . __('Settings', 'wp-scheduled-posts') . '</a>');

        // go pro
        if (!$this->pro_enabled()) {
            $links[] = sprintf('<a href="https://wpdeveloper.com/in/schedulepress-pro" target="_blank" style="color: #39b54a; font-weight: bold;">' . __('Go Pro', 'wp-scheduled-posts') . '</a>');
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
            $links[] = sprintf('<a href="https://wpdeveloper.com/docs/schedulepress" target="_blank">' . __('Docs & FAQs', 'wp-scheduled-posts') . '</a>');

            // video tutorials
            // $links[] = sprintf('<a href="https://www.youtube.com/channel/UCOjzLEdsnpnFVkm1JKFurPA?utm_medium=admin&utm_source=wp.org&utm_term=ea" target="_blank">' . __('Video Tutorials') . '</a>');
        }

        return $links;
    }

    public function admin_notice()
    {
        $_asset_url = plugins_url('assets/', WPSP_PLUGIN_BASENAME);

        $notices = new Notices([
            'id'             => 'schedulepress',
            // 'dev_mode'       => true,
            'storage_key'    => 'notices',
            'lifetime'       => 3,
            'stylesheet_url' => WPSP_ASSETS_URI . 'css/wpscp-admin-notice.css',
            'styles' => WPSP_ASSETS_URI . 'css/wpscp-admin-notice.css',
            'priority'       => 8,
        ]);


        /**
         * This is review message and thumbnail.
         */
        $_review_notice = [
            'thumbnail' => $_asset_url . 'images/wpsp-logo.svg',
            'html' => '<p>' . __('We hope you\'re enjoying SchedulePress! Could you please do us a BIG favor and give it a 5-star rating on WordPress to help us spread the word and boost our motivation?', 'wp-scheduled-posts') . '</p>',
            'links' => [
                'later' => array(
                    'link' => 'https://wpdeveloper.com/go/review-wpsp',
                    'target' => '_blank',
                    'label' => __('Ok, you deserve it!', 'wp-scheduled-posts'),
                    'icon_class' => 'dashicons dashicons-external',
                ),
                'allready' => array(
                    'label' => __('I already did', 'wp-scheduled-posts'),
                    'icon_class' => 'dashicons dashicons-smiley',
                    'attributes' => [
                        'data-dismiss' => true
                    ],
                ),
                'maybe_later' => array(
                    'label' => __('Maybe Later', 'wp-scheduled-posts'),
                    'icon_class' => 'dashicons dashicons-calendar-alt',
                    'attributes' => [
                        'data-later' => true
                    ],
                ),
                'support' => array(
                    'link' => 'https://wpdeveloper.com/support',
                    'label' => __('I need help', 'wp-scheduled-posts'),
                    'icon_class' => 'dashicons dashicons-sos',
                ),
                'never_show_again' => array(
                    'label' => __('Never show again', 'wp-scheduled-posts'),
                    'icon_class' => 'dashicons dashicons-dismiss',
                    'attributes' => [
                        'data-dismiss' => true
                    ],
                ),
            ],
        ];

        $notices->add(
            'review',
            $_review_notice,
            [
                'start'       => $notices->strtotime( '+15 day' ),
                'recurrence'  => 30,
                'dismissible' => true,
                'refresh'     => WPSP_VERSION,
            ]
        );

        $_upsale_notice = [
            'thumbnail' => $_asset_url . 'images/wpsp-logo.svg',
            'html' => '<p>' . __('Enjoying <strong>SchedulePress</strong>? Why not check our <strong><a href="https://wpdeveloper.com/in/wp-scheduled-posts-pro" target="_blank">Pro version</a></strong> which will enable auto schedule, multi social account share and many more features! [<strong><a href="https://wpdeveloper.com/plugins/wp-scheduled-posts/" target="_blank">Learn More</a></strong>]', 'wp-scheduled-posts') . '</p>',
        ];

        $notices->add(
            'upsale',
            $_upsale_notice,
            [
                'start'       => $notices->strtotime( '+20 day' ),
                'recurrence'  => false,
                'dismissible' => true,
                'refresh'     => WPSP_VERSION,
                'display_if'  => ! is_array( $notices->is_installed( 'wp-scheduled-posts-pro/wp-scheduled-posts-pro.php' ) )
            ]
        );

        $notices->add(
            'optin',
            [ $this->insights, 'optin_notice' ],
            [
                'start'       => $notices->strtotime( '+30 days' ),
                'recurrence'  => 30,
                'dismissible' => true,
                'refresh'     => WPSP_VERSION,
                'do_action'   => 'wpdeveloper_notice_clicked_for_wp-scheduled-posts',
                'display_if'  => ! is_array( $notices->is_installed( 'wp-scheduled-posts-pro/wp-scheduled-posts-pro.php' ) )
            ]
        );

        $notice_text            = '<p style="margin-top: 0; margin-bottom: 10px;">Black Friday Sale: Get up to 40% off & add <strong>more power to your content scheduling</strong> with premium features 🗓️</p>
        <a class="button button-primary" href="https://wpdeveloper.com/upgrade/schedulepress-bfcm" target="_blank">Upgrade to pro</a> <button data-dismiss="true" class="dismiss-btn button button-link">I don’t want to save money</button>';

        $_black_friday = [
            'thumbnail' => $_asset_url . 'images/wpsp-logo-full.svg',
            'html'      => $notice_text,
        ];

        $notices->add(
            'black_friday_23',
            $_black_friday,
            [
                'start'       => $notices->time(),
                'recurrence'  => false,
                'dismissible' => true,
                'refresh'     => WPSP_VERSION,
                "expire"      => strtotime( '11:59:59pm 2nd December, 2023' ),
                'display_if'  => ! is_array( $notices->is_installed( 'wp-scheduled-posts-pro/wp-scheduled-posts-pro.php' ) )
            ]
        );


        self::$cache_bank->create_account( $notices );
        self::$cache_bank->calculate_deposits( $notices );
    }
    public function usage_tracker()
    {
        $this->insights = new Admin\WPDev\PluginUsageTracker(
            WPSP_PLUGIN_FILE,
            'http://app.wpdeveloper.com',
            array(),
            true,
            true,
            1
        );
    }

    /**
     * Undocumented function
     *
     * @return Admin\Settings
     */
    public function load_settings()
    {
        if (!$this->settings) {
            $this->settings = new Admin\Settings(WPSP_SETTINGS_SLUG, WPSP_SETTINGS_NAME);
        }
        return $this->settings;
    }

    public function schedulepress_el_tab () {
        $allow_post_types = \WPSP\Helper::get_all_allowed_post_type();
        ?>
        <div class="dialog-widget dialog-lightbox-widget dialog-type-buttons dialog-type-lightbox elementor-templates-modal"
            id="schedulepress-elementor-modal" style="display: none;">
            <div class="dialog-widget-content dialog-lightbox-widget-content" style="top: 50%;left: 50%;transform: translate(-50%, -50%);">
                <div class="dialog-header dialog-lightbox-header">
                    <div class="elementor-templates-modal__header">
                        <div class="elementor-templates-modal__header__logo-area">
                            <div class="elementor-templates-modal__header__logo">
                                <img src="<?php echo plugins_url( 'assets/images/wpsp-el-editor-modal-logo.png', WPSP_PLUGIN_BASENAME ); ?>" alt="<?php esc_html_e( 'WPSP Logo', 'wp-scheduled-posts' ); ?>">
                            </div>
                        </div>
                        <div class="elementor-templates-modal__header__menu-area"></div>
                        <div class="elementor-templates-modal__header__items-area">
                            <div class="elementor-templates-modal__header__close elementor-templates-modal__header__close--normal elementor-templates-modal__header__item">
                                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px"
                                     viewBox="0 0 200 200" style="enable-background:new 0 0 200 200; width: 11px;" xml:space="preserve">
                                    <g>
                                        <path style="fill: #303042;" d="M10.3,199.8c-2.6,0-5.3-1-7.3-3c-4-4-4-10.5,0-14.6L182.4,2.8c4-4,10.5-4,14.6,0c4,4,4,10.5,0,14.6L17.6,196.8
        C15.6,198.8,12.9,199.8,10.3,199.8z"/>
                                        <path style="fill: #303042;" d="M189.9,199.8c-2.6,0-5.2-1-7.2-3L6,20.1c-4-4-4-10.4,0-14.3s10.4-4,14.3,0L197,182.5c4,4,4,10.4,0,14.3
        C195,198.8,192.5,199.8,189.9,199.8z"/>
                                    </g>
                                </svg>

                                <span class="elementor-screen-only"><?php esc_html_e( 'Close', 'wp-scheduled-posts' ); ?></span>
                            </div>
                            <div id="elementor-template-library-header-tools"></div>
                        </div>
                    </div>
                </div>
                <div class="dialog-message dialog-lightbox-message">
                    <?php if( !in_array( get_post_type( get_the_ID() ), $allow_post_types) ) : ?>
                    <div class="dialog-lightbox-warning">
                        <div class="post-type-message">
                            <span><?php echo sprintf( __('Sorry, you can\'t schedule this <strong>%s</strong>. Please allow this post type from <a href="%s" target="_blank">SchedulePress settings</a>.', 'wp-scheduled-posts'), get_post_type( get_the_ID() ),  admin_url('admin.php?page=schedulepress') ) ?></span>
                        </div>
                    </div>
                    <?php endif ?>
                    <div class="dialog-content dialog-lightbox-content <?php echo !in_array( get_post_type( get_the_ID() ), $allow_post_types ) ? 'add-overlay' : '' ?>">
                        <form action="<?php echo admin_url( 'admin-ajax.php' ); ?>" method="post">
                            <div class="wpsp-el-fields-prev wpsp-el-fields active">
                                <?php
                                wp_nonce_field( 'wpsp-el-editor', 'wpsp-el-editor' );
                                $post_id     = get_the_ID();
                                $post        = get_post( $post_id );
                                $status      = get_post_status( $post_id );
                                $is_future   = $status === 'future';
                                $post_date   = apply_filters('wpsp_el_modal_post_date', $post->post_date, $post);
                                ?>
                                <input type="hidden" name="action" value="wpsp_el_editor_form">
                                <input type="hidden" name="id" value="<?php echo $post_id; ?>">

                                <label>
                                    <span><?php esc_html_e( 'Publish On', 'wp-scheduled-posts' ); ?></span>
                                    <input id="wpsp-schedule-datetime" type="text" name="date" value="<?php echo esc_attr( $post_date ) ?>" readonly>
                                </label>
                                <?php do_action( 'wpsp_el_modal_pro_fields', $post_id ); ?>
                            </div>
                            <div class="wpsp-el-fields-next wpsp-el-fields">
                                <?php do_action('wpsp_el_modal_social_share_profile') ?>
                            </div>
                        </form>
                        <div class="wpsp-el-result" style="display: none;"></div>
                    </div>
                    <div class="dialog-loading dialog-lightbox-loading"></div>
                </div>
                <div class="dialog-buttons-wrapper dialog-lightbox-buttons-wrapper wpsp-elementor-modal-wrapper">
                    <div id="wpsp-el-form-prev-next-button">
                        <button class="elementor-button wpsp-el-form-next" <?php echo !in_array( get_post_type( get_the_ID() ), $allow_post_types ) ? 'disabled' : '' ?>>
                            <span><?php echo esc_html__( 'Next','wp-scheduled-posts' ) ?></span>
                        </button>
                        <button class="elementor-button wpsp-el-form-prev">
                            <span><?php echo esc_html__( 'Prev','wp-scheduled-posts' ) ?></span>
                        </button>
                    </div>
                    <div id="wpsp-el-form-update-button" class="wpsp_form_next_button_wrapper">
                        <button class="elementor-button wpsp-immediately-publish" style="<?php if ( ! $is_future ) { echo 'display: none;'; } ?>">
                            <span class="elementor-state-icon">
                                <i class="eicon-loading eicon-animation-spin" aria-hidden="true"></i>
                            </span>
                            <?php esc_html_e( 'Publish Post Immediately', 'wp-scheduled-posts' ); ?>
                        </button>
                        <button class="wpsp_el_share_now"><?php echo esc_html__('Share Now','wp-scheduled-posts') ?></button>
                        <button class="elementor-button wpsp-el-form-submit"
                                data-label-schedule="<?php esc_html_e( 'Schedule', 'wp-scheduled-posts' ); ?>"
                                data-label-publish="<?php esc_html_e( 'Update', 'wp-scheduled-posts' ); ?>"
                                data-label-draft="<?php esc_html_e( 'Publish', 'wp-scheduled-posts' ); ?>"
                                data-label-update="<?php esc_html_e( 'Update', 'wp-scheduled-posts' ); ?>">
                            <span class="elementor-state-icon">
                                <i class="eicon-loading eicon-animation-spin" aria-hidden="true"></i>
                            </span>
                            <span>
                            <?php
                            if ( $is_future ) {
                                esc_html_e( 'Schedule', 'wp-scheduled-posts' );
                            } elseif( $status == 'publish') {
                                esc_html_e( 'Update', 'wp-scheduled-posts' );
                            } else {
                                esc_html_e( 'Publish', 'wp-scheduled-posts' );
                            }
                            ?>
                            </span>
                        </button>
                        <?php do_action("wpsp_el_after_publish_button", $post);?>
                    </div>
                </div>
                <div class="wpsp-el-modal-date-picker"></div>
            </div>
        </div>

        <div id="elementor-panel-footer-sub-menu-item-wpsp" class="elementor-panel-footer-sub-menu-item tooltip-target" data-tooltip="<?php esc_attr_e( 'SchedulePress', 'wp-scheduled-posts' ); ?>">
            <i class="elementor-icon eicon-folder" aria-hidden="true"></i>
            <span class="elementor-title"><?php esc_html_e( 'SchedulePress', 'wp-scheduled-posts' ); ?></span>
        </div>

        <div id="elementor-panel-footer-wpsp-modal" class="elementor-panel-footer-tool tooltip-target" data-tooltip="<?php esc_attr_e( 'SchedulePress', 'wp-scheduled-posts' ); ?>">
            <span id="elementor-panel-footer-wpsp-modal-label">
                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px"
                     viewBox="0 0 500 500" style="enable-background:new 0 0 500 500;display:block;width:13px;margin:0 auto;" xml:space="preserve">
                <style type="text/css">
                    .st0{fill:#A4AFB7;}
                    #elementor-panel-footer-wpsp-modal:hover .st0{fill:#d5dadf;}
                </style>
                <g>
                    <g>
                        <path class="st0" d="M212.3,462.4C95,462.4-0.4,366.9-0.4,249.7S95,37,212.3,37c37,0,73.2,9.6,105.1,27.9
                            c9.8,5.7,13.2,18.1,7.5,27.7c-5.7,9.8-18.1,13.2-27.7,7.5c-25.6-14.7-55.1-22.5-84.9-22.5c-94.7,0-171.8,77.1-171.8,171.8
                            s77.1,171.8,171.8,171.8c48.1,0,92.6-19.4,125.5-54.3c7.8-8.3,20.7-8.5,28.7-1c8.3,7.8,8.5,20.7,1,28.7
                            C327.4,437.8,271,462.4,212.3,462.4z"/>
                    </g>
                    <path class="st0" d="M186.1,208.3l-43.2-39.3c-8.3-7.5-21.2-7-28.7,1.3c-7.5,8.3-7,21.2,1.3,28.7l46.8,42.4
                        C165.9,227.7,174.5,215.8,186.1,208.3z"/>
                    <path class="st0" d="M445.4,81.7c-7-8.8-19.9-10.4-28.7-3.4L250,210.1c11.1,8.3,19.1,20.4,21.7,34.7L442,110.2
                        C451.1,103.2,452.4,90.5,445.4,81.7z"/>
                    <path class="st0" d="M234.3,222.8c-5.2-2.8-11.1-4.4-17.3-4.4c-5.7,0-10.9,1.3-15.5,3.4c-12.7,6-21.2,18.6-21.2,33.4
                        c0,0.8,0,1.6,0,2.3c1.3,19.1,17.1,34.4,36.7,34.4c18.9,0,34.4-14.2,36.5-32.6c0.3-1.3,0.3-2.8,0.3-4.4
                        C253.7,241.1,245.9,229,234.3,222.8z"/>
                    <path class="st0" d="M493.8,202.6h-51.2c-3.4,0-6.2,2.8-6.2,6.2v45.5c0,3.4,2.8,6.2,6.2,6.2h51.2c3.4,0,6.2-2.8,6.2-6.2v-45.5
                        C500,205.4,497.2,202.6,493.8,202.6z"/>
                    <g>
                        <path class="st0" d="M410,202.6h-51.2c-3.4,0-6.2,2.8-6.2,6.2v45.5c0,3.4,2.8,6.2,6.2,6.2H410c3.4,0,6.2-2.8,6.2-6.2v-45.5
                            C416.4,205.4,413.6,202.6,410,202.6z"/>
                        <path class="st0" d="M410,277.6h-51.2c-3.4,0-6.2,2.8-6.2,6.2v45.5c0,3.4,2.8,6.2,6.2,6.2H410c3.4,0,6.2-2.8,6.2-6.2v-45.5
                            C416.4,280.2,413.6,277.6,410,277.6z"/>
                        <path class="st0" d="M493.8,277.6h-51.2c-3.4,0-6.2,2.8-6.2,6.2v45.5c0,3.4,2.8,6.2,6.2,6.2h51.2c3.4,0,6.2-2.8,6.2-6.2v-45.5
                            C500,280.2,497.2,277.6,493.8,277.6z"/>
                    </g>
                </g>
                </svg>
                <span class="elementor-screen-only"><?php echo __( 'SchedulePress', 'wp-scheduled-posts' ); ?></span>
            </span>
        </div>
<?php
    }

    public function wpsp_el_modal_pro_fields( $post_id ) { ?>
        <div class="wpsp-pro-fields">
            <label title="<?php esc_html_e( 'Pro Feature', 'wp-scheduled-posts' ); ?>">
                <span><?php esc_html_e( 'Unpublish On', 'wp-scheduled-posts' ); ?>
                    <a href="https://wpdeveloper.com/docs/advanced-schedule-update-published-posts/" class="advance-schedule-info" target="_blank"><span class="dashicons dashicons-info"></span></a>
                    <span>
                        <?php esc_html_e( 'PRO', 'wp-scheduled-posts' ); ?></span>
                    </span>
                <input type="text" placeholder="<?php echo esc_attr('Y/M/D H:M:S') ?>" disabled>
            </label>
            <label title="<?php esc_html_e( 'Pro Feature', 'wp-scheduled-posts' ); ?>">
                <span><?php esc_html_e( 'Republish On', 'wp-scheduled-posts' ); ?>
                <a href="https://wpdeveloper.com/docs/advanced-schedule-update-published-posts/" class="advance-schedule-info" target="_blank"><span class="dashicons dashicons-info"></span></a>
                <span><?php esc_html_e( 'PRO', 'wp-scheduled-posts' ); ?></span></span>
                <input type="text" placeholder="<?php echo esc_attr('Y/M/D H:M:S') ?>" disabled>
            </label>
            <label title="<?php esc_html_e( 'Pro Feature', 'wp-scheduled-posts' ); ?>">
                <span><?php esc_html_e( 'Advanced Schedule', 'wp-scheduled-posts' ); ?>
                <a href="https://wpdeveloper.com/docs/advanced-schedule-update-published-posts/" class="advance-schedule-info" target="_blank"><span class="dashicons dashicons-info"></span></a>
                <span><?php esc_html_e( 'PRO', 'wp-scheduled-posts' ); ?></span></span>
                <input type="text" placeholder="<?php echo esc_attr('Y/M/D H:M:S') ?>" disabled>
            </label>
        </div>
        <?php
    }
    
    function wpsp_filter_selected_profile_object($profile)
    {
       if ( is_array( $profile ) && isset($profile['name']) ) {
            if( !empty( $profile['default_board_name']['label'] ) ) {
                return $profile['default_board_name']['label'];
            }
           return $profile['name'];
        }elseif( is_object( $profile ) && isset( $profile->name ) ) {
            if( isset( $profile->default_board_name->label ) ) {
                return $profile->default_board_name->label;
            }
            return $profile->name;
        } 
        return;
    }

    public function wpsp_get_pinterest_sections( $profiles )
    {
        if( isset( $profiles['platform'] ) && $profiles['platform'] == 'pinterest' ) {
            if ( isset( $profiles['pinterest_custom_board_name'] ) && isset( $profiles['pinterest_custom_section_name'] ) ) {
                return [ $profiles['pinterest_custom_board_name'] => $profiles['pinterest_custom_section_name'] ];
             }
        }
    }

    public function wpsp_el_modal_social_share_profile() 
    {
        wp_nonce_field(basename(__FILE__), 'wpscp_pro_instant_social_share_nonce');
        // status=
        $twitterIntegation = \WPSP\Helper::get_settings('twitter_profile_status');
        $facebookIntegation = \WPSP\Helper::get_settings('facebook_profile_status');
        $linkedinIntegation = \WPSP\Helper::get_settings('linkedin_profile_status');
        $pinterestIntegation = \WPSP\Helper::get_settings('pinterest_profile_status');
        $instagramIntegation = \WPSP\Helper::get_settings('instagram_profile_status');
        $mediumIntegation = \WPSP\Helper::get_settings('medium_profile_status');

        // social media share type settings 
        $facebookShareType     = get_post_meta( get_the_ID(), '_facebook_share_type', true );
        $instagramShareType     = get_post_meta( get_the_ID(), '_instagram_share_type', true );
        $twitterShareType      = get_post_meta( get_the_ID(), '_twitter_share_type', true );
        $linkedinShareType     = get_post_meta( get_the_ID(), '_linkedin_share_type', true );
        $linkedinShareTypePage = get_post_meta( get_the_ID(), '_linkedin_share_type_page', true );
        $pinterestShareType    = get_post_meta( get_the_ID(), '_pinterest_share_type', true );
        $instagramShareType    = get_post_meta( get_the_ID(), '_instagram_share_type', true );
        $mediumShareType    = get_post_meta( get_the_ID(), '_medium_share_type', true );
        // get all selected social profile 
        $allSelectedSocialProfiles = get_post_meta( get_the_ID(), '_selected_social_profile', true );
        $filteredSelectedProfiles = array_map( [ $this, 'wpsp_filter_selected_profile_object' ], !empty( $allSelectedSocialProfiles ) ? $allSelectedSocialProfiles : [] );
        $getPinterestSections = array_map( [ $this, 'wpsp_get_pinterest_sections' ], !empty( $allSelectedSocialProfiles ) ? $allSelectedSocialProfiles : [] );
        $getPinterestSections = array_filter($getPinterestSections, function($item) {
            return !empty($item);
        });
        $getPinterestSections = array_reduce($getPinterestSections, function($carry, $item) {
            return $carry + $item; 
        }, []);

        $filteredSelectedProfiles = array_filter($filteredSelectedProfiles);

        // profile
        $facebookProfile = \WPSP\Helper::get_settings('facebook_profile_list');
        if( !class_exists('WPSP_PRO') && is_array( $facebookProfile ) ) {
            $facebookProfile = array_slice( $facebookProfile, 0, 1, true );
        }

        // profile
        $instagramProfile = \WPSP\Helper::get_settings('instagram_profile_list');
        if( !class_exists('WPSP_PRO') && is_array( $instagramProfile ) ) {
            $instagramProfile = array_slice( $instagramProfile, 0, 1, true );
        }
        $twitterProfile = \WPSP\Helper::get_settings('twitter_profile_list');
        if( !class_exists('WPSP_PRO') && is_array( $twitterProfile ) ) {
            $twitterProfile = array_slice( $twitterProfile, 0, 1, true );
        }
        $linkedinProfile = \WPSP\Helper::get_settings('linkedin_profile_list');
        if( !class_exists('WPSP_PRO') && is_array( $linkedinProfile ) ) {
            $linkedinProfile = array_filter($linkedinProfile, function($single_linkedin) {
                return $single_linkedin->type == 'person';
            });
            $linkedinProfile = array_slice( $linkedinProfile, 0, 1, true );
        }
        $pinterestProfile = \WPSP\Helper::get_settings('pinterest_profile_list');
        if( !class_exists('WPSP_PRO') && is_array( $pinterestProfile ) ) {
            $pinterestProfile = array_slice( $pinterestProfile, 0, 1, true );
        }
        $mediumProfile = \WPSP\Helper::get_settings('medium_profile_list');
        if( !class_exists('WPSP_PRO') && is_array( $mediumProfile ) ) {
            $mediumProfile = array_slice( $mediumProfile, 0, 1, true );
        }
        ?>
           <div class="el-social-share-platform">
                <h4><?php echo esc_html__( 'Choose Social Share Platform', 'wp-scheduled-posts' ) ?></h4>
                <input type="hidden" name="postid" id="wpscppropostid" value="<?php print get_the_ID(); ?>">
                <div id="el-social-checkbox-wrapper">
                    <div class="wpsp-el-accordion">
                        <div class="wpsp-el-accordion-item wpsp-el-accordion-item-facebook">
                            <div class="wpsp-el-accordion-header">
                                <img src="<?php echo esc_url( WPSP_ASSETS_URI . '/images/facebook.svg' ) ?>" alt=""><span><?php echo esc_html('Facebook') ?></span>
                            </div>
                            <div class="wpsp-el-accordion-content">
                                <?php if( !empty( $facebookIntegation ) && !empty( $facebookProfile ) ) : ?>
                                    <div class="wpsp-el-container">
                                        <label><input type="radio" data-platform="facebook" name="wpsp-el-content-facebook" value="wpsp-el-social-facebook-default" <?php echo ( ( !empty( $facebookShareType ) && $facebookShareType == 'default' ) || empty( $facebookShareType ) ) ? 'checked' : ''  ?>><?php echo esc_html__('Default','wp-scheduled-posts') ?></label>
                                        <label><input type="radio" data-platform="facebook" name="wpsp-el-content-facebook" value="wpsp-el-social-facebook-custom" <?php echo !empty( $facebookShareType ) && $facebookShareType == 'custom' ? 'checked' : ''  ?>><?php echo esc_html__('Custom','wp-scheduled-posts') ?></label>
                                    </div>
                                    <div class="wpsp-el-content wpsp-el-content-facebook" data-value="wpsp-el-social-facebook-custom" style="<?php echo !empty( $facebookShareType ) && $facebookShareType == 'custom' ? 'display: block;' : 'display: none;' ?>">
                                        <?php if( count( $facebookProfile ) > 0 ) : ?>
                                            <?php foreach( $facebookProfile as $facebook ) : ?>
                                                <div class="facebook-profile social-profile">
                                                    <input type="checkbox" value="<?php echo !empty( $facebook->name ) ? $facebook->name : '' ?>" name="wpsp_el_social_facebook[]" <?php echo in_array( $facebook->name, $filteredSelectedProfiles ) ? 'checked' : '' ?>>
                                                    <h3><?php echo !empty( $facebook->name ) ? $facebook->name : '' ?> ( <?php echo $facebook->type ? $facebook->type : '' ?> ) </h3>
                                                </div>
                                            <?php endforeach ?>
                                        <?php endif ?>
                                    </div>
                                <?php else : ?>
                                    <div class="wpsp-el-empty-profile-message">
                                        <?php echo sprintf( __( 'You may forget to add or enable profile/page from <a href="%s">SchedulePress settings</a>. ', 'wp-scheduled-posts' ), admin_url('admin.php?page=schedulepress&tab=social-profile') ) ?>
                                    </div>
                                <?php endif ?>
                            </div>
                        </div>
                        <div class="wpsp-el-accordion-item wpsp-el-accordion-item-twitter">
                            <div class="wpsp-el-accordion-header">
                                <img src="<?php echo esc_url( WPSP_ASSETS_URI . '/images/twitter.svg' ) ?>" alt=""><span><?php echo esc_html('Twitter') ?></span>
                            </div>
                            <div class="wpsp-el-accordion-content">
                                <?php if( !empty( $twitterIntegation ) && !empty( $twitterProfile ) ) : ?>
                                    <div class="wpsp-el-container">
                                        <label><input type="radio" data-platform="twitter" name="wpsp-el-content-twitter" value="wpsp-el-social-twitter-default" <?php echo ( ( !empty( $twitterShareType ) && $twitterShareType == 'default' ) || empty( $twitterShareType ) ) ? 'checked' : ''  ?>><?php echo esc_html__('Default','wp-scheduled-posts') ?></label>
                                        <label><input type="radio" data-platform="twitter" name="wpsp-el-content-twitter" value="wpsp-el-social-twitter-custom" <?php echo !empty( $twitterShareType ) && $twitterShareType == 'custom' ? 'checked' : ''  ?>><?php echo esc_html__('Custom','wp-scheduled-posts') ?></label>
                                    </div>
                                    <div class="wpsp-el-content wpsp-el-content-twitter" data-value="wpsp-el-social-twitter-custom" style="<?php echo !empty( $twitterShareType ) && $twitterShareType == 'custom' ? 'display: block;' : 'display: none;' ?>">
                                        <?php if( count( $twitterProfile ) > 0 ) : ?>
                                            <?php foreach( $twitterProfile as $twitter ) : ?>
                                                <div class="twitter-profile social-profile">
                                                    <input type="checkbox" value="<?php echo !empty( $twitter->name ) ? $twitter->name : '' ?>" name="wpsp_el_social_twitter[]" <?php echo  in_array( $twitter->name, $filteredSelectedProfiles ) ? 'checked' : '' ?>><h3><?php echo $twitter->name ? $twitter->name : '' ?> </h3>
                                                </div>
                                            <?php endforeach ?>
                                        <?php endif ?>
                                    </div>
                                <?php else : ?>
                                    <div class="wpsp-el-empty-profile-message">
                                        <?php echo sprintf( __( 'You may forget to add or enable profile/page from <a href="%s">SchedulePress settings</a>. ', 'wp-scheduled-posts' ), admin_url('admin.php?page=schedulepress&tab=social-profile') ) ?>
                                    </div>
                                <?php endif ?>
                            </div>
                        </div>
                        <div class="wpsp-el-accordion-item wpsp-el-accordion-item-linkedin">
                            <div class="wpsp-el-accordion-header">
                                <img src="<?php echo esc_url( WPSP_ASSETS_URI . '/images/linkedin.svg' ) ?>" alt=""><span><?php echo esc_html('LinkedIn') ?></span>
                            </div>
                            <div class="wpsp-el-accordion-content">
                                <div class="wpsp-el-custom-linkedin-tab">
                                    <div class="wpsp-el-container wpsp-el-social-linkedin-tab-wrapper wpsp-pro-fields">
                                        <label for="wpsp-el-social-linkedin-profile-tab" class="active">
                                            <input type="radio" data-platform="linkedin-tab" id="wpsp-el-social-linkedin-profile-tab" name="wpsp-el-content-linkedin-tab" value="wpsp-el-social-linkedin-profile" checked><?php echo esc_html__('Profile','wp-scheduled-posts') ?>
                                        </label>
                                        <label for="wpsp-el-social-linkedin-page-tab" class="<?php echo !class_exists('WPSP_PRO') ? 'disabled' : '' ?>">
                                            <input type="radio" data-platform="linkedin-tab" id="wpsp-el-social-linkedin-page-tab" name="wpsp-el-content-linkedin-tab" value="wpsp-el-social-linkedin-page" <?php echo !class_exists('WPSP_PRO') ? 'disabled' : '' ?>><?php echo esc_html__('Page','wp-scheduled-posts') ?>
                                            <?php if( !class_exists('WPSP_PRO') ) : ?>
                                                <span><span><?php echo esc_html('PRO') ?></span></span>
                                            <?php endif ?>
                                        </label>
                                    </div>
                                    <?php if( !empty( $linkedinIntegation ) && !empty( $linkedinIntegation ) ) : ?>
                                        <div class="wpsp-el-content wpsp-el-content-linkedin wpsp-el-social-linkedin-profile" data-value="wpsp-el-social-linkedin-profile" style="display: block;">
                                            <?php if( count( $linkedinProfile ) > 0 ) : ?>
                                                <?php 
                                                    $count = 0;
                                                    foreach ($linkedinProfile as $linkedin) {
                                                        if( $linkedin->type == 'person' ) {
                                                            $count++;
                                                        }
                                                    }
                                                ?>
                                                <?php if( $count != 0 ) : ?>
                                                <div class="wpsp-el-container">
                                                    <label><input type="radio" data-platform="linkedin-profile" name="wpsp-el-content-linkedin-profile" value="wpsp-el-social-linkedin-profile-default" <?php echo ( ( !empty( $linkedinShareType ) && $linkedinShareType == 'default' ) || empty( $linkedinShareType ) ) ? 'checked' : ''  ?>><?php echo esc_html__('Default','wp-scheduled-posts') ?></label>
                                                    <label><input type="radio" data-platform="linkedin-profile" name="wpsp-el-content-linkedin-profile" value="wpsp-el-social-linkedin-profile-custom" <?php echo !empty( $linkedinShareType ) && $linkedinShareType == 'custom' ? 'checked' : ''  ?>><?php echo esc_html__('Custom','wp-scheduled-posts') ?></label>
                                                </div>
                                                <div class="wpsp-el-content wpsp-el-content-linkedin-profile" data-value="wpsp-el-social-linkedin-profile-custom" style="<?php echo !empty( $linkedinShareType ) && $linkedinShareType == 'custom' ? 'display: block;' : 'display: none;' ?>">
                                                    <?php foreach( $linkedinProfile as $linkedin ) : ?>
                                                        <?php if ($linkedin->type == 'person') : ?>
                                                            <div class="linkedin-profile social-profile">
                                                                <input type="checkbox" value="<?php echo !empty( $linkedin->name ) ? $linkedin->name : '' ?>" name="wpsp_el_social_linkedin[]" <?php echo  in_array( $linkedin->name, $filteredSelectedProfiles ) ? 'checked' : '' ?>><h3><?php echo isset( $linkedin->name ) ? $linkedin->name : '' ?> <?php echo esc_html__('(Profile)', 'wp-scheduled-posts' )  ?> </h3>
                                                            </div>
                                                        <?php endif; ?>
                                                    <?php endforeach ?>
                                                </div>
                                                <?php else : ?>
                                                    <div class="wpsp-el-empty-profile-message">
                                                        <?php echo sprintf( __( 'You may forget to add or enable profile/page from <a href="%s">SchedulePress settings</a>. ', 'wp-scheduled-posts' ), admin_url('admin.php?page=schedulepress&tab=social-profile') ) ?>
                                                    </div>
                                                <?php endif ?>
                                            <?php endif ?>
                                        </div>
                                        <div class="wpsp-el-content wpsp-el-content-linkedin wpsp-el-social-linkedin-page" data-value="wpsp-el-social-linkedin-page" style="display: none;">
                                            <?php if( count( $linkedinProfile ) > 0 && class_exists('WPSP_PRO') ) : ?>
                                                <?php 
                                                    $count = 0;
                                                    foreach ($linkedinProfile as $linkedin) {
                                                        if( $linkedin->type == 'organization' ) {
                                                            $count++;
                                                        }
                                                    }
                                                ?>
                                                <?php if( $count != 0 ) : ?>
                                                <div class="wpsp-el-container">
                                                    <label><input type="radio" data-platform="linkedin-page" name="wpsp-el-content-linkedin-page" value="wpsp-el-social-linkedin-page-default" <?php echo ( ( !empty( $linkedinShareTypePage ) && $linkedinShareTypePage == 'default' ) || empty( $linkedinShareTypePage ) ) ? 'checked' : ''  ?>><?php echo esc_html__('Default','wp-scheduled-posts') ?></label>
                                                    <label><input type="radio" data-platform="linkedin-page" name="wpsp-el-content-linkedin-page" value="wpsp-el-social-linkedin-page-custom" <?php echo !empty( $linkedinShareTypePage ) && $linkedinShareTypePage == 'custom' ? 'checked' : ''  ?>><?php echo esc_html__('Custom','wp-scheduled-posts') ?></label>
                                                </div>
                                                <div class="wpsp-el-content wpsp-el-content-linkedin-page" data-value="wpsp-el-social-linkedin-page-custom" style="<?php echo !empty( $linkedinShareTypePage ) && $linkedinShareTypePage == 'custom' ? 'display: block;' : 'display: none;' ?>">
                                                    <?php foreach( $linkedinProfile as $linkedin ) : ?>
                                                        <?php if ($linkedin->type == 'organization') : ?>
                                                            <div class="linkedin-profile social-profile">
                                                                <input type="checkbox" value="<?php echo !empty( $linkedin->name ) ? $linkedin->name : '' ?>" name="wpsp_el_social_linkedin[]" <?php echo  in_array( $linkedin->name, $filteredSelectedProfiles ) ? 'checked' : '' ?>><h3><?php echo isset( $linkedin->name ) ? $linkedin->name : '' ?> <?php echo esc_html__('(Page)', 'wp-scheduled-posts') ?> </h3>
                                                            </div>
                                                        <?php endif; ?>
                                                    <?php endforeach ?>
                                                </div>
                                                <?php else :  ?>
                                                    <div class="wpsp-el-empty-profile-message">
                                                        <?php echo sprintf( __( 'You may forget to add or enable profile/page from <a href="%s">SchedulePress settings</a>. ', 'wp-scheduled-posts' ), admin_url('admin.php?page=schedulepress&tab=social-profile') ) ?>
                                                    </div>
                                                <?php endif ?>
                                            <?php endif ?>
                                        </div>
                                    <?php else : ?>
                                        <div class="wpsp-el-empty-profile-message">
                                            <?php echo sprintf( __( 'You may forget to add or enable profile/page from <a href="%s">SchedulePress settings</a>. ', 'wp-scheduled-posts' ), admin_url('admin.php?page=schedulepress&tab=social-profile') ) ?>
                                        </div>
                                    <?php endif ?>
                                </div>
                            </div>
                        </div>
                        <div class="wpsp-el-accordion-item wpsp-el-accordion-item-pinterest">
                            <div class="wpsp-el-accordion-header">
                                <img src="<?php echo esc_url( WPSP_ASSETS_URI . '/images/pinterest.svg' ) ?>" alt=""><span><?php echo esc_html('Pinterest') ?></span>
                            </div>
                            <div class="wpsp-el-accordion-content">
                                <?php if( !empty( $pinterestIntegation ) && !empty( $pinterestIntegation ) ) : ?>
                                <div class="wpsp-el-container">
                                    <label><input type="radio" data-platform="pinterest" name="wpsp-el-content-pinterest" value="wpsp-el-social-pinterest-default" <?php echo ( ( !empty( $pinterestShareType ) && $pinterestShareType == 'default' ) || empty( $pinterestShareType ) ) ? 'checked' : ''  ?>><?php echo esc_html__('Default','wp-scheduled-posts') ?></label>
                                    <label><input type="radio" data-platform="pinterest" name="wpsp-el-content-pinterest" value="wpsp-el-social-pinterest-custom" <?php echo !empty( $pinterestShareType ) && $pinterestShareType == 'custom' ? 'checked' : ''  ?>><?php echo esc_html__('Custom','wp-scheduled-posts') ?></label>
                                </div>
                                <div class="wpsp-el-content wpsp-el-content-pinterest" data-value="wpsp-el-social-pinterest-custom" style="<?php echo !empty( $pinterestShareType ) && $pinterestShareType == 'custom' ? 'display: block;' : 'display: none;' ?>">
                                    <?php if( count( $pinterestProfile ) > 0 ) : ?>
                                        <?php foreach( $pinterestProfile as $key => $pinterest ) : ?>
                                            <?php
                                                if( empty( $pinterest->default_board_name->value ) )  {
                                                    continue;
                                                }
                                                $pinterest = $this->get_pinterest_from_meta( $pinterest );
                                            ?>
                                            <?php 
                                                $pinterest_section = new SocialProfile();
                                                $get_pinterest_sections = $pinterest_section->social_profile_fetch_pinterest_section( [ 'defaultBoard'  => $pinterest->default_board_name->value, 'profile' => $key, 'method_called'  => true ]  );
                                            ?>
                                            <div class="pinterest-profile social-profile">
                                                <input type="checkbox" value="<?php echo $pinterest->default_board_name->value ?>" name="wpsp_el_social_pinterest[]" <?php echo  in_array( $pinterest->default_board_name->label, $filteredSelectedProfiles ) ? 'checked' : '' ?>>
                                                <h3><?php echo !empty( $pinterest->default_board_name->label ) ? $pinterest->default_board_name->label : '' ?> </h3>
                                                <select name="wpsp_el_pinterest_board[]" id="wpsp_el_pinterest_section_<?php echo $pinterest->default_board_name->value ?>">
                                                    <option value=""><?php echo esc_html('No Section','wp-scheduled-posts') ?></option>
                                                    <?php if( !empty( $get_pinterest_sections ) ) : ?>
                                                        <?php foreach( $get_pinterest_sections as $section ) : ?>
                                                            <?php if( !empty( $getPinterestSections[ $pinterest->default_board_name->value ] ) ) : ?>
                                                                <option value="<?php echo !empty( $section['id'] ) ? $section['id']. '|'. $pinterest->default_board_name->value : '' ?>" <?php echo $getPinterestSections[$pinterest->default_board_name->value] == $section['id'] ? 'selected' : '' ?> ><?php echo !empty( $section['name'] ) ? $section['name'] : '' ?></option>
                                                            <?php endif ?>
                                                        <?php endforeach ?>
                                                    <?php endif ?>
                                                </select>
                                            </div>
                                        <?php endforeach ?>
                                    <?php endif ?>
                                </div>
                                <?php else : ?>
                                    <div class="wpsp-el-empty-profile-message">
                                        <?php echo sprintf( __( 'You may forget to add or enable profile/page from <a href="%s">SchedulePress settings</a>. ', 'wp-scheduled-posts' ), admin_url('admin.php?page=schedulepress&tab=social-profile') ) ?>
                                    </div>
                                <?php endif ?>
                            </div>
                        </div>
                        <div class="wpsp-el-accordion-item wpsp-el-accordion-item-instagram">
                            <div class="wpsp-el-accordion-header">
                                <img src="<?php echo esc_url( WPSP_ASSETS_URI . '/images/instagram.png' ) ?>" alt=""><span><?php echo esc_html('Instagram') ?></span>
                            </div>
                            <div class="wpsp-el-accordion-content">
                                <?php if( !empty( $instagramIntegation ) && !empty( $instagramProfile ) ) : ?>
                                    <div class="wpsp-el-container">
                                        <label><input type="radio" data-platform="instagram" name="wpsp-el-content-instagram" value="wpsp-el-social-instagram-default" <?php echo ( ( !empty( $instagramShareType ) && $instagramShareType == 'default' ) || empty( $instagramShareType ) ) ? 'checked' : ''  ?>><?php echo esc_html__('Default','wp-scheduled-posts') ?></label>
                                        <label><input type="radio" data-platform="instagram" name="wpsp-el-content-instagram" value="wpsp-el-social-instagram-custom" <?php echo !empty( $instagramShareType ) && $instagramShareType == 'custom' ? 'checked' : ''  ?>><?php echo esc_html__('Custom','wp-scheduled-posts') ?></label>
                                    </div>
                                    <div class="wpsp-el-content wpsp-el-content-instagram" data-value="wpsp-el-social-instagram-custom" style="<?php echo !empty( $instagramShareType ) && $instagramShareType == 'custom' ? 'display: block;' : 'display: none;' ?>">
                                        <?php if( count( $instagramProfile ) > 0 ) : ?>
                                            <?php foreach( $instagramProfile as $instagram ) : ?>
                                                <div class="instagram-profile social-profile">
                                                    <input type="checkbox" value="<?php echo !empty( $instagram->name ) ? $instagram->name : '' ?>" name="wpsp_el_social_instagram[]" <?php echo in_array( $instagram->name, $filteredSelectedProfiles ) ? 'checked' : '' ?>>
                                                    <h3><?php echo !empty( $instagram->name ) ? $instagram->name : '' ?> ( <?php echo $instagram->type ? $instagram->type : '' ?> ) </h3>
                                                </div>
                                            <?php endforeach ?>
                                        <?php endif ?>
                                    </div>
                                <?php else : ?>
                                    <div class="wpsp-el-empty-profile-message">
                                        <?php echo sprintf( __( 'You may forget to add or enable profile/page from <a href="%s">SchedulePress settings</a>. ', 'wp-scheduled-posts' ), admin_url('admin.php?page=schedulepress&tab=social-profile') ) ?>
                                    </div>
                                <?php endif ?>
                            </div>
                        </div>
                        <div class="wpsp-el-accordion-item wpsp-el-accordion-item-medium">
                            <div class="wpsp-el-accordion-header">
                                <img src="<?php echo esc_url( WPSP_ASSETS_URI . '/images/medium.svg' ) ?>" width="25" alt=""><span><?php echo esc_html('Medium') ?></span>
                            </div>
                            <div class="wpsp-el-accordion-content">
                                <?php if( !empty( $mediumIntegation ) && !empty( $mediumProfile ) ) : ?>
                                    <div class="wpsp-el-container">
                                        <label><input type="radio" data-platform="medium" name="wpsp-el-content-medium" value="wpsp-el-social-medium-default" <?php echo ( ( !empty( $mediumShareType ) && $mediumShareType == 'default' ) || empty( $mediumShareType ) ) ? 'checked' : ''  ?>><?php echo esc_html__('Default','wp-scheduled-posts') ?></label>
                                        <label><input type="radio" data-platform="medium" name="wpsp-el-content-medium" value="wpsp-el-social-medium-custom" <?php echo !empty( $mediumShareType ) && $mediumShareType == 'custom' ? 'checked' : ''  ?>><?php echo esc_html__('Custom','wp-scheduled-posts') ?></label>
                                    </div>
                                    <div class="wpsp-el-content wpsp-el-content-medium" data-value="wpsp-el-social-medium-custom" style="<?php echo !empty( $mediumShareType ) && $mediumShareType == 'custom' ? 'display: block;' : 'display: none;' ?>">
                                        <?php if( count( $mediumProfile ) > 0 ) : ?>
                                            <?php foreach( $mediumProfile as $medium ) : ?>
                                                <div class="medium-profile social-profile">
                                                    <input type="checkbox" value="<?php echo !empty( $medium->name ) ? $medium->name : '' ?>" name="wpsp_el_social_medium[]" <?php echo in_array( $medium->name, $filteredSelectedProfiles ) ? 'checked' : '' ?>>
                                                    <h3><?php echo !empty( $medium->name ) ? $medium->name : '' ?> ( <?php echo $medium->type ? $medium->type : '' ?> ) </h3>
                                                </div>
                                            <?php endforeach ?>
                                        <?php endif ?>
                                    </div>
                                <?php else : ?>
                                    <div class="wpsp-el-empty-profile-message">
                                        <?php echo sprintf( __( 'You may forget to add or enable profile/page from <a href="%s">SchedulePress settings</a>. ', 'wp-scheduled-posts' ), admin_url('admin.php?page=schedulepress&tab=social-profile') ) ?>
                                    </div>
                                <?php endif ?>
                            </div>
                        </div>
                    </div>
                </div>
           </div>
        <?php 
    }

    public function get_pinterest_from_meta($pinterest)
    {
        if( !empty( $pinterest ) ) {
            $get_selected_profiles = get_post_meta(get_the_ID(), '_selected_social_profile', true);
            if( !empty( $get_selected_profiles ) ) {
                $pinterestSelectedProfile = array_filter($get_selected_profiles, function ($profile) use ( $pinterest ) {
                    return isset( $profile->default_board_name->value ) && isset( $pinterest->default_board_name->value ) && $profile->default_board_name->value == $pinterest->default_board_name->value;
                });
                if( empty( $pinterestSelectedProfile ) ) {
                    return $pinterest;
                }else{
                    return reset( $pinterestSelectedProfile );
                }
            }
        }
        return $pinterest;
    }

    public function wpsp_format_profile_data( $selectedSocialProfiles ) {
        $platformKeyMapping = [
            'linkedin'  => ['type'],
            'instagram' => ['type'],
            'medium'    => ['type'],
            'twitter'   => [],
            'facebook'  => ['type'],
            'pinterest' => ['default_board_name', 'defaultSection'],
        ];
        foreach ($selectedSocialProfiles as $key => $item) {
            $platform = '';
            if (property_exists($item, 'urn')) {
                $platform = 'linkedin';
            } elseif (property_exists($item, 'oauth_token')) {
                $platform = 'twitter';
            } elseif (property_exists($item, 'type') && $item->type == 'profile' ) {
                $platform = 'instagram';
            } elseif (property_exists($item, 'type')) {
                $platform = 'facebook';
            } elseif (property_exists($item, 'access_token') && !empty($item->boards)) {
                $platform = 'pinterest';
            } elseif (property_exists($item, 'type') && $item->type == 'profile' && $item->id == $item->app_id ) {
                $platform = 'medium';
            }
            $formattedItem = [
                'id'            => ($platform === 'pinterest') ? $item->default_board_name->value : $item->id,
                'platform'      => $platform,
                'platformKey'   => 0,
                'name'          => ($platform === 'pinterest') ? $item->default_board_name->label : $item->name,
                'thumbnail_url' => $item->thumbnail_url,
                'share_type'    => 'default',
            ];
            foreach ($platformKeyMapping[$platform] as $key) {
                if ($platform === 'pinterest') {
                    $formattedItem['pinterest_custom_board_name']   = $item->default_board_name->value;
                    $formattedItem['pinterest_custom_section_name'] = $item->defaultSection->value;
                    unset( $formattedItem['default_board_name'], $formattedItem['defaultSection'] );
                } else {
                    $formattedItem[$key] = $item->$key;
                }
            }
            $formattedData[] = $formattedItem;
        }
        return $formattedData;
    }

    public function wpsp_el_tab_action() {
        if ( check_ajax_referer( 'wpsp-el-editor', 'wpsp-el-editor' ) ) {
            $offset = get_option( 'gmt_offset' );
            $offset = $offset == 0 ? 0 : ( 0 - $offset );

            $args   = wp_parse_args( $_POST, [
                'id'                               => 0,
                'date'                             => '',
                'republish_datetime'               => '',
                'unpublish_datetime'               => '',
                'post_status'                      => 'future',
                '_wpscppro_advance_schedule_date'  => '',
                'advanced'                         => null,
                'wpsp-el-content-facebook'         => '',
                'wpsp-el-content-twitter'          => '',
                'wpsp-el-content-linkedin-profile' => '',
                'wpsp-el-content-linkedin-page'    => '',
                'wpsp-el-content-pinterest'        => '',
                'wpsp_el_social_facebook'          => [],
                'wpsp_el_social_instagram'         => [],
                'wpsp_el_social_medium'            => [],
                'wpsp_el_social_twitter'           => [],
                'wpsp_el_social_linkedin'          => [],
                'wpsp_el_social_pinterest'         => [],
                'wpsp_el_pinterest_board'          => [],
            ] );

            do_action( 'wpsp_el_action_before', $args );

            // @todo moved to pro, will be removed in next version...
            if ( $this->pro_enabled ) {
                if ( ! empty( $args['republish_datetime'] ) ) {
                    update_post_meta( $args['id'], '_wpscp_schedule_republish_date', sanitize_text_field( $args['republish_datetime'] ) );
                }

                if ( ! empty( $args['unpublish_datetime'] ) ) {
                    update_post_meta( $args['id'], '_wpscp_schedule_draft_date', sanitize_text_field( $args['unpublish_datetime'] ) );
                }
            }


            $is_future = true;

            $msg = __( 'Your post successfully updated', 'wp-scheduled-posts' );

            // update selected profiles
            $facebookProfile  = \WPSP\Helper::get_settings('facebook_profile_list');
            $twitterProfile   = \WPSP\Helper::get_settings('twitter_profile_list');
            $linkedinProfile  = \WPSP\Helper::get_settings('linkedin_profile_list');
            $pinterestProfile = \WPSP\Helper::get_settings('pinterest_profile_list');
            $instagramProfile = \WPSP\Helper::get_settings('instagram_profile_list');
            $mediumProfile    = \WPSP\Helper::get_settings('medium_profile_list');
            $selectedSocialProfiles = [];
            if( !empty( $args['wpsp_el_social_facebook'] ) && !empty( $facebookProfile ) && !empty( $args['wpsp-el-content-facebook'] ) ) {
                if( $args['wpsp-el-content-facebook'] == 'wpsp-el-social-facebook-custom' ) {
                    $selectedFacebookProfile = $args['wpsp_el_social_facebook'];
                    $facebookSelectedProfile = array_filter($facebookProfile, function ($obj) use ( $selectedFacebookProfile ) {
                        return in_array($obj->name, $selectedFacebookProfile);
                    });
                    $selectedSocialProfiles = array_merge( $facebookSelectedProfile, $selectedSocialProfiles );
                }else{
                    $selectedSocialProfiles =  array_merge( $selectedSocialProfiles, $facebookProfile );
                }
                
            }
            if( !empty( $args['wpsp_el_social_twitter'] ) && !empty( $twitterProfile )  && !empty( $args['wpsp-el-content-twitter'] ) ) {
                if( $args['wpsp-el-content-twitter'] == 'wpsp-el-social-twitter-custom' ) {
                    $selectedTwitterProfile = $args['wpsp_el_social_twitter'];
                    $twitterSelectedProfile = array_filter($twitterProfile, function ($obj) use ( $selectedTwitterProfile ) {
                        return in_array($obj->name, $selectedTwitterProfile);
                    });
                    $selectedSocialProfiles = array_merge( $twitterSelectedProfile, $selectedSocialProfiles );    
                } else{
                    $selectedSocialProfiles =  array_merge( $selectedSocialProfiles, $twitterProfile );
                }
            }
            if( !empty( $args['wpsp_el_social_linkedin'] ) && !empty( $linkedinProfile ) ) {
                if( ( ( !empty( $args['wpsp-el-content-linkedin-page'] ) && $args['wpsp-el-content-linkedin-page'] == 'wpsp-el-social-linkedin-page-custom' ) || (!empty( $args['wpsp-el-content-linkedin-profile'] ) && $args['wpsp-el-content-linkedin-profile'] == 'wpsp-el-social-linkedin-profile-custom') ) ) {
                    $selectedLinkedinProfile = $args['wpsp_el_social_linkedin'];
                    $linkedinSelectedProfile = array_filter($linkedinProfile, function ($obj) use ( $selectedLinkedinProfile ) {
                        return in_array($obj->name, $selectedLinkedinProfile);
                    });
                    $selectedSocialProfiles = array_merge( $linkedinSelectedProfile, $selectedSocialProfiles );
                }else{
                    $selectedSocialProfiles =  array_merge( $selectedSocialProfiles, $linkedinProfile );
                }
            }
            if( !empty( $pinterestProfile ) && !empty( $args['wpsp-el-content-pinterest'] ) ) {
                if( $args['wpsp-el-content-pinterest'] == 'wpsp-el-social-pinterest-custom' && !empty( $args['wpsp_el_social_pinterest'] ) ) {
                    $selectedPinterestProfile = $args['wpsp_el_social_pinterest'];
                    $pinterestSelectedProfile = array_filter($pinterestProfile, function ($obj) use ( $selectedPinterestProfile ) {
                        return in_array($obj->default_board_name->value, $selectedPinterestProfile);
                    });
                     // update pinterest section 
                    $pinterest_section = new SocialProfile();
                    if( !empty( $args['wpsp_el_pinterest_board'] ) ) {
                        foreach ( $args['wpsp_el_pinterest_board'] as $section_of_board) {
                            $explode_board_and_section = explode( '|', $section_of_board );
                            if( !empty( $explode_board_and_section[0] ) && !empty( $explode_board_and_section[1] ) ) {
                                $get_section_array = $pinterest_section->social_fetch_pinterest_section_array( $explode_board_and_section[1], $explode_board_and_section[0] );
                                // Iterate through the array
                                foreach ($pinterestSelectedProfile as $pinterest_profile) {
                                    if ( isset($pinterest_profile->default_board_name->value) && $pinterest_profile->default_board_name->value == $explode_board_and_section[1] ) {
                                        $pinterest_profile->defaultSection = json_decode( json_encode( $get_section_array ) );
                                    }
                                }
                            }
                        }
                    }
                    $selectedSocialProfiles = array_merge( $pinterestSelectedProfile, $selectedSocialProfiles );
                }else{
                    $selectedSocialProfiles =  array_merge( $selectedSocialProfiles, $pinterestProfile );
                }   
            }
            if( !empty( $instagramProfile ) && !empty( $args['wpsp-el-content-instagram'] ) ) {
                if( $args['wpsp-el-content-instagram'] == 'wpsp-el-social-instagram-custom' && !empty( $args['wpsp_el_social_instagram'] ) ) {
                    $selectedInstagramProfile = $args['wpsp_el_social_instagram'];
                    $instagramSelectedProfile = array_filter($instagramProfile, function ($obj) use ( $selectedInstagramProfile ) {
                        return in_array($obj->name, $selectedInstagramProfile);
                    });
                    $selectedSocialProfiles = array_merge( $instagramSelectedProfile, $selectedSocialProfiles );
                } else{
                    $selectedSocialProfiles =  array_merge( $selectedSocialProfiles, $instagramProfile );
                }
            }
            if( !empty( $mediumProfile ) && !empty( $args['wpsp-el-content-medium'] ) ) {
                if( $args['wpsp-el-content-medium'] == 'wpsp-el-social-medium-custom' && !empty( $args['wpsp_el_social_medium'] ) ) {
                    $selectedInstagramProfile = $args['wpsp_el_social_medium'];
                    $mediumSelectedProfile = array_filter($mediumProfile, function ($obj) use ( $selectedInstagramProfile ) {
                        return in_array($obj->name, $selectedInstagramProfile);
                    });
                    $selectedSocialProfiles = array_merge( $mediumSelectedProfile, $selectedSocialProfiles );
                } else{
                    $selectedSocialProfiles =  array_merge( $selectedSocialProfiles, $mediumProfile );
                }
            }
            $selectedSocialProfiles = $this->wpsp_format_profile_data( $selectedSocialProfiles );
            if( Helper::is_enable_classic_editor() ) {
                update_post_meta( $args['id'], '_selected_social_profile', $selectedSocialProfiles );
            }
            

            // social media type selection settings
            if( !empty( $args['wpsp-el-content-facebook'] ) ) {
                if( $args['wpsp-el-content-facebook'] == 'wpsp-el-social-facebook-custom' ) {
                    update_post_meta( $args['id'], '_facebook_share_type', 'custom' );
                }else{
                    update_post_meta( $args['id'], '_facebook_share_type', 'default' );
                }
            }

            if( !empty( $args['wpsp-el-content-linkedin-profile'] ) ) {
                if( $args['wpsp-el-content-linkedin-profile'] == 'wpsp-el-social-linkedin-profile-custom' ) {
                    update_post_meta( $args['id'], '_linkedin_share_type', 'custom' );
                }else{
                    update_post_meta( $args['id'], '_linkedin_share_type', 'default' );
                }
            }

            if( !empty( $args['wpsp-el-content-linkedin-page'] ) ) {
                if( $args['wpsp-el-content-linkedin-page'] == 'wpsp-el-social-linkedin-page-custom' ) {
                    update_post_meta( $args['id'], '_linkedin_share_type_page', 'custom' );
                }else{
                    update_post_meta( $args['id'], '_linkedin_share_type_page', 'default' );
                }
            }

            if( !empty( $args['wpsp-el-content-pinterest'] ) ) {
                if( $args['wpsp-el-content-pinterest'] == 'wpsp-el-social-pinterest-custom' ) {
                    update_post_meta( $args['id'], '_pinterest_share_type', 'custom' );
                }else{
                    update_post_meta( $args['id'], '_pinterest_share_type', 'default' );
                }
            }

            if( !empty( $args['wpsp-el-content-twitter'] ) ) {
                if( $args['wpsp-el-content-twitter'] == 'wpsp-el-social-twitter-custom' ) {
                    update_post_meta( $args['id'], '_twitter_share_type', 'custom' );
                }else{
                    update_post_meta( $args['id'], '_twitter_share_type', 'default' );
                }
            }
            if( !empty( $args['wpsp-el-content-instagram'] ) ) {
                if( $args['wpsp-el-content-instagram'] == 'wpsp-el-social-instagram-custom' ) {
                    update_post_meta( $args['id'], '_instagram_share_type', 'custom' );
                }else{
                    update_post_meta( $args['id'], '_instagram_share_type', 'default' );
                }
            } 
            if( !empty( $args['wpsp-el-content-medium'] ) ) {
                if( $args['wpsp-el-content-medium'] == 'wpsp-el-social-medium-custom' ) {
                    update_post_meta( $args['id'], '_medium_share_type', 'custom' );
                }else{
                    update_post_meta( $args['id'], '_medium_share_type', 'default' );
                }
            }

            if ( empty( $args['date'] ) ) {
                $args['date'] = date( 'Y-m-d H:i:s', current_time( 'U' ) );
                $is_future    = false;
                $msg          = __( 'Your post successfully published', 'wp-scheduled-posts' );
            }

            if ( $offset !== 0 ) {
                $date_gmt = date( "Y-m-d H:i:s", strtotime( $args['date'] ) + $offset * HOUR_IN_SECONDS );
            } else {
                $date_gmt = $args['date'];
            }

            if ( empty( $args['id'] ) ) {
                wp_send_json_error( [
                    'msg' => __( 'Your post id is empty', 'wp-scheduled-posts' )
                ] );
            }

            $id = wp_update_post( [
                'ID'            => absint( $args['id'] ),
                'post_date'     => $args['date'],
                'post_date_gmt' => $date_gmt,
                'post_status'   => $args['post_status'],
                'edit_date'     => true,
            ] );

            /**
             * When scheduling draft post the post status is set to publish by wp.
             */
            if ( $is_future && get_post_status( $id ) !== 'future' ) {
                $id = wp_update_post( [
                    'ID'            => absint( $args['id'] ),
                    'post_date'     => $args['date'],
                    'post_date_gmt' => $date_gmt,
                    'post_status'   => $args['post_status']
                ] );
            }

            $status = get_post_status( $id );

            if ( $status === 'future' ) {
                $msg = __( 'Your post successfully scheduled', 'wp-scheduled-posts' );
            }

            do_action( 'wpsp_el_action', absint( $args['id'] ) );

            wp_send_json_success( [
                'id'        => $id,
                'status'    => $status,
                'post_time' => $args['date'],
                'msg'       => $msg
            ] );
        }
    }
}
