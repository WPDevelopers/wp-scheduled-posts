<?php

namespace WPSP\Admin;

use WP;
use WPSP\Helper;
use WP_REST_Response;
use WP_Error;

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

    public function wpscp_register_custom_route()
    {
        register_rest_route(
            'wpscp/v1',
            '/calendar',
            array(
                'methods'  => \WP_REST_Server::EDITABLE,
                'callback' => array($this, 'wpscp_future_post_rest_route_output'),
                'permission_callback' => '__return_true',
                'args' => [
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
                    'month' => [
                        'required' => true,
                    ],
                    'year' => [
                        'required' => true,
                    ],
                ],
            )
        );

        register_rest_route( 'wpscp/v1', '/posts', array(
            'methods' => 'POST',
            'callback' => [$this, 'get_draft_posts'],
        ) );

        register_rest_route(
            'wpscp/v1',
            '/get_tax_terms',
            array(
                'methods'  => 'GET',
                'callback' => array($this, 'get_tax_terms'),
                'permission_callback' => '__return_true'
            )
        );

        register_rest_route(
            'wpscp/v1',
            '/post',
            array(
                array(
                    'methods' => \WP_REST_Server::READABLE,
                    'callback' => [$this, 'quick_edit_get_post'],
                ),
                array(
                    'methods' => \WP_REST_Server::EDITABLE,
                    'callback' => [$this, 'calender_ajax_request_php'],
                ),
                array(
                    'methods' => \WP_REST_Server::DELETABLE,
                    'callback' => [$this, 'delete_event_action'],
                ),
            )
        );

    }


    // Define the callback function for the custom route
    public function get_draft_posts( $request ) {
        // Get the query parameters from the request
        // post type
        $post_type  = $request->get_param('post_type');
        $post_type  = !empty($post_type) ? $post_type : [];
        $taxonomies = $request->get_param('taxonomy');
        $taxonomies = !empty($taxonomies) ? $taxonomies : [];

        if(empty($post_type)){
            $post_type = \WPSP\Helper::get_settings('allow_post_types');
        }
        else if(in_array('elementorlibrary', $post_type)){
            $post_type   = array_diff($post_type, ['elementorlibrary']);
            $post_type[] = 'elementor_library';
        }



        // Create a new WP_Query object with the parameters
        $query = new \WP_Query(array(
            'post_type'      => $post_type,
            'tax_query'      => $this->get_tax_query($taxonomies),
            'post_status'    => array('draft', 'pending'),
            'posts_per_page' => -1,
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

    public function get_tax_terms($request){
        $post_types       = $request->get_param('post_type');
        $allow_post_types = Helper::get_settings('allow_post_types');
        $allow_post_types = (!empty($allow_post_types) ? $allow_post_types : array('post'));
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
                        'value'    => "{$term['postType']}-{$term['taxonomy']}-{$term['slug']}",
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
        // post type
        $post_type  = $request->get_param('post_type');
        $taxonomies = $request->get_param('taxonomy');
        $taxonomies = !empty($taxonomies) ? $taxonomies : [];

        if(empty($post_type)){
            $post_type = \WPSP\Helper::get_settings('allow_post_types');
        }
        else if(is_array($post_type) && in_array('elementorlibrary', $post_type)){
            $post_type   = array_diff($post_type, ['elementorlibrary']);
            $post_type[] = 'elementor_library';
        }
        $post_type  = !empty($post_type) ? $post_type : ['post'];

        // date
        $now = new \DateTime('now');
        $now_month = $now->format('m');
        $now_year = $now->format('Y');
        // month
        $month = urldecode($request->get_param('month'));
        $month = (!empty($month) ? $month : $now_month);
        // year
        $year = urldecode($request->get_param('year'));
        $year = (!empty($year) ? $year : $now_year);

        $first_day = date('Y/m/01', strtotime("$year-$month-01"));
        $last_day  = date('Y/m/t', strtotime("$year-$month-01"));

        // query
        $query_1 = new \WP_Query(array(
            'post_type'      => $post_type,
            'post_status'    => array('future', 'publish'),
            'posts_per_page' => -1,
            'date_query'     => array(
                array(
                    'year'  => $year,
                    'month' => $month,
                ),
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
        return $allData;
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
        $republish_date = $republish ? get_post_meta(get_the_ID(), '_wpscp_schedule_republish_date', true) : null;

        return array(
            'postId'   => get_the_ID(),
            'title'    => wp_trim_words(get_the_title(), 3, '...'),
            'href'     => get_the_permalink(),
            'edit'     => get_edit_post_link(),
            'postType' => get_post_type(),
            'status'   => $this->get_post_status(get_the_ID(), $republish),
            'postTime' => $this->get_post_time('g:i a', $republish_date),
            'start'    => $this->get_post_time('Y-m-d', $republish_date),
            'end'      => $this->get_post_time('Y-m-d H:i:s', $republish_date),
            'allDay'   => false,
        );
    }

    public function get_event_data($post = null, $republish = false){
        $post           = get_post($post);
        $post_id        = empty( $post->ID ) ? get_the_ID() : $post->ID;
        $republish_date = $republish ? get_post_meta(get_the_ID(), '_wpscp_schedule_republish_date', true) : null;

        return array(
            'postId'   => $post_id,
            'title'    => wp_trim_words(get_the_title($post), 3, '...'),
            'href'     => get_the_permalink($post),
            'edit'     => get_edit_post_link($post),
            'postType' => get_post_type($post),
            'status'   => $this->get_post_status($republish, $post_id),
            'postTime' => $this->get_post_time('g:i a', $republish_date),
            'start'    => $this->get_post_time('Y-m-d', $republish_date),
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
            return date($format, strtotime($republish_date));
        }
    }

    public function get_post_status($post_id, $republish = false){
        $status         = get_post_status($post_id);
        $scheduled      = get_post_meta($post_id, 'wpscp_pending_schedule', true);
        $el_scheduled   = get_post_meta($post_id, 'wpscp_el_pending_schedule', true);
        $republish_date = $republish ? get_post_meta($post_id, '_wpscp_schedule_republish_date', true) : null;

        if($status == 'future' && !empty($scheduled)){
            $status = 'Advanced Scheduled';
        }
        else if($status == 'future' && !empty($el_scheduled['post_time'])){
            $status = 'Advanced Scheduled';
        }
        else if($status == 'publish' && !empty($republish_date)){
            $status = 'Republish';
        }
        else if($status == 'future'){
            $status = 'Scheduled';
        }
        else if($status == 'publish'){
            $status = 'Published';
        }

        return ucwords($status);
    }

    /**
     * Calendar Main Ajax Operation
     * @method calender_ajax_request_php
     * @param  \WP_REST_Request $request
     * @version 3.0.1
     */
    public function calender_ajax_request_php($request)
    {
        global $post;

        $calendar_schedule_time = \WPSP\Helper::get_settings('calendar_schedule_time');
        $post_status = $request->get_param('post_status');

        if ($post_status != '') {
            $post_status = (($post_status == 'Scheduled') ? 'future' : 'draft');
        }

        $type        = $request->get_param('type');
        $post_type   = $request->get_param('post_type');
        $dateStr     = $request->get_param('date');
        $postid      = $request->get_param('ID');
        $postTitle   = $request->get_param('postTitle');
        $postContent = $request->get_param('postContent');

        if($type == 'drop') {
            $default_schedule_time = '12:00 am';
            if (!empty($calendar_schedule_time)) {
                $default_schedule_time = $calendar_schedule_time;
            }

            $date_string = substr($dateStr, 0, 16) . $default_schedule_time;
            $postdate = new \DateTime($date_string);
            $postdateformat = $postdate->format('Y-m-d H:i:s');
            $postdate_gmt = ($postdateformat != "" ? get_gmt_from_date($postdateformat) : '');
        } else {
            $postdate = new \DateTime(substr($dateStr, 0, 25));
            $postdateformat = $postdate->format('Y-m-d H:i:s');
            $postdate_gmt = ($postdateformat != "" ? get_gmt_from_date($postdateformat) : '');
        }


        /**
         * Post Status Change and Date modifired
         */
        if ($type == 'drop') { // draft post to future post
            $post_id = wp_update_post(array(
                'ID'            => $postid,
                'post_status'   => 'future',
                'post_type'     => $post_type,
                'post_date'     => (isset($postdateformat) ? $postdateformat : ''),
                'post_date_gmt' => (isset($postdate_gmt) ? $postdate_gmt : ''),
                'edit_date'     => true,
            ));
            if (!is_wp_error($post_id)) {
                print json_encode(query_posts(array('p' => $post_id, 'post_type' => $post_type)));
            }
        } else if ($type == 'draftDrop') {
            $post_id = wp_update_post(array(
                'ID'          => $postid,
                'post_type'   => $post_type,
                'post_status' => 'draft',
            ));
            if (!is_wp_error($post_id)) {
                $taxonomies = \WPSP\Helper::get_all_post_terms($postid);
                $post = query_posts(array('p' => $post_id, 'post_type' => $post_type));
                $post[0]->taxonomies = $taxonomies;
                print json_encode($post);
            }
        } else if ($post_status == 'draft') {
            $post_id = wp_insert_post(array(
                'post_title'   => wp_strip_all_tags($postTitle),
                'post_type'    => $post_type,
                'post_content' => $postContent,
                'post_status'  => 'draft',
                'post_author'  => get_current_user_id(),
            ));

            if ($post_id != 0) {
                $post     = get_post($post_id);
                $response = array(
                    'id'      => $post->ID,
                    'title'   => $post->post_title,
                    'content' => $post->post_content,
                    'status'  => $post->post_status,
                    'author'  => $post->post_author,
                    'type'    => $post->post_type
                );
                return new WP_REST_Response($response, 200);
            }
            $response = array(
                'status'  => 'error',
                'message' => 'Invalid request'
            );
            return new WP_REST_Response($response, 400);

        } else if ($type == 'addEvent') {

            // only works if update event is fired
            if (!empty($postid)) {
                $postid = wp_update_post(array(
                    'ID'            => $postid,
                    'post_type'     => $post_type,
                    'post_title'    => wp_strip_all_tags($postTitle),
                    'post_content'  => $postContent,
                    'post_status'   => 'future',
                    'post_author'   => get_current_user_id(),
                    'post_date'     => (isset($postdateformat) ? $postdateformat : ''),
                    'post_date_gmt' => (isset($postdate_gmt) ? $postdate_gmt : ''),
                    'edit_date'     => true,
                ));
                if (!is_wp_error($postid)) {
                    $post = get_post($postid);
                    setup_postdata( $post );
                    $event_data = $this->get_post_data();
                    wp_reset_postdata();
                    return rest_ensure_response($event_data);
                }
                else{
                    // return wp error rest response
                    return $postid;
                }
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
                ));
                if (!is_wp_error($postid)) {
                    $post = get_post($postid);
                    setup_postdata( $post );
                    $event_data = $this->get_post_data();
                    wp_reset_postdata();
                    return rest_ensure_response($event_data);
                }
                else{
                    // return wp error rest response
                    return $postid;
                }
            }
        } else if ($type == 'eventDrop') {
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
                ));
            }
            if ($post_id != 0) {
                $posts = query_posts(array('p' => $post_id, 'post_type' => $post_type));
                $posts = apply_filters('wpsp_eventDrop_posts', $posts, $post_id);
                print(json_encode($posts));
            }
        } else if ($post_status != 'draft') { // future post date modify date
            wp_update_post(array(
                'ID'            => $postid,
                'post_type'     => $post_type,
                'post_status'   => 'future',
                'post_date'     => (isset($postdateformat) ? $postdateformat : ''),
                'post_date_gmt' => (isset($postdate_gmt) ? $postdate_gmt : ''),
                'edit_date'     => true,
            ));
        }
        exit();
    }

    /**
     * Ajax Request for quick edit
     * @method quick_edit_get_post
     * @param  \WP_REST_Request $request
     * @version 3.0.1
     */
    function quick_edit_get_post( $request ) {
        $post_id = $request->get_param('postId');
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
        if ($postId != "") {
            $result = wp_delete_post($postId, true);
            if ($result === false) {
                $error = new WP_Error('delete_failed', 'Failed to delete post', array('status' => 500));
                return new WP_REST_Response($error, 500);
            }
            $response = array('message' => 'Post deleted successfully', 'id' => $postId);
            return new WP_REST_Response($response, 200);
        }
        $error = new WP_Error('missing_id', 'ID parameter is missing', array('status' => 400));
        return new WP_REST_Response($error, 400);
    }

    public function wpsp_pre_eventDrop($return, $pid, $postdateformat, $postdate_gmt){
        $republish_date = get_post_meta($pid, '_wpscp_schedule_republish_date', true);
        if(!empty($republish_date)){
            update_post_meta($pid, '_wpscp_schedule_republish_date', get_date_from_gmt($postdate_gmt, 'Y/m/d H:i:s'));
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
