<?php
/**
 * Unit tests for the WP-Cron health check (card 84951).
 *
 * It only counts what is late: scheduled posts past their time and queued
 * social shares that should have run. These tests pin what counts as late,
 * the Site Health result and when the admin notice shows.
 *
 * @package WPScheduledPosts
 */

namespace WPSP\Tests\Unit;

require_once dirname( __DIR__ ) . '/stubs/cron-health-functions.php';

use PHPUnit\Framework\TestCase;
use WPSP\Admin\CronHealth;
use WPSP\Tests\Stubs\CapStore;
use WPSP\Tests\Stubs\CronHealthStore;
use WPSP\Tests\Stubs\CronStore;
use WPSP\Tests\Stubs\HookStore;
use WPSP\Tests\Stubs\OptionStore;
use WPSP\Tests\Stubs\UserStore;

class CronHealthTest extends TestCase {

	const ADMIN_ID = 1;

	protected function setUp(): void {
		parent::setUp();
		if ( ! defined( 'WPSP_SETTINGS_NAME' ) ) {
			define( 'WPSP_SETTINGS_NAME', 'wpsp_settings_v5' );
		}
		if ( ! defined( 'WPSP_SETTINGS_SLUG' ) ) {
			define( 'WPSP_SETTINGS_SLUG', 'schedulepress' );
		}
		CronStore::reset();
		CronHealthStore::reset();
		CapStore::reset();
		OptionStore::reset();
		HookStore::reset( 'wpsp_share_event_hooks' );
		OptionStore::seed( WPSP_SETTINGS_NAME, json_encode( array( 'allow_post_types' => array( 'post', 'page' ) ) ) );
		UserStore::login( self::ADMIN_ID, 'administrator' );
	}

	private function minutes_ago( $minutes ) {
		return time() - $minutes * 60;
	}

	public function test_nothing_late_is_good() {
		$result = ( new CronHealth() )->run_test();

		$this->assertSame( 'good', $result['status'] );
		$this->assertSame( 'wpsp_wp_cron', $result['test'] );
	}

	public function test_late_posts_are_counted_from_our_post_types_only() {
		CronHealthStore::$foundPosts = 3;

		$status = CronHealth::get_status( true );

		$this->assertSame( 3, $status['late_posts'] );
		$query = CronHealthStore::$queries[0];
		$this->assertSame( array( 'post', 'page' ), $query['post_type'] );
		$this->assertSame( 'future', $query['post_status'] );
		$this->assertSame( 'post_date_gmt', $query['date_query'][0]['column'] );
	}

	/** A post a few minutes past its time is normal cron delay, not a problem. */
	public function test_post_counts_as_late_only_after_ten_minutes() {
		CronHealth::get_status( true );

		$before = strtotime( CronHealthStore::$queries[0]['date_query'][0]['before'] . ' UTC' );
		$this->assertEqualsWithDelta( time() - CronHealth::LATE_AFTER, $before, 2 );
		$this->assertSame( 600, CronHealth::LATE_AFTER );
	}

	public function test_no_allowed_post_types_means_no_post_query() {
		OptionStore::seed( WPSP_SETTINGS_NAME, json_encode( array( 'allow_post_types' => array() ) ) );

		$status = CronHealth::get_status( true );

		$this->assertSame( 0, $status['late_posts'] );
		$this->assertSame( array(), CronHealthStore::$queries );
	}

	public function test_only_share_events_past_ten_minutes_count_as_late_shares() {
		CronStore::schedule( $this->minutes_ago( 30 ), 'WpScp_Facebook_post', array( 7 ) );
		CronStore::schedule( $this->minutes_ago( 20 ), 'wpsp_twitter_post', array( 7 ) );
		CronStore::schedule( $this->minutes_ago( 5 ), 'WpScp_linkedin_post', array( 8 ) );   // not late yet
		CronStore::schedule( $this->minutes_ago( 60 ), 'wp_version_check', array() );        // not a share
		CronStore::schedule( time() + 3600, 'WpScp_Facebook_post', array( 9 ) );             // future

		$status = CronHealth::get_status( true );

		$this->assertSame( 2, $status['late_shares'] );
	}

