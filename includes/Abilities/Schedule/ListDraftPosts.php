<?php
/**
 * List draft posts ability.
 *
 * @package WPSP\Abilities\Schedule
 */

namespace WPSP\Abilities\Schedule;

use WPSP\Abilities\AbilityBase;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Lists unscheduled drafts — the pool an AI client draws from when asked to
 * fill empty slots in the calendar.
 */
class ListDraftPosts extends AbilityBase {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id          = 'schedulepress/list-draft-posts';
		$this->label       = __( 'List Draft Posts', 'wp-scheduled-posts' );
		$this->description = __( 'List drafts and pending posts that have no publish date yet — the pool to draw from when filling empty calendar slots.', 'wp-scheduled-posts' );
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
				'post_types' => array(
					'type'        => 'array',
					'items'       => array( 'type' => 'string' ),
					'description' => 'Restrict to these post types. Defaults to every type SchedulePress manages.',
				),
				'statuses'   => array(
					'type'        => 'array',
					'items'       => array(
						'type' => 'string',
						'enum' => array( 'draft', 'pending' ),
					),
					'description' => 'Which unscheduled statuses to include. Defaults to both.',
				),
				'search'     => array(
					'type'        => 'string',
					'description' => 'Optional keyword to match against the title and content.',
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
				'count' => array( 'type' => 'integer' ),
				'posts' => array(
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

		$statuses = isset( $input['statuses'] ) && is_array( $input['statuses'] ) && ! empty( $input['statuses'] )
			? array_values( array_intersect( array_map( 'strval', $input['statuses'] ), array( 'draft', 'pending' ) ) )
			: array( 'draft', 'pending' );

		$limit = isset( $input['limit'] ) ? (int) $input['limit'] : 50;
		$limit = max( 1, min( 200, $limit ) );

		$args = array(
			'post_type'        => $post_types,
			'post_status'      => $statuses,
			'posts_per_page'   => $limit,
			'orderby'          => 'modified',
			'order'            => 'DESC',
			'no_found_rows'    => true,
			'suppress_filters' => false,
		);
		if ( ! empty( $input['search'] ) ) {
			$args['s'] = (string) $input['search'];
		}

		$query = new \WP_Query( $args );
		$posts = array();
		foreach ( $query->posts as $post ) {
			$described             = $this->describe_post( $post );
			$described['modified'] = $this->to_iso( $post->post_modified );
			// A draft's post_date is a placeholder, not a plan — saying
			// "scheduled_at" for it would invite the model to treat an
			// unscheduled item as scheduled.
			unset( $described['scheduled_at'], $described['scheduled_at_gmt'] );
			$posts[] = $described;
		}

		return array(
			'count' => count( $posts ),
			'posts' => $posts,
		);
	}
}
