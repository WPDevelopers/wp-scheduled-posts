<?php
/**
 * Find schedule gaps ability.
 *
 * @package WPSP\Abilities\Schedule
 */

namespace WPSP\Abilities\Schedule;

use WPSP\Abilities\AbilityBase;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Reports which days in a range have no scheduled content, so an AI client can
 * propose slots without first pulling the whole calendar and counting by hand.
 */
class FindScheduleGaps extends AbilityBase {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id          = 'schedulepress/find-schedule-gaps';
		$this->label       = __( 'Find Schedule Gaps', 'wp-scheduled-posts' );
		$this->description = __( 'Find days in a date range that have fewer scheduled posts than your target cadence, and suggest a publish time for each. Use this to answer "when should I publish next" and to fill an empty week.', 'wp-scheduled-posts' );
	}

	/**
	 * {@inheritDoc}
	 *
	 * @return array
	 */
	public function get_annotations() {
		return array(
			'readonly'      => true,
			'destructive'   => false,
			'idempotent'    => true,
			'priority'      => 1.0,
			'openWorldHint' => false,
		);
	}

	/**
	 * {@inheritDoc}
	 *
	 * @return array
	 */
	public function get_input_schema() {
		return array(
			'type'                 => 'object',
			'additionalProperties' => false,
			'required'             => array( 'from', 'to' ),
			'properties'           => array(
				'from'          => array(
					'type'        => 'string',
					'description' => 'Start of the range (ISO 8601 date; no offset means site-local).',
				),
				'to'            => array(
					'type'        => 'string',
					'description' => 'End of the range (ISO 8601 date; no offset means site-local).',
				),
				'post_types'    => array(
					'type'        => 'array',
					'items'       => array( 'type' => 'string' ),
					'description' => 'Restrict to these post types. Defaults to every type SchedulePress manages.',
				),
				'per_day'       => array(
					'type'        => 'integer',
					'minimum'     => 1,
					'maximum'     => 24,
					'default'     => 1,
					'description' => 'Target number of posts per day. Days below this are reported as gaps.',
				),
				'preferred_time' => array(
					'type'        => 'string',
					'default'     => '09:00',
					'description' => 'Time of day (HH:MM, site-local) to suggest for each gap.',
				),
				'skip_weekends' => array(
					'type'        => 'boolean',
					'default'     => false,
					'description' => 'Ignore Saturdays and Sundays.',
				),
			),
		);
	}

	/**
	 * {@inheritDoc}
	 *
	 * @return array
	 */
	public function get_output_schema() {
		return array(
			'type'       => 'object',
			'properties' => array(
				'timezone' => array( 'type' => 'string' ),
				'per_day'  => array( 'type' => 'integer' ),
				'gaps'     => array(
					'type'  => 'array',
					'items' => array(
						'type'       => 'object',
						'properties' => array(
							'date'            => array( 'type' => 'string' ),
							'scheduled_count' => array( 'type' => 'integer' ),
							'free_slots'      => array( 'type' => 'integer' ),
							'suggested_time'  => array( 'type' => 'string' ),
						),
					),
				),
			),
		);
	}

	/**
	 * Execute ability.
	 *
	 * @param array $input Ability input payload.
	 * @return array|\WP_Error
	 */
	public function execute( $input ) {
		$input = is_array( $input ) ? $input : array();

		$from = $this->parse_datetime( isset( $input['from'] ) ? $input['from'] : '' );
		if ( is_wp_error( $from ) ) {
			return $from;
		}
		$to = $this->parse_datetime( isset( $input['to'] ) ? $input['to'] : '' );
		if ( is_wp_error( $to ) ) {
			return $to;
		}
		if ( $to < $from ) {
			return new \WP_Error(
				'wpsp_invalid_range',
				__( 'The "to" date must be the same as or later than the "from" date.', 'wp-scheduled-posts' ),
				array( 'status' => 400 )
			);
		}

		$post_types = $this->resolve_post_types( isset( $input['post_types'] ) ? $input['post_types'] : null );
		if ( is_wp_error( $post_types ) ) {
			return $post_types;
		}

		$per_day = isset( $input['per_day'] ) ? (int) $input['per_day'] : 1;
		$per_day = max( 1, min( 24, $per_day ) );

		$time = isset( $input['preferred_time'] ) ? (string) $input['preferred_time'] : '09:00';
		if ( ! preg_match( '/^([01]\d|2[0-3]):([0-5]\d)$/', $time ) ) {
			return new \WP_Error(
				'wpsp_invalid_time',
				__( 'preferred_time must be a 24-hour HH:MM value, for example 09:00.', 'wp-scheduled-posts' ),
				array( 'status' => 400 )
			);
		}

		$skip_weekends = ! empty( $input['skip_weekends'] );

		// Count what is already scheduled per day across the range.
		$query  = new \WP_Query(
			array(
				'post_type'        => $post_types,
				'post_status'      => array( 'future', 'publish' ),
				'posts_per_page'   => 500,
				'orderby'          => 'date',
				'order'            => 'ASC',
				'no_found_rows'    => true,
				'suppress_filters' => false,
				'date_query'       => array(
					array(
						'after'     => $from->format( 'Y-m-d 00:00:00' ),
						'before'    => $to->format( 'Y-m-d 23:59:59' ),
						'inclusive' => true,
					),
				),
			)
		);
		$counts = array();
		foreach ( $query->posts as $post ) {
			$day            = substr( (string) $post->post_date, 0, 10 );
			$counts[ $day ] = isset( $counts[ $day ] ) ? $counts[ $day ] + 1 : 1;
		}

		$gaps   = array();
		$cursor = new \DateTime( $from->format( 'Y-m-d' ) . ' 00:00:00', $this->site_timezone() );
		$end    = new \DateTime( $to->format( 'Y-m-d' ) . ' 00:00:00', $this->site_timezone() );
		$step   = new \DateInterval( 'P1D' );

		// Bounded so a wildly wide range can't spin: a year of daily slots is
		// already far more than a scheduling conversation needs.
		$guard = 0;
		while ( $cursor <= $end && $guard < 366 ) {
			$guard++;
			$day = $cursor->format( 'Y-m-d' );
			$dow = (int) $cursor->format( 'N' );

			if ( ! ( $skip_weekends && $dow >= 6 ) ) {
				$scheduled = isset( $counts[ $day ] ) ? (int) $counts[ $day ] : 0;
				if ( $scheduled < $per_day ) {
					$suggested = new \DateTime( $day . ' ' . $time . ':00', $this->site_timezone() );
					$gaps[]    = array(
						'date'            => $day,
						'scheduled_count' => $scheduled,
						'free_slots'      => $per_day - $scheduled,
						'suggested_time'  => $suggested->format( 'c' ),
					);
				}
			}
			$cursor->add( $step );
		}

		return array(
			'timezone' => $this->site_timezone()->getName(),
			'per_day'  => $per_day,
			'gaps'     => $gaps,
		);
	}
}
