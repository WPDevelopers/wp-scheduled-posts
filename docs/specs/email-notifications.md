# Email Notifications & Review Workflow — Functional Specification

**Availability:** Free (also handles Pro `delayed_*` statuses)
**Location:** **Settings → Email Notify**.

## Summary

Sends HTML email alerts on post status changes and provides a lightweight editorial review/approval flow for multi-author sites. All notifications are **OFF by default**.

## Settings & options

| Notification (label) | Option key | Fires when status → | Recipients |
|---|---|---|---|
| Notify user on **Under Review** | `notify_author_post_is_review` | `pending` | Role / Username / Email |
| Notify author on **Rejected** | `notify_author_post_is_rejected` | `trash` | Post author |
| Notify user on **Scheduled** | `notify_author_post_is_scheduled` | `future` (and Pro `delayed_future`) | Role / Username / Email + author |
| Notify author when **Scheduled post Published** | `notify_author_post_scheduled_to_publish` | `publish` (and Pro `delayed_publish`) | Author |
| Notify author on **Published** | `notify_author_post_is_publish` | `publish` | Author |

Recipient selectors for review/scheduled notifications:
- `notify_author_post_review_by_role` / `_username` / `_email`
- `notify_author_post_scheduled_by_role` / `_username` / `_email`

## Message tokens

| Token | Renders |
|---|---|
| `%title%` | Post title |
| `%date%` | Post date |
| `%author%` | Post author |
| `%permalink%` | Post URL |

## Behavior & rules

- Notifications are driven by WordPress `transition_post_status` (priority 90) → an internal `wpsp_transition_post_status` action.
- A change where **old status == new status** is ignored (no email).
- A **10-second transient** (`wpsp_email_is_send_flag`) suppresses duplicate sends triggered within the same save.
- Emails are **HTML**; subjects/bodies use the tokens above.
- Only enabled (`== 1`) notifications send.

## Review / approval workflow

The Under-Review (`pending`) and Rejected (`trash`) notifications, combined with the [Calendar](schedule-calendar.md) and [dashboard widget](dashboard-widget.md) overviews, form a simple editorial loop:

1. Author submits → status `pending` → **reviewers notified**.
2. Reviewer approves/schedules → status `future` → **scheduled notification**; or rejects → status `trash` → **author notified**.
3. On publish → **published notification**.

## States → notification map

| Status transition | Notification |
|---|---|
| → `pending` | Under Review |
| → `trash` | Rejected |
| → `future` / `delayed_future` | Scheduled |
| → `publish` (from scheduled) | Scheduled post Published |
| → `publish` | Published |

## Limits & edge cases

- The 10s dedupe window can suppress a legitimate second email if two transitions happen within it.
- Recipient resolution depends on valid role/username/email inputs; empty selectors send only to the author where applicable.
- Pro `delayed_future` / `delayed_publish` statuses reuse the Scheduled / Published notifications.

## Technical touchpoints

- **Option keys:** as tabled above (`notify_author_post_is_review`, `notify_author_post_is_rejected`, `notify_author_post_is_scheduled`, `notify_author_post_scheduled_to_publish`, `notify_author_post_is_publish`, plus `_by_role|_username|_email` recipients).
- **Hooks:** `transition_post_status` (priority 90), `wpsp_transition_post_status`.
- **Transient:** `wpsp_email_is_send_flag` (10s dedupe).
- **Tokens:** `%title%`, `%date%`, `%author%`, `%permalink%`.
