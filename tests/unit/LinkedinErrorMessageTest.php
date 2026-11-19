<?php
/**
 * Unit tests for Linkedin::error_message() (card 6789, R3 sheet rows 47-48).
 *
 * A failed LinkedIn share used to log an empty reason unless the answer had a
 * serviceErrorCode. The message now carries LinkedIn's reason and a hint for
 * the common cases.
 *
 * @package WPScheduledPosts
 */

namespace WPSP\Tests\Unit;

use PHPUnit\Framework\TestCase;
use WPSP\Social\Linkedin;

class LinkedinErrorMessageTest extends TestCase {

	public function test_revoked_token_keeps_the_reason_and_says_reconnect() {
		$message = Linkedin::error_message( '{"status":401,"serviceErrorCode":65601,"code":"REVOKED_ACCESS_TOKEN","message":"The token used in the request has been revoked by the user"}' );

		$this->assertStringContainsString( 'Error code: 401.', $message );
		$this->assertStringContainsString( 'has been revoked by the user', $message );
		$this->assertStringContainsString( 'Reconnect this profile', $message );
	}

	public function test_access_denied_explains_page_admin() {
		$message = Linkedin::error_message( '{"status":403,"serviceErrorCode":100,"code":"ACCESS_DENIED","message":"Not enough permissions to access: partnerApiPostsExternal.CREATE"}' );

		$this->assertStringContainsString( 'Not enough permissions', $message );
		$this->assertStringContainsString( 'admin of it', $message );
	}

	public function test_old_api_version_says_update() {
		$message = Linkedin::error_message( '{"status":426,"code":"NONEXISTENT_VERSION","message":"Requested version 20240401 is not active"}' );

		$this->assertStringContainsString( 'Error code: 426.', $message );
		$this->assertStringContainsString( 'Requested version 20240401 is not active.', $message );
		$this->assertStringContainsString( 'Please update SchedulePress', $message );
	}

	public function test_answer_without_service_error_code_is_no_longer_empty() {
		$message = Linkedin::error_message( '{"message":"Resource level throttle APPLICATION DAY limit for calls to this resource is reached.","status":429}' );

		$this->assertStringContainsString( 'Error code: 429.', $message );
		$this->assertStringContainsString( 'Resource level throttle', $message );
		$this->assertStringContainsString( 'try again later', $message );
	}

	public function test_duplicate_post() {
		$message = Linkedin::error_message( '{"message":"Content is a duplicate of urn:li:share:7212345678901234567","status":422}' );

		$this->assertStringContainsString( 'Content is a duplicate', $message );
		$this->assertStringContainsString( 'same post twice', $message );
	}

	public function test_invalid_string_format_still_shows_its_message() {
		$message = Linkedin::error_message( '{"code":"INVALID_STRING_FORMAT","message":"Invalid string format in commentary"}' );

		$this->assertStringContainsString( 'Invalid string format in commentary', $message );
	}

	public function test_non_json_answer() {
		$message = Linkedin::error_message( '<html><body>Bad Gateway</body></html>' );

		$this->assertStringContainsString( 'unexpected response', $message );
		$this->assertStringNotContainsString( '<html>', $message );
	}

	public function test_no_answer_at_all() {
		$this->assertStringContainsString( 'no answer', Linkedin::error_message( false ) );
		$this->assertStringContainsString( 'no answer', Linkedin::error_message( '' ) );
	}

	public function test_html_in_the_reason_is_stripped() {
		$message = Linkedin::error_message( '{"status":400,"message":"<b>Bad</b> request"}' );

		$this->assertStringNotContainsString( '<b>', $message );
		$this->assertStringContainsString( 'Bad request', $message );
	}
}
