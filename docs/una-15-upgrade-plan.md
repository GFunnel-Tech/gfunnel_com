# UNA 14.0.0 → 15.x Upgrade Plan

> **Status:** File-level upgrade **DONE on branch `claude/unacms-update-issue-dyij3w`.**
> The codebase on this branch is now **UNA 15.0.0-RC1** with every GFunnel
> customization ported on top (see §8 for the execution record). Production is
> still on 14.0.0; the remaining work is host-side — deploy this tree to
> **staging**, run the DB migration, and test (§9).
>
> Target is **15.0.0-RC1** (Release Candidate). There is still **no 15.0.0
> stable** — this is a staging/preview build, not for production until 15.0.0
> final ships and staging is verified.
>
> **Do NOT run the Studio in-app updater on production for this fork** — it
> overwrites the customized core files in place (§2). The upgrade was done as a
> controlled 3-way merge in git instead.

---

## 1. TL;DR / recommendation

1. **Do not put 15.0.0-A1 on production.** It is an **Alpha** (pre-release,
   released 2025-10-08, ~135 changes incl. session refactor, new caching,
   logging overhaul, one-way DB migrations). Alpha builds are for testing, not
   live sites. **Wait for a 15.x stable**, and only ever exercise the Alpha on a
   throwaway staging clone.
2. **Never click "Update" in Studio on the live site for this fork.** The
   updater overwrites upstream core files *in place*. This fork has **18 marked
   in-place edits** to those exact files, so an in-place update wipes them and
   leaves the DB half-migrated → white screen / fatals.
3. **The correct path is:** snapshot prod → stand up a staging clone → update
   staging → re-apply the marked `GFunnel` customizations onto the new file
   versions → test → deploy staging to prod in a maintenance window, keeping the
   snapshot as the rollback.
4. **Do it once, properly:** while re-applying, move the heaviest in-place edits
   into subclasses / `BxDolAlerts` hooks / `gfunnel` modules (per
   `CLAUDE.md §7`) so the *next* UNA update no longer clobbers them.

---

## 2. Why the in-app updater breaks *this* site specifically

