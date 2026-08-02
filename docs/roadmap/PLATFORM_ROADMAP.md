# GFunnel Platform Roadmap

> **What this is.** A module-by-module plan of fixes, expansions, tests, and
> documentation for every GFunnel-owned surface, plus the cross-cutting
> initiatives that touch all of them. It exists so we can plan and build a
> better platform in a deliberate order instead of firefighting.
>
> **Scope.** GFunnel-authored code only — the four `modules/gfunnel/*` modules,
> the root `gf_*.php` feature endpoints, the shared `inc/gf_*.inc.php` renderers,
> the GFunnel root pages (`home.php`, `workspaces.php`), and the Supabase edge
> functions. Upstream `boonex/*` and `inc/classes/BxDol*` are **extend-only**
> (see CLAUDE.md §2B/§7) and are out of scope except where we hook them.
>
> **How it was built.** Eight parallel read-only engineering audits, one per
> surface, each citing `file:line`. Findings are consolidated here; the per-module
> line references are the actionable backlog.
>
> **Priority key.** `P0` = security/correctness/blocks-a-fresh-deploy · `P1` =
> significant defect or trust/maintainability risk · `P2` = hardening,
> refactor, or feature expansion. Dates in this doc are relative to the roadmap
> creation and should be re-baselined when scheduled.

---

## 1. Executive summary

The GFunnel layer is **feature-rich but pre-production on security and
foundations**. Individual features are surprisingly complete (per-workspace
billing in the timer, a streaming sitemap with index-splitting, a
workspace-scoped app hub with IDOR guards). But the same class of defect recurs
across nearly every surface, and there is **no automated test coverage or CI
anywhere in the repo**.

**The five findings that repeat across modules — fix these as platform
initiatives, not per-module (see §2):**

1. **CSRF-via-GET.** Almost every state-changing endpoint (`gf_menu`,
   `gf_sidebar`, `gf_timer`, `gf_bug`, and *all* workspace admin/invite actions)
   accepts mutations with no CSRF token, and most accept them over `GET`. A
   single `<img>` tag can wipe a victim's menu, delete time entries, or flip
   workspace roles.
2. **`getGfActiveWorkspaceId()` trusts client `gf_ws`** with no membership
   check (`BxBaseFunctions.php:251`). The apps path defends against this
   explicitly; menu/sidebar/timer/bug do not.
3. **Committed / plaintext secrets.** Supabase URL + anon JWT hardcoded in
   source, the write-back service key stored plaintext in the DB, and an
   onboarding HMAC fallback secret committed to the repo.
4. **Runtime DDL & missing install SQL.** Several modules `CREATE TABLE` on the
   web request path or never create their tables at all — onboarding and the
   homepage's "live" data tables are non-functional on a fresh deploy.
5. **No tests, no CI.** No `composer.json`, no `phpunit`, no `.github/workflows`,
   no test directory. Every fix above ships unverified.

**Maturity snapshot:**

| Module / surface | Maturity | Headline risk |
|---|---|---|
| Onboarding | 🔴 Prototype / non-functional | Missing install SQL; dead login hook; unverified HMAC |
| Applications / Hub | 🟡 Functional, gaps | Committed creds + plaintext key; no role tier on shared writes |
| Home + SEO landings | 🟡 Polished shell, hollow data | Backing tables never provisioned; dead markers; duplicated renderers |
| Sitemap | 🟢 Mature, needs scaling | Inline rebuild can time out crawlers; in-memory dedup ceiling |
| Workspaces + Admin | 🟡 Rich, insecure | CSRF-via-GET on all actions; stored XSS; admin escalation |
| Timer | 🟡 Rich, monolithic | CSRF; concurrent-timer races; billing drift |
| Auth / Bug / Menu / Sidebar | 🟡 Mostly careful | CSRF-via-GET; sidebar JS-context XSS |
| Supabase functions | 🟡 MVP | YouTube quota overrun; non-atomic backfill |

---

## 2. Cross-cutting platform initiatives

These are the highest-leverage work items because each closes the same defect
across many modules. **Do these first (Phase 0/1).**

