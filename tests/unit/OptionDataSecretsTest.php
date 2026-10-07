<?php
/**
 * Unit tests for Settings::public_option_data() (card 84788).
 *
 * @package WPScheduledPosts
 */

namespace WPSP\Tests\Unit;

use PHPUnit\Framework\TestCase;
use WPSP\API\Settings;

class OptionDataSecretsTest extends TestCase {

	private function settings() {
		return array(
			'openai_api_key'               => 'sk-secret',
			'allow_user_by_role'           => array( 'administrator', 'author' ),
			'notify_author_post_is_review' => true,
			'post_republish_unpublish'     => true,
			'facebook_profile_list'        => array(
				array( 'id' => 'fb1', 'name' => 'Page', 'type' => 'page', 'status' => true, 'thumbnail_url' => 'https://x/a.png', 'access_token' => 't', 'refresh_token' => 'r', 'app_id' => 'a', 'app_secret' => 's', 'expires_in' => 3600 ),
			),
			'twitter_profile_list'         => array(
				array( 'id' => 'tw1', 'name' => 'X', 'oauth_token' => 't', 'oauth_token_secret' => 's', 'app_id' => 'a', 'app_secret' => 's' ),
			),
			'linkedin_profile_list'        => array(
				array( 'id' => 'li1', 'name' => 'In', 'type' => 'organization', 'access_token' => 't', 'refresh_token_expires_in' => 1, 'client_id' => 'c', 'client_secret' => 's' ),
			),
			'pinterest_profile_list'       => array(
				array(
					'id'                            => 'pi1',
					'default_board_name'            => array( 'value' => 'b1', 'label' => 'Board' ),
					'defaultSection'                => array( 'value' => 's1', 'label' => 'Section' ),
					'pinterest_board_type'          => 'custom',
					'pinterest_custom_board_name'   => 'b1',
					'pinterest_custom_section_name' => 's1',
					'access_token'                  => 't',
					'auth'                          => array( 'refresh_token' => 'r', 'scope' => 'boards:read' ),
				),
			),
			'bluesky_profile_list'         => array(
				array( 'id' => 'bs1', 'name' => 'Sky', 'handle' => 'me.bsky.social', 'password' => 'p' ),
			),
			'mastodon_profile_list'        => array(
				array( 'id' => 'ma1', 'name' => 'Toot', 'instance_url' => 'https://m.social', 'client_id' => 'c', 'client_secret' => 's', 'access_token' => 't' ),
			),
			'google_business_profile_list' => array(
				array( 'id' => 'gb1', 'name' => 'Shop', 'status' => true, 'access_token' => 't', 'refresh_token' => 'r' ),
			),
		);
	}

	private function secret_keys( array $data, $path = '' ) {
		$found = array();
		foreach ( $data as $key => $value ) {
			if ( is_string( $key ) && preg_match( '/token|secret|password|api_?key|^(app|client)_id$/i', $key ) ) {
				$found[] = $path . $key;
			}
			if ( is_array( $value ) ) {
				$found = array_merge( $found, $this->secret_keys( $value, $path . $key . '.' ) );
			}
		}
		return $found;
	}

	public function test_returns_no_secret_at_any_depth() {
		$this->assertSame( array(), $this->secret_keys( Settings::public_option_data( $this->settings() ) ) );
	}

	public function test_returns_only_profile_lists_and_the_republish_flag() {
		$public = Settings::public_option_data( $this->settings() );

		$this->assertSame(
			array( 'post_republish_unpublish', 'facebook_profile_list', 'twitter_profile_list', 'linkedin_profile_list', 'pinterest_profile_list', 'bluesky_profile_list', 'mastodon_profile_list', 'google_business_profile_list' ),
			array_keys( $public )
		);
		$this->assertTrue( $public['post_republish_unpublish'] );
	}

	public function test_keeps_the_fields_the_editor_ui_reads() {
		$public = Settings::public_option_data( $this->settings() );

		$this->assertSame(
			array( 'id' => 'fb1', 'name' => 'Page', 'type' => 'page', 'status' => true, 'thumbnail_url' => 'https://x/a.png', 'expires_in' => 3600 ),
			$public['facebook_profile_list'][0]
		);
		$pin = $public['pinterest_profile_list'][0];
		$this->assertSame( array( 'value' => 'b1', 'label' => 'Board' ), $pin['default_board_name'] );
		$this->assertSame( array( 'value' => 's1', 'label' => 'Section' ), $pin['defaultSection'] );
		$this->assertSame( 'custom', $pin['pinterest_board_type'] );
		$this->assertSame( array( 'scope' => 'boards:read' ), $pin['auth'] );
		$this->assertSame( 'https://m.social', $public['mastodon_profile_list'][0]['instance_url'] );
		$this->assertSame( 'me.bsky.social', $public['bluesky_profile_list'][0]['handle'] );
	}

	public function test_empty_settings_give_an_empty_result() {
		$this->assertSame( array(), Settings::public_option_data( array() ) );
	}
}
