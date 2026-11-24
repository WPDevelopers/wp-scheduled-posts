<?php
/**
 * Control-flow exception used to survive wp_die() during an instant share.
 *
 * @package WPSP\Abilities\Social
 */

namespace WPSP\Abilities\Social;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Thrown by the temporary wp_die handler installed around the instant-share
 * routine.
 *
 * The share pipeline is written for AJAX: it finishes by calling wp_die() (or
 * wp_send_json_error(), which calls wp_die() itself). In an AJAX request that
 * is correct — the response is complete. Inside an MCP tool call it would kill
 * the PHP process mid-JSON-RPC, so the client would see a truncated response
 * instead of a result, even though the share itself had already succeeded.
 *
 * Converting that terminal wp_die() into an exception lets the share run
 * unmodified while control returns to the ability, which then reports what
 * happened. The message wp_die() was called with is preserved so a genuine
 * failure ("Invalid nonce", "unauthorized") still reaches the caller.
 */
class ShareHalt extends \Exception {
}