### CC-1 · CSRF + POST-only enforcement (P0)
A shared helper that every state-changing `gf_*` handler calls: require
`REQUEST_METHOD === 'POST'` and verify a token via UNA's existing
`BxDolForm::isCsrfTokenValid`. Emit the token into every form/JS caller.
- **Endpoints to convert:** `gf_menu.php:66`, `gf_sidebar.php:97`,
  `gf_timer.php:507`, `gf_bug.php:34`, and the workspace handlers
  (`workspaces.php:780,788,799,815,862,875` + the `method="get"` forms in
  `page_workspaces_member_item.html` / `manage.html`).
- **Deliverable:** `inc/gf_security.inc.php` with `gfRequirePost()` +
  `gfVerifyCsrf()`; JS helpers thread the token; regression tests assert every
  mutation is rejected without a valid token and over GET.

### CC-2 · Trust boundary for `gf_ws` (P0/P1)
Centralize a membership check inside `getGfActiveWorkspaceId()`
(`BxBaseFunctions.php:251`) so pinning a workspace requires owner/joined
membership — mirroring the guard already in `gf_app_blocks.inc.php:169`. Removes
per-endpoint drift in menu/sidebar/timer/bug.

### CC-3 · Secrets out of source & DB-plaintext (P0)
- Move the Supabase URL + anon key out of `inc/gf_app_blocks.inc.php:300-301`
  into `sys_option`/env; remove the literal default.
- Encrypt (or relocate to env) the write-back service key
  (`gf_app_config.v`, `:302,520`); add clear/rotate affordance.
- Remove the committed onboarding HMAC fallback secret
  (`BxGfunnelOnbModule.php:10`); **fail closed** if the env var is unset.
- Rotate every credential that has touched the repo.
- Constant-time compares for all shared-secret checks (Supabase fns
  `:180/:94`).

### CC-4 · Kill runtime DDL; ship install SQL (P0)
Adopt the pattern that modules provision schema through their installer
(`execute_sql` + `install/sql/*.sql`), never on the request path.
- **Missing entirely:** onboarding (`gfo_onboarding_data`, the two
  `bx_persons_data` columns) and the homepage "live" tables
  (`gf_departments`, `gf_content_objects`, `gf_community_posts` — DDL exists
  only in `docs/sql/`).
- **Runtime DDL to relocate:** `workspaces.php:60`, `gf_sidebar.php:37`,
  `gf_timer.php:48/55-99`, `gf_bug.php` table self-provisioning.

### CC-5 · Test harness & CI (P0 foundation)
There is no way to verify any of the above today.
- Bootstrap `composer.json` + PHPUnit with a UNA bootstrap stub so module
  classes and the `inc/gf_*` pure functions are unit-testable.
- Add Deno tests for the two Supabase functions.
- Add `.github/workflows/ci.yml`: PHP lint (`php -l`), PHPUnit, Deno test,
  and a marker-completeness check for templates.
- Seed the suite with the per-module test lists in §3.

### CC-6 · Escaping discipline (P0/P1)
Two confirmed JS-context XSS holes come from using `bx_html_attribute`
(HTML-attribute escaping) inside an `onclick=""` JS string — the browser
decodes the entity before JS parses:
- `gf_sidebar.php` iframe onclick via `BxBaseFunctions.php:705`.
- Workspace transfer confirm via `gf_workspace_admin.inc.php:374-375`
  (`member_item.html:16`).
Fix both with a real JS-string encoder or by passing values as `data-`
attributes read in JS. Add an escaping-context lint/test and audit all
`onclick` string builders.

### CC-7 · Observability & audit logging (P1)
No traceability on security-sensitive actions.
- Workspace audit log (transfer/claim/role/invite) with actor, target, ts, IP.
- Supabase `sync_runs`/`monitor_runs` tables so pg_cron-fired runs are
  auditable; structured logs with request id + per-table counts.
- Admin-visible "last sync at / last error" for the app importer.

---

## 3. Module-by-module backlog

Each subsection is self-contained: current state, the ranked fixes (with
`file:line`), expansions, tests, and docs. Items marked ⟶CC roll up into a
cross-cutting initiative in §2.

