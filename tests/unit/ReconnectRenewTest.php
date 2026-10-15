<?php
/**
 * Unit tests for card 84587: a reconnect reports success only once the new token is saved.
 *
 * @package WPScheduledPosts
 */

namespace {

	if ( ! function_exists( 'add_query_arg' ) ) {
		function add_query_arg( $args, $url ) {
			return $url . '?' . http_build_query( $args );
		}
	}

	if ( ! function_exists( 'rest_ensure_response' ) ) {
		function rest_ensure_response( $response ) {
			if ( $response instanceof \WP_Error || $response instanceof \WP_REST_Response ) {
				return $response;
			}
			return new \WP_REST_Response( $response );
		}
	}
}

namespace WPSP\Tests\Unit {

	use PHPUnit\Framework\TestCase;
	use WPSP\API\Settings;
	use WPSP\Social\ReconnectHandler;
	use WPSP\Tests\Stubs\FakeRequest;
	use WPSP\Tests\Stubs\HttpStore;
	use WPSP\Tests\Stubs\OptionStore;

	class ReconnectRenewTest extends TestCase {

		const OPTION_NAME = 'wpsp_settings_v5';
		const REFRESH_URL = 'https://graph.threads.net/refresh_access_token';

		public static function setUpBeforeClass(): void {
			parent::setUpBeforeClass();
			if ( ! defined( 'WPSP_SETTINGS_NAME' ) ) {
				define( 'WPSP_SETTINGS_NAME', self::OPTION_NAME );
			}
		}

		protected function setUp(): void {
			parent::setUp();
			OptionStore::reset();
			HttpStore::reset();
			OptionStore::seed( self::OPTION_NAME, json_encode( array(
				'threads_profile_list' => array(
					array(
						'id'                      => 'th-1',
						'__id'                    => 'th-entry-1',
						'long_lived_access_token' => 'old-token',
						'expires_in'              => 5184000,
						'added_date'              => '2026-08-01 10:00:00',
					),
				),
			) ) );
			HttpStore::on( self::REFRESH_URL, array(
				HttpStore::json( 200, array( 'access_token' => 'new-token', 'expires_in' => 5184000 ) ),
			) );
		}

		private function stored_token() {
			$settings = json_decode( OptionStore::get( self::OPTION_NAME ), true );
			return $settings['threads_profile_list'][0]['long_lived_access_token'];
		}

		private function route( $callback, array $params ) {
			$settings = ( new \ReflectionClass( Settings::class ) )->newInstanceWithoutConstructor();
			return $settings->$callback( new FakeRequest( $params ) );
		}

		// ── Reconnect button: update-refresh-token ───────────────────────────────

		public function test_a_saved_token_is_reported_as_renewed() {
			$result = ReconnectHandler::handleProfileReconnect( 'threads', array( 'id' => 'th-1' ) );

			$this->assertTrue( $result['success'] );
			$this->assertTrue( $result['reconnected'] );
			$this->assertSame( 'new-token', $this->stored_token() );
		}

		public function test_a_failed_save_is_a_500_not_renewed() {
			OptionStore::$failWrites = true;

			$result = ReconnectHandler::handleProfileReconnect( 'threads', array( 'id' => 'th-1' ) );

			$this->assertFalse( $result['success'] );
			$this->assertFalse( $result['reconnected'] );
			$this->assertSame( 'reconnect_not_saved', $result['code'] );
			$this->assertSame( 500, $result['status'] );
			$this->assertSame( 'old-token', $this->stored_token() );
		}

		public function test_an_unknown_profile_is_a_404_and_nothing_is_requested() {
			$result = ReconnectHandler::handleProfileReconnect( 'threads', array( 'id' => 'ghost', 'long_lived_access_token' => 'x' ) );

			$this->assertFalse( $result['success'] );
			$this->assertSame( 'reconnect_profile_missing', $result['code'] );
			$this->assertSame( 404, $result['status'] );
			$this->assertSame( array(), HttpStore::$requests );
			$this->assertSame( 'old-token', $this->stored_token() );
		}

		public function test_the_route_answers_a_failed_save_with_500() {
			OptionStore::$failWrites = true;

			$response = $this->route( 'wpsp_update_refresh_token', array( 'platform' => 'threads', 'item' => array( 'id' => 'th-1' ) ) );

			$this->assertInstanceOf( \WP_Error::class, $response );
			$this->assertSame( 'reconnect_not_saved', $response->get_error_code() );
			$this->assertSame( array( 'status' => 500 ), $response->get_error_data() );
		}

		public function test_the_route_answers_an_unknown_profile_with_404() {
			$response = $this->route( 'wpsp_update_refresh_token', array( 'platform' => 'threads', 'item' => array( 'id' => 'ghost' ) ) );

			$this->assertInstanceOf( \WP_Error::class, $response );
			$this->assertSame( 'reconnect_profile_missing', $response->get_error_code() );
			$this->assertSame( array( 'status' => 404 ), $response->get_error_data() );
		}

		// ── Consent popup: complete-reconnect ────────────────────────────────────

		public function test_a_popup_reconnect_saves_the_token() {
			$result = ReconnectHandler::complete_reconnect( 'threads', 'th-1', array( 'id' => 'th-1', 'long_lived_access_token' => 'popup-token' ) );

			$this->assertTrue( $result['success'] );
			$this->assertSame( 'popup-token', $this->stored_token() );
		}

		public function test_a_popup_reconnect_that_cannot_save_is_a_500() {
			OptionStore::$failWrites = true;

			$result = ReconnectHandler::complete_reconnect( 'threads', 'th-1', array( 'id' => 'th-1', 'long_lived_access_token' => 'popup-token' ) );

			$this->assertFalse( $result['success'] );
			$this->assertSame( 'reconnect_not_saved', $result['code'] );
			$this->assertSame( 500, $result['status'] );
			$this->assertSame( 'old-token', $this->stored_token() );
		}
	}
}
