<?php
/**
 * Unit tests for ShareUrl::tag() (card 85020): the UTM tags added to the link of
 * a social share. The link must keep working: an existing query, a fragment and
 * tags the site owner already set all have to survive.
 *
 * @package WPScheduledPosts
 */

namespace WPSP\Tests\Unit;

use PHPUnit\Framework\TestCase;
use WPSP\Helpers\ShareUrl;

class ShareUrlTest extends TestCase {

	public function test_a_plain_permalink_gets_the_three_tags() {
		$this->assertSame(
			'https://example.com/hello/?utm_source=facebook&utm_medium=social&utm_campaign=schedulepress',
			ShareUrl::tag( 'https://example.com/hello/', ShareUrl::default_tags( 'facebook' ) )
		);
	}

	public function test_an_existing_query_is_kept_and_the_tags_follow_it() {
		$this->assertSame(
			'https://example.com/?p=12&utm_source=x&utm_medium=social&utm_campaign=schedulepress',
			ShareUrl::tag( 'https://example.com/?p=12', ShareUrl::default_tags( 'x' ) )
		);
	}

	public function test_a_fragment_stays_at_the_end() {
		$this->assertSame(
			'https://example.com/a/?utm_source=linkedin&utm_medium=social&utm_campaign=schedulepress#comments',
			ShareUrl::tag( 'https://example.com/a/#comments', ShareUrl::default_tags( 'linkedin' ) )
		);
	}

	public function test_a_link_that_already_has_a_utm_tag_is_left_alone() {
		$url = 'https://example.com/a/?utm_source=newsletter';
		$this->assertSame( $url, ShareUrl::tag( $url, ShareUrl::default_tags( 'facebook' ) ) );

		$url = 'https://example.com/a/?p=1&UTM_Campaign=spring#top';
		$this->assertSame( $url, ShareUrl::tag( $url, ShareUrl::default_tags( 'facebook' ) ) );
	}

	public function test_a_non_utm_query_key_is_not_mistaken_for_a_tag() {
		$this->assertSame(
			'https://example.com/?my_utm_x=1&utm_source=bluesky&utm_medium=social&utm_campaign=schedulepress',
			ShareUrl::tag( 'https://example.com/?my_utm_x=1', ShareUrl::default_tags( 'bluesky' ) )
		);
	}

	public function test_a_tag_with_an_empty_value_is_skipped() {
		$this->assertSame(
			'https://example.com/a/?utm_source=mastodon&utm_medium=social',
			ShareUrl::tag(
				'https://example.com/a/',
				array( 'utm_source' => 'mastodon', 'utm_medium' => 'social', 'utm_campaign' => '' )
			)
		);
	}

	public function test_no_tags_or_an_empty_url_return_the_url_unchanged() {
		$this->assertSame( 'https://example.com/a/', ShareUrl::tag( 'https://example.com/a/', array() ) );
		$this->assertSame( '', ShareUrl::tag( '', ShareUrl::default_tags( 'facebook' ) ) );
	}

	public function test_values_are_url_encoded() {
		$this->assertSame(
			'https://example.com/a/?utm_campaign=spring%20sale%20%26%20more',
			ShareUrl::tag( 'https://example.com/a/', array( 'utm_campaign' => 'spring sale & more' ) )
		);
	}

	public function test_a_trailing_question_mark_does_not_double_up() {
		$this->assertSame(
			'https://example.com/a/?utm_source=threads',
			ShareUrl::tag( 'https://example.com/a/?', array( 'utm_source' => 'threads' ) )
		);
	}
}