### 3.1 Onboarding — `modules/gfunnel/onboarding_module/` + `gf_onboarding.php`
**State:** 🔴 Prototype. Two parallel flows coexist (a native persons-form page
and an external SPA over three JSON endpoints). Ships **no install SQL**, the
login hook is unregistered *and* mutates a by-value copy, the iframe template is
missing, and the "HMAC-signed" write endpoints don't verify the HMAC.

**Fixes**
- **P0** Ship `install/sql/{install,uninstall,enable,disable}.sql`; the module
  is non-functional without `gfo_onboarding_data` + the `bx_persons_data`
  columns (`config.php:41-49`, `installer.php:19-24`). ⟶CC-4
- **P0** Remove hardcoded HMAC fallback secret; fail closed
  (`BxGfunnelOnbModule.php:10`). ⟶CC-3
- **P0** Write endpoints don't call `verifyToken`; they session-gate a
  cross-origin caller whose cookie won't be sent → legit saves 403
  (`save-onboarding.php:22-27`, `complete-onboarding.php:22-27`). Authenticate
  via the HMAC token and derive `user_id` from the verified payload.
- **P1** Login hook is dead: `onLoginAfter` reassigns a local `$aExtras` and
  never writes back (`:28,44`), and no `alerts` handler is registered — so
  onboarding is never enforced (`config.php:63-71`).
- **P2** Token replay (no nonce/jti, no `action` claim check, `:194-218`); PII
  base64'd into the iframe URL query string (`:182,241`); missing
  `onboarding_iframe.html` template (`:243`); swallowed exception with no log +
  missing null-check (`save-onboarding.php:29-35`).

**Expansions:** reconcile the two flows into one canonical model; input
whitelisting for `industry`/`team_size`/`budget_range`/`timeline` + length caps;
rate-limit `verify-token.php`; resume-from-step (columns exist, unused);
admin/reporting surface over `gfo_onboarding_data`.

**Tests:** token sign/verify roundtrip, tampered sig, expired (300s boundary),
**replay**, wrong `action`, forged-under-fallback; endpoint 403 path; partial
save→resume upsert idempotency; install/uninstall schema; hook sets relocate on
incomplete users only.

**Docs:** `docs/api/API_CAPABILITIES.md:145-149` wrongly claims all three
endpoints are HMAC-signed — correct it; document the schema and which flow is
canonical.

---

### 3.2 Applications / Hub — `modules/gfunnel/applications/`, `gf_applications.php`, `inc/gf_app_blocks.inc.php`
**State:** 🟡 Functionally mature (CSRF token on writes, same-origin, a
workspace-membership IDOR guard all present) but with real secret-handling,
authorization-tier, and sync-correctness gaps. 1,414-line shared renderer;
Supabase is source of truth, MySQL is a read mirror.

**Fixes**
- **P0** Hardcoded Supabase URL + long-lived anon JWT in source
  (`:300-301`) and plaintext write key in `gf_app_config.v` (`:302,520`).
  ⟶CC-3
- **P0** No role tier on shared-workspace mutations: any *joined* member can
  add/remove apps from the whole workspace's shared collection
  (`gfAppHandleUserAction`, `:723-733`). Add owner/admin gate.
- **P1** Import is a state-changing **GET** guarded only by Referer/Origin
  (`gfAppRunImport`, `:414-424`) — require POST. ⟶CC-1
- **P1** Import correctness: silent write failures counted as success
  (`:383-411,457-458`); pull importer is upsert-only so deletes never
  propagate → orphan cards (`:437-455`); no atomicity on mid-page fetch failure
  (`:306-322`).
- **P2** `gfAppMember()` re-runs membership queries 2-3×/request (memoize);
  fragile unescaped `<title>` invariant (`:780`); single-column `category`
  mirror loses the source's JSON-array faceting (`:1114`).

**Expansions:** role/permission tier + `added_by` attribution; FULLTEXT or
Supabase-side search (current `LIKE %q%` full-scans, `:1113`); in-block AJAX
search/pager (today it navigates out of Studio); short-TTL cache on
list/count/category; admin "last sync / last error" row. ⟶CC-7

