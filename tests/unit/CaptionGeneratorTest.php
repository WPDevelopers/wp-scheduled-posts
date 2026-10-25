<?php
/**
 * Unit tests for the shared caption generator.
 *
 * The editor's Write With AI prompt and limit trimming moved from AICaption
 * into CaptionGenerator. The fixtures were captured from AICaption before the
 * move, so these tests fail if the editor's prompt or trimming changes at all.
 *
 * @package WPScheduledPosts
 */

namespace WPSP\Tests\Unit;

use PHPUnit\Framework\TestCase;
use WPSP\Abilities\Social\CaptionGenerator;
use WPSP\API\AICaption;

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __DIR__, 2 ) . '/' );
}

class CaptionGeneratorTest extends TestCase {

	/**
	 * Fixtures captured from the pre-extraction AICaption.
	 *
	 * @return array
	 */
	private static function fixtures() {
		return json_decode( file_get_contents( __DIR__ . '/fixtures/caption-generator.json' ), true );
	}

	/**
	 * Call a protected generator method.
	 *
	 * @param string $method Method name.
	 * @param array  $args   Arguments.
	 * @return mixed
	 */
	private static function call( $method, array $args ) {
		$ref = new \ReflectionMethod( CaptionGenerator::class, $method );
		$ref->setAccessible( true );
		return $ref->invokeArgs( new CaptionGenerator(), $args );
	}

	/**
	 * The editor's own platform list, as it passes it to the generator.
	 *
	 * @return array
	 */
	private static function platform_meta() {
		$ref  = new \ReflectionClass( AICaption::class );
		$prop = $ref->getProperty( 'platform_meta' );
		$prop->setAccessible( true );
		return $prop->getValue( $ref->newInstanceWithoutConstructor() );
	}

	public function message_cases() {
		$cases = array();
		foreach ( self::fixtures()['messages'] as $name => $case ) {
			$cases[ $name ] = array( $case );
		}
		return $cases;
	}

	/**
	 * @dataProvider message_cases
	 */
	public function test_prompt_matches_the_editor_before_the_move( $case ) {
		$this->assertSame(
			$case['expected'],
			self::call( 'build_messages', array( $case['platforms'], $case['opts'], $case['post'], self::platform_meta(), $case['limits'] ) )
		);
	}

	public function test_platform_limit_falls_back_to_the_meta_limit() {
		$opts     = array(
			'prompt'            => '',
			'auto_generate'     => false,
			'tone'              => 'professional',
			'length'            => 'auto',
			'generate_hashtags' => false,
			'include_emojis'    => false,
		);
		$post     = array( 'title' => 'T', 'excerpt' => '', 'permalink' => '' );
		$messages = self::call( 'build_messages', array( array( 'linkedin' ), $opts, $post, self::platform_meta(), array() ) );

		$this->assertStringContainsString( '- "linkedin" (LinkedIn, max 3000 characters', $messages[1]['content'] );
	}

	public function limit_cases() {
		$cases = array();
		foreach ( self::fixtures()['enforce_limit'] as $case ) {
			$cases[ $case['text'] . ' @' . $case['limit'] ] = array( $case['text'], $case['limit'], $case['expected'] );
		}
		return $cases;
	}

	/**
	 * @dataProvider limit_cases
	 */
	public function test_trimming_matches_the_editor_before_the_move( $text, $limit, $expected ) {
		$this->assertSame( $expected, self::call( 'enforce_limit', array( $text, $limit ) ) );
	}
}
