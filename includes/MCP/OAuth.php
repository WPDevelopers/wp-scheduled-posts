<?php
/**
 * MCP OAuth 2.1 authorization server — the "paste a URL only" connect path.
 *
 * The pairing token (Pairing) covers clients that accept a pasted Bearer
 * token; this class covers spec-compliant MCP clients that take only the
 * server URL and run the OAuth 2.1 authorization-code + PKCE flow themselves.
 *
 * Flow: unauthenticated MCP call → 401 + WWW-Authenticate (Server) → client
 * fetches /.well-known/oauth-protected-resource + oauth-authorization-server →
 * dynamic registration (RFC 7591) → /authorize (admin consent + PKCE) → /token
 * (code + verifier → access + refresh) → MCP calls with
 * `Authorization: Bearer <access>` validated by validate_token().
 *
 * Security contract:
 *   - PKCE S256 REQUIRED (OAuth 2.1 public clients); codes are single-use,
 *     60 s TTL, bound to client_id + redirect_uri + challenge.
 *   - /authorize gates on manage_options — only an admin can grant access.
 *   - Access/refresh tokens stored only as SHA-256 hashes; the raw value
 *     exists solely in the /token response. Constant-time comparison.
 *   - Tokens carry the read/write scope model; a read-only grant refuses every
 *     write tool, exactly like a read-only pairing token.
 *
 * State lives in the `wpsp_mcp_oauth` option (clients, codes, tokens, refresh
 * — keyed by id or sha256 of the secret); expired entries are pruned lazily on
 * every read.
 *
 * @package WPSP\MCP
 */

namespace WPSP\MCP;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Minimal OAuth 2.1 authorization server for the SchedulePress MCP endpoint.
 */
final class OAuth {

	/**
	 * Option key holding all OAuth server state.
	 */
	const OPTION = 'wpsp_mcp_oauth';

	/**
	 * Authorization-code lifetime (seconds). Deliberately short.
	 */
	const CODE_TTL = 60;

	/**
	 * Access-token lifetime (seconds) — 1 hour, refreshable.
	 */
	const ACCESS_TTL = 3600;

	/**
	 * Refresh-token lifetime (seconds) — 30 days.
	 */
	const REFRESH_TTL = 2592000;

	/**
	 * Scopes we advertise + honor. `mcp` is the umbrella scope MCP clients
	 * request.
	 */
	const SUPPORTED_SCOPES = array( 'mcp', 'read', 'write' );

	/**
	 * Throttle window (seconds) for per-client last-used writes — at most one
	 * option write per minute per client, so a busy connector can't turn every
	 * MCP call into a database write.
	 */
	const LAST_USED_THROTTLE = 60;

	/**
	 * How many registered clients to keep. RFC 7591 registration is open by
	 * necessity — a client must register BEFORE it can hold any credential —
	 * so without a cap anyone on the internet can grow this option without
	 * bound, and every state() read pays for it. Clients holding a live token
	 * are never evicted, so the cap only ever discards abandoned registrations.
	 */
	const MAX_CLIENTS = 50;

	/**
	 * How long an unused client registration survives (seconds). A client that
	 * registers and never completes the flow is abandoned; real ones exchange a
	 * code within a minute.
	 */
	const CLIENT_TTL = 86400; // 24 hours.

	// -- URLs ------------------------------------------------------------

	/**
	 * The OAuth issuer identifier. Path-based (RFC 8414 §2 allows an issuer
	 * with a path component): using the MCP endpoint URL itself means clients
	 * derive the path-suffixed well-known URLs, which stay specific to
	 * SchedulePress even when another plugin runs its own MCP OAuth server at
	 * the same site root.
	 *
	 * @return string
	 */
	public static function issuer() {
		return untrailingslashit( home_url( '/' . Pairing::SITE_ENDPOINT_PATH ) );
	}

	/**
	 * The protected resource identifier — the MCP endpoint URL.
	 *
	 * @return string
	 */
	public static function resource() {
		return Pairing::site_endpoint();
	}

	/**
	 * The browser-facing authorize page. Served OUTSIDE the REST API (via a
	 * rewrite rule) so standard cookie auth works after the wp-login
	 * round-trip — a REST route would see the cookie without a nonce and treat
	 * the admin as logged-out, looping back to login.
	 *
	 * @return string
	 */
	public static function authorize_url() {
		return home_url( '/schedulepress/authorize' );
	}

