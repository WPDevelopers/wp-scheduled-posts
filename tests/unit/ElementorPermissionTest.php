<?php
/**
 * Unit tests for card 84790: the old Elementor modal's ajax action
 * (wpsp_el_editor_form) must refuse a role left out of "Allow users", and a
 * Contributor on their own draft or on someone else's post.
 *
 * Only the refusals are covered here. Past its checks the handler runs about
 * 250 lines of social-profile code; the allowed path is covered end to end in
 * Docs/issue-84790 instead.
 *
 * @package WPScheduledPosts
 */

namespace WPSP\Tests\Unit;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use WPSP\Admin;
use WPSP\Tests\Stubs\CapStore;
use WPSP\Tests\Stubs\JsonResponse;
use WPSP\Tests\Stubs\PostStore;
use WPSP\Tests\Stubs\UserStore;

class ElementorPermissionTest extends TestCase {

	const BEFORE_HOOK = 'wpsp_el_action_before';

	/** @var Admin */
	private $admin;

	protected function setUp(): void {
		parent::setUp();
		PostStore::reset();
		CapStore::reset();
		UserStore::allowRoles( 'administrator', 'editor', 'author', 'contributor' );
		// The constructor registers hooks for the whole admin; the handler needs none.
		$this->admin = ( new ReflectionClass( Admin::class ) )->newInstanceWithoutConstructor();
	}

	protected function tearDown(): void {
		$_POST = array();
		parent::tearDown();
	}

	private function send( $post_status ) {
		$_POST = array(
			'wpsp-el-editor' => 'nonce',
			'id'             => 7,
			'post_status'    => $post_status,
			'date'           => '2030-01-01 10:00:00',
		);
		try {
			$this->admin->wpsp_el_tab_action();
		} catch ( JsonResponse $response ) {
			return $response;
		}
		$this->fail( 'The request got past the permission checks.' );
	}

	private function assertRefused( JsonResponse $response ) {
		$this->assertFalse( $response->success );
		$this->assertSame( 403, $response->status );
		$this->assertNotContains( self::BEFORE_HOOK, PostStore::$firedActions, 'Nothing may run for a refused request.' );
	}

	public function test_role_not_in_allow_users_is_refused() {
		UserStore::login( 3, 'author' );
		CapStore::grant( 'edit_post', 'publish_post' );
		UserStore::allowRoles( 'administrator' );

		$this->assertRefused( $this->send( 'future' ) );
	}

	public function test_contributor_cannot_publish_their_own_draft() {
		UserStore::login( 2, 'contributor' );
		CapStore::grant( 'edit_post' );

		$this->assertRefused( $this->send( 'publish' ) );
	}

	public function test_contributor_cannot_reschedule_someone_elses_post() {
		UserStore::login( 2, 'contributor' );

		$this->assertRefused( $this->send( 'future' ) );
	}
}
