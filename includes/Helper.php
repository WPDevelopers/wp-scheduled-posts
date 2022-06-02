<?php

namespace WPSP;

class Helper
{
    public static function get_all_post_type()
    {
        $postType = get_post_types('', 'names');
        $not_neccessary_post_types = array('custom_css', 'attachment', 'revision', 'nav_menu_item', 'customize_changeset', 'oembed_cache', 'user_request', 'product_variation', 'shop_order', 'scheduled-action', 'shop_order_refund', 'shop_coupon', 'nxs_qp');
        return array_diff($postType, $not_neccessary_post_types);
    }

    public static function get_all_category()
    {
        $category  = get_categories(array(
            'orderby' => 'name',
            'order'   => 'ASC',
            "hide_empty" => 0,
        ));
        $category = wp_list_pluck($category, 'name', 'slug');
        return array_merge(array('all' => 'All Categories'), $category);
    }

    public static function _get_all_category()
    {
        $return = ['result' => [
            [
                'value' => 'all',
                'label' => "All",
            ]
        ]];
        $allow_post_types  = \WPSP\Helper::get_settings('allow_post_types');
        $taxonomies = self::get_all_tax_term($allow_post_types);

        foreach ($taxonomies as $tax_label => $terms) {
            foreach ($terms as $term) {
                if(empty($return['result'][$tax_label])){
                    $return['result'][$tax_label] = [
                        'label' => $tax_label,
                        'options' => [[
                            'value' => $term['taxonomy'] . '.' . $term['slug'],
                            'label' => $term['name'],
                        ]],
                    ];
                }
                else{
                    $return['result'][$tax_label]['options'][] = [
                        'value' => $term['taxonomy'] . '.' . $term['slug'],
                        'label' => $term['name'],
                    ];
                }

            }
        }
        $return['result'] = array_values($return['result']);
        return $return['result'];
    }

    public static function get_all_tax_term($postTypes)
    {
        $taxonomies = [];
        $postTypes = (array) $postTypes;
        foreach ($postTypes as $key => $postType) {
            $tax = get_object_taxonomies($postType, 'objects');
            if(!empty($tax)){
                $terms = get_terms( array(
                    'taxonomy'   => array_keys($tax),
                    'hide_empty' => false,
                    // 'fields'     => 'id=>name',
                ) );
                foreach ($terms as $key => $term) {
                    $tax_label = $tax[$term->taxonomy]->label;
                    $taxonomies[$tax_label . " ($postType)"][$term->slug] = [
                        'slug'     => $term->slug,
                        'name'     => $term->name,
                        'taxonomy' => $term->taxonomy,
                    ];
                }
            }
        }
        // $taxonomies = wp_list_pluck($taxonomies, 'name', 'slug');
        return $taxonomies;
    }

    public static function get_all_post_terms($pid = null){
        $pid = $pid ? $pid : get_the_id();
        $taxonomies = [];
        $tax = get_object_taxonomies(get_post_type($pid));
        $terms = wp_get_post_terms($pid, $tax);
        foreach ($terms as $key => $term) {
            $taxonomies[$term->taxonomy][] = $term->slug;
        }
        return $taxonomies;
    }

    public static function get_all_roles_as_dropdown($selected = array(), $skip_subscribe = false)
    {
        $p = '';
        $r = '';
        $editable_roles = \get_editable_roles();
        if (is_array($editable_roles) && count($editable_roles) > 0) {
            foreach ($editable_roles as $role => $details) {
                if ($role == 'subscriber' && $skip_subscribe == true) {
                    continue;
                }
                $name = translate_user_role($details['name']);
                if ($selected !== "" && is_array($selected) && in_array($role, $selected)) {
                    $p .= "\n\t<option selected='selected' value='" . esc_attr($role) . "'>$name</option>";
                } else {
                    $r .= "\n\t<option value='" . esc_attr($role) . "'>$name</option>";
                }
            }
        }

        return $p . $r;
    }

    public static function get_all_cats_id_to_slugs($allids)
    {
        $catSlug = array();
        if (is_array($allids)) {
            foreach ($allids as $id) {
                if ($id == 0) {
                    $catSlug[] = 'all';
                } else {
                    $category = \get_category($id);
                    $catSlug[] = $category->slug;
                }
            }
        }
        return $catSlug;
    }

