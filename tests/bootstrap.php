<?php
/**
 * PHPUnit bootstrap for SchedulePress (wp-scheduled-posts).
 *
 * Supports two suites (see phpunit.xml):
 *   - unit        : pure PHP, only Composer autoload is required (no database).
 *   - integration : boots the WordPress test environment and loads the plugin.
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

$_tests_dir = getenv( 'WP_TESTS_DIR' );
if ( ! $_tests_dir ) {
	$_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
}

// If the WP test library is not present, keep going: the `unit` suite can still
// run. Only the `integration` suite needs WordPress.
if ( ! file_exists( $_tests_dir . '/includes/functions.php' ) ) {
	// Composer's `files` autoload eagerly loads includes/functions.php, which
	// registers hooks (add_action/add_filter) at file scope. Stub those two
	// functions as no-ops so the autoloader can load without WordPress.
	if ( ! function_exists( 'add_action' ) ) {
		function add_action( ...$args ) {}
	}
	if ( ! function_exists( 'add_filter' ) ) {
		function add_filter( ...$args ) {}
	}

	// Composer autoload — gives WPSP\ classes to the unit suite without WordPress.
	if ( file_exists( $_plugin_dir . '/vendor/autoload.php' ) ) {
		require_once $_plugin_dir . '/vendor/autoload.php';
	}

	fwrite(
		STDERR,
		PHP_EOL . "[SchedulePress tests] WordPress test library not found at {$_tests_dir}." . PHP_EOL
		. "Integration tests are skipped. Run bin/install-wp-tests.sh to enable them." . PHP_EOL . PHP_EOL
	);
	return;
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
