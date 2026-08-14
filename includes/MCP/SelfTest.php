<?php
/**
 * MCP connection self-test.
 *
 * @package WPSP\MCP
 */

namespace WPSP\MCP;

use WPSP\Abilities\Registrar;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Exercises the MCP round trip the way an external client would and reports
 * *where* it broke, so an admin can tell a certificate problem from an
 * authentication problem from an ability-discovery problem without leaving the
 * MCP page.
 *
 * Checks, each against a surface a real client actually uses:
 *
 *   1. `endpoint`  — the pretty URL the user pastes (/schedulepress/mcp), with
 *                    the connection token. Depends on rewrite rules, so it
 *                    fails on plain permalinks or an unflushed rule table.
 *   2. `fallback`  — the always-on /wp-json/… route. Works even when rewrites
 *                    do not, which is what separates "the whole MCP surface is
 *                    down" from "only the pretty URL is".
 *   3. `discovery` — the RFC 9728 / RFC 8414 metadata documents.
 *   4. `challenge` — an UNAUTHENTICATED call, which must answer 401 with a
 *                    WWW-Authenticate header. This is the only thing an
 *                    OAuth-only client has to go on: if the challenge is
 *                    missing it reports that the server does not implement
 *                    OAuth, no matter how healthy the rest is.
 *
 * The staged result names the first failing step: `disabled` → `not_connected`
 * → `unreachable` → `tls` → `redirect` → `auth` → `no_tools` → `rewrite` →
 * `discovery` → `challenge` → `ok`.
 */
final class SelfTest {

	/**
	 * User agents to replay the challenge probe with, to detect a host that
	 * filters by User-Agent. These are the shapes real MCP backends send — none
	 * of them is a browser, which is exactly what "block bad bots" rules key on.
	 * A site that answers WordPress's own UA but 403s these is unreachable for
	 * every AI client while looking perfectly healthy from inside.
	 */
	private static $client_user_agents = array(
		'python-requests/2.32.3',
		'node-fetch/3.3.2',
	);

