# REST Endpoints

SchedulePress registers its REST routes on `rest_api_init`. Two namespaces are in
use:

- **`wp-scheduled-posts/v1`** — the value of the `WPSP_PLUGIN_SLUG` constant plus
  `/v1`. Used by the four handler classes under [../../includes/API/](../../includes/API/).
- **`wpscp/v1`** — the Schedule **Calendar** routes, registered by
  [../../includes/Admin/Calendar.php](../../includes/Admin/Calendar.php).

The four handler classes are wired up from [../../includes/API.php](../../includes/API.php),
whose `load_settings_API()` boots each as a singleton:

```php
API\Settings::get_instance();
API\CustomSocialTemplates::get_instance();
API\PostPanel::get_instance();
API\AICaption::get_instance();
```

Every route defines a `permission_callback`. Post-scoped routes typically gate on
the collection capability (`edit_posts`) at registration and then re-check the
per-post capability (`edit_post`) inside the handler; settings routes require
`manage_options`. Responses are `WP_REST_Response` objects that (except for the
Calendar and a few passthrough routes) share a `{ success: bool, ... }` envelope.

| Handler | Source | Purpose |
| --- | --- | --- |
| `Settings` | [../../includes/API/Settings.php](../../includes/API/Settings.php) | Read/write the `wpsp_settings_v5` option, social-profile helpers, post-meta registration. |
| `CustomSocialTemplates` | [../../includes/API/CustomSocialTemplates.php](../../includes/API/CustomSocialTemplates.php) | Per-post custom social message templates + per-post social settings. See [../specs/social-templates.md](../specs/social-templates.md). |
| `PostPanel` | [../../includes/API/PostPanel.php](../../includes/API/PostPanel.php) | Editor post-panel scheduling data + "publish future post immediately". |
| `AICaption` | [../../includes/API/AICaption.php](../../includes/API/AICaption.php) | AI caption generation via OpenAI. See [../specs/ai-caption.md](../specs/ai-caption.md). |
| `Calendar` | [../../includes/Admin/Calendar.php](../../includes/Admin/Calendar.php) | Schedule Calendar data, drag-and-drop reschedule, quick edit. See [../specs/schedule-calendar.md](../specs/schedule-calendar.md). |

---

## Settings

`WPSP\API\Settings` — namespace `wp-scheduled-posts/v1`. Registers three groups of
routes across three `rest_api_init` callbacks (`register_routes`,
`register_social_profile_routes`, `meta_rest_api`).

### Settings CRUD

The settings collection path defaults to `/settings/` but is filterable via the
`wpsp_rest_endpoint` filter. All four verbs require `manage_options`
(`wpsp_permissions_check`).

| Method | Route | Callback | Response |
| --- | --- | --- | --- |
| `GET` | `wp-scheduled-posts/v1/settings/` | `get_value` | `{ success: true, value: <json-string> }` — the raw `wpsp_settings_v5` option (empty string when unset). |
| `POST` | `wp-scheduled-posts/v1/settings/` | `update_value` | `{ success: bool, value: <params> }` |
| `PUT`/`PATCH` | `wp-scheduled-posts/v1/settings/` | `update_value` | `{ success: bool, value: <params> }` |
| `DELETE` | `wp-scheduled-posts/v1/settings/` | `delete_value` | `{ success: bool, value: "" }` |

