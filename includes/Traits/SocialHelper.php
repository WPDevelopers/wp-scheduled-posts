<?php

namespace WPSP\Traits;

/**
 * Social Share common method
 */
trait SocialHelper
{
    /**
     * Tags
     */
    public function getPostHasTags($post_id)
    {
        if (\get_the_tags($post_id) != false) {
            $tags = \wp_list_pluck(\get_the_tags($post_id), 'name', 'term_id');
            $search = array(' ', '-', '_');
            $replace = '';
            \array_walk(
                $tags,
                function (&$v) use ($search, $replace) {
                    $v = str_replace($search, $replace, $v);
                }
            );
            return '#' . \implode(' #', $tags);
        }
        return false;
    }

    /**
     * Category
     */
    public function getPostHasCats($post_id)
    {
        if (\get_the_category($post_id) != false) {
            $categories = wp_list_pluck(\get_the_category($post_id), 'name', 'term_id');
            $search = array(' ', '-', '_');
            $replace = '';
            array_walk(
                $categories,
                function (&$v) use ($search, $replace) {
                    $v = str_replace($search, $replace, $v);
                }
            );
            return '#' . \implode(' #', $categories);
        }
        return false;
    }
    /**
     * Generate Social Template Structure
     * @param template, post_title, post_description, post_link, post_tags
     * @since 2.5.1
     */
    public function social_share_content_template_structure($template_structure, $title, $desc, $post_link, $hashTags, $limit)
    {
        $title              = html_entity_decode($title);
        $desc               = html_entity_decode($desc);
        $post_content_limit = intval($limit);
        if (!empty($title)) {
            $post_content_limit = intval($post_content_limit) - strlen($title);
            $template_structure = str_replace('{title}', '::::' . $title . '::::', $template_structure);
        }
        if (!empty($post_link)) {
            $post_content_limit = intval($post_content_limit) - strlen($post_link);
            $template_structure = str_replace('{url}', '::::' . $post_link . '::::', $template_structure);
        }
        if (!empty($hashTags)) {
            $post_content_limit = intval($post_content_limit) - strlen($hashTags);
            $template_structure = str_replace('{tags}', '::::' . $hashTags . '::::', $template_structure);
        } else {
            $template_structure = str_replace('{tags}', '', $template_structure);
        }

        if (!empty($desc)) {
            $post_content = substr($desc, 0, $post_content_limit);
            $template_structure = str_replace('{content}', '::::' . $post_content . '::::', $template_structure);
        }

        $template_structure = trim($template_structure, '::::');
        $template_structure = str_replace('::::', "\n", $template_structure);
        return $template_structure;
    }
}
