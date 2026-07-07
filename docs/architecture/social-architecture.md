# Social Sharing Architecture

How auto-sharing to social platforms is built inside the Free plugin. For end-to-end feature behaviour see [../specs/social-sharing.md](../specs/social-sharing.md); for message formatting see [../specs/social-templates.md](../specs/social-templates.md). This document describes the code: the orchestrator, the per-platform classes, the shared trait, OAuth/middleware, and how the platform SDKs are loaded.

## The orchestrator — `WPSP\Social`

[../../includes/Social.php](../../includes/Social.php) is the entry point, instantiated once from `WPSP::getSocial()` in [../../wp-scheduled-posts.php](../../wp-scheduled-posts.php). Its constructor:

1. **Defines the option-name and OAuth-scope constants** (`define_constants()`) — one `WPSCP_{PLATFORM}_OPTION_NAME` per network, each mapping to the settings key that holds that network's connected accounts:

   | Constant | Value (settings key) |
   |---|---|
   | `WPSCP_FACEBOOK_OPTION_NAME` | `facebook_profile_list` |
   | `WPSCP_TWITTER_OPTION_NAME` | `twitter_profile_list` |
   | `WPSCP_LINKEDIN_OPTION_NAME` | `linkedin_profile_list` |
   | `WPSCP_PINTEREST_OPTION_NAME` | `pinterest_profile_list` |
   | `WPSCP_INSTAGRAM_OPTION_NAME` | `instagram_profile_list` |
   | `WPSCP_MEDIUM_OPTION_NAME` | `medium_profile_list` |
   | `WPSCP_THREADS_OPTION_NAME` | `threads_profile_list` |
   | `WPSCP_GOOGLE_BUSINESS_OPTION_NAME` | `google_business_profile_list` |

   Alongside these it defines the per-network OAuth **scope** strings (`WPSCP_FACEBOOK_SCOPE`, `WPSCP_INSTAGRAM_SCOPE`, `WPSCP_LINKEDIN_SCOPE`/`…_OPENID`/`…_OPENID_PAGE`, `WPSCP_THREADS_SCOPE`, `WPSCP_GOOGLE_BUSINESS_SCOPE`).

2. **Loads the always-on subsystems** (`load_dependancy()`): `SocialProfile` (connect UI + AJAX), `ReconnectHandler`, and `SocialReconnection`.

3. **Conditionally loads each platform integration** (`load_third_party_integration()`) — a platform's class is only instantiated and hooked when its `{platform}_profile_status` toggle is `true` in settings. Each `{platform}()` method does `new Social\{Platform}()` then calls `->instance()` to register hooks.

4. **Wires the publish pipeline** and instantiates `InstantShare` (only for allowed users, via `Helper::is_user_allow()`).

### The publish pipeline

`Social::__construct()` hooks WordPress core's `publish_future_post` (and `wpsp_custom_social_template`) and re-dispatches them through a single internal action, `do_action('wpsp_publish_future_post', $post_id)` (guarded so it fires once per post). Every enabled platform's `instance()` subscribes to `wpsp_publish_future_post`; its handler (e.g. `Facebook::WpScp_Facebook_post_event()`) verifies the post is `publish` and then schedules a **per-platform WP-Cron single event** (e.g. `WpScp_Facebook_post`) that performs the actual API call asynchronously. This keeps the publish request fast and lets each network fail independently.

## Per-platform classes

One class per network lives in [../../includes/Social/](../../includes/Social/), all in the `WPSP\Social` namespace and all using `WPSP\Helper` plus the shared `WPSP\Traits\SocialHelper` trait:

| Class | File | Notes |
|---|---|---|
| `Facebook` | [Facebook.php](../../includes/Social/Facebook.php) | Page/Group posting; Open Graph `wp_head` meta |
| `Twitter` | [Twitter.php](../../includes/Social/Twitter.php) | Uses `Abraham\TwitterOAuth` |
| `Linkedin` | [Linkedin.php](../../includes/Social/Linkedin.php) | Personal profile in Free (Company pages are Pro); uses `myPHPNotes\LinkedIn` |
| `Pinterest` | [Pinterest.php](../../includes/Social/Pinterest.php) | Board/section pins; uses `DirkGroenen\Pinterest` |
| `Instagram` | [Instagram.php](../../includes/Social/Instagram.php) | Business accounts via the Graph API |
| `Medium` | [Medium.php](../../includes/Social/Medium.php) | Tags/categories passed as arrays, not `#hashtags` |
| `Threads` | [Threads.php](../../includes/Social/Threads.php) | Threads content-publish API |
| `GoogleBusiness` | [GoogleBusiness.php](../../includes/Social/GoogleBusiness.php) | **Pro-only platform** (connect + share gated on `class_exists('WPSP_PRO')`) |
| `InstantShare` | [InstantShare.php](../../includes/Social/InstantShare.php) | Share-Now metabox + AJAX (not tied to one network) |

