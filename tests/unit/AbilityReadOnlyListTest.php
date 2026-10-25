<?php
/**
 * Pins which abilities are read-only.
 *
 * The MCP scope gate trusts each ability's `readonly` annotation, so a write
 * ability annotated `readonly: true` by mistake would be callable with a
 * read-only token. This test lists every ability class under
 * includes/Abilities and fails when the read-only set changes, so the change
 * shows up in review.
 *
 * Abilities added by other plugins through `schedulepress_register_abilities`
 * are not covered here.
 *
 * @package WPScheduledPosts
 */

namespace {
	// Ability constructors translate their labels; the unit suite has no WordPress.
	if ( ! function_exists( '__' ) ) {
		function __( $text, $domain = 'default' ) {
			return $text;
		}
	}
}

namespace WPSP\Tests\Unit {

	use PHPUnit\Framework\TestCase;
	use WPSP\Abilities\AbilityBase;

	if ( ! defined( 'ABSPATH' ) ) {
		define( 'ABSPATH', dirname( __DIR__, 2 ) . '/' );
	}

	class AbilityReadOnlyListTest extends TestCase {

		/**
		 * The only abilities a read-only connection may call.
		 */
		const READ_ONLY = array(
			'schedulepress/find-schedule-gaps',
			'schedulepress/get-calendar',
			'schedulepress/get-connection-status',
			'schedulepress/get-missed-schedules',
			'schedulepress/get-settings',
			'schedulepress/get-share-status',
			'schedulepress/get-social-template',
			'schedulepress/list-content-types',
			'schedulepress/list-draft-posts',
			'schedulepress/list-scheduled-posts',
			'schedulepress/list-social-profiles',
		);

		/**
		 * Every concrete ability class under includes/Abilities, instantiated.
		 *
		 * @return AbilityBase[] Keyed by ability id.
		 */
		private static function abilities() {
			$root      = dirname( __DIR__, 2 ) . '/includes/Abilities';
			$abilities = array();
			$files     = new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $root, \FilesystemIterator::SKIP_DOTS ) );

			foreach ( $files as $file ) {
				if ( 'php' !== $file->getExtension() ) {
					continue;
				}
				$relative = substr( $file->getPathname(), strlen( $root ) + 1, -4 );
				$class    = 'WPSP\\Abilities\\' . str_replace( '/', '\\', $relative );
				if ( ! class_exists( $class ) ) {
					continue; // Traits and helpers.
				}
				$ref = new \ReflectionClass( $class );
				if ( $ref->isAbstract() || ! $ref->isSubclassOf( AbilityBase::class ) ) {
					continue;
				}
				$ability                         = $ref->newInstance();
				$abilities[ $ability->get_id() ] = $ability;
			}

			ksort( $abilities );
			return $abilities;
		}

		public function test_the_scan_finds_the_abilities() {
			$ids = array_keys( self::abilities() );

			$this->assertGreaterThanOrEqual( 20, count( $ids ) );
			$this->assertContains( 'schedulepress/schedule-post', $ids );
			$this->assertContains( 'schedulepress/suggest-caption', $ids );
		}

		public function test_read_only_abilities_are_exactly_the_pinned_list() {
			$read_only = array();
			foreach ( self::abilities() as $id => $ability ) {
				$annotations = $ability->get_annotations();
				if ( ! empty( $annotations['readonly'] ) ) {
					$read_only[] = $id;
				}
			}

			$this->assertSame(
				self::READ_ONLY,
				$read_only,
				'A read-only token can call every ability in this list. Change READ_ONLY only on purpose.'
			);
		}

		public function test_every_other_ability_is_annotated_as_a_write() {
			foreach ( self::abilities() as $id => $ability ) {
				if ( in_array( $id, self::READ_ONLY, true ) ) {
					continue;
				}
				$annotations = $ability->get_annotations();
				$this->assertArrayHasKey( 'readonly', $annotations, $id );
				$this->assertFalse( $annotations['readonly'], $id );
			}
		}
	}
}
