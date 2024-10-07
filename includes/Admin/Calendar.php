<?php

namespace WPSP\Admin;

use WP;
use WPSP\Helper;
use WP_REST_Response;
use WP_Error;
use WPSP_PRO\Scheduled\Published;

class Calendar
{

    public function __construct()
    {
        $this->hooks();
    }
    /**
     * Calendar Hooks
     * @method hooks
     * @since 3.0.1
     */
    public function hooks()
    {
        add_action('rest_api_init', array($this, 'wpscp_register_custom_route'));
        // add_action('wp_ajax_wpscp_calender_ajax_request', array($this, 'calender_ajax_request_php'));
        // add_action('wp_ajax_wpscp_delete_event', array($this, 'delete_event_action'));
        add_filter('wpsp_pre_eventDrop', [$this, 'wpsp_pre_eventDrop'], 10, 4 );
        add_filter('wpsp_eventDrop_posts', [$this, 'wpsp_eventDrop_posts'], 10, 2 );
    }

    /**
     *
     * @param WP_REST_Request $request
     * @return bool
     */
    public function permission_callback() {
        return current_user_can('edit_posts');
    }

    /**
     *
     * @param WP_REST_Request $request
     * @return bool
     */
    public function edit_permission_callback($request) {
        $id = $request->get_param('ID');
        if(!empty($id)){
            return current_user_can('edit_post', $id);
        }
        return current_user_can('publish_posts');
    }

    /**
     *
     * @param WP_REST_Request $request
     * @return bool
     */
    public function quick_edit_get_permission_callback($request) {
        return current_user_can('edit_post', $request->get_param('postId'));
    }

    /**
     *
     * @param WP_REST_Request $request
     * @return bool
     */
    public function delete_permission_callback($request) {
        return current_user_can('delete_post', $request->get_param('ID'));
    }

    public function wpscp_register_custom_route()
    {
        register_rest_route(
            'wpscp/v1',
            '/calendar',
            array(
                'methods'             => \WP_REST_Server::EDITABLE,
                'callback'            => array($this, 'wpscp_future_post_rest_route_output'),
                'permission_callback' => [$this, 'permission_callback'],
                'args'                => [
                    'post_type' => [
                        'required' => true,
                        'type'     => 'array',
                        'default'  => [],
                    ],
                    'taxonomy' => [
                        'required' => true,
                        'type'     => 'array',
                        'default'  => [],
                    ],
                    'activeStart' => [
                        'required' => true,
                    ],
                    'activeEnd' => [
                        'required' => true,
                    ],
                ],
            )
        );

        register_rest_route('wpscp/v1', '/posts', array(
            'methods'             => 'POST',
            'callback'            => [$this, 'get_draft_posts'],
            'permission_callback' => [$this, 'permission_callback'],
        ));

        register_rest_route(
            'wpscp/v1',
            '/get_tax_terms',
            array(
                'methods'             => 'GET',
                'callback'            => array($this, 'get_tax_terms'),
                'permission_callback' => [$this, 'permission_callback'],
            )
        );

        register_rest_route(
            'wpscp/v1',
            '/post',
            array(
                array(
                    'methods'             => \WP_REST_Server::READABLE,
                    'callback'            => [$this, 'quick_edit_get_post'],
                    'permission_callback' => [$this, 'quick_edit_get_permission_callback'],
                ),
                array(
                    'methods'             => \WP_REST_Server::EDITABLE,
                    'callback'            => [$this, 'calender_ajax_request_php'],
                    'permission_callback' => [$this, 'edit_permission_callback'],
                ),
                array(
                    'methods'             => \WP_REST_Server::DELETABLE,
                    'callback'            => [$this, 'delete_event_action'],
                    'permission_callback' => [$this, 'delete_permission_callback'],
                ),
            )
        );

    }