Each platform class follows the same shape:

- **`__construct()`** reads its slice of the `social_templates` setting (template structure, content type/source, character limit, "show meta", "category as tags", etc.) so those values are available to every share.
- **`instance()`** registers the `wpsp_publish_future_post` handler and its own cron action, and conditionally adds the Pro republish hook (`wpscp_pro_schedule_republish_share`) when `is_republish_social_share` is enabled.
- **`get_share_content_args()`** assembles title/description/link/tags for the post.
- **`remote_post()`** performs the authenticated API call using the platform SDK.
- **`socialMediaInstantShare()`** is the Share-Now path invoked by `InstantShare`.
- **`save_metabox_social_share*()`** records the result (share log/count) into post meta.

### InstantShare (Share Now)

[InstantShare.php](../../includes/Social/InstantShare.php) adds the classic-editor "Social Share Settings" metabox and its persistence, and registers the AJAX endpoints `wp_ajax_wpscp_instant_share_fetch_profile` and `wp_ajax_wpscp_instant_social_single_profile_share` (also exposed as the `wpsp_instant_social_single_profile_share` action). Each call dispatches to the relevant platform's `socialMediaInstantShare()` and returns per-profile success/failure for the status modal.

## Shared logic — the `SocialHelper` trait

[../../includes/Traits/SocialHelper.php](../../includes/Traits/SocialHelper.php) (`WPSP\Traits\SocialHelper`) holds the formatting logic every platform reuses:

- **`getPostHasTags()` / `getPostHasCats()`** — collect the post's terms across the allowed taxonomies and render them as `#hashtags` (or as plain arrays for Medium).
- **`social_share_content_template_structure()`** — the message builder. It substitutes the `{title}`, `{content}`, `{url}`, `{tags}` tokens of the platform template, enforces the character limit (trimming `{content}` and dropping tags that would overflow), and applies filters such as `wpsp_social_share_title`, `wpsp_social_share_desc`, and `wpsp_social_share_content_template_line_break`. When a post opts into a custom template (`_wpsp_enable_custom_social_template` meta) it resolves the per-post/per-profile template via `WPSP\Helpers\CustomTemplateHelper` — see [../specs/custom-post-templates.md](../specs/custom-post-templates.md).

## Connecting accounts — `SocialProfile`

[../../includes/Social/SocialProfile.php](../../includes/Social/SocialProfile.php) powers **Settings → Social Profile**. It registers the AJAX handlers that connect and store accounts:

- `wp_ajax_wpsp_social_add_social_profile` → `add_social_profile()` — persists a newly authorized account into `{platform}_profile_list`.
- `wp_ajax_wpsp_social_profile_fetch_user_info_and_token` → exchanges the OAuth `code`/token and fetches profile info (Facebook, Instagram, Threads, Google Business, LinkedIn, Pinterest).
- `wp_ajax_wpsp_social_profile_fetch_pinterest_section` → fetches Pinterest boards/sections.

It also enforces the **Free vs Pro boundary**: `social_single_profile_checkpoint()` and the `wpsp_social_profile_limit_checkpoint` filter gate how many profiles may be connected. In Free, `Helper::get_social_profile()` returns only the first enabled profile (`array_slice($profile, 0, 1)`) unless `WPSP_PRO` is active and the checkpoint filter approves — this is the "one profile per network" rule described in the [social-sharing spec](../specs/social-sharing.md).

## Reconnection & token refresh

Two classes keep tokens alive by rewriting the profile entry inside the settings option:

- **`SocialReconnection`** ([SocialReconnection.php](../../includes/Social/SocialReconnection.php)) — schedules and runs LinkedIn refresh-token renewal on a WP-Cron single event (`wpsp_linkedin_reconnect_cron_event`, re-armed ~59 days out), POSTing the stored `refresh_token` to LinkedIn and updating `access_token`/`expires_in` in `linkedin_profile_list`.
- **`ReconnectHandler`** ([ReconnectHandler.php](../../includes/Social/ReconnectHandler.php)) — refreshes Instagram long-lived tokens (`ig_refresh_token` against `graph.instagram.com`) and updates the profile's `long_lived_access_token`/`expires_at` in `instagram_profile_list`.

