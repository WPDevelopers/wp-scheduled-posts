<?php
/**
 * MCP pairing lifecycle — mint / rotate / revoke the per-site connection token.
 *
 * The primary way an AI assistant connects to SchedulePress: an admin clicks
 * Connect, the plugin mints a 32-byte secret, and the user pastes either the
 * single connect URL (token embedded in the path) or the endpoint + Bearer
 * token into their AI client. The token is validated directly by Server — no
 * hosted infrastructure is involved.
 *
 * State is stored in the `wpsp_mcp_pairing` option:
 *   {
 *     site_token:   string (the secret the client presents),
 *     connected:    bool,
 *     connected_at: int (unix ts),
 *     scopes:       string[] (e.g. ['read','write']),
 *     user_id:      int (admin who minted the token; MCP calls run as them)
 *   }
 *
 * @package WPSP\MCP
 */

namespace WPSP\MCP;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Connection-token lifecycle for the SchedulePress MCP server.
 */
final class Pairing {

	/**
	 * Option key holding all MCP pairing state.
	 */
	const OPTION = 'wpsp_mcp_pairing';

	/**
	 * Path segment of the pretty per-site endpoint.
	 */
	const SITE_ENDPOINT_PATH = 'schedulepress/mcp';

	/**
	 * Default scopes granted on connect.
	 */
	private static $default_scopes = array( 'read', 'write' );

	/**
	 * Throttle window (seconds) for last-used writes — one option write per
	 * minute at most, so a busy client can't hammer the option on every call.
	 */
	const LAST_USED_THROTTLE = 60;

	/**
	 * The PRIMARY endpoint the user pastes into their AI client — this site's
	 * own MCP URL.
	 *
	 * @return string
	 */
	public static function site_endpoint() {
		return home_url( '/' . self::SITE_ENDPOINT_PATH );
	}

	/**
	 * Always-on fallback endpoint via the REST namespace, for hosts where the
	 * pretty rewrite can't be served (e.g. plain permalinks).
	 *
	 * @return string
	 */
	public static function site_endpoint_fallback() {
		return rest_url( WPSP_PLUGIN_SLUG . '/v1/mcp' );
	}

	/**
	 * The SINGLE URL the user pastes into their AI client — the pretty
	 * endpoint with the connection token embedded as a path segment. Empty
	 * string when not connected.
	 *
	 * @return string
	 */
	public static function connect_url() {
		$token = self::site_token();
		if ( '' === $token ) {
			return '';
		}
		return self::site_endpoint() . '/' . $token;
	}

	/**
	 * Current pairing state, defaults merged.
	 *
	 * @return array
	 */
	public static function state() {
		$stored = get_option( self::OPTION, array() );
		if ( ! is_array( $stored ) ) {
			$stored = array();
		}
		return array(
			'site_token'   => isset( $stored['site_token'] ) ? (string) $stored['site_token'] : '',
			'connected'    => ! empty( $stored['connected'] ),
			'connected_at' => isset( $stored['connected_at'] ) ? (int) $stored['connected_at'] : 0,
			'scopes'       => isset( $stored['scopes'] ) && is_array( $stored['scopes'] )
				? array_values( array_map( 'strval', $stored['scopes'] ) )
				: array(),
			'user_id'      => isset( $stored['user_id'] ) ? (int) $stored['user_id'] : 0,
			'last_used'    => isset( $stored['last_used'] ) ? (int) $stored['last_used'] : 0,
		);
	}

	/**
	 * Record that the static token was just used to authenticate an MCP call.
	 * Throttled to at most one option write per minute so a busy client can't
	 * turn every request into a database write. No-op when not connected.
	 *
	 * @return void
	 */
	public static function touch_last_used() {
		$stored = get_option( self::OPTION, array() );
		if ( ! is_array( $stored ) || empty( $stored['site_token'] ) ) {
			return;
		}
		$now  = time();
		$last = isset( $stored['last_used'] ) ? (int) $stored['last_used'] : 0;
		if ( $now - $last < self::LAST_USED_THROTTLE ) {
			return;
		}
		$stored['last_used'] = $now;
		update_option( self::OPTION, $stored, false );
	}

	/**
	 * The stored site token (secret). Empty string when not connected.
	 *
	 * @return string
	 */
	public static function site_token() {
		$state = self::state();
		return $state['site_token'];
	}

	/**
	 * The admin user the connection runs as (the token's minter).
	 *
	 * @return int
	 */
	public static function user_id() {
		$state = self::state();
		return $state['user_id'];
	}

	/**
	 * Whether an MCP connection token is currently active for this site.
	 *
	 * @return bool
	 */
	public static function is_connected() {
		$state = self::state();
		return $state['connected'] && '' !== $state['site_token'];
	}

	/**
	 * Whether the active connection is limited to read-only tools.
	 *
	 * @return bool
	 */
	public static function is_read_only() {
		$state = self::state();
		return ! in_array( 'write', $state['scopes'], true );
	}

	/**
	 * Sanitized snapshot for the MCP admin page.
	 *
	 * @return array
	 */
	public static function public_status() {
		$state = self::state();
		return array(
			'connected'         => self::is_connected(),
			'connection_token'  => $state['site_token'],
			'connect_url'       => self::connect_url(),
			'mcp_endpoint'      => self::site_endpoint(),
			'mcp_endpoint_rest' => self::site_endpoint_fallback(),
			'connected_at'      => $state['connected_at'],
			'last_used'         => $state['last_used'],
			'scopes'            => $state['scopes'],
			'read_only'         => self::is_read_only(),
			// Ready-to-paste connection recipes (header-based — token stays out
			// of the URL, so it can't leak into server/proxy logs).
			'config'            => self::config_snippets(),
			// A drop-in instruction the user can paste into their AI client so
			// it sets the connection up itself.
			'ai_prompt'         => self::ai_prompt(),
		);
	}

