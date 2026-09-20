<?php
/**
 * Unit tests for the social-platform registry.
 *
 * The registry is how SchedulePress Pro adds a network without any of its code
 * living in the free plugin. Everything downstream — Share Now dispatch,
 * character limits, the settings card, the post panel — reads through these
 * methods, so a malformed definition getting through would surface as a fatal
 * somewhere far from here.
 *
 * @package WPScheduledPosts
 */

namespace WPSP\Tests\Unit;

use PHPUnit\Framework\TestCase;
use WPSP\Social\Platforms;
use WPSP\Tests\Stubs\HookStore;

class SocialPlatformsRegistryTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		HookStore::reset( 'wpsp_social_platforms' );

		if ( ! defined( 'WPSP_ASSETS_URI' ) ) {
			define( 'WPSP_ASSETS_URI', 'https://example.test/assets/' );
		}
	}

	protected function tearDown(): void {
		HookStore::reset( 'wpsp_social_platforms' );
		parent::tearDown();
	}

	private function register( array $definition, $slug = 'google_business' ) {
		add_filter(
			'wpsp_social_platforms',
			function ( $platforms ) use ( $slug, $definition ) {
				$platforms[ $slug ] = $definition;
				return $platforms;
			}
		);
	}

	private function valid_definition( array $overrides = array() ) {
		return array_merge(
			array(
				'label'      => 'Google Business Profile',
				'list_key'   => 'google_business_profile_list',
				'status_key' => 'google_business_profile_status',
				'char_limit' => 1500,
			),
			$overrides
		);
	}

	public function test_nothing_is_registered_by_default() {
		$this->assertSame( array(), Platforms::registered() );
		$this->assertSame( array(), Platforms::slugs() );
		$this->assertNull( Platforms::get( 'google_business' ) );
		$this->assertFalse( Platforms::is_registered( 'google_business' ) );
	}

	public function test_a_registered_platform_round_trips() {
		$this->register( $this->valid_definition() );

		$this->assertSame( array( 'google_business' ), Platforms::slugs() );
		$this->assertTrue( Platforms::is_registered( 'google_business' ) );
		$this->assertSame( 'google_business_profile_list', Platforms::list_keys()['google_business'] );
		$this->assertSame( 'google_business_profile_status', Platforms::status_keys()['google_business'] );
		$this->assertSame( 'Google Business Profile', Platforms::labels()['google_business'] );
		$this->assertSame( 1500, Platforms::limits()['google_business'] );
	}

	/**
	 * @dataProvider incompleteDefinitions
	 */
	public function test_an_incomplete_definition_is_dropped( $missing_key ) {
		$definition = $this->valid_definition();
		unset( $definition[ $missing_key ] );

		$this->register( $definition );

		$this->assertSame(
			array(),
			Platforms::registered(),
			"A definition without {$missing_key} must not reach any consumer."
		);
	}

	public function incompleteDefinitions() {
		return array(
			'no label'      => array( 'label' ),
			'no list key'   => array( 'list_key' ),
			'no status key' => array( 'status_key' ),
		);
	}

	public function test_a_non_array_definition_is_dropped() {
		add_filter(
			'wpsp_social_platforms',
			function ( $platforms ) {
				$platforms['broken'] = 'not-an-array';
				return $platforms;
			}
		);

		$this->assertSame( array(), Platforms::registered() );
	}

	public function test_a_filter_returning_a_non_array_is_survivable() {
		add_filter(
			'wpsp_social_platforms',
			function () {
				return null;
			}
		);

		$this->assertSame( array(), Platforms::registered() );
	}

	public function test_google_business_is_offered_for_sale_while_unregistered() {
		$locked = Platforms::locked_for_js();

		$this->assertArrayHasKey( 'google_business', Platforms::PRO_UPSELL );
		$this->assertArrayHasKey( 'google_business', $locked );
		$this->assertTrue( $locked['google_business']['locked'] );
		$this->assertNotSame( '', $locked['google_business']['label'] );
		$this->assertSame( array(), Platforms::for_js() );
	}

	public function test_registering_a_platform_retires_its_upsell_card() {
		$this->register( $this->valid_definition() );

		$this->assertSame(
			array(),
			Platforms::locked_for_js(),
			'A platform that something is serving must stop being advertised.'
		);

		$live = Platforms::for_js();
		$this->assertArrayHasKey( 'google_business', $live );
		$this->assertFalse( $live['google_business']['locked'] );
		$this->assertSame( 1500, $live['google_business']['limit'] );
	}

	public function test_a_definition_without_a_limit_reports_zero() {
		$definition = $this->valid_definition();
		unset( $definition['char_limit'] );

		$this->register( $definition );

		$this->assertSame( 0, Platforms::limits()['google_business'] );
	}
}
