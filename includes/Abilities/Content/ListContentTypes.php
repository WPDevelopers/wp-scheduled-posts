<?php
/**
 * List content types ability.
 *
 * @package WPSP\Abilities\Content
 */

namespace WPSP\Abilities\Content;

use WPSP\Abilities\AbilityBase;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Lists the post types SchedulePress manages, plus the site's timezone.
 *
 * This is the orientation call: an AI client runs it first to learn which post
 * types it may schedule and — critically — what timezone every other tool's
 * dates are anchored to.
 */
class ListContentTypes extends AbilityBase {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id          = 'schedulepress/list-content-types';
		$this->label       = __( 'List Schedulable Content Types', 'wp-scheduled-posts' );
		$this->description = __( 'List the post types SchedulePress is configured to schedule, along with the site timezone every scheduling tool uses. Call this first to learn what can be scheduled here.', 'wp-scheduled-posts' );
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
			'properties'           => array(),
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
				'timezone'        => array( 'type' => 'string' ),
				'current_time'    => array( 'type' => 'string' ),
				'post_types'      => array(
					'type'  => 'array',
					'items' => array(
						'type'       => 'object',
						'properties' => array(
							'slug'      => array( 'type' => 'string' ),
							'label'     => array( 'type' => 'string' ),
							'scheduled' => array( 'type' => 'integer' ),
							'drafts'    => array( 'type' => 'integer' ),
						),
					),
				),
				'all_post_types'  => array(
					'type'  => 'array',
					'items' => array( 'type' => 'string' ),
				),
			),
		);
	}

	/**
	 * Execute ability.
	 *
	 * @param array $input Ability input payload.
	 * @return array
	 */
	public function execute( $input ) {
		$now        = new \DateTime( 'now', $this->site_timezone() );
		$post_types = array();

		foreach ( $this->allowed_post_types() as $slug ) {
			$object = get_post_type_object( $slug );
			if ( ! $object ) {
				continue;
			}
			$counts       = wp_count_posts( $slug );
			$post_types[] = array(
				'slug'      => $slug,
				'label'     => (string) ( isset( $object->labels->singular_name ) ? $object->labels->singular_name : $object->label ),
				'scheduled' => isset( $counts->future ) ? (int) $counts->future : 0,
				'drafts'    => isset( $counts->draft ) ? (int) $counts->draft : 0,
			);
		}

		return array(
			'timezone'       => $this->site_timezone()->getName(),
			'current_time'   => $now->format( 'c' ),
			'post_types'     => $post_types,
			'all_post_types' => array_values( get_post_types( array( 'public' => true ), 'names' ) ),
		);
	}
}
