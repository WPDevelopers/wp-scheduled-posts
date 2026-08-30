<?php
/**
 * Minimal WordPress function stubs for the `unit` suite.
 *
 * Only what the units under test actually call. Every stub is guarded with
 * function_exists() so this file is inert if a real WordPress ever loads first.
 *
 * Post meta lives in a simple in-memory store that tests seed directly through
 * WPSP\Tests\Stubs\MetaStore.
 *
 * @package WPScheduledPosts
 */

namespace WPSP\Tests\Stubs {

/**
 * In-memory stand-in for the post meta table.
 */
class MetaStore {

	/** @var array<int,array<string,mixed>> */
	private static $meta = array();

	public static function reset() {
		self::$meta = array();
	}

	/**
	 * @param int    $post_id
	 * @param string $key
	 * @param mixed  $value Stored exactly as given, so tests can plant a raw
	 *                      string the way a corrupt database row would hold one.
	 */
	public static function set( $post_id, $key, $value ) {
		self::$meta[ $post_id ][ $key ] = $value;
	}

	/**
	 * @param int    $post_id
	 * @param string $key
	 * @return mixed Empty string when absent, matching get_post_meta( ..., true ).
	 */
	public static function get( $post_id, $key ) {
		return isset( self::$meta[ $post_id ][ $key ] ) ? self::$meta[ $post_id ][ $key ] : '';
	}
}

}

namespace {

	if ( ! function_exists( 'get_post_meta' ) ) {
		/**
		 * @param int    $post_id
		 * @param string $key
		 * @param bool   $single
		 * @return mixed
		 */
		function get_post_meta( $post_id, $key = '', $single = false ) {
			$value = \WPSP\Tests\Stubs\MetaStore::get( $post_id, $key );
			return $single ? $value : ( '' === $value ? array() : array( $value ) );
		}
	}

	if ( ! function_exists( 'is_serialized' ) ) {
		/**
		 * Copy of the WordPress implementation, so the unit suite exercises the
		 * same acceptance rule the plugin sees in production.
		 *
		 * @param mixed $data
		 * @param bool  $strict
		 * @return bool
		 */
		function is_serialized( $data, $strict = true ) {
			if ( ! is_string( $data ) ) {
				return false;
			}
			$data = trim( $data );
			if ( 'N;' === $data ) {
				return true;
			}
			if ( strlen( $data ) < 4 ) {
				return false;
			}
			if ( ':' !== $data[1] ) {
				return false;
			}
			if ( $strict ) {
				$lastc = substr( $data, -1 );
				if ( ';' !== $lastc && '}' !== $lastc ) {
					return false;
				}
			} else {
				$semicolon = strpos( $data, ';' );
				$brace     = strpos( $data, '}' );
				if ( false === $semicolon && false === $brace ) {
					return false;
				}
				if ( false !== $semicolon && $semicolon < 3 ) {
					return false;
				}
				if ( false !== $brace && $brace < 4 ) {
					return false;
				}
			}
			$token = $data[0];
			switch ( $token ) {
				case 's':
					if ( $strict ) {
						if ( '"' !== substr( $data, -2, 1 ) ) {
							return false;
						}
					} elseif ( false === strpos( $data, '"' ) ) {
						return false;
					}
					// break intentionally omitted, matching core.
				case 'a':
				case 'O':
				case 'E':
					return (bool) preg_match( "/^{$token}:[0-9]+:/s", $data );
				case 'b':
				case 'i':
				case 'd':
					$end = $strict ? '$' : '';
					return (bool) preg_match( "/^{$token}:[0-9.E+-]+;$end/", $data );
			}
			return false;
		}
	}

	if ( ! function_exists( 'sanitize_text_field' ) ) {
		/**
		 * @param string $str
		 * @return string
		 */
		function sanitize_text_field( $str ) {
			$filtered = strip_tags( (string) $str );
			$filtered = preg_replace( '/[\r\n\t ]+/', ' ', $filtered );
			return trim( $filtered );
		}
	}

