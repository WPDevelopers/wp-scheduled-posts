<?php
/**
 * Unit tests for card 84589: the Calendar's edit route must not let a user
 * who can edit a post but not publish it (a Contributor on their own draft)
 * schedule it.
 *
 * Card 84790: a role left out of "Allow users" must not change posts from it.
 *
 * @package WPScheduledPosts
 */

namespace WPSP\Tests\Unit;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use WPSP\Admin\Calendar;
use WPSP\Tests\Stubs\CapStore;
use WPSP\Tests\Stubs\FakeRequest;
use WPSP\Tests\Stubs\UserStore;

class CalendarPermissionTest extends TestCase {

	/** @var Calendar */
	private $calendar;

	protected function setUp(): void {
		parent::setUp();
		CapStore::reset();
		// Every role allowed, so the 84589 tests see capabilities only.
		UserStore::login( 3, 'author' );
		UserStore::allowRoles( 'administrator', 'editor', 'author', 'contributor' );
		// The constructor registers hooks and reads settings; the permission
		// callback needs neither.
		$this->calendar = ( new ReflectionClass( Calendar::class ) )->newInstanceWithoutConstructor();
	}

	private function allowed( $type ) {
		return $this->calendar->edit_permission_callback( new FakeRequest( array( 'ID' => 7, 'type' => $type ) ) );
	}

	/**
	 * @dataProvider scheduling_types
	 */
	public function test_contributor_cannot_schedule_their_post( $type ) {
		CapStore::grant( 'edit_post' );
		$this->assertFalse( $this->allowed( $type ) );
	}

	/**
	 * @dataProvider scheduling_types
	 */
	public function test_publisher_can_schedule_a_post( $type ) {
		CapStore::grant( 'edit_post', 'publish_post' );
		$this->assertTrue( $this->allowed( $type ) );
	}

	public function scheduling_types() {
		return array(
			'add event'                  => array( 'addEvent' ),
			'edit event'                 => array( 'editEvent' ),
			'drop on a date'             => array( 'eventDrop' ),
			'any other type (fallthrough)' => array( 'somethingElse' ),
		);
	}

	/**
	 * @dataProvider draft_types
	 */
	public function test_contributor_can_still_move_their_post_between_drafts( $type ) {
		CapStore::grant( 'edit_post' );
		$this->assertTrue( $this->allowed( $type ) );
	}

	public function draft_types() {
		return array(
			'edit a draft'          => array( 'editDraft' ),
			'drop back to drafts'   => array( 'draftDrop' ),
		);
	}

	public function test_trash_drop_needs_the_right_to_delete_the_post() {
		CapStore::grant( 'edit_post' );
		$this->assertFalse( $this->allowed( 'trashDrop' ) );

		CapStore::grant( 'delete_post' );
		$this->assertTrue( $this->allowed( 'trashDrop' ) );
	}

	public function test_delete_route_refuses_a_role_not_in_allow_users() {
		CapStore::grant( 'delete_post' );
		$request = new FakeRequest( array( 'ID' => 7 ) );
		$this->assertTrue( $this->calendar->delete_permission_callback( $request ) );

		UserStore::allowRoles( 'administrator' );
		$this->assertFalse( $this->calendar->delete_permission_callback( $request ) );
	}

	public function test_user_who_cannot_edit_the_post_is_still_refused() {
		CapStore::grant( 'publish_post' );
		$this->assertFalse( $this->allowed( 'eventDrop' ) );
	}

	public function test_new_post_without_an_id_still_needs_publish_posts() {
		CapStore::grant( 'edit_post', 'publish_post' );
		$this->assertFalse( $this->calendar->edit_permission_callback( new FakeRequest( array( 'type' => 'addEvent' ) ) ) );

		CapStore::grant( 'publish_posts' );
		$this->assertTrue( $this->calendar->edit_permission_callback( new FakeRequest( array( 'type' => 'addEvent' ) ) ) );
	}

	/**
	 * @dataProvider every_write_type
	 */
	public function test_role_not_in_allow_users_cannot_change_posts_from_the_calendar( $type ) {
		CapStore::grant( 'edit_post', 'publish_post', 'publish_posts', 'delete_post' );
		UserStore::allowRoles( 'administrator' );

		$this->assertFalse( $this->allowed( $type ) );
	}

	public function every_write_type() {
		return array_merge( $this->scheduling_types(), $this->draft_types(), array( 'drop on the trash' => array( 'trashDrop' ) ) );
	}

	public function test_role_not_in_allow_users_cannot_add_a_post_from_the_calendar() {
		CapStore::grant( 'publish_posts' );
		UserStore::allowRoles( 'administrator' );

		$this->assertFalse( $this->calendar->edit_permission_callback( new FakeRequest( array( 'type' => 'addEvent' ) ) ) );
	}
}
