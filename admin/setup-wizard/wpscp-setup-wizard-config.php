<?php

include plugin_dir_path( __FILE__ ) . 'inc/class-wpscp-setup-wizard.php';


wpscpSetupWizard::setSection(array(
	'id'    	=> 'wpsi_test_group_setting',
    'title' 	=> __( 'Basic Settings', 'wpsi' ),
    'sub_title' => __('basic sub title', 'wpsi'),
	'page'		=> 'wpsci_tab_one',
	'fields'	=> array(
		array(
			'id'      		=> 'wpsp_twitter_consumer_key',
            'title'   		=> __( 'Text Input', 'wpsi' ),
            'sub_title'     => __('field sub title', 'wpsi'),
			'desc'			=> 'this is description',
			'default'		=> 'Default Value',
			'placeholder'	=> 'Placeholder',
			'type'    		=> 'text',
		),
		array(
			'id'      		=> 'wpsi_test_field_two',
			'title'   		=> __( 'textarea Input', 'wpsi' ),
			'desc'			=> 'this is description',
			'placeholder'	=> 'Placeholder',
			'type'    		=> 'textarea',
		),
	)
));




wpscpSetupWizard::setSection(array(
	'id'    	=> 'wpsi_test_group_setting_two',
    'title' 	=> __( 'Basic Settings Two', 'wpsi' ),
    'sub_title' => __('basic sub title', 'wpsi'),
	'page'		=> 'wpsci_tab_two',
	'fields'	=> array(
		array(
			'id'      		=> 'wpsi_test_field_three',
			'title'   		=> __( 'Text Input', 'wpsi' ),
			'desc'			=> 'this is simple text field',
			'default'		=> 'this is default value',
			'placeholder'	=> 'this is placeholder',
			'type'    		=> 'text',
		),
		array(
			'id'      		=> 'wpsi_test_field_four',
			'title'   		=> __( 'textarea Input', 'wpsi' ),
			'desc'			=> 'this is simple text field',
			'type'    		=> 'textarea',
		),
	)
));





