<?php
/**
 * Suggest social captions ability.
 *
 * @package WPSP\Abilities\Social
 */

namespace WPSP\Abilities\Social;

use WPSP\Abilities\AbilityBase;
use WPSP\API\AICaption;
use WPSP\Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Generates a social share caption for one or more platforms from a post, using
 * the site's configured OpenAI key and the same generator as the editor's
 * Write With AI.
 *
 * It only suggests — nothing is saved. An AI client reads the suggestions and
 * then calls `update-social-template` to store the ones it wants. It is
 * annotated `readonly: false` on purpose: generating a caption spends the
 * site's own AI budget and is non-deterministic, so a read-only connection must
 * not be able to trigger it.
 */
class SuggestCaption extends AbilityBase {

	use PlatformMap;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id          = 'schedulepress/suggest-caption';
		$this->label       = __( 'Suggest Social Captions', 'wp-scheduled-posts' );
		$this->description = __( 'Generate a social share caption for one or more platforms from a post, using your configured OpenAI key. Returns suggestions only — it does not save anything; call update-social-template to store a caption. Each suggestion is trimmed to that platform\'s character limit.', 'wp-scheduled-posts' );
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
			// Reaches an external service (OpenAI), so it is not a closed-world tool.
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
			'required'             => array( 'post_id', 'platforms' ),
			'properties'           => array(
				'post_id'           => array(
					'type'        => 'integer',
					'description' => 'ID of the post to write captions for.',
				),
				'platforms'         => array(
					'type'        => 'array',
					'items'       => array( 'type' => 'string' ),
					'minItems'    => 1,
					'description' => 'Platform slugs, e.g. facebook, twitter, linkedin.',
				),
				'prompt'            => array(
					'type'        => 'string',
					'description' => 'Optional extra instructions to steer the caption. Without one, captions are based on the post title and content.',
				),
				'tone'              => array(
					'type'        => 'string',
					'description' => 'Writing tone, e.g. professional, casual, witty. Use "post_specific" to match the post\'s own voice. Defaults to professional.',
				),
				'length'            => array(
					'type'        => 'string',
					'enum'        => array( 'auto', 'short', 'medium', 'long' ),
					'description' => 'Desired caption length. Defaults to auto.',
				),
				'generate_hashtags' => array(
					'type'        => 'boolean',
					'description' => 'Include a few relevant hashtags. Defaults to false.',
				),
				'include_emojis'    => array(
					'type'        => 'boolean',
					'description' => 'Use tasteful, relevant emojis. Defaults to false.',
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
				'post_id'  => array( 'type' => 'integer' ),
				'saved'    => array( 'type' => 'boolean' ),
				'model'    => array( 'type' => 'string' ),
				'captions' => array(
					'type'  => 'array',
					'items' => array(
						'type'       => 'object',
						'properties' => array(
							'platform'        => array( 'type' => 'string' ),
							'caption'         => array( 'type' => 'string' ),
							'character_limit' => array( 'type' => 'integer' ),
							'characters_used' => array( 'type' => 'integer' ),
						),
					),
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

		// Reading the post and spending the site's AI budget warrants the same
		// check as editing it.
		$post = $this->require_post( isset( $input['post_id'] ) ? $input['post_id'] : 0 );
		if ( is_wp_error( $post ) ) {
			return $post;
		}

		$platforms = isset( $input['platforms'] ) && is_array( $input['platforms'] )
			? array_values( array_unique( array_map( 'strval', $input['platforms'] ) ) )
			: array();
		if ( empty( $platforms ) ) {
			return new \WP_Error(
				'wpsp_no_platform',
				__( 'Select at least one platform.', 'wp-scheduled-posts' ),
				array( 'status' => 400 )
			);
		}

		// Names and fallback limits come from the editor's list, so both prompt alike.
		$platform_meta = AICaption::get_instance()->get_platform_meta();
		foreach ( $platforms as $platform ) {
			$valid = $this->validate_platform( $platform );
			if ( is_wp_error( $valid ) ) {
				return $valid;
			}
			if ( ! isset( $platform_meta[ $platform ] ) ) {
				return new \WP_Error(
					'wpsp_invalid_platform',
					/* translators: %s: platform slug */
					sprintf( __( 'Captions are not available for platform "%s".', 'wp-scheduled-posts' ), $platform ),
					array( 'status' => 400 )
				);
			}
		}

		$api_key = Helper::get_settings( 'openai_api_key' );
		$api_key = is_string( $api_key ) ? trim( $api_key ) : '';
		if ( '' === $api_key ) {
			return new \WP_Error(
				'wpsp_openai_key_missing',
				__( 'OpenAI API key is not configured. Add it in SchedulePress → Settings → AI.', 'wp-scheduled-posts' ),
				array( 'status' => 400 )
			);
		}

		$opts = array(
			'prompt'            => isset( $input['prompt'] ) ? sanitize_textarea_field( (string) $input['prompt'] ) : '',
			// The editor drawer's default: with no prompt, write from the post itself.
			'auto_generate'     => true,
			'tone'              => ( isset( $input['tone'] ) ? sanitize_text_field( (string) $input['tone'] ) : '' ) ?: 'professional',
			'length'            => ( isset( $input['length'] ) ? sanitize_key( (string) $input['length'] ) : '' ) ?: 'auto',
			'generate_hashtags' => ! empty( $input['generate_hashtags'] ),
			'include_emojis'    => ! empty( $input['include_emojis'] ),
		);

		$generator = new CaptionGenerator();
		$result    = $generator->generate( $api_key, $post->ID, $platforms, $platform_meta, $opts );
		if ( is_wp_error( $result ) ) {
			return $this->clean_error( $result );
		}

		$limits   = Helper::get_social_platform_limits();
		$captions = array();
		foreach ( $result['captions'] as $slug => $text ) {
			// A blank reply would save an empty template; skip it.
			if ( '' === $text ) {
				continue;
			}
			$captions[] = array(
				'platform'        => (string) $slug,
				'caption'         => (string) $text,
				'character_limit' => isset( $limits[ $slug ] ) ? (int) $limits[ $slug ] : 0,
				'characters_used' => function_exists( 'mb_strlen' ) ? mb_strlen( $text ) : strlen( $text ),
			);
		}
		if ( empty( $captions ) ) {
			return $this->clean_error( new \WP_Error( 'wpsp_openai_empty' ) );
		}

		return array(
			'post_id'  => (int) $post->ID,
			'saved'    => false,
			'model'    => (string) $result['model'],
			'captions' => $captions,
		);
	}

	/**
	 * Swap a generator error for a fixed message.
	 *
	 * OpenAI's own error text can echo part of the API key, and a transport
	 * error carries raw HTTP/cURL detail; neither may reach a tool response.
	 *
	 * @param \WP_Error $error Error from CaptionGenerator.
	 * @return \WP_Error
	 */
	protected function clean_error( \WP_Error $error ) {
		$code = $error->get_error_code();
		$data = $error->get_error_data();
		$http = is_array( $data ) && isset( $data['http_status'] ) ? (int) $data['http_status'] : 0;

		switch ( $code ) {
			case 'wpsp_openai_request_failed':
				$message = __( 'Could not reach OpenAI. Please try again later.', 'wp-scheduled-posts' );
				break;
			case 'wpsp_openai_error':
				if ( 401 === $http ) {
					$message = __( 'OpenAI rejected the API key. Check it in SchedulePress → Settings → AI.', 'wp-scheduled-posts' );
				} elseif ( 429 === $http ) {
					$message = __( 'OpenAI rate limit or quota reached. Please try again later.', 'wp-scheduled-posts' );
				} else {
					$message = __( 'OpenAI request failed. Please try again later.', 'wp-scheduled-posts' );
				}
				break;
			case 'wpsp_openai_empty':
				$message = __( 'OpenAI returned an empty response.', 'wp-scheduled-posts' );
				break;
			case 'wpsp_openai_unparsable':
				$message = __( 'Could not parse captions from the AI response.', 'wp-scheduled-posts' );
				break;
			default:
				$code    = 'wpsp_caption_failed';
				$message = __( 'Could not generate captions. Please try again later.', 'wp-scheduled-posts' );
		}

		return new \WP_Error( $code, $message, array( 'status' => 502 ) );
	}
}
