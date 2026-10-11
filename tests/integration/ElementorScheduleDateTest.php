<?php
/**
 * Integration tests: the Elementor modal's admin-ajax save (wpsp_el_editor_form)
 * stores post_date_gmt with the offset of the scheduled date, not today's.
 *
 * @package WPScheduledPosts
 */

namespace WPSP\Tests\Integration;

use WP_Ajax_UnitTestCase;
use WPAjaxDieContinueException;
use WPSP\Admin;

class ElementorScheduleDateTest extends WP_Ajax_UnitTestCase {

	private function schedule_through_the_modal( $date ) {
		if ( ! has_action( 'wp_ajax_wpsp_el_editor_form' ) ) {
			// Admin only loads in wp-admin, so register just this handler.
			$admin = ( new \ReflectionClass( Admin::class ) )->newInstanceWithoutConstructor();
			add_action( 'wp_ajax_wpsp_el_editor_form', array( $admin, 'wpsp_el_tab_action' ) );
		}
		wp_set_current_user( 1 );
		$id = wp_insert_post( array( 'post_title' => 'Elementor date', 'post_status' => 'draft' ) );

		$_POST = array(
			'wpsp-el-editor' => wp_create_nonce( 'wpsp-el-editor' ),
			'id'             => $id,
			'date'           => $date,
			'post_status'    => 'future',
		);
		try {
			$this->_handleAjax( 'wpsp_el_editor_form' );
		} catch ( WPAjaxDieContinueException $e ) {
			unset( $e );
		}

		return get_post_field( 'post_date_gmt', $id );
	}

	public function test_gmt_date_uses_the_offset_of_the_date() {
		// One date in each half of the year, so today's offset is wrong for one of them.
		update_option( 'timezone_string', 'America/New_York' );

		$this->assertSame( '2036-07-10 14:00:00', $this->schedule_through_the_modal( '2036-07-10 10:00:00' ) );
		$this->assertSame( '2036-12-10 15:00:00', $this->schedule_through_the_modal( '2036-12-10 10:00:00' ) );
	}

	public function test_half_hour_time_zone() {
		update_option( 'timezone_string', 'Asia/Kolkata' );

		$this->assertSame( '2036-12-10 04:30:00', $this->schedule_through_the_modal( '2036-12-10 10:00:00' ) );
	}
}
