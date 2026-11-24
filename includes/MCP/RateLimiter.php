<?php
/**
 * MCP rate limiter — a per-IP lockout on FAILED token authentication.
 *
 * The MCP connection token is a 256-bit secret, so online brute-forcing is
 * already infeasible. This limiter stops the cheaper abuse: a flood of
 * bad-token requests burning CPU + filling logs, and gives a rotated token's
 * stale clients a hard wall. Defence-in-depth, not the primary control.
 *
 * Model: count consecutive FAILED attempts per client IP in a rolling window
 * (transient-backed). At/after the threshold the IP is locked out for the
 * window; a SUCCESSFUL auth clears the counter immediately.
 *
 * "Failed" means a credential was PRESENTED and rejected. A request with no
 * Authorization header never reaches the counter — that is the first step of
 * the OAuth handshake (client asks for the RFC 9728 challenge), so counting it
 * would lock out every OAuth client during normal discovery.
 *
 * Threshold + window are overridable via the WPSP_MCP_MAX_FAILS /
 * WPSP_MCP_LOCKOUT_SECONDS constants and the `wpsp_mcp_rate_limit` filter
 * ( [ max_fails, lockout_seconds ] ).
 *
 * @package WPSP\MCP
 */

namespace WPSP\MCP;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Per-IP failed-auth lockout for the MCP endpoint.
 */
final class RateLimiter {

	/**
	 * Transient key prefix; the client-IP hash is appended.
	 */
	const PREFIX = 'wpsp_mcp_rl_';

	/**
	 * Default: lock out after this many failed attempts.
	 */
	const DEFAULT_MAX_FAILS = 10;

	/**
	 * Default: lockout / rolling-window length, in seconds.
	 */
	const DEFAULT_LOCKOUT = 900; // 15 minutes.

	/**
	 * Is the current client currently locked out? Call BEFORE comparing the
	 * token so a locked IP never even reaches the (constant-time) compare.
	 *
	 * @return bool
	 */
	public static function is_locked() {
		$limits = self::limits();
		return self::attempts() >= $limits[0];
	}

	/**
	 * Record a failed auth attempt for the current client and return whether
	 * the client is now locked out.
	 *
	 * The window is FIXED from the first failure — recording a failure never
	 * extends it. Resetting the expiry on every increment would let a stranded
	 * client that retries every few minutes (exactly what a connector
	 * configured with a rotated-away token does) re-arm its own lockout
	 * forever. A lockout must be escapable by simply waiting out one window.
	 *
	 * @return bool True if this failure crossed into a lockout.
	 */
	public static function record_failure() {
		$limits = self::limits();
		$max    = $limits[0];
		$window = $limits[1];

		$key   = self::key();
		$entry = get_transient( $key );

		if ( is_array( $entry ) && isset( $entry['count'], $entry['until'] ) ) {
			$entry['count']++;
			// Preserve the ORIGINAL window end: TTL = remaining time only.
			$remaining = max( 1, (int) $entry['until'] - time() );
			set_transient( $key, $entry, $remaining );
			return $entry['count'] >= $max;
		}

		// First failure in a window (also migrates any legacy integer entry —
		// a stale int simply restarts as a fresh window of 1).
		$entry = array(
			'count' => 1,
			'until' => time() + $window,
		);
		set_transient( $key, $entry, $window );
		return 1 >= $max;
	}

	/**
	 * Clear the counter for the current client — call on a SUCCESSFUL auth.
	 *
	 * @return void
	 */
	public static function clear() {
		delete_transient( self::key() );
	}

	/**
	 * Seconds until the current client's window ends. Falls back to the full
	 * window length when no entry exists.
	 *
	 * @return int
	 */
	public static function retry_after() {
		$entry = get_transient( self::key() );
		if ( is_array( $entry ) && isset( $entry['until'] ) ) {
			return max( 1, (int) $entry['until'] - time() );
		}
		$limits = self::limits();
		return $limits[1];
	}