	/**
	 * Run the round-trip self-test.
	 *
	 * @return array
	 */
	public static function run() {
		$endpoint = Pairing::site_endpoint();
		$fallback = Pairing::site_endpoint_fallback();

		$result = array(
			'ok'                  => false,
			'stage'               => '',
			'message'             => '',
			'endpoint'            => $endpoint,
			'endpoint_rest'       => $fallback,
			'mcp_enabled'         => Manager::is_enabled(),
			'connected'           => Pairing::is_connected(),
			'http_status'         => null,
			'redirected'          => false,
			'authenticated'       => false,
			'tools_count'         => null,
			'checks'              => array(),
			// url => decoded metadata (or the raw body when it isn't JSON).
			'discovery_documents' => array(),
		);

		if ( ! $result['mcp_enabled'] ) {
			$result['stage']   = 'disabled';
			$result['message'] = __( 'MCP access is turned off, so the endpoint refuses every request. Enable MCP access above and try again.', 'wp-scheduled-posts' );
			return $result;
		}

		if ( ! $result['connected'] ) {
			$result['stage']   = 'not_connected';
			$result['message'] = __( 'No connection token exists yet. Click Connect to mint one, then run the test again.', 'wp-scheduled-posts' );
			return $result;
		}

		$token = Pairing::site_token();

		$pretty = self::probe_jsonrpc( $endpoint, $token );
		$rest   = self::probe_jsonrpc( $fallback, $token );

		// Back-compat top-level fields describe the primary (pretty) endpoint,
		// falling back to the REST route when the pretty URL never answered.
		$primary                 = 'unreachable' === $pretty['stage'] ? $rest : $pretty;
		$result['http_status']   = $primary['status'];
		$result['redirected']    = 'redirect' === $primary['stage'];
		$result['authenticated'] = $primary['authenticated'];
		$result['tools_count']   = $primary['tools'];

		$result['checks'][] = self::check( 'endpoint', __( 'Connection URL', 'wp-scheduled-posts' ), $pretty['stage'], $pretty['detail'] );
		$result['checks'][] = self::check( 'fallback', __( 'REST fallback URL', 'wp-scheduled-posts' ), $rest['stage'], $rest['detail'] );

		$discovery = self::probe_discovery();
		// The documents themselves, so support can read what the site actually
		// serves instead of asking the customer for screenshots.
		$result['discovery_documents'] = $discovery['documents'];
		$result['checks'][]            = self::check( 'discovery', __( 'OAuth discovery', 'wp-scheduled-posts' ), $discovery['stage'], $discovery['detail'] );

		$challenge          = self::probe_challenge( $endpoint, $fallback );
		$result['checks'][] = self::check( 'challenge', __( 'OAuth challenge', 'wp-scheduled-posts' ), $challenge['stage'], $challenge['detail'] );

		// Only reported when it could actually run — claiming a pass we did not
		// measure is the failure mode this whole test exists to avoid.
		$user_agent = self::probe_user_agent( $endpoint );
		if ( null !== $user_agent ) {
			$result['checks'][] = self::check( 'user_agent', __( 'Client access', 'wp-scheduled-posts' ), $user_agent['stage'], $user_agent['detail'] );
		}

		// Locked-out clients. The loopback below can pass while a REMOTE client
		// is walled off by the failed-auth limiter — the exact state a connector
		// still holding a rotated-away token produces. Reported only when the
		// count is knowable (null under a persistent object cache).
		$lockouts                 = RateLimiter::active_lockouts();
		$result['locked_clients'] = $lockouts;
		if ( null !== $lockouts && $lockouts > 0 ) {
			$result['checks'][] = self::check(
				'lockouts',
				__( 'Client lockouts', 'wp-scheduled-posts' ),
				'locked_clients',
				sprintf(
					/* translators: %d: number of currently locked-out clients. */
					_n(
						'%d client is currently locked out after repeated failed authentications — typically a connector still holding a rotated-away token. Remove and re-add the connector in the AI client; the lockout clears itself within 15 minutes of the retries stopping.',
						'%d clients are currently locked out after repeated failed authentications — typically connectors still holding a rotated-away token. Remove and re-add the connector in the AI client; lockouts clear within 15 minutes of the retries stopping.',
						$lockouts,
						'wp-scheduled-posts'
					),
					$lockouts
				)
			);
		}

		// The pretty URL failing while the fallback works is its own finding:
		// the site is usable, but only via the REST URL.
		if ( 'ok' !== $pretty['stage'] && 'ok' === $rest['stage'] ) {
			$result['stage']   = 'rewrite';
			$result['message'] = sprintf(
				/* translators: 1: pretty MCP endpoint URL, 2: REST fallback URL. */
				__( 'The connection URL %1$s did not answer, but the REST fallback %2$s works. Re-save Settings → Permalinks to rebuild the rewrite rules; until then, give your AI client the fallback URL.', 'wp-scheduled-posts' ),
				$endpoint,
				$fallback
			);
			return $result;
		}

		// Otherwise report the first failing check in order.
		foreach ( array( $pretty, $rest, $discovery, $challenge, $user_agent ) as $check ) {
			if ( null === $check ) {
				continue;
			}
			if ( 'ok' !== $check['stage'] ) {
				$result['stage']   = $check['stage'];
				$result['message'] = $check['detail'];
				return $result;
			}
		}

		$result['ok']      = true;
		$result['stage']   = 'ok';
		$result['message'] = sprintf(
			/* translators: %d: number of MCP tools returned. */
			_n(
				'Connection healthy: the endpoint authenticated, offered OAuth, and returned %d tool.',
				'Connection healthy: the endpoint authenticated, offered OAuth, and returned %d tools.',
				(int) $result['tools_count'],
				'wp-scheduled-posts'
			),
			(int) $result['tools_count']
		);
		return $result;
	}

	// -- Probes ------------------------------------------------------------

