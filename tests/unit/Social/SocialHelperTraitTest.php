<?php
/**
 * Unit tests for WPSP\Traits\SocialHelper
 *
 * @package WPSP\Tests\Unit\Social
 */

namespace WPSP\Tests\Unit\Social;

use WP_UnitTestCase;
use WPSP\Traits\SocialHelper;

/**
 * Tests for the SocialHelper trait.
 *
 * A concrete anonymous class is constructed to exercise the trait methods
 * against real WordPress objects created via the factory.
 */
class SocialHelperTraitTest extends WP_UnitTestCase {

    /** @var object Concrete class using the SocialHelper trait */
    private $subject;

    protected function setUp(): void {
        parent::setUp();

        // Restrict allowed taxonomies to post_tag only.
        // Without this, WordPress posts get 'Uncategorized' (category taxonomy) by
        // default and getPostHasTags() would return '#Uncategorized' instead of false.
        update_option( WPSP_SETTINGS_NAME, json_encode( [ 'allow_taxonomy_as_tags' => [ 'post_tag' ] ] ) );

        $this->subject = new class {
            use SocialHelper;
        };
    }

    // -------------------------------------------------------------------------
    // getPostHasTags() — no tags / empty state
    // -------------------------------------------------------------------------

    /**
     * @test
     * A post with no tags should return false for regular platforms.
     */
    public function test_get_post_has_tags_returns_false_when_post_has_no_tags(): void {
        $post_id = $this->factory->post->create();

        $result = $this->subject->getPostHasTags( $post_id, 'twitter' );

        $this->assertFalse( $result );
    }

    /**
     * @test
     * A post with no tags should return an empty array for the medium platform.
     */
    public function test_get_post_has_tags_returns_empty_array_for_medium_when_no_tags(): void {
        $post_id = $this->factory->post->create();

        $result = $this->subject->getPostHasTags( $post_id, 'medium' );

        $this->assertIsArray( $result );
        $this->assertEmpty( $result );
    }

    // -------------------------------------------------------------------------
    // getPostHasTags() — tags present
    // -------------------------------------------------------------------------

    /**
     * @test
     * A post with tags should return a hashtag string for regular platforms.
     */
    public function test_get_post_has_tags_returns_hashtag_string(): void {
        $post_id = $this->factory->post->create();
        wp_set_post_tags( $post_id, [ 'WordPress', 'PHP' ] );

        $result = $this->subject->getPostHasTags( $post_id );

        $this->assertIsString( $result );
        $this->assertStringStartsWith( '#', $result );
    }

    /**
     * @test
     * Tags should be prefixed with a hash and separated by spaces.
     */
    public function test_get_post_has_tags_formats_each_tag_with_hash(): void {
        $post_id = $this->factory->post->create();
        wp_set_post_tags( $post_id, [ 'WordPress', 'OpenSource' ] );

        $result = $this->subject->getPostHasTags( $post_id, 'twitter' );

        $this->assertStringContainsString( '#WordPress', $result );
        $this->assertStringContainsString( '#OpenSource', $result );
    }

    /**
     * @test
     * Spaces in tag names should be removed so the hashtag has no spaces.
     */
    public function test_get_post_has_tags_removes_spaces_from_tag_names(): void {
        $post_id = $this->factory->post->create();
        wp_set_post_tags( $post_id, [ 'Open Source' ] );

        $result = $this->subject->getPostHasTags( $post_id, 'twitter' );

        $this->assertStringContainsString( '#OpenSource', $result );
        $this->assertStringNotContainsString( '# ', $result );
        $this->assertStringNotContainsString( '#Open Source', $result );
    }

    /**
     * @test
     * Hyphens in tag names should be removed.
     */
    public function test_get_post_has_tags_removes_hyphens_from_tag_names(): void {
        $post_id = $this->factory->post->create();
        wp_set_post_tags( $post_id, [ 'open-source' ] );

        $result = $this->subject->getPostHasTags( $post_id, 'twitter' );

        $this->assertStringNotContainsString( '-', $result );
        $this->assertStringContainsString( '#opensource', $result );
    }

