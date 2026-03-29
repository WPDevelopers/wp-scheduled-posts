<?php
/**
 * PHPUnit bootstrap for SchedulePress.
 */

// Define test constant (guard against duplicate definition from phpunit.xml)
if ( ! defined( 'WPSP_TESTING' ) ) {
    define( 'WPSP_TESTING', true );
}

// Path to the WordPress test suite (installed by Docker)
$_tests_dir = getenv( 'WP_TESTS_DIR' );

if ( ! $_tests_dir ) {
    $_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
}

if ( ! file_exists( $_tests_dir . '/includes/functions.php' ) ) {
    echo "Could not find $_tests_dir/includes/functions.php" . PHP_EOL;
    exit( 1 );
}

// Define PHPUnit Polyfills path (required by WP test suite >= 5.9)
if ( ! defined( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH' ) ) {
    $polyfills_path = getenv( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH' );
    if ( ! $polyfills_path ) {
        $polyfills_path = '/root/.composer/vendor/yoast/phpunit-polyfills';
    }
    define( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH', $polyfills_path );
}

// Load WordPress test bootstrap
require_once $_tests_dir . '/includes/functions.php';

/**
 * Manually load the plugin during WordPress bootstrap.
 */
function _manually_load_plugin() {
    require dirname( __DIR__ ) . '/wp-scheduled-posts.php';
}
tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

// Load WordPress test suite
require $_tests_dir . '/includes/bootstrap.php';