`update_value` merges defaults for `allow_post_types` / `allow_categories` /
`allow_user_by_role` when they arrive empty, runs the payload through the
[`wpsp_settings_before_save`](hooks-filters.md#filters) filter, then JSON-encodes
it into the option.

### Social-profile helpers

Registered in `register_social_profile_routes`. All require `edit_posts`.

| Method | Route | Callback | Notes |
| --- | --- | --- | --- |
| `GET` | `wp-scheduled-posts/v1/get-option-data` | `wpsp_get_options_data` | Returns the `wpsp_settings_v5` option with social profile lists normalized (default `thumbnail_url` / `name`, LinkedIn `__id`→`id`). Gated additionally by `Helper::is_user_allow()` (401 otherwise). |
| `GET` | `wp-scheduled-posts/v1/instant-social-share` | `wpsp_instant_social_share` | Fires the [`wpsp_instant_social_single_profile_share`](hooks-filters.md#actions) action with the request params. |
| `GET` | `wp-scheduled-posts/v1/get-categories` | `wpsp_get_categories` | Paginated taxonomy terms across all allowed post types. Params: `limit` (default 10), `page` (default 1). Returns an array of `{ term_id, label, slug, taxonomy, postType, value }`. |
| `POST` | `wp-scheduled-posts/v1/update-refresh-token` | `wpsp_update_refresh_token` | Params: `platform`, `item`. Delegates to `Social\ReconnectHandler::handleProfileReconnect()`. |

Two additional routes are registered in `register_routes` (both require
`manage_options`):

| Method | Route | Callback | Notes |
| --- | --- | --- | --- |
| `PUT`/`PATCH` | `wp-scheduled-posts/v1/fetch_pinterest_section` | `fetch_pinterest_section` | Fires the `social_profile_fetch_pinterest_section` action with the request params. |
| `POST` | `wp-scheduled-posts/v1/save-profile` | `save_profile` | Params: `platform`, `profiles[]`. Fires `wpsp_profile_reconnect_{platform}` per profile. |

### Registered post meta

`meta_rest_api` calls `register_post_meta()` for every allowed post type so the
block editor can read/write the social-sharing meta over the core REST API
(`auth_callback` = `edit_posts`). Registered keys include
`_wpscppro_dont_share_socialmedia` (boolean), `_wpscppro_custom_social_share_image`
(integer), the per-platform `_<platform>_share_type` strings, and
`_selected_social_profile` (typed object array).

---

## Custom Social Templates

`WPSP\API\CustomSocialTemplates` — namespace `wp-scheduled-posts/v1`. All routes
register with a `permission_callback` of `edit_posts` and re-verify `edit_post`
for the specific `post_id` inside the handler (403 on failure). Each route takes
`post_id` as a numeric path segment.

| Method | Route | Callback | Params | Response |
| --- | --- | --- | --- | --- |
| `GET` | `wp-scheduled-posts/v1/custom-templates/{post_id}` | `get_custom_templates` | `post_id` | `{ success: true, data: { <platform templates>, scheduling: {…} } }` |
| `POST` | `wp-scheduled-posts/v1/custom-templates/{post_id}` | `save_custom_template` | `post_id`; either single-platform (`platform`, `template`, `profiles`, `is_global`) or batch (`platforms[]`); optional `scheduling` | `{ success: bool, message }` or `{ success: false, errors: {…} }` (400) on validation failure |
| `DELETE` | `wp-scheduled-posts/v1/custom-templates/{post_id}` | `delete_custom_template` | `post_id`, `platform` (required, one of facebook/twitter/linkedin/pinterest/instagram/medium/threads) | `{ success: bool, message }` |
| `POST` | `wp-scheduled-posts/v1/social-settings/{post_id}` | `save_social_settings` | `post_id`, `disable_social_share` (bool), `social_banner_id` (int/string/null) | `{ success: bool, message }` |

Saving templates writes the `_wpsp_custom_templates`,
`_wpsp_enable_custom_social_template`, and `_wpsp_social_scheduling` post meta, and
re-arms the social scheduling for published/future posts. Template text is
validated against each platform's character budget
(`Helper::get_social_platform_limits()`).

---

## Post Panel

`WPSP\API\PostPanel` — namespace `wp-scheduled-posts/v1`. Every route uses
`permission_check`, which requires `edit_post` for the given `post_id` (returns a
`rest_forbidden` `WP_Error` with status 403 otherwise).

| Method | Route | Callback | Params | Response |
| --- | --- | --- | --- | --- |
| `GET` | `wp-scheduled-posts/v1/post-panel/{post_id}` | `get_settings` | `post_id` | `{ success: true, data: { schedule_date, post_status, prevent_future_post, prevent_future_post_date } }` (404 if the post is missing). `prevent_future_post` is true only while the stored meta still matches `post_date`, which is the condition under which it keeps forcing `publish`. |
| `POST` | `wp-scheduled-posts/v1/post-panel/{post_id}` | `save_settings` | `post_id`, `schedule_date` (string), `is_scheduled` (bool) | `{ success: true, message }` |
| `POST` | `wp-scheduled-posts/v1/update-settings/{post_id}` | `publish_immediately` | `post_id`, `publish_immediately_current_date` (bool), `publish_immediately_future_date` (bool) | `{ success: true, message }` |
| `DELETE` | `wp-scheduled-posts/v1/update-settings/{post_id}` | `clear_publish_immediately` | `post_id` | Deletes the `prevent_future_post` meta. When the post is still dated in the future it is returned to `future` so WordPress schedules it again. `{ success: true, message, data: { post_status, prevent_future_post: false, rescheduled } }` |

`save_settings` handles only the free-tier `schedule_date` field (setting a future
`post_date` and `future` status), then fires
[`schedulepress_after_free_settings_save`](hooks-filters.md#actions) so Pro and
other extensions can persist their own fields from the same request.

`publish_immediately` backs the "Publish future post immediately" controls:
`publish_immediately_current_date` publishes now with a current timestamp;
`publish_immediately_future_date` publishes now while preserving the future
`post_date` (bypassing WordPress's forced `future` status via a temporary
`wp_insert_post_data` filter), then fires `wpsp_pro_update_post` so Pro can
reschedule its unpublish/republish cron jobs.

---

## AI Caption

`WPSP\API\AICaption` — namespace `wp-scheduled-posts/v1`. Generates social captions
through the OpenAI Chat Completions API using the `openai_api_key` saved in
`wpsp_settings_v5`. See [../specs/ai-caption.md](../specs/ai-caption.md).

| Method | Route | Callback | Permission | Response |
| --- | --- | --- | --- | --- |
| `POST` | `wp-scheduled-posts/v1/ai-caption/{post_id}` | `generate_captions` | `edit_posts` (registration); `edit_post` re-checked in handler | `{ success: true, captions: { <platform>: "…" } }` |
| `POST` | `wp-scheduled-posts/v1/ai-test-key` | `test_connection` | `manage_options` | `{ success: bool, message }` |

`generate_captions` params:

- `post_id` (path, required, numeric)
- `platforms[]` — subset of `facebook`, `twitter`, `linkedin`, `pinterest`,
  `instagram`, `medium`, `threads`, `google_business`
- `prompt` (string), `autoGenerate` (bool), `tone` (string, default
  `professional`; `post_specific` matches the post's own voice), `length`
  (`auto`/`short`/`medium`/`long`), `generateHashtags` (bool), `includeEmojis` (bool)

The model (`gpt-4o-mini` by default) is filterable via
[`wpsp_openai_model`](hooks-filters.md#filters). Generated captions are hard-trimmed
to each platform's character limit so they pass template validation on save.
Failure modes return `success: false` with an HTTP status of 400 (misconfiguration
/ bad input), 403 (permission), or 502 (upstream OpenAI error).

`test_connection` accepts an optional `api_key` param (the value currently typed
into the settings field) and falls back to the saved key; it validates the key
against the OpenAI models endpoint.

---

## Calendar

`WPSP\Admin\Calendar` — namespace `wpscp/v1`. Powers the Schedule Calendar screen.
See [../specs/schedule-calendar.md](../specs/schedule-calendar.md). Permission
callbacks are per-route; the post-mutating routes verify per-post capabilities
(`edit_post` / `delete_post`).

| Method | Route | Callback | Permission | Notes |
| --- | --- | --- | --- | --- |
| `PUT`/`PATCH` | `wpscp/v1/calendar` | `wpscp_future_post_rest_route_output` | `validate_user_post_access` | Calendar feed. Params: `post_type[]`, `taxonomy[]`, `activeStart`, `activeEnd` (all required). |
| `POST` | `wpscp/v1/posts` | `get_draft_posts` | `validate_user_post_access` | Draft posts for the unscheduled drafts drawer. |
| `GET` | `wpscp/v1/get_tax_terms` | `get_tax_terms` | `permission_callback` | Taxonomy terms for calendar filters. |
| `GET` | `wpscp/v1/post` | `quick_edit_get_post` | `quick_edit_get_permission_callback` | Fetch a single post for quick edit. |
| `PUT`/`PATCH` | `wpscp/v1/post` | `calender_ajax_request_php` | `edit_permission_callback` (`edit_post` on `postId`) | Reschedule / quick-edit a post (drag-and-drop). |
| `DELETE` | `wpscp/v1/post` | `delete_event_action` | `delete_permission_callback` (`delete_post` on `ID`) | Delete a post from the calendar. |
| `GET` | `wpscp/v1/scf-fields` | `wpscp_register_scf_fields_rest_route` | `edit_posts` | ACF/SCF field groups for a post type (empty when ACF is inactive). |

Calendar drag-and-drop reschedules run through the
[`wpsp_pre_eventDrop`](hooks-filters.md#filters) and
[`wpsp_eventDrop_posts`](hooks-filters.md#filters) filters.

---

## Source

- [../../includes/API.php](../../includes/API.php) — boots the four handler singletons.
- [../../includes/API/](../../includes/API/) — `Settings`, `CustomSocialTemplates`, `PostPanel`, `AICaption`.
- [../../includes/Admin/Calendar.php](../../includes/Admin/Calendar.php) — the `wpscp/v1` calendar routes.
- [hooks-filters.md](hooks-filters.md) — the actions/filters referenced above.
