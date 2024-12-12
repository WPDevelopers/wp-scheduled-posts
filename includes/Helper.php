<?php

namespace WPSP;

use WPSP\Deps\GuzzleHttp\Client;
use WPSP\Deps\GuzzleHttp\Exception\RequestException;
use WPSP\Deps\GuzzleHttp\Psr7\Request;

class Helper
{
    public static function get_all_post_type()
    {
        $postType = get_post_types('', 'names');
        $not_neccessary_post_types = array('custom_css', 'attachment', 'revision', 'nav_menu_item', 'customize_changeset', 'oembed_cache', 'user_request', 'product_variation', 'shop_order', 'scheduled-action', 'shop_order_refund', 'shop_coupon', 'nxs_qp');
        return array_diff($postType, $not_neccessary_post_types);
    }

    public static function get_all_taxonomies()
    {
        $taxonomies = get_taxonomies('', 'names');
        $not_necessary_taxonomies = array('nav_menu', 'link_category', 'post_format');
        return array_diff($taxonomies, $not_necessary_taxonomies);
    }


    public static function get_all_allowed_post_type() {
        $allow_post_types = \WPSP\Helper::get_settings('allow_post_types');
        if( !is_array( $allow_post_types ) ) {
            return self::get_all_post_type();
        }
        if( in_array( 'all', $allow_post_types ) ) {
            $allow_post_types = self::get_all_post_type();
        }
        $allow_post_types = array_values( $allow_post_types );
        return $allow_post_types;
    }

    public static function get_all_allowed_taxonomy() {
        $allow_taxonomy_as_tags = \WPSP\Helper::get_settings('allow_taxonomy_as_tags');
        if( !is_array( $allow_taxonomy_as_tags ) ) {
            return self::get_all_taxonomies();
        }
        if( in_array( 'all', $allow_taxonomy_as_tags ) ) {
            $allow_taxonomy_as_tags = self::get_all_taxonomies();
        }
        $allow_taxonomy_as_tags = array_values( $allow_taxonomy_as_tags );
        return $allow_taxonomy_as_tags;
    }

