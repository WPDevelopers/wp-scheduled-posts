<?php
/**
 * Unit tests for Twitter::error_message() (card 84758).
 *
 * A failed post used to show only "error code: 402", which hid that X wants
 * API credits. The message now carries X's own reason and a hint for 402.
 *
 * @package WPScheduledPosts
 */

namespace WPSP\Tests\Unit;

use PHPUnit\Framework\TestCase;
use WPSP\Social\Twitter;

class TwitterErrorMessageTest extends TestCase {

	public function test_402_keeps_the_code_and_adds_the_credit_hint() {
		$message = Twitter::error_message( 402, (object) array( 'title' => 'CreditsDepleted', 'detail' => 'Your enrolled account does not have any credits.' ) );

		$this->assertStringContainsString( 'error code: 402', $message );
		$this->assertStringContainsString( 'Your enrolled account does not have any credits.', $message );
		$this->assertStringContainsString( 'console.x.com', $message );
	}

	public function test_title_is_used_when_there_is_no_detail() {
		$message = Twitter::error_message( 403, (object) array( 'title' => 'Forbidden' ) );

		$this->assertStringContainsString( '(Forbidden)', $message );
	}

	public function test_v1_style_errors_array_is_read() {
		$body    = (object) array( 'errors' => array( (object) array( 'message' => 'Rate limit exceeded' ) ) );
		$message = Twitter::error_message( 429, $body );

		$this->assertStringContainsString( '(Rate limit exceeded)', $message );
	}

	public function test_credit_hint_is_only_for_402() {
		$message = Twitter::error_message( 403, (object) array( 'detail' => 'You are not permitted to perform this action.' ) );

		$this->assertStringNotContainsString( 'console.x.com', $message );
	}

	public function test_body_without_a_reason_gives_the_old_message() {
		$this->assertSame( 'Twitter Connection Problem. error code: 500', Twitter::error_message( 500, null ) );
		$this->assertSame( 'Twitter Connection Problem. error code: 500', Twitter::error_message( 500, (object) array( 'detail' => array( 'x' ) ) ) );
	}

	public function test_markup_in_the_reason_is_stripped() {
		$message = Twitter::error_message( 400, (object) array( 'detail' => '<b>Invalid</b> request' ) );

		$this->assertStringContainsString( '(Invalid request)', $message );
	}
}
