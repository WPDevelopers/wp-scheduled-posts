<?php
/**
 * Unit tests for Settings::merge_settings() (card 84789): a settings save merges
 * the form's fields into the stored option and never overwrites a profile's
 * stored credentials, which reconnect and the renewal cron keep current.
 *
 * @package WPScheduledPosts
 */

namespace WPSP\Tests\Unit;

use PHPUnit\Framework\TestCase;
use WPSP\API\Settings;

class SettingsMergeTest extends TestCase {

	private function stored() {
		return array(
			'adminbar_list_structure_title_length' => '45',
			'pro_only_key'                         => 'kept',
			'mastodon_profile_list'                => array(
				array( 'id' => 'ma1', 'name' => 'Toot', 'status' => true, 'access_token' => 'new' ),
			),
			'facebook_profile_list'                => array(
				array( 'id' => 'fb1', 'name' => 'Page', 'status' => true, 'access_token' => 'renewed', 'expires_in' => 2000, 'renewal_failed' => false, 'app_id' => 'a', 'app_secret' => 's' ),
				array( 'id' => 'fb2', 'name' => 'Other', 'status' => true, 'access_token' => 't2' ),
			),
		);
	}

	public function test_keys_the_form_did_not_send_are_kept() {
		$merged = Settings::merge_settings( $this->stored(), array( 'adminbar_list_structure_title_length' => '46' ) );

		$this->assertSame( '46', $merged['adminbar_list_structure_title_length'] );
		$this->assertSame( 'kept', $merged['pro_only_key'] );
		$this->assertSame( $this->stored()['mastodon_profile_list'], $merged['mastodon_profile_list'] );
	}

	public function test_a_profile_keeps_its_stored_credentials_and_takes_the_form_s_other_fields() {
		$form = array(
			array( 'id' => 'fb1', 'name' => 'Page', 'status' => false, 'access_token' => 'stale', 'expires_in' => 1000, 'renewal_failed' => true, 'refresh_token' => 'stale' ),
			array( 'id' => 'fb2', 'name' => 'Other', 'status' => true, 'access_token' => 't2' ),
		);

		$merged = Settings::merge_settings( $this->stored(), array( 'facebook_profile_list' => $form ) );
		$fb1    = $merged['facebook_profile_list'][0];

		$this->assertFalse( $fb1['status'] );
		$this->assertSame( 'renewed', $fb1['access_token'] );
		$this->assertSame( 2000, $fb1['expires_in'] );
		$this->assertFalse( $fb1['renewal_failed'] );
		$this->assertSame( array( 'a', 's' ), array( $fb1['app_id'], $fb1['app_secret'] ) );
		$this->assertArrayNotHasKey( 'refresh_token', $fb1, 'a credential the stored profile does not have is not added back' );
	}

	public function test_deleting_a_profile_in_the_form_still_removes_it() {
		$form = array( array( 'id' => 'fb2', 'name' => 'Other', 'status' => true, 'access_token' => 't2' ) );

		$merged = Settings::merge_settings( $this->stored(), array( 'facebook_profile_list' => $form ) );

		$this->assertSame( array( 'fb2' ), array_column( $merged['facebook_profile_list'], 'id' ) );
	}

	public function test_a_profile_the_server_does_not_have_is_taken_as_sent() {
		$new  = array( 'id' => 'ma2', 'name' => 'New', 'status' => true, 'access_token' => 'fresh' );
		$form = array_merge( $this->stored()['mastodon_profile_list'], array( $new ) );

		$merged = Settings::merge_settings( $this->stored(), array( 'mastodon_profile_list' => $form ) );

		$this->assertSame( $new, $merged['mastodon_profile_list'][1] );
	}

	public function test_empty_stored_settings_take_the_form_as_sent() {
		$this->assertSame( array( 'a' => 1 ), Settings::merge_settings( array(), array( 'a' => 1 ) ) );
	}
}
