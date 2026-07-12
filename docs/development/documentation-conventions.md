# Documentation Conventions

House style for the `docs/` tree. Read this before adding or restructuring a doc.

## Structure
- Every folder has a `README.md` index linking its contents in a table.
- **User guides** (plain-language, task-oriented) live in [../guides/](../guides/).
- **Developer specs** (per-feature technical detail) live in [../specs/](../specs/).
- **Architecture** (how the plugin is built) lives in [../architecture/](../architecture/); **API** in [../api/](../api/).

## Links
- Use **relative** links between docs (`../architecture/overview.md`) and into code (`../../includes/…`) so they resolve on GitHub and in editors.
- Cross-plugin links into Pro use `../../../wp-scheduled-posts-pro/docs/…`.
- When you move a doc, update inbound links (grep the `docs/` tree, `CLAUDE.md`, and the Pro plugin's docs).

## Writing style
- Guides: plain language for end users. Specs/architecture: precise, link source files by path.
- Mark incomplete docs with a `> **Status:** stub` blockquote.
- State Free vs Pro clearly — Pro logic lives in the sibling `wp-scheduled-posts-pro` plugin.

## Where things go
| Content | Folder |
| --- | --- |
| How to use a feature | [../guides/](../guides/) |
| How a feature works internally | [../specs/](../specs/) |
| How the plugin is built | [../architecture/](../architecture/) |
| REST / hooks surface | [../api/](../api/) |
| Contributor how-tos | [./](.) (development) |
