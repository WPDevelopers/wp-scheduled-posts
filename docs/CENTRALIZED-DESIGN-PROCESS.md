# SchedulePress — Centralized Design Process

> A single, editor-agnostic React modal that centralizes every SchedulePress setting (scheduling, social share, social templates, share now) across **Gutenberg, Classic Editor, Elementor, and other page builders**.

This document describes the architecture, data flow, extension points, and integration model of the centralized modal system that lives under [src/](../src/).

---

## 1. Goals

Before centralization, each editor (Gutenberg sidebar, Classic meta box, Elementor panel, etc.) had its own duplicated UI for SchedulePress features. The centralized design process unifies all of that into **one React app**, mounted into a single DOM root, that:

- Detects the active editor at runtime and reads the post data from whichever source is available.
- Renders the same UI everywhere — Scheduling, Social Share, Share Now, Social Templates.
- Manages all state through a single React Context store.
- Gates Pro features through a reusable overlay pattern.
- Extends cleanly via WordPress hooks so the Pro plugin can inject its own components.

---

## 2. Top-Level Layout

```
src/
├── index.js              # ReactDOM render entry
├── App.js                # Root component (wraps AppProvider)
├── context/              # Global state (Context + Reducer)
├── components/
│   ├── common/           # Modal shell (Header / Content / Footer)
│   ├── Settings/         # Scheduling tab (ScheduleOn, ManageSchedule, SchedulingOptions)
│   ├── Content/          # Social Share tab + Share Now
│   └── modals/           # Overlay modals (Pro popup, Social Templates)
│       └── socialTemplates/
│           ├── hooks/    # useCurrentPostData, useSocialProfiles
│           └── utils/
├── helper/               # API helpers + useProOverlay hook
├── icons/                # Inline SVG components
├── scss/                 # Source styles
└── css/                  # Compiled (minified) styles
```

---

## 3. Boot & Editor Detection

**Entry point:** [src/index.js](../src/index.js)

The app is mounted via `wp.element.render()` into the DOM root `#wpsp-post-panel-react-root`. The root element is injected by the PHP side (free plugin) for whichever editor is currently active, which is why the same React tree works everywhere.

**Editor detection happens at runtime** — there is no per-editor build. Components ask for post data in this priority order:

1. **Gutenberg** — `wp.data.select('core/editor')` (post id, date, title, content, featured image).
2. **Classic Editor** — DOM fields: `#post_ID`, `#title`, `#content`, plus `window.tinymce` for live content.
3. **Elementor** — `window.elementor` document model.
4. **Fallback** — globals `window.WPSchedulePostsFree` (free plugin) and `window.WPSchedulePosts` (Pro), localized from PHP.

The fallback globals carry: `current_post_id`, `current_post_status`, `current_post_date`, `current_post_title`, `current_post_content`, `current_post_url`, `current_post_featured_image`, `is_pro`, `assetsURI`, `nonce`, `social_media_enabled`, `socialProfileURL`.

> See [useCurrentPostData.js](../src/components/modals/socialTemplates/hooks/useCurrentPostData.js) for the canonical reference implementation of the detection cascade.

---

## 4. Global State

State lives in a Context + Reducer pair:

- [context/AppContext.js](../src/context/AppContext.js) — exposes `{ state, dispatch }` via `AppProvider`.
- [context/AppReducer.js](../src/context/AppReducer.js) — pure reducer, spread-based immutable updates.
- [context/initialState.js](../src/context/initialState.js) — default shape.

### State shape

```js
{
  isOpenCustomSocialMessageModal: false,   // Social template editor open?
  isOpenProPopup: false,                    // Upgrade prompt open?

  // Scheduling
  publishImmediately: false,
  isScheduled: false,
  scheduleType: '',
  scheduleDate: '',
  unpublishOn: '',                          // Pro
  republishOn: '',                          // Pro
  advancedSchedule: false,                  // Pro
  advancedScheduleDate: '',                 // Pro

  // Social share
  socialShareSettings: {
    isSocialShareDisabled: false,
    socialBannerId: null,
    socialBannerUrl: '',
  },
}
```

### Action types