    public static function get_allow_post_types()
    {
        $post_types       = [];
        $allow_post_types = \WPSP\Helper::get_settings('allow_post_types');
        $allow_post_types = (!empty($allow_post_types) ? $allow_post_types : array('post'));
        if( !is_array( $allow_post_types ) ) {
            return self::get_all_post_type();
        }
        if( in_array( 'all', $allow_post_types ) ) {
            $allow_post_types = self::get_all_post_type();
        }
        foreach ($allow_post_types as $post_type) {
            $post_type_object       = get_post_type_object($post_type);
            if(!empty($post_type_object)){
                $post_types[$post_type] = $post_type_object->label;
            }
        }
        return $post_types;
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
        $return = ['result' => []];
        $allow_post_types  = \WPSP\Helper::get_all_allowed_post_type();
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

    public static function get_post_types(){
        $post_type = (isset($_GET['post_type']) ? $_GET['post_type'] : '');
        if(isset($_GET['page']) && $_GET['page'] === 'schedulepress-post'){
            $post_type = 'post';
        }
        else if(isset($_GET['page']) && strpos($_GET['page'], 'schedulepress-') === 0){
            $post_type = str_replace('schedulepress-', '', $_GET['page']);
        }
        if($post_type == 'calendar'){
            $post_type = 'post';
        }

        return $post_type;
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
                        'term_id'  => $term->term_id,
                        'slug'     => $term->slug,
                        'name'     => $term->name,
                        'taxonomy' => $term->taxonomy,
                        'postType' => $postType,
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
        if ( is_super_admin($current_user->ID) ) return true;
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
        global $wpsp_settings_v5;
        if (isset($wpsp_settings_v5->{$key})) {
            return $wpsp_settings_v5->{$key};
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
        $allow_post_types = (!empty(self::get_all_allowed_post_type()) ? self::get_all_allowed_post_type() : array('post'));
        if (
            in_array($current_post_type, $allow_post_types) ||
            $hook == 'posts_page_' . WPSP_SETTINGS_SLUG . '-post' ||
            $hook == 'toplevel_page_' .  WPSP_SETTINGS_SLUG ||
            $hook == WPSP_SETTINGS_SLUG . '_page_' . WPSP_SETTINGS_SLUG ||
            $hook == WPSP_SETTINGS_SLUG . '_page_' . WPSP_SETTINGS_SLUG . '-calendar' ||
            strpos($hook, '_page_' . WPSP_SETTINGS_SLUG) !== false
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

    public static function get_social_profile($profile, $includes = [])
    {
        $profile =  self::get_settings($profile) ?? [];
        
        if( !empty( $includes ) ) {
            $includes = array_map(function($element) {
                return str_replace('.', ' ', $element);
            }, $includes);
            $profile = array_filter( $profile, function($get_single_profile) use($includes) {
                $single_name = str_replace('.', ' ',$get_single_profile->name);
                return in_array( $single_name, $includes );
            } );
        }
        $is_pro_wpscp = apply_filters('wpsp_social_profile_limit_checkpoint', $profile);
        if (class_exists('WPSP_PRO') && $is_pro_wpscp === true) {
            return $profile;
        }
        $profile = array_filter( $profile, function($single_profile){
            return $single_profile->status == true;
        } );
        return  array_slice($profile, 0, 1);
    }


    public static function update_access_token( $type, $platformKey, $access_token ) {
        global $wpsp_settings_v5;
        $platformOptions = [
            'facebook'  => WPSCP_FACEBOOK_OPTION_NAME,
            'twitter'   => WPSCP_TWITTER_OPTION_NAME,
            'linkedin'  => WPSCP_LINKEDIN_OPTION_NAME,
            'pinterest' => WPSCP_PINTEREST_OPTION_NAME,
        ];

        $opt_name = isset($platformOptions[$type]) ? $platformOptions[$type] : '';
        if(empty($wpsp_settings_v5->{$opt_name}[$platformKey])) return $access_token;
        $profile  = &$wpsp_settings_v5->{$opt_name}[$platformKey];

        if( isset( $access_token ) ){
            $profile->access_token = $access_token;
            update_option(WPSP_SETTINGS_NAME, json_encode($wpsp_settings_v5));
        }
    }

    /**
     * generate access token from refresh token.
     *
     * @param  mixed $profile
     * @return string
     */
    public static function get_access_token($type, $platformKey, $access_token = null)
    {
        global $wpsp_settings_v5;
        $token        = [];
        $platformOptions = [
            'facebook'  => WPSCP_FACEBOOK_OPTION_NAME,
            'twitter'   => WPSCP_TWITTER_OPTION_NAME,
            'linkedin'  => WPSCP_LINKEDIN_OPTION_NAME,
            'pinterest' => WPSCP_PINTEREST_OPTION_NAME,
        ];

        $opt_name = isset($platformOptions[$type]) ? $platformOptions[$type] : '';
        if(empty($wpsp_settings_v5->{$opt_name}[$platformKey])) return $access_token;
        $profile  = &$wpsp_settings_v5->{$opt_name}[$platformKey];

        if(isset($profile->expires_in, $profile->rt_expires_in) && $profile->expires_in < time() && $profile->rt_expires_in > time()){
            $refresh_token_url = add_query_arg([
                'type'          => $type,
                'refresh_token' => $profile->refresh_token,
            ], $profile->redirectURI);

            $response = wp_remote_get($refresh_token_url, ['sslverify' => false]);

            if (!is_wp_error($response)) {
                $body                  = wp_remote_retrieve_body($response);
                $token                 = json_decode($body);
                $profile->access_token = $token->access_token;
                $profile->expires_in   = time() + $token->expires_in;
                update_option(WPSP_SETTINGS_NAME, json_encode($wpsp_settings_v5));
            }
        }

        return $profile->access_token;
    }

    /**
     * generate access token from refresh token.
     *
     * @param  mixed $profile
     * @return array
     */
    public static function get_profiles($type)
    {
        global $wpsp_settings_v5;
        $platformOptions = [
            'facebook'  => WPSCP_FACEBOOK_OPTION_NAME,
            'twitter'   => WPSCP_TWITTER_OPTION_NAME,
            'linkedin'  => WPSCP_LINKEDIN_OPTION_NAME,
            'pinterest' => WPSCP_PINTEREST_OPTION_NAME,
        ];

        $opt_name = isset($platformOptions[$type]) ? $platformOptions[$type] : '';
        if(!empty($wpsp_settings_v5->{$opt_name})){
            return $wpsp_settings_v5->{$opt_name};
        }
        else{
            return [];
        }

        return [];
    }

    /**
     * generate access token from refresh token.
     *
     * @param  mixed $profile
     * @return object
     */
    public static function get_profile($type, $platformKey = null)
    {
        $platformOptions = self::get_profiles($type);

        if(false === $platformKey || null === $platformKey){
            return $platformOptions;
        }
        else{
            if(!empty($platformOptions[$platformKey])){
                return $platformOptions[$platformKey];
            }
        }

        return (object) [];
    }

    /**
     * Check is enable classic editor
     */
    public static function is_enable_classic_editor() {
        $current_screen = get_current_screen();
        if ( is_object($current_screen) && method_exists( $current_screen, 'is_block_editor' ) && $current_screen->is_block_editor() ) {
            return false;
        }
        return true;
    }

    public static function is_profile_exits($ID, $profiles) {
        foreach ($profiles as $item) {
            if (isset($item['id']) && $item['id'] === $ID) {
                return $item;
            }
        }
        return false;
    }

    public static function wpsp_curl($url, $parameters, $content_type, $post = true, $headers = [], $ssl = true ) 
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $ssl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        if ($post) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $parameters);
        }
        curl_setopt($ch, CURLOPT_POST, $post);

        $headers[] = "Content-Type: {$content_type}";
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $result = curl_exec($ch);
        $response_code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);
        return [
            'result' => $result,
            'code'   => $response_code
        ];
    }