	/**
	 * The token endpoint URL.
	 *
	 * @return string
	 */
	public static function token_url() {
		return rest_url( WPSP_PLUGIN_SLUG . '/v1/mcp/oauth/token' );
	}

	/**
	 * The dynamic client registration endpoint URL.
	 *
	 * @return string
	 */
	public static function register_url() {
		return rest_url( WPSP_PLUGIN_SLUG . '/v1/mcp/oauth/register' );
	}

	// -- Discovery documents (RFC 8414 / RFC 9728) -----------------------

	/**
	 * RFC 9728 protected-resource metadata — tells the client which
	 * authorization server(s) protect the MCP endpoint (this site).
	 *
	 * @return array
	 */
	public static function protected_resource_metadata() {
		return array(
			'resource'                 => self::resource(),
			'authorization_servers'    => array( self::issuer() ),
			'scopes_supported'         => self::SUPPORTED_SCOPES,
			'bearer_methods_supported' => array( 'header' ),
		);
	}

	/**
	 * RFC 8414 authorization-server metadata — the endpoint map + the
	 * capabilities we actually implement.
	 *
	 * @return array
	 */
	public static function authorization_server_metadata() {
		return array(
			'issuer'                                => self::issuer(),
			'authorization_endpoint'                => self::authorize_url(),
			'token_endpoint'                        => self::token_url(),
			'registration_endpoint'                 => self::register_url(),
			'scopes_supported'                      => self::SUPPORTED_SCOPES,
			'response_types_supported'              => array( 'code' ),
			'grant_types_supported'                 => array( 'authorization_code', 'refresh_token' ),
			'code_challenge_methods_supported'      => array( 'S256' ),
			'token_endpoint_auth_methods_supported' => array( 'none' ),
		);
	}

	// -- Dynamic client registration (RFC 7591) --------------------------

	/**
	 * Register a public client. We accept the client's redirect_uris and mint a
	 * client_id (no secret — public clients rely on PKCE).
	 *
	 * @param array $body Parsed JSON registration request.
	 * @return array|\WP_Error
	 */
	public static function register_client( array $body ) {
		$redirect_uris = isset( $body['redirect_uris'] ) && is_array( $body['redirect_uris'] )
			? array_values( array_filter( array_map( 'strval', $body['redirect_uris'] ), array( __CLASS__, 'is_valid_redirect_uri' ) ) )
			: array();

		if ( empty( $redirect_uris ) ) {
			return new \WP_Error(
				'invalid_redirect_uri',
				__( 'At least one valid redirect_uri is required.', 'wp-scheduled-posts' ),
				array( 'status' => 400 )
			);
		}

		$name      = isset( $body['client_name'] ) ? sanitize_text_field( (string) $body['client_name'] ) : 'MCP Client';
		$client_id = 'wpsp_' . bin2hex( random_bytes( 16 ) );

		$state                          = self::state();
		$state['clients'][ $client_id ] = array(
			'redirect_uris' => $redirect_uris,
			'name'          => $name,
			'created'       => time(),
		);
		$state['clients']               = self::prune_clients( $state );
		self::save( $state );

		return array(
			'client_id'                  => $client_id,
			'client_id_issued_at'        => time(),
			'redirect_uris'              => $redirect_uris,
			'client_name'                => $name,
			'token_endpoint_auth_method' => 'none',
			'grant_types'                => array( 'authorization_code', 'refresh_token' ),
			'response_types'             => array( 'code' ),
		);
	}

	// -- Authorization endpoint ------------------------------------------

