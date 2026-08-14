<?php
/**
 * Abilities registrar.
 *
 * @package WPSP\Abilities
 */

namespace WPSP\Abilities;

use WPSP\Abilities\Schedule\GetCalendar;
use WPSP\Abilities\Schedule\ListScheduledPosts;
use WPSP\Abilities\Schedule\ListDraftPosts;
use WPSP\Abilities\Schedule\GetMissedSchedules;
use WPSP\Abilities\Schedule\FindScheduleGaps;
use WPSP\Abilities\Schedule\SchedulePost;
use WPSP\Abilities\Schedule\ReschedulePost;
use WPSP\Abilities\Schedule\BulkReschedule;
use WPSP\Abilities\Schedule\UnschedulePost;
use WPSP\Abilities\Schedule\PublishNow;
use WPSP\Abilities\Content\ListContentTypes;
use WPSP\Abilities\Social\ListSocialProfiles;
use WPSP\Abilities\Social\GetSocialTemplate;
use WPSP\Abilities\Social\UpdateSocialTemplate;
use WPSP\Abilities\Social\GetShareStatus;
use WPSP\Abilities\Social\ShareNow;
use WPSP\Abilities\Settings\GetSettings;
use WPSP\Abilities\Settings\UpdateSettings;
use WPSP\Abilities\Diagnostics\GetConnectionStatus;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Registers SchedulePress abilities with the WordPress Abilities API.
 *
 * Abilities are **always** registered when the Abilities API is available —
 * registration is decoupled from the `enable_mcp` toggle. Each ability is a
 * permission-checked read/write surface (its own `current_user_can()` callback
 * runs on every call), so registration alone exposes nothing; it only makes
 * SchedulePress discoverable to generic WordPress Abilities clients the same
 * way WordPress core abilities are. The `enable_mcp` toggle gates only
 * SchedulePress's own MCP server ({@see \WPSP\MCP\Manager}) — the endpoint,
 * discovery documents, and OAuth surface. The MCP server reads this abilities
 * registry as its tool catalog. The Abilities API itself ships in
 * `dependencies/` and no-ops gracefully when missing.
 *
 * A kill switch remains available via the `wpsp_abilities_api_enabled` filter
 * (defaults to `true`; see {@see AbilityBase::abilities_enabled()}).
 */
class Registrar {

	/**
	 * Ability-name prefixes that mark an ability as SchedulePress's. The free
	 * plugin owns `schedulepress/`; Pro may register under `schedulepress-pro/`
	 * via the `schedulepress_register_abilities` filter.
	 */
	const ABILITY_PREFIXES = array( 'schedulepress/', 'schedulepress-pro/' );

	/**
	 * Whether the registration replay (see {@see self::ensure_registered()})
	 * has already run this request. One attempt only — a replay that produced
	 * nothing will not produce anything on the second try either, and the MCP
	 * server asks for the tool list more than once per request.
	 *
	 * @var bool
	 */
	private static $replayed = false;

