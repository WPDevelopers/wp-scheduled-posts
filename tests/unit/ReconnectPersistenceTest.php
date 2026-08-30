<?php
/**
 * Unit tests for ReconnectHandler::update_access_token().
 *
 * Covers the follow-up finding against #115/#117: a reconnect must not report
 * success unless the refreshed credential was actually written. The old method
 * returned a bare boolean, which could not distinguish "the profile is not in
 * settings" or "the write failed" from "the value did not need changing".
 *
 * @package WPScheduledPosts
 */

namespace WPSP\Tests\Unit;

use PHPUnit\Framework\TestCase;
use WPSP\Social\ReconnectHandler;
use WPSP\Tests\Stubs\OptionStore;

class ReconnectPersistenceTest extends TestCase {

	const OPTION_NAME = 'wpsp_settings_v5';
	const LIST_KEY    = 'instagram_profile_list';

	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();
		if ( ! defined( 'WPSP_SETTINGS_NAME' ) ) {
			define( 'WPSP_SETTINGS_NAME', self::OPTION_NAME );
		}
	}

	protected function setUp(): void {
		parent::setUp();
		OptionStore::reset();
	}

	/** Seed settings holding one Instagram profile. */
	private function seedSettings( $profile_id = 'ig-1', $token = 'old-token' ) {
		OptionStore::seed( self::OPTION_NAME, json_encode( array(
			self::LIST_KEY => array(
				array( 'id' => $profile_id, 'long_lived_access_token' => $token ),
			),
		) ) );
	}

	private function save( $profile_id, $token = 'fresh-token' ) {
		return ReconnectHandler::update_access_token( self::LIST_KEY, $profile_id, '', $token, '2026-12-01' );
	}

	// ── Success ─────────────────────────────────────────────────────────────

	public function test_returns_true_and_writes_the_token() {
		$this->seedSettings();

		$this->assertTrue( $this->save( 'ig-1' ) );

		$stored = json_decode( OptionStore::get( self::OPTION_NAME ), true );
		$this->assertSame( 'fresh-token', $stored[ self::LIST_KEY ][0]['long_lived_access_token'] );
		$this->assertSame( '2026-12-01', $stored[ self::LIST_KEY ][0]['expires_at'] );
	}

	public function test_an_unchanged_value_is_still_a_success() {
		// update_option() returns false when nothing changed. Treating that as a
		// failure would report a working reconnect as broken.
		$this->seedSettings( 'ig-1', 'same-token' );
		$this->save( 'ig-1', 'same-token' );

		$this->assertTrue( $this->save( 'ig-1', 'same-token' ) );
	}

	// ── Failure paths that used to report success ───────────────────────────

	public function test_unknown_profile_id_is_an_error() {
		$this->seedSettings( 'ig-1' );

		$result = $this->save( 'ig-does-not-exist' );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertSame( 'reconnect_profile_missing', $result->get_error_code() );
	}

	public function test_unknown_profile_id_does_not_change_stored_settings() {
		$this->seedSettings( 'ig-1', 'old-token' );
		$before = OptionStore::get( self::OPTION_NAME );

		$this->save( 'ig-does-not-exist' );

		$this->assertSame( $before, OptionStore::get( self::OPTION_NAME ) );
	}

	public function test_failed_write_is_an_error() {
		$this->seedSettings();
		OptionStore::$failWrites = true;

		$result = $this->save( 'ig-1' );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertSame( 'reconnect_save_failed', $result->get_error_code() );
	}

	public function test_missing_settings_option_is_an_error() {
		$result = $this->save( 'ig-1' );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertSame( 'reconnect_settings_missing', $result->get_error_code() );
	}

	public function test_malformed_settings_are_an_error() {
		OptionStore::seed( self::OPTION_NAME, json_encode( array( self::LIST_KEY => 'not-a-list' ) ) );

		$result = $this->save( 'ig-1' );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertSame( 'reconnect_settings_malformed', $result->get_error_code() );
	}

	public function test_missing_profile_list_is_an_error() {
		OptionStore::seed( self::OPTION_NAME, json_encode( array( 'facebook_profile_list' => array() ) ) );

		$result = $this->save( 'ig-1' );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertSame( 'reconnect_settings_malformed', $result->get_error_code() );
	}

	public function test_no_token_supplied_is_an_error() {
		$this->seedSettings();

		$result = ReconnectHandler::update_access_token( self::LIST_KEY, 'ig-1', '', '', '' );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertSame( 'reconnect_no_token', $result->get_error_code() );
	}
}
