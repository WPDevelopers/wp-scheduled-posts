<?php
/**
 * Unit tests for MCP review items 6, 8, 10, 12 and 13 (issue #109, 16 Nov 2026).
 *
 * - 6:  publish-now acts only on draft, pending and future posts.
 * - 8:  a date-only "to" covers the whole of that day.
 * - 10: per-post tools and post-type filters stay inside allow_post_types.
 * - 12: update-settings accepts only the roles the "Allow users" setting offers.
 * - 13: share-now refuses a password-protected post, also in a dry run.
 *
 * @package WPScheduledPosts
 */

namespace {
	if ( ! defined( 'ABSPATH' ) ) {
		define( 'ABSPATH', dirname( __DIR__, 2 ) . '/' );
	}
	if ( ! defined( 'WPSP_SETTINGS_NAME' ) ) {
		define( 'WPSP_SETTINGS_NAME', 'wpsp_settings_v5' );
	}
	// Posts and pages exist (as in the get_post_type_object stub); nothing else does.
	if ( ! function_exists( 'post_type_exists' ) ) {
		function post_type_exists( $post_type ) {
			return null !== get_post_type_object( $post_type );
		}
	}
	if ( ! function_exists( 'get_post_types' ) ) {
		function get_post_types( $args = array(), $output = 'names' ) {
			return array( 'post' => 'post', 'page' => 'page' );
		}
	}
	if ( ! function_exists( 'get_editable_roles' ) ) {
		function get_editable_roles() {
			$roles = array();
			foreach ( array( 'administrator', 'editor', 'author', 'contributor', 'subscriber' ) as $role ) {
				$roles[ $role ] = array( 'name' => ucfirst( $role ) );
			}
			return $roles;
		}
	}
	if ( ! function_exists( 'get_the_title' ) ) {
		function get_the_title( $post = 0 ) {
			return 'Test post';
		}
	}
	if ( ! function_exists( 'wp_list_pluck' ) ) {
		function wp_list_pluck( $list, $field ) {
			return array_map(
				function ( $item ) use ( $field ) {
					return is_array( $item ) ? $item[ $field ] : $item->{$field};
				},
				$list
			);
		}
	}
}

namespace WPSP\Tests\Unit {

	use PHPUnit\Framework\TestCase;
	use WPSP\Abilities\Schedule\PublishNow;
	use WPSP\Abilities\Settings\UpdateSettings;
	use WPSP\Abilities\Social\ShareNow;
	use WPSP\Tests\Stubs\CapStore;
	use WPSP\Tests\Stubs\OptionStore;
	use WPSP\Tests\Stubs\PostStore;

	/** Exposes AbilityBase's protected helpers. */
	class McpReviewProbe extends PublishNow {
		public function range_end( $value ) {
			return $this->parse_range_end( $value );
		}
		public function post_for( $id ) {
			return $this->require_post( $id );
		}
		public function types( $requested ) {
			return $this->resolve_post_types( $requested );
		}
	}

	class McpReviewItemsTest extends TestCase {

		protected function setUp(): void {
			parent::setUp();
			PostStore::reset();
			CapStore::reset();
			OptionStore::reset();
			CapStore::grant( 'edit_post', 'publish_post' );
			$this->settings( array( 'allow_post_types' => array( 'post' ) ) );
		}

		private function settings( array $values ) {
			OptionStore::seed( WPSP_SETTINGS_NAME, json_encode( $values ) );
		}

		private function seed_post( $id, $status, $type = 'post', $password = '' ) {
			PostStore::seed( $id, $status, '2026-12-01 10:00:00', '2026-12-01 10:00:00' );
			$post                = PostStore::get( $id );
			$post->post_type     = $type;
			$post->post_password = $password;
			return $post;
		}

		private function code( $result ) {
			return is_wp_error( $result ) ? $result->get_error_code() : 'ok';
		}

		// Item 6.

		public function statuses_publish_now_refuses() {
			return array( array( 'private' ), array( 'trash' ), array( 'auto-draft' ), array( 'inherit' ) );
		}

		/** @dataProvider statuses_publish_now_refuses */
		public function test_publish_now_refuses_status( $status ) {
			$this->seed_post( 11, $status );
			$result = ( new PublishNow() )->execute( array( 'post_id' => 11, 'confirm' => true ) );
			$this->assertSame( 'wpsp_invalid_post_status', $this->code( $result ) );
			$this->assertSame( $status, PostStore::get( 11 )->post_status, 'the post must not change' );
		}

		public function test_publish_now_keeps_its_already_published_answer() {
			$this->seed_post( 12, 'publish' );
			$result = ( new PublishNow() )->execute( array( 'post_id' => 12, 'confirm' => true ) );
			$this->assertSame( 'wpsp_already_published', $this->code( $result ) );
		}