Stock UNA expects customizations to live in your own modules and template, not
in edits to core files. This fork does the opposite in several places — it edits
upstream files directly (documented as an anti-pattern in `CLAUDE.md §7`:
"editing `boonex/`, `base/`, `inc/classes/`, `template/scripts/` is overwritten
on UNA upgrade").

The Studio updater (and any manual file-replace upgrade) **replaces upstream
files with the new version's copies**. So on a 14→15 update:

- Every marked `GFunnel` edit inside an upstream file is **overwritten and lost**.
- The surrounding GFunnel code (modules, root pages) still *calls* those edits →
  the site errors because the hooks it depends on are gone.
- 15 runs **one-way DB migrations**; once they run, rolling back files alone is
  not enough — you must also restore the database.

That is a two-part failure (lost code + migrated DB) and it is why the update
"has an issue" for us when it is smooth for a stock UNA site.

---

## 3. Pre-update safety net (do this first, every time)

Before touching anything, on the live host:

1. **Full file backup** of the web root (the whole `public_html`).
2. **Full database dump:** `mysqldump -u <user> -p <db> > una14_pre_upgrade.sql`
   (Hostinger's automatic backups also work — confirm one exists and note its
   timestamp.)
3. Verify both backups are **restorable**, not just present.

**Rollback = restore files + restore the SQL dump.** Because 15's migrations are
one-way, the DB dump is the non-negotiable half — a file-only rollback will not
undo the schema changes.

---

## 4. The port worklist — customizations a 14→15 update will clobber

All in-place edits are marked with `GFunnel` / `gf_` comment anchors, so they are
greppable. Regenerate this list anytime:

```
grep -rlnE "GFunnel|gf_ws|gf_sidebar|__gf_" \
  inc/ modules/base/ modules/boonex/ template/ | grep -v '^modules/gfunnel/'
```

### A. In-place edits to UPSTREAM files — MUST be re-applied after the update

These are the breakage points. "Markers" = count of `GFunnel`/`gf_` anchors in
the file (a proxy for how much was changed).

| File | Markers | What the edit powers | Re-port target |
|---|---:|---|---|
| `template/scripts/BxBaseFunctions.php` | 30 | The whole workspace shell chrome: two-bar header, **workspace selector**, **active workspace (`gf_ws`)** logic, **time-tracker** pill, hub-tab subheader, **left sidebar** (`getGfSidebar`) | Highest priority. Subclass `BxTemplateFunctions` / template functions in a `gfunnel` module; move renderers out of core. |
| `inc/gf_home_blocks.inc.php` | 33 | Shared homepage + SEO-landing section renderers | GFunnel-owned include; low upstream-collision risk but re-verify against 15 template APIs |
| `modules/boonex/messenger/classes/BxMessengerTemplate.php` | 8 | Messenger two-pane / Facebook-style skin hooks | Subclass or `BxDolAlerts` hook; stop editing core |
| `inc/utils.inc.php` | 2 | Content-heavy page tuning + drop `X-Powered-By` (white-labeling) | Move to a bootstrap hook / server header config |
| `modules/boonex/organizations/classes/BxOrgsModule.php` | 2 | Structured workspace **overview** service block | Subclass `BxOrgsModule` in a gfunnel module |
| `modules/base/groups/classes/BxBaseModGroupsModule.php` | 1 | `serviceOverviewStructured()` for workspace orgs/spaces/groups | Subclass in a gfunnel module |
| `modules/boonex/persons/classes/BxPersonsModule.php` | 1 | Person profile **overview** service block | Subclass `BxPersonsModule` |
| `template/scripts/BxBaseMenu.php` | 1 | Menu rendering tweak | Subclass |
| `template/scripts/BxBaseMenuAccountPopup.php` | 1 | Account popup menu | Subclass |
| `template/scripts/BxBaseMenuFooter.php` | 1 | Footer menu | Subclass |
| `template/scripts/BxBaseServiceProfiles.php` | 1 | Profile service tweak | Subclass |
| `modules/boonex/nexus/template/js.html` | 1 | Nexus JS injection | Template override in gfunnel skin |
| `modules/boonex/artificer/data/template/system/pt_application.html` | 2 | The `__gf_sidebar__` slot in the workspace shell layout | Artificer template override, not a core edit |
| `modules/base/groups/template/overview_structured.html` (+ `css`) | 2 | Workspace overview markup/skin | Template override |
| `modules/boonex/persons/template/overview.html` (+ `css`) | 1 | Person overview markup/skin | Template override |
| `modules/boonex/organizations/template/overview.html` (+ `css`, `main.css`) | 1 | Org overview markup/skin | Template override |
| `modules/boonex/messenger/template/talk-header.html` | 4 | Messenger header skin | Template override |
| `modules/boonex/messenger/template/lots-briefs.html` | 4 | Messenger conversation list skin | Template override |
| `modules/boonex/messenger/template/css/gf-facebook-skin.css` | — | Messenger skin CSS | Ships in gfunnel skin dir |

**Method for each row:** do a **3-way merge** — take 15's new version of the file
as the base, find the `GFunnel`-marked hunk in the 14 version, and re-apply that
hunk onto 15's structure (15's session/template refactor may have moved the code
the hunk attached to). Do **not** blind-copy the whole 14 file over 15's.

### B. Net-new `gf_*` assets in the shared `template/` dir (15 files)

`template/css/gf_*.css` and `template/js/gf_*.js` (applications, auth, bug,
header, home, onboarding, sidebar, timer) plus `template/_gf_sidebar.html`,
`template/_page_toolbar_*.html`, `template/page_home*.html`,
`template/page_workspaces.html`. These are *additions*, so the updater will not
overwrite their content — but confirm the update process does not **prune**
unknown files from `template/`. If it does, re-copy them from the snapshot.

### C. GFunnel-owned modules — should survive, but re-test against 15