	/**
	 * One authenticated JSON-RPC `tools/list` round trip.
	 *
	 * @param string $url   Endpoint to call.
	 * @param string $token Connection token.
	 * @return array
	 */
	private static function probe_jsonrpc( $url, $token ) {
		$response = wp_remote_post(
			$url,
			array(
				'timeout'     => 10,
				// Don't follow redirects: a 301/302 here IS the finding (the
				// classic http<->https scheme bounce), so surface it verbatim.
				'redirection' => 0,
				'headers'     => array(
					'Authorization' => 'Bearer ' . $token,
					'Content-Type'  => 'application/json',
					'Accept'        => 'application/json',
				),
				'body'        => wp_json_encode(
					array(
						'jsonrpc' => '2.0',
						'id'      => 1,
						'method'  => 'tools/list',
					)
				),
			)
		);

		$out = array(
			'stage'         => 'ok',
			'status'        => null,
			'tools'         => null,
			'authenticated' => false,
			'detail'        => '',
		);

		if ( is_wp_error( $response ) ) {
			$err           = $response->get_error_message();
			$is_tls        = false !== stripos( $err, 'ssl' ) || false !== stripos( $err, 'certificate' );
			$out['stage']  = $is_tls ? 'tls' : 'unreachable';
			$out['detail'] = $is_tls
				/* translators: 1: endpoint URL, 2: underlying transport error. */
				? sprintf( __( '%1$s could not be reached over HTTPS: %2$s. On local/dev sites this is usually a self-signed certificate the AI client must be told to trust.', 'wp-scheduled-posts' ), $url, $err )
				/* translators: 1: endpoint URL, 2: underlying transport error. */
				: sprintf( __( '%1$s could not be reached: %2$s.', 'wp-scheduled-posts' ), $url, $err );
			return $out;
		}

		$status        = (int) wp_remote_retrieve_response_code( $response );
		$out['status'] = $status;

		if ( in_array( $status, array( 301, 302, 307, 308 ), true ) ) {
			$location      = (string) wp_remote_retrieve_header( $response, 'location' );
			$out['stage']  = 'redirect';
			$out['detail'] = $location
				/* translators: 1: endpoint URL, 2: redirect target URL. */
				? sprintf( __( '%1$s redirected to %2$s instead of answering. A redirect between HTTP and HTTPS usually means the site address and WordPress address schemes disagree.', 'wp-scheduled-posts' ), $url, $location )
				/* translators: %s: endpoint URL. */
				: sprintf( __( '%s redirected instead of answering, which usually means the site address and WordPress address schemes disagree.', 'wp-scheduled-posts' ), $url );
			return $out;
		}

		if ( 404 === $status ) {
			$out['stage']  = 'rewrite';
			$out['detail'] = sprintf(
				/* translators: %s: endpoint URL. */
				__( '%s returned 404 — WordPress does not know this URL. Re-save Settings → Permalinks to rebuild the rewrite rules.', 'wp-scheduled-posts' ),
				$url
			);
			return $out;
		}

		if ( 401 === $status || 403 === $status ) {
			$out['stage']  = 'auth';
			$out['detail'] = sprintf(
				/* translators: %s: endpoint URL. */
				__( '%s rejected the connection token (authentication failed). Rotate the token and reconnect your AI client.', 'wp-scheduled-posts' ),
				$url
			);
			return $out;
		}

		if ( 429 === $status ) {
			$out['stage']  = 'auth';
			$out['detail'] = sprintf(
				/* translators: %s: endpoint URL. */
				__( '%s is rate-limiting this server after repeated failed tokens. Wait for the lockout to lapse, then rotate the token and reconnect.', 'wp-scheduled-posts' ),
				$url
			);
			return $out;
		}

		$out['authenticated'] = true;
		$body                 = json_decode( (string) wp_remote_retrieve_body( $response ), true );
		$tools                = ( is_array( $body ) && isset( $body['result']['tools'] ) && is_array( $body['result']['tools'] ) )
			? $body['result']['tools']
			: null;

		// An EMPTY tools array counts as a failure, not a pass: that is the
		// shape of a connection an AI client reports as healthy while it has
		// nothing to call. The abilities snapshot goes into the detail so
		// support can tell "no abilities registered" (a foreign Abilities API
		// copy owns the registry) from "the runtime is missing entirely".
		if ( 200 !== $status || null === $tools || array() === $tools ) {
			$out['stage']  = 'no_tools';
			$out['tools']  = is_array( $tools ) ? count( $tools ) : 0;
			$out['detail'] = sprintf(
				/* translators: 1: endpoint URL, 2: abilities-registry diagnostic summary. */
				__( '%1$s answered but returned no tool catalog. Confirm the MCP runtime is built and abilities are registered. Diagnostics — %2$s', 'wp-scheduled-posts' ),
				$url,
				Registrar::summary()
			);
			return $out;
		}

		$out['tools']  = count( $tools );
		$out['detail'] = sprintf(
			/* translators: 1: endpoint URL, 2: number of tools. */
			__( '%1$s authenticated and returned %2$d tools.', 'wp-scheduled-posts' ),
			$url,
			count( $tools )
		);
		return $out;
	}

