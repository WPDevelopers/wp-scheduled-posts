<?php
/**
 * Unit tests for Social::run_queued_shares_now().
 *
 * Covers card 84850: a scheduled post is published from inside a WP-Cron run,
 * and the share events the platforms queued during that run waited for the
 * next one — up to an hour late on a site whose server cron fires hourly.
 *
 * @package WPScheduledPosts
 */

namespace WPSP\Tests\Unit;

use PHPUnit\Framework\TestCase;
use WPSP\Social;
use WPSP\Tests\Stubs\CronStore;
use WPSP\Tests\Stubs\HookStore;

class RunQueuedSharesNowTest extends TestCase {

	const POST_ID  = 7;
	const OTHER_ID = 8;

	protected function setUp(): void {
		parent::setUp();
		CronStore::reset();
		HookStore::reset( 'wpsp_run_queued_shares_now' );
		HookStore::reset( 'wpsp_share_event_hooks' );
	}

	/**
	 * Queue a share the way a platform's `*_post_event()` does.
	 */
	private function queue_share( $hook, $post_id = self::POST_ID, $timestamp = null ) {
		CronStore::schedule( null === $timestamp ? time() : $timestamp, $hook, array( $post_id ) );
	}

	/** @return string[] Hooks that ran, in order. */
	private function ran_hooks() {
		return array_column( CronStore::$ran, 'hook' );
	}

	public function test_cron_run_shares_right_away_instead_of_on_the_next_run() {
		CronStore::$doingCron = true;
		$this->queue_share( 'WpScp_Facebook_post' );
		$this->queue_share( 'wpsp_twitter_post' );

		Social::run_queued_shares_now( self::POST_ID );

		$this->assertSame( array( 'WpScp_Facebook_post', 'wpsp_twitter_post' ), $this->ran_hooks() );
		$this->assertSame( array( self::POST_ID ), CronStore::$ran[0]['args'] );
		$this->assertSame( array(), CronStore::$events, 'A share that ran must not run again on the next cron run.' );
	}

	/**
	 * wp-cron.php unschedules before it runs, so a run that dies part-way leaves
	 * the platforms it never reached queued rather than losing them.
	 */
	public function test_each_share_is_unscheduled_before_it_runs() {
		CronStore::$doingCron = true;
		$this->queue_share( 'WpScp_Facebook_post' );
		$this->queue_share( 'WpScp_linkedin_post' );

		Social::run_queued_shares_now( self::POST_ID );

		$this->assertFalse( CronStore::$ran[0]['queued'] );
		$this->assertFalse( CronStore::$ran[1]['queued'] );
	}

	public function test_web_request_leaves_shares_queued() {
		$this->queue_share( 'WpScp_Facebook_post' );

		Social::run_queued_shares_now( self::POST_ID );

		$this->assertSame( array(), CronStore::$ran, 'A page load must never wait on social APIs.' );
		$this->assertTrue( CronStore::is_queued( 'WpScp_Facebook_post', array( self::POST_ID ) ) );
	}

	public function test_filter_can_run_shares_right_away_outside_cron() {
		add_filter( 'wpsp_run_queued_shares_now', function ( $run_now, $post_id ) {
			return self::POST_ID === $post_id;
		}, 10, 2 );
		$this->queue_share( 'WpScp_Facebook_post' );

		Social::run_queued_shares_now( self::POST_ID );

		$this->assertSame( array( 'WpScp_Facebook_post' ), $this->ran_hooks() );
	}

	public function test_filter_can_keep_shares_queued_inside_cron() {
		CronStore::$doingCron = true;
		add_filter( 'wpsp_run_queued_shares_now', function () {
			return false;
		}, 10, 2 );
		$this->queue_share( 'WpScp_Facebook_post' );

		Social::run_queued_shares_now( self::POST_ID );

		$this->assertSame( array(), CronStore::$ran );
		$this->assertTrue( CronStore::is_queued( 'WpScp_Facebook_post', array( self::POST_ID ) ) );
	}

	/**
	 * SocialProfile fires `wpsp_publish_future_post` with an object, not an ID.
	 */
	public function test_accepts_a_post_object() {
		CronStore::$doingCron = true;
		$this->queue_share( 'WpScp_pinterest_post' );

		Social::run_queued_shares_now( (object) array( 'ID' => self::POST_ID, 'post_status' => 'publish' ) );

		$this->assertSame( array( 'WpScp_pinterest_post' ), $this->ran_hooks() );
	}

	public function test_only_runs_share_hooks() {
		CronStore::$doingCron = true;
		$this->queue_share( 'wpscp_pending_schedule' );

		Social::run_queued_shares_now( self::POST_ID );

		$this->assertSame( array(), CronStore::$ran, 'Unrelated events for the post wait for their own cron run.' );
	}

	public function test_leaves_other_posts_and_later_events_alone() {
		CronStore::$doingCron = true;
		$this->queue_share( 'WpScp_Facebook_post', self::OTHER_ID );
		$this->queue_share( 'wpsp_mastodon_post', self::POST_ID, time() + 3600 );

		Social::run_queued_shares_now( self::POST_ID );

		$this->assertSame( array(), CronStore::$ran );
		$this->assertCount( 2, CronStore::$events );
	}

	public function test_filter_adds_share_hooks_from_other_plugins() {
		CronStore::$doingCron = true;
		add_filter( 'wpsp_share_event_hooks', function ( $hooks ) {
			$hooks[] = 'acme_social_post';
			return $hooks;
		}, 10, 2 );
		$this->queue_share( 'acme_social_post' );

		Social::run_queued_shares_now( self::POST_ID );

		$this->assertSame( array( 'acme_social_post' ), $this->ran_hooks() );
	}

	public function test_ignores_a_missing_post_id() {
		CronStore::$doingCron = true;
		CronStore::schedule( time(), 'WpScp_Facebook_post', array( 0 ) );

		Social::run_queued_shares_now( 0 );

		$this->assertSame( array(), CronStore::$ran );
	}
}
