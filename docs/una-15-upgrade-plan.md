# UNA 14.0.0 → 15.x Upgrade Plan

> **Status:** Planning. Site is currently on **UNA 14.0.0** (`inc/version.inc.php`)
> and healthy. The target release the team looked at is
> [UNA 15.0.0-A1](https://github.com/unacms/UNA/releases/tag/15.0.0-A1).
>
> **Read this before running the Studio in-app updater. For this fork, the
> in-app updater is unsafe to run directly on production** — see §2.

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

## 7. What I (the repo tooling) can and cannot do

This repo is a copy of the 14.0.0 codebase + customizations; the live upgrade
runs on the hosting environment, which is not reachable from here. The port
worklist (§4) and merges (§5.5) are repo-side and can be prepared here against a
15.x source tree; the snapshot, staging, DB migration, and deploy (§3, §5.1–2,
§5.7) are operational steps on the host.