    public static function wpsp_medium_curl($url, $parameters, $content_type, $post = true, $headers = [], $ssl = true ) 
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $ssl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        if ($post) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $parameters);
        }
        curl_setopt($ch, CURLOPT_POST, $post);
    
        // Add the content type to headers
        $headers[] = "Content-Type: {$content_type}";
    
        // Add the provided headers
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
        // Additional options
        curl_setopt($ch, CURLOPT_ENCODING, '');  // Handle different encodings
        curl_setopt($ch, CURLOPT_MAXREDIRS, 10); // Follow up to 10 redirects
        curl_setopt($ch, CURLOPT_TIMEOUT, 0);    // No timeout
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // Follow redirects
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1); // Use HTTP/1.1
    
        $result = curl_exec($ch);
    
        // Check for errors
        if (curl_errno($ch)) {
            echo 'Error:' . curl_error($ch);
        }
    
        // Get the response code
        $response_code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);
    
        return [
            'result' => $result,
            'code'   => $response_code
        ];
    }

    public static function get_medium_data($url, $headers) {
        $client = new Client();
        try {
            $request = new Request('GET', $url, $headers);
            $response = $client->sendAsync($request)->wait();
            return $response->getBody();
        } catch (RequestException $e) {
            // Handle the exception or log it
            return 'Request failed: ' . $e->getMessage();
        }
    }

    public static function strip_all_html_and_keep_single_breaks($content) {
        $cleaned_content = wp_strip_all_tags($content);
        $cleaned_content = preg_replace('/(\s*\n\s*)+/', "\n", $cleaned_content);
        $cleaned_content = trim($cleaned_content);
        return $cleaned_content;
    }

}