**Tests:** admin import auth matrix (`hash_equals` token, same-origin, 403);
coerce correctness; upsert idempotency + the silent-failure/no-delete gaps;
directory pagination clamp / empty / category chips / badge precedence;
`gfAppWorkspaceAllowed` owner/joined/non-member IDOR guard; CSRF/Origin
rejection; detail slug-vs-id fallback + 404.

**Docs:** `directory-sync-runbook.md` wrongly says the PHP "Sync now" propagates
DELETE (it's upsert-only); document `gf_app_config` keys + rotation;
`applications-hub-status.md:50` overstates the auth model (membership ≠
role-based write authz).

---

### 3.3 Home + SEO landings — `home.php`, `inc/gf_home_blocks.inc.php`, `modules/gfunnel/home/`, `gf_business|services|marketplace|resources.php`
**State:** 🟡 A polished marketing shell over a mostly-hollow data layer.
Renderers escape output consistently and honor a "no fake data" discipline in
most places, but the module classes are empty stubs, the "live" tables are never
provisioned, and the standalone page and SEO pages duplicate a lot of markup.

**Fixes**
- **P0** Dead markers burning queries: `home.php:74-82` computes
  `hero_stats`/`departments_grid`/`featured_section`/`marketplace_section`/
  `resources_section`/`version_badge`, but `page_home.html` has **no
  placeholders** for them — every hit runs `SHOW TABLES`+`COUNT/SELECT` and
  discards it, and those sections are absent from the default homepage.
- **P0** Backing tables never provisioned: `gf_content_objects` (`inc:721`),
  `gf_community_posts` (`inc:756`), `gf_departments` (`inc:28`) are created by
  no installer — "live" mode is unreachable on a fresh deploy. ⟶CC-4 (+ build
  the Supabase→MySQL sync for departments/content/community like directory-apps).
- **P1** Duplicate array key `business_url` (`home.php:47` overwritten by `:62`)
  → wrong Audiences link; broken `__cookies_url__` footer marker
  (`page_home_blocks.html:131`).
- **P1** Duplicated renderer logic across five files: business/product/resources
  card loops re-implemented in the SEO pages instead of reusing the `inc`
  renderers (`gf_business.php:85`, `gf_marketplace.php:81`,
  `gf_resources.php:56`); five divergent footers; duplicated `<head>`/pager
  `<style>`. Collapse into shared renderers.
- **P2** Hardcoded/unverifiable marketing claims re-introduced via
  `gfHomeCaseStudies()` (`:1053`), `gfHomeTrustBar()` (`:1087`),
  `gfHomeVersionBadge()` (`:173`) — violates the no-fake-data rule; location
  search matches a serialized blob (`gf_business.php:43`); block↔standalone
  parity gap (no blocks for ai_console/hubs/founder_quote/case_studies/trust_bar);
  `home.php` doesn't self-check `gf_root_home`.

**Expansions:** real data wiring + ship `docs/sql` DDL through the installer;
per-request memoization + short-TTL cache for counts/featured (repeated
`COUNT(*)` on ~31k-row `mz_listing_entries`); unify render modes so every
section is Studio-editable; Verified Vendors / VA-talent model (unbuilt).

**Tests:** **marker-completeness** assertion over both templates (catches the
three bugs above); unit tests for `gfHomeListingLocation`/`gfHomeExcerpt`/
`gfHomeCountPlus`/`gfHomeFeedDate`/`gfHomeSeasonalVisible`; escaping regression
per card; kill-switch 404s; `r.php` routing + safe fallback.

**Docs:** document the four SEO routes in `SEO_PAGE_MAP.md` (canonical/robots/
JSON-LD, and the directly-servable `/gf_*.php` duplicate-crawl risk); note the
manual-DDL requirement and the block-parity gap in `gfunnel-home-blocks.md`.

---

### 3.4 Sitemap — `modules/gfunnel/sitemap/`
**State:** 🟢 The most mature module: streaming writer, file locking, atomic
temp+rename, index-splitting past 45k URLs, per-entry lastmod, graceful 503.
Gaps are refinements, not foundational.

**Fixes**
- **P0** On-demand rebuild blocks the triggering request: `serve()` runs
  `generate()` synchronously when stale (`:192-193`, `set_time_limit(0)`), so a
  crawler hitting a cold cache pays the full multi-second/minute build inline.
  Serve stale-while-revalidate; regenerate via cron/background only.
- **P0** View-page `noindex`/guest-visibility not honored for entries:
  `collectModuleEntries` (`:368-470`) never checks the module's view page in
  `sys_objects_page` (only system pages are checked, `:342`) — contradicts
  `SEO_PAGE_MAP.md:86`.
- **P1** In-memory dedup (`aSeenLoc`, `:52,665`) holds every URL → real scaling
  ceiling; chunk split counts URLs (`:43`) but not the 50 MB/file byte limit;
  add byte tracking + disk-backed/removed dedup.
- **P1** `robots.php` hardcodes host-root paths (`:18-38`), wrong for sub-path
  installs; add gzip + `If-Modified-Since`/304 to `serve()` (`:216-217`).
- **P2** lastmod inconsistency (UTC vs server-local, `:460` vs `:596`) and
  missing on most URLs; flat `priority 0.7` for all module entries (`:455`).

**Expansions:** image/video sitemap extensions for photos/videos/albums;
per-section changefreq/priority config; `/m/<controller>` + custom root page
coverage; hreflang alternates; news sitemap.

**Tests:** `renderUrl` XML-escaping; chunk rotation at the boundary → index
generated + stale chunks pruned; concurrent `generate()` (loser returns false);
empty-site valid `urlset`; `serve()` HTTP contract (404 disabled, 503+Retry-After
cold, out-of-range chunk); coverage integration asserting noindex view pages
excluded.

**Docs:** document the in-memory dedup ceiling + byte caveat; that generation
writes `sys_seo_links` (side effect); fix the noindex doc/behavior mismatch; add
a "cron down + large site" runbook.

---

### 3.5 Workspaces + Admin — `workspaces.php`, `inc/gf_workspace_admin.inc.php`
**State:** 🟡 Functionally rich (roles, ownership transfer/claim, an invite
system with codes/expiry/max-uses/affiliate attribution) on UNA-native
primitives with no core edits — but **pre-production on security**, and
`workspaces.php` is a 903-line root script doing DDL, codegen, rendering, and 8
inline handlers.

**Fixes**
- **P0** Every state-changing handler is an unauthenticated **GET** with no CSRF
  token: `set_role` (`:780`), `transfer_to` (`:788`), `invite_reset`
  (`:799,881`), `claim_ws` (`:815`), `accept/decline_invite` (`:862,875`), code
  redemption (`:837`); the role form is literally `method="get"`. ⟶CC-1
- **P1** JS-string injection: a member's display name flows via
  `bx_html_attribute` into `onclick="return confirm('__member_name__')"`
  (`gf_workspace_admin.inc.php:374-375` / `member_item.html:16`) → authenticated
  stored XSS. ⟶CC-6
- **P1** Admin privilege escalation: `gfWsSetMemberRole` (`:169`) lets any
  delegated admin mint/strip other admins; only the owner row is locked
  (`:177`). Enforce owner-only admin management.
- **P1** Non-atomic invite use (`:237` check then `:274-280` write → TOCTOU past
  `max_uses`) and non-atomic ownership claim (`:299` check then `:313` move →
  double claim). Use conditional single-statement updates + affected-rows.
- **P2** Ownership change is a 3-write sequence with no transaction (`:205-217`);
  permanent-invite get-or-create can double-insert (`:312-328`); invite `role`
  never whitelisted at redemption (a row with `role='admin'` auto-grants admin,
  `:258`); `gf_ws` IDOR trust ⟶CC-2; extract invite/renderer logic out of the
  903-line file.

**Expansions:** capability-scoped roles (billing/invites/content) + "admins
can't manage admins"; audit log ⟶CC-7; member list pagination/search + a
**remove-member** action (none exists today, capped at 1000, `:132`); full
invite management UI (email/limited/admin, list/revoke); leave/archive/delete a
workspace; rate-limit join-by-code.

**Tests:** full authz matrix {owner,admin,member,non-member,anon} × actions;
invite lifecycle (pending→accepted, permanent stays pending, expiry/limit,
email-bound, cookie-carry-after-register, affiliate-once); transfer edge cases +
partial-failure rollback; parallel limited-invite/claim races; CSRF regression.

**Docs:** `workspace-administration.md:57` wrongly claims POST-redirect-GET —
correct it; add a security section (CSRF, code entropy, `gf_ws` boundary,
admin-vs-owner); document the invite subsystem; mark the unbuilt claim
verification gate as TODO.

---

### 3.6 Timer — `gf_timer.php`
**State:** 🟡 Feature-rich (per-workspace billing overrides, rounding, activity
trail, overlap trimming, week-over-week) but a monolithic 743-line procedural
script: runtime DDL, billing math, transport, and dispatch in one file, with no
module/permission boundary.

**Fixes**
- **P0** No CSRF and no HTTP-method enforcement on any mutating action
  (`:42-45,507`); `bx_get` reads GET, so `<img src="gf_timer.php?a=discard">` or
  `?a=entry_del&id=N` silently destroys data. ⟶CC-1
- **P0** Concurrent-timer race: `start` does stop-then-`INSERT running=1`
  (`:514-539`) with no lock and no unique constraint on `(account_id,running)`;
  two starts leave orphaned running rows and mis-total (`:441`). Add
  partial-unique / transactional start.
- **P1** Overlap re-billing is asymmetric: save/add re-bill only the touched row
  (`:618,636`), delete/discard re-bill nothing (`:640-645`) → billed totals
  drift (money-correctness bug).
- **P1** Client/server timezone mismatch: server-local `strtotime('today')`
  (`:377`) and manual-entry parsing (`:603`) vs browser-local JS → wrong daily
  totals + mis-timed entries. Store per-account TZ.
- **P1** Extract into a proper UNA module with migrations (stop per-request DDL,
  `:48,55-99`; make the billing engine testable). ⟶CC-4
- **P2** Uncapped running-timer duration → `decimal(12,2)` amount overflow
  (`:351`); `workspace_id` not validated against membership (`:591`) ⟶CC-2;
  start-from-task ignores the task's workspace (`:533`).

**Expansions:** reporting/history/date-range + CSV/PDF export + invoicing
(entries hard-capped at `LIMIT 20`, `:395`); link entries to real
projects/CRM/applications; idle detection + keep/discard; edit audit trail;
team/manager rollups + per-workspace access control.

**Tests:** start→stop→resume with exactly one running row under concurrent
starts; duration math + 7-day clamp + `parseDuration`; timezone round-trip for a
non-server-TZ user; overlap add/edit/delete symmetric re-billing; activity-merge
down-scaling + caps; settings validation.

**Docs:** no dedicated doc exists — write one covering the action protocol,
billing/overlap/rounding rules, the table schema, and a **privacy note**: the
tracker records every URL a user visits while tracking (currently undocumented).

---

### 3.7 Auth / Bug / Menu / Sidebar — `gf_auth.php`, `gf_bug.php`, `gf_menu.php`, `gf_sidebar.php`
**State:** 🟡 Auth is a careful re-skin of UNA's real forms (keeps UNA's CSRF).
Bug/menu/sidebar are account-scoped with prepared statements and URL-scheme
validation, but share the CSRF-via-GET gap, and sidebar has a JS-context XSS.