	/**
	 * Validate an /authorize request's parameters WITHOUT issuing anything.
	 * Returns a sanitized param bag on success, or WP_Error on a protocol
	 * violation. The caller decides how to surface it (redirect vs error page)
	 * based on whether redirect_uri is trustworthy.
	 *
	 * @param array $params Query params.
	 * @return array|\WP_Error
	 */
	public static function validate_authorize_request( array $params ) {
		$client_id     = isset( $params['client_id'] ) ? (string) $params['client_id'] : '';
		$redirect_uri  = isset( $params['redirect_uri'] ) ? (string) $params['redirect_uri'] : '';
		$response_type = isset( $params['response_type'] ) ? (string) $params['response_type'] : '';
		$challenge     = isset( $params['code_challenge'] ) ? (string) $params['code_challenge'] : '';
		$method        = isset( $params['code_challenge_method'] ) ? (string) $params['code_challenge_method'] : '';
		$scope         = isset( $params['scope'] ) ? (string) $params['scope'] : 'mcp';
		$state         = isset( $params['state'] ) ? (string) $params['state'] : '';

		$client = self::client( $client_id );
		if ( null === $client ) {
			return new \WP_Error( 'invalid_client', __( 'Unknown client_id.', 'wp-scheduled-posts' ), array( 'status' => 400 ) );
		}
		if ( ! in_array( $redirect_uri, $client['redirect_uris'], true ) ) {
			// redirect_uri mismatch must NOT redirect (open-redirect guard).
			return new \WP_Error( 'invalid_redirect_uri', __( 'redirect_uri does not match a registered value.', 'wp-scheduled-posts' ), array( 'status' => 400 ) );
		}
		if ( 'code' !== $response_type ) {
			return new \WP_Error(
				'unsupported_response_type',
				__( 'Only response_type=code is supported.', 'wp-scheduled-posts' ),
				array(
					'status'       => 400,
					'redirectable' => true,
				)
			);
		}
		// OAuth 2.1: PKCE S256 is mandatory for public clients.
		if ( 'S256' !== $method || '' === $challenge ) {
			return new \WP_Error(
				'invalid_request',
				__( 'PKCE with code_challenge_method=S256 is required.', 'wp-scheduled-posts' ),
				array(
					'status'       => 400,
					'redirectable' => true,
				)
			);
		}

		return array(
			'client_id'      => $client_id,
			'client_name'    => $client['name'],
			'redirect_uri'   => $redirect_uri,
			'code_challenge' => $challenge,
			'scope'          => self::normalize_scope( $scope ),
			'state'          => $state,
		);
	}

	/**
	 * Issue an authorization code after the admin approves consent. Binds the
	 * code to the client, redirect_uri, PKCE challenge, granted scope, and the
	 * approving user. Single-use, 60 s TTL.
	 *
	 * @param array $req     Output of validate_authorize_request().
	 * @param int   $user_id Approving admin user id.
	 * @return string The authorization code.
	 */
	public static function issue_code( array $req, $user_id ) {
		$code                    = bin2hex( random_bytes( 32 ) );
		$state                   = self::state();
		$state['codes'][ $code ] = array(
			'client_id'    => $req['client_id'],
			'redirect_uri' => $req['redirect_uri'],
			'challenge'    => $req['code_challenge'],
			'scope'        => $req['scope'],
			'user_id'      => (int) $user_id,
			'expires'      => time() + self::CODE_TTL,
		);
		self::save( $state );
		return $code;
	}

	// -- Token endpoint --------------------------------------------------

	/**
	 * Exchange an authorization code (+ PKCE verifier) for tokens, or a refresh
	 * token for a fresh access token.
	 *
	 * @param array $body POST body params.
	 * @return array|\WP_Error
	 */
	public static function exchange_token( array $body ) {
		$grant = isset( $body['grant_type'] ) ? (string) $body['grant_type'] : '';

		if ( 'authorization_code' === $grant ) {
			return self::grant_authorization_code( $body );
		}
		if ( 'refresh_token' === $grant ) {
			return self::grant_refresh_token( $body );
		}
		return self::oauth_error( 'unsupported_grant_type', 'Unsupported grant_type.' );
	}