	/**
	 * Fetch both OAuth discovery documents and confirm they are served and
	 * well-formed. An OAuth client reads these before it holds any credential,
	 * so a 404 here is invisible to every other check.
	 *
	 * @return array
	 */
	private static function probe_discovery() {
		// Each document must carry an identifier EXACTLY equal to the one we
		// compute locally. A mere "the key exists" check passes on another
		// plugin's metadata served from the same /.well-known/ path, which is
		// the hijack case the rewrite rules already warn about.
		$docs = array(
			home_url( '/.well-known/oauth-protected-resource/' . Pairing::SITE_ENDPOINT_PATH )    => array(
				'key'      => 'resource',
				'expected' => Pairing::site_endpoint(),
			),
			home_url( '/.well-known/oauth-authorization-server/' . Pairing::SITE_ENDPOINT_PATH ) => array(
				'key'      => 'issuer',
				'expected' => OAuth::issuer(),
			),
		);

		$documents = array();

		foreach ( $docs as $url => $spec ) {
			$response = wp_remote_get(
				$url,
				array(
					'timeout'     => 10,
					'redirection' => 0,
				)
			);
			if ( is_wp_error( $response ) ) {
				return array(
					'stage'     => 'discovery',
					'documents' => $documents,
					'detail'    => sprintf(
						/* translators: 1: discovery document URL, 2: transport error. */
						__( 'The OAuth discovery document %1$s could not be fetched: %2$s. Clients that connect by URL alone cannot authenticate without it.', 'wp-scheduled-posts' ),
						$url,
						$response->get_error_message()
					),
				);
			}
			$status            = (int) wp_remote_retrieve_response_code( $response );
			$raw               = (string) wp_remote_retrieve_body( $response );
			$body              = json_decode( $raw, true );
			$documents[ $url ] = is_array( $body ) ? $body : $raw;

			if ( 200 !== $status || ! is_array( $body ) || ! isset( $body[ $spec['key'] ] ) ) {
				return array(
					'stage'     => 'discovery',
					'documents' => $documents,
					'detail'    => sprintf(
						/* translators: 1: discovery document URL, 2: HTTP status code. */
						__( 'The OAuth discovery document %1$s returned %2$d instead of valid metadata. Re-save Settings → Permalinks; if it persists, another plugin may be claiming the /.well-known/ URLs.', 'wp-scheduled-posts' ),
						$url,
						$status
					),
				);
			}

			$advertised = (string) $body[ $spec['key'] ];
			if ( $advertised !== $spec['expected'] ) {
				return array(
					'stage'     => 'discovery',
					'documents' => $documents,
					'detail'    => sprintf(
						/* translators: 1: metadata field name, 2: value found in the document, 3: value it should be, 4: discovery document URL. */
						__( 'The discovery document %4$s advertises %1$s "%2$s" but this site\'s MCP endpoint is "%3$s". RFC 9728 requires an exact match, so clients reject the metadata and report that the server does not implement OAuth. If the two differ only by scheme, a reverse proxy is terminating TLS without passing X-Forwarded-Proto; otherwise another plugin is serving this URL.', 'wp-scheduled-posts' ),
						$spec['key'],
						$advertised,
						$spec['expected'],
						$url
					),
				);
			}
		}

		// Both documents agree with us — but they agree on whatever home_url()
		// says, so a site whose stored URL is http:// while it actually serves
		// https:// is self-consistently wrong. Clients connect over https and
		// then reject the http identifier.
		$scheme_issue = self::probe_scheme();
		if ( null !== $scheme_issue ) {
			$scheme_issue['documents'] = $documents;
			return $scheme_issue;
		}

		return array(
			'stage'     => 'ok',
			'documents' => $documents,
			'detail'    => __( 'Both OAuth discovery documents are served and advertise this site\'s MCP endpoint exactly.', 'wp-scheduled-posts' ),
		);
	}