	public function test_share_hooks_added_by_filter_are_counted() {
		add_filter( 'wpsp_share_event_hooks', function ( $hooks ) {
			$hooks[] = 'my_custom_share';
			return $hooks;
		} );
		CronStore::schedule( $this->minutes_ago( 30 ), 'my_custom_share', array( 7 ) );

		$this->assertSame( 1, CronHealth::get_status( true )['late_shares'] );
	}

	public function test_status_is_cached_unless_fresh_is_asked() {
		CronHealth::get_status( true );
		CronHealthStore::$foundPosts = 5;

		$this->assertSame( 0, CronHealth::get_status()['late_posts'], 'The notice reads the 15 minute cache.' );
		$this->assertSame( 5, CronHealth::get_status( true )['late_posts'], 'Site Health always counts again.' );
	}

	public function test_late_work_is_critical_with_counts_and_doc_link() {
		CronHealthStore::$foundPosts = 2;
		CronStore::schedule( $this->minutes_ago( 30 ), 'WpScp_Facebook_post', array( 7 ) );

		$result = ( new CronHealth() )->run_test();

		$this->assertSame( 'critical', $result['status'] );
		$this->assertStringContainsString( '2 scheduled posts are late', $result['description'] );
		$this->assertStringContainsString( '1 social share is late', $result['description'] );
		$this->assertStringContainsString( CronHealth::DOC_URL, $result['actions'] );
	}

	public function test_check_never_changes_any_schedule() {
		CronStore::schedule( $this->minutes_ago( 30 ), 'WpScp_Facebook_post', array( 7 ) );
		$before = CronStore::$events;

		( new CronHealth() )->run_test();

		$this->assertSame( $before, CronStore::$events );
		$this->assertSame( array(), CronStore::$ran );
	}

	public function test_notice_shows_on_dashboard_for_admins_when_work_is_late() {
		CapStore::grant( 'manage_options' );
		CronHealthStore::$screen     = (object) array( 'id' => 'dashboard' );
		CronHealthStore::$foundPosts = 1;

		$html = $this->render_notice();

		$this->assertStringContainsString( 'notice-warning', $html );
		$this->assertStringContainsString( '1 scheduled post is late', $html );
		$this->assertStringContainsString( 'wpsp_dismiss_cron_notice', $html );
	}

	public function test_notice_hidden_for_users_who_cannot_manage_options() {
		CronHealthStore::$screen     = (object) array( 'id' => 'dashboard' );
		CronHealthStore::$foundPosts = 1;

		$this->assertSame( '', $this->render_notice() );
	}

	public function test_notice_hidden_on_other_screens() {
		CapStore::grant( 'manage_options' );
		CronHealthStore::$screen     = (object) array( 'id' => 'edit-post' );
		CronHealthStore::$foundPosts = 1;

		$this->assertSame( '', $this->render_notice() );
	}

	public function test_notice_hidden_for_a_day_after_dismiss() {
		CapStore::grant( 'manage_options' );
		CronHealthStore::$screen = (object) array( 'id' => 'dashboard' );
		CronHealthStore::$foundPosts = 1;
		CronHealthStore::$userMeta[ self::ADMIN_ID ][ CronHealth::DISMISS_META ] = time() + 3600;

		$this->assertSame( '', $this->render_notice() );
	}

	public function test_notice_hidden_when_nothing_is_late() {
		CapStore::grant( 'manage_options' );
		CronHealthStore::$screen = (object) array( 'id' => 'dashboard' );

		$this->assertSame( '', $this->render_notice() );
	}

	private function render_notice() {
		ob_start();
		( new CronHealth() )->render_notice();
		return ob_get_clean();
	}
}
