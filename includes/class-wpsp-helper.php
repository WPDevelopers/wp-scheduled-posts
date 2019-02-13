<?php

class WPSP_Helper {
    /**
     * Number of future schedule dates to show.
     * @var integer
     */
    private static $numberOfListItem = 5;
    /**
     * All future post date
     * @return void
     */
    public static function future_post() {
        $future_post_date = array();

        $posts = new WP_Query(array(
            'post_type' => 'post',
            'posts_per_page' => '-1',
            'post_status' => 'future'
        ));
        $dates = [];
        while( $posts->have_posts() ) : $posts->the_post();
            $date = get_the_date( 'Y-m-d H:i:s' );
            $date_timestamp = strtotime( $date );
            $dates[ $date_timestamp ] = $date;
        endwhile;

        return $dates;
    }
    /**
     * All schedule of current week.
     * @return void
     */
    public static function current_week_schedule() {
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
    /**
     * Generate next schedule for schedule post.
     * @return void
     */
    public static function schedule(){
        $current_week_new = $deserved_dates = [];
        $current_week = self::current_week_schedule();
        $future_post = self::future_post();
        $future_post_date_keys = array_keys( $future_post );
        $future_post_count = count( $future_post );
        $future_post_count = ( $future_post_count == 0 ? 2 : $future_post_count ) * 2;

        for( $i = 1; $i <= $future_post_count; $i++ ) {
            $days = $i * 7;
            foreach( $current_week as $date_timestamp => $date ) {
                $new_date_timestamp = strtotime( date('Y-m-d H:i:s', $date_timestamp ) . ' +'. $days .' day');
                $new_date = date( 'Y-m-d H:i:s', $new_date_timestamp );
                $current_week_new[ $new_date_timestamp ] = [ 
                    'label' => date( 'l, F j, Y \a\t g:i a', $new_date_timestamp ),
                    'date' => $new_date, 
                    'status' => 'future', 
                    'date_gmt' => get_gmt_from_date( $new_date, 'Y-m-d H:i:s' ) 
                ];
            }
        }
        
        $dateIterator = 1;
        $current_week_new = $current_week + $current_week_new;
        foreach( $current_week_new as $date_timestamp => $date ) {
            if( ! in_array( $date_timestamp, $future_post_date_keys ) && $dateIterator <= self::$numberOfListItem ) {
                $deserved_dates[ $date_timestamp ] = $date;  
                $dateIterator++;
            }
        }
        return $deserved_dates;
    }


    private static function next_day_max_posts( $timestamp ){
        $pts_options = get_option('manage-schedule', false);
        if( $pts_options ) {
            $now = date('w', $timestamp );
            $max_post = $pts_options[ "pts_$now" ];
            $iterator = 0;
            while( $max_post <= 0 && $iterator < 7 ) {
                $timestamp = strtotime( date('ymd', $timestamp ) . ' +' . $iterator++ . ' Days ');
                $now = date('w', $timestamp );
                $max_post = $pts_options[ "pts_$now" ];
            }

            if( $max_post ) {
                return array(
                    'max_post' => $max_post,
                    'time' => $timestamp,
                );
            }
        }
        return false;
    }

    public static function auto_schedule( $timestamp = '', $iterator = 0 ) {
        $options = get_option('manage-schedule', false);
        # get start and end minutes from 0 to 1440-1
        $startMinute = date('H', strtotime($options['pts_start'])) * 60 + date('i', strtotime($options['pts_start']));
        $endMinute = date('H', strtotime($options['pts_end'])) * 60 + date('i', strtotime($options['pts_end']));
    
        if( empty( $timestamp ) ) {
            $timestamp = strtotime( current_time('mysql') );
        }

        $next_day_posts = self::next_day_max_posts( $timestamp );

        
        $max_post = $next_day_posts['max_post'];
        $n_post_in_day = 0;
        $max_post_day_time = $next_day_posts['time'];
        
        $date = date('Y-m-d', $max_post_day_time);

        $startDate = date( 'Y-m-d', strtotime( current_time('mysql') . '' ) );

        $future_posts = new WP_Query(array(
            'post_type' => 'post',
            'posts_per_page' => -1,
            'post_status' => array( 'future', 'publish' ),
            'orderby' => 'post_date',
            'order' => 'ASC',
            'date_query' => array(
                'after' => $startDate,
                'inclusive' => true
            )
        ));

        while( $future_posts->have_posts() ) : $future_posts->the_post();
            if( get_the_date('Y-m-d') === date('Y-m-d', $max_post_day_time) ) {
                $n_post_in_day++;
            }
        endwhile;
        wp_reset_postdata();

        if( $date === $startDate ) {
            $nowLocal = current_time('mysql', $gmt = 0);
            $nowTotalMinutes = date('H', strtotime($nowLocal)) * 60 + date('i', strtotime($nowLocal));
			if( $nowTotalMinutes >= $startMinute ){
				$startMinute = $nowTotalMinutes;
			}
        }

        $minutePublish = rand($startMinute, $endMinute);
		if( $minutePublish == 0 ){
			$minutePublish += 1;
		}
       
        $auto_date = date("Y-m-d", $max_post_day_time) . ' ' . intval($minutePublish / 60) . ':' . $minutePublish % 60;

        if( $n_post_in_day < $max_post ) {
            $new_date_timestamp = strtotime( $auto_date );
            $new_date = date( 'Y-m-d H:i:s', $new_date_timestamp );
            return array( 
                'label' => date( 'l, F j, Y \a\t g:i a', $new_date_timestamp ),
                'date' => $new_date, 
                'status' => 'future', 
                'date_gmt' => get_gmt_from_date( $new_date, 'Y-m-d H:i:s' ) 
            );
        } else {
            $time = strtotime( date('Y-m-d', $max_post_day_time) . ' +'. $iterator .' Days' );
            return self::auto_schedule( $time, $iterator + 1 );
        }
    }
}