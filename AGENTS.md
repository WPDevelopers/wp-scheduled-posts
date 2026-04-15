# AGENTS.md — SchedulePress (wp-scheduled-posts)

Rules and context for **all AI agents** working in this repository.
Applies to: Claude Code, GitHub Copilot, Cursor, Codex, Gemini, Windsurf, Cline, and any other AI tool.

---

## Execution Sequence

Always reply with **"Applying rules X, Y, Z"** when starting a task, listing which rules apply.

1. **Search first** — use search tools until finding similar functionality or confirming none exists. Be 100% sure before implementing.
2. **Reuse first** — check existing functions, patterns, and structure. Extend before creating new. Strive for the smallest possible change.
3. **No assumptions** — only act on: files you have read, user messages, and tool results. If information is missing, search then ask.
4. **Challenge ideas** — if you see flaws, risks, or better approaches, say so directly.
5. **Be honest** — state what is needed or problematic. Do not sugarcoat to please.
6. **Self-check** — periodically verify rule compliance during long conversations.
7. **Rule refresh** — re-read this file every few messages to stay compliant.

---

## Behavior Rules

### Must always do
- Read a file before editing it. Never assume its contents.
- Only change what was explicitly asked. No scope creep.
- Plan before coding — explain reasoning for complex suggestions.
- Verify that any file path, function, or constant you reference actually exists in the codebase before stating it.
- Ask the user when a task is ambiguous or could have unintended side effects.
- Run the relevant checks (see **Before Every Task**) before and after making changes.

### Must never do
- Write documentation unless explicitly asked.
- Start dev servers — assume they are already running.
- Modify legacy code without understanding the impact first.
- Break existing API contracts without explicit approval.
- Edit compiled output files — build from source instead.
- Edit `vendor/`, `node_modules/`, or `includes/Deps/` — these are auto-generated.
- Add comments, docblocks, or type annotations to code you did not write or modify.
- Add error handling, abstractions, or features for hypothetical future requirements.
- Refactor, reformat, or clean up code beyond what the task requires.
- Commit `.env` files, API keys, OAuth tokens, or any credentials.
- Use `--force`, `--no-verify`, or bypass any git hook or CI check.
- Push to the remote unless the user explicitly asks.
- Run destructive git commands (`reset --hard`, `clean -f`, `branch -D`) without user confirmation.

---

## Before Every Task

Before making changes, confirm the baseline is clean:

```bash
# PHP changes
./vendor/bin/phpcs          # check code standards
./vendor/bin/phpunit        # run tests

# JS / TS changes
npm run build               # confirm build passes

# Settings UI changes
npm run admin-start         # dev server — verify in browser
```

Run the same commands **after** your changes to confirm nothing regressed.

---

## Code Conventions

### PHP
- Indentation: **tabs** (WordPress standard — not spaces)
- Conditions: **Yoda style** — `if ( 'value' === $var )`
- All custom functions, filters, and actions must be prefixed with `wpsp_`
- Namespace all new classes under `WPSP\` (PSR-4, Composer-autoloaded)
- No raw SQL — use `$wpdb->prepare()` or the WP Options/Post Meta API
- All user-facing strings must be wrapped:
  ```php
  __( 'String', 'wp-scheduled-posts' )
  _e( 'String', 'wp-scheduled-posts' )
  ```

### JavaScript / TypeScript
- Functional React components only — no class components
- Use `@wordpress/data` for shared state — no external state libraries
- WordPress globals (`wp.*`, `jQuery`, `React`, `ReactDOM`) are injected by WordPress — never bundle them
- Build preset: `@wordpress/babel-preset-default` (already configured)

### CSS / SASS
- Write styles in `assets/sass/` — never write directly to `assets/css/`
- `assets/css/` is compiled output — edit the SASS source and rebuild
- Do not introduce new CSS frameworks without discussion

### General
- Keep imports alphabetically sorted
- Keep code SOLID but simple — separation of concerns without over-engineering
- Write tests for critical paths only, using the AAA pattern (Arrange, Act, Assert)

---

## Code Style & Quality

### Avoid Magic Numbers
- Do not use unexplained hardcoded values ("magic numbers") in code or tests.
- Define such values as named constants or use existing constants to clarify their meaning.

### Consistent Error Codes and Status
- When returning error codes and HTTP status, always be very specific — not only `200` and `500`.

### Prioritize Style and Developer Experience
- Always pay attention to clarity, maintainability, and ease of understanding, even if the underlying logic does not change.
- Code style and developer experience are important for long-term project health.

### Self-Documented Code
- Avoid adding comments that can be a constant or a well-named function.
- Always prefer to create small functions that describe themselves.
- Only add comments to explain "why" when it is truly not understandable from the code itself.
- Do NOT add comments that explain "what" the code does — the code should be self-explanatory.
- Examples of unnecessary comments to avoid:
  - `// Loop through items` (obvious from for loop)
  - `// Check if user exists` (obvious from if statement)
  - `// Return the result` (obvious from return statement)
  - JSDoc comments that just repeat the function name or parameter names