	if ( ! function_exists( '__' ) ) {
		/**
		 * @param string $text
		 * @param string $domain
		 * @return string
		 */
		function __( $text, $domain = 'default' ) {
			return $text;
		}
	}
}

namespace WPSP\Tests\Stubs {

	/**
	 * In-memory stand-in for the options table.
	 *
	 * update_option() is modelled faithfully, including the part that matters
	 * here: it returns false when the stored value has not changed, so a caller
	 * cannot tell "nothing to do" from "the write failed" by the return alone.
	 */
	class OptionStore {

		/** @var array<string,mixed> */
		private static $options = array();

		/** @var bool When true, every write is reported as failed. */
		public static $failWrites = false;

		public static function reset() {
			self::$options    = array();
			self::$failWrites = false;
		}

		public static function get( $name ) {
			return array_key_exists( $name, self::$options ) ? self::$options[ $name ] : false;
		}

		/** Seed a value without going through the update semantics. */
		public static function seed( $name, $value ) {
			self::$options[ $name ] = $value;
		}

		public static function update( $name, $value ) {
			if ( self::$failWrites ) {
				return false;
			}
			if ( array_key_exists( $name, self::$options ) && self::$options[ $name ] === $value ) {
				return false; // Unchanged, exactly as WordPress reports it.
			}
			self::$options[ $name ] = $value;
			return true;
		}
	}
}

namespace {

	if ( ! class_exists( 'WP_Error' ) ) {
		class WP_Error {

			/** @var array<string,string[]> */
			protected $errors = array();

			/** @var array<string,mixed> */
			protected $error_data = array();

			public function __construct( $code = '', $message = '', $data = '' ) {
				if ( '' === $code ) {
					return;
				}
				$this->errors[ $code ][] = $message;
				if ( '' !== $data ) {
					$this->error_data[ $code ] = $data;
				}
			}

			public function get_error_code() {
				$codes = array_keys( $this->errors );
				return empty( $codes ) ? '' : $codes[0];
			}

			public function get_error_message( $code = '' ) {
				if ( '' === $code ) {
					$code = $this->get_error_code();
				}
				return isset( $this->errors[ $code ][0] ) ? $this->errors[ $code ][0] : '';
			}

			public function get_error_data( $code = '' ) {
				if ( '' === $code ) {
					$code = $this->get_error_code();
				}
				return isset( $this->error_data[ $code ] ) ? $this->error_data[ $code ] : null;
			}
		}
	}

	if ( ! function_exists( 'is_wp_error' ) ) {
		function is_wp_error( $thing ) {
			return $thing instanceof \WP_Error;
		}
	}

	if ( ! function_exists( 'get_option' ) ) {
		function get_option( $name, $default = false ) {
			$value = \WPSP\Tests\Stubs\OptionStore::get( $name );
			return false === $value ? $default : $value;
		}
	}

	if ( ! function_exists( 'update_option' ) ) {
		function update_option( $name, $value, $autoload = null ) {
			return \WPSP\Tests\Stubs\OptionStore::update( $name, $value );
		}
	}
}

namespace WPSP\Tests\Stubs {

	/**
	 * In-memory stand-in for the posts table, enough for the post-panel handlers.
	 */
	class PostStore {

		/** @var array<int,object> */
		private static $posts = array();

		/** @var string|null Error code to fail the next wp_update_post() with. */
		public static $failUpdateWith = null;

		/** @var bool When true, delete_post_meta() reports failure. */
		public static $failMetaDelete = false;

		/** @var string[] Actions fired, so tests can assert Pro was not notified. */
		public static $firedActions = array();

		public static function reset() {
			self::$posts          = array();
			self::$failUpdateWith = null;
			self::$failMetaDelete = false;
			self::$firedActions   = array();
			MetaStore::reset();
		}

		/**
		 * @param int    $id
		 * @param string $status
		 * @param string $date     Local post_date, 'Y-m-d H:i:s'.
		 * @param string $date_gmt GMT post_date, 'Y-m-d H:i:s'.
		 */
		public static function seed( $id, $status, $date, $date_gmt ) {
			self::$posts[ $id ] = (object) array(
				'ID'            => $id,
				'post_status'   => $status,
				'post_date'     => $date,
				'post_date_gmt' => $date_gmt,
			);
		}

