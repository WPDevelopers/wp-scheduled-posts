# Architecture Overview

> **Status:** stub — outline only. Fill in following [../development/documentation-conventions.md](../development/documentation-conventions.md).

The big picture of how SchedulePress is assembled. Entry point for the [architecture/](./) section.

## What SchedulePress is
_TODO — content scheduling + social auto-sharing; the `WPSP` engine; Pro features live in the sibling `wp-scheduled-posts-pro`._

## The engine & namespace
_TODO — `final class WPSP` ([../../wp-scheduled-posts.php](../../wp-scheduled-posts.php)), static singleton `WPSP::init()`; namespace `WPSP\` → `includes/` (PSR-4); procedural helpers `wpsp_*`/`wpscp_*` in [../../includes/functions.php](../../includes/functions.php)._

## Subsystem map
_TODO — Installer/Migration, Admin, Social, API, Email, Helper. Deep dive: [subsystems.md](subsystems.md)._

## Where to go next
- [plugin-lifecycle.md](plugin-lifecycle.md) — bootstrap chain & constants
- [subsystems.md](subsystems.md) — the `includes/` subsystems
- [social-architecture.md](social-architecture.md) — how social auto-sharing works
- [settings-and-data.md](settings-and-data.md) — the settings option & migrations
- [mozart-dependencies.md](mozart-dependencies.md) — prefixed vendored libs

## Source
- [../../wp-scheduled-posts.php](../../wp-scheduled-posts.php) — entry point, `WPSP` engine
- [../../CLAUDE.md](../../CLAUDE.md) — architecture summary
