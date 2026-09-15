<?php
/**
 * Unit tests for the post-panel publish and clear endpoints.
 *
 * Covers issue #111 and the two follow-up findings against its fix:
 *   - publish_immediately() must not answer "published successfully" when no
 *     action ran or the write failed;
 *   - clear_publish_immediately() must require an active intent before it
 *     reschedules anything, and must not leave a half-applied state behind.
 *
 * @package WPScheduledPosts
 */

namespace WPSP\API;

use WPSP\Tests\Stubs\MetaStore;

/**
 * Test-local override so rollback write failures can be exercised without
 * changing the shared WordPress stubs used by other unit tests.
 */
function update_post_meta( $post_id, $key, $value ) {
	\WPSP\Tests\Unit\PostPanelPublishTest::$lastMetaUpdateValue = $value;
	if ( \WPSP\Tests\Unit\PostPanelPublishTest::$failMetaUpdate ) {
		return false;
	}
	if ( MetaStore::get( (int) $post_id, $key ) === $value ) {
		return false;
	}

	MetaStore::set( (int) $post_id, $key, $value );
	return true;
}

namespace WPSP\Tests\Unit;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use WPSP\API\PostPanel;
use WPSP\Tests\Stubs\FakeRequest;
use WPSP\Tests\Stubs\MetaStore;
use WPSP\Tests\Stubs\PostStore;

class PostPanelPublishTest extends TestCase {

	const POST_ID  = 7;
	const META_KEY = 'prevent_future_post';

	/** @var bool Make the test-local update_post_meta() override fail. */
	public static $failMetaUpdate = false;

	/** @var mixed Exact value most recently passed to update_post_meta(). */
	public static $lastMetaUpdateValue;

	/** @var PostPanel */
	private $panel;

	protected function setUp(): void {
		parent::setUp();
		PostStore::reset();
		self::$failMetaUpdate       = false;
		self::$lastMetaUpdateValue = null;
		// The constructor only registers a hook; skip it rather than stub the
		// whole plugin bootstrap.
		$this->panel = ( new ReflectionClass( PostPanel::class ) )->newInstanceWithoutConstructor();
	}

	private function futureDate() {
		return gmdate( 'Y-m-d H:i:s', time() + 86400 );
	}

	private function pastDate() {
		return gmdate( 'Y-m-d H:i:s', time() - 86400 );
	}

	private function seedFuturePost() {
		$date = $this->futureDate();
		PostStore::seed( self::POST_ID, 'future', $date, $date );
		return $date;
	}

	private function publish( array $params ) {
		return $this->panel->publish_immediately(
			new FakeRequest( array_merge( array( 'post_id' => self::POST_ID ), $params ) )
		);
	}

	private function clear() {
		return $this->panel->clear_publish_immediately(
			new FakeRequest( array( 'post_id' => self::POST_ID ) )
		);
	}

	// ── publish_immediately: false success ──────────────────────────────────

	public function test_no_action_flag_is_a_bad_request() {
		$this->seedFuturePost();

		$response = $this->publish( array() );

		$this->assertSame( 400, $response->get_status() );
		$this->assertFalse( $response->get_data()['success'] );
	}

	public function test_no_action_flag_leaves_the_post_untouched() {
		$this->seedFuturePost();

		$this->publish( array() );

		$this->assertSame( 'future', PostStore::get( self::POST_ID )->post_status );
	}

	public function test_both_action_flags_is_a_bad_request() {
		$this->seedFuturePost();

		$response = $this->publish( array(
			'publish_immediately_current_date' => true,
			'publish_immediately_future_date'  => true,
		) );

		$this->assertSame( 400, $response->get_status() );
	}

	public function test_missing_post_is_a_404() {
		$response = $this->publish( array( 'publish_immediately_current_date' => true ) );

		$this->assertSame( 404, $response->get_status() );
	}

	public function test_future_action_on_a_past_dated_post_is_a_bad_request() {
		$date = $this->pastDate();
		PostStore::seed( self::POST_ID, 'draft', $date, $date );

		$response = $this->publish( array( 'publish_immediately_future_date' => true ) );

		$this->assertSame( 400, $response->get_status() );
		$this->assertFalse( $response->get_data()['success'] );
	}

	public function test_failed_write_is_reported_as_a_server_error() {
		$this->seedFuturePost();
		PostStore::$failUpdateWith = 'db_error';

		$response = $this->publish( array( 'publish_immediately_future_date' => true ) );

		$this->assertSame( 500, $response->get_status() );
		$this->assertFalse( $response->get_data()['success'] );
	}

	public function test_failed_write_does_not_persist_the_intent() {
		$this->seedFuturePost();
		PostStore::$failUpdateWith = 'db_error';

		$this->publish( array( 'publish_immediately_future_date' => true ) );

		$this->assertSame( '', MetaStore::get( self::POST_ID, self::META_KEY ) );
	}

