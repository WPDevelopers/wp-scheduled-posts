<?php
/**
 * Unit tests for issue #142: since 5.3.8 the UTM tags on the shared link were
 * subtracted from the character limit, so they cut the post text (65 characters
 * on Facebook). Facebook, LinkedIn, Instagram and Google Business now count the
 * link as the plain permalink, up to the network's own maximum. X, Bluesky,
 * Threads, Mastodon, Pinterest and Medium are unchanged.
 *
 * @package WPScheduledPosts
 */

namespace WPSP\Tests\Unit;

use PHPUnit\Framework\TestCase;
use WPSP\Helpers\ShareUrl;
use WPSP\Tests\Stubs\HookStore;
use WPSP\Tests\Stubs\MetaStore;
use WPSP\Tests\Stubs\OptionStore;
use WPSP\Tests\Stubs\PermalinkStore;
use WPSP\Traits\SocialHelper;

class ShareTextUtmLengthTest extends TestCase {

	const POST_ID = 7;

	/** @var string 141 characters, the length of the permalink in the report. */
	private $permalink;

	/** @var string */
	private $excerpt;

	protected function setUp(): void {
		if ( ! defined( 'WPSP_SETTINGS_NAME' ) ) {
			define( 'WPSP_SETTINGS_NAME', 'wpsp_settings_v5' );
		}
		OptionStore::reset();
		MetaStore::reset();
		HookStore::reset();
		PermalinkStore::reset();
		$this->permalink = 'https://www.example.org/funding-opportunity/' . str_repeat( 'grant-', 14 ) . 'conservation/';
		$this->excerpt   = str_repeat( 'The trust is a charity that supports projects on rivers. ', 10 );
		PermalinkStore::$links[ self::POST_ID ] = $this->permalink;
	}

	protected function tearDown(): void {
		OptionStore::reset();
		HookStore::reset();
		PermalinkStore::reset();
	}

	private function utm( $on ) {
		OptionStore::seed( WPSP_SETTINGS_NAME, json_encode( array( 'utm_tags_on_shared_links' => $on ) ) );
	}

	private function build( $template, $desc, $link, $limit, $platform = 'facebook', $url_limit = null, $title = '', $tags = '' ) {
		$builder = new class() { use SocialHelper; };
		return $builder->social_share_content_template_structure( $template, $title, $desc, $link, $tags, $limit, $url_limit, $platform, self::POST_ID );
	}

	/** The text before the link in a "{content}{url}" message. */
	private function text_of( $message ) {
		$parts = explode( "\n\n", $message );
		return $parts[0];
	}

	public function test_the_permalink_in_the_report_is_141_characters() {
		$this->assertSame( 141, mb_strlen( $this->permalink ) );
		$this->assertSame( 65, mb_strlen( ShareUrl::for_post( self::POST_ID, 'facebook' ) ) - 141 );
	}

	public function test_client_case_utm_tags_do_not_cut_the_facebook_text() {
		$link    = ShareUrl::for_post( self::POST_ID, 'facebook' );
		$message = $this->build( '{content}{url}', $this->excerpt, $link, 260 );
		// 5.3.7: 260 - 141 = 119, cut to 116 + "..." (5.3.8 gave 51 + "...").
		$this->assertSame( 119, mb_strlen( $this->text_of( $message ) ) );
		$this->assertStringEndsWith( "\n\n" . $link, $message );
		$this->assertStringContainsString( 'utm_source=facebook', $message );
	}

	public function test_utm_on_and_off_give_the_same_text() {
		$this->utm( false );
		$off_link = ShareUrl::for_post( self::POST_ID, 'facebook' );
		$this->assertSame( $this->permalink, $off_link );
		$off = $this->build( '{content}{url}', $this->excerpt, $off_link, 260 );
		$this->assertSame( 119, mb_strlen( $this->text_of( $off ) ) );

		$this->utm( true );
		$on = $this->build( '{content}{url}', $this->excerpt, ShareUrl::for_post( self::POST_ID, 'facebook' ), 260 );
		$this->assertSame( $this->text_of( $off ), $this->text_of( $on ) );
	}

	public function test_default_template_keeps_title_content_and_tags_on_linkedin() {
		$template = '{title}{content}{url}{tags}';
		$this->utm( false );
		$off = $this->build( $template, $this->excerpt, ShareUrl::for_post( self::POST_ID, 'linkedin' ), 300, 'linkedin', null, 'A post title', '#rivers' );
		$this->utm( true );
		$link = ShareUrl::for_post( self::POST_ID, 'linkedin' );
		$on   = $this->build( $template, $this->excerpt, $link, 300, 'linkedin', null, 'A post title', '#rivers' );
		$this->assertSame( str_replace( $this->permalink, $link, $off ), $on );
		$this->assertStringContainsString( '#rivers', $on );
	}

	public function test_x_is_unchanged() {
		$link    = ShareUrl::for_post( self::POST_ID, 'x' );
		$message = $this->build( '{content}{url}', $this->excerpt, $link, 275, 'twitter' );
		// X keeps counting the whole tagged link (x_link_length), as in 5.3.8.
		$this->assertSame( 275 - \WPSP\Helper::x_link_length( $link ), \WPSP\Helper::x_strlen( $this->text_of( $message ) ) );
		$this->assertGreaterThan( 23, \WPSP\Helper::x_link_length( $link ) );
	}

