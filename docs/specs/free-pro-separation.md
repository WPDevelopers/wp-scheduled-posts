# Free / Pro Separation — Implementation Spec

**Status:** implemented in 5.4.0 · SchedulePress 5.4.0 + SchedulePress Pro 5.4.0
**Repos:** `wp-scheduled-posts` (Free) · `wp-scheduled-posts-pro` (Pro)

## Summary

Google Business Profile is marketed as a Pro feature (`readme.txt:26`) but is
implemented end to end in Free, gated only by a client-side check. This spec moves
the implementation into Pro behind a **generic social-platform registry**, leaves a
complete upsell surface in Free, and guarantees no fatal error on any Free/Pro
version combination.

## Audit result (what is and is not misplaced)

Already correct — Free carries only `is_pro => true` settings definitions, the
implementations live in Pro:

Missed Schedule · Auto/Manual Scheduler · Advanced Schedule · Republish/Unpublish ·
Elementor section schedule · publish-now-with-future-date.

Multi-profile sharing is correctly gated server-side in `includes/Helper.php:401`
(`wpsp_social_profile_limit_checkpoint` + `array_slice($profile, 0, 1)`).

**The only leak is Google Business Profile** — 165 references across 27 files in Free.
Today's split: Pro builds the OAuth consent URL; Free does token exchange
(`SocialProfile.php:837-901`), the 561-line engine, cron, reconnect, settings UI and
post panel. Free's `add_social_profile()` has no `google_business` branch and falls
through silently, so Pro's callback (registered second) is the only one that emits.

Two bugs found along the way, fixed as part of this work:

- `includes/Admin/Calendar.php:9,879` — Free `use`s and instantiates
  `WPSP_PRO\Scheduled\Published` unguarded. Fatal when Pro is absent.
- `Admin::wpsp_el_tab_action()` (`includes/Admin.php:1282-1300`) never reads
  `wpsp_el_social_google_business[]`, so the Elementor custom-profile selection for
  Google Business was never persisted.

## Decisions

1. Google Business is removed from Free **outright**. No data-migration notice for
   free users; saved settings and post meta are left in place untouched.
2. **No fatal errors on any version mismatch.** See the matrix below.
3. Free gains a **generic** "update SchedulePress Pro" admin notice driven by a
   version constant, not a Google-Business-specific message. Future feature moves
   bump the constant only.
4. Free keeps a **complete locked upsell surface** for Google Business on every
   screen where it appears today.

## Version mismatch matrix

Free-old = 5.3.4, Free-new = 5.4.0; Pro-old = 5.3.3, Pro-new = 5.4.0.

| Cell | Hazard | Guard |
|---|---|---|
| Free-old + Pro absent / Pro-old | status quo | none needed |
| **Free-old + Pro-new** | two GB engines both hook `wpsp_publish_future_post`, `wpsp_schedule_republish_share`, `wpsp_google_business_token_refresh` and `update_option_wpsp_settings_v5` → double share, double token refresh | Pro's `free_owns_platform()` keeps the new engine dormant; Pro reverts to 5.3.3 stub behaviour (OAuth URL only). Pro React: `if (!window.wpspSettingsApp) return ret;` |
| **Free-new + Pro absent** | every removed symbol is a PHP 8 `Error`: `WPSP\Social\GoogleBusiness`, `WPSCP_GOOGLE_BUSINESS_OPTION_NAME`, `WPSCP_GOOGLE_BUSINESS_SCOPE`, `WPSP_SOCIAL_OAUTH2_GOOGLE_BUSINESS_APP_ID`, `SocialProfile::handle_google_business_profile_changes/getGoogleMyBusinessProfile*/fetchLocations/fetchProfilePictureUrl`. Orphaned crons. `Calendar.php` fatal | removal-commit grep gate (below); crons left in place (`do_action` with no listener is a no-op, hook name unchanged so Pro re-attaches on install); `Calendar.php` fix |
| **Free-new + Pro-old** | Pro-old calls `WPSPHelper::is_user_allow()` and `\WPSP\Social\OAuthPopup::return_url()` — both survive. No fatal. GB simply non-functional | generic version notice + "update Pro" state on the locked card |
| Free-new + Pro-new | target state | — |

