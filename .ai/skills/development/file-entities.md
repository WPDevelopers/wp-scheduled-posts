# Creating File-Based Code Entities

## Fundamental Rule: No Standalone Functions

**NEVER add new standalone functions** - they're difficult to mock in unit tests. Always use class methods.

Exception: `includes/functions.php` contains plugin-wide global helpers that are loaded early. Only add to this file when a truly global, stateless utility is needed and it cannot belong to a class.

## Adding New Classes

### Default Location: `includes/`

New PHP classes go in `includes/` under the appropriate subdirectory matching their namespace:

| Subdirectory | Namespace | Purpose |
| ------------ | --------- | ------- |
| `includes/Admin/` | `WPSP\Admin\` | Admin UI, settings, metaboxes, calendar |
| `includes/API/` | `WPSP\API\` | REST API route handlers |
| `includes/Social/` | `WPSP\Social\` | Social media platform integrations |
| `includes/Helpers/` | `WPSP\Helpers\` | Reusable utility classes |
| `includes/Traits/` | `WPSP\Traits\` | Reusable PHP traits |

**Examples:**

- A new social platform handler → `includes/Social/Instagram.php` with namespace `WPSP\Social`
- A REST route for bulk scheduling → `includes/API/BulkSchedule.php` with namespace `WPSP\API`
- A utility for calculating publish times → `includes/Helpers/TimeCalculator.php` with namespace `WPSP\Helpers`

> **Note:** `src/` is reserved for the React/JavaScript frontend. Do NOT place PHP files there.

## Naming Conventions

### Class Names

- **Must be PascalCase**
- **Must follow [PSR-4 standard](https://www.php-fig.org/psr/psr-4/)**
- Adjust the name given by the user if necessary
- Root namespace for the `includes/` directory is `WPSP`

**Examples:**

```php
// User says: "create a time calculator helper"
// You create: includes/Helpers/TimeCalculator.php
namespace WPSP\Helpers;

class TimeCalculator {
    // ...
}
```

## Namespace and Import Conventions

When referencing a namespaced class:

1. Always add a `use` statement with the fully qualified class name at the beginning of the file
2. Reference the short class name throughout the code

**Good:**

```php
use WPSP\Helpers\TimeCalculator;

// Later in code:
$calculator = new TimeCalculator();
```

**Avoid:**

```php
// No use statement, using fully qualified name:
$calculator = new \WPSP\Helpers\TimeCalculator();
```