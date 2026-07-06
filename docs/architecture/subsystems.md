# Subsystems

> **Status:** stub — outline only.

The `includes/` subsystems and what each owns. Per-feature behaviour is specced in [../specs/](../specs/).

## Map
_TODO — table:_

| Subsystem | Path | Owns |
| --- | --- | --- |
| Installer / Migration | [../../includes/Installer.php](../../includes/Installer.php), [../../includes/Migration.php](../../includes/Migration.php) | Activation, schema/version transitions. |
| Admin | [../../includes/Admin/](../../includes/Admin/) | `Calendar`, `Menu`, `Metabox/`, `Notices/`, `Rule(s)` (access control), `Widgets/` (dashboard widget), React **Settings** app. |
| Social | [../../includes/Social/](../../includes/Social/) | One class per platform + `SocialProfile`, `SocialReconnection`/`ReconnectHandler`; see [social-architecture.md](social-architecture.md). |
| API | [../../includes/API.php](../../includes/API.php), [../../includes/API/](../../includes/API/) | REST endpoints — see [../api/rest-endpoints.md](../api/rest-endpoints.md). |
| Email | [../../includes/Email.php](../../includes/Email.php) | Schedule / review notification emails ([../specs/email-notifications.md](../specs/email-notifications.md)). |
| Helper | [../../includes/Helper.php](../../includes/Helper.php) | Static utilities: post-type/taxonomy/role queries, settings access, social token storage, char limits, formatting. |

## Source
- [../../CLAUDE.md](../../CLAUDE.md)
- [../specs/README.md](../specs/README.md)
