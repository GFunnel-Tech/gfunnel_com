# Homepage Audit — Processes-First Public Homepage (Step 0)

**Branch:** `claude/processes-homepage-698a31`
**Repo:** `GFunnel-Tech/gfunnel_com`
**Read plane audited:** Supabase project `yjneucgsaayyzoyxrlnb` (name: "GFunnel", Postgres 17)
**Date:** 2026-07-21
**Status:** Blocking Step 0 deliverable — complete. Read this before any UI work.

> **Headline finding (must read):** The reference PR (`PR_Processes_Homepage.md`) was written for a
> **Next.js + Tailwind + shadcn/ui app reading Supabase directly**. **This repo is not that app.**
> `gfunnel_com` is the **UNA / PHP source-of-truth platform** itself. It renders the public homepage
> server-side from a UNA HTML template and reads its **own MySQL**, not Supabase at request time.
> Every instruction in the reference doc that assumes an App Router route, a Supabase server client,
> `shadcn/ui`, or anon Supabase reads has to be **translated** into the PHP/UNA idiom. This audit does
> that translation and reports what can actually ship here today.

---

## Part A — Repo audit

### A1. Stack & the existing homepage (this is the big one)

- **Stack:** UNA (PHP) social-platform framework. No Next.js, no Tailwind, no `shadcn/ui`, no App Router,
  no `package.json` at repo root. Pages are PHP entry scripts (`home.php`, `splash.php`,
  `workspaces.php`, `gf_applications.php`) that render UNA HTML templates via
  `BxDolTemplate::parseHtmlByName()`.
- **There is already a full, polished public homepage.** Root routing (`index.php`) sends logged-out
  visitors to `home.php`, which renders `template/page_home.html` (679 lines) with its own
  header/footer chrome, styled by `template/css/gf_home.css` (1,424 lines) and
  `template/js/gf_home.js` (217 lines). It is **not** a blank slate — it is a complete marketing page
  ("Every Tool. Every Action. One Platform.") with hero, an interactive app-directory demo, workspaces,
  integrations marquee, a module grid, a three-paths band, a "why it works" section, founder quote,
  stats, case studies, CTA, and footer.
- **Implication:** "Build the new homepage" here means **reworking `template/page_home.html` +
  `gf_home.css` + `gf_home.js` in place**, conforming to the design system already there — **not**
  creating a new route from scratch. (Slime Mold: reuse the existing page, do not fork a parallel one.)

### A2. Design system (source of truth = the repo, and it is already consistent)

Resolved from `template/page_home.html` + `template/css/gf_home.css`:

| Token | Value | Notes |
|---|---|---|
| Shell / nav background | `#0A0A1E`-family dark (hero uses a dark navy gradient) | Shell/hero only |
| Content background | light (`#f6f7f9` / white sections, `gfh-sec-soft` = soft gray) | **Content is light with white cards** — matches the enforced theming rule |
| Accent | orange `#F97316` (with `#EA580C` / `#FB923C` ramp) | Buttons, links, brand mark |
| Headings | **Sora** (600/700/800) | Loaded from Google Fonts in `page_home.html` |
| Body | **DM Sans** (400–700) | " |
| Mono | **Space Mono** | " |
| Component prefix | `gfh-` (e.g. `gfh-container`, `gfh-btn`, `gfh-sec`, `gfh-card`) | Self-contained CSS namespace |

**Theming verdict:** The homepage runs **light content on white cards with a dark shell/hero** — exactly
the rule the reference doc enforces. **Do not** introduce dark content areas here. (Note: the sibling
directory page `gf_applications.php` renders dark as a deliberate standalone "console" surface; that is
its own page and is **not** a precedent to darken the homepage.)

**Theme toggle:** none exists. Do not add one.

**Per-department color:** the live `departments` table **does** populate `color` + `color_bg` (see B1),
so per-card tints are available if we render departments.

### A3. Reusable building blocks

- **Homepage chrome** (nav, hero, footer, back-to-top, reveal-on-scroll, count-up stats, mobile menu,
  interactive demos) all live in `template/page_home.html` + `gf_home.js` and are the components to reuse.
- **Global search:** the homepage header already has a real search form posting to
  `searchKeyword.php` (`name=keyword`). There is **no ⌘K command palette** in this repo; the "global
  search (⌘K)" from the reference is aspirational. Reuse the existing search form; do **not** invent a
  palette.
