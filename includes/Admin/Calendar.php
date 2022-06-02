<?php

namespace WPSP\Admin;

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
    }

    public function wpscp_register_custom_route()
    {
        register_rest_route(
            'wpscp/v1',
            '/post_type=(?P<post_type>[a-zA-Z0-9-_]+)/month=(?P<month>[0-9 .\-]+)/year=(?P<year>[0-9 .\-]+)',
            array(
                'methods'  => 'GET',
                'callback' => array($this, 'wpscp_future_post_rest_route_output'),
                'permission_callback' => '__return_true'
            )
        );
    }


    public function wpscp_future_post_rest_route_output($request)
    {
        // post type
        $post_type = urldecode($request->get_param('post_type'));
        $post_type = (($post_type == 'elementorlibrary') ? 'elementor_library' : $post_type);
        if($post_type == 'all'){
            $post_type = \WPSP\Helper::get_settings('allow_post_types');
        }
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
        // query
        $query = new \WP_Query(array(
            'post_type'      => (($post_type != "") ? $post_type : 'post'),
            'post_status'    => array('future', 'publish'),
            'posts_per_page' => -1,
            'date_query'     => array(
                array(
                    'year'  => $year,
                    'month' => $month,
                ),
            ),
        ));
        $allData = array();

        if ($query->have_posts()) {
            while ($query->have_posts()) : $query->the_post();
                $markup = '';
                $markup .= '<div class="wpscp-event-post" data-postid="' . get_the_ID() . '" data-post-type="' .  get_post_type() . '">';
                $markup .= '<div class="postlink"><span><span class="posttime">[' . get_the_date('g:i a') . ']</span> ' . wp_trim_words(get_the_title(), 3, '...') . ' [' . get_post_status(get_the_ID()) . ']</span></div>';
                $link = '';
                $link .= '<div class="edit"><a href="' . get_site_url() . '/wp-admin/post.php?post=' . get_the_ID() . '&action=edit""><i class="dashicons dashicons-edit"></i>Edit</a><a class="wpscpquickedit" href="#" data-type="quickedit"><i class="dashicons dashicons-welcome-write-blog"></i>Quick Edit</a></div>';
                $link .= '<div class="deleteview"><a class="wpscpEventDelete" href="#"><i class="dashicons dashicons-trash"></i> Delete</a><a href="' . get_the_permalink() . '"><i class="dashicons dashicons-admin-links"></i> View</a></div>';
                $markup .= '<div class="postactions"><div>' . $link . '</div></div>';
                $markup .= '</div>';
                array_push($allData, array(
                    'title'  => $markup,
                    'start'  => get_the_date('Y-m-d'),
                    'end'  => get_the_date('Y-m-d H:i:s'),
                    'allDay' => false,
                ));
            endwhile;
            wp_reset_postdata();
        }
        return $allData;
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
            $postdate_gmt = ($postdateformat != "" ? gmdate('Y-m-d H:i:s', strtotime($postdateformat)) : '');
        } else {
            $postdate = new \DateTime(substr($dateStr, 0, 25));
            $postdateformat = $postdate->format('Y-m-d H:i:s');
            $postdate_gmt = ($postdateformat != "" ? gmdate('Y-m-d H:i:s', strtotime($postdateformat)) : '');
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
            $post_id = wp_update_post(array(
                'ID'            => $postid,
                'post_type'     => $post_type,
                'post_status'   => 'future',
                'post_date'     => (isset($postdateformat) ? $postdateformat : ''),
                'post_date_gmt' => (isset($postdate_gmt) ? $postdate_gmt : ''),
                'edit_date'     => true,
            ));
            if ($post_id != 0) {
                print json_encode(query_posts(array('p' => $post_id, 'post_type' => $post_type)));
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
            print json_encode(query_posts(array('p' => $postId, 'post_type' => $post_type)));
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
}
