<?php
/**
 * Unit tests for Facebook::graph_error_message() (R3 sheet row 47).
 *
 * Facebook shares (and Instagram accounts connected through Facebook) showed
 * "Graph returned an error: …" or "SDK returned an error: …" with no hint.
 * The message now carries Meta's reason and a hint picked by the Graph code.
 * Uses the real Graph SDK exception classes, built from a Graph answer.
 *
 * @package WPScheduledPosts
 */

namespace WPSP\Tests\Unit;

use Facebook\Exceptions\FacebookResponseException;
use Facebook\Exceptions\FacebookSDKException;
use Facebook\FacebookRequest;
use Facebook\FacebookResponse;
use PHPUnit\Framework\TestCase;
use WPSP\Social\Facebook;

class FacebookGraphErrorMessageTest extends TestCase {

	public static function setUpBeforeClass(): void {
		// The bundled Graph SDK 5.7 trips PHP 8.4 deprecations when its classes load; keep them out of the output.
		$level = error_reporting( error_reporting() & ~E_DEPRECATED );
		foreach ( array( FacebookRequest::class, FacebookResponse::class, FacebookResponseException::class ) as $class ) {
			class_exists( $class );
		}
		error_reporting( $level );
	}

	private function graph_error( $status, $body ) {
		return FacebookResponseException::create( new FacebookResponse( new FacebookRequest( null, 'token', 'POST', '/123/feed' ), $body, $status ) );
	}

	public function test_expired_token_says_reconnect() {
		$message = Facebook::graph_error_message( $this->graph_error( 400, '{"error":{"message":"Error validating access token: Session has expired on Thursday.","type":"OAuthException","code":190,"error_subcode":463}}' ) );

		$this->assertStringStartsWith( 'Facebook did not publish the post.', $message );
		$this->assertStringContainsString( 'Error code: 400.', $message );
		$this->assertStringContainsString( 'Session has expired on Thursday.', $message );
		$this->assertStringContainsString( 'Reconnect this profile', $message );
		$this->assertStringNotContainsString( 'Graph returned', $message );
	}

	public function test_page_permission_says_admin() {
		$message = Facebook::graph_error_message( $this->graph_error( 403, '{"error":{"message":"(#200) The user hasn\'t authorized the application to perform this action","type":"OAuthException","code":200}}' ) );

		$this->assertStringContainsString( 'admin of the page', $message );
	}

	public function test_duplicate_and_spam_and_limit() {
		$this->assertStringContainsString( 'same post twice', Facebook::graph_error_message( $this->graph_error( 400, '{"error":{"message":"Duplicate status message","code":506}}' ) ) );
		$this->assertStringContainsString( 'blocked this post', Facebook::graph_error_message( $this->graph_error( 400, '{"error":{"message":"It looks like you were misusing this feature","code":368}}' ) ) );
		$this->assertStringContainsString( 'try again later', Facebook::graph_error_message( $this->graph_error( 400, '{"error":{"message":"Application request limit reached","code":4}}' ) ) );
	}

	public function test_user_message_is_preferred() {
		$message = Facebook::graph_error_message( $this->graph_error( 400, '{"error":{"message":"(#100) Invalid parameter","code":100,"error_user_msg":"The link could not be loaded"}}' ) );

		$this->assertStringContainsString( 'The link could not be loaded.', $message );
		$this->assertStringNotContainsString( 'Invalid parameter', $message );
	}

	public function test_instagram_wording() {
		$expired = Facebook::graph_error_message( $this->graph_error( 400, '{"error":{"message":"Session has expired","code":190}}' ), 'Instagram' );
		$image   = Facebook::graph_error_message( $this->graph_error( 400, '{"error":{"message":"Only photo or video can be accepted as media type.","code":9004}}' ), 'Instagram' );
		$perm    = Facebook::graph_error_message( $this->graph_error( 403, '{"error":{"message":"Permissions error","code":10}}' ), 'Instagram' );
		$twice   = Facebook::graph_error_message( $this->graph_error( 400, '{"error":{"message":"Duplicate status message","code":506}}' ), 'Instagram' );

		$this->assertStringStartsWith( 'Instagram did not publish the post.', $expired );
		$this->assertStringContainsString( 'The Instagram connection has expired', $expired );
		$this->assertStringContainsString( 'public JPEG', $image );
		$this->assertStringContainsString( 'Business or Creator', $perm );
		$this->assertStringContainsString( 'Instagram does not accept the same post twice', $twice );
		$this->assertStringNotContainsString( 'Facebook', $expired . $image . $perm . $twice );
	}

	public function test_no_usable_answer() {
		$message = Facebook::graph_error_message( new FacebookSDKException( 'cURL error 28: Operation timed out' ) );

		$this->assertStringContainsString( 'Operation timed out.', $message );
		$this->assertStringContainsString( 'Please try sharing again', $message );
	}

	public function test_html_in_the_reason_is_stripped() {
		$message = Facebook::graph_error_message( $this->graph_error( 400, '{"error":{"message":"<b>Bad</b> request","code":1}}' ) );

		$this->assertStringNotContainsString( '<b>', $message );
		$this->assertStringContainsString( 'Bad request', $message );
	}
}
