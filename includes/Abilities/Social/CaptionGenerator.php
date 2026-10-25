<?php
/**
 * Social caption generator.
 *
 * @package WPSP\Abilities\Social
 */

namespace WPSP\Abilities\Social;

use WPSP\Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Turns a post into platform-tailored social captions using the site's own
 * OpenAI key.
 *
 * The one home of the Write With AI prompt, OpenAI call and limit trimming:
 * the editor's `WPSP\API\AICaption` route and the `suggest-caption` ability
 * both call it, so the two can't drift. Callers validate their own input
 * (post access, API key, platform slugs) and own their error messages.
 */
class CaptionGenerator {

	/**
	 * OpenAI chat completions endpoint.
	 */
	const OPENAI_ENDPOINT = 'https://api.openai.com/v1/chat/completions';

	/**
	 * Generate a caption for each platform.
	 *
	 * @param string $api_key       OpenAI API key.
	 * @param int    $post_id       Post to write captions for.
	 * @param array  $platforms     Platform slugs, each a key of $platform_meta.
	 * @param array  $platform_meta Slug => `{ name, limit }` (display name, fallback limit).
	 * @param array  $opts          `prompt`, `auto_generate`, `tone`, `length`, `generate_hashtags`, `include_emojis`.
	 * @return array|\WP_Error `{ captions: array<slug,string>, model: string }`, or an error.
	 */
	public function generate( $api_key, $post_id, $platforms, $platform_meta, $opts ) {
		$opts = array_merge(
			array(
				'prompt'            => '',
				'auto_generate'     => false,
				'tone'              => 'professional',
				'length'            => 'auto',
				'generate_hashtags' => false,
				'include_emojis'    => false,
			),
			(array) $opts
		);

		$limits   = Helper::get_social_platform_limits();
		$messages = $this->build_messages( $platforms, $opts, $this->post_context( $post_id ), $platform_meta, $limits );
		$model    = apply_filters( 'wpsp_openai_model', 'gpt-4o-mini' );

		$content = $this->request_openai( $api_key, $model, $messages );
		if ( is_wp_error( $content ) ) {
			return $content;
		}

		$captions = $this->parse_captions( $content, $platforms, $limits );
		if ( is_wp_error( $captions ) ) {
			return $captions;
		}

		return array(
			'captions' => $captions,
			'model'    => $model,
		);
	}

	/**
	 * The post fields the prompt is built from.
	 *
	 * @param int $post_id Post ID.
	 * @return array `title`, `excerpt` (tags stripped, first 1500 characters), `permalink`.
	 */
	protected function post_context( $post_id ) {
		$post         = get_post( $post_id );
		$post_content = wp_strip_all_tags( $post ? $post->post_content : '' );
		$post_content = trim( preg_replace( '/\s+/', ' ', $post_content ) );

		return array(
			'title'     => get_the_title( $post_id ),
			'excerpt'   => function_exists( 'mb_substr' ) ? mb_substr( $post_content, 0, 1500 ) : substr( $post_content, 0, 1500 ),
			'permalink' => get_permalink( $post_id ),
		);
	}

	/**
	 * Build the OpenAI chat messages (system + user).
	 *
	 * @param array $platforms     Platform slugs.
	 * @param array $opts          Generation options.
	 * @param array $post          Post context.
	 * @param array $platform_meta Slug => `{ name, limit }`.
	 * @param array $limits        Slug => limit from the settings; wins over the meta limit.
	 * @return array
	 */
	protected function build_messages( $platforms, $opts, $post, $platform_meta, $limits ) {
		// Character ranges the AI generates to. Keep these in sync with
		// LENGTH_OPTIONS in src/components/modals/socialTemplates/AICaptionDrawer.js.
		$length_guide = array(
			'auto'   => 'an appropriate length for each platform',
			'short'  => 'short and punchy — roughly 50-120 characters (1-2 sentences)',
			'medium' => 'a moderate length — roughly 120-250 characters (2-4 sentences)',
			'long'   => 'detailed and engaging — roughly 250-500 characters (multiple sentences)',
		);
		$length_text = isset( $length_guide[ $opts['length'] ] ) ? $length_guide[ $opts['length'] ] : $length_guide['auto'];

		// Per-platform constraints. Use the limits configured in settings (the
		// same ones template validation enforces) so generated captions fit.
		$platform_lines = array();
		foreach ( $platforms as $key ) {
			$meta             = $platform_meta[ $key ];
			$limit            = isset( $limits[ $key ] ) ? $limits[ $key ] : $meta['limit'];
			$platform_lines[] = sprintf( '- "%s" (%s, max %d characters — never exceed this)', $key, $meta['name'], $limit );
		}
		$platform_block = implode( "\n", $platform_lines );
		$keys_list      = '"' . implode( '", "', $platforms ) . '"';

		$system = 'You are an expert social media copywriter. You write engaging, platform-native captions that drive engagement. '
			. 'You always respond with a single valid JSON object and nothing else — no markdown, no code fences, no commentary.';

		$user  = "Write a social media caption for each of the following platforms, tailored to each platform's style and character limit:\n";
		$user .= $platform_block . "\n\n";
		$user .= 'Post title: ' . $post['title'] . "\n";
		if ( ! empty( $post['excerpt'] ) ) {
			$user .= 'Post content: ' . $post['excerpt'] . "\n";
		}
		if ( ! empty( $post['permalink'] ) ) {
			$user .= 'Post URL: ' . $post['permalink'] . "\n";
		}
		$user .= "\nRequirements:\n";
		if ( 'post_specific' === $opts['tone'] ) {
			// Match the post's own voice instead of applying a fixed preset.
			$user .= "- Tone/style: analyze the tone, voice, and writing style of the post title and content above, then write each caption to match that same tone and style.\n";
		} else {
			$user .= "- Tone/style: {$opts['tone']}.\n";
		}
		$user .= "- Length: {$length_text}.\n";
		$user .= '- Hashtags: ' . ( $opts['generate_hashtags'] ? 'include a few relevant hashtags.' : 'do not include hashtags.' ) . "\n";
		$user .= '- Emojis: ' . ( $opts['include_emojis'] ? 'use tasteful, relevant emojis.' : 'do not use emojis.' ) . "\n";

		if ( ! empty( $opts['prompt'] ) ) {
			$user .= "- Extra instructions from the user: {$opts['prompt']}\n";
		} elseif ( ! empty( $opts['auto_generate'] ) ) {
			$user .= "- Base the captions on the post title and content above.\n";
		}

		$user .= "\nReturn a JSON object whose keys are exactly {$keys_list} and whose values are the caption strings. Respect each platform's character limit.";

		return array(
			array(
				'role'    => 'system',
				'content' => $system,
			),
			array(
				'role'    => 'user',
				'content' => $user,
			),
		);
	}

