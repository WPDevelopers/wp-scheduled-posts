# CLAUDE.md — SchedulePress

> All project rules, conventions, architecture, and task guides live in [AGENTS.md](./AGENTS.md).
> Read that file first. This file only contains Claude Code-specific configuration.

---

## Response Style

- Be concise. No padding, no summaries of what you just did.
- Do not use emojis unless asked.
- Reference code locations as `file:line` so they are clickable.
- When something is uncertain, say so — do not guess silently.

## Tool Preferences

- Use dedicated tools (Read, Edit, Grep, Glob, Write) over Bash equivalents.
- Reserve Bash for commands that have no dedicated tool (running tests, builds, git).
- When searching the codebase, use Grep or Glob before spawning an agent.
- Prefer editing existing files over creating new ones.

## Allowed Bash Commands

These are pre-approved for this project and can be run without extra confirmation:

```bash
# Dependency install
composer install
npm install
cd includes/Admin/Settings && npm install

# Build
npm run build
npm run admin-start
npm start

# Tests & linting
./vendor/bin/phpunit
./vendor/bin/phpunit --testsuite unit
./vendor/bin/phpunit --testsuite integration
./vendor/bin/phpcs
./vendor/bin/phpcbf

# Git read-only
git status
git log
git diff
git branch
```

## Confirmation Required

Always ask before:
- Running `npm run release` or `npm run zip` (produces a distributable)
- Any `git commit`, `git push`, `git merge`, or `git rebase`
- Any destructive operation (`rm`, `git reset`, `git clean`)
- Creating new files (prefer editing existing ones)

## Memory

If you learn something non-obvious about how this project works, the team's preferences,
or decisions made during a session that would be useful in future sessions, save it to
`/Users/shakib/.claude/projects/-Users-shakib-Documents-wordpress-schedulepress/memory/`.
