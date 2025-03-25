<?php

namespace WPSP\Social;

use WPSP\Helper;
use WPSP\Traits\SocialHelper;

class Pinterest
{
    use SocialHelper;
    private $is_set_image_link;
    private $is_category_as_tags;
    private $content_source;
    private $template_structure;
    private $note_limit;
    private $post_share_limit;
    private $remove_css_from_content;


    public function __construct()
    {
        $settings = \WPSP\Helper::get_settings('social_templates');
        $settings = json_decode(json_encode($settings->pinterest), true);
        $this->is_set_image_link = (isset($settings['is_set_image_link']) ? $settings['is_set_image_link'] : '');
        $this->is_category_as_tags = (isset($settings['is_category_as_tags']) ? $settings['is_category_as_tags'] : '');
        $this->content_source = (isset($settings['content_source']) ? $settings['content_source'] : '');
        $this->template_structure = (isset($settings['template_structure']) ? $settings['template_structure'] : '');
        $this->note_limit = (isset($settings['note_limit']) ? $settings['note_limit'] : 500);
        $this->post_share_limit = (isset($settings['post_share_limit']) ? $settings['post_share_limit'] : 0);
        $this->remove_css_from_content = (isset($settings['remove_css_from_content']) ? $settings['remove_css_from_content'] : true);
    }

    public function instance()
    {
        // hook
        add_action('wpsp_publish_future_post', array($this, 'WpScp_pinterest_post_event'), 30, 1);
        add_action('WpScp_pinterest_post', array($this, 'WpScp_pinterest_post'), 15, 2);
        // republish hook
        $this->schedule_republish_social_share_hook();
    }
    /**
     * Schedule Republish Social Share
     * @since 2.5.0
     * @return hooks
     */
    public function schedule_republish_social_share_hook()
    {
        if (\WPSP\Helper::get_settings('is_republish_social_share')) {
            add_action('wpscp_pro_schedule_republish_share', array($this, 'wpscp_pro_republish_pinterest_post'), 15, 1);
        }
    }
    /**
     * 'WpScp_pinterest_post_event' should be triggered by 'publish_future_post' action
     *
     */
    public function WpScp_pinterest_post_event($post_id)
    {
        //post data
        $post_details = $post_id;
        if ( !is_object( $post_id ) ){
            $post_details = get_post($post_id);
        }

        if ($post_details->post_status == 'publish') {
            // Schedule the actual event
            wp_schedule_single_event(time(), 'WpScp_pinterest_post', array($post_details->ID));
        }
    }


