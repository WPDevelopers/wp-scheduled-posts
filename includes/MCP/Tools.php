<?php
/**
 * MCP tool registry — exposes SchedulePress's registered abilities as MCP tools.
 *
 * Unlike a hand-written catalog, the tool surface here is the WordPress
 * Abilities API registry: every `schedulepress/*` (and Pro `schedulepress-pro/*`)
 * ability becomes one MCP tool (name, description, JSON Schema inputSchema).
 * Consumed by Server for both tools/list and tools/call, so the ability
 * registry and the MCP surface can never drift.
 *
 * Tool naming: MCP tool names may not contain `/`, so the category prefix
 * (`schedulepress/` or `schedulepress-pro/`) is stripped — the ability
 * `schedulepress/get-calendar` is the tool `get-calendar`. Free and Pro bare
 * names do not collide, so a bare tool name resolves back to exactly one
 * ability (see invoke()).
 *
 * Scope model: a read-only credential may only invoke read tools. An ability is
 * read-only when its name (sans prefix) starts with `get-` or `list-`;
 * everything else (schedule-*, update-*, share-*) mutates state.
 *
 * @package WPSP\MCP
 */

namespace WPSP\MCP;

use WPSP\Abilities\Registrar;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Bridges the Abilities API registry to the MCP tool surface.
 */
final class Tools {

	/**
	 * Ability name prefixes that mark a SchedulePress ability exposed over MCP.
	 * The free plugin owns `schedulepress/`; SchedulePress Pro may register its
	 * abilities under `schedulepress-pro/` via the
	 * `schedulepress_register_abilities` filter.
	 */
	const ABILITY_PREFIXES = array( 'schedulepress/', 'schedulepress-pro/' );

	/**
	 * Tool-name prefixes that identify read-only (non-mutating) abilities.
	 */
	const READ_PREFIXES = array( 'get-', 'list-' );

	/**
	 * Per-call read-only override. Null means "defer to the pairing token's
	 * scope". true/false is set by Server when an OAuth access token (with its
	 * own scope) authorized the request.
	 *
	 * @var bool|null
	 */
	private static $read_only_override = null;

	/**
	 * Set the active credential's read-only state for the current request.
	 * Passing null clears the override (back to the pairing-token default).
	 *
	 * @param bool|null $read_only Whether the active credential is read-only.
	 * @return void
	 */
	public static function set_read_only_override( $read_only ) {
		self::$read_only_override = ( null === $read_only ) ? null : (bool) $read_only;
	}

	/**
	 * Whether the active MCP credential is limited to read-only tools.
	 *
	 * @return bool
	 */
	private static function is_read_only() {
		if ( null !== self::$read_only_override ) {
			return self::$read_only_override;
		}
		return Pairing::is_read_only();
	}

	/**
	 * The tool list in MCP `tools/list` shape, built from the abilities
	 * registry.
	 *
	 * @return array
	 */
	public static function all() {
		$out = array();
		foreach ( self::abilities() as $ability ) {
			$schema = $ability->get_input_schema();
			$out[]  = array(
				'name'        => self::tool_name( $ability->get_name() ),
				'description' => $ability->get_description(),
				'inputSchema' => ! empty( $schema ) ? self::normalize_schema( $schema ) : array(
					'type'       => 'object',
					'properties' => (object) array(),
				),
			);
		}
		return $out;
	}

	/**
	 * Normalize a JSON Schema for MCP clients: an empty PHP `properties` array
	 * JSON-encodes as `[]`, but the schema spec (and the MCP SDK's validator)
	 * requires an OBJECT — `{}`. Recurse first so nested object schemas are
	 * fixed too.
	 *
	 * @param array $schema JSON Schema node.
	 * @return array
	 */
	private static function normalize_schema( array $schema ) {
		foreach ( $schema as $key => $value ) {
			if ( is_array( $value ) ) {
				$schema[ $key ] = self::normalize_schema( $value );
			}
		}
		if ( isset( $schema['properties'] ) && array() === $schema['properties'] ) {
			$schema['properties'] = (object) array();
		}
		return $schema;
	}

