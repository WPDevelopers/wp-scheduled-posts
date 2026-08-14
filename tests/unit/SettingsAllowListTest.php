<?php
/**
 * Unit tests for the settings allow-list.
 *
 * The settings abilities read and write `wpsp_settings_v5` — the same option
 * that stores every social access token, refresh token and the OpenAI API key.
 * These tests pin the property that makes that safe: the allow-list names what
 * may be shared, so a credential key can never become readable or writable by
 * accident.
 *
 * @package WPScheduledPosts
 */

namespace WPSP\Tests\Unit;

use PHPUnit\Framework\TestCase;
use WPSP\Abilities\Settings\SettingsMap;

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __DIR__, 2 ) . '/' );
}

class SettingsAllowListTest extends TestCase {

	use SettingsMap;

	/**
	 * Option keys that carry credentials. None may ever appear in the
	 * allow-list.
	 *
	 * @return array
	 */
	private function credential_keys() {
		return array(
			'openai_api_key',
			'facebook_profile_list',
			'twitter_profile_list',
			'linkedin_profile_list',
			'pinterest_profile_list',
			'instagram_profile_list',
			'medium_profile_list',
			'threads_profile_list',
			'google_business_profile_list',
			'bluesky_profile_list',
			'mastodon_profile_list',
		);
	}

	public function test_no_credential_key_is_writable() {
		$writable = array_keys( $this->settings_map() );

		foreach ( $this->credential_keys() as $key ) {
			$this->assertNotContains(
				$key,
				$writable,
				"{$key} holds a credential and must never be writable over MCP"
			);
		}
	}

	public function test_no_credential_key_is_readable() {
		$readable = array_merge(
			array_keys( $this->settings_map() ),
			array_keys( $this->read_only_settings() )
		);

		foreach ( $this->credential_keys() as $key ) {
			$this->assertNotContains(
				$key,
				$readable,
				"{$key} holds a credential and must never be readable over MCP"
			);
		}
	}

	/**
	 * Nothing declared read-only may also be writable, or the read-only
	 * marking would be decorative.
	 */
	public function test_read_only_settings_are_not_also_writable() {
		$overlap = array_intersect_key( $this->read_only_settings(), $this->settings_map() );
		$this->assertSame( array(), $overlap );
	}

	public function test_every_allow_listed_key_declares_a_known_type() {
		$known = array( 'bool', 'int', 'string', 'string[]' );

		foreach ( $this->settings_map() as $key => $type ) {
			$this->assertContains( $type, $known, "{$key} declares an unknown storage type" );
		}
	}

	/**
	 * Integer coercion, the one cast that needs no WordPress helpers. A model
	 * sending "1" for a toggle must not widen the stored type.
	 *
	 * The string and array casts route through sanitize_text_field(), so they
	 * belong in the integration suite rather than here.
	 */
	public function test_int_casting_normalizes_loose_input() {
		$this->assertSame( 1, $this->cast_setting( '1', 'int' ) );
		$this->assertSame( 0, $this->cast_setting( 'nonsense', 'int' ) );
		$this->assertSame( 0, $this->cast_setting( false, 'int' ) );
	}
}