`free_owns_platform()` in Pro:

```php
!defined('WPSP_VERSION')
|| version_compare(WPSP_VERSION, WPSP_PRO_MIN_FREE_VERSION, '<')
|| class_exists('\WPSP\Social\GoogleBusiness');
```

This also makes release order irrelevant and keeps the intermediate commit window
(P1 landed, F2 not yet) safe.

Removal-commit gate — this must return only `Platforms::PRO_UPSELL` data, placeholder
field/tab definitions, sass selectors and image filenames:

```
grep -rn -i "google_business\|googlebusiness\|GOOGLE_BUSINESS" \
  includes wp-scheduled-posts.php src --exclude-dir=Deps
```

## Seam design (Free)

One registry plus a small number of dispatch points, so the next platform Pro adds
needs no Free changes at all.

### Registry — new `includes/Social/Platforms.php` (`WPSP\Social\Platforms`)

```php
public static function registered(): array   // apply_filters('wpsp_social_platforms', []) + validation
public static function get(string $slug): ?array
public static function slugs(): array
public static function list_keys(): array    // slug => list_key
public static function status_keys(): array  // slug => status_key
public static function limits(): array       // slug => char_limit
public static function labels(): array       // slug => label
public static function for_js(): array       // live, Pro-registered platforms
public static function locked_for_js(): array // PRO_UPSELL minus anything live
```

No request-level caching in `registered()` — it is read after `init` by every
consumer, and caching before Pro's `init` callback would freeze an empty list.

Definition shape (validated: `label`, `list_key`, `status_key` required):

```php
'google_business' => [
    'label'      => 'Google Business Profile',
    'list_key'   => 'google_business_profile_list',
    'status_key' => 'google_business_profile_status',
    'char_limit' => 1500,
    'limit_key'  => 'note_limit',
    'color'      => '#db4437',
    'icon_url'   => WPSP_ASSETS_URI . 'images/google_business.svg',
    'icon_bg_url'=> WPSP_ASSETS_URI . 'images/google-my-business-logo-small.png',
    'reconnect'  => [ 'lead_time' => 12 * HOUR_IN_SECONDS, 'token_endpoint' => '...', 'shared_app_id' => '...' ],
    'automatic_connect' => true,
],
```

`PRO_UPSELL` is a marketing constant listing platforms Free should show **locked**.
It drives every upsell surface; an entry disappears from `locked_for_js()` the moment
Pro registers the same slug, so one build serves both tiers with no `is_pro`
branching in the components.

### Hooks Free exposes

| Hook | Signature |
|---|---|
| `wpsp_social_platforms` | `array $platforms` |
| `wpsp_instant_share_{slug}` | `object $profile, int $profileKey, int $postId, bool $isShareOnPublish` |
| `wpsp_social_fetch_profile_response` | `null\|array $response, string $type, array $args` |
| `wpsp_social_profile_fields` | `array $fields` |
| `wpsp_social_template_tabs` | `array $tabs` |
| `wpsp_el_modal_social_platform_fields` | `array $selectedProfiles, int $postId` |
| `wpsp_calendar_delete_event` | `null\|bool $handled, int $postId, string $status` |
| `wpsp_social_platforms_loaded` | `WPSP\Social $social` |

### Free edit points