	/**
	 * Call the OpenAI API and return the model's reply text.
	 *
	 * The messages are the ones the editor shows. An HTTP failure carries
	 * `http_status` in its data so a caller can map it to its own text.
	 *
	 * @param string $api_key  OpenAI API key.
	 * @param string $model    Model id.
	 * @param array  $messages Chat messages.
	 * @return string|\WP_Error
	 */
	protected function request_openai( $api_key, $model, $messages ) {
		$body = array(
			'model'           => $model,
			'messages'        => $messages,
			'temperature'     => 0.7,
			'response_format' => array( 'type' => 'json_object' ),
		);

		$response = wp_remote_post(
			self::OPENAI_ENDPOINT,
			array(
				'timeout' => 60,
				'headers' => array(
					'Content-Type'  => 'application/json',
					'Authorization' => 'Bearer ' . $api_key,
				),
				'body'    => wp_json_encode( $body ),
			)
		);

		if ( is_wp_error( $response ) ) {
			return new \WP_Error( 'wpsp_openai_request_failed', $response->get_error_message() );
		}

		$code = wp_remote_retrieve_response_code( $response );
		$raw  = wp_remote_retrieve_body( $response );
		$data = json_decode( $raw, true );

		if ( 200 !== $code ) {
			$message = isset( $data['error']['message'] )
				? $data['error']['message']
				: __( 'OpenAI request failed. Please verify your API key and try again.', 'wp-scheduled-posts' );
			return new \WP_Error( 'wpsp_openai_error', $message, array( 'http_status' => (int) $code ) );
		}

		$content = isset( $data['choices'][0]['message']['content'] )
			? $data['choices'][0]['message']['content']
			: '';

		if ( empty( $content ) ) {
			return new \WP_Error( 'wpsp_openai_empty', __( 'OpenAI returned an empty response.', 'wp-scheduled-posts' ) );
		}

		return $content;
	}

	/**
	 * Parse captions out of the model's reply.
	 *
	 * @param string $content   Model reply.
	 * @param array  $platforms Requested platform slugs.
	 * @param array  $limits    Slug => character limit to trim to.
	 * @return array|\WP_Error Map of platform => caption.
	 */
	protected function parse_captions( $content, $platforms, $limits ) {
		$parsed = json_decode( $content, true );

		// Fallback: if the model didn't return clean JSON, apply the text to every platform.
		if ( ! is_array( $parsed ) ) {
			$text   = trim( $content );
			$parsed = array();
			foreach ( $platforms as $key ) {
				$parsed[ $key ] = $text;
			}
		}

		// Keep only requested platforms and coerce to trimmed strings.
		// Hard-trim to each platform's character limit so the generated caption
		// always passes template validation when the user saves it.
		$captions = array();
		foreach ( $platforms as $key ) {
			if ( isset( $parsed[ $key ] ) ) {
				$caption          = is_string( $parsed[ $key ] ) ? trim( $parsed[ $key ] ) : trim( wp_json_encode( $parsed[ $key ] ) );
				$limit            = isset( $limits[ $key ] ) ? $limits[ $key ] : 0;
				$captions[ $key ] = $this->enforce_limit( $caption, $limit );
			}
		}

		if ( empty( $captions ) ) {
			return new \WP_Error( 'wpsp_openai_unparsable', __( 'Could not parse captions from the AI response.', 'wp-scheduled-posts' ) );
		}

		return $captions;
	}

	/**
	 * Trim a caption to a platform character limit without cutting mid-word.
	 *
	 * Counts characters (not bytes) so emojis are handled correctly, and backs
	 * up to the last whitespace when possible to avoid a chopped word.
	 *
	 * @param string $text  Caption text.
	 * @param int    $limit Character limit; 0 or less means "no limit".
	 * @return string
	 */
	protected function enforce_limit( $text, $limit ) {
		$limit  = (int) $limit;
		$length = function_exists( 'mb_strlen' ) ? mb_strlen( $text ) : strlen( $text );

		if ( $limit <= 0 || $length <= $limit ) {
			return $text;
		}

		$cut   = function_exists( 'mb_substr' ) ? mb_substr( $text, 0, $limit ) : substr( $text, 0, $limit );
		$space = function_exists( 'mb_strrpos' ) ? mb_strrpos( $cut, ' ' ) : strrpos( $cut, ' ' );

		// Only honour the word boundary if it does not discard too much text.
		if ( false !== $space && $space > (int) ( $limit * 0.6 ) ) {
			$cut = function_exists( 'mb_substr' ) ? mb_substr( $cut, 0, $space ) : substr( $cut, 0, $space );
		}

		return rtrim( $cut );
	}
}
