<?php
/**
 * Get social template ability.
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
 * Reads the per-post social share templates, alongside each platform's
 * character limit so a model can write a caption that will actually fit.
 */
class GetSocialTemplate extends AbilityBase {

	use PlatformMap;

	/**
	 * Post meta holding the per-platform templates.
	 */
	const META_KEY = '_wpsp_custom_templates';

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id          = 'schedulepress/get-social-template';
		$this->label       = __( 'Get Social Share Templates', 'wp-scheduled-posts' );
		$this->description = __( 'Read the per-platform social share captions saved on a post, with each platform\'s character limit and how much of it the current caption uses. Call this before writing or editing a caption.', 'wp-scheduled-posts' );
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
				'post_id'  => array(
					'type'        => 'integer',
					'description' => 'ID of the post whose templates to read.',
				),
				'platform' => array(
					'type'        => 'string',
					'description' => 'Restrict to a single platform slug. Omit for all platforms.',
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
				'post_id'   => array( 'type' => 'integer' ),
				'enabled'   => array( 'type' => 'boolean' ),
				'templates' => array(
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

		$platforms = $this->platform_slugs();
		if ( ! empty( $input['platform'] ) ) {
			$valid = $this->validate_platform( $input['platform'] );
			if ( is_wp_error( $valid ) ) {
				return $valid;
			}
			$platforms = array( (string) $input['platform'] );
		}

		$stored = get_post_meta( (int) $post->ID, self::META_KEY, true );
		$stored = is_array( $stored ) ? $stored : array();
		$limits = Helper::get_social_platform_limits();

		$templates = array();
		foreach ( $platforms as $platform ) {
			$entry    = isset( $stored[ $platform ] ) && is_array( $stored[ $platform ] ) ? $stored[ $platform ] : array();
			$template = isset( $entry['template'] ) ? (string) $entry['template'] : '';
			$limit    = isset( $limits[ $platform ] ) ? (int) $limits[ $platform ] : 0;

			$templates[] = array(
				'platform'        => $platform,
				'template'        => $template,
				'is_global'       => ! empty( $entry['is_global'] ),
				'profiles'        => isset( $entry['profiles'] ) && is_array( $entry['profiles'] ) ? array_values( $entry['profiles'] ) : array(),
				'character_limit' => $limit,
				'characters_used' => function_exists( 'mb_strlen' ) ? mb_strlen( $template ) : strlen( $template ),
			);
		}

		return array(
			'post_id'   => (int) $post->ID,
			'enabled'   => (bool) get_post_meta( (int) $post->ID, '_wpsp_enable_custom_social_template', true ),
			'templates' => $templates,
		);
	}
}