    // Define the callback function for the custom route
    public function get_draft_posts( $request ) {
        // Get the query parameters from the request
        // post type
        $post_type        = $request->get_param('post_type');
        $posts_per_page   = $request->get_param('posts_per_page');
        $page             = $request->get_param('page');
        $post_type        = !empty($post_type) ? $post_type : [];
        $taxonomies       = $request->get_param('taxonomy');
        $taxonomies       = !empty($taxonomies) ? $taxonomies : [];
        $allow_post_types = Helper::get_all_allowed_post_type();

        if(empty($post_type)){
            $post_type = $allow_post_types;
        }
        else if(in_array('elementorlibrary', $post_type)){
            $post_type   = array_diff($post_type, ['elementorlibrary']);
            $post_type[] = 'elementor_library';
        }

        $post_type = array_intersect($post_type, $allow_post_types);


        // Create a new WP_Query object with the parameters
        $query = new \WP_Query(array(
            'post_type'      => $post_type,
            'tax_query'      => $this->get_tax_query($taxonomies),
            'post_status'    => array('draft', 'pending'),
            'posts_per_page' => $posts_per_page,
            'paged'          => $page,
        ));

        // Check if the query found any posts
        if ( $query->have_posts() ) {
            $allData = array();
            // Return the posts as a JSON response
            while ($query->have_posts()) : $query->the_post();
                do_action('wpscp_calender_the_post');
                $allData[] = $this->get_post_data();
            endwhile;
            wp_reset_postdata();

            return rest_ensure_response( $allData );
        } else {
        // Return an empty array as a JSON response
            return rest_ensure_response( array() );
        }
    }

    public function __get_tax_query($taxonomies, $post_type) {
        // If $post_type is a string, convert it to an array to handle both types
        if (!is_array($post_type)) {
            $post_type = array($post_type);
        }
    
        $taxonomies = array_values(array_map(function($item) {
            $parts = explode('.', $item);
            return end($parts);
        }, $taxonomies));
        $tax_query = array();
        foreach ($post_type as $type) {
            $registered_taxonomies = get_object_taxonomies($type, 'objects');
            foreach ($registered_taxonomies as $taxonomy_name => $taxonomy_object) {
                if (!empty($taxonomies) && is_array($taxonomies)) {
                    if (!empty($terms)) {
                        $tax_query[] = array(
                            'taxonomy' => $taxonomy_name,  // The taxonomy name (e.g., category, tag, custom taxonomy)
                            'field'    => 'slug',          // We're using the term slug
                            'terms'    => $taxonomies,          // The extracted term slugs
                            'operator' => 'IN',            // Matching terms
                        );
                    }
                }
            }
        }
    
        // Return the tax_query array if not empty, otherwise return an empty array
        return !empty($tax_query) ? $tax_query : array();
    }
    

    public function get_tax_terms($request){
        $post_types       = $request->get_param('post_type');
        $allow_post_types = Helper::get_all_allowed_post_type();
        $allow_categories = Helper::get_settings('allow_categories');
        $allow_post_types = (!empty($allow_post_types) ? $allow_post_types : array('post'));
        $post_types       = array_intersect($post_types, $allow_post_types);
        $tax_terms        = Helper::get_all_tax_term($post_types ? $post_types : $allow_post_types);
        $return           = [];
        foreach ($tax_terms as $tax => $terms) {
            $return[] = [
                'label'   => $tax,
                'options' => array_values(array_map(function($term){
                    return [
                        'term_id'  => $term['term_id'],
                        'label'    => $term['name'],
                        'slug'     => $term['slug'],
                        'taxonomy' => $term['taxonomy'],
                        'postType' => $term['postType'],
                        'value'    => "{$term['postType']}.{$term['taxonomy']}.{$term['slug']}",
                    ];
                }, $terms)),
            ];
        }
        return $return;
    }

