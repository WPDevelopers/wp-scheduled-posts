<?php
/**
 * MCP manager — the site side of SchedulePress's MCP integration.
 *
 * The plugin speaks the MCP protocol DIRECTLY at this site's own URL. The user
 * pastes their own site's MCP endpoint + connection token into their AI client
 * — or, for OAuth-capable clients, just the URL:
 *
 *     https://thissite.com/schedulepress/mcp                    (pretty, via rewrite)
 *     https://thissite.com/wp-json/wp-scheduled-posts/v1/mcp    (always-on fallback)
 *
 * The MCP JSON-RPC handling lives in Server; the tool surface is the abilities
 * registry (Tools). Auth is the per-site connection token (Pairing) or an
 * OAuth 2.1 access token (OAuth).
 *
 * Admin-only management routes (manage_options) drive the MCP page:
 * /mcp/connection, /mcp/connect, /mcp/rotate, /mcp/disconnect, /mcp/self-test,
 * /mcp/apps, /mcp/settings.
 *
 * A single admin toggle (`enable_mcp`) is the master switch: when off, the MCP
 * endpoint, discovery documents, and OAuth endpoints all refuse to serve.
 *
 * @package WPSP\MCP
 */

namespace WPSP\MCP;

use WPSP\Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Registers the MCP endpoint, OAuth discovery/authorize/token surface, and the
 * admin management routes.
 */
final class Manager {

	/**
	 * Query var flagging a pretty /schedulepress/mcp request.
	 */
	const QUERY_VAR = 'wpsp_mcp';

	/**
	 * Query var carrying the token when embedded in the URL path.
	 */
	const TOKEN_QUERY_VAR = 'wpsp_mcp_token';

	/**
	 * Query var flagging a /.well-known/ OAuth discovery request.
	 */
	const WELLKNOWN_QUERY_VAR = 'wpsp_mcp_wellknown';

	/**
	 * Query var flagging the browser-facing OAuth authorize page. This is
	 * served OUTSIDE the REST API on purpose: a REST route only honors cookie
	 * auth when a REST nonce accompanies it, but a browser arriving from
	 * wp-login carries the cookie with NO nonce — so is_user_logged_in() would
	 * be false there and the consent screen would loop back to login forever. A
	 * normal front-end URL (rewrite + parse_request) sees standard cookie auth,
	 * so the logged-in admin check works.
	 */
	const AUTHORIZE_QUERY_VAR = 'wpsp_mcp_authorize';

	/**
	 * The REST namespace shared with the rest of the plugin.
	 *
	 * @return string
	 */
	private static function ns() {
		return WPSP_PLUGIN_SLUG . '/v1';
	}

