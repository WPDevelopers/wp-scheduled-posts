# Dependencies & Mozart

How third-party PHP libraries are pulled in and namespace-isolated so they cannot collide with other plugins or WordPress core. The short rule: **never hand-edit `includes/Deps/` — it is generated.**

## Why Mozart

WordPress loads every active plugin into one PHP process. If two plugins each bundle their own copy of, say, Guzzle at different versions, the first one autoloaded wins and the other breaks. [Mozart](https://github.com/coenjacobs/mozart) solves this by **prefixing** a package's namespace when it is copied out of `vendor/`, so SchedulePress's Guzzle becomes `WPSP\Deps\GuzzleHttp\…` and can never clash with another plugin's `GuzzleHttp\…`.

Composer installs the declared packages into `vendor/` as usual; Mozart then rewrites the ones that need isolating into [../../includes/Deps/](../../includes/Deps/) under the `WPSP\Deps\` namespace. This is configured in the `extra.mozart` block of [../../composer.json](../../composer.json):

```json
"extra": {
    "mozart": {
        "dep_namespace": "WPSP\\Deps\\",
        "dep_directory": "/includes/Deps/",
        "classmap_directory": "/includes/Deps/classes/",
        "classmap_prefix": "WPSP_",
        "delete_vendor_directories": true,
        "excluded_packages": [ ... ]
    }
}
```

## What actually lands in `includes/Deps/`

Only the packages Mozart processes are prefixed. Everything listed in `excluded_packages` is **left in `vendor/` with its original namespace** and autoloaded from there. As a result the two groups are:

**Prefixed into `includes/Deps/` (namespace `WPSP\Deps\`):**

| Package | Prefixed namespace |
|---|---|
| `guzzlehttp/guzzle` | `WPSP\Deps\GuzzleHttp` |
| `guzzlehttp/promises` | `WPSP\Deps\GuzzleHttp\Promise` |
| `guzzlehttp/psr7` | `WPSP\Deps\GuzzleHttp\Psr7` |
| `psr/http-message` | `WPSP\Deps\Psr` |
| (supporting Symfony polyfills) | `WPSP\Deps\Symfony` |

For example, [../../includes/Helper.php](../../includes/Helper.php) imports `use WPSP\Deps\GuzzleHttp\Client;`.

**Excluded from Mozart — loaded from `vendor/` under their original namespaces:**

| Package | Namespace used in code |
|---|---|
| `facebook/graph-sdk` | `Facebook\` |
| `wpdevelopers/twitteroauth` | `Abraham\TwitterOAuth\` |
| `wpdevelopers/linkedin-sdk-php` | `myPHPNotes\` |
| `wpdevelopers/pinterest-api-php` | `DirkGroenen\Pinterest\` |
| `priyomukul/wp-notice` | (admin notices) |
| `paragonie/random_compat` | polyfill |

The social SDKs are deliberately excluded (they are not re-prefixed), which is why the platform classes in [../../includes/Social/](../../includes/Social/) reference the SDKs by their upstream names — e.g. `new \Facebook\Facebook([...])`, `new \Abraham\TwitterOAuth\TwitterOAuth(...)`, `new \DirkGroenen\Pinterest\Pinterest(...)`, `new \myPHPNotes\LinkedIn(...)`. See [social-architecture.md](social-architecture.md#platform-sdks-deps) for where each SDK is used.

Several of these packages come from WPDeveloper forks rather than the original vendors; the forks are declared as VCS `repositories` in [../../composer.json](../../composer.json) so Composer resolves them from GitHub.

## Workflow — how to change a dependency

1. Edit the `require` (and, if it is a fork, `repositories`) section of [../../composer.json](../../composer.json).
2. Run `composer install` (or `composer update <package>`). This installs into `vendor/`, then runs Mozart, which regenerates [../../includes/Deps/](../../includes/Deps/). With `delete_vendor_directories: true`, the prefixed source is removed from `vendor/` after copying.
3. If you are isolating a **new** namespace that currently collides, add it to `require`; if a package must stay un-prefixed (like the social SDKs), add it to `excluded_packages`.
4. Commit the regenerated `includes/Deps/` alongside the `composer.json` change.

**Never edit files under `includes/Deps/` by hand.** They are build output — any manual change is silently overwritten the next time `composer install` runs. Fix the source package (or its fork) and regenerate. This same rule is called out in the repo [../../CLAUDE.md](../../CLAUDE.md).

## Source

- [../../composer.json](../../composer.json) — `require`, `repositories`, and the `extra.mozart` config
- [../../includes/Deps/](../../includes/Deps/) — generated, prefixed libraries (do not edit)
- [social-architecture.md](social-architecture.md) — where the platform SDKs are consumed