**Fixes**
- **P0** `gf_menu.php:66` & `gf_sidebar.php:97`: mutating actions accept **GET**
  with no CSRF token — `?a=reset` wipes a victim's menu/rail in one click.
  ⟶CC-1
- **P0** `gf_sidebar.php:141` → `BxBaseFunctions.php:705`: JS-context XSS in the
  iframe `onclick` (`bx_html_attribute` is the wrong escaper); chainable to
  cross-user stored XSS via the GET/CSRF gap. ⟶CC-6
- **P0/P1** `gf_bug.php:34`: CSRF on the report submit (has a POST check but no
  token) — forces uploads/mail on behalf of a logged-in user. ⟶CC-1
- **P1** `gf_bug.php:145`: no captcha/rate-limit → authenticated spam + 40 MB
  uploads can exhaust disk; `storage/gf_bug/` served publicly by unguessable
  name only (`:180-200`), URL leaks via the notification email.
- **P1** `gf_auth.php:54`: malformed `login_form` params
  (`['no_auth_buttons no_join_text']` — one bare string) so the flags never
  apply, likely duplicating social/join UI on the login page.
- **P2** menu/sidebar reorder payloads unbounded (`gf_menu.php:69`) → mild DoS;
  sidebar `icon` regex allows spaces → extra CSS classes (`:147`); `gf_ws`
  trust ⟶CC-2.

