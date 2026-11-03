<?php
/**
 * Unit tests for card 84789 (QA follow-up): when a fresh authorisation adds a
 * LinkedIn page or a Pinterest board, the settings screen has the server renew
 * the stored profile it belongs to through complete_reconnect() before the
 * settings save. That save keeps stored credentials, so the renewal must land
 * first and carry the whole grant.
 *
 * @package WPScheduledPosts
 */

namespace WPSP\Tests\Unit;

use PHPUnit\Framework\TestCase;
use WPSP\API\Settings;
use WPSP\Social\ReconnectHandler;
use WPSP\Tests\Stubs\OptionStore;

class FreshGrantRenewTest extends TestCase {

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
	}

	private function seed( array $settings ) {
		OptionStore::seed( self::OPTION_NAME, json_encode( $settings ) );
	}

	private function stored() {
		return json_decode( OptionStore::get( self::OPTION_NAME ), true );
	}

	private function person( $token, $refresh, $exp, $failed ) {
		return array( 'id' => 'li-person', 'type' => 'person', 'name' => 'Person', 'status' => true, 'access_token' => $token, 'refresh_token' => $refresh, 'expires_in' => $exp, 'rt_expires_in' => $exp + 100, 'renewal_failed' => $failed );
	}

	/** What the LinkedIn fetch step returns for a page authorisation: one grant, the member, their pages. */
	private function linkedin_grant( array $pages ) {
		return array(
			'__id'          => 1,
			'access_token'  => 'NEW',
			'refresh_token' => 'RT-NEW',
			'expires_in'    => 3000,
			'rt_expires_in' => 3100,
			'profiles'      => array( array( 'id' => 'li-person', 'name' => 'Person' ) ),
			'pages'         => $pages,
		);
	}

	public function test_a_page_grant_renews_the_stored_person_with_the_whole_grant() {
		$this->seed( array( 'linkedin_profile_list' => array( $this->person( 'OLD', 'RT-OLD', 1000, true ) ) ) );

		$result = ReconnectHandler::complete_reconnect( 'linkedin', 'li-person', $this->linkedin_grant( array( array( 'id' => 'li-page', 'profile_id' => 'li-person' ) ) ) );

		$this->assertTrue( $result['success'] );
		$person = $this->stored()['linkedin_profile_list'][0];
		$this->assertSame( array( 'NEW', 'RT-NEW', 3000, 3100, false ), array( $person['access_token'], $person['refresh_token'], $person['expires_in'], $person['rt_expires_in'], $person['renewal_failed'] ) );
		$this->assertSame( 'Person', $person['name'] );
	}

	public function test_the_settings_save_after_it_keeps_the_renewed_person_and_adds_the_page() {
		$this->seed( array( 'linkedin_profile_list' => array( $this->person( 'OLD', 'RT-OLD', 1000, true ) ) ) );
		ReconnectHandler::complete_reconnect( 'linkedin', 'li-person', $this->linkedin_grant( array( array( 'id' => 'li-page', 'profile_id' => 'li-person' ) ) ) );

		// What the modal sends: the person as loaded with only access_token copied, and the new page.
		$tab_person = array_merge( $this->person( 'OLD', 'RT-OLD', 1000, false ), array( 'access_token' => 'NEW' ) );
		$page       = array( 'id' => 'li-page', 'profile_id' => 'li-person', 'name' => 'Page', 'status' => true, 'access_token' => 'NEW', 'refresh_token' => 'RT-NEW', 'expires_in' => 3000 );
		$merged     = Settings::merge_settings( $this->stored(), array( 'linkedin_profile_list' => array( $tab_person, $page ) ) );

		$person = $merged['linkedin_profile_list'][0];
		$this->assertSame( array( 'NEW', 'RT-NEW', 3000, false ), array( $person['access_token'], $person['refresh_token'], $person['expires_in'], $person['renewal_failed'] ) );
		$this->assertSame( $page, $merged['linkedin_profile_list'][1] );
	}

	public function test_a_member_only_grant_leaves_a_stored_page_alone() {
		$page = array( 'id' => 'li-page', 'profile_id' => 'li-person', 'access_token' => 'ORG', 'renewal_failed' => false );
		$this->seed( array( 'linkedin_profile_list' => array( $page ) ) );

		$result = ReconnectHandler::complete_reconnect( 'linkedin', 'li-page', $this->linkedin_grant( array() ) );

		$this->assertSame( 409, $result['status'] );
		$this->assertSame( array( $page ), $this->stored()['linkedin_profile_list'] );
	}

	public function test_a_pinterest_grant_renews_every_board_of_the_account_and_the_new_board_keeps_it() {
		$board = function ( $value, $token, $failed ) {
			return array( 'id' => 'user', 'default_board_name' => array( 'value' => $value ), 'access_token' => $token, 'refresh_token' => 'R-' . $token, 'renewal_failed' => $failed );
		};
		$this->seed( array( 'pinterest_profile_list' => array( $board( 'b1', 'T1', true ), $board( 'b2', 'T1', true ) ) ) );

		$result = ReconnectHandler::complete_reconnect( 'pinterest', 'user', array( 'id' => 'user', 'access_token' => 'T2', 'refresh_token' => 'R-T2', 'expires_in' => 5000 ) );
		$this->assertTrue( $result['success'] );

		// The modal then saves the two stored boards as loaded plus a new board from the fresh grant.
		$merged = Settings::merge_settings( $this->stored(), array( 'pinterest_profile_list' => array( $board( 'b1', 'T1', true ), $board( 'b2', 'T1', true ), $board( 'b3', 'T2', false ) ) ) );

		foreach ( $merged['pinterest_profile_list'] as $saved ) {
			$this->assertSame( array( 'T2', 'R-T2', false ), array( $saved['access_token'], $saved['refresh_token'], $saved['renewal_failed'] ), $saved['default_board_name']['value'] );
		}
	}
}
