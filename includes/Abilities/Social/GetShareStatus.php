<?php
/**
 * Get share status ability.
 *
 * @package WPSP\Abilities\Social
 */

namespace WPSP\Abilities\Social;

use WPSP\Abilities\AbilityBase;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Reports whether a post has been shared to each platform, and what the
 * platform said when it was.
 */
class GetShareStatus extends AbilityBase {

	use PlatformMap;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id          = 'schedulepress/get-share-status';
		$this->label       = __( 'Get Social Share Status', 'wp-scheduled-posts' );
		$this->description = __( 'Show whether a post has already been shared to each connected social platform, including the recorded response and whether sharing is disabled for that post. Use this before sharing to avoid duplicate posts.', 'wp-scheduled-posts' );
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
			'required'             => array( 'post_id' ),
			'properties'           => array(
				'post_id' => array(
					'type'        => 'integer',
					'description' => 'ID of the post to inspect.',
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
				'post_id'          => array( 'type' => 'integer' ),
				'sharing_disabled' => array( 'type' => 'boolean' ),
				'shared_platforms' => array( 'type' => 'integer' ),
				'platforms'        => array(
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

		$post = $this->require_post( isset( $input['post_id'] ) ? $input['post_id'] : 0, 'read_post' );
		if ( is_wp_error( $post ) ) {
			return $post;
		}

		$platforms = array();
		$shared    = 0;

		foreach ( $this->platform_slugs() as $platform ) {
			$log = get_post_meta( (int) $post->ID, $this->share_log_meta_key( $platform ), true );
			$log = is_array( $log ) ? $log : array();

			$entries = array();
			foreach ( $log as $profile_key => $response ) {
				$entries[] = array(
					'profile_key' => (string) $profile_key,
					// The stored response shape varies per platform SDK; pass it
					// through as a string so the model can read an error message
					// without us pretending to a schema we do not control.
					'response'    => is_scalar( $response ) ? (string) $response : wp_json_encode( $response ),
				);
			}

			if ( ! empty( $entries ) ) {
				$shared++;
			}

			$platforms[] = array(
				'platform'  => $platform,
				'shared'    => ! empty( $entries ),
				'share_log' => $entries,
			);
		}

		return array(
			'post_id'          => (int) $post->ID,
			'sharing_disabled' => (bool) get_post_meta( (int) $post->ID, '_wpscppro_dont_share_socialmedia', true ),
			'shared_platforms' => $shared,
			'platforms'        => $platforms,
		);
	}
}
