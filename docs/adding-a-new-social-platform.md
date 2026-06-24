# Adding a New Social Platform to SchedulePress

This guide documents every touchpoint required to add a new social network as a
share target in SchedulePress (the `wp-scheduled-posts` plugin). It uses the
**Bluesky** integration as a worked example end‑to‑end, so you can copy the same
pattern for the next platform (Mastodon, Tumblr, etc.).

> All social logic lives in the **free** plugin (`wp-scheduled-posts`). The Pro
> plugin depends on it. There are **two** JavaScript builds you must rebuild:
> the post‑editor panel (`src/`) and the settings app (`includes/Admin/Settings/`).

---

## 0. Decide the authentication model

Two patterns already exist in the codebase — pick whichever the platform supports:

| Model | Examples | How it connects |
|-------|----------|-----------------|
| **OAuth redirect** | Facebook, Twitter, LinkedIn, Pinterest, Instagram, Threads, Google Business | `add_social_profile()` returns an authorize URL; the callback is exchanged for tokens in `social_profile_fetch_user_info_and_token()`. |
| **Credential / token** | Medium (integration token), **Bluesky** (handle + App Password) | `add_social_profile()` validates the credentials synchronously and returns the profile object directly — no redirect. |

Bluesky runs on the **AT Protocol** and authenticates with a **handle + App
Password** (`com.atproto.server.createSession`). The session `accessJwt` is
short‑lived, so we store the identifier + App Password and create a fresh
session on every share. This is the **credential** model below.

---

## 1. Backend (PHP)

### 1.1 Register the platform — `includes/Social.php`

- Add option‑name constants in `define_constants()`:
  ```php
  $this->define('WPSCP_BLUESKY_OPTION_NAME', 'bluesky_profile_list');
  $this->define('WPSCP_BLUESKY_PDS', 'https://bsky.social');
  ```
- Conditionally boot the integration in `load_third_party_integration()`:
  ```php
  if (Helper::get_settings('bluesky_profile_status') == true) {
      $this->bluesky();
  }
  ```
- Add the loader method (mirror `medium()`):
  ```php
  public function bluesky() {
      $WpScp_bluesky = new Social\Bluesky();
      $WpScp_bluesky->instance();
  }
  ```

### 1.2 Create the share class — `includes/Social/Bluesky.php`

Model on `includes/Social/Twitter.php` (microblog) + `includes/Social/Medium.php`
(credential model). Use the `WPSP\Traits\SocialHelper` trait so you can reuse:

- `social_share_content_template_structure()` — applies the `{title}{content}{url}{tags}`
  template and the character limit.
- `getPostHasTags()` / `getPostHasCats()` — hashtag + category handling.

Key members:

| Member | Responsibility |
|--------|----------------|
| `__construct()` | Read `Helper::get_settings('social_templates')->bluesky` (template_structure, is_category_as_tags, content_source, note_limit=300, post_share_limit, is_show_post_thumbnail). |
| `instance()` | `add_action('wpsp_publish_future_post', 'wpsp_bluesky_post_event')`; schedule `wpsp_bluesky_post`; `schedule_republish_social_share_hook()`. |
| `create_session($pds,$id,$pw)` | POST `com.atproto.server.createSession` → `{did, accessJwt}`. Uses `Helper::wpsp_curl()`. |
| `upload_blob($pds,$jwt,$file)` | POST raw image bytes to `com.atproto.repo.uploadBlob` → blob ref. |
| `build_facets($text)` | Regex URLs → richtext `app.bsky.richtext.facet#link` with UTF‑8 **byte** offsets (clickable links). |
| `get_share_content_args($post_id)` | Build text + resolve image (custom social image → featured → request fallback). |
| `remote_post(...)` | Apply the standard guards, create session, build the `app.bsky.feed.post` record, POST `com.atproto.repo.createRecord`, save log. |
| `wpsp_bluesky_post`, `wpscp_republish_bluesky_post` | Loop `Helper::get_social_profile(WPSCP_BLUESKY_OPTION_NAME)` and call `remote_post()`. |
| `socialMediaInstantShare(...)` | Wrapper that returns a JSON response for the "Share Now" AJAX. |

**The standard guards every `remote_post()` must apply** (copied from Twitter/Medium):

```php
$dont_share = get_post_meta($post_id, '_wpscppro_dont_share_socialmedia', true); // skip if on
get_post_meta($post_id, '_bluesky_share_type', true);          // 'custom' → only selected profiles
get_post_meta($post_id, '_wpsp_enable_custom_social_template', true); // per‑post template/profiles
// per‑post share‑count limit via __wpsp_bluesky_share_count_{id} + $this->post_share_limit
get_post_meta($post_id, '_wpsp_is_bluesky_share', true);       // 'on' or $force_share
```

