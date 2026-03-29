<?php
/**
 * Unit tests for WPSP\Installer
 *
 * @package WPSP\Tests\Unit
 */

namespace WPSP\Tests\Unit;

use WP_UnitTestCase;
use WPSP\Installer;

/**
 * Tests for the Installer class.
 *
 * Covers hook registration and the plugin_redirect / migrate behaviour
 * without performing actual HTTP redirects.
 */
class InstallerTest extends WP_UnitTestCase {

    /** @var Installer */
    private $installer;

    protected function setUp(): void {
        parent::setUp();
        $this->installer = new Installer();
    }

    // -------------------------------------------------------------------------
    // Constructor / Hook registration
    // -------------------------------------------------------------------------

    /**
     * @test
     */
    public function test_constructor_registers_plugins_loaded_hook(): void {
        $this->assertNotFalse(
            has_action( 'plugins_loaded', [ $this->installer, 'plugin_redirect' ] )
        );
    }

    /**
     * @test
     */
    public function test_constructor_registers_plugin_update_message_hook(): void {
        $this->assertNotFalse(
            has_action(
                'in_plugin_update_message-wp-scheduled-posts/wp-scheduled-posts.php',
                [ $this->installer, 'plugin_update_message' ]
            )
        );
    }

    // -------------------------------------------------------------------------
    // plugin_redirect()
    // -------------------------------------------------------------------------

    /**
     * @test
     * When the redirect option is not set, plugin_redirect() should be a no-op
     * and the option should remain absent.
     */
    public function test_plugin_redirect_does_nothing_when_option_not_set(): void {
        delete_option( 'wpsp_do_activation_redirect' );

        // plugin_redirect() calls wp_safe_redirect() which would exit in a real
        // request, but in the test environment there is no output buffer / exit,
        // so we simply assert the option stays absent after the call.
        $this->installer->plugin_redirect();

        $this->assertFalse( get_option( 'wpsp_do_activation_redirect' ) );
    }

    /**
     * @test
     * When the redirect option IS set, plugin_redirect() should delete it.
     */
    public function test_plugin_redirect_deletes_redirect_option(): void {
        update_option( 'wpsp_do_activation_redirect', true );

        // Override wp_safe_redirect to prevent an actual header() call in tests.
        add_filter( 'wp_redirect', '__return_false' );

        try {
            @$this->installer->plugin_redirect(); // phpcs:ignore
        } catch ( \Throwable $e ) {
            // A redirect may throw in some test environments; that is acceptable.
        }

        remove_filter( 'wp_redirect', '__return_false' );

        $this->assertFalse( get_option( 'wpsp_do_activation_redirect' ) );
    }

    // -------------------------------------------------------------------------
    // migrate()
    // -------------------------------------------------------------------------

    /**
     * @test
     * When the stored version already equals WPSP_VERSION, migrate() should
     * not downgrade or change the version option.
     */
    public function test_migrate_does_not_downgrade_version_option(): void {
        update_option( 'wpsp_version', WPSP_VERSION );

        $this->installer->migrate();

        $this->assertEquals( WPSP_VERSION, get_option( 'wpsp_version' ) );
    }

    /**
     * @test
     * When no version is stored, migrate() should write the current version.
     */
    public function test_migrate_writes_current_version_when_none_stored(): void {
        delete_option( 'wpsp_version' );

        $this->installer->migrate();

        $this->assertEquals( WPSP_VERSION, get_option( 'wpsp_version' ) );
    }

    /**
     * @test
     * migrate() should not run the v3→v4 migration when the old v3 option
     * does not exist.
     */
    public function test_migrate_skips_v3_to_v4_when_old_option_absent(): void {
        delete_option( 'wpscp_options' );
        delete_option( 'wpsp_data_migration_3_to_4' );
        update_option( 'wpsp_version', WPSP_VERSION );

        $this->installer->migrate();

        // The migration flag should remain absent because no old data existed.
        $this->assertFalse( get_option( 'wpsp_data_migration_3_to_4' ) );
    }

    /**
     * @test
     * get_social_profile_status_modified_data() with 'convert' flag sets
     * __status and forces status to false for non-first profiles.
     */
    public function test_get_social_profile_status_modified_data_converts_status(): void {
        $settings = [
            'facebook_profile_list' => [
                [ 'status' => true,  'name' => 'Profile A' ],
                [ 'status' => true,  'name' => 'Profile B' ],
            ],
        ];

        $this->installer->get_social_profile_status_modified_data( $settings, 'convert' );

        $saved = json_decode( get_option( WPSP_SETTINGS_NAME ), true );

        // First profile (index 0) should keep its status unchanged.
        $this->assertTrue( $saved['facebook_profile_list'][0]['status'] );

        // Second profile (index 1) should have status forced to false.
        $this->assertFalse( $saved['facebook_profile_list'][1]['status'] );
        $this->assertTrue( $saved['facebook_profile_list'][1]['__status'] );
    }

    /**
     * @test
     * get_social_profile_status_modified_data() with 'revert' flag restores
     * __status back to status.
     */
    public function test_get_social_profile_status_modified_data_reverts_status(): void {
        $settings = [
            'facebook_profile_list' => [
                [ 'status' => true,  '__status' => true,  'name' => 'Profile A' ],
                [ 'status' => false, '__status' => true,  'name' => 'Profile B' ],
            ],
        ];

        $this->installer->get_social_profile_status_modified_data( $settings, 'revert' );

        $saved = json_decode( get_option( WPSP_SETTINGS_NAME ), true );

        $this->assertTrue( $saved['facebook_profile_list'][1]['status'] );
    }
}
