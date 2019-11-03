<?php
if( ! class_exists( 'wpscpSetupWizard' ) ){
	class wpscpSetupWizard {
        public static $sections_array = array();
        public static $optionGroupName = 'wpscp_options';
		public static function load(){
			// Hook it up.
			// add_action( 'admin_init', array( __CLASS__, 'admin_init' ) );
			// Menu.
            add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ) );
            
            // tab builder
            add_action( 'admin_enqueue_scripts', array(__CLASS__, 'setup_wizard_scripts'), 30 );
            add_action('wpscp_nav_tabs', array(__CLASS__, 'add_nav_tabs'));
            add_action('wpscp_tabs_content', array(__CLASS__, 'add_tab_content'));
            // ajax request
            add_action( 'wp_ajax_quick_setup_wizard_action', array(__CLASS__, 'quick_setup_wizard_data_save') );
        }
        
        public static function setup_wizard_scripts(){
            wp_enqueue_style( 'wpscp-setup-wizard', WPSCP_ADMIN_URL . 'setup-wizard/assets/css/wpscp-setup-wizard.css' );
            $wpscpQswVersionNumber  = date("ymd-Gis", filemtime( WPSCP_ADMIN_DIR_PATH . 'setup-wizard/assets/js/wpscp-setup-wizard.js' ));
            wp_enqueue_script( 'wpscp-setup-wizard', WPSCP_ADMIN_URL . 'setup-wizard/assets/js/wpscp-setup-wizard.js', array('jquery'), $wpscpQswVersionNumber, false );
        }

		// add admin page
		public static function admin_menu(){
			add_submenu_page(
				null,
				'Quick Setup Wizard',
				'Quick Setup Wizard',
				'manage_options',
				'wpscp-quick-setup-wizard',
				array(  __CLASS__, 'plugin_setting_page' )
			);
        }
        
        public static function quick_setup_wizard_data_save(){
            check_ajax_referer( 'wpscpqswnonce', 'security');
            $oldValue = get_option( self::$optionGroupName );
            $newValue['show_dashboard_widget'] = (isset($_POST['show_dashboard_widget']) ? $_POST['show_dashboard_widget'] : '');
            $newValue['show_in_front_end_adminbar'] = (isset($_POST['show_in_front_end_adminbar']) ? $_POST['show_in_front_end_adminbar'] : '');
            $newValue['show_in_adminbar'] = (isset($_POST['show_in_adminbar']) ? $_POST['show_in_adminbar'] : '');
            $newValue['prevent_future_post'] = (isset($_POST['prevent_future_post']) ? $_POST['prevent_future_post'] : '');
            $newValue['allow_post_types'] = (isset($_POST['allow_post_types']) ? $_POST['allow_post_types'] : '');
            $newValue['allow_categories'] = (isset($_POST['allow_categories']) ? $_POST['allow_categories'] : '');
            $newValue['allow_user_role'] = (isset($_POST['allow_user_role']) ? $_POST['allow_user_role'] : '');
            // new update value
            $updatedValue = array_merge($oldValue, $newValue);
            if(!get_option(self::$optionGroupName)){
                add_option(self::$optionGroupName, $updatedValue);
            }else {
                update_option(self::$optionGroupName, $updatedValue);
            }
            // auto scheduled / manual scheduled option save
            $autoScheduler = (isset($_POST['autoScheduler']) ? $_POST['autoScheduler'] : '');
            $manualScheduler = (isset($_POST['manualScheduler']) ? $_POST['manualScheduler'] : '');
            if($autoScheduler == 'ok') {
                update_option('pub_active_option', 'ok');
                update_option('cal_active_option', false);
            }else if($manualScheduler == 'ok') {
                update_option('pub_active_option', false);
                update_option('cal_active_option', 'ok');
            }
            // miss scheduled option saved
            if (isset($_POST['missscheduled']) && $_POST['missscheduled'] == 1) {
                if(!get_option( 'miss_schedule_active_option' )){
                    add_option('miss_schedule_active_option', 'yes');
                } else {
                    update_option('miss_schedule_active_option', 'yes');
                }
            }else{
                update_option('miss_schedule_active_option', 'no');
            }
            //  social integation update - twitter
            $tw_consumer_key = (isset($_POST['tw_consumer_key']) ? trim($_POST['tw_consumer_key']) : '');
            $tw_consumer_sec = (isset($_POST['tw_consumer_sec']) ? trim($_POST['tw_consumer_sec']) : '');
            $tw_access_key 	= (isset($_POST['tw_access_key']) ? trim($_POST['tw_access_key']) : '');
            $tw_access_sec 	= (isset($_POST['tw_access_sec']) ? trim($_POST['tw_access_sec']) : '');
            $tw_con_key_up = update_option('wpsp_twitter_consumer_key', $tw_consumer_key);
            $tw_con_sec_up = update_option('wpsp_twitter_consumer_sec', $tw_consumer_sec);
            $tw_acc_key_up = update_option('wpsp_twitter_access_key', $tw_access_key);	
            $tw_acc_sec_up = update_option('wpsp_twitter_access_sec', $tw_access_sec);
            // social integation update - facebook
            $wpscp_pro_app_type = (isset($_POST['wpscp_pro_app_type']) ? trim($_POST['wpscp_pro_app_type']) : '');
            $fb_app_id = (isset($_POST['fb_app_id']) ? trim($_POST['fb_app_id']) : '');
            $fb_app_secret = (isset($_POST['fb_app_secret']) ? trim($_POST['fb_app_secret']) : '');
            $fb_access_token = (isset($_POST['fb_access_token']) ? trim($_POST['fb_access_token']) : '');

            if($wpscp_pro_app_type == 'wpscpapp') {
                update_option('wpscp_pro_fb_app_id', '');
                update_option('wpscp_pro_fb_secret', '');
                update_option('wpscp_pro_fb_access_token', $fb_access_token);
            } else {
                update_option('wpscp_pro_fb_app_id', $fb_app_id);
                update_option('wpscp_pro_fb_secret', $fb_app_secret);
                update_option('wpscp_pro_fb_access_token', $fb_access_token);
            }
            update_option('wpscp_pro_app_type', $wpscp_pro_app_type);


            wp_die(); // this is required to terminate immediately and return a proper response
        }
		
		public static function plugin_setting_page() {
            ?> 
                <div id="wpwrap">
                    <div class="wpsp-dashboard-body">
                        <div class="wpsp_loader">
                            <img src="<?php echo plugins_url('/wp-scheduled-posts/admin/assets/images/wpscp-logo.gif'); ?>" alt="Loader">
                        </div>
                        <!-- Topbar -->
                        <div class="wpsp_top_bar_wrapper">
                            <div class="wpsp_top_bar_logo">
                                <img src="<?php echo plugins_url(); ?>/wp-scheduled-posts/admin/assets/images/wpsp-icon.svg" alt="">
                            </div>
                            <div class="wpsp_top_bar_heading">
                                <h2 class="wpsp_topbar_title"><?php esc_html_e('WP Scheduled Posts', 'wpscp'); ?></h2>
                                <p class="wpsp_topbar_version_name"><?php echo esc_html__('Version ', 'wpscp') . WPSP_VERSION; ?></p>
                            </div>
                        </div>
                        <!-- setup wizard -->
                        <div class="wpscp-setup-wizard">
                            <form method="post" action="#">
                                <input type="hidden" name="wpscpqswnonce" value="<?php print wp_create_nonce( 'wpscpqswnonce' ); ?>">
                                <div class="wpscp-tabnav-wrap">
                                    <ul class="tab-nav">
                                        <?php do_action('wpscp_nav_tabs'); ?>
                                    </ul>
                                </div>
                                <div class="wpscp-tab-content-wrap">
                                    <?php 
                                        do_action('wpscp_tabs_content'); 
                                    ?>
                                    <div class="wpscp-button-wrap">
                                        <a id="wpscp-prev-option" href="#" class="btn wpscp-prev-option">Previous</a>
                                        <a id="wpscp-next-option" href="#" class="btn wpscp-next-option">Next</a>
                                    </div>
                                    <div class="bottom-notice-left">
                                        <p class="whatwecollecttext">We collect non-sensitive diagnostic data and plugin usage <br> information. Your site URL, WordPress & PHP version, <br> plugins & themes and email address to send you the discount <br> coupon. This data lets us make sure this plugin always stays <br> compatible with the most popular plugins and themes. No spam, I promise.</p>
                                        <button type="button" id="whatwecollectdata" class="btn-collect">What We Collect?</button>
                                    </div>
                                    <div class="bottom-notice">
                                        <button type="button" id="wpscpqswemailskipbutton" class="btn-skip">Skip This Step</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
			<?php
		}

		public static function setSection( $section ){
			// Bail if not array.
			if ( ! is_array( $section ) ) {
				return false;
            }
            
            self::$sections_array[$section['id']] = $section;

			// Assign to the sections array
			return  self::$sections_array;
		}

		public static function get_value($args){
            if($args == null || $args == ''){
                return;
            }
            $optionValue = get_option( self::$optionGroupName ) ;
            if(isset($optionValue[$args['id']])) {
               return $optionValue[$args['id']];
            }else {
                    return $args['default'];
            }
            return;
		}

		public static function get_field_description($args){
			if ( ! empty( $args['desc'] ) ) {
				$desc = sprintf( '<p class="description">%s</p>', $args['desc'] );
			} else {
				$desc = '';
			}

			return $desc;
		}

        /**
         * Fields Type: Text
         * @param array
         * @return Markup
         */
		public static function callback_text($args) {
			$value = esc_attr( self::get_value($args) );
			$size  = isset( $args['size'] ) && ! is_null( $args['size'] ) ? $args['size'] : 'regular';
			$type  = isset( $args['type'] ) ? $args['type'] : 'text';
            $field = '';
            $markup = '';
            $field  = sprintf( '<input type="%1$s" class="%2$s-text" name="%3$s" value="%4$s" placeholder="%5$s"/>', $type, $size, $args['id'], $value, $args['placeholder'] );
            $field .= self::get_field_description( $args );
            $markup .= '<tr>
                <th scope="row">
                    <label for="'.$args['id'].'">'.$args['title'].'</label>
                </th>
                <td>
                    '.$field.'
                </td>
            </tr>';
			echo $markup;
		}

        /**
         * Fields Type: textarea
         * @param array
         * @return Markup
         */
		public static function callback_textarea( $args ) {
			$value = esc_textarea( self::get_value($args) );
			$size  = isset( $args['size'] ) && ! is_null( $args['size'] ) ? $args['size'] : 'regular';
            $field = '';
            $markup = '';
			$field  = sprintf( '<textarea rows="5" cols="55" class="%1$s-text" id="%2$s" name="%3$s" placeholder="%4$s">%5$s</textarea>', $size, $args['id'], $args['id'], $args['placeholder'], $value );
            $field .= self::get_field_description( $args );
            $markup .= '<tr>
                <th scope="row">
                    <label for="'.$args['id'].'">'.$args['title'].'</label>
                </th>
                <td>
                    '.$field.'
                </td>
            </tr>';
			echo $markup;
        }
        
        /**
         * Fields Type: checkbox
         * @param array
         * @return Markup
         */
        public static function callback_checkbox( $args ) {
            $value = self::get_value($args);
            $field = '';
            $markup = '';
            $field  = sprintf( '<input class="%1$s-checkbox" id="%1$s" name="%1$s" type="checkbox" %3$s>', $args['id'], 1, checked( 1, $value, false ) );
            $field .= self::get_field_description( $args );
            $markup .= '<tr>
                <th scope="row">
                    <label for="'.$args['id'].'">'.$args['title'].'</label>
                </th>
                <td>
                    '.$field.'
                </td>
            </tr>';
            echo $markup;
        }

        /**
         * Fields Type: radio
         * @param array
         * @return Markup
         */
        public static function callback_radio( $args ) {
            $value = self::get_value($args);
            $field = '';
            $markup = '';
            if(is_array($args['options'])){
                foreach($args['options'] as $key => $option){
                    $field .= sprintf( '<input class="%1$s-radio" type="radio" name="%1$s" value="%2$s" %4$s>%3$s', $args['id'], $key, $option, checked( $key, $value, false ) );
                }
            }
            $field .= self::get_field_description( $args );
            $markup .= '<tr>
                <th scope="row">
                    <label for="'.$args['id'].'">'.$args['title'].'</label>
                </th>
                <td>
                    '.$field.'
                </td>
            </tr>';
			echo $markup;
        }


        public static function callback_select( $args ){
            $value = self::get_value($args);
            $field = $markup = '';
            $field .= sprintf( '<select id="%1$s" class="%1$s-select" name="%1$s" multiple>', $args['id'] );
                if(is_array($args['options'])){
                    $field .='<option value=""></option>';
                    foreach($args['options'] as $key => $option){
                        $field .= sprintf( '<option value="%1$s" '.(($value != "") ? (in_array($key, $value) ? 'selected' : '') : '').'>%2$s</option>',$key, $option);
                    }
                }
            $field .= '</select>';
            $field .= self::get_field_description( $args );
            $markup .= '<tr>
                <th scope="row">
                    <label for="'.$args['id'].'">'.$args['title'].'</label>
                </th>
                <td>
                    '.$field.'
                </td>
            </tr>';
            echo $markup;
        }

        public static function callback_scheduled( $args ){
            $field = $markup = '';
            ?>
            <tr>
                <td>
                    <h2 id="<?php print $args['id']; ?>"><?php print $args['title']; ?></h2>
                </td>
            </tr>
            <tr>
                <td>
                   <?php 
                        if(function_exists('wpscp_qsw_manage_scheduled_markup')){
                            wpscp_qsw_manage_scheduled_markup();
                        }
                    ?>
                </td>
            </tr>
            <?php
        }

        public static function callback_socialintegation( $args ){
            $field = $markup = '';
            ?>
            <tr>
                <td>
                    <h2 id="<?php print $args['id']; ?>"><?php print $args['title']; ?></h2>
                </td>
            </tr>
            <tr>
                <td>
                    <?php 
                        do_action('wpscp_pro_qsw_socialintegation');
                    ?>
                </td>
            </tr>
            <?php
        }
        public static function callback_welcome( $args ){
            ?>
            <tr>
                <td>
                    <h2 id="<?php print $args['id']; ?>"><?php print $args['title']; ?></h2>
                </td>
            </tr>
            <tr>
                <td>
                    <?php 
                        do_action('wpscp_pro_qsw_welcomescreen');
                    ?>
                </td>
            </tr>
            <?php
        }

        public static function callback_profeature( $args ){
            $field = $markup = '';
            ?>
            <tr>
                <td>
                    <h2 id="<?php print $args['id']; ?>"><?php print $args['title']; ?></h2>
                </td>
            </tr>
            <tr>
                <?php 
                    do_action('wpscp_pro_qsw_profeature_list');
                ?>
            </tr>
            <?php
        }
        



       
        public static function add_nav_tabs(){
            $tabNavCounter = 0;
            $allSections = apply_filters( 'wpscp_setup_wizard_fields', self::$sections_array );
            foreach ($allSections as $section) :
                ?>
                    <li class="nav-item<?php print ($tabNavCounter == 0 ? ' tab-active' : ''); ?>">
                        <span class="text"><?php print (isset($section['title']) ? $section['title'] : ''); ?></span>
                        <span class="number"><?php print (isset($section['sub_title']) ? $section['sub_title'] : ''); ?></span>
                    </li>
                <?php
                $tabNavCounter++;
            endforeach;
        }

        public static function add_tab_content(){
            $tabContentCounter = 0;
            $allSections = apply_filters( 'wpscp_setup_wizard_fields', self::$sections_array );
            foreach ($allSections as $section) :
            ?>
                <div class="tab-content" id="<?php print (isset($section['id']) ? $section['id'] : 'default-nav'); ?>">
                    <table class="form-table" role="presentation">
                        <tbody>
                        <?php 
                            if(isset($section['fields']) && is_array($section['fields'])){
                                foreach ( $section['fields'] as $field ) {
                                    $methodName = 'callback_' . $field['type'];
                                    self::$methodName($field);
                                }
                            }
                        ?>
                         </tbody>
                    </table>
                </div>
            <?php
            $tabContentCounter++;
            endforeach;
        }


	}
	wpscpSetupWizard::load();
}
