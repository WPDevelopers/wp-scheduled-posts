# Plugin Lifecycle

How SchedulePress boots, wires its subsystems, and manages versions. For the subsystems themselves see [subsystems.md](subsystems.md); for the settings option and its migrations see [settings-and-data.md](settings-and-data.md).

## Bootstrap chain

Everything starts in [../../wp-scheduled-posts.php](../../wp-scheduled-posts.php):

1. **PHP gate.** If `PHP_VERSION` is below `7.2`, the plugin registers the `wpsp_fail_php_version` admin notice and `return`s — nothing else loads.
2. **Autoload.** Otherwise it requires `vendor/autoload.php` (Composer PSR-4 for `WPSP\` → `includes/`, plus the Mozart-prefixed `includes/Deps/`).
3. **Boot.** It calls `WPSP_Start()`, which returns `WPSP::init()`. `init()` keeps a static `$instance` and constructs the engine only once — the engine is a **singleton**, so always use `WPSP::init()`, never `new WPSP()`.

The private `WPSP::__construct()` then:

- runs `define_constants()` (see below);
- on `admin_init`, runs the **Pro-compatibility check** (`check_pro_compatibility()`) — if an incompatible `wp-scheduled-posts-pro` version is installed it shows the `wpsp_fail_pro_version` notice, and it mirrors settings into `$GLOBALS['wpsp_settings_v5']` / `$GLOBALS['wpsp_settings']` via `set_global_settings()`;
- registers the activation/deactivation hooks and the `upgrader_process_complete` handler (both clear the plugin-update transient);
- instantiates `WPSP\Installer` immediately;
- wires subsystems on WordPress hooks: `init` → `init_plugin()` (Assets, Email, Social, API, and — only when `is_admin()` — Admin, plus text domain), `init` → `load_calendar()` (`WPSP\Admin\Calendar`), and `wp_loaded` → `run_migrator()` (`Installer::migrate()`);
- whitelists the plugin's REST namespaces for JWT auth (`jwt_auth_whitelist`).

## Constants

`define_constants()` in [../../wp-scheduled-posts.php](../../wp-scheduled-posts.php) defines every `WPSP_*` constant. The ones to know:

| Constant | Value | Purpose |
| --- | --- | --- |
| `WPSP_VERSION` | `5.3.1` | Plugin version; also stored in the `wpsp_version` option and compared during migration. |
| `WPSP_PLUGIN_SLUG` | `wp-scheduled-posts` | Text domain, REST namespace root, plugin folder. |
| `WPSP_SETTINGS_SLUG` | `schedulepress` | Settings admin-page slug (`admin.php?page=schedulepress`). |
| `WPSP_SETTINGS_NAME` | `wpsp_settings_v5` | The single option key holding all settings (JSON). |
| `WPSP_SETTINGS_NAME_OLD` | `wpsp_settings` | Previous key, migrated forward by [../../includes/Migration.php](../../includes/Migration.php). |

Also defined here: path/URI constants (`WPSP_ROOT_DIR_PATH`, `WPSP_INCLUDES_DIR_PATH`, `WPSP_ASSETS_URI`, `WPSCP_ADMIN_DIR_PATH`, …) and the social OAuth middleware endpoints / app IDs (`WPSP_SOCIAL_OAUTH2_TOKEN_MIDDLEWARE`, `WPSP_SOCIAL_OAUTH2_PINTEREST_APP_ID`, etc.) consumed by the [Social subsystem](social-architecture.md).

> **Releasing:** bump `WPSP_VERSION` in `define_constants()` **and** the `Version:` plugin header **and** [../../package.json](../../package.json) together. The version mismatch check (`get_option('wpsp_version') != WPSP_VERSION`) is what triggers update-transient cleanup and the migration run.

## Activation & deactivation

- **Activation** (`register_activation_hook` → `WPSP::activate()`): clears the plugin-update transient and sets `wpsp_do_activation_redirect`. On the next `plugins_loaded`, `WPSP\Installer::plugin_redirect()` consumes that flag and redirects to the Settings page.
- **Deactivation** (`WPSP::deactivate()`): fires the `wpsp_run_deactivate_installer` action (a hook other code — including Pro — can attach cleanup to).

## Migration

On every `wp_loaded`, `Installer::migrate()` ([../../includes/Installer.php](../../includes/Installer.php)) runs the version-gated migrations in [../../includes/Migration.php](../../includes/Migration.php):

- seeds default settings if none exist (`wpsp_save_settings_default_value`);
- `scheduled_post_social_share_meta_update` (≤ `4.0.1`), `allow_categories` (≤ `4.1.6`), `version_4_to_5` (< `5.0.0`), and `version_3_to_4` (when the legacy `wpscp_options` key is present);
- updates the stored `wpsp_version` option to `WPSP_VERSION`.

Each migration is idempotent, guarded by a one-time `wpsp_data_migration_*` option flag. See [settings-and-data.md](settings-and-data.md) for the settings-key history and schema.

## Source

- [../../wp-scheduled-posts.php](../../wp-scheduled-posts.php)
- [../../includes/Installer.php](../../includes/Installer.php)
- [../../includes/Migration.php](../../includes/Migration.php)
