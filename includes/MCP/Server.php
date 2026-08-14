<?php
/**
 * MCP server — the per-site JSON-RPC endpoint.
 *
 * The plugin speaks the MCP protocol directly at this site's own URL
 * (https://thissite.com/schedulepress/mcp), so there is NO hosted broker in the
 * path. MCP's Streamable-HTTP transport is JSON-RPC 2.0 over HTTP POST. We
 * implement the small server surface an AI client needs:
 *   - initialize            → capabilities + serverInfo
 *   - notifications/*       → acknowledged (no response body)
 *   - ping                  → {}
 *   - tools/list            → Tools::all()
 *   - tools/call            → Tools::invoke() wrapped as MCP content
 *
 * Auth: either the static pairing token (Pairing) or an OAuth 2.1 access token
 * (OAuth), both presented as a Bearer token. On success the request runs AS the
 * admin who granted the credential (wp_set_current_user), so every ability's
 * own capability check still applies. A single unauthenticated call gets a
 * JSON-RPC 401 + RFC 9728 WWW-Authenticate challenge that points OAuth-capable
 * clients at the discovery metadata.
 *
 * @package WPSP\MCP
 */

namespace WPSP\MCP;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * JSON-RPC 2.0 handler for the SchedulePress MCP endpoint.
 */
final class Server {

	/**
	 * MCP protocol version this server implements.
	 */
	const PROTOCOL_VERSION = '2025-06-18';

	/**
	 * JSON-RPC standard error codes.
	 */
	const PARSE_ERROR      = -32700;
	const INVALID_REQUEST  = -32600;
	const METHOD_NOT_FOUND = -32601;
	const INVALID_PARAMS   = -32602;
	const UNAUTHORIZED     = -32001;

