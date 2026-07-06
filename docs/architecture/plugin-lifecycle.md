# Plugin Lifecycle

> **Status:** stub — outline only.

How SchedulePress boots and manages versions.

## Bootstrap chain
_TODO — [../../wp-scheduled-posts.php](../../wp-scheduled-posts.php) checks PHP ≥ 7.2, loads `vendor/autoload.php`, calls `WPSP_Start()` → `WPSP::init()` (static `$instance`, not `new`). Constructor runs `define_constants()`, Pro-compatibility checks, instantiates `WPSP\Installer`, wires subsystems on `init` / `wp_loaded`._

## Constants
_TODO — `WPSP_VERSION`, `WPSP_PLUGIN_SLUG` (`wp-scheduled-posts`), `WPSP_SETTINGS_SLUG` (`schedulepress`), `WPSP_SETTINGS_NAME` (`wpsp_settings_v5`), `WPSP_SETTINGS_NAME_OLD` (`wpsp_settings`). Bump `WPSP_VERSION` in `define_constants()` and `package.json` together._

## Activation & migration
_TODO — [../../includes/Installer.php](../../includes/Installer.php) (activation) + [../../includes/Migration.php](../../includes/Migration.php) (old→new settings key). See [settings-and-data.md](settings-and-data.md)._

## Source
- [../../wp-scheduled-posts.php](../../wp-scheduled-posts.php)
- [../../includes/Installer.php](../../includes/Installer.php)
- [../../includes/Migration.php](../../includes/Migration.php)