| Action | Purpose |
| --- | --- |
| `SET_CUSTOM_SOCIAL_MESSAGE_MODAL` | Open/close the social template editor |
| `SET_OPEN_PRO_POPUP` | Open/close the upgrade popup |
| `SET_PUBLISH_IMMEDIATELY` | Toggle "publish immediately" |
| `SET_IS_SCHEDULED` / `SET_SCHEDULE_TYPE` / `SET_SCHEDULE_DATE` | Default schedule fields |
| `SET_UNPUBLISH_ON` / `SET_REPUBLISH_ON` | Publishing cycle (Pro) |
| `SET_ADVANCED_SCHEDULE` / `SET_ADVANCED_SCHEDULE_DATE` | Advanced schedule (Pro) |
| `SET_SOCIAL_SHARE_SETTINGS` | Disable social share + banner |

Consume the store anywhere with:

```js
const { state, dispatch } = useContext(AppContext);
```

---

## 5. Modal Shell

The visible modal is composed from three shared pieces in [components/common/](../src/components/common/):

| File | Responsibility |
| --- | --- |
| [Header.js](../src/components/common/Header.js) | Branded top bar (SchedulePress logo) |
| [Content.js](../src/components/common/Content.js) | Two-column body — Scheduling (left, ~65%) + Social Share (right, ~35%) |
| [Footer.js](../src/components/common/Footer.js) | "Save Changes" button — dispatches dual REST calls (schedule + social) |

The shell is identical across every editor; only the data sources behind it change.

---

## 6. Scheduling Tab

Rendered from [components/Content/Settings.js](../src/components/Content/Settings.js), composed of three cards. Each card is exposed via a WordPress hook so the **Pro plugin can filter or replace** any section.

| Card | File | Description | Pro? |
| --- | --- | --- | --- |
| Default Schedule | [ScheduleOn.js](../src/components/Settings/ScheduleOn.js) | "Publish On" with `DateTimePicker` popover. Hydrates from Gutenberg `getEditedPostAttribute('date')`, Classic DOM, or globals. Distinguishes a user-set date from a draft default. | Free |
| Manage Schedule | [ManageSchedule.js](../src/components/Settings/ManageSchedule.js) | Auto Schedule / Manual Schedule inputs. | **Pro** |
| Publishing Cycle | [SchedulingOptions.js](../src/components/Settings/SchedulingOptions.js) | Unpublish On, Republish On, Advanced Schedule toggle. | **Pro** |

Pro-only cards render through `useProOverlay` (see §9), so the free version shows them visually but disabled, with a click handler that opens the upgrade popup.

---

## 7. Social Share Tab

Rendered from [components/Content/SocialShare.js](../src/components/Content/SocialShare.js). It exposes:

- **Disable social share** checkbox — persists to meta `_wpscppro_dont_share_socialmedia`.
- **Custom social banner** uploader (WordPress media library) — persists to meta `_wpscppro_custom_social_share_image`. Falls back to the post's featured image when empty.
- **Selected Social Platforms** — cards for each enabled platform showing the connected profile thumbnails (up to 5 visible, then a `+N` count badge).
- **Add / Edit Social Message** — opens the Social Templates modal (§8).
- **Share Now** — [ShareNowButton.js](../src/components/Content/ShareNowButton.js) calls `/wp-scheduled-posts/v1/instant-social-share` and opens [ShareNowStatusModal.js](../src/components/Content/ShareNowStatusModal.js), which streams per-profile status (pending → success/error) live.

---

## 8. Social Templates Modal

A full-screen overlay for authoring per-platform messages. Lives in [components/modals/socialTemplates/](../src/components/modals/socialTemplates/).

