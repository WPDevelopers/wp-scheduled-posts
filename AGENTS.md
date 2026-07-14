# AGENTS.md — SchedulePress (wp-scheduled-posts)

Quick orientation for coding agents. Full project guidance lives in
[CLAUDE.md](CLAUDE.md); the documentation index is [docs/README.md](docs/README.md)
(guides / architecture / api / development / specs).

## Tests

Config: [phpunit.xml](phpunit.xml), bootstrap: [tests/bootstrap.php](tests/bootstrap.php).
PHPUnit 9 is required (the WP test suite is not PHPUnit 10 ready); it is NOT a
composer dependency — use the `phpunit-9.phar` from https://phar.phpunit.de/.

- Unit (no WordPress, no database): `phpunit --testsuite unit`
- Integration (needs the WP test library + MySQL):
  1. `bash bin/install-wp-tests.sh <db-name> <db-user> <db-pass> [db-host] [wp-version]`
  2. `phpunit --testsuite integration`
- Containerized alternative: the sibling `wp-scheduled-posts-docker` repo ships a
  PHP 8.2 + PHPUnit 9 + WP-test-suite container preconfigured for this plugin.

CI: `.github/workflows/phpunit.yml` runs both suites; it is wired into the QA
orchestrator (`qa.yml`) and gates the WordPress.org deploy (`deploy.yml`).

## Builds

Node 20 (see [.nvmrc](.nvmrc)); `npm ci` uses package-lock.json (the only lockfile).

- `npm run build` / `npm run start` — main Gutenberg bundle (wp-scripts).
- `npm run admin-start` — React Settings app in `includes/Admin/Settings/`
  (it has its own package.json; production build is `npm run prod` there).
- `npm run release` — build + pot + distributable zip.

## Key conventions

- NEVER edit `includes/Deps/` — Mozart-generated (`WPSP\Deps\` prefixed copies of
  composer packages). Change the source package and re-run `composer install`.
- `vendor/` and `composer.lock` are git-tracked; the committed autoloader is
  production-shaped, so tests/CI need no `composer install`.
- PSR-4: `WPSP\` maps to `includes/` ([composer.json](composer.json)). The engine
  is a static singleton — `WPSP::init()`, never `new WPSP()`.
- Settings live in one versioned option, `wpsp_settings_v5`, read via
  `WPSP\Helper::get_settings()` (spec: docs/specs/settings-data-model.md).
- Pro features belong in the sibling `wp-scheduled-posts-pro` plugin — expose
  hooks here instead of adding Pro-only logic.
- A husky pre-commit hook runs `php -l` on staged PHP files (`.husky/pre-commit`).
