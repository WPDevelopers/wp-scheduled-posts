<?php
/**
 * Unit tests for WPSP\Email
 *
 * @package WPSP\Tests\Unit
 */

namespace WPSP\Tests\Unit;

use WP_UnitTestCase;
use WPSP\Email;

/**
 * Tests for the Email notification class.
 *
 * Covers hook registration and notification suppression logic
 * without hitting external mail services.
 */
class EmailTest extends WP_UnitTestCase {

    /** @var Email */
    private $email;

    protected function setUp(): void {
        parent::setUp();
        delete_option( WPSP_SETTINGS_NAME );
        delete_transient( 'wpsp_email_is_send_flag' );
        $this->email = new Email();
    }

    protected function tearDown(): void {
        delete_transient( 'wpsp_email_is_send_flag' );
        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // Constructor / Hook registration
    // -------------------------------------------------------------------------

    /**
     * @test
     */
    public function test_constructor_registers_transition_post_status_hook(): void {
        $this->assertNotFalse(
            has_action( 'transition_post_status', [ $this->email, 'transition_post_action' ] )
        );
    }

    /**
     * @test
     */
    public function test_constructor_registers_wpsp_transition_post_status_hook(): void {
        $this->assertNotFalse(
            has_action( 'wpsp_transition_post_status', [ $this->email, 'notify_status_change' ] )
        );
    }

    /**
     * @test
     */
    public function test_constructor_loads_notify_author_is_sent_review_setting(): void {
        delete_option( WPSP_SETTINGS_NAME );
        update_option( WPSP_SETTINGS_NAME, json_encode( [ 'notify_author_post_is_review' => 1 ] ) );

        $email = new Email();

        $this->assertEquals( 1, $email->notify_author_is_sent_review );
    }

    /**
     * @test
     */
    public function test_constructor_loads_notify_author_post_is_schedule_setting(): void {
        update_option( WPSP_SETTINGS_NAME, json_encode( [ 'notify_author_post_is_scheduled' => 1 ] ) );

        $email = new Email();

        $this->assertEquals( 1, $email->notify_author_post_is_schedule );
    }

    // -------------------------------------------------------------------------
    // notify_status_change() — early-return guards
    // -------------------------------------------------------------------------

    /**
     * @test
     * When old and new status are the same, the method should bail out
     * without triggering any mail actions.
     */
    public function test_notify_status_change_returns_early_when_statuses_are_equal(): void {
        $mail_sent = false;
        add_filter(
            'wp_mail',
            function () use ( &$mail_sent ) {
                $mail_sent = true;
                return false;
            }
        );

        $post = $this->factory->post->create_and_get( [ 'post_status' => 'future' ] );
        $this->email->notify_status_change( 'future', 'future', $post );

        $this->assertFalse( $mail_sent, 'No email should be sent when statuses are identical.' );
    }

    /**
     * @test
     * When the de-duplication transient is set, no email should be sent.
     */
    public function test_notify_status_change_returns_early_when_dedup_transient_is_set(): void {
        set_transient( 'wpsp_email_is_send_flag', true, 60 );

        $mail_sent = false;
        add_filter(
            'wp_mail',
            function () use ( &$mail_sent ) {
                $mail_sent = true;
                return false;
            }
        );

        $post = $this->factory->post->create_and_get( [ 'post_status' => 'publish' ] );
        $this->email->notify_status_change( 'publish', 'future', $post );

        $this->assertFalse( $mail_sent, 'No email should be sent when de-duplication transient is present.' );
    }

    /**
     * @test
     * When review notifications are disabled, no email should fire on pending status.
     */
    public function test_notify_status_change_does_not_send_review_email_when_disabled(): void {
        // Ensure review notifications are off (setting not set / 0).
        $this->email->notify_author_is_sent_review = 0;

        $mail_sent = false;
        add_filter(
            'wp_mail',
            function () use ( &$mail_sent ) {
                $mail_sent = true;
                return false;
            }
        );

        $post = $this->factory->post->create_and_get( [ 'post_status' => 'pending' ] );
        $this->email->notify_status_change( 'pending', 'draft', $post );

        $this->assertFalse( $mail_sent );
    }

    /**
     * @test
     * When rejected notifications are disabled, no email should fire on trash status.
     */
    public function test_notify_status_change_does_not_send_rejected_email_when_disabled(): void {
        $this->email->notify_author_post_is_rejected = 0;

        $mail_sent = false;
        add_filter(
            'wp_mail',
            function () use ( &$mail_sent ) {
                $mail_sent = true;
                return false;
            }
        );

        $post = $this->factory->post->create_and_get( [ 'post_status' => 'trash' ] );
        $this->email->notify_status_change( 'trash', 'publish', $post );

        $this->assertFalse( $mail_sent );
    }

    // -------------------------------------------------------------------------
    // Property initialisation defaults
    // -------------------------------------------------------------------------

    /**
     * @test
     * When no settings are stored, all notification properties should be null/falsy.
     */
    public function test_all_notification_properties_are_null_when_no_settings(): void {
        delete_option( WPSP_SETTINGS_NAME );
        $email = new Email();

        $this->assertNull( $email->notify_author_is_sent_review );
        $this->assertNull( $email->notify_author_post_is_rejected );
        $this->assertNull( $email->notify_author_post_is_schedule );
        $this->assertNull( $email->notify_author_schedule_post_is_publish );
        $this->assertNull( $email->notify_author_post_is_publish );
    }
}
