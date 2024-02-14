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

    public function schedulepress_el_tab () { ?>
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
                    <div class="dialog-content dialog-lightbox-content">
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
                <div class="dialog-buttons-wrapper dialog-lightbox-buttons-wrapper" style="display: flex; justify-content:space-between;">
                    <div id="wpsp-el-form-prev-next-button">
                        <button class="elementor-button wpsp-el-form-next">
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
                                data-label-publish="<?php esc_html_e( 'Publish', 'wp-scheduled-posts' ); ?>"
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

        <div id="elementor-panel-footer-sub-menu-item-wpsp" class="elementor-panel-footer-sub-menu-item elementor-panel-footer-tool tooltip-target" data-tooltip="<?php esc_attr_e( 'SchedulePress', 'wp-scheduled-posts' ); ?>">
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
                <span><?php esc_html_e( 'Republish On', 'wp-scheduled-posts' ); ?><span><?php esc_html_e( 'PRO', 'wp-scheduled-posts' ); ?></span></span>
                <input type="text" disabled>
            </label>
            <label title="<?php esc_html_e( 'Pro Feature', 'wp-scheduled-posts' ); ?>">
                <span><?php esc_html_e( 'Unpublish On', 'wp-scheduled-posts' ); ?><span><?php esc_html_e( 'PRO', 'wp-scheduled-posts' ); ?></span></span>
                <input type="text" disabled>
            </label>
        </div>
        <?php
    }

    public function wpsp_el_modal_social_share_profile() 
    {
        wp_nonce_field(basename(__FILE__), 'wpscp_pro_instant_social_share_nonce');
        // status=
        $twitterIntegation = \WPSP\Helper::get_settings('twitter_profile_status');
        $facebookIntegation = \WPSP\Helper::get_settings('facebook_profile_status');
        $linkedinIntegation = \WPSP\Helper::get_settings('linkedin_profile_status');
        $pinterestIntegation = \WPSP\Helper::get_settings('pinterest_profile_status');
        // profile
        $facebookProfile = \WPSP\Helper::get_settings('facebook_profile_list');
        if( !class_exists('WPSP_PRO') ) {
            $facebookProfile = array_slice( $facebookProfile, 0, 1, true );
        }

        $twitterProfile = \WPSP\Helper::get_settings('twitter_profile_list');
        if( !class_exists('WPSP_PRO') ) {
            $twitterProfile = array_slice( $twitterProfile, 0, 1, true );
        }
        $linkedinProfile = \WPSP\Helper::get_settings('linkedin_profile_list');
        if( !class_exists('WPSP_PRO') ) {
            $linkedinProfile = array_slice( $linkedinProfile, 0, 1, true );
        }
        $pinterestProfile = \WPSP\Helper::get_settings('pinterest_profile_list');
        if( !class_exists('WPSP_PRO') ) {
            $pinterestProfile = array_slice( $pinterestProfile, 0, 1, true );
        }

        ?>
           <div class="el-social-share-platform">
                <h4><?php echo esc_html__( 'Choose Social Share Platform', 'wp-scheduled-posts' ) ?></h4>
                <input type="hidden" name="postid" id="wpscppropostid" value="<?php print get_the_ID(); ?>">
                <div id="el-social-checkbox-wrapper">
                    <div class="wpsp-el-accordion">
                        <?php if( !empty( $facebookIntegation ) ) : ?>
                            <div class="wpsp-el-accordion-item wpsp-el-accordion-item-facebook">
                                <div class="wpsp-el-accordion-header"><img src="<?php echo esc_url( WPSP_ASSETS_URI . '/images/facebook.svg' ) ?>" alt=""><span><?php echo esc_html('Facebook') ?></span></div>
                                <div class="wpsp-el-accordion-content">
                                    <div class="wpsp-el-container">
                                        <label><input type="radio" data-platform="facebook" name="wpsp-el-content-facebook" value="wpsp-el-social-facebook-default" checked><?php echo esc_html__('Default','wp-scheduled-posts') ?></label>
                                        <label><input type="radio" data-platform="facebook" name="wpsp-el-content-facebook" value="wpsp-el-social-facebook-custom"><?php echo esc_html__('Custom','wp-scheduled-posts') ?></label>
                                    </div>
                                    <div class="wpsp-el-content wpsp-el-content-facebook" data-value="wpsp-el-social-facebook-custom" style="display: none;">
                                        <?php if( count( $facebookProfile ) > 0 ) : ?>
                                            <?php foreach( $facebookProfile as $facebook ) : ?>
                                                <div class="facebook-profile social-profile">
                                                    <input type="checkbox" value="<?php echo !empty( $facebook->name ) ? $facebook->name : '' ?>" name="wpsp_el_social_facebook" checked>
                                                    <h3><?php echo !empty( $facebook->name ) ? $facebook->name : '' ?> ( <?php echo $facebook->type ? $facebook->type : '' ?> ) </h3>
                                                </div>
                                            <?php endforeach ?>
                                        <?php endif ?>
                                    </div>
                                </div>
                            </div>
                        <?php endif ?>
                        <?php if( !empty( $twitterIntegation ) ) : ?>
                            <div class="wpsp-el-accordion-item wpsp-el-accordion-item-twitter">
                                <div class="wpsp-el-accordion-header"><img src="<?php echo esc_url( WPSP_ASSETS_URI . '/images/twitter.svg' ) ?>" alt=""><span><?php echo esc_html('Twitter') ?></span></div>
                                <div class="wpsp-el-accordion-content">
                                    <div class="wpsp-el-container">
                                        <label><input type="radio" data-platform="twitter" name="wpsp-el-content-twitter" value="wpsp-el-social-twitter-default" checked><?php echo esc_html__('Default','wp-scheduled-posts') ?></label>
                                        <label><input type="radio" data-platform="twitter" name="wpsp-el-content-twitter" value="wpsp-el-social-twitter-custom"><?php echo esc_html__('Custom','wp-scheduled-posts') ?></label>
                                    </div>
                                    <div class="wpsp-el-content wpsp-el-content-twitter" data-value="wpsp-el-social-twitter-custom" style="display: none;">
                                        <?php if( count( $twitterProfile ) > 0 ) : ?>
                                            <?php foreach( $twitterProfile as $twitter ) : ?>
                                                <div class="twitter-profile social-profile">
                                                    <input type="checkbox" value="<?php echo !empty( $twitter->name ) ? $twitter->name : '' ?>" name="wpsp_el_social_twitter" checked><h3><?php echo $twitter->name ? $twitter->name : '' ?> </h3>
                                                </div>
                                            <?php endforeach ?>
                                        <?php endif ?>
                                    </div>
                                </div>
                            </div>
                        <?php endif ?>
                        <?php if( !empty( $linkedinIntegation ) ) : ?>
                            <div class="wpsp-el-accordion-item wpsp-el-accordion-item-linkedin">
                                <div class="wpsp-el-accordion-header"><img src="<?php echo esc_url( WPSP_ASSETS_URI . '/images/linkedin.svg' ) ?>" alt=""><span><?php echo esc_html('LinkedIn') ?></span></div>
                                <div class="wpsp-el-accordion-content">
                                    <div class="wpsp-el-container">
                                        <label><input type="radio" data-platform="linkedin" name="wpsp-el-content-linkedin" value="wpsp-el-social-linkedin-default" checked><?php echo esc_html__('Default','wp-scheduled-posts') ?></label>
                                        <label><input type="radio" data-platform="linkedin" name="wpsp-el-content-linkedin" value="wpsp-el-social-linkedin-custom"><?php echo esc_html__('Custom','wp-scheduled-posts') ?></label>
                                    </div>
                                    <div class="wpsp-el-content wpsp-el-content-linkedin" data-value="wpsp-el-social-linkedin-custom" style="display: none;">
                                        <?php if( count( $linkedinProfile ) > 0 ) : ?>
                                            <?php foreach( $linkedinProfile as $linkedin ) : ?>
                                                <div class="linkedin-profile social-profile">
                                                    <input type="checkbox" value="<?php echo !empty( $linkedin->name ) ? $linkedin->name : '' ?>" name="wpsp_el_social_linkedin" checked><h3><?php echo isset( $linkedin->name ) ? $linkedin->name : '' ?> <?php isset( $linkedin->type ) && $linkedin->type == 'organization' ? __( '(Page)', 'wp-scheduled-posts' ) : '' ?> </h3>
                                                </div>
                                            <?php endforeach ?>
                                        <?php endif ?>
                                    </div>
                                </div>
                            </div>
                        <?php endif ?>
                        <?php if( !empty( $pinterestIntegation ) ) : ?>
                            <div class="wpsp-el-accordion-item wpsp-el-accordion-item-pinterest">
                                <div class="wpsp-el-accordion-header"><img src="<?php echo esc_url( WPSP_ASSETS_URI . '/images/pinterest.svg' ) ?>" alt=""><span><?php echo esc_html('Pinterest') ?></span></div>
                                <div class="wpsp-el-accordion-content">
                                    <div class="wpsp-el-container">
                                        <label><input type="radio" data-platform="pinterest" name="wpsp-el-content-pinterest" value="wpsp-el-social-pinterest-default" checked><?php echo esc_html__('Default','wp-scheduled-posts') ?></label>
                                        <label><input type="radio" data-platform="pinterest" name="wpsp-el-content-pinterest" value="wpsp-el-social-pinterest-custom"><?php echo esc_html__('Custom','wp-scheduled-posts') ?></label>
                                    </div>
                                    <div class="wpsp-el-content wpsp-el-content-pinterest" data-value="wpsp-el-social-pinterest-custom" style="display: none;">
                                        <?php if( count( $pinterestProfile ) > 0 ) : ?>
                                            <?php foreach( $pinterestProfile as $key => $pinterest ) : ?>
                                                <?php
                                                    if( empty( $pinterest->default_board_name->value ) )  {
                                                        continue;
                                                    }
                                                ?>
                                                <?php 
                                                    $pinterest_section = new SocialProfile();
                                                    $get_pinterest_sections = $pinterest_section->social_profile_fetch_pinterest_section( [ 'defaultBoard'  => $pinterest->default_board_name->value, 'profile' => $key, 'method_called'  => true ]  );
                                                ?>
                                                <div class="pinterest-profile social-profile">
                                                    <input type="checkbox" value="<?php echo $pinterest->default_board_name->value ?>" name="wpsp_el_social_pinterest" checked>
                                                    <h3><?php echo !empty( $pinterest->default_board_name->label ) ? $pinterest->default_board_name->label : '' ?> </h3>
                                                    <select name="wpsp_el_pinterest_board" id="wpsp_el_pinterest_section_<?php echo $pinterest->default_board_name->value ?>">
                                                        <option value=""><?php echo esc_html('No Section','wp-scheduled-posts') ?></option>
                                                        <?php if( !empty( $get_pinterest_sections ) ) : ?>
                                                            <?php foreach( $get_pinterest_sections as $section ) : ?>
                                                                <option value="<?php echo !empty( $section['id'] ) ? $section['id'] : '' ?> "><?php echo !empty( $section['name'] ) ? $section['name'] : '' ?></option>
                                                            <?php endforeach ?>
                                                        <?php endif ?>
                                                    </select>
                                                </div>
                                            <?php endforeach ?>
                                        <?php endif ?>
                                    </div>
                                </div>
                            </div>
                        <?php endif ?>
                    </div>
                </div>
           </div>
        <?php 
    }

    public function wpsp_el_tab_action() {
        if ( check_ajax_referer( 'wpsp-el-editor', 'wpsp-el-editor' ) ) {
            $offset = get_option( 'gmt_offset' );
            $offset = $offset == 0 ? 0 : ( 0 - $offset );
            $args   = wp_parse_args( $_POST, [
                'id'                 => 0,
                'date'               => '',
                'republish_datetime' => '',
                'unpublish_datetime' => '',
                'post_status'        => 'future',
                'advanced'           => null,
            ] );

            // @todo moved to pro, will be removed in next version...
            if ( $this->pro_enabled ) {
                if ( ! empty( $args['republish_datetime'] ) ) {
                    update_post_meta( $args['id'], '_wpscp_schedule_republish_date', sanitize_text_field( $args['republish_datetime'] ) );
                }

                if ( ! empty( $args['unpublish_datetime'] ) ) {
                    update_post_meta( $args['id'], '_wpscp_schedule_draft_date', sanitize_text_field( $args['unpublish_datetime'] ) );
                }
            }

            do_action( 'wpsp_el_action_before', $args );

            $is_future = true;

            $msg = __( 'Your post successfully updated', 'wp-scheduled-posts' );

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
