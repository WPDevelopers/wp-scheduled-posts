# Building Assets

> **Status:** stub — outline only. Authoritative command list is in [../../CLAUDE.md](../../CLAUDE.md).

JS/asset builds use `@wordpress/scripts` (wp-scripts). See [../../package.json](../../package.json).

## Commands

| Command | Does |
| --- | --- |
| `npm run start` | Watch the main bundle. |
| `npm run build` | Production build. |
| `npm run admin-start` | Watch the admin Settings React app ([../../includes/Admin/Settings](../../includes/Admin/Settings)). |
| `npm run pot` | Regenerate `languages/wp-scheduled-posts.pot`. |
| `npm run release` | `build` + `pot` + `zip`. |
| `npm run zip` | Build the distributable archive. |

## PHP dependencies
_TODO — `composer install` installs social SDKs + guzzle, Mozart-prefixed into `includes/Deps/` (`WPSP\Deps\`). Never edit `includes/Deps/`. See [../architecture/mozart-dependencies.md](../architecture/mozart-dependencies.md)._

## Source
- [../../package.json](../../package.json)
- [../../CLAUDE.md](../../CLAUDE.md)