		// Item 8.

		public function test_date_only_end_is_the_end_of_that_day() {
			$end = ( new McpReviewProbe() )->range_end( '2026-11-30' );
			$this->assertSame( '2026-11-30 23:59:59', $end->format( 'Y-m-d H:i:s' ) );
		}

		public function test_end_with_a_time_is_kept() {
			$end = ( new McpReviewProbe() )->range_end( '2026-11-30T10:00:00' );
			$this->assertSame( '2026-11-30 10:00:00', $end->format( 'Y-m-d H:i:s' ) );
		}

		public function test_unparseable_end_is_still_an_error() {
			$this->assertSame( 'wpsp_invalid_datetime', $this->code( ( new McpReviewProbe() )->range_end( 'not a date' ) ) );
		}

		// Item 10.

		public function test_post_outside_allow_post_types_is_refused() {
			$this->seed_post( 21, 'draft', 'page' );
			$this->assertSame( 'wpsp_post_type_not_allowed', $this->code( ( new McpReviewProbe() )->post_for( 21 ) ) );
		}

		public function test_post_inside_allow_post_types_is_returned() {
			$this->seed_post( 22, 'draft', 'post' );
			$this->assertSame( 22, ( new McpReviewProbe() )->post_for( 22 )->ID );
		}

		public function test_page_is_allowed_once_the_setting_lists_it() {
			$this->settings( array( 'allow_post_types' => array( 'post', 'page' ) ) );
			$this->seed_post( 23, 'draft', 'page' );
			$this->assertSame( 23, ( new McpReviewProbe() )->post_for( 23 )->ID );
		}

		public function test_type_filter_outside_the_setting_is_refused() {
			$this->assertSame( 'wpsp_post_type_not_allowed', $this->code( ( new McpReviewProbe() )->types( array( 'page' ) ) ) );
		}

		public function test_type_filter_inside_the_setting_and_unknown_types_behave_as_before() {
			$probe = new McpReviewProbe();
			$this->assertSame( array( 'post' ), $probe->types( array( 'post' ) ) );
			$this->assertSame( array( 'post' ), $probe->types( null ) );
			$this->assertSame( 'wpsp_unknown_post_type', $this->code( $probe->types( array( 'nope' ) ) ) );
		}

		// Item 12.

		public function test_subscriber_role_is_refused_and_nothing_is_saved() {
			$before = OptionStore::get( WPSP_SETTINGS_NAME );
			$result = ( new UpdateSettings() )->execute( array( 'settings' => array( 'allow_user_by_role' => array( 'administrator', 'subscriber' ) ) ) );
			$this->assertSame( 'wpsp_invalid_role', $this->code( $result ) );
			$this->assertSame( $before, OptionStore::get( WPSP_SETTINGS_NAME ) );
		}

		public function test_made_up_role_is_refused() {
			$result = ( new UpdateSettings() )->execute( array( 'settings' => array( 'allow_user_by_role' => array( 'superuser' ) ) ) );
			$this->assertSame( 'wpsp_invalid_role', $this->code( $result ) );
		}

		public function test_offered_roles_are_saved() {
			$result = ( new UpdateSettings() )->execute( array( 'settings' => array( 'allow_user_by_role' => array( 'administrator', 'editor' ) ) ) );
			$this->assertSame( 'ok', $this->code( $result ) );
			$saved = json_decode( OptionStore::get( WPSP_SETTINGS_NAME ), true );
			$this->assertSame( array( 'administrator', 'editor' ), $saved['allow_user_by_role'] );
		}

		// Item 13.

		private function with_profile() {
			$this->settings(
				array(
					'allow_post_types'      => array( 'post' ),
					'facebook_profile_list' => array( array( 'id' => 'p1', 'name' => 'Test page', 'type' => 'page', 'status' => true, 'access_token' => 'fake' ) ),
				)
			);
		}

		public function test_share_now_dry_run_refuses_a_password_protected_post() {
			$this->with_profile();
			$this->seed_post( 31, 'publish', 'post', 'secret' );
			$result = ( new ShareNow() )->execute( array( 'post_id' => 31, 'platform' => 'facebook', 'platform_key' => '0' ) );
			$this->assertSame( 'wpsp_post_password_protected', $this->code( $result ) );
		}

		public function test_share_now_dry_run_previews_an_open_post() {
			$this->with_profile();
			$this->seed_post( 32, 'publish' );
			$result = ( new ShareNow() )->execute( array( 'post_id' => 32, 'platform' => 'facebook', 'platform_key' => '0' ) );
			$this->assertSame( 'ok', $this->code( $result ) );
			$this->assertTrue( $result['dry_run'] );
		}
	}
}
