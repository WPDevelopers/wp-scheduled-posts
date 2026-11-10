<?php
/**
 * Unit tests for WPSP\Helper::mb_strlen() / mb_substr() (card 84785): social
 * messages and admin-bar titles must be measured and cut by characters, so
 * Bengali, CJK and emoji text is not broken the way byte-based strlen/substr
 * broke it.
 *
 * Pure logic — no WordPress required, so this runs in the `unit` suite.
 *
 * @package WPScheduledPosts
 */

namespace WPSP\Tests\Unit;

use PHPUnit\Framework\TestCase;
use WPSP\Helper;

class MbTruncateTest extends TestCase {

	const BENGALI = 'আজকের পোস্টটি সম্পর্কে';
	const CJK     = '今日の投稿の詳細';
	const EMOJI   = '🎉 New launch 🚀';

	public function test_length_is_counted_in_characters_not_bytes() {
		$this->assertSame( 22, Helper::mb_strlen( self::BENGALI ) );
		$this->assertSame( 8, Helper::mb_strlen( self::CJK ) );
		$this->assertSame( 14, Helper::mb_strlen( self::EMOJI ) );
		// Each of these has more bytes than characters.
		$this->assertGreaterThan( Helper::mb_strlen( self::BENGALI ), strlen( self::BENGALI ) );
	}

	public function test_substr_keeps_whole_characters() {
		$cut = Helper::mb_substr( self::BENGALI, 0, 5 );
		$this->assertSame( 'আজকের', $cut );
		$this->assertSame( 5, Helper::mb_strlen( $cut ) );
		// Valid UTF-8: nothing cut mid-character.
		$this->assertTrue( mb_check_encoding( $cut, 'UTF-8' ) );
	}

	public function test_substr_keeps_whole_cjk_and_emoji() {
		$this->assertSame( '今日', Helper::mb_substr( self::CJK, 0, 2 ) );
		$this->assertSame( '🎉', Helper::mb_substr( self::EMOJI, 0, 1 ) );
		$this->assertTrue( mb_check_encoding( Helper::mb_substr( self::EMOJI, 0, 1 ), 'UTF-8' ) );
	}

	public function test_ascii_is_unchanged() {
		$this->assertSame( strlen( 'Hello world' ), Helper::mb_strlen( 'Hello world' ) );
		$this->assertSame( substr( 'Hello world', 0, 5 ), Helper::mb_substr( 'Hello world', 0, 5 ) );
	}

	public function test_a_limit_at_or_past_the_end_returns_the_whole_string() {
		$this->assertSame( self::BENGALI, Helper::mb_substr( self::BENGALI, 0, 100 ) );
	}

	public function test_null_length_returns_to_the_end() {
		$this->assertSame( 'কের পোস্টটি সম্পর্কে', Helper::mb_substr( self::BENGALI, 2 ) );
	}
}
