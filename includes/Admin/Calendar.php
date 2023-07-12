<?php

namespace WPSP\Admin;

use WPSP\Helper;

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
        add_action('wp_ajax_wpscp_calender_ajax_request', array($this, 'calender_ajax_request_php'));
        add_action('wp_ajax_wpscp_quick_edit', array($this, 'quick_edit_action'));
        add_action('wp_ajax_wpscp_delete_event', array($this, 'delete_event_action'));
        add_filter('wpsp_pre_eventDrop', [$this, 'wpsp_pre_eventDrop'], 10, 4 );
        add_filter('wpsp_eventDrop_posts', [$this, 'wpsp_eventDrop_posts'], 10, 2 );
    }

    public function wpscp_register_custom_route()
    {
        register_rest_route(
            'wpscp/v1',
            '/calendar',
            array(
                'methods'  => 'GET',
                'callback' => array($this, 'wpscp_future_post_rest_route_output'),
                'permission_callback' => '__return_true',
                'args' => [
                    'post_type' => [
                        'required' => true,
                        'type' => 'string',
                        'default' => 'post',
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
            'methods' => 'GET',
            'callback' => [$this, 'myplugin_get_posts'],
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
    }


    // Define the callback function for the custom route
    public function myplugin_get_posts( $request ) {
        // Get the query parameters from the request
        $post_type = $request->get_param( 'post_type' );
        $_page     = $request->get_param( 'page' );

        // Set the default post types to allow
        $allow_post_types = array( 'post', 'page' );

        // Create a new WP_Query object with the parameters
        $query = new \WP_Query(array(
            'post_type'         => ($post_type && $_page != 'schedulepress-calendar') ? $post_type : $allow_post_types,
            'post_status'       => array('draft', 'pending'),
            'posts_per_page'    => -1
        ));

        // Check if the query found any posts
        if ( $query->have_posts() ) {
            $allData = array();
            // Return the posts as a JSON response
            while ($query->have_posts()) : $query->the_post();
                do_action('wpscp_calender_the_post');

                array_push($allData, array(
                    'postId'   => get_the_ID(),
                    'title'    => wp_trim_words(get_the_title(), 3, '...'),
                    'href'     => get_the_permalink(),
                    'edit'     => get_edit_post_link(),
                    'postType' => get_post_type(),
                    'status'   => $this->get_post_status(get_the_ID()),
                    'postTime' => $this->get_post_time('g:i a'),
                    'start'    => $this->get_post_time('Y-m-d'),
                    'end'      => $this->get_post_time('Y-m-d H:i:s'),
                    'allDay'   => false,
                ));
            endwhile;
            wp_reset_postdata();

            return rest_ensure_response( $allData );
        } else {
        // Return an empty array as a JSON response
            return rest_ensure_response( array() );
        }
    }

    public function get_tax_terms(){
        $post_type        = Helper::get_post_types();
        $allow_post_types = Helper::get_settings('allow_post_types');
        $allow_post_types = (!empty($allow_post_types) ? $allow_post_types : array('post'));
        $tax_terms        = Helper::get_all_tax_term($post_type ? $post_type : $allow_post_types);

        return $tax_terms;

    }


    public function wpscp_future_post_rest_route_output($request)
    {
        global $wpdb;
        // post type
        $post_type = urldecode($request->get_param('post_type'));
        $post_type = (($post_type == 'elementorlibrary') ? 'elementor_library' : $post_type);
        if($post_type == 'all'){
            $post_type = \WPSP\Helper::get_settings('allow_post_types');
        }
        else if(empty($post_type)){
            $post_type = 'post';
        }
        $post_type = (array) $post_type;

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
        ));
        $posts_1 = $query_1->get_posts();

        $post_type_placeholders = implode(',', array_fill(0, count($post_type), '%s'));
        $query = $wpdb->prepare( "
            SELECT $wpdb->posts.*
            FROM $wpdb->posts
            INNER JOIN $wpdb->postmeta ON ( $wpdb->posts.ID = $wpdb->postmeta.post_id )
            WHERE 1=1 AND (
                ( $wpdb->postmeta.meta_key = '_wpscp_schedule_republish_date' AND CONVERT($wpdb->postmeta.meta_value, DATE) BETWEEN %s AND %s )
            )
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

                // $markup = '';
                // $markup .= '<div class="wpscp-event-post" data-postid="' . get_the_ID() . '" data-post-type="' .  get_post_type() . '">';
                // $markup .= '<div class="postlink"><span><span class="posttime">[' . (empty($republish_date) ? get_the_date('g:i a') : date('g:i a', strtotime($republish_date))) . ']</span> ' . wp_trim_words(get_the_title(), 3, '...') . ' [' . $this->get_post_status($republish) . ']</span></div>';
                // $link = '';
                // $link .= '<div class="edit"><a href="' . get_site_url() . '/wp-admin/post.php?post=' . get_the_ID() . '&action=edit""><i class="dashicons dashicons-edit"></i>Edit</a><a class="wpscpquickedit" href="#" data-type="quickedit"><i class="dashicons dashicons-welcome-write-blog"></i>Quick Edit</a></div>';
                // $link .= '<div class="deleteview"><a class="wpscpEventDelete" href="#"><i class="dashicons dashicons-trash"></i> Delete</a><a href="' . get_the_permalink() . '"><i class="dashicons dashicons-admin-links"></i> View</a></div>';
                // $markup .= '<div class="postactions"><div>' . $link . '</div></div>';
                // $markup .= '</div>';
                array_push($allData, array(
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
                ));
            }
            wp_reset_postdata();
        }
        return $allData;
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
     * @version 3.0.1
     */
    public function calender_ajax_request_php()
    {
        $nonce = $_POST['nonce'];
        if (!wp_verify_nonce($nonce, 'wpscp-calendar-ajax-nonce')) {
            die(__('Security check', 'wp-scheduled-posts'));
        }
        $calendar_schedule_time = \WPSP\Helper::get_settings('calendar_schedule_time');
        $post_status = '';
        if (!empty($_POST['post_status']) && $_POST['post_status'] != '') {
            $post_status = (($_POST['post_status'] == 'Scheduled') ? 'future' : 'draft');
        }

        $type = (isset($_POST['type']) ? $_POST['type'] : '');
        $post_type = (isset($_POST['post_type']) ? $_POST['post_type'] : '');
        $dateStr = (isset($_POST['date']) ? $_POST['date'] : '');
        $postid = (isset($_POST['ID']) ? $_POST['ID'] : '');
        $postTitle = (isset($_POST['postTitle']) ? $_POST['postTitle'] : '');
        $postContent = (isset($_POST['postContent']) ? $_POST['postContent'] : '');

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
                $res = json_encode(query_posts(array('p' => $post_id, 'post_type' => $post_type)));

				print $res;
            }
        } else if ($type == 'addEvent') {

            // only works if update event is fired
            if ($postid != "") {
                wp_update_post(array(
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
                    print json_encode(query_posts(array('p' => $postid, 'post_type' => $post_type)));
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
                if ($post_id != 0) {
                    print json_encode(query_posts(array('p' => $post_id, 'post_type' => $post_type)));
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
     * @method quick_edit_action
     * @version 3.0.1
     */
    public function quick_edit_action()
    {
        $post_type = (isset($_POST['post_type']) ? $_POST['post_type'] : '');
        $postId = (isset($_POST['ID']) ? intval($_POST['ID']) : '');
        if ($postId != 0) {
            $posts = query_posts(array('p' => $postId, 'post_type' => $post_type));
            $posts = apply_filters('wpsp_eventDrop_posts', $posts, $postId);
            print json_encode($posts);
        }
        wp_die(); // this is required to terminate immediately and return a proper response
    }

    /**
     * Ajax Request for delete event action
     * @method delete_event_action
     * @version 3.0.1
     */
    public function delete_event_action()
    {
        $postId = (isset($_POST['ID']) ? intval($_POST['ID']) : '');
        if ($postId != "") {
            wp_delete_post($postId, true);
            print $postId;
        }
        wp_die(); // this is required to terminate immediately and return a proper response
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
