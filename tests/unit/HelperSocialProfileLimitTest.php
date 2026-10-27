<?php
/**
 * Unit tests for Helper::social_profile_limit() and the limit in
 * Helper::get_social_profile().
 *
 * Free shares to one profile per network. An add-on raises that through
 * filters, never through a check on the Pro class, so these pin both paths.
 *
 * @package WPScheduledPosts
 */

namespace WPSP\Tests\Unit;

use PHPUnit\Framework\TestCase;
use WPSP\Helper;
use WPSP\Tests\Stubs\HookStore;
use WPSP\Tests\Stubs\OptionStore;

class HelperSocialProfileLimitTest extends TestCase {

	const OPTION_NAME = 'wpsp_settings_v5';

	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();
		if ( ! defined( 'WPSP_SETTINGS_NAME' ) ) {
			define( 'WPSP_SETTINGS_NAME', self::OPTION_NAME );
		}
	}

	protected function setUp(): void {
		parent::setUp();
		HookStore::reset();
		OptionStore::reset();
		OptionStore::seed(
			self::OPTION_NAME,
			json_encode(
				array(
					'facebook_profile_list' => array(
						array( 'id' => 'a', 'name' => 'Disabled', 'status' => false ),
						array( 'id' => 'b', 'name' => 'First active', 'status' => true ),
						array( 'id' => 'c', 'name' => 'Second active', 'status' => true ),
					),
				)
			)
		);
	}

	protected function tearDown(): void {
		HookStore::reset();
		parent::tearDown();
	}

	public function test_free_allows_one_profile() {
		$this->assertSame( 1, Helper::social_profile_limit() );
	}

	public function test_the_checkpoint_filter_pro_answers_lifts_the_limit() {
		add_filter( 'wpsp_social_profile_limit_checkpoint', function () {
			return true;
		} );

		$this->assertSame( PHP_INT_MAX, Helper::social_profile_limit() );
	}

	public function test_a_checkpoint_answer_other_than_true_keeps_one() {
		add_filter( 'wpsp_social_profile_limit_checkpoint', function ( $value ) {
			return $value;
		} );

		$this->assertSame( 1, Helper::social_profile_limit() );
	}

	public function test_the_numeric_filter_sets_the_limit_but_never_below_one() {
		add_filter( 'wpsp_social_profile_limit', function () {
			return 3;
		} );
		$this->assertSame( 3, Helper::social_profile_limit() );

		HookStore::reset();
		add_filter( 'wpsp_social_profile_limit', function () {
			return 0;
		} );
		$this->assertSame( 1, Helper::social_profile_limit() );
	}

	public function test_with_the_default_limit_only_the_first_enabled_profile_comes_back() {
		$profiles = Helper::get_social_profile( 'facebook_profile_list' );

		$this->assertCount( 1, $profiles );
		$this->assertSame( 'b', $profiles[0]->id );
	}

	public function test_a_numeric_limit_returns_that_many_enabled_profiles() {
		add_filter( 'wpsp_social_profile_limit', function () {
			return 2;
		} );

		$ids = array_map( function ( $profile ) {
			return $profile->id;
		}, Helper::get_social_profile( 'facebook_profile_list' ) );

		$this->assertSame( array( 'b', 'c' ), $ids );
	}

	public function test_without_a_limit_every_profile_comes_back() {
		add_filter( 'wpsp_social_profile_limit_checkpoint', function () {
			return true;
		} );

		$this->assertCount( 3, Helper::get_social_profile( 'facebook_profile_list' ) );
	}
}
