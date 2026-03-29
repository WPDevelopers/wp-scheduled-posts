<?php
/**
 * Unit tests for WPSP\Helper
 *
 * @package WPSP\Tests\Unit
 */

namespace WPSP\Tests\Unit;

use WP_UnitTestCase;
use WPSP\Helper;

/**
 * Tests for the Helper utility class.
 *
 * These tests cover the static helper methods that interact with
 * WordPress options and post type / taxonomy lookups.
 */
class HelperTest extends WP_UnitTestCase {

    /**
     * Clean up the WPSP settings option before each test.
     */
    protected function setUp(): void {
        parent::setUp();
        delete_option( WPSP_SETTINGS_NAME );
    }

    // -------------------------------------------------------------------------
    // get_settings()
    // -------------------------------------------------------------------------

    /**
     * @test
     */
    public function test_get_settings_returns_null_when_option_not_set(): void {
        $result = Helper::get_settings( 'allow_post_types' );

        $this->assertNull( $result );
    }

    /**
     * @test
     */
    public function test_get_settings_returns_null_for_unknown_key(): void {
        update_option( WPSP_SETTINGS_NAME, json_encode( [ 'allow_post_types' => [ 'post' ] ] ) );

        $result = Helper::get_settings( 'non_existent_key' );

        $this->assertNull( $result );
    }

    /**
     * @test
     */
    public function test_get_settings_returns_array_value(): void {
        $expected = [ 'post', 'page' ];
        update_option( WPSP_SETTINGS_NAME, json_encode( [ 'allow_post_types' => $expected ] ) );

        $result = Helper::get_settings( 'allow_post_types' );

        $this->assertEquals( $expected, (array) $result );
    }

    /**
     * @test
     */
    public function test_get_settings_returns_boolean_value(): void {
        update_option( WPSP_SETTINGS_NAME, json_encode( [ 'is_show_dashboard_widget' => true ] ) );

        $result = Helper::get_settings( 'is_show_dashboard_widget' );

        $this->assertTrue( (bool) $result );
    }

    /**
     * @test
     */
    public function test_get_settings_returns_string_value(): void {
        update_option( WPSP_SETTINGS_NAME, json_encode( [ 'calendar_schedule_time' => '09:00' ] ) );

        $result = Helper::get_settings( 'calendar_schedule_time' );

        $this->assertSame( '09:00', $result );
    }

    // -------------------------------------------------------------------------
    // wpsp_settings_v5()
    // -------------------------------------------------------------------------

    /**
     * @test
     */
    public function test_wpsp_settings_v5_returns_empty_object_when_option_not_set(): void {
        $result = Helper::wpsp_settings_v5();

        $this->assertIsObject( $result );
    }

    /**
     * @test
     */
    public function test_wpsp_settings_v5_returns_object_with_saved_settings(): void {
        update_option( WPSP_SETTINGS_NAME, json_encode( [ 'allow_post_types' => [ 'post' ] ] ) );

        $result = Helper::wpsp_settings_v5();

        $this->assertIsObject( $result );
        $this->assertTrue( property_exists( $result, 'allow_post_types' ) );
    }

    // -------------------------------------------------------------------------
    // get_all_post_type()
    // -------------------------------------------------------------------------

    /**
     * @test
     */
    public function test_get_all_post_type_returns_array(): void {
        $result = Helper::get_all_post_type();

        $this->assertIsArray( $result );
    }

    /**
     * @test
     */
    public function test_get_all_post_type_includes_post_and_page(): void {
        $result = Helper::get_all_post_type();

        $this->assertContains( 'post', $result );
        $this->assertContains( 'page', $result );
    }

    /**
     * @test
     */
    public function test_get_all_post_type_excludes_internal_post_types(): void {
        $excluded = [ 'attachment', 'revision', 'nav_menu_item', 'custom_css' ];
        $result   = Helper::get_all_post_type();

        foreach ( $excluded as $type ) {
            $this->assertNotContains( $type, $result );
        }
    }

    // -------------------------------------------------------------------------
    // get_all_taxonomies()
    // -------------------------------------------------------------------------

    /**
     * @test
     */
    public function test_get_all_taxonomies_returns_array(): void {
        $result = Helper::get_all_taxonomies();

        $this->assertIsArray( $result );
    }

