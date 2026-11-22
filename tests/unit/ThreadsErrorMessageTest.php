<?php
/**
 * Unit tests for Threads::error_message() (R3 sheet row 47).
 *
 * A failed Threads share was prefixed "SDK returned an error:" (no SDK is used)
 * and gave only the HTTP status for most failures. The message now carries
 * Meta's reason, the transport error, and a hint picked by the Graph error code.
 *
 * @package WPScheduledPosts
 */

namespace WPSP\Tests\Unit;

use PHPUnit\Framework\TestCase;
use WPSP\Social\Threads;

class ThreadsErrorMessageTest extends TestCase {

	private function answer( $status, $body ) {
		return array( 'response' => array( 'code' => $status ), 'body' => $body );
	}

	public function test_expired_token_says_reconnect() {
		$message = Threads::error_message( $this->answer( 400, '{"error":{"message":"Error validating access token: Session has expired on Thursday, 19-Nov-26.","type":"OAuthException","code":190,"error_subcode":463}}' ) );

		$this->assertStringContainsString( 'Error code: 400.', $message );
		$this->assertStringContainsString( 'Session has expired', $message );
		$this->assertStringContainsString( 'Reconnect this profile', $message );
		$this->assertStringNotContainsString( 'SDK', $message );
	}

	public function test_user_message_is_preferred() {
		$message = Threads::error_message( $this->answer( 400, '{"error":{"message":"(#100) Invalid parameter","code":100,"error_user_msg":"The image could not be downloaded"}}' ) );

		$this->assertStringContainsString( 'The image could not be downloaded.', $message );
		$this->assertStringNotContainsString( 'Invalid parameter', $message );
		$this->assertStringContainsString( 'featured image is public', $message );
	}

	public function test_permission_and_rate_limit() {
		$this->assertStringContainsString( 'allow SchedulePress to publish', Threads::error_message( $this->answer( 403, '{"error":{"message":"Permissions error","code":10}}' ) ) );
		$this->assertStringContainsString( 'allow SchedulePress to publish', Threads::error_message( $this->answer( 400, '{"error":{"message":"Requires threads_content_publish","code":200}}' ) ) );
		$this->assertStringContainsString( 'try again later', Threads::error_message( $this->answer( 400, '{"error":{"message":"Application request limit reached","code":4}}' ) ) );
		$this->assertStringContainsString( 'try again later', Threads::error_message( $this->answer( 429, '' ) ) );
	}

	public function test_network_failure_keeps_the_transport_reason() {
		$message = Threads::error_message( new \WP_Error( 'http_request_failed', 'cURL error 28: Operation timed out' ) );

		$this->assertStringContainsString( 'no answer', $message );
		$this->assertStringContainsString( 'Operation timed out. Please try sharing again.', $message );
	}

	public function test_ok_status_without_an_id() {
		$message = Threads::error_message( $this->answer( 200, '{}' ) );

		$this->assertStringContainsString( 'did not return a post ID', $message );
		$this->assertStringNotContainsString( 'Error code: 200', $message );
	}

	public function test_html_page_is_not_shown() {
		$message = Threads::error_message( $this->answer( 502, '<html><body><h1>502 Bad Gateway</h1></body></html>' ) );

		$this->assertStringContainsString( 'Error code: 502.', $message );
		$this->assertStringContainsString( 'unexpected response', $message );
		$this->assertStringNotContainsString( '<', $message );
	}

	public function test_odd_shapes_do_not_break() {
		$this->assertStringContainsString( 'unexpected response', Threads::error_message( $this->answer( 400, '{"error":"nope"}' ) ) );
		$this->assertStringContainsString( 'unexpected response', Threads::error_message( $this->answer( 400, '{"error":{"message":["x"]}}' ) ) );
		$this->assertStringNotContainsString( '<b>', Threads::error_message( $this->answer( 400, '{"error":{"message":"<b>Bad</b> request"}}' ) ) );
	}
}
