<?php
/**
 * Unit tests for card 84589: a user who can edit a post but not publish it
 * (a Contributor on their own draft) must not be able to publish or schedule
 * it through the post-panel routes.
 *
 * @package WPScheduledPosts
 */

namespace WPSP\Tests\Unit;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use WPSP\API\PostPanel;
use WPSP\Tests\Stubs\CapStore;
use WPSP\Tests\Stubs\FakeRequest;
use WPSP\Tests\Stubs\MetaStore;
use WPSP\Tests\Stubs\PostStore;

class PostPanelPermissionTest extends TestCase {

	const POST_ID      = 7;
	const SAVE_HOOK    = 'schedulepress_after_free_settings_save';
	const REPUBLISH_ON = '_wpscp_schedule_republish_date';

	/** @var PostPanel */
	private $panel;

	protected function setUp(): void {
		parent::setUp();
		PostStore::reset();
		CapStore::reset();
		$date = gmdate( 'Y-m-d H:i:s' );
		PostStore::seed( self::POST_ID, 'draft', $date, $date );
		// The constructor only registers a hook; skip it rather than stub the
		// whole plugin bootstrap.
		$this->panel = ( new ReflectionClass( PostPanel::class ) )->newInstanceWithoutConstructor();
	}

	private function asContributor() {
		CapStore::grant( 'edit_post' );
	}

	private function asPublisher() {
		CapStore::grant( 'edit_post', 'publish_post' );
	}

	private function request( array $params = array() ) {
		return new FakeRequest( array_merge( array( 'post_id' => self::POST_ID ), $params ) );
	}

	private function futureDate() {
		return gmdate( 'Y-m-d\TH:i:s', time() + 86400 );
	}

	// ── update-settings routes (publish now / undo) ─────────────────────────

	public function test_publish_routes_reject_a_user_who_can_edit_but_not_publish() {
		$this->asContributor();
		$result = $this->panel->publish_permission_check( $this->request() );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertSame( 'rest_cannot_publish', $result->get_error_code() );
		$this->assertSame( array( 'status' => 403 ), $result->get_error_data() );
	}

	public function test_publish_routes_still_reject_a_user_who_cannot_edit() {
		$result = $this->panel->publish_permission_check( $this->request() );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertSame( 'rest_forbidden', $result->get_error_code() );
	}

	public function test_publish_routes_allow_a_user_who_can_publish() {
		$this->asPublisher();
		$this->assertTrue( $this->panel->publish_permission_check( $this->request() ) );
	}

	// ── post-panel save ─────────────────────────────────────────────────────

	public function test_contributor_cannot_schedule_from_the_panel() {
		$this->asContributor();
		$response = $this->panel->save_settings( $this->request( array(
			'is_scheduled'  => true,
			'schedule_date' => $this->futureDate(),
		) ) );

		$this->assertSame( 403, $response->get_status() );
		$this->assertSame( 'draft', get_post_status( self::POST_ID ) );
		$this->assertNotContains( self::SAVE_HOOK, PostStore::$firedActions, 'Pro must not get a request that was refused.' );
	}

	/**
	 * @dataProvider publishing_fields_forwarded_to_pro
	 */
	public function test_contributor_cannot_send_publishing_fields_on_to_pro( $field, $value ) {
		$this->asContributor();
		$response = $this->panel->save_settings( $this->request( array( $field => $value ) ) );

		$this->assertSame( 403, $response->get_status() );
		$this->assertNotContains( self::SAVE_HOOK, PostStore::$firedActions );
	}

	public function publishing_fields_forwarded_to_pro() {
		return array(
			'publish now'                 => array( 'publish_immediately_current_date', true ),
			'publish now, form encoded'   => array( 'publish_immediately_current_date', 'true' ),
			'publish ahead of its date'   => array( 'publish_immediately_future_date', true ),
			'a new Republish On date'     => array( 'republish_on', gmdate( 'Y-m-d\TH:i:s', time() + 3600 ) ),
		);
	}

	public function test_contributor_can_still_save_the_panel_without_publishing() {
		$this->asContributor();
		$response = $this->panel->save_settings( $this->request( array(
			'is_scheduled'  => false,
			'schedule_date' => '',
			'unpublish_on'  => '',
			'republish_on'  => '',
			'publish_immediately_current_date' => false,
		) ) );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 'draft', get_post_status( self::POST_ID ) );
		$this->assertContains( self::SAVE_HOOK, PostStore::$firedActions );
	}

	public function test_contributor_save_that_resends_an_existing_republish_date_is_not_refused() {
		// The panel sends every field on each save. A date that is already
		// stored (set by an editor) is not a new request to publish.
		$stored = gmdate( 'Y-m-d\TH:i:s', time() + 3600 );
		MetaStore::set( self::POST_ID, self::REPUBLISH_ON, $stored );
		$this->asContributor();

		$response = $this->panel->save_settings( $this->request( array( 'republish_on' => $stored ) ) );

		$this->assertSame( 200, $response->get_status() );
	}

	public function test_publisher_can_still_schedule_from_the_panel() {
		$this->asPublisher();
		$response = $this->panel->save_settings( $this->request( array(
			'is_scheduled'  => true,
			'schedule_date' => $this->futureDate(),
		) ) );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 'future', get_post_status( self::POST_ID ) );
		$this->assertContains( self::SAVE_HOOK, PostStore::$firedActions );
	}
}
