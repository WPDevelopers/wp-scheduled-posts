<?php
/**
 * Get connection status ability.
 *
 * @package WPSP\Abilities\Diagnostics
 */

namespace WPSP\Abilities\Diagnostics;

use WPSP\Abilities\AbilityBase;
use WPSP\Abilities\Social\PlatformMap;
use WPSP\Abilities\Social\ShareNow;
use WPSP\Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * One call that answers "is this site's scheduling actually working?".
 *
 * Bundles the handful of facts that explain most scheduling failures — cron
 * health, timezone, stuck posts, expiring social tokens — so a support
 * conversation starts from evidence instead of a screenshot.
 */
class GetConnectionStatus extends AbilityBase {

	use PlatformMap;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id          = 'schedulepress/get-connection-status';
		$this->label       = __( 'Get SchedulePress Status', 'wp-scheduled-posts' );
		$this->description = __( 'Health check for scheduling on this site: plugin version, site timezone, WP-Cron status, how many posts are queued or stuck as missed schedules, and which social connections are broken or expiring. Start troubleshooting here.', 'wp-scheduled-posts' );
	}

	/**
	 * {@inheritDoc}
	 *
	 * @return array
	 */
	public function get_annotations() {
		return array(
			'readonly'      => true,
			'destructive'   => false,
			'idempotent'    => true,
			'priority'      => 1.0,
			'openWorldHint' => false,
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
			'properties'           => array(),
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
				'plugin'    => array( 'type' => 'object' ),
				'site'      => array( 'type' => 'object' ),
				'cron'      => array( 'type' => 'object' ),
				'schedule'  => array( 'type' => 'object' ),
				'social'    => array( 'type' => 'object' ),
				'mcp'       => array( 'type' => 'object' ),
				'warnings'  => array(
					'type'  => 'array',
					'items' => array( 'type' => 'string' ),
				),
			),
		);
	}

	/**
	 * Execute ability.
	 *
	 * @param array $input Ability input payload.
	 * @return array
	 */
	public function execute( $input ) {
		$warnings = array();
		$now      = new \DateTime( 'now', $this->site_timezone() );

		// -- Scheduling queue ------------------------------------------------
		$post_types = $this->allowed_post_types();

		$queued = new \WP_Query(
			array(
				'post_type'      => $post_types,
				'post_status'    => 'future',
				'posts_per_page' => 1,
				'fields'         => 'ids',
			)
		);

		$missed = new \WP_Query(
			array(
				'post_type'      => $post_types,
				'post_status'    => 'future',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'date_query'     => array(
					array(
						'column'    => 'post_date_gmt',
						'before'    => gmdate( 'Y-m-d H:i:s' ),
						'inclusive' => false,
					),
				),
			)
		);

		$missed_count = (int) $missed->found_posts;
		if ( $missed_count > 0 ) {
			$warnings[] = sprintf(
				/* translators: %d: number of missed schedules. */
				_n(
					'%d post has passed its scheduled time but is still waiting to publish.',
					'%d posts have passed their scheduled time but are still waiting to publish.',
					$missed_count,
					'wp-scheduled-posts'
				),
				$missed_count
			);
		}

		// -- Cron ------------------------------------------------------------
		$cron_disabled = defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON;
		if ( $cron_disabled ) {
			$warnings[] = __( 'WP-Cron is disabled (DISABLE_WP_CRON). Scheduled posts will not publish unless a real cron job calls wp-cron.php.', 'wp-scheduled-posts' );
		}

		$next_publish = 0;
		$crons        = _get_cron_array();
		if ( is_array( $crons ) ) {
			foreach ( $crons as $timestamp => $hooks ) {
				if ( isset( $hooks['publish_future_post'] ) ) {
					$next_publish = (int) $timestamp;
					break;
				}
			}
		}

		// -- Social ----------------------------------------------------------
		$total_profiles = 0;
		$broken         = 0;
		$expiring       = array();
		$soon           = time() + ( 7 * DAY_IN_SECONDS );

		foreach ( $this->platform_slugs() as $platform ) {
			foreach ( $this->read_profiles( $platform ) as $profile ) {
				$total_profiles++;
				if ( ! $profile['active'] || ! $profile['has_token'] || $profile['expired'] ) {
					$broken++;
					continue;
				}
				if ( '' !== $profile['expires_at'] ) {
					$expires = strtotime( $profile['expires_at'] );
					if ( $expires && $expires < $soon ) {
						$expiring[] = $profile['platform'] . ': ' . $profile['name'];
					}
				}
			}
		}

		if ( $broken > 0 ) {
			$warnings[] = sprintf(
				/* translators: %d: number of social profiles needing attention. */
				_n(
					'%d social connection is inactive or has an expired token and needs reconnecting.',
					'%d social connections are inactive or have expired tokens and need reconnecting.',
					$broken,
					'wp-scheduled-posts'
				),
				$broken
			);
		}
		if ( ! empty( $expiring ) ) {
			$warnings[] = sprintf(
				/* translators: %s: comma-separated list of social profiles. */
				__( 'These social tokens expire within a week: %s.', 'wp-scheduled-posts' ),
				implode( ', ', $expiring )
			);
		}

		// -- MCP -------------------------------------------------------------
		$runtime_ok = function_exists( 'wp_register_ability' );
		if ( ! $runtime_ok ) {
			$warnings[] = __( 'The bundled Abilities runtime is missing, so AI clients connect but see no tools. Reinstall the plugin from an official build.', 'wp-scheduled-posts' );
		}

		return array(
			'plugin'   => array(
				'version'     => defined( 'WPSP_VERSION' ) ? WPSP_VERSION : '',
				'pro_active'  => (bool) Helper::get_settings( 'is_pro' ),
				'post_types'  => $post_types,
			),
			'site'     => array(
				'url'          => home_url(),
				'timezone'     => $this->site_timezone()->getName(),
				'current_time' => $now->format( 'c' ),
				'wp_version'   => get_bloginfo( 'version' ),
				'php_version'  => PHP_VERSION,
			),
			'cron'     => array(
				'disabled'            => $cron_disabled,
				'next_publish_run'    => $next_publish ? gmdate( 'c', $next_publish ) : '',
				'next_publish_in_sec' => $next_publish ? max( 0, $next_publish - time() ) : null,
			),
			'schedule' => array(
				'scheduled_count' => (int) $queued->found_posts,
				'missed_count'    => $missed_count,
			),
			'social'   => array(
				'profiles_total'      => $total_profiles,
				'profiles_needing_attention' => $broken,
				'expiring_soon'       => $expiring,
				'share_on_publish'    => (bool) Helper::get_settings( 'is_share_on_post_publish' ),
			),
			'mcp'      => array(
				'runtime_available' => $runtime_ok,
				'social_publishing_allowed' => ShareNow::is_publishing_allowed(),
			),
			'warnings' => $warnings,
		);
	}
}