	/**
	 * authorization_code grant: verify the code + PKCE, mint tokens.
	 *
	 * @param array $body POST body.
	 * @return array|\WP_Error
	 */
	private static function grant_authorization_code( array $body ) {
		$code         = isset( $body['code'] ) ? (string) $body['code'] : '';
		$client_id    = isset( $body['client_id'] ) ? (string) $body['client_id'] : '';
		$redirect_uri = isset( $body['redirect_uri'] ) ? (string) $body['redirect_uri'] : '';
		$verifier     = isset( $body['code_verifier'] ) ? (string) $body['code_verifier'] : '';

		$state = self::state();
		if ( '' === $code || ! isset( $state['codes'][ $code ] ) ) {
			return self::oauth_error( 'invalid_grant', 'Unknown or expired authorization code.' );
		}
		$entry = $state['codes'][ $code ];

		// Single-use: remove immediately whether or not verification passes.
		unset( $state['codes'][ $code ] );
		self::save( $state );

		if ( $entry['expires'] < time() ) {
			return self::oauth_error( 'invalid_grant', 'Authorization code expired.' );
		}
		if ( ! hash_equals( (string) $entry['client_id'], $client_id ) ) {
			return self::oauth_error( 'invalid_grant', 'client_id mismatch.' );
		}
		if ( ! hash_equals( (string) $entry['redirect_uri'], $redirect_uri ) ) {
			return self::oauth_error( 'invalid_grant', 'redirect_uri mismatch.' );
		}
		// PKCE S256: BASE64URL(SHA256(verifier)) must equal the stored challenge.
		if ( '' === $verifier || ! hash_equals( (string) $entry['challenge'], self::s256( $verifier ) ) ) {
			return self::oauth_error( 'invalid_grant', 'PKCE verification failed.' );
		}

		return self::mint_tokens( (string) $entry['client_id'], (string) $entry['scope'], (int) $entry['user_id'] );
	}

	/**
	 * refresh_token grant: rotate the refresh token, issue a fresh access
	 * token. The old refresh + its access token are revoked.
	 *
	 * @param array $body POST body.
	 * @return array|\WP_Error
	 */
	private static function grant_refresh_token( array $body ) {
		$refresh   = isset( $body['refresh_token'] ) ? (string) $body['refresh_token'] : '';
		$client_id = isset( $body['client_id'] ) ? (string) $body['client_id'] : '';

		$state = self::state();
		$rhash = self::hash( $refresh );
		if ( '' === $refresh || ! isset( $state['refresh'][ $rhash ] ) ) {
			return self::oauth_error( 'invalid_grant', 'Unknown refresh token.' );
		}
		$entry = $state['refresh'][ $rhash ];
		if ( '' !== $client_id && ! hash_equals( (string) $entry['client_id'], $client_id ) ) {
			return self::oauth_error( 'invalid_grant', 'client_id mismatch.' );
		}

		// Rotate: drop old refresh + its access token.
		unset( $state['refresh'][ $rhash ] );
		if ( isset( $entry['access_hash'] ) ) {
			unset( $state['tokens'][ $entry['access_hash'] ] );
		}
		self::save( $state );

		return self::mint_tokens( (string) $entry['client_id'], (string) $entry['scope'], (int) $entry['user_id'] );
	}

	/**
	 * Mint an access + refresh token pair, store them hashed, and return the
	 * RFC 6749 token response with the raw values.
	 *
	 * @param string $client_id Client id.
	 * @param string $scope     Granted scope string.
	 * @param int    $user_id   Resource-owner user id.
	 * @return array
	 */
	private static function mint_tokens( $client_id, $scope, $user_id ) {
		$access  = bin2hex( random_bytes( 32 ) );
		$refresh = bin2hex( random_bytes( 32 ) );
		$ahash   = self::hash( $access );
		$rhash   = self::hash( $refresh );

		$state                      = self::state();
		$state['tokens'][ $ahash ]  = array(
			'client_id' => $client_id,
			'scope'     => $scope,
			'user_id'   => $user_id,
			'expires'   => time() + self::ACCESS_TTL,
			'refresh'   => $rhash,
		);
		$state['refresh'][ $rhash ] = array(
			'access_hash' => $ahash,
			'client_id'   => $client_id,
			'scope'       => $scope,
			'user_id'     => $user_id,
			'expires'     => time() + self::REFRESH_TTL,
		);
		self::save( $state );

		return array(
			'access_token'  => $access,
			'token_type'    => 'Bearer',
			'expires_in'    => self::ACCESS_TTL,
			'refresh_token' => $refresh,
			'scope'         => $scope,
		);
	}

	// -- Access-token validation (called by Server) ----------------------