	/**
	 * Catch the reverse-proxy scheme trap: WordPress stores an http:// home
	 * URL, so every advertised OAuth identifier is http://, while the site is
	 * really served over https://. Everything is internally consistent, so no
	 * comparison against our own values can see it — the only tell is that the
	 * https:// variant of the endpoint answers too.
	 *
	 * @return array|null Null when nothing is wrong.
	 */
	private static function probe_scheme() {
		$endpoint = Pairing::site_endpoint();
		if ( 'https' === wp_parse_url( $endpoint, PHP_URL_SCHEME ) ) {
			return null;
		}

		$secure = set_url_scheme( $endpoint, 'https' );
		if ( null === self::probe_status( $secure, null ) ) {
			// No HTTPS at all. A plain-HTTP site is its own (reported) problem,
			// not the proxy misconfiguration this check is for.
			return null;
		}

		return array(
			'stage'  => 'discovery',
			'detail' => sprintf(
				/* translators: 1: http endpoint URL advertised, 2: https endpoint URL that also answers. */
				__( 'The discovery documents advertise %1$s, but %2$s answers as well — WordPress is storing an http:// site address behind a proxy that terminates TLS. AI clients connect over https and reject the http identifier as a mismatch. Fix the Site Address in Settings → General, or have the proxy send X-Forwarded-Proto.', 'wp-scheduled-posts' ),
				$endpoint,
				$secure
			),
		);
	}

	/**
	 * Confirm an unauthenticated call answers 401 WITH the RFC 9728
	 * WWW-Authenticate challenge. A client that connects by URL alone has
	 * nothing else to discover OAuth from — a bare 401, or any other status,
	 * reads to it as "this server does not implement OAuth".
	 *
	 * @param string $endpoint Pretty endpoint URL.
	 * @param string $fallback REST fallback URL.
	 * @return array
	 */
	private static function probe_challenge( $endpoint, $fallback ) {
		$answered = false;

		foreach ( array( $endpoint, $fallback ) as $url ) {
			$response = wp_remote_post(
				$url,
				array(
					'timeout'     => 10,
					'redirection' => 0,
					'headers'     => array(
						'Content-Type' => 'application/json',
						'Accept'       => 'application/json',
					),
					'body'        => wp_json_encode(
						array(
							'jsonrpc' => '2.0',
							'id'      => 1,
							'method'  => 'initialize',
							'params'  => array(),
						)
					),
				)
			);
			if ( is_wp_error( $response ) ) {
				continue; // Reachability is the other checks' job.
			}
			$answered = true;

			$status    = (int) wp_remote_retrieve_response_code( $response );
			$challenge = (string) wp_remote_retrieve_header( $response, 'www-authenticate' );

			if ( 401 !== $status ) {
				return array(
					'stage'  => 'challenge',
					'detail' => sprintf(
						/* translators: 1: endpoint URL, 2: HTTP status code. */
						__( 'An unauthenticated call to %1$s answered %2$d instead of 401. Clients that connect by URL alone need the 401 challenge to start the OAuth flow.', 'wp-scheduled-posts' ),
						$url,
						$status
					),
				);
			}
			if ( '' === $challenge ) {
				return array(
					'stage'  => 'challenge',
					'detail' => sprintf(
						/* translators: %s: endpoint URL. */
						__( '%s answered 401 but sent no WWW-Authenticate header — a security plugin or proxy is likely stripping it. Clients that connect by URL alone will report that this server does not implement OAuth.', 'wp-scheduled-posts' ),
						$url
					),
				);
			}

			// The challenge is only useful if the URL inside it resolves — that
			// URL is the client's entire entry point into the flow.
			if ( ! preg_match( '/resource_metadata="([^"]+)"/i', $challenge, $m ) ) {
				return array(
					'stage'  => 'challenge',
					'detail' => sprintf(
						/* translators: 1: endpoint URL, 2: the WWW-Authenticate header value received. */
						__( '%1$s sent a WWW-Authenticate header with no resource_metadata URL (%2$s). Clients have nowhere to look up this site\'s OAuth metadata.', 'wp-scheduled-posts' ),
						$url,
						$challenge
					),
				);
			}

			$metadata_url = $m[1];
			$metadata     = wp_remote_get(
				$metadata_url,
				array(
					'timeout'     => 10,
					'redirection' => 2, // A host-level redirect to the real doc is fine.
				)
			);
			$reachable    = ! is_wp_error( $metadata )
				&& 200 === (int) wp_remote_retrieve_response_code( $metadata )
				&& is_array( json_decode( (string) wp_remote_retrieve_body( $metadata ), true ) );

			if ( ! $reachable ) {
				return array(
					'stage'  => 'challenge',
					'detail' => sprintf(
						/* translators: 1: resource_metadata URL from the challenge header, 2: endpoint URL. */
						__( 'The challenge from %2$s points at %1$s, but that URL does not return OAuth metadata. This is the first thing a client fetches, so the connection fails there. On a subdirectory install the spec-derived URL sits at the domain root, which WordPress cannot serve — a root redirect to this URL is needed.', 'wp-scheduled-posts' ),
						$metadata_url,
						$url
					),
				);
			}
		}

		// Neither URL answered at all. Reporting `ok` here would be the exact
		// false pass this test exists to prevent — an unreachable endpoint is
		// not a passing challenge. The other checks name the reachability
		// failure, so this one only has to refuse to claim success.
		if ( ! $answered ) {
			return array(
				'stage'  => 'challenge',
				'detail' => __( 'The OAuth challenge could not be checked because the endpoint did not answer. Fix the connection error above and re-run the test.', 'wp-scheduled-posts' ),
			);
		}

		return array(
			'stage'  => 'ok',
			'detail' => __( 'Unauthenticated calls answer with the OAuth challenge, so URL-only clients can authenticate.', 'wp-scheduled-posts' ),
		);
	}

