# Application Hub — functional status & QA

Feature-by-feature state of the GFunnel Application Hub after the full audit pass.
Legend: ✅ working · ⚙️ works, needs config/data · ⚠️ known limitation · 🔒 security.

Architecture: `gf_applications.php` (thin dispatcher) → shared renderers in
`inc/gf_app_blocks.inc.php` → skin `template/css/gf_applications.css` +
`template/js/gf_applications.js`. The same renderers power the in-shell Studio
block via the `gfunnel_applications` module (`serviceBlockHub/Apps/Directory`).
Data: Supabase `directory_apps`/`platform_apps` (source of truth) →
MySQL mirror (`gf_directory_apps` …) via the server-side pull importer.

---

## Routes & pages
| Route | Status | Notes |
|---|---|---|
| `/applications` (Apps tab) | ✅ | Hero, Core Applications, hub cards, scroll cue. |
| `/marketplace/applications` (Directory) | ✅ | Search, category chips, pagination. |
| `/application/<slug>` (detail) | ✅ | About, use cases, automation, categories, departments; tutorials/docs/help light up when data exists. |
| `/applications?gfa_action=admin` | ⚙️ | Admin-only. Editing apps needs a Supabase secret key (below). |
| Module blocks (Studio Page Builder) | ✅ | Hub / App Launcher / App Directory. Renders in the workspace shell. |
| Endpoints `?gfa_action=import\|add\|remove\|list` | ✅ | JSON; POST + same-origin for writes. |

## Apps tab
| Feature | Status | Notes |
|---|---|---|
| Welcome hero (rotating banners) | ✅ | 4 banners cross-fade; dots. |
| Live date subtitle | ✅ | Ticks every 30s (JS); tagline is the no-JS fallback. |
| Tab bar (Apps/Marketplace) | ✅ | Route links (standalone) / client toggle (block); ARIA roles + `aria-selected`. |
| Video Chat popover | ✅ | Google Meet (closes on launch); custom link validated (`https://` only) + saved; `aria-expanded`. |
| Core Applications launcher | ⚙️ | Workspace-scoped; **launches the app** when it has an `app_url`, else opens the detail page. See launch-URL gap below. |
| "Add App" tile | ✅ | → App Directory. |
| Empty state (signed-in, no apps) | ✅ | Workspace-aware "add your first app" hint. |
| Hub cards | ✅ | Learning→Resources, Software→Services, Help→Resources — **all admin-configurable** (Admin → Settings → Hub card links). |

## Marketplace / Directory
| Feature | Status | Notes |
|---|---|---|
| Server search (`?q=`) + category chips + pagination | ✅ | Name/description; page clamped; filters preserved. |
| Instant client filter | ⚠️ | Filters the **current page** only (48/page); Enter runs the full server search. |
| App cards (logo, name→detail, visit-site, add/remove, badges) | ✅ | Add/remove is workspace-scoped, optimistic with revert. |
| "Add New App" card | ✅ | Admin→Manage Apps, member→Services (request), guest→sign up. |

## Personalization (workspace-scoped)
| Feature | Status | Notes |
|---|---|---|
| Per-workspace app list (`gf_workspace_apps`) | ✅ | Shared per workspace; personal list per account. |
| Add/remove | ✅ | Server-side; guests fall back to localStorage. |
| Workspace membership enforced | 🔒✅ | `?gf_ws=` is validated against real membership (owner/joined) before scoping — no cross-tenant access. |
| CSRF/same-origin on writes | 🔒✅ | Add/remove + import + all admin POSTs require same-origin; admin forms carry a per-session CSRF token. |

## Admin (Manage & Settings)
| Feature | Status | Notes |
|---|---|---|
| Settings (Supabase URL / anon / **secret** / import token / hub links) | ✅ | Stored in `gf_app_config`. |
| Sync now | ✅ | Pulls the directory from Supabase into the mirror. |
| Manage Apps — edit name/launch URL/logo/category/featured | ⚙️ | Writes back to Supabase then updates the mirror. **Requires the Supabase secret key** (Admin → Settings). |
| Add a new app | ⚙️ | Same; blank slug lets Postgres generate it. |

## Data pipeline
| Piece | Status | Notes |
|---|---|---|
| Pull importer (Supabase→MySQL) | ✅ | Admin "Sync now" or `?gfa_action=import&key=<token>` for cron. Always returns JSON. |
| Sitemap coverage | ✅ | `/applications`, `/marketplace/applications`, and every `/application/<slug>`. |
| YouTube tutorial monitor | ⚙️ | `supabase/functions/fetch-app-tutorials` — deploy + `YOUTUBE_API_KEY` + schedule (see `docs/directory-content-pipeline.md`). |

---

## Known gaps that need data/config (not code bugs)
1. **Launch URLs are sparse.** Only ~13 of ~5,000 apps have an `app_url`; the rest
   correctly fall back to the detail page. Populate `app_url` via **Admin → Manage
   Apps** (or a bulk enrichment) to make "click to launch" work broadly.
2. **Tutorials / docs / help are empty** until the content monitor runs (needs a
   YouTube API key).
3. **Editing apps needs the Supabase secret key** entered once in Admin → Settings.
4. **Auto-sync**: point a cron at `?gfa_action=import&key=<import_token>` to keep
   the mirror fresh (otherwise use the admin "Sync now" button).

## Remaining minor polish (tracked, non-blocking)
- Block-mode Directory search/pagination/category links navigate to the standalone
  `/marketplace/applications` (leaves the Studio page).
- Instant filter is page-local (see above).
- Detail page: empty right column when an app has no rich `platform_apps` row;
  "Related" shows slugs rather than resolved names.
- `prefers-reduced-motion` not yet honored for hero/scroll animations.
- No explicit "clear secret key" affordance (replace-only).
