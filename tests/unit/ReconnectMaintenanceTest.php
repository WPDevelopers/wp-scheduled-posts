<?php
/**
 * Unit tests for ReconnectHandler::run_maintenance() (card 84590): boards of
 * one Pinterest account share a token, so the daily pass renews it once.
 *
 * @package WPScheduledPosts
 */

namespace WPSP\Social;

use WPSP\Tests\Unit\FakeTokenServer;

// Test-local HTTP, resolved before the global functions.
function wp_safe_remote_post( $url, $args = array() ) {
	return FakeTokenServer::handle( $args['body']['refresh_token'] );
}

function wp_remote_retrieve_response_code( $response ) {
	return $response['response']['code'];
}

function wp_remote_retrieve_body( $response ) {
	return $response['body'];
}

namespace WPSP\Tests\Unit;

use PHPUnit\Framework\TestCase;
use WPSP\Social\ReconnectHandler;
use WPSP\Tests\Stubs\OptionStore;

if ( ! defined( 'DAY_IN_SECONDS' ) ) {
	define( 'DAY_IN_SECONDS', 86400 );
}
if ( ! defined( 'HOUR_IN_SECONDS' ) ) {
	define( 'HOUR_IN_SECONDS', 3600 );
}
if ( ! defined( 'WPSP_SOCIAL_OAUTH2_TOKEN_MIDDLEWARE_DEV' ) ) {
	define( 'WPSP_SOCIAL_OAUTH2_TOKEN_MIDDLEWARE_DEV', 'https://middleware.example/callback.php' );
}

/** Fake token service: like Pinterest, each refresh token works once. */
class FakeTokenServer {

	/** @var array<string,string> refresh token => account it belongs to */
	public static $live = array();

	/** @var string[] refresh tokens received, in order */
	public static $requests = array();

	public static function reset() {
		self::$live     = array();
		self::$requests = array();
	}

	public static function handle( $refresh_token ) {
		self::$requests[] = $refresh_token;

		if ( ! isset( self::$live[ $refresh_token ] ) ) {
			return array( 'response' => array( 'code' => 400 ), 'body' => json_encode( array( 'error' => 'invalid_grant' ) ) );
		}

		$account = self::$live[ $refresh_token ];
		unset( self::$live[ $refresh_token ] );
		$next                = $account . '-refresh-' . count( self::$requests );
		self::$live[ $next ] = $account;

		return array(
			'response' => array( 'code' => 200 ),
			'body'     => json_encode( array(
				'access_token'  => $account . '-access-' . count( self::$requests ),
				'refresh_token' => $next,
				'expires_in'    => 30 * DAY_IN_SECONDS,
			) ),
		);
	}
}

class ReconnectMaintenanceTest extends TestCase {

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
		FakeTokenServer::reset();
	}

	/** One stored Pinterest board, due for renewal (expires inside the 5-day lead time). */
	private function board( $account, $board, $refresh_token ) {
		return array(
			'id'                => $account,
			'name'              => $account,
			'default_board_name' => array( 'label' => $board, 'value' => $board ),
			'status'            => true,
			'access_token'      => $account . '-access-0',
			'refresh_token'     => $refresh_token,
			'expires_in'        => time() + DAY_IN_SECONDS,
			'redirectURI'       => WPSP_SOCIAL_OAUTH2_TOKEN_MIDDLEWARE_DEV,
		);
	}

	private function seed( $list_key, array $profiles ) {
		OptionStore::seed( self::OPTION_NAME, json_encode( array( $list_key => $profiles ) ) );
	}

	private function stored( $list_key ) {
		$settings = json_decode( OptionStore::get( self::OPTION_NAME ), true );
		return $settings[ $list_key ];
	}

	public function test_two_boards_of_one_account_are_renewed_once_and_both_stay_healthy() {
		FakeTokenServer::$live = array( 'acme-refresh-0' => 'acme' );
		$this->seed( 'pinterest_profile_list', array(
			$this->board( 'acme', 'Recipes', 'acme-refresh-0' ),
			$this->board( 'acme', 'Travel', 'acme-refresh-0' ),
		) );

		$report = ReconnectHandler::run_maintenance();

		foreach ( $this->stored( 'pinterest_profile_list' ) as $board ) {
			$this->assertFalse( $board['renewal_failed'], $board['default_board_name']['value'] . ' was renewed but is flagged as needing a reconnect.' );
			$this->assertSame( 'acme-refresh-1', $board['refresh_token'] );
		}
		$this->assertSame( array( 'acme-refresh-0' ), FakeTokenServer::$requests, 'The rotated refresh token must not be sent a second time.' );
		$this->assertSame( 1, $report['renewed'] );
		$this->assertSame( 0, $report['failed'] );
	}

	public function test_a_dead_grant_flags_every_board_of_the_account_with_one_request() {
		FakeTokenServer::$live = array(); // the provider no longer knows this token
		$this->seed( 'pinterest_profile_list', array(
			$this->board( 'acme', 'Recipes', 'acme-refresh-0' ),
			$this->board( 'acme', 'Travel', 'acme-refresh-0' ),
		) );

		$report = ReconnectHandler::run_maintenance();

		$this->assertCount( 1, FakeTokenServer::$requests );
		foreach ( $this->stored( 'pinterest_profile_list' ) as $board ) {
			$this->assertTrue( $board['renewal_failed'] );
		}
		$this->assertSame( 1, $report['failed'] );
	}

	public function test_boards_of_different_accounts_are_each_renewed() {
		FakeTokenServer::$live = array( 'acme-refresh-0' => 'acme', 'beta-refresh-0' => 'beta' );
		$this->seed( 'pinterest_profile_list', array(
			$this->board( 'acme', 'Recipes', 'acme-refresh-0' ),
			$this->board( 'beta', 'Garden', 'beta-refresh-0' ),
			$this->board( 'acme', 'Travel', 'acme-refresh-0' ),
		) );

		$report = ReconnectHandler::run_maintenance();

		$this->assertSame( array( 'acme-refresh-0', 'beta-refresh-0' ), FakeTokenServer::$requests );
		$this->assertSame( 2, $report['renewed'] );
		foreach ( $this->stored( 'pinterest_profile_list' ) as $board ) {
			$this->assertFalse( $board['renewal_failed'] );
		}
	}

	public function test_entries_with_their_own_internal_id_are_renewed_separately() {
		// Entries with their own __id are renewed one by one.
		FakeTokenServer::$live = array( 'li-a' => 'li', 'li-b' => 'li' );
		$a        = $this->board( 'li', 'n/a', 'li-a' );
		$a['__id'] = 1001;
		$b        = $this->board( 'li', 'n/a', 'li-b' );
		$b['__id'] = 1002;
		$this->seed( 'linkedin_profile_list', array( $a, $b ) );

		$report = ReconnectHandler::run_maintenance();

		$this->assertSame( array( 'li-a', 'li-b' ), FakeTokenServer::$requests );
		$this->assertSame( 2, $report['renewed'] );
	}
}
