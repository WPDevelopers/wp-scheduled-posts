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
            add_action( 'admin_enqueue_scripts', array(__CLASS__, 'setup_wizard_scripts') );
            add_action('wpscp_nav_tabs', array(__CLASS__, 'add_nav_tabs'));
            add_action('wpscp_tabs_content', array(__CLASS__, 'add_tab_content'));
            // ajax request
            add_action( 'wp_ajax_quick_setup_wizard_action', array(__CLASS__, 'quick_setup_wizard_data_save') );
        }
        
        public static function setup_wizard_scripts(){
            wp_enqueue_style( 'wpscp-setup-wizard', WPSCP_ADMIN_URL . 'setup-wizard/assets/css/wpscp-setup-wizard.css' );
            wp_enqueue_script( 'wpscp-setup-wizard', WPSCP_ADMIN_URL . 'setup-wizard/assets/js/wpscp-setup-wizard.js', array('jquery'), null, false );
        }

		// add admin page
		public static function admin_menu(){
			add_submenu_page(
				'wp-scheduled-posts',
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
            $newValue = $_POST;
            // delete unwanted key
            unset($newValue['action'], $newValue['security']);

            // new update value
            $updatedValue = array_merge($oldValue, $newValue);
            if(!get_option(self::$optionGroupName)){
                add_option(self::$optionGroupName, $updatedValue);
            }else {
                update_option(self::$optionGroupName, $updatedValue);
            }
            print 'updated...';
            wp_die(); // this is required to terminate immediately and return a proper response
        }
		
		public static function plugin_setting_page() {
			?>
				<div class="wrap">
                    <h1><?php esc_html_e('WP Settings API', 'wsi'); ?></h1>
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
                            </div>
                        </form>
                    </div>
		        </div>
			<?php
		}

		public static function setSection( $section ){
			// Bail if not array.
			if ( ! is_array( $section ) ) {
				return false;
            }
            
            self::$sections_array[] = $section;

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
        public function callback_radio( $args ) {
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


        public function callback_select( $args ){
            $value = self::get_value($args);
            $field = $markup = '';
            $field .= sprintf( '<select id="%1$s" class="%1$s-select" name="%1$s" multiple>', $args['id'] );
                if(is_array($args['options'])){
                    $field .='<option value=""></option>';
                    foreach($args['options'] as $key => $option){
                        $field .= sprintf( '<option value="%1$s" '.(in_array($key, $value) ? 'selected' : '').'>%2$s</option>',$key, $option);
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

        public function callback_scheduled( $args ){
           
            $field = $markup = '';
            ?>

            <tr>
                <th scope="row">
                    <label for="'.$args['id'].'"><?php print $args['title']; ?></label>
                </th>
                <td>
                   <?php wpscp_qsw_manage_scheduled_markup();  ?>
                </td>
            </tr>
            <?php
        }
        



       
        public static function add_nav_tabs(){
            $tabNavCounter = 0;
            $allSections = apply_filters( 'wpscp_setup_wizard_fields', self::$sections_array );
            foreach ($allSections as $section) :
                ?>
                    <li class="nav-item<?php print ($tabNavCounter == 0 ? ' wpscp-step-complete tab-active' : ''); ?>">
                        <a href="#<?php print (isset($section['id']) ? $section['id'] : 'default-nav'); ?>" rel="nofollow">
                            <span class="text"><?php print (isset($section['title']) ? $section['title'] : ''); ?></span>
                            <span class="number"><?php print (isset($section['sub_title']) ? $section['sub_title'] : ''); ?></span>
                            
                        </a>
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
                <div class="tab-content<?php print ($tabContentCounter == 0 ? ' wpscp-step-complete active' : ''); ?>" id="<?php print (isset($section['id']) ? $section['id'] : 'default-nav'); ?>">
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
                    <?php
                        print '<div class="wpscp-button-wrap">';
                        // navigation control
                        if($tabContentCounter <= 0) {
                            print '<a href="#" class="btn wpscp-next-option">Next</a>';
                        }else if($tabContentCounter >= 1 && count($allSections) != ($tabContentCounter + 1)){
                            print '<a href="#" class="btn wpscp-prev-option">Previous</a>';
                            print '<a href="#" class="btn wpscp-next-option">Next</a>';
                        }else {
                            print '<a href="#" class="btn wpscp-prev-option">Previous</a>';
                            print '<input id="quicksetupwizardsave" type="button" value="Save Changes" class="button button-primary" />';
                        }
                        print '</div>';
                    ?>
                </div>
            <?php
            $tabContentCounter++;
            endforeach;
        }


	}
	wpscpSetupWizard::load();
}
