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

require_once __DIR__ . '/ZeroTestsListener.php';
require_once __DIR__ . '/stubs/wp-functions.php';
