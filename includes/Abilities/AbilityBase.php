<?php
/**
 * Ability base class.
 *
 * @package WPSP\Abilities
 */

namespace WPSP\Abilities;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Base ability implementation for SchedulePress.
 *
 * Each SchedulePress MCP ability extends this class, providing an input/output
 * JSON schema and an execute() body. Abilities are registered with the
 * WordPress Abilities API and exposed to AI clients through the MCP server.
 */
abstract class AbilityBase {

	/**
	 * Minimum capability allowed for SchedulePress abilities.
	 */
	const MIN_CAPABILITY = 'manage_options';

	/**
	 * Unique ability identifier.
	 *
	 * @var string
	 */
	protected $id = '';

	/**
	 * Human-readable label.
	 *
	 * @var string
	 */
	protected $label = '';

	/**
	 * Ability description.
	 *
	 * @var string
	 */
	protected $description = '';

	/**
	 * Ability category.
	 *
	 * @var string
	 */
	protected $category = 'schedulepress';

	/**
	 * Required WordPress capability.
	 *
	 * @var string
	 */
	protected $capability = 'manage_options';

	/**
	 * Get the JSON Schema for ability input.
	 *
	 * @return array
	 */
	abstract public function get_input_schema();

	/**
	 * Get the JSON Schema for ability output.
	 *
	 * @return array
	 */
	abstract public function get_output_schema();

	/**
	 * Execute the ability.
	 *
	 * @param array $input Validated input.
	 * @return array|\WP_Error
	 */
	abstract public function execute( $input );

	/**
	 * Check whether abilities are enabled.
	 *
	 * @return bool
	 */
	public static function abilities_enabled() {
		return (bool) apply_filters( 'wpsp_abilities_api_enabled', true );
	}

	/**
	 * Check whether this ability can be registered and executed.
	 *
	 * @return bool
	 */
	public function is_enabled() {
		return (bool) apply_filters( 'wpsp_ability_enabled', self::abilities_enabled(), $this->id, $this );
	}

	/**
	 * Permission callback for the abilities API.
	 *
	 * @return bool
	 */
	public function permission_callback() {
		if ( ! $this->is_enabled() ) {
			return false;
		}

		return current_user_can( $this->capability );
	}

	/**
	 * Enforce SchedulePress's current admin capability policy.
	 *
	 * @return bool
	 */
	public function meets_capability_policy() {
		return self::MIN_CAPABILITY === $this->capability;
	}

	/**
	 * MCP-compatible annotations for this ability.
	 *
	 * @return array
	 */
	public function get_annotations() {
		return array(
			'readonly'      => false,
			'destructive'   => false,
			'idempotent'    => false,
			'priority'      => 2.0,
			'openWorldHint' => false,
		);
	}

	/**
	 * Wrapper around execute() with action hooks.
	 *
	 * @param array $input Validated input.
	 * @return array|\WP_Error
	 */
	public function execute_wrapper( $input ) {
		do_action( 'wpsp_before_ability_execute', $this->id, $input );

		$output = $this->execute( $input );

		do_action( 'wpsp_after_ability_execute', $this->id, $input, $output );

		return $output;
	}

	/**
	 * Register the ability with the WordPress Abilities API.
	 *
	 * @return void
	 */
	public function register() {
		if ( ! function_exists( 'wp_register_ability' ) ) {
			return;
		}

		wp_register_ability(
			$this->id,
			array(
				'label'               => $this->label,
				'description'         => $this->description,
				'category'            => $this->category,
				'input_schema'        => $this->get_input_schema(),
				'output_schema'       => $this->get_output_schema(),
				'permission_callback' => array( $this, 'permission_callback' ),
				'execute_callback'    => array( $this, 'execute_wrapper' ),
				'meta'                => array(
					'show_in_rest' => true,
					'annotations'  => $this->get_annotations(),
					'mcp'          => array(
						'public' => false,
					),
				),
			)
		);
	}

	/**
	 * Get the ability ID.
	 *
	 * @return string
	 */
	public function get_id() {
		return $this->id;
	}

	// -- Shared helpers for schedule-facing abilities ----------------------

	/**
	 * The site's configured timezone, resolved the way WordPress itself does.
	 *
	 * Every date this plugin's abilities emit or accept is anchored to this
	 * zone. Scheduling is the one domain where an off-by-one-hour bug is a
	 * published-at-the-wrong-time incident, so no ability may hand an AI client
	 * a bare "2026-10-03 14:00" and hope both sides guess the same offset.
	 *
	 * @return \DateTimeZone
	 */
	protected function site_timezone() {
		if ( function_exists( 'wp_timezone' ) ) {
			return wp_timezone();
		}
		return new \DateTimeZone( date_default_timezone_get() );
	}

	/**
	 * Format a WordPress local datetime string as ISO 8601 with the site's
	 * offset, so the value round-trips through an AI client unambiguously.
	 *
	 * @param string $mysql_datetime Local `Y-m-d H:i:s` as stored in post_date.
	 * @return string ISO 8601 datetime, or '' when unparseable.
	 */
	protected function to_iso( $mysql_datetime ) {
		$mysql_datetime = (string) $mysql_datetime;
		if ( '' === $mysql_datetime || '0000-00-00 00:00:00' === $mysql_datetime ) {
			return '';
		}
		try {
			$date = new \DateTime( $mysql_datetime, $this->site_timezone() );
		} catch ( \Exception $e ) {
			return '';
		}
		return $date->format( 'c' );
	}

