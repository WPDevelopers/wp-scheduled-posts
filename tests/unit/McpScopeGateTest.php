<?php
/**
 * Unit tests for the MCP read/write scope gate.
 *
 * Pure logic — no WordPress required, so this runs in the `unit` suite.
 *
 * @package WPScheduledPosts
 */

namespace WPSP\Tests\Unit;

use PHPUnit\Framework\TestCase;
use WPSP\MCP\OAuth;
use WPSP\MCP\Tools;

// The MCP classes guard on ABSPATH the way every WordPress file does; the unit
// suite has no WordPress, so stand one in before the autoloader reaches them.
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __DIR__, 2 ) . '/' );
}

class McpScopeGateTest extends TestCase {

	/**
	 * Everything a read-only credential is allowed to reach.
	 *
	 * @return array
	 */
	public function read_tools() {
		return array(
			array( 'get-calendar' ),
			array( 'get-connection-status' ),
			array( 'get-missed-schedules' ),
			array( 'get-settings' ),
			array( 'get-share-status' ),
			array( 'get-social-template' ),
			array( 'list-content-types' ),
			array( 'list-draft-posts' ),
			array( 'list-scheduled-posts' ),
			array( 'list-social-profiles' ),
		);
	}

	/**
	 * Everything that changes state and so must be refused to a read-only
	 * credential. share-now and publish-now are the ones that matter: they are
	 * outward-facing and cannot be undone.
	 *
	 * @return array
	 */
	public function write_tools() {
		return array(
			array( 'schedule-post' ),
			array( 'reschedule-post' ),
			array( 'bulk-reschedule' ),
			array( 'unschedule-post' ),
			array( 'publish-now' ),
			array( 'share-now' ),
			array( 'update-social-template' ),
			array( 'suggest-caption' ),
			array( 'update-settings' ),
		);
	}

	/**
	 * @dataProvider read_tools
	 *
	 * @param string $tool Tool name.
	 */
	public function test_read_tools_are_not_treated_as_writes( $tool ) {
		$this->assertFalse(
			Tools::is_write_tool( $tool ),
			"{$tool} should be readable by a read-only connection"
		);
	}

	/**
	 * @dataProvider write_tools
	 *
	 * @param string $tool Tool name.
	 */
	public function test_write_tools_are_treated_as_writes( $tool ) {
		$this->assertTrue(
			Tools::is_write_tool( $tool ),
			"{$tool} mutates state and must be refused to a read-only connection"
		);
	}

	/**
	 * An unrecognised name must fail closed. A tool added later without a
	 * `get-`/`list-` prefix should be refused to read-only credentials by
	 * default rather than silently allowed.
	 */
	public function test_unknown_tool_names_default_to_write() {
		$this->assertTrue( Tools::is_write_tool( 'something-new' ) );
		$this->assertTrue( Tools::is_write_tool( '' ) );
	}

	/**
	 * A name that merely CONTAINS "get" is not a read tool — only a prefix
	 * counts, otherwise "budget-reset" would read as safe.
	 */
	public function test_read_prefix_must_be_a_prefix() {
		$this->assertTrue( Tools::is_write_tool( 'budget-reset' ) );
		$this->assertTrue( Tools::is_write_tool( 'forget-schedule' ) );
	}

	/**
	 * The ability's `readonly` annotation wins over its name. `find-schedule-gaps`
	 * is read-only despite lacking a get-/list- prefix, and a get-/list- named
	 * tool annotated non-readonly is still a write.
	 */
	public function test_readonly_annotation_overrides_the_name() {
		$this->assertFalse( Tools::is_write_tool( 'find-schedule-gaps', true ) );
		$this->assertFalse( Tools::is_write_tool( 'bulk-export', true ) );
		$this->assertTrue( Tools::is_write_tool( 'get-something', false ) );
	}

	/**
	 * With no annotation the gate falls back to the name prefix and fails closed:
	 * `find-schedule-gaps` is then treated as a write.
	 */
	public function test_missing_annotation_falls_back_to_name() {
		$this->assertTrue( Tools::is_write_tool( 'find-schedule-gaps' ) );
		$this->assertTrue( Tools::is_write_tool( 'find-schedule-gaps', null ) );
		$this->assertFalse( Tools::is_write_tool( 'get-calendar' ) );
	}

	public function test_read_scope_alone_is_read_only() {
		$this->assertTrue( OAuth::scope_is_read_only( 'read' ) );
	}

	public function test_write_and_umbrella_scopes_are_not_read_only() {
		$this->assertFalse( OAuth::scope_is_read_only( 'write' ) );
		$this->assertFalse( OAuth::scope_is_read_only( 'read write' ) );
		// `mcp` is the umbrella scope MCP clients request; it grants both.
		$this->assertFalse( OAuth::scope_is_read_only( 'mcp' ) );
		$this->assertFalse( OAuth::scope_is_read_only( 'read mcp' ) );
	}

	/**
	 * An empty or unrecognised scope must not be mistaken for write access.
	 */
	public function test_empty_scope_is_read_only() {
		$this->assertTrue( OAuth::scope_is_read_only( '' ) );
		$this->assertTrue( OAuth::scope_is_read_only( '   ' ) );
		$this->assertTrue( OAuth::scope_is_read_only( 'something-else' ) );
	}
}
