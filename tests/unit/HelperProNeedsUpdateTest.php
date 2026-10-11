<?php
/**
 * Unit tests for Helper::pro_needs_update().
 *
 * This decides whether the "update SchedulePress Pro" notice appears. Getting
 * it wrong in either direction is visible on every admin screen: too eager and
 * it nags people who are already current, too lax and a Pro that can no longer
 * serve its own features looks healthy.
 *
 * @package WPScheduledPosts
 */

namespace WPSP\Tests\Unit;

use PHPUnit\Framework\TestCase;
use WPSP\Helper;

class HelperProNeedsUpdateTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();

		if ( ! defined( 'WPSP_MIN_PRO_VERSION' ) ) {
			define( 'WPSP_MIN_PRO_VERSION', '5.4.0' );
		}
	}

	public function test_no_pro_installed_is_not_an_update_prompt() {
		$this->assertFalse(
			Helper::pro_needs_update( null ),
			'Someone running free alone has nothing to update.'
		);
		$this->assertFalse( Helper::pro_needs_update( '' ) );
	}

	public function test_an_older_pro_needs_updating() {
		$this->assertTrue( Helper::pro_needs_update( '5.3.3' ) );
		$this->assertTrue( Helper::pro_needs_update( '5.0.0' ) );
		$this->assertTrue( Helper::pro_needs_update( '4.3.3' ) );
	}

	public function test_the_minimum_and_anything_newer_is_fine() {
		$this->assertFalse( Helper::pro_needs_update( '5.4.0' ) );
		$this->assertFalse( Helper::pro_needs_update( '5.4.1' ) );
		$this->assertFalse( Helper::pro_needs_update( '6.0.0' ) );
	}

	/**
	 * A beta or RC of the required release already carries the feature, so it
	 * must not be told to update to the version it already is. Plain
	 * version_compare() ranks '5.4.0-beta1' below '5.4.0' and would.
	 */
	public function test_a_prerelease_of_the_minimum_is_fine() {
		$this->assertFalse( Helper::pro_needs_update( '5.4.0-beta1' ) );
		$this->assertFalse( Helper::pro_needs_update( '5.4.0-RC2' ) );
		$this->assertFalse( Helper::pro_needs_update( '5.4.1-beta1' ) );
	}

	public function test_a_prerelease_of_an_older_release_still_needs_updating() {
		$this->assertTrue( Helper::pro_needs_update( '5.3.9-beta1' ) );
		$this->assertTrue( Helper::pro_needs_update( '5.0.0-RC1' ) );
	}
}
