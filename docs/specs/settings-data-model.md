# Settings & Data Model — Functional Specification

**Availability:** Free (Pro extends the same option)
**Location:** **SchedulePress → Settings** (single-page app with tabbed sections).

## Summary

Describes where SchedulePress stores configuration and per-post data, how the settings screen is organized, and how saving/migration works.

## Settings storage

- All plugin settings live in **one WordPress option: `wpsp_settings_v5`** (constant `WPSP_SETTINGS_NAME`), stored as structured JSON.
- Legacy options `wpsp_settings` / `wpscp_options` are migrated forward (see Migration below).
- Defaults are seeded on install.

## Settings screen structure

The settings app renders tabs from a field tree. Tabs (by internal id):

| Tab | Internal id | Purpose |
|---|---|---|
| General | `layout_general` | Display + workflow toggles, post types, roles, categories, admin-bar template |
| Calendar | `layout_calendar` | Embedded editorial calendar |
| Email Notify | `layout_email_notify` | Notification toggles + recipients |
| Social Profile | `layout_social_profile` | Connect/enable social accounts |
| Social Templates | `layout_social_template` | Per-platform share formatting |
| Write With AI | `layout_ai` | OpenAI API key |
| Scheduling Hub 🔒 Pro | `layout_scheduling_hub` | Auto/Manual scheduler, Missed schedule |
| License (Pro) | `layout_license` | License activation (added by Pro; removes the free upsell card) |

## Saving behavior

- Each section with its own **Save** button persists that section to `wpsp_settings_v5`.
- On save, social template character counts are **hard-capped** per platform (see [social templates](social-templates.md)).
- Values persist immediately after saving.

## Per-post data (meta)

Key post meta written by features:

| Meta key | Written by | Purpose |
|---|---|---|
| `_wpsp_is_{platform}_share` | [Social sharing](social-sharing.md) | Per-platform share flag |
| `__wpscppro_social_share_{platform}` | Social sharing | Share-count log |
| `_wpscppro_custom_social_share_image` | Social sharing | Custom banner image |
| `_wpscppro_dont_share_socialmedia` | Social sharing | Opt-out flag |
| `_wpscppro_pinterest*` | Pinterest | Board type / board / section |
| `useGlobalTemplate_{platform}` | [Custom templates](custom-post-templates.md) | Global vs custom template |
| `prevent_future_post` | [Scheduling](scheduling.md) | Publish-immediately behavior marker |

(Pro adds Advanced-schedule / republish-unpublish meta — see the Pro specs.)

## Statuses

| Status | Origin | Meaning |
|---|---|---|
| `future` | Core | Scheduled |
| `publish` | Core | Live |
| `pending` / `trash` | Core | Review / rejected (notifications) |
| `delayed_future` / `delayed_publish` | Pro | Advanced-schedule states |
| `wpsp_adv_schedule` | Pro | Advanced-schedule hidden copy |

## Migration

- On load, legacy settings and post data are migrated into `wpsp_settings_v5` (e.g. version-3→4 transformations). Migration runs on `wp_loaded`.

## Constants (for reference)

`WPSP_VERSION`, `WPSP_PLUGIN_SLUG` (`wp-scheduled-posts`), `WPSP_SETTINGS_SLUG` (`schedulepress`), `WPSP_SETTINGS_NAME` (`wpsp_settings_v5`), `WPSP_SETTINGS_NAME_OLD` (`wpsp_settings`), plus path/URI and social OAuth app-id constants.

## Technical touchpoints

- **Option:** `wpsp_settings_v5` (JSON).
- **Migration:** runs on `wp_loaded`.
- **REST:** settings read/write via `wp-scheduled-posts/v1` (`get-option-data`, save routes).
- **JWT whitelist:** `wp-scheduled-posts/v1`, `wp-scheduled-posts-pro/v1`, `wpscp/v1`.
