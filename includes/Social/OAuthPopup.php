<?php

namespace WPSP\Social;

use WPSP\Helper;

/**
 * The landing strip for an authorisation that was run in a popup.
 *
 * Connecting a profile for the first time ends on the settings screen, because
 * the author still has to pick which page, board or account to add. Reconnecting
 * an existing profile has nothing left to ask, so it runs the consent screen in a
 * popup and finishes on the screen the author was already looking at.
 *
 * That only works if the popup itself never becomes a second settings screen.
 * The provider sends the browser back to this site with the authorisation code,
 * and without this class that lands on the full admin page inside the popup:
 * the author is left staring at a second copy of WordPress, and worse, the app
 * loaded there spends the one-time code that the opener is about to exchange.
 *
 * So the callback is intercepted before WordPress renders anything, and the
 * query string is handed to the window that opened it. The popup closes itself
 * and the settings screen completes the reconnect in place.
 */
class OAuthPopup
{
    /**
     * Marks a callback that belongs to a popup rather than the settings screen.
     */
    const FLAG = 'wpsp_oauth_popup';

    /**
     * Name given to the popup by the reconnect button, so the callback page can
     * tell "opened by the settings screen" from "opened by anything else".
     */
    const WINDOW_NAME = 'wpsp_reconnect';

    /**
     * Message type the settings screen listens for.
     */
    const MESSAGE_TYPE = 'wpsp_oauth_callback';

    public static function hooks()
    {
        // Early enough that nothing has been rendered yet, so the popup never
        // shows a WordPress screen at all.
        add_action('admin_init', array(__CLASS__, 'maybe_render_bridge'));
        // For a callback that arrived without the marker — an authorisation
        // started by an add-on that builds its own return URL, say — the same
        // hand-off still runs, just after the page has begun loading.
        add_action('admin_head', array(__CLASS__, 'print_fallback_script'));
    }

    /**
     * Where a provider should send the browser once the author has approved.
     *
     * The settings screen for a normal connect, the popup bridge when the
     * authorisation was started from a reconnect.
     *
     * @return string
     */
    public static function return_url()
    {
        $url = admin_url('/admin.php?page=' . WPSP_SETTINGS_SLUG);

        if (self::is_popup_request()) {
            $url = add_query_arg(self::FLAG, 1, $url);
        }

        /**
         * Filter the URL a social authorisation returns to.
         *
         * @param string $url
         * @param bool   $is_popup Whether the authorisation is running in a popup.
         */
        // Raw, not esc_url(): this is handed to the provider and to the OAuth
        // middleware as data, and esc_url() would encode the separator between
        // the two query arguments into an HTML entity.
        return esc_url_raw(apply_filters('wpsp_social_oauth_return_url', $url, self::is_popup_request()));
    }

    /**
     * Whether the authorisation being started was launched from a popup.
     *
     * @return bool
     */
    public static function is_popup_request()
    {
        if (empty($_POST['popupCallback'])) {
            return false;
        }
        $flag = sanitize_text_field(wp_unslash($_POST['popupCallback']));

        return !in_array($flag, array('0', 'false', 'undefined', ''), true);
    }

    /**
     * Hand the callback back to the settings screen and close the popup.
     *
     * Runs before any admin output, so nothing of WordPress is ever painted in
     * the popup. If the window turns out not to have an opener after all — the
     * author copied the URL into a tab, or the popup was replaced — the same
     * page falls back to the ordinary settings screen, which knows how to finish
     * a connection on its own.
     *
     * @return void
     */
    public static function maybe_render_bridge()
    {
        if (empty($_GET[self::FLAG])) {
            return;
        }

        if (!is_user_logged_in() || !Helper::is_user_allow()) {
            return;
        }

        $query = wp_unslash($_GET);
        unset($query[self::FLAG]);
        $search   = $query ? '?' . http_build_query($query) : '';
        $fallback = admin_url('admin.php') . $search;

        nocache_headers();

        // Deliberately not an admin screen: no styles, no scripts, nothing that
        // could run the connect flow a second time in here.
        ?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php esc_html_e('Finishing authorisation…', 'wp-scheduled-posts'); ?></title>
</head>
<body style="font: 14px -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; padding: 32px; text-align: center; color: #1e1e1e;">
    <p><?php esc_html_e('Finishing authorisation…', 'wp-scheduled-posts'); ?></p>
    <script>
        (function () {
            var search = <?php echo wp_json_encode($search); ?>;
            var fallback = <?php echo wp_json_encode($fallback); ?>;
            try {
                if (window.opener && !window.opener.closed) {
                    // Same origin as the opener by construction, so the message
                    // is addressed to this site and nothing wider.
                    window.opener.postMessage({
                        type: <?php echo wp_json_encode(self::MESSAGE_TYPE); ?>,
                        search: search
                    }, window.location.origin);
                    window.close();
                    return;
                }
            } catch (e) {}
            // No opener to hand this to — finish the old way, on the settings screen.
            window.location.replace(fallback);
        })();
    </script>
</body>
</html>
        <?php
        exit;
    }

    /**
     * Hand the callback back from a settings screen that is itself the popup.
     *
     * Only reached when the return URL carried no marker, which happens for an
     * authorisation started somewhere that builds its own return URL. Printed in
     * the head so it runs before the settings app boots and before the one-time
     * code can be spent here.
     *
     * @return void
     */
    public static function print_fallback_script()
    {
        if (empty($_GET['page']) || $_GET['page'] !== WPSP_SETTINGS_SLUG) {
            return;
        }
        if (empty($_GET['action']) || $_GET['action'] !== 'wpsp_social_add_social_profile') {
            return;
        }
        ?>
<script>
    (function () {
        try {
            if (window.opener && !window.opener.closed && window.name === <?php echo wp_json_encode(self::WINDOW_NAME); ?>) {
                // Read by the settings app, which must not exchange a code the
                // window that opened this one is already exchanging.
                window.__wpspOAuthPopupHandled = true;
                window.opener.postMessage({
                    type: <?php echo wp_json_encode(self::MESSAGE_TYPE); ?>,
                    search: window.location.search
                }, window.location.origin);
                window.close();
            }
        } catch (e) {}
    })();
</script>
        <?php
    }
}
