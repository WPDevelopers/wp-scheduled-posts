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

    public static function get_all_roles_as_dropdown($selected = array(), $skip_subscribe = false)
    {
        $p = '';
        $r = '';
        $editable_roles = get_editable_roles();
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

    public static function get_all_roles()
    {
        $allroles = wp_list_pluck(get_editable_roles(), 'name');
        unset($allroles['subscriber']);
        return $allroles;
    }

    public static function is_user_allow()
    {
        global $current_user;
        global $wpscp_options;

        if (!is_array($current_user->roles)) return false;
        if (!is_array($wpscp_options['allow_user_role'])) $wpscp_options['allow_user_role'] = array('administrator');

        foreach ($current_user->roles as $ur) {
            if (in_array($ur, $wpscp_options['allow_user_role'])) {
                return true;
                break;
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
        $allow_post_types = (!empty(self::get_settings('allow_post_types')) ? array('post') : self::get_settings('allow_post_types'));
        if (
            in_array($current_post_type, $allow_post_types) ||
            $hook == 'posts_page_wp-scheduled-calendar-post' ||
            $hook == 'toplevel_page_wp-scheduled-posts' ||
            $hook == 'admin_page_wpscp-quick-setup-wizard' ||
            $hook == 'scheduled-posts_page_wp-scheduled-calendar'
        ) {
            return true;
        }
        return false;
    }
}
