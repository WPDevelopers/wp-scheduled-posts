<?php
/**
 * List scheduled posts ability.
 *
 * @package WPSP\Abilities\Schedule
 */

namespace WPSP\Abilities\Schedule;

use WPSP\Abilities\AbilityBase;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Lists posts in the future queue, optionally narrowed to a date range or a
 * set of post types.
 */
class ListScheduledPosts extends AbilityBase {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id          = 'schedulepress/list-scheduled-posts';
		$this->label       = __( 'List Scheduled Posts', 'wp-scheduled-posts' );
		$this->description = __( 'List posts that are scheduled to publish in the future, optionally narrowed to a date range or specific post types. Dates are ISO 8601 in the site timezone. Use this to answer "what is queued?" questions.', 'wp-scheduled-posts' );
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
			'properties'           => array(
				'from'       => array(
					'type'        => 'string',
					'description' => 'Only include posts scheduled at or after this date/time (ISO 8601; no offset means site-local).',
				),
				'to'         => array(
					'type'        => 'string',
					'description' => 'Only include posts scheduled at or before this date/time (ISO 8601; no offset means site-local).',
				),
				'post_types' => array(
					'type'        => 'array',
					'items'       => array( 'type' => 'string' ),
					'description' => 'Restrict to these post types. Defaults to every type SchedulePress manages.',
				),
				'limit'      => array(
					'type'        => 'integer',
					'minimum'     => 1,
					'maximum'     => 200,
					'default'     => 50,
					'description' => 'Maximum posts to return (1-200).',
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
				'count'    => array( 'type' => 'integer' ),
				'posts'    => array(
					'type'  => 'array',
					'items' => array( 'type' => 'object' ),
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

		$post_types = $this->resolve_post_types( isset( $input['post_types'] ) ? $input['post_types'] : null );
		if ( is_wp_error( $post_types ) ) {
			return $post_types;
		}

		$limit = isset( $input['limit'] ) ? (int) $input['limit'] : 50;
		$limit = max( 1, min( 200, $limit ) );

		$args = array(
			'post_type'        => $post_types,
			'post_status'      => 'future',
			'posts_per_page'   => $limit,
			'orderby'          => 'date',
			'order'            => 'ASC',
			'suppress_filters' => false,
			'no_found_rows'    => true,
		);

		$range = $this->build_date_query( $input );
		if ( is_wp_error( $range ) ) {
			return $range;
		}
		if ( ! empty( $range ) ) {
			$args['date_query'] = array( $range );
		}

		$query = new \WP_Query( $args );
		$posts = array();
		foreach ( $query->posts as $post ) {
			$posts[] = $this->describe_post( $post );
		}

		return array(
			'timezone' => $this->site_timezone()->getName(),
			'count'    => count( $posts ),
			'posts'    => $posts,
		);
	}

	/**
	 * Translate the from/to inputs into a WP_Query date_query clause.
	 *
	 * Both bounds are inclusive and interpreted in the site timezone, matching
	 * how the values are presented back to the client.
	 *
	 * @param array $input Ability input.
	 * @return array|\WP_Error Clause (possibly empty).
	 */
	protected function build_date_query( array $input ) {
		$clause = array( 'inclusive' => true );

		if ( ! empty( $input['from'] ) ) {
			$from = $this->parse_datetime( $input['from'] );
			if ( is_wp_error( $from ) ) {
				return $from;
			}
			$clause['after'] = $from->format( 'Y-m-d H:i:s' );
		}
		if ( ! empty( $input['to'] ) ) {
			$to = $this->parse_datetime( $input['to'] );
			if ( is_wp_error( $to ) ) {
				return $to;
			}
			$clause['before'] = $to->format( 'Y-m-d H:i:s' );
		}

		return ( count( $clause ) > 1 ) ? $clause : array();
	}
}