`modules/gfunnel/{applications, home, onboarding_module, sitemap}` are self-owned
and untouched by a core update. Still smoke-test them on 15: the session and
template-API changes in 15 can break assumptions in `service*` blocks and the
signed `api/` surface even without a file change.

---

## 5. Step-by-step runbook

1. **Snapshot prod** — files + `mysqldump` (§3). Confirm restorable.
2. **Stand up staging** from that snapshot on a subdomain / separate DB. Confirm
   staging renders correctly on 14.0.0 first.
3. **Record the worklist** on staging: run the grep in §4 and diff it against the
   table above so nothing new has crept in since this doc.
4. **Update staging** — Studio in-app updater (or manual file replace) to the
   target 15.x. Let its DB migrations run; clear caches (`Studio → Clear cache`
   or delete `cache/`, `cache_public/`).
5. **Re-apply §4.A** with 3-way merges. Re-copy §4.B if pruned.
6. **Smoke-test the customized surfaces** on staging:
   - Left **sidebar** renders (`__gf_sidebar__` slot filled)
   - Workspace **selector** + active `gf_ws` switching
   - **Time-tracker** pill/popup
   - Person / org / workspace **overview** blocks
   - **Messenger** two-pane Facebook skin
   - **Home** + SEO landing pages (`/applications`, `/marketplace`, `/business`,
     `/services`, `/resources`)
   - Onboarding signed `api/` flow
7. **Deploy to prod** in a maintenance window (15 adds a maintenance mode — use
   it). Keep the §3 snapshot as the rollback.
8. **Post-deploy:** watch the new DEBUG/INFO/WARN/ERROR logs (15 feature) for the
   first hours.

---

## 6. Recommended: retire the in-place edits (so the next update is painless)

The reason this upgrade is hard is §4.A. While porting, convert the biggest
offenders from core edits into the mechanisms `CLAUDE.md §7` prescribes:

- **`BxBaseFunctions.php` (30 edits)** → the single largest liability. Move the
  workspace shell renderers (sidebar, workspace selector, timer pill, hub tabs)
  into a `gfunnel` module + template overrides, exposed as service blocks, so
  core stays stock.
- **Overview blocks** (`persons`, `organizations`, `base/groups`) → subclass the
  module classes in a gfunnel module and register the subclass; drop the core
  edits.
- **Messenger skin** → `BxDolAlerts` hook + template overrides instead of editing
  `BxMessengerTemplate.php`.

Do this incrementally as part of the port; each one removed is one fewer thing
that breaks on 15.x.stable and every future release.

---

## 7. What is repo-side vs host-side

This repo is a copy of the codebase; the live upgrade runs on the hosting
environment, not reachable from here. **Done repo-side (§8):** the whole file
tree upgraded to 15.0.0-RC1 with customizations 3-way-merged. **Still host-side
(§9):** snapshot, staging deploy, DB migration, and testing.

---

## 8. Execution record — how the 14→15.0.0-RC1 merge was done

Done as two commits on `claude/unacms-update-issue-dyij3w`, using a real 3-way
merge against pristine source trees (not the in-place updater):

- Baseline = pristine **UNA 14.0.0**, target = pristine **UNA 15.0.0-RC1**
  (both from the official GitHub release assets), ours = this repo.
- Diffing the repo against pristine 14.0.0 classified all 11,687 tracked files:
  **8,017 unchanged** stock, **3,619 net-new** (GFunnel + third-party),
  **51 modified** upstream, **0 collisions**, **0** of our edits removed in 15.

**Commit 1 — `chore(core): upgrade UNA core 14.0.0 -> 15.0.0-RC1 (pristine)`**
- Every unmodified stock file upgraded to its 15 version (3,206 actually
  differed), **6,119 new 15 files added**, **172** stock files 15 removed were
  dropped. All GFunnel/third-party modules, root pages, and template assets
  preserved. `install/` left untracked as before.

