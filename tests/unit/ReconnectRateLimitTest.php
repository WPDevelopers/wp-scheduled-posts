<?php
/**
 * Unit tests for telling a temporary rate limit apart from a dead grant
 * during token renewal (card 84782).
 *
 * Pure logic — no WordPress required, so this runs in the `unit` suite.
 *
 * @package WPScheduledPosts
 */

namespace WPSP\Tests\Unit;

use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use WPSP\Social\ReconnectHandler;

class ReconnectRateLimitTest extends TestCase {

	/**
	 * @param int   $status
	 * @param mixed $data
	 * @return bool
	 */
	private function is_rate_limited( $status, $data ) {
		$method = new ReflectionMethod( ReconnectHandler::class, 'is_rate_limited' );
		if ( PHP_VERSION_ID < 80100 ) {
			$method->setAccessible( true );
		}

		return $method->invoke( null, $status, $data );
	}

	/** A bare HTTP 429 is a rate limit whatever the body says. */
	public function test_http_429_is_a_rate_limit() {
		$this->assertTrue( $this->is_rate_limited( 429, array() ) );
		$this->assertTrue( $this->is_rate_limited( 429, array( 'error' => 'slow_down' ) ) );
	}

	/** Meta's throttling codes are a rate limit even when the status is 400/200. */
	public function test_meta_rate_limit_codes_are_a_rate_limit() {
		foreach ( array( 4, 17, 32, 613 ) as $code ) {
			$data = array( 'error' => array( 'code' => $code, 'message' => 'throttled' ) );
			$this->assertTrue( $this->is_rate_limited( 400, $data ), "Meta code {$code} should be a rate limit" );
			$this->assertTrue( $this->is_rate_limited( 200, $data ), "Meta code {$code} should be a rate limit at 200" );
		}
	}

	/** A code sent as a numeric string is still matched. */
	public function test_meta_code_as_string_is_a_rate_limit() {
		$data = array( 'error' => array( 'code' => '613' ) );
		$this->assertTrue( $this->is_rate_limited( 400, $data ) );
	}

	/** An expired/invalid grant is NOT a rate limit — it must still ask for re-auth. */
	public function test_expired_grant_is_not_a_rate_limit() {
		$this->assertFalse( $this->is_rate_limited( 400, array( 'error' => array( 'code' => 190, 'message' => 'expired' ) ) ) );
		$this->assertFalse( $this->is_rate_limited( 400, array( 'error' => 'invalid_grant' ) ) );
		$this->assertFalse( $this->is_rate_limited( 401, array( 'error_description' => 'The token has expired' ) ) );
	}

	/** No error body, or a non-array body, is not a rate limit. */
	public function test_non_rate_limit_bodies() {
		$this->assertFalse( $this->is_rate_limited( 400, array() ) );
		$this->assertFalse( $this->is_rate_limited( 400, null ) );
		$this->assertFalse( $this->is_rate_limited( 200, 'not json' ) );
	}
}
