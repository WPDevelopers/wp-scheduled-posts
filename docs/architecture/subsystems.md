# Subsystems

The `includes/` subsystems and what each owns. This is the detailed companion to [overview.md](overview.md); per-feature behaviour is specced in [../specs/](../specs/). Every subsystem is a class in the `WPSP\` namespace (PSR-4 → `includes/`); the engine ([../../wp-scheduled-posts.php](../../wp-scheduled-posts.php)) instantiates them from its constructor and its `init` / `wp_loaded` / `init_plugin()` wiring.

## Map

| Subsystem | Path | Owns |
| --- | --- | --- |
| Installer | [../../includes/Installer.php](../../includes/Installer.php) | Post-activation redirect to the Settings page (`wpsp_do_activation_redirect`), the plugin-list update message, and the `migrate()` entry point run on `wp_loaded`. Also flips per-profile social status when Pro is toggled (`get_social_profile_status_modified_data`). |
| Migration | [../../includes/Migration.php](../../includes/Migration.php) | Version-gated data migrations: `version_3_to_4` (from the old `wpscp_options` key), `version_4_to_5` (`wpsp_settings` → `wpsp_settings_v5`), `allow_categories`, and scheduled-post social-share meta. Each guarded by a one-time `wpsp_data_migration_*` flag. See [settings-and-data.md](settings-and-data.md). |
| Assets | [../../includes/Assets.php](../../includes/Assets.php) | Enqueues block-editor, admin, admin-bar, and Elementor editor scripts/styles. |
| Admin | [../../includes/Admin/](../../includes/Admin/), [../../includes/Admin.php](../../includes/Admin.php) | Admin-only surface (loaded when `is_admin()`): `Menu`, per-post `Metabox/`, `Notices/` (upgrade message), `Rule`/`Rules` (access control), `Widgets/` (dashboard widget `ScheduledPostList`), the Elementor SchedulePress modal, admin notices/upsell campaigns, usage tracking, and the React **Settings** app under `Admin/Settings/`. `Calendar` ([../../includes/Admin/Calendar.php](../../includes/Admin/Calendar.php)) is loaded separately on `init`. |
| Social | [../../includes/Social/](../../includes/Social/), [../../includes/Social.php](../../includes/Social.php) | One class per platform — `Facebook`, `Twitter`, `Linkedin`, `Pinterest`, `Instagram`, `Medium`, `Threads`, `GoogleBusiness`, `InstantShare` — each instantiated only when its `*_profile_status` setting is on. Plus `SocialProfile`, `SocialReconnection`/`ReconnectHandler`, and the shared `WPSP\Traits\SocialHelper` trait. Hooks `publish_future_post` → fires `wpsp_publish_future_post`. OAuth goes through the middleware endpoints defined in `WPSP::define_constants()`; SDKs come from `includes/Deps/`. See [social-architecture.md](social-architecture.md). |
| API | [../../includes/API.php](../../includes/API.php), [../../includes/API/](../../includes/API/) | REST controllers registered under the `wp-scheduled-posts/v1` namespace — `Settings`, `CustomSocialTemplates`, `PostPanel`, `AICaption` (each a `get_instance()` singleton). See [../api/rest-endpoints.md](../api/rest-endpoints.md). |
| Email | [../../includes/Email.php](../../includes/Email.php) | Schedule / review notification emails, driven off `transition_post_status`; formats subject/body and sends to author, roles, usernames, or explicit addresses ([../specs/email-notifications.md](../specs/email-notifications.md)). |
| Helper | [../../includes/Helper.php](../../includes/Helper.php), [../../includes/Helpers/](../../includes/Helpers/) | Static utilities: settings access (`get_settings()`, `wpsp_settings_v5()`), allowed post-type/taxonomy/role queries (`get_all_allowed_post_type()`, `is_user_allow()`), social profile/token storage (`get_social_profile()`), per-platform char limits (`get_social_platform_limits()`), category slug mapping, and content formatting. |
| Deps | [../../includes/Deps/](../../includes/Deps/) | Mozart-generated, prefixed third-party libraries (`WPSP\Deps\`). **Never edit by hand** — see [mozart-dependencies.md](mozart-dependencies.md). |

## Notes

- **Free vs Pro:** every subsystem here ships in Free. The paid `wp-scheduled-posts-pro` plugin extends the same classes and hooks (e.g. multi-account sharing unlocks the profile lists that Free trims to one, and the Auto/Manual schedulers plug into `manage_schedule`). Pro-only internals are documented under [../../../wp-scheduled-posts-pro/docs/](../../../wp-scheduled-posts-pro/docs/).
- **Instantiation gating:** Admin loads only in `is_admin()`; each social platform loads only when its profile status is enabled; `InstantShare` loads only when `Helper::is_user_allow()` passes.

## Source

- [../../CLAUDE.md](../../CLAUDE.md)
- [../specs/README.md](../specs/README.md)