`Helper::get_access_token()` / `Helper::update_access_token()` in [../../includes/Helper.php](../../includes/Helper.php) provide the generic refresh-on-expiry path for Facebook, Twitter, LinkedIn and Pinterest.

## OAuth & middleware

Each network is connected with the **user's own app credentials** (entered in the connect modal), so tokens and rate limits belong to the site owner. The OAuth handshake is routed through a WPDeveloper-hosted callback so the redirect URL is stable across sites. The relevant constants are defined in `define_constants()` in [../../wp-scheduled-posts.php](../../wp-scheduled-posts.php):

| Constant | Purpose |
|---|---|
| `WPSP_SOCIAL_OAUTH2_TOKEN_MIDDLEWARE` | OAuth callback/redirect endpoint handed to the connect JS (`redirect_url` in [../../includes/Assets.php](../../includes/Assets.php)) |
| `WPSP_SOCIAL_OAUTH2_TOKEN_MIDDLEWARE_DEV` | Callback endpoint used by the Google Business token exchange in [GoogleBusiness.php](../../includes/Social/GoogleBusiness.php) |
| `WPSP_SOCIAL_OAUTH2_PINTEREST_APP_ID` | Shared Pinterest app id used as a fallback when the user has not supplied their own |
| `WPSP_SOCIAL_OAUTH2_LINKEDIN_APP_ID` | Shared LinkedIn app id (fallback) |
| `WPSP_SOCIAL_OAUTH2_GOOGLE_BUSINESS_APP_ID` | Shared Google Business OAuth client id (fallback) |

The per-platform **character limits** used to trim messages come from a single source of truth, `Helper::get_social_platform_limits()` in [../../includes/Helper.php](../../includes/Helper.php). It returns the user-configured `social_templates` limits (`tweet_limit` / `status_limit` / `note_limit`) and falls back to platform defaults (Facebook 63206, Twitter 280, LinkedIn 1300, Pinterest 500, Instagram 2100, Medium 45000, Threads 480, Google Business 1500). The same function backs [AI Caption](../specs/ai-caption.md) and template validation so the back-end never rejects content the UI shows as valid.

## Platform SDKs (Deps)

The networking libraries are vendored under [../../includes/Deps/](../../includes/Deps/) and `vendor/`, split by how Mozart treats them:

- **Guzzle / PSR-7** are Mozart-prefixed into `includes/Deps/` under the `WPSP\Deps\` namespace (e.g. `WPSP\Deps\GuzzleHttp\Client`, used by `Helper`).
- **The social SDKs are excluded from Mozart** and loaded from `vendor/` under their **original** namespaces: `Facebook\Facebook` (facebook/graph-sdk), `Abraham\TwitterOAuth` (twitteroauth), `myPHPNotes\LinkedIn` (linkedin-sdk-php), and `DirkGroenen\Pinterest` (pinterest-api-php).

See [mozart-dependencies.md](mozart-dependencies.md) for the exact package list, the Mozart config, and why `includes/Deps/` must never be hand-edited.

## Free vs Pro

The Free plugin ships the full engine above. Pro (the sibling `wp-scheduled-posts-pro` plugin) extends it through the same hooks and settings: multiple profiles per network and Page-type accounts (via the `wpsp_social_profile_limit_checkpoint` filter), LinkedIn Company pages, the entire Google Business Profile platform, and re-share-on-republish (`wpscp_pro_schedule_republish_share`). No Pro-specific sharing logic lives in this plugin — it only exposes the extension points.

## Source

- [../../includes/Social.php](../../includes/Social.php) — orchestrator
- [../../includes/Social/](../../includes/Social/) — per-platform classes, `SocialProfile`, reconnection
- [../../includes/Traits/SocialHelper.php](../../includes/Traits/SocialHelper.php) — shared formatting trait
- [../../includes/Helper.php](../../includes/Helper.php) — token storage, `get_social_platform_limits()`
- [../specs/social-sharing.md](../specs/social-sharing.md), [../specs/social-templates.md](../specs/social-templates.md) — feature specs
