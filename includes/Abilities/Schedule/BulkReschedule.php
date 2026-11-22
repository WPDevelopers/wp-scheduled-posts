<?php
/**
 * Bulk reschedule ability.
 *
 * @package WPSP\Abilities\Schedule
 */

namespace WPSP\Abilities\Schedule;

use WPSP\Abilities\AbilityBase;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Shifts a set of scheduled posts by the same relative amount — "move
 * everything scheduled for Friday to next week".
 *
 * Supports a dry run, because a bulk move is the one scheduling operation an AI
 * client is most likely to get wrong on the first try.
 */
class BulkReschedule extends AbilityBase {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id          = 'schedulepress/bulk-reschedule';
		$this->label       = __( 'Bulk Reschedule Posts', 'wp-scheduled-posts' );
		$this->description = __( 'Shift several scheduled posts by the same relative amount, selected either by explicit IDs or by a date range. Always preview with dry_run first: it reports exactly which posts would move and where, without changing anything.', 'wp-scheduled-posts' );
	}

	/**
	 * {@inheritDoc}
	 *
	 * @return array
	 */
	public function get_annotations() {
		return array(
			'readonly'      => false,
			'destructive'   => false,
			'idempotent'    => false,
			'priority'      => 2.5,
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
			'required'             => array( 'shift' ),
			'properties'           => array(
				'shift'      => array(
					'type'        => 'string',
					'description' => 'Relative move applied to every selected post, e.g. "+1 week", "+3 days", "-2 hours".',
				),
				'post_ids'   => array(
					'type'        => 'array',
					'items'       => array( 'type' => 'integer' ),
					'description' => 'Explicit post IDs to move. Use this or a from/to range, not both.',
				),
				'from'       => array(
					'type'        => 'string',
					'description' => 'Select scheduled posts at or after this date/time (ISO 8601).',
				),
				'to'         => array(
					'type'        => 'string',
					'description' => 'Select scheduled posts at or before this date/time (ISO 8601).',
				),
				'post_types' => array(
					'type'        => 'array',
					'items'       => array( 'type' => 'string' ),
					'description' => 'Restrict a range selection to these post types.',
				),
				'dry_run'    => array(
					'type'        => 'boolean',
					'default'     => true,
					'description' => 'When true (the default) nothing is changed; the response shows what would happen. Pass false to apply.',
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
				'dry_run' => array( 'type' => 'boolean' ),
				'moved'   => array( 'type' => 'integer' ),
				'skipped' => array( 'type' => 'integer' ),
				'changes' => array(
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

		$shift = isset( $input['shift'] ) ? trim( (string) $input['shift'] ) : '';
		if ( ! preg_match( '/^[+-]\s*\d+\s+(second|minute|hour|day|week|month|year)s?$/i', $shift ) ) {
			return new \WP_Error(
				'wpsp_invalid_shift',
				sprintf(
					/* translators: %s: the shift expression that was rejected. */
					__( 'Could not understand the shift "%s". Use a signed relative value such as "+1 week", "+3 days" or "-2 hours".', 'wp-scheduled-posts' ),
					$shift
				),
				array( 'status' => 400 )
			);
		}

		$posts = $this->select_posts( $input );
		if ( is_wp_error( $posts ) ) {
			return $posts;
		}

		// Default to a preview. A bulk move that silently applied because the
		// caller omitted the flag is exactly the accident worth designing out.
		$dry_run = array_key_exists( 'dry_run', $input ) ? (bool) $input['dry_run'] : true;

		$changes = array();
		$moved   = 0;
		$skipped = 0;

		foreach ( $posts as $post ) {
			$outcome = $this->plan_move( $post, $shift );
			if ( isset( $outcome['error'] ) ) {
				$skipped++;
				$changes[] = $outcome;
				continue;
			}

			if ( ! $dry_run ) {
				$gmt = clone $outcome['when'];
				$gmt->setTimezone( new \DateTimeZone( 'UTC' ) );
				$result = wp_update_post(
					array(
						'ID'            => (int) $post->ID,
						'post_status'   => 'future',
						'post_date'     => $outcome['when']->format( 'Y-m-d H:i:s' ),
						'post_date_gmt' => $gmt->format( 'Y-m-d H:i:s' ),
						'edit_date'     => true,
					),
					true
				);
				if ( is_wp_error( $result ) ) {
					$skipped++;
					$changes[] = array(
						'post_id' => (int) $post->ID,
						'title'   => get_the_title( $post ),
						'error'   => $result->get_error_message(),
					);
					continue;
				}
			}

			$moved++;
			$changes[] = array(
				'post_id' => (int) $post->ID,
				'title'   => get_the_title( $post ),
				'from'    => $this->to_iso( $post->post_date ),
				'to'      => $outcome['when']->format( 'c' ),
			);
		}

		return array(
			'dry_run' => $dry_run,
			'moved'   => $moved,
			'skipped' => $skipped,
			'changes' => $changes,
		);
	}

	/**
	 * Work out where one post would land, or why it cannot move.
	 *
	 * @param \WP_Post $post  Candidate post.
	 * @param string   $shift Relative expression.
	 * @return array {when: \DateTime} or {post_id, title, error}.
	 */
	protected function plan_move( $post, $shift ) {
		if ( ! current_user_can( 'edit_post', $post->ID ) ) {
			return array(
				'post_id' => (int) $post->ID,
				'title'   => get_the_title( $post ),
				'error'   => __( 'Not permitted to edit this post.', 'wp-scheduled-posts' ),
			);
		}

		$moved = strtotime( $shift, strtotime( $post->post_date ) );
		if ( false === $moved ) {
			return array(
				'post_id' => (int) $post->ID,
				'title'   => get_the_title( $post ),
				'error'   => __( 'Could not apply the shift to this post.', 'wp-scheduled-posts' ),
			);
		}
		if ( $moved <= time() ) {
			return array(
				'post_id' => (int) $post->ID,
				'title'   => get_the_title( $post ),
				'error'   => __( 'The shift would move this post into the past, leaving it stuck as a missed schedule.', 'wp-scheduled-posts' ),
			);
		}

		$when = new \DateTime( '@' . $moved );
		$when->setTimezone( $this->site_timezone() );

		return array( 'when' => $when );
	}

	/**
	 * Resolve the selection — explicit IDs or a date range, never both.
	 *
	 * @param array $input Ability input.
	 * @return \WP_Post[]|\WP_Error
	 */
	protected function select_posts( array $input ) {
		$has_ids   = ! empty( $input['post_ids'] ) && is_array( $input['post_ids'] );
		$has_range = ! empty( $input['from'] ) || ! empty( $input['to'] );

		if ( $has_ids === $has_range ) {
			return new \WP_Error(
				'wpsp_invalid_selection',
				__( 'Select posts either by post_ids or by a from/to range — exactly one of the two.', 'wp-scheduled-posts' ),
				array( 'status' => 400 )
			);
		}

		if ( $has_ids ) {
			$posts = array();
			foreach ( array_map( 'intval', $input['post_ids'] ) as $id ) {
				$post = get_post( $id );
				if ( $post && 'future' === $post->post_status ) {
					$posts[] = $post;
				}
			}
			return $posts;
		}

		$post_types = $this->resolve_post_types( isset( $input['post_types'] ) ? $input['post_types'] : null );
		if ( is_wp_error( $post_types ) ) {
			return $post_types;
		}

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

		$query = new \WP_Query(
			array(
				'post_type'        => $post_types,
				'post_status'      => 'future',
				'posts_per_page'   => 200,
				'orderby'          => 'date',
				'order'            => 'ASC',
				'no_found_rows'    => true,
				'suppress_filters' => false,
				'date_query'       => array( $clause ),
			)
		);

		return $query->posts;
	}
}
