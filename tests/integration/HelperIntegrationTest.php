<?php
/**
 * Integration tests for WPSP\Helper methods that depend on WordPress
 * (options access and wp_strip_all_tags()).
 *
 * @package WPScheduledPosts
 */

namespace WPSP\Tests\Integration;

use WP_UnitTestCase;
use WPSP\Helper;

class HelperIntegrationTest extends WP_UnitTestCase {

	public function test_social_platform_limits_fall_back_to_defaults() {
		// No custom social_templates settings exist on a clean install, so the
		// documented per-platform character defaults are returned.
		$limits = Helper::get_social_platform_limits();

		$this->assertIsArray( $limits );
		foreach ( array( 'facebook', 'twitter', 'linkedin', 'pinterest', 'instagram', 'medium', 'threads', 'google_business' ) as $platform ) {
			$this->assertArrayHasKey( $platform, $limits, "Missing limit for {$platform}." );
			$this->assertIsInt( $limits[ $platform ] );
			$this->assertGreaterThan( 0, $limits[ $platform ] );
		}

		$this->assertSame( 280, $limits['twitter'] );
		$this->assertSame( 480, $limits['threads'] );
		$this->assertSame( 63206, $limits['facebook'] );
	}

	public function test_strip_all_html_collapses_blank_lines() {
		$input    = "<p>Hello</p>\n\n\n<b>World</b>";
		$expected = "Hello\nWorld";

		$this->assertSame( $expected, Helper::strip_all_html_and_keep_single_breaks( $input ) );
	}

	public function test_strip_all_html_trims_surrounding_whitespace() {
		$this->assertSame( 'Just text', Helper::strip_all_html_and_keep_single_breaks( "   <span>Just text</span>   " ) );
	}

	public function test_strip_all_html_keeps_a_single_newline() {
		// A single line break between two lines is preserved.
		$this->assertSame( "Line 1\nLine 2", Helper::strip_all_html_and_keep_single_breaks( "Line 1\nLine 2" ) );
	}
}
