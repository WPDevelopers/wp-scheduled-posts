<?php
/**
 * PHPUnit bootstrap for SchedulePress (wp-scheduled-posts).
 *
 * Boots the WordPress test environment and loads the plugin. Used by
 * phpunit-integration.xml.
 *
 * The `unit` suite has its own bootstrap (tests/bootstrap-unit.php) and never
 * loads WordPress.
 *
 * The WordPress test library is located via the WP_TESTS_DIR env var, falling
 * back to the system temp dir. Install it with:
 *   bin/install-wp-tests.sh wordpress_test root '' localhost latest
 *
 * @package WPScheduledPosts
 */

if ( ! defined( 'WPSP_TESTING' ) ) {
	define( 'WPSP_TESTING', true );
}

$_plugin_dir = dirname( __DIR__ );

// Composer autoload — gives WPSP\ classes to the unit suite without WordPress.
if ( file_exists( $_plugin_dir . '/vendor/autoload.php' ) ) {
	require_once $_plugin_dir . '/vendor/autoload.php';
}

$_tests_dir = getenv( 'WP_TESTS_DIR' );
if ( ! $_tests_dir ) {
	$_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
}

// A missing test library is a hard failure. Returning quietly here used to let
// the run finish green having executed nothing, which is worse than no tests at
// all: it looks like passing evidence.
if ( ! file_exists( $_tests_dir . '/includes/functions.php' ) ) {
	fwrite(
		STDERR,
		PHP_EOL . "[SchedulePress tests] WordPress test library not found at {$_tests_dir}." . PHP_EOL
		. "Run bin/install-wp-tests.sh to install it, or run the unit suite with" . PHP_EOL
		. "  phpunit --configuration phpunit.xml" . PHP_EOL . PHP_EOL
	);
	exit( 1 );
}

require_once $_tests_dir . '/includes/functions.php';

/**
 * Manually load the plugin under test into the WordPress test environment.
 */
function _wpsp_manually_load_plugin() {
	require dirname( __DIR__ ) . '/wp-scheduled-posts.php';
}
tests_add_filter( 'muplugins_loaded', '_wpsp_manually_load_plugin' );

require $_tests_dir . '/includes/bootstrap.php';
