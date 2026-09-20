<?php
/**
 * Integration test: Google Business is gone, and what replaces it is an upsell.
 *
 * The removal is easy to half-undo — a stray `use`, a constant someone puts
 * back, a settings field quietly reverted to the real card. These assertions
 * are what keep a Pro feature from drifting back into the free plugin.
 *
 * @package WPScheduledPosts
 */

namespace WPSP\Tests\Integration;

use WP_UnitTestCase;
use WPSP\Social\Platforms;

class GoogleBusinessRemovalTest extends WP_UnitTestCase {

	public function test_the_engine_class_is_not_shipped() {
		$this->assertFalse(
			class_exists( '\WPSP\Social\GoogleBusiness' ),
			'The Google Business engine belongs to SchedulePress Pro.'
		);
	}

	public function test_its_constants_are_not_defined() {
		foreach ( array(
			'WPSCP_GOOGLE_BUSINESS_OPTION_NAME',
			'WPSCP_GOOGLE_BUSINESS_SCOPE',
			'WPSP_SOCIAL_OAUTH2_GOOGLE_BUSINESS_APP_ID',
		) as $const ) {
			$this->assertFalse( defined( $const ), "{$const} should have left with the engine." );
		}
	}

	public function test_no_token_refresh_listener_is_registered() {
		$this->assertFalse(
			has_action( 'wpsp_google_business_token_refresh' ),
			'Nothing here can refresh a Google token, so nothing should claim to.'
		);
	}

	public function test_the_platform_is_offered_for_sale() {
		$locked = Platforms::locked_for_js();

		$this->assertArrayHasKey( 'google_business', $locked );
		$this->assertTrue( $locked['google_business']['locked'] );
		$this->assertNotEmpty( $locked['google_business']['label'] );
		$this->assertArrayNotHasKey( 'google_business', Platforms::for_js() );
	}

	public function test_the_settings_screen_shows_a_locked_card_and_tab() {
		$settings = ( new \WPSP\Admin\Settings( WPSP_SETTINGS_SLUG, WPSP_SETTINGS_NAME ) )->get_settings_array();

		$card = $this->find_node( $settings, 'google_business_profile_list' );
		$this->assertNotNull( $card, 'The Social Profile tab should still list Google Business.' );
		$this->assertSame( 'pro-social-platform', $card['type'] );
		$this->assertTrue( ! empty( $card['is_pro'] ) );

		$tab = $this->find_node( $settings, 'layouts_google_business' );
		$this->assertNotNull( $tab, 'The Social Templates tab should still list Google Business.' );
		$this->assertTrue( ! empty( $tab['is_pro'] ) );
	}

	/**
	 * First node in the settings tree with this `name`.
	 *
	 * @param array  $node
	 * @param string $name
	 * @return array|null
	 */
	private function find_node( $node, $name ) {
		if ( ! is_array( $node ) ) {
			return null;
		}

		if ( isset( $node['name'] ) && $node['name'] === $name ) {
			return $node;
		}

		foreach ( $node as $child ) {
			$found = $this->find_node( $child, $name );
			if ( null !== $found ) {
				return $found;
			}
		}

		return null;
	}
}
