<?php

namespace WPSP\Admin;

use WPSP\Helper;
use WPSP\Social;

/**
 * Warns when WP-Cron is not running: a Site Health test and an admin notice.
 * Read-only: it only counts what is late and never changes any schedule.
 */
class CronHealth
{
    const CACHE_KEY    = 'wpsp_cron_health';
    const LATE_AFTER   = 10 * MINUTE_IN_SECONDS;
    const DISMISS_META = 'wpsp_cron_notice_dismissed';
    const DOC_URL      = 'https://developer.wordpress.org/plugins/cron/hooking-wp-cron-into-the-system-task-scheduler/';

    public function __construct()
    {
        add_filter('site_status_tests', [$this, 'register_test']);
        add_action('admin_init', [$this, 'handle_dismiss']);
        add_action('admin_notices', [$this, 'render_notice']);
    }

    /**
     * @param bool $fresh Skip the 15 minute cache (Site Health runs on demand).
     * @return array{cron_disabled: bool, late_posts: int, late_shares: int}
     */
    public static function get_status($fresh = false)
    {
        $status = $fresh ? false : get_transient(self::CACHE_KEY);
        if (is_array($status)) {
            return $status;
        }

        $status = [
            'cron_disabled' => defined('DISABLE_WP_CRON') && DISABLE_WP_CRON,
            'late_posts'    => self::count_late_posts(),
            'late_shares'   => self::count_late_shares(),
        ];
        set_transient(self::CACHE_KEY, $status, 15 * MINUTE_IN_SECONDS);

        return $status;
    }

    public static function has_problem(array $status)
    {
        return $status['late_posts'] > 0 || $status['late_shares'] > 0;
    }

    /** Scheduled posts of our post types whose time passed a while ago. */
    private static function count_late_posts()
    {
        $post_types = Helper::get_all_allowed_post_type();
        if (empty($post_types)) {
            return 0;
        }

        $query = new \WP_Query([
            'post_type'      => $post_types,
            'post_status'    => 'future',
            'posts_per_page' => 1,
            'fields'         => 'ids',
            'date_query'     => [[
                'column' => 'post_date_gmt',
                'before' => gmdate('Y-m-d H:i:s', time() - self::LATE_AFTER),
            ]],
        ]);

        return (int) $query->found_posts;
    }

    /** Queued social shares that should have run by now. */
    private static function count_late_shares()
    {
        $hooks = (array) apply_filters('wpsp_share_event_hooks', Social::SHARE_EVENT_HOOKS, 0);
        $limit = time() - self::LATE_AFTER;
        $late  = 0;

        foreach ((array) _get_cron_array() as $timestamp => $events) {
            if ($timestamp > $limit) {
                break;
            }
            foreach (array_intersect_key((array) $events, array_flip($hooks)) as $instances) {
                $late += count($instances);
            }
        }

        return $late;
    }

    public function register_test($tests)
    {
        $tests['direct']['wpsp_wp_cron'] = [
            'label' => __('SchedulePress scheduled posts and shares run on time', 'wp-scheduled-posts'),
            'test'  => [$this, 'run_test'],
        ];

        return $tests;
    }

    public function run_test()
    {
        $status = self::get_status(true);

        $result = [
            'label'       => __('SchedulePress scheduled posts and shares run on time', 'wp-scheduled-posts'),
            'status'      => 'good',
            'badge'       => [
                'label' => __('SchedulePress', 'wp-scheduled-posts'),
                'color' => 'blue',
            ],
            'description' => '<p>' . esc_html__('Nothing scheduled with SchedulePress is waiting past its time.', 'wp-scheduled-posts') . '</p>',
            'actions'     => '',
            'test'        => 'wpsp_wp_cron',
        ];

        if (self::has_problem($status)) {
            $result['status']      = 'critical';
            $result['label']       = __('SchedulePress scheduled posts or shares are late', 'wp-scheduled-posts');
            $result['description'] = '<p>' . esc_html($this->problem_text($status)) . '</p>';
            $result['actions']     = $this->doc_link();
        } elseif ($status['cron_disabled']) {
            $result['status']      = 'recommended';
            $result['label']       = __('WP-Cron is turned off for SchedulePress', 'wp-scheduled-posts');
            $result['description'] = '<p>' . esc_html__('DISABLE_WP_CRON is on. Scheduled posts and shares only go out if a server cron job runs wp-cron.php. Nothing is late right now.', 'wp-scheduled-posts') . '</p>';
            $result['actions']     = $this->doc_link();
        }

        return $result;
    }

    public function render_notice()
    {
        if (!current_user_can('manage_options') || !$this->is_notice_screen()) {
            return;
        }
        if ((int) get_user_meta(get_current_user_id(), self::DISMISS_META, true) > time()) {
            return;
        }

        $status = self::get_status();
        if (!self::has_problem($status)) {
            return;
        }

        $dismiss_url = wp_nonce_url(add_query_arg('wpsp_dismiss_cron_notice', 1), 'wpsp_dismiss_cron_notice');

        printf(
            '<div class="notice notice-warning"><p><strong>%1$s</strong> %2$s %3$s | <a href="%4$s">%5$s</a></p></div>',
            esc_html__('SchedulePress:', 'wp-scheduled-posts'),
            esc_html($this->problem_text($status)),
            $this->doc_link(), // phpcs:ignore WordPress.Security.EscapeOutput -- built and escaped in doc_link().
            esc_url($dismiss_url),
            esc_html__('Hide for a day', 'wp-scheduled-posts')
        );
    }

    public function handle_dismiss()
    {
        if (empty($_GET['wpsp_dismiss_cron_notice']) || !current_user_can('manage_options')) {
            return;
        }
        check_admin_referer('wpsp_dismiss_cron_notice');

        update_user_meta(get_current_user_id(), self::DISMISS_META, time() + DAY_IN_SECONDS);
        wp_safe_redirect(remove_query_arg(['wpsp_dismiss_cron_notice', '_wpnonce']));
        exit;
    }

    /** The dashboard and SchedulePress's own screens only. */
    private function is_notice_screen()
    {
        $screen = get_current_screen();

        return $screen && ('dashboard' === $screen->id || false !== strpos($screen->id, WPSP_SETTINGS_SLUG));
    }

    private function problem_text(array $status)
    {
        $parts = [];
        if ($status['late_posts'] > 0) {
            /* translators: %d: number of scheduled posts */
            $parts[] = sprintf(_n('%d scheduled post is late', '%d scheduled posts are late', $status['late_posts'], 'wp-scheduled-posts'), $status['late_posts']);
        }
        if ($status['late_shares'] > 0) {
            /* translators: %d: number of queued social shares */
            $parts[] = sprintf(_n('%d social share is late', '%d social shares are late', $status['late_shares'], 'wp-scheduled-posts'), $status['late_shares']);
        }

        $text = implode(' ' . __('and', 'wp-scheduled-posts') . ' ', $parts) . '. ';
        $text .= $status['cron_disabled']
            ? __('WP-Cron is turned off here, so check that your server cron job runs wp-cron.php.', 'wp-scheduled-posts')
            : __('WP-Cron may not be running. It needs site visits, so a quiet or fully cached site can run late.', 'wp-scheduled-posts');

        return $text;
    }

    private function doc_link()
    {
        return sprintf(
            '<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>',
            esc_url(self::DOC_URL),
            esc_html__('How to run WP-Cron from the server', 'wp-scheduled-posts')
        );
    }
}