| File | Responsibility |
| --- | --- |
| [CustomTemplateModal.js](../src/components/modals/socialTemplates/CustomTemplateModal.js) | Modal root — orchestrates the children below |
| [Header.js](../src/components/modals/socialTemplates/Header.js) | Title + close |
| [PlatformNavigation.js](../src/components/modals/socialTemplates/PlatformNavigation.js) | Facebook · Twitter · LinkedIn · Pinterest · Instagram · Medium · Threads · Google Business |
| [ProfileSelector.js](../src/components/modals/socialTemplates/ProfileSelector.js) | Multi-select connected profiles for the active platform |
| [TemplateEditor.js](../src/components/modals/socialTemplates/TemplateEditor.js) | Message editor with per-platform character limits (Twitter 280, LinkedIn 1300, Instagram 2100, …) |
| [ScheduleControls.js](../src/components/modals/socialTemplates/ScheduleControls.js) | Relative or absolute schedule for this template |
| [PreviewCard.js](../src/components/modals/socialTemplates/PreviewCard.js) | Live preview using current post data |
| [AllDisabledPlatform.js](../src/components/modals/socialTemplates/AllDisabledPlatform.js) | Empty state when no platforms are connected |

### Hooks

- [useSocialProfiles.js](../src/components/modals/socialTemplates/hooks/useSocialProfiles.js) — fetches `/wp-scheduled-posts/v1/get-option-data`, deduplicates profiles per platform, handles Pinterest board/section nesting.
- [useCurrentPostData.js](../src/components/modals/socialTemplates/hooks/useCurrentPostData.js) — `useSelect`-based reader that resolves the active post from Gutenberg / Classic / Elementor / globals.

---

## 9. Pro Gating Pattern — `useProOverlay`

[helper/useProOverlay.js](../src/helper/useProOverlay.js) is the single source of truth for Pro detection.

```js
const { isPro, openProPopup, proOverlay, itemStyle } = useProOverlay();
```

- `isPro` — `window.WPSchedulePostsFree.is_pro` boolean.
- `openProPopup()` — dispatches `SET_OPEN_PRO_POPUP`.
- `proOverlay` / `itemStyle` — a transparent overlay + dimmed style that you spread over any Pro-only card. Click anywhere on the card → upgrade popup opens.

The popup itself lives at [components/modals/ProPopup.js](../src/components/modals/ProPopup.js) and is mounted by [components/modals/Modals.js](../src/components/modals/Modals.js).

---

## 10. REST Endpoints

| Method | Endpoint | Used by |
| --- | --- | --- |
| `POST` | `/wp-scheduled-posts/v1/social-settings/{postId}` | Footer save — social disable + banner |
| `POST` | `/wp-scheduled-posts/v1/post-panel/{postId}` | Footer save — schedule, unpublish, republish, advanced schedule |
| `GET`  | `/wp-scheduled-posts/v1/custom-templates/{postId}` | Selected profiles per platform |
| `GET`  | `/wp-scheduled-posts/v1/get-option-data` | All connected social profiles |
| `POST` | `/wp-scheduled-posts/v1/fetch_pinterest_section` | Pinterest board / section list |
| `GET`  | `/wp-scheduled-posts/v1/instant-social-share` | Share Now (params: `id`, `platform`, `postid`, `nonce`) |

Wrappers in [helper/helper.js](../src/helper/helper.js):

- `fetchSocialProfileData(url, queryParams, customQuery)` — thin `wp.apiFetch` wrapper.
- `fetchPinterestSection(body)` — POST helper for Pinterest section lookup.

---

## 11. Extension Points (for the Pro plugin)

The centralized modal is built so the Pro plugin never has to fork a component. Instead it:

1. **Filters tab content** through the WordPress hook points used in [Content/Settings.js](../src/components/Content/Settings.js) (e.g. `wpsp_schedule_on`, `wpsp_manage_schedule`, `wpsp_schedule_options`).
2. **Adds new action types** by extending the reducer (kept open-ended via spread updates).
3. **Adds REST handlers** that respond on the same `wp-scheduled-posts/v1` namespace — the Footer save call already dispatches to both endpoints, so Pro fields piggyback automatically.

This means a Pro release can ship new scheduling features by registering a filter — no React fork, no duplicate modal.

---

## 12. Styling

- Source: [src/scss/styles.scss](../src/scss/styles.scss)
- Compiled: [src/css/styles.min.css](../src/css/styles.min.css)

Key BEM-ish roots:

