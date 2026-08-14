<?php
/**
 * Update social template ability.
 *
 * @package WPSP\Abilities\Social
 */

namespace WPSP\Abilities\Social;

use WPSP\Abilities\AbilityBase;
use WPSP\Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Writes a per-post social share caption for one platform.
 *
 * The write is validated against the same character limit the editor's counter
 * shows, so a caption accepted here cannot be rejected later by the platform.
 */
class UpdateSocialTemplate extends AbilityBase {

	use PlatformMap;

	/**
	 * Post meta holding the per-platform templates.
	 */
	const META_KEY = '_wpsp_custom_templates';

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id          = 'schedulepress/update-social-template';
		$this->label       = __( 'Update a Social Share Template', 'wp-scheduled-posts' );
		$this->description = __( 'Write the social share caption for one platform on one post. The caption is checked against that platform\'s character limit before saving. This only edits the saved caption — it does not post anything.', 'wp-scheduled-posts' );
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
			'required'             => array( 'post_id', 'platform', 'template' ),
			'properties'           => array(
				'post_id'  => array(
					'type'        => 'integer',
					'description' => 'ID of the post to update.',
				),
				'platform' => array(
					'type'        => 'string',
					'description' => 'Platform slug, e.g. facebook, twitter, linkedin.',
				),
				'template' => array(
					'type'        => 'string',
					'description' => 'The caption text. Must fit the platform\'s character limit.',
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
				'post_id'         => array( 'type' => 'integer' ),
				'platform'        => array( 'type' => 'string' ),
				'template'        => array( 'type' => 'string' ),
				'previous'        => array( 'type' => 'string' ),
				'character_limit' => array( 'type' => 'integer' ),
				'characters_used' => array( 'type' => 'integer' ),
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

		$platform = isset( $input['platform'] ) ? (string) $input['platform'] : '';
		$valid    = $this->validate_platform( $platform );
		if ( is_wp_error( $valid ) ) {
			return $valid;
		}

		$template = isset( $input['template'] ) ? (string) $input['template'] : '';
		// Captions are plain text destined for third-party APIs; markup here is
		// never wanted and would be posted verbatim.
		$template = wp_strip_all_tags( $template );

		$limits = Helper::get_social_platform_limits();
		$limit  = isset( $limits[ $platform ] ) ? (int) $limits[ $platform ] : 0;
		$used   = function_exists( 'mb_strlen' ) ? mb_strlen( $template ) : strlen( $template );

		if ( $limit > 0 && $used > $limit ) {
			return new \WP_Error(
				'wpsp_template_too_long',
				sprintf(
					/* translators: 1: platform slug, 2: character limit, 3: characters supplied. */
					__( 'The caption is too long for %1$s: the limit is %2$d characters and this is %3$d. Shorten it and try again.', 'wp-scheduled-posts' ),
					$platform,
					$limit,
					$used
				),
				array( 'status' => 400 )
			);
		}

		$stored = get_post_meta( (int) $post->ID, self::META_KEY, true );
		$stored = is_array( $stored ) ? $stored : array();

		$entry    = isset( $stored[ $platform ] ) && is_array( $stored[ $platform ] ) ? $stored[ $platform ] : array();
		$previous = isset( $entry['template'] ) ? (string) $entry['template'] : '';

		$entry['template']  = $template;
		$entry['profiles']  = isset( $entry['profiles'] ) && is_array( $entry['profiles'] ) ? $entry['profiles'] : array();
		$entry['is_global'] = ! empty( $entry['is_global'] );

		$stored[ $platform ] = $entry;
		update_post_meta( (int) $post->ID, self::META_KEY, $stored );

		// Mirror the editor's own bookkeeping: the "custom template" switch is
		// derived from whether any platform actually has text, so writing a
		// caption without updating it would leave the caption saved but unused.
		$has_any = false;
		foreach ( $stored as $one ) {
			if ( is_array( $one ) && ! empty( $one['template'] ) ) {
				$has_any = true;
				break;
			}
		}
		update_post_meta( (int) $post->ID, '_wpsp_enable_custom_social_template', $has_any );

		return array(
			'post_id'         => (int) $post->ID,
			'platform'        => $platform,
			'template'        => $template,
			'previous'        => $previous,
			'character_limit' => $limit,
			'characters_used' => $used,
		);
	}
}
