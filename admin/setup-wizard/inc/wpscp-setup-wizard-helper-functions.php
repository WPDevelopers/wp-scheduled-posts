<?php
if(!function_exists('wpscp_get_all_category')){
	function wpscp_get_all_category(){
		$category  = get_categories( array(
			'orderby' => 'name',
			'order'   => 'ASC',
			"hide_empty" => 0,
		) );
		$category = wp_list_pluck($category, 'name', 'term_id');
		array_unshift($category, 'All Categories');
		return $category;
	}
}

/**
 * Welcome Screen
 */
add_action('wpscp_pro_qsw_welcomescreen', 'wpscp_pro_qsw_welcomescreen_markup');
function wpscp_pro_qsw_welcomescreen_markup(){
	?>
	<div class="wpsp_getting_started_form text-center">
		<input type="email" class="wpsp_field_gettting_started" name="wpscp_user_email_address" value="" placeholder="Your Email Address">
	</div>
	<?php
}


/**
 * Pro Feature
 */
add_action('wpscp_pro_qsw_profeature_list', 'wpscp_pro_feature_list_markup');
function wpscp_pro_feature_list_markup(){
	?>
	<!-- left side -->
	<td>
		<div class="wpscp-checkbox wpscp-pro-feature-checkbox" data-id="module_auto_scheduled">
			<input disabled="" type="checkbox" id="module_auto_scheduled" name="module_auto_scheduled">
			<label for="module_auto_scheduled"></label>
			<p class="wpscp-module-title">Auto Scheduled               
				<sup class="wpscp-pro-label has-to-update"></sup><sup class="wpscp-pro-label">Pro</sup>                </p>
		</div>
	</td>
	<!-- right side -->
	<td>
		<div class="wpscp-checkbox wpscp-pro-feature-checkbox" data-id="wpscp_pro_module">
			<input disabled="" type="checkbox" id="wpscp_pro_module" name="wpscp_pro_module">
			<label for="wpscp_pro_module"></label>
			<p class="wpscp-module-title">Manual Scheduled              
				<sup class="wpscp-pro-label has-to-update">1.2.0</sup><sup class="wpscp-pro-label">Pro</sup>                
			</p>
		</div>
	</td>
	
	<?php
}