    /**
     * Saved Post Meta info
     *
     */
    public function save_metabox_social_share_metabox($post_id, $response, $ID)
    {
        if (get_post_meta($post_id, '__wpscppro_social_share_pinterest', true) != "") {
            $root_meta_data = get_post_meta($post_id, '__wpscppro_social_share_pinterest', true);
            // new meta value push
            array_push($root_meta_data, $response);
            update_post_meta($post_id, '__wpscppro_social_share_pinterest', $root_meta_data);
        } else {
            $root_meta_data = array();
            array_push($root_meta_data, $response);
            add_post_meta($post_id, '__wpscppro_social_share_pinterest', $root_meta_data);
        }
        $count_meta_key = '__wpsp_pinterest_share_count_'.$ID;
        $old_share_count = get_post_meta( $post_id, $count_meta_key, true );
        if( $old_share_count != '' ) {
            update_post_meta($post_id, $count_meta_key, intval( $old_share_count ) + 1);
        }else{
            add_post_meta($post_id, $count_meta_key, 1);
        }
    }
    /**
     * Build formated content for share
     * @param post_id, board_name
     * @return array
     * @since 2.5.1
     */
    public function get_create_pin_args($post_id, $board_name, $board_name_key, $section_name, $instant_share = false)
    {
        $has_url = false;
        $post_details = get_post($post_id);
        $PostTitle = get_the_title($post_id);
        $PostPermalink = esc_url(get_permalink($post_id));;
        $board_type = get_post_meta($post_id, '_wpscppro_pinterestboardtype', true);
        $customThumbnailID = get_post_meta($post_id, '_wpscppro_custom_social_share_image', true);
        
        if ( $customThumbnailID != "" && $customThumbnailID != 0 ) {
            $customThumbnail = wp_get_attachment_image_src($customThumbnailID, 'full', false);
            $PostThumbnailURI = ($customThumbnail != false ? $customThumbnail[0] : '');
        } else {
            if( has_post_thumbnail($post_id) ) {
                $PostThumbnailURI = get_the_post_thumbnail_url($post_id, 'full');
            }else{
                $featured_image_id = Helper::get_featured_image_id_from_request();
                if( !empty( $featured_image_id ) ) {
                    $PostThumbnailURI = wp_get_attachment_image_url($featured_image_id, 'full');
                }
            }
        }
        if(!$instant_share && $board_type === 'custom') {
            // overriding default board name from meta.
            $custom_board_name = get_post_meta($post_id, '_wpscppro_pinterest_board_name', true);
            if($custom_board_name && !empty($custom_board_name[$board_name_key])){
                $board_name = $custom_board_name[$board_name_key];
            }
            else{
                $board_name = '';
            }
            $custom_section_name = get_post_meta($post_id, '_wpscppro_pinterest_section_name', true);
            if($custom_section_name && !empty($custom_section_name[$board_name_key])){
                $section_name = $custom_section_name[$board_name_key];
            }
            else{
                $section_name = '';
            }
        }
        if(is_object($board_name)){
            $board_name = $board_name->value;
        }
        if(is_object($section_name)){
            $section_name = $section_name->value;
        }

        // tags
        $hashTags = (($this->getPostHasTags($post_id, 'pinterest', $this->is_category_as_tags) != false) ? $this->getPostHasTags($post_id, 'pinterest', $this->is_category_as_tags) : '');
        if ($this->is_category_as_tags == true) {
            $hashTags .= ' ' . $this->getPostHasCats($post_id);
        }

        // content
        if ($this->content_source === 'excerpt' && has_excerpt($post_details->ID)) {
            $desc = wp_strip_all_tags($post_details->post_excerpt);
        } else {
            $desc = wp_strip_all_tags($post_details->post_content);
            if( is_visual_composer_post($post_id) && class_exists('WPBMap') ){
                \WPBMap::addAllMappedShortcodes();
                $desc = Helper::strip_all_html_and_keep_single_breaks(do_shortcode($desc));
            }
        }
        if(strpos($this->template_structure, '{url}') !== false){
            $has_url = true;
            $this->template_structure = str_replace('{url}', '', $this->template_structure);
        }
        if(strpos($this->template_structure, '{title}') !== false){
            $this->template_structure = str_replace('{title}', '', $this->template_structure);
        }
        else{
            $PostTitle = '';
        }

        $note_content = $this->social_share_content_template_structure(
            $this->template_structure,
            '',
            $desc,
            '',
            $hashTags,
            $this->note_limit,
            null,
            'pinterest'
        );
        // main arguments
        $pinterest_create_args = array(
            "title"       => apply_filters('wpsp_social_share_title', html_entity_decode($PostTitle), get_called_class(), $PostPermalink, $post_id),
            "description" => substr($note_content, 0, $this->note_limit),
            'link'        => $has_url ? $PostPermalink : '',
            "board_id"    => $board_name,
        );
        if($section_name){
            $pinterest_create_args['board_section_id'] = $section_name;
        }
        if ($this->is_set_image_link === true && $PostThumbnailURI) {
            $pinterest_create_args['media_source'] = [
                'source_type' => 'image_url',
                'url'         => $PostThumbnailURI,
            ];
        }
        return $pinterest_create_args;
    }

