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
        $_allow_categories = ['relation' => 'OR'];
        $post_types        = \WPSP\Helper::get_settings('allow_post_types');
        $post_types        = (!empty($post_types) ? $post_types : array('post'));
        $allow_categories  = \WPSP\Helper::get_settings('allow_categories');
        if (($key = array_search('all', $allow_categories)) !== false) {
            unset($allow_categories);
        }
        else{
            foreach ($allow_categories as $key => $value) {
                list($taxonomy, $term) = preg_split("/\./", $value, 2);
                if(empty($_allow_categories[$taxonomy])){
                    $_allow_categories[$taxonomy] = [
                        'taxonomy' => $taxonomy,
                        'field'    => 'slug',
                        'terms'    => [$term],
                    ];
                }
                else{
                    $_allow_categories[$taxonomy]['terms'][] = $term;
                }
            }
        }

        if (empty($allow_categories)) {
            $result = new \WP_Query(array(
                'post_type'   => $post_types,
                'post_status' => 'future',
                'order'       => 'ASC',
            ));
        } else {
            $result = new \WP_Query(array(
                'post_type'   => $post_types,
                'post_status' => 'future',
                'order'       => 'ASC',
                'tax_query'   => ($_allow_categories),
            ));
        }
        if ($result->have_posts()) :
            echo '<table class="widefat">';
            while ($result->have_posts()) : $result->the_post();
                echo '<tr>
                            <td><a href="' . get_edit_post_link(get_the_ID()) . '">' . get_the_title() . '</a></td>
                            <td>' . get_the_date('d F, Y') . '</td>
                            <td>' . get_the_date('g:i a') . '</td>
                            <td>' . get_the_author() . '</td>
                        </tr>';
            endwhile;
            wp_reset_postdata();
            echo "</table>";
        else:
            echo "No post is scheduled.";
        endif;
    }


    /**
     * Hook into the 'wp_dashboard_setup' action to register our other functions
     * Create the function use in the action hook
     * @method wpscp_widget_post_scheduled
     * @since 2.3.1
     */
    public function wpscp_widget_post_scheduled()
    {
        if (\WPSP\Helper::get_settings('is_show_dashboard_widget')) {
            if (\WPSP\Helper::is_user_allow()) {
                wp_add_dashboard_widget('wp_scp_dashboard_widget', 'Scheduled Posts', array($this, 'widget_scheduled_post_markup'));
            }
        }
    }
}
