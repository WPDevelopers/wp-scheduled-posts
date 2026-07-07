# Architecture Overview

The big picture of how SchedulePress is assembled. This is the entry point for the [architecture/](./) section; each area below has a dedicated deep-dive.

## What SchedulePress is

SchedulePress (plugin slug `wp-scheduled-posts`, formerly *WP Scheduled Posts*) is a WordPress plugin by WPDeveloper for **automated content scheduling** and **social auto-sharing**. The Free plugin ships the editorial [Schedule Calendar](../specs/schedule-calendar.md), a [dashboard widget](../specs/dashboard-widget.md), a sitewide [admin-bar menu](../specs/admin-bar-menu.md), per-post scheduling controls, and auto-sharing to Facebook, Twitter/X, LinkedIn, Pinterest, Instagram, Medium, Threads, and Google Business Profile.

Paid capabilities (multi-account sharing, auto/manual schedulers, missed-schedule handling, republish/unpublish, and more) live in the **separate `wp-scheduled-posts-pro` plugin**. Pro is not a fork — it integrates through the same subsystems and WordPress hooks that Free exposes, and the two are version-matched at runtime (see [plugin-lifecycle.md](plugin-lifecycle.md)).

## The engine & namespace

The whole plugin is driven by a single engine class, `final class WPSP`, defined in [../../wp-scheduled-posts.php](../../wp-scheduled-posts.php). It is a **static singleton**: call `WPSP::init()` (which stores a static `$instance`), never `new WPSP()`. `WPSP_Start()` in the same file is the bootstrap function that WordPress invokes on load; it just returns `WPSP::init()`.

Code is organized in two layers:

- **Object-oriented `WPSP\` namespace → `includes/`** (PSR-4 autoloaded via Composer, see [../../composer.json](../../composer.json)). Every subsystem is a class under `WPSP\` — e.g. `WPSP\Admin`, `WPSP\Social`, `WPSP\API`, `WPSP\Helper`.
- **Procedural helpers** in [../../includes/functions.php](../../includes/functions.php), prefixed `wpsp_*` / `wpscp_*` (e.g. the admin-bar menu builder `wpscp_scheduled_post_menu`, the immediate-publish filter `wpscp_prevent_future_type`). These hook directly into WordPress and lean on `WPSP\Helper` for settings and access checks.

Third-party PHP libraries (Facebook Graph SDK, TwitterOAuth, LinkedIn, Pinterest, Guzzle) are **Mozart-prefixed** into `includes/Deps/` under the `WPSP\Deps\` namespace — never hand-edited. See [mozart-dependencies.md](mozart-dependencies.md).

## Subsystem map

The constructor of `WPSP` instantiates `WPSP\Installer` immediately, then wires the rest of the subsystems on the `init` and `wp_loaded` hooks (via `init_plugin()` and `run_migrator()`):

- **Installer / Migration** — activation redirect, upgrade messaging, and version/schema migrations.
- **Assets** (`WPSP\Assets`) — enqueues block-editor, admin, admin-bar, and Elementor scripts/styles.
- **Admin** (`WPSP\Admin`, admin-only) — menu pages, metabox, dashboard widget, admin notices, Elementor panel, and the React Settings app.
- **Social** (`WPSP\Social`) — one class per platform plus profile/reconnection handling and instant share.
- **API** (`WPSP\API`) — REST controllers.
- **Email** (`WPSP\Email`) — schedule/review notification emails.
- **Helper** (`WPSP\Helper`) — static utilities used across all of the above.

For the full breakdown of what each `includes/` subsystem owns, see **[subsystems.md](subsystems.md)**.

## Settings & data

All plugin settings live in a **single option**, `WPSP_SETTINGS_NAME` (`wpsp_settings_v5`), stored as JSON and read via `WPSP\Helper::get_settings($key)`. The engine also mirrors it into `$GLOBALS['wpsp_settings_v5']` on `admin_init`. Older keys (`wpsp_settings`, `wpscp_options`) are migrated forward by [../../includes/Migration.php](../../includes/Migration.php). See [settings-and-data.md](settings-and-data.md) and the [settings data model spec](../specs/settings-data-model.md).

## Where to go next

- [plugin-lifecycle.md](plugin-lifecycle.md) — bootstrap chain, constants, activation & migration
- [subsystems.md](subsystems.md) — the `includes/` subsystems in detail
- [social-architecture.md](social-architecture.md) — how social auto-sharing works
- [settings-and-data.md](settings-and-data.md) — the settings option & migrations
- [mozart-dependencies.md](mozart-dependencies.md) — prefixed vendored libraries

## Source

- [../../wp-scheduled-posts.php](../../wp-scheduled-posts.php) — entry point, `WPSP` engine
- [../../includes/functions.php](../../includes/functions.php) — procedural `wpsp_*` / `wpscp_*` helpers
- [../../CLAUDE.md](../../CLAUDE.md) — architecture summary