| Selector | Scope |
| --- | --- |
| `.wpsp-post-panel-wrapper` | Outer mount wrapper |
| `.wpsp-post-panel` | 65 / 35 grid (settings / sidebar) |
| `.wpsp-modal--header`, `.wpsp-modal--footer` | Shell chrome |
| `.wpsp-post--card` | Card container inside any tab |
| `.wpsp-post-panel-modal-settings` | Scheduling tab |
| `.wpsp-modal-social-share` | Social Share tab |
| `.wpsp-pro-option`, `.wpsp-post-items` | Pro overlay states |
| `.wpsp-date--picker` | DateTimePicker popover |
| `.wpsp-custom-template-modal` | Social Templates overlay |
| `.wpsp-pro-popup-overlay`, `.wpsp-pro-popup` | Upgrade popup |
| `.wpsp-social-platforms-cards`, `.wpsp-social-card` | Profile thumbnails |
| `.wpsp-custom-toast` | Toast notifications |

---

## 13. Data Flow at a Glance

```
            ┌────────────────────────────────────────────┐
            │  Editor (Gutenberg / Classic / Elementor)  │
            └────────────────────┬───────────────────────┘
                                 │  (post id, date, title, content, image)
                                 ▼
                  ┌──────────────────────────┐
                  │   useCurrentPostData     │
                  └──────────────┬───────────┘
                                 │
                                 ▼
           ┌─────────────────────────────────────────┐
           │   AppContext  (state + dispatch)        │
           └──────┬───────────────────────────┬──────┘
                  │                           │
        ┌─────────▼─────────┐       ┌─────────▼──────────┐
        │  Scheduling Tab   │       │  Social Share Tab  │
        │  (3 hookable      │       │  + Share Now       │
        │   sub-cards)      │       │  + Templates Modal │
        └─────────┬─────────┘       └─────────┬──────────┘
                  │                           │
                  └────────────┬──────────────┘
                               ▼
                    ┌──────────────────────┐
                    │  Footer "Save"       │
                    │  POST /post-panel    │
                    │  POST /social-settings│
                    └──────────────────────┘
```

---

## 14. Adding a New Feature — Checklist

1. **State** — add a key to [initialState.js](../src/context/initialState.js) and a matching action in [AppReducer.js](../src/context/AppReducer.js).
2. **UI** — drop a new card under `components/Settings/` (scheduling) or `components/Content/` (social) and import it from the matching parent.
3. **Pro gating** — if it's a Pro feature, wrap it with `useProOverlay`'s overlay/style.
4. **Persistence** — extend the relevant Footer save call, or add a new REST endpoint on the `wp-scheduled-posts/v1` namespace.
5. **Editor support** — if you need new post data, extend [useCurrentPostData.js](../src/components/modals/socialTemplates/hooks/useCurrentPostData.js) rather than reading editor APIs in your component. That's what keeps the modal editor-agnostic.

---

## 15. File Reference (Quick Index)

- Boot: [index.js](../src/index.js) · [App.js](../src/App.js)
- State: [context/AppContext.js](../src/context/AppContext.js) · [AppReducer.js](../src/context/AppReducer.js) · [initialState.js](../src/context/initialState.js)
- Shell: [common/Header.js](../src/components/common/Header.js) · [common/Content.js](../src/components/common/Content.js) · [common/Footer.js](../src/components/common/Footer.js)
- Scheduling: [Settings/ScheduleOn.js](../src/components/Settings/ScheduleOn.js) · [Settings/ManageSchedule.js](../src/components/Settings/ManageSchedule.js) · [Settings/SchedulingOptions.js](../src/components/Settings/SchedulingOptions.js)
- Social: [Content/SocialShare.js](../src/components/Content/SocialShare.js) · [Content/ShareNowButton.js](../src/components/Content/ShareNowButton.js) · [Content/ShareNowStatusModal.js](../src/components/Content/ShareNowStatusModal.js)
- Modals: [modals/Modals.js](../src/components/modals/Modals.js) · [modals/ProPopup.js](../src/components/modals/ProPopup.js) · [modals/SocialTemplates.js](../src/components/modals/SocialTemplates.js)
- Templates: [modals/socialTemplates/CustomTemplateModal.js](../src/components/modals/socialTemplates/CustomTemplateModal.js) and siblings
- Helpers: [helper/helper.js](../src/helper/helper.js) · [helper/useProOverlay.js](../src/helper/useProOverlay.js)
- Styles: [scss/styles.scss](../src/scss/styles.scss)