    /**
     * Main share method
     * all logic witten here
     * @since 2.5.0
     * @return array
     */
    public function remote_post($post_id, $board_name, $section_name, $profile_key, $force_share = false, $instant_share = false)
    {
        if( is_object( $board_name ) ) {
            $count_meta_key = '__wpsp_pinterest_share_count_'.$board_name->value;
        }else{
            $count_meta_key = '__wpsp_pinterest_share_count_'.$board_name;
        }
        // check post is skip social sharing
        // if (get_post_meta($post_id, '_wpscppro_dont_share_socialmedia', true) == 'on') {
        //     return;
        // }
        $dont_share     = get_post_meta($post_id, '_wpscppro_dont_share_socialmedia', true);
        // get social share type 
        $get_share_type =   get_post_meta($post_id, '_pinterest_share_type', true);
        if( $get_share_type === 'custom' ) {
            $get_all_selected_profile     = get_post_meta($post_id, '_selected_social_profile', true);
            if( is_object( $board_name ) ) {
                $check_profile_exists         = Helper::is_profile_exits( $board_name->value, $get_all_selected_profile );
            }else{
                $check_profile_exists         = Helper::is_profile_exits( $board_name, $get_all_selected_profile );
            }
            if( empty( $check_profile_exists ) ) {
                return;
            }
            if( !empty( $check_profile_exists->pinterest_custom_section_name ) ) {
                $section_name = $check_profile_exists->pinterest_custom_section_name;
            }
        }
        
        if ($dont_share  == 'on' || $dont_share == 1 ) {
            return;
        }

        if( ( get_post_meta( $post_id, $count_meta_key, true ) ) && $this->post_share_limit != 0 && get_post_meta( $post_id, $count_meta_key, true ) >= $this->post_share_limit ) {
            return array(
                'success' => false,
                'log' => __('Your max share post limit has been executed!!','wp-scheduled-posts')
            );
        }

        if(get_post_meta($post_id, '_wpsp_is_pinterest_share', true) == 'on' || $force_share) {
            $errorFlag = false;
            $response = '';

            $app_access_token = \WPSP\Helper::get_access_token('pinterest', $profile_key);
            $pin_args = $this->get_create_pin_args($post_id, $board_name, md5($app_access_token), $section_name, $instant_share);
            $_board_name = '';
            if( is_object( $board_name ) ) { 
                $_board_name = $board_name->label;
            }else{
                $_board_name = $board_name;
            }
            try {
                $pinterest = new \DirkGroenen\Pinterest\Pinterest(null, null);
                $pinterest->auth->setOAuthToken($app_access_token);
                $results = $pinterest->pins->create($pin_args);
                if ($results != "") {
                    $shareInfo = array(
                        'share_id' => $results->id,
                        'publish_date' => time(),
                    );
                    if( is_object( $board_name ) ) {
                        $this->save_metabox_social_share_metabox($post_id, $shareInfo, $profile_key, $board_name->value);
                    }else{
                        $this->save_metabox_social_share_metabox($post_id, $shareInfo, $profile_key, $board_name);
                    }
                }
                $errorFlag = true;
                $results = json_decode($results, true);
                $response = [];
                if( empty( $results['id'] ) && empty( $response['created_at'] ) ) {
                    $errorFlag = false;
                    throw new \Exception("this Pinterest board doesn't exits!!", 404);
                }
                if (array_key_exists('id', $results) && array_key_exists('created_at', $results)) {
                    $response['message']    = __('Your post has been successfully shared!', 'wp-scheduled-posts');
                    $response['id']         = $results['id'];
                    $response['created_at'] = $results['created_at'];
                }
            } catch (\Exception $e) {
                $errorFlag = false;
                $response = $_board_name . ' - ' . $e->getMessage();
            }
            return array(
                'success' => $errorFlag,
                'log' => $response
            );
        }
        return;
    }

    /**
     * Schedule Republish
     */
    public function wpscp_pro_republish_pinterest_post($post_id)
    {
        // check post is skip social sharing
        // if (get_post_meta($post_id, '_wpscppro_dont_share_socialmedia', true) == 'on') {
        //     return;
        // }
        $dont_share     = get_post_meta($post_id, '_wpscppro_dont_share_socialmedia', true);
        if ($dont_share  == 'on' || $dont_share == 1 ) {
            return;
        }
        $profiles = \WPSP\Helper::get_social_profile(WPSCP_PINTEREST_OPTION_NAME);
        if (is_array($profiles) && count($profiles) > 0) {
            foreach ($profiles as $profile_key => $profile) {
                // skip if status is false
                if ($profile->status == false) {
                    continue;
                }
                // share
                $this->remote_post(
                    $post_id,
                    $profile->default_board_name,
                    $profile->defaultSection,
                    $profile_key,
                    true
                );
            }
        }
    }
    /**
     * Schedule Publish
     */
    public function WpScp_pinterest_post($post_id)
    {
        // check post is skip social sharing
        // if (get_post_meta($post_id, '_wpscppro_dont_share_socialmedia', true) == 'on') {
        //     return;
        // }
        $dont_share     = get_post_meta($post_id, '_wpscppro_dont_share_socialmedia', true);
        if ($dont_share  == 'on' || $dont_share == 1 ) {
            return;
        }
        $profiles = \WPSP\Helper::get_social_profile(WPSCP_PINTEREST_OPTION_NAME);
        if (is_array($profiles) && count($profiles) > 0) {
            foreach ($profiles as $profile_key => $profile) {
                // skip if status is false
                if ($profile->status == false) {
                    continue;
                }
                // share
                $this->remote_post(
                    $post_id,
                    $profile->default_board_name,
                    $profile->defaultSection,
                    $profile_key,
                    true
                );
            }
        }
    }


    public function socialMediaInstantShare($post_id, $board_name, $section_name, $profile_key, $is_share_on_publish)
    {
        $response = $this->remote_post($post_id, $board_name, $section_name, $profile_key, true, true);
        if( $is_share_on_publish ) {
            return;
        }
        if ($response['success'] == false) {
            wp_send_json_error($response['log']);
        } else {
            wp_send_json_success($response['log']);
        }
    }

}