	/**
	 * Parse a client-supplied datetime into the site's timezone.
	 *
	 * Accepts a full ISO 8601 value (with or without an explicit offset) and
	 * anything else `DateTime` understands. A value with NO offset is read as
	 * site-local, which is what a user means by "schedule it for 9am".
	 *
	 * @param string $value Client-supplied datetime.
	 * @return \DateTime|\WP_Error
	 */
	protected function parse_datetime( $value ) {
		$value = trim( (string) $value );
		if ( '' === $value ) {
			return new \WP_Error(
				'wpsp_invalid_datetime',
				__( 'A date/time is required.', 'wp-scheduled-posts' ),
				array( 'status' => 400 )
			);
		}
		try {
			$date = new \DateTime( $value, $this->site_timezone() );
		} catch ( \Exception $e ) {
			return new \WP_Error(
				'wpsp_invalid_datetime',
				sprintf(
					/* translators: %s: the datetime value that could not be parsed. */
					__( 'Could not understand the date/time "%s". Use ISO 8601, for example 2026-10-03T14:00:00.', 'wp-scheduled-posts' ),
					$value
				),
				array( 'status' => 400 )
			);
		}
		// Normalize into the site zone so post_date is written site-local.
		$date->setTimezone( $this->site_timezone() );
		return $date;
	}

	/**
	 * A compact, safe summary of a post for schedule-facing tool output.
	 *
	 * @param \WP_Post $post Post to describe.
	 * @return array
	 */
	protected function describe_post( $post ) {
		return array(
			'id'           => (int) $post->ID,
			'title'        => (string) get_the_title( $post ),
			'post_type'    => (string) $post->post_type,
			'status'       => (string) $post->post_status,
			'scheduled_at' => $this->to_iso( $post->post_date ),
			'scheduled_at_gmt' => $this->to_iso_gmt( $post->post_date_gmt ),
			'author'       => (string) get_the_author_meta( 'display_name', $post->post_author ),
			'edit_link'    => (string) get_edit_post_link( $post->ID, 'raw' ),
			'permalink'    => (string) get_permalink( $post->ID ),
		);
	}

	/**
	 * Format a GMT datetime string as ISO 8601 in UTC.
	 *
	 * @param string $mysql_datetime GMT `Y-m-d H:i:s` as stored in post_date_gmt.
	 * @return string
	 */
	protected function to_iso_gmt( $mysql_datetime ) {
		$mysql_datetime = (string) $mysql_datetime;
		if ( '' === $mysql_datetime || '0000-00-00 00:00:00' === $mysql_datetime ) {
			return '';
		}
		try {
			$date = new \DateTime( $mysql_datetime, new \DateTimeZone( 'UTC' ) );
		} catch ( \Exception $e ) {
			return '';
		}
		return $date->format( 'c' );
	}

	/**
	 * The post types SchedulePress is configured to manage, falling back to
	 * every public type when the setting is empty.
	 *
	 * @return string[]
	 */
	protected function allowed_post_types() {
		$allowed = array();
		if ( class_exists( '\WPSP\Helper' ) && method_exists( '\WPSP\Helper', 'get_allow_post_types' ) ) {
			// Keyed by post-type slug (the value is the human label), for both
			// the configured list and the all-types fallback it delegates to.
			$configured = \WPSP\Helper::get_allow_post_types();
			if ( is_array( $configured ) ) {
				$allowed = array_map( 'strval', array_keys( $configured ) );
			}
		}
		$allowed = array_values( array_filter( array_unique( $allowed ), 'post_type_exists' ) );
		if ( empty( $allowed ) ) {
			$allowed = array_values( get_post_types( array( 'public' => true ), 'names' ) );
		}
		return $allowed;
	}

	/**
	 * Validate a caller-supplied post-type list against what this site allows.
	 *
	 * @param mixed $requested Requested post types (array or empty).
	 * @return string[]|\WP_Error
	 */
	protected function resolve_post_types( $requested ) {
		$allowed = $this->allowed_post_types();
		if ( empty( $requested ) || ! is_array( $requested ) ) {
			return $allowed;
		}
		$requested = array_values( array_unique( array_map( 'strval', $requested ) ) );
		$unknown   = array();
		foreach ( $requested as $type ) {
			if ( ! post_type_exists( $type ) ) {
				$unknown[] = $type;
			}
		}
		if ( ! empty( $unknown ) ) {
			return new \WP_Error(
				'wpsp_unknown_post_type',
				sprintf(
					/* translators: 1: the unknown post type(s), 2: the post types this site supports. */
					__( 'Unknown post type(s): %1$s. This site supports: %2$s.', 'wp-scheduled-posts' ),
					implode( ', ', $unknown ),
					implode( ', ', $allowed )
				),
				array( 'status' => 400 )
			);
		}
		return $requested;
	}

	/**
	 * Guard a per-post write behind the same capability wp-admin would apply.
	 *
	 * The MCP request already runs as the granting admin, but an ability that
	 * only checked `manage_options` would ignore per-post capability filters
	 * (and SchedulePress's own access rules) that a site may layer on top.
	 *
	 * @param int    $post_id Target post.
	 * @param string $cap     Capability to require, default `edit_post`.
	 * @return \WP_Post|\WP_Error The post when permitted.
	 */
	protected function require_post( $post_id, $cap = 'edit_post' ) {
		$post_id = (int) $post_id;
		$post    = get_post( $post_id );
		if ( ! $post ) {
			return new \WP_Error(
				'wpsp_post_not_found',
				sprintf(
					/* translators: %d: post ID. */
					__( 'No post found with ID %d.', 'wp-scheduled-posts' ),
					$post_id
				),
				array( 'status' => 404 )
			);
		}
		if ( ! current_user_can( $cap, $post_id ) ) {
			return new \WP_Error(
				'wpsp_forbidden',
				sprintf(
					/* translators: %d: post ID. */
					__( 'You do not have permission to modify post %d.', 'wp-scheduled-posts' ),
					$post_id
				),
				array( 'status' => 403 )
			);
		}
		return $post;
	}
}
