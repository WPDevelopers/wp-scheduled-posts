<?php
/**
 * Unit tests for Helper::wpsp_curl() and Helper::wpsp_medium_curl().
 *
 * Both used raw cURL and now go through the WordPress HTTP API. These pin what
 * the callers (Bluesky, Mastodon, Medium) rely on: the return shape, the
 * failure shape, the request arguments, and that "Name: value" header strings
 * reach the request as a header array.
 *
 * @package WPScheduledPosts
 */

namespace WPSP\Tests\Unit;

use PHPUnit\Framework\TestCase;
use WPSP\Helper;
use WPSP\Tests\Stubs\HttpStore;

class HelperHttpRequestTest extends TestCase {

	const URL = 'https://api.example.test/v1/thing';

	protected function setUp(): void {
		parent::setUp();
		HttpStore::reset();
	}

	public function test_returns_body_and_code() {
		HttpStore::on( self::URL, array( HttpStore::json( 200, array( 'ok' => true ) ) ) );

		$res = Helper::wpsp_curl( self::URL, '{"a":1}', 'application/json' );

		$this->assertSame( 200, $res['code'] );
		$this->assertSame( '{"ok":true}', $res['result'] );
	}

	public function test_failure_keeps_the_old_shape() {
		HttpStore::on( self::URL, array( new \WP_Error( 'http_request_failed', 'timeout' ) ) );

		$res = Helper::wpsp_curl( self::URL, '', 'application/json', false );

		$this->assertFalse( $res['result'] );
		$this->assertSame( 0, $res['code'] );
	}

	public function test_post_sends_the_body_and_content_type() {
		HttpStore::on( self::URL, array( HttpStore::json( 200, array() ) ) );

		Helper::wpsp_curl( self::URL, '{"a":1}', 'application/json', true );

		$args = HttpStore::$args[0];
		$this->assertSame( 'POST', $args['method'] );
		$this->assertSame( '{"a":1}', $args['body'] );
		$this->assertSame( 'application/json', $args['headers']['Content-Type'] );
	}

	public function test_get_sends_no_body() {
		HttpStore::on( self::URL, array( HttpStore::json( 200, array() ) ) );

		Helper::wpsp_curl( self::URL, 'ignored', 'application/json', false );

		$args = HttpStore::$args[0];
		$this->assertSame( 'GET', $args['method'] );
		$this->assertArrayNotHasKey( 'body', $args );
	}

	public function test_header_strings_become_a_header_array() {
		HttpStore::on( self::URL, array( HttpStore::json( 200, array() ) ) );

		Helper::wpsp_curl(
			self::URL,
			'',
			'application/json',
			false,
			array( 'Authorization: Bearer abc:def', 'Accept: application/json' )
		);

		$headers = HttpStore::$args[0]['headers'];
		// Only the first colon splits, so a token with a colon survives.
		$this->assertSame( 'Bearer abc:def', $headers['Authorization'] );
		$this->assertSame( 'application/json', $headers['Accept'] );
	}

	public function test_the_content_type_argument_wins_over_a_header_string() {
		HttpStore::on( self::URL, array( HttpStore::json( 200, array() ) ) );

		Helper::wpsp_curl( self::URL, 'x', 'multipart/form-data; boundary=abc', true, array( 'content-type: text/plain' ) );

		$headers = HttpStore::$args[0]['headers'];
		$this->assertSame( 'multipart/form-data; boundary=abc', $headers['Content-Type'] );
		$this->assertArrayNotHasKey( 'content-type', $headers );
	}

	public function test_request_arguments_match_the_old_cURL_behaviour() {
		HttpStore::on( self::URL, array( HttpStore::json( 200, array() ), HttpStore::json( 200, array() ) ) );

		Helper::wpsp_curl( self::URL, '', 'application/json', false );
		Helper::wpsp_medium_curl( self::URL, '', 'application/json', false );

		list( $plain, $medium ) = HttpStore::$args;
		$this->assertSame( 0, $plain['redirection'], 'wpsp_curl never followed redirects' );
		$this->assertSame( 10, $medium['redirection'], 'the Medium helper followed up to 10' );
		foreach ( array( $plain, $medium ) as $args ) {
			$this->assertSame( 60, $args['timeout'], 'cURL had no limit; the 5s default would cut uploads' );
			$this->assertSame( '1.1', $args['httpversion'] );
			$this->assertTrue( $args['sslverify'] );
		}
	}

	public function test_ssl_flag_is_passed_through() {
		HttpStore::on( self::URL, array( HttpStore::json( 200, array() ) ) );

		Helper::wpsp_curl( self::URL, '', 'application/json', false, array(), false );

		$this->assertFalse( HttpStore::$args[0]['sslverify'] );
	}
}
