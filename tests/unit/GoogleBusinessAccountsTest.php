<?php
/**
 * Unit tests for card 84745: listing Google Business Profile accounts and
 * locations, business groups included.
 *
 * @package WPScheduledPosts
 */

namespace WPSP\Tests\Unit;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use WPSP\Social\SocialProfile;
use WPSP\Tests\Stubs\HttpStore;

class GoogleBusinessAccountsTest extends TestCase {

	const ACCOUNTS     = 'https://mybusinessaccountmanagement.googleapis.com/v1/accounts';
	const OLD_ACCOUNTS = 'https://mybusinessbusinessinformation.googleapis.com/v1/accounts?';
	const LOCATIONS    = 'https://mybusinessbusinessinformation.googleapis.com/v1/accounts/222/locations';

	/** @var SocialProfile */
	private $profile;

	protected function setUp(): void {
		parent::setUp();
		HttpStore::reset();
		// The constructor only registers hooks.
		$this->profile = ( new ReflectionClass( SocialProfile::class ) )->newInstanceWithoutConstructor();
	}

	private function accounts() {
		$method = new \ReflectionMethod( SocialProfile::class, 'fetchGoogleAccounts' );
		$method->setAccessible( true );
		return $method->invoke( $this->profile, 'token' );
	}

	private function names( $result ) {
		return array_map( function ( $a ) { return $a->name; }, $result['data']->accounts );
	}

	public function test_lists_the_business_group_as_well_as_the_personal_account() {
		HttpStore::on( self::ACCOUNTS, array( HttpStore::json( 200, array( 'accounts' => array(
			array( 'name' => 'accounts/111', 'type' => 'PERSONAL' ),
			array( 'name' => 'accounts/222', 'type' => 'LOCATION_GROUP', 'accountName' => 'E design Business Group' ),
		) ) ) ) );

		$result = $this->accounts();

		$this->assertFalse( $result['error'] );
		$this->assertSame( array( 'accounts/111', 'accounts/222' ), $this->names( $result ) );
		$this->assertCount( 1, HttpStore::$requests, 'The old endpoint is not needed when the documented one works.' );
	}

	public function test_follows_the_next_page_of_accounts() {
		HttpStore::on( self::ACCOUNTS, array(
			HttpStore::json( 200, array( 'accounts' => array( array( 'name' => 'accounts/1' ) ), 'nextPageToken' => 'p2' ) ),
			HttpStore::json( 200, array( 'accounts' => array( array( 'name' => 'accounts/2' ) ) ) ),
		) );

		$this->assertSame( array( 'accounts/1', 'accounts/2' ), $this->names( $this->accounts() ) );
		$this->assertStringContainsString( 'pageToken=p2', HttpStore::$requests[1] );
	}

	public function test_falls_back_to_the_old_endpoint_when_the_documented_one_fails() {
		HttpStore::on( self::ACCOUNTS, array( HttpStore::json( 403, array( 'error' => array( 'message' => 'API not enabled' ) ) ) ) );
		HttpStore::on( self::OLD_ACCOUNTS, array( HttpStore::json( 200, array( 'accounts' => array( array( 'name' => 'accounts/9' ) ) ) ) ) );

		$result = $this->accounts();

		$this->assertFalse( $result['error'] );
		$this->assertSame( array( 'accounts/9' ), $this->names( $result ) );
	}

	public function test_reports_the_documented_endpoints_error_when_both_fail() {
		HttpStore::on( self::ACCOUNTS, array( HttpStore::json( 403, array( 'error' => array( 'message' => 'API not enabled' ) ) ) ) );
		HttpStore::on( self::OLD_ACCOUNTS, array( HttpStore::json( 404, array( 'error' => array( 'message' => 'Not found' ) ) ) ) );

		$result = $this->accounts();

		$this->assertTrue( $result['error'] );
		$this->assertSame( 'API not enabled', $result['message'] );
	}

	public function test_collects_every_page_of_locations() {
		HttpStore::on( self::LOCATIONS, array(
			HttpStore::json( 200, array( 'locations' => array( array( 'name' => 'locations/1', 'title' => 'Shop A' ) ), 'nextPageToken' => 'n2' ) ),
			HttpStore::json( 200, array( 'locations' => array( array( 'name' => 'locations/2', 'title' => 'Shop B' ) ) ) ),
		) );
		$error = '';

		$locations = $this->profile->fetchLocations( 'token', 'accounts/222', $error );

		$this->assertSame( array( 'Shop A', 'Shop B' ), array_column( $locations, 'title' ) );
		$this->assertSame( '', $error );
	}

	public function test_a_failed_location_request_reports_googles_message() {
		HttpStore::on( self::LOCATIONS, array( HttpStore::json( 403, array( 'error' => array( 'message' => 'The caller does not have permission' ) ) ) ) );
		$error = '';

		$locations = $this->profile->fetchLocations( 'token', 'accounts/222', $error );

		$this->assertSame( array(), $locations );
		$this->assertSame( 'The caller does not have permission', $error );
	}
}
