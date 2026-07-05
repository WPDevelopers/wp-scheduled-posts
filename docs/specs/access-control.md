# Access Control (Post Types, Roles, Categories) — Functional Specification

**Availability:** Free
**Location:** **Settings → General**.

## Summary

Central switches that decide **what** SchedulePress manages (post types, categories) and **who** can use it (roles). These gate almost every other feature.

## Settings & options

| Option (label) | Option key | Effect | Default |
|---|---|---|---|
| Show Post Types | `allow_post_types` | Post types SchedulePress manages everywhere | `['post']` |
| Allow users | `allow_user_by_role` | Roles permitted to use SchedulePress | `['administrator']` |
| Show Categories | `allow_categories` | Restrict scheduling/listing to categories | `['all']` |
| Allow Taxonomy as Tags | `allow_taxonomy_as_tags` | Taxonomies treated as tags for `{tags}` | `['category','post_tag']` |

## Behavior & rules

### Post types (`allow_post_types`)
Enabling a post type activates, **for that type**: the editor Schedule-And-Share panel, the per-type Calendar submenu, dashboard-widget listing, admin-bar listing, and social sharing. Any public post type can be enabled.

### Roles (`allow_user_by_role`)
A user must be in an allowed role to: open the settings/Calendar, see the dashboard widget and admin-bar menu, and act on calendar events. This is enforced by an internal `is_user_allow()` check across features — independent of native post capabilities (a user still also needs `edit_post`/`delete_post` for per-post actions).

### Categories (`allow_categories`)
`['all']` means no restriction. Selecting specific categories limits which posts are listed/creatable in the Calendar, dashboard widget, and admin bar, and scopes scheduling.

### Taxonomy as tags (`allow_taxonomy_as_tags`)
Determines which taxonomies contribute terms to the `{tags}` token in [social templates](social-templates.md).

## States

Access is a **site-wide configuration**, not per-post. Changing it immediately widens/narrows where SchedulePress appears.

## Interactions

- Referenced by every UI surface: [scheduling](scheduling.md), [calendar](schedule-calendar.md), [dashboard widget](dashboard-widget.md), [admin bar](admin-bar-menu.md), [social sharing](social-sharing.md).
- Special integrations: **Elementor**, **BetterDocs** (`docs` type), **Visual Composer** detection.

## Limits & edge cases

- Role allow-list is **additive to**, not a replacement for, WordPress capabilities — both must pass for per-post edit/delete.
- Removing a post type from `allow_post_types` hides SchedulePress there but does not alter existing scheduled posts.

## Technical touchpoints

- **Option keys:** `allow_post_types`, `allow_user_by_role`, `allow_categories`, `allow_taxonomy_as_tags`.
- **Gate:** internal `WPSP\Helper::is_user_allow()` / `get_all_allowed_post_type()`.
