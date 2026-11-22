<?php
/**
 * Unschedule post ability.
 *
 * @package WPSP\Abilities\Schedule
 */

namespace WPSP\Abilities\Schedule;

use WPSP\Abilities\AbilityBase;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Pulls a post out of the future queue and back to draft.
 */
class UnschedulePost extends AbilityBase {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id          = 'schedulepress/unschedule-post';
		$this->label       = __( 'Unschedule a Post', 'wp-scheduled-posts' );
		$this->description = __( 'Take a scheduled post out of the queue and return it to draft. The post is not deleted and its content is untouched; only its status changes.', 'wp-scheduled-posts' );
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
			'required'             => array( 'post_id' ),
			'properties'           => array(
				'post_id' => array(
					'type'        => 'integer',
					'description' => 'ID of the scheduled post to unschedule.',
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

		if ( 'future' !== $post->post_status ) {
			return new \WP_Error(
				'wpsp_not_scheduled',
				sprintf(
					/* translators: 1: post ID, 2: current post status. */
					__( 'Post %1$d is not scheduled (its status is "%2$s"), so there is nothing to unschedule.', 'wp-scheduled-posts' ),
					(int) $post->ID,
					$post->post_status
				),
				array( 'status' => 409 )
			);
		}

		$previous = $this->describe_post( $post );

		$result = wp_update_post(
			array(
				'ID'          => (int) $post->ID,
				'post_status' => 'draft',
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
