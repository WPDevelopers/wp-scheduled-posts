# Admin UI kit (Tailwind + React)

The SchedulePress Settings app is a Tailwind + React application. It renders
its own shell, navigation and fields; **quickbuilder is used headlessly**, as a
state store only.

## How the app is put together

`SettingsWrapper` calls quickbuilder's `useBuilder(settings)` and puts the
result on `BuilderProvider`. Nothing else from quickbuilder is used — not
`FormBuilder`, not its field components, not its stylesheet. What we keep is
the part worth keeping:

- `builderContext.tabs` — the settings tree, sorted by priority
- `getFieldProps(field)` — a field's current value, `onChange`, pro-gating, and
  `visible` (its `rules` conditional logic, evaluated for us)
- `setFieldValue` / `setActiveTab` / `values`

`shell/SettingsApp` draws the chrome and `renderer/Renderer` walks the tree.
Settings save themselves as they change; the top bar reports the state.

## Where things live

| Path | Purpose |
| --- | --- |
| `includes/Admin/Settings/tailwind.config.js` | Design tokens + Tailwind guard rails |
| `includes/Admin/Settings/postcss.config.js` | PostCSS pipeline (Tailwind, autoprefixer, cssnano) |
| `includes/Admin/Settings/app/assets/css/tailwind.css` | `@tailwind` entry, scoped base reset, shared component classes |
| `includes/Admin/Settings/app/Settings/components/ui/` | The component kit |
| `includes/Admin/Settings/app/Settings/shell/` | Top bar, nav rail, app layout |
| `includes/Admin/Settings/app/Settings/renderer/` | The settings-tree renderer and its controls |

## Field types the renderer handles

Drawn directly: `section`, `group`, `tab` (sub-tabs), `toggle`, `text`,
`number`, `radio-card`, `html`. `action` resolves the WP filter it names — the
Pro plugin's license form arrives that way. Everything else routes through
`fields/Field.tsx` to our own field components, unchanged.

Two rules keep the layout sane: a section whose children all draw their own
card renders bare (no card-in-card), and a section with no label passes its
children straight through (unlabelled sections exist only to group fields for a
conditional rule).

Build with `npm run admin-start` (watch) or `npm run build` from
`includes/Admin/Settings/`. Output lands in `includes/Admin/Settings/assets/`
and is committed.

## Guard rails

wp-admin is a hostile CSS environment, so three settings are non-negotiable:

- **`prefix: 'tw-'`** — every utility is namespaced. `.hidden`, `.block`,
  `.container` and `.button` all already exist in wp-admin.
- **`preflight: false`** — Tailwind's reset is global and would strip the
  surrounding admin chrome. A scoped stand-in lives in `tailwind.css` under
  `#wpsp-dashboard-body` and `.wpsp-ui-portal`. That stand-in **must** keep the
  `border-width: 0; border-style: solid` pair: without it, any utility that
  sets a border style but not a width (`divide-solid`, `border-solid`) lets the
  remaining sides fall back to the CSS initial width of `medium`, and 3px lines
  appear on three sides of everything.
- **`important: true`** — wp-admin ships element selectors like
  `#wpbody-content a` and `input[type="text"]` that out-specify a single class.

Anything rendered through a portal (modals, popovers) sits outside
`#wpsp-dashboard-body`, so its root needs the `wpsp-ui-portal` class to pick up
the scoped base styles. Focusable elements get `wpsp-ui` so the shared
focus-ring rule can replace wp-admin's blue outline.

## Tokens

Defined under `theme.extend` in `tailwind.config.js` — use these rather than
raw hex values:

| Token | Value | Used for |
| --- | --- | --- |
| `brand-500` | `#6c62ff` | Primary actions, links, active nav |
| `brand-50` | `#f5f4ff` | Tinted backgrounds, icon chips |
| `ink` / `ink-strong` | `#232c3b` / `#141a24` | Body text / page headings |
| `ink-muted` | `#5b6779` | Secondary text |
| `ink-subtle` | `#8b97a8` | Icons, meta |
| `line` / `line-strong` | `#eaeef4` / `#dbe2ec` | Card borders / input borders |
| `canvas` / `canvas-sunken` | `#f7f8fb` / `#f1f3f8` | Page background / inset blocks |
| `success-500` | `#10b981` | Connected / published |
| `warning-500` | `#f59e0b` | Pro upsell, missed schedules |
| `danger-500` | `#ef4444` | Destructive actions, errors |
| `info-500` | `#3b82f6` | Neutral notices |