	/**
	 * Ready-to-paste connection recipes for the dashboard. All header-based
	 * (Authorization: Bearer) so the secret stays out of URLs and logs. Empty
	 * strings when not connected.
	 *
	 * @return array
	 */
	public static function config_snippets() {
		$token = self::site_token();
		if ( '' === $token ) {
			return array(
				'cli'  => '',
				'json' => '',
			);
		}
		$endpoint = self::site_endpoint();

		// Claude Code one-liner. The CLI requires the positional NAME and URL
		// BEFORE any flags (`claude mcp add <name> <url> --flags`).
		$cli = sprintf(
			'claude mcp add schedulepress %s --transport http --header "Authorization: Bearer %s"',
			$endpoint,
			$token
		);

		// Portable mcpServers JSON block (Claude Desktop / other clients).
		$json = wp_json_encode(
			array(
				'mcpServers' => array(
					'schedulepress' => array(
						'url'     => $endpoint,
						'headers' => array(
							'Authorization' => 'Bearer ' . $token,
						),
					),
				),
			),
			JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
		);

		return array(
			'cli'  => $cli,
			'json' => is_string( $json ) ? $json : '',
		);
	}

	/**
	 * A copy-paste instruction the user hands to their AI assistant so IT sets
	 * up the SchedulePress MCP connection for them. Empty when not connected.
	 *
	 * @return string
	 */
	public static function ai_prompt() {
		if ( ! self::is_connected() ) {
			return '';
		}
		$token    = self::site_token();
		$endpoint = self::site_endpoint();
		$access   = self::is_read_only()
			? 'read-only (inspect the schedule and social connections only)'
			: 'read-write (can schedule, reschedule and share posts)';

		$snippets = self::config_snippets();

		$lines = array(
			'Add the following remote MCP server to your connections so you can manage the content schedule on my WordPress site, then use it.',
			'',
			'Server name: SchedulePress',
			'Server URL: ' . $endpoint,
			'Transport: streamable HTTP',
			'Authentication: Bearer token (in the Authorization header)',
			'API key: ' . $token,
			'Access level: ' . $access,
			'',
			'If you use the Claude Code CLI, this is the exact command (name and URL come BEFORE the flags):',
			'  ' . $snippets['cli'],
			'',
			'Add it now, confirm it is connected by calling its "get-connection-status" tool, and tell me what you can see.',
		);
		$prompt = implode( "\n", $lines );

		/**
		 * Filter the copy-paste AI setup prompt shown in the MCP page.
		 *
		 * @param string $prompt    The default prompt text.
		 * @param bool   $read_only Whether the connection is read-only.
		 */
		return (string) apply_filters( 'wpsp_mcp_ai_prompt', $prompt, self::is_read_only() );
	}

	/**
	 * Connect — mint a connection token for this site's MCP endpoint.
	 *
	 * Idempotent: re-connecting keeps the existing token (and its scopes) so a
	 * paired client isn't silently broken. Use rotate() to change either.
	 *
	 * @param bool $read_only Grant only the `read` scope on a NEW token.
	 * @return array Public status.
	 */
	public static function connect( $read_only = false ) {
		$state    = self::state();
		$existing = '' !== $state['site_token'];
		$token    = $existing ? $state['site_token'] : self::mint_token();
		$scopes   = $existing && ! empty( $state['scopes'] )
			? $state['scopes']
			: self::scopes_for( (bool) $read_only );

		update_option(
			self::OPTION,
			array(
				'site_token'   => $token,
				'connected'    => true,
				'connected_at' => $existing ? $state['connected_at'] : time(),
				'scopes'       => $scopes,
				'user_id'      => $existing && $state['user_id'] ? $state['user_id'] : get_current_user_id(),
			),
			false
		);

		return self::public_status();
	}

	/**
	 * Rotate — mint a BRAND-NEW token, invalidating the previous one
	 * immediately. The leaked-token remedy. Optionally flips read-only.
	 *
	 * @param bool|null $read_only null = keep current scopes; true/false = set.
	 * @return array Public status with the fresh token.
	 */
	public static function rotate( $read_only = null ) {
		$state  = self::state();
		$scopes = null === $read_only
			? ( ! empty( $state['scopes'] ) ? $state['scopes'] : self::$default_scopes )
			: self::scopes_for( (bool) $read_only );

		update_option(
			self::OPTION,
			array(
				'site_token'   => self::mint_token(),
				'connected'    => true,
				'connected_at' => time(),
				'scopes'       => $scopes,
				'user_id'      => get_current_user_id() ? get_current_user_id() : $state['user_id'],
			),
			false
		);

		return self::public_status();
	}

	/**
	 * Disconnect — revoke the connection token AND every OAuth grant, so
	 * Disconnect is a single kill switch for ALL MCP access.
	 *
	 * @return array Public status after disconnect.
	 */
	public static function disconnect() {
		delete_option( self::OPTION );
		OAuth::revoke_all();

		return self::public_status();
	}

	/**
	 * Map a read-only flag to the granted scope list.
	 *
	 * @param bool $read_only Whether to grant read-only access.
	 * @return string[]
	 */
	private static function scopes_for( $read_only ) {
		return $read_only ? array( 'read' ) : self::$default_scopes;
	}

	/**
	 * Mint a 32-byte random token (64 hex chars).
	 *
	 * @return string
	 */
	private static function mint_token() {
		return bin2hex( random_bytes( 32 ) );
	}
}
