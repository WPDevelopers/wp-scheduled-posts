<?php

namespace WPSP\Admin\Widgets;

class ScheduledPostList
{
    /**
     * Load all hooks and method
     * @since 2.3.1
     */
    public function __construct()
    {
        add_action('wp_dashboard_setup', array($this, 'wpscp_widget_post_scheduled'));
    }

    /**
     * WP Scheduled Post Widget Function
     *
     * @method widget_scheduled_post_wrap
     * @since 2.3.1
     */
    public function widget_scheduled_post_markup()
    {
        global $wpdb;
        global $wpscp_options;
        $post_types     =    (isset($wpscp_options['allow_post_types']) ? $wpscp_options['allow_post_types'] : array('post'));

        $post_cats = $wpscp_options['allow_categories'];
        if (is_array($post_cats) && $post_cats[0] == 0 && count($post_cats) == 1) {
            $result = new \WP_Query(array(
                'post_type' => $post_types,
                'post_status' => 'future'
            ));
        } else {
            $result = new \WP_Query(array(
                'post_type' => $post_types,
                'post_status' => 'future',
                'tax_query' => array(
                    array(
                        'taxonomy' => 'category',
                        'field'    => 'term_id',
                        'terms'    => $post_cats,
                    ),
                ),
            ));
        }



        echo '<table class="widefat">';
        if ($result->have_posts()) :
            while ($result->have_posts()) : $result->the_post();
                echo '<tr>
                            <td><a href="' . get_edit_post_link(get_the_ID()) . '">' . get_the_title() . '</a></td>
                            <td>' . get_the_date('d F, Y') . '</td>
                            <td>' . get_the_author() . '</td>
                            <td>' . get_the_date('g:i a') . '</td>
                        </tr>';
            endwhile;
            wp_reset_postdata();
        endif;
        echo "</table>";
    }


    /**
     * Hook into the 'wp_dashboard_setup' action to register our other functions
     * Create the function use in the action hook
     * @method wpscp_widget_post_scheduled
     * @since 2.3.1
     */
    public function wpscp_widget_post_scheduled()
    {
        global $wpscp_options;
        if ($wpscp_options['show_dashboard_widget']) {
            if (\WPSP\Helper::is_user_allow()) {
                wp_add_dashboard_widget('wp_scp_dashboard_widget', 'Scheduled Posts', array($this, 'widget_scheduled_post_markup'));
            }
        }
    }
}
