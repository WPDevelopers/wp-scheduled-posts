<?php
/**
 * Integration test: the plugin boots correctly inside WordPress.
 *
 * @package WPScheduledPosts
 */

namespace WPSP\Tests\Integration;

use WP_UnitTestCase;

class PluginBootTest extends WP_UnitTestCase {

	public function test_engine_class_is_loaded() {
		$this->assertTrue( class_exists( '\WPSP' ), 'The WPSP engine class should be loaded.' );
	}

	public function test_init_returns_a_singleton() {
		$a = \WPSP::init();
		$b = \WPSP::init();

		$this->assertInstanceOf( '\WPSP', $a );
		$this->assertSame( $a, $b, 'WPSP::init() must always return the same instance.' );
	}

	public function test_core_constants_are_defined() {
		foreach ( array(
			'WPSP_VERSION',
			'WPSP_PLUGIN_SLUG',
			'WPSP_SETTINGS_SLUG',
			'WPSP_SETTINGS_NAME',
			'WPSP_PLUGIN_FILE',
		) as $const ) {
			$this->assertTrue( defined( $const ), "{$const} should be defined." );
		}
	}

	public function test_identity_constants_have_expected_values() {
		$this->assertSame( 'wp-scheduled-posts', WPSP_PLUGIN_SLUG );
		$this->assertSame( 'schedulepress', WPSP_SETTINGS_SLUG );
		// Settings are stored under the versioned option key.
		$this->assertSame( 'wpsp_settings_v5', WPSP_SETTINGS_NAME );
	}

	public function test_version_constant_matches_plugin_header() {
		$data = get_file_data( WPSP_PLUGIN_FILE, array( 'Version' => 'Version' ) );
		$this->assertSame( $data['Version'], WPSP_VERSION, 'WPSP_VERSION must match the plugin header Version.' );
	}
}
