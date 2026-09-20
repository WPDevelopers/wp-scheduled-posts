<?php
/**
 * Bootstrap for the `unit` suite.
 *
 * Pure PHP: Composer autoload plus a small set of WordPress function stubs.
 * No database, no WordPress install.
 *
 * This deliberately does NOT load wp-scheduled-posts.php or includes/functions.php.
 * Both exit when ABSPATH is undefined, and pulling either one in here would end
 * the process before PHPUnit reported anything.
 *
 * @package WPScheduledPosts
 */

if ( ! defined( 'WPSP_TESTING' ) ) {
	define( 'WPSP_TESTING', true );
}

$_plugin_dir = dirname( __DIR__ );

if ( ! file_exists( $_plugin_dir . '/vendor/autoload.php' ) ) {
	fwrite( STDERR, "[SchedulePress tests] vendor/autoload.php is missing. Run composer install." . PHP_EOL );
	exit( 1 );
}

require_once $_plugin_dir . '/vendor/autoload.php';

// WordPress time constants. The reconnect lead times are class constants built
// out of these, so they are needed before anything under test is loaded.
foreach (
	array(
		'MINUTE_IN_SECONDS' => 60,
		'HOUR_IN_SECONDS'   => 3600,
		'DAY_IN_SECONDS'    => 86400,
		'WEEK_IN_SECONDS'   => 604800,
	) as $_wpsp_constant => $_wpsp_value
) {
	if ( ! defined( $_wpsp_constant ) ) {
		define( $_wpsp_constant, $_wpsp_value );
	}
}
unset( $_wpsp_constant, $_wpsp_value );

require_once __DIR__ . '/ZeroTestsListener.php';
require_once __DIR__ . '/stubs/HookStore.php';
require_once __DIR__ . '/stubs/wp-functions.php';
