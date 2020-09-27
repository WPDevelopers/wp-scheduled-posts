<?php

use function GuzzleHttp\json_decode;

if (!class_exists('WpScp_Calendar')) {
    class WpScp_Calendar
    {
        public $wpscp_options;
        public function __construct()
        {
            $this->hooks();
            $this->wpscp_options = get_option('wpscp_options');
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
            add_action('wp_ajax_wpscp_calender_filter_markup_generate',  array($this, 'calender_filter_markup_generate'));
        }

        public function wpscp_register_custom_route()
        {
            register_rest_route(
                'wpscp/v1',
                // '/post_type=(?P<post_type>[a-zA-Z0-9-_]+)/month=(?P<month>[0-9 .\-]+)/year=(?P<year>[0-9 .\-]+)',
                '/calendar',
                array(
                    'methods'  => 'GET',
                    'callback' => array($this, 'wpscp_future_post_rest_route_output'),
                    'permission_callback' => '__return_true'
                )
            );
        }


        public function wpscp_future_post_rest_route_output($request)
        {
            $requestArray =  json_decode(urldecode($request->get_param('query')), true);
            // post type
            $post_type = (isset($requestArray['post_type']) && $requestArray['post_type'][0] !== 'all' ? $requestArray['post_type'] : ['post']);
            // post status
            $post_status = (isset($requestArray['post_status']) && $requestArray['post_status'][0] !== 'all' ? $requestArray['post_status'] : ['future', 'publish']);
            // date
            $now = new \DateTime('now');
            $now_month = $now->format('m');
            $now_year = $now->format('Y');
            // month
            $month = (isset($requestArray['month']) && !empty($requestArray['month']) ? $requestArray['month'] : $now_month);
            // year
            $year = (isset($requestArray['year']) && !empty($requestArray['year']) ? $requestArray['year'] : $now_year);
            // taxonomy
            $tax = $requestArray['tax'];

            // query
            $args = array(
                'post_type'      => $post_type,
                'post_status'    => $post_status,
                'posts_per_page' => -1,
                'date_query'     => array(
                    array(
                        'year'  => $year,
                        'month' => $month,
                    ),
                ),
            );
            if (count($tax) > 1) {
                $args['tax_query'] = $tax;
            }
            if (!empty($requestArray['author']) && $requestArray['author'] !== 'all') {
                $args['author'] = $requestArray['author'];
            }

            error_log(print_r($args, true));
            $query = new WP_Query($args);
            $allData = array();

            if ($query->have_posts()) {
                while ($query->have_posts()) : $query->the_post();
                    $markup = '';
                    $markup .= '<div class="wpscp-event-post" data-postid="' . get_the_ID() . '">';
                    $markup .= '<div class="postlink"><span><span class="posttime">[' . get_the_date('g:i a') . ']</span> ' . wp_trim_words(get_the_title(), 3, '...') . ' [' . get_post_status(get_the_ID()) . ']</span></div>';
                    $link = '';
                    $link .= '<div class="edit"><a href="' . get_site_url() . '/wp-admin/post.php?post=' . get_the_ID() . '&action=edit""><i class="dashicons dashicons-edit"></i>Edit</a><a class="wpscpquickedit" href="#" data-type="quickedit"><i class="dashicons dashicons-welcome-write-blog"></i>Quick Edit</a></div>';
                    $link .= '<div class="deleteview"><a class="wpscpEventDelete" href="#"><i class="dashicons dashicons-trash"></i> Delete</a><a href="' . get_the_permalink() . '"><i class="dashicons dashicons-admin-links"></i> View</a></div>';
                    $markup .= '<div class="postactions"><div>' . $link . '</div></div>';
                    $markup .= '</div>';
                    array_push($allData, array(
                        'title'  => $markup,
                        'start'  => get_the_date('Y-m-d H:i:s'),
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

            if ($dateStr != "Invalid Date" && empty($postid)) {
                $postdate = new DateTime(substr($dateStr, 0, 25));
                $postdateformat = $postdate->format('Y-m-d H:i:s');
                $postdate_gmt = ($postdateformat != "" ? gmdate('Y-m-d H:i:s', strtotime($postdateformat)) : '');
            } else if ($dateStr != "Invalid Date" && !empty($postid)) {
                $default_schedule_time = '12:00 am';
                if (isset($this->wpscp_options['calendar_default_schedule_time']) && $this->wpscp_options['calendar_default_schedule_time'] != null) {
                    $default_schedule_time = $this->wpscp_options['calendar_default_schedule_time'];
                }

                $date_string = substr($dateStr, 0, 16) . $default_schedule_time;
                $postdate = new DateTime($date_string);
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
                    print json_encode(query_posts(array('p' => $post_id, 'post_type' => $post_type)));
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
                    print json_encode(query_posts(array('p' => $post_id, 'post_type' => $post_type)));
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


        public function calender_filter_markup_generate()
        {

            $nonce = $_POST['nonce'];
            if (!wp_verify_nonce($nonce, 'wpscp-calendar-ajax-nonce')) {
                die(__('Security check', 'wp-scheduled-posts'));
            }

            $markup = '';
            $post_type_markup = '';
            $markup_prefix = 'wpscp_calendar_filter_';

            $wpscp_options     =    wpscp_get_options();
            $post_types     =    (isset($wpscp_options['allow_post_types']) ? $wpscp_options['allow_post_types'] : array('post'));
            $response = [];
            $term_list = [];
            // post type select
            foreach ($post_types as $post_type) {
                $terms = get_object_taxonomies($post_type);
                $term_names = '';
                foreach ($terms as $term) {
                    $term_names .= ($term_names === '' ? $term : ' ' . $term);
                    $term_list[$term] = get_terms(array(
                        'taxonomy' => $term,
                        'hide_empty' => false,
                    ));
                }
                $response[$post_type] = $term_list;

                $post_type_markup .= '<option value="' . esc_attr($post_type) . '" data-termlist="' . esc_attr($term_names) . '">' . esc_html($post_type) . '</option>';
            }
            $markup .= '<select name="parent" id="' . esc_attr($markup_prefix . 'post_type') . '" class="wpscp-cf-select"><option value="' . esc_attr('all') . '">' . esc_html__('Post type', 'wp-scheduled-posts') . '</option>' . $post_type_markup . '</select>';



            // term taxonomy select
            foreach ($term_list as $term_key => $term_value) {
                if ($term_key === 'post_format') {
                    continue;
                }
                $markup .= '<select data-depend="" name="parent" id="' . esc_attr($markup_prefix . $term_key) . '" class="wpscp-cf-select taxonomy">';
                $markup .= '<option value="' . esc_attr('all') . '">' . esc_html($term_key) . '</option>';
                $tax_markup = '';
                foreach ($term_value as $single_term) {
                    $tax_markup .= '<option value="' . esc_attr($single_term->term_id) . '">' . esc_html($single_term->name) . '</option>';
                }
                $markup .=  $tax_markup;
                $markup .=  '</select>';
            }


            // post status
            $post_statuses = get_post_statuses();
            $markup .= '<select name="parent" id="' . esc_attr($markup_prefix . 'post_status') . '" class="wpscp-cf-select">';
            $markup .= '<option value="' . esc_attr('all') . '">' . esc_html__('Post Status', 'wp-scheduled-posts') . '</option>';
            foreach ($post_statuses as $status_key => $status_value) {
                $markup .= '<option value="' . esc_attr($status_key) . '">' . esc_html($status_value) . '</option>';
            }
            $markup .=  '</select>';
            // author
            $allusers = get_users();
            $markup .= '<select name="parent" id="' . esc_attr($markup_prefix . 'user') . '" class="wpscp-cf-select">';
            $markup .= '<option value="' . esc_attr('all') . '">' . esc_html__('All User', 'wp-scheduled-posts') . '</option>';
            foreach ($allusers as $user) {
                $markup .= '<option value="' . esc_attr($user->ID) . '">' . esc_html($user->user_login) . '</option>';
            }
            $markup .=  '</select>';

            $markup .= '<button id="wpscp_calendar_filter_btn">' . esc_html__('Filter', 'wp-scheduled-posts') . '</button>';

            wp_send_json_success($markup);
        }
    }
    new WpScp_Calendar();
}
