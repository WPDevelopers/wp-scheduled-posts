<?php
/**
 * Get settings ability.
 *
 * @package WPSP\Abilities\Settings
 */

namespace WPSP\Abilities\Settings;

use WPSP\Abilities\AbilityBase;
use WPSP\Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Reads the shareable SchedulePress settings.
 */
class GetSettings extends AbilityBase {

	use SettingsMap;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id          = 'schedulepress/get-settings';
		$this->label       = __( 'Get SchedulePress Settings', 'wp-scheduled-posts' );
		$this->description = __( 'Read SchedulePress configuration: which post types and roles it manages, dashboard and admin-bar display, missed-schedule handling, auto-share behaviour and email notification toggles. Credentials are never included.', 'wp-scheduled-posts' );
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
			'properties'           => array(
				'keys' => array(
					'type'        => 'array',
					'items'       => array( 'type' => 'string' ),
					'description' => 'Restrict the response to these setting keys. Omit for all readable settings.',
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
				'settings'      => array( 'type' => 'object' ),
				'writable_keys' => array(
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
	 * @return array|\WP_Error
	 */
	public function execute( $input ) {
		$input = is_array( $input ) ? $input : array();

		$readable = array_merge( $this->settings_map(), $this->read_only_settings() );

		$wanted = $readable;
		if ( ! empty( $input['keys'] ) && is_array( $input['keys'] ) ) {
			$requested = array_map( 'strval', $input['keys'] );
			$unknown   = array_diff( $requested, array_keys( $readable ) );
			if ( ! empty( $unknown ) ) {
				return new \WP_Error(
					'wpsp_unknown_setting',
					sprintf(
						/* translators: 1: the unknown setting keys, 2: the readable keys. */
						__( 'Unknown or non-readable setting(s): %1$s. Readable settings are: %2$s.', 'wp-scheduled-posts' ),
						implode( ', ', $unknown ),
						implode( ', ', array_keys( $readable ) )
					),
					array( 'status' => 400 )
				);
			}
			$wanted = array_intersect_key( $readable, array_flip( $requested ) );
		}

		$settings = array();
		foreach ( $wanted as $key => $type ) {
			$value = Helper::get_settings( $key );
			// An unset setting reads as null; report the type's empty value so a
			// model does not have to guess what null means for a toggle.
			if ( null === $value ) {
				$value = ( 'string[]' === $type ) ? array() : ( 'string' === $type ? '' : 0 );
			}
			if ( is_object( $value ) ) {
				$value = (array) $value;
			}
			$settings[ $key ] = $this->cast_setting( $value, $type );
		}

		return array(
			'settings'      => $settings,
			'writable_keys' => array_keys( $this->settings_map() ),
		);
	}
}