**Commit 2 — `feat(core): re-apply GFunnel customizations onto UNA 15.0.0-RC1`**
- 24 upstream files carry real GFunnel edits. **17 merged cleanly** — including
  the entire workspace shell (`BxBaseFunctions.php`), `r.php` SEO routing,
  `.htaccess`, the overview blocks, and the menus.
- **7 conflicts resolved:**

  | File | Resolution |
  |---|---|
  | `inc/classes/BxDolDb.php` | Took 15's `error()`; re-added the `$sOutput=''` guard (15 still leaves it undefined when visual processing is off — the bug the GFunnel edit fixed) |
  | `studio/classes/BxDolStudioToolsAudit.php` | Kept GFunnel's `shell_exec` neutralization (host hardening) |
  | `template/_page_toolbar.html` | Kept `__gf_toolbar__` (custom header owns the bar) |
  | `modules/boonex/artificer/.../pt_application.html` | Kept `__gf_toolbar_app__` (workspace shell) |
  | `inc/classes/BxDolTemplate.php` | Took 15's richer OG/meta — see **deferred** below |
  | `modules/boonex/artificer/install/langs/en.xml` | Took 15's strings (pure lang drift, no GFunnel keys) |
  | `modules/boonex/persons/install/langs/en.xml` | Took 15's strings (pure lang drift) |

- Verification: all hand-merged PHP files `php -l` clean (PHP 8.4), lang XML
  well-formed, **no conflict markers** anywhere.

### Deferred (not carried over — needs a deliberate re-layer)

- **GFunnel SEO meta extras in `BxDolTemplate.php`** — Twitter cards
  (`twitter:title/description/image`), `og:site_name`, and the extra `og:url`
  canonical. UNA 15 rewrote this method with its own richer OG handling
  (dynamic `og:type` by content). Grafting GFunnel's block back verbatim would
  reference variables that no longer exist in 15's scope (`$sMetaTitleTag`,
  `$sMetaDescTag`), so it was intentionally left out. Re-add against 15's
  variable names if the Twitter cards are wanted.

### Compatibility not yet verified (runtime, needs staging)

- The 24 in-place edits are ported, but 15's **session refactor** and
  **template-API** changes can still affect GFunnel/third-party code that
  *calls* core without editing it — the `modules/gfunnel/*` service blocks and
  signed `api/`, plus third-party modules (modzzz, aqb, smsoftwares, …). These
  need the staging smoke test (§9), not a file merge.

---

## 9. Staging deploy + DB migration runbook (host-side, remaining work)

The file tree is ready; the **database is still at the 14.0.0 schema**. UNA
compares file version vs DB version (`bx_get_ver`) and will require the DB
migration before the site runs correctly on 15.

1. **Snapshot production** — full files + `mysqldump` (see §3). Non-negotiable.
2. **Stand up staging** from that snapshot (separate subdomain + DB). Confirm it
   renders on 14.0.0 first.
3. **Deploy this branch's tree** onto staging (git checkout / rsync). Keep the
   host's own `inc/header.inc.php`, `storage/`, `cache*/`, `logs/` — those are
   gitignored and environment-specific; do not overwrite them.
4. **Run the 15 DB migration** on staging. UNA detects the file>DB version gap
   and drives the update from **Studio → Dashboard**; follow UNA's 15 upgrade
   notes for the multi-step (14→15) DB deltas. Clear caches afterwards
   (Studio → Clear cache, or delete `cache/` + `cache_public/`).
5. **Smoke-test the customized + dependent surfaces** (from §5.6): left sidebar,
   workspace selector + `gf_ws` switching, time-tracker, person/org/workspace
   overview blocks, Messenger skin, home + SEO landings (`/applications`,
   `/marketplace`, `/business`, `/services`, `/resources`), onboarding `api/`,
   and the third-party modules.
6. **Only after staging is clean and 15.0.0 *stable* has shipped**, promote to
   production in a maintenance window, keeping the snapshot as rollback.

> Because 15's migrations are one-way, the pre-migration DB dump is the only
> real rollback. Never run step 4 against production without it.