	/**
	 * Validate a bearer access token presented to the MCP endpoint. Returns the
	 * token's grant record (scope, user_id, client_id) when valid + unexpired,
	 * or null. Constant-time via hashed lookup.
	 *
	 * @param string $token Raw access token from the Authorization header.
	 * @return array|null
	 */
	public static function validate_token( $token ) {
		if ( '' === $token ) {
			return null;
		}
		$state = self::state();
		$hash  = self::hash( $token );
		if ( ! isset( $state['tokens'][ $hash ] ) ) {
			return null;
		}
		$entry = $state['tokens'][ $hash ];
		if ( (int) $entry['expires'] < time() ) {
			return null;
		}

		// Record activity against the owning client so the "Connected AI apps"
		// list can show a last-used date. Throttled + stored on the client
		// record so it survives access-token rotation.
		$client_id = (string) $entry['client_id'];
		$now       = time();
		if ( isset( $state['clients'][ $client_id ] ) && is_array( $state['clients'][ $client_id ] ) ) {
			$last = isset( $state['clients'][ $client_id ]['last_used'] ) ? (int) $state['clients'][ $client_id ]['last_used'] : 0;
			if ( $now - $last >= self::LAST_USED_THROTTLE ) {
				$state['clients'][ $client_id ]['last_used'] = $now;
				self::save( $state );
			}
		}

		return array(
			'client_id' => $client_id,
			'scope'     => (string) $entry['scope'],
			'user_id'   => (int) $entry['user_id'],
		);
	}

	/**
	 * Whether a granted scope string is read-only. `mcp` is the umbrella scope
	 * that grants read+write, so only a grant that carries NEITHER `write` NOR
	 * `mcp` — i.e. `read` alone — is read-only.
	 *
	 * @param string $scope Space-separated scope string.
	 * @return bool
	 */
	public static function scope_is_read_only( $scope ) {
		$parts = preg_split( '/\s+/', trim( (string) $scope ) );
		$parts = is_array( $parts ) ? $parts : array();
		return ! in_array( 'write', $parts, true ) && ! in_array( 'mcp', $parts, true );
	}

	/**
	 * Revoke every OAuth token + client (used by disconnect).
	 *
	 * @return void
	 */
	public static function revoke_all() {
		delete_option( self::OPTION );
	}

	/**
	 * The OAuth clients currently holding a live grant, for the "Connected AI
	 * apps" list. A client counts as connected while it holds an unexpired
	 * refresh token (the durable 30-day grant) or access token; a client that
	 * only registered but never completed consent is excluded. One entry per
	 * client_id, newest connection first.
	 *
	 * @return array
	 */
	public static function connected_apps() {
		$state = self::state();

		// Collect the scope + approving user per active client. Refresh tokens
		// are the durable grant, so prefer them; fall back to access tokens.
		$active = array();
		foreach ( array( 'refresh', 'tokens' ) as $bucket ) {
			foreach ( $state[ $bucket ] as $entry ) {
				$cid = isset( $entry['client_id'] ) ? (string) $entry['client_id'] : '';
				if ( '' === $cid || isset( $active[ $cid ] ) ) {
					continue;
				}
				$active[ $cid ] = array(
					'scope'   => isset( $entry['scope'] ) ? (string) $entry['scope'] : 'mcp',
					'user_id' => isset( $entry['user_id'] ) ? (int) $entry['user_id'] : 0,
				);
			}
		}

		$apps = array();
		foreach ( $active as $cid => $info ) {
			$client = isset( $state['clients'][ $cid ] ) && is_array( $state['clients'][ $cid ] ) ? $state['clients'][ $cid ] : array();
			$apps[] = array(
				'client_id'    => $cid,
				'name'         => isset( $client['name'] ) ? (string) $client['name'] : __( 'MCP Client', 'wp-scheduled-posts' ),
				'scope'        => $info['scope'],
				'read_only'    => self::scope_is_read_only( $info['scope'] ),
				'user_id'      => $info['user_id'],
				'connected_at' => isset( $client['created'] ) ? (int) $client['created'] : 0,
				'last_used'    => isset( $client['last_used'] ) ? (int) $client['last_used'] : 0,
			);
		}

		// Newest connection first.
		usort(
			$apps,
			function ( $a, $b ) {
				if ( $a['connected_at'] === $b['connected_at'] ) {
					return 0;
				}
				return ( $a['connected_at'] < $b['connected_at'] ) ? 1 : -1;
			}
		);

		return $apps;
	}

