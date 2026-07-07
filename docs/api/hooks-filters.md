# Hooks & Filters

The extensibility surface of SchedulePress Free — the actions and filters that
integrations, and especially the sibling `wp-scheduled-posts-pro` plugin, hook
into. Pro adds its advanced scheduling, republish/unpublish, and per-platform
features by listening on these hooks and the shared subsystems; the free plugin
never contains Pro-only logic — it exposes seams instead. Hook names use the
`wpsp_` / `wpscp_` prefixes (older code) plus the newer `schedulepress_` prefix.

All paths below are relative to the plugin root ([../../](../../)).

## Actions

| Action | Fires when / where | Args |
| --- | --- | --- |
| `wpsp_publish_future_post` | The social auto-share trigger. Fired by `Social::publish_future_post()` ([includes/Social.php:31](../../includes/Social.php)) when a scheduled post goes live (WP core `publish_future_post`) or when a `wpsp_custom_social_template` cron event runs. Every social platform class listens on it (Facebook, Twitter, LinkedIn, Pinterest, Instagram, Medium, Threads, GoogleBusiness) to push the post. Also dispatched from `Social\SocialProfile` for per-profile shares. | `$post_id` (int) — or a profile object `{ ID, ... }` when fired for a single selected profile. |
| `schedulepress_after_free_settings_save` | After the Post Panel REST endpoint has saved its free-tier fields ([includes/API/PostPanel.php:174](../../includes/API/PostPanel.php)). This is the primary seam for Pro to persist its own post-panel fields (unpublish/republish, advanced schedule). Handlers must **not** touch free-tier fields such as `schedule_date`. | `$post_id` (int), `$request` (`WP_REST_Request` — read any extra params from it). |
| `wpsp_pro_update_post` | After a future-dated post is force-published via "publish immediately (future date)" ([includes/API/PostPanel.php:282](../../includes/API/PostPanel.php)). Lets Pro reschedule its unpublish/republish cron jobs. | `$post_id` (int) |
| `wpsp_instant_social_single_profile_share` | The `instant-social-share` REST route ([includes/API/Settings.php:240](../../includes/API/Settings.php)). `Social\InstantShare` listens and shares the post to a single profile on demand. | `$params` (array — the request params) |
| `wpsp_profile_reconnect_{$platform}` | Per profile in the `save-profile` REST route ([includes/API/Settings.php:396](../../includes/API/Settings.php)); dynamic — the platform slug is appended (e.g. `wpsp_profile_reconnect_facebook`). | `['id' => $profile]` |
| `social_profile_fetch_pinterest_section` | The `fetch_pinterest_section` REST route ([includes/API/Settings.php:409](../../includes/API/Settings.php)) — fetches Pinterest board sections. | `$params` (array) |
| `wpscp_calender_the_post` | Inside the Calendar's post loop while building calendar events ([includes/Admin/Calendar.php](../../includes/Admin/Calendar.php)). Lets extensions augment each rendered calendar item. | none (uses the global post) |
| `wpsp_save_settings_default_value` | On install/upgrade from `Installer` ([includes/Installer.php:45](../../includes/Installer.php)) so extensions can seed their own default settings. | `WPSP_VERSION` (string) |

Note: `publish_future_post` (WordPress core) and `wpsp_custom_social_template` (an
internal cron event) both feed into `wpsp_publish_future_post` — integrations
should hook the `wpsp_publish_future_post` action rather than the raw core hook.

## Filters

