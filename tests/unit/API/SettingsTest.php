<?php
/**
 * Unit tests for WPSP\API\Settings
 *
 * @package WPSP\Tests\Unit\API
 */

namespace WPSP\Tests\Unit\API;

use WP_UnitTestCase;
use WP_REST_Request;
use WPSP\API\Settings;

/**
 * Tests for the Settings REST API class.
 *
 * Covers permission checks and route registration.
 * Full request/response cycles are in the integration suite.
 */
class SettingsTest extends WP_UnitTestCase {

    /** @var Settings */
    private $settings;

    /** @var \WP_REST_Server */
    private $server;

    protected function setUp(): void {
        parent::setUp();

        // Instantiate singleton BEFORE rest_api_init so its hooks are registered.
        $this->settings = Settings::get_instance();

        // Boot the REST server and fire rest_api_init to register routes.
        global $wp_rest_server;
        $this->server = $wp_rest_server = new \WP_REST_Server();
        do_action( 'rest_api_init' );
    }

    protected function tearDown(): void {
        global $wp_rest_server;
        $wp_rest_server = null;
        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // Singleton
    // -------------------------------------------------------------------------

    /**
     * @test
     */
    public function test_get_instance_returns_settings_object(): void {
        $instance = Settings::get_instance();

        $this->assertInstanceOf( Settings::class, $instance );
    }

    /**
     * @test
     */
    public function test_get_instance_always_returns_the_same_object(): void {
        $a = Settings::get_instance();
        $b = Settings::get_instance();

        $this->assertSame( $a, $b );
    }

    // -------------------------------------------------------------------------
    // wpsp_permissions_check()
    // -------------------------------------------------------------------------

    /**
     * @test
     * An unauthenticated (logged-out) user must be denied access.
     */
    public function test_permissions_check_returns_false_for_logged_out_user(): void {
        wp_set_current_user( 0 );

        $request = new WP_REST_Request( 'GET', '/wp-scheduled-posts/v1/settings' );
        $result  = $this->settings->wpsp_permissions_check( $request );

        $this->assertFalse( $result );
    }

    /**
     * @test
     * A subscriber (no manage_options capability) must be denied access.
     */
    public function test_permissions_check_returns_false_for_subscriber(): void {
        $subscriber_id = $this->factory->user->create( [ 'role' => 'subscriber' ] );
        wp_set_current_user( $subscriber_id );

        $request = new WP_REST_Request( 'GET', '/wp-scheduled-posts/v1/settings' );
        $result  = $this->settings->wpsp_permissions_check( $request );

        $this->assertFalse( $result );
    }

    /**
     * @test
     * An administrator must be granted access.
     */
    public function test_permissions_check_returns_true_for_administrator(): void {
        $admin_id = $this->factory->user->create( [ 'role' => 'administrator' ] );
        wp_set_current_user( $admin_id );

        $request = new WP_REST_Request( 'GET', '/wp-scheduled-posts/v1/settings' );
        $result  = $this->settings->wpsp_permissions_check( $request );

        $this->assertTrue( (bool) $result );
    }

    /**
     * @test
     * An editor must be denied access (editors lack manage_options by default).
     */
    public function test_permissions_check_returns_false_for_editor(): void {
        $editor_id = $this->factory->user->create( [ 'role' => 'editor' ] );
        wp_set_current_user( $editor_id );

        $request = new WP_REST_Request( 'GET', '/wp-scheduled-posts/v1/settings' );
        $result  = $this->settings->wpsp_permissions_check( $request );

        $this->assertFalse( $result );
    }

    // -------------------------------------------------------------------------
    // Route registration
    // -------------------------------------------------------------------------

    /**
     * @test
     */
    public function test_settings_route_is_registered(): void {
        $routes = $this->server->get_routes();

        $this->assertArrayHasKey( '/wp-scheduled-posts/v1/settings', $routes );
    }

    /**
     * @test
     * The settings route should support GET requests.
     */
    public function test_settings_route_supports_get_method(): void {
        $routes = $this->server->get_routes();

        $this->assertArrayHasKey( '/wp-scheduled-posts/v1/settings', $routes );

        $methods = array_keys( $routes['/wp-scheduled-posts/v1/settings'][0]['methods'] );
        $this->assertContains( 'GET', $methods );
    }

    // -------------------------------------------------------------------------
    // Unauthenticated request returns 401
    // -------------------------------------------------------------------------

    /**
     * @test
     */
    public function test_get_settings_returns_401_for_unauthenticated_request(): void {
        wp_set_current_user( 0 );

        $request  = new WP_REST_Request( 'GET', '/wp-scheduled-posts/v1/settings' );
        $response = $this->server->dispatch( $request );

        $this->assertEquals( 401, $response->get_status() );
    }

    /**
     * @test
     */
    public function test_get_settings_returns_403_for_subscriber(): void {
        $subscriber_id = $this->factory->user->create( [ 'role' => 'subscriber' ] );
        wp_set_current_user( $subscriber_id );

        $request  = new WP_REST_Request( 'GET', '/wp-scheduled-posts/v1/settings' );
        $response = $this->server->dispatch( $request );

        $this->assertContains( $response->get_status(), [ 401, 403 ] );
    }
}
