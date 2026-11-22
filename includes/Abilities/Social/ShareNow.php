<?php
/**
 * Share now ability.
 *
 * @package WPSP\Abilities\Social
 */

namespace WPSP\Abilities\Social;

use WPSP\Abilities\AbilityBase;
use WPSP\Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Shares a post to one connected social profile, immediately.
 *
 * This is the single most consequential tool in the catalog: it publishes to a
 * real audience on a real brand account, and nothing here can take it back. It
 * is therefore gated four ways —
 *
 *   1. an explicit site setting (`enable_mcp_social_publish`), off by default;
 *   2. `confirm: true` on every call;
 *   3. `dry_run` defaulting to true, so the obvious first call is a preview;
 *   4. a duplicate check against the platform's existing share log.
 *
 * A read-only MCP credential cannot reach it at all (the tool name does not
 * start with get-/list-, so the scope gate refuses it upstream).
 */
class ShareNow extends AbilityBase {

	use PlatformMap;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id          = 'schedulepress/share-now';
		$this->label       = __( 'Share a Post Now', 'wp-scheduled-posts' );
		$this->description = __( 'Immediately share a published post to one connected social profile. This posts publicly to a real account and cannot be undone. It is disabled unless the site has explicitly enabled MCP social publishing, and always requires confirm: true. Call with dry_run: true first to check what would be posted.', 'wp-scheduled-posts' );
	}

	/**
	 * {@inheritDoc}
	 *
	 * @return array
	 */
	public function get_annotations() {
		return array(
			'readonly'      => false,
			'destructive'   => true,
			'idempotent'    => false,
			'priority'      => 3.0,
			// This one genuinely reaches the outside world.
			'openWorldHint' => true,
		);
	}

	/**
	 * {@inheritDoc}
	 *
	 * @return array
	 */
	public function get_input_schema() {
		return array(
			'type'                 => 'object',
			'additionalProperties' => false,
			'required'             => array( 'post_id', 'platform', 'platform_key' ),
			'properties'           => array(
				'post_id'      => array(
					'type'        => 'integer',
					'description' => 'ID of the published post to share.',
				),
				'platform'     => array(
					'type'        => 'string',
					'description' => 'Platform slug, e.g. facebook, twitter, linkedin.',
				),
				'platform_key' => array(
					'type'        => 'string',
					'description' => 'Which connected profile to share to, as returned by list-social-profiles.',
				),
				'dry_run'      => array(
					'type'        => 'boolean',
					'default'     => true,
					'description' => 'When true (the default) nothing is posted; the response reports what would be shared.',
				),
				'confirm'      => array(
					'type'        => 'boolean',
					'default'     => false,
					'description' => 'Must be true for a real share. Confirm with the user first — this posts publicly.',
				),
			),
		);
	}

	/**
	 * {@inheritDoc}
	 *
	 * @return array
	 */
	public function get_output_schema() {
		return array(
			'type'       => 'object',
			'properties' => array(
				'dry_run'  => array( 'type' => 'boolean' ),
				'shared'   => array( 'type' => 'boolean' ),
				'post_id'  => array( 'type' => 'integer' ),
				'platform' => array( 'type' => 'string' ),
				'profile'  => array( 'type' => 'object' ),
				'message'  => array( 'type' => 'string' ),
			),
		);
	}

	/**
	 * Whether the site has opted in to social publishing over MCP.
	 *
	 * Separate from `enable_mcp`: a site can want AI access to its schedule
	 * without wanting an AI able to post to its audience.
	 *
	 * @return bool
	 */
	public static function is_publishing_allowed() {
		/**
		 * Filter whether MCP clients may publish to social platforms.
		 *
		 * @param bool $allowed Defaults to the `enable_mcp_social_publish` setting.
		 */
		return (bool) apply_filters(
			'wpsp_mcp_allow_social_publish',
			(bool) Helper::get_settings( 'enable_mcp_social_publish' )
		);
	}

	/**
	 * Execute ability.
	 *
	 * @param array $input Ability input payload.
	 * @return array|\WP_Error
	 */
	public function execute( $input ) {
		$input = is_array( $input ) ? $input : array();

		$post = $this->require_post( isset( $input['post_id'] ) ? $input['post_id'] : 0 );
		if ( is_wp_error( $post ) ) {
			return $post;
		}

		$platform = isset( $input['platform'] ) ? (string) $input['platform'] : '';
		$valid    = $this->validate_platform( $platform );
		if ( is_wp_error( $valid ) ) {
			return $valid;
		}

		$platform_key = isset( $input['platform_key'] ) ? (string) $input['platform_key'] : '';
		$profile      = $this->find_profile( $platform, $platform_key );
		if ( null === $profile ) {
			return new \WP_Error(
				'wpsp_profile_not_found',
				sprintf(
					/* translators: 1: platform key, 2: platform slug. */
					__( 'No connected profile "%1$s" for %2$s. Call list-social-profiles to see the available profiles.', 'wp-scheduled-posts' ),
					$platform_key,
					$platform
				),
				array( 'status' => 404 )
			);
		}

		if ( ! $profile['active'] || ! $profile['has_token'] ) {
			return new \WP_Error(
				'wpsp_profile_inactive',
				sprintf(
					/* translators: %s: profile name. */
					__( 'The profile "%s" is inactive or has no access token. Reconnect it in SchedulePress before sharing.', 'wp-scheduled-posts' ),
					$profile['name']
				),
				array( 'status' => 409 )
			);
		}
		if ( $profile['expired'] ) {
			return new \WP_Error(
				'wpsp_profile_expired',
				sprintf(
					/* translators: %s: profile name. */
					__( 'The access token for "%s" has expired. Reconnect the profile in SchedulePress before sharing.', 'wp-scheduled-posts' ),
					$profile['name']
				),
				array( 'status' => 409 )
			);
		}

		if ( 'publish' !== $post->post_status ) {
			return new \WP_Error(
				'wpsp_post_not_published',
				sprintf(
					/* translators: 1: post ID, 2: post status. */
					__( 'Post %1$d is "%2$s", not published. Only published posts can be shared — the share would link to a page the audience cannot open.', 'wp-scheduled-posts' ),
					(int) $post->ID,
					$post->post_status
				),
				array( 'status' => 409 )
			);
		}

		if ( get_post_meta( (int) $post->ID, '_wpscppro_dont_share_socialmedia', true ) ) {
			return new \WP_Error(
				'wpsp_sharing_disabled',
				sprintf(
					/* translators: %d: post ID. */
					__( 'Social sharing is switched off for post %d. Turn it back on in the post editor before sharing.', 'wp-scheduled-posts' ),
					(int) $post->ID
				),
				array( 'status' => 409 )
			);
		}

		// Duplicate guard: this profile already has a share logged for this post.
		$log = get_post_meta( (int) $post->ID, $this->share_log_meta_key( $platform ), true );
		if ( is_array( $log ) && isset( $log[ $platform_key ] ) ) {
			return new \WP_Error(
				'wpsp_already_shared',
				sprintf(
					/* translators: 1: post ID, 2: profile name. */
					__( 'Post %1$d has already been shared to "%2$s". Sharing again would post a duplicate; use get-share-status to review what was sent.', 'wp-scheduled-posts' ),
					(int) $post->ID,
					$profile['name']
				),
				array( 'status' => 409 )
			);
		}

		$dry_run = array_key_exists( 'dry_run', $input ) ? (bool) $input['dry_run'] : true;

		if ( $dry_run ) {
			return array(
				'dry_run'  => true,
				'shared'   => false,
				'post_id'  => (int) $post->ID,
				'platform' => $platform,
				'profile'  => $profile,
				'message'  => sprintf(
					/* translators: 1: post title, 2: profile name. */
					__( 'Preview only — nothing was posted. A real call would share "%1$s" to %2$s.', 'wp-scheduled-posts' ),
					get_the_title( $post ),
					$profile['name']
				),
			);
		}

		if ( ! self::is_publishing_allowed() ) {
			return new \WP_Error(
				'wpsp_social_publish_disabled',
				__( 'Social publishing over MCP is switched off for this site. An administrator must enable it under SchedulePress → MCP before an AI client can post to your social accounts.', 'wp-scheduled-posts' ),
				array( 'status' => 403 )
			);
		}

		if ( empty( $input['confirm'] ) ) {
			return new \WP_Error(
				'wpsp_confirmation_required',
				__( 'This posts publicly to a real social account and cannot be undone. Confirm with the user, then call again with confirm: true.', 'wp-scheduled-posts' ),
				array( 'status' => 400 )
			);
		}

		if ( ! Helper::is_user_allow() ) {
			return new \WP_Error(
				'wpsp_forbidden',
				__( 'This account is not permitted to use the social profiles configured on this site.', 'wp-scheduled-posts' ),
				array( 'status' => 403 )
			);
		}

		return $this->dispatch_share( $post, $platform, $platform_key, $profile );
	}

	/**
	 * Run the plugin's own instant-share routine and survive its wp_die().
	 *
	 * The share pipeline is an AJAX handler: it reads $_GET, verifies a nonce,
	 * and finishes by calling wp_die(). Rather than duplicating each platform's
	 * publishing logic here — which would drift the moment a platform's API
	 * changes — we call the real thing and neutralise the two AJAX-isms:
	 * the superglobal it reads from, and the wp_die() that would otherwise kill
	 * the JSON-RPC response after the share had already gone out.
	 *
	 * @param \WP_Post $post         Post being shared.
	 * @param string   $platform     Platform slug.
	 * @param string   $platform_key Profile index.
	 * @param array    $profile      Redacted profile record (for the response).
	 * @return array|\WP_Error
	 */
	protected function dispatch_share( $post, $platform, $platform_key, array $profile ) {
		$params = array(
			'nonce'            => wp_create_nonce( 'wpscp-pro-social-profile' ),
			'postid'           => (int) $post->ID,
			'platform'         => $platform,
			'platformKey'      => (string) $platform_key,
			'id'               => $profile['id'],
			'share_on_publish' => false,
		);

		$previous_get = $_GET;
		$halt_message = '';

		$handler = function () {
			return function ( $message ) {
				$text = '';
				if ( is_string( $message ) ) {
					$text = $message;
				} elseif ( is_wp_error( $message ) ) {
					$text = $message->get_error_message();
				}
				throw new ShareHalt( $text );
			};
		};

		$filters = array(
			'wp_die_handler',
			'wp_die_ajax_handler',
			'wp_die_json_handler',
			'wp_die_jsonp_handler',
			'wp_die_xmlrpc_handler',
			'wp_die_xml_handler',
		);
		foreach ( $filters as $filter ) {
			add_filter( $filter, $handler, PHP_INT_MAX );
		}

		// The routine echoes JSON on failure; keep that out of our response.
		ob_start();
		try {
			do_action( 'wpsp_instant_social_single_profile_share', $params );
		} catch ( ShareHalt $e ) {
			$halt_message = $e->getMessage();
		} catch ( \Exception $e ) {
			$halt_message = $e->getMessage();
		}
		$echoed = (string) ob_get_clean();

		foreach ( $filters as $filter ) {
			remove_filter( $filter, $handler, PHP_INT_MAX );
		}
		$_GET = $previous_get;

		// The share log is the authority on whether the post actually went out:
		// the routine reports success by writing it, not by returning anything.
		$log    = get_post_meta( (int) $post->ID, $this->share_log_meta_key( $platform ), true );
		$shared = is_array( $log ) && isset( $log[ $platform_key ] );

		if ( ! $shared ) {
			$detail = '' !== $halt_message ? $halt_message : $this->extract_error( $echoed );
			return new \WP_Error(
				'wpsp_share_failed',
				sprintf(
					/* translators: 1: profile name, 2: reason reported by the platform. */
					__( 'The share to "%1$s" did not complete: %2$s', 'wp-scheduled-posts' ),
					$profile['name'],
					'' !== $detail ? $detail : __( 'the platform returned no confirmation.', 'wp-scheduled-posts' )
				),
				array( 'status' => 502 )
			);
		}

		return array(
			'dry_run'  => false,
			'shared'   => true,
			'post_id'  => (int) $post->ID,
			'platform' => $platform,
			'profile'  => $profile,
			'message'  => sprintf(
				/* translators: 1: post title, 2: profile name. */
				__( 'Shared "%1$s" to %2$s.', 'wp-scheduled-posts' ),
				get_the_title( $post ),
				$profile['name']
			),
		);
	}

	/**
	 * Pull a human-readable message out of whatever the share routine echoed.
	 *
	 * @param string $echoed Buffered output.
	 * @return string
	 */
	protected function extract_error( $echoed ) {
		$echoed = trim( (string) $echoed );
		if ( '' === $echoed ) {
			return '';
		}
		$decoded = json_decode( $echoed, true );
		if ( is_array( $decoded ) && isset( $decoded['data']['message'] ) ) {
			return (string) $decoded['data']['message'];
		}
		return wp_strip_all_tags( $echoed );
	}
}
