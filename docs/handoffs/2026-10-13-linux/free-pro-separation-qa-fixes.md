# Handoff: QA fixes on feat/free-pro-separation (free plugin)

Written 2026-10-13 while moving this work off a Mac onto Linux. The work below
is finished and pushed. What is left is listed under "Not done".

- Repo: `wp-scheduled-posts`
- Branch: `feat/free-pro-separation`, open as PR #120 against `dev`
- Companion: `wp-scheduled-posts-pro` on a branch of the same name, PR #18. See
  that repo's copy of this file under the same path.

## What this branch does

It moves Google Business Profile out of the free plugin and into Pro, leaving
free with a locked upsell card and a generic registry (`WPSP\Social\Platforms`)
that any Pro network can describe itself through. See
[docs/specs/free-pro-separation.md](../../specs/free-pro-separation.md).

## QA round this handoff covers

Nahid and N.Hasan tested both PRs on 2026-10-11 and left their findings as PR
comments. The free verdict was "separation works, one regression blocks merge".
Every finding was checked. Five were fixed, one was answered as not a bug.

| Finding | Severity | Outcome |
|---|---|---|
| Post panel dropped Google Business Profile | High, blocking | Fixed, checked in a browser |
| Classic editor checkbox id did not match its script selector | Low, latent | Fixed |
| Locked surfaces sold Pro to people who already had Pro | Low | Fixed |
| Character limits docblock attached to the wrong function | Low | Fixed |
| A Pro beta was told to update to the version it already was | Low | Fixed, with tests |
| Free updating before Pro pauses Google Business shares | Note | Documented in `readme.txt` |
| Elementor textarea name does not match its script | Low | Not a bug on this branch, see below |

## Commits

| SHA | Subject |
|---|---|
| `6589803c` | Read the post panel's platform list from the registry |
| `58883e8f` | Derive the classic-editor share checkbox ids from the platform slug |
| `94b526b1` | Ask for an update, not a sale, when Pro is already installed |
| `6dc1e3f1` | Let a Pro pre-release satisfy the minimum version |
| `942ff684` | Rebuild the bundle, refresh the pot, and flag the update order |

### The blocking regression

`src/components/Content/SocialShare.js` kept its own hardcoded `PLATFORM_CONFIG`
and `PLATFORM_ORDER`. Removing `google_business` from those tables took the
network out of "Selected Social Platforms" even with Pro active and a profile
connected. Share Now sends only what is in that map, so it skipped the network
too, and a post whose only saved selection was Google Business failed
`hasSavedSelection` and fell back to sharing everywhere.

The panel was the last of five consumers still on a hardcoded list. It now
merges the built-in networks, which carry inline SVGs, with whatever the
registry reports, whose icons arrive as URLs and get wrapped in an `img`. A
registered platform's profiles are read from the option key the registry names.

`assets/js/app.min.js` is a tracked, shipped file, so the fix does nothing until
it is rebuilt. Commit `942ff684` rebuilds it. Rebuild it again after any further
change under `src/`.

### Elementor naming, answered rather than changed

QA flagged that the Elementor textarea is `wpsp-el-content-google-business`
while `assets/js/elementor-editor.js` reads `wpsp-el-content-google_business`.
That mismatch is on `dev` already: `dev`'s `includes/Admin.php` printed the
hyphenated name, and Pro now prints the same hyphenated name from
`includes/Admin/GoogleBusinessSettings.php`. Behaviour is identical to `dev`, so
this branch neither caused nor worsened it. It needs its own ticket.

## Checks actually run

Run on macOS against a local sandbox, not in CI. No CI is configured on this
branch in either repo.

- Free unit suite: `OK (105 tests, 219 assertions)`. The branch stood at 100
  before this round, so the three new registry tests and two new version tests
  account for the rest.
- `php -l` clean on every edited PHP file. `node --check` clean on
  `assets/js/wpsp-socialprofile.js`.
- `npm run build` succeeded. `assets/js/app.min.js` md5
  `7412831f1f19f66351e4de15506b9658`.
