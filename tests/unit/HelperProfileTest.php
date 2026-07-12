<?php
/**
 * Unit tests for WPSP\Helper::is_profile_exits().
 *
 * Pure logic — no WordPress required, so this runs in the `unit` suite.
 *
 * @package WPScheduledPosts
 */

namespace WPSP\Tests\Unit;

use PHPUnit\Framework\TestCase;
use WPSP\Helper;

class HelperProfileTest extends TestCase {

	/** @return array<string,array{id:string}> */
	private function profiles(): array {
		return array(
			array( 'id' => 'fb-1', 'name' => 'Page One' ),
			array( 'id' => 'tw-2', 'name' => 'Handle Two' ),
			array( 'name' => 'No ID Here' ), // profile without an id key
		);
	}

	public function test_returns_matching_profile_when_id_exists() {
		$match = Helper::is_profile_exits( 'tw-2', $this->profiles() );

		$this->assertIsArray( $match );
		$this->assertSame( 'tw-2', $match['id'] );
		$this->assertSame( 'Handle Two', $match['name'] );
	}

	public function test_returns_false_when_id_absent() {
		$this->assertFalse( Helper::is_profile_exits( 'missing', $this->profiles() ) );
	}

	public function test_uses_strict_comparison() {
		// '0' !== 0, and the profiles have no such id anyway.
		$this->assertFalse( Helper::is_profile_exits( 0, $this->profiles() ) );
	}

	public function test_skips_items_without_id_key() {
		// The third profile has no 'id'; matching an empty string must not hit it.
		$this->assertFalse( Helper::is_profile_exits( '', $this->profiles() ) );
	}

	public function test_empty_profiles_returns_false() {
		$this->assertFalse( Helper::is_profile_exits( 'fb-1', array() ) );
	}
}