The post‑meta keys follow the `{platform}` convention:
`__wpscppro_bluesky_share_log`, `__wpsp_bluesky_share_count_{id}`,
`_wpsp_is_bluesky_share`, `_bluesky_share_type`.

### 1.3 Connect the account — `includes/Social/SocialProfile.php`

Add an `else if ($type == 'bluesky')` branch in `add_social_profile()`. For the
credential model: read `$_POST['appId']` (handle) + `$_POST['appSecret']`
(App Password), create a session, fetch `app.bsky.actor.getProfile` for the
display name + avatar, upload the avatar with the existing
`handle_thumbnail_upload()`, then `wp_send_json_success()` with the profile object:

```php
[
  'id'            => time(),     // unique numeric id
  '__id'          => $did,       // platform user id
  'app_id'        => $identifier,
  'app_secret'    => $app_password,
  'name'          => $displayName,
  'thumbnail_url' => $uploadedAvatarUrl,
  'type'          => 'profile',
  'status'        => true,
  'access_token'  => $accessJwt,
  'pds'           => 'https://bsky.social',
  'added_by'      => $current_user->user_login,
  'added_date'    => current_time('mysql'),
]
```

> On auth failure, return `wp_send_json_success(['message' => ...])`. The frontend
> treats a response without a `name` as an error and shows `data.message` as a toast
> (same convention as Medium).

### 1.4 Instant Share + post meta — `includes/Social/InstantShare.php`

Mirror every `medium`/`threads` reference:

- Profile + integration status fetch (`$blueskyIntegation`, `$blueskyProfile`).
- The "Share Now" checkbox `<li class="bluesky">` in the markup.
- `update_post_meta($post_id, '_bluesky_share_type', 'default')` and merge bluesky
  profiles into `$selectedSocialProfiles`.
- `bluesky_selected_profiles` + `is_bluesky_share` in `instant_share_fetch_profile()`.
- An `else if ($platform == 'bluesky')` dispatch branch that resolves the profile
  by `id` and calls `Bluesky::socialMediaInstantShare(...)`.

### 1.5 Register post meta — `includes/API/Settings.php`

Add `_bluesky_share_type` to the `$social_media_meta_key` array so the meta is
exposed over REST for the block editor.

### 1.5b Per‑post custom templates — `includes/API/CustomSocialTemplates.php`

The "Add Social Message" modal saves per‑post templates/profiles via REST. The
platform name is whitelisted in **three** places — add the new platform to all of
them or the save silently fails validation:

- The `default_templates` array in `initialize_custom_templates_meta()`.
- The two `validate_callback`s on the `platform` REST arg.
- `$valid_platforms` in `process_single_platform_data()` (this is the one the
  batch save flows through).

### 1.6 Settings schema — `includes/Admin/Settings.php`

1. **Character cap** — add to the `$limits` array in `wpsp_update_settings()`:
   ```php
   'bluesky' => ['note_limit' => 300],
   ```
2. **Profile card field** — add `bluesky_profile_list` (after `google_business_profile_list`)
   with `'type' => 'bluesky'`, a `logo` (`WPSP_ASSETS_URI . 'images/bluesky.svg'`),
   a `desc`, and a `modal` block (label/help text for the connect form).
3. **Social‑template tab** — add `layouts_bluesky` (clone `layouts_medium`) with
   `note_limit` default/max `300` and an `is_show_post_thumbnail` toggle.

### 1.7 Expose enabled state to JS — `includes/Admin/../Assets.php`

`social_media_enabled` is localized to `window.WPSchedulePostsFree` in **two**
`wp_localize_script('wpsp-react-app', ...)` calls in `includes/Assets.php`. Add
your platform to **both**:

```php
'bluesky' => \WPSP\Helper::get_settings('bluesky_profile_status'),
```

> ⚠️ Easy to miss — the post‑editor panel & custom‑template modal only show a
> platform when `social_media_enabled[platform]` is truthy.

---

## 2. Frontend — Settings app (`includes/Admin/Settings/app`)

1. **Field component** — `Settings/fields/Bluesky.tsx`. Copy `Medium.tsx`, rename
   `medium`→`bluesky`, status key `bluesky_profile_status`, platform `'bluesky'`.
   The connect handler POSTs `action: 'wpsp_social_add_social_profile'`,
   `type: 'bluesky'`, `appId` (handle), `appSecret` (App Password).
2. **Register the field type** — `Settings/fields/Field.tsx`: import the component
   and add `case "bluesky": return <Bluesky {...props} />;`.