	/**
	 * Initialize the registrar (called by the plugin bootstrap).
	 *
	 * Deliberately does NOT bail on a missing `wp_register_ability`: the
	 * Abilities API is a set of GLOBAL functions loaded under `function_exists`
	 * guards, so which copy owns them — ours in `dependencies/`, another
	 * plugin's, or core's — is decided by load order, not by us. A copy that
	 * lands after `plugins_loaded` would have made this an early return and left
	 * SchedulePress permanently unregistered. The callbacks themselves are
	 * guarded instead, so hooking unconditionally is free when no Abilities API
	 * ever shows up.
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'wp_abilities_api_categories_init', array( $this, 'register_category' ) );
		add_action( 'wp_abilities_api_init', array( $this, 'register_abilities' ) );
	}

	/**
	 * Guarantee SchedulePress's abilities are in the registry, replaying
	 * registration once if they are not.
	 *
	 * `wp_abilities_api_init` fires exactly once, from the lazy registry
	 * singleton of whichever Abilities API copy owns the globals. If a foreign
	 * copy owns them and fires its init under a different name or at a moment
	 * when our hook is not attached yet, our callback never runs: the registry
	 * is populated by everyone else and `tools/list` answers with an empty array
	 * while auth, discovery and `initialize` all report success.
	 *
	 * Calling `wp_get_abilities()` here forces that lazy init, so by the time we
	 * decide to replay, `wp_abilities_api_init` has fired and
	 * `wp_register_ability()` will accept our registrations. Registration is
	 * idempotent (each ability is skipped when `wp_has_ability()` already knows
	 * it), so a replay after a *successful* hook run is a no-op anyway.
	 *
	 * @param callable|null $replay Optional replay routine, for tests. Default
	 *                              is this class's own category + abilities
	 *                              registration.
	 * @return int Number of SchedulePress abilities registered afterwards.
	 */
	public static function ensure_registered( $replay = null ) {
		if ( ! function_exists( 'wp_get_abilities' ) ) {
			return 0;
		}

		// Forces the registry's lazy init (and with it `wp_abilities_api_init`).
		$count = self::count_registered();
		if ( $count > 0 || self::$replayed ) {
			return $count;
		}

		// Before the registry has initialized, `wp_register_ability()` refuses
		// the registration and calls `_doing_it_wrong()`. Nothing to replay yet.
		if ( ! function_exists( 'did_action' ) || ! did_action( 'wp_abilities_api_init' ) ) {
			return $count;
		}

		self::$replayed = true;

		if ( ! AbilityBase::abilities_enabled() ) {
			return 0;
		}

		if ( null === $replay ) {
			$registrar = new self();
			$replay    = function () use ( $registrar ) {
				$registrar->register_category();
				$registrar->register_abilities();
			};
		}

		call_user_func( $replay );

		$count = self::count_registered();

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- WP_DEBUG-gated diagnostic.
				'[WPSP-MCP] SchedulePress abilities were missing from the registry; replayed registration. ' . self::summary()
			);
		}

		return $count;
	}

	/**
	 * How many SchedulePress abilities the registry currently holds.
	 *
	 * @return int
	 */
	public static function count_registered() {
		if ( ! function_exists( 'wp_get_abilities' ) ) {
			return 0;
		}

		$count = 0;
		foreach ( wp_get_abilities() as $ability ) {
			if ( ! is_object( $ability ) || ! method_exists( $ability, 'get_name' ) ) {
				continue;
			}
			foreach ( self::ABILITY_PREFIXES as $prefix ) {
				if ( 0 === strpos( (string) $ability->get_name(), $prefix ) ) {
					++$count;
					break;
				}
			}
		}

		return $count;
	}

	/**
	 * Which file defines the global Abilities API functions for this request. A
	 * path outside SchedulePress's `dependencies/` means a foreign copy owns the
	 * registry — the precondition for a silently empty tool list.
	 *
	 * @return string Absolute path, or '' when the API is absent/unresolvable.
	 */
	public static function owner_path() {
		if ( ! function_exists( 'wp_get_abilities' ) ) {
			return '';
		}

		try {
			$reflection = new \ReflectionFunction( 'wp_get_abilities' );
			return (string) $reflection->getFileName();
		} catch ( \ReflectionException $e ) {
			return '';
		}
	}

	/**
	 * Diagnostic snapshot of the Abilities API as this request sees it. Feeds
	 * the MCP self-test and the debug log so "no tools registered" is
	 * distinguishable from "tools filtered out" without shell access.
	 *
	 * @return array
	 */
	public static function diagnostics() {
		$available = function_exists( 'wp_get_abilities' );
		$owner     = self::owner_path();
		$bundled   = defined( 'WPSP_ROOT_DIR_PATH' ) ? WPSP_ROOT_DIR_PATH : '';

		// Read the registry BEFORE `hook_fired`: `wp_get_abilities()` forces the
		// lazy singleton's init (which fires `wp_abilities_api_init`). Reading
		// `did_action()` first would report `hook_fired => false` in the same
		// snapshot that already counts registered abilities — an internally
		// inconsistent line for the exact support scenario this feeds.
		$total = $available ? count( wp_get_abilities() ) : 0;
		$ours  = self::count_registered();

		return array(
			'api_available' => $available,
			'owner'         => $owner,
			'foreign'       => ( '' !== $owner && '' !== $bundled && 0 !== strpos( $owner, $bundled ) ),
			'hook_fired'    => function_exists( 'did_action' ) ? (bool) did_action( 'wp_abilities_api_init' ) : false,
			'total'         => $total,
			'schedulepress' => $ours,
			'replayed'      => self::$replayed,
		);
	}

	/**
	 * One-line, human-readable form of {@see self::diagnostics()}.
	 *
	 * @return string
	 */
	public static function summary() {
		$d = self::diagnostics();

		return sprintf(
			'Abilities API: %s; owner: %s%s; abilities total: %d, schedulepress: %d; init fired: %s; replayed: %s',
			$d['api_available'] ? 'present' : 'missing',
			'' !== $d['owner'] ? $d['owner'] : 'unknown',
			$d['foreign'] ? ' (foreign copy — not the bundled runtime)' : '',
			$d['total'],
			$d['schedulepress'],
			$d['hook_fired'] ? 'yes' : 'no',
			$d['replayed'] ? 'yes' : 'no'
		);
	}

	/**
	 * Register the SchedulePress ability category.
	 *
	 * @return void
	 */
	public function register_category() {
		if ( function_exists( 'wp_has_ability_category' ) && wp_has_ability_category( 'schedulepress' ) ) {
			return;
		}

		if ( function_exists( 'wp_register_ability_category' ) ) {
			wp_register_ability_category(
				'schedulepress',
				array(
					'label'       => __( 'SchedulePress', 'wp-scheduled-posts' ),
					'description' => __( 'Content scheduling, calendar and social auto-sharing abilities powered by SchedulePress.', 'wp-scheduled-posts' ),
				)
			);
		}
	}

	/**
	 * Register SchedulePress abilities.
	 *
	 * @return void
	 */
	public function register_abilities() {
		if ( ! AbilityBase::abilities_enabled() ) {
			return;
		}

		$abilities = array(
			// Discovery.
			new ListContentTypes(),
			// Reading the schedule.
			new GetCalendar(),
			new ListScheduledPosts(),
			new ListDraftPosts(),
			new GetMissedSchedules(),
			new FindScheduleGaps(),
			// Changing the schedule.
			new SchedulePost(),
			new ReschedulePost(),
			new BulkReschedule(),
			new UnschedulePost(),
			new PublishNow(),
			// Social.
			new ListSocialProfiles(),
			new GetSocialTemplate(),
			new UpdateSocialTemplate(),
			new GetShareStatus(),
			new ShareNow(),
			// Settings + diagnostics.
			new GetSettings(),
			new UpdateSettings(),
			new GetConnectionStatus(),
		);

		/**
		 * Filter the abilities SchedulePress registers.
		 *
		 * Add-ons append their own AbilityBase instances here; they should use
		 * their own name prefix (e.g. `schedulepress-pro/`) so ownership stays
		 * legible in the registry and in the MCP tool list.
		 *
		 * @param AbilityBase[] $abilities Abilities about to be registered.
		 */
		$abilities = apply_filters( 'schedulepress_register_abilities', $abilities );

		foreach ( $abilities as $ability ) {
			if ( ! $ability instanceof AbilityBase ) {
				continue;
			}

			if ( ! $ability->meets_capability_policy() || ! $ability->is_enabled() ) {
				continue;
			}

			if ( function_exists( 'wp_has_ability' ) && wp_has_ability( $ability->get_id() ) ) {
				continue;
			}

			$ability->register();
		}
	}
}
