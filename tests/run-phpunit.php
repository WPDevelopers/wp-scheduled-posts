<?php
/**
 * Run the PHP 7.2-compatible PHPUnit line on newer PHP versions.
 *
 * Some pinned production dependencies emit PHP deprecations while Composer's
 * autoloader starts. Hide only deprecation levels for the test runner; PHPUnit
 * still turns warnings, notices, risky tests, and test output into failures.
 *
 * @package WPScheduledPosts
 */

$_error_reporting = error_reporting( E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED );

$_autoload = dirname( __DIR__ ) . '/vendor/autoload.php';

if ( ! file_exists( $_autoload ) ) {
	fwrite( STDERR, "[SchedulePress tests] vendor/autoload.php is missing. Run composer install." . PHP_EOL );
	exit( 1 );
}

define( 'PHPUNIT_COMPOSER_INSTALL', $_autoload );
require $_autoload;
error_reporting( $_error_reporting );

$_status = \PHPUnit\TextUI\Command::main( false );

if ( class_exists( '\\WPSP\\Tests\\ZeroTestsListener', false ) && 0 === \WPSP\Tests\ZeroTestsListener::$tests_started ) {
	fwrite( STDERR, "[SchedulePress tests] No tests were executed." . PHP_EOL );
	exit( 1 );
}

exit( $_status );
