# Settings & Data Model

> **Status:** stub — outline only. Full schema: [../specs/settings-data-model.md](../specs/settings-data-model.md).

## The single settings option
_TODO — all settings live in one option `WPSP_SETTINGS_NAME` (`wpsp_settings_v5`), read via `WPSP\Helper::get_settings($key)` / `wpsp_settings_v5()`._

## Versioning & migration
_TODO — the older `wpsp_settings` (`WPSP_SETTINGS_NAME_OLD`) is migrated by [../../includes/Migration.php](../../includes/Migration.php)._

## Social token / profile storage
_TODO — where connected social profiles and tokens are stored (via Helper). See [social-architecture.md](social-architecture.md)._

## Source
- [../../includes/Helper.php](../../includes/Helper.php)
- [../specs/settings-data-model.md](../specs/settings-data-model.md)