	/**
	 * Initialize (called by the plugin bootstrap).
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'rest_api_init', array( $this, 'register_rest' ) );

		// Pretty per-site endpoint: /schedulepress/mcp → MCP JSON-RPC handler.
		add_action( 'init', array( $this, 'add_rewrite' ) );
		add_filter( 'query_vars', array( $this, 'register_query_var' ) );
		add_action( 'parse_request', array( $this, 'maybe_handle_pretty_endpoint' ) );

		// The MCP tab in SchedulePress → Settings. Added through the settings
		// app's own filter rather than by editing the field tree, so the whole
		// MCP feature stays in this directory.
		add_filter( 'wpsp_layout_tabs', array( $this, 'register_settings_tab' ) );

		// The one broken state the server can't see from inside a request: MCP
		// enabled but the bundled Abilities runtime absent. Everything else
		// still works — OAuth discovers, tokens mint, clients connect — and
		// tools/list is an empty array served as success. Three layers each
		// "no-op gracefully" and composed they manufacture a connector that
		// connects and offers nothing, with no signal anywhere. Say it loudly
		// where an admin will look.
		add_action( 'admin_notices', array( $this, 'warn_when_runtime_missing' ) );
	}

	/**
	 * Admin notice when MCP is enabled but the Abilities runtime is missing.
	 *
	 * That combination almost always means an incomplete package — a source
	 * archive or a zip built without `dependencies/vendor/`. Shown to admins on
	 * every screen: the fix is reinstalling the plugin, and a user mid-support
	 * ticket needs to see it without knowing which screen to visit.
	 *
	 * @return void
	 */
	public function warn_when_runtime_missing() {
		if ( ! self::is_enabled() || function_exists( 'wp_register_ability' ) ) {
			return;
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		printf(
			'<div class="notice notice-error"><p><strong>%s</strong> %s</p></div>',
			esc_html__( 'SchedulePress MCP: AI assistants will connect but see no tools.', 'wp-scheduled-posts' ),
			esc_html__( 'MCP access is enabled, but the bundled Abilities runtime (dependencies/vendor) is missing from this installation — usually a plugin package built without it. Reinstall SchedulePress from wordpress.org or an official build; until then, connected AI clients get an empty tool list.', 'wp-scheduled-posts' )
		);
	}

	/**
	 * Whether the MCP integration is enabled via the admin setting.
	 *
	 * @return bool
	 */
	public static function is_enabled() {
		return (bool) Helper::get_settings( 'enable_mcp' );
	}

	/**
	 * Flip the master toggle, persisting it into the plugin's settings option.
	 * Enabling also mints a connection token so the connect recipes are
	 * populated without a second click.
	 *
	 * @param bool $enabled Desired state.
	 * @return bool The state actually stored.
	 */
	public static function set_enabled( $enabled ) {
		$enabled  = (bool) $enabled;
		$settings = json_decode( get_option( WPSP_SETTINGS_NAME, '{}' ), true );
		if ( ! is_array( $settings ) ) {
			$settings = array();
		}
		$settings['enable_mcp'] = $enabled;
		update_option( WPSP_SETTINGS_NAME, wp_json_encode( $settings ) );

		if ( $enabled && ! Pairing::is_connected() ) {
			Pairing::connect();
		}

		return $enabled;
	}

	/**
	 * Add the MCP tab to the settings app's tab tree.
	 *
	 * The connection details are rendered server-side into an `html` field: the
	 * settings payload is already admin-gated, and this avoids a second
	 * round-trip just to show a URL. The token is only rendered once MCP is
	 * enabled, so a site that never turns the feature on never puts a secret on
	 * screen.
	 *
	 * @param array $tabs Existing tabs.
	 * @return array
	 */
	public function register_settings_tab( $tabs ) {
		if ( ! is_array( $tabs ) ) {
			return $tabs;
		}

		// One custom field renders the whole panel. Everything on it is live
		// connection state driven by the /mcp/* REST routes rather than form
		// values, so the section deliberately has no Save button — there is
		// nothing to save that the panel does not write itself.
		$tabs['layout_mcp'] = array(
			'id'       => 'layout_mcp',
			'name'     => 'layout_mcp',
			'label'    => __( 'AI (MCP)', 'wp-scheduled-posts' ),
			'priority' => 18,
			'fields'   => array(
				'mcp_section' => array(
					'name'       => 'mcp_section',
					'type'       => 'section',
					'label'      => null,
					'priority'   => 5,
					'showSubmit' => false,
					'fields'     => array(
						'mcp_panel' => array(
							'id'       => 'mcp_panel',
							'name'     => 'mcp_panel',
							'type'     => 'mcp',
							'label'    => null,
							'priority' => 5,
						),
					),
				),
			),
		);

		return $tabs;
	}

	// -- Pretty endpoint: /schedulepress/mcp --

	/**
	 * Register rewrite rules for the MCP endpoint, OAuth discovery documents,
	 * and the browser-facing authorize page.
	 *
	 * @return void
	 */
	public function add_rewrite() {
		// Token-in-URL form: /schedulepress/mcp/<token> — a single string the
		// user pastes into their AI client (no separate token field). The bare
		// /schedulepress/mcp still works with a Bearer token.
		add_rewrite_rule(
			'^schedulepress/mcp/([a-f0-9]{64})/?$',
			'index.php?' . self::QUERY_VAR . '=1&' . self::TOKEN_QUERY_VAR . '=$matches[1]',
			'top'
		);
		add_rewrite_rule( '^schedulepress/mcp/?$', 'index.php?' . self::QUERY_VAR . '=1', 'top' );

		// OAuth discovery documents. RFC 9728 §3.1 / RFC 8414 §3.1 place the
		// `.well-known` segment BEFORE the resource path, so our resource at
		// /schedulepress/mcp is discovered at the path-suffixed form:
		//   /.well-known/oauth-protected-resource/schedulepress/mcp
		//   /.well-known/oauth-authorization-server/schedulepress/mcp
		// The OAuth issuer is the path-based identifier
		// home_url('/schedulepress/mcp') (see OAuth::issuer), so spec-compliant
		// clients derive exactly these URLs — and the rule stays specific to OUR
		// path. That matters for coexistence: another plugin serving its own MCP
		// OAuth surface claims the generic `(?:/.*)?` root rule, and rewrite
		// rules are keyed by regex, so a shared broad rule would be silently
		// overwritten by whichever plugin registers last.
		add_rewrite_rule(
			'^\.well-known/oauth-(protected-resource|authorization-server)/schedulepress/mcp/?$',
			'index.php?' . self::WELLKNOWN_QUERY_VAR . '=$matches[1]',
			'top'
		);
		// Root-form fallback for clients that only try the bare well-known URL.
		// Harmless when another plugin also registers this exact regex — last
		// registrant wins, and our clients use the path-suffixed form.
		add_rewrite_rule(
			'^\.well-known/oauth-(protected-resource|authorization-server)(?:/.*)?/?$',
			'index.php?' . self::WELLKNOWN_QUERY_VAR . '=$matches[1]',
			'top'
		);
		// Suffix form: <issuer>/.well-known/... . RFC 8414 specifies the
		// path-INSERT form above, but the older OpenID Connect Discovery
		// convention appends instead, and clients built on an OIDC library try
		// that shape first (sometimes only that shape). Serving both costs two
		// rules and removes a whole class of "server does not implement OAuth"
		// failures from clients that never fall back.
		add_rewrite_rule(
			'^schedulepress/mcp/\.well-known/oauth-(protected-resource|authorization-server)/?$',
			'index.php?' . self::WELLKNOWN_QUERY_VAR . '=$matches[1]',
			'top'
		);
		add_rewrite_rule(
			'^schedulepress/mcp/\.well-known/openid-configuration/?$',
			'index.php?' . self::WELLKNOWN_QUERY_VAR . '=authorization-server',
			'top'
		);

		// Browser-facing OAuth consent page — served OUTSIDE REST so cookie auth
		// (is_user_logged_in) works after the wp-login round-trip.
		add_rewrite_rule( '^schedulepress/authorize/?$', 'index.php?' . self::AUTHORIZE_QUERY_VAR . '=1', 'top' );

		// Self-heal: flush once if ANY of our rules is missing from the stored
		// rewrite table, so the endpoints work without a manual permalink
		// re-save (and newly added rules trigger a re-flush on upgrade).
		$expected = array(
			'^schedulepress/mcp/([a-f0-9]{64})/?$',
			'^schedulepress/mcp/?$',
			'^\.well-known/oauth-(protected-resource|authorization-server)/schedulepress/mcp/?$',
			'^schedulepress/mcp/\.well-known/oauth-(protected-resource|authorization-server)/?$',
			'^schedulepress/mcp/\.well-known/openid-configuration/?$',
			'^schedulepress/authorize/?$',
		);
		$rules = get_option( 'rewrite_rules' );
		if ( is_array( $rules ) ) {
			foreach ( $expected as $rule ) {
				if ( ! isset( $rules[ $rule ] ) ) {
					flush_rewrite_rules( false );
					break;
				}
			}
		}
	}

	/**
	 * Register our query vars.
	 *
	 * @param string[] $vars Registered query vars.
	 * @return string[]
	 */
	public function register_query_var( $vars ) {
		$vars[] = self::QUERY_VAR;
		$vars[] = self::TOKEN_QUERY_VAR;
		$vars[] = self::WELLKNOWN_QUERY_VAR;
		$vars[] = self::AUTHORIZE_QUERY_VAR;
		return $vars;
	}

	/**
	 * Serve the MCP endpoint on the pretty path. Runs on parse_request so it
	 * fires before the main query, and short-circuits WP entirely.
	 *
	 * @param \WP $wp The WP request object.
	 * @return void
	 */
	public function maybe_handle_pretty_endpoint( $wp ) {
		// OAuth discovery documents (served at the site root).
		if ( ! empty( $wp->query_vars[ self::WELLKNOWN_QUERY_VAR ] ) ) {
			if ( ! self::is_enabled() ) {
				status_header( 404 );
				exit;
			}
			$doc  = (string) $wp->query_vars[ self::WELLKNOWN_QUERY_VAR ];
			$data = 'authorization-server' === $doc
				? OAuth::authorization_server_metadata()
				: OAuth::protected_resource_metadata();
			status_header( 200 );
			header( 'Content-Type: application/json; charset=utf-8' );
			// Discovery metadata is public + cacheable.
			header( 'Cache-Control: public, max-age=3600' );
			echo wp_json_encode( $data );
			exit;
		}

		// Browser-facing OAuth consent page (cookie auth applies here).
		if ( ! empty( $wp->query_vars[ self::AUTHORIZE_QUERY_VAR ] ) ) {
			if ( ! self::is_enabled() ) {
				status_header( 404 );
				exit;
			}
			$this->handle_authorize_page();
			return;
		}

		if ( empty( $wp->query_vars[ self::QUERY_VAR ] ) ) {
			return;
		}

		$request = new \WP_REST_Request( 'POST', '/' . self::ns() . '/mcp' );
		$request->set_header( 'content-type', 'application/json' );
		// Carry the auth header + raw body from the live PHP request.
		$auth = self::server_header( 'authorization' );
		if ( null !== $auth ) {
			$request->set_header( 'authorization', $auth );
		}
		// Token embedded in the URL path (/schedulepress/mcp/<token>) — surface
		// it as a Bearer header so Server validates it the same way. A real
		// Authorization header (if also sent) takes precedence.
		$path_token = isset( $wp->query_vars[ self::TOKEN_QUERY_VAR ] )
			? (string) $wp->query_vars[ self::TOKEN_QUERY_VAR ]
			: '';
		if ( '' !== $path_token && '' === (string) $request->get_header( 'authorization' ) ) {
			$request->set_header( 'authorization', 'Bearer ' . $path_token );
		}
		$request->set_body( (string) file_get_contents( 'php://input' ) );

		$response = Server::handle( $request );
		$this->emit_json( $response );
	}

	// -- REST registration --

	/**
	 * Register the REST routes: the MCP JSON-RPC fallback, the admin management
	 * routes, and the OAuth registration/token endpoints.
	 *
	 * @return void
	 */
	public function register_rest() {
		$ns = self::ns();

		// --- MCP JSON-RPC endpoint (fallback path via wp-json) -----------
		// permission_callback is __return_true because Server does its own token
		// auth and must reply with a JSON-RPC 401 + WWW-Authenticate, not a bare
		// WP permission failure.
		register_rest_route(
			$ns,
			'/mcp',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'rest_mcp' ),
				'permission_callback' => '__return_true',
			)
		);

