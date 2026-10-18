<?php
/**
 * Unit tests for Settings::with_missing_defaults() (card 83714): on upgrade a
 * settings key added in a later version (e.g. show_publish_post_button) is
 * backfilled with its schema default, while every stored value is left as it was.
 *
 * @package WPScheduledPosts
 */

namespace WPSP\Tests\Unit;

use PHPUnit\Framework\TestCase;
use WPSP\Admin\Settings;

class BackfillMissingDefaultsTest extends TestCase {

	public function test_a_missing_key_is_added_with_its_default() {
		$merged = Settings::with_missing_defaults(
			array( 'is_show_dashboard_widget' => 1 ),
			array( 'is_show_dashboard_widget' => 1, 'show_publish_post_button' => 1 )
		);

		$this->assertSame( 1, $merged['show_publish_post_button'] );
	}

	public function test_a_stored_value_is_never_overwritten_by_its_default() {
		$merged = Settings::with_missing_defaults(
			array( 'show_publish_post_button' => 0 ),
			array( 'show_publish_post_button' => 1 )
		);

		$this->assertSame( 0, $merged['show_publish_post_button'] );
	}

	public function test_stored_keys_outside_the_schema_are_kept() {
		$merged = Settings::with_missing_defaults(
			array( 'facebook_profile_list' => array( 'fb1' ), 'is_show_dashboard_widget' => 1 ),
			array( 'is_show_dashboard_widget' => 1, 'show_publish_post_button' => 1 )
		);

		$this->assertSame( array( 'fb1' ), $merged['facebook_profile_list'] );
		$this->assertSame( 1, $merged['show_publish_post_button'] );
	}

	public function test_nothing_missing_returns_the_stored_array_unchanged() {
		$stored = array( 'a' => 1, 'b' => 2 );

		$this->assertSame( $stored, Settings::with_missing_defaults( $stored, array( 'a' => 9, 'b' => 9 ) ) );
	}

	public function test_empty_stored_takes_all_defaults() {
		$this->assertSame(
			array( 'show_publish_post_button' => 1 ),
			Settings::with_missing_defaults( array(), array( 'show_publish_post_button' => 1 ) )
		);
	}
}
