<?php
/**
 * Unit tests for the get-option-data route handler (card 84788).
 *
 * @package WPScheduledPosts
 */

namespace {

	// Kept here, not in the shared stubs file, so open card branches don't conflict.
	if ( ! function_exists( 'rest_ensure_response' ) ) {
		function rest_ensure_response( $response ) {
			return $response instanceof WP_REST_Response ? $response : new WP_REST_Response( $response );
		}
	}

	if ( ! function_exists( 'is_super_admin' ) ) {
		function is_super_admin( $user_id = false ) {
			return false;
		}
	}
}

namespace WPSP\Tests\Unit {

	use PHPUnit\Framework\TestCase;
	use WPSP\API\Settings;
	use WPSP\Tests\Stubs\OptionStore;

	class OptionDataRouteTest extends TestCase {

		const OPTION_NAME = 'wpsp_settings_v5';

		public static function setUpBeforeClass(): void {
			parent::setUpBeforeClass();
			if ( ! defined( 'WPSP_SETTINGS_NAME' ) ) {
				define( 'WPSP_SETTINGS_NAME', self::OPTION_NAME );
			}
		}

		protected function setUp(): void {
			parent::setUp();
			OptionStore::reset();
			OptionStore::seed( self::OPTION_NAME, json_encode( array(
				'allow_user_by_role'     => array( 'administrator', 'author' ),
				'openai_api_key'         => 'sk-secret',
				'facebook_profile_list'  => array(
					array( 'id' => 'fb1', 'name' => 'Page', 'status' => true, 'access_token' => 't', 'app_id' => 'a', 'app_secret' => 's' ),
				),
				'pinterest_profile_list' => array(
					array( 'id' => 'pi1', 'name' => 'Pin', 'status' => true, 'access_token' => 't', 'auth' => array( 'refresh_token' => 'r' ) ),
				),
			) ) );
		}

		protected function tearDown(): void {
			unset( $GLOBALS['current_user'] );
			parent::tearDown();
		}

		private function call_as( ...$roles ) {
			$GLOBALS['current_user'] = (object) array( 'ID' => 7, 'roles' => $roles );
			return Settings::get_instance()->wpsp_get_options_data( new \WP_REST_Request() );
		}

		public function test_allowed_author_gets_profiles_without_secrets() {
			$response = $this->call_as( 'author' );
			$json     = $response->get_data();
			$data     = json_decode( $json, true );

			$this->assertSame( 'Page', $data['facebook_profile_list'][0]['name'] );
			$this->assertSame( 'Pin', $data['pinterest_profile_list'][0]['name'] );
			$this->assertArrayNotHasKey( 'openai_api_key', $data );
			$this->assertArrayNotHasKey( 'allow_user_by_role', $data );
			$this->assertSame( 0, preg_match( '/token|secret|app_id|sk-secret/', $json ), $json );
		}

		public function test_role_outside_allow_list_is_refused() {
			$response = $this->call_as( 'editor' );

			$this->assertInstanceOf( \WP_Error::class, $response );
			$this->assertSame( array( 'status' => 401 ), $response->get_error_data() );
		}
	}
}
