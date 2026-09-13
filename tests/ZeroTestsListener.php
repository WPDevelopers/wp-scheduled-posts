<?php
/**
 * PHPUnit 8 listener that makes an empty suite fail.
 *
 * @package WPScheduledPosts
 */

namespace WPSP\Tests;

use PHPUnit\Framework\TestListener;
use PHPUnit\Framework\TestListenerDefaultImplementation;
use PHPUnit\Framework\Test;

class ZeroTestsListener implements TestListener {

	use TestListenerDefaultImplementation;

	/** @var int */
	public static $tests_started = 0;

	/**
	 * Count tests that PHPUnit actually starts after applying CLI filters.
	 *
	 * @param Test $test Test about to run.
	 * @return void
	 */
	public function startTest( Test $test ): void {
		self::$tests_started++;
	}
}
