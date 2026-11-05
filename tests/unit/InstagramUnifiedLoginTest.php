<?php
/**
 * Unit tests for Instagram's Unified Login: connect, account listing, publishing
 * and what a reconnect does with a profile connected that way.
 *
 * @package WPScheduledPosts
 */

namespace WPSP\Tests\Unit;

use PHPUnit\Framework\TestCase;
use WPSP\Social\InstagramUnifiedLogin;
use WPSP\Social\ReconnectHandler;
use WPSP\Tests\Stubs\HttpStore;

class InstagramUnifiedLoginTest extends TestCase {

	const GRAPH = 'https://graph.facebook.com/v26.0';

	protected function setUp(): void {
		parent::setUp();
		HttpStore::reset();
	}

	private function query( $url ) {
		parse_str( (string) parse_url( $url, PHP_URL_QUERY ), $query );
		return $query;
	}

	// ── Login dialog ─────────────────────────────────────────────────────────

	public function test_the_dialog_matches_the_dashboard_login_url_plus_state() {
		$url = InstagramUnifiedLogin::auth_url( '111', '222', 'https://api.schedulepress.com/callback.php', 'c3RhdGU=' );

		$this->assertStringStartsWith( 'https://www.facebook.com/dialog/oauth?', $url );
		$this->assertSame(
			array(
				'client_id'                      => '111',
				'config_id'                      => '222',
				'display'                        => 'popup',
				'override_default_response_type' => 'true',
				'redirect_uri'                   => 'https://api.schedulepress.com/callback.php',
				'response_type'                  => 'code',
				'state'                          => 'c3RhdGU=',
				'prompt'                         => 'login',
			),
			$this->query( $url )
		);
		$this->assertArrayNotHasKey( 'scope', $this->query( $url ) );
	}

	// ── Code exchange ────────────────────────────────────────────────────────

	public function test_a_system_user_token_is_kept_as_it_comes() {
		HttpStore::on( self::GRAPH . '/oauth/access_token', array(
			HttpStore::json( 200, array( 'access_token' => 'bisu-token', 'token_type' => 'bearer' ) ),
		) );

		$token = InstagramUnifiedLogin::exchange_code( '111', 'secret', 'https://cb.test', 'the-code' );

		$this->assertSame( array( 'access_token' => 'bisu-token', 'expires_in' => null ), $token );
		$this->assertCount( 1, HttpStore::$requests );
		$this->assertSame( 'the-code', $this->query( HttpStore::$requests[0] )['code'] );
		$this->assertSame( 'https://cb.test', $this->query( HttpStore::$requests[0] )['redirect_uri'] );
	}

	public function test_a_short_lived_user_token_is_swapped_for_a_long_lived_one() {
		HttpStore::on( self::GRAPH . '/oauth/access_token', array(
			HttpStore::json( 200, array( 'access_token' => 'short', 'expires_in' => 3600 ) ),
			HttpStore::json( 200, array( 'access_token' => 'long', 'expires_in' => 5184000 ) ),
		) );

		$token = InstagramUnifiedLogin::exchange_code( '111', 'secret', 'https://cb.test', 'the-code' );

		$this->assertSame( array( 'access_token' => 'long', 'expires_in' => 5184000 ), $token );
		$exchange = $this->query( HttpStore::$requests[1] );
		$this->assertSame( 'fb_exchange_token', $exchange['grant_type'] );
		$this->assertSame( 'short', $exchange['fb_exchange_token'] );
	}

	public function test_a_refused_exchange_reports_metas_reason() {
		HttpStore::on( self::GRAPH . '/oauth/access_token', array(
			HttpStore::json( 400, array( 'error' => array( 'message' => 'Error validating verification code.' ) ) ),
		) );

		$this->expectException( \Exception::class );
		$this->expectExceptionMessage( 'Error validating verification code.' );
		InstagramUnifiedLogin::exchange_code( '111', 'secret', 'https://cb.test', 'stale' );
	}

	// ── Accounts ─────────────────────────────────────────────────────────────

	public function test_assigned_accounts_are_listed_with_their_details() {
		HttpStore::on( self::GRAPH . '/me/assigned_instagram_accounts', array(
			HttpStore::json( 200, array( 'data' => array(
				array( 'id' => '1784', 'username' => 'shop' ),
				array( 'id' => '1785', 'username' => 'blog' ),
			) ) ),
		) );
		HttpStore::on( self::GRAPH . '/1784?', array(
			HttpStore::json( 200, array( 'id' => '1784', 'name' => 'The Shop', 'username' => 'shop', 'profile_picture_url' => 'https://cdn.test/shop.jpg' ) ),
		) );
		// No details for the second account: what the edge said is enough.

		$accounts = InstagramUnifiedLogin::fetch_accounts( 'bisu-token' );

		$this->assertSame(
			array(
				array( 'id' => '1784', 'name' => 'The Shop', 'username' => 'shop', 'profile_picture_url' => 'https://cdn.test/shop.jpg' ),
				array( 'id' => '1785', 'name' => 'blog', 'username' => 'blog', 'profile_picture_url' => '' ),
			),
			$accounts
		);
	}

	public function test_a_user_token_falls_back_to_page_linked_accounts() {
		HttpStore::on( self::GRAPH . '/me/assigned_instagram_accounts', array(
			HttpStore::json( 400, array( 'error' => array( 'message' => 'Tried accessing nonexisting field' ) ) ),
		) );
		HttpStore::on( self::GRAPH . '/me/accounts', array(
			HttpStore::json( 200, array( 'data' => array(
				array( 'id' => 'page-1', 'instagram_business_account' => array( 'id' => '1790', 'username' => 'linked' ) ),
				array( 'id' => 'page-2' ),
			) ) ),
		) );

		$accounts = InstagramUnifiedLogin::fetch_accounts( 'user-token' );

		$this->assertSame( array( array( 'id' => '1790', 'name' => 'linked', 'username' => 'linked', 'profile_picture_url' => '' ) ), $accounts );
	}

