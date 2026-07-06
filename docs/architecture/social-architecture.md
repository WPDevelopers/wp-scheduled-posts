# Social Sharing Architecture

> **Status:** stub — outline only. Feature-level behaviour: [../specs/social-sharing.md](../specs/social-sharing.md), [../specs/social-templates.md](../specs/social-templates.md).

How auto-sharing to social platforms is built.

## Per-platform classes
_TODO — one class per platform in [../../includes/Social/](../../includes/Social/): `Facebook`, `Twitter`, `Linkedin`, `Pinterest`, `Instagram`, `Medium`, `Threads`, `GoogleBusiness`, `InstantShare`._

## Shared logic
_TODO — the `WPSP\Traits\SocialHelper` trait; `SocialProfile`; `SocialReconnection`/`ReconnectHandler`._

## OAuth & middleware
_TODO — OAuth flows go through the middleware endpoints defined in `define_constants()`; per-platform char limits via `Helper::get_social_platform_limits()`._

## Platform SDKs (Deps)
_TODO — SDKs vendored under `includes/Deps/` (facebook graph-sdk, twitteroauth, linkedin, pinterest, guzzle); Mozart-prefixed — see [mozart-dependencies.md](mozart-dependencies.md)._

## Source
- [../../includes/Social/](../../includes/Social/)
- [../specs/social-sharing.md](../specs/social-sharing.md)