| # | File:line | Change |
|---|---|---|
| S1 | `Social/InstantShare.php:842-864` | replace GB `else if` with generic dispatch via `Platforms::get()` + `do_action("wpsp_instant_share_{$platform}", ...)`; `wp_send_json_error` when no listener and not share-on-publish |
| S2 | `InstantShare.php:520-590` | drop GB lines `:528,:545,:557,:582-584`; loop `Platforms::registered()` for `{slug}_selected_profiles` / `is_{slug}_share`; use `icon_small_white_url`/`label` at `:598-600` |
| S3 | `InstantShare.php:414` | loop built-ins + `Platforms::slugs()` writing `_{slug}_share_type = 'default'` |
| S4 | `InstantShare.php:40,51,314-334,345` | delete GB vars and `<li>`; render locked `<li>` per `locked_for_js()` entry, live `<li>` per registered platform; fold registered status keys into the "nothing connected" condition |
| S5 | `Social/SocialProfile.php:837-901` | delete GB branch; before `:902` add the `wpsp_social_fetch_profile_response` filter returning the same envelope as built-ins |
| S6 | `SocialProfile.php:907-1087` | delete `fetchLocations`, `fetchProfilePictureUrl`, `getGoogleMyBusinessProfile`, `getGoogleMyBusinessProfileByToken` (keep `handle_thumbnail_upload`) |
| S7 | `SocialProfile.php:27,1413-1443` | delete; Pro hooks `update_option_wpsp_settings_v5` itself |
| S8 | `SocialProfile.php:1108-1406` | no code change; comment that unknown `type` must return silently — Pro-old depends on it |
| S9 | `Social.php:92,99,176-178,195-199` | delete GB constants, boot branch and method; add `do_action('wpsp_social_platforms_loaded', $this)` at end of `load_third_party_integration()` |
| S10 | `Helper.php:257-270` | `$defaults = array_merge($defaults, Platforms::limits())` |
| S11 | `Helpers/CustomTemplateHelper.php:178-189,245-256,305-316` | drop GB; merge registry limits / empty template arrays |
| S12 | `API/CustomSocialTemplates.php:173-184,320,350,691-702,713` | one private `valid_platforms()` = built-ins + `Platforms::slugs()`; label fallback to `Platforms::labels()` |
| S13 | `API/AICaption.php:43-54` | drop GB; merge registry into `$this->platform_meta` |
| S14 | `API/Settings.php:78` | drop `_google_business_share_type`; append `_{slug}_share_type` per registry |
| S15 | `Social/ReconnectHandler.php:16-26,99-108,268-272,458-462` | remove GB rows; replace direct const reads (`:68,73,141,291,336,498,503,607,793,872`) with `profile_options()`, `renew_lead_time()`, `provider_token_endpoint()`, `shared_app_id()` merging the registry `reconnect` block |
| S16 | `Admin/Settings.php:747-763` | replace with `pro-social-platform` locked placeholder from `PRO_UPSELL`; wrap the `social_profile_wrapper` fields at `:610` in `wpsp_social_profile_fields` |
| S17 | `Settings.php:833,1712-1795` | wrap `tab_social_template` fields in `wpsp_social_template_tabs`; replace the GB tab with a locked placeholder (`is_pro`, `pro_feature` class, upsell html) |
| S18 | `Settings.php:32-60` | append registry limits |
| S19 | `Assets.php:82-93,164-175` | drop GB; add registry status keys; add `social_platforms` **and** `locked_platforms` to both `WPSchedulePostsFree` localizations |
| S20 | `Admin/Settings/Assets.php:54-66` | add `social_platforms`, `locked_platforms`, `min_pro_version` to `wpspSettingsGlobal` |
| S21 | `Admin.php:652,664,712-715,1123-1175` | delete GB accordion; add `do_action('wpsp_el_modal_social_platform_fields', ...)`; render the locked header per `PRO_UPSELL` when `!class_exists('WPSP_PRO')`. Pro persists `wpsp_el_social_google_business[]` in its existing `wpsp_el_action_before` handler, fixing the pre-existing gap |
| S22 | `wp-scheduled-posts.php:124` | delete `WPSP_SOCIAL_OAUTH2_GOOGLE_BUSINESS_APP_ID` (registry carries it) |
| S23 | `wp-scheduled-posts.php:44-56,98-126,155-169` | version notice — see below |

## Upsell surfaces in Free

Free already has the full toolkit; nothing new is built.

