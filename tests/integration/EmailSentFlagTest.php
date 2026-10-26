<?php
/**
 * Integration tests: the email dedup flag is per post and status, so one
 * email no longer blocks other posts' emails, and the same change still
 * sends once.
 *
 * @package WPScheduledPosts
 */

namespace WPSP\Tests\Integration;

use WP_UnitTestCase;
use WPSP\Email;

class EmailSentFlagTest extends WP_UnitTestCase {

	private $mails = 0;
	private $posts = array();

	/** An Email with only "Scheduled post Published" on; the constructor would read the saved settings. */
	private function email() {
		$email = ( new \ReflectionClass( Email::class ) )->newInstanceWithoutConstructor();
		$email->notify_author_schedule_post_is_publish = 1;
		return $email;
	}

	private function scheduled_post() {
		$id            = wp_insert_post( array( 'post_title' => 'Email flag', 'post_status' => 'draft', 'post_author' => 1 ) );
		$this->posts[] = $id;
		return get_post( $id );
	}

	/** Count mails while $run runs, then remove the flags it left. */
	private function count_mails( callable $run ) {
		$this->mails = 0;
		$catch       = function () {
			$this->mails++;
			return true;
		};
		add_filter( 'pre_wp_mail', $catch );
		$run();
		remove_filter( 'pre_wp_mail', $catch );
		foreach ( $this->posts as $id ) {
			delete_transient( 'wpsp_email_sent_' . $id . '_publish' );
		}
		return $this->mails;
	}

	public function test_two_posts_going_live_together_send_two_emails() {
		$email = $this->email();
		$a     = $this->scheduled_post();
		$b     = $this->scheduled_post();

		$this->assertSame( 2, $this->count_mails( function () use ( $email, $a, $b ) {
			$email->notify_status_change( 'publish', 'future', $a );
			$email->notify_status_change( 'publish', 'future', $b );
		} ) );
	}

	public function test_the_same_post_and_status_twice_sends_one_email() {
		$email = $this->email();
		$a     = $this->scheduled_post();

		$this->assertSame( 1, $this->count_mails( function () use ( $email, $a ) {
			$email->notify_status_change( 'publish', 'future', $a );
			$email->notify_status_change( 'publish', 'future', $a );
		} ) );
	}

	public function test_a_save_while_the_mail_is_sent_does_not_send_again() {
		$email = $this->email();
		$a     = $this->scheduled_post();
		// Re-enter from inside wp_mail(), as a hook that saves the post would.
		$again = function ( $r ) use ( $email, $a ) {
			static $depth = 0;
			if ( 0 === $depth++ ) {
				$email->notify_status_change( 'publish', 'future', $a );
			}
			return $r;
		};
		add_filter( 'pre_wp_mail', $again, 5 );

		$this->assertSame( 1, $this->count_mails( function () use ( $email, $a ) {
			$email->notify_status_change( 'publish', 'future', $a );
		} ) );
		remove_filter( 'pre_wp_mail', $again, 5 );
	}
}
