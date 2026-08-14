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
| `brand-500` | `#6c62ff` | Primary actions, links, active states |
| `brand-50` | `#f3f2ff` | Tinted backgrounds, icon chips |
| `ink` | `#1b1b50` | Headings and body text |
| `ink-muted` | `#6e6e8d` | Secondary text |
| `ink-subtle` | `#989fab` | Icons, meta |
| `line` / `line-strong` | `#ebeef5` / `#e1e5e9` | Card borders / input borders |
| `canvas` | `#f9fafc` | Page background |
| `success-500` | `#02ac6e` | Connected / published |
| `warning-500` | `#ff9437` | Pro upsell, missed schedules |
| `danger-500` | `#dc3545` | Destructive actions, errors |

The type scale is tightened to match the existing admin (`base` is 14px, not
16px). `Inter` is the only font family.

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

Done — these render entirely from the kit, and the SCSS that described their
markup has been removed:

- `Settings/Header.tsx`, `Settings/Sidebar.tsx`
- `fields/License.tsx`, `fields/OpenAI.tsx`, `fields/Features.tsx`,
  `fields/ScheduleHubFeature.tsx`
- `fields/utils/MainProfile.tsx`, `fields/utils/SelectedProfile.tsx`,
  `fields/utils/ProAlert.tsx`, `fields/utils/ViewMore.tsx`,
  `fields/utils/Verification.tsx`

Still on SCSS, roughly in descending order of size:

| Area | Partial | Notes |
| --- | --- | --- |
| Social connect modals | `_modals.scss` (~1.6k lines) | Biggest remaining surface. The shell (`.modal_wrapper`, `.modalhead`, `.modalbody`) and every per-platform list live together, so this wants one focused pass with the modals actually open in a browser |
| Schedule calendar | `_calendar.scss` | FullCalendar's own DOM; expect to keep overrides rather than replace them |
| Tab chrome / layout | `_content.scss` | quickbuilder's markup — override only |
| MCP panel | `_mcp.scss` | Recently written; migrate after the modals |
| Custom fields | `_fields.scss` | quickbuilder field wrappers |
| Manage / advanced / missed schedule | `_manageSchedule.scss`, `_advance-missedSchedule.scss` | Pro screens |

`_openai.scss` was deleted outright. `_header.scss`, `_sidebar.scss`,
`_license.scss`, `_scheduling-hub.scss` and `_socialProfile.scss` are reduced to
the rules that target markup we do not own.

## Migration rules

1. When a screen moves to the kit, delete the SCSS partial that styled it
   rather than leaving both in place.
2. Keep the existing root class names (`.wpsp-admin-header`,
   `.wpsp-admin-sidebar`, …) — quickbuilder's layout and the Pro plugin hook
   into some of them.
3. New markup uses utilities only. Reach for a SCSS partial solely for things
   Tailwind cannot express against a selector we do not own.
4. Do not add raw hex colours; extend the token set instead.