	/**
	 * Revoke a single OAuth client's ACCESS — drops its access tokens, refresh
	 * tokens, and any pending codes, cutting that one app off immediately while
	 * leaving every other connection intact. It disappears from
	 * connected_apps() (which keys off live tokens), so the UI shows it gone.
	 *
	 * The client's dynamic registration (its client_id + redirect_uris) is
	 * intentionally KEPT: MCP clients cache the client_id from their first
	 * registration and reuse it on reconnect, hitting /authorize with that id
	 * rather than registering afresh. If we deleted the registration, that
	 * reconnect would fail with "Unknown client_id". Keeping it lets the app
	 * re-authorize — which still requires fresh admin consent (and mints
	 * brand-new tokens), so revocation loses nothing.
	 *
	 * @param string $client_id The client whose access to revoke.
	 * @return bool True if any live grant was removed.
	 */
	public static function revoke_client( $client_id ) {
		if ( '' === $client_id ) {
			return false;
		}
		$state   = self::state();
		$removed = false;

		foreach ( array( 'tokens', 'refresh', 'codes' ) as $bucket ) {
			foreach ( $state[ $bucket ] as $key => $entry ) {
				if ( isset( $entry['client_id'] ) && (string) $entry['client_id'] === $client_id ) {
					unset( $state[ $bucket ][ $key ] );
					$removed = true;
				}
			}
		}

		if ( $removed ) {
			self::save( $state );
		}
		return $removed;
	}

	// -- State + helpers -------------------------------------------------

	/**
	 * Load state with defaults, pruning expired codes/tokens/refresh entries on
	 * the way out so the option can't grow unbounded.
	 *
	 * @return array
	 */
	private static function state() {
		$stored = get_option( self::OPTION, array() );
		if ( ! is_array( $stored ) ) {
			$stored = array();
		}
		$state = array(
			'clients' => isset( $stored['clients'] ) && is_array( $stored['clients'] ) ? $stored['clients'] : array(),
			'codes'   => isset( $stored['codes'] ) && is_array( $stored['codes'] ) ? $stored['codes'] : array(),
			'tokens'  => isset( $stored['tokens'] ) && is_array( $stored['tokens'] ) ? $stored['tokens'] : array(),
			'refresh' => isset( $stored['refresh'] ) && is_array( $stored['refresh'] ) ? $stored['refresh'] : array(),
		);

		$now = time();
		foreach ( $state['codes'] as $k => $v ) {
			if ( ! isset( $v['expires'] ) || $v['expires'] < $now ) {
				unset( $state['codes'][ $k ] );
			}
		}
		foreach ( $state['tokens'] as $k => $v ) {
			if ( ! isset( $v['expires'] ) || $v['expires'] < $now ) {
				unset( $state['tokens'][ $k ] );
			}
		}
		foreach ( $state['refresh'] as $k => $v ) {
			if ( isset( $v['expires'] ) && $v['expires'] < $now ) {
				unset( $state['refresh'][ $k ] );
			}
		}
		return $state;
	}

	/**
	 * Persist state (autoload off — hot-write, request-scoped option).
	 *
	 * @param array $state State to persist.
	 * @return void
	 */
	private static function save( array $state ) {
		update_option( self::OPTION, $state, false );
	}

	/**
	 * Look up a registered client.
	 *
	 * @param string $client_id Client id.
	 * @return array|null
	 */
	private static function client( $client_id ) {
		if ( '' === $client_id ) {
			return null;
		}
		$state   = self::state();
		$clients = $state['clients'];
		if ( ! isset( $clients[ $client_id ] ) || ! is_array( $clients[ $client_id ] ) ) {
			return null;
		}
		$c = $clients[ $client_id ];
		return array(
			'redirect_uris' => isset( $c['redirect_uris'] ) && is_array( $c['redirect_uris'] ) ? array_map( 'strval', $c['redirect_uris'] ) : array(),
			'name'          => isset( $c['name'] ) ? (string) $c['name'] : 'MCP Client',
			'created'       => isset( $c['created'] ) ? (int) $c['created'] : 0,
		);
	}