The brand violet is the only colour carried over from the previous design; the
neutrals are a cool grey ramp rather than the old navy. Radii run 6/10/14/18/24,
and shadows are layered and soft rather than a single hard edge.

The type scale is tightened to match the admin (`base` is 14px, not 16px).
`Inter` is the only font family.

## Components

Import from the barrel:

```tsx
import { Button, Card, SettingRow, Toggle } from '../components/ui';
```

| Component | Notes |
| --- | --- |
| `Button` / `ButtonLink` | `primary`, `secondary`, `outline`, `ghost`, `danger`, `warning`, `link`; `loading` swaps the left icon for a spinner. `ButtonLink` is a real `<a>` so middle-click still works |
| `IconButton` | Icon-only; `label` is required and becomes the accessible name |
| `Card` + `CardHeader/Title/Description/Body/Footer` | `tone="raised"` for a shadow instead of a border |
| `SettingRow` | The "label + description left, control right" row that makes up most settings screens; `isPro` adds the badge and dims the control |
| `SectionHeader` | Panel heading with optional icon and right-aligned actions |
| `FormField` | Label / hint / error wrapper around any control |
| `Input`, `Textarea`, `Select` | `Select` is a styled native `<select>`; keep `react-select` for async and multi-value cases |
| `Toggle`, `Checkbox`, `Radio` | Native inputs redrawn, since wp-admin draws its own tick glyph. `Toggle` takes `tone="success"` for connected/live states |
| `Badge` / `ProBadge` | Status pills; `dot` adds a leading status dot |
| `Alert` | Inline notice with tone, optional title, actions and dismiss |
| `Modal` | Portal-based, closes on Escape and overlay click, locks body scroll |
| `Tabs` | `underline`, `pill` and `vertical` variants |
| `Tooltip` | CSS-only, so the container must not clip overflow |
| `Avatar` | Falls back to initials; `badge` pins a platform icon for social profiles, `onImageError` swaps in your own fallback |
| `Skeleton`, `Spinner`, `EmptyState`, `Divider` | Loading and empty states |

## Migration status

The shell, navigation and every settings control are rendered by the kit. What
is still on the old SCSS is the interior of a few screens:

| Area | Partial | Notes |
| --- | --- | --- |
| Social connect modals | `_modals.scss` (~1.6k lines) | Biggest remaining surface. Scoped globally, so it still applies |
| Schedule calendar | `_calendar.scss`, part of `_content.scss` | FullCalendar's own DOM; its toolbar still relies on the `.wprf-tab-layout_calendar … .wprf-section-fields` chain, which is why the renderer keeps those class names |
| Scheduling hub panels | `_manageSchedule.scss`, `_advance-missedSchedule.scss` | Pro screens |
| MCP panel | `_mcp.scss` | Recently written; migrate after the modals |

`_openai.scss` was deleted. `_header.scss`, `_sidebar.scss`, `_license.scss`,
`_scheduling-hub.scss` and `_socialProfile.scss` are down to the rules that
target markup we do not own. Most of `_content.scss` and all of `_fields.scss`
are now unreachable — they hang off quickbuilder's tab chrome, which no longer
exists — but they are left in place rather than deleted, because the calendar
chain above proved how load-bearing some of those selectors still are.

## Migration rules

1. When a screen moves to the kit, delete the SCSS partial that styled it
   rather than leaving both in place.
2. The renderer emits `wprf-tab-<id>`, `wprf-control-section <name>` and
   `wprf-section-fields` on purpose. Do not drop them until the screens listed
   above are migrated — the calendar toolbar in particular is styled through
   that chain.
3. New markup uses utilities only. Reach for a SCSS partial solely for things
   Tailwind cannot express against a selector we do not own.
4. Do not add raw hex colours; extend the token set instead.