	public function test_no_route_answering_is_an_error() {
		HttpStore::on( self::GRAPH . '/me/assigned_instagram_accounts', array(
			HttpStore::json( 400, array( 'error' => array( 'message' => 'Invalid OAuth access token.' ) ) ),
		) );
		HttpStore::on( self::GRAPH . '/me/accounts', array(
			HttpStore::json( 400, array( 'error' => array( 'message' => 'Invalid OAuth access token.' ) ) ),
		) );

		$this->expectExceptionMessage( 'Invalid OAuth access token.' );
		InstagramUnifiedLogin::fetch_accounts( 'dead-token' );
	}

	// ── Publishing ───────────────────────────────────────────────────────────

	public function test_an_image_is_published_once_its_container_is_ready() {
		HttpStore::on( self::GRAPH . '/1784/media_publish', array(
			HttpStore::json( 200, array( 'id' => 'media-9' ) ),
		) );
		HttpStore::on( self::GRAPH . '/1784/media', array(
			HttpStore::json( 200, array( 'id' => 'container-5' ) ),
		) );
		HttpStore::on( self::GRAPH . '/container-5?', array(
			HttpStore::json( 200, array( 'status_code' => 'FINISHED' ) ),
		) );

		$result = InstagramUnifiedLogin::publish( '1784', 'bisu-token', 'https://site.test/a.jpg', 'Hello' );

		$this->assertSame( array( 'success' => true, 'id' => 'media-9' ), $result );
		$this->assertSame(
			array( self::GRAPH . '/1784/media', self::GRAPH . '/container-5', self::GRAPH . '/1784/media_publish' ),
			array_map( function ( $url ) { return strtok( $url, '?' ); }, HttpStore::$requests )
		);
		$this->assertSame(
			array( 'image_url' => 'https://site.test/a.jpg', 'caption' => 'Hello', 'access_token' => 'bisu-token' ),
			HttpStore::$bodies[0]
		);
		$this->assertSame( array( 'creation_id' => 'container-5', 'access_token' => 'bisu-token' ), HttpStore::$bodies[1] );
	}

	public function test_a_rejected_image_is_never_published() {
		HttpStore::on( self::GRAPH . '/1784/media_publish', array(
			HttpStore::json( 200, array( 'id' => 'media-9' ) ),
		) );
		HttpStore::on( self::GRAPH . '/1784/media', array(
			HttpStore::json( 200, array( 'id' => 'container-5' ) ),
		) );
		HttpStore::on( self::GRAPH . '/container-5?', array(
			HttpStore::json( 200, array( 'status_code' => 'ERROR', 'status' => 'Error: Media format not supported' ) ),
		) );

		$result = InstagramUnifiedLogin::publish( '1784', 'bisu-token', 'https://site.test/a.webp', 'Hello' );

		$this->assertFalse( $result['success'] );
		$this->assertStringContainsString( 'Media format not supported', $result['error'] );
		$this->assertCount( 2, HttpStore::$requests );
	}

	public function test_a_container_refusal_reports_metas_reason() {
		HttpStore::on( self::GRAPH . '/1784/media', array(
			HttpStore::json( 400, array( 'error' => array(
				'message'        => 'Invalid parameter',
				'error_user_msg' => 'The aspect ratio is not supported.',
			) ) ),
		) );

		$result = InstagramUnifiedLogin::publish( '1784', 'bisu-token', 'https://site.test/a.jpg', 'Hello' );

		$this->assertSame( array( 'success' => false, 'error' => 'The aspect ratio is not supported.' ), $result );
	}

	public function test_a_post_without_an_image_makes_no_request() {
		$result = InstagramUnifiedLogin::publish( '1784', 'bisu-token', '', 'Hello' );

		$this->assertFalse( $result['success'] );
		$this->assertSame( array(), HttpStore::$requests );
	}

	// ── Reconnect ────────────────────────────────────────────────────────────

	public function test_a_unified_profile_is_never_renewed_on_graph_instagram() {
		$result = ReconnectHandler::renew( 'instagram', array(
			'id'           => '1784',
			'app_id'       => '111',
			'app_secret'   => 'secret',
			'config_id'    => '222',
			'access_token' => 'bisu-token',
			'auth_mode'    => 'unified',
		) );

		$this->assertTrue( $result['needs_auth'] );
		$this->assertSame( 'manual', $result['method'] );
		$this->assertSame( '222', $result['config_id'] );
		$this->assertSame( array(), HttpStore::$requests );
	}

	public function test_a_unified_profile_without_an_expiry_is_left_alone_by_maintenance() {
		$profile = array( 'id' => '1784', 'access_token' => 'bisu-token', 'expires_at' => '', 'auth_mode' => 'unified' );

		$this->assertFalse( ReconnectHandler::needs_renewal( 'instagram', $profile ) );
		$this->assertFalse( ReconnectHandler::has_renewal_credential( 'instagram', $profile ) );
	}

	public function test_profiles_from_instagram_login_are_not_unified() {
		$this->assertFalse( InstagramUnifiedLogin::is_unified( (object) array( 'instagram_app' => true ) ) );
		$this->assertTrue( InstagramUnifiedLogin::is_unified( (object) array( 'auth_mode' => 'unified' ) ) );
	}
}
