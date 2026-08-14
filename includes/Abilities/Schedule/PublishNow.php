<?php
/**
 * Publish now ability.
 *
 * @package WPSP\Abilities\Schedule
 */

namespace WPSP\Abilities\Schedule;

use WPSP\Abilities\AbilityBase;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Publishes a post immediately.
 *
 * Publishing is outward-facing and effectively irreversible — the post becomes
 * publicly visible, feeds go out, and any share-on-publish automation fires. So
 * this one requires an explicit `confirm` rather than trusting a single tool
 * call, and it refuses posts the current user cannot publish.
 */
class PublishNow extends AbilityBase {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id          = 'schedulepress/publish-now';
		$this->label       = __( 'Publish a Post Now', 'wp-scheduled-posts' );
		$this->description = __( 'Publish a post immediately, ahead of its schedule. This makes the post publicly visible right away and can trigger social auto-sharing, so it requires confirm: true. Prefer reschedule-post unless the user explicitly asked to publish now.', 'wp-scheduled-posts' );
	}

	/**
	 * {@inheritDoc}
	 *
	 * @return array
	 */
	public function get_annotations() {
		return array(
			'readonly'      => false,
			'destructive'   => true,
			'idempotent'    => true,
			'priority'      => 3.0,
			'openWorldHint' => true,
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
			'required'             => array( 'post_id', 'confirm' ),
			'properties'           => array(
				'post_id' => array(
					'type'        => 'integer',
					'description' => 'ID of the post to publish immediately.',
				),
				'confirm' => array(
					'type'        => 'boolean',
					'description' => 'Must be true. Publishing is immediate and publicly visible; confirm with the user first.',
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

		if ( empty( $input['confirm'] ) ) {
			return new \WP_Error(
				'wpsp_confirmation_required',
				__( 'Publishing is immediate and publicly visible. Confirm with the user, then call again with confirm: true.', 'wp-scheduled-posts' ),
				array( 'status' => 400 )
			);
		}

		$post = $this->require_post( isset( $input['post_id'] ) ? $input['post_id'] : 0, 'publish_post' );
		if ( is_wp_error( $post ) ) {
			return $post;
		}

		if ( 'publish' === $post->post_status ) {
			return new \WP_Error(
				'wpsp_already_published',
				sprintf(
					/* translators: %d: post ID. */
					__( 'Post %d is already published.', 'wp-scheduled-posts' ),
					(int) $post->ID
				),
				array( 'status' => 409 )
			);
		}

		$previous = $this->describe_post( $post );
		$now      = new \DateTime( 'now', $this->site_timezone() );
		$gmt      = clone $now;
		$gmt->setTimezone( new \DateTimeZone( 'UTC' ) );

		$result = wp_update_post(
			array(
				'ID'            => (int) $post->ID,
				'post_status'   => 'publish',
				'post_date'     => $now->format( 'Y-m-d H:i:s' ),
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
}
