# Post Scheduling (Editor) — Functional Specification

**Availability:** Free (advanced auto/manual/missed scheduling is 🔒 Pro — see [Pro specs](../../../wp-scheduled-posts-pro/docs/specs/README.md))
**Location:** Post/page editor → **SchedulePress** box → **"Schedule And Share"** panel; and the Classic editor Publish box.
**Applies to:** every post type enabled in Settings → General → *Show Post Types*.

## Summary

Lets an author set (or change) a post's publish date/time and, for already-scheduled posts, publish them immediately with a choice of displayed date. Works identically across the **block editor**, **classic editor**, and **Elementor**.

## Settings & options

| Option (label) | Option key | Effect | Default |
|---|---|---|---|
| Show Publish Post Immediately Button | `show_publish_post_button` | Adds the "Publish future post immediately" control to the Classic editor submit box | ON |
| Show Scheduled Posts in Elementor | `show_on_elementor_editor` | Enables the Schedule And Share panel inside Elementor | ON |
| Calendar Default Schedule Time | `calendar_schedule_time` | Default time applied when a date is chosen without a time | `00:00` |

## Behavior & rules

### Publish On (schedule/reschedule)
- The **Publish On** date-time picker reads the post's current date from the active editor (block editor store, classic DOM field, or the page-builder global) so it always reflects the true post date.
- Setting a **future** date/time saves the post as **Scheduled** (`future`); WordPress publishes it at that time.
- The **"Now"** shortcut sets the current time (publish immediately). A clear control resets the picker.

### Publish future post immediately
Shown **only when the post is already in `future` status**. Two mutually-exclusive actions:

| Action | Result |
|---|---|
| **Current Date** | Post publishes now, `post_date` stamped to the current time. |
| **Future Date** | Post publishes now (`post_status` → `publish`) but the **displayed date stays the chosen future date**. |

Rules:
- Selecting either action detaches WordPress's future-publish hook so the post goes live immediately.
- In the block editor/Elementor these call the panel's save action and show a confirmation toast, then close the panel.
- In the Classic editor the control is a checkbox in the Publish box with a **Current Date / Future Date** radio, plus an inline help tooltip. It is gated by `show_publish_post_button`.

### Cross-editor support
- **Block editor / Elementor:** the full-screen Schedule And Share panel.
- **Classic editor:** the Publish-box checkbox + a hidden manual-date field that can set the post date directly.

## States

| State | Meaning |
|---|---|
| Draft/Pending | Not scheduled; no auto-publish. |
| **Scheduled (`future`)** | Has a future date; auto-publishes at that time; appears in Calendar, dashboard widget, admin bar. |
| Published (`publish`) | Live. If reached via "Future Date", the shown date may be in the future. |

## Interactions

- On transition to `future`, the **[email notifications](email-notifications.md)** "Scheduled" trigger may fire.
- On publish, the **[social sharing engine](social-sharing.md)** auto-shares to enabled profiles.
- Scheduled posts are surfaced in the **[Calendar](schedule-calendar.md)**, **[dashboard widget](dashboard-widget.md)**, and **[admin bar menu](admin-bar-menu.md)**.
- Advanced automation (Auto/Manual scheduler, Missed schedule, Republish/Unpublish, Advanced schedule) is **🔒 Pro**.

## Limits & edge cases

- Publish times honor the **site timezone** (WordPress → Settings → General). A mismatched timezone is the usual cause of "wrong time" reports.
- If the post type is not enabled or the user's role is not allowed, the panel does not appear (see [access control](access-control.md)).
- "Future Date" publishing intentionally leaves a future `post_date` on a live post — themes that hide future-dated content should be tested.

## Technical touchpoints

- **Option keys:** `show_publish_post_button`, `show_on_elementor_editor`, `calendar_schedule_time`.
- **REST:** post-panel updates via `wp-scheduled-posts/v1/update-settings/{post_id}` (and Pro settings updates `publish_immediately_current_date` / `publish_immediately_future_date`).
- **Status:** WordPress core `future` → `publish`; future-publish hook detached on immediate publish.
- **Editors:** block editor, Classic editor Publish box, Elementor panel.
