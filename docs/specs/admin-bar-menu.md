# Sitewide Admin Bar Menu — Functional Specification

**Availability:** Free
**Location:** WordPress **admin bar** (wp-admin and/or front end) → **"Scheduled Posts (N)"**.

## Summary

A toolbar menu listing all upcoming scheduled posts, grouped and paged, with a fully customizable item template. Lets editors jump to any scheduled post from anywhere.

## Settings & options

| Option (label) | Option key | Effect | Default |
|---|---|---|---|
| Show Scheduled Posts in Admin Bar | `is_show_admin_bar_posts` | Show the menu inside **wp-admin** | ON |
| Show Scheduled Posts in Sitewide Admin Bar | `is_show_sitewide_bar_posts` | Show the menu on the **front end** | ON |
| Item template | `adminbar_list_structure_template` | Markup/format of each listed item | `<strong>%TITLE%</strong> / %AUTHOR% / %DATE%` |
| Title length | `adminbar_list_structure_title_length` | Max characters of the title shown | `45` |
| Date format | `adminbar_list_structure_date_format` | PHP date format for each item | `M-d h:i:a` |
| Allow users | `allow_user_by_role` | Which roles see the menu | `['administrator']` |
| Show Post Types | `allow_post_types` | Which post types are listed | `['post']` |

## Behavior & rules

- Top item label: **"Scheduled Posts (N)"**, where **N** = total `future` posts across enabled post types.
- **Visibility:**
  - In **wp-admin**: shown when `is_show_admin_bar_posts` is ON.
  - On the **front end**: shown when `is_show_sitewide_bar_posts` is ON.
  - Always gated by the role allow-list.
- **Ordering:** posts are sorted **ascending by GMT date** (soonest first).
- **Paging/grouping:** items are chunked **8 per submenu group** (Sub 0, Sub 1, …) with prev/next paging arrows.
- **Item rendering:** each item renders `adminbar_list_structure_template` with these tokens:
  - `%TITLE%` — post title, truncated to `adminbar_list_structure_title_length` (default 45),
  - `%AUTHOR%` — author nicename,
  - `%DATE%` — formatted with `adminbar_list_structure_date_format` (default `M-d h:i:a`).
- Each item links to the post **edit screen**.
- A **"Powered By SchedulePress"** link appears at the end of the list.

## States shown

Only **scheduled (`future`)** posts of enabled post types.

## Interactions

- Same source data as the [dashboard widget](dashboard-widget.md) and [Calendar](schedule-calendar.md).
- Governed by [access control](access-control.md).

## Limits & edge cases

- HTML is allowed in the item template (styling supported); output is escaped for safe rendering of tokens.
- Very large scheduled queues remain browsable via the 8-per-group paging.
- The template tokens are the only supported placeholders; unknown tokens render literally.

## Technical touchpoints

- **Option keys:** `is_show_admin_bar_posts`, `is_show_sitewide_bar_posts`, `adminbar_list_structure_template`, `adminbar_list_structure_title_length`, `adminbar_list_structure_date_format`, `allow_user_by_role`, `allow_post_types`.
- **Hook:** `admin_bar_menu` (priority 1000).
- **Filter:** `wpsp_admin_bar_menu_posts` (adjust the listed posts).
- **Tokens:** `%TITLE%`, `%AUTHOR%`, `%DATE%`.
