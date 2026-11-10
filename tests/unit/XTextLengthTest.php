<?php
/**
 * Unit tests for WPSP\Helper::x_strlen() / x_substr() / x_link_length() (card 84785, QA
 * follow-up): X counts CJK and emoji as 2 and a link as 23, so the Twitter message must be
 * measured that way. The helpers may count higher than X, never lower. Expected values
 * marked "X:" are twitter-text 3.1.0's weightedLength for the same text.
 *
 * Pure logic — no WordPress required, so this runs in the `unit` suite.
 *
 * @package WPScheduledPosts
 */

namespace WPSP\Tests\Unit;

use PHPUnit\Framework\TestCase;
use WPSP\Helper;

class XTextLengthTest extends TestCase {

	public function test_latin_bengali_and_arabic_count_one_per_character() {
		$this->assertSame( 11, Helper::x_strlen( 'Hello world' ) );              // X: 11
		$this->assertSame( 22, Helper::x_strlen( 'আজকের পোস্টটি সম্পর্কে' ) );     // X: 22
		$this->assertSame( 4, Helper::x_strlen( 'عربي' ) );
		$this->assertSame( 9, Helper::x_strlen( 'e.g. this' ) );                // X: 9, not a link
	}

	public function test_cjk_and_emoji_count_two() {
		$this->assertSame( 16, Helper::x_strlen( '今日の投稿の詳細' ) );            // X: 16
		$this->assertSame( 2, Helper::x_strlen( '🎉' ) );                        // X: 2
		// X counts a joined emoji as 2; counting its parts is higher, never lower.
		$this->assertGreaterThanOrEqual( 2, Helper::x_strlen( '👨‍👩‍👧' ) );
	}

	public function test_a_character_nfc_splits_counts_two() {
		$this->assertSame( 2, Helper::x_strlen( "\u{09DF}" ) );                  // X: 2 (য় becomes য + ়)
	}

	public function test_links_count_at_least_what_x_counts() {
		$this->assertGreaterThanOrEqual( 23, Helper::x_strlen( 'wp.org' ) );     // X: 23
		$this->assertGreaterThanOrEqual( 32, Helper::x_strlen( 'Read https://example.com/2026/10/a-post/ now' ) ); // X: 32
		// The post link: 23 if shorter (X: 23), its characters if longer, which also covers a host X does not shorten.
		$this->assertSame( 23, Helper::x_link_length( 'https://a.co/' ) );                         // X: 23
		$this->assertSame( 35, Helper::x_link_length( 'https://example.com/2026/10/a-post/' ) );   // X: 23
		$this->assertSame( 28, Helper::x_link_length( 'https://localhost:10028/?p=5' ) );          // X: 28
	}

	public function test_substr_fits_the_budget_by_x_count() {
		$cut = Helper::x_substr( str_repeat( '今日は', 50 ), 10 );
		$this->assertSame( '今日は今日', $cut );
		$this->assertSame( 'Hello', Helper::x_substr( 'Hello world', 5 ) );
		$this->assertSame( 'Hello world', Helper::x_substr( 'Hello world', 280 ) );
	}

	public function test_substr_keeps_emoji_and_links_whole() {
		// The joined emoji does not fit and is left out whole, not cut in half.
		$this->assertSame( 'Hi ', Helper::x_substr( 'Hi 👨‍👩‍👧', 5 ) );
		$this->assertSame( 'See ', Helper::x_substr( 'See https://example.com/a-long-path', 30 ) );
		$this->assertTrue( mb_check_encoding( Helper::x_substr( '🎉🚀😀', 3 ), 'UTF-8' ) );
	}

	public function test_a_budget_of_zero_or_less_gives_nothing() {
		$this->assertSame( '', Helper::x_substr( 'Hello', 0 ) );
		$this->assertSame( '', Helper::x_substr( 'Hello', -3 ) );
	}
}
