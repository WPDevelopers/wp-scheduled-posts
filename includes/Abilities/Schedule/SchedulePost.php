<?php
/**
 * Schedule post ability.
 *
 * @package WPSP\Abilities\Schedule
 */

namespace WPSP\Abilities\Schedule;

use WPSP\Abilities\AbilityBase;
use WPSP\Abilities\Social\ShareNow;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Moves an existing draft into the future queue at a given date/time.
 */
class SchedulePost extends AbilityBase {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id          = 'schedulepress/schedule-post';
		$this->label       = __( 'Schedule a Post', 'wp-scheduled-posts' );
		$this->description = __( 'Schedule an existing draft or pending post to publish at a given date and time. The time is interpreted in the site timezone unless the value carries an explicit offset. A post that is already scheduled is moved to the new time (reschedule-post does the same and also takes a relative shift). When it goes live the post is shared to social media by its own share settings; while AI posting to social media is blocked, sharing is turned off for the post and the response says so.', 'wp-scheduled-posts' );
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
			'idempotent'    => true,
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
			'required'             => array( 'post_id', 'publish_at' ),
			'properties'           => array(
				'post_id'    => array(
					'type'        => 'integer',
					'description' => 'ID of the post to schedule.',
				),
				'publish_at' => array(
					'type'        => 'string',
					'description' => 'When to publish (ISO 8601; no offset means site-local). Must be in the future.',
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
				'note'     => array( 'type' => 'string' ),
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

		$when = $this->parse_datetime( isset( $input['publish_at'] ) ? $input['publish_at'] : '' );
		if ( is_wp_error( $when ) ) {
			return $when;
		}

		if ( 'trash' === $post->post_status ) {
			return new \WP_Error(
				'wpsp_post_trashed',
				__( 'That post is in the trash. Restore it before scheduling.', 'wp-scheduled-posts' ),
				array( 'status' => 409 )
			);
		}

		// Private and published posts are refused: `future` would make a private post public, or take a live one offline.
		$allowed = $this->require_schedulable( $post, array( 'draft', 'pending', 'future' ) );
		if ( is_wp_error( $allowed ) ) {
			return $allowed;
		}

		// A past or too-near time would publish at once, the opposite of "schedule this".
		$soon = $this->require_lead_time( $when );
		if ( is_wp_error( $soon ) ) {
			return $soon;
		}

		$previous = $this->describe_post( $post );

		// With AI posting blocked, a post the assistant schedules must not share when it goes live.
		$share_off  = false;
		$share_meta = get_post_meta( (int) $post->ID, '_wpscppro_dont_share_socialmedia', true );
		// The classic box once saved 'on' / 'off', the block editor saves '1'.
		$already_off = in_array( $share_meta, array( 'on', '1', 1, true ), true );
		if ( 'future' !== $post->post_status && ! $already_off && ! ShareNow::is_publishing_allowed() ) {
			update_post_meta( (int) $post->ID, '_wpscppro_dont_share_socialmedia', '1' );
			$share_off = true;
		}

		$gmt = clone $when;
		$gmt->setTimezone( new \DateTimeZone( 'UTC' ) );

		$result = wp_update_post(
			array(
				'ID'            => (int) $post->ID,
				'post_status'   => 'future',
				'post_date'     => $when->format( 'Y-m-d H:i:s' ),
				'post_date_gmt' => $gmt->format( 'Y-m-d H:i:s' ),
				'edit_date'     => true,
			),
			true
		);
		if ( is_wp_error( $result ) ) {
			if ( $share_off ) {
				update_post_meta( (int) $post->ID, '_wpscppro_dont_share_socialmedia', $share_meta );
			}
			return $result;
		}

		$out = array(
			'post'     => $this->describe_post( get_post( (int) $post->ID ) ),
			'previous' => $previous,
		);
		if ( $share_off ) {
			$out['note'] = __( 'Social sharing was turned off for this post because AI posting to social media is blocked in SchedulePress > AI (MCP). Turn sharing back on in the post editor if it should be shared when it goes live.', 'wp-scheduled-posts' );
		}
		return $out;
	}
}