**Settings app:** `is_pro => true` → `pro-deactivated` greyed label (`ProToggle.tsx:22`) ·
`SweetAlertProMsg()` (`ToasterMsg.tsx:46`) · `ProAlert.tsx` inline link ·
`pro_features` tiles (`Settings.php:160-199`).

**Post panel app:** `useProOverlay.js` + `ProPopup.js` — transparent click-catching
overlay → "Opps! You Need SchedulePress PRO" → `schedulepress.com/#pricing`.

All seven surfaces keep a locked Google Business entry:

| # | Surface | Locked treatment |
|---|---|---|
| 1 | Settings → Social Profiles card | `pro-social-platform` placeholder field (S16) |
| 2 | Settings → Social Templates tab | locked tab with upsell html (S17) |
| 3 | Elementor modal accordion | locked header (S21) |
| 4 | Classic metabox checkbox | disabled checkbox + PRO badge linking to pricing (S4) |
| 5 | Post panel Social Share | **keep** in `PLATFORM_CONFIG` / `PLATFORM_ORDER`; greyed row, empty profile list, `proOverlay` + `itemStyle` → `ProPopup` |
| 6 | Custom template modal · AI caption drawer · Share Now status · `useSocialProfiles` | same locked treatment, driven by `locked_platforms` |
| 7 | `pro_features` tiles | add a fifth tile: Google Business Profile → `wpdeveloper.com/docs/share-wordpress-posts-on-google-business-profile/` |

Because surface 5 keeps rendering, **the Google Business icons stay in Free**
(`google_business.svg`, the `WithBG` variant, `google-my-business-logo.svg`,
`google-business-pro.svg`, `app/assets/images/google-business-small.svg`). Pro reuses
Free's copies via `WPSP_ASSETS_URI` rather than shipping duplicates.

## React / asset plan

**Pro ships no React.** The plan originally had Pro build its own settings
bundle and inject a component through quickbuilder's `custom_field` filter.
That needed `window.wpspSettingsApp` to re-export quickbuilder's context,
`SocialModal`, `ApiCredentialsForm` and a handful of helpers, because
quickbuilder is bundled into free's `admin.js` and a second copy would resolve
to a different React context. It also put a Pro script into free's enqueue
order, where the no-conflict dequeue could strip it.

Instead free gained **one generic component** and Pro supplies data:

- `fields/SocialPlatform.jsx` renders any registered network — slug,
  status key, logo and copy all arrive in the field definition. It is the
  old `GoogleBusiness.jsx` with the platform parameterised out.
- `fields/ProSocialPlatform.jsx` is the locked card. With no Pro it opens the
  pricing popup; with a Pro too old to have claimed the platform it says so
  instead, because selling a licence they already hold is the wrong answer.
- `Profiles/PlatformProfile.jsx` and `Modals/PlatformProfileList.jsx` are the
  former Google Business header and profile list, unchanged — both were
  already generic.
- `Field.tsx` gained `social-platform` and `pro-social-platform`;
  `SocialModal.tsx` falls back to `PlatformProfileList` for any type it has no
  built-in component for; `ApiCredentialsForm.tsx` reads `automatic_connect`
  from the definition rather than naming platforms.

Pro's `WPSP_PRO\Admin\GoogleBusinessSettings` returns the real field and tab
through `wpsp_social_profile_fields` / `wpsp_social_template_tabs`, both keyed
the same as free's placeholders so the card keeps its position.

The post panel (`src/`) reads `social_platforms` and `locked_platforms` off
`window.WPSchedulePostsFree` through `src/helper/platforms.js`. A locked
platform appears in **Manage Social Sharing** as a disabled tab with a PRO
tooltip that opens the pricing popup.

### Images

All five Google Business images **stay in free** — the locked card, the
Elementor header, the classic-editor row and the post-panel tab all render
them, and Pro reuses free's copies through `WPSP_ASSETS_URI` rather than
shipping duplicates.

## Calendar Pro-class dependency

`includes/Admin/Calendar.php:878-883` becomes:

