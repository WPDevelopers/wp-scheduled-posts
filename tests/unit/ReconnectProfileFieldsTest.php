<?php
/**
 * Unit tests for ReconnectHandler::update_profile_fields().
 *
 * Two regressions this covers, both introduced on release/84488-20260913:
 *
 * 1. The method stopped at the first profile carrying the target id. Every
 *    Pinterest board of one account is stored as a separate entry under the
 *    same id (the account username), so renewing the account's token reached
 *    one board and left the rest holding the token that had just expired.
 * 2. Callers reported "Connection renewed" without looking at what this
 *    returned, so a write that never landed still read as a success.
 *
 * @package WPScheduledPosts
 */

namespace WPSP\Tests\Unit;

use PHPUnit\Framework\TestCase;
use WPSP\Social\ReconnectHandler;
use WPSP\Tests\Stubs\OptionStore;

class ReconnectProfileFieldsTest extends TestCase {

	const OPTION_NAME = 'wpsp_settings_v5';

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

	private function seed( $key, array $profiles ) {
		OptionStore::seed( self::OPTION_NAME, json_encode( array( $key => $profiles ) ) );
	}

	private function stored( $key ) {
		$settings = json_decode( OptionStore::get( self::OPTION_NAME ), true );
		return $settings[ $key ];
	}

	private function update( $platform, array $item, array $updates ) {
		$method = new \ReflectionMethod( ReconnectHandler::class, 'update_profile_fields' );
		if ( PHP_VERSION_ID < 80100 ) {
			$method->setAccessible( true );
		}
		return $method->invoke( null, $platform, $item, $updates );
	}

	// ── Pinterest: one account, several boards, one shared id ───────────────

	public function test_every_board_of_the_account_gets_the_renewed_token() {
		$this->seed( 'pinterest_profile_list', array(
			array( 'id' => 'acme', 'default_board_name' => 'Recipes', 'access_token' => 'old' ),
			array( 'id' => 'acme', 'default_board_name' => 'Travel', 'access_token' => 'old' ),
		) );

		$this->assertTrue( $this->update( 'pinterest', array( 'id' => 'acme' ), array( 'access_token' => 'fresh' ) ) );

		$boards = $this->stored( 'pinterest_profile_list' );
		$this->assertSame( 'fresh', $boards[0]['access_token'] );
		$this->assertSame( 'fresh', $boards[1]['access_token'] );
	}

	public function test_a_second_account_is_left_alone() {
		$this->seed( 'pinterest_profile_list', array(
			array( 'id' => 'acme', 'access_token' => 'old' ),
			array( 'id' => 'other', 'access_token' => 'other-token' ),
		) );

		$this->update( 'pinterest', array( 'id' => 'acme' ), array( 'access_token' => 'fresh' ) );

		$boards = $this->stored( 'pinterest_profile_list' );
		$this->assertSame( 'fresh', $boards[0]['access_token'] );
		$this->assertSame( 'other-token', $boards[1]['access_token'] );
	}

	public function test_the_boards_own_fields_survive() {
		$this->seed( 'pinterest_profile_list', array(
			array( 'id' => 'acme', 'default_board_name' => 'Recipes', 'access_token' => 'old' ),
		) );

		$this->update( 'pinterest', array( 'id' => 'acme' ), array( 'access_token' => 'fresh' ) );

		$boards = $this->stored( 'pinterest_profile_list' );
		$this->assertSame( 'Recipes', $boards[0]['default_board_name'] );
	}

	// ── __id identifies exactly one entry ───────────────────────────────────

	public function test_an_id_match_only_touches_that_entry() {
		$this->seed( 'linkedin_profile_list', array(
			array( '__id' => '1001', 'access_token' => 'old-a' ),
			array( '__id' => '1002', 'access_token' => 'old-b' ),
		) );

		$this->update( 'linkedin', array( '__id' => '1002' ), array( 'access_token' => 'fresh' ) );

		$profiles = $this->stored( 'linkedin_profile_list' );
		$this->assertSame( 'old-a', $profiles[0]['access_token'] );
		$this->assertSame( 'fresh', $profiles[1]['access_token'] );
	}

	public function test_id_is_used_when_the_entries_carry_no_id() {
		// A LinkedIn profile added before __id existed still has to be reachable.
		$this->seed( 'linkedin_profile_list', array(
			array( 'id' => 'member-9', 'access_token' => 'old' ),
		) );

		$this->assertTrue(
			$this->update( 'linkedin', array( '__id' => '1002', 'id' => 'member-9' ), array( 'access_token' => 'fresh' ) )
		);
		$profiles = $this->stored( 'linkedin_profile_list' );
		$this->assertSame( 'fresh', $profiles[0]['access_token'] );
	}

	// ── What the callers now act on ─────────────────────────────────────────

	public function test_an_unknown_profile_reports_failure() {
		$this->seed( 'pinterest_profile_list', array( array( 'id' => 'acme' ) ) );

		$this->assertFalse( $this->update( 'pinterest', array( 'id' => 'nobody' ), array( 'access_token' => 'fresh' ) ) );
	}

	public function test_a_blocked_write_reports_failure() {
		$this->seed( 'pinterest_profile_list', array( array( 'id' => 'acme', 'access_token' => 'old' ) ) );
		OptionStore::$failWrites = true;

		$this->assertFalse( $this->update( 'pinterest', array( 'id' => 'acme' ), array( 'access_token' => 'fresh' ) ) );
	}

	public function test_an_unchanged_token_is_a_success_not_a_failed_save() {
		// update_option() reports false when nothing changed. The profile already
		// holds what was about to be written, so this is not a failure.
		$this->seed( 'pinterest_profile_list', array( array( 'id' => 'acme', 'access_token' => 'same' ) ) );

		$this->assertTrue( $this->update( 'pinterest', array( 'id' => 'acme' ), array( 'access_token' => 'same' ) ) );
	}

	public function test_an_unsupported_platform_reports_failure() {
		$this->assertFalse( $this->update( 'myspace', array( 'id' => 'acme' ), array( 'access_token' => 'fresh' ) ) );
	}

	public function test_missing_settings_report_failure() {
		$this->assertFalse( $this->update( 'pinterest', array( 'id' => 'acme' ), array( 'access_token' => 'fresh' ) ) );
	}
}
