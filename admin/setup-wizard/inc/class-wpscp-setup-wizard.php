<?php
if( ! class_exists( 'wpscpSetupWizard' ) ){
	class wpscpSetupWizard {
        public static $sections_array = array();
        public static $optionGroupName = 'wp_simple_settings';
		public static function load(){
			// Hook it up.
			add_action( 'admin_init', array( __CLASS__, 'admin_init' ) );
			// Menu.
            add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ) );
            
            // tab builder
            add_action( 'admin_enqueue_scripts', array(__CLASS__, 'setup_wizard_scripts') );
            add_action('wpscp_nav_tabs', array(__CLASS__, 'add_nav_tabs'));
            add_action('wpscp_tabs_content', array(__CLASS__, 'add_tab_content'));
        }
        
        public static function setup_wizard_scripts(){
            wp_enqueue_style( 'wpscp-setup-wizard', WPSCP_ADMIN_URL . 'setup-wizard/assets/css/wpscp-setup-wizard.css' );
            wp_enqueue_script( 'wpscp-setup-wizard', WPSCP_ADMIN_URL . 'setup-wizard/assets/js/wpscp-setup-wizard.js', array('jquery'), null, false );
        }

		// add admin page
		public static function admin_menu(){
			add_options_page(
				'WP Settings API',
				'WP Settings API',
				'manage_options',
				'wp_simple_settings',
				array(  __CLASS__, 'plugin_setting_page' )
			);
		}
		
		public static function plugin_setting_page() {
			?>
				<div class="wrap">
                    <h1><?php esc_html_e('WP Settings API', 'wsi'); ?></h1>
                    <div class="wpscp-setup-wizard">
                        <form method="post" action="options.php">
                            <?php 
                                settings_fields(self::$optionGroupName);
                            ?>
                            <ul class="tabs-nav">
                                <?php do_action('wpscp_nav_tabs'); ?>
                            </ul>
                            <div class="tabs-content">
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
			return (get_option( $args['id'] ) != '') ? get_option( $args['id'] ) :  (isset($args['default']) ? $args['default'] : '');
		}

		public static function get_field_description($args){
			if ( ! empty( $args['desc'] ) ) {
				$desc = sprintf( '<p class="description">%s</p>', $args['desc'] );
			} else {
				$desc = '';
			}

			return $desc;
		}

		public static function callback_text($args) {
			$value = esc_attr( self::get_value($args) );
			$size  = isset( $args['size'] ) && ! is_null( $args['size'] ) ? $args['size'] : 'regular';
			$type  = isset( $args['type'] ) ? $args['type'] : 'text';

			$html  = sprintf( '<input type="%1$s" class="%2$s-text" name="%3$s" value="%4$s" placeholder="%5$s"/>', $type, $size, $args['id'], $value, $args['placeholder'] );
			$html .= self::get_field_description( $args );

			echo $html;
		}

		public static function callback_textarea( $args ) {

			$value = esc_textarea( self::get_value($args) );
			$size  = isset( $args['size'] ) && ! is_null( $args['size'] ) ? $args['size'] : 'regular';

			$html  = sprintf( '<textarea rows="5" cols="55" class="%1$s-text" id="%2$s" name="%3$s" placeholder="%4$s">%5$s</textarea>', $size, $args['id'], $args['id'], $args['placeholder'], $value );
			$html .= self::get_field_description( $args );

			echo $html;
		}




       
        public static function add_nav_tabs(){
            $tabNavCounter = 0;
            $allSections = apply_filters( 'wpscp_setup_wizard_fields', self::$sections_array );
            foreach ($allSections as $section) :
                ?>
                    <li class="nav-item <?php print ($tabNavCounter == 0 ? 'tab-active' : ''); ?>">
                        <a href="#<?php print (isset($section['id']) ? $section['id'] : 'default-nav'); ?>" rel="nofollow">
                            <?php print (isset($section['title']) ? $section['title'] : ''); ?>
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
                <div class="tab-content<?php print ($tabContentCounter == 0 ? ' active' : ''); ?>" id="<?php print (isset($section['id']) ? $section['id'] : 'default-nav'); ?>">
                    <?php 
                        do_settings_sections($section['page']);
                        // navigation control
                        if($tabContentCounter <= 0) {
                            print '<a href="#" class="btn btn-next-option">Next Option</a>';
                        }else if($tabContentCounter >= 1 && count($allSections) != ($tabContentCounter + 1)){
                            print '<a href="#" class="btn btn-prev-option">Prev Option</a>';
                            print '<a href="#" class="btn btn-next-option">Next Option</a>';
                        }else {
                            print '<a href="#" class="btn btn-prev-option">Prev Option</a>';
                            submit_button();
                        }
                    ?>
                    
                </div>
            <?php
            $tabContentCounter++;
            endforeach;
        }
	
		public static function admin_init(){
            $allSections = apply_filters( 'wpscp_setup_wizard_fields', self::$sections_array );
			foreach ($allSections as $section) {
				$page = $section['page'];
                $field_section = $section['id'];
				add_settings_section( 
					$section['id'], 
					$section['title'], 
					null, 
					$page 
                );
               
                if(isset($section['fields']) && is_array($section['fields'])){
                    foreach ( $section['fields'] as $field ) {
                        $args = array(
                            'id'            => $field['id'],
                            'title'   		=> $field['title'],
                            'sub_title'		=> (isset($field['sub_title']) ? $field['sub_title'] : ''),
                            'desc'			=> (isset($field['desc']) ? $field['desc'] : ''),
                            'default'		=> (isset($field['default']) ? $field['default'] : ''),
                            'placeholder'	=> (isset($field['placeholder']) ? $field['placeholder'] : ''),
                            'type'    		=> $field['type'],
                        );

                        add_settings_field(
                            $field['id'],
                            $field['title'],
                            array(__CLASS__, 'callback_' . $field['type'] ),
                            $page,
                            $field_section,
                            $args
                        );
                        register_setting( self::$optionGroupName, $field['id']);
                    }
                }
            }
		}


	}
	wpscpSetupWizard::load();
}