- **Directory page:** `gf_applications.php` (routed at `/applications` via `r.php`) is a
  Star-Head-style app directory (list + per-app detail) and is the natural link target for
  app/marketplace surfaces.
- **Markers / DAL:** data flows into templates as `__marker__` placeholders filled by the PHP entry
  script (see `getGfHomePageCode()` in `home.php`). Data access is **UNA `BxDolDb`** against MySQL —
  e.g. `gf_applications.php` does `SELECT COUNT(*) FROM gf_directory_apps`. There is **no Supabase
  client in the PHP runtime.**

### A4. Data-access reality (critical constraint)

- The public homepage is **server-rendered PHP reading MySQL**. It **cannot** read Supabase at request
  time (no creds, no client in-repo).
- The **only** Supabase data that reaches this repo's MySQL today is **directory apps**, via the
  established one-way sync: Supabase Postgres → Database Webhook → Edge Function
  `supabase/functions/sync-directory-apps-to-mysql` → MySQL mirror tables
  (`gf_directory_apps`, `gf_platform_apps`, `gf_app_tutorials`, `gf_app_docs`, `gf_app_help_articles`).
  Mirror tables auto-create on first load (`gfDirEnsureTables`) and empty-state cleanly.
- Therefore, for any homepage block to show **live** data it must be sourced from either (a) a MySQL
  mirror that already exists, or (b) UNA-native MySQL tables, or (c) a **new** sync built on the
  established pattern. Anon-Supabase-read from the browser is **not** how this page works.

### A5. Deploy target

- Per `.htaccess` + the PHP entry-point layout, this is a **PHP/Apache VPS deploy** (UNA hosting), not
  Vercel. The App-Router/Vercel deploy path in the reference doc does **not** apply. Changes ship by
  merging to `main` and the VPS serving the updated PHP/template/CSS/JS.

---

## Part B — Data audit (live, Supabase `yjneucgsaayyzoyxrlnb`)

