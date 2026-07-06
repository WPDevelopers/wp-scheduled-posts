# SchedulePress (Free) — Feature Specifications

Detailed **functional specifications** for each feature: exact settings and their effects, behavior rules, states, edge cases, and the free/Pro boundary. These complement the plain-language [Feature Guide](../guides/FEATURES.md).

Each spec follows the same shape: **Summary → Settings & options → Behavior & rules → States → Interactions → Limits & edge cases → Technical touchpoints**.

## Specs

| # | Feature | Spec |
|---|---|---|
| 1 | Post scheduling (editor) | [scheduling.md](scheduling.md) |
| 2 | Schedule Calendar | [schedule-calendar.md](schedule-calendar.md) |
| 3 | Dashboard widget | [dashboard-widget.md](dashboard-widget.md) |
| 4 | Sitewide admin bar menu | [admin-bar-menu.md](admin-bar-menu.md) |
| 5 | Social sharing engine | [social-sharing.md](social-sharing.md) |
| 6 | Social templates | [social-templates.md](social-templates.md) |
| 7 | Custom per-post social messages | [custom-post-templates.md](custom-post-templates.md) |
| 8 | AI Caption (Write With AI) | [ai-caption.md](ai-caption.md) |
| 9 | Email notifications & review workflow | [email-notifications.md](email-notifications.md) |
| 10 | Access control (post types, roles, categories) | [access-control.md](access-control.md) |
| 11 | Settings & data model | [settings-data-model.md](settings-data-model.md) |

## Conventions used in these specs

- **Option key** — the internal setting name saved in the `wpsp_settings_v5` option (what the field maps to).
- **🔒 Pro** — the control exists in free but is locked; the effect only applies with SchedulePress Pro active.
- **Default** — the value seeded on a fresh install.
- **Technical touchpoints** — a light list of option keys, REST/AJAX endpoints, post meta, hooks, and statuses for developers. Not a code walkthrough.

Pro-only features are specified in the [Pro specs](../../../wp-scheduled-posts-pro/docs/specs/README.md).
