---
name: schedulepress-php-development
description: Add or modify SchedulePress backend PHP code following project conventions. Use when creating new classes, methods, hooks, or modifying existing backend code. **MUST be invoked before writing any PHP unit tests.**
---

# SchedulePress Backend Development

This skill provides guidance for developing SchedulePress backend PHP code according to project standards and conventions.

## When to Use This Skill

**ALWAYS invoke this skill before:**

- Writing new PHP unit tests (`*Test.php` files)
- Creating new PHP classes
- Modifying existing backend PHP code
- Adding hooks or filters

## Instructions

Follow SchedulePress project conventions when adding or modifying backend PHP code:

1. **Creating new code structures**: See [file-entities.md](file-entities.md) for conventions on creating classes and organizing files (but for new unit test files see [unit-tests.md](unit-tests.md)).
2. **Naming conventions**: See [code-entities.md](code-entities.md) for naming methods, variables, and parameters
3. **Coding style**: See [coding-conventions.md](coding-conventions.md) for general coding standards and best practices
4. **Working with hooks**: See [hooks.md](hooks.md) for hook callback conventions and documentation
5. **Dependency injection**: See [dependency-injection.md](dependency-injection.md) for component initialization patterns
6. **Data integrity**: See [data-integrity.md](data-integrity.md) for ensuring data integrity when performing CRUD operations
7. **Writing tests**: See [unit-tests.md](unit-tests.md) for unit testing conventions

## Key Principles

- Always follow WordPress Coding Standards
- Use class methods instead of standalone functions
- Place new PHP classes in `includes/` subdirectories under the appropriate namespace
- Use PSR-4 autoloading with `WPSP\` as the root namespace mapped to `includes/`
- Write comprehensive unit tests for new functionality in `tests/unit/`
- Run linting and tests before committing changes

## Version Information

To determine the current SchedulePress version number for `@since` annotations:

- Read the `Version` header in `wp-scheduled-posts.php`
- Example: If the header shows `Version: 5.2.17`, use `@since 5.2.17`