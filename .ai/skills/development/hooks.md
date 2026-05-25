# Working with Hooks

## Hook Callback Naming Convention

Name hook callback methods: `handle_{hook_name}` with `@internal` annotation.

**Examples:**

```php
/**
 * Handle the wpsp_init hook.
 *
 * @internal
 */
public function handle_wpsp_init() {
    // Initialize components
}

/**
 * Handle the wpsp_before_schedule hook.
 *
 * @internal
 *
 * @param int $post_id The post being scheduled.
 */
public function handle_wpsp_before_schedule( $post_id ) {
    // Pre-schedule logic
}
```

## Hook Naming Convention

SchedulePress hooks use the `wpsp_` prefix.

- **Actions:** `wpsp_{event}` — e.g., `wpsp_post_scheduled`, `wpsp_auto_schedule_enabled`
- **Filters:** `wpsp_{thing}_filter` or `wpsp_{thing}` — e.g., `wpsp_schedule_interval`, `wpsp_social_message`

Avoid generic WordPress hook names without the prefix. They are hard to discover and may conflict with other plugins.

## Hook Docblocks

If you modify a line that fires a hook without a docblock:

1. Add docblock with description and `@param` tags
2. Use `git log -S "hook_name"` to find when it was introduced
3. Add `@since` annotation with that version

```php
/**
 * Fires after a post has been auto-scheduled.
 *
 * @param int   $post_id   The scheduled post ID.
 * @param array $slot_data The time slot data.
 *
 * @since 5.1.0
 */
do_action( 'wpsp_post_scheduled', $post_id, $slot_data );
```

## Hook Documentation Requirements

All hooks must have docblocks that include:

- Description of when the hook fires
- `@param` tags for each parameter passed to the hook
- `@since` annotation with the version number (last line, with blank line before)
    - For new hooks: Read the `Version` header in `wp-scheduled-posts.php`
    - For existing hooks: Use `git log -S "hook_name"` to find when it was introduced

**Action hook example:**

```php
/**
 * Fires after a post is added to the schedule queue.
 *
 * @param int    $post_id  The post ID.
 * @param string $platform The social platform slug (e.g. 'twitter', 'facebook').
 *
 * @since 5.2.0
 */
do_action( 'wpsp_social_post_queued', $post_id, $platform );
```

**Filter hook example:**

```php
/**
 * Filters the social media message before it is sent.
 *
 * @param string $message The generated message text.
 * @param int    $post_id The post ID.
 *
 * @since 5.2.0
 */
$message = apply_filters( 'wpsp_social_message', $message, $post_id );
```