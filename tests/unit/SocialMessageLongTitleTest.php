<?php
/**
 * Unit tests for card 84931: when the title (or the link) alone fills the share limit, the
 * message builder cut the content with a negative length, and substr() then kept almost the
 * whole post. The content is now left out when there is no room, and normal posts are unchanged.
 *
 * Uses the WordPress stubs in tests/stubs (get_post_meta, apply_filters).
 *
 * @package WPScheduledPosts
 */

namespace WPSP\Tests\Unit;

use PHPUnit\Framework\TestCase;
use WPSP\Traits\SocialHelper;

class SocialMessageLongTitleTest extends TestCase {

	private function build( $template, $title, $desc, $link, $limit, $platform = 'facebook' ) {
		$builder = new class() { use SocialHelper; };
		return $builder->social_share_content_template_structure( $template, $title, $desc, $link, '', $limit, null, $platform, 0 );
	}

	public function test_a_title_longer_than_the_limit_leaves_the_content_out() {
		$title = str_repeat( 'A very long post title ', 28 );
		$text  = $this->build( '{title}{content}', $title, str_repeat( 'Body text. ', 100 ), '', 100 );
		$this->assertSame( 100, mb_strlen( $text ) );
		$this->assertStringNotContainsString( 'Body text', $text );
	}

	public function test_a_link_longer_than_the_limit_does_not_keep_the_whole_title() {
		$link = 'https://example.com/' . str_repeat( 'a', 120 );
		$text = $this->build( '{title}{url}', str_repeat( 'Title ', 50 ), '', $link, 100 );
		$this->assertSame( $link, $text );
	}

	public function test_a_long_title_on_x_stays_within_x_count() {
		$text = $this->build( '{title}{content}', str_repeat( '長いタイトル', 40 ), str_repeat( '本文です。', 60 ), '', 275, 'twitter' );
		$this->assertLessThanOrEqual( 275, \WPSP\Helper::x_strlen( $text ) );
		$this->assertStringNotContainsString( '本文', $text );
	}

	public function test_a_normal_post_is_cut_as_before() {
		$this->assertSame( "Hello\n\nabcdefghijab...", $this->build( '{title}{content}', 'Hello', str_repeat( 'abcdefghij', 5 ), '', 20 ) );
		$this->assertSame( "Hello\n\nabc", $this->build( '{title}{content}', 'Hello', 'abc', '', 20 ) );
	}
}
