<!-- Topbar -->
<div class="wpsp_top_bar_wrapper">
	<div class="wpsp_top_bar_logo">
		<img src="<?php echo esc_url(WPSP_ASSETS_URI . 'images/wpsp-icon.svg'); ?>" alt="<?php esc_attr_e('logo', 'wp-schduled-posts'); ?>">
	</div>
	<div class="wpsp_top_bar_heading">
		<h2 class="wpsp_topbar_title"><?php esc_html_e('WP Scheduled Posts', 'wp-scheduled-posts'); ?></h2>
		<p class="wpsp_topbar_version_name">
			<span>
				<span class="free"><?php echo esc_html__('Core Version: ', 'wp-scheduled-posts') . WPSP_VERSION; ?></span>
				<?php if (defined('WPSCP_PRO_VERSION')) : ?>
					<br><span class="pro"><?php echo esc_html__('Pro Version: ', 'wp-scheduled-posts') . WPSCP_PRO_VERSION; ?></span>
				<?php endif; ?>
			</span>
		</p>
	</div>
</div>

<!-- Nav Tab -->
<div class="wpsp_top_nav_link_wrapper">
	<ul>
		<?php
		// if wpsp tab is not in slug by default active general 
		if (!isset($_GET['wpsptab'])) {
			$gen_active = 'wpsp_top_nav_tab_active';
		} else {
			$tab = isset($_GET['wpsptab']);
			if ($tab == 'gen') {
				$gen_active = 'wpsp_top_nav_tab_active';
			}
		}
		?>
		<li data-tab="wpsp_gen"><a href="#wpsp_gen" class="<?php echo $gen_active; ?>"><?php _e('General', 'wp-scheduled-posts') ?></a></li>
		<li data-tab="wpsp_email"><a href="#wpsp_email"><?php _e('Email Notify', 'wp-scheduled-posts') ?></a></li>
		<li data-tab="wpsp_integ"><a href="#wpsp_integ"><?php _e('Social Profile', 'wp-scheduled-posts'); ?></a></li>
		<li data-tab="wpsp_social_templates"><a href="#wpsp_social_templates"><?php _e('Social Templates', 'wp-scheduled-posts'); ?></a></li>
		<?php
		if (class_exists('WpScp_Pro')) {
			do_action('wpsp_pro_topbar_menu');
		}
		?>
	</ul>
</div>