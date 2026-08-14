<?php
/**
 * Update settings ability.
 *
 * @package WPSP\Abilities\Settings
 */

namespace WPSP\Abilities\Settings;

use WPSP\Abilities\AbilityBase;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Writes the shareable SchedulePress settings.
 *
 * Only keys named in the allow-list can be written, and each is coerced to the
 * type it is stored as — a partial write must never reshape the option that
 * also holds every social credential.
 */
class UpdateSettings extends AbilityBase {

	use SettingsMap;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id          = 'schedulepress/update-settings';
		$this->label       = __( 'Update SchedulePress Settings', 'wp-scheduled-posts' );
		$this->description = __( 'Change SchedulePress configuration — managed post types and roles, display toggles, missed-schedule handling, auto-share behaviour and email notifications. Only the settings listed by get-settings as writable can be changed.', 'wp-scheduled-posts' );
	}

	/**
	 * {@inheritDoc}
	 *
	 * @return array
	 */
	public function get_annotations() {
		return array(
			'readonly'      => false,
			'destructive'   => false,
			'idempotent'    => true,
			'priority'      => 2.0,
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
			'required'             => array( 'settings' ),
			'properties'           => array(
				'settings' => array(
					'type'        => 'object',
					'description' => 'Map of setting key to new value. Call get-settings first for the writable keys and their current values.',
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
				'updated'  => array(
					'type'  => 'array',
					'items' => array( 'type' => 'string' ),
				),
				'settings' => array( 'type' => 'object' ),
				'previous' => array( 'type' => 'object' ),
			),
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

		$incoming = isset( $input['settings'] ) ? $input['settings'] : null;
		if ( is_object( $incoming ) ) {
			$incoming = (array) $incoming;
		}
		if ( ! is_array( $incoming ) || empty( $incoming ) ) {
			return new \WP_Error(
				'wpsp_no_settings',
				__( 'Provide a settings object with at least one key to change.', 'wp-scheduled-posts' ),
				array( 'status' => 400 )
			);
		}

		$writable = $this->settings_map();
		$unknown  = array_diff( array_keys( $incoming ), array_keys( $writable ) );
		if ( ! empty( $unknown ) ) {
			return new \WP_Error(
				'wpsp_unknown_setting',
				sprintf(
					/* translators: 1: the rejected setting keys, 2: the writable keys. */
					__( 'These settings cannot be changed through this tool: %1$s. Writable settings are: %2$s.', 'wp-scheduled-posts' ),
					implode( ', ', $unknown ),
					implode( ', ', array_keys( $writable ) )
				),
				array( 'status' => 400 )
			);
		}

		// Read-modify-write the whole option: it holds far more than the
		// allow-list (social credentials among it), so anything not explicitly
		// changed must be carried across untouched.
		$stored = json_decode( get_option( WPSP_SETTINGS_NAME, '{}' ), true );
		if ( ! is_array( $stored ) ) {
			$stored = array();
		}

		$previous = array();
		$updated  = array();
		$applied  = array();

		foreach ( $incoming as $key => $value ) {
			$type              = $writable[ $key ];
			$previous[ $key ]  = isset( $stored[ $key ] ) ? $stored[ $key ] : null;
			$cast              = $this->cast_setting( $value, $type );
			$stored[ $key ]    = $cast;
			$applied[ $key ]   = $cast;
			$updated[]         = (string) $key;
		}

		$saved = update_option( WPSP_SETTINGS_NAME, wp_json_encode( $stored ) );
		if ( false === $saved && $applied !== $previous ) {
			// update_option() also returns false when the value is unchanged, so
			// only treat it as a failure when something really should have moved.
			$fresh = json_decode( get_option( WPSP_SETTINGS_NAME, '{}' ), true );
			foreach ( $applied as $key => $value ) {
				if ( ! is_array( $fresh ) || ! array_key_exists( $key, $fresh ) || $fresh[ $key ] !== $value ) {
					return new \WP_Error(
						'wpsp_settings_not_saved',
						__( 'The settings could not be saved. Another plugin may be filtering this option.', 'wp-scheduled-posts' ),
						array( 'status' => 500 )
					);
				}
			}
		}

		// Keep the in-request global the rest of the plugin reads in step with
		// what was just written, so a follow-up tool call in the same request
		// does not see stale settings.
		$GLOBALS['wpsp_settings_v5'] = json_decode( get_option( WPSP_SETTINGS_NAME ) );

		return array(
			'updated'  => $updated,
			'settings' => $applied,
			'previous' => $previous,
		);
	}
}
