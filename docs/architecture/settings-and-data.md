# Settings & Data Model

How SchedulePress stores its configuration and how that store is versioned and migrated. This is the *architecture* view — the full field-by-field schema, the settings-screen tabs, and per-post meta are documented in [../specs/settings-data-model.md](../specs/settings-data-model.md); read that for the complete data model. This page covers the storage mechanism, the accessors, and migration.

## The single settings option

Every plugin setting lives in **one WordPress option**, keyed by the `WPSP_SETTINGS_NAME` constant (`wpsp_settings_v5`), defined in `define_constants()` in [../../wp-scheduled-posts.php](../../wp-scheduled-posts.php). The value is a **JSON string** — a flat-ish object whose top-level keys are individual settings (`facebook_profile_status`, `social_templates`, `allow_post_types`, `manage_schedule`, the `{platform}_profile_list` arrays, and so on).

Reads and writes go through static helpers in [../../includes/Helper.php](../../includes/Helper.php):

- **`Helper::get_settings($key)`** — `json_decode(get_option(WPSP_SETTINGS_NAME, '{}'))` then returns the requested top-level property (or `null` if absent). This is the canonical way to read one setting anywhere in the codebase.
- **`Helper::wpsp_settings_v5()`** — returns the whole decoded object.

Because the option holds JSON (not a PHP-serialized array), writers must `json_encode()` before calling `update_option(WPSP_SETTINGS_NAME, …)`. The settings React app persists sections through the REST layer (see [../specs/settings-data-model.md](../specs/settings-data-model.md)); back-end code that mutates a single profile (token refresh, reconnection) decodes the JSON, edits the target entry, and re-encodes it — see `Helper::update_access_token()`, `SocialReconnection`, and `ReconnectHandler`.

## Versioning & migration

The option key is **versioned** (`_v5`). The previous key is preserved as `WPSP_SETTINGS_NAME_OLD` (`wpsp_settings`), and an even older v3 store used the plain-array option `wpscp_options`. All forward-migration lives in [../../includes/Migration.php](../../includes/Migration.php) and runs at runtime — the main plugin hooks `wp_loaded` → `run_migrator()` → `Installer::migrate()` in [../../wp-scheduled-posts.php](../../wp-scheduled-posts.php) / [../../includes/Installer.php](../../includes/Installer.php).

`Installer::migrate()` decides which migrations to run by comparing the stored `wpsp_version` option against thresholds, then delegates to `Migration`:

| Migration method | From → To | What it does |
|---|---|---|
| `Migration::version_4_to_5()` | `wpsp_settings` (v4) → `wpsp_settings_v5` | Reshapes `manage_schedule` (auto/manual) and `social_templates` into the v5 structure; renames `hide_on_elementor_editor` → `show_on_elementor_editor`. Guarded by the `wpsp_data_migration_4_to_5` flag. |
| `Migration::version_3_to_4()` | `wpscp_options` (v3, individual options) → `wpsp_settings_v5` | Pulls the old scattered options (display toggles, roles/categories, email-notify options, per-network account arrays, per-platform social templates) into the single v5 object. Guarded by `wpsp_data_migration_3_to_4`. |
| `Migration::allow_categories()` | in-place | Normalizes stored category ids to `category.{id}` form. Guarded by `wpsp_data_migration_allow_categories`. |
| `Migration::scheduled_post_social_share_meta_update()` | in-place | Backfills `_wpsp_is_{platform}_share` meta on existing `future` posts. |

Each migration is **idempotent**: it sets a one-time `wpsp_data_migration_*` option flag (or checks `wpsp_version`) so it never re-runs. `Installer::migrate()` also seeds default settings on a fresh install via the `wpsp_save_settings_default_value` action and bumps the stored `wpsp_version` to `WPSP_VERSION`.

## Social token / profile storage

Connected social accounts are **not** stored separately — they live inside the same `wpsp_settings_v5` option, one array per network under the `{platform}_profile_list` key (mapped to the `WPSCP_{PLATFORM}_OPTION_NAME` constants in [../../includes/Social.php](../../includes/Social.php)). Each entry carries the account id, display name, an on/off `status`, the app credentials, and the OAuth tokens (`access_token`, and where applicable `refresh_token` / `long_lived_access_token` / `expires_in` / `expires_at`). The companion `{platform}_profile_status` key toggles the whole network on or off.

Token lifecycle helpers operate directly on this store: `Helper::get_profiles()` / `Helper::get_profile()` read a network's list, and `Helper::get_access_token()` / `Helper::update_access_token()` refresh-and-persist an expiring token. See [social-architecture.md](social-architecture.md) for how these are wired into the sharing pipeline and the reconnection cron jobs.

## Per-post data

Feature code stores per-post state in **post meta** (not in the settings option) — share flags, share-count logs, custom banner images, opt-out flags, Pinterest board selection, and custom-template markers. The full meta-key table lives in [../specs/settings-data-model.md](../specs/settings-data-model.md#per-post-data-meta).

## Constants (for reference)

Defined in `define_constants()` in [../../wp-scheduled-posts.php](../../wp-scheduled-posts.php):

| Constant | Value |
|---|---|
| `WPSP_SETTINGS_NAME` | `wpsp_settings_v5` (the live option) |
| `WPSP_SETTINGS_NAME_OLD` | `wpsp_settings` (previous key, migrated) |
| `WPSP_VERSION` | current plugin version |
| `WPSP_PLUGIN_SLUG` | `wp-scheduled-posts` |
| `WPSP_SETTINGS_SLUG` | `schedulepress` (settings-UI page slug) |

The related v3 array option `wpscp_options` and the `wpsp_version` / `wpsp_data_migration_*` bookkeeping options are read by [Migration.php](../../includes/Migration.php) but are not part of the public settings schema.

## Free vs Pro

The Pro plugin (`wp-scheduled-posts-pro`) **extends the same option** — it adds its own top-level keys (Scheduling Hub, advanced-schedule state, license data) into `wpsp_settings_v5` rather than using a separate store, and reads/writes through the same `Helper::get_settings()` accessor. No parallel settings option exists for Pro.

## Source

- [../../includes/Helper.php](../../includes/Helper.php) — `get_settings()`, `wpsp_settings_v5()`, token/profile storage
- [../../includes/Migration.php](../../includes/Migration.php) — version migrations
- [../../includes/Installer.php](../../includes/Installer.php) — migration dispatch + default seeding
- [../specs/settings-data-model.md](../specs/settings-data-model.md) — full schema, tabs, and per-post meta
