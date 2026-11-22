<?php
/**
 * The settings an MCP client may read and write.
 *
 * @package WPSP\Abilities\Settings
 */

namespace WPSP\Abilities\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * An explicit allow-list of settings keys, with the type each one is stored as.
 *
 * This is deliberately an allow-list rather than a deny-list. Every setting
 * this plugin has — including the social profile lists (which hold access
 * tokens, refresh tokens and app secrets) and the OpenAI API key — lives in the
 * SAME option. Exposing the option and subtracting the secrets would mean any
 * future key added upstream leaks by default, and the first such leak is an
 * account takeover. Naming what may be shared inverts that: a new key is
 * invisible until someone deliberately adds it here.
 */
trait SettingsMap {

	/**
	 * Setting key => storage type ('bool', 'int', 'string', 'string[]').
	 *
	 * @return array
	 */
	protected function settings_map() {
		return array(
			// What SchedulePress manages.
			'allow_post_types'                  => 'string[]',
			'allow_user_by_role'                => 'string[]',
			'allow_categories'                  => 'string[]',
			// Display surfaces.
			'is_show_dashboard_widget'          => 'int',
			'is_show_admin_bar_posts'           => 'int',
			'is_show_sitewide_bar_posts'        => 'int',
			'show_publish_post_button'          => 'int',
			// Scheduling behaviour.
			'is_active_missed_schedule'         => 'int',
			'set_future_date_on_post_publish'   => 'bool',
			'calendar_schedule_time'            => 'string',
			'post_publishing_and_sharing_option' => 'int',
			// Sharing behaviour (not credentials).
			'is_share_on_post_publish'          => 'bool',
			'is_republish_social_share'         => 'bool',
			// Email notifications.
			'notify_author_post_is_publish'     => 'bool',
			'notify_author_post_is_rejected'    => 'bool',
			'notify_author_post_is_review'      => 'bool',
			'notify_author_post_is_scheduled'   => 'bool',
			'notify_author_post_scheduled_to_publish' => 'bool',
			// MCP itself.
			'enable_mcp'                        => 'bool',
			'enable_mcp_social_publish'         => 'bool',
		);
	}

	/**
	 * Keys that may be read but never written through an ability.
	 *
	 * @return array
	 */
	protected function read_only_settings() {
		return array( 'is_pro' => 'bool' );
	}

	/**
	 * Coerce a value to the type a setting is stored as, so a model passing
	 * `"true"` or `1` cannot corrupt the option's shape.
	 *
	 * @param mixed  $value Incoming value.
	 * @param string $type  Declared type.
	 * @return mixed
	 */
	protected function cast_setting( $value, $type ) {
		switch ( $type ) {
			case 'bool':
				return (bool) rest_sanitize_boolean( $value );
			case 'int':
				return (int) $value;
			case 'string[]':
				$value = is_array( $value ) ? $value : array( $value );
				return array_values( array_map( 'sanitize_text_field', array_map( 'strval', $value ) ) );
			case 'string':
			default:
				return sanitize_text_field( (string) $value );
		}
	}
}