	/**
	 * How many clients are currently locked out, across all IPs.
	 *
	 * Diagnostic for the self-test: a healthy loopback plus a locked-out remote
	 * client is exactly the state a stranded connector (rotated-away token,
	 * still retrying) produces, and it is otherwise invisible — support can't
	 * tell "server broken" from "client walled itself off".
	 *
	 * Returns null when a persistent object cache is in use — transients don't
	 * live in the options table there, so the count is unknowable and claiming
	 * zero would be a lie.
	 *
	 * @return int|null Locked-out client count, or null when unknowable.
	 */
	public static function active_lockouts() {
		if ( wp_using_ext_object_cache() ) {
			return null;
		}

		global $wpdb;
		if ( ! is_object( $wpdb ) || ! method_exists( $wpdb, 'get_col' ) ) {
			return null;
		}
		$limits = self::limits();
		$max    = $limits[0];

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- diagnostic scan over transient rows; no core API enumerates them.
		$rows = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT option_value FROM {$wpdb->options} WHERE option_name LIKE %s",
				$wpdb->esc_like( '_transient_' . self::PREFIX ) . '%'
			)
		);

		$locked = 0;
		foreach ( (array) $rows as $row ) {
			$entry = maybe_unserialize( $row );
			$count = is_array( $entry ) && isset( $entry['count'] )
				? (int) $entry['count']
				: ( is_numeric( $entry ) ? (int) $entry : 0 );
			if ( $count >= $max ) {
				$locked++;
			}
		}

		return $locked;
	}

	// -- internals --

	/**
	 * Current failed-attempt count for this client (0 when none).
	 *
	 * @return int
	 */
	private static function attempts() {
		$v = get_transient( self::key() );
		if ( is_array( $v ) && isset( $v['count'] ) ) {
			return (int) $v['count'];
		}
		// Legacy integer entries from before the fixed-window format.
		return is_numeric( $v ) ? (int) $v : 0;
	}

	/**
	 * Transient key bound to the (hashed) client IP.
	 *
	 * @return string
	 */
	private static function key() {
		return self::PREFIX . md5( self::client_ip() );
	}

	/**
	 * Resolve [ max_fails, lockout_seconds ] from constants, then filter.
	 *
	 * @return array
	 */
	private static function limits() {
		$max    = defined( 'WPSP_MCP_MAX_FAILS' ) ? (int) WPSP_MCP_MAX_FAILS : self::DEFAULT_MAX_FAILS;
		$window = defined( 'WPSP_MCP_LOCKOUT_SECONDS' ) ? (int) WPSP_MCP_LOCKOUT_SECONDS : self::DEFAULT_LOCKOUT;

		/**
		 * Filter the MCP failed-auth rate limit.
		 *
		 * @param array $limits [ max_fails, lockout_seconds ].
		 */
		$limits = (array) apply_filters( 'wpsp_mcp_rate_limit', array( $max, $window ) );
		$max    = isset( $limits[0] ) ? max( 1, (int) $limits[0] ) : self::DEFAULT_MAX_FAILS;
		$window = isset( $limits[1] ) ? max( 1, (int) $limits[1] ) : self::DEFAULT_LOCKOUT;
		return array( $max, $window );
	}

	/**
	 * Best-effort client IP. REMOTE_ADDR only — we deliberately do NOT trust
	 * X-Forwarded-For (spoofable → an attacker could dodge the limit or lock
	 * out a victim). Behind a reverse proxy or tunnel every client shares one
	 * REMOTE_ADDR and therefore one bucket; such a site should either set
	 * REMOTE_ADDR upstream or opt in via the filter below, which is safe only
	 * when the proxy is known to overwrite the forwarded header.
	 *
	 * @return string
	 */
	private static function client_ip() {
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- used only as a rate-limit bucket key (md5'd), never output or stored raw.
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? (string) wp_unslash( $_SERVER['REMOTE_ADDR'] ) : '';

		/**
		 * Filter the IP used as the MCP rate-limit bucket key.
		 *
		 * Opt-in escape hatch for sites behind a trusted reverse proxy, where
		 * REMOTE_ADDR is the proxy and every client would otherwise collapse
		 * into a single bucket. Only return a forwarded header's value when
		 * the proxy is known to overwrite it.
		 *
		 * @param string $ip Resolved REMOTE_ADDR ('' when unavailable).
		 */
		$ip = (string) apply_filters( 'wpsp_mcp_client_ip', $ip );

		return '' !== $ip ? $ip : 'unknown';
	}
}
