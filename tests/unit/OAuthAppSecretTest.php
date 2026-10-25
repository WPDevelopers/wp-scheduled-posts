<?php
/**
 * Unit tests for card 84907: the app secret of a connect stays on the server
 * instead of travelling in the OAuth state and callback URL.
 *
 * @package WPScheduledPosts
 */

namespace WPSP\Tests\Stubs {

	class TransientStore {
		public static $values  = array();
		public static $user_id = 1;

		public static function reset() {
			self::$values  = array();
			self::$user_id = 1;
		}
	}
}

namespace {

	use WPSP\Tests\Stubs\TransientStore;

	if ( ! function_exists( 'set_transient' ) ) {
		function set_transient( $name, $value, $expiration = 0 ) {
			TransientStore::$values[ $name ] = $value;
			return true;
		}
	}

	if ( ! function_exists( 'get_transient' ) ) {
		function get_transient( $name ) {
			return isset( TransientStore::$values[ $name ] ) ? TransientStore::$values[ $name ] : false;
		}
	}

	if ( ! function_exists( 'get_current_user_id' ) ) {
		function get_current_user_id() {
			return TransientStore::$user_id;
		}
	}
}

namespace WPSP\Tests\Unit {

	use PHPUnit\Framework\TestCase;
	use WPSP\Social\SocialProfile;
	use WPSP\Tests\Stubs\TransientStore;

	class OAuthAppSecretTest extends TestCase {

		protected function setUp(): void {
			TransientStore::reset();
		}

		public function test_the_kept_secret_comes_back_for_the_same_app() {
			SocialProfile::remember_app_secret( 'facebook', '1234', 's3cret' );

			$this->assertSame( 's3cret', SocialProfile::recall_app_secret( 'facebook', '1234' ) );
		}

		public function test_a_different_app_id_gets_nothing() {
			SocialProfile::remember_app_secret( 'facebook', '1234', 's3cret' );

			$this->assertSame( '', SocialProfile::recall_app_secret( 'facebook', '9999' ) );
		}

		public function test_secrets_are_kept_per_network() {
			SocialProfile::remember_app_secret( 'facebook', '1234', 'fb-secret' );
			SocialProfile::remember_app_secret( 'instagram', '1234', 'ig-secret' );

			$this->assertSame( 'fb-secret', SocialProfile::recall_app_secret( 'facebook', '1234' ) );
			$this->assertSame( 'ig-secret', SocialProfile::recall_app_secret( 'instagram', '1234' ) );
		}

		public function test_secrets_are_kept_per_user() {
			SocialProfile::remember_app_secret( 'facebook', '1234', 's3cret' );

			TransientStore::$user_id = 2;

			$this->assertSame( '', SocialProfile::recall_app_secret( 'facebook', '1234' ) );
		}

		public function test_an_empty_secret_is_not_kept() {
			SocialProfile::remember_app_secret( 'facebook', '1234', 's3cret' );
			SocialProfile::remember_app_secret( 'facebook', '1234', '' );

			$this->assertSame( 's3cret', SocialProfile::recall_app_secret( 'facebook', '1234' ) );
		}

		public function test_nothing_kept_gives_an_empty_secret() {
			$this->assertSame( '', SocialProfile::recall_app_secret( 'linkedin', '' ) );
		}
	}
}
