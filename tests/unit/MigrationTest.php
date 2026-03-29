<?php
/**
 * Unit tests for WPSP\Migration
 *
 * @package WPSP\Tests\Unit
 */

namespace WPSP\Tests\Unit;

use WP_UnitTestCase;
use WPSP\Migration;

/**
 * Tests for the Migration utility class.
 *
 * Pure-PHP methods (convert_to_12_hour_format) are covered exhaustively.
 * DB-driven migration methods are tested for their idempotency guards.
 */
class MigrationTest extends WP_UnitTestCase {

    protected function setUp(): void {
        parent::setUp();
        // Start each test with a clean slate.
        delete_option( 'wpsp_data_migration_3_to_4' );
        delete_option( 'wpsp_data_migration_4_to_5' );
        delete_option( 'wpsp_data_migration_allow_categories' );
        delete_option( 'wpscp_options' );
        delete_option( WPSP_SETTINGS_NAME );
    }

    // -------------------------------------------------------------------------
    // convert_to_12_hour_format() — pure PHP, no WordPress dependencies
    // -------------------------------------------------------------------------

    /**
     * @test
     */
    public function test_convert_midnight_to_12_hour_format(): void {
        $result = Migration::convert_to_12_hour_format( '00:00' );

        $this->assertSame( '12:00 AM', $result );
    }

    /**
     * @test
     */
    public function test_convert_noon_to_12_hour_format(): void {
        $result = Migration::convert_to_12_hour_format( '12:00' );

        $this->assertSame( '12:00 PM', $result );
    }

    /**
     * @test
     */
    public function test_convert_afternoon_hour_to_12_hour_format(): void {
        $result = Migration::convert_to_12_hour_format( '13:30' );

        $this->assertSame( '01:30 PM', $result );
    }

    /**
     * @test
     */
    public function test_convert_last_minute_of_day_to_12_hour_format(): void {
        $result = Migration::convert_to_12_hour_format( '23:59' );

        $this->assertSame( '11:59 PM', $result );
    }

    /**
     * @test
     */
    public function test_convert_early_morning_hour_to_12_hour_format(): void {
        $result = Migration::convert_to_12_hour_format( '09:05' );

        $this->assertSame( '09:05 AM', $result );
    }

    /**
     * @test
     */
    public function test_convert_1am_to_12_hour_format(): void {
        $result = Migration::convert_to_12_hour_format( '01:00' );

        $this->assertSame( '01:00 AM', $result );
    }

    /**
     * @test
     */
    public function test_convert_11am_to_12_hour_format(): void {
        $result = Migration::convert_to_12_hour_format( '11:45' );

        $this->assertSame( '11:45 AM', $result );
    }

    /**
     * @test
     */
    public function test_convert_1pm_to_12_hour_format(): void {
        $result = Migration::convert_to_12_hour_format( '13:00' );

        $this->assertSame( '01:00 PM', $result );
    }

    // -------------------------------------------------------------------------
    // version_3_to_4() — idempotency guard
    // -------------------------------------------------------------------------

    /**
     * @test
     * When the migration-complete flag is already set, version_3_to_4() must
     * be a no-op regardless of what legacy options are present.
     */
    public function test_version_3_to_4_is_skipped_when_already_migrated(): void {
        update_option( 'wpsp_data_migration_3_to_4', true );
        // Simulate legacy data that would normally trigger migration.
        update_option( 'wpscp_options', [ 'allow_post_types' => [ 'post' ] ] );

        Migration::version_3_to_4();

        // The WPSP_SETTINGS_NAME option should NOT have been written.
        $this->assertFalse( get_option( WPSP_SETTINGS_NAME ) );
    }

    /**
     * @test
     * When no legacy v3 option exists the migration should not write anything
     * and should leave the flag unset.
     */
    public function test_version_3_to_4_does_nothing_when_no_legacy_option(): void {
        delete_option( 'wpscp_options' );

        Migration::version_3_to_4();

        $this->assertFalse( get_option( 'wpsp_data_migration_3_to_4' ) );
    }

    /**
     * @test
     * When legacy v3 data exists, version_3_to_4() should map allow_post_types
     * into the new settings format and set the migration flag.
     */
    public function test_version_3_to_4_maps_allow_post_types(): void {
        update_option( 'wpscp_options', [ 'allow_post_types' => [ 'post', 'page' ] ] );
        update_option( WPSP_SETTINGS_NAME, json_encode( [] ) );

        Migration::version_3_to_4();

        $saved = json_decode( get_option( WPSP_SETTINGS_NAME ), true );

        $this->assertArrayHasKey( 'allow_post_types', $saved );
        $this->assertContains( 'post', $saved['allow_post_types'] );
        $this->assertTrue( (bool) get_option( 'wpsp_data_migration_3_to_4' ) );
    }

