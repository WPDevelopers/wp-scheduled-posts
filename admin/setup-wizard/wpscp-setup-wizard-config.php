<?php

include plugin_dir_path( __FILE__ ) . 'inc/wpscp-setup-wizard-helper-functions.php';
include plugin_dir_path( __FILE__ ) . 'inc/class-wpscp-setup-wizard.php';

// step one
wpscpSetupWizard::setSection(array(
	'id'    	=> 'wpscp_step_one_settings',
	'title' 	=> __( '01', 'wp-scheduled-posts' ),
	'sub_title'	=> __('Step', 'wp-scheduled-posts'),
	'fields'	=> array(
		array(
			'id'      		=> 'getting_started',
            'title'   		=> __( 'Getting Started', 'wp-scheduled-posts' ),
			'type'    		=> 'welcome',
		),
	)
));
// step two
wpscpSetupWizard::setSection(array(
	'id'    	=> 'wpscp_step_two_settings',
	'title' 	=> __( '02', 'wp-scheduled-posts' ),
	'sub_title'	=> __('Step', 'wp-scheduled-posts'),
	'fields'	=> array(
		array(
			'id'      		=> 'show_dashboard_widget',
            'title'   		=> __( 'Dashboard Widget Show/Hide', 'wp-scheduled-posts' ),
			'desc'			=> __( 'Show Scheduled Posts in  Widget', 'wp-scheduled-posts' ),
			'type'    		=> 'checkbox',
		),
		array(
			'id'      		=> 'show_in_front_end_adminbar',
            'title'   		=> __( 'Sitewide Admin Bar Widget Show/Hide', 'wp-scheduled-posts' ),
			'desc'			=> __( 'Show Scheduled Posts in Sitewide Admin Bar', 'wp-scheduled-posts' ),
			'type'    		=> 'checkbox',
		),
		array(
			'id'      		=> 'show_in_adminbar',
            'title'   		=> __( 'Admin Bar Widget Show/Hide', 'wp-scheduled-posts' ),
			'desc'			=> __( 'Show Scheduled Posts in Admin Bar', 'wp-scheduled-posts' ),
			'type'    		=> 'checkbox',
		),
		array(
			'id'      		=> 'prevent_future_post',
            'title'   		=> __( 'Publish Post Button Show/Hide', 'wp-scheduled-posts' ),
			'desc'			=> __( 'Show Publish Post Immediately Button', 'wp-scheduled-posts' ),
			'type'    		=> 'checkbox',
		),
	)
));


wpscpSetupWizard::setSection(array(
	'id'    	=> 'wpscp_step_three_settings',
	'title' 	=> __( '03', 'wp-scheduled-posts' ),
	'sub_title'	=> __('Step', 'wp-scheduled-posts'),
	'fields'	=> array(
		array(
			'id'      		=> 'allow_post_types',
            'title'   		=> __( 'Post Types Support', 'wp-scheduled-posts' ),
			'type'    		=> 'select',
			'options'		=> get_post_types('','names')
		),
		array(
			'id'      		=> 'allow_categories',
            'title'   		=> __( 'Show Categories', 'wp-scheduled-posts' ),
			'type'    		=> 'select',
			'options'    	=> wpscp_get_all_category(),
		),
		array(
			'id'      		=> 'allow_user_role',
            'title'   		=> __( 'Allow users', 'wp-scheduled-posts' ),
			'type'    		=> 'select',
			'options'    	=> array(
				'administrator'	=> 'administrator',
				'editor'		=> 'editor',
				'author'		=> 'author',
				'contributor'	=> 'contributor',
				'subscriber'	=> 'subscriber',
			),
		),
	)
));

// Pro Feature
wpscpSetupWizard::setSection(array(
	'id'    	=> 'wpscp_step_pro_settings',
	'title' 	=> __( 'Pro', 'wp-scheduled-posts' ),
	'sub_title'	=> __('Step', 'wp-scheduled-posts'),
	'fields'	=> array(
		array(
			'id'      		=> 'pro_step',
            'title'   		=> __( 'Pro Feature', 'wp-scheduled-posts' ),
			'type'    		=> 'profeature',
		),
	)
));