Row counts and RLS verified live via the Supabase MCP. **RLS matters two ways:** (1) whether an
anon browser could read it (it can't, this page is PHP), and (2) it signals which tables are considered
"public" by the platform.

### B1. Departments — 14 rows, **not** the doc's "9", all org-scoped, richly populated

- `select count(*) from departments` → **14** (doc guessed ~14 total / 9 canonical).
- `departments where organization_id is null` → **0**. **There is no org-null "canonical" set.** All 14
  belong to a **single** org (`59a6b7d2-30ed-4e7e-8dc7-ea450b6bbdab`).
- Columns `name, subtitle, icon, color, color_bg, sort_order` are **all populated.** Icons are Tabler
  (`ti-*`) names; colors are hex; `color_bg` are rgba tints. Ordered by `sort_order` 1–14:
  1 Strategy · 2 Marketing · 3 Sales / Revenue · 4 Product · 5 Creative · 6 Technology ·
  7 AI & Automation · 8 Delivery / Fulfillment · 9 Operations · 10 Data & Analytics ·
  11 Customer Success & Support · 12 People / HR · 13 Finance · 14 Legal, Risk & Security.
- **This is a 14-department operating model, richer than and different from the blueprint's "9-department
  framework."** Any "9 departments" language in the reference is superseded by this live 14-set.
- **RLS:** `departments_select_members` — SELECT allowed only to members of the owning org
  (`auth.uid()` ∈ `org_members`). **Not anon-readable.** **Not** mirrored to MySQL.
  → To render on a public PHP page, the department set must be either embedded as static real content
  (the 14 names/subtitles/icons/colors are a stable framework, snapshotted from this live table) **or**
  synced to MySQL like directory apps. It cannot be live-read anon.

### B2. Per-department process **count** — no real source (confirmed)

- `templates` = **1 row**, `modules` = **1 row** (stubs). There is **no populated "process" entity** and
  no `template_modules`/`workspace_modules` population that yields a real per-department count.
- **Verdict: there is no honest count source. Omit the count entirely** (per the no-fake-data rule). Do
  not print "N processes" on department cards.

### B3. "5,000+ apps" hero stat — **real and reachable** ✅

- `platform_apps` = **5,000**; `directory_apps` = **5,010**.
- **RLS:** both are genuinely public — `directory_apps` "Public read" (`qual=true`) and
  `platform_apps_public_read` (`qual=true`).
- **Reachable here:** `directory_apps` is **already mirrored to MySQL** as `gf_directory_apps`, and
  `gf_applications.php` already computes `SELECT COUNT(*) FROM gf_directory_apps` live. So the homepage
  can show a **real** app count from the same MySQL mirror — no invented number needed.

### B4. Community feed — `posts_posts` = 13, **not anon-readable, not mirrored**

- 13 rows, all `status = 'active'`. Columns include author/content/timestamps (UNA-shaped, mirrored from
  UNA source of truth into Supabase).
- **RLS:** enabled with **0 policies** → **not anon-readable.** Also **not** mirrored into this repo's
  MySQL as a homepage-ready table.
- Note: this repo *is* UNA, so a native UNA timeline/posts module exists (`modules/boonex/posts/`), but
  wiring a public homepage feed to it is separate work with its own privacy considerations.
  → **Render an honest empty state or DEFER;** do not fabricate posts.

### B5. News & guides — `content_objects` = 22 (17 published / 5 draft), **not anon-readable, not mirrored**

- `content_objects` is the canonical platform-content type: **17 `published`**, 5 `draft`. Has
  `title, slug, excerpt, cover_image_url, published_at, status, content_type` — everything a news/guides
  rail needs. (`content_articles`/`articles` from the doc are not the canonical type here.)
- **RLS:** enabled with **0 policies** → **not anon-readable.** **Not** mirrored to MySQL.
  → **DEFER** until a `content_objects` → MySQL mirror is built (same pattern as directory apps), **or**
  render a designed empty state. Do not fabricate news rows.

### B6. Earn / vendor / marketplace — UNA-side, **DEFER**

- `aff_referrals` = 248 (RLS on, 0 anon policies), `organizations` = mirrored, `org_members` present.
  Affiliate attribution + org/vendor data is the **UNA write plane**; this read-only public homepage
  cannot (and should not) drive Earn flows.
  → The **Earn** path renders as a card but its destination is **feature-flagged "coming soon"** unless
  an existing public Partners/affiliate route is confirmed. (`home.php` currently links a
  `__partners_url__` → `affiliate-activities` page; verify it is public before treating it as live.)

---

## Part C — "What can be added now" (the Step 0 deliverable)

Per-block ship decision for the homepage rework. **BUILD NOW** = real data reachable from the PHP/MySQL
runtime today. **STUB** = render with an honest empty state. **DEFER** = needs a new Supabase→MySQL sync
or the UNA write plane.

| Block | Data source (reachable path) | Status | Notes |
|---|---|---|---|
| **Design system / shell / nav / hero / footer** | `template/page_home.html`, `gf_home.css`, `gf_home.js` | **BUILD NOW** | Already exists; rework in place. Light content + white cards + dark shell. Reuse existing search form (no ⌘K palette exists). |
| **Hero app stat ("5,000+ apps")** | `SELECT COUNT(*) FROM gf_directory_apps` (MySQL mirror) | **BUILD NOW** | Real, already computed by `gf_applications.php`. Wire the same count into the hero/stat; do not hardcode. |
| **Department grid (process domains)** | `departments` (14 rows, live) — via **static real snapshot** of name/subtitle/icon/color/color_bg/sort_order | **BUILD NOW (static real) / or DEFER for live** | 14 real departments, fully populated, ordered by `sort_order`. Not anon-readable & not mirrored, so live-read needs a new sync. Recommend embedding the **real 14** now (stable framework data) and flagging the sync as a follow-up. Map `ti-*` icons to inline SVGs to match the page. |
| **Per-department process count** | none | **OMIT** | No real count source (`modules`/`templates` are stubs). Do not print counts. |
| **Three-path band — Build** | Processes/Modules surface → `__market_url__` / `/applications` | **BUILD NOW** | Links to existing directory/marketplace routes. |
| **Three-path band — Buy** | Marketplace / directory (`/applications`, `gf_applications.php`) | **BUILD NOW** | Real route exists. |
| **Three-path band — Earn** | Partners / affiliate | **DEFER / feature-flag** | UNA write plane. Render card; gate destination "coming soon" unless the public partners route is confirmed live. |
| **Community feed** | `posts_posts` (13) | **STUB → empty state / DEFER** | Not anon-readable, not mirrored. Empty state now; live needs a UNA-posts wire or a sync. |
| **News & guides** | `content_objects` (17 published) | **DEFER (or STUB)** | Not anon-readable, not mirrored. Needs a `content_objects`→MySQL sync (directory-apps pattern) before it can render live; empty state until then. |
| **Platform version badge ("4.8.3 LIVE")** | no confirmed real platform-version source in the public path | **OMIT unless real** | Only add if a real version value is available; otherwise leave out (no fake badge). |
| **Footer legal — DMCA / Terms / Privacy / Cookies** | UNA pages (`terms`, `privacy`) exist; **DMCA/Cookies** not confirmed | **BUILD NOW (Terms/Privacy) + add DMCA** | Add `/legal/dmca` link; create the page/route if missing and flag it. |
| **SEO metadata + JSON-LD** | `home.php` already sets title/description/canonical + Organization/WebSite JSON-LD | **BUILD NOW** | Extend existing metadata; do not duplicate. |

### Design direction (updated per stakeholder review)

The first pass used the spec's light skin. On review, the stakeholder confirmed the
intended look is the **dark Star-Head "hub"** (matching the reference and the repo's own
dark directory page `gf_applications.php`), with the **GFunnel orange accent** (not
cyan). The homepage was therefore re-skinned dark: dark hero with the GFunnel wordmark +
a centered global search + a live-status badge, a Star-Head-style **catalogs row**
(Departments / Marketplace / Integrations / Templates / Learn / Partners) **plus** the
14-department grid, then News & Community, then footer. This intentionally overrides the
spec's "light content only" rule (§1) at the stakeholder's direction; counts and feeds
remain real-or-omitted exactly as below.

### Build outcome (what shipped in this branch)

The homepage (`template/page_home.html` + `template/css/gf_home.css` + `home.php`):

- **Nav/hero** reframed to the operating model; hero stat line uses the **real** app
  count (from `gf_directory_apps`, conservative "N,000+", omitted if empty) + the real
  department count.
- **Department grid** — 14 real process domains. Reads a **`gf_departments` MySQL
  table when present** (load `docs/sql/gf_departments.mysql.sql`), else an identical
  static real snapshot. No process counts.
- **Build / Buy / Earn band** — Build→join, Buy→`/applications`; **Earn is
  feature-flagged** (`gf_home_earn_enabled`, off by default → "coming soon").
- **Community + News feeds** — real rows when a backing MySQL table exists
  (`gf_community_posts` / `gf_content_objects`), else designed **empty states**. No fake rows.
- **Removed** every fabricated section (workspaces picker, modules split, stats band,
  case studies) and stripped invented counts ("1,000+", "100+", "5,000+ entrepreneurs")
  from kept illustrative sections.
- **Footer** — Terms / Privacy / **DMCA** / Cookies; dead in-page anchors repaired.

### Open TODOs / gaps (documented, not faked)
- **T1 — Department live sync:** to make the department grid live (vs. static-real), build a
  `departments` → MySQL mirror on the `sync-directory-apps-to-mysql` pattern. Until then the grid uses a
  real static snapshot of the 14 departments.
- **T2 — Content sync:** to make News & guides live, build a `content_objects` (status='published') →
  MySQL mirror. Until then: empty state.
- **T3 — Community feed:** decide between a native UNA posts wire vs. a `posts_posts` mirror; until then:
  empty state.
- **T4 — Earn route:** confirm whether `affiliate-activities` (or another partners page) is public; if
  not, feature-flag the Earn destination.
- **T5 — Existing placeholder data:** DONE — fabricated sections removed and invented counts stripped in
  this branch. The interactive app-directory mock is kept as a clearly-framed browser-window illustration
  (labeled `gfunnel.com/workspace/app-directory`), not presented as live data.
- **T6 — Legal CMS pages:** the footer now links `page.php?i=dmca` and `page.php?i=cookies` (same
  permalink pattern as Terms/Privacy). The **DMCA and Cookies page content must be created in UNA CMS**
  for those links to resolve to real pages — the routes resolve, the content is a CMS task.
- **T7 — Live feeds:** to light up the Community/News columns with real rows, load/sync
  `gf_community_posts` (from `posts_posts`) and `gf_content_objects` (published `content_objects`) into
  MySQL on the directory-apps sync pattern; the renderers already read them when present.
