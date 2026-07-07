# Schedule Calendar — Functional Specification

**Availability:** Free
**Location:** **SchedulePress → Calendar**, and a **Calendar** submenu under each enabled post type.
**Built on:** a drag-and-drop calendar (FullCalendar), backed by REST namespace `wpscp/v1`.

## Summary

A visual editorial calendar for creating, rescheduling, editing, and deleting posts. Gives multi-author teams an at-a-glance view of what publishes when.

## Settings & options

| Option (label) | Option key | Effect | Default |
|---|---|---|---|
| Calendar Default Schedule Time | `calendar_schedule_time` | Time assigned to a post created by clicking a day cell | `00:00` |
| Show Post Types | `allow_post_types` | Which post types appear/can be created on the calendar | `['post']` |
| Show Categories | `allow_categories` | Restricts which categories are listed/creatable | `['all']` |
| Allow users | `allow_user_by_role` | Which roles can open and act on the calendar | `['administrator']` |

Calendar rendering also respects WordPress **timezone** and **week-start day** (Settings → General).

## Behavior & rules

### Create
- Clicking a date/time cell creates a new post at that slot. If no time is implied by the cell, `calendar_schedule_time` is applied.
- The new post uses the current post type context and respects `allow_categories`.

### Reschedule (drag & drop)
- Dragging an event moves the post's `post_date`/status to the drop target.
- The move is permission-checked server-side (`edit_post` on that post); disallowed moves are rejected.

### Edit (inline / quick-edit)
- Title and category can be edited inline from the calendar.
- Editing is permission-checked (`edit_post`).

### Delete
- An event can be deleted from the calendar; permission-checked (`delete_post`).

### Filter
- Filter the view by **category** and by **post type** (only post types in `allow_post_types`).

## States shown

The calendar surfaces posts by status for planning: **scheduled (`future`)**, and depending on view, **draft** and **published** items for editorial overview. Only enabled post types and permitted categories are shown.

## Interactions

- Newly created/scheduled items follow the normal **[scheduling](scheduling.md)** lifecycle (auto-publish, social share, notifications).
- Visibility is bounded by **[access control](access-control.md)** (post types, roles, categories).
- Pro adds drag-drop rescheduling of **already-published** posts and Advanced-schedule integration.

## Limits & edge cases

- A user who fails the role allow-list cannot open the calendar even if they can edit posts elsewhere.
- Timezone/week-start mismatches shift where events appear — configure WordPress timezone correctly.
- Per-event actions still enforce native capabilities, so an author cannot move/delete another user's post they lack rights to.

## Technical touchpoints

- **REST (namespace `wpscp/v1`):**
  - `GET /calendar` and an EDITABLE `/calendar` route (list/update events),
  - `POST /posts` (create),
  - `/post` — `GET` (quick-edit fetch), `EDITABLE` (update), `DELETABLE` (delete),
  - plus an additional `GET` route.
- **Permission callbacks:** per-method (`validate_user_post_access`, `permission_callback`, `edit_permission_callback`, `quick_edit_get_permission_callback`, `delete_permission_callback`) enforcing `edit_post`/`delete_post` + allowed post types.
- **Option keys:** `calendar_schedule_time`, `allow_post_types`, `allow_categories`, `allow_user_by_role`.