    public function get_tax_query($taxonomies){
        $tax_query = [];
        if(!empty($taxonomies)){
            foreach ($taxonomies as $key => $value) {
                if(empty($tax_query[$value['taxonomy']])){
                    $tax_query[$value['taxonomy']] = array(
                        'taxonomy' => $value['taxonomy'],
                        'field'    => 'slug',
                        'terms'    => [$value['slug']],
                    );
                }
                else{
                    $tax_query[$value['taxonomy']]['terms'][] = $value['slug'];
                }
            }
            $tax_query = array_merge( ['relation' => 'OR'], $tax_query );
        }
        return $tax_query;
    }

    /**
     * Calendar Rest Route Output
     * @method wpscp_future_post_rest_route_output
     * @param  \WP_REST_Request $request
     * @version 3.0.1
     */
    public function wpscp_future_post_rest_route_output($request)
    {
        global $wpdb;
        // post type []
        $post_type  = $request->get_param('post_type');
        $taxonomies = $request->get_param('taxonomy');
        $taxonomies = !empty($taxonomies) ? $taxonomies : [];
        $allow_post_types = \WPSP\Helper::get_all_allowed_post_type();

        if(empty($post_type)){
            $post_type = $allow_post_types;
        }
        else if(is_array($post_type) && in_array('elementorlibrary', $post_type)){
            $post_type   = array_diff($post_type, ['elementorlibrary']);
            $post_type[] = 'elementor_library';
        }
        $post_type  = !empty($post_type) ? $post_type : ['post'];

        // check if all $post_type s exists in $allow_post_types
        $post_type = array_intersect($post_type, $allow_post_types);

        $first_day = $request->get_param('activeStart');
        $first_day = (!empty($first_day) ? $first_day : date('Y/m/01', current_time('timestamp')));
        $last_day = $request->get_param('activeEnd');
        $last_day = (!empty($last_day) ? $last_day : date('Y/m/t', current_time('timestamp')));


        // query
        $query_1 = new \WP_Query(array(
            'post_type'      => $post_type,
            'post_status'    => array('future', 'publish'),
            'posts_per_page' => -1,
            'date_query'     => array(
                'after'  => $first_day,
                'before' => $last_day,
            ),
            'tax_query' => $this->get_tax_query($taxonomies),
        ));
        $posts_1 = $query_1->get_posts();

        $post_type_placeholders = implode(',', array_fill(0, count($post_type), '%s'));
        $tax_join = '';
        $tax_where = '';
        if(!empty($tax_query)){
            $_tax_query = new \WP_Tax_Query($tax_query);
            $_tax_query = $_tax_query->get_sql( $wpdb->posts, 'ID');
            if(!empty($_tax_query)){
                $tax_join  = $_tax_query['join'];
                $tax_where = $_tax_query['where'];
            }
        }
        $query = $wpdb->prepare( "
            SELECT $wpdb->posts.*
            FROM $wpdb->posts
            INNER JOIN $wpdb->postmeta ON ( $wpdb->posts.ID = $wpdb->postmeta.post_id )
            $tax_join
            WHERE 1=1 AND (
                ( $wpdb->postmeta.meta_key = '_wpscp_schedule_republish_date' AND CONVERT($wpdb->postmeta.meta_value, DATE) BETWEEN %s AND %s )
            )
            $tax_where
            AND $wpdb->posts.post_type IN ($post_type_placeholders)
            AND $wpdb->posts.post_status = 'publish'
        ", $first_day, $last_day, ...$post_type );

        $posts_2 = $wpdb->get_results( $query );

        $allData = array();

        $allData = $this->calendar_view($posts_1, $allData);
        $allData = $this->calendar_view($posts_2, $allData, true);
        $allData = $this->advanced_scheduled_view($post_type, $allData);
        return $allData;
    }

    function get_formatted_taxonomy_terms_for_post_types($taxonomy = 'category') {
        $post_types = get_post_types(array('public' => true), 'names');
        $formatted_terms = array();
        foreach ($post_types as $post_type) {
            $terms = get_terms(array(
                'taxonomy' => $taxonomy,
                'hide_empty' => false,
            ));
    
            if (!is_wp_error($terms)) {
                foreach ($terms as $term) {
                    $formatted_terms[] = array(
                        'term_id'   => $term->term_id,
                        'label'     => $term->name,
                        'slug'      => $term->slug,
                        'taxonomy'  => $term->taxonomy,
                        'postType'  => $post_type,
                        'value'     => "{$post_type}.{$term->taxonomy}.{$term->slug}"
                    );
                }
            }
        }
    
        return $formatted_terms;
    }

    protected function advanced_scheduled_view( $post_type, $allData ) {
        $args = array(
            'post_type'      => $post_type,
            'post_status'    => 'publish',
            'meta_query'     => array(
                array(
                    'key'     => '_wpscppro_advance_schedule_date',
                    'compare' => 'EXISTS',
                ),
            ),
            'posts_per_page'    => -1,
        );
        $posts = new \WP_Query($args);
        if ($posts->have_posts()) {
            while ($posts->have_posts()) {
                $posts->the_post();
                $advance_schedule_date = $this->wpsp_get_advance_schedule_date( get_the_ID() );
                if(empty($advance_schedule_date)){
                    continue;
                }
                $allData[] = $this->get_advanced_post_data();
            }
            wp_reset_postdata();
        }
        return $allData;
    }

    public function get_advanced_post_data(){
        $adv_data = get_post_meta(get_the_ID(), 'wpscp_pending_schedule', true);
        $advance_schedule_date = $this->wpsp_get_advance_schedule_date( get_the_ID() );
        $advance_schedule_date = get_gmt_from_date( $advance_schedule_date, 'Y-m-d H:i:s' );
        $post_title = '';
        if( !empty( $adv_data['post_title'] ) ) {
            $post_title = wp_trim_words( $adv_data['post_title'], 3, '...');
        }else{
            $post_title = wp_trim_words(get_the_title(), 3, '...');
        }

        return array(
            'postId'   => get_the_ID(),
            'title'    => $post_title,
            'href'     => get_the_permalink(),
            'edit'     => get_edit_post_link(get_the_ID(), null),
            'postType' => get_post_type(),
            'status'   => $this->get_post_status(get_the_ID(), false, true),
            'postTime' => $this->get_post_time('g:i a', $advance_schedule_date),
            'start'    => $this->get_post_time('Y-m-d 00:00:00', $advance_schedule_date),
            'end'      => $this->get_post_time('Y-m-d H:i:s', $advance_schedule_date),
            'allDay'   => false,
        );
    }

    protected function calendar_view($posts, $allData, $republish = false){
        global $post;
        if ($posts && is_array($posts)) {
            foreach ( $posts as $post ) {
                setup_postdata( $post );

                do_action('wpscp_calender_the_post');
                $republish_date = $republish ? get_post_meta(get_the_ID(), '_wpscp_schedule_republish_date', true) : null;
                if($republish && empty($republish_date)){
                    continue;
                }
                $allData[] = $this->get_post_data($republish);
            }
            wp_reset_postdata();
        }
        return $allData;
    }

    public function get_post_data($republish = false){
        $republish_date = null;
        if( $republish ) {
            $republish_date = get_post_meta(get_the_ID(), '_wpscp_schedule_republish_date', true);
            $republish_date = get_gmt_from_date( $republish_date, 'Y-m-d H:i:s' );
        }
        return array(
            'postId'   => get_the_ID(),
            'title'    => wp_trim_words(get_the_title(), 3, '...'),
            'href'     => get_the_permalink(),
            'edit'     => get_edit_post_link(get_the_ID(), null),
            'postType' => get_post_type(),
            'status'   => $this->get_post_status(get_the_ID(), $republish),
            'postTime' => $this->get_post_time('g:i a', $republish_date),
            'start'    => $this->get_post_time('Y-m-d 00:00:00', $republish_date),
            'end'      => $this->get_post_time('Y-m-d H:i:s', $republish_date),
            'allDay'   => false,
        );
    }

    // Define a function to get the post time
    public function get_post_time($format, $republish_date = '') {
        // If republish date is empty, use the current post date
        if (empty($republish_date)) {
            return get_the_date($format);
        }
        // Otherwise, use the republish date
        else {
            return wp_date($format, strtotime($republish_date));
        }
    }

    public function get_post_status($post_id, $republish = false, $adv = false){
        $status         = get_post_status($post_id);
        $scheduled      = get_post_meta($post_id, 'wpscp_pending_schedule', true);
        $el_scheduled   = get_post_meta($post_id, 'wpscp_el_pending_schedule', true);
        $republish_date = $republish ? get_post_meta($post_id, '_wpscp_schedule_republish_date', true) : null;
        $adv_date = $adv ? $this->wpsp_get_advance_schedule_date( $post_id ) : null;

        if($status == 'publish' && !empty($republish_date)){
            $status = 'Republish';
        }
        else if($status == 'publish' && !empty($adv_date)){
            $status = 'Adv. Scheduled';
        }
        else if($status == 'future' && !empty($el_scheduled['post_time'])){
            $status = 'Advanced Scheduled';
        }
        else if($status == 'future' && !empty($scheduled)){
            $status = 'Advanced Scheduled';
        }
        else if($status == 'future'){
            $status = 'Scheduled';
        }
        else if($status == 'publish'){
            $status = 'Published';
        }

        return ucwords($status);
    }

    public function wpsp_get_advance_schedule_date( $post_id ) {
        $advance_schedule_date = get_post_meta( $post_id, '_wpscppro_advance_schedule_date', true);
        $wpsp_scheduled_data = get_post_meta( $post_id, 'wpscp_pending_schedule',true );
        if( empty( $advance_schedule_date ) && !empty( $wpsp_scheduled_data['meta']['_wpscppro_advance_schedule_date'] ) ) {
            $advance_schedule_date = $wpsp_scheduled_data['meta']['_wpscppro_advance_schedule_date'];
        }
        return $advance_schedule_date;
    }

    /**
     * Calendar Main Ajax Operation
     * @method calender_ajax_request_php
     * @param  \WP_REST_Request $request
     * @version 3.0.1
     */
    public function calender_ajax_request_php($request)
    {
        $allow_post_types = Helper::get_all_allowed_post_type();
        $calendar_schedule_time = \WPSP\Helper::get_settings('calendar_schedule_time');
        $_post_status           = $request->get_param('post_status');
        $post_status            = $_post_status;

        if ($post_status != '') {
            $post_status = (($post_status == 'Scheduled') ? 'future' : 'draft');
        }

        $type        = $request->get_param('type');
        $post_type   = $request->get_param('post_type');
        $dateStr     = $request->get_param('date') ?? current_time('mysql');
        $postid      = $request->get_param('ID');
        $postTitle   = $request->get_param('postTitle');
        $postContent = $request->get_param('postContent');
        if(!in_array($post_type, $allow_post_types)){
            return new WP_Error('rest_post_update_error', __('Post type isn\'t allowed in Settings page.', 'wp-scheduled-posts'), array('status' => 400));
        }

        if(in_array($type, ['newDraft', 'editDraft', 'addEvent', 'editEvent', 'editEvent'])) {
            $postdateformat = $dateStr;
            $postdate_gmt   = get_gmt_from_date($dateStr);
        } else {
            // $postdate       = new \DateTime(substr($dateStr, 0, 25));
            $postdateformat = get_date_from_gmt($dateStr);
            $postdate_gmt   = $dateStr;
        }


        /**
         * Post Status Change and Date modifired
         */
        if ($type == 'addEvent' || $type == 'editEvent') {

            // only works if update event is fired
            if (!empty($postid)) {
                $post_id = wp_update_post(array(
                    'ID'            => $postid,
                    'post_type'     => $post_type,
                    'post_title'    => wp_strip_all_tags($postTitle),
                    'post_content'  => $postContent,
                    'post_status'   => 'future',
                    'post_author'   => get_current_user_id(),
                    'post_date'     => (isset($postdateformat) ? $postdateformat : ''),
                    'post_date_gmt' => (isset($postdate_gmt) ? $postdate_gmt : ''),
                    'edit_date'     => true,
                ), true);
                return $this->get_rest_result($post_id);
            } else {
                // only work new event created
                $post_id = wp_insert_post(array(
                    'post_title'    => wp_strip_all_tags($postTitle),
                    'post_type'     => $post_type,
                    'post_content'  => $postContent,
                    'post_status'   => 'future',
                    'post_author'   => get_current_user_id(),
                    'post_date'     => (isset($postdateformat) ? $postdateformat : ''),
                    'post_date_gmt' => (isset($postdate_gmt) ? $postdate_gmt : ''),
                    'edit_date'     => true,
                ), true);
                return $this->get_rest_result($post_id);
            }
        }
        // moving event from calendar to calendar
        // moving event from sidebar to calendar
        else if ($type == 'eventDrop') {
            $change = apply_filters('wpsp_pre_eventDrop', null, $postid, $postdateformat, $postdate_gmt);
            if($change){
                $post_id = $change;
            }
            else{
                $post_id = wp_update_post(array(
                    'ID'            => $postid,
                    'post_type'     => $post_type,
                    'post_status'   => 'future',
                    'post_date'     => (isset($postdateformat) ? $postdateformat : ''),
                    'post_date_gmt' => (isset($postdate_gmt) ? $postdate_gmt : ''),
                    'edit_date'     => true,
                ), true);
            }
            return $this->get_rest_result($post_id);
        }
        // dropping event to sidebar
        else if ($type == 'draftDrop') {
            $post_id = wp_update_post(array(
                'ID'          => $postid,
                'post_type'   => $post_type,
                'post_status' => 'draft',
            ), true);
            return $this->get_rest_result($post_id);
        }
        else if ($type == 'trashDrop') {
            $post_id = wp_update_post(array(
                'ID'          => $postid,
                'post_type'   => $post_type,
                'post_status' => 'trash',
            ), true);
            $response = array('message' => 'Post moved to Trash successfully', 'id' => $postid);
            return new WP_REST_Response($response, 200);
        }
        else if ($type == 'newDraft') {
            $post_id = wp_insert_post(array(
                'post_title'   => wp_strip_all_tags($postTitle),
                'post_type'    => $post_type,
                'post_content' => $postContent,
                'post_status'  => 'draft',
                'post_author'  => get_current_user_id(),
            ), true);
            return $this->get_rest_result($post_id);
        } else if ($type == 'editDraft') {
            $post_id = wp_update_post(array(
                'ID'            => $postid,
                'post_type'     => $post_type,
                'post_title'    => wp_strip_all_tags($postTitle),
                'post_content'  => $postContent,
                'post_status'   => 'draft',
                'post_author'   => get_current_user_id(),
                'post_date'     => (isset($postdateformat) ? $postdateformat : ''),
                'post_date_gmt' => (isset($postdate_gmt) ? $postdate_gmt : ''),
                'edit_date'     => true,
            ), true);
            return $this->get_rest_result($post_id);
        }  else if ($post_status != 'draft') { // future post date modify date
            $post_id = wp_update_post(array(
                'ID'            => $postid,
                'post_type'     => $post_type,
                'post_status'   => 'future',
                'post_date'     => (isset($postdateformat) ? $postdateformat : ''),
                'post_date_gmt' => (isset($postdate_gmt) ? $postdate_gmt : ''),
                'edit_date'     => true,
            ), true);
            return $this->get_rest_result($post_id);
        }

        $error_messages = array();

        if (empty($type)) {
            $error_messages[] = __('Type is required.', 'wp-scheduled-posts');
        }
        if(empty($error_message)){
            $error_messages[] = __('Something went wrong.', 'wp-scheduled-posts');
        }

        $error_message = implode(' ', $error_messages);
        return new WP_Error('rest_post_update_error', $error_message, array('status' => 400));
    }

    public function get_rest_result($post_id){
        global $post;
        if (!empty($post_id) && !is_wp_error($post_id)) {
            $post = get_post($post_id);
            setup_postdata( $post );
            $event_data = $this->get_post_data();
            wp_reset_postdata();
            return rest_ensure_response($event_data);
        }
        else{

            if(is_wp_error($post_id)){
                return $post_id;
            }
            else{
                return new WP_Error('rest_post_update_error', __('Something went wrong.', 'wp-scheduled-posts'), array('status' => 400));
            }

            // return $post_id;
        }
    }

    /**
     * Ajax Request for quick edit
     * @method quick_edit_get_post
     * @param  \WP_REST_Request $request
     * @version 3.0.1
     */
    function quick_edit_get_post( $request ) {
        $post_id          = $request->get_param('postId');
        $allow_post_types = Helper::get_all_allowed_post_type();

        if(!in_array(get_post_type($post_id), $allow_post_types)){
            return new WP_Error('rest_post_update_error', __('Post type isn\'t allowed in Settings page.', 'wp-scheduled-posts'), array('status' => 400));
        }

        $post = get_post((int) $post_id);
        if ($post) {
            $posts = apply_filters('wpsp_eventDrop_posts', [$post], $post_id);
            $response = new WP_REST_Response($posts, 200);
            return $response;
        }
        $error = array('error' => 'Post not found');
        $response = new WP_REST_Response($error, 404);
        return $response;
    }

    /**
     * Ajax Request for delete event action
     * @method delete_event_action
     * @param  \WP_REST_Request $request
     * @version 3.0.1
     */
    public function delete_event_action($request)
    {
        $postId = $request->get_param('ID');
        $status = $request->get_param('status');

        if ($postId != "") {
            $allow_post_types = Helper::get_all_allowed_post_type();
            if(!in_array(get_post_type($postId), $allow_post_types)){
                return new WP_Error('rest_post_update_error', __('Post type isn\'t allowed in Settings page.', 'wp-scheduled-posts'), array('status' => 400));
            }
            
            if( 'Adv. Scheduled' == $status ) {
                $published = new Published();
                $published->wpscp_pending_schedule_fn($postId, $status );
                $response = array('message' => 'Advanced schedule removed', 'id' => $postId, 'status' => $status );
                return new WP_REST_Response($response, 200);
            }

            $result = wp_delete_post($postId, false);
            if ($result === false) {
                $error = new WP_Error('delete_failed', 'Failed to delete post', array('status' => 500));
                return new WP_REST_Response($error, 500);
            }
            $response = array('message' => 'Post deleted successfully', 'id' => $postId, 'status' => $status);
            return new WP_REST_Response($response, 200);
        }
        $error = new WP_Error('missing_id', 'ID parameter is missing', array('status' => 400));
        return new WP_REST_Response($error, 400);
    }

    public function wpsp_pre_eventDrop($return, $pid, $postdateformat, $postdate_gmt){
        $republish_date = get_post_meta($pid, '_wpscp_schedule_republish_date', true);
        if(!empty($republish_date) && 'publish' === get_post_status($pid)){
            update_post_meta($pid, '_wpscp_schedule_republish_date', get_date_from_gmt($postdate_gmt, 'Y/m/d H:i:s'));
            // update_post_meta($pid, '_wpscp_schedule_republish_date', $postdateformat);
            return $pid;
        }

        return $return;
    }

    public function wpsp_eventDrop_posts($posts, $pid){
        foreach ($posts as $key => $published_post) {
            $republish_date = get_post_meta($published_post->ID, '_wpscp_schedule_republish_date', true);
            if(!empty($republish_date)){
                $published_post->post_status   = 'republish';
                $published_post->post_date     = date("Y-m-d H:i:s", strtotime($republish_date));
                $published_post->post_date_gmt = get_gmt_from_date($republish_date, 'Y-m-d H:i:s');
            }
        }

        return $posts;
    }
}
