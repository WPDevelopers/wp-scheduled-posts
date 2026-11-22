<?php
/**
 * Shared social-platform lookups for the social abilities.
 *
 * @package WPSP\Abilities\Social
 */

namespace WPSP\Abilities\Social;

use WPSP\Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Maps platform slugs to the settings keys and post-meta keys the rest of the
 * plugin already uses, and reads profiles back in a shape that is safe to hand
 * to an AI client.
 */
trait PlatformMap {

	/**
	 * Platform slug => settings key holding that platform's profile list.
	 *
	 * @return array
	 */
	protected function platform_option_keys() {
		return array(
			'facebook'        => 'facebook_profile_list',
			'twitter'         => 'twitter_profile_list',
			'linkedin'        => 'linkedin_profile_list',
			'pinterest'       => 'pinterest_profile_list',
			'instagram'       => 'instagram_profile_list',
			'medium'          => 'medium_profile_list',
			'threads'         => 'threads_profile_list',
			'google_business' => 'google_business_profile_list',
			'bluesky'         => 'bluesky_profile_list',
			'mastodon'        => 'mastodon_profile_list',
		);
	}

	/**
	 * Every platform slug this plugin knows about.
	 *
	 * @return string[]
	 */
	protected function platform_slugs() {
		return array_keys( $this->platform_option_keys() );
	}

	/**
	 * The post-meta key holding a platform's per-post share log.
	 *
	 * @param string $platform Platform slug.
	 * @return string
	 */
	protected function share_log_meta_key( $platform ) {
		return '__wpscppro_' . $platform . '_share_log';
	}

	/**
	 * Read one platform's configured profiles, with every credential stripped.
	 *
	 * Access tokens, refresh tokens and app secrets must never leave the site
	 * through a tool response: an MCP transcript is stored and replayed by the
	 * client, so a leaked token is a leaked account. Only non-secret identity
	 * and status fields are copied out — this is an allow-list, not a
	 * redact-list, so a new credential field added upstream cannot leak by
	 * default.
	 *
	 * @param string $platform Platform slug.
	 * @return array
	 */
	protected function read_profiles( $platform ) {
		$keys = $this->platform_option_keys();
		if ( ! isset( $keys[ $platform ] ) ) {
			return array();
		}

		$stored = Helper::get_settings( $keys[ $platform ] );
		if ( empty( $stored ) ) {
			return array();
		}

		$profiles = array();
		foreach ( (array) $stored as $index => $profile ) {
			$profile = is_object( $profile ) ? (array) $profile : (array) $profile;

			$expires_in = isset( $profile['expires_in'] ) ? (int) $profile['expires_in'] : 0;

			$profiles[] = array(
				'platform'     => $platform,
				'platform_key' => (string) $index,
				'name'         => isset( $profile['name'] ) ? (string) $profile['name'] : '',
				'id'           => isset( $profile['id'] ) ? (string) $profile['id'] : '',
				'type'         => isset( $profile['type'] ) ? (string) $profile['type'] : '',
				'active'       => ! empty( $profile['status'] ),
				'has_token'    => ! empty( $profile['access_token'] ),
				'expires_at'   => $expires_in > 0 ? gmdate( 'c', $expires_in ) : '',
				'expired'      => $expires_in > 0 && $expires_in < time(),
			);
		}

		return $profiles;
	}

	/**
	 * Locate a single profile by platform + key.
	 *
	 * @param string $platform     Platform slug.
	 * @param string $platform_key Index within the platform's profile list.
	 * @return array|null
	 */
	protected function find_profile( $platform, $platform_key ) {
		foreach ( $this->read_profiles( $platform ) as $profile ) {
			if ( (string) $profile['platform_key'] === (string) $platform_key ) {
				return $profile;
			}
		}
		return null;
	}

	/**
	 * Validate a platform slug, returning a WP_Error naming the valid ones.
	 *
	 * @param string $platform Candidate slug.
	 * @return true|\WP_Error
	 */
	protected function validate_platform( $platform ) {
		if ( in_array( (string) $platform, $this->platform_slugs(), true ) ) {
			return true;
		}
		return new \WP_Error(
			'wpsp_unknown_platform',
			sprintf(
				/* translators: 1: the unknown platform slug, 2: the supported slugs. */
				__( 'Unknown platform "%1$s". Supported platforms: %2$s.', 'wp-scheduled-posts' ),
				$platform,
				implode( ', ', $this->platform_slugs() )
			),
			array( 'status' => 400 )
		);
	}
}
