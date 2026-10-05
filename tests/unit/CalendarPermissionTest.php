<?php
/**
 * Unit tests for card 84589: the Calendar's edit route must not let a user
 * who can edit a post but not publish it (a Contributor on their own draft)
 * schedule it.
 *
 * @package WPScheduledPosts
 */

namespace WPSP\Tests\Unit;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use WPSP\Admin\Calendar;
use WPSP\Tests\Stubs\CapStore;
use WPSP\Tests\Stubs\FakeRequest;

class CalendarPermissionTest extends TestCase {

	/** @var Calendar */
	private $calendar;

	protected function setUp(): void {
		parent::setUp();
		CapStore::reset();
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
			'drop on the trash'     => array( 'trashDrop' ),
		);
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
}
