<?php
/**
 * This file is for testing WPCS pre-commit hook with all issues fixed.
 * Stage this file and commit - it should PASS without any violations.
 *
 * @package SchedulePress
 */

/**
 * Get user data by ID.
 *
 * @param int $id User ID.
 * @return int
 */
function get_user_data( $id ) {
	// GOOD: sanitized input.
	$search = isset( $_GET['query'] ) ? sanitize_text_field( wp_unslash( $_GET['query'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

	// GOOD: escaped output.
	echo esc_html( $search );

	// GOOD: Yoda condition used.
	if ( 1 === $id ) {
		echo esc_html( 'admin found' );
	}

	return $id;
}
