<div class="wpsp-integ-wrapper wpsp_nav_tab_content" id="wpsp-wpsp_social_templates">
	<!-- twitter -->
	<div class="wpsp-integ-item_section wpsp-integ-active">
		<div class="wpsp-integ-bar wpsp-integ-active">
			<input type="radio" checked="checked" class="wpsp_field_activate">
			<h3><?php esc_html_e('Twitter Tweet Settings', 'wp-scheduled-posts-pro'); ?></h3>
			<p><?php esc_html_e('To configure the Twitter Tweet Settings, check out this', 'wp-scheduled-posts-pro'); ?> <a class="docs" href="https://wpdeveloper.net/docs/automatically-tweet-wordpress-posts/" target="_blank"><?php esc_html_e('Doc', 'wp-scheduled-posts-pro'); ?></a></p>
		</div>
		<div class="wpsp-integ-content wpsp-social-integ-content" style="display: block;">
			<?php
			$wpscp_current_template_structure = get_option('wpscp_twitter_template_structure');
			?>
			<!-- twitter form settings -->
			<form action="<?php print admin_url('admin-post.php'); ?>" method="post" class="wpscp-twitter-template">
				<input type="hidden" name="action" value="twitter_template_structure">
				<tr>
					<td colspan="2" align="left">
						<div class="wpsp_form_group">
							<label for=""><?php esc_html_e('Tweet Template Settings', 'wp-scheduled-posts-pro'); ?></label>
							<input type="text" name="wpscp_twitter_template_settings" class="wpsp_field_activate" value="<?php print($wpscp_current_template_structure != "" ? $wpscp_current_template_structure : '{title}{content}{url}{tags}'); ?>" placeholder="<?php esc_attr_e('Template Settings', 'wp-scheduled-posts-pro'); ?>">
						</div>
					</td>
				</tr>
				<tr>
					<td>
						<p class="wpsp_integ_structure"><?php esc_html_e('Default Structure:', 'wp-scheduled-posts-pro'); ?> <span><?php print esc_html('{title}{content}{url}{tags}'); ?><span /></p>
					</td>
				</tr>
				<tr>
					<td colspan="2" align="left">
						<div class="wpsp_form_group">
							<label for=""><?php esc_html_e('Add Category as a tags', 'wp-scheduled-posts-pro'); ?></label>
							<input type="checkbox" name="wpscp_twitter_template_category_tags_support" class="wpsp_field_activate" value="yes" <?php checked('yes', get_option('wpscp_twitter_template_category_tags_support')); ?>><?php esc_html_e('Yes', 'wp-scheduled-posts-pro'); ?><br>
						</div>
					</td>
				</tr>
				<tr>
					<p></p>
				</tr>
				<tr>
					<td colspan="2" align="left">
						<div class="wpsp_form_group">
							<label for=""><?php esc_html_e('Show Post Thumbnail', 'wp-scheduled-posts-pro'); ?></label>
							<input type="checkbox" name="wpscp_twitter_template_thumbnail" class="wpsp_field_activate" value="yes" <?php checked('yes', get_option('wpscp_twitter_template_thumbnail')); ?>><?php esc_html_e('Yes', 'wp-scheduled-posts-pro'); ?><br>
						</div>
					</td>
				</tr>
				<tr>
					<p></p>
				</tr>
				<tr>
					<td colspan="2" align="left">
						<div class="wpsp_form_group">
							<label for=""><?php esc_html_e('Content Source', 'wp-scheduled-posts-pro'); ?></label>
							<?php
							$twitter_content_source = (get_option('wpscp_twitter_content_source') !== false ? get_option('wpscp_twitter_content_source') : 'excerpt');
							?>
							<input type="radio" class="wpsp_field_activate" name="wpscp_twitter_content_source" value="excerpt" <?php checked('excerpt', $twitter_content_source); ?>> <?php esc_html_e('Excerpt', 'wp-scheduled-posts-pro'); ?>
							<input type="radio" class="wpsp_field_activate" name="wpscp_twitter_content_source" value="content" <?php checked('content', $twitter_content_source); ?>> <?php esc_html_e('Content', 'wp-scheduled-posts-pro'); ?>
						</div>
					</td>
				</tr>
				<tr>
					<p></p>
				</tr>
				<tr>
					<td colspan="2" align="left">
						<div class="wpsp_form_group">
							<label for=""><?php esc_html_e('Tweet Limit', 'wp-scheduled-posts-pro'); ?></label>
							<input type="number" name="wpscp_twitter_tweet_limit" class="wpsp_number_field" min="1" max="280" value="<?php echo (get_option('wpscp_twitter_tweet_limit') !== false ? esc_attr(get_option('wpscp_twitter_tweet_limit')) : 280); ?>" placeholder="<?php esc_attr_e('280', 'wp-scheduled-posts-pro'); ?>" /> <?php esc_html_e('Maximum Limit: 280 character', 'wp-scheduled-posts-pro'); ?>
						</div>
					</td>
				</tr>
				<tr>
					<td colspan="2" align="left">
						<div class="wpsp_form_group">
							<input type="submit" class="wpsp_form_submit" name="wpscp_twitter_template_settings_submit" value="save">
						</div>
					</td>
				</tr>
			</form>

		</div>
	</div>

	<!-- facebook -->
	<div class="wpsp-integ-item_section wpsp-integ-active">
		<div class="wpsp-integ-bar wpsp-integ-active">
			<input type="radio" checked="checked">
			<h3><?php esc_html_e('Facebook Status Settings', 'wp-scheduled-posts-pro'); ?></h3>
			<p><?php esc_html_e('To configure the Facebook Share Settings, check out this', 'wp-scheduled-posts-pro'); ?> <a class="docs" href="https://wpdeveloper.net/docs/share-scheduled-posts-facebook/" target="_blank"><?php esc_html_e('Doc', 'wp-scheduled-posts-pro'); ?></a></p>
		</div>
		<div class="wpsp-integ-content wpsp-social-integ-content" style="display: block;">

			<!-- facebook form settings -->
			<?php
			$facebook_content_type = (get_option('wpscp_pro_fb_content_type') !== false ? get_option('wpscp_pro_fb_content_type') : 'link');
			?>
			<form action="<?php print admin_url('admin-post.php'); ?>" method="post" class="wpscp-facebook-template">
				<input type="hidden" name="action" value="facebook_template_structure">
				<input type="hidden" name="_wpnonce" value="<?php print wp_create_nonce('facebook_template_structure'); ?>">
				<tr>
					<td colspan="2" align="left">
						<div class="wpsp_form_group">
							<label for=""><?php esc_html_e('Facebook Meta Data', 'wp-scheduled-posts-pro'); ?></label>
							<input type="checkbox" class="wpsp_field_activate" name="wpscp_pro_fb_meta_head_support" value="yes" <?php checked('yes', get_option('wpscp_pro_fb_meta_head_support')); ?>><?php esc_html_e('Yes', 'wp-scheduled-posts-pro'); ?><br>
						</div>
					</td>
					<td>
						<span class="wpsp_form_description"><?php esc_html_e('Add Open Graph meta data to your site head section', 'wp-scheduled-posts-pro') ?> <br> <?php esc_html_e('and other social network use this data when your pages are shared.', 'wp-scheduled-posts-pro'); ?></span>
						<p></p>
					</td>
				</tr>
				<tr>
					<td colspan="2" align="left">
						<div class="wpsp_form_group">
							<label for=""><?php esc_html_e('Content Type', 'wp-scheduled-posts-pro'); ?></label>
							<input type="radio" class="wpsp_field_activate" name="wpscp_pro_fb_content_type" value="link" <?php checked('link', $facebook_content_type); ?>> <?php esc_html_e('Link', 'wp-scheduled-posts-pro'); ?>
							<input type="radio" class="wpsp_field_activate" name="wpscp_pro_fb_content_type" value="status" <?php checked('status', $facebook_content_type); ?>> <?php esc_html_e('Status', 'wp-scheduled-posts-pro'); ?>
							<input type="radio" class="wpsp_field_activate" name="wpscp_pro_fb_content_type" value="statusandlink" <?php checked('statusandlink', $facebook_content_type); ?>> <?php esc_html_e('Status + Link', 'wp-scheduled-posts-pro'); ?>
						</div>
					</td>
				</tr>
				<tr>
					<p></p>
				</tr>
				<tr>
					<td colspan="2" align="left">
						<div class="wpsp_form_group">
							<label for=""><?php esc_html_e('Add Category as a tags', 'wp-scheduled-posts-pro'); ?></label>
							<input type="checkbox" name="wpscp_pro_fb_template_category_tags_support" class="wpsp_field_activate" value="yes" <?php checked('yes', get_option('wpscp_pro_fb_template_category_tags_support')); ?>><?php esc_html_e('Yes', 'wp-scheduled-posts-pro'); ?><br>
						</div>
					</td>
				</tr>
				<tr>
					<p></p>
				</tr>
				<tr>
					<td colspan="2" align="left">
						<div class="wpsp_form_group">
							<label for=""><?php esc_html_e('Content Source', 'wp-scheduled-posts-pro'); ?></label>
							<?php
							$facebook_content_source = (get_option('wpscp_pro_fb_content_source') !== false ? get_option('wpscp_pro_fb_content_source') : 'content');
							?>
							<input type="radio" class="wpsp_field_activate" name="wpscp_pro_fb_content_source" value="excerpt" <?php checked('excerpt', $facebook_content_source); ?>> <?php esc_html_e('Excerpt', 'wp-scheduled-posts-pro'); ?>
							<input type="radio" class="wpsp_field_activate" name="wpscp_pro_fb_content_source" value="content" <?php checked('content', $facebook_content_source); ?>> <?php esc_html_e('Content', 'wp-scheduled-posts-pro'); ?>
						</div>
					</td>
				</tr>
				<tr>
					<p></p>
				</tr>
				<tr>
					<td colspan="2" align="left">
						<div class="wpsp_form_group">
							<?php
							$wpscp_pro_facebook_template_structure = get_option('wpscp_pro_facebook_template_structure');
							?>
							<label for=""><?php esc_html_e('Status Template Settings', 'wp-scheduled-posts-pro'); ?></label>
							<input type="text" name="wpscp_pro_facebook_template_structure" class="wpsp_field_activate" value="<?php ($wpscp_pro_facebook_template_structure != "" ? print $wpscp_pro_facebook_template_structure : print '{title}{content}{tags}'); ?>" placeholder="Template Settings">
						</div>
					</td>
				</tr>
				<tr>
					<td>
						<p class="wpsp_integ_structure"><?php esc_html_e('Default Structure:', 'wp-scheduled-posts-pro') ?> <span><?php print esc_html('{title}{content}{url}{tags}'); ?><span /></p>
					</td>
				</tr>
				<tr>
					<p></p>
				</tr>
				<tr>
					<td colspan="2" align="left">
						<div class="wpsp_form_group">
							<label for=""><?php esc_html_e('Status Limit', 'wp-scheduled-posts-pro'); ?></label>
							<input type="number" name="wpscp_pro_facebook_status_limit" class="wpsp_number_field" min="1" max="63206" value="<?php echo (get_option('wpscp_pro_facebook_status_limit') !== false ? esc_attr(get_option('wpscp_pro_facebook_status_limit')) : 63206); ?>" /> <?php esc_html_e('Maximum Limit: 63206 character', 'wp-scheduled-posts-pro'); ?>
						</div>
					</td>
				</tr>
				<tr>
					<td colspan="2" align="left">
						<div class="wpsp_form_group">
							<input type="submit" class="wpsp_form_submit" name="wpscp_facebook_template_settings_submit" value="save">
						</div>
					</td>
				</tr>
			</form>

		</div>
	</div>

	<!-- linkedin -->
	<div class="wpsp-integ-item_section wpsp-integ-active" style="border-top: 1px solid #f5f5f5;">
		<div class="wpsp-integ-bar wpsp-integ-active">
			<input type="radio" checked="checked">
			<h3><?php esc_html_e('Linkedin Status Settings', 'wp-scheduled-posts-pro'); ?></h3>
			<p><?php esc_html_e('To configure the Linkedin Share Settings, check out this', 'wp-scheduled-posts-pro'); ?> <a class="docs" href="https://wpdeveloper.net/docs/share-wordpress-posts-linkedin/"><?php esc_html_e('Doc', 'wp-scheduled-posts-pro'); ?></a></p>
		</div>
		<div class="wpsp-integ-content wpsp-social-integ-content" style="display: block;">
			<?php
			$linkedin_content_type = (get_option('wpscp_pro_linkedin_content_type') !== false ? get_option('wpscp_pro_linkedin_content_type') : 'link');
			$linkedin_status_limit = (get_option('wpscp_pro_linkedin_status_limit') !== false ? get_option('wpscp_pro_linkedin_status_limit') : 1300);
			?>
			<!-- linkedin form settings -->
			<form action="<?php print admin_url('admin-post.php'); ?>" method="post" class="wpscp-twitter-template">
				<input type="hidden" name="action" value="wpscppro_linkedin_template_structure">
				<input type="hidden" name="_wpnonce" value="<?php print wp_create_nonce('linkedin_template_structure'); ?>">
				<tr>
					<td colspan="2" align="left">
						<div class="wpsp_form_group">
							<label for=""><?php esc_html_e('Content Type', 'wp-scheduled-posts-pro'); ?></label>
							<input type="radio" class="wpsp_field_activate" name="wpscp_pro_linkedin_content_type" value="link" <?php checked('link', $linkedin_content_type); ?>> <?php esc_html_e('Link', 'wp-scheduled-posts-pro'); ?>
							<input type="radio" class="wpsp_field_activate" name="wpscp_pro_linkedin_content_type" value="status" <?php checked('status', $linkedin_content_type); ?>> <?php esc_html_e('Status', 'wp-scheduled-posts-pro'); ?>
						</div>
					</td>
				</tr>
				<tr>
					<p></p>
				</tr>
				<tr>
					<td colspan="2" align="left">
						<div class="wpsp_form_group">
							<label for=""><?php esc_html_e('Add Category as a tags', 'wp-scheduled-posts-pro'); ?></label>
							<input type="checkbox" name="wpscp_pro_liinkedin_template_category_tags_support" class="wpsp_field_activate" value="yes" <?php checked('yes', get_option('wpscp_pro_liinkedin_template_category_tags_support')); ?>>Yes<br>
						</div>
					</td>
				</tr>
				<tr>
					<p></p>
				</tr>
				<tr>
					<td colspan="2" align="left">
						<div class="wpsp_form_group">
							<label for=""><?php esc_html_e('Content Source', 'wp-scheduled-posts-pro'); ?></label>
							<?php
							$linkedin_content_source = (get_option('wpscp_pro_linkedin_content_source') !== false ? get_option('wpscp_pro_linkedin_content_source') : 'content');
							?>
							<input type="radio" class="wpsp_field_activate" name="wpscp_pro_linkedin_content_source" value="excerpt" <?php checked('excerpt', $linkedin_content_source); ?>> <?php esc_html_e('Excerpt', 'wp-scheduled-posts-pro'); ?>
							<input type="radio" class="wpsp_field_activate" name="wpscp_pro_linkedin_content_source" value="content" <?php checked('content', $linkedin_content_source); ?>> <?php esc_html_e('Content', 'wp-scheduled-posts-pro'); ?>
						</div>
					</td>
				</tr>
				<tr>
					<p></p>
				</tr>
				<tr>
					<td colspan="2" align="left">
						<div class="wpsp_form_group">
							<?php
							$wpscp_pro_linkedin_template_structure = get_option('wpscp_pro_linkedin_template_structure');
							?>
							<label for=""><?php esc_html_e('Status Template Settings', 'wp-scheduled-posts-pro'); ?></label>
							<input type="text" name="wpscp_pro_linkedin_template_structure" class="wpsp_field_activate" value="<?php ($wpscp_pro_linkedin_template_structure != "" ? print $wpscp_pro_linkedin_template_structure : print '{title}{content}{tags}'); ?>" placeholder="<?php esc_attr_e('Template Settings', 'wp-scheduled-posts-pro'); ?>">
						</div>
					</td>
				</tr>
				<tr>
					<td>
						<p class="wpsp_integ_structure"><?php esc_html_e('Default Structure:', 'wp-scheduled-posts-pro'); ?> <span><?php print esc_html('{title}{content}{url}{tags}'); ?><span /></p>
					</td>
				</tr>
				<tr>
					<p></p>
				</tr>
				<tr>
					<td colspan="2" align="left">
						<div class="wpsp_form_group">
							<label for=""><?php esc_html_e('Status Limit', 'wp-scheduled-posts-pro'); ?></label>
							<input type="number" name="wpscp_pro_linkedin_status_limit" class="wpsp_number_field" min="1" max="1300" value="<?php echo esc_attr($linkedin_status_limit); ?>" /> <?php esc_html_e('Maximum Limit: 1300 character', 'wp-scheduled-posts-pro'); ?>
						</div>
					</td>
				</tr>
				<tr>
					<td colspan="2" align="left">
						<div class="wpsp_form_group">
							<input type="submit" class="wpsp_form_submit" name="wpscp_linkedin_template_settings_submit" value="save">
						</div>
					</td>
				</tr>
			</form>

		</div>
	</div>

	<!-- pinterest -->
	<div class="wpsp-integ-item_section wpsp-integ-active" style="border-top: 1px solid #f5f5f5;">
		<div class="wpsp-integ-bar wpsp-integ-active">
			<input type="radio" checked="checked">
			<h3><?php esc_html_e('Pinterest Pin Settings', 'wp-scheduled-posts-pro'); ?></h3>
			<p><?php esc_html_e('To configure the Pinterest Share Settings, check out this', 'wp-scheduled-posts-pro'); ?> <a class="docs" href="https://wpdeveloper.net/docs/wordpress-posts-on-pinterest/"><?php esc_html_e('Doc', 'wp-scheduled-posts-pro'); ?></a></p>
		</div>
		<div class="wpsp-integ-content wpsp-social-integ-content" style="display: block;">
			<?php
			$pinterest_template_settings = get_option('wpscp_pro_pinterest_template_settings');
			$pinterest_add_image_link = (isset($pinterest_template_settings['add_image_link']) ? $pinterest_template_settings['add_image_link'] : 'yes');
			$pinterest_template_category_tags_support = (isset($pinterest_template_settings['template_category_tags_support']) ? $pinterest_template_settings['template_category_tags_support'] : '');
			$pinterest_template_structure = (isset($pinterest_template_settings['template_structure']) ? $pinterest_template_settings['template_structure'] : '{title}');
			$pinterest_pin_note_limit = (isset($pinterest_template_settings['pin_note_limit']) ? $pinterest_template_settings['pin_note_limit'] : 500);
			$pinterest_content_source = (isset($pinterest_template_settings['content_source']) ? $pinterest_template_settings['content_source'] : 'content');

			?>
			<!-- twitter form settings -->
			<form action="<?php print admin_url('admin-post.php'); ?>" method="post" class="wpscp-pinterest-template">
				<input type="hidden" name="action" value="wpscppro_pinterest_template_structure">
				<input type="hidden" name="_wpnonce" value="<?php print wp_create_nonce('pinterest_template_structure'); ?>">
				<tr>
					<p></p>
				</tr>
				<tr>
					<td colspan="2" align="left">
						<div class="wpsp_form_group">
							<label for=""><?php esc_html_e('Add Image Link', 'wp-scheduled-posts-pro'); ?></label>
							<input type="checkbox" name="add_image_link" class="wpsp_field_activate" value="yes" <?php checked('yes', $pinterest_add_image_link); ?>><?php esc_html_e('Yes', 'wp-scheduled-posts-pro'); ?><br>
						</div>
					</td>
				</tr>
				<tr>
					<p></p>
				</tr>
				<tr>
					<td colspan="2" align="left">
						<div class="wpsp_form_group">
							<label for=""><?php esc_html_e('Add Category as a tags', 'wp-scheduled-posts-pro'); ?></label>
							<input type="checkbox" name="template_category_tags_support" class="wpsp_field_activate" value="yes" <?php checked('yes', $pinterest_template_category_tags_support); ?>><?php esc_html_e('Yes', 'wp-scheduled-posts-pro'); ?><br>
						</div>
					</td>
				</tr>
				<tr>
					<p></p>
				</tr>
				<tr>
					<td colspan="2" align="left">
						<div class="wpsp_form_group">
							<label for=""><?php esc_html_e('Content Source', 'wp-scheduled-posts-pro'); ?></label>
							<?php
							?>
							<input type="radio" class="wpsp_field_activate" name="content_source" value="excerpt" <?php checked('excerpt', $pinterest_content_source); ?>> <?php esc_html_e('Excerpt', 'wp-scheduled-posts-pro'); ?>
							<input type="radio" class="wpsp_field_activate" name="content_source" value="content" <?php checked('content', $pinterest_content_source); ?>> <?php esc_html_e('Content', 'wp-scheduled-posts-pro'); ?>
						</div>
					</td>
				</tr>
				<tr>
					<p></p>
				</tr>
				<tr>
					<td colspan="2" align="left">
						<div class="wpsp_form_group">
							<label for=""><?php esc_html_e('Pin Template Settings', 'wp-scheduled-posts-pro'); ?></label>
							<input type="text" name="template_structure" class="wpsp_field_activate" value="<?php ($pinterest_template_structure != "" ? print esc_attr($pinterest_template_structure) : print '{title}{content}{tags}'); ?>" placeholder="<?php esc_attr_e('Template Settings', 'wp-scheduled-posts-pro'); ?>">
						</div>
					</td>
				</tr>
				<tr>
					<td>
						<p class="wpsp_integ_structure"><?php esc_html_e('Default Structure:', 'wp-scheduled-posts-pro'); ?> <span><?php print esc_html('{title}{content}{url}{tags}'); ?><span /></p>
					</td>
				</tr>
				<tr>
					<p></p>
				</tr>
				<tr>
					<td colspan="2" align="left">
						<div class="wpsp_form_group">
							<label for=""><?php esc_html_e('Pin Note Limit', 'wp-scheduled-posts-pro'); ?></label>
							<input type="number" name="pin_note_limit" class="wpsp_number_field" min="1" max="500" value="<?php echo esc_attr($pinterest_pin_note_limit); ?>" /> <?php esc_html_e('Maximum Limit: 500 character', 'wp-scheduled-posts-pro'); ?>
						</div>
					</td>
				</tr>
				<tr>
					<td colspan="2" align="left">
						<div class="wpsp_form_group">
							<input type="submit" class="wpsp_form_submit" name="wpscp_pinterest_template_settings_submit" value="save">
						</div>
					</td>
				</tr>
			</form>

		</div>
	</div>
</div>