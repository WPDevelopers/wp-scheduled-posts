<?php
/**
 * Integration tests: which email a status change sends, row by row from
 * docs/specs/email-notifications.md.
 *
 * @package WPScheduledPosts
 */

namespace WPSP\Tests\Integration;

use WP_UnitTestCase;
use WPSP\Email;

class EmailStatusChangeTest extends WP_UnitTestCase {

	/**
	 * Number of mails notify_status_change() sends with only $settings on.
	 *
	 * @param string[] $settings Email properties to turn on.
	 * @param string   $old      Previous status.
	 * @param string   $new      New status.
	 * @return int
	 */
	private function mails_sent( array $settings, $old, $new ) {
		// Skip the constructor, which reads the saved settings.
		$email = ( new \ReflectionClass( Email::class ) )->newInstanceWithoutConstructor();
		foreach ( $settings as $setting ) {
			$email->$setting = 1;
		}
		$post = get_post( wp_insert_post( array( 'post_title' => 'Email status', 'post_status' => 'draft', 'post_author' => 1 ) ) );

		$mails = 0;
		$catch = function () use ( &$mails ) {
			$mails++;
			return true;
		};
		add_filter( 'pre_wp_mail', $catch );
		delete_transient( 'wpsp_email_is_send_flag' );
		$email->notify_status_change( $new, $old, $post );
		remove_filter( 'pre_wp_mail', $catch );
		delete_transient( 'wpsp_email_is_send_flag' );

		return $mails;
	}

	/**
	 * @dataProvider status_changes
	 */
	public function test_status_change_sends_the_right_email( $settings, $old, $new, $expected ) {
		$this->assertSame( $expected, $this->mails_sent( $settings, $old, $new ) );
	}

	public function status_changes() {
		$published = 'notify_author_post_is_publish';
		$scheduled = 'notify_author_schedule_post_is_publish';
		$rejected  = 'notify_author_post_is_rejected';

		return array(
			'Published: draft to publish'                       => array( array( $published ), 'draft', 'publish', 1 ),
			'Published: pending to publish'                     => array( array( $published ), 'pending', 'publish', 1 ),
			'Published: scheduled post goes live'               => array( array( $published ), 'future', 'publish', 1 ),
			'Scheduled post Published: draft to publish'        => array( array( $scheduled ), 'draft', 'publish', 0 ),
			'Scheduled post Published: scheduled post goes live' => array( array( $scheduled ), 'future', 'publish', 1 ),
			'Both on: scheduled post goes live, one mail'       => array( array( $published, $scheduled ), 'future', 'publish', 1 ),
			'Rejected: post under review is trashed'            => array( array( $rejected ), 'pending', 'trash', 1 ),
			'Rejected: published post is trashed'               => array( array( $rejected ), 'publish', 'trash', 0 ),
			'Rejected: draft is trashed'                        => array( array( $rejected ), 'draft', 'trash', 0 ),
			'Nothing on: draft to publish'                      => array( array(), 'draft', 'publish', 0 ),
		);
	}
}
