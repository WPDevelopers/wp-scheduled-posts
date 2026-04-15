# Creating Code Entities (Methods, Variables, Parameters)

## Table of Contents

- [Naming Conventions](#naming-conventions)
- [Method Visibility](#method-visibility)
- [Static Methods](#static-methods)
- [Docblock Requirements](#docblock-requirements)
    - [Public, Protected Methods, and Hooks](#public-protected-methods-and-hooks)
    - [Private Methods and Internal Callbacks](#private-methods-and-internal-callbacks)
    - [@internal Annotation Placement](#internal-annotation-placement)
- [Hook Docblocks](#hook-docblocks)

## Naming Conventions

Use snake_case for methods, variables, and hooks (not camelCase or PascalCase).

**Examples:**

```php
// Correct
public function calculate_order_total() { }
private $order_items;
```

## Method Visibility

New class methods should be `private` by default.

**Use `protected`** only if it's clear the method will be used in derived classes.

**Use `public`** only if the method will be called from outside the class.

**Examples:**

```php
class AutoScheduler {
    // Default: private for internal helpers
    private function validate_slot( array $slot ) { }

    // Protected: for use in child classes
    protected function get_schedule_interval( string $post_type ) { }

    // Public: external API
    public function schedule_post( int $post_id ) { }
}
```

## Static Methods

Pure methods (output depends only on inputs, no external dependencies) must be `static`.

**Examples of pure methods (should be static):**

```php
// Mathematical calculations
public static function calculate_percentage( float $amount, float $percent ) {
    return $amount * ( $percent / 100 );
}

// String manipulations
public static function sanitize_social_message( string $message ) {
    return trim( wp_strip_all_tags( $message ) );
}

// Data transformations
public static function normalize_schedule_data( array $data ) {
    return array_map( 'trim', $data );
}
```

**Examples of non-pure methods (should NOT be static):**

```php
// Depends on database
public function get_scheduled_posts( string $post_type ) { }

// Depends on system time
public function is_slot_available( int $timestamp ) { }

// Uses object state
public function get_next_slot_with_buffer( int $timestamp ) {
    return $timestamp + $this->buffer_seconds;
}
```

**Exception:** Non-pure methods should not be `static` unless there's a specific architectural reason (e.g., singleton pattern, factory methods).

## Docblock Requirements

Add concise docblocks to all hooks and methods. One line is ideal.

### Public, Protected Methods, and Hooks

Must include a `@since` annotation with the current SchedulePress version number.

The `@since` annotation must be:

- The last line in the docblock
- Preceded by a blank comment line
- Use the version from the `Version` header in `wp-scheduled-posts.php`
  (e.g., if the header shows `Version: 5.2.17`, use `@since 5.2.17`)

**Good - Concise:**

```php
/**
 * Schedule a post and update its status.
 *
 * @param int $post_id The post ID.
 * @return bool True if successful.
 *
 * @since 5.2.17
 */
public function schedule_post( int $post_id ) { }

/**
 * Fires after a post is auto-scheduled.
 *
 * @param int $post_id The post ID.
 *
 * @since 5.2.17
 */
do_action( 'wpsp_post_scheduled', $post_id );
```

**Avoid - Over-explained:**

```php
/**
 * This method processes the order by validating the order data,
 * checking inventory levels, processing payment, and then updating
 * the order status to reflect the successful processing.
 *
 * @param int $order_id The unique identifier for the order that needs to be processed.
 * @return bool Returns true if the order was processed successfully, false otherwise.
 *
 * @since 9.5.0
 */
```

For hooks, aim for a single descriptive line whenever possible.

### Private Methods and Internal Callbacks

Do NOT require a `@since` annotation if they are:

- Private methods
- Internal callbacks (marked with `@internal`)

**Example:**

```php
/**
 * Internal helper to validate order items.
 *
 * @param array $items The items to validate.
 * @return bool
 */
private function validate_items( array $items ) { }
```

### @internal Annotation Placement

When an `@internal` annotation is added, it must be:

- Placed after the method description
- Placed before the arguments list
- Have a blank comment line before and after

**Example:**

```php
/**
 * Handle the wpsp_init hook.
 *
 * @internal
 *
 * @param array $args Hook arguments.
 */
public function handle_wpsp_init( array $args ) { }
```

## Hook Docblocks

For information about documenting hooks (including adding docblocks to existing hooks), see [hooks.md](hooks.md).