    /**
     * @test
     * When legacy v3 data exists, show_dashboard_widget should be mapped to
     * is_show_dashboard_widget in the new settings.
     */
    public function test_version_3_to_4_maps_show_dashboard_widget(): void {
        update_option( 'wpscp_options', [ 'show_dashboard_widget' => true ] );
        update_option( WPSP_SETTINGS_NAME, json_encode( [] ) );

        Migration::version_3_to_4();

        $saved = json_decode( get_option( WPSP_SETTINGS_NAME ), true );

        $this->assertArrayHasKey( 'is_show_dashboard_widget', $saved );
        $this->assertTrue( $saved['is_show_dashboard_widget'] );
    }

    /**
     * @test
     * Facebook integration status 'on' should be saved as boolean true.
     */
    public function test_version_3_to_4_maps_facebook_status_on_to_true(): void {
        // wpscp_options must be non-empty: PHP empty array == false in loose comparison,
        // which causes the migration to skip its body entirely.
        update_option( 'wpscp_options', [ 'allow_post_types' => [ 'post' ] ] );
        update_option( 'wpscp_facebook_account', [ 'page_id' => '123' ] );
        update_option( 'wpsp_facebook_integration_status', 'on' );
        update_option( WPSP_SETTINGS_NAME, json_encode( [] ) );

        Migration::version_3_to_4();

        $saved = json_decode( get_option( WPSP_SETTINGS_NAME ), true );

        $this->assertTrue( $saved['facebook_profile_status'] );
    }

    // -------------------------------------------------------------------------
    // version_4_to_5() — idempotency guard
    // -------------------------------------------------------------------------

    /**
     * @test
     * When the migration-complete flag is already set, version_4_to_5() must
     * be a no-op.
     */
    public function test_version_4_to_5_is_skipped_when_already_migrated(): void {
        update_option( 'wpsp_data_migration_4_to_5', true );
        update_option( 'wpsp_settings', json_encode( [ 'some_old_key' => 'value' ] ) );

        Migration::version_4_to_5();

        // WPSP_SETTINGS_NAME should not have been touched.
        $this->assertFalse( get_option( WPSP_SETTINGS_NAME ) );
    }

    /**
     * @test
     * When no old v4 settings exist, version_4_to_5() should not set the flag.
     */
    public function test_version_4_to_5_does_nothing_when_no_old_settings(): void {
        delete_option( 'wpsp_settings' );

        Migration::version_4_to_5();

        $this->assertFalse( get_option( 'wpsp_data_migration_4_to_5' ) );
    }

    /**
     * @test
     * hide_on_elementor_editor (true) should be inverted to
     * show_on_elementor_editor (false) and the old key removed.
     */
    public function test_version_4_to_5_inverts_hide_on_elementor_editor(): void {
        update_option( 'wpsp_settings', json_encode( [ 'hide_on_elementor_editor' => true ] ) );

        Migration::version_4_to_5();

        $saved = json_decode( get_option( WPSP_SETTINGS_NAME ), true );

        $this->assertArrayHasKey( 'show_on_elementor_editor', $saved );
        $this->assertFalse( $saved['show_on_elementor_editor'] );
        $this->assertArrayNotHasKey( 'hide_on_elementor_editor', $saved );
    }

    // -------------------------------------------------------------------------
    // allow_categories() — idempotency guard
    // -------------------------------------------------------------------------

    /**
     * @test
     * When the migration flag is already set, allow_categories() should not
     * modify the stored settings.
     */
    public function test_allow_categories_is_skipped_when_already_migrated(): void {
        update_option( 'wpsp_data_migration_allow_categories', true );
        update_option( WPSP_SETTINGS_NAME, json_encode( [ 'allow_categories' => [ 'wordpress' ] ] ) );

        Migration::allow_categories();

        $saved = json_decode( get_option( WPSP_SETTINGS_NAME ), true );

        // The category slug should NOT have been prefixed again.
        $this->assertContains( 'wordpress', $saved['allow_categories'] );
    }

    /**
     * @test
     * When allow_categories contains slugs, they should be prefixed with
     * "category." and the migration flag should be set.
     */
    public function test_allow_categories_prefixes_slugs_with_taxonomy(): void {
        // Seed social_templates.google_business so GoogleBusiness::__construct()
        // does not crash when the option update triggers SocialProfile hooks.
        update_option( WPSP_SETTINGS_NAME, json_encode( [
            'allow_categories' => [ 'all', 'wordpress', 'php' ],
            'social_templates'  => [ 'google_business' => [] ],
        ] ) );

        Migration::allow_categories();

        $saved = json_decode( get_option( WPSP_SETTINGS_NAME ), true );

        // 'all' should remain unchanged.
        $this->assertContains( 'all', $saved['allow_categories'] );

        // Other slugs should be prefixed.
        $this->assertContains( 'category.wordpress', $saved['allow_categories'] );
        $this->assertContains( 'category.php', $saved['allow_categories'] );

        $this->assertTrue( (bool) get_option( 'wpsp_data_migration_allow_categories' ) );
    }
}