**Expansions:** Bug → captcha + per-account cooldown, server-side image
re-encode, admin triage grid (status stored, unused), authenticated download
proxy for attachments. Menu → icons/colors + sections + item cap. Sidebar →
iframe `sandbox` + embeddable-host allowlist, atomic reorder. Auth → i18n the
hardcoded strings, error/flash surface.

**Tests:** CSRF rejection per action (menu/sidebar/bug); XSS unit test on
`_getGfSidebarCustomItem` with `'`/`"`/`</script>`/backslash titles; URL-scheme
matrix; upload MIME-spoof/oversize/`is_uploaded_file` bypass; auth
`gf_auth_pages=off` + join-vs-login regression; cross-account scoping.

**Docs:** no dedicated docs for `gf_auth` (theming, `gf_auth_pages`) or `gf_bug`
(`gf_bug_reports` schema, storage, notifications); menu/sidebar settings +
schemas undocumented outside inline comments.

---

### 3.8 Supabase edge functions — `supabase/functions/{sync-directory-apps-to-mysql,fetch-app-tutorials}/index.ts`
**State:** 🟡 Two standalone Deno functions, no shared code, no tests. Sync is a
one-way PG→MySQL mirror (webhook + backfill); fetch is a YouTube→`app_tutorials`
monitor. Functional MVP.

