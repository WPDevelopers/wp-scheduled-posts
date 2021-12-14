<?php

namespace WPSP;


class Admin
{
	/**
	 * @var bool
	 */
	private $pro_enabled;

	public function __construct()
    {
        $this->load_plugin_menu_pages();
        $this->pro_enabled();
        // Core
        add_filter('plugin_action_links_' . WPSP_PLUGIN_BASENAME, array($this, 'insert_plugin_links'));
        add_filter('plugin_row_meta', array($this, 'insert_plugin_row_meta'), 10, 2);
        $this->admin_notice();
        $this->usage_tracker();
        $this->load_dashboard_widgets();
        $this->load_settings();
        $this->load_elementor_panel_icon();
	    if ( ! $this->pro_enabled ) {
		    add_action( 'wpsp_el_modal_pro_fields', [ $this, 'wpsp_el_modal_pro_fields' ] );
	    }

	    add_action( 'wp_ajax_wpsp_el_editor_form', [ $this, 'wpsp_el_tab_action' ] );
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
		$hide_on_elementor_editor = Helper::get_settings('hide_on_elementor_editor');
		if ( ! $hide_on_elementor_editor ) {
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
        $notice = new Admin\WPDev\WPDevNotice(WPSP_PLUGIN_BASENAME, WPSP_VERSION);

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
        $notice->message('review', '<p>' . __('We hope you\'re enjoying SchedulePress! Could you please do us a BIG favor and give it a 5-star rating on WordPress to help us spread the word and boost our motivation?', 'wp-scheduled-posts') . '</p>');
        $notice->thumbnail('review', plugins_url('assets/images/wpsp-logo.svg', WPSP_PLUGIN_BASENAME));
        /**
         * This is upsale notice settings
         * classes for wrapper, 
         * Message message for showing.
         */
        $notice->classes('upsale', 'notice is-dismissible ');
        $notice->message('upsale', '<p>' . __('Enjoying <strong>SchedulePress</strong>? Why not check our <strong><a href="https://wpdeveloper.com/in/wp-scheduled-posts-pro" target="_blank">Pro version</a></strong> which will enable auto schedule, multi social account share and many more features! [<strong><a href="https://wpdeveloper.com/plugins/wp-scheduled-posts/" target="_blank">Learn More</a></strong>]', 'wp-scheduled-posts') . '</p>');
        $notice->thumbnail('upsale', plugins_url('assets/images/wpsp-logo.svg', WPSP_PLUGIN_BASENAME));

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
    public function usage_tracker()
    {
        new Admin\WPDev\PluginUsageTracker(
            WPSP_PLUGIN_FILE,
            'http://app.wpdeveloper.com',
            array(),
            true,
            true,
            1
        );
    }

    public function load_settings()
    {
        new Admin\Settings(WPSP_SETTINGS_SLUG, WPSP_SETTINGS_NAME);
    }

    public function schedulepress_el_tab () { ?>
        <style>
            #schedulepress-elementor-modal.elementor-templates-modal .dialog-widget-content {
                background: #f5f7fd;
            }

            .wpsp-el-modal-date-picker .flatpickr-calendar {
                left: 50% !important;
                top: 50% !important;
                transform: translate(-50%, -50%);
                animation: none !important;
            }

            #schedulepress-elementor-modal.elementor-templates-modal .dialog-header {
                background: #fff;
                box-shadow: none;
            }

            #schedulepress-elementor-modal .elementor-templates-modal__header__close--normal {
                border-left: none;
            }

