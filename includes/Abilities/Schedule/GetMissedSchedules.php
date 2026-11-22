<?php
/**
 * Get missed schedules ability.
 *
 * @package WPSP\Abilities\Schedule
 */

namespace WPSP\Abilities\Schedule;

use WPSP\Abilities\AbilityBase;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Posts stuck in the `future` status whose publish time has already passed —
 * the classic "missed schedule" failure, usually a cron problem.
 */
class GetMissedSchedules extends AbilityBase {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id          = 'schedulepress/get-missed-schedules';
		$this->label       = __( 'Get Missed Schedules', 'wp-scheduled-posts' );
		$this->description = __( 'List posts whose scheduled publish time has already passed but are still waiting in the future queue — the "missed schedule" failure. Includes how late each one is and whether WP-Cron looks healthy.', 'wp-scheduled-posts' );
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
				'count'           => array( 'type' => 'integer' ),
				'cron_disabled'   => array( 'type' => 'boolean' ),
				'checked_at'      => array( 'type' => 'string' ),
				'posts'           => array(
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

		$now = new \DateTime( 'now', $this->site_timezone() );

		// A missed schedule is a `future` post whose time has passed. Compare in
		// GMT — post_date_gmt is the only field that is unambiguous regardless
		// of what the site's timezone setting has been changed to since.
		$query = new \WP_Query(
			array(
				'post_type'        => $post_types,
				'post_status'      => 'future',
				'posts_per_page'   => $limit,
				'orderby'          => 'date',
				'order'            => 'ASC',
				'no_found_rows'    => true,
				'suppress_filters' => false,
				'date_query'       => array(
					array(
						'column'    => 'post_date_gmt',
						'before'    => gmdate( 'Y-m-d H:i:s' ),
						'inclusive' => false,
					),
				),
			)
		);

		$posts = array();
		foreach ( $query->posts as $post ) {
			$described = $this->describe_post( $post );
			$late      = 0;
			$due       = strtotime( $post->post_date_gmt . ' UTC' );
			if ( $due ) {
				$late = max( 0, time() - (int) $due );
			}
			$described['late_seconds'] = $late;
			$described['late_human']   = human_time_diff( time() - $late, time() );
			$posts[]                   = $described;
		}

		return array(
			'count'         => count( $posts ),
			// The usual root cause. Worth reporting even when nothing is late,
			// because it predicts the next failure.
			'cron_disabled' => defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON,
			'checked_at'    => $now->format( 'c' ),
			'posts'         => $posts,
		);
	}
}
