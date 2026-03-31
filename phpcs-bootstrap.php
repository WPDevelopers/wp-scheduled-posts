<?php
/**
 * PHPCS Bootstrap - stubs WordPress core functions so PHPCS can run
 * outside of a WordPress environment without fatal errors.
 */

if ( ! function_exists( 'add_action' ) ) {
	function add_action() {}
}
if ( ! function_exists( 'add_filter' ) ) {
	function add_filter() {}
}
if ( ! function_exists( 'apply_filters' ) ) {
	function apply_filters( $tag, $value ) { return $value; }
}
if ( ! function_exists( 'do_action' ) ) {
	function do_action() {}
}
if ( ! function_exists( 'is_admin' ) ) {
	function is_admin() { return false; }
}
if ( ! function_exists( '__' ) ) {
	function __( $text, $domain = 'default' ) { return $text; }
}
if ( ! function_exists( 'esc_html__' ) ) {
	function esc_html__( $text, $domain = 'default' ) { return $text; }
}
if ( ! function_exists( 'plugin_dir_path' ) ) {
	function plugin_dir_path( $file ) { return trailingslashit( dirname( $file ) ); }
}
if ( ! function_exists( 'trailingslashit' ) ) {
	function trailingslashit( $string ) { return rtrim( $string, '/\\' ) . '/'; }
}
if ( ! function_exists( 'plugins_url' ) ) {
	function plugins_url( $path = '', $plugin = '' ) { return ''; }
}
if ( ! function_exists( 'register_activation_hook' ) ) {
	function register_activation_hook() {}
}
if ( ! function_exists( 'register_deactivation_hook' ) ) {
	function register_deactivation_hook() {}
}