**Fixes**
- **P0** `fetch-app-tutorials`: YouTube quota overrun — each app = 100 units;
  `limit` caps at 100 (`:100`) so one run can burn the 10k/day quota, and the
  documented 6h `limit:40` schedule = 16k units/day (over quota). Add unit
  budgeting + 403-`quotaExceeded` early abort (`:54,59,153`).
- **P0** `sync`: `backfill` upserts row-by-row (`:171`) across multiple tables
  (`:194-197`) with no transaction and ~10k sequential awaits → times out +
  leaves MySQL half-synced. Batch multi-row inserts + checkpoint/resume.
- **P1** Duplicate-insert race: TOCTOU dedup (`:129-131` read, `:148` insert)
  with no unique constraint on `(platform_app_id, youtube_video_id)` (DDL
  `gf_directory_apps.mysql.sql:52-64`). Add the constraint.
- **P1** No orphan cleanup / out-of-order resurrection: backfill is upsert-only
  (`:171`) so PG-deleted rows persist; a stale UPDATE after a DELETE re-inserts
  (`:212`). Add reconciliation + `updated_at`/version guard.
- **P2** Wrong app-selection: orders by `popularity_rank` (`:115-118`) despite
  comment/docs saying "apps with fewest/no tutorials" → re-searches the same
  apps forever, wasting quota. `asNum` → NaN into `db.execute` (`:45,155`);
  internal error leakage via `String(e)` (`:215`); non-record payloads → 400
  retry loop with no dead-letter; timing-unsafe secret compare (`:180,94`)
  ⟶CC-3.

**Expansions:** bidirectional-sync guard/marker to prevent echo loops;
incremental/delta sync with a watermark cursor + periodic orphan reconciliation;
structured logging + `sync_runs`/`monitor_runs` tables ⟶CC-7; dead-letter +
replay; a shared `_shared/` module (auth/env/logging/YouTube client) before
adding the planned `app_docs` / `app_help_articles` monitors.

**Tests:** per-table column/coercer mapping + upsert idempotency; DELETE +
record-less/TRUNCATE no-loop + orphan reconcile; malformed YouTube payloads +
`asNum` NaN guard; quota-exceeded early abort; backfill paging/batch/resume at
~10k; auth constant-time; concurrent-run no-double-insert.