    /**
     * @test
     * Underscores in tag names should be removed.
     */
    public function test_get_post_has_tags_removes_underscores_from_tag_names(): void {
        $post_id = $this->factory->post->create();
        wp_set_post_tags( $post_id, [ 'open_source' ] );

        $result = $this->subject->getPostHasTags( $post_id, 'twitter' );

        $this->assertStringNotContainsString( '_', $result );
    }

    /**
     * @test
     * The medium platform should receive an array of cleaned tag names (no hash).
     */
    public function test_get_post_has_tags_returns_array_for_medium_platform(): void {
        $post_id = $this->factory->post->create();
        wp_set_post_tags( $post_id, [ 'WordPress', 'PHP' ] );

        $result = $this->subject->getPostHasTags( $post_id, 'medium' );

        $this->assertIsArray( $result );
        $this->assertNotEmpty( $result );
        // Array values should not start with '#'.
        foreach ( $result as $tag ) {
            $this->assertStringStartsNotWith( '#', $tag );
        }
    }

    // -------------------------------------------------------------------------
    // getPostHasCats() — no categories
    // -------------------------------------------------------------------------

    /**
     * @test
     * A post in the default Uncategorized category returns a hashtag string.
     */
    public function test_get_post_has_cats_returns_string_for_categorised_post(): void {
        $post_id = $this->factory->post->create();

        $result = $this->subject->getPostHasCats( $post_id );

        // WordPress assigns 'Uncategorized' by default.
        $this->assertIsString( $result );
        $this->assertStringStartsWith( '#', $result );
    }

    /**
     * @test
     * Category names should be formatted as hashtags with spaces removed.
     */
    public function test_get_post_has_cats_formats_categories_as_hashtags(): void {
        $cat_id  = $this->factory->term->create( [ 'taxonomy' => 'category', 'name' => 'Open Source' ] );
        $post_id = $this->factory->post->create( [ 'post_category' => [ $cat_id ] ] );

        $result = $this->subject->getPostHasCats( $post_id );

        $this->assertStringContainsString( '#OpenSource', $result );
    }

    /**
     * @test
     * Medium platform should receive an array, not a string.
     */
    public function test_get_post_has_cats_returns_array_for_medium_platform(): void {
        $post_id = $this->factory->post->create();

        $result = $this->subject->getPostHasCats( $post_id, 'medium' );

        $this->assertIsArray( $result );
    }

    // -------------------------------------------------------------------------
    // social_share_content_template_structure()
    // -------------------------------------------------------------------------

    /**
     * @test
     * The method should return a non-empty string for a minimal template.
     */
    public function test_social_share_content_template_structure_returns_string(): void {
        $post_id = $this->factory->post->create( [ 'post_title' => 'Hello World' ] );

        $result = $this->subject->social_share_content_template_structure(
            '{title}',         // template_structure
            'Hello World',     // title
            'Post description', // desc
            'https://example.com', // post_link
            '#WordPress',      // hashTags
            280,               // limit
            null,              // url_limit
            'twitter',         // platform
            $post_id           // post_id
        );

        $this->assertIsString( $result );
        $this->assertNotEmpty( $result );
    }

    /**
     * @test
     * The title placeholder should be replaced with the actual post title.
     */
    public function test_social_share_template_replaces_title_placeholder(): void {
        $post_id = $this->factory->post->create( [ 'post_title' => 'My Test Post' ] );

        $result = $this->subject->social_share_content_template_structure(
            '{title}',
            'My Test Post',
            '',
            'https://example.com',
            '',
            500,
            null,
            'twitter',
            $post_id
        );

        $this->assertStringContainsString( 'My Test Post', $result );
    }
}
