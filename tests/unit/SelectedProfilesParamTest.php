<?php
/**
 * Unit tests for InstantShare::get_selected_profiles_param().
 *
 * Covers issue #116 and the follow-up finding: the method must not turn
 * malformed input into an empty array, because Helper::get_social_profile()
 * reads an empty list as "no filter" and answers with every profile.
 *
 * The method is private and its class needs WordPress to construct, so the
 * behaviour is exercised through a reflection call on an uninitialised
 * instance. That keeps the assertions against the shipped code rather than a
 * copy of it.
 *
 * @package WPScheduledPosts
 */

namespace WPSP\Tests\Unit;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use WPSP\Social\InstantShare;

class SelectedProfilesParamTest extends TestCase {

	/** @var ReflectionMethod */
	private $method;

	/** @var InstantShare */
	private $instance;

	protected function setUp(): void {
		parent::setUp();

		$class          = new ReflectionClass( InstantShare::class );
		$this->instance = $class->newInstanceWithoutConstructor();
		$this->method   = $class->getMethod( 'get_selected_profiles_param' );
		$this->method->setAccessible( true );

		$_REQUEST = array();
	}

	protected function tearDown(): void {
		$_REQUEST = array();
		parent::tearDown();
	}

	/**
	 * @param mixed $value
	 * @return array
	 */
	private function read( $value, $set = true ) {
		if ( $set ) {
			$_REQUEST['facebook_selected_profiles'] = $value;
		}
		return $this->method->invoke( $this->instance, 'facebook_selected_profiles' );
	}

	// ── The genuine "no filter" case ────────────────────────────────────────

	public function test_absent_key_is_no_filter() {
		// This is how a caller asks for every profile on a platform, and it has
		// to keep working: it is not the malformed case.
		$this->assertSame( array(), $this->read( null, false ) );
	}

	public function test_empty_array_is_unchanged() {
		$this->assertSame( array(), $this->read( array() ) );
	}

	// ── Normal input ────────────────────────────────────────────────────────

	public function test_array_of_keys_is_sanitised_and_kept() {
		$this->assertSame( array( 'Page A', 'Page B' ), $this->read( array( 'Page A', 'Page B' ) ) );
	}

	public function test_list_is_reindexed() {
		$this->assertSame( array( 'Page B' ), $this->read( array( 3 => 'Page B' ) ) );
	}

	// ── The widening bug (#116 follow-up) ───────────────────────────────────

	public function test_scalar_becomes_a_one_item_selection() {
		// Returning array() here would read downstream as "share to everyone".
		$this->assertSame( array( 'Page B' ), $this->read( 'Page B' ) );
	}

	public function test_scalar_is_sanitised() {
		$this->assertSame( array( 'Page A' ), $this->read( '<b>Page A</b>' ) );
	}

	public function test_numeric_scalar_is_kept_as_a_selection() {
		$this->assertSame( array( '0' ), $this->read( 0 ) );
	}

	public function test_empty_string_matches_nothing_rather_than_everything() {
		$result = $this->read( '' );
		$this->assertNotSame( array(), $result, 'An empty string must not read as "no filter".' );
		$this->assertSame( array( '' ), $result );
	}

	// ── Malformed input must fail closed, and must not fatal (#116) ─────────

	public function test_nested_array_does_not_reach_sanitize_text_field() {
		// array_map( 'sanitize_text_field', ... ) over a nested array is a fatal
		// on PHP 8, which is the crash #116 was filed for.
		$result = $this->read( array( array( 'x' => 1 ) ) );
		$this->assertNotSame( array(), $result, 'Unreadable input must not read as "no filter".' );
		$this->assertSame( array( '' ), $result );
	}

	public function test_mixed_input_keeps_only_the_readable_entries() {
		$this->assertSame( array( 'Page C' ), $this->read( array( array( 'x' => 1 ), 'Page C' ) ) );
	}

	public function test_object_input_is_not_treated_as_no_filter() {
		$result = $this->read( (object) array( 'x' => 1 ) );
		$this->assertNotSame( array(), $result, 'Unreadable input must not read as "no filter".' );
		$this->assertSame( array( '' ), $result );
	}
}
