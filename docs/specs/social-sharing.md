# Social Sharing Engine — Functional Specification

**Availability:** Free (with 🔒 Pro extensions: unlimited profiles, Page-type accounts, LinkedIn pages, Google Business — see [Pro specs](../../../wp-scheduled-posts-pro/docs/specs/README.md))
**Location:** **Settings → Social Profile** (connect); post editor **Social Share** section / Classic **Social Share Settings** box (per-post + Share Now).

## Summary

Connects social accounts and shares posts to them — automatically when a post publishes, or instantly via **Share Now**. Formatting is controlled by [social templates](social-templates.md).

## Supported platforms & free/Pro boundary

| Platform | Free | Pro extension |
|---|---|---|
| Facebook (Page/Group) | ✅ | multiple profiles |
| Twitter / X | ✅ | multiple profiles |
| LinkedIn | ✅ personal **profile** | 🔒 **Company/organization pages**, multiple profiles |
| Pinterest | ✅ (board) | 🔒 multiple **boards & sections**, multiple profiles |
| Instagram (Business) | ✅ | multiple profiles |
| Medium | ✅ | multiple profiles |
| Threads | ✅ | multiple profiles |
| Google Business Profile | 🔒 Pro (entire platform) | connect + share |

Rules:
- **Free = one profile per network.** Connecting additional profiles, and **"Page"-type accounts** (except Facebook), triggers the Pro upgrade popup.
- A platform's integration only loads/shares when its **status toggle** (`{platform}_profile_status`) is ON.

## Settings & options (per platform, Social Profile tab)

| Option | Option key | Effect |
|---|---|---|
| Platform status toggle | `{platform}_profile_status` | Enables/disables the whole platform |
| Connected accounts list | `{platform}_profile_list` | Stored connected profiles, each with an on/off toggle |
| App credentials modal | — | App ID/secret entry + copyable Redirect/Callback URL + doc links |

`{platform}` ∈ `facebook`, `twitter`, `linkedin`, `pinterest`, `instagram`, `medium`, `threads`, `google_business`.

## Behavior & rules

### Connecting
Each platform uses **your own app credentials** (privacy + your own rate limits). Flow: open the connect modal → copy the **Redirect URL** → create the app in the platform's portal → paste keys back → authorize → the account appears in the list → enable it.

### Auto-share on publish
- When a scheduled post reaches `publish`, sharing runs for every **enabled** connected profile of every **enabled** platform.
- The message is built from the platform's [template](social-templates.md); wording/limits are per-platform.

### Share Now (instant/manual)
- **Block editor:** the **Social Share** section lets you pick connected profiles and click **Share Now**; a per-profile status modal reports success/failure and a share counter.
- **Classic editor:** the **Social Share Settings** box adds: platform/profile selection, a **custom social banner image** upload, a **Disable Social Share** toggle, Pinterest **Default vs Custom Board** (board + section), and **Share Now**.

### Per-post controls
- Upload a **custom social banner image** for the post.
- **Opt the post out** of social sharing entirely.
- Choose which connected profiles receive the post.

## States (per post, per profile)

| State | Meaning |
|---|---|
| Not shared | No share attempted/queued. |
| Shared | Successfully posted to that profile (share count incremented). |
| Failed | Share attempt returned an error (shown in the status modal). |
| Opted out | Sharing disabled for this post. |

## Interactions

- Wording/limits come from [social templates](social-templates.md); per-post overrides from [custom templates](custom-post-templates.md).
- [AI Caption](ai-caption.md) can generate the message.
- Pro adds share-on-publish-with-future-date and re-share-on-republish.

## Limits & edge cases

- **Auth/redirect mismatch** is the most common failure — the app's Redirect URL must exactly match the one shown.
- **Token expiry** — profiles may need reconnecting; sharing silently stops until reconnected.
- Per-platform **character limits** apply (see [social templates](social-templates.md)); over-limit content is trimmed.
- Outbound HTTP timeout is raised to 30s to accommodate slow social APIs.

## Technical touchpoints

- **Option keys:** `{platform}_profile_status`, `{platform}_profile_list`.
- **Post meta:** `_wpsp_is_{platform}_share` (per-platform share flag), `__wpscppro_social_share_{platform}` (share-count log), `_wpscppro_custom_social_share_image`, `_wpscppro_dont_share_socialmedia`, Pinterest `_wpscppro_pinterestboardtype` / `_wpscppro_pinterest_board_name` / `_wpscppro_pinterest_section_name`.
- **Hooks:** `wpsp_publish_future_post` (auto-share on publish); Pro gate filter `wpsp_social_profile_limit_checkpoint`.
- **AJAX:** `wpscp_instant_social_single_profile_share` (Share Now).
- **Pro gating:** Google Business instant-share requires `class_exists('WPSP_PRO')`.
