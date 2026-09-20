<?php
/**
 * Unit tests for the registry-aware lookups in ReconnectHandler.
 *
 * These used to be plain class constants. They are now merges, and the built-in
 * half has to survive that merge: a platform silently losing its profile-list
 * key stops reconnecting, which only shows up when a token expires weeks later.
 *
 * @package WPScheduledPosts
 */

namespace WPSP\Tests\Unit;

use PHPUnit\Framework\TestCase;
use WPSP\Social\ReconnectHandler;
use WPSP\Tests\Stubs\HookStore;

class ReconnectHandlerPlatformMapsTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		HookStore::reset( 'wpsp_social_platforms' );
	}

	protected function tearDown(): void {
		HookStore::reset( 'wpsp_social_platforms' );
		parent::tearDown();
	}

	private function register_extension() {
		add_filter(
			'wpsp_social_platforms',
			function ( $platforms ) {
				$platforms['google_business'] = array(
					'label'      => 'Google Business Profile',
					'list_key'   => 'google_business_profile_list',
					'status_key' => 'google_business_profile_status',
					'char_limit' => 1500,
					'reconnect'  => array(
						'lead_time'      => 43200,
						'token_endpoint' => 'https://oauth2.googleapis.com/token',
						'shared_app_id'  => 'shared-client-id',
					),
				);
				return $platforms;
			}
		);
	}

	public function test_built_in_platforms_survive_the_merge() {
		$this->register_extension();

		$options = ReconnectHandler::profile_options();

		$this->assertSame( 'facebook_profile_list', $options['facebook'] );
		$this->assertSame( 'pinterest_profile_list', $options['pinterest'] );
		$this->assertSame( 'mastodon_profile_list', $options['mastodon'] );
	}

	public function test_an_extension_contributes_its_own_reconnect_details() {
		$this->register_extension();

		$this->assertSame( 'google_business_profile_list', ReconnectHandler::profile_options()['google_business'] );
		$this->assertSame( 43200, ReconnectHandler::renew_lead_time( 'google_business' ) );
		$this->assertSame( 'https://oauth2.googleapis.com/token', ReconnectHandler::provider_token_endpoint( 'google_business' ) );
		$this->assertSame( 'shared-client-id', ReconnectHandler::shared_app_ids()['google_business'] );
	}

	public function test_a_platform_with_no_automatic_renewal_reports_null() {
		$this->assertNull(
			ReconnectHandler::renew_lead_time( 'facebook' ),
			'A Facebook Page token only dies by revocation, which needs a human.'
		);
		$this->assertNull( ReconnectHandler::renew_lead_time( 'nonsense' ) );
	}

	public function test_a_platform_with_no_own_app_endpoint_reports_empty() {
		$this->assertSame( '', ReconnectHandler::provider_token_endpoint( 'facebook' ) );
		$this->assertSame( '', ReconnectHandler::provider_token_endpoint( 'nonsense' ) );
	}

	public function test_built_in_lead_times_are_untouched_by_an_extension() {
		$this->register_extension();

		$this->assertSame( 7 * DAY_IN_SECONDS, ReconnectHandler::renew_lead_time( 'linkedin' ) );
		$this->assertSame( 10 * DAY_IN_SECONDS, ReconnectHandler::renew_lead_time( 'instagram' ) );
	}
}
