# Development Guides

Contributor guides for SchedulePress. For the architecture see [../architecture/](../architecture/); for feature specs see [../specs/](../specs/); for plugin-wide conventions and build commands see [../../CLAUDE.md](../../CLAUDE.md).

## Guides

| Guide | What it covers |
| --- | --- |
| [documentation-conventions.md](documentation-conventions.md) | House style for these docs. |
| [building-assets.md](building-assets.md) | The wp-scripts build/watch commands. |
| [centralized-design-process.md](centralized-design-process.md) | The shared/centralized design process. |

## Testing
PHPUnit suites live in `../../tests/` (`unit` — no WordPress; `integration` — boots WP). Provision the test library with `bin/install-wp-tests.sh` first, then `vendor/bin/phpunit`. See [../../CLAUDE.md](../../CLAUDE.md).
