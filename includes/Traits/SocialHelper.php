<?php

namespace WPSP\Traits;

/**
 * Social Share common method
 */
trait SocialHelper
{
    /**
     * Allowed Tags
     */
    public function getPostHasTags($post_id, $platform = '', $category_as_tags = false)
    {
        $terms = [];
        $post_type = get_post_type($post_id);

        // Get the allowed taxonomies
        $get_allowed_taxonomy = \WPSP\Helper::get_all_allowed_taxonomy();

        // Iterate through each allowed taxonomy
        foreach ($get_allowed_taxonomy as $taxonomy) {
            if( $taxonomy == 'category' && $category_as_tags ) {
                continue;
            }
            // Check if the taxonomy is associated with the post
            $taxonomy_terms = get_the_terms($post_id, $taxonomy);
            if ($taxonomy_terms && !is_wp_error($taxonomy_terms)) {
                $terms = array_merge($terms, $taxonomy_terms);
            }
        }

        if (!empty($terms)) {
            // Extract tag names and remove keys
            $tags = array_values(\wp_list_pluck($terms, 'name'));

            // Clean up tag names
            $search = array(' ', '-', '_');
            $replace = '';
            \array_walk(
                $tags,
                function (&$v) use ($search, $replace) {
                    $v = str_replace($search, $replace, $v);
                }
            );
            if( !empty($platform) && $platform == 'medium' ) {
                return $tags;
            }
            // Return formatted hashtags
            return '#' . \implode(' #', $tags);
        }
        if( !empty($platform) && $platform == 'medium' ) {
            return [];
        }
        // Return false if no tags are found
        return false;
    }

    /**
     * Category
     */
    public function getPostHasCats($post_id, $platform = '')
    {
        $terms = null;
        $post_type = get_post_type($post_id);
        if('product' === $post_type){
            $terms = get_the_terms( $post_id, 'product_cat' );
        }
        else{
            $terms = \get_the_category($post_id);
        }
        if ($terms != false) {
            $categories = wp_list_pluck($terms, 'name', 'term_id');
            $search = array(' ', '-', '_');
            $replace = '';
            array_walk(
                $categories,
                function (&$v) use ($search, $replace) {
                    $v = str_replace($search, $replace, $v);
                }
            );
            if( !empty($platform) && $platform == 'medium' ) {
                return $categories;
            }
            return '#' . \implode(' #', $categories);
        }
        if( !empty($platform) && $platform == 'medium' ) {
            return [];
        }
        return false;
    }
    /**
     * Generate Social Template Structure with Custom Template Support
     * @param template, post_title, post_description, post_link, post_tags
     * @since 2.5.1
     * @updated 2.6.0 - Added custom template support
     */
    public function social_share_content_template_structure($template_structure, $title, $desc, $post_link, $hashTags, $limit, $url_limit = null, $platform = '', $post_id = null, $profile_id = null)
    {
        // Check for custom template if post_id and profile_id are provided and meta is enabled
        $enable_custom_template = false;
        if ($post_id) {
            $meta_value = get_post_meta($post_id, '_wpsp_enable_custom_social_template', true);
            $enable_custom_template = ($meta_value === '1' || $meta_value === 1);
        }
        if ($enable_custom_template && $post_id && $profile_id && $platform && class_exists('\WPSP\Helpers\CustomTemplateHelper')) {
            $templates = get_post_meta($post_id, '_wpsp_custom_templates', true);
        
            // Step 1: Detect global template
            $global_template = null;
            foreach ($templates as $tpl_platform => $tpl_data) {
                if (!empty($tpl_data['is_global'])) {
                    $global_template = $tpl_data['template'];
                    break;
                }
            }
        
            // Step 2: Use global template if available
            if ($global_template && $global_template !== $template_structure) {
                $template_structure = $global_template;
            }
            // Step 3: If no global template, fallback to platform-specific
            else {
                $platform_data = isset($templates[$platform]) ? $templates[$platform] : null;
                $profiles = is_array($platform_data) && isset($platform_data['profiles']) ? $platform_data['profiles'] : [];
        
                // Only apply custom template if profiles is not empty and profile_id is in profiles
                if (!empty($profiles) && in_array($profile_id, $profiles)) {
                    $custom_template = \WPSP\Helpers\CustomTemplateHelper::get_resolved_template($post_id, $platform, $profile_id);
                    if ($custom_template && $custom_template !== $template_structure) {
                        $template_structure = $custom_template;
                    }
                }
            }
        }
        
        
        $title              = html_entity_decode($title);
        $desc               = html_entity_decode($desc);
        $post_content_limit = intval($limit);
        if (!empty($post_link) && strpos($template_structure, '{url}') !== false) {
            $post_content_limit = intval($post_content_limit) - ($url_limit ? $url_limit : strlen($post_link));
            $template_structure = str_replace('{url}', '::::' . $post_link . '::::', $template_structure);
        }
        else{
            $template_structure = str_replace('{url}', '', $template_structure);
        }
        if (!empty($title) && strpos($template_structure, '{title}') !== false) {
            $title              = substr($title, 0, $post_content_limit);
            $title              = apply_filters('wpsp_social_share_title', $title, get_called_class(), $post_link);
            $post_content_limit = intval($post_content_limit) - strlen($title);
            $template_structure = str_replace('{title}', '::::' . $title . '::::', $template_structure);
        }
        else{
            $template_structure = str_replace('{title}', '', $template_structure);
        }
        if (!empty($hashTags) && strpos($template_structure, '{tags}') !== false) {
            $tags = '';
            $_tags = explode('#', $hashTags);
            $_tags = apply_filters('wpsp_filter_social_content_tags', $_tags, $platform);
            foreach ($_tags as $tag) {
                $tag = trim($tag);
                if (empty($tag))
                    continue;
                $_tag = "#$tag ";
                $post_content_limit = intval($post_content_limit) - strlen($_tag);
                if($post_content_limit > 0){
                    $tags .= $_tag;
                }
                else{
                    break;
                }
            }

            $template_structure = str_replace('{tags}', '::::' . $tags . '::::', $template_structure);
        } else {
            $template_structure = str_replace('{tags}', '', $template_structure);
        }

        if (!empty($desc) && strpos($template_structure, '{content}') !== false) {
            $desc = apply_filters('wpsp_social_share_desc', $desc, $platform);
            if ( strlen($desc) > $post_content_limit ) {
                $post_content = substr($desc, 0, $post_content_limit - 3 ) . '...';
            }else{
                $post_content = substr($desc, 0, $post_content_limit );
            }
            $template_structure = str_replace('{content}', '::::' . $post_content . '::::', $template_structure);
        }
        else{
            $template_structure = str_replace('{content}', '', $template_structure);
        }

        $template_structure = trim($template_structure, '::::');
        $replace_value = apply_filters('wpsp_social_share_content_template_line_break', "\n", func_get_args());
        $template_structure = str_replace('::::', $replace_value, $template_structure);
        return trim($template_structure);
    }

   
    
}
