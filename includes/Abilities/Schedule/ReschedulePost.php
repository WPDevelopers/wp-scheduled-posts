<?php
/**
 * Reschedule post ability.
 *
 * @package WPSP\Abilities\Schedule
 */

namespace WPSP\Abilities\Schedule;

use WPSP\Abilities\AbilityBase;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Moves an already-scheduled post to a new date/time, either absolutely or by
 * a relative shift ("push it a week").
 */
class ReschedulePost extends AbilityBase {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id          = 'schedulepress/reschedule-post';
		$this->label       = __( 'Reschedule a Post', 'wp-scheduled-posts' );
		$this->description = __( 'Move a scheduled post to a new date and time. Give either an absolute publish_at, or a relative shift such as "+3 days" or "-2 hours". Times are site-local unless an offset is supplied.', 'wp-scheduled-posts' );
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
			'priority'      => 2.0,
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
			'required'             => array( 'post_id' ),
			'properties'           => array(
				'post_id'    => array(
					'type'        => 'integer',
					'description' => 'ID of the scheduled post to move.',
				),
				'publish_at' => array(
					'type'        => 'string',
					'description' => 'New absolute publish time (ISO 8601). Mutually exclusive with shift.',
				),
				'shift'      => array(
					'type'        => 'string',
					'description' => 'Relative move from the current time, e.g. "+1 week", "+3 days", "-2 hours". Mutually exclusive with publish_at.',
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
				'post'     => array( 'type' => 'object' ),
				'previous' => array( 'type' => 'object' ),
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

		$post = $this->require_post( isset( $input['post_id'] ) ? $input['post_id'] : 0 );
		if ( is_wp_error( $post ) ) {
			return $post;
		}

		$has_absolute = ! empty( $input['publish_at'] );
		$has_shift    = ! empty( $input['shift'] );

		if ( $has_absolute === $has_shift ) {
			return new \WP_Error(
				'wpsp_invalid_input',
				__( 'Provide exactly one of publish_at (absolute) or shift (relative).', 'wp-scheduled-posts' ),
				array( 'status' => 400 )
			);
		}

		if ( $has_absolute ) {
			$when = $this->parse_datetime( $input['publish_at'] );
			if ( is_wp_error( $when ) ) {
				return $when;
			}
		} else {
			$when = $this->shift_from_current( $post, (string) $input['shift'] );
			if ( is_wp_error( $when ) ) {
				return $when;
			}
		}

		$previous = $this->describe_post( $post );

		// A post sitting in the future queue must keep a future date: moving it
		// backwards past "now" would leave it stuck as a missed schedule rather
		// than publishing it. Posts in any other status just get their date
		// changed, status untouched.
		$is_queued = ( 'future' === $post->post_status );
		if ( $is_queued && $when->getTimestamp() <= time() ) {
			return new \WP_Error(
				'wpsp_past_datetime',
				sprintf(
					/* translators: %s: the requested publish time. */
					__( 'The requested time %s is in the past, which would leave the post stuck as a missed schedule. Pick a future time, or use publish-now.', 'wp-scheduled-posts' ),
					$when->format( 'c' )
				),
				array( 'status' => 400 )
			);
		}
		$status = $post->post_status;

		$gmt = clone $when;
		$gmt->setTimezone( new \DateTimeZone( 'UTC' ) );

		$result = wp_update_post(
			array(
				'ID'            => (int) $post->ID,
				'post_status'   => $status,
				'post_date'     => $when->format( 'Y-m-d H:i:s' ),
				'post_date_gmt' => $gmt->format( 'Y-m-d H:i:s' ),
				'edit_date'     => true,
			),
			true
		);
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return array(
			'post'     => $this->describe_post( get_post( (int) $post->ID ) ),
			'previous' => $previous,
		);
	}

	/**
	 * Apply a relative shift to a post's current publish time.
	 *
	 * @param \WP_Post $post  Post being moved.
	 * @param string   $shift Relative expression, e.g. "+1 week".
	 * @return \DateTime|\WP_Error
	 */
	protected function shift_from_current( $post, $shift ) {
		$shift = trim( $shift );
		// strtotime() accepts almost anything, including absolute dates that
		// would silently ignore the post's current time. Require a signed
		// relative expression so "shift" can only ever mean a shift.
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

		try {
			$current = new \DateTime( $post->post_date, $this->site_timezone() );
		} catch ( \Exception $e ) {
			return new \WP_Error(
				'wpsp_invalid_datetime',
				__( 'That post has no usable publish date to shift from.', 'wp-scheduled-posts' ),
				array( 'status' => 409 )
			);
		}

		$moved = strtotime( $shift, $current->getTimestamp() );
		if ( false === $moved ) {
			return new \WP_Error(
				'wpsp_invalid_shift',
				__( 'Could not apply that shift to the post\'s current publish time.', 'wp-scheduled-posts' ),
				array( 'status' => 400 )
			);
		}

		$result = new \DateTime( '@' . $moved );
		$result->setTimezone( $this->site_timezone() );
		return $result;
	}
}
