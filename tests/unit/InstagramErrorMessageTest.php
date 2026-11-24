<?php
/**
 * Unit tests for Instagram::error_message() (R3 sheet row 47).
 *
 * The "Instagram app" login path returned its error as an array with the raw
 * response body inside (JSON or an HTML page). The message is now text with
 * Instagram's reason, the transport error, and a hint picked by the Graph code.
 *
 * @package WPScheduledPosts
 */

namespace WPSP\Tests\Unit;

use PHPUnit\Framework\TestCase;
use WPSP\Social\Instagram;

class InstagramErrorMessageTest extends TestCase {

	private function answer( $status, $body ) {
		return array( 'response' => array( 'code' => $status ), 'body' => $body );
	}

	public function test_expired_token_says_reconnect() {
		$message = Instagram::error_message( $this->answer( 400, '{"error":{"message":"Error validating access token: Session has expired.","type":"OAuthException","code":190,"error_subcode":463}}' ) );

		$this->assertStringContainsString( 'Error code: 400.', $message );
		$this->assertStringContainsString( 'Session has expired.', $message );
		$this->assertStringContainsString( 'Reconnect this profile', $message );
	}

	public function test_image_problem() {
		$message = Instagram::error_message( $this->answer( 400, '{"error":{"message":"Only photo or video can be accepted as media type.","code":9004,"error_subcode":2207052,"error_user_msg":"The media could not be fetched from this uri"}}' ) );

		$this->assertStringContainsString( 'The media could not be fetched from this uri.', $message );
		$this->assertStringContainsString( 'public JPEG', $message );
	}

	public function test_permission_and_limit() {
		$this->assertStringContainsString( 'Business or Creator', Instagram::error_message( $this->answer( 403, '{"error":{"message":"Permissions error","code":10}}' ) ) );
		$this->assertStringContainsString( 'try again later', Instagram::error_message( $this->answer( 400, '{"error":{"message":"Application request limit reached","code":4}}' ) ) );
	}

	public function test_network_failure_keeps_the_transport_reason() {
		$message = Instagram::error_message( new \WP_Error( 'http_request_failed', 'cURL error 28: Operation timed out' ) );

		$this->assertStringContainsString( 'no answer', $message );
		$this->assertStringContainsString( 'Operation timed out. Please try sharing again.', $message );
	}

	public function test_ok_status_without_an_id() {
		$message = Instagram::error_message( $this->answer( 200, '{}' ) );

		$this->assertStringContainsString( 'did not return a post ID', $message );
		$this->assertStringNotContainsString( 'Error code: 200', $message );
	}

	public function test_html_page_is_not_shown() {
		$message = Instagram::error_message( $this->answer( 502, '<html><body><h1>502 Bad Gateway</h1></body></html>' ) );

		$this->assertStringContainsString( 'Error code: 502.', $message );
		$this->assertStringContainsString( 'unexpected response', $message );
		$this->assertStringNotContainsString( '<', $message );
	}

	public function test_odd_shapes_do_not_break() {
		$this->assertStringContainsString( 'unexpected response', Instagram::error_message( $this->answer( 400, '{"error":"nope"}' ) ) );
		$this->assertStringNotContainsString( '<b>', Instagram::error_message( $this->answer( 400, '{"error":{"message":"<b>Bad</b> request"}}' ) ) );
	}
}