		public static function get( $id ) {
			return isset( self::$posts[ $id ] ) ? self::$posts[ $id ] : null;
		}

		public static function update( array $args ) {
			$id = isset( $args['ID'] ) ? (int) $args['ID'] : 0;
			if ( ! isset( self::$posts[ $id ] ) ) {
				return new \WP_Error( 'invalid_post', 'Invalid post ID.' );
			}
			if ( self::$failUpdateWith ) {
				return new \WP_Error( self::$failUpdateWith, 'Simulated update failure.' );
			}
			foreach ( array( 'post_status', 'post_date', 'post_date_gmt' ) as $field ) {
				if ( isset( $args[ $field ] ) ) {
					self::$posts[ $id ]->{$field} = $args[ $field ];
				}
			}
			return $id;
		}
	}

	/**
	 * Minimal WP_REST_Request stand-in: the handlers only read params.
	 */
	class FakeRequest {

		/** @var array<string,mixed> */
		private $params;

		public function __construct( array $params = array() ) {
			$this->params = $params;
		}

		public function get_param( $key ) {
			return array_key_exists( $key, $this->params ) ? $this->params[ $key ] : null;
		}
	}
}

namespace {

	if ( ! class_exists( 'WP_REST_Response' ) ) {
		class WP_REST_Response {

			public $data;
			public $status;

			public function __construct( $data = null, $status = 200 ) {
				$this->data   = $data;
				$this->status = $status;
			}

			public function get_data() {
				return $this->data;
			}

			public function get_status() {
				return $this->status;
			}
		}
	}

	if ( ! class_exists( 'WP_REST_Request' ) ) {
		class_alias( \WPSP\Tests\Stubs\FakeRequest::class, 'WP_REST_Request' );
	}

	if ( ! function_exists( 'get_post' ) ) {
		function get_post( $id = null ) {
			return \WPSP\Tests\Stubs\PostStore::get( (int) $id );
		}
	}

	if ( ! function_exists( 'get_post_status' ) ) {
		function get_post_status( $id = null ) {
			$post = \WPSP\Tests\Stubs\PostStore::get( (int) $id );
			return $post ? $post->post_status : false;
		}
	}

	if ( ! function_exists( 'wp_update_post' ) ) {
		function wp_update_post( $args = array(), $wp_error = false ) {
			$result = \WPSP\Tests\Stubs\PostStore::update( (array) $args );
			if ( is_wp_error( $result ) && ! $wp_error ) {
				return 0;
			}
			return $result;
		}
	}

	if ( ! function_exists( 'update_post_meta' ) ) {
		function update_post_meta( $post_id, $key, $value ) {
			\WPSP\Tests\Stubs\MetaStore::set( (int) $post_id, $key, $value );
			return true;
		}
	}

	if ( ! function_exists( 'delete_post_meta' ) ) {
		function delete_post_meta( $post_id, $key ) {
			if ( \WPSP\Tests\Stubs\PostStore::$failMetaDelete ) {
				return false;
			}
			\WPSP\Tests\Stubs\MetaStore::set( (int) $post_id, $key, '' );
			return true;
		}
	}

	if ( ! function_exists( 'current_time' ) ) {
		function current_time( $type = 'mysql', $gmt = 0 ) {
			return gmdate( 'Y-m-d H:i:s' );
		}
	}

	if ( ! function_exists( 'do_action' ) ) {
		function do_action( $hook, ...$args ) {
			\WPSP\Tests\Stubs\PostStore::$firedActions[] = $hook;
		}
	}

	if ( ! function_exists( 'add_filter' ) ) {
		function add_filter( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
			return true;
		}
	}

	if ( ! function_exists( 'remove_filter' ) ) {
		function remove_filter( $hook, $callback, $priority = 10 ) {
			return true;
		}
	}

	if ( ! function_exists( 'add_action' ) ) {
		function add_action( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
			return true;
		}
	}
}