            #schedulepress-elementor-modal .elementor-templates-modal__header__close--normal svg {
                cursor: pointer;
                transition: .3s;
            }

            #schedulepress-elementor-modal .elementor-templates-modal__header__close--normal svg:hover {
                transform: scale(1.3);
            }

            #schedulepress-elementor-modal .dialog-widget-content {
                border-radius: 10px;
            }

            #schedulepress-elementor-modal.elementor-templates-modal .dialog-buttons-wrapper {
                background: transparent;
                box-shadow: none;
            }

            #schedulepress-elementor-modal.elementor-templates-modal .dialog-message {
                height: auto;
                padding-bottom: 20px;
            }

            @media (max-width: 1439px) {
                #schedulepress-elementor-modal.elementor-templates-modal .dialog-widget-content {
                    max-width: 500px;
                }
            }

            @media (min-width: 1440px) {
                #schedulepress-elementor-modal.elementor-templates-modal .dialog-widget-content {
                    max-width: 500px;
                }
            }

            #schedulepress-elementor-modal form label {
                display: block;
                text-align: left;
            }

            #schedulepress-elementor-modal form label input {
                background-color: #e6eaf8;
                background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' xmlns:xlink='http://www.w3.org/1999/xlink' x='0px' y='0px' viewBox='0 0 200 200' style='enable-background:new 0 0 200 200;' width='10' xml:space='preserve'%3E%3Cstyle type='text/css'%3E .st0%7Bfill:%239E9ED8;%7D%0A%3C/style%3E%3Cpath class='st0' d='M175,24.7h-8.3V8.1c0-4.6-3.7-8.3-8.3-8.3H150c-4.6,0-8.3,3.7-8.3,8.3v16.7H58.3V8.1c0-4.6-3.7-8.3-8.3-8.3 h-8.3c-4.6,0-8.3,3.7-8.3,8.3v16.7H25c-13.8,0-25,11.2-25,25v125c0,13.8,11.2,25,25,25h150c13.8,0,25-11.2,25-25v-125 C200,36,188.8,24.7,175,24.7z M183.3,174.7c0,4.6-3.7,8.3-8.3,8.3H25c-4.6,0-8.3-3.7-8.3-8.3V83.4h166.7V174.7z'/%3E%3C/svg%3E%0A");
                background-repeat: no-repeat;
                background-position: calc(100% - 15px) center;
                border: none;
                border-radius: 5px;
                height: 35px;
                padding: 0 15px;
                color: #303042;
            }

            #schedulepress-elementor-modal form label + label {
                margin-top: 15px;
            }

            #schedulepress-elementor-modal form label > span {
                display: block;
                color: #303042;
                font-weight: 700;
                margin-bottom: 5px;
            }

            #schedulepress-elementor-modal .wpsp-pro-fields label {
                margin-top: 15px;
            }

            #schedulepress-elementor-modal .wpsp-pro-fields:not(.wpsp-pro-activated) label > span {
                color: #9696af;
            }

            #schedulepress-elementor-modal .wpsp-pro-fields label > span > span {
                background: #6d64ff;
                border-radius: 5px;
                line-height: 10px;
                display: inline-block;
                font-size: 8px;
                padding: 0 4px;
                margin-left: 5px;
                color: #f5f7fd;
                transform: translateY(-1px);
            }

            #schedulepress-elementor-modal form .wpsp-pro-fields:not(.wpsp-pro-activated) label input {
                opacity: .5;
            }

            #schedulepress-elementor-modal .elementor-button {
                color: #6d64ff;
                background: rgba(109, 100, 255, .2);
                height: 35px;
                font-size: 15px;
                padding: 0 25px;
                border-radius: 18px;
                font-weight: 400;
                text-transform: initial;
            }

            #schedulepress-elementor-modal .elementor-button.wpsp-el-form-submit {
                background: rgba(109, 100, 255, 1);
                color: #fff;
            }

            #schedulepress-elementor-modal .elementor-button.wpsp-immediately-publish.active {
                background: #00cc76;
                color: #fff;
            }

            #schedulepress-elementor-modal .elementor-button + .elementor-button {
                margin-left: 15px;
            }

            #schedulepress-elementor-modal.elementor-templates-modal .dialog-buttons-wrapper {
                padding: 0 30px 30px;
            }

            #schedulepress-elementor-modal .wpsp-el-result {
                text-align: left;
                color: red;
                padding-top: 10px;
            }

            #schedulepress-elementor-modal .wpsp-el-result.wpsp-msg-success {
                color: green;
            }

            #schedulepress-elementor-modal .wpsp-el-form-submit.elementor-button-state > .elementor-state-icon + span {
                display: none;
            }
        </style>
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
						    <?php
						    wp_nonce_field( 'wpsp-el-editor', 'wpsp-el-editor' );
						    $post_id   = get_the_ID();
						    $post      = get_post( $post_id );
						    $status    = get_post_status( $post_id );
						    $is_future = $status === 'future';
						    ?>
                            <input type="hidden" name="action" value="wpsp_el_editor_form">
                            <input type="hidden" name="id" value="<?php echo $post_id; ?>">

                            <label>
                                <span><?php esc_html_e( 'Publish On', 'wp-scheduled-posts' ); ?></span>
                                <input id="wpsp-schedule-datetime" type="text" name="date" value="<?php echo esc_attr( $post->post_date ) ?>" readonly>
                            </label>
	                        <?php do_action( 'wpsp_el_modal_pro_fields', $post_id ); ?>
                        </form>
                        <div class="wpsp-el-result" style="display: none;"></div>
                    </div>
				    <div class="dialog-loading dialog-lightbox-loading"></div>
			    </div>
                <div class="dialog-buttons-wrapper dialog-lightbox-buttons-wrapper" style="display: flex;">
                    <button class="elementor-button wpsp-immediately-publish" style="<?php if ( ! $is_future ) { echo 'display: none;'; } ?>">
                        <?php esc_html_e( 'Publish Post Immediately', 'wp-scheduled-posts' ); ?>
                    </button>
                    <button class="elementor-button wpsp-el-form-submit" data-label-schedule="<?php esc_html_e( 'Schedule', 'wp-scheduled-posts' ); ?>"
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

	public function wpsp_el_tab_action() {
		if ( check_ajax_referer( 'wpsp-el-editor', 'wpsp-el-editor' ) ) {
			$offset = get_option( 'gmt_offset' );
			$offset = $offset == 0 ? 0 : ( 0 - $offset );
			$args   = wp_parse_args( $_POST, [
				'id'                 => 0,
				'date'               => '',
				'republish_datetime' => '',
				'unpublish_datetime' => '',
				'post_status'        => 'future'
			] );

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
				'post_status'   => $args['post_status']
			] );

			if ( $is_future && get_post_status( $id ) !== 'future' ) {
				$id = wp_update_post( [
					'ID'            => absint( $args['id'] ),
					'post_date'     => $args['date'],
					'post_date_gmt' => $date_gmt,
					'post_status'   => $args['post_status']
				] );
			}

			if ( $this->pro_enabled ) {
				if ( ! empty( $args['republish_datetime'] ) ) {
					update_post_meta( $args['id'], '_wpscp_schedule_republish_date', sanitize_text_field( $args['republish_datetime'] ) );
				}

				if ( ! empty( $args['unpublish_datetime'] ) ) {
					update_post_meta( $args['id'], '_wpscp_schedule_draft_date', sanitize_text_field( $args['unpublish_datetime'] ) );
				}
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
