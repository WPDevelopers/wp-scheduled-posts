<?php
/**
 * Get calendar ability.
 *
 * @package WPSP\Abilities\Schedule
 */

namespace WPSP\Abilities\Schedule;

use WPSP\Abilities\AbilityBase;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * The editorial calendar for a date range, grouped by day.
 *
 * Where list-scheduled-posts answers "what is queued", this answers "what does
 * my week look like" — including already-published and draft items, so an AI
 * client can reason about gaps and clustering.
 */
class GetCalendar extends AbilityBase {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id          = 'schedulepress/get-calendar';
		$this->label       = __( 'Get Schedule Calendar', 'wp-scheduled-posts' );
		$this->description = __( 'Get the editorial calendar for a date range, grouped by day, including scheduled, published and draft posts. Use this for "what does my calendar look like" questions and to spot clustering or empty days.', 'wp-scheduled-posts' );
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
				'from'       => array(
					'type'        => 'string',
					'description' => 'Start of the range (ISO 8601 date or datetime; no offset means site-local).',
				),
				'to'         => array(
					'type'        => 'string',
					'description' => 'End of the range (ISO 8601 date or datetime; no offset means site-local).',
				),
				'post_types' => array(
					'type'        => 'array',
					'items'       => array( 'type' => 'string' ),
					'description' => 'Restrict to these post types. Defaults to every type SchedulePress manages.',
				),
				'statuses'   => array(
					'type'        => 'array',
					'items'       => array(
						'type' => 'string',
						'enum' => array( 'future', 'publish', 'draft', 'pending', 'private' ),
					),
					'description' => 'Post statuses to include. Defaults to future, publish and draft.',
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
				'from'     => array( 'type' => 'string' ),
				'to'       => array( 'type' => 'string' ),
				'total'    => array( 'type' => 'integer' ),
				'days'     => array(
					'type'  => 'array',
					'items' => array(
						'type'       => 'object',
						'properties' => array(
							'date'  => array( 'type' => 'string' ),
							'count' => array( 'type' => 'integer' ),
							'posts' => array(
								'type'  => 'array',
								'items' => array( 'type' => 'object' ),
							),
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

		$statuses = isset( $input['statuses'] ) && is_array( $input['statuses'] ) && ! empty( $input['statuses'] )
			? array_values( array_intersect( array_map( 'strval', $input['statuses'] ), array( 'future', 'publish', 'draft', 'pending', 'private' ) ) )
			: array( 'future', 'publish', 'draft' );

		$query = new \WP_Query(
			array(
				'post_type'        => $post_types,
				'post_status'      => $statuses,
				'posts_per_page'   => 500,
				'orderby'          => 'date',
				'order'            => 'ASC',
				'no_found_rows'    => true,
				'suppress_filters' => false,
				'date_query'       => array(
					array(
						'after'     => $from->format( 'Y-m-d H:i:s' ),
						'before'    => $to->format( 'Y-m-d H:i:s' ),
						'inclusive' => true,
					),
				),
			)
		);

		$days  = array();
		$total = 0;
		foreach ( $query->posts as $post ) {
			$day = substr( (string) $post->post_date, 0, 10 );
			if ( ! isset( $days[ $day ] ) ) {
				$days[ $day ] = array();
			}
			$days[ $day ][] = $this->describe_post( $post );
			$total++;
		}
		ksort( $days );

		$out = array();
		foreach ( $days as $date => $posts ) {
			$out[] = array(
				'date'  => $date,
				'count' => count( $posts ),
				'posts' => $posts,
			);
		}

		return array(
			'timezone' => $this->site_timezone()->getName(),
			'from'     => $from->format( 'c' ),
			'to'       => $to->format( 'c' ),
			'total'    => $total,
			'days'     => $out,
		);
	}
}