3. **Connect form** — `Settings/fields/Modals/ApiCredentialsForm.tsx`: add a
   `platform == 'bluesky'` form with two inputs (Identifier/Handle → `appID`,
   App Password → `appSecret`) and allow submit in `onSubmitHandler`.
4. **"Add New" button gate** — `Settings/fields/utils/MainProfile.tsx`: the
   onClick only opens the modal when an account type is selected **or** the
   platform is in a hardcoded allow‑list. Platforms with no account‑type selector
   (twitter, pinterest, instagram, medium, threads, **bluesky**) must be added to
   that list, otherwise clicking "Add New" silently does nothing.
   ```js
   if (accountType || ['twitter','pinterest','instagram','medium','threads','bluesky'].includes(props?.type)) {
   ```
5. **Badge (optional)** — `Settings/fields/utils/SelectedProfile.tsx`: add a
   `bluesky:` entry to the badge map.
6. **Profile‑selection modal CSS** — `app/assets/sass/utils/_modals.scss`: the
   "select a profile" card is styled per platform via `.wpsp-modal-social-<platform>`
   (the component renders `wpsp-modal-social-platform wpsp-modal-social-${platform}`).
   Without a matching rule the avatar/name/checkbox render unstyled. For a
   credential platform, add it to the shared `.wpsp-modal-social-medium` rule:
   ```scss
   .wpsp-modal-social-medium,
   .wpsp-modal-social-bluesky { /* item-content + entry-thumbnail layout */ }
   ```
7. **Sidebar icon (optional)** — `app/assets/images/bluesky-small.svg`.

---

## 3. Frontend — Post‑editor panel (`src/`)

1. **Icons** — `src/icons/icons.js`: `export const bluesky` (plain) and
   `export const blueskyWithBG` (badge variant).
2. **Share list** — `src/components/Content/SocialShare.js`: add to `PLATFORM_CONFIG`,
   `PLATFORM_ORDER`, and the `allProfiles` map
   (`bluesky: processProfiles(optionData?.bluesky_profile_list)`).
3. **Custom‑template modal** — `src/components/modals/socialTemplates/CustomTemplateModal.js`:
   add `'bluesky'` to `SOCIAL_PLATFORMS`, `platformLimits` (`300`), the `platforms`
   array (icon + brand color `#0085ff`), and the `getAvailableProfiles` switch.
4. **Profiles hook** — `src/components/modals/socialTemplates/hooks/useSocialProfiles.js`:
   add `bluesky: []` to initial state and
   `bluesky: processProfiles(response.bluesky_profile_list)` to the result.

---

## 4. Assets

- `assets/images/bluesky.svg` — used by the PHP settings field `logo`/`modal.logo`.
- `includes/Admin/Settings/app/assets/images/bluesky-small.svg` — settings sidebar.

---

## 5. Build

```bash
# Post‑editor panel (run from the plugin root)
npm run build

# Settings app
cd includes/Admin/Settings && npm run build
```

Both must compile without errors. Size/Sass deprecation warnings are expected.

---

## 6. Verify end‑to‑end

1. **Connect** — SchedulePress → Settings → Social Profiles → enable Bluesky →
   connect with a handle + App Password. The profile card (name + avatar) should
   appear and persist after saving.
2. **Schedule** — schedule a post with Bluesky enabled and a featured image. On
   publish, confirm the skeet appears with the image and a **clickable** link,
   within the 300‑char limit.
3. **Editor panel** — Bluesky shows in the social‑share list and the
   custom‑template modal.
4. **Share Now** — Instant Share posts immediately; the share is logged in
   `__wpscppro_bluesky_share_log` and re‑share respects the per‑post limit.

---

## Quick checklist

- [ ] `Social.php` — constants + loader + method
- [ ] `Social/<Platform>.php` — share class (uses `SocialHelper` trait)
- [ ] `Social/SocialProfile.php` — connect branch in `add_social_profile()`
- [ ] `Social/InstantShare.php` — status fetch, checkbox, meta, dispatch branch
- [ ] `API/Settings.php` — `_<platform>_share_type` meta
- [ ] `API/CustomSocialTemplates.php` — 3 platform whitelists (per‑post templates)
- [ ] `Admin/Settings.php` — char limit, profile field, template tab
- [ ] `Assets.php` — `social_media_enabled` (×2)
- [ ] Settings app — `<Platform>.tsx`, `Field.tsx`, `ApiCredentialsForm.tsx`, `MainProfile.tsx` (Add‑New gate)
- [ ] Post panel — `icons.js`, `SocialShare.js`, `CustomTemplateModal.js`, `useSocialProfiles.js`
- [ ] Icons — `assets/images/<platform>.svg` (+ small svg)
- [ ] `npm run build` (both apps)
- [ ] Verify connect → schedule → share
