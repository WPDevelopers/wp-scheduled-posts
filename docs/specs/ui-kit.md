# Admin UI kit (Tailwind + React)

The SchedulePress Settings app is being moved off hand-written SCSS onto a
Tailwind-based component kit. This spec covers how the kit is set up and the
rules to follow while migrating the remaining screens.

## Where things live

| Path | Purpose |
| --- | --- |
| `includes/Admin/Settings/tailwind.config.js` | Design tokens + Tailwind guard rails |
| `includes/Admin/Settings/postcss.config.js` | PostCSS pipeline (Tailwind, autoprefixer, cssnano) |
| `includes/Admin/Settings/app/assets/css/tailwind.css` | `@tailwind` entry + scoped base reset |
| `includes/Admin/Settings/app/Settings/components/ui/` | The component kit |

Build with `npm run admin-start` (watch) or `npm run build` from
`includes/Admin/Settings/`. Output lands in `includes/Admin/Settings/assets/`
and is committed.

## Guard rails

wp-admin is a hostile CSS environment, so three settings are non-negotiable:

- **`prefix: 'tw-'`** — every utility is namespaced. `.hidden`, `.block`,
  `.container` and `.button` all already exist in wp-admin.
- **`preflight: false`** — Tailwind's reset is global and would strip the
  surrounding admin chrome. A scoped stand-in lives in `tailwind.css` under
  `#wpsp-dashboard-body` and `.wpsp-ui-portal`.
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
| `Toggle`, `Checkbox`, `Radio` | Native inputs redrawn, since wp-admin draws its own tick glyph |
| `Badge` / `ProBadge` | Status pills; `dot` adds a leading status dot |
| `Alert` | Inline notice with tone, optional title, actions and dismiss |
| `Modal` | Portal-based, closes on Escape and overlay click, locks body scroll |
| `Tabs` | `underline`, `pill` and `vertical` variants |
| `Tooltip` | CSS-only, so the container must not clip overflow |
| `Avatar` | Falls back to initials; `badge` pins a platform icon for social profiles |
| `Skeleton`, `Spinner`, `EmptyState`, `Divider` | Loading and empty states |

## Migration rules

1. When a screen moves to the kit, delete the SCSS partial that styled it
   rather than leaving both in place.
2. Keep the existing root class names (`.wpsp-admin-header`,
   `.wpsp-admin-sidebar`, …) — quickbuilder's layout and the Pro plugin hook
   into some of them.
3. New markup uses utilities only. Reach for a SCSS partial solely for things
   Tailwind cannot express against a selector we do not own.
4. Do not add raw hex colours; extend the token set instead.
