<?php

class WPSP_Helper {

    public static function future_post() {
        $future_post_date = array();

        $posts = new WP_Query(array(
            'post_type' => 'post',
            'posts_per_page' => '-1',
            'post_status' => 'future'
        ));
        // dump( $posts->posts );
        $dates = [];

        while( $posts->have_posts() ) : $posts->the_post();
            $date = get_the_date( 'Y-m-d H:i:s' );
            $date_timestamp = strtotime( $date );
            $dates[ $date_timestamp ] = $date;
        endwhile;

        return $dates;
    }

    public static function all_day_schedule() {
        global $wpdb;
        $my_table = 'psm_manage_schedule';
        $retrieveSQL = "SELECT * FROM ". $my_table;
        $day_schedules 	= $wpdb->get_results( $retrieveSQL, ARRAY_A );
        $all_day_schedule = array();

        $current_time = current_time( 'timestamp' );
        $today_name = date( "l", $current_time );
        $today_date_time = date( "Y-m-d H:i:s", $current_time );

        if( ! empty( $day_schedules ) ) :
            foreach( $day_schedules as $day_schedule ) {
                if( strtolower( $today_name ) === strtolower( $day_schedule['day'] ) ) {		
                    $next_schedule = date( "Y-m-d") . " " . $day_schedule['schedule'];                 
                    $next_schedule_timestamp = strtotime( $next_schedule );		
                    $next_schedule = date( "Y-m-d H:i:s", $next_schedule_timestamp );           
                    $today_timestamp = strtotime( $today_date_time );		
                    if( $next_schedule_timestamp > $today_timestamp ){
                        $all_day_schedule[ $next_schedule_timestamp ] = [ 
                            'label' => date( 'l, F j, Y \a\t g:i a', $next_schedule_timestamp ), // //Thursday, January 17, 2019 at 8:00 am
                            'date' => $next_schedule, 
                            'status' => 'future', 
                            'date_gmt' => get_gmt_from_date( $next_schedule, 'Y-m-d H:i:s' ) 
                        ];
                    }
                } else {	
                    $next_day_schedule_timestamp = strtotime( "Next ". $day_schedule['day'] ." ". $day_schedule['schedule'] );
                    $next_day_schedule = date( "Y-m-d H:i:s", $next_day_schedule_timestamp );
                    $all_day_schedule[ $next_day_schedule_timestamp ] = [ 
                        'label' => date( 'l, F j, Y \a\t g:i a', $next_day_schedule_timestamp ),
                        'date' => $next_day_schedule, 
                        'status' => 'future', 
                        'date_gmt' => get_gmt_from_date( $next_day_schedule, 'Y-m-d H:i:s' )
                    ];
                }
            }
            ksort( $all_day_schedule );
        endif;

        return $all_day_schedule;
    }

    public static function next_schedule(){
        $all_day_schedule = self::all_day_schedule();
        $all_day_schedule_count = count( $all_day_schedule );

        $future_post_date = self::future_post();
        $future_post_date_count = count( self::future_post() );
        $future_post_date_keys = array_keys( $future_post_date );
        
        $deserved_dates = [];
        foreach( $all_day_schedule as $date_timestamp => $date ) {
            if( ! in_array( $date_timestamp, $future_post_date_keys ) ) {
                $deserved_dates[ $date_timestamp ] = $date;  
            }
        }
    
        $next_week_schedule = self::next_week_schedule( $all_day_schedule );
        $deserved_dates = $deserved_dates + $next_week_schedule;
        $deserved_dates = $deserved_dates + self::next_week_schedule( $deserved_dates );

        return $deserved_dates;
    }

    public static function next_week_schedule( $current_week ){
        $current_week_new = array();

        foreach( $current_week as $date_timestamp => $date ) {
            $new_date_timestamp = strtotime( date('Y-m-d H:i:s', $date_timestamp ) . ' +7 day');
            $new_date = date( 'Y-m-d H:i:s', $new_date_timestamp );
            $current_week_new[ $new_date_timestamp ] = [ 
                'label' => date( 'l, F j, Y \a\t g:i a', $new_date_timestamp ),
                'date' => $new_date, 
                'status' => 'future', 
                'date_gmt' => get_gmt_from_date( $new_date, 'Y-m-d H:i:s' ) 
            ];
        }

        return $current_week_new;
    }

}