	/**
	 * Invoke a tool by name with decoded arguments. The ability's own input
	 * validation and permission callback run inside WP_Ability::execute() (the
	 * authenticated credential's user is already set by Server).
	 *
	 * @param string $name Tool name (ability name sans `schedulepress/` prefix).
	 * @param array  $args Decoded arguments.
	 * @return mixed|\WP_Error Result payload or error.
	 */
	public static function invoke( $name, array $args ) {
		$ability = null;
		if ( function_exists( 'wp_get_ability' ) ) {
			foreach ( self::ABILITY_PREFIXES as $prefix ) {
				$candidate = wp_get_ability( $prefix . $name );
				if ( $candidate ) {
					$ability = $candidate;
					break;
				}
			}
		}

		if ( ! $ability ) {
			return new \WP_Error(
				'wpsp_mcp_unknown_tool',
				sprintf(
					/* translators: %s: tool name. */
					__( 'Unknown tool: %s', 'wp-scheduled-posts' ),
					$name
				),
				array( 'status' => 404 )
			);
		}

		// Scope enforcement: a read-only connection cannot invoke a tool that
		// mutates state.
		if ( self::is_write_tool( $name ) && self::is_read_only() ) {
			return new \WP_Error(
				'wpsp_mcp_read_only',
				sprintf(
					/* translators: %s: tool name. */
					__( 'This MCP connection is read-only; the "%s" tool changes state and is not permitted. Reconnect with write access to use it.', 'wp-scheduled-posts' ),
					$name
				),
				array( 'status' => 403 )
			);
		}

		return $ability->execute( $args );
	}

	/**
	 * Whether a tool mutates state. Read tools are `get-*` / `list-*`;
	 * everything else is treated as write.
	 *
	 * @param string $name Tool name (sans prefix).
	 * @return bool
	 */
	public static function is_write_tool( $name ) {
		foreach ( self::READ_PREFIXES as $prefix ) {
			if ( 0 === strpos( $name, $prefix ) ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Map an ability name to its MCP tool name (strip the category prefix — MCP
	 * tool names may not contain `/`).
	 *
	 * @param string $ability_name Full ability name, e.g. `schedulepress/get-calendar`.
	 * @return string
	 */
	private static function tool_name( $ability_name ) {
		$bare = self::strip_prefix( $ability_name );
		if ( null !== $bare ) {
			return $bare;
		}
		return str_replace( '/', '-', $ability_name );
	}

	/**
	 * All registered SchedulePress abilities.
	 *
	 * @return array
	 */
	private static function abilities() {
		if ( ! function_exists( 'wp_get_abilities' ) ) {
			return array();
		}

		// Our abilities reach the registry through `wp_abilities_api_init`,
		// which fires once from whichever Abilities API copy owns the global
		// functions. When a foreign copy owns them our callback can be missed
		// entirely, leaving this filter with nothing to match and the client
		// with a connected-but-empty tool list. This replays the registration
		// once, and is a no-op on a healthy request.
		Registrar::ensure_registered();

		$out = array();
		foreach ( wp_get_abilities() as $ability ) {
			if ( ! is_object( $ability ) || ! method_exists( $ability, 'get_name' ) ) {
				continue;
			}
			if ( null !== self::strip_prefix( $ability->get_name() ) ) {
				$out[] = $ability;
			}
		}
		return $out;
	}

	/**
	 * Strip a recognized SchedulePress ability prefix, returning the bare tool
	 * name. Returns null when the ability is not one of ours (so callers can
	 * filter).
	 *
	 * @param string $ability_name Full ability name.
	 * @return string|null Bare name, or null if not a SchedulePress ability.
	 */
	private static function strip_prefix( $ability_name ) {
		foreach ( self::ABILITY_PREFIXES as $prefix ) {
			if ( 0 === strpos( $ability_name, $prefix ) ) {
				return substr( $ability_name, strlen( $prefix ) );
			}
		}
		return null;
	}
}
