<?php
/**
 * Extra WordPress stubs for CronHealthTest only.
 *
 * Kept out of wp-functions.php so other branches that edit that file do not
 * conflict with this one. Every stub is guarded in case it is added there later.
 *
 * @package WPScheduledPosts
 */

namespace WPSP\Tests\Stubs {

	class CronHealthStore {

		/** @var array<string,mixed> */
		public static $transients = array();

		/** @var int What WP_Query reports as found_posts. */
		public static $foundPosts = 0;

		/** @var array[] Arguments of every WP_Query made. */
		public static $queries = array();

		/** @var array<int,array<string,mixed>> user ID => meta. */
		public static $userMeta = array();

		/** @var object|null What get_current_screen() returns. */
		public static $screen = null;

		public static function reset() {
			self::$transients = array();
			self::$foundPosts = 0;
			self::$queries    = array();
			self::$userMeta   = array();
			self::$screen     = null;
		}
	}
}

namespace {

	use WPSP\Tests\Stubs\CronHealthStore;
	use WPSP\Tests\Stubs\CronStore;

	if ( ! class_exists( 'WP_Query' ) ) {
		class WP_Query {
			public $found_posts = 0;

			public function __construct( $args = array() ) {
				CronHealthStore::$queries[] = $args;
				$this->found_posts          = CronHealthStore::$foundPosts;
			}
		}
	}

	if ( ! function_exists( 'get_transient' ) ) {
		function get_transient( $key ) {
			return array_key_exists( $key, CronHealthStore::$transients ) ? CronHealthStore::$transients[ $key ] : false;
		}
	}

	if ( ! function_exists( 'set_transient' ) ) {
		function set_transient( $key, $value, $expiration = 0 ) {
			CronHealthStore::$transients[ $key ] = $value;
			return true;
		}
	}

	// The WordPress shape of CronStore's events: timestamp => hook => key => event, oldest first.
	if ( ! function_exists( '_get_cron_array' ) ) {
		function _get_cron_array() {
			$crons = array();
			foreach ( CronStore::$events as $event ) {
				$crons[ $event['timestamp'] ][ $event['hook'] ][ md5( serialize( $event['args'] ) ) ] = array( 'args' => $event['args'] );
			}
			ksort( $crons );
			return $crons;
		}
	}

	if ( ! function_exists( '_n' ) ) {
		function _n( $single, $plural, $number, $domain = 'default' ) {
			return 1 === (int) $number ? $single : $plural;
		}
	}

	if ( ! function_exists( 'esc_html' ) ) {
		function esc_html( $text ) {
			return htmlspecialchars( (string) $text, ENT_QUOTES );
		}
	}

	if ( ! function_exists( 'esc_html__' ) ) {
		function esc_html__( $text, $domain = 'default' ) {
			return esc_html( $text );
		}
	}

	if ( ! function_exists( 'esc_url' ) ) {
		function esc_url( $url ) {
			return (string) $url;
		}
	}

	if ( ! function_exists( 'get_current_user_id' ) ) {
		function get_current_user_id() {
			return isset( $GLOBALS['current_user']->ID ) ? (int) $GLOBALS['current_user']->ID : 0;
		}
	}

	if ( ! function_exists( 'get_user_meta' ) ) {
		function get_user_meta( $user_id, $key = '', $single = false ) {
			return isset( CronHealthStore::$userMeta[ $user_id ][ $key ] ) ? CronHealthStore::$userMeta[ $user_id ][ $key ] : '';
		}
	}

	if ( ! function_exists( 'get_current_screen' ) ) {
		function get_current_screen() {
			return CronHealthStore::$screen;
		}
	}

	// Both call forms. The array form matches the ReconnectRenewTest stub, since whichever file loads first defines it.
	if ( ! function_exists( 'add_query_arg' ) ) {
		function add_query_arg( ...$args ) {
			if ( is_array( $args[0] ) ) {
				return ( isset( $args[1] ) ? $args[1] : '' ) . '?' . http_build_query( $args[0] );
			}
			return ( isset( $args[2] ) ? $args[2] : 'admin.php' ) . '?' . http_build_query( array( $args[0] => isset( $args[1] ) ? $args[1] : '' ) );
		}
	}

	if ( ! function_exists( 'wp_nonce_url' ) ) {
		function wp_nonce_url( $url, $action = -1 ) {
			return $url . '&_wpnonce=test';
		}
	}
}
