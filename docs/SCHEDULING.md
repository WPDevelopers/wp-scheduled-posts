# Scheduling Posts

SchedulePress gives you several ways to schedule content. This guide explains each one and when to use it.

## The quick way — schedule in the editor

Every post/page editor gets a **SchedulePress** box with a **"Schedule And Share"** button. Click it to open the scheduling panel:

- **Publish On** — a date & time picker. Set a future time to schedule the post, or use **Now** to publish immediately.
- It reads the post's current date automatically, and works the same in the **block editor**, **classic editor**, and **Elementor**.

Then publish/update the post. That's it — the post is scheduled.

## Publish a scheduled post immediately

Sometimes you scheduled a post but want it out *now*. For any post already in **Scheduled** status you get a **"Publish future post immediately"** control with two choices:

- **Current Date** — publish right now, stamped with today's date and time.
- **Future Date** — publish right now, but keep showing the future date you'd chosen (useful for backdating/forward-dating the displayed date).

You'll find this in:
- the **Schedule And Share** panel (block editor / Elementor), and
- the **Publish box** of the classic editor (enable *Show Publish Post Immediately Button* in Settings → General).

## Plan visually — the Schedule Calendar

**SchedulePress → Calendar** is a drag-and-drop editorial calendar. You can:

- **Create a post** by clicking any date/time cell (it uses your *Calendar Default Schedule Time*).
- **Reschedule** by dragging a post to another day/time.
- **Edit** a post's title and category inline.
- **Delete** a post.
- **Filter** by category and post type.

It respects your site timezone, week start, and each user's edit/delete permissions — ideal for planning a multi-author editorial pipeline. A calendar is also available under each enabled post type's menu.

## How scheduling works (the basics)

- A post with a **future date** is saved as **Scheduled** (`future` status) and WordPress publishes it automatically at that time.
- SchedulePress shows all your scheduled posts in three places: the **Calendar**, the **dashboard widget**, and the **admin bar** menu.
- When a scheduled post publishes, any enabled **social sharing** runs automatically — see [Connecting Social Accounts](SOCIAL-SETUP.md).

## Advanced scheduling (Pro)

These automate scheduling further and are unlocked with SchedulePress Pro (**Settings → Scheduling Hub**):

| Feature | What it does |
|---|---|
| **Auto Scheduler** | Automatically drops your queued posts into time slots across the week, respecting a per-weekday post limit and a daily time window. |
| **Manual Scheduler** | You define preferred time slots per weekday; SchedulePress fills the next open ones. |
| **Advanced Schedule** | Schedule an **update** to an already-published post to go live later — without unpublishing it. |
| **Republish / Unpublish** | Automatically take a post down and/or bring it back on set dates. |
| **Missed Schedule** | Auto-publishes posts that WordPress missed at their scheduled time (fixes the classic "missed schedule" error). |

See the **[Pro feature guide](../../wp-scheduled-posts-pro/docs/FEATURES.md)** for details.

## Troubleshooting

- **A scheduled post didn't publish on time?** This is WordPress's built-in cron missing the moment (common on low-traffic sites). SchedulePress **Pro's Missed Schedule** handler fixes this automatically. See [FAQ](FAQ.md).
- **Wrong publish time?** Check **Settings → General → Timezone** in WordPress and your *Calendar Default Schedule Time*.
- **Can't see the SchedulePress panel?** Make sure the post type is enabled in **Settings → General → Show Post Types**, and your role is in **Allow users**.
