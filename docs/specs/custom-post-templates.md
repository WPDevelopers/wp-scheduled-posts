# Custom Per-Post Social Messages — Functional Specification

**Availability:** Free (applying distinct messages across **all** profiles is 🔒 Pro)
**Location:** Post editor → custom social template modal (within the Schedule And Share / Social Share area).

## Summary

Overrides the global [social template](social-templates.md) for a single post — write a custom message per platform (or one global message), preview it, edit, choose target profiles, and share/schedule.

## Behavior & rules

- From the editor you can compose a **per-platform message** or a **global template** for the post.
- A **live preview** renders how the post will look on each platform (preview card).
- A **template editor** supports the same tokens as global templates: `{title}`, `{content}`, `{url}`, `{tags}`.
- You select which **connected profiles** should receive the post.
- The custom message can be **shared immediately** or **scheduled**.
- **Free rule:** editing applies to the **first enabled profile per platform** only. **Pro** unlocks distinct messages across every connected profile/network.
- A **global-vs-per-platform** toggle (`useGlobalTemplate_{platform}`) decides whether the post uses the global template or its own.

## Character handling

- A live **character counter** shows `used / limit`; over-limit content is flagged and server-validated against each platform's max (same caps as [social templates](social-templates.md)).

## States

| State | Meaning |
|---|---|
| Using global template | Post shares with the platform default. |
| Custom (per-platform/global) | Post uses its own saved message. |
| Scheduled custom share | A custom share is queued for later. |

## Interactions

- Saved with the post and executed by the [social sharing engine](social-sharing.md).
- Can be generated via [AI Caption](ai-caption.md).
- Sits on top of the global [social templates](social-templates.md).

## Limits & edge cases

- Over-limit messages are rejected/trimmed on save.
- Free users editing multiple profiles see the Pro popup when going beyond the first profile.

## Technical touchpoints

- **REST:** `wp-scheduled-posts/v1/custom-templates/{post_id}` (GET/POST/DELETE); `social-settings/{post_id}`.
- **Option/meta:** per-post custom template meta; `useGlobalTemplate_{platform}` flag.
- **Scheduling hook:** `wpsp_custom_social_template`.