	/**
	 * Bound the registered-client list. Drops abandoned registrations past
	 * CLIENT_TTL first, then — if still over MAX_CLIENTS — the oldest of what is
	 * left. A client referenced by a live code, access token, or refresh token
	 * is NEVER dropped: evicting one would break a working connection, so a
	 * site legitimately holding more than MAX_CLIENTS live grants keeps them all
	 * and the cap simply stops applying to that remainder.
	 *
	 * @param array $state Full state (clients + grant buckets).
	 * @return array The clients array to store.
	 */
	private static function prune_clients( array $state ) {
		$clients = $state['clients'];

		$in_use = array();
		foreach ( array( 'codes', 'tokens', 'refresh' ) as $bucket ) {
			foreach ( $state[ $bucket ] as $entry ) {
				if ( is_array( $entry ) && isset( $entry['client_id'] ) ) {
					$in_use[ (string) $entry['client_id'] ] = true;
				}
			}
		}

		$now = time();
		foreach ( $clients as $id => $client ) {
			$created = isset( $client['created'] ) ? (int) $client['created'] : 0;
			if ( ! isset( $in_use[ $id ] ) && $created + self::CLIENT_TTL < $now ) {
				unset( $clients[ $id ] );
			}
		}

		if ( count( $clients ) <= self::MAX_CLIENTS ) {
			return $clients;
		}

		// Still over the cap — evict the oldest unused registrations.
		$evictable = array_filter(
			$clients,
			function ( $id ) use ( $in_use ) {
				return ! isset( $in_use[ $id ] );
			},
			ARRAY_FILTER_USE_KEY
		);
		uasort(
			$evictable,
			function ( $a, $b ) {
				$ac = isset( $a['created'] ) ? (int) $a['created'] : 0;
				$bc = isset( $b['created'] ) ? (int) $b['created'] : 0;
				if ( $ac === $bc ) {
					return 0;
				}
				return ( $ac < $bc ) ? -1 : 1;
			}
		);
		foreach ( array_keys( $evictable ) as $id ) {
			if ( count( $clients ) <= self::MAX_CLIENTS ) {
				break;
			}
			unset( $clients[ $id ] );
		}

		return $clients;
	}

	/**
	 * SHA-256 hash used to store tokens at rest.
	 *
	 * @param string $value Raw secret.
	 * @return string
	 */
	private static function hash( $value ) {
		return hash( 'sha256', (string) $value );
	}

	/**
	 * BASE64URL(SHA256(verifier)) — the PKCE S256 transformation.
	 *
	 * @param string $verifier PKCE code verifier.
	 * @return string
	 */
	private static function s256( $verifier ) {
		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- base64url of the PKCE challenge, mandated by RFC 7636.
		return rtrim( strtr( base64_encode( hash( 'sha256', (string) $verifier, true ) ), '+/', '-_' ), '=' );
	}

	/**
	 * Constrain a requested scope to what we support. Defaults to `mcp`
	 * (read+write umbrella).
	 *
	 * @param string $requested Requested scope string.
	 * @return string
	 */
	private static function normalize_scope( $requested ) {
		$parts = preg_split( '/\s+/', trim( (string) $requested ) );
		$parts = is_array( $parts ) ? $parts : array();
		$parts = array_values( array_intersect( $parts, self::SUPPORTED_SCOPES ) );
		if ( empty( $parts ) ) {
			return 'mcp';
		}
		return implode( ' ', $parts );
	}

	/**
	 * Whether a redirect_uri is structurally acceptable (http(s) or a
	 * native-client custom scheme).
	 *
	 * @param string $uri Candidate redirect URI.
	 * @return bool
	 */
	private static function is_valid_redirect_uri( $uri ) {
		$uri = trim( (string) $uri );
		if ( '' === $uri ) {
			return false;
		}
		return (bool) preg_match( '#^[a-zA-Z][a-zA-Z0-9+.\-]*://#', $uri );
	}

	/**
	 * Build a WP_Error whose data carries an OAuth 2.0 `error` code so the
	 * token route can render the RFC 6749 error body.
	 *
	 * @param string $code    OAuth error code (invalid_grant, ...).
	 * @param string $message Human-readable description.
	 * @return \WP_Error
	 */
	private static function oauth_error( $code, $message ) {
		return new \WP_Error(
			$code,
			$message,
			array(
				'status'            => 400,
				'error'             => $code,
				'error_description' => $message,
			)
		);
	}
}