	/**
	 * Handle a raw MCP HTTP request. Reads the JSON-RPC message from the
	 * request body, dispatches it, and returns a WP_REST_Response (or a 202
	 * with empty body for notifications).
	 *
	 * @param \WP_REST_Request $request Incoming request (raw body).
	 * @return \WP_REST_Response
	 */
	public static function handle( \WP_REST_Request $request ) {
		// Diagnostic tap: define WPSP_MCP_DEBUG in wp-config.php to log every
		// inbound MCP request (pre-auth) to the PHP error log. Bodies are
		// truncated; credentials are never logged.
		if ( defined( 'WPSP_MCP_DEBUG' ) && WPSP_MCP_DEBUG ) {
			error_log( sprintf( // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- opt-in debug tap.
				'[WPSP-MCP] in method=%s auth=%s accept=%s body=%s',
				isset( $_SERVER['REQUEST_METHOD'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) : '?',
				$request->get_header( 'authorization' ) ? 'yes' : 'no',
				(string) $request->get_header( 'accept' ),
				substr( (string) $request->get_body(), 0, 300 )
			) );
		}

		// The admin toggle is the master switch: off = no MCP surface at all.
		if ( ! Manager::is_enabled() ) {
			return self::error_response( null, self::UNAUTHORIZED, __( 'MCP is disabled on this site. Enable it under SchedulePress → MCP.', 'wp-scheduled-posts' ), 403 );
		}

		// A request carrying NO credential is the normal opening move of the
		// OAuth flow — the client is asking for the RFC 9728 challenge, not
		// guessing a token. Only a credential that was PRESENTED and rejected
		// counts against the limiter, and only such a request can be locked
		// out; otherwise every OAuth-capable client walls itself off after
		// DEFAULT_MAX_FAILS discovery probes.
		$presented = self::extract_token( $request );

		// Lockout check first: a rate-limited IP never reaches the compare.
		if ( '' !== $presented && RateLimiter::is_locked() ) {
			$response = self::error_response( null, self::UNAUTHORIZED, __( 'Too many failed attempts. Try again later.', 'wp-scheduled-posts' ), 429 );
			// Keep the challenge on the 429 too: a client that only ever sees a
			// bare 429 concludes the server has no OAuth at all.
			$response->header( 'WWW-Authenticate', self::challenge_header() );
			$response->header( 'Retry-After', (string) RateLimiter::retry_after() );
			return $response;
		}

		// Authenticate: static pairing token OR an OAuth 2.1 access token (both
		// Bearer). Either satisfies the gate.
		if ( true !== self::authorize( $request ) ) {
			if ( '' !== $presented ) {
				RateLimiter::record_failure();
			}
			$response = self::error_response( null, self::UNAUTHORIZED, __( 'Unauthorized: invalid or missing connection token.', 'wp-scheduled-posts' ), 401 );
			// RFC 9728 challenge: point OAuth-capable clients at the
			// protected-resource metadata so they can start the auth flow.
			$response->header( 'WWW-Authenticate', self::challenge_header() );
			return $response;
		}
		RateLimiter::clear();

		$raw = $request->get_body();
		$msg = json_decode( $raw, true );

		if ( null === $msg && JSON_ERROR_NONE !== json_last_error() ) {
			return self::error_response( null, self::PARSE_ERROR, 'Parse error: body is not valid JSON.', 400 );
		}

		// Batched requests: an array of messages. Handle each; drop notification
		// (id-less) responses per JSON-RPC.
		if ( is_array( $msg ) && array_key_exists( 0, $msg ) ) {
			$responses = array();
			foreach ( $msg as $one ) {
				$r = self::dispatch( is_array( $one ) ? $one : array() );
				if ( null !== $r ) {
					$responses[] = $r;
				}
			}
			if ( empty( $responses ) ) {
				return new \WP_REST_Response( null, 202 );
			}
			return new \WP_REST_Response( $responses, 200 );
		}

		if ( ! is_array( $msg ) ) {
			return self::error_response( null, self::INVALID_REQUEST, 'Invalid request.', 400 );
		}

		$response = self::dispatch( $msg );
		if ( null === $response ) {
			// Notification — no response body, 202 Accepted.
			return new \WP_REST_Response( null, 202 );
		}
		return new \WP_REST_Response( $response, 200 );
	}

	/**
	 * Dispatch a single JSON-RPC message. Returns the response array, or null
	 * for notifications (messages with no `id`).
	 *
	 * @param array $msg Decoded JSON-RPC message.
	 * @return array|null
	 */
	private static function dispatch( array $msg ) {
		$method = isset( $msg['method'] ) ? (string) $msg['method'] : '';
		$id     = isset( $msg['id'] ) ? $msg['id'] : null;
		$params = isset( $msg['params'] ) && is_array( $msg['params'] ) ? $msg['params'] : array();

		// Notifications (no id) get acknowledged with no response.
		$is_notification = ! array_key_exists( 'id', $msg );

		switch ( $method ) {
			case 'initialize':
				return self::result(
					$id,
					array(
						'protocolVersion' => self::PROTOCOL_VERSION,
						'capabilities'    => array(
							'tools' => array( 'listChanged' => false ),
						),
						'serverInfo'      => array(
							'name'    => 'schedulepress',
							'version' => defined( 'WPSP_VERSION' ) ? WPSP_VERSION : '1.0.0',
						),
					)
				);

			case 'ping':
				return self::result( $id, (object) array() );

			case 'tools/list':
				$tools = Tools::all();
				// An empty list while MCP is enabled means the Abilities runtime
				// never loaded (broken package) — the client sees a clean,
				// useless connection. Leave a trail for whoever debugs it; the
				// admin notice and self-test carry the loud version.
				if ( empty( $tools ) && defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					error_log( '[WPSP-MCP] tools/list returned 0 tools. ' . \WPSP\Abilities\Registrar::summary() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- WP_DEBUG-gated diagnostic.
				}
				return self::result( $id, array( 'tools' => $tools ) );

			case 'tools/call':
				return self::call_tool( $id, $params );

			default:
				// notifications/initialized, notifications/cancelled, etc.
				if ( $is_notification || 0 === strpos( $method, 'notifications/' ) ) {
					return null;
				}
				return self::error( $id, self::METHOD_NOT_FOUND, 'Method not found: ' . $method );
		}
	}

	/**
	 * Execute a tools/call request and wrap the result in MCP content.
	 *
	 * @param mixed $id     JSON-RPC id.
	 * @param array $params { name:string, arguments:array }.
	 * @return array
	 */
	private static function call_tool( $id, array $params ) {
		$name = isset( $params['name'] ) ? (string) $params['name'] : '';
		$args = isset( $params['arguments'] ) && is_array( $params['arguments'] ) ? $params['arguments'] : array();

		if ( '' === $name ) {
			return self::error( $id, self::INVALID_PARAMS, 'Missing tool name.' );
		}

		$result = Tools::invoke( $name, $args );

		if ( is_wp_error( $result ) ) {
			// Tool-level failure is reported as a successful JSON-RPC response
			// with isError=true (per MCP), so the model can read the message
			// rather than the transport swallowing it.
			return self::result(
				$id,
				array(
					'content' => array(
						array(
							'type' => 'text',
							'text' => $result->get_error_message(),
						),
					),
					'isError' => true,
				)
			);
		}

		return self::result(
			$id,
			array(
				'content' => array(
					array(
						'type' => 'text',
						'text' => wp_json_encode( $result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ),
					),
				),
				'isError' => false,
			)
		);
	}

	// -- Auth --

	/**
	 * Validate the Bearer credential — pairing token or OAuth access token. On
	 * success, switch the request to the granting admin's user so every
	 * ability's own permission callback (current_user_can) still applies.
	 *
	 * @param \WP_REST_Request $request Incoming request.
	 * @return bool
	 */
	private static function authorize( \WP_REST_Request $request ) {
		$presented = self::extract_token( $request );
		if ( '' === $presented ) {
			return false;
		}

		// Path 1: the static per-site pairing token. Leave the tool scope
		// override cleared so Tools defers to the pairing token's scope.
		$stored = Pairing::site_token();
		if ( '' !== $stored && hash_equals( $stored, $presented ) ) {
			Tools::set_read_only_override( null );
			if ( self::impersonate( Pairing::user_id() ) ) {
				// Record activity for the "Static token connections" row.
				Pairing::touch_last_used();
				return true;
			}
			return false;
		}

		// Path 2: an OAuth 2.1 access token minted by OAuth. Its own granted
		// scope decides read-only, independent of the pairing token.
		$grant = OAuth::validate_token( $presented );
		if ( null !== $grant ) {
			Tools::set_read_only_override( OAuth::scope_is_read_only( $grant['scope'] ) );
			return self::impersonate( $grant['user_id'] );
		}

		return false;
	}

	/**
	 * Run the request as the admin who granted the credential. Refuses when the
	 * stored user no longer exists or lost manage_options — a demoted or
	 * deleted admin's grants die with them.
	 *
	 * @param int $user_id Granting user id.
	 * @return bool
	 */
	private static function impersonate( $user_id ) {
		$user_id = (int) $user_id;
		if ( $user_id <= 0 ) {
			return false;
		}
		$user = get_user_by( 'id', $user_id );
		if ( ! $user || ! user_can( $user, 'manage_options' ) ) {
			return false;
		}
		wp_set_current_user( $user_id );
		return true;
	}

	/**
	 * The RFC 9728 WWW-Authenticate challenge value. Points the client at this
	 * site's protected-resource metadata so an OAuth-capable client can
	 * discover the authorization server and begin the flow.
	 *
	 * @return string
	 */
	private static function challenge_header() {
		// The path-suffixed form (RFC 9728 §3.1) — specific to OUR resource, so
		// it can't collide with another plugin's root-form metadata.
		$metadata_url = home_url( '/.well-known/oauth-protected-resource/' . Pairing::SITE_ENDPOINT_PATH );
		return sprintf( 'Bearer resource_metadata="%s"', $metadata_url );
	}

	/**
	 * Pull the token from the Authorization: Bearer header.
	 *
	 * @param \WP_REST_Request $request Incoming request.
	 * @return string
	 */
	private static function extract_token( \WP_REST_Request $request ) {
		$auth = $request->get_header( 'authorization' );
		if ( is_string( $auth ) && preg_match( '/^Bearer\s+(.+)$/i', trim( $auth ), $m ) ) {
			return trim( $m[1] );
		}
		return '';
	}

	// -- JSON-RPC envelope helpers --

	/**
	 * Build a JSON-RPC success envelope.
	 *
	 * @param mixed $id     JSON-RPC id.
	 * @param mixed $result Result payload.
	 * @return array
	 */
	private static function result( $id, $result ) {
		return array(
			'jsonrpc' => '2.0',
			'id'      => $id,
			'result'  => $result,
		);
	}

	/**
	 * Build a JSON-RPC error envelope (for a single message).
	 *
	 * @param mixed  $id      JSON-RPC id.
	 * @param int    $code    JSON-RPC error code.
	 * @param string $message Error message.
	 * @return array
	 */
	private static function error( $id, $code, $message ) {
		return array(
			'jsonrpc' => '2.0',
			'id'      => $id,
			'error'   => array(
				'code'    => (int) $code,
				'message' => $message,
			),
		);
	}

	/**
	 * Build a top-level error WP_REST_Response with an HTTP status.
	 *
	 * @param mixed  $id      JSON-RPC id.
	 * @param int    $code    JSON-RPC error code.
	 * @param string $message Error message.
	 * @param int    $http    HTTP status.
	 * @return \WP_REST_Response
	 */
	private static function error_response( $id, $code, $message, $http ) {
		return new \WP_REST_Response( self::error( $id, $code, $message ), (int) $http );
	}
}