	public function test_short_limit_networks_still_count_the_full_link() {
		foreach ( array( 'bluesky' => 295, 'threads' => 480, 'mastodon' => 495 ) as $platform => $limit ) {
			$link    = ShareUrl::for_post( self::POST_ID, $platform );
			$message = $this->build( '{content}{url}', $this->excerpt, $link, $limit, $platform );
			$this->assertSame( $limit - mb_strlen( $link ), mb_strlen( $this->text_of( $message ) ), $platform );
		}
	}

	public function test_a_passed_url_limit_is_used_as_before() {
		$message = $this->build( '{content}{url}', $this->excerpt, ShareUrl::for_post( self::POST_ID, 'facebook' ), 260, 'facebook', 30 );
		$this->assertSame( 230, mb_strlen( $this->text_of( $message ) ) );
	}

	public function test_a_link_that_is_not_the_posts_permalink_counts_in_full() {
		$link = 'https://other.example.net/page/?utm_source=facebook&utm_medium=social&utm_campaign=schedulepress';
		$this->assertSame( 0, ShareUrl::added_length( $link, self::POST_ID ) );
		$message = $this->build( '{content}{url}', $this->excerpt, $link, 260 );
		$this->assertSame( 260 - mb_strlen( $link ), mb_strlen( $this->text_of( $message ) ) );
	}

	public function test_a_longer_path_under_the_permalink_is_not_counted_as_tags() {
		$this->assertSame( 0, ShareUrl::added_length( $this->permalink . 'child/', self::POST_ID ) );
	}

	public function test_a_permalink_with_its_own_utm_tags_counts_in_full() {
		$own = 'https://www.example.org/a/?utm_source=newsletter';
		PermalinkStore::$links[ self::POST_ID ] = $own;
		$link = ShareUrl::for_post( self::POST_ID, 'facebook' );
		$this->assertSame( $own, $link );
		$this->assertSame( 0, ShareUrl::added_length( $link, self::POST_ID ) );
		$message = $this->build( '{content}{url}', $this->excerpt, $link, 100 );
		$this->assertSame( 100 - mb_strlen( $own ), mb_strlen( $this->text_of( $message ) ) );
	}

	public function test_added_length_is_the_tags_only_and_zero_when_off() {
		$this->assertSame( 65, ShareUrl::added_length( ShareUrl::for_post( self::POST_ID, 'facebook' ), self::POST_ID ) );
		$this->assertSame( 0, ShareUrl::added_length( '', self::POST_ID ) );
		$this->assertSame( 0, ShareUrl::added_length( ShareUrl::for_post( self::POST_ID, 'facebook' ), 0 ) );
		$this->utm( false );
		$this->assertSame( 0, ShareUrl::added_length( $this->permalink . '?utm_source=facebook', self::POST_ID ) );
	}

	public function test_network_max_lengths() {
		$this->assertSame( 63206, ShareUrl::network_max_length( 'facebook' ) );
		$this->assertSame( 3000, ShareUrl::network_max_length( 'linkedin' ) );
		$this->assertSame( 2200, ShareUrl::network_max_length( 'instagram' ) );
		$this->assertSame( 1500, ShareUrl::network_max_length( 'google_business' ) );
		foreach ( array( 'twitter', 'x', 'bluesky', 'threads', 'mastodon', 'pinterest', 'medium', '' ) as $platform ) {
			$this->assertSame( 0, ShareUrl::network_max_length( $platform ), $platform );
		}
	}

	public function test_the_message_never_goes_over_the_network_maximum() {
		$long = str_repeat( 'Long content for the share. ', 3000 );
		foreach ( array( 'facebook' => 63206, 'linkedin' => 2990 ) as $platform => $limit ) {
			$link    = ShareUrl::for_post( self::POST_ID, $platform );
			$message = $this->build( '{content}{url}', $long, $link, $limit, $platform );
			$text    = $this->text_of( $message );
			$this->assertLessThanOrEqual( ShareUrl::network_max_length( $platform ), mb_strlen( $text ) + mb_strlen( $link ), $platform );
			// Never shorter than 5.3.8 gave.
			$this->assertGreaterThanOrEqual( $limit - mb_strlen( $link ), mb_strlen( $text ), $platform );
		}
	}

	public function test_multibyte_text_is_cut_between_whole_characters() {
		$desc = str_repeat( 'আজকের পোস্ট 🎉 今日の投稿 ', 30 );
		$this->utm( false );
		$off = $this->build( '{content}{url}', $desc, ShareUrl::for_post( self::POST_ID, 'facebook' ), 260 );
		$this->utm( true );
		$on = $this->build( '{content}{url}', $desc, ShareUrl::for_post( self::POST_ID, 'facebook' ), 260 );
		$this->assertSame( $this->text_of( $off ), $this->text_of( $on ) );
		$this->assertSame( 119, mb_strlen( $this->text_of( $on ) ) );
		$this->assertTrue( mb_check_encoding( $on, 'UTF-8' ) );
	}
}
