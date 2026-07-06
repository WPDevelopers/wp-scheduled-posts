# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project

SchedulePress (plugin slug `wp-scheduled-posts`) is a WordPress plugin by WPDeveloper for automated content scheduling and social auto-sharing. Core capabilities: missed-schedule handling, a **Schedule Calendar**, a **Dashboard widget**, a **sitewide admin-bar menu**, per-post scheduling controls, and auto-sharing to Facebook, Twitter/X, LinkedIn, Pinterest, Instagram, Medium, Threads, and Google Business. Text domain `wp-scheduled-posts`; settings-UI slug `schedulepress`; main entry [wp-scheduled-posts.php](wp-scheduled-posts.php) (`final class WPSP`). The paid features live in the sibling plugin `wp-scheduled-posts-pro` and integrate via the same subsystems + WP hooks.

PHP namespace root: `WPSP\` → `includes/` (PSR-4 autoload via Composer; see [composer.json](composer.json)). Global procedural helpers live in [includes/functions.php](includes/functions.php) (`wpsp_*` / `wpscp_*` prefixes).

## Commands

JS/asset builds use `@wordpress/scripts` (wp-scripts). See [package.json](package.json):
- `npm run start` — watch the main bundle.
- `npm run build` — production build.
- `npm run admin-start` — watch the admin Settings React app ([includes/Admin/Settings](includes/Admin/Settings)).
- `npm run pot` — regenerate `languages/wp-scheduled-posts.pot`.
- `npm run release` — `build` + `pot` + `zip`. `npm run zip` builds the distributable archive.

PHP / tests:
- `composer install` — installs PHP libs (social SDKs, guzzle) declared in [composer.json](composer.json). Third-party deps are prefixed into `includes/Deps/` by **Mozart** (`WPSP\Deps\`) — do NOT edit `includes/Deps/` by hand.
- `bin/install-wp-tests.sh <db-name> <db-user> <db-pass> [db-host] [wp-version]` — provisions the WordPress PHPUnit test library (required before running tests).
- `vendor/bin/phpunit` — runs the suite (config: [phpunit.xml](phpunit.xml), bootstrap: [tests/bootstrap.php](tests/bootstrap.php)). Two suites: `unit` (`tests/unit/`, no WordPress needed) and `integration` (`tests/integration/`, boots the WP test env). Filter with `--testsuite unit` or `--testsuite integration`. Test files use the `*Test.php` suffix. The `WPSP_TESTING` constant is defined during tests.
- `vendor/bin/phpcs --standard=phpcs.xml` — coding standards ([phpcs.xml](phpcs.xml)).

## Architecture

### Bootstrap chain
[wp-scheduled-posts.php](wp-scheduled-posts.php) checks PHP ≥ 7.2, loads `vendor/autoload.php`, then calls `WPSP_Start()` which boots the `WPSP` singleton (`WPSP::init()` — a static `$instance`, not the WordPress `new`). The constructor defines all `WPSP_*` constants (`define_constants()`), runs Pro-compatibility checks against `wp-scheduled-posts-pro`, instantiates `WPSP\Installer`, and wires subsystems on `init` / `wp_loaded`.

Key constants: `WPSP_VERSION`, `WPSP_PLUGIN_SLUG` (`wp-scheduled-posts`), `WPSP_SETTINGS_SLUG` (`schedulepress`), and the settings option key `WPSP_SETTINGS_NAME` (`wpsp_settings_v5` — the older `wpsp_settings` is `WPSP_SETTINGS_NAME_OLD`, migrated by [includes/Migration.php](includes/Migration.php)). Bump `WPSP_VERSION` in `define_constants()` and `package.json` together when shipping.

### Subsystems (`includes/`)
- **Installer** ([includes/Installer.php](includes/Installer.php)) + **Migration** ([includes/Migration.php](includes/Migration.php)) — activation, schema/version transitions.
- **Admin** ([includes/Admin/](includes/Admin/)) — `Calendar`, `Menu`, `Metabox/`, `Notices/`, `Rule(s)` (access control), `Widgets/` (dashboard widget), and the React **Settings** app under `Admin/Settings/`.
- **Social** ([includes/Social/](includes/Social/)) — one class per platform (`Facebook`, `Twitter`, `Linkedin`, `Pinterest`, `Instagram`, `Medium`, `Threads`, `GoogleBusiness`, `InstantShare`) plus `SocialProfile`, `SocialReconnection`/`ReconnectHandler`. Shared logic in the `WPSP\Traits\SocialHelper` trait. OAuth flows go through the middleware endpoints defined in `define_constants()`. Platform SDKs come from `includes/Deps/` (facebook graph-sdk, twitteroauth, linkedin, pinterest, guzzle).
- **API** ([includes/API.php](includes/API.php), [includes/API/](includes/API/)) — REST endpoints: `AICaption`, `CustomSocialTemplates`, `PostPanel`, `Settings`.
- **Email** ([includes/Email.php](includes/Email.php)) — schedule / review notification emails.
- **Helper** ([includes/Helper.php](includes/Helper.php)) — static utilities: post-type/taxonomy/role queries, settings access (`get_settings()`, `wpsp_settings_v5()`), social profile/token storage, per-platform char limits (`get_social_platform_limits()`), content formatting.

### Settings
All plugin settings live in a single option, `WPSP_SETTINGS_NAME` (`wpsp_settings_v5`), read via `WPSP\Helper::get_settings($key)`. See [docs/specs/settings-data-model.md](docs/specs/settings-data-model.md) for the schema.

## Conventions worth knowing

- The engine is a static singleton — use `WPSP::init()`, never `new WPSP()`.
- Never edit `includes/Deps/` — it is Mozart-generated from Composer packages (`WPSP\Deps\` namespace). Change the source package + re-run `composer install`.
- Pro features live in the separate `wp-scheduled-posts-pro` plugin. Don't add Pro-only logic here; expose hooks instead.
- Global helpers in `includes/functions.php` use `wpsp_*` / `wpscp_*` prefixes.
- Settings key is versioned (`_v5`); migrations from older keys go through `Migration.php`.

## Reference docs in-repo

Task-oriented guides and per-feature specs — read these before touching a feature:

- [docs/README.md](docs/README.md) — docs index.
- [docs/guides/GETTING-STARTED.md](docs/guides/GETTING-STARTED.md), [docs/guides/FEATURES.md](docs/guides/FEATURES.md), [docs/guides/FAQ.md](docs/guides/FAQ.md), [docs/guides/SCHEDULING.md](docs/guides/SCHEDULING.md), [docs/guides/SOCIAL-SETUP.md](docs/guides/SOCIAL-SETUP.md).
- **Feature specs** in [docs/specs/](docs/specs/): scheduling, schedule-calendar, social-sharing, social-templates, ai-caption, admin-bar-menu, email-notifications, settings-data-model, dashboard-widget, access-control, custom-post-templates.
