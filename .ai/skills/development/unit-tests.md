# Unit Testing Conventions

## Table of Contents

- [Complete Test File Template](#complete-test-file-template)
- [Test File Naming and Location](#test-file-naming-and-location)
- [System Under Test Variable](#system-under-test-variable)
- [Test Method Documentation](#test-method-documentation)
- [Comments in Tests](#comments-in-tests)
- [Test Configuration](#test-configuration)
- [Example: Schedule Time Calculator Tests](#example-schedule-time-calculator-tests)
- [General Testing Best Practices](#general-testing-best-practices)

## Complete Test File Template

Use this template when creating new test files. It shows all conventions applied together:

```php
<?php
declare( strict_types = 1 );

namespace WPSP\Tests\Helpers;

use WPSP\Helpers\TimeCalculator;
use WP_UnitTestCase;

/**
 * Tests for the TimeCalculator class.
 */
class TimeCalculatorTest extends WP_UnitTestCase {

	/**
	 * The System Under Test.
	 *
	 * @var TimeCalculator
	 */
	private $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = new TimeCalculator();
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		parent::tearDown();
	}

	/**
	 * @testdox Should return a future timestamp when given a valid offset.
	 */
	public function test_returns_future_timestamp_for_valid_offset(): void {
		$result = $this->sut->calculate_publish_time( 3600 );

		$this->assertGreaterThan( time(), $result, 'Calculated time should be in the future' );
	}

	/**
	 * @testdox Should throw exception when offset is negative.
	 */
	public function test_throws_exception_for_negative_offset(): void {
		$this->expectException( \InvalidArgumentException::class );

		$this->sut->calculate_publish_time( -1 );
	}
}
```

### Key Elements

| Element | Requirement |
| ------- | ----------- |
| `declare( strict_types = 1 )` | Required at file start |
| Namespace | Match source location: `WPSP\Tests\{subdirectory}` |
| Base class | Extend `WP_UnitTestCase` |
| SUT variable | Use `$sut` with docblock "The System Under Test." |
| Test docblock | Use `@testdox` with sentence ending in `.` |
| Return type | Use `void` for test methods |
| Assertion messages | Include helpful context for failures |

## Test File Naming and Location

| Source | Test | Pattern |
| ------ | ---- | ------- |
| `includes/Helpers/TimeCalculator.php` | `tests/unit/Helpers/TimeCalculatorTest.php` | Append `Test` (no hyphen) |
| `includes/API/ScheduleRoute.php` | `tests/unit/API/ScheduleRouteTest.php` | Append `Test` |
| `includes/Social/Twitter.php` | `tests/unit/Social/TwitterTest.php` | Append `Test` |

- Unit tests live in `tests/unit/` mirroring the `includes/` subdirectory structure.
- Integration tests live in `tests/integration/`.
- Test class name = source class name + `Test` suffix, extends `WP_UnitTestCase`.

## System Under Test Variable

Use `$sut` with docblock "The System Under Test."

```php
/**
 * The System Under Test.
 *
 * @var TimeCalculator
 */
private $sut;
```

## Test Method Documentation

When adding or modifying a unit test method, the part of the docblock that describes the test must be prepended with `@testdox`. End the comment with `.` for compliance with linting rules.

**Example:**

```php
/**
 * @testdox Should return true when post is scheduled.
 */
public function test_returns_true_for_scheduled_post() {
    // ...
}

/**
 * @testdox Should throw exception when post ID is invalid.
 */
public function test_throws_exception_for_invalid_post_id() {
    // ...
}
```

## Comments in Tests

**Avoid over-commenting tests.** Test names and assertion messages should explain intent.

**Good - Self-explanatory:**

```php
/**
 * @testdox Should return true when post status is future.
 */
public function test_returns_true_for_future_posts() {
    $post_id = $this->factory->post->create( array( 'post_status' => 'future' ) );

    $result = $this->sut->is_scheduled( $post_id );

    $this->assertTrue( $result, 'Future posts should be considered scheduled' );
}
```

**Avoid - Over-commented:**

```php
/**
 * @testdox Should return true when post status is future.
 */
public function test_returns_true_for_future_posts() {
    // Create a future post
    $post_id = $this->factory->post->create( array( 'post_status' => 'future' ) );

    // Call the method we're testing
    $result = $this->sut->is_scheduled( $post_id );

    // Verify the result is true
    $this->assertTrue( $result, 'Future posts should be considered scheduled' );
}
```

**Avoid - Arrange/Act/Assert comments:**

```php
// Don't add these structural comments
// Arrange
$post_id = $this->factory->post->create( array( 'post_status' => 'future' ) );

// Act
$result = $this->sut->is_scheduled( $post_id );

// Assert
$this->assertTrue( $result );
```

Use blank lines for visual separation instead. The test structure should be self-evident.

**When comments ARE useful in tests:**

- Explaining complex test setup: `// Simulate concurrent schedule update`
- Documenting known issues: `// Workaround for WordPress core timezone bug`
- Clarifying business rules: `// Auto-scheduler requires 15 min minimum gap`

## Test Configuration

Test configuration file: `phpunit.xml`

Test suites:
- `unit` — runs `tests/unit/**/*Test.php`
- `integration` — runs `tests/integration/**/*Test.php`

Run unit tests only:
```bash
vendor/bin/phpunit --testsuite unit
```

Run a single test class:
```bash
vendor/bin/phpunit --filter TimeCalculatorTest
```

## Example: Schedule Time Calculator Tests

The following demonstrates good testing patterns for SchedulePress-specific functionality using data providers.

### Key Patterns Used

1. **Data-driven tests** using PHPUnit data providers
2. **Post status verification** for different scheduling scenarios
3. **Clear test organization** by feature area

### Example Test Structure

```php
class AutoSchedulerTest extends WP_UnitTestCase {
    /**
     * The System Under Test.
     *
     * @var AutoScheduler
     */
    private $sut;

    public function setUp(): void {
        parent::setUp();
        $this->sut = new AutoScheduler();
    }

    /**
     * @testdox Should return correct next slot for various post types.
     * @dataProvider post_type_slot_data
     */
    public function test_get_next_slot_for_post_type(
        string $post_type,
        string $expected_status
    ) {
        $post_id = $this->factory->post->create(
            array(
                'post_type'   => $post_type,
                'post_status' => 'draft',
            )
        );

        $result = $this->sut->get_next_available_slot( $post_id );

        $this->assertSame(
            $expected_status,
            $result['status'],
            "Expected status '{$expected_status}' for post type '{$post_type}'"
        );
    }

    /**
     * Data provider for post type slot tests.
     *
     * @return array
     */
    public function post_type_slot_data() {
        return array(
            'standard post' => array( 'post', 'available' ),
            'page'          => array( 'page', 'available' ),
            'custom type'   => array( 'product', 'unavailable' ),
        );
    }
}
```

## General Testing Best Practices

1. **Always run tests after making changes** to verify functionality
2. **Write descriptive test names** that explain what is being tested
3. **Use data providers** for testing multiple scenarios with the same logic
4. **Include helpful assertion messages** for debugging when tests fail
5. **Test both success and failure cases**
6. **Use `$this->factory` helpers** for creating WordPress posts, users, terms, etc.
7. **Clean up after tests** — `tearDown()` should undo any persistent state changes (options, post meta, etc.)