	public function test_failed_write_does_not_notify_pro() {
		$this->seedFuturePost();
		PostStore::$failUpdateWith = 'db_error';

		$this->publish( array( 'publish_immediately_future_date' => true ) );

		$this->assertNotContains( 'wpsp_pro_update_post', PostStore::$firedActions );
	}

	// ── publish_immediately: the happy paths still work ─────────────────────

	public function test_future_date_publish_succeeds_and_records_the_intent() {
		$date = $this->seedFuturePost();

		$response = $this->publish( array( 'publish_immediately_future_date' => true ) );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 'publish', PostStore::get( self::POST_ID )->post_status );
		$this->assertSame( $date, MetaStore::get( self::POST_ID, self::META_KEY ) );
		$this->assertContains( 'wpsp_pro_update_post', PostStore::$firedActions );
	}

	public function test_future_date_publish_accepts_false_meta_result_when_readback_is_exact() {
		$date = $this->seedFuturePost();
		MetaStore::set( self::POST_ID, self::META_KEY, $date );

		$response = $this->publish( array( 'publish_immediately_future_date' => true ) );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 'publish', PostStore::get( self::POST_ID )->post_status );
		$this->assertSame( $date, MetaStore::get( self::POST_ID, self::META_KEY ) );
		$this->assertContains( 'wpsp_pro_update_post', PostStore::$firedActions );
	}

	public function test_failed_intent_persistence_rolls_publish_back_exactly() {
		$date = $this->seedFuturePost();
		self::$failMetaUpdate = true;

		$response = $this->publish( array( 'publish_immediately_future_date' => true ) );

		$this->assertSame( 500, $response->get_status() );
		$this->assertFalse( $response->get_data()['success'] );
		$this->assertSame( 'future', PostStore::get( self::POST_ID )->post_status );
		$this->assertSame( $date, PostStore::get( self::POST_ID )->post_date );
		$this->assertSame( $date, PostStore::get( self::POST_ID )->post_date_gmt );
		$this->assertSame( '', MetaStore::get( self::POST_ID, self::META_KEY ) );
	}

	public function test_failed_intent_persistence_does_not_notify_pro() {
		$this->seedFuturePost();
		self::$failMetaUpdate = true;

		$this->publish( array( 'publish_immediately_future_date' => true ) );

		$this->assertNotContains( 'wpsp_pro_update_post', PostStore::$firedActions );
	}

	public function test_current_date_publish_succeeds() {
		$this->seedFuturePost();

		$response = $this->publish( array( 'publish_immediately_current_date' => true ) );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 'publish', PostStore::get( self::POST_ID )->post_status );
	}

	public function test_string_flags_are_accepted_from_form_encoded_requests() {
		$this->seedFuturePost();

		$response = $this->publish( array( 'publish_immediately_future_date' => 'true' ) );

		$this->assertSame( 200, $response->get_status() );
	}

	// ── clear_publish_immediately: precondition ─────────────────────────────

	public function test_clear_without_any_intent_does_not_change_post_status() {
		// A published, future-dated post this feature never touched.
		$date = $this->futureDate();
		PostStore::seed( self::POST_ID, 'publish', $date, $date );

		$response = $this->clear();

		$this->assertSame( 200, $response->get_status() );
		$this->assertFalse( $response->get_data()['data']['rescheduled'] );
		$this->assertSame( 'publish', PostStore::get( self::POST_ID )->post_status );
	}

	public function test_clear_with_a_stale_intent_does_not_reschedule() {
		$date = $this->futureDate();
		PostStore::seed( self::POST_ID, 'publish', $date, $date );
		// Recorded against a different date, so it is not active.
		MetaStore::set( self::POST_ID, self::META_KEY, $this->pastDate() );

		$response = $this->clear();

		$this->assertSame( 'publish', PostStore::get( self::POST_ID )->post_status );
		$this->assertFalse( $response->get_data()['data']['rescheduled'] );
	}

	public function test_clear_removes_a_stale_intent_row() {
		$date = $this->futureDate();
		PostStore::seed( self::POST_ID, 'publish', $date, $date );
		MetaStore::set( self::POST_ID, self::META_KEY, $this->pastDate() );

		$this->clear();

		$this->assertSame( '', MetaStore::get( self::POST_ID, self::META_KEY ) );
	}

	public function test_failed_stale_intent_delete_is_a_server_error() {
		$date       = $this->futureDate();
		$stale_date = $this->pastDate();
		PostStore::seed( self::POST_ID, 'publish', $date, $date );
		MetaStore::set( self::POST_ID, self::META_KEY, $stale_date );
		PostStore::$failMetaDelete = true;

		$response = $this->clear();

		$this->assertSame( 500, $response->get_status() );
		$this->assertFalse( $response->get_data()['success'] );
		$this->assertSame( $stale_date, MetaStore::get( self::POST_ID, self::META_KEY ) );
		$this->assertSame( 'publish', PostStore::get( self::POST_ID )->post_status );
	}

	// ── clear_publish_immediately: the real undo ────────────────────────────

	public function test_clear_with_an_active_intent_restores_the_schedule() {
		$date = $this->futureDate();
		PostStore::seed( self::POST_ID, 'publish', $date, $date );
		MetaStore::set( self::POST_ID, self::META_KEY, $date );

		$response = $this->clear();

		$this->assertSame( 200, $response->get_status() );
		$this->assertTrue( $response->get_data()['data']['rescheduled'] );
		$this->assertSame( 'future', PostStore::get( self::POST_ID )->post_status );
		$this->assertSame( '', MetaStore::get( self::POST_ID, self::META_KEY ) );
	}

	public function test_clear_returns_the_new_status_so_the_editor_can_resync() {
		$date = $this->futureDate();
		PostStore::seed( self::POST_ID, 'publish', $date, $date );
		MetaStore::set( self::POST_ID, self::META_KEY, $date );

		$response = $this->clear();

		$this->assertSame( 'future', $response->get_data()['data']['post_status'] );
	}

	public function test_clear_on_a_past_dated_post_drops_the_intent_without_rescheduling() {
		$date = $this->pastDate();
		PostStore::seed( self::POST_ID, 'publish', $date, $date );
		MetaStore::set( self::POST_ID, self::META_KEY, $date );

		$response = $this->clear();

		$this->assertSame( 200, $response->get_status() );
		$this->assertFalse( $response->get_data()['data']['rescheduled'] );
		$this->assertSame( 'publish', PostStore::get( self::POST_ID )->post_status );
		$this->assertSame( '', MetaStore::get( self::POST_ID, self::META_KEY ) );
	}

	// ── clear_publish_immediately: atomicity ────────────────────────────────

	public function test_failed_reschedule_restores_the_intent() {
		$date = $this->futureDate();
		PostStore::seed( self::POST_ID, 'publish', $date, $date );
		MetaStore::set( self::POST_ID, self::META_KEY, $date );
		PostStore::$failUpdateWith = 'db_error';

		$response = $this->clear();

		$this->assertSame( 500, $response->get_status() );
		$this->assertSame(
			$date,
			MetaStore::get( self::POST_ID, self::META_KEY ),
			'A failed reschedule must not leave the post unprotected.'
		);
		$this->assertSame( $date, self::$lastMetaUpdateValue );
	}

	public function test_failed_reschedule_reports_failed_intent_restoration() {
		$date = $this->futureDate();
		PostStore::seed( self::POST_ID, 'publish', $date, $date );
		MetaStore::set( self::POST_ID, self::META_KEY, $date );
		PostStore::$failUpdateWith = 'db_error';
		self::$failMetaUpdate      = true;

		$response = $this->clear();

		$this->assertSame( 500, $response->get_status() );
		$this->assertFalse( $response->get_data()['success'] );
		$this->assertStringContainsString(
			'could not restore the stored publishing intent',
			$response->get_data()['message']
		);
		$this->assertSame( $date, self::$lastMetaUpdateValue );
		$this->assertSame( '', MetaStore::get( self::POST_ID, self::META_KEY ) );
	}

	public function test_failed_meta_delete_is_a_server_error() {
		$date = $this->futureDate();
		PostStore::seed( self::POST_ID, 'publish', $date, $date );
		MetaStore::set( self::POST_ID, self::META_KEY, $date );
		PostStore::$failMetaDelete = true;

		$response = $this->clear();

		$this->assertSame( 500, $response->get_status() );
		$this->assertSame( 'publish', PostStore::get( self::POST_ID )->post_status );
	}

	public function test_clear_on_a_missing_post_is_a_404() {
		$response = $this->clear();

		$this->assertSame( 404, $response->get_status() );
	}

	// ── get_settings reports the state the UI needs ─────────────────────────

	public function test_get_settings_reports_an_active_intent() {
		$date = $this->futureDate();
		PostStore::seed( self::POST_ID, 'publish', $date, $date );
		MetaStore::set( self::POST_ID, self::META_KEY, $date );

		$data = $this->panel->get_settings( new FakeRequest( array( 'post_id' => self::POST_ID ) ) )->get_data();

		$this->assertTrue( $data['data']['prevent_future_post'] );
		$this->assertSame( $date, $data['data']['prevent_future_post_date'] );
	}

	public function test_get_settings_does_not_report_a_stale_intent_as_active() {
		$date = $this->futureDate();
		PostStore::seed( self::POST_ID, 'publish', $date, $date );
		MetaStore::set( self::POST_ID, self::META_KEY, $this->pastDate() );

		$data = $this->panel->get_settings( new FakeRequest( array( 'post_id' => self::POST_ID ) ) )->get_data();

		$this->assertFalse( $data['data']['prevent_future_post'] );
	}
}