**Docs:** document the required unique constraint; fix the runbook's
"idempotent" claim (false for backfill) and the content-pipeline's "few/no
tutorials" selection claim (code orders by popularity); document YouTube quota
math vs schedule; add failure/alerting/dead-letter + orphan-cleanup runbook
sections; make Vault-stored secrets mandatory, not a parenthetical.

---

## 4. Phased execution plan

Ordered so foundations land before the features that depend on them, and so the
recurring security defects are closed once (cross-cutting) rather than N times.

### Phase 0 — Foundations (unblocks everything) · ~1 sprint
- **CC-5** Test harness + CI (`composer.json`, PHPUnit, Deno tests, CI workflow,
  marker-completeness check).
- **CC-4** Install-SQL / anti-runtime-DDL convention + the two blocking gaps
  (onboarding schema, homepage backing tables) so fresh deploys work.
- **CC-3** Secrets out of source/DB + rotation (fastest-to-exploit exposure).

### Phase 1 — Security hardening (P0 sweep) · ~1–2 sprints
- **CC-1** CSRF + POST-only across menu/sidebar/timer/bug/workspaces.
- **CC-6** Fix both JS-context XSS holes + escaping lint.
- **CC-2** `gf_ws` membership trust boundary.
- Module P0s that aren't purely cross-cutting: workspace admin escalation
  (3.5); applications role-tier + committed creds (3.2); onboarding HMAC
  verification (3.1); sitemap inline-rebuild timeout (3.4); Supabase quota +
  non-atomic backfill (3.8); timer concurrent-timer race (3.6).

### Phase 2 — Correctness & data integrity (P1) · ~2–3 sprints
- Timer billing-drift + timezone (3.6); applications import correctness +
  delete-propagation (3.2); workspace invite/claim atomicity (3.5); home
  dead-markers + duplicated renderers + wrong links (3.3); Supabase orphan
  reconciliation + dedup constraint (3.8); sitemap noindex + scaling (3.4).
- **CC-7** Audit logging + sync observability.

### Phase 3 — Refactor & consolidation (P1/P2) · ~2–3 sprints
- Extract timer into a real module (3.6); extract invite/renderer logic from
  `workspaces.php` (3.5); collapse home/SEO duplicated renderers + block↔
  standalone parity (3.3); shared `_shared/` for Supabase fns (3.8).

### Phase 4 — Expansions (P2 / product) · ongoing
- Timer reporting/exports/invoicing; applications faceted search + in-block
  AJAX + caching; workspace capability-roles + member management UI + invite UI;
  home real-data wiring + Verified Vendors/VA model; sitemap image/video +
  hreflang; new Supabase content monitors (docs/help).

---

## 5. Backlog at a glance

| Module | P0 | P1 | P2 | Tests | Docs |
|---|---|---|---|---|---|
| Onboarding | 3 | 1 | 4 | 6 areas | 1 correction + schema |
| Applications | 2 | 2 | 3 | 5 areas | 3 corrections |
| Home + SEO | 2 | 2 | 4 | 5 areas | 3 gaps |
| Sitemap | 2 | 2 | 2 | 6 areas | 4 gaps |
| Workspaces | 1 | 3 | 4 | 5 areas | 4 gaps |
| Timer | 2 | 3 | 3 | 6 areas | new doc |
| Auth/Bug/Menu/Sidebar | 3 | 2 | 2 | 6 areas | 2 new docs |
| Supabase fns | 2 | 2 | 3 | 7 areas | 6 corrections |
| **Cross-cutting** | CC-1,3,4,5,6 | CC-2,7 | — | harness | this doc |

**Bottom line:** the platform's feature depth is real; the work ahead is to make
it *safe, verifiable, and deployable*. Do Phase 0–1 before shipping anything new.

---

*Maintenance: when a module changes materially, update its §3 subsection in the
same commit (CLAUDE.md §8). This roadmap is generated from a point-in-time audit;
re-run the per-module audits after each phase to re-baseline.*
