# Dashboard Widget — Functional Specification

**Availability:** Free
**Location:** WordPress **Dashboard** → "Scheduled Posts" widget.

## Summary

Adds a **"Scheduled Posts"** box to the WordPress dashboard listing all upcoming (scheduled) posts across enabled post types — a quick overview for single- and multi-author sites.

## Settings & options

| Option (label) | Option key | Effect | Default |
|---|---|---|---|
| Show Scheduled Posts in Dashboard Widget | `is_show_dashboard_widget` | Registers/removes the dashboard widget | ON |
| Show Post Types | `allow_post_types` | Which post types are listed | `['post']` |
| Show Categories | `allow_categories` | Restricts listed posts by category | `['all']` |
| Allow users | `allow_user_by_role` | Which roles see the widget | `['administrator']` |

## Behavior & rules

- The widget registers on dashboard setup **only when** `is_show_dashboard_widget` is ON **and** the current user passes the role allow-list.
- It lists all posts in **`future`** status across `allow_post_types`, honoring `allow_categories`.
- Each row shows: **title** (links to the post edit screen), **post type**, **category**, **scheduled date & time** (site date + time format), and **author**.
- Empty state: **"No post is scheduled."**

## States shown

Only **scheduled (`future`)** posts. Draft/published posts are not listed here (use the [Calendar](schedule-calendar.md) for those).

## Interactions

- Reflects everything scheduled via the [editor](scheduling.md) or [Calendar](schedule-calendar.md).
- Visibility bounded by [access control](access-control.md).

## Limits & edge cases

- Users not in the allowed roles never see the widget.
- Date/time formatting follows the site's configured formats and timezone.

## Technical touchpoints

- **Option keys:** `is_show_dashboard_widget`, `allow_post_types`, `allow_categories`, `allow_user_by_role`.
- **Hook:** registered on `wp_dashboard_setup`.
- **Query:** posts with `post_status = future` for the allowed post types/categories.
