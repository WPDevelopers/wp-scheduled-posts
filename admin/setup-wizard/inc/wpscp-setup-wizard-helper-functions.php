<?php
if (!function_exists('wpscp_get_all_category')) {
	function wpscp_get_all_category()
	{
		$category  = get_categories(array(
			'orderby' => 'name',
			'order'   => 'ASC',
			"hide_empty" => 0,
		));
		$category = wp_list_pluck($category, 'name', 'term_id');
		array_unshift($category, 'All Categories');
		return $category;
	}
}

/**
 * Get All Post Types
 */
function wpscp_get_all_post_type()
{
	$postType = get_post_types('', 'names');
	$not_neccessary_post_types = array('custom_css', 'attachment', 'revision', 'nav_menu_item', 'customize_changeset', 'oembed_cache', 'user_request', 'product_variation', 'shop_order', 'scheduled-action', 'shop_order_refund', 'shop_coupon', 'nxs_qp');
	return array_diff($postType, $not_neccessary_post_types);
}

/**
 * Welcome Screen
 */
add_action('wpscp_pro_qsw_welcomescreen', 'wpscp_pro_qsw_welcomescreen_markup');
function wpscp_pro_qsw_welcomescreen_markup()
{
	$current_user = wp_get_current_user();
?>
	<div class="wpsp_getting_started_form text-center">
		<input type="email" id="wpscp_user_email_address" class="wpsp_field_gettting_started" name="wpscp_user_email_address" value="<?php print $current_user->user_email; ?>" placeholder="<?php esc_attr_e('Your Email Address', 'wp-scheduled-posts'); ?>">
	</div>
<?php
}


/**
 * Pro Feature
 */
add_action('wpscp_pro_qsw_profeature_list', 'wpscp_pro_feature_list_markup');
function wpscp_pro_feature_list_markup()
{
?>
	<td>
		<!-- auto scheudled -->
		<div class="wpscp-checkbox wpscp-pro-feature-checkbox" data-id="module_auto_scheduled">
			<input disabled="" type="checkbox" id="module_auto_scheduled" name="module_auto_scheduled">
			<label for="module_auto_scheduled"></label>
			<p class="wpscp-module-title"><?php esc_html_e('Auto Scheduler', 'wp-scheduled-posts'); ?>
				<a rel="nofollow" target="_blank" href="https://wpdeveloper.net/docs/wp-scheduled-posts/how-does-auto-scheduler-work/">
					<img width="6px" src="<?php print plugin_dir_url(WPSP_PLUGIN_FILE) . 'admin/assets/images/question.svg'; ?>" alt="wp scheduled posts" />
				</a>
				<sup class="wpscp-pro-label has-to-update"></sup><sup class="wpscp-pro-label"><?php esc_html_e('Pro', 'wp-scheduled-posts'); ?></sup>
			</p>
		</div>
		<!-- Manual Scheduled -->
		<div class="wpscp-checkbox wpscp-pro-feature-checkbox" data-id="module_manual_scheduled">
			<input disabled="" type="checkbox" id="module_manual_scheduled" name="module_manual_scheduled">
			<label for="module_manjual_scheduled"></label>
			<p class="wpscp-module-title"><?php esc_html_e('Manual Scheduler', 'wp-scheduled-posts'); ?>
				<a rel="nofollow" target="_blank" href="https://wpdeveloper.net/docs/wp-scheduled-posts/how-does-manual-scheduler-work/">
					<img width="6px" src="<?php print plugin_dir_url(WPSP_PLUGIN_FILE) . 'admin/assets/images/question.svg'; ?>" alt="wp scheduled posts" />
				</a>
				<sup class="wpscp-pro-label has-to-update"></sup><sup class="wpscp-pro-label"><?php esc_html_e('Pro', 'wp-scheduled-posts'); ?></sup>
			</p>
		</div>
		<!-- Miss Scheduled -->
		<div class="wpscp-checkbox wpscp-pro-feature-checkbox" data-id="module_miss_scheduled">
			<input disabled="" type="checkbox" id="module_miss_scheduled" name="module_miss_scheduled">
			<label for="module_miss_scheduled"></label>
			<p class="wpscp-module-title"><?php esc_html_e('Missed Schedule Handler', 'wp-scheduled-posts'); ?>
				<a rel="nofollow" target="_blank" href="https://wpdeveloper.net/docs/wp-scheduled-posts/how-to-handle-the-missed-schedule-error-using-wp-scheduled-post/">
					<img width="6px" src="<?php print plugin_dir_url(WPSP_PLUGIN_FILE) . 'admin/assets/images/question.svg'; ?>" alt="wp scheduled posts" />
				</a>
				<sup class="wpscp-pro-label has-to-update"></sup><sup class="wpscp-pro-label"><?php esc_html_e('Pro', 'wp-scheduled-posts'); ?></sup>
			</p>
		</div>
	</td>
<?php
}