	/**
	 * Detect a host that answers WordPress but refuses AI clients by
	 * User-Agent. Replays the unauthenticated probe under the UAs a real MCP
	 * backend sends and compares against the baseline; a 403/406/503 that the
	 * baseline did not get is a bot filter, not a plugin problem.
	 *
	 * Blind spot worth stating plainly: this runs from the server's own IP,
	 * which host firewalls usually trust, so it catches UA filtering but NOT an
	 * IP-range block of the AI vendor. A green result here does not prove an
	 * external client can connect.
	 *
	 * @param string $endpoint Pretty endpoint URL.
	 * @return array|null Null when it could not run.
	 */
	private static function probe_user_agent( $endpoint ) {
		$baseline = self::probe_status( $endpoint, null );
		if ( null === $baseline ) {
			return null; // Endpoint unreachable — the other checks own that.
		}

		foreach ( self::$client_user_agents as $agent ) {
			$status = self::probe_status( $endpoint, $agent );
			if ( null === $status || $status === $baseline ) {
				continue;
			}
			// A different status is only damning when it is a refusal. An MCP
			// answer (401 challenge / 200 / 202) under any UA is fine.
			if ( in_array( $status, array( 200, 202, 401 ), true ) ) {
				continue;
			}
			return array(
				'stage'  => 'ua_filter',
				'detail' => sprintf(
					/* translators: 1: user agent string, 2: HTTP status returned for it, 3: HTTP status returned for WordPress's own user agent. */
					__( 'The endpoint answered %3$d for WordPress but %2$d for an AI client\'s User-Agent (%1$s). A security plugin, firewall or "block bad bots" rule is refusing non-browser clients — exempt the MCP and /.well-known/ paths, or no AI client will ever reach this site.', 'wp-scheduled-posts' ),
					$agent,
					$status,
					$baseline
				),
			);
		}

		return array(
			'stage'  => 'ok',
			'detail' => __( 'The endpoint answers AI-client User-Agents the same way it answers WordPress, so no bot filter is blocking them. This cannot see an IP-level block of the AI vendor.', 'wp-scheduled-posts' ),
		);
	}

	// -- Helpers -----------------------------------------------------------

	/**
	 * Status code of one unauthenticated probe, or null if it never answered.
	 *
	 * @param string      $url   Endpoint to call.
	 * @param string|null $agent User-Agent to send, or null for WordPress's own.
	 * @return int|null
	 */
	private static function probe_status( $url, $agent ) {
		$args = array(
			'timeout'     => 10,
			'redirection' => 0,
			'headers'     => array(
				'Content-Type' => 'application/json',
				'Accept'       => 'application/json',
			),
			'body'        => wp_json_encode(
				array(
					'jsonrpc' => '2.0',
					'id'      => 1,
					'method'  => 'initialize',
					'params'  => array(),
				)
			),
		);
		if ( null !== $agent ) {
			$args['user-agent'] = $agent;
		}

		$response = wp_remote_post( $url, $args );
		if ( is_wp_error( $response ) ) {
			return null;
		}
		return (int) wp_remote_retrieve_response_code( $response );
	}

	/**
	 * Shape one check for the UI list.
	 *
	 * @param string $id     Check id.
	 * @param string $label  Human label.
	 * @param string $stage  Resulting stage ('ok' when it passed).
	 * @param string $detail Explanatory line.
	 * @return array
	 */
	private static function check( $id, $label, $stage, $detail ) {
		return array(
			'id'     => $id,
			'label'  => $label,
			'ok'     => 'ok' === $stage,
			'detail' => $detail,
		);
	}
}