| Filter | Modifies | Args |
| --- | --- | --- |
| `wpsp_settings_before_save` | The full settings array just before it is JSON-encoded into the `wpsp_settings_v5` option ([includes/API/Settings.php:456](../../includes/API/Settings.php)). Pro uses this to persist its own settings keys. | `$settings` (array) |
| `wpsp_rest_endpoint` | The settings REST route path (default `/settings/`) ([includes/API/Settings.php:326](../../includes/API/Settings.php)). | `'/settings/'` (string) |
| `wpsp_openai_model` | The OpenAI model used for AI caption generation (default `gpt-4o-mini`) ([includes/API/AICaption.php:315](../../includes/API/AICaption.php)). | `'gpt-4o-mini'` (string) |
| `wpsp_layout_tabs` | The Settings React app's tab layout ([includes/Admin/Settings.php:146](../../includes/Admin/Settings.php)). Pro injects its tabs here. | `$tabs` (array) |
| `wpsp_general_fields` | The General settings tab's field definitions ([includes/Admin/Settings.php:152](../../includes/Admin/Settings.php)). | `$fields` (array) |
| `wpsp_ai_fields` | The AI settings tab's fields ([includes/Admin/Settings.php:1587](../../includes/Admin/Settings.php)). | `$fields` (array) |
| `wpsp_schedule_hub_fields` | The Schedule Hub settings fields ([includes/Admin/Settings.php:1616](../../includes/Admin/Settings.php)). | `$fields` (array) |
| `wpsp_settings_global` / `wpsp_settings_calendar` | The `wpspSettingsGlobal` JS data localized into the admin apps ([includes/Admin/Settings/Assets.php:54](../../includes/Admin/Settings/Assets.php)). | `$data` (array) |
| `wpsp_social_share_title` | The title used when composing a social share ([includes/Traits/SocialHelper.php:150](../../includes/Traits/SocialHelper.php); also Pinterest). | `$title`, `$class`, `$post_link` (+ `$post_id` on Pinterest) |
| `wpsp_social_share_desc` | The description/body of a social share ([includes/Traits/SocialHelper.php:181](../../includes/Traits/SocialHelper.php)). | `$desc`, `$platform` |
| `wpsp_filter_social_content_tags` | The template placeholder tags available in social message templates ([includes/Traits/SocialHelper.php:160](../../includes/Traits/SocialHelper.php)). | `$tags` (array), `$platform` |
| `wpsp_social_share_content_template_line_break` | The line-break replacement in template content ([includes/Traits/SocialHelper.php:194](../../includes/Traits/SocialHelper.php)). | `"\n"`, `func_get_args()` |
| `wpsp_social_profile_limit_checkpoint` | Gate that enforces the free social-profile-count limit ([includes/Helper.php:399](../../includes/Helper.php)). Pro returns `true` to lift the cap. | `$profile` |
| `wpsp_filter_linkedin_pages` | The list of LinkedIn pages for a profile ([includes/Social/SocialProfile.php:561](../../includes/Social/SocialProfile.php)). | `$pages`, `$profiles` |
| `wpsp_admin_bar_menu_posts` | The scheduled posts shown in the admin-bar menu ([includes/functions.php:58](../../includes/functions.php)). | `$result`, `$post_types` |
| `wpsp_pre_eventDrop` | Short-circuits / customizes a Calendar drag-and-drop reschedule ([includes/Admin/Calendar.php:721](../../includes/Admin/Calendar.php)). | `null`, `$postid`, `$postdateformat`, `$postdate_gmt` |
| `wpsp_eventDrop_posts` | The posts affected by a Calendar drag-and-drop ([includes/Admin/Calendar.php:852](../../includes/Admin/Calendar.php)). | `[$post]`, `$post_id` |
| `schedulepress_skip_no_conflict` | Whether to skip the "no-conflict" asset-loading guard ([includes/Admin/Settings/Assets.php:29](../../includes/Admin/Settings/Assets.php)). | `false` (bool) |

The plugin also fires the shared WPDeveloper notice/insights hooks
(`wpdeveloper_*`, `wpins_*`) that the bundled notice library relies on — these are
infrastructure rather than a public extensibility surface.

## How Pro hooks in

`wp-scheduled-posts-pro` is a separate plugin. It does not fork the free code; it
integrates through:

- **The Post Panel seam** — `schedulepress_after_free_settings_save` (persist Pro
  post-panel fields) and `wpsp_pro_update_post` (reschedule Pro cron jobs).
- **The settings surface** — `wpsp_layout_tabs`, `wpsp_general_fields`,
  `wpsp_ai_fields`, `wpsp_schedule_hub_fields`, and `wpsp_settings_before_save` to
  add tabs/fields and persist their values into `wpsp_settings_v5`.
- **The social-share pipeline** — the `wpsp_publish_future_post` action and the
  `wpsp_social_share_*` / `wpsp_filter_social_content_tags` filters.
- **Limit gates** — `wpsp_social_profile_limit_checkpoint` to lift free-tier caps.

When adding a Pro-facing feature, expose a hook here rather than adding Pro-only
branches to the free repo. See [../../CLAUDE.md](../../CLAUDE.md) ("Conventions worth
knowing").

## Source

- Grep the tree: `grep -rn "do_action(\|apply_filters(" includes/ | grep -v includes/Deps/`
- [rest-endpoints.md](rest-endpoints.md) — the REST routes that fire many of these hooks.
- [../../CLAUDE.md](../../CLAUDE.md)
