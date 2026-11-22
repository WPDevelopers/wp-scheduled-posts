<?php
/**
 * Unit tests for Pinterest::error_message() (R3 sheet row 47).
 *
 * Any 404 read "this Pinterest board doesn't exits!!", a network failure showed
 * the raw cURL text, and other errors had no hint. Uses the real SDK exceptions.
 *
 * @package WPScheduledPosts
 */

namespace WPSP\Tests\Unit;

use DirkGroenen\Pinterest\Exceptions\CurlException;
use DirkGroenen\Pinterest\Exceptions\PinterestException;
use PHPUnit\Framework\TestCase;
use WPSP\Social\Pinterest;

class PinterestErrorMessageTest extends TestCase {

	public function test_no_pin_returned_points_at_the_board() {
		$message = Pinterest::error_message( null, 'Recipes' );

		$this->assertStringContainsString( 'to the board "Recipes".', $message );
		$this->assertStringContainsString( 'may no longer exist', $message );
		$this->assertStringNotContainsString( 'exits', $message );
	}

	public function test_expired_token_says_reconnect() {
		$message = Pinterest::error_message( new PinterestException( 'Pinterest error (code: 401) with message: Authentication failed.', 401 ), 'Recipes' );

		$this->assertStringContainsString( 'Error code: 401.', $message );
		$this->assertStringContainsString( 'Authentication failed.', $message );
		$this->assertStringContainsString( 'Reconnect this profile', $message );
		$this->assertStringNotContainsString( 'Pinterest error (code', $message );
	}

	public function test_forbidden_rate_limit_and_bad_request() {
		$this->assertStringContainsString( 'save pins to this board', Pinterest::error_message( new PinterestException( 'Pinterest error (code: 403) with message: Not permitted to access the board.', 403 ) ) );
		$this->assertStringContainsString( 'try again later', Pinterest::error_message( new PinterestException( 'Pinterest error (code: 429) with message: Rate limit exceeded', 429 ) ) );
		$this->assertStringContainsString( 'Check the image, title and link', Pinterest::error_message( new PinterestException( 'Pinterest error (code: 400) with message: Invalid image URL', 400 ) ) );
	}

	public function test_network_failure() {
		$curl = Pinterest::error_message( new PinterestException( 'Error: execute() - cURL error: Operation timed out after 90001 milliseconds', 28 ), 'Recipes' );
		$none = Pinterest::error_message( new CurlException( 'Error: Curl request failed' ) );

		$this->assertStringContainsString( 'no answer', $curl );
		$this->assertStringContainsString( 'Operation timed out after 90001 milliseconds. Please try sharing again.', $curl );
		$this->assertStringNotContainsString( 'Error code: 28', $curl );
		$this->assertStringContainsString( 'no answer', $none );
	}

	public function test_empty_reason_and_html() {
		$this->assertStringContainsString( 'Error code: 502.', Pinterest::error_message( new PinterestException( 'Pinterest error (code: 502) with message: ', 502 ) ) );
		$this->assertStringNotContainsString( '<b>', Pinterest::error_message( new PinterestException( 'Pinterest error (code: 400) with message: <b>Bad</b> pin', 400 ), '<i>Board</i>' ) );
	}
}
