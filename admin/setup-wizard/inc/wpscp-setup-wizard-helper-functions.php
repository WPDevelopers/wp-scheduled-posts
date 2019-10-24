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