```php
if ('Adv. Scheduled' == $status) {
    $handled = apply_filters('wpsp_calendar_delete_event', null, $postId, $status);
    if ($handled === null && class_exists('\WPSP_PRO\Scheduled\Published')) { // Pro < 5.4.0
        (new \WPSP_PRO\Scheduled\Published())->wpscp_pending_schedule_fn($postId, $status);
        $handled = true;
    }
    if ($handled === null) {
        return new WP_REST_Response(new WP_Error('wpsp_pro_required', __('Advanced Schedule needs SchedulePress Pro.', 'wp-scheduled-posts'), ['status' => 400]), 400);
    }
    return new WP_REST_Response(['message' => 'Advanced schedule removed', 'id' => $postId, 'status' => $status], 200);
}
```

The `use WPSP_PRO\Scheduled\Published;` line at `:9` is removed. Pro adds
`add_filter('wpsp_calendar_delete_event', ...)` in `Published::__construct()`.

## Version, notice, release

- **Free** `WPSP_VERSION` → `5.4.0` (`wp-scheduled-posts.php:103`, header `:5`,
  `package.json:3`, `readme.txt` Stable tag). New constant beside it:
  `define('WPSP_MIN_PRO_VERSION', '5.4.0');` — 5.4.0 is the first Pro that owns the GB
  engine. Future feature moves bump this constant only.
- **Notice** — `wp-scheduled-posts.php:44-56` uses a new pure static
  `Helper::pro_needs_update($proVersion)` (`$pro !== null && version_compare($pro, WPSP_MIN_PRO_VERSION, '<')`),
  unit-testable. `wpsp_fail_pro_version()` (`:155-169`) is rewritten generically:
  "SchedulePress %s requires SchedulePress Pro %s or newer; you are running Pro %s.
  Some Pro features stay unavailable until you update." It fires only when Pro is
  **active** (`defined('WPSP_PRO_VERSION')`), not merely installed — today's
  `is_plugin_installed` check at `:136` nags people with an inactive copy in the
  plugins folder. The 4.3.3 auto-activation block stays, keyed on
  `check_pro_compatibility('4.3.3', '=')` only.
- **Pro** `WPSP_PRO_VERSION` → `5.4.0`; add `WPSP_PRO_MIN_FREE_VERSION = '5.4.0'` as a
  **soft** gate. The hard `check_free_compatibility()` 5.0.0 gate
  (`wp-scheduled-posts-pro.php:98`) is unchanged, so the auto-upgrader is not provoked.
- Release Free to wp.org first, then Pro through EDD. `free_owns_platform()` makes the
  reverse order non-fatal.

## Commit sequence

Pro first, so nothing is broken between commits.

| Commit | Repo | Contents |
|---|---|---|
| **P1** | Pro | engine lands, dormant on old Free — rewrite `Social/GoogleBusiness.php`, new `Social/GoogleBusinessProfile.php`, constants |
| **P2** | Pro | PHP field/tab injection + Elementor accordion (no React) |
| **P3** | Pro | `wpsp_calendar_delete_event` hook, version bump, readme |
| **F1** | Free | registry + seams + generic/locked components + Calendar fix + notice, **additive** (GB rows still present) |
| **F2** | Free | remove Google Business (grep gate must pass) |
| **F3** | Free | `npm run build`, `npm run pot`, version bump, readme |

During the F1 window `class_exists('\WPSP\Social\GoogleBusiness')` keeps Pro dormant,
so GB keeps working from Free. `WPSP_MIN_PRO_VERSION` must not be bumped before F2 lands.

## Test plan

**Free unit** (`composer test`) — the no-op `add_filter` stub became a real
in-memory hook store (`tests/stubs/HookStore.php`) backing
`apply_filters`/`has_action`/`has_filter`, and the bootstrap now defines the
WordPress time constants the reconnect lead times are built from. Tests:
`SocialPlatformsRegistryTest` (round-trip, invalid definitions dropped, `PRO_UPSELL`
contains `google_business`, `locked_for_js()` drops a slug once registered) ·
`ReconnectHandlerPlatformMapsTest` · `CustomTemplateHelperLimitsTest` ·
`HelperPlatformLimitsTest` · `HelperProNeedsUpdateTest`.

