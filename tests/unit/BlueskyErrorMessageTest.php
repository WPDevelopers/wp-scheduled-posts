<?php
/**
 * Unit tests for Bluesky::error_message() (R3 sheet row 47).
 *
 * A network failure while signing in used to say "check your handle and App
 * Password", and a failed post with no answer said "error code: 0". The message
 * now carries Bluesky's reason or the transport error, and a hint per stage.
 *
 * @package WPScheduledPosts
 */

namespace WPSP\Tests\Unit;

use PHPUnit\Framework\TestCase;
use WPSP\Social\Bluesky;

class BlueskyErrorMessageTest extends TestCase {

	public function test_wrong_app_password_on_sign_in() {
		$message = Bluesky::error_message( array( 'result' => '{"error":"AuthenticationRequired","message":"Invalid identifier or password"}', 'code' => 401 ), 'session' );

		$this->assertStringContainsString( 'did not let SchedulePress sign in', $message );
		$this->assertStringContainsString( 'Invalid identifier or password.', $message );
		$this->assertStringContainsString( 'App Password', $message );
	}

	public function test_network_failure_on_sign_in_is_not_called_a_wrong_password() {
		$message = Bluesky::error_message( array( 'result' => false, 'code' => 0, 'error' => 'cURL error 28: Operation timed out' ), 'session' );

		$this->assertStringContainsString( 'no answer', $message );
		$this->assertStringContainsString( 'Operation timed out.', $message );
		$this->assertStringNotContainsString( 'App Password', $message );
	}

	public function test_post_rejected_for_content() {
		$message = Bluesky::error_message( array( 'result' => '{"error":"InvalidRequest","message":"Invalid app.bsky.feed.post record: Record/text must not be longer than 300 graphemes"}', 'code' => 400 ), 'post' );

		$this->assertStringContainsString( 'did not publish the post', $message );
		$this->assertStringContainsString( 'longer than 300 graphemes.', $message );
		$this->assertStringContainsString( 'Change the text or image', $message );
	}

	public function test_expired_token_on_post() {
		$message = Bluesky::error_message( array( 'result' => '{"error":"ExpiredToken","message":"Token has expired"}', 'code' => 400 ), 'post' );

		$this->assertStringContainsString( 'Token has expired.', $message );
		$this->assertStringContainsString( 'reconnect this profile', $message );
	}

	public function test_rate_limit_on_either_stage() {
		$body = array( 'result' => '{"error":"RateLimitExceeded","message":"Rate Limit Exceeded"}', 'code' => 429 );

		$this->assertStringContainsString( 'try again later', Bluesky::error_message( $body, 'session' ) );
		$this->assertStringContainsString( 'try again later', Bluesky::error_message( $body, 'post' ) );
	}

	public function test_error_name_is_used_when_there_is_no_message() {
		$this->assertStringContainsString( 'AccountTakedown.', Bluesky::error_message( array( 'result' => '{"error":"AccountTakedown"}', 'code' => 401 ), 'session' ) );
	}

	public function test_html_page_is_not_shown() {
		$message = Bluesky::error_message( array( 'result' => '<html><body><h1>502 Bad Gateway</h1></body></html>', 'code' => 502 ), 'post' );

		$this->assertStringContainsString( 'Error code: 502.', $message );
		$this->assertStringContainsString( 'unexpected response', $message );
		$this->assertStringNotContainsString( '<', $message );
	}

	public function test_odd_shapes_do_not_break() {
		$this->assertStringContainsString( 'unexpected response', Bluesky::error_message( array( 'result' => '{"message":["x"]}', 'code' => 400 ) ) );
		$this->assertStringContainsString( 'unexpected response', Bluesky::error_message( array( 'result' => '', 'code' => 500 ) ) );
		$this->assertStringContainsString( 'no answer', Bluesky::error_message( array() ) );
		$this->assertStringNotContainsString( '<b>', Bluesky::error_message( array( 'result' => '{"message":"<b>Bad</b> request"}', 'code' => 400 ) ) );
	}
}