- Live install, both plugins active at 5.4.0:
  - Registry reports `registered=[google_business]`, `locked=[]`. With Pro off
    that reverses.
  - The panel lists Facebook and Google Business Profile, the latter with its
    icon served from the registry URL.
  - A post whose only saved selection is Google Business renders exactly that
    one card and does not fall back to every enabled network.
  - With Pro off the card retires, the locked badge reads `PRO`, and no page
    throws.
  - `Platforms::upsell_cta()` returns `PRO` plus the pricing link with no Pro,
    and `UPDATE` plus a link to the plugins screen and "5.4.0 or newer" when
    Pro 5.3.3 is installed.
  - The script derives `#wpscpprogoogle_businessis`, which is what the markup
    prints.
  - No fatals in the sandbox log. The settings screen renders Google Business
    as a real field, not an upsell.

### Not run

- The integration suite. The WordPress test library is not installed here, so
  `tests/integration/GoogleBusinessRemovalTest.php` has still never executed.
- `composer test`. `composer install` fails on this machine, so `vendor/bin/phpunit`
  is missing and the `tests/run-phpunit.php` zero-test guard was bypassed. The
  suite ran under a global PHPUnit 9.6.31 with
  `phpunit --configuration phpunit.xml`. **Run it through `composer test` on
  Linux**, which is the gate this repo documents.
- `phpcs`. `vendor/bin/phpcs` is absent for the same reason.
- Any real Google OAuth connect. See the Pro handoff.

## Not done

1. **No reply posted to PR #120, and no label restored.** Both PRs still carry
   no QA label, which keeps them out of every tester's queue. The fixes need a
   plain-language reply and then `Needs Re-Test`. Deferred because this
   migration pass was told not to message anyone.
2. **The commit trailer question is unresolved.** The seven commits already on
   this branch from the earlier session each end in a `Co-Authored-By` line. An
   earlier instruction said that count must be zero before pushing. Removing
   them needs a history rewrite and a force push, which this migration pass was
   told not to do, so they stand. The five commits added in this round add none.
   Neither PR has inline review comments, so a rewrite would orphan nothing if
   someone decides to do it later. This needs a human decision.
3. The Elementor naming ticket is not filed.

## Files that live only on this Mac

Nothing here is needed to continue, but none of it transfers.

- `sandbox.config.json` at the repo root, untracked on purpose. It defines the
  local test site and points at Mac paths. Recreate it on Linux with the paths
  adjusted:

  ```json
  {
    "slug": "wpsp-separation",
    "plugins": {
      "wp-scheduled-posts": ".",
      "wp-scheduled-posts-pro": "<path to the pro checkout>"
    },
    "phpVersion": "8.1",
    "server": "nginx",
    "tld": "tst",
    "wpCron": { "enabled": false }
  }
  ```

- The sandbox instance and its seeded Google Business profile and post. Both
  were created by hand and are disposable. Recreate rather than move.
- `/Users/alim/Sites/git/.scratch/wp-scheduled-posts/feat-free-pro-separation/`
  holds the investigation notes and the throwaway patch scripts used this
  round. Evidence only.
- `node_modules` and `vendor`, both reinstallable. `vendor` is incomplete here
  anyway.

## Pending remote work

None. No CI runs on this branch, nothing is queued, and no job is waiting.

## Continuing on Linux

```
Pick up SchedulePress PR #120 (wp-scheduled-posts, branch
feat/free-pro-separation) and PR #18 (wp-scheduled-posts-pro, same branch
name). Both are pushed and green on my own checks. Read
docs/handoffs/2026-10-13-linux/free-pro-separation-qa-fixes.md in each repo
first.

Do this:
1. Get the suites running the way the repo documents. `composer install`, then
   `composer test` in free and the unit suite in Pro. Confirm free still
   reports 105 tests. Then provision the WordPress test library with
   bin/install-wp-tests.sh and run `composer test:integration`, which has never
   executed: tests/integration/GoogleBusinessRemovalTest.php is the one that
   matters. Run phpcs too.
2. Stand up a local site with both plugins active at 5.4.0 and re-confirm the
   blocking fix: with a Google Business profile connected, the Gutenberg panel's
   "Selected Social Platforms" must list it, and Share Now from the panel must
   include it.
3. Decide the Co-Authored-By trailer question in "Not done" item 2 before
   anything merges, since clearing it needs a force push.
4. Then reply on both PRs in plain words, no mention of tooling, and set
   `Needs Re-Test` on each.

Do not force push, retag, or merge without being asked.
```
