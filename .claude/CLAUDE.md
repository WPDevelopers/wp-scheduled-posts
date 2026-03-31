# SchedulePress (wp-scheduled-posts) — CLAUDE.md

## Plugin Overview

**SchedulePress** is a WordPress plugin by WPDeveloper for advanced post scheduling with social media auto-sharing. It provides a scheduling calendar, auto-scheduler, missed schedule recovery, and multi-platform social sharing (Facebook, Twitter, LinkedIn, Pinterest, Instagram, Medium, Threads, Google Business).

- **Plugin slug:** `wp-scheduled-posts`
- **PHP namespace:** `WPSP\`
- **Text domain:** `wp-scheduled-posts`
- **Current version:** 5.2.17
- **License:** GPLv3
- **Minimum PHP:** 7.2 (runtime), 8.0 (PHPCS tested)
- **Minimum WordPress:** 5.8

## Repository Layout

```
wp-scheduled-posts/
├── wp-scheduled-posts.php      # Plugin entry point (singleton WPSP class)
├── includes/                   # PSR-4 PHP source (namespace WPSP\)
│   ├── Admin.php               # Core admin module (~2000 lines)
│   ├── Assets.php              # Script/style enqueue
│   ├── Email.php               # Email notifications
│   ├── Social.php              # Social sharing facade
│   ├── API.php                 # REST API registration
│   ├── Helper.php              # Utility helpers
│   ├── Installer.php           # Activation/install logic
│   ├── Migration.php           # DB migrations
│   ├── Admin/
│   │   ├── Calendar.php        # Schedule calendar UI
│   │   ├── Settings.php        # Settings handler (155 KB)
│   │   └── Settings/           # React admin dashboard (own Node build)
│   ├── API/                    # REST endpoint classes
│   ├── Social/                 # Per-platform sharing classes
│   ├── Helpers/                # Utility trait classes
│   ├── Traits/                 # Reusable PHP traits
│   └── Deps/                   # Mozart-namespaced third-party PHP deps
├── src/                        # Gutenberg block JS source → assets/js/
├── assets/                     # Compiled CSS, JS, images
│   ├── js/
│   ├── css/
│   └── sass/                   # SCSS source
├── tests/                      # PHPUnit suites (unit/, integration/)
├── languages/                  # i18n .pot / .po / .mo files
├── vendor/                     # Composer dependencies
└── .github/workflows/          # CI/CD pipelines
```

## Development Commands

### PHP

```bash
composer install                # Install PHP dependencies
composer phpcs                  # Run WordPress coding standards check
composer phpcbf                 # Auto-fix PHPCS violations
```

### JavaScript / Frontend

```bash
npm install                     # Install JS dependencies

# Gutenberg blocks (src/ → assets/js/)
npm start                       # Dev server with hot reload
npm run build                   # Production build

# Admin settings dashboard (includes/Admin/Settings/)
npm run admin-start             # Dev server for React settings app

# Release
npm run pot                     # Generate .pot translation file
npm run release                 # build + i18n + zip (full release artifact)
```

### Testing

```bash
# PHPUnit (requires WordPress test environment)
vendor/bin/phpunit              # Run all test suites
vendor/bin/phpunit --testsuite unit          # Unit tests only
vendor/bin/phpunit --testsuite integration   # Integration tests only
```

## Coding Standards

### PHP

- **Standard:** WordPress Coding Standards (WPCS) via `phpcs.xml`
- **Namespace:** `WPSP\` (PSR-4, maps to `./includes/`)
- **Exceptions from WPCS defaults:**
  - PSR-4 file naming is used (not WordPress `class-*.php` convention)
  - camelCase method and variable names are used (not snake_case)
- **Minimum compatible:** PHP 8.0 / WordPress 5.8 (per PHPCS config)
- **Excluded from scanning:** `vendor/`, `includes/Deps/`, `node_modules/`, `assets/`, `tests/`

Always run `composer phpcs` before committing PHP changes. Pre-commit hooks enforce this via `.husky/`.

### JavaScript / React

- ES6+ transpiled via Babel (`@wordpress/babel-preset-default`)
- React 17 with JSX
- Linting via `@wordpress/scripts` ESLint defaults
- Two separate webpack builds — do not mix their configs
- External globals: `jQuery`, `React`, `ReactDOM`, WordPress packages loaded globally by WordPress

### SCSS / CSS

- SASS source in `assets/sass/`; compiled output in `assets/css/`
- Do not edit compiled files directly — edit SASS sources

## Architecture Notes

### Plugin Bootstrapping

`wp-scheduled-posts.php` defines the `WPSP` singleton. Modules are lazy-loaded:

```php
WPSP::instance()->assets   // Assets
WPSP::instance()->admin    // Admin
WPSP::instance()->email    // Email
WPSP::instance()->social   // Social
WPSP::instance()->api      // REST API
```

### Settings Storage

- Settings key: `wpsp_settings_v5` (WordPress option)
- Backwards-compatible with v4 settings keys
- REST endpoints: `/wp-json/wp-scheduled-posts/v1/*`

### Third-party PHP Dependencies

Managed by Composer and namespaced into `WPSP\Deps\` via **Mozart** to prevent conflicts with other plugins. Never reference vendor classes directly — always use the `WPSP\Deps\` prefix.

### Pro Version

The plugin checks for a `WPSP_PRO` constant / companion pro plugin. Pro-only features are gated behind these checks. Do not remove or stub these checks.

### Social Platforms

Each platform has a dedicated class under `includes/Social/`:
`Facebook`, `Twitter`, `LinkedIn`, `Pinterest`, `Instagram`, `Medium`, `Threads`, `GoogleBusiness`

OAuth middleware endpoints are whitelisted in the JWT auth layer. When adding a new platform, follow the existing pattern in `Social.php` and register its REST route whitelist.

## CI/CD (GitHub Actions)

| Workflow | Purpose |
|---|---|
| `php-syntax-check.yml` | PHP syntax across 7.4, 8.2, 8.5 |
| `schedulepress-compatibility-test.yml` | Plugin activation on WP 6.x × PHP 7.4–8.5 matrix |
| `pcp-plugin-check.yml` | WordPress Plugin Check (PCP) validation |
| `plugin-version-verification.yml` | Version consistency across files |
| `qa.yml` | QA orchestration |
| `create-plugin-zip.yml` | Build distribution zip |
| `deploy.yml` | Deployment pipeline |

All PRs should pass the syntax check and compatibility test workflows before merging.

## Key Conventions

- **Never edit files in `includes/Deps/`** — these are auto-generated by Mozart.
- **Never edit files in `vendor/`** — managed by Composer.
- **Never edit compiled `assets/js/` or `assets/css/`** — always edit source and rebuild.
- When modifying settings, update both the PHP handler (`includes/Admin/Settings.php`) and the React settings UI (`includes/Admin/Settings/`).
- Internationalize all user-facing strings with `__( 'string', 'wp-scheduled-posts' )` / `esc_html__()` etc.
- Use `wpsp_` prefix for all hooks (actions/filters), options, and global functions.
- Escape all output; sanitize all input. Follow WordPress security best practices.

## Distribution

- `.distignore` defines files excluded from the WordPress.org zip.
- WordPress.org assets (banner, screenshots, readme) live in `.wordpress-org/`.
- Use `npm run release` to generate a clean distribution zip.
