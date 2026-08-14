<?php
/**
 * List social profiles ability.
 *
 * @package WPSP\Abilities\Social
 */

namespace WPSP\Abilities\Social;

use WPSP\Abilities\AbilityBase;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Reports every connected social profile and its health — active, token
 * present, token expiry — without ever emitting a credential.
 */
class ListSocialProfiles extends AbilityBase {

	use PlatformMap;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id          = 'schedulepress/list-social-profiles';
		$this->label       = __( 'List Social Profiles', 'wp-scheduled-posts' );
		$this->description = __( 'List the connected social accounts and their health: which are active, which have an access token, and which tokens have expired or are about to. Answers "which social connections need reconnecting". Never returns tokens or secrets.', 'wp-scheduled-posts' );
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
			'properties'           => array(
				'platform' => array(
					'type'        => 'string',
					'description' => 'Restrict to a single platform slug (e.g. facebook, twitter, linkedin). Omit for all.',
				),
				'only_problems' => array(
					'type'        => 'boolean',
					'default'     => false,
					'description' => 'Return only profiles that need attention (inactive, missing token, or expired).',
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
				'total'          => array( 'type' => 'integer' ),
				'needs_attention' => array( 'type' => 'integer' ),
				'profiles'       => array(
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

		$platforms = $this->platform_slugs();
		if ( ! empty( $input['platform'] ) ) {
			$valid = $this->validate_platform( $input['platform'] );
			if ( is_wp_error( $valid ) ) {
				return $valid;
			}
			$platforms = array( (string) $input['platform'] );
		}

		$only_problems = ! empty( $input['only_problems'] );

		$profiles = array();
		$problems = 0;
		foreach ( $platforms as $platform ) {
			foreach ( $this->read_profiles( $platform ) as $profile ) {
				$needs = ( ! $profile['active'] || ! $profile['has_token'] || $profile['expired'] );
				if ( $needs ) {
					$problems++;
				}
				$profile['needs_attention'] = $needs;
				if ( $only_problems && ! $needs ) {
					continue;
				}
				$profiles[] = $profile;
			}
		}

		return array(
			'total'           => count( $profiles ),
			'needs_attention' => $problems,
			'profiles'        => $profiles,
		);
	}
}
