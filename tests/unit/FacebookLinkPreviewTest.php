<?php
/**
 * Unit tests for FacebookLinkPreview (card 85075): a Facebook share whose link
 * carries UTM tags showed only the domain instead of the preview card. Before
 * posting, Facebook is asked to read the link; if it cannot, the share uses the
 * clean permalink.
 *
 * @package WPScheduledPosts
 */

namespace WPSP\Tests\Unit;

use PHPUnit\Framework\TestCase;
use WPSP\Helpers\FacebookLinkPreview;
use WPSP\Tests\Stubs\HookStore;
use WPSP\Tests\Stubs\HttpStore;

class FacebookLinkPreviewTest extends TestCase {

	const PERMALINK = 'https://example.com/hello/';
	const TAGGED    = 'https://example.com/hello/?utm_source=facebook&utm_medium=social&utm_campaign=schedulepress';

	protected function setUp(): void {
		parent::setUp();
		HttpStore::reset();
		HookStore::reset();
		FacebookLinkPreview::reset();
	}

	private function read( $url ) {
		return HttpStore::json( 200, array( 'url' => $url, 'title' => 'Hello world', 'id' => '123' ) );
	}

	private function failed() {
		return HttpStore::json( 400, array( 'error' => array( 'message' => 'Could not fetch' ) ) );
	}

	/** @return string[] The URLs Facebook was asked to scrape, in order. */
	private function scraped_urls() {
		return array_map(
			function ( $body ) {
				return $body['id'];
			},
			HttpStore::$bodies
		);
	}

	public function test_both_the_clean_and_the_tagged_link_are_scraped_before_a_link_share() {
		HttpStore::on( FacebookLinkPreview::GRAPH_URL, array( $this->read( self::PERMALINK ), $this->read( self::TAGGED ) ) );

		$data = FacebookLinkPreview::prepare( 'app', 'secret', array( 'link' => self::TAGGED ), self::TAGGED, self::PERMALINK );

		$this->assertSame( array( self::PERMALINK, self::TAGGED ), $this->scraped_urls() );
		$this->assertSame( array( 'link' => self::TAGGED ), $data, 'The tagged link is kept when Facebook can read it.' );
	}

	public function test_the_scrape_is_a_post_with_the_token_in_the_body_not_the_url() {
		HttpStore::on( FacebookLinkPreview::GRAPH_URL, array( $this->read( self::TAGGED ) ) );

		FacebookLinkPreview::scrape( 'app', 'secret', self::TAGGED );

		$this->assertSame( array( FacebookLinkPreview::GRAPH_URL ), HttpStore::$requests );
		$this->assertSame(
			array( 'id' => self::TAGGED, 'scrape' => 'true', 'access_token' => 'app|secret' ),
			HttpStore::$bodies[0]
		);
	}

	public function test_the_share_falls_back_to_the_clean_link_when_facebook_cannot_read_the_tagged_one() {
		HttpStore::on( FacebookLinkPreview::GRAPH_URL, array( $this->read( self::PERMALINK ), $this->failed() ) );

		$data = FacebookLinkPreview::prepare(
			'app',
			'secret',
			array( 'message' => "Hello world\n" . self::TAGGED, 'link' => self::TAGGED ),
			self::TAGGED,
			self::PERMALINK
		);

		$this->assertSame(
			array( 'message' => "Hello world\n" . self::PERMALINK, 'link' => self::PERMALINK ),
			$data
		);
	}

	public function test_the_fallback_can_be_switched_off() {
		add_filter(
			'wpsp_facebook_share_clean_link_fallback',
			function () {
				return false;
			}
		);
		HttpStore::on( FacebookLinkPreview::GRAPH_URL, array( $this->failed(), $this->failed() ) );

		$data = FacebookLinkPreview::prepare( 'app', 'secret', array( 'link' => self::TAGGED ), self::TAGGED, self::PERMALINK );

		$this->assertSame( array( 'link' => self::TAGGED ), $data );
	}

	public function test_without_utm_tags_the_permalink_is_scraped_once() {
		HttpStore::on( FacebookLinkPreview::GRAPH_URL, array( $this->read( self::PERMALINK ) ) );

		$data = FacebookLinkPreview::prepare( 'app', 'secret', array( 'link' => self::PERMALINK ), self::PERMALINK, self::PERMALINK );

		$this->assertSame( array( self::PERMALINK ), $this->scraped_urls() );
		$this->assertSame( array( 'link' => self::PERMALINK ), $data );
	}

	public function test_a_status_with_the_link_in_its_text_is_scraped_and_can_fall_back() {
		HttpStore::on( FacebookLinkPreview::GRAPH_URL, array( $this->read( self::PERMALINK ), $this->failed() ) );

		$data = FacebookLinkPreview::prepare( 'app', 'secret', array( 'message' => 'Read: ' . self::TAGGED ), self::TAGGED, self::PERMALINK );

		$this->assertSame( array( self::PERMALINK, self::TAGGED ), $this->scraped_urls() );
		$this->assertSame( array( 'message' => 'Read: ' . self::PERMALINK ), $data );
	}

	public function test_a_status_without_a_link_is_not_scraped() {
		$data = FacebookLinkPreview::prepare( 'app', 'secret', array( 'message' => 'Just text' ), self::TAGGED, self::PERMALINK );

		$this->assertSame( array(), HttpStore::$requests );
		$this->assertSame( array( 'message' => 'Just text' ), $data );
	}

	public function test_a_url_is_scraped_once_per_request_across_profiles() {
		HttpStore::on( FacebookLinkPreview::GRAPH_URL, array( $this->read( self::PERMALINK ), $this->read( self::TAGGED ) ) );

		FacebookLinkPreview::prepare( 'app', 'secret', array( 'link' => self::TAGGED ), self::TAGGED, self::PERMALINK );
		$data = FacebookLinkPreview::prepare( 'app2', 'secret2', array( 'link' => self::TAGGED ), self::TAGGED, self::PERMALINK );

		$this->assertCount( 2, HttpStore::$requests, 'The second profile reuses the first scrape.' );
		$this->assertSame( array( 'link' => self::TAGGED ), $data );
	}

	public function test_missing_app_credentials_skip_the_scrape() {
		$this->assertFalse( FacebookLinkPreview::scrape( '', 'secret', self::TAGGED ) );
		$this->assertFalse( FacebookLinkPreview::scrape( 'app', '', self::TAGGED ) );
		$this->assertSame( array(), HttpStore::$requests );
	}

	/**
	 * @dataProvider responses
	 */
	public function test_only_a_page_facebook_read_counts_as_readable( $response, $expected ) {
		$this->assertSame( $expected, FacebookLinkPreview::is_readable_response( $response ) );
	}

	public function responses() {
		return array(
			'read, with a title'      => array( HttpStore::json( 200, array( 'url' => 'u', 'title' => 'T' ) ), true ),
			'200 without a title'     => array( HttpStore::json( 200, array( 'url' => 'u', 'id' => '1' ) ), false ),
			'200 with a blank title'  => array( HttpStore::json( 200, array( 'url' => 'u', 'title' => '  ' ) ), false ),
			'200 carrying an error'   => array( HttpStore::json( 200, array( 'error' => array( 'message' => 'x' ) ) ), false ),
			'HTTP error'              => array( HttpStore::json( 500, array( 'title' => 'T' ) ), false ),
			'body that is not JSON'   => array( array( 'response' => array( 'code' => 200 ), 'body' => '<html>' ), false ),
		);
	}
}
