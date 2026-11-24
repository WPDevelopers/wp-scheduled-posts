<?php
/**
 * Unit tests for Medium::error_message() (R3 sheet row 47).
 *
 * A failed Medium share used to say only "Something went wrong..". The message
 * now carries Medium's reason, the transport error, and a hint for common cases.
 *
 * @package WPScheduledPosts
 */

namespace WPSP\Tests\Unit;

use PHPUnit\Framework\TestCase;
use WPSP\Social\Medium;

class MediumErrorMessageTest extends TestCase {

	public function test_invalid_token_keeps_the_reason_and_says_reconnect() {
		$message = Medium::error_message( array( 'result' => '{"errors":[{"message":"Token was invalid.","code":6003}]}', 'code' => 401 ) );

		$this->assertStringContainsString( 'Error code: 401.', $message );
		$this->assertStringContainsString( 'Token was invalid.', $message );
		$this->assertStringContainsString( 'Reconnect this profile', $message );
	}

	public function test_forbidden_says_check_the_token() {
		$message = Medium::error_message( array( 'result' => '{"errors":[{"message":"You do not have permission to publish","code":6027}]}', 'code' => 403 ) );

		$this->assertStringContainsString( 'You do not have permission to publish.', $message );
		$this->assertStringContainsString( 'can publish to this account', $message );
	}

	public function test_rate_limit() {
		$message = Medium::error_message( array( 'result' => '{"errors":[{"message":"Too many requests"}]}', 'code' => 429 ) );

		$this->assertStringContainsString( 'Error code: 429.', $message );
		$this->assertStringContainsString( 'try again later', $message );
	}

	public function test_network_failure_keeps_the_transport_reason() {
		$message = Medium::error_message( array( 'result' => false, 'code' => 0, 'error' => 'cURL error 28: Operation timed out' ) );

		$this->assertStringContainsString( 'no answer', $message );
		$this->assertStringContainsString( 'Operation timed out. Please try sharing again.', $message );
	}

	public function test_network_failure_without_a_transport_reason() {
		$message = Medium::error_message( array( 'result' => false, 'code' => 0 ) );

		$this->assertStringContainsString( 'no answer', $message );
	}

	public function test_html_page_is_not_shown() {
		$message = Medium::error_message( array( 'result' => '<html><body><h1>502 Bad Gateway</h1></body></html>', 'code' => 502 ) );

		$this->assertStringContainsString( 'Error code: 502.', $message );
		$this->assertStringContainsString( 'unexpected response', $message );
		$this->assertStringNotContainsString( '<', $message );
	}

	public function test_odd_shapes_do_not_break() {
		$this->assertStringContainsString( 'unexpected response', Medium::error_message( array( 'result' => '{"errors":[]}', 'code' => 400 ) ) );
		$this->assertStringContainsString( 'unexpected response', Medium::error_message( array( 'result' => '{"errors":"nope"}', 'code' => 400 ) ) );
		$this->assertStringContainsString( 'unexpected response', Medium::error_message( array( 'result' => '{"errors":[{"message":["x"]}]}', 'code' => 400 ) ) );
		$this->assertStringContainsString( 'no answer', Medium::error_message( array() ) );
	}

	public function test_html_in_the_reason_is_stripped() {
		$message = Medium::error_message( array( 'result' => '{"errors":[{"message":"<b>Bad</b> request"}]}', 'code' => 400 ) );

		$this->assertStringNotContainsString( '<b>', $message );
		$this->assertStringContainsString( 'Bad request', $message );
	}
}