		// --- Admin-only management routes (the MCP page) ------------------
		register_rest_route(
			$ns,
			'/mcp/connection',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'rest_connection' ),
				'permission_callback' => array( $this, 'admin_permission' ),
			)
		);
		register_rest_route(
			$ns,
			'/mcp/settings',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'rest_settings' ),
				'permission_callback' => array( $this, 'admin_permission' ),
				'args'                => array(
					'enable_mcp'                => array(
						'type'        => 'boolean',
						'required'    => false,
						'description' => 'Master switch for the MCP endpoint, discovery documents and OAuth surface.',
					),
					'enable_mcp_social_publish' => array(
						'type'        => 'boolean',
						'required'    => false,
						'description' => 'Whether connected AI clients may post to the connected social accounts.',
					),
				),
			)
		);
		register_rest_route(
			$ns,
			'/mcp/connect',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'rest_connect' ),
				'permission_callback' => array( $this, 'admin_permission' ),
				'args'                => array(
					'read_only' => array(
						'type'        => 'boolean',
						'required'    => false,
						'default'     => false,
						'description' => 'Grant read-only access (cannot schedule, reschedule or share).',
					),
				),
			)
		);
		register_rest_route(
			$ns,
			'/mcp/rotate',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'rest_rotate' ),
				'permission_callback' => array( $this, 'admin_permission' ),
				'args'                => array(
					'read_only' => array(
						'type'        => 'boolean',
						'required'    => false,
						'description' => 'Optionally set read-only on the new token; omit to keep current scopes.',
					),
				),
			)
		);
		register_rest_route(
			$ns,
			'/mcp/disconnect',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'rest_disconnect' ),
				'permission_callback' => array( $this, 'admin_permission' ),
			)
		);

		// Live round-trip diagnostic for the MCP page. Admin-only; exercises the
		// endpoint the way an external client would.
		register_rest_route(
			$ns,
			'/mcp/self-test',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'rest_self_test' ),
				'permission_callback' => array( $this, 'admin_permission' ),
			)
		);

		// Connected AI apps: list the OAuth-connected clients and revoke one.
		register_rest_route(
			$ns,
			'/mcp/apps',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'rest_apps' ),
				'permission_callback' => array( $this, 'admin_permission' ),
			)
		);
		register_rest_route(
			$ns,
			'/mcp/apps/revoke',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'rest_revoke_app' ),
				'permission_callback' => array( $this, 'admin_permission' ),
				'args'                => array(
					'client_id' => array(
						'type'        => 'string',
						'required'    => true,
						'description' => 'The OAuth client_id to revoke.',
					),
				),
			)
		);

		// --- OAuth 2.1 authorization server (the "paste a URL only" path) -
		// Discovery, dynamic client registration, and the token endpoint are all
		// public (permission enforced inside): a client must reach them BEFORE
		// it holds any credential.
		register_rest_route(
			$ns,
			'/mcp/oauth/register',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'rest_oauth_register' ),
				'permission_callback' => '__return_true',
			)
		);
		// NOTE: /authorize is deliberately NOT a REST route — it is served as a
		// normal front-end page at /schedulepress/authorize (see
		// handle_authorize_page) so cookie auth works after wp-login.
		register_rest_route(
			$ns,
			'/mcp/oauth/token',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'rest_oauth_token' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * Capability gate for the admin-only management routes.
	 *
	 * @return bool
	 */
	public function admin_permission() {
		return current_user_can( 'manage_options' );
	}

	// -- Handlers ----------------------------------------------------------

	/**
	 * MCP JSON-RPC over the wp-json fallback path.
	 *
	 * @param \WP_REST_Request $request Incoming request.
	 * @return \WP_REST_Response
	 */
	public function rest_mcp( \WP_REST_Request $request ) {
		$response = Server::handle( $request );
		// Advertise the MCP protocol version on the wp-json transport too, so
		// both endpoints behave identically to a strict Streamable-HTTP client.
		$response->header( 'MCP-Protocol-Version', Server::PROTOCOL_VERSION );
		return $response;
	}

	/**
	 * GET /mcp/connection — pairing status for the MCP page.
	 *
	 * @return \WP_REST_Response
	 */
	public function rest_connection() {
		$this->ensure_connected();
		$status                              = Pairing::public_status();
		$status['enable_mcp']                = self::is_enabled();
		$status['enable_mcp_social_publish'] = \WPSP\Abilities\Social\ShareNow::is_publishing_allowed();
		$status['runtime_ok']                = function_exists( 'wp_register_ability' );
		$status['tools_count']               = count( Tools::all() );
		return rest_ensure_response( $status );
	}

	/**
	 * POST /mcp/settings — flip the master toggle.
	 *
	 * @param \WP_REST_Request $request Carries enable_mcp.
	 * @return \WP_REST_Response
	 */
	public function rest_settings( \WP_REST_Request $request ) {
		if ( null !== $request->get_param( 'enable_mcp' ) ) {
			self::set_enabled( (bool) $request->get_param( 'enable_mcp' ) );
		}
		if ( null !== $request->get_param( 'enable_mcp_social_publish' ) ) {
			self::set_social_publish( (bool) $request->get_param( 'enable_mcp_social_publish' ) );
		}
		return $this->rest_connection();
	}

	/**
	 * Flip the social-publishing opt-in.
	 *
	 * Kept separate from `enable_mcp`: a site can want AI access to its
	 * schedule without wanting an AI able to post to its audience.
	 *
	 * @param bool $allowed Desired state.
	 * @return bool The state actually stored.
	 */
	public static function set_social_publish( $allowed ) {
		$allowed  = (bool) $allowed;
		$settings = json_decode( get_option( WPSP_SETTINGS_NAME, '{}' ), true );
		if ( ! is_array( $settings ) ) {
			$settings = array();
		}
		$settings['enable_mcp_social_publish'] = $allowed;
		update_option( WPSP_SETTINGS_NAME, wp_json_encode( $settings ) );

		return $allowed;
	}

	/**
	 * Self-heal: whenever the admin views the MCP page with MCP enabled, make
	 * sure a connection token exists — idempotent and admin-gated, so the
	 * connect recipes stay populated without a separate "Generate token" click.
	 *
	 * @return void
	 */
	private function ensure_connected() {
		if ( self::is_enabled() && ! Pairing::is_connected() ) {
			Pairing::connect();
		}
	}

	/**
	 * POST /mcp/connect — mint a connection token.
	 *
	 * @param \WP_REST_Request $request Carries optional read_only.
	 * @return \WP_REST_Response
	 */
	public function rest_connect( \WP_REST_Request $request ) {
		$read_only = (bool) $request->get_param( 'read_only' );
		return rest_ensure_response( Pairing::connect( $read_only ) );
	}

	/**
	 * POST /mcp/rotate — mint a fresh token, invalidating the old one.
	 *
	 * @param \WP_REST_Request $request Carries optional read_only.
	 * @return \WP_REST_Response
	 */
	public function rest_rotate( \WP_REST_Request $request ) {
		$read_only = null;
		if ( null !== $request->get_param( 'read_only' ) ) {
			$read_only = (bool) $request->get_param( 'read_only' );
		}
		return rest_ensure_response( Pairing::rotate( $read_only ) );
	}

	/**
	 * POST /mcp/disconnect — revoke the connection token + all OAuth grants.
	 *
	 * @return \WP_REST_Response
	 */
	public function rest_disconnect() {
		return rest_ensure_response( Pairing::disconnect() );
	}

	/**
	 * POST /mcp/self-test — run the live round-trip diagnostic.
	 *
	 * @return \WP_REST_Response
	 */
	public function rest_self_test() {
		return rest_ensure_response( SelfTest::run() );
	}

	/**
	 * GET /mcp/apps — the "Connected AI apps" list.
	 *
	 * @return \WP_REST_Response
	 */
	public function rest_apps() {
		$this->ensure_connected();
		return rest_ensure_response( $this->apps_payload() );
	}

	/**
	 * POST /mcp/apps/revoke — cut off a single OAuth-connected app. Returns the
	 * refreshed app list so the UI updates in one round trip. (The shared static
	 * token has no per-client identity, so it is not listed or revoked here — it
	 * is rotated from the connect card via /mcp/rotate.)
	 *
	 * @param \WP_REST_Request $request Carries client_id.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function rest_revoke_app( \WP_REST_Request $request ) {
		$client_id = (string) $request->get_param( 'client_id' );
		if ( '' === $client_id ) {
			return new \WP_Error(
				'wpsp_missing_client_id',
				__( 'A client_id is required to revoke an OAuth app.', 'wp-scheduled-posts' ),
				array( 'status' => 400 )
			);
		}
		OAuth::revoke_client( $client_id );

		return rest_ensure_response( $this->apps_payload() );
	}

	/**
	 * Build the "Connected AI apps" payload: the OAuth-connected clients, with
	 * the approving admin's display name resolved. Header-based (static-token)
	 * clients share one anonymous secret and so are not represented here.
	 *
	 * @return array
	 */
	private function apps_payload() {
		$oauth_apps = array();
		foreach ( OAuth::connected_apps() as $app ) {
			$user         = $app['user_id'] > 0 ? get_userdata( $app['user_id'] ) : false;
			$oauth_apps[] = array(
				'client_id'    => $app['client_id'],
				'name'         => $app['name'],
				'read_only'    => $app['read_only'],
				'approved_by'  => $user ? $user->display_name : __( 'Unknown user', 'wp-scheduled-posts' ),
				'connected_at' => $app['connected_at'],
				'last_used'    => $app['last_used'],
			);
		}

		return array(
			'oauth_apps' => $oauth_apps,
		);
	}

	// -- OAuth 2.1 handlers ------------------------------------------------

	/**
	 * POST /mcp/oauth/register — RFC 7591 dynamic client registration.
	 *
	 * @param \WP_REST_Request $request JSON body with redirect_uris.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function rest_oauth_register( \WP_REST_Request $request ) {
		if ( ! self::is_enabled() ) {
			return new \WP_Error( 'wpsp_mcp_disabled', __( 'MCP is disabled on this site.', 'wp-scheduled-posts' ), array( 'status' => 403 ) );
		}
		$body = $request->get_json_params();
		if ( ! is_array( $body ) ) {
			$body = array();
		}
		$result = OAuth::register_client( $body );
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		return new \WP_REST_Response( $result, 201 );
	}

	/**
	 * POST /mcp/oauth/token — exchange a code (or refresh token) for tokens.
	 *
	 * @param \WP_REST_Request $request Form-encoded or JSON token request.
	 * @return \WP_REST_Response
	 */
	public function rest_oauth_token( \WP_REST_Request $request ) {
		if ( ! self::is_enabled() ) {
			$response = new \WP_REST_Response(
				array(
					'error'             => 'invalid_request',
					'error_description' => 'MCP is disabled on this site.',
				),
				403
			);
			$response->header( 'Cache-Control', 'no-store' );
			return $response;
		}

		// Token requests are application/x-www-form-urlencoded per OAuth, but
		// accept JSON too. get_body_params() covers the form case.
		$body = $request->get_body_params();
		if ( empty( $body ) ) {
			$json = $request->get_json_params();
			$body = is_array( $json ) ? $json : array();
		}
		$body = array_map( 'strval', $body );

		$result = OAuth::exchange_token( $body );
		if ( is_wp_error( $result ) ) {
			$data     = $result->get_error_data();
			$response = new \WP_REST_Response(
				array(
					'error'             => isset( $data['error'] ) ? $data['error'] : 'invalid_request',
					'error_description' => isset( $data['error_description'] ) ? $data['error_description'] : $result->get_error_message(),
				),
				isset( $data['status'] ) ? (int) $data['status'] : 400
			);
			$response->header( 'Cache-Control', 'no-store' );
			return $response;
		}
		$response = new \WP_REST_Response( $result, 200 );
		$response->header( 'Cache-Control', 'no-store' );
		$response->header( 'Pragma', 'no-cache' );
		return $response;
	}

	// -- OAuth authorize page ------------------------------------------------

	/**
	 * The browser-facing OAuth authorize page (served at
	 * /schedulepress/authorize via a rewrite, NOT the REST API). Reads request
	 * params from the superglobals because this is a normal front-end request
	 * where cookie auth populates is_user_logged_in().
	 *
	 * GET renders the consent screen (requires a logged-in admin; anonymous
	 * users go to wp-login and return here). POST is the nonce-checked consent
	 * submission: Approve issues a code and 302s to the client's redirect_uri;
	 * Deny 302s back with error=access_denied. Always emits its own response
	 * (HTML page or redirect) and exits.
	 *
	 * @return void
	 */
	public function handle_authorize_page() {
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- compared against a literal after strtoupper(); nothing is stored or echoed.
		$is_post = isset( $_SERVER['REQUEST_METHOD'] ) && 'POST' === strtoupper( (string) wp_unslash( $_SERVER['REQUEST_METHOD'] ) );
		// Params come from GET on the consent link and POST on the form submit.
		// Nonce is verified below before any POST value is acted on.
		// phpcs:disable WordPress.Security.NonceVerification.Recommended, WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- each member is sanitize_text_field()ed in the loop below; nothing reads $source directly.
		$source = $is_post ? $_POST : $_GET;
		// phpcs:enable WordPress.Security.NonceVerification.Recommended, WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$params = array();
		foreach ( array( 'client_id', 'redirect_uri', 'response_type', 'code_challenge', 'code_challenge_method', 'scope', 'state', 'approve', 'deny', '_wpsp_oauth_nonce' ) as $k ) {
			$params[ $k ] = isset( $source[ $k ] ) ? sanitize_text_field( wp_unslash( $source[ $k ] ) ) : '';
		}

		// Validate the OAuth params before touching the session.
		$req = OAuth::validate_authorize_request( $params );
		if ( is_wp_error( $req ) ) {
			$data         = $req->get_error_data();
			$redirectable = is_array( $data ) && ! empty( $data['redirectable'] );
			// Only redirect the error back when redirect_uri is verified valid;
			// otherwise show a page (never bounce to an unverified URL).
			if ( $redirectable && '' !== $params['redirect_uri'] ) {
				$this->redirect_error( $params['redirect_uri'], $req->get_error_code(), $req->get_error_message(), $params['state'] );
			}
			$this->emit_oauth_error_page( $req->get_error_message() );
		}

		// Require a logged-in admin. Anonymous → wp-login, back to this URL.
		if ( ! is_user_logged_in() ) {
			$this->redirect_to_login();
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			$this->emit_oauth_error_page(
				__( 'You must be an administrator to authorize an AI assistant to manage the schedule on this site.', 'wp-scheduled-posts' )
			);
		}

		// POST = consent form submitted.
		if ( $is_post ) {
			if ( ! wp_verify_nonce( $params['_wpsp_oauth_nonce'], 'wpsp_oauth_consent' ) ) {
				$this->emit_oauth_error_page( __( 'Security check failed. Please try connecting again.', 'wp-scheduled-posts' ) );
			}
			if ( '' === $params['approve'] ) {
				$this->redirect_error( $req['redirect_uri'], 'access_denied', 'The user denied the request.', $req['state'] );
			}
			$code = OAuth::issue_code( $req, get_current_user_id() );
			$this->redirect_success( $req['redirect_uri'], $code, $req['state'] );
		}

		// GET = render the consent screen.
		$this->emit_consent_screen( $req );
	}

	// -- OAuth browser-response helpers ------------------------------------

	/**
	 * The absolute URL of the current authorize request (for login return).
	 *
	 * @return string
	 */
	private function current_authorize_url() {
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- reconstructing the current URL for a login round-trip; escaped at use.
		$uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
		return home_url( $uri );
	}

	/**
	 * Send an anonymous visitor to wp-login, returning to this authorize URL.
	 *
	 * @return void
	 */
	private function redirect_to_login() {
		wp_safe_redirect( wp_login_url( $this->current_authorize_url() ) );
		exit;
	}

	/**
	 * 302 back to the client with the authorization code (+ state).
	 *
	 * @param string $redirect_uri Validated client redirect URI.
	 * @param string $code         Authorization code.
	 * @param string $state        Client state.
	 * @return void
	 */
	private function redirect_success( $redirect_uri, $code, $state ) {
		$args = array( 'code' => $code );
		if ( '' !== $state ) {
			$args['state'] = $state;
		}
		// Not wp_safe_redirect: redirect_uri is a client-registered off-site
		// callback, already validated against the client's registered set.
		wp_redirect( add_query_arg( $args, $redirect_uri ) ); // phpcs:ignore WordPress.Security.SafeRedirect -- validated OAuth redirect_uri.
		exit;
	}

	/**
	 * 302 back to the client with an OAuth error (+ state).
	 *
	 * @param string $redirect_uri Validated client redirect URI.
	 * @param string $error        OAuth error code.
	 * @param string $description  Human-readable description.
	 * @param string $state        Client state.
	 * @return void
	 */
	private function redirect_error( $redirect_uri, $error, $description, $state ) {
		$args = array(
			'error'             => $error,
			'error_description' => $description,
		);
		if ( '' !== $state ) {
			$args['state'] = $state;
		}
		wp_redirect( add_query_arg( array_map( 'rawurlencode', $args ), $redirect_uri ) ); // phpcs:ignore WordPress.Security.SafeRedirect -- validated OAuth redirect_uri.
		exit;
	}

	/**
	 * Render the consent screen. Minimal self-contained HTML (no admin chrome —
	 * this is a client-facing OAuth page). Approve/Deny post back to the same
	 * authorize URL with a nonce.
	 *
	 * @param array $req Validated authorize params.
	 * @return void
	 */
	private function emit_consent_screen( array $req ) {
		$read_only    = OAuth::scope_is_read_only( $req['scope'] );
		$access_label = $read_only ? __( 'Read-only', 'wp-scheduled-posts' ) : __( 'Read & write', 'wp-scheduled-posts' );
		$access_desc  = $read_only
			? __( 'Review your content schedule — the calendar, scheduled and missed posts, social connections and sharing history. No changes are made.', 'wp-scheduled-posts' )
			: __( 'Review and manage your content schedule — schedule and reschedule posts, edit social templates, and share posts to your connected social accounts.', 'wp-scheduled-posts' );
		$client     = '' !== $req['client_name'] ? $req['client_name'] : __( 'An AI assistant', 'wp-scheduled-posts' );
		$action_url = OAuth::authorize_url();
		$nonce      = wp_create_nonce( 'wpsp_oauth_consent' );
		$user       = wp_get_current_user();

		// Preserve every OAuth param so the POST re-validates identically.
		$hidden = '';
		foreach ( array( 'client_id', 'redirect_uri', 'code_challenge', 'scope', 'state' ) as $k ) {
			$val     = isset( $req[ $k ] ) ? $req[ $k ] : '';
			$hidden .= sprintf( '<input type="hidden" name="%s" value="%s" />', esc_attr( $k ), esc_attr( (string) $val ) );
		}
		// code_challenge_method + response_type are re-asserted for validation.
		$hidden .= '<input type="hidden" name="code_challenge_method" value="S256" />';
		$hidden .= '<input type="hidden" name="response_type" value="code" />';

		status_header( 200 );
		header( 'Content-Type: text/html; charset=utf-8' );
		header( 'Cache-Control: no-store' );

		$host = (string) wp_parse_url( home_url(), PHP_URL_HOST );
		$lock = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>';
		$mark = '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="3"/><path d="M16 2v4M8 2v4M3 10h18"/><path d="M12 14v3l2 1"/></svg>';

		echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>' . esc_html__( 'Authorize AI access', 'wp-scheduled-posts' ) . '</title>';
		echo '<style>'
			. ':root{color-scheme:dark}*{box-sizing:border-box}'
			. 'body{font:15px/1.6 -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;background:radial-gradient(1100px 600px at 50% -15%,#20284a,#0b1020 62%);color:#e7ecf3;margin:0;display:flex;min-height:100vh;align-items:center;justify-content:center;padding:24px}'
			. '.card{width:100%;max-width:460px;background:#141a2e;border:1px solid #263149;border-radius:20px;padding:28px;box-shadow:0 24px 60px rgba(0,0,0,.5)}'
			. '.brand{display:flex;align-items:center;gap:10px;margin-bottom:22px}'
			. '.logo{width:36px;height:36px;border-radius:11px;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#5a57ff,#5b98ff);box-shadow:0 6px 16px rgba(99,102,241,.4)}'
			. '.brand b{font-size:14px;font-weight:700;letter-spacing:.02em}'
			. 'h1{font-size:20px;font-weight:700;margin:0 0 6px}'
			. '.sub{color:#9aa6be;font-size:13.5px;margin:0 0 22px}.sub strong{color:#e7ecf3;font-weight:600}'
			. '.rows{border:1px solid #263149;border-radius:14px;overflow:hidden;margin-bottom:16px}'
			. '.row{display:flex;justify-content:space-between;align-items:center;gap:16px;padding:13px 16px;font-size:13.5px}'
			. '.row+.row,.access{border-top:1px solid #263149}'
			. '.row .k{color:#9aa6be}.row .v{font-weight:600;text-align:right;word-break:break-word}'
			. '.access{padding:14px 16px;background:rgba(99,102,241,.07)}'
			. '.access .k{color:#9aa6be;font-size:13px;margin-bottom:8px}'
			. '.badge{display:inline-flex;align-items:center;font-size:12px;font-weight:700;padding:3px 10px;border-radius:999px;background:rgba(99,102,241,.18);color:#c7cbff;border:1px solid rgba(99,102,241,.4)}'
			. '.badge.ro{background:rgba(245,158,11,.15);color:#fcd9a1;border-color:rgba(245,158,11,.4)}'
			. '.access .d{color:#c3ccdd;font-size:13px;margin-top:8px}'
			. '.note{display:flex;align-items:center;gap:7px;color:#7f8aa3;font-size:12px;margin:0 0 20px}'
			. '.actions{display:flex;gap:12px}'
			. 'button{flex:1;padding:13px;border-radius:12px;border:0;font-size:14px;font-weight:600;cursor:pointer;transition:filter .15s,transform .05s}button:active{transform:translateY(1px)}'
			. '.approve{background:linear-gradient(135deg,#6366f1,#7c73ff);color:#fff;box-shadow:0 8px 20px rgba(99,102,241,.38)}.approve:hover{filter:brightness(1.07)}'
			. '.deny{background:transparent;color:#aeb8cc;border:1px solid #33405c}.deny:hover{background:rgba(255,255,255,.04)}'
			. '</style></head><body><div class="card">';

		echo '<div class="brand"><span class="logo">' . $mark . '</span><b>SchedulePress</b></div>'; // phpcs:ignore WordPress.Security.EscapeOutput -- static markup.
		echo '<h1>' . esc_html__( 'Connect to SchedulePress', 'wp-scheduled-posts' ) . '</h1>';
		$sub = sprintf(
			/* translators: %s: AI client name, already escaped and wrapped in <strong>. */
			esc_html__( '%s wants to manage the content schedule on this site.', 'wp-scheduled-posts' ),
			'<strong>' . esc_html( $client ) . '</strong>'
		);
		echo '<p class="sub">' . $sub . '</p>'; // phpcs:ignore WordPress.Security.EscapeOutput -- static translation; client name esc_html'd.

		echo '<div class="rows">';
		echo '<div class="row"><span class="k">' . esc_html__( 'Site', 'wp-scheduled-posts' ) . '</span><span class="v">' . esc_html( $host ) . '</span></div>';
		echo '<div class="row"><span class="k">' . esc_html__( 'Signed in as', 'wp-scheduled-posts' ) . '</span><span class="v">' . esc_html( $user->user_login ) . '</span></div>';
		echo '<div class="access"><div class="k">' . esc_html__( 'Access', 'wp-scheduled-posts' ) . '</div>';
		echo '<span class="badge ' . ( $read_only ? 'ro' : '' ) . '">' . esc_html( $access_label ) . '</span>';
		echo '<div class="d">' . esc_html( $access_desc ) . '</div></div>';
		echo '</div>';

		echo '<p class="note">' . $lock . '<span>' . esc_html__( 'Secured with OAuth. Revoke anytime in SchedulePress → MCP.', 'wp-scheduled-posts' ) . '</span></p>'; // phpcs:ignore WordPress.Security.EscapeOutput -- static icon; text esc_html'd.

		echo '<form method="post" action="' . esc_url( $action_url ) . '">';
		echo $hidden; // phpcs:ignore WordPress.Security.EscapeOutput -- built from esc_attr() above.
		echo '<input type="hidden" name="_wpsp_oauth_nonce" value="' . esc_attr( $nonce ) . '" />';
		echo '<div class="actions">';
		echo '<button class="deny" name="deny" value="1">' . esc_html__( 'Deny', 'wp-scheduled-posts' ) . '</button>';
		echo '<button class="approve" name="approve" value="1">' . esc_html__( 'Approve', 'wp-scheduled-posts' ) . '</button>';
		echo '</div></form></div></body></html>';
		exit;
	}

	/**
	 * Render a standalone OAuth error page (no redirect).
	 *
	 * @param string $message Error message.
	 * @return void
	 */
	private function emit_oauth_error_page( $message ) {
		status_header( 400 );
		header( 'Content-Type: text/html; charset=utf-8' );
		header( 'Cache-Control: no-store' );
		echo '<!doctype html><html><head><meta charset="utf-8"><title>' . esc_html__( 'Authorization error', 'wp-scheduled-posts' ) . '</title>';
		echo '<style>body{font:15px/1.5 -apple-system,sans-serif;background:#0f172a;color:#e2e8f0;display:flex;min-height:100vh;align-items:center;justify-content:center;margin:0}'
			. '.card{background:#1e293b;border:1px solid #334155;border-radius:16px;max-width:440px;padding:32px;text-align:center}</style></head><body>';
		echo '<div class="card"><h1>' . esc_html__( 'Could not authorize', 'wp-scheduled-posts' ) . '</h1><p>' . esc_html( $message ) . '</p></div></body></html>';
		exit;
	}

	// -- Helpers --

	/**
	 * Read an inbound HTTP header from $_SERVER (for the pretty path).
	 *
	 * @param string $name Header name.
	 * @return string|null
	 */
	private static function server_header( $name ) {
		$key = 'HTTP_' . strtoupper( str_replace( '-', '_', $name ) );
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- token compared constant-time downstream; raw header needed verbatim.
		return isset( $_SERVER[ $key ] ) ? wp_unslash( $_SERVER[ $key ] ) : null;
	}

	/**
	 * Emit a WP_REST_Response as a JSON HTTP response and stop.
	 *
	 * @param \WP_REST_Response $response Response to emit.
	 * @return void
	 */
	private function emit_json( \WP_REST_Response $response ) {
		status_header( $response->get_status() );
		// MCP Streamable HTTP: advertise the protocol version we speak so a
		// strict client can pin it. We answer JSON (a spec-permitted response
		// type); we never open an SSE stream, so no session header is needed.
		header( 'MCP-Protocol-Version: ' . Server::PROTOCOL_VERSION );
		// Forward any headers the handler set (notably WWW-Authenticate on a
		// 401, which drives the OAuth discovery flow).
		foreach ( $response->get_headers() as $name => $value ) {
			// Re-assert the status on every header: PHP special-cases
			// WWW-Authenticate and forces a 401 when no status is given, which
			// would silently mask the 429 lockout response.
			header( $name . ': ' . $value, true, $response->get_status() );
		}
		$data = $response->get_data();
		if ( null !== $data ) {
			header( 'Content-Type: application/json; charset=utf-8' );
			echo wp_json_encode( $data );
		}
		exit;
	}
}