    /**
     * @test
     */
    public function test_get_all_taxonomies_excludes_internal_taxonomies(): void {
        $excluded = [ 'nav_menu', 'link_category', 'post_format' ];
        $result   = Helper::get_all_taxonomies();

        foreach ( $excluded as $tax ) {
            $this->assertNotContains( $tax, $result );
        }
    }

    /**
     * @test
     */
    public function test_get_all_taxonomies_includes_category_and_post_tag(): void {
        $result = Helper::get_all_taxonomies();

        $this->assertContains( 'category', $result );
        $this->assertContains( 'post_tag', $result );
    }

    // -------------------------------------------------------------------------
    // get_all_allowed_post_type()
    // -------------------------------------------------------------------------

    /**
     * @test
     */
    public function test_get_all_allowed_post_type_returns_all_when_setting_not_set(): void {
        $all    = Helper::get_all_post_type();
        $result = Helper::get_all_allowed_post_type();

        // When no setting is configured, falls back to all registered post types.
        $this->assertEquals( array_values( $all ), array_values( $result ) );
    }

    /**
     * @test
     */
    public function test_get_all_allowed_post_type_returns_all_when_value_is_all(): void {
        update_option( WPSP_SETTINGS_NAME, json_encode( [ 'allow_post_types' => [ 'all' ] ] ) );

        $all    = Helper::get_all_post_type();
        $result = Helper::get_all_allowed_post_type();

        $this->assertEquals( array_values( $all ), array_values( $result ) );
    }

    /**
     * @test
     */
    public function test_get_all_allowed_post_type_returns_configured_types(): void {
        update_option( WPSP_SETTINGS_NAME, json_encode( [ 'allow_post_types' => [ 'post' ] ] ) );

        $result = Helper::get_all_allowed_post_type();

        $this->assertEquals( [ 'post' ], $result );
    }

    // -------------------------------------------------------------------------
    // is_user_allow()
    // -------------------------------------------------------------------------

    /**
     * @test
     */
    public function test_is_user_allow_returns_false_when_no_user_logged_in(): void {
        wp_set_current_user( 0 );

        $result = Helper::is_user_allow();

        $this->assertFalse( $result );
    }

    /**
     * @test
     */
    public function test_is_user_allow_returns_true_for_administrator(): void {
        $admin_id = $this->factory->user->create( [ 'role' => 'administrator' ] );
        wp_set_current_user( $admin_id );

        $result = Helper::is_user_allow();

        $this->assertTrue( $result );
    }

    /**
     * @test
     */
    public function test_is_user_allow_returns_false_for_subscriber_by_default(): void {
        $subscriber_id = $this->factory->user->create( [ 'role' => 'subscriber' ] );
        wp_set_current_user( $subscriber_id );

        // Default allowed roles is only administrator.
        $result = Helper::is_user_allow();

        $this->assertFalse( $result );
    }

    /**
     * @test
     */
    public function test_is_user_allow_returns_true_when_editor_role_is_allowed(): void {
        update_option( WPSP_SETTINGS_NAME, json_encode( [ 'allow_user_by_role' => [ 'administrator', 'editor' ] ] ) );
        $editor_id = $this->factory->user->create( [ 'role' => 'editor' ] );
        wp_set_current_user( $editor_id );

        $result = Helper::is_user_allow();

        $this->assertTrue( $result );
    }

    // -------------------------------------------------------------------------
    // get_all_allowed_taxonomy()
    // -------------------------------------------------------------------------

    /**
     * @test
     */
    public function test_get_all_allowed_taxonomy_returns_all_when_setting_not_set(): void {
        $all    = Helper::get_all_taxonomies();
        $result = Helper::get_all_allowed_taxonomy();

        $this->assertEquals( array_values( $all ), array_values( $result ) );
    }

    /**
     * @test
     */
    public function test_get_all_allowed_taxonomy_respects_configured_value(): void {
        update_option( WPSP_SETTINGS_NAME, json_encode( [ 'allow_taxonomy_as_tags' => [ 'post_tag' ] ] ) );

        $result = Helper::get_all_allowed_taxonomy();

        $this->assertEquals( [ 'post_tag' ], $result );
    }
}
