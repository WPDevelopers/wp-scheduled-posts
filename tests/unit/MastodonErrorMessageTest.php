<?php
/**
 * Unit tests for Mastodon::error_message() (R3 sheet row 47).
 *
 * A failed Mastodon share used to show a bare error key ("invalid_token") or
 * "error code: 0" when there was no answer. The message now carries Mastodon's
 * reason, the transport error, and a hint for common cases.
 *
 * @package WPScheduledPosts
 */

namespace WPSP\Tests\Unit;

use PHPUnit\Framework\TestCase;
use WPSP\Social\Mastodon;

class MastodonErrorMessageTest extends TestCase {

	public function test_invalid_token_keeps_the_reason_and_says_reconnect() {
		$message = Mastodon::error_message( array( 'result' => '{"error":"The access token is invalid"}', 'code' => 401 ) );

		$this->assertStringContainsString( 'Error code: 401.', $message );
		$this->assertStringContainsString( 'The access token is invalid.', $message );
		$this->assertStringContainsString( 'Reconnect this profile', $message );
	}

	public function test_oauth_error_prefers_the_description() {
		$message = Mastodon::error_message( array( 'result' => '{"error":"invalid_token","error_description":"The access token was revoked"}', 'code' => 401 ) );

		$this->assertStringContainsString( 'The access token was revoked.', $message );
		$this->assertStringNotContainsString( 'invalid_token', $message );
	}

	public function test_validation_failed() {
		$message = Mastodon::error_message( array( 'result' => '{"error":"Validation failed: Text character limit of 500 exceeded"}', 'code' => 422 ) );

		$this->assertStringContainsString( 'character limit of 500 exceeded.', $message );
		$this->assertStringContainsString( 'Change the text or image', $message );
	}

	public function test_forbidden_and_not_found_and_rate_limit() {
		$this->assertStringContainsString( 'can post on its server', Mastodon::error_message( array( 'result' => '{"error":"This action is not allowed"}', 'code' => 403 ) ) );
		$this->assertStringContainsString( 'Check the server URL', Mastodon::error_message( array( 'result' => '{"error":"Record not found"}', 'code' => 404 ) ) );
		$this->assertStringContainsString( 'try again later', Mastodon::error_message( array( 'result' => '{"error":"Too many requests"}', 'code' => 429 ) ) );
	}

	public function test_network_failure_keeps_the_transport_reason() {
		$message = Mastodon::error_message( array( 'result' => false, 'code' => 0, 'error' => 'cURL error 6: Could not resolve host: mastodon.example' ) );

		$this->assertStringContainsString( 'no answer', $message );
		$this->assertStringContainsString( 'Could not resolve host: mastodon.example. Please try sharing again.', $message );
	}

	public function test_html_page_is_not_shown() {
		$message = Mastodon::error_message( array( 'result' => '<html><body><h1>502 Bad Gateway</h1></body></html>', 'code' => 502 ) );

		$this->assertStringContainsString( 'Error code: 502.', $message );
		$this->assertStringContainsString( 'unexpected response', $message );
		$this->assertStringNotContainsString( '<', $message );
	}

	public function test_odd_shapes_do_not_break() {
		$this->assertStringContainsString( 'unexpected response', Mastodon::error_message( array( 'result' => '{"error":["x"]}', 'code' => 400 ) ) );
		$this->assertStringContainsString( 'unexpected response', Mastodon::error_message( array( 'result' => '[]', 'code' => 400 ) ) );
		$this->assertStringContainsString( 'unexpected response', Mastodon::error_message( array( 'result' => '', 'code' => 500 ) ) );
		$this->assertStringContainsString( 'no answer', Mastodon::error_message( array() ) );
	}

	public function test_html_in_the_reason_is_stripped() {
		$message = Mastodon::error_message( array( 'result' => '{"error":"<b>Bad</b> request"}', 'code' => 400 ) );

		$this->assertStringNotContainsString( '<b>', $message );
		$this->assertStringContainsString( 'Bad request', $message );
	}
}
