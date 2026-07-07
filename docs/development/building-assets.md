# Building Assets

How to build SchedulePress's JavaScript/CSS bundles and PHP dependencies. The
authoritative command list lives in the repo root
[../../CLAUDE.md](../../CLAUDE.md) ("Commands"); this page summarizes the workflow
for contributors.

JS/asset builds use [`@wordpress/scripts`](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-scripts/)
(`wp-scripts`). Scripts are declared in [../../package.json](../../package.json).

## JS / CSS commands

Run from the plugin root unless noted.

| Command | Does |
| --- | --- |
| `npm run start` | `wp-scripts start` — watch + rebuild the main bundle during development. |
| `npm run build` | `wp-scripts build` — production build of the main bundle. |
| `npm run admin-start` | `cd includes/Admin/Settings && wp-scripts start` — watch the admin **Settings** React app ([../../includes/Admin/Settings](../../includes/Admin/Settings)), which has its own build. |
| `npm run pot` | `wp i18n make-pot . languages/wp-scheduled-posts.pot` — regenerate the translation template. |
| `npm run zip` | `wp dist-archive ./ ../wp-scheduled-posts.<version>.zip` — build the distributable archive (version comes from `package.json`). |
| `npm run release` | `build` + `pot` + `zip` — the full release pipeline. |
| `npm run packages-update` | `wp-scripts packages-update` — update the `@wordpress/*` packages. |

The Settings app under [../../includes/Admin/Settings](../../includes/Admin/Settings)
is a **separate** wp-scripts project — use `npm run admin-start` (or its own
`build`) when working on the React settings UI, not the root `npm run start`.

**Versioning:** `WPSP_VERSION` (defined in `define_constants()` in
[../../wp-scheduled-posts.php](../../wp-scheduled-posts.php)) and the `version` in
`package.json` must be bumped together when shipping — `npm run zip` names the
archive from the `package.json` version.

## PHP dependencies

`composer install` installs the PHP libraries declared in
[../../composer.json](../../composer.json) — the social-platform SDKs
(`facebook/graph-sdk`, `wpdevelopers/twitteroauth`, `wpdevelopers/linkedin-sdk-php`,
`wpdevelopers/pinterest-api-php`), Guzzle, and the WPDeveloper notice library.

Third-party packages are **prefixed** into
[../../includes/Deps/](../../includes/Deps/) under the `WPSP\Deps\` namespace by
[Mozart](https://github.com/coenjacobs/mozart) (configured in the `extra.mozart`
block of `composer.json`), which runs as part of `composer install`. This prevents
class collisions with other plugins that bundle the same libraries.

**Never edit `includes/Deps/` by hand** — it is generated. To change a bundled
dependency, update the source package (or the `require`/`extra.mozart` config in
`composer.json`) and re-run `composer install`. Some packages are listed in
`excluded_packages` and are shipped unprefixed. See
[../architecture/mozart-dependencies.md](../architecture/mozart-dependencies.md)
for the full details.

## Related PHP tooling

- `composer install` — installs deps and runs Mozart prefixing (above).
- `bin/install-wp-tests.sh <db-name> <db-user> <db-pass> [db-host] [wp-version]` — provisions the WordPress PHPUnit test library.
- `vendor/bin/phpunit` — runs the test suite ([../../phpunit.xml](../../phpunit.xml)).
- `vendor/bin/phpcs --standard=phpcs.xml` — coding-standards check.

## Source

- [../../package.json](../../package.json) — npm scripts.
- [../../composer.json](../../composer.json) — PHP deps + Mozart config.
- [../../CLAUDE.md](../../CLAUDE.md) — authoritative command list.
- [../architecture/mozart-dependencies.md](../architecture/mozart-dependencies.md) — how the `WPSP\Deps\` prefixing works.