**Free integration** (`composer test:integration`) — `GoogleBusinessRemovalTest`
(class/constant/cron absent; settings array contains the locked field and tab) ·
`CalendarDeleteEventTest` (400 not fatal without Pro; 200 with a filter) ·
`InstantShareRegistryTest` (fake platform dispatches with the documented signature).

Use the composer scripts, never `vendor/bin/phpunit` — see CLAUDE.md.

**Pro** — needs a one-time `composer require --dev phpunit/phpunit yoast/phpunit-polyfills`.
unit: `GoogleBusinessFormatTest`, `GoogleBusinessOwnershipTest` (`free_owns_platform()`
against injected versions). integration: registry entry shape, `wpsp_instant_share_google_business`
listener, engine + cron hooked only when status is on, `wpsp_social_fetch_profile_response`
envelope, `wpsp_calendar_delete_event` handled.

**Manual matrix** — one pass per cell:

1. Free 5.4.0 alone — locked card, locked template tab, locked post-panel row, locked
   Elementor header, locked classic checkbox, pricing popup from each; no PHP notices
   with `WP_DEBUG`; calendar delete of an Adv-scheduled post gives a friendly error.
2. Free 5.4.0 + Pro 5.3.3 — generic notice; GB card says "update Pro"; everything else works.
3. Free 5.4.0 + Pro 5.4.0 — connect (automatic + own app), locations, enable, Share Now,
   share on publish, republish share, one token-refresh cron (`wp cron event list`),
   reconnect, custom template + AI caption list GB at 1500 chars, Elementor selection
   persists, deleting the profile unschedules the cron.
4. Free 5.3.4 + Pro 5.4.0 — GB behaves exactly as 5.3.3 did; exactly one share per
   publish; `has_filter('wpsp_social_platforms')` false.
5. Free 5.3.4 + Pro 5.3.3, and Free 5.3.4 alone — unchanged control cells.

## Risks and open questions

1. ~~quickbuilder `custom_field` chaining~~, ~~shared `useBuilderContext`~~ and
   ~~script order~~ — all three disappeared with the Pro React bundle. Pro
   contributes PHP field definitions only.
2. **Elementor profile selection still does not persist for an extension
   platform.** `wpsp_el_tab_action()` never read `wpsp_el_social_google_business[]`
   before this work either. Closing it needs `wpsp_format_profile_data()`
   (`includes/Admin.php:1250`) to stop identifying platforms by which properties
   an object happens to carry — a Google Business profile has `type => 'profile'`
   and would be filed as Instagram. Left as it was rather than made wrong in a
   new way.
3. **`wpsp_publish_future_post` payload type** — `SocialProfile.php:39-43,50-53` fires
   it with an object while engines expect an int. Pre-existing for every platform; the
   moved engine inherits it. Out of scope.
5. **`remote_post()` returning `null`** on skip paths (`GoogleBusiness.php:79,85,104`)
   — `socialMediaInstantShare` guards it (`:402-404`); keep that guard when moving.
6. **Open:** clear orphaned `wpsp_google_business_token_refresh` crons once in
   `Migration` when `!class_exists('WPSP_PRO')`? Currently: no.
7. **Open:** force Pro's hard Free gate to 5.4.0 after Free 5.4.0 is live on wp.org?
   Currently: no, stays at 5.0.0.
8. **`.pot` churn** — moved strings change text domain; run `npm run pot` in both repos.
9. **Refreshed tokens are written to the wrong place.** Both token-refresh paths
   call `update_option('google_business_profile_list', ...)`, but the profiles are
   read back out of `wpsp_settings_v5` via `Helper::get_social_profile()`, so a
   refreshed access token never lands and the next share refreshes again. Carried
   over verbatim in P1 to keep the move behaviour-neutral, and annotated in place.
   Worth fixing on its own after the separation ships, not inside it.