- Examples of valuable comments to keep:
  - Business logic explanations that aren't obvious from code
  - Workarounds for bugs in third-party libraries
  - Performance optimizations that might look odd without context
  - Complex algorithms where the "why" matters more than the "what"

---

## Security

Every change that touches user input or output must follow these rules:

| Situation | Required function |
|-----------|------------------|
| Sanitize text input | `sanitize_text_field()` |
| Sanitize integer input | `absint()` |
| Sanitize HTML content | `wp_kses_post()` |
| Escape HTML output | `esc_html()` |
| Escape attribute output | `esc_attr()` |
| Escape URL output | `esc_url()` |
| Database queries with input | `$wpdb->prepare()` |
| Form submissions / AJAX | Verify nonce with `check_ajax_referer()` or `wp_verify_nonce()` |
| Privileged actions | Check `current_user_can()` |

Never trust client-supplied data for file paths, eval-style operations, or direct DB queries.

---

## Git & Commits

- Never commit directly to `main` or `master` — use a feature branch
- Stage files by name explicitly — never `git add .` or `git add -A`
- Never amend a published commit — create a new commit instead
- Commit messages: concise, describe *why* not just *what*
- Never skip hooks (`--no-verify`)
- Never push without the user asking

---

## Off-limits Files

These files must **never** be manually edited:

| Path | Reason |
|------|--------|
| `vendor/` | Composer-managed — use `composer install` |
| `node_modules/` | npm-managed — use `npm install` |
| `includes/Deps/` | Mozart-generated vendor namespace bundles |
| `assets/js/*.min.js` | Webpack compiled output |
| `assets/css/` | SASS compiled output |
| `languages/*.mo` | Compiled translation binaries |

---

## Project Reference

### Identity
| Key | Value |
|-----|-------|
| Plugin name | SchedulePress |
| Version | 5.2.17 |
| Main file | `wp-scheduled-posts.php` |
| PHP namespace | `WPSP\` |
| Text domain | `wp-scheduled-posts` |
| REST API base | `/wp-json/wp-scheduled-posts/v1/` |
| Author | WPDeveloper |

### Key Entry Points

| What | File |
|------|------|
| Plugin bootstrap & main class | `wp-scheduled-posts.php` |
| Admin UI controller | `includes/Admin.php` |
| Settings (PHP) | `includes/Admin/Settings.php` |
| Settings (React/TSX) | `includes/Admin/Settings/app/Settings/` |
| Social sharing dispatcher | `includes/Social.php` |
| Platform integrations | `includes/Social/*.php` |
| REST API | `includes/API.php`, `includes/API/` |
| Gutenberg sidebar | `index.js` |
| Email notifications | `includes/Email.php` |
| Helpers | `includes/Helper.php`, `includes/Helpers/` |

### Build Commands

| Command | What it does |
|---------|-------------|
| `npm run build` | Production build |
| `npm start` | Dev watch (Gutenberg sidebar) |
| `npm run admin-start` | Dev watch (Settings React UI) |
| `npm run pot` | Generate `.pot` translation file |
| `npm run release` | build + pot + ZIP archive |
| `composer install` | Install PHP dependencies |

### Test & Lint Commands

```bash
./vendor/bin/phpunit                         # all tests
./vendor/bin/phpunit --testsuite unit        # unit tests only
./vendor/bin/phpunit --testsuite integration # integration tests only
./vendor/bin/phpcs                           # PHP code standards check
./vendor/bin/phpcbf                          # auto-fix PHP standards
```

### PHP Architecture
- **Pattern:** Singleton `WPSP` class with service-locator getters
- **Boot:** `WPSP_Start()` → `new WPSP()` → `init_plugin()` on WordPress `init` hook
- **Services:** `getAdmin()` · `getAssets()` · `getEmail()` · `getSocial()` · `getAPI()`
- **Vendor isolation:** Mozart bundles all third-party namespaces under `WPSP\Deps\`

### Runtime Constants

| Constant | Purpose |
|----------|---------|
| `WPSP_VERSION` | Plugin version string |
| `WPSP_ROOT` | Absolute path to plugin root |
| `WPSP_URL` | URL to plugin root |
| `WPSP_ASSETS` | URL to `assets/` directory |
| `WPSP_TESTING` | `true` during PHPUnit test runs |

### Common Task Guides

**Add a social platform**
1. Create `includes/Social/PlatformName.php` (mirror an existing platform)
2. Register it in `includes/Social.php`
3. Add settings fields in `includes/Admin/Settings.php`
4. Add OAuth flow if needed

**Modify the settings page**
- PHP: `includes/Admin/Settings.php` (large — search for the right section)
- React: `includes/Admin/Settings/app/Settings/`
- Dev server: `npm run admin-start`

**Add a REST endpoint**
- Register via `register_rest_route()` in `includes/API.php` or a new file under `includes/API/`
- Namespace: `wp-scheduled-posts/v1`

**Release**
1. Bump version in `wp-scheduled-posts.php`, `package.json`, `readme.txt`
2. `npm run release` → produces `wp-scheduled-posts.{version}.zip`
