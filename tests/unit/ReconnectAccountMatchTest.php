<?php
/**
 * Unit tests for the account matching behind a reconnect.
 *
 * Pure logic — no WordPress required, so this runs in the `unit` suite.
 *
 * @package WPScheduledPosts
 */

namespace WPSP\Tests\Unit;

use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use WPSP\Social\ReconnectHandler;

class ReconnectAccountMatchTest extends TestCase {

	/**
	 * @param mixed  $payload
	 * @param string $profile_id
	 * @return array|null
	 */
	private function match( $payload, $profile_id ) {
		$method = new ReflectionMethod( ReconnectHandler::class, 'find_reauthorised_account' );
		$method->setAccessible( true );

		return $method->invoke( null, $payload, $profile_id );
	}

	/**
	 * LinkedIn returns one set of credentials for the whole authorisation and
	 * lists the member and their company pages underneath it, so the account
	 * that matches carries no token of its own.
	 */
	public function test_linkedin_account_inherits_the_authorisation_credentials() {
		$payload = array(
			'success'  => true,
			'type'     => 'linkedin',
			'linkedin' => array(
				'access_token'  => 'linkedin-access',
				'refresh_token' => 'linkedin-refresh',
				'expires_in'    => 1799999999,
				'profiles'      => array(
					array( array( 'id' => 'person-1', 'name' => 'A Member' ) ),
				),
				'pages'         => array(
					array( 'id' => 'org-9', 'name' => 'A Company' ),
				),
			),
		);

		$member = $this->match( $payload, 'person-1' );
		$this->assertIsArray( $member );
		$this->assertSame( 'A Member', $member['name'] );
		$this->assertSame( 'linkedin-access', $member['access_token'] );
		$this->assertSame( 'linkedin-refresh', $member['refresh_token'] );
		$this->assertSame( 1799999999, $member['expires_in'] );

		$page = $this->match( $payload, 'org-9' );
		$this->assertIsArray( $page );
		$this->assertSame( 'A Company', $page['name'] );
		$this->assertSame( 'linkedin-access', $page['access_token'] );
	}

	/**
	 * Facebook gives every page its own token, and that one must win over the
	 * user token sitting further up the response.
	 */
	public function test_account_keeps_its_own_credentials() {
		$payload = array(
			'success'      => true,
			'type'         => 'facebook',
			'access_token' => 'user-token',
			'page'         => array(
				array( 'id' => 'page-1', 'name' => 'Page One', 'access_token' => 'page-one-token' ),
				array( 'id' => 'page-2', 'name' => 'Page Two', 'access_token' => 'page-two-token' ),
			),
		);

		$match = $this->match( $payload, 'page-2' );
		$this->assertIsArray( $match );
		$this->assertSame( 'page-two-token', $match['access_token'] );
	}

	/**
	 * An account that came back with an empty token field still gets the one the
	 * authorisation returned, rather than being left with nothing usable.
	 */
	public function test_empty_account_credentials_fall_back_to_the_inherited_ones() {
		$payload = array(
			'data' => array(
				'refresh_token' => 'shared-refresh',
				'profiles'      => array(
					array( 'id' => 'p-1', 'refresh_token' => '', 'access_token' => 'own-access' ),
				),
			),
		);

		$match = $this->match( $payload, 'p-1' );
		$this->assertSame( 'own-access', $match['access_token'] );
		$this->assertSame( 'shared-refresh', $match['refresh_token'] );
	}

	/** Credentials from a sibling branch must not leak onto the match. */
	public function test_sibling_credentials_are_not_inherited() {
		$payload = array(
			'other'    => array( 'access_token' => 'not-mine' ),
			'profiles' => array(
				array( 'id' => 'p-1', 'name' => 'Mine' ),
			),
		);

		$match = $this->match( $payload, 'p-1' );
		$this->assertIsArray( $match );
		$this->assertArrayNotHasKey( 'access_token', $match );
	}

	public function test_returns_null_when_the_author_authorised_another_account() {
		$payload = array(
			'profiles' => array(
				array( 'id' => 'someone-else', 'access_token' => 'token' ),
			),
		);

		$this->assertNull( $this->match( $payload, 'p-1' ) );
	}

	/** The fetch step hands back objects as often as arrays. */
	public function test_walks_objects_as_well_as_arrays() {
		$payload = (object) array(
			'linkedin' => (object) array(
				'access_token' => 'object-access',
				'profiles'     => array( (object) array( 'id' => 'person-1' ) ),
			),
		);

		$match = $this->match( $payload, 'person-1' );
		$this->assertIsArray( $match );
		$this->assertSame( 'object-access', $match['access_token'] );
	}
}
