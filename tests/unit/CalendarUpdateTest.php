<?php
/**
 * Unit tests for card 84790 (QA follow-up): a Calendar edit keeps the post's
 * author and type, whatever the request names.
 *
 * @package WPScheduledPosts
 */

namespace {

	// Kept here, not in the shared stubs file, so open card branches don't conflict.
	if ( ! function_exists( 'get_post_type' ) ) {
		function get_post_type( $post = null ) {
			$found = \WPSP\Tests\Stubs\PostStore::get( (int) $post );
			return $found && isset( $found->post_type ) ? $found->post_type : false;
		}
	}

	if ( ! function_exists( 'get_current_user_id' ) ) {
		function get_current_user_id() {
			return isset( $GLOBALS['current_user']->ID ) ? (int) $GLOBALS['current_user']->ID : 0;
		}
	}

	if ( ! function_exists( 'wp_strip_all_tags' ) ) {
		function wp_strip_all_tags( $text ) {
			return trim( strip_tags( $text ) );
		}
	}

	if ( ! function_exists( 'get_date_from_gmt' ) ) {
		function get_date_from_gmt( $date_string, $format = 'Y-m-d H:i:s' ) {
			return gmdate( $format, strtotime( $date_string ) );
		}
	}
}

namespace WPSP\Tests\Unit {

	use PHPUnit\Framework\TestCase;
	use WPSP\Admin\Calendar;
	use WPSP\Tests\Stubs\FakeRequest;
	use WPSP\Tests\Stubs\OptionStore;
	use WPSP\Tests\Stubs\PostStore;
	use WPSP\Tests\Stubs\UserStore;

	class CalendarUpdateTest extends TestCase {

		/** @var Calendar */
		private $calendar;

		protected function setUp(): void {
			parent::setUp();
			PostStore::reset();
			UserStore::login( 4, 'editor' );
			UserStore::allowRoles( 'administrator', 'editor' );
			$settings                     = json_decode( OptionStore::get( WPSP_SETTINGS_NAME ), true );
			$settings['allow_post_types'] = array( 'post', 'page' );
			OptionStore::seed( WPSP_SETTINGS_NAME, json_encode( $settings ) );

			// An admin's page.
			PostStore::seed( 7, 'draft', '2030-01-01 10:00:00', '2030-01-01 10:00:00' );
			PostStore::get( 7 )->post_author = 1;
			PostStore::get( 7 )->post_type   = 'page';

			// get_rest_result() reads the post back through the loop; the writes are what matter here.
			$this->calendar = $this->getMockBuilder( Calendar::class )
				->disableOriginalConstructor()
				->onlyMethods( array( 'get_rest_result' ) )
				->getMock();
			$this->calendar->method( 'get_rest_result' )->willReturnArgument( 0 );
		}

		private function send( $type ) {
			return $this->calendar->calender_ajax_request_php( new FakeRequest( array(
				'type'        => $type,
				'ID'          => 7,
				'post_type'   => 'post',
				'postTitle'   => 'Edited',
				'postContent' => 'Edited',
				'date'        => '2030-01-02 10:00:00',
			) ) );
		}

		/**
		 * @dataProvider edit_types
		 */
		public function test_edit_keeps_the_author_and_the_type( $type ) {
			$this->assertSame( 7, $this->send( $type ) );
			$this->assertSame( 1, PostStore::get( 7 )->post_author );
			$this->assertSame( 'page', PostStore::get( 7 )->post_type );
		}

		public function edit_types() {
			return array(
				'edit a draft'          => array( 'editDraft' ),
				'edit a scheduled post' => array( 'editEvent' ),
				'drop back to drafts'   => array( 'draftDrop' ),
			);
		}

		public function test_post_of_a_type_not_allowed_in_settings_is_refused() {
			PostStore::get( 7 )->post_type = 'product';

			$this->assertInstanceOf( \WP_Error::class, $this->send( 'editDraft' ) );
			$this->assertSame( 'product', PostStore::get( 7 )->post_type );
		}
	}
}
