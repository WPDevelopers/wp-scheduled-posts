<?php
/**
 * Unit tests for WPSP\Helper::get_selected_social_profiles().
 *
 * Covers issue #108 and the two follow-up findings against its fix:
 *   - a doubly serialized row must be recovered, not returned as a string;
 *   - recovery must not instantiate classes (no __wakeup()/__destruct());
 *   - every returned record must be an array, because the consumers read them
 *     with array syntax and an object is a fatal there.
 *
 * @package WPScheduledPosts
 */

namespace WPSP\Tests\Unit;

use PHPUnit\Framework\TestCase;
use WPSP\Helper;
use WPSP\Tests\Stubs\MetaStore;

/**
 * Records whether its magic methods ran, so a test can assert they did not.
 */
class WakeupSpy {

	/** @var bool */
	public static $woke = false;

	public function __wakeup() {
		self::$woke = true;
	}
}

class SelectedSocialProfilesTest extends TestCase {

	const META_KEY = '_selected_social_profile';

	protected function setUp(): void {
		parent::setUp();
		MetaStore::reset();
		WakeupSpy::$woke = false;
	}

	/** Seed the meta row exactly as the database would hold it. */
	private function seed( $value ) {
		MetaStore::set( 1, self::META_KEY, $value );
	}

	// ── Healthy data ────────────────────────────────────────────────────────

	public function test_returns_healthy_array_records_unchanged() {
		$profiles = array(
			array( 'id' => 'fb-1', 'name' => 'Page One' ),
			array( 'id' => 'tw-2', 'name' => 'Handle Two' ),
		);
		$this->seed( $profiles );

		$this->assertSame( $profiles, Helper::get_selected_social_profiles( 1 ) );
	}

	public function test_returns_empty_array_for_empty_meta() {
		$this->seed( '' );
		$this->assertSame( array(), Helper::get_selected_social_profiles( 1 ) );
	}

	public function test_returns_empty_array_for_unparseable_string() {
		$this->seed( 'not serialized at all' );
		$this->assertSame( array(), Helper::get_selected_social_profiles( 1 ) );
	}

	// ── Recovery (#108) ─────────────────────────────────────────────────────

	public function test_recovers_doubly_serialized_array_of_arrays() {
		$this->seed( serialize( array( array( 'id' => 'fb-42', 'name' => 'Recovered' ) ) ) );

		$result = Helper::get_selected_social_profiles( 1 );

		$this->assertCount( 1, $result );
		$this->assertSame( 'fb-42', $result[0]['id'] );
		$this->assertSame( 'Recovered', $result[0]['name'] );
	}

	public function test_recovers_the_empty_serialized_array_from_the_issue() {
		// 'a:0:{}' is the exact value reported in #108.
		$this->seed( 'a:0:{}' );
		$this->assertSame( array(), Helper::get_selected_social_profiles( 1 ) );
	}

	// ── Shape canonicalisation ──────────────────────────────────────────────

	public function test_recovered_stdclass_records_become_arrays() {
		// A recovered object handed straight to a consumer is a fatal:
		// is_profile_exits() does isset($item['id']).
		$this->seed( serialize( array( json_decode( '{"id":"ig-7","name":"Account"}' ) ) ) );

		$result = Helper::get_selected_social_profiles( 1 );

		$this->assertIsArray( $result[0] );
		$this->assertSame( 'ig-7', $result[0]['id'] );
	}

	public function test_nested_objects_become_arrays_too() {
		$this->seed( serialize( array( json_decode( '{"id":"p-1","default_board_name":{"value":"board","label":"Board"}}' ) ) ) );

		$result = Helper::get_selected_social_profiles( 1 );

		$this->assertIsArray( $result[0]['default_board_name'] );
		$this->assertSame( 'board', $result[0]['default_board_name']['value'] );
	}

	public function test_canonicalised_records_survive_the_consumer_that_used_to_fatal() {
		$this->seed( serialize( array( json_decode( '{"id":"ig-7","name":"Account"}' ) ) ) );

		$profiles = Helper::get_selected_social_profiles( 1 );
		$match    = Helper::is_profile_exits( 'ig-7', $profiles );

		$this->assertIsArray( $match );
		$this->assertSame( 'ig-7', $match['id'] );
	}

	// ── Deserialization safety ──────────────────────────────────────────────

	public function test_crafted_object_payload_does_not_run_wakeup() {
		$this->seed( serialize( array( new WakeupSpy() ) ) );

		$result = Helper::get_selected_social_profiles( 1 );

		$this->assertFalse( WakeupSpy::$woke, '__wakeup() must not run during recovery.' );
		$this->assertSame( array(), $result );
	}

	public function test_top_level_serialized_object_is_rejected_without_waking() {
		$this->seed( serialize( new WakeupSpy() ) );

		$result = Helper::get_selected_social_profiles( 1 );

		$this->assertFalse( WakeupSpy::$woke );
		$this->assertSame( array(), $result );
	}

	public function test_crafted_entry_is_dropped_but_valid_siblings_survive() {
		$this->seed( serialize( array( new WakeupSpy(), array( 'id' => 'fb-1' ) ) ) );

		$result = Helper::get_selected_social_profiles( 1 );

		$this->assertFalse( WakeupSpy::$woke );
		$this->assertCount( 1, $result );
		$this->assertSame( 'fb-1', reset( $result )['id'] );
	}

	public function test_serialized_scalar_is_not_treated_as_profiles() {
		$this->seed( serialize( 'hello' ) );
		$this->assertSame( array(), Helper::get_selected_social_profiles( 1 ) );
	}

	public function test_keys_are_preserved_so_json_shape_does_not_change() {
		$this->seed( array( 'a' => array( 'id' => '1' ), 'b' => array( 'id' => '2' ) ) );

		$this->assertSame( array( 'a', 'b' ), array_keys( Helper::get_selected_social_profiles( 1 ) ) );
	}
}