    public static function get_all_roles()
    {
        if (!function_exists('get_editable_roles')) {
            require_once ABSPATH . 'wp-admin/includes/user.php';
        }
        $allroles = wp_list_pluck(\get_editable_roles(), 'name');
        unset($allroles['subscriber']);
        return $allroles;
    }

    public static function is_user_allow()
    {
        global $current_user;
        $allow_user_by_role = \WPSP\Helper::get_settings('allow_user_by_role');
        $allow_user_by_role = (is_array($allow_user_by_role) && count($allow_user_by_role) > 0) ? $allow_user_by_role : array('administrator');
        if (!is_array($current_user->roles)) return false;
        if(is_array($allow_user_by_role)){
            foreach ($current_user->roles as $ur) {
                if (in_array($ur, $allow_user_by_role)) {
                    return true;
                    break;
                }
            }
        }
        return false;
    }

    public static function get_settings($key)
    {
        global $wpsp_settings;
        if (isset($wpsp_settings->{$key})) {
            return $wpsp_settings->{$key};
        }
        return;
    }

    /**
     * Check Supported Post type for admin page and plugin main settings page
     *
     * @return bool
     * @version 3.1.12
     */

    public static function plugin_page_hook_suffix($current_post_type, $hook)
    {
        $allow_post_types = (!empty(self::get_settings('allow_post_types')) ? self::get_settings('allow_post_types') : array('post'));
        if (
            in_array($current_post_type, $allow_post_types) ||
            $hook == 'posts_page_' . WPSP_SETTINGS_SLUG . '-post' ||
            $hook == 'toplevel_page_' .  WPSP_SETTINGS_SLUG ||
            $hook == WPSP_SETTINGS_SLUG . '_page_' . WPSP_SETTINGS_SLUG ||
            $hook == WPSP_SETTINGS_SLUG . '_page_' . WPSP_SETTINGS_SLUG . '-calendar'
        ) {
            return true;
        }
        return false;
    }

    /**
     * Email Notify review Email List
     * @return array
     */
    public static function email_notify_review_email_list()
    {
        $email = array();
        // collect email from role
        $roles = self::get_settings('notify_author_post_review_by_role');
        if (!empty($roles)) {
            $email = wp_list_pluck(get_users(array(
                'fields'     => array('user_email'),
                'role__in'    => $roles
            )), 'user_email');
        }
        // collect email from email fields
        $meta_email = array_values(self::get_settings('notify_author_post_review_by_email'));
        if (!empty($meta_email)) {
            $email = array_merge($email, $meta_email);
        }
        // get email from username
        $meta_username = self::get_settings('notify_author_post_review_by_username');
        if (!empty($meta_username)) {
            $email = array_merge($email, wp_list_pluck(get_users(array(
                'fields'     => array('user_email'),
                'login__in'    => $meta_username
            )), 'user_email'));
        }
        return array_unique($email);
    }

    public static function email_notify_schedule_email_list()
    {
        $email = array();
        // collect email from role
        $roles = self::get_settings('notify_author_post_scheduled_by_role');
        if (!empty($roles)) {
            $email = wp_list_pluck(get_users(array(
                'fields'     => array('user_email'),
                'role__in'    => $roles
            )), 'user_email');
        }
        // collect email from email fields
        $meta_email = array_values(self::get_settings('notify_author_post_scheduled_by_email'));
        if (!empty($meta_email)) {
            $email = array_merge($email, $meta_email);
        }
        // get email from username
        $meta_username = self::get_settings('notify_author_post_scheduled_by_username');
        if (!empty($meta_username)) {
            $email = array_merge($email, wp_list_pluck(get_users(array(
                'fields'     => array('user_email'),
                'login__in'    => $meta_username
            )), 'user_email'));
        }
        return array_unique($email);
    }

    /**
     * social single profile data return
     * wpscp_get_social_profile
     *
     * @param  mixed $profile
     * @return array
     * @since 3.3.0
     */

    public static function get_social_profile($profile)
    {
        $profile =  self::get_settings($profile);
        $is_pro_wpscp = apply_filters('wpsp_social_profile_limit_checkpoint', $profile);
        if (class_exists('WPSP_PRO') && $is_pro_wpscp === true) {
            return $profile;
        }
        return (is_array($profile) ? array_slice($profile, 0, 1) : []);
    }
}
