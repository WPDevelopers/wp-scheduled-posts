<div class="wpsp-integ-wrapper wpsp_nav_tab_content social-integrations" id="wpsp-wpsp_integ">
	<!-- social tabs -->
	<div class="wpscp-social-tab">
		<ul class="wp-tab-bar">
			<li class="wp-tab-active">
				<a href="#facebook">
					<img src="<?php print plugin_dir_url(__FILE__) . './../assets/images/icon-facebook-small.png'; ?>" alt="<?php esc_attr_e('icon', 'wp-scheduled-posts-pro'); ?>">
					<?php
					_e('Facebook', 'wp-scheduled-posts-pro');
					?>
				</a>
			</li>
			<li>
				<a href="#twitter">
					<img src="<?php print plugin_dir_url(__FILE__) . './../assets/images/icon-twitter-small.png'; ?>" alt="<?php esc_attr_e('icon', 'wp-scheduled-posts-pro'); ?>">
					<?php
					_e('Twitter', 'wp-scheduled-posts-pro');
					?>
				</a>
			</li>
			<li>
				<a href="#linkedin">
					<img src="<?php print plugin_dir_url(__FILE__) . './../assets/images/icon-linkedin-small.png'; ?>" alt="<?php esc_attr_e('icon', 'wp-scheduled-posts-pro'); ?>">
					<?php
					_e('LinkedIn', 'wp-scheduled-posts-pro');
					?>
				</a>
			</li>
			<li>
				<a href="#pinterest">
					<img src="<?php print plugin_dir_url(__FILE__) . './../assets/images/icon-pinterest-small.png'; ?>" alt="<?php esc_attr_e('icon', 'wp-scheduled-posts-pro'); ?>">
					<?php
					_e('Pinterest', 'wp-scheduled-posts-pro');
					?>
				</a>
			</li>
		</ul>
		<div class="wp-tab-panel" id="facebook">
			<?php
			$facebookStatus = get_option('wpsp_facebook_integration_status');
			?>
			<div class="wpscp-social-tab__item-header wpscp-social-tab__item-header--facebook">
				<div class="entry-icon">
					<img src="<?php print plugin_dir_url(__FILE__) . './../assets/images/icon-facebook.png'; ?>" alt="<?php esc_attr_e('icon', 'wp-scheduled-posts-pro'); ?>">
				</div>
				<div class="entry-content">
					<h3><?php _e('Facebook', 'wp-scheduled-posts-pro'); ?></h3>
					<p><?php _e('You can enable/disable facebook social share. For details on facebook configuration, check out this', 'wp-scheduled-posts-pro'); ?> <a class="docs" href="https://wpdeveloper.net/docs/share-scheduled-posts-facebook/"" target=" _blank"><?php esc_html_e('Doc', 'wp-schedule-posts'); ?></a></p>
				</div>
				<div class="entry-control">
					<div class="checkbox-toggle">
						<form method="post">
							<input type="checkbox" class="wpsp_field_activate" name="wpsp_facebook_integration_status" <?php checked($facebookStatus, 'on'); ?>>
							<svg class="is_checked" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 426.67 426.67">
								<path d="M153.504 366.84c-8.657 0-17.323-3.303-23.927-9.912L9.914 237.265c-13.218-13.218-13.218-34.645 0-47.863 13.218-13.218 34.645-13.218 47.863 0l95.727 95.727 215.39-215.387c13.218-13.214 34.65-13.218 47.86 0 13.22 13.218 13.22 34.65 0 47.863L177.435 356.928c-6.61 6.605-15.27 9.91-23.932 9.91z" />
							</svg>
							<svg class="is_unchecked" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 212.982 212.982">
								<path d="M131.804 106.49l75.936-75.935c6.99-6.99 6.99-18.323 0-25.312-6.99-6.99-18.322-6.99-25.312 0L106.49 81.18 30.555 5.242c-6.99-6.99-18.322-6.99-25.312 0-6.99 6.99-6.99 18.323 0 25.312L81.18 106.49 5.24 182.427c-6.99 6.99-6.99 18.323 0 25.312 6.99 6.99 18.322 6.99 25.312 0L106.49 131.8l75.938 75.937c6.99 6.99 18.322 6.99 25.312 0 6.99-6.99 6.99-18.323 0-25.313l-75.936-75.936z" fill-rule="evenodd" clip-rule="evenodd" />
							</svg>
						</form>
					</div>
				</div>
			</div>
			<div class="wpscp-social-tab__item-list">
				<?php
				do_action('wpscp_social_profile_facebook_notice');
				do_action('wpscp_social_profile_template_list_view',  array(
					'social_profile'	=> 'facebook',
					'status'			=> $facebookStatus
				));
				?>
			</div>
			<!-- temp account add it will be remove after approve real app -->
			<button data-type="facebook" class="wpscp-social-tab__btn wpscp-social-tab__btn--facebook wpscp-social-tab__btn--temp-addnew-profile">
				<img src="<?php print plugin_dir_url(__FILE__) . './../assets/images/icon-facebook.png'; ?>" alt="<?php esc_attr_e('icon', 'wp-scheduled-posts-pro'); ?>">
				<?php esc_html_e('Add New Profile', 'wp-scheduled-posts-pro'); ?>
			</button>
		</div>
		<div class="wp-tab-panel" id="twitter" style="display: none;">
			<?php
			$twitterStatus = get_option('wpsp_twitter_integration_status');
			?>
			<div class="wpscp-social-tab__item-header wpscp-social-tab__item-header--twitter">
				<div class="entry-icon">
					<img src="<?php print plugin_dir_url(__FILE__) . './../assets/images/icon-twitter.png'; ?>" alt="<?php esc_attr_e('icon', 'wp-scheduled-posts-pro'); ?>">
				</div>
				<div class="entry-content">
					<h3><?php _e('Twitter', 'wp-scheduled-posts-pro'); ?></h3>
					<p><?php _e('You can enable/disable twitter social share. For details on twitter configuration, check out this', 'wp-scheduled-posts-pro'); ?> <a class="docs" href="https://wpdeveloper.net/docs/automatically-tweet-wordpress-posts/" target="_blank"><?php esc_html_e('Doc', 'wp-schedule-posts'); ?></a></p>
				</div>
				<div class="entry-control">
					<div class="checkbox-toggle">
						<form method="post">
							<input type="checkbox" class="wpsp_field_activate" name="wpsp_twitter_integration_status" <?php checked($twitterStatus, 'on'); ?>>
							<svg class="is_checked" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 426.67 426.67">
								<path d="M153.504 366.84c-8.657 0-17.323-3.303-23.927-9.912L9.914 237.265c-13.218-13.218-13.218-34.645 0-47.863 13.218-13.218 34.645-13.218 47.863 0l95.727 95.727 215.39-215.387c13.218-13.214 34.65-13.218 47.86 0 13.22 13.218 13.22 34.65 0 47.863L177.435 356.928c-6.61 6.605-15.27 9.91-23.932 9.91z" />
							</svg>
							<svg class="is_unchecked" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 212.982 212.982">
								<path d="M131.804 106.49l75.936-75.935c6.99-6.99 6.99-18.323 0-25.312-6.99-6.99-18.322-6.99-25.312 0L106.49 81.18 30.555 5.242c-6.99-6.99-18.322-6.99-25.312 0-6.99 6.99-6.99 18.323 0 25.312L81.18 106.49 5.24 182.427c-6.99 6.99-6.99 18.323 0 25.312 6.99 6.99 18.322 6.99 25.312 0L106.49 131.8l75.938 75.937c6.99 6.99 18.322 6.99 25.312 0 6.99-6.99 6.99-18.323 0-25.313l-75.936-75.936z" fill-rule="evenodd" clip-rule="evenodd" />
							</svg>
						</form>
					</div>
				</div>
			</div>
			<div class="wpscp-social-tab__item-list">
				<?php
				do_action('wpscp_social_profile_twitter_notice');
				do_action('wpscp_social_profile_template_list_view',  array(
					'social_profile'	=> 'twitter',
					'status'			=> $twitterStatus
				));
				?>
			</div>
			<!-- temp account add it will be remove after approve real app -->
			<button data-type="twitter" class="wpscp-social-tab__btn wpscp-social-tab__btn--twitter wpscp-social-tab__btn--temp-addnew-profile">
				<img src="<?php print plugin_dir_url(__FILE__) . './../assets/images/icon-twitter.png'; ?>" alt="<?php esc_attr_e('icon', 'wp-scheduled-posts-pro'); ?>">
				<?php esc_html_e('Add New Profile', 'wp-scheduled-posts-pro'); ?>
			</button>
		</div>
		<div class="wp-tab-panel" id="linkedin" style="display: none;">
			<?php
			$linkedinStatus = get_option('wpsp_linkedin_integration_status');
			?>
			<div class="wpscp-social-tab__item-header wpscp-social-tab__item-header--linkedin">
				<div class="entry-icon">
					<img src="<?php print plugin_dir_url(__FILE__) . './../assets/images/icon-linkedin.png'; ?>" alt="<?php esc_attr_e('icon', 'wp-scheduled-posts-pro'); ?>">
				</div>
				<div class="entry-content">
					<h3><?php _e('LinkedIn', 'wp-scheduled-posts-pro'); ?></h3>
					<p><?php _e('You can enable/disable LinkedIn social share. For details on LinkedIn configuration, check out this', 'wp-scheduled-posts-pro'); ?> <a class="docs" href="https://wpdeveloper.net/docs/share-wordpress-posts-on-linkedin/" target="_blank"><?php esc_html_e('Doc', 'wp-schedule-posts'); ?></a></p>
					<p class="docinfo"><a href="https://www.linkedin.com/developers/" target="_blank"><strong><?php esc_html_e('Click here', 'wp-schedule-posts'); ?></strong></a> <?php esc_html_e('to Retrieve Your API Keys from your LinkedIn account', 'wp-schedule-posts'); ?></p>
				</div>
				<div class="entry-control">
					<div class="checkbox-toggle">
						<form method="post">
							<input type="checkbox" class="wpsp_field_activate" name="wpsp_linkedin_integration_status" <?php checked($linkedinStatus, 'on'); ?>>
							<svg class="is_checked" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 426.67 426.67">
								<path d="M153.504 366.84c-8.657 0-17.323-3.303-23.927-9.912L9.914 237.265c-13.218-13.218-13.218-34.645 0-47.863 13.218-13.218 34.645-13.218 47.863 0l95.727 95.727 215.39-215.387c13.218-13.214 34.65-13.218 47.86 0 13.22 13.218 13.22 34.65 0 47.863L177.435 356.928c-6.61 6.605-15.27 9.91-23.932 9.91z" />
							</svg>
							<svg class="is_unchecked" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 212.982 212.982">
								<path d="M131.804 106.49l75.936-75.935c6.99-6.99 6.99-18.323 0-25.312-6.99-6.99-18.322-6.99-25.312 0L106.49 81.18 30.555 5.242c-6.99-6.99-18.322-6.99-25.312 0-6.99 6.99-6.99 18.323 0 25.312L81.18 106.49 5.24 182.427c-6.99 6.99-6.99 18.323 0 25.312 6.99 6.99 18.322 6.99 25.312 0L106.49 131.8l75.938 75.937c6.99 6.99 18.322 6.99 25.312 0 6.99-6.99 6.99-18.323 0-25.313l-75.936-75.936z" fill-rule="evenodd" clip-rule="evenodd" />
							</svg>
						</form>
					</div>
				</div>
			</div>
			<div class="wpscp-social-tab__item-list">
				<?php
				do_action('wpscp_social_profile_template_list_view',  array(
					'social_profile'	=> 'linkedin',
					'status'			=> $linkedinStatus
				));
				?>
			</div>
			<!-- <button class="wpscp-social-tab__btn wpscp-social-tab__btn--linkedin wpscp-social-tab__btn--addnew-profile">
				<img src="<?php //print plugin_dir_url( __FILE__ ) . './../assets/images/icon-linkedin.png'; 
							?>" alt="<?php //esc_attr_e( 'icon', 'wp-scheduled-posts-pro' ); 
										?>">
				<?php //esc_html_e( 'Add New Profile', 'wp-scheduled-posts-pro' ); 
				?>
			</button> -->
			<!-- temp account add it will be remove after approve real app -->
			<button data-type="linkedin" class="wpscp-social-tab__btn wpscp-social-tab__btn--linkedin wpscp-social-tab__btn--temp-addnew-profile">
				<img src="<?php print plugin_dir_url(__FILE__) . './../assets/images/icon-linkedin.png'; ?>" alt="<?php esc_attr_e('icon', 'wp-scheduled-posts-pro'); ?>">
				<?php esc_html_e('Add New Profile', 'wp-scheduled-posts-pro'); ?>
			</button>
		</div>
		<div class="wp-tab-panel" id="pinterest" style="display: none;">
			<?php
			$pinterestStatus = get_option('wpsp_pinterest_integration_status');
			?>
			<div class="wpscp-social-tab__item-header wpscp-social-tab__item-header--pinterest">
				<div class="entry-icon">
					<img src="<?php print plugin_dir_url(__FILE__) . './../assets/images/icon-pinterest.png'; ?>" alt="<?php esc_attr_e('icon', 'wp-scheduled-posts-pro'); ?>">
				</div>
				<div class="entry-content">
					<h3><?php _e('Pinterest', 'wp-scheduled-posts-pro'); ?></h3>
					<p><?php _e('You can enable/disable pinterest social share. For details on pinterest configuration, check out this', 'wp-scheduled-posts-pro'); ?> <a class="docs" href="https://wpdeveloper.net/docs/wordpress-posts-on-pinterest/" target="_blank"><?php esc_html_e('Doc', 'wp-schedule-posts'); ?></a></p>
					<p class="docinfo"><a href="https://developers.pinterest.com/" target="_blank"><strong><?php esc_html_e('Click here', 'wp-schedule-posts'); ?></strong></a> <?php esc_html_e('to Retrieve Your API Keys from your Pinterest account', 'wp-schedule-posts'); ?></p>
				</div>
				<div class="entry-control">
					<div class="checkbox-toggle">
						<form method="post">
							<input type="checkbox" class="wpsp_field_activate" name="wpsp_pinterest_integration_status" <?php checked($pinterestStatus, 'on'); ?>>
							<svg class="is_checked" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 426.67 426.67">
								<path d="M153.504 366.84c-8.657 0-17.323-3.303-23.927-9.912L9.914 237.265c-13.218-13.218-13.218-34.645 0-47.863 13.218-13.218 34.645-13.218 47.863 0l95.727 95.727 215.39-215.387c13.218-13.214 34.65-13.218 47.86 0 13.22 13.218 13.22 34.65 0 47.863L177.435 356.928c-6.61 6.605-15.27 9.91-23.932 9.91z" />
							</svg>
							<svg class="is_unchecked" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 212.982 212.982">
								<path d="M131.804 106.49l75.936-75.935c6.99-6.99 6.99-18.323 0-25.312-6.99-6.99-18.322-6.99-25.312 0L106.49 81.18 30.555 5.242c-6.99-6.99-18.322-6.99-25.312 0-6.99 6.99-6.99 18.323 0 25.312L81.18 106.49 5.24 182.427c-6.99 6.99-6.99 18.323 0 25.312 6.99 6.99 18.322 6.99 25.312 0L106.49 131.8l75.938 75.937c6.99 6.99 18.322 6.99 25.312 0 6.99-6.99 6.99-18.323 0-25.313l-75.936-75.936z" fill-rule="evenodd" clip-rule="evenodd" />
							</svg>
						</form>
					</div>
				</div>
			</div>
			<div class="wpscp-social-tab__item-list">
				<?php
				do_action('wpscp_social_profile_template_list_view',  array(
					'social_profile'	=> 'pinterest',
					'status'			=> $pinterestStatus
				));
				?>
			</div>
			<!-- <button class="wpscp-social-tab__btn wpscp-social-tab__btn--pinterest wpscp-social-tab__btn--addnew-profile">
				<img src="<?php //print plugin_dir_url( __FILE__ ) . './../assets/images/icon-pinterest-small.png'; 
							?>" alt="<?php esc_attr_e('icon', 'wp-scheduled-posts-pro'); ?>">
				<?php //esc_html_e( 'Add New Profile', 'wp-scheduled-posts-pro' ); 
				?>
			</button> -->
			<!-- temp account add it will be remove after approve real app -->
			<button data-type="pinterest" class="wpscp-social-tab__btn wpscp-social-tab__btn--pinterest wpscp-social-tab__btn--temp-addnew-profile">
				<img src="<?php print plugin_dir_url(__FILE__) . './../assets/images/icon-pinterest-small.png'; ?>" alt="<?php esc_attr_e('icon', 'wp-scheduled-posts-pro'); ?>">
				<?php esc_html_e('Add New Profile', 'wp-scheduled-posts-pro'); ?>
			</button>
		</div>
	</div>
	<!-- /.social tabs